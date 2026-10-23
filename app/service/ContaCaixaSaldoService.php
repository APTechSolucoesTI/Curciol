<?php
/**
 * Saldo da conta-caixa calculado a partir do extrato (fonte da verdade):
 *   saldo instantaneo    = saldo inicial + entradas - saidas dos extratos compensados
 *   saldo nao compensado = entradas - saidas dos extratos ainda nao compensados
 *
 * Toda operacao que cria, altera, compensa, descompensa ou apaga extrato chama
 * recalcular() com as contas-caixa afetadas, dentro da mesma transacao, depois
 * de gravar o extrato. Nada soma ou subtrai saldo "na mao": assim editar ou
 * apagar um extrato nunca deixa o saldo divergente.
 */
class ContaCaixaSaldoService
{
    /** Recalcula e grava o saldo das contas-caixa informadas. Exige transacao aberta. */
    public static function recalcular(...$contaCaixaIds): void
    {
        $ids = [];
        foreach ($contaCaixaIds as $id) {
            foreach ((array) $id as $x) {
                if ((int) $x > 0) { $ids[(int) $x] = (int) $x; }
            }
        }
        if (!$ids) { return; }
        sort($ids); // ordem fixa de travamento: duas operacoes simultaneas nao se travam mutuamente

        $c = TTransaction::get();
        if (!$c) { throw new Exception('Recalculo de saldo da conta-caixa exige uma transação aberta.'); }

        foreach ($ids as $id) {
            // trava a conta-caixa ate o fim da transacao; a soma abaixo (outro comando)
            // ja enxerga o que outra transacao gravou e confirmou enquanto esta esperava
            $trava = $c->prepare('SELECT id FROM conta_caixa WHERE id = ? FOR UPDATE');
            $trava->execute([$id]);
            if (!$trava->fetchColumn()) { throw new Exception('Conta-caixa não encontrada.'); }

            $s = self::somar($c, $id);
            $up = $c->prepare('UPDATE conta_caixa
                                  SET saldo_instantaneo = COALESCE(saldo_inicial, 0) + CAST(? AS numeric),
                                      saldo_nao_compensado = CAST(? AS numeric)
                                WHERE id = ?');
            $up->execute([sprintf('%.2f', $s['compensado']), sprintf('%.2f', $s['nao_compensado']), $id]);
        }
    }

    /**
     * Saldo gravado x saldo calculado de cada conta-caixa (somente leitura), para conferencia.
     * @return array[] ['id','nome','gravado_instantaneo','calculado_instantaneo','gravado_nao_compensado','calculado_nao_compensado']
     */
    public static function conferir(): array
    {
        $c = TTransaction::get();
        if (!$c) { throw new Exception('Conferência de saldo exige uma transação aberta.'); }
        $linhas = [];
        foreach ($c->query('SELECT id, nome, COALESCE(saldo_inicial,0) ini, COALESCE(saldo_instantaneo,0) inst, COALESCE(saldo_nao_compensado,0) nao FROM conta_caixa ORDER BY id')->fetchAll(PDO::FETCH_OBJ) as $cc) {
            $s = self::somar($c, (int) $cc->id);
            $linhas[] = [
                'id' => (int) $cc->id,
                'nome' => $cc->nome,
                'gravado_instantaneo' => round((float) $cc->inst, 2),
                'calculado_instantaneo' => round((float) $cc->ini + $s['compensado'], 2),
                'gravado_nao_compensado' => round((float) $cc->nao, 2),
                'calculado_nao_compensado' => round($s['nao_compensado'], 2),
            ];
        }
        return $linhas;
    }

    private static function somar(PDO $c, int $id): array
    {
        $st = $c->prepare("SELECT
                COALESCE(SUM(CASE WHEN compensado = 'S' THEN COALESCE(entrada_valor, 0) - COALESCE(saida_valor, 0) END), 0) AS compensado,
                COALESCE(SUM(CASE WHEN COALESCE(compensado, 'N') <> 'S' THEN COALESCE(entrada_valor, 0) - COALESCE(saida_valor, 0) END), 0) AS nao_compensado
            FROM extrato WHERE conta_caixa_id = ?");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return ['compensado' => (float) $r['compensado'], 'nao_compensado' => (float) $r['nao_compensado']];
    }
}
