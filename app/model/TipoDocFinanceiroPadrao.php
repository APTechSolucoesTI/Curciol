<?php

class TipoDocFinanceiroPadrao extends TRecord
{
    const TABLENAME  = 'tipo_doc_financeiro_padrao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const NENHUM = '1';
    const MANUAL = '2';
    const ATENDIMENTO = '3';
    const CONTRATO = '4';
    const PROCESSO = '5';

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
            
    }

    /**
     * Method getTipoDocumentoFinanceiros
     */
    public function getTipoDocumentoFinanceiros()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('padrao_id', '=', $this->id));
        return TipoDocumentoFinanceiro::getObjects( $criteria );
    }

    public function set_tipo_documento_financeiro_tipo_conta_to_string($tipo_documento_financeiro_tipo_conta_to_string)
    {
        if(is_array($tipo_documento_financeiro_tipo_conta_to_string))
        {
            $values = TipoConta::where('id', 'in', $tipo_documento_financeiro_tipo_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->tipo_documento_financeiro_tipo_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_tipo_conta_to_string = $tipo_documento_financeiro_tipo_conta_to_string;
        }

        $this->vdata['tipo_documento_financeiro_tipo_conta_to_string'] = $this->tipo_documento_financeiro_tipo_conta_to_string;
    }

    public function get_tipo_documento_financeiro_tipo_conta_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_tipo_conta_to_string))
        {
            return $this->tipo_documento_financeiro_tipo_conta_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('padrao_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_padrao_to_string($tipo_documento_financeiro_padrao_to_string)
    {
        if(is_array($tipo_documento_financeiro_padrao_to_string))
        {
            $values = TipoDocFinanceiroPadrao::where('id', 'in', $tipo_documento_financeiro_padrao_to_string)->getIndexedArray('nome', 'nome');
            $this->tipo_documento_financeiro_padrao_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_padrao_to_string = $tipo_documento_financeiro_padrao_to_string;
        }

        $this->vdata['tipo_documento_financeiro_padrao_to_string'] = $this->tipo_documento_financeiro_padrao_to_string;
    }

    public function get_tipo_documento_financeiro_padrao_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_padrao_to_string))
        {
            return $this->tipo_documento_financeiro_padrao_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('padrao_id', '=', $this->id)->getIndexedArray('padrao_id','{padrao->nome}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_criacao_user_to_string($tipo_documento_financeiro_criacao_user_to_string)
    {
        if(is_array($tipo_documento_financeiro_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_documento_financeiro_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_documento_financeiro_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_criacao_user_to_string = $tipo_documento_financeiro_criacao_user_to_string;
        }

        $this->vdata['tipo_documento_financeiro_criacao_user_to_string'] = $this->tipo_documento_financeiro_criacao_user_to_string;
    }

    public function get_tipo_documento_financeiro_criacao_user_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_criacao_user_to_string))
        {
            return $this->tipo_documento_financeiro_criacao_user_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('padrao_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_modificacao_user_to_string($tipo_documento_financeiro_modificacao_user_to_string)
    {
        if(is_array($tipo_documento_financeiro_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_documento_financeiro_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_documento_financeiro_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_modificacao_user_to_string = $tipo_documento_financeiro_modificacao_user_to_string;
        }

        $this->vdata['tipo_documento_financeiro_modificacao_user_to_string'] = $this->tipo_documento_financeiro_modificacao_user_to_string;
    }

    public function get_tipo_documento_financeiro_modificacao_user_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_modificacao_user_to_string))
        {
            return $this->tipo_documento_financeiro_modificacao_user_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('padrao_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

