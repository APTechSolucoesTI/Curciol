<?php
/**
 * Repasses a colaboradores.
 *
 * Todo recebimento numa conta a receber com repasse na parcela (lancamento_profissional) gera, para cada
 * colaborador, uma conta a pagar ligada a origem (tabela repasse_colaborador):
 *   - pagamento parcial: valor proporcional ao recebido (valor do colaborador na parcela x recebido / valor da parcela);
 *   - quitacao: o restante do colaborador na parcela (com os ajustes de acrescimo/desconto da quitacao);
 *   - levantamento (Gerar financeiro): e uma quitacao; o lancamento_profissional ja tem o repasse de cada um.
 * A parte do escritorio (pessoa com o CNPJ do escritorio da conta) nao gera conta a pagar.
 *
 * Pagamento em lote: varias contas de UM colaborador quitadas de uma vez, uma linha por conta no extrato,
 * todas com o mesmo lote_pagamento. Estornar o lote reabre as contas e tira as linhas do extrato.
 *
 * Todas as funcoes usam a transacao aberta pelo chamador (as de tela abrem a propria quando chamadas sem).
 */
class RepasseColaboradorService
{
    const ESTORNE_PRIMEIRO = 'O repasse ao colaborador %s já foi pago (%s). Estorne o pagamento ao colaborador antes.';

    /**
     * Ordem dos colaboradores da planilha do escritorio: Curciol, Jac, Kcur, Dani, Ivan, Juan, Kbça (ou Campos),
     * Alliance, Estrutura (cadastro provisorio: LUMES). Quem nao esta aqui vem depois, por nome.
     */
    const ORDEM_COLABORADORES = [
        'CURCIOL SOCIEDADE DE ADVOGADOS', 'JOSÉ ALMIR CURCIOL', 'KLEBER CURCIOL', 'DANIELLE DOS SANTOS MARQUES CURCIOL',
        'IVAN PAULO FIORANI', 'JUAN WILLIAN DE OLIVEIRA', 'KBÇA', 'CAMPOS',
        'ALLIANCE ADMINISTRAÇÃO COMÉRCIO E ENGENHARIA LTDA', 'LUMES GESTAO IMOBILIARIA LTDA',
    ];

    private static $posicoes = null;

    /** pessoa_id => posicao na ORDEM_COLABORADORES (pelo nome, sem diferenca de acentuacao NFC/NFD). */
    private static function posicoes(): array
    {
        if (self::$posicoes !== null) { return self::$posicoes; }
        $propria = self::abrir();
        try {
            $nomes = array_map(function ($n) { return class_exists('Normalizer') ? (Normalizer::normalize($n, Normalizer::FORM_C) ?: $n) : $n; }, self::ORDEM_COLABORADORES);
            $marcas = implode(', ', array_fill(0, count($nomes), 'normalize(?, NFC)'));
            $st = TTransaction::get()->prepare("SELECT id, normalize(nome, NFC) AS nome FROM pessoa WHERE normalize(nome, NFC) IN ({$marcas})");
            $st->execute($nomes);
            self::$posicoes = [];
            foreach ($st->fetchAll(PDO::FETCH_OBJ) as $p) {
                self::$posicoes[(int) $p->id] = array_search($p->nome, $nomes, true);
            }
            if ($propria) { TTransaction::close(); }
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
        return self::$posicoes;
    }

    /**
     * Ordena itens pelo colaborador na ordem da planilha. $pessoa e $nome extraem o pessoa_id e o nome de cada item
     * (o nome desempata quem nao esta na lista).
     */
    public static function ordenarPorColaborador(array $itens, callable $pessoa, ?callable $nome = null): array
    {
        $pos = self::posicoes();
        $fim = count(self::ORDEM_COLABORADORES);
        usort($itens, function ($a, $b) use ($pos, $fim, $pessoa, $nome) {
            $pa = $pos[(int) $pessoa($a)] ?? $fim;
            $pb = $pos[(int) $pessoa($b)] ?? $fim;
            if ($pa !== $pb) { return $pa <=> $pb; }
            return $nome ? strcmp((string) $nome($a), (string) $nome($b)) : ((int) $pessoa($a) <=> (int) $pessoa($b));
        });
        return $itens;
    }

    // ------------------------------------------------------------------ geracao

    /**
     * Gera as contas a pagar dos colaboradores para um recebimento da parcela $lancamentoReceberId.
     * @param int    $extratoId   registro do extrato do recebimento
     * @param float  $valorRecebido valor que entrou neste recebimento
     * @param string $data        data do recebimento (Y-m-d)
     * @param bool   $quitacao    true = quitacao da parcela (gera o restante); false = pagamento parcial (proporcional)
     * @return int[] ids das contas a pagar criadas
     */
    public static function gerarPorRecebimento(int $lancamentoReceberId, int $extratoId, float $valorRecebido, string $data, bool $quitacao, ?int $levantamentoId = null): array
    {
        $c = self::conexao();
        $parcela = Lancamento::find($lancamentoReceberId);
        if (!$parcela) { return []; }
        $receber = Conta::find((int) $parcela->conta_id);
        if (!$receber || (string) $receber->tipo_conta_id !== (string) TipoConta::RECEBER) { return []; }

        // conta a receber do levantamento quitada de novo (depois de desquitada): os repasses voltam ligados a ficha
        if ($levantamentoId === null) {
            $st = $c->prepare('SELECT id FROM levantamento_financeiro WHERE conta_receber_id = ? ORDER BY id DESC LIMIT 1');
            $st->execute([(int) $receber->id]);
            $levantamentoId = ($ficha = $st->fetchColumn()) ? (int) $ficha : null;
            if ($levantamentoId && $quitacao && $extratoId) {
                $c->prepare('UPDATE levantamento_financeiro SET extrato_id = ? WHERE id = ? AND extrato_id IS NULL')->execute([$extratoId, $levantamentoId]);
            }
        }

        $profissionais = LancamentoProfissional::where('lancamento_id', '=', $parcela->id)->orderBy('id')->load();
        if (!$profissionais) { return []; }

        $escritorioPessoa = self::pessoaDoEscritorio((int) $receber->escritorio_id);
        $config = FinanceiroConfiguracao::repasse();
        if (!$config['categoria_id'] || !$config['documento_id']) {
            throw new Exception('Configure a categoria e o documento padrão do repasse em Configurações › Financeiro › Configuração do financeiro.');
        }
        $data = substr($data, 0, 10);
        $vencimento = date('Y-m-d', strtotime($data . ' +' . (int) $config['dias'] . ' days'));
        $baseParcela = (float) $parcela->valor;
        $criadas = [];

        foreach ($profissionais as $lp) {
            $pessoaId = (int) $lp->pessoa_id;
            if ($pessoaId <= 0 || $pessoaId === $escritorioPessoa) { continue; }

            if ($quitacao) {
                $total = (float) $lp->valor + self::ajustes($c, (int) $lp->id);
                $st = $c->prepare('SELECT COALESCE(SUM(valor), 0) FROM repasse_colaborador WHERE lancamento_receber_id = ? AND pessoa_id = ?');
                $st->execute([$parcela->id, $pessoaId]);
                $valor = round($total - (float) $st->fetchColumn(), 2);
            } else {
                $valor = ($baseParcela > 0) ? round((float) $lp->valor * $valorRecebido / $baseParcela, 2) : 0.0;
            }
            if ($valor <= 0) { continue; }

            $criadas[] = self::criarContaPagar($receber, $parcela, $pessoaId, $valor, $vencimento, $config, [
                'extrato_recebimento_id' => $extratoId ?: null,
                'levantamento_financeiro_id' => $levantamentoId,
                'percentual' => $lp->percentual,
                'valor_recebido' => round($valorRecebido, 2),
                'data_recebimento' => $data,
            ]);
        }
        return $criadas;
    }

    /**
     * Desfaz os repasses gerados pelo recebimento (registro do extrato) $extratoId: apaga as contas a pagar.
     * Recusa se alguma ja foi paga ao colaborador.
     */
    public static function estornarPorRecebimento(int $extratoId): void
    {
        if ($extratoId <= 0) { return; }
        $c = self::conexao();
        $st = $c->prepare('SELECT id FROM repasse_colaborador WHERE extrato_recebimento_id = ? ORDER BY id FOR UPDATE');
        $st->execute([$extratoId]);
        $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        foreach ($ids as $id) { self::exigirRepasseEmAberto($c, $id); }
        foreach ($ids as $id) { self::apagarRepasse($c, $id); }
    }

    /** Motivo que impede desfazer o recebimento $extratoId (repasse ja pago), ou null. */
    public static function motivoBloqueioRecebimento(int $extratoId): ?string
    {
        if ($extratoId <= 0) { return null; }
        $c = self::conexao();
        $st = $c->prepare('SELECT id FROM repasse_colaborador WHERE extrato_recebimento_id = ? ORDER BY id');
        $st->execute([$extratoId]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
            try { self::exigirRepasseEmAberto($c, (int) $id); } catch (Exception $e) { return $e->getMessage(); }
        }
        return null;
    }

    /**
     * Grava a divisao dos colaboradores na parcela (lancamento_profissional), base dos repasses no recebimento.
     * $profissionais: [[pessoa_id, percentual], ...]. Valor = parcela x %; se os % somam 100, o ultimo fica com os centavos.
     * Substitui a divisao que a parcela tiver. Usa a transacao aberta.
     */
    public static function dividirParcela(int $lancamentoId, array $profissionais): void
    {
        $parcela = Lancamento::find($lancamentoId);
        if (!$parcela) { throw new Exception('Parcela não encontrada.'); }
        $linhas = [];
        foreach ($profissionais as $p) {
            $pessoa = (int) ($p[0] ?? 0);
            $pct = round((float) ($p[1] ?? 0), 8);
            if ($pessoa > 0 && $pct > 0) { $linhas[] = [$pessoa, $pct]; }
        }
        LancamentoProfissional::where('lancamento_id', '=', $lancamentoId)->delete();
        if (!$linhas) { return; }

        $baseCentavos = (int) round((float) $parcela->valor * 100);
        $fechaCem = abs(array_sum(array_column($linhas, 1)) - 100) < 0.01;
        $acumulado = 0;
        $ultimo = count($linhas) - 1;
        foreach ($linhas as $i => [$pessoa, $pct]) {
            $centavos = ($fechaCem && $i === $ultimo) ? $baseCentavos - $acumulado : (int) round($baseCentavos * $pct / 100);
            $acumulado += $centavos;
            $lp = new LancamentoProfissional();
            $lp->lancamento_id = $lancamentoId;
            $lp->pessoa_id = $pessoa;
            $lp->percentual = $pct;
            $lp->valor = $centavos / 100;
            $lp->store();
        }
    }

    // ------------------------------------------------------------------ protecoes

    /** A conta e um repasse a colaborador? Usa a transacao aberta (ou abre a propria). */
    public static function ehRepasse(int $contaId): bool
    {
        if ($contaId <= 0) { return false; }
        $propria = self::abrir();
        try {
            $st = TTransaction::get()->prepare('SELECT 1 FROM repasse_colaborador WHERE conta_pagar_id = ?');
            $st->execute([$contaId]);
            $r = (bool) $st->fetchColumn();
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /** Conta de repasse nao pode ser editada, excluida nem ter parcela cancelada (so pelo estorno do recebimento). */
    public static function exigirNaoRepasse(int $contaId): void
    {
        if (self::ehRepasse($contaId)) {
            throw new Exception('Conta de repasse a colaborador: ela acompanha o recebimento que a gerou. Para corrigir, desquite a parcela recebida (ou desfaça o financeiro do levantamento).');
        }
    }


    // ------------------------------------------------------------------ consulta (tela e relatorio)

    /**
     * Repasses conforme os filtros. $f: pessoa_id, processo (texto), requisicao_id, contrato (texto), situacao (aberto|pago|''),
     * periodo_tipo (recebimento|vencimento|pagamento), data_de, data_ate, lote_id. Usa a transacao aberta.
     * @return object[] (id, conta_pagar_id, colaborador, valor, vencimento, data_recebimento, pago_em, lote_id, compensado, origem...)
     */
    public static function listar(array $f): array
    {
        $c = self::conexao();
        [$where, $par] = self::filtros($f);
        $sql = "SELECT r.id, r.conta_pagar_id, r.conta_receber_id, r.pessoa_id, pe.nome AS colaborador,
                       r.valor, r.percentual, r.valor_recebido, r.data_recebimento, r.levantamento_financeiro_id,
                       lp.dt_vencimento AS vencimento, lp.dt_pagamento AS pago_em,
                       xp.lote_pagamento_id AS lote_id, xp.compensado, xp.data_compensacao,
                       lr.parcela, cr.total_parcelas, cr.descricao AS receber_descricao,
                       cr.contrato_id, ct.numero AS contrato_numero,
                       COALESCE(cr.requisicao_pagamento_id, lf.requisicao_pagamento_id) AS requisicao_id,
                       (SELECT trp.nome FROM requisicao_pagamento rq JOIN tipos_requisicao_pagamento trp ON trp.id = rq.tipos_requisicao_pagamento_id
                         WHERE rq.id = COALESCE(cr.requisicao_pagamento_id, lf.requisicao_pagamento_id)) AS requisicao_tipo,
                       e3.data_deposito,
                       COALESCE(pr.numero_cnj_numero, pr.numero_outro) AS processo_numero,
                       COALESCE(cli.nome, pc.nome, pcr.nome) AS cliente
                  FROM repasse_colaborador r
                  JOIN pessoa pe ON pe.id = r.pessoa_id
                  JOIN lancamento lp ON lp.id = r.lancamento_pagar_id
             LEFT JOIN extrato xp ON xp.id = lp.extrato_id
                  JOIN lancamento lr ON lr.id = r.lancamento_receber_id
                  JOIN conta cr ON cr.id = r.conta_receber_id
             LEFT JOIN contrato ct ON ct.id = cr.contrato_id
             LEFT JOIN processo pr ON pr.id = cr.processo_id
             LEFT JOIN levantamento_financeiro lf ON lf.id = r.levantamento_financeiro_id
             LEFT JOIN requisicao_pagamento_etapa3 e3 ON e3.id = lf.requisicao_pagamento_etapa3_id
             LEFT JOIN requisicao_pagamento_cliente rpc ON rpc.id = lf.requisicao_pagamento_cliente_id
             LEFT JOIN pessoa cli ON cli.id = rpc.pessoa_id
             LEFT JOIN pessoa pcr ON pcr.id = cr.pessoa_id
             LEFT JOIN pessoa pc ON pc.id = (SELECT cp.cliente_id FROM contrato_pessoa cp WHERE cp.contrato_id = cr.contrato_id ORDER BY cp.id LIMIT 1)
                 {$where}
              ORDER BY pe.nome, r.data_recebimento, r.id";
        $st = $c->prepare($sql);
        $st->execute($par);
        return $st->fetchAll(PDO::FETCH_OBJ);
    }

    /** Texto de origem de um repasse (para a tela e o relatorio). */
    public static function origem(object $r): string
    {
        $partes = [];
        if ($r->cliente) { $partes[] = $r->cliente; }
        if ($r->processo_numero) { $partes[] = 'Processo ' . $r->processo_numero; }
        if ($r->requisicao_id) {
            $partes[] = 'Requisição' . (!empty($r->requisicao_tipo) ? ' ' . $r->requisicao_tipo : '') . ($r->data_deposito ? ' (depósito de ' . date('d/m/Y', strtotime($r->data_deposito)) . ')' : '');
        } elseif ($r->contrato_id) {
            $partes[] = 'Contrato' . ($r->contrato_numero ? ' ' . $r->contrato_numero : '') . ((int) $r->total_parcelas > 1 ? ' - parcela ' . (int) $r->parcela . '/' . (int) $r->total_parcelas : '');
        } elseif (trim((string) ($r->receber_descricao ?? '')) !== '') {
            // conta a receber avulsa (sem processo, requisicao nem contrato): a descricao dela
            $partes[] = trim((string) $r->receber_descricao) . ((int) $r->total_parcelas > 1 ? ' - parcela ' . (int) $r->parcela . '/' . (int) $r->total_parcelas : '');
        }
        return implode(' · ', $partes);
    }

    // ------------------------------------------------------------------ auxiliares

    private static function criarContaPagar(Conta $receber, Lancamento $parcela, int $pessoaId, float $valor, string $vencimento, array $config, array $extra): int
    {
        $userId = TSession::getValue('userid');
        $descricao = 'Repasse - ' . trim((string) $receber->descricao)
            . (((int) $receber->total_parcelas > 1) ? ' - parcela ' . (int) $parcela->parcela . '/' . (int) $receber->total_parcelas : '');

        $pagar = new Conta();
        $pagar->tipo_conta_id = TipoConta::PAGAR;
        $pagar->pessoa_id = $pessoaId;
        $pagar->categoria_conta_id = $config['categoria_id'];
        $pagar->tipo_documento_financeiro_id = $config['documento_id'];
        $pagar->escritorio_id = (int) $receber->escritorio_id;
        $pagar->contrato_id = $receber->contrato_id ?: null;
        $pagar->processo_id = $receber->processo_id ?: null;
        $pagar->requisicao_pagamento_id = $receber->requisicao_pagamento_id ?: null;
        $pagar->data_emissao = $extra['data_recebimento'];
        $pagar->total_conta = $valor;
        $pagar->total_parcelas = 1;
        $pagar->quitada = 'N';
        $pagar->descricao = mb_substr($descricao, 0, 500);
        $pagar->conta_origem_id = $receber->id;
        $pagar->proximo_vencimento_lancamento = $vencimento;
        $pagar->criacao_user_id = $userId;
        $pagar->store();

        $lanc = new Lancamento();
        $lanc->conta_id = $pagar->id;
        $lanc->parcela = 1;
        $lanc->valor = $valor;
        $lanc->valor_total = $valor;
        $lanc->dt_vencimento = $vencimento;
        $lanc->tipo_pagamento_id = TipoPagamento::TRANSFERENCIA;
        $lanc->store();

        $r = new RepasseColaborador();
        $r->pessoa_id = $pessoaId;
        $r->conta_pagar_id = $pagar->id;
        $r->lancamento_pagar_id = $lanc->id;
        $r->conta_receber_id = $receber->id;
        $r->lancamento_receber_id = $parcela->id;
        $r->extrato_recebimento_id = $extra['extrato_recebimento_id'];
        $r->levantamento_financeiro_id = $extra['levantamento_financeiro_id'];
        $r->percentual = $extra['percentual'];
        $r->valor_recebido = $extra['valor_recebido'];
        $r->valor = $valor;
        $r->data_recebimento = $extra['data_recebimento'];
        $r->data_criacao = date('Y-m-d H:i:s');
        $r->criacao_user_id = $userId;
        $r->store();

        return (int) $pagar->id;
    }

    private static function exigirRepasseEmAberto(PDO $c, int $repasseId): void
    {
        $st = $c->prepare('SELECT pe.nome, l.dt_pagamento, x.lote_pagamento_id,
                                  (SELECT COUNT(*) FROM extrato e WHERE e.lancamento_id = l.id) AS extratos
                             FROM repasse_colaborador r JOIN pessoa pe ON pe.id = r.pessoa_id
                             JOIN lancamento l ON l.id = r.lancamento_pagar_id LEFT JOIN extrato x ON x.id = l.extrato_id
                            WHERE r.id = ?');
        $st->execute([$repasseId]);
        $r = $st->fetch(PDO::FETCH_OBJ);
        if ($r && (!empty($r->dt_pagamento) || (int) $r->extratos > 0)) {
            $como = $r->lote_pagamento_id ? 'lote nº ' . (int) $r->lote_pagamento_id : 'pago em ' . ($r->dt_pagamento ? date('d/m/Y', strtotime($r->dt_pagamento)) : 'parte');
            throw new Exception(sprintf(self::ESTORNE_PRIMEIRO, $r->nome, $como));
        }
    }

    private static function apagarRepasse(PDO $c, int $repasseId): void
    {
        $st = $c->prepare('SELECT conta_pagar_id, lancamento_pagar_id FROM repasse_colaborador WHERE id = ?');
        $st->execute([$repasseId]);
        $r = $st->fetch(PDO::FETCH_OBJ);
        if (!$r) { return; }
        $c->prepare('DELETE FROM repasse_colaborador WHERE id = ?')->execute([$repasseId]);
        $c->prepare('DELETE FROM lancamento WHERE conta_id = ?')->execute([(int) $r->conta_pagar_id]);
        $c->prepare('DELETE FROM conta WHERE id = ?')->execute([(int) $r->conta_pagar_id]);
    }

    private static function ajustes(PDO $c, int $lancamentoProfissionalId): float
    {
        $st = $c->prepare("SELECT COALESCE(SUM(CASE WHEN tipo = 'A' THEN valor WHEN tipo = 'D' THEN -valor ELSE 0 END), 0) FROM lancamento_profissional_ajuste WHERE lancamento_profissional_id = ?");
        $st->execute([$lancamentoProfissionalId]);
        return (float) $st->fetchColumn();
    }

    /** Pessoa que e o proprio escritorio (mesmo CNPJ), ou 0. */
    public static function pessoaDoEscritorio(int $escritorioId): int
    {
        $c = self::conexao();
        $st = $c->prepare("SELECT p.id FROM escritorio e JOIN pessoa p
                              ON regexp_replace(COALESCE(p.cpf_cnpj, ''), '\\D', '', 'g') = regexp_replace(COALESCE(e.cnpj, ''), '\\D', '', 'g')
                           WHERE e.id = ? AND regexp_replace(COALESCE(e.cnpj, ''), '\\D', '', 'g') <> '' ORDER BY p.id LIMIT 1");
        $st->execute([$escritorioId]);
        return (int) $st->fetchColumn();
    }

    private static function filtros(array $f): array
    {
        $w = [];
        $p = [];
        if (!empty($f['pessoa_id'])) { $w[] = 'r.pessoa_id = ?'; $p[] = (int) $f['pessoa_id']; }
        if (!empty($f['requisicao_id'])) { $w[] = 'COALESCE(cr.requisicao_pagamento_id, lf.requisicao_pagamento_id) = ?'; $p[] = (int) $f['requisicao_id']; }
        if (!empty($f['lote_id'])) { $w[] = 'xp.lote_pagamento_id = ?'; $p[] = (int) $f['lote_id']; }
        if (!empty($f['conta_receber_id'])) { $w[] = 'r.conta_receber_id = ?'; $p[] = (int) $f['conta_receber_id']; }
        if (!empty($f['conta_pagar_id'])) { $w[] = 'r.conta_pagar_id = ?'; $p[] = (int) $f['conta_pagar_id']; }
        if (!empty($f['levantamento_ids'])) { $w[] = 'r.levantamento_financeiro_id IN (' . implode(',', array_map('intval', (array) $f['levantamento_ids'])) . ')'; }
        if (trim((string) ($f['processo'] ?? '')) !== '') {
            $dig = preg_replace('/\D/', '', $f['processo']);
            if ($dig !== '') {
                $w[] = "(regexp_replace(COALESCE(pr.numero_cnj_numero, ''), '\\D', '', 'g') LIKE ? OR regexp_replace(COALESCE(pr.numero_outro, ''), '\\D', '', 'g') LIKE ?)";
                $p[] = '%' . $dig . '%'; $p[] = '%' . $dig . '%';
            } else {
                $w[] = '(pr.numero_outro ILIKE ?)'; $p[] = '%' . trim($f['processo']) . '%';
            }
        }
        if (trim((string) ($f['contrato'] ?? '')) !== '') { $w[] = 'CAST(ct.numero AS TEXT) ILIKE ?'; $p[] = '%' . trim($f['contrato']) . '%'; }
        // tela de repasses: processo (com os processos filhos) e contrato escolhidos na lista
        if (!empty($f['processo_id'])) {
            $familia = ExtratoParametrosService::familia((int) $f['processo_id']);
            $w[] = 'cr.processo_id IN (' . implode(',', array_map('intval', $familia ?: [0])) . ')';
        }
        if (!empty($f['contrato_id'])) { $w[] = 'cr.contrato_id = ?'; $p[] = (int) $f['contrato_id']; }
        // filtros do cabecalho das colunas
        if (trim((string) ($f['texto'] ?? '')) !== '') {
            $w[] = "(COALESCE(cli.nome, pc.nome, pcr.nome, '') ILIKE ? OR COALESCE(pr.numero_cnj_numero, pr.numero_outro, '') ILIKE ? OR CAST(ct.numero AS TEXT) ILIKE ? OR cr.descricao ILIKE ?)";
            $t = '%' . trim($f['texto']) . '%'; array_push($p, $t, $t, $t, $t);
        }
        if (!empty($f['recebido_em'])) { $w[] = 'r.data_recebimento = ?'; $p[] = substr($f['recebido_em'], 0, 10); }
        if (!empty($f['vencimento_em'])) { $w[] = 'lp.dt_vencimento = ?'; $p[] = substr($f['vencimento_em'], 0, 10); }
        if (isset($f['valor']) && $f['valor'] !== null && $f['valor'] !== '') { $w[] = 'r.valor = ?'; $p[] = round((float) $f['valor'], 2); }
        if (($f['situacao'] ?? '') === 'aberto') { $w[] = 'lp.dt_pagamento IS NULL'; }
        if (($f['situacao'] ?? '') === 'pago') { $w[] = 'lp.dt_pagamento IS NOT NULL'; }
        $col = ['recebimento' => 'r.data_recebimento', 'vencimento' => 'lp.dt_vencimento', 'pagamento' => 'lp.dt_pagamento'][$f['periodo_tipo'] ?? 'recebimento'] ?? 'r.data_recebimento';
        if (!empty($f['data_de'])) { $w[] = "{$col} >= ?"; $p[] = substr($f['data_de'], 0, 10); }
        if (!empty($f['data_ate'])) { $w[] = "{$col} <= ?"; $p[] = substr($f['data_ate'], 0, 10); }
        return [$w ? 'WHERE ' . implode(' AND ', $w) : '', $p];
    }

    private static function nomePessoa(PDO $c, int $id): string
    {
        $st = $c->prepare('SELECT nome FROM pessoa WHERE id = ?');
        $st->execute([$id]);
        return (string) $st->fetchColumn();
    }

    private static function conexao(): PDO
    {
        $c = TTransaction::get();
        if (!$c) { throw new Exception('Repasses a colaboradores exigem uma transação aberta.'); }
        return $c;
    }

    private static function abrir(): bool
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        return $propria;
    }
}
