<?php
// app/model/LotePagamento.php
/** Pagamento unico de varias contas a pagar de repasse de um colaborador (uma linha por conta no extrato). */
class LotePagamento extends TRecord
{
    const TABLENAME  = 'lote_pagamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['pessoa_id', 'data_pagamento', 'conta_caixa_id', 'tipo_pagamento_id', 'quantidade', 'total', 'historico', 'data_criacao', 'criacao_user_id'] as $a) {
            parent::addAttribute($a);
        }
    }
}
