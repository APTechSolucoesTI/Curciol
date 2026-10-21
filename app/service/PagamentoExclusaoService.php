<?php
/**
 * Exclusao de um pagamento lancado na requisicao (requisicao_pagamento_etapa3).
 *
 * Com financeiro gerado nao exclui (21/10/2026, igual ao contrato): o usuario desfaz o financeiro antes ("Ver financeiro" >
 * "Desfazer financeiro"), mesmo sem nada quitado ou compensado. Depois de excluir, o status Quitado e reavaliado.
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

    /** Exclui o pagamento (sem financeiro gerado). Abre as proprias transacoes. */
    public static function excluir(int $etapa3Id): void
    {
        $motivo = self::motivoBloqueio($etapa3Id);
        if ($motivo !== null) {
            throw new Exception($motivo);
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
            // financeiro gerado por outra pessoa entre a verificacao e aqui
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

        $st = $c->prepare('SELECT 1 FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ?');
        $st->execute([$etapa3Id]);
        if ($st->fetchColumn()) {
            return 'Este pagamento tem financeiro gerado e não pode ser excluído. Desfaça o financeiro antes: botão "Ver financeiro" > "Desfazer financeiro" '
                . '(se algo impedir o desfazer, a janela mostra o motivo). Depois exclua o pagamento.';
        }
        return null;
    }
}
