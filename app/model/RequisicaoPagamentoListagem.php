<?php

class RequisicaoPagamentoListagem extends TRecord
{
    const TABLENAME  = 'requisicao_pagamento_listagem';
    const PRIMARYKEY = 'requisicao_pagamento_id';
    const IDPOLICY   =  'max'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('numero_processo');
        parent::addAttribute('tipo_requisicao');
        parent::addAttribute('cliente');
        parent::addAttribute('status');
        parent::addAttribute('data_requerimento');
        parent::addAttribute('data_pedido_mle');
        parent::addAttribute('requisicao_pagamento_cliente_id');
        parent::addAttribute('pessoa_id');
        parent::addAttribute('data_deferimento_expedicao_requisitorio');
        parent::addAttribute('data_deferimento_mle');
            
    }

    
}

