<?php
// app/model/LotePagamento.php
/** Acao em lote (quitar ou compensar) em Contas a pagar/receber: uma linha no extrato por parcela; desfazer em ate 30 min. */
class LotePagamento extends TRecord
{
    const TABLENAME  = 'lote_pagamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   = 'serial';

    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        foreach (['pessoa_id', 'data_pagamento', 'conta_caixa_id', 'tipo_pagamento_id', 'quantidade', 'total', 'historico', 'data_criacao', 'criacao_user_id', 'acao', 'tipo_conta_id', 'compensado', 'desfeito_em', 'desfeito_user_id'] as $a) {
            parent::addAttribute($a);
        }
    }
}
