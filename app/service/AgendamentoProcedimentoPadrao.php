<?php
/**
 * Procedimentos do agendamento sem a coluna Parceiro (30/10/2026): o valor padrao vem do cadastro do procedimento
 * (Consulta R$ 300, Retorno R$ 0), qualquer que seja a agenda, e continua editavel na tela. O parceiro_id, obrigatorio
 * em agendamento_procedimento, e gravado sozinho com o parceiro desse preco (o escritorio so usa o Particular).
 */
class AgendamentoProcedimentoPadrao
{
    /** Valor padrao do procedimento. Usa a transacao aberta. */
    public static function valor(int $procedimentoId): float
    {
        $p = self::preco($procedimentoId);
        return $p ? (float) $p->valor : 0.0;
    }

    /** Parceiro gravado no item do agendamento. Usa a transacao aberta. */
    public static function parceiro(int $procedimentoId): int
    {
        $p = self::preco($procedimentoId);
        if ($p) { return (int) $p->parceiro_id; }
        return (int) TTransaction::get()->query('SELECT min(id) FROM parceiro')->fetchColumn();
    }

    /** Transformer do storeItems: completa o parceiro do item. */
    public static function completar($agendamento, $item): void
    {
        if (empty($item->parceiro_id)) {
            $item->parceiro_id = self::parceiro((int) $item->procedimento_id);
        }
    }

    private static function preco(int $procedimentoId)
    {
        if ($procedimentoId <= 0) { return null; }
        $st = TTransaction::get()->prepare('SELECT parceiro_id, valor FROM procedimento_preco WHERE procedimento_id = ? ORDER BY parceiro_id, id LIMIT 1');
        $st->execute([$procedimentoId]);
        return $st->fetch(PDO::FETCH_OBJ) ?: null;
    }
}
