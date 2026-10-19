<?php
/**
 * Exclusao de um pagamento lancado na requisicao (requisicao_pagamento_etapa3).
 *
 * Com financeiro gerado, o financeiro e desfeito junto (LevantamentoFinanceiroService::desfazer). So e permitido se
 * nenhuma conta vinda do pagamento foi compensada ou quitada: o recebimento nao pode estar compensado no extrato,
 * a conta a pagar ao cliente e as contas dos colaboradores nao podem ter pagamento. (A conta a receber ja nasce
 * quitada pelo proprio pagamento, entao ela nao conta.) Depois de excluir, o status Quitado e reavaliado.
 */
class PagamentoExclusaoService
{
    /** Por que o pagamento nao pode ser excluido; null quando pode. */
    public static function motivoBloqueio(int $etapa3Id): ?string
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $r = self::motivo(TTransaction::get(), $etapa3Id);
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /** @return array etapa3Id => motivo|null, para varios pagamentos (historico). */
    public static function motivos(array $etapa3Ids): array
    {
        $r = [];
        foreach ($etapa3Ids as $id) {
            $r[(int) $id] = self::motivoBloqueio((int) $id);
        }
        return $r;
    }

    /** Exclui o pagamento (e o financeiro dele, se houver). Abre as proprias transacoes. */
    public static function excluir(int $etapa3Id): void
    {
        $motivo = self::motivoBloqueio($etapa3Id);
        if ($motivo !== null) {
            throw new Exception($motivo);
        }

        if (LevantamentoFinanceiroService::existePara($etapa3Id) !== null) {
            LevantamentoFinanceiroService::desfazer($etapa3Id);
        }

        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            $st = $c->prepare('SELECT requisicao_pagamento_cliente_id FROM requisicao_pagamento_etapa3 WHERE id = ? FOR UPDATE');
            $st->execute([$etapa3Id]);
            $rpcId = $st->fetchColumn();
            if ($rpcId === false) {
                throw new Exception('Pagamento não encontrado.');
            }
            // financeiro gerado por outra pessoa entre o desfazer e aqui
            if (LevantamentoFinanceiroService::existePara($etapa3Id) !== null) {
                throw new Exception('O financeiro deste pagamento foi gerado de novo enquanto isso. Atualize a tela.');
            }
            $c->prepare('DELETE FROM requisicao_pagamento_etapa3 WHERE id = ?')->execute([$etapa3Id]);
            RequisicaoQuitacaoService::atualizar((int) $rpcId);
            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    private static function motivo(PDO $c, int $etapa3Id): ?string
    {
        $st = $c->prepare('SELECT id FROM requisicao_pagamento_etapa3 WHERE id = ?');
        $st->execute([$etapa3Id]);
        if (!$st->fetchColumn()) {
            return 'Pagamento não encontrado.';
        }

        $st = $c->prepare('SELECT lf.conta_receber_id FROM levantamento_financeiro lf WHERE lf.requisicao_pagamento_etapa3_id = ?');
        $st->execute([$etapa3Id]);
        $ficha = $st->fetch(PDO::FETCH_OBJ);
        if (!$ficha) {
            return null;
        }

        // recebimento ja compensado no extrato
        $st = $c->prepare("SELECT COUNT(*) FROM extrato x JOIN lancamento l ON l.id = x.lancamento_id
                            WHERE l.conta_id = ? AND COALESCE(x.compensado, 'N') = 'S'");
        $st->execute([(int) $ficha->conta_receber_id]);
        if ((int) $st->fetchColumn() > 0) {
            return 'O recebimento deste pagamento já foi compensado no extrato. Descompense antes de excluir.';
        }

        // conta a pagar ao cliente paga ou repasse a colaborador pago (mesmas travas do desfazer financeiro)
        return LevantamentoFinanceiroService::podeDesfazer($etapa3Id);
    }
}
