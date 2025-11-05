<?php

class TipoContaCaixa extends TRecord
{
    const TABLENAME  = 'tipo_conta_caixa';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const BANCO = '1';
    const DINHEIRO = '2';

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
            
    }

    /**
     * Method getContaCaixas
     */
    public function getContaCaixas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_conta_caixa_id', '=', $this->id));
        return ContaCaixa::getObjects( $criteria );
    }

    public function set_conta_caixa_tipo_conta_caixa_to_string($conta_caixa_tipo_conta_caixa_to_string)
    {
        if(is_array($conta_caixa_tipo_conta_caixa_to_string))
        {
            $values = TipoContaCaixa::where('id', 'in', $conta_caixa_tipo_conta_caixa_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_caixa_tipo_conta_caixa_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_caixa_tipo_conta_caixa_to_string = $conta_caixa_tipo_conta_caixa_to_string;
        }

        $this->vdata['conta_caixa_tipo_conta_caixa_to_string'] = $this->conta_caixa_tipo_conta_caixa_to_string;
    }

    public function get_conta_caixa_tipo_conta_caixa_to_string()
    {
        if(!empty($this->conta_caixa_tipo_conta_caixa_to_string))
        {
            return $this->conta_caixa_tipo_conta_caixa_to_string;
        }
    
        $values = ContaCaixa::where('tipo_conta_caixa_id', '=', $this->id)->getIndexedArray('tipo_conta_caixa_id','{tipo_conta_caixa->nome}');
        return implode(', ', $values);
    }

    public function set_conta_caixa_banco_to_string($conta_caixa_banco_to_string)
    {
        if(is_array($conta_caixa_banco_to_string))
        {
            $values = Banco::where('id', 'in', $conta_caixa_banco_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_caixa_banco_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_caixa_banco_to_string = $conta_caixa_banco_to_string;
        }

        $this->vdata['conta_caixa_banco_to_string'] = $this->conta_caixa_banco_to_string;
    }

    public function get_conta_caixa_banco_to_string()
    {
        if(!empty($this->conta_caixa_banco_to_string))
        {
            return $this->conta_caixa_banco_to_string;
        }
    
        $values = ContaCaixa::where('tipo_conta_caixa_id', '=', $this->id)->getIndexedArray('banco_id','{banco->nome}');
        return implode(', ', $values);
    }

    public function set_conta_caixa_criacao_user_to_string($conta_caixa_criacao_user_to_string)
    {
        if(is_array($conta_caixa_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $conta_caixa_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->conta_caixa_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_caixa_criacao_user_to_string = $conta_caixa_criacao_user_to_string;
        }

        $this->vdata['conta_caixa_criacao_user_to_string'] = $this->conta_caixa_criacao_user_to_string;
    }

    public function get_conta_caixa_criacao_user_to_string()
    {
        if(!empty($this->conta_caixa_criacao_user_to_string))
        {
            return $this->conta_caixa_criacao_user_to_string;
        }
    
        $values = ContaCaixa::where('tipo_conta_caixa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_conta_caixa_modificacao_user_to_string($conta_caixa_modificacao_user_to_string)
    {
        if(is_array($conta_caixa_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $conta_caixa_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->conta_caixa_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_caixa_modificacao_user_to_string = $conta_caixa_modificacao_user_to_string;
        }

        $this->vdata['conta_caixa_modificacao_user_to_string'] = $this->conta_caixa_modificacao_user_to_string;
    }

    public function get_conta_caixa_modificacao_user_to_string()
    {
        if(!empty($this->conta_caixa_modificacao_user_to_string))
        {
            return $this->conta_caixa_modificacao_user_to_string;
        }
    
        $values = ContaCaixa::where('tipo_conta_caixa_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

