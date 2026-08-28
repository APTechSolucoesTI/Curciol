<?php

class RequisicaoPagamentoEtapa3 extends TRecord
{
    const TABLENAME  = 'requisicao_pagamento_etapa3';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private RequisicaoPagamentoCliente $requisicao_pagamento_cliente;
    private Processo $processo_filho;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('requisicao_pagamento_cliente_id');
        parent::addAttribute('processo_filho_id');
        parent::addAttribute('data_deposito');
        parent::addAttribute('valor_bruto_depositado');
        parent::addAttribute('valor_mle');
        parent::addAttribute('conta_indicada_mle');
        parent::addAttribute('data_pedido_mle');
        parent::addAttribute('data_deferimento_mle');
        parent::addAttribute('numero_ciclo');
        parent::addAttribute('saldo_bruto');
        parent::addAttribute('data_base_saldo');
        parent::addAttribute('possui_saldo');
            
    }

    /**
     * Method set_requisicao_pagamento_cliente
     * Sample of usage: $var->requisicao_pagamento_cliente = $object;
     * @param $object Instance of RequisicaoPagamentoCliente
     */
    public function set_requisicao_pagamento_cliente(RequisicaoPagamentoCliente $object)
    {
        $this->requisicao_pagamento_cliente = $object;
        $this->requisicao_pagamento_cliente_id = $object->id;
    }

    /**
     * Method get_requisicao_pagamento_cliente
     * Sample of usage: $var->requisicao_pagamento_cliente->attribute;
     * @returns RequisicaoPagamentoCliente instance
     */
    public function get_requisicao_pagamento_cliente()
    {
    
        // loads the associated object
        if (empty($this->requisicao_pagamento_cliente))
            $this->requisicao_pagamento_cliente = new RequisicaoPagamentoCliente($this->requisicao_pagamento_cliente_id);
    
        // returns the associated object
        return $this->requisicao_pagamento_cliente;
    }
    /**
     * Method set_processo
     * Sample of usage: $var->processo = $object;
     * @param $object Instance of Processo
     */
    public function set_processo_filho(Processo $object)
    {
        $this->processo_filho = $object;
        $this->processo_filho_id = $object->id;
    }

    /**
     * Method get_processo_filho
     * Sample of usage: $var->processo_filho->attribute;
     * @returns Processo instance
     */
    public function get_processo_filho()
    {
    
        // loads the associated object
        if (empty($this->processo_filho))
            $this->processo_filho = new Processo($this->processo_filho_id);
    
        // returns the associated object
        return $this->processo_filho;
    }

    
}

