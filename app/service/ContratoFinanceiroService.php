<?php
/**
 * Financeiro gerado por um pagamento do contrato (contrato_pagamento_parcela), no mesmo espirito do financeiro da
 * requisicao de pagamento (21/10/2026):
 *  - Desfazer financeiro: apaga as parcelas (lancamentos) geradas por aquele pagamento, ajusta a conta a receber
 *    (total, numero de parcelas; apaga a conta se ela ficar vazia) e o pagamento volta a "Em aberto", sem ser apagado.
 *    So e possivel enquanto nenhuma dessas parcelas foi recebida.
 *  - Pagamento com financeiro gerado nao pode ser excluido nem ter valor, data, opcao ou numero de parcelas alterados
 *    no cadastro do contrato: primeiro desfaz o financeiro.
 * As parcelas sao identificadas por lancamento.contrato_parcela_id.
 */
class ContratoFinanceiroService
{
    const STATUS_ABERTO = 1;

    /** Parcelas (lancamentos) geradas pelo pagamento do contrato. Usa a transacao aberta. */
    public static function lancamentosDoPagamento(int $pagamentoId): array
    {
        $st = TTransaction::get()->prepare('SELECT id, conta_id, parcela, dt_vencimento, dt_pagamento, extrato_id, valor, saldo
                                              FROM lancamento WHERE contrato_parcela_id = ? ORDER BY conta_id, parcela, id');
        $st->execute([$pagamentoId]);
        return $st->fetchAll(PDO::FETCH_OBJ);
    }

    /** O pagamento tem financeiro gerado (status Gerado/Gerado com saldo ou parcelas ligadas)? Usa a transacao aberta. */
    public static function temFinanceiro(int $pagamentoId): bool
    {
        $p = ContratoPagamentoParcela::find($pagamentoId);
        if (!$p) {
            return false;
        }
        return in_array((int) $p->status_contrato_pagamento_id, [2, 3], true) || self::lancamentosDoPagamento($pagamentoId);
    }

    /** Por que o financeiro do pagamento nao pode ser desfeito; null quando pode. Usa a transacao aberta ou abre a propria. */
    public static function motivoBloqueioDesfazer(int $pagamentoId): ?string
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $r = self::motivo($pagamentoId);
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    private static function motivo(int $pagamentoId): ?string
    {
        $p = ContratoPagamentoParcela::find($pagamentoId);
        if (!$p) {
            return 'Pagamento do contrato não encontrado.';
        }
        $lancs = self::lancamentosDoPagamento($pagamentoId);
        if (!$lancs) {
            if (in_array((int) $p->status_contrato_pagamento_id, [2, 3], true)) {
                return 'O financeiro deste pagamento foi gerado antes de o sistema ligar as parcelas ao pagamento, então não dá para identificar '
                    . 'quais parcelas desfazer. Ajuste pela conta a receber do contrato.';
            }
            return 'Este pagamento não tem financeiro gerado.';
        }
        $c = TTransaction::get();
        foreach ($lancs as $l) {
            $recebida = !empty($l->dt_pagamento) || !empty($l->extrato_id)
                || ($l->saldo !== null && round((float) $l->saldo, 2) != round((float) $l->valor, 2));
            if (!$recebida) {
                $st = $c->prepare('SELECT 1 FROM extrato WHERE lancamento_id = ? LIMIT 1');
                $st->execute([(int) $l->id]);
                $recebida = (bool) $st->fetchColumn();
            }
            if ($recebida) {
                return 'A parcela ' . (int) $l->parcela . ' da conta a receber (vencimento ' . date('d/m/Y', strtotime($l->dt_vencimento)) . ') já foi recebida'
                    . ($l->dt_pagamento ? ' em ' . date('d/m/Y', strtotime($l->dt_pagamento)) : ' (total ou parcialmente)')
                    . '. Desquite o recebimento antes de desfazer o financeiro deste pagamento.';
            }
            $st = $c->prepare('SELECT 1 FROM repasse_colaborador WHERE lancamento_receber_id = ? LIMIT 1');
            $st->execute([(int) $l->id]);
            if ($st->fetchColumn()) {
                return 'A parcela ' . (int) $l->parcela . ' da conta a receber já gerou repasse a colaborador. '
                    . 'Desquite o recebimento antes de desfazer o financeiro deste pagamento.';
            }
        }
        return null;
    }

    /** Desfaz o financeiro do pagamento do contrato (ver o cabecalho). Abre a propria transacao. */
    public static function desfazer(int $pagamentoId): void
    {
        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            $c->prepare('SELECT id FROM contrato_pagamento_parcela WHERE id = ? FOR UPDATE')->execute([$pagamentoId]);
            $motivo = self::motivo($pagamentoId);
            if ($motivo !== null) {
                throw new Exception($motivo);
            }

            $lancs = self::lancamentosDoPagamento($pagamentoId);
            $ids = implode(',', array_map(function ($l) { return (int) $l->id; }, $lancs));
            $contas = array_values(array_unique(array_map(function ($l) { return (int) $l->conta_id; }, $lancs)));

            $c->exec("DELETE FROM lancamento_profissional_ajuste WHERE lancamento_profissional_id IN (SELECT id FROM lancamento_profissional WHERE lancamento_id IN ({$ids}))");
            $c->exec("DELETE FROM lancamento_profissional WHERE lancamento_id IN ({$ids})");
            $c->exec("DELETE FROM lancamento WHERE id IN ({$ids})");

            foreach ($contas as $contaId) {
                self::ajustarConta($c, $contaId);
            }

            $c->prepare('UPDATE contrato_pagamento_parcela SET saldo = NULL, status_contrato_pagamento_id = ?, data_modificacao = now(), modificacao_user_id = ? WHERE id = ?')
              ->execute([self::STATUS_ABERTO, TSession::getValue('userid'), $pagamentoId]);

            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    /** Conta depois de tirar parcelas: sem parcelas, sai; com parcelas, renumera e recalcula total e proximo vencimento. */
    private static function ajustarConta(PDO $c, int $contaId): void
    {
        $st = $c->prepare('SELECT id, valor, valor_total, dt_vencimento, dt_pagamento, cancelado FROM lancamento WHERE conta_id = ? ORDER BY parcela, id');
        $st->execute([$contaId]);
        $restantes = $st->fetchAll(PDO::FETCH_OBJ);
        if (!$restantes) {
            $c->prepare('DELETE FROM conta_profissional WHERE conta_id = ?')->execute([$contaId]);
            $c->prepare('DELETE FROM conta WHERE id = ?')->execute([$contaId]);
            return;
        }
        $total = 0.0;
        $proximo = null;
        $ativos = 0;
        foreach ($restantes as $i => $l) {
            $c->prepare('UPDATE lancamento SET parcela = ? WHERE id = ?')->execute([$i + 1, (int) $l->id]);
            if ($l->cancelado === 'S') {
                continue;
            }
            $ativos++;
            $total += ($l->valor_total !== null && $l->valor_total !== '') ? (float) $l->valor_total : (float) $l->valor;
            if (empty($l->dt_pagamento) && ($proximo === null || $l->dt_vencimento < $proximo)) {
                $proximo = $l->dt_vencimento;
            }
        }
        $quitada = ($proximo === null) ? 'S' : 'N';
        $c->prepare('UPDATE conta SET total_conta = ?, total_parcelas = ?, proximo_vencimento_lancamento = COALESCE(?, proximo_vencimento_lancamento), quitada = ? WHERE id = ?')
          ->execute([round($total, 2), $ativos, $proximo, $quitada, $contaId]);
    }

    /**
     * Cadastro do contrato: pagamentos com financeiro gerado nao podem sair da lista nem mudar valor, data, opcao ou
     * numero de parcelas. $itensPostados: linhas da lista (__row__data). Usa a transacao aberta.
     */
    public static function exigirPagamentosPreservados(int $contratoId, array $itensPostados): void
    {
        if ($contratoId <= 0) {
            return;
        }
        $postados = [];
        foreach ($itensPostados as $linha) {
            $d = @unserialize(base64_decode((string) $linha));
            // campos do modelo no nivel de cima (o __display__ tem os valores formatados para a tela)
            $valores = is_object($d) ? $d : (object) (is_array($d) ? $d : []);
            $id = (int) ($valores->id ?? 0);
            if ($id > 0) {
                $postados[$id] = $valores;
            }
        }
        foreach (ContratoPagamentoParcela::where('contrato_id', '=', $contratoId)->load() as $p) {
            if (!self::temFinanceiro((int) $p->id)) {
                continue;
            }
            $nome = self::descricao($p);
            if (!isset($postados[(int) $p->id])) {
                throw new Exception("O pagamento {$nome} já tem financeiro gerado e não pode ser excluído. "
                    . 'Use "Desfazer financeiro" na aba Pagamentos do contrato (possível enquanto nenhuma parcela tiver sido recebida) e depois exclua.');
            }
            $novo = $postados[(int) $p->id];
            $mudou = [];
            if (round(self::numero($novo->valor ?? null), 2) != round((float) $p->valor, 2)) { $mudou[] = 'valor'; }
            if ((int) ($novo->numero_parcelas ?? 0) !== (int) $p->numero_parcelas) { $mudou[] = 'número de parcelas'; }
            if ((int) ($novo->contrato_opcao_pagamento_id ?? 0) !== (int) $p->contrato_opcao_pagamento_id) { $mudou[] = 'opção de pagamento'; }
            if (self::data($novo->data_pagamento ?? null) !== self::data($p->data_pagamento)) { $mudou[] = 'data'; }
            if ($mudou) {
                throw new Exception("O pagamento {$nome} já tem financeiro gerado: " . implode(', ', $mudou) . ' não podem ser alterados. '
                    . 'Use "Desfazer financeiro" na aba Pagamentos do contrato (possível enquanto nenhuma parcela tiver sido recebida), altere e gere de novo.');
            }
        }
    }

    /** Motivo para nao tirar o pagamento da lista do cadastro (null quando pode). Abre a propria transacao se precisar. */
    public static function motivoNaoExcluir(int $pagamentoId): ?string
    {
        if ($pagamentoId <= 0) {
            return null;
        }
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $r = null;
            if (self::temFinanceiro($pagamentoId)) {
                $p = ContratoPagamentoParcela::find($pagamentoId);
                $r = 'O pagamento ' . self::descricao($p) . ' já tem financeiro gerado e não pode ser excluído. '
                    . 'Use "Desfazer financeiro" na aba Pagamentos do contrato (possível enquanto nenhuma parcela tiver sido recebida) e depois exclua.';
            }
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    private static function descricao(ContratoPagamentoParcela $p): string
    {
        $opcao = $p->contrato_opcao_pagamento_id ? ContratoPagamentoOpcao::find((int) $p->contrato_opcao_pagamento_id) : null;
        return '"' . trim(($opcao->nome ?? 'Pagamento') . ' - R$ ' . number_format((float) $p->valor, 2, ',', '.')) . '"';
    }

    private static function numero($v): float
    {
        if ($v === null || $v === '') { return 0.0; }
        if (is_numeric($v)) { return (float) $v; }
        $t = str_replace(['R$', ' '], '', (string) $v);
        if (strpos($t, ',') !== false) { $t = str_replace(',', '.', str_replace('.', '', $t)); }
        return (float) $t;
    }

    private static function data($v): string
    {
        $t = trim((string) $v);
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})#', $t, $m)) { return "{$m[3]}-{$m[2]}-{$m[1]}"; }
        return substr($t, 0, 10);
    }
}
