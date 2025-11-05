<?php

class TipoExtrato extends TRecord
{
    const TABLENAME  = 'tipo_extrato';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const RECEBER = '1';
    const PAGAR = '2';
    const TRANSFERENCIA = '3';
    const ENTRADA = '4';
    const SAIDA = '5';

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
            
    }

    /**
     * Method getExtratos
     */
    public function getExtratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_extrato_id', '=', $this->id));
        return Extrato::getObjects( $criteria );
    }

    public function set_extrato_escritorio_to_string($extrato_escritorio_to_string)
    {
        if(is_array($extrato_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $extrato_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_escritorio_to_string = $extrato_escritorio_to_string;
        }

        $this->vdata['extrato_escritorio_to_string'] = $this->extrato_escritorio_to_string;
    }

    public function get_extrato_escritorio_to_string()
    {
        if(!empty($this->extrato_escritorio_to_string))
        {
            return $this->extrato_escritorio_to_string;
        }
    
        $values = Extrato::where('tipo_extrato_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_conta_caixa_to_string($extrato_conta_caixa_to_string)
    {
        if(is_array($extrato_conta_caixa_to_string))
        {
            $values = ContaCaixa::where('id', 'in', $extrato_conta_caixa_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_conta_caixa_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_conta_caixa_to_string = $extrato_conta_caixa_to_string;
        }

        $this->vdata['extrato_conta_caixa_to_string'] = $this->extrato_conta_caixa_to_string;
    }

    public function get_extrato_conta_caixa_to_string()
    {
        if(!empty($this->extrato_conta_caixa_to_string))
        {
            return $this->extrato_conta_caixa_to_string;
        }
    
        $values = Extrato::where('tipo_extrato_id', '=', $this->id)->getIndexedArray('conta_caixa_id','{conta_caixa->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_lancamento_to_string($extrato_lancamento_to_string)
    {
        if(is_array($extrato_lancamento_to_string))
        {
            $values = Lancamento::where('id', 'in', $extrato_lancamento_to_string)->getIndexedArray('id', 'id');
            $this->extrato_lancamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_lancamento_to_string = $extrato_lancamento_to_string;
        }

        $this->vdata['extrato_lancamento_to_string'] = $this->extrato_lancamento_to_string;
    }

    public function get_extrato_lancamento_to_string()
    {
        if(!empty($this->extrato_lancamento_to_string))
        {
            return $this->extrato_lancamento_to_string;
        }
    
        $values = Extrato::where('tipo_extrato_id', '=', $this->id)->getIndexedArray('lancamento_id','{lancamento->id}');
        return implode(', ', $values);
    }

    public function set_extrato_categoria_conta_to_string($extrato_categoria_conta_to_string)
    {
        if(is_array($extrato_categoria_conta_to_string))
        {
            $values = CategoriaConta::where('id', 'in', $extrato_categoria_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_categoria_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_categoria_conta_to_string = $extrato_categoria_conta_to_string;
        }

        $this->vdata['extrato_categoria_conta_to_string'] = $this->extrato_categoria_conta_to_string;
    }

    public function get_extrato_categoria_conta_to_string()
    {
        if(!empty($this->extrato_categoria_conta_to_string))
        {
            return $this->extrato_categoria_conta_to_string;
        }
    
        $values = Extrato::where('tipo_extrato_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_tipo_extrato_to_string($extrato_tipo_extrato_to_string)
    {
        if(is_array($extrato_tipo_extrato_to_string))
        {
            $values = TipoExtrato::where('id', 'in', $extrato_tipo_extrato_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_tipo_extrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_tipo_extrato_to_string = $extrato_tipo_extrato_to_string;
        }

        $this->vdata['extrato_tipo_extrato_to_string'] = $this->extrato_tipo_extrato_to_string;
    }

    public function get_extrato_tipo_extrato_to_string()
    {
        if(!empty($this->extrato_tipo_extrato_to_string))
        {
            return $this->extrato_tipo_extrato_to_string;
        }
    
        $values = Extrato::where('tipo_extrato_id', '=', $this->id)->getIndexedArray('tipo_extrato_id','{tipo_extrato->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_transferencia_conta_caixa_to_string($extrato_transferencia_conta_caixa_to_string)
    {
        if(is_array($extrato_transferencia_conta_caixa_to_string))
        {
            $values = ContaCaixa::where('id', 'in', $extrato_transferencia_conta_caixa_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_transferencia_conta_caixa_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_transferencia_conta_caixa_to_string = $extrato_transferencia_conta_caixa_to_string;
        }

        $this->vdata['extrato_transferencia_conta_caixa_to_string'] = $this->extrato_transferencia_conta_caixa_to_string;
    }

    public function get_extrato_transferencia_conta_caixa_to_string()
    {
        if(!empty($this->extrato_transferencia_conta_caixa_to_string))
        {
            return $this->extrato_transferencia_conta_caixa_to_string;
        }
    
        $values = Extrato::where('tipo_extrato_id', '=', $this->id)->getIndexedArray('transferencia_conta_caixa_id','{transferencia_conta_caixa->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_criacao_user_to_string($extrato_criacao_user_to_string)
    {
        if(is_array($extrato_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $extrato_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->extrato_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_criacao_user_to_string = $extrato_criacao_user_to_string;
        }

        $this->vdata['extrato_criacao_user_to_string'] = $this->extrato_criacao_user_to_string;
    }

    public function get_extrato_criacao_user_to_string()
    {
        if(!empty($this->extrato_criacao_user_to_string))
        {
            return $this->extrato_criacao_user_to_string;
        }
    
        $values = Extrato::where('tipo_extrato_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_extrato_modificacao_user_to_string($extrato_modificacao_user_to_string)
    {
        if(is_array($extrato_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $extrato_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->extrato_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_modificacao_user_to_string = $extrato_modificacao_user_to_string;
        }

        $this->vdata['extrato_modificacao_user_to_string'] = $this->extrato_modificacao_user_to_string;
    }

    public function get_extrato_modificacao_user_to_string()
    {
        if(!empty($this->extrato_modificacao_user_to_string))
        {
            return $this->extrato_modificacao_user_to_string;
        }
    
        $values = Extrato::where('tipo_extrato_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

