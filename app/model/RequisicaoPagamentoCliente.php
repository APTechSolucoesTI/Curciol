<?php

class RequisicaoPagamentoCliente extends TRecord
{
    const TABLENAME  = 'requisicao_pagamento_cliente';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const UPDATEDAT  = 'data_modificacao';

    private Pessoa $pessoa;
    private Pessoa $entidade_devedora;
    private RequisicaoPagamento $requisicao_pagamento;
    private StatusRequisicaoPagamento $status_requisicao_pagamento;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pessoa_id');
        parent::addAttribute('entidade_devedora_id');
        parent::addAttribute('requisicao_pagamento_id');
        parent::addAttribute('status_requisicao_pagamento_id');
        parent::addAttribute('valor');
        parent::addAttribute('obs');
        parent::addAttribute('conta_indicada_mle');
        parent::addAttribute('data_base');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
        parent::addAttribute('data_requerimento');
            
    }

    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_pessoa(Pessoa $object)
    {
        $this->pessoa = $object;
        $this->pessoa_id = $object->id;
    }

    /**
     * Method get_pessoa
     * Sample of usage: $var->pessoa->attribute;
     * @returns Pessoa instance
     */
    public function get_pessoa()
    {
    
        // loads the associated object
        if (empty($this->pessoa))
            $this->pessoa = new Pessoa($this->pessoa_id);
    
        // returns the associated object
        return $this->pessoa;
    }
    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_entidade_devedora(Pessoa $object)
    {
        $this->entidade_devedora = $object;
        $this->entidade_devedora_id = $object->id;
    }

    /**
     * Method get_entidade_devedora
     * Sample of usage: $var->entidade_devedora->attribute;
     * @returns Pessoa instance
     */
    public function get_entidade_devedora()
    {
    
        // loads the associated object
        if (empty($this->entidade_devedora))
            $this->entidade_devedora = new Pessoa($this->entidade_devedora_id);
    
        // returns the associated object
        return $this->entidade_devedora;
    }
    /**
     * Method set_requisicao_pagamento
     * Sample of usage: $var->requisicao_pagamento = $object;
     * @param $object Instance of RequisicaoPagamento
     */
    public function set_requisicao_pagamento(RequisicaoPagamento $object)
    {
        $this->requisicao_pagamento = $object;
        $this->requisicao_pagamento_id = $object->id;
    }

    /**
     * Method get_requisicao_pagamento
     * Sample of usage: $var->requisicao_pagamento->attribute;
     * @returns RequisicaoPagamento instance
     */
    public function get_requisicao_pagamento()
    {
    
        // loads the associated object
        if (empty($this->requisicao_pagamento))
            $this->requisicao_pagamento = new RequisicaoPagamento($this->requisicao_pagamento_id);
    
        // returns the associated object
        return $this->requisicao_pagamento;
    }
    /**
     * Method set_status_requisicao_pagamento
     * Sample of usage: $var->status_requisicao_pagamento = $object;
     * @param $object Instance of StatusRequisicaoPagamento
     */
    public function set_status_requisicao_pagamento(StatusRequisicaoPagamento $object)
    {
        $this->status_requisicao_pagamento = $object;
        $this->status_requisicao_pagamento_id = $object->id;
    }

    /**
     * Method get_status_requisicao_pagamento
     * Sample of usage: $var->status_requisicao_pagamento->attribute;
     * @returns StatusRequisicaoPagamento instance
     */
    public function get_status_requisicao_pagamento()
    {
    
        // loads the associated object
        if (empty($this->status_requisicao_pagamento))
            $this->status_requisicao_pagamento = new StatusRequisicaoPagamento($this->status_requisicao_pagamento_id);
    
        // returns the associated object
        return $this->status_requisicao_pagamento;
    }

    /**
     * Method getRequisicaoPagamentoEtapa2s
     */
    public function getRequisicaoPagamentoEtapa2s()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('requisicao_pagamento_cliente_id', '=', $this->id));
        return RequisicaoPagamentoEtapa2::getObjects( $criteria );
    }
    /**
     * Method getRequisicaoPagamentoEtapa3s
     */
    public function getRequisicaoPagamentoEtapa3s()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('requisicao_pagamento_cliente_id', '=', $this->id));
        return RequisicaoPagamentoEtapa3::getObjects( $criteria );
    }

    public function set_requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string($requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string)
    {
        if(is_array($requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string))
        {
            $values = RequisicaoPagamentoCliente::where('id', 'in', $requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string)->getIndexedArray('id', 'id');
            $this->requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string = $requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string;
        }

        $this->vdata['requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string'] = $this->requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string;
    }

    public function get_requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string()
    {
        if(!empty($this->requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string))
        {
            return $this->requisicao_pagamento_etapa2_requisicao_pagamento_cliente_to_string;
        }
    
        $values = RequisicaoPagamentoEtapa2::where('requisicao_pagamento_cliente_id', '=', $this->id)->getIndexedArray('requisicao_pagamento_cliente_id','{requisicao_pagamento_cliente->id}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_etapa2_processo_filho_to_string($requisicao_pagamento_etapa2_processo_filho_to_string)
    {
        if(is_array($requisicao_pagamento_etapa2_processo_filho_to_string))
        {
            $values = Processo::where('id', 'in', $requisicao_pagamento_etapa2_processo_filho_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->requisicao_pagamento_etapa2_processo_filho_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_etapa2_processo_filho_to_string = $requisicao_pagamento_etapa2_processo_filho_to_string;
        }

        $this->vdata['requisicao_pagamento_etapa2_processo_filho_to_string'] = $this->requisicao_pagamento_etapa2_processo_filho_to_string;
    }

    public function get_requisicao_pagamento_etapa2_processo_filho_to_string()
    {
        if(!empty($this->requisicao_pagamento_etapa2_processo_filho_to_string))
        {
            return $this->requisicao_pagamento_etapa2_processo_filho_to_string;
        }
    
        $values = RequisicaoPagamentoEtapa2::where('requisicao_pagamento_cliente_id', '=', $this->id)->getIndexedArray('processo_filho_id','{processo_filho->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string($requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string)
    {
        if(is_array($requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string))
        {
            $values = RequisicaoPagamentoCliente::where('id', 'in', $requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string)->getIndexedArray('id', 'id');
            $this->requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string = $requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string;
        }

        $this->vdata['requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string'] = $this->requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string;
    }

    public function get_requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string()
    {
        if(!empty($this->requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string))
        {
            return $this->requisicao_pagamento_etapa3_requisicao_pagamento_cliente_to_string;
        }
    
        $values = RequisicaoPagamentoEtapa3::where('requisicao_pagamento_cliente_id', '=', $this->id)->getIndexedArray('requisicao_pagamento_cliente_id','{requisicao_pagamento_cliente->id}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_etapa3_processo_filho_to_string($requisicao_pagamento_etapa3_processo_filho_to_string)
    {
        if(is_array($requisicao_pagamento_etapa3_processo_filho_to_string))
        {
            $values = Processo::where('id', 'in', $requisicao_pagamento_etapa3_processo_filho_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->requisicao_pagamento_etapa3_processo_filho_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_etapa3_processo_filho_to_string = $requisicao_pagamento_etapa3_processo_filho_to_string;
        }

        $this->vdata['requisicao_pagamento_etapa3_processo_filho_to_string'] = $this->requisicao_pagamento_etapa3_processo_filho_to_string;
    }

    public function get_requisicao_pagamento_etapa3_processo_filho_to_string()
    {
        if(!empty($this->requisicao_pagamento_etapa3_processo_filho_to_string))
        {
            return $this->requisicao_pagamento_etapa3_processo_filho_to_string;
        }
    
        $values = RequisicaoPagamentoEtapa3::where('requisicao_pagamento_cliente_id', '=', $this->id)->getIndexedArray('processo_filho_id','{processo_filho->numero_cnj_numero}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(RequisicaoPagamentoEtapa2::where('requisicao_pagamento_cliente_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
        if(RequisicaoPagamentoEtapa3::where('requisicao_pagamento_cliente_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

