<?php

class StatusRequisicaoPagamento extends TRecord
{
    const TABLENAME  = 'status_requisicao_pagamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('cor');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
    }

    /**
     * Method getRequisicaoPagamentoClientes
     */
    public function getRequisicaoPagamentoClientes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('status_requisicao_pagamento_id', '=', $this->id));
        return RequisicaoPagamentoCliente::getObjects( $criteria );
    }

    public function set_requisicao_pagamento_cliente_pessoa_to_string($requisicao_pagamento_cliente_pessoa_to_string)
    {
        if(is_array($requisicao_pagamento_cliente_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $requisicao_pagamento_cliente_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->requisicao_pagamento_cliente_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_cliente_pessoa_to_string = $requisicao_pagamento_cliente_pessoa_to_string;
        }

        $this->vdata['requisicao_pagamento_cliente_pessoa_to_string'] = $this->requisicao_pagamento_cliente_pessoa_to_string;
    }

    public function get_requisicao_pagamento_cliente_pessoa_to_string()
    {
        if(!empty($this->requisicao_pagamento_cliente_pessoa_to_string))
        {
            return $this->requisicao_pagamento_cliente_pessoa_to_string;
        }
    
        $values = RequisicaoPagamentoCliente::where('status_requisicao_pagamento_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_cliente_entidade_devedora_to_string($requisicao_pagamento_cliente_entidade_devedora_to_string)
    {
        if(is_array($requisicao_pagamento_cliente_entidade_devedora_to_string))
        {
            $values = Pessoa::where('id', 'in', $requisicao_pagamento_cliente_entidade_devedora_to_string)->getIndexedArray('nome', 'nome');
            $this->requisicao_pagamento_cliente_entidade_devedora_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_cliente_entidade_devedora_to_string = $requisicao_pagamento_cliente_entidade_devedora_to_string;
        }

        $this->vdata['requisicao_pagamento_cliente_entidade_devedora_to_string'] = $this->requisicao_pagamento_cliente_entidade_devedora_to_string;
    }

    public function get_requisicao_pagamento_cliente_entidade_devedora_to_string()
    {
        if(!empty($this->requisicao_pagamento_cliente_entidade_devedora_to_string))
        {
            return $this->requisicao_pagamento_cliente_entidade_devedora_to_string;
        }
    
        $values = RequisicaoPagamentoCliente::where('status_requisicao_pagamento_id', '=', $this->id)->getIndexedArray('entidade_devedora_id','{entidade_devedora->nome}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_cliente_requisicao_pagamento_to_string($requisicao_pagamento_cliente_requisicao_pagamento_to_string)
    {
        if(is_array($requisicao_pagamento_cliente_requisicao_pagamento_to_string))
        {
            $values = RequisicaoPagamento::where('id', 'in', $requisicao_pagamento_cliente_requisicao_pagamento_to_string)->getIndexedArray('id', 'id');
            $this->requisicao_pagamento_cliente_requisicao_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_cliente_requisicao_pagamento_to_string = $requisicao_pagamento_cliente_requisicao_pagamento_to_string;
        }

        $this->vdata['requisicao_pagamento_cliente_requisicao_pagamento_to_string'] = $this->requisicao_pagamento_cliente_requisicao_pagamento_to_string;
    }

    public function get_requisicao_pagamento_cliente_requisicao_pagamento_to_string()
    {
        if(!empty($this->requisicao_pagamento_cliente_requisicao_pagamento_to_string))
        {
            return $this->requisicao_pagamento_cliente_requisicao_pagamento_to_string;
        }
    
        $values = RequisicaoPagamentoCliente::where('status_requisicao_pagamento_id', '=', $this->id)->getIndexedArray('requisicao_pagamento_id','{requisicao_pagamento->id}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_cliente_status_requisicao_pagamento_to_string($requisicao_pagamento_cliente_status_requisicao_pagamento_to_string)
    {
        if(is_array($requisicao_pagamento_cliente_status_requisicao_pagamento_to_string))
        {
            $values = StatusRequisicaoPagamento::where('id', 'in', $requisicao_pagamento_cliente_status_requisicao_pagamento_to_string)->getIndexedArray('id', 'id');
            $this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string = $requisicao_pagamento_cliente_status_requisicao_pagamento_to_string;
        }

        $this->vdata['requisicao_pagamento_cliente_status_requisicao_pagamento_to_string'] = $this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string;
    }

    public function get_requisicao_pagamento_cliente_status_requisicao_pagamento_to_string()
    {
        if(!empty($this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string))
        {
            return $this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string;
        }
    
        $values = RequisicaoPagamentoCliente::where('status_requisicao_pagamento_id', '=', $this->id)->getIndexedArray('status_requisicao_pagamento_id','{status_requisicao_pagamento->id}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(RequisicaoPagamentoCliente::where('status_requisicao_pagamento_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

