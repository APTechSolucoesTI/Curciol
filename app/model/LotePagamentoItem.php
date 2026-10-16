<?php
// app/model/LotePagamentoItem.php
/** O que uma acao em lote movimentou: a parcela e a linha do extrato (criada na quitacao ou compensada). */
class LotePagamentoItem extends TRecord
{
    const TABLENAME  = 'lote_pagamento_item';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['lote_pagamento_id', 'lancamento_id', 'extrato_id', 'valor'] as $a) {
            parent::addAttribute($a);
        }
    }
}
