<?php
/**
 * Libera o repasse das parcelas marcadas como anteriores a publicacao (decisao de 06/11/2026). Roda uma vez, por
 * app/database/migrations/2026-11-06-repasse-parcelas-anteriores.php.
 *
 * - Parcela em aberto: passa a gerar repasse ao ser quitada, pelo fluxo normal.
 * - Sem colaborador: copia da conta, do repasse do contrato (somando 100%) ou, em conta de atendimento, usa 100% para o profissional.
 * - Recebida em parte antes: guarda o que entrou em recebido_sem_repasse; a quitacao repassa so a proporcao do restante.
 * - Recebida a partir de 23/10/2026: gera agora o repasse daquele recebimento.
 * - Paga antes de 23/10/2026: continua sem repasse.
 */
class RepasseParcelasAnterioresService
{
    const CORTE = '2026-10-23';
    // ligacao perdida ao salvar a conta em 15/07/2026, conforme system_change_log; refeita por decisao do usuario de 06/11/2026
    const RELIGAR = [1144 => 1233, 1148 => 1682];

    /**
     * @param bool  $aplicar false desfaz tudo no fim e so devolve o relatorio
     * @param int[] $contas  limita a estas contas (usado nos testes)
     */
    public static function executar(bool $aplicar, array $contas = []): array
    {
        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            $filtro = $contas ? ' AND c.id IN (' . implode(',', array_map('intval', $contas)) . ')' : '';
            $parcelas = $c->query("SELECT l.id, l.conta_id, l.valor, l.saldo, l.dt_pagamento, l.extrato_id,
                                          c.contrato_id, c.atendimento_id, c.profissional_id, c.escritorio_id, c.total_conta
                                     FROM lancamento l JOIN conta c ON c.id = l.conta_id
                                    WHERE c.tipo_conta_id = " . (int) TipoConta::RECEBER . " AND l.anterior_publicacao
                                      AND COALESCE(l.cancelado, 'N') NOT IN ('S', 'T')
                                      AND (l.dt_pagamento IS NULL OR l.dt_pagamento >= '" . self::CORTE . "'){$filtro}
                                    ORDER BY l.conta_id, l.parcela, l.id")->fetchAll(PDO::FETCH_OBJ);

            $r = ['parcelas' => count($parcelas), 'em_aberto' => 0, 'valor_em_aberto' => 0.0, 'recebidas' => 0, 'colaboradores' => [], 'sem_colaborador' => [],
                  'parciais' => [], 'repasses' => [], 'avisos' => []];

            foreach ($parcelas as $p) {
                $origem = self::garantirColaboradores($c, $p);
                $r['colaboradores'][$origem] = ($r['colaboradores'][$origem] ?? 0) + 1;
                if ($origem === 'nenhum' && (float) $p->valor > 0) { $r['sem_colaborador'][] = (int) $p->id; }

                $c->prepare('UPDATE lancamento SET anterior_publicacao = false WHERE id = ?')->execute([$p->id]);

                if ($p->dt_pagamento === null) {
                    $r['em_aberto']++;
                    $r['valor_em_aberto'] += (float) $p->valor;
                    self::guardarRecebidoEmParte($c, $p, $r);
                } else {
                    $r['recebidas']++;
                    self::gerarRepasseDoRecebimento($c, $p, $r);
                }
            }

            if (!$contas) {
                $religacao = self::religar(self::RELIGAR);
                $r['parcelas_religadas'] = $religacao['religadas'];
                $r['avisos'] = array_merge($r['avisos'], $religacao['avisos']);
            }

            $r['valor_em_aberto'] = round($r['valor_em_aberto'], 2);
            $r['aplicado'] = $aplicar;
            if ($aplicar) { TTransaction::close(); } else { TTransaction::rollback(); }
            return $r;
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    /**
     * Devolve as parcelas ao pagamento do contrato de onde vieram. So liga se conta e pagamento forem do mesmo contrato e a parcela
     * estiver sem ligacao; rodar de novo nao muda nada. Usa a transacao aberta.
     *
     * @param array $mapa conta => pagamento do contrato
     */
    public static function religar(array $mapa): array
    {
        $c = TTransaction::get();
        $r = ['religadas' => 0, 'avisos' => []];
        $confere = $c->prepare('SELECT 1 FROM conta co JOIN contrato_pagamento_parcela p ON p.contrato_id = co.contrato_id WHERE co.id = ? AND p.id = ?');
        $liga = $c->prepare('UPDATE lancamento SET contrato_parcela_id = ? WHERE conta_id = ? AND contrato_parcela_id IS NULL');
        foreach ($mapa as $conta => $pagamento) {
            $confere->execute([(int) $conta, (int) $pagamento]);
            if (!$confere->fetchColumn()) {
                $r['avisos'][] = "Conta {$conta} e pagamento {$pagamento} nao sao do mesmo contrato; ligacao nao refeita.";
                continue;
            }
            $liga->execute([(int) $pagamento, (int) $conta]);
            $r['religadas'] += $liga->rowCount();
        }
        return $r;
    }

    /** Devolve de onde vieram os colaboradores da parcela: ja_tinha, conta, contrato, profissional ou nenhum. */
    private static function garantirColaboradores(PDO $c, $p): string
    {
        $st = $c->prepare('SELECT count(*) FROM lancamento_profissional WHERE lancamento_id = ?');
        $st->execute([$p->id]);
        if ((int) $st->fetchColumn() > 0) { return 'ja_tinha'; }

        $origem = 'conta';
        $st = $c->prepare('SELECT pessoa_id, percentual::numeric AS percentual FROM conta_profissional WHERE conta_id = ? ORDER BY id');
        $st->execute([$p->conta_id]);
        $divisao = $st->fetchAll(PDO::FETCH_OBJ);

        if (!$divisao && $p->contrato_id) {
            $st = $c->prepare('SELECT pessoa_id, percentual::numeric AS percentual FROM contrato_repasse WHERE contrato_id = ? ORDER BY id');
            $st->execute([$p->contrato_id]);
            $repasse = $st->fetchAll(PDO::FETCH_OBJ);
            $soma = array_sum(array_map(function ($d) { return (float) $d->percentual; }, $repasse));
            if ($repasse && abs($soma - 100) < 0.011) { $divisao = $repasse; $origem = 'contrato'; }
        }
        if (!$divisao && $p->atendimento_id && $p->profissional_id) {
            $divisao = [(object) ['pessoa_id' => (int) $p->profissional_id, 'percentual' => 100]];
            $origem = 'profissional';
        }
        if (!$divisao) { return 'nenhum'; }

        // a edicao da conta redistribui a partir de conta_profissional: sem isso, salvar a conta apagaria os colaboradores
        if ($origem !== 'conta') { self::gravarDaConta($c, (int) $p->conta_id, (float) $p->total_conta, $divisao, (int) $p->escritorio_id); }
        if ((float) $p->valor > 0) { self::gravarDaParcela($c, (int) $p->id, (float) $p->valor, $divisao, (int) $p->escritorio_id); }
        return $origem;
    }

    private static function gravarDaConta(PDO $c, int $contaId, float $total, array $divisao, int $escritorioId): void
    {
        $st = $c->prepare('SELECT count(*) FROM conta_profissional WHERE conta_id = ?');
        $st->execute([$contaId]);
        if ((int) $st->fetchColumn() > 0) { return; }
        $ins = $c->prepare('INSERT INTO conta_profissional (conta_id, pessoa_id, percentual, valor) VALUES (?, ?, ?, ?)');
        foreach (self::dividir($total, $divisao, $escritorioId) as [$pessoa, $percentual, $valor]) {
            $ins->execute([$contaId, $pessoa, $percentual, $valor]);
        }
    }

    private static function gravarDaParcela(PDO $c, int $lancamentoId, float $valor, array $divisao, int $escritorioId): void
    {
        $ins = $c->prepare('INSERT INTO lancamento_profissional (lancamento_id, pessoa_id, percentual, valor) VALUES (?, ?, ?, ?)');
        foreach (self::dividir($valor, $divisao, $escritorioId) as [$pessoa, $percentual, $parte]) {
            $ins->execute([$lancamentoId, $pessoa, $percentual, $parte]);
        }
    }

    /** Divide em centavos; a diferenca do arredondamento fica com o escritorio ou, sem ele na divisao, com o ultimo. */
    private static function dividir(float $valor, array $divisao, int $escritorioId): array
    {
        $centavos = (int) round($valor * 100);
        $escritorio = RepasseColaboradorService::pessoaDoEscritorio($escritorioId);
        $linhas = [];
        $distribuido = 0;
        $quemFica = count($divisao) - 1;
        foreach ($divisao as $i => $d) {
            if ((int) $d->pessoa_id === $escritorio) { $quemFica = $i; }
        }
        foreach ($divisao as $i => $d) {
            if ($i === $quemFica) { $linhas[$i] = [(int) $d->pessoa_id, (float) $d->percentual, 0]; continue; }
            $parte = (int) round($centavos * (float) $d->percentual / 100);
            $distribuido += $parte;
            $linhas[$i] = [(int) $d->pessoa_id, (float) $d->percentual, $parte / 100];
        }
        $linhas[$quemFica][2] = ($centavos - $distribuido) / 100;
        ksort($linhas);
        return $linhas;
    }

    private static function guardarRecebidoEmParte(PDO $c, $p, array &$r): void
    {
        $valor = (float) $p->valor;
        $saldo = $p->saldo === null ? null : (float) $p->saldo;
        if ($saldo === null || $saldo >= $valor || $saldo <= 0) { return; }

        $recebido = round($valor - $saldo, 2);
        $c->prepare('UPDATE lancamento SET recebido_sem_repasse = ? WHERE id = ?')->execute([$recebido, $p->id]);
        $r['parciais'][] = ['parcela' => (int) $p->id, 'conta' => (int) $p->conta_id, 'valor' => $valor, 'recebido' => $recebido, 'saldo' => $saldo];

        $st = $c->prepare('SELECT count(*) FROM extrato WHERE lancamento_id = ? AND data_lancamento >= ?');
        $st->execute([$p->id, self::CORTE]);
        if ((int) $st->fetchColumn() > 0) {
            $r['avisos'][] = "Parcela {$p->id}: tem recebimento parcial a partir de " . self::CORTE . '; conferir o repasse dele.';
        }
    }

    private static function gerarRepasseDoRecebimento(PDO $c, $p, array &$r): void
    {
        if (!$p->extrato_id) {
            $r['avisos'][] = "Parcela {$p->id}: recebida sem registro no extrato; repasse nao gerado.";
            return;
        }
        $criadas = RepasseColaboradorService::gerarPorRecebimento((int) $p->id, (int) $p->extrato_id, (float) $p->valor, (string) $p->dt_pagamento, true);
        $st = $c->prepare('SELECT COALESCE(SUM(valor), 0) FROM repasse_colaborador WHERE lancamento_receber_id = ?');
        $st->execute([$p->id]);
        $r['repasses'][] = ['parcela' => (int) $p->id, 'conta' => (int) $p->conta_id, 'contas_a_pagar' => count($criadas), 'valor' => round((float) $st->fetchColumn(), 2)];
    }
}
