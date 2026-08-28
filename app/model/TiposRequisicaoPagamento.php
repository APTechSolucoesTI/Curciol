<?php

class TiposRequisicaoPagamento extends TRecord
{
    const TABLENAME  = 'tipos_requisicao_pagamento';
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
        parent::addAttribute('descricao');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
    }

    /**
     * Method getRequisicaoPagamentos
     */
    public function getRequisicaoPagamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipos_requisicao_pagamento_id', '=', $this->id));
        return RequisicaoPagamento::getObjects( $criteria );
    }

    public function set_requisicao_pagamento_processo_to_string($requisicao_pagamento_processo_to_string)
    {
        if(is_array($requisicao_pagamento_processo_to_string))
        {
            $values = Processo::where('id', 'in', $requisicao_pagamento_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->requisicao_pagamento_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_processo_to_string = $requisicao_pagamento_processo_to_string;
        }

        $this->vdata['requisicao_pagamento_processo_to_string'] = $this->requisicao_pagamento_processo_to_string;
    }

    public function get_requisicao_pagamento_processo_to_string()
    {
        if(!empty($this->requisicao_pagamento_processo_to_string))
        {
            return $this->requisicao_pagamento_processo_to_string;
        }
    
        $values = RequisicaoPagamento::where('tipos_requisicao_pagamento_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_tipos_requisicao_pagamento_to_string($requisicao_pagamento_tipos_requisicao_pagamento_to_string)
    {
        if(is_array($requisicao_pagamento_tipos_requisicao_pagamento_to_string))
        {
            $values = TiposRequisicaoPagamento::where('id', 'in', $requisicao_pagamento_tipos_requisicao_pagamento_to_string)->getIndexedArray('id', 'id');
            $this->requisicao_pagamento_tipos_requisicao_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_tipos_requisicao_pagamento_to_string = $requisicao_pagamento_tipos_requisicao_pagamento_to_string;
        }

        $this->vdata['requisicao_pagamento_tipos_requisicao_pagamento_to_string'] = $this->requisicao_pagamento_tipos_requisicao_pagamento_to_string;
    }

    public function get_requisicao_pagamento_tipos_requisicao_pagamento_to_string()
    {
        if(!empty($this->requisicao_pagamento_tipos_requisicao_pagamento_to_string))
        {
            return $this->requisicao_pagamento_tipos_requisicao_pagamento_to_string;
        }
    
        $values = RequisicaoPagamento::where('tipos_requisicao_pagamento_id', '=', $this->id)->getIndexedArray('tipos_requisicao_pagamento_id','{tipos_requisicao_pagamento->id}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(RequisicaoPagamento::where('tipos_requisicao_pagamento_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

