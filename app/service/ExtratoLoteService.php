<?php
/**
 * Extrato: uma linha por lote de quitacao (23/10/2026).
 *
 * No banco nada muda: o lote continua gravando uma linha de extrato por conta (saldo, compensacao, desfazer e
 * desquitar uma conta continuam iguais). So a tela do Extrato mostra o lote numa linha: a de menor id entre as linhas
 * do lote que passam pelos filtros, com a soma dessas linhas e o historico "LOTE Nº X - texto do usuario".
 * O relatorio (PDF/Excel) continua detalhado por conta.
 */
class ExtratoLoteService
{
    /**
     * Acrescenta ao criterio o filtro "uma linha por lote" e devolve, por linha representante, o resumo do lote:
     * [extrato_id => (object) lote_id, entrada, saida, quantidade, compensado (S/N/P), data_compensacao, historico].
     * Usa a transacao aberta.
     */
    public static function agrupar(TCriteria $criteria): array
    {
        $filtros = clone $criteria;
        $filtros->resetProperties();
        $filtros->add(new TFilter('lote_pagamento_id', 'IS NOT', NULL));

        $sql = new TSqlSelect;
        $sql->setEntity('extrato');
        foreach (['id', 'lote_pagamento_id', 'entrada_valor', 'saida_valor', 'compensado', 'data_compensacao'] as $col) { $sql->addColumn($col); }
        $sql->setCriteria($filtros);
        $st = TTransaction::get()->prepare($sql->getInstruction(TRUE));
        $st->execute($filtros->getPreparedVars() ?: []);

        $lotes = [];
        foreach ($st->fetchAll(PDO::FETCH_OBJ) as $l) {
            $k = (int) $l->lote_pagamento_id;
            if (!isset($lotes[$k])) {
                $lotes[$k] = (object) ['lote_id' => $k, 'rep' => (int) $l->id, 'entrada' => 0.0, 'saida' => 0.0, 'quantidade' => 0, 'comp' => 0, 'datas' => []];
            }
            $g = $lotes[$k];
            $g->rep = min($g->rep, (int) $l->id);
            $g->entrada += (float) $l->entrada_valor;
            $g->saida += (float) $l->saida_valor;
            $g->quantidade++;
            if (($l->compensado ?? 'N') === 'S') { $g->comp++; $g->datas[] = (string) $l->data_compensacao; }
        }

        // so a linha representante de cada lote aparece; linhas sem lote continuam todas
        $umaPorLote = new TCriteria;
        $umaPorLote->add(new TFilter('lote_pagamento_id', 'IS', NULL));
        $umaPorLote->add(new TFilter('id', 'in', $lotes ? array_values(array_map(function ($g) { return $g->rep; }, $lotes)) : [0]), TExpression::OR_OPERATOR);
        $criteria->add($umaPorLote);

        $complementos = [];
        if ($lotes) {
            foreach (LotePagamento::where('id', 'in', array_keys($lotes))->load() as $lp) { $complementos[(int) $lp->id] = trim((string) $lp->historico); }
        }

        $resumo = [];
        foreach ($lotes as $k => $g) {
            $resumo[$g->rep] = (object) [
                'lote_id' => $k,
                'entrada' => round($g->entrada, 2),
                'saida' => round($g->saida, 2),
                'quantidade' => $g->quantidade,
                'compensado' => $g->comp === 0 ? 'N' : ($g->comp === $g->quantidade ? 'S' : 'P'),
                'data_compensacao' => $g->comp === $g->quantidade && $g->datas ? max($g->datas) : null,
                'historico' => 'LOTE Nº ' . $k . (($complementos[$k] ?? '') !== '' ? ' - ' . $complementos[$k] : ''),
            ];
        }
        return $resumo;
    }

    /** A linha e de um lote? (botao "Ver lote" e para esconder editar, visualizar e marcar) */
    public static function ehLote($object): bool
    {
        return !empty($object->lote_pagamento_id);
    }
}
