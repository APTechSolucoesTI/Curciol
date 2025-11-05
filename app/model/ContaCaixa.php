<?php

class ContaCaixa extends TRecord
{
    const TABLENAME  = 'conta_caixa';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private TipoContaCaixa $tipo_conta_caixa;
    private Banco $banco;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('tipo_conta_caixa_id');
        parent::addAttribute('dt_inicial');
        parent::addAttribute('saldo_inicial');
        parent::addAttribute('saldo_instantaneo');
        parent::addAttribute('saldo_nao_compensado');
        parent::addAttribute('ativo');
        parent::addAttribute('cor_nao_compensado');
        parent::addAttribute('cor_compensado');
        parent::addAttribute('banco_id');
        parent::addAttribute('codigo_agencia');
        parent::addAttribute('codigo_conta');
        parent::addAttribute('descricao_agencia');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
    }

    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_criacao_user(SystemUsers $object)
    {
        $this->criacao_user = $object;
        $this->criacao_user_id = $object->id;
    }

    /**
     * Method get_criacao_user
     * Sample of usage: $var->criacao_user->attribute;
     * @returns SystemUsers instance
     */
    public function get_criacao_user()
    {
    
        // loads the associated object
        if (empty($this->criacao_user))
            $this->criacao_user = new SystemUsers($this->criacao_user_id);
    
        // returns the associated object
        return $this->criacao_user;
    }
    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_modificacao_user(SystemUsers $object)
    {
        $this->modificacao_user = $object;
        $this->modificacao_user_id = $object->id;
    }

    /**
     * Method get_modificacao_user
     * Sample of usage: $var->modificacao_user->attribute;
     * @returns SystemUsers instance
     */
    public function get_modificacao_user()
    {
    
        // loads the associated object
        if (empty($this->modificacao_user))
            $this->modificacao_user = new SystemUsers($this->modificacao_user_id);
    
        // returns the associated object
        return $this->modificacao_user;
    }
    /**
     * Method set_tipo_conta_caixa
     * Sample of usage: $var->tipo_conta_caixa = $object;
     * @param $object Instance of TipoContaCaixa
     */
    public function set_tipo_conta_caixa(TipoContaCaixa $object)
    {
        $this->tipo_conta_caixa = $object;
        $this->tipo_conta_caixa_id = $object->id;
    }

    /**
     * Method get_tipo_conta_caixa
     * Sample of usage: $var->tipo_conta_caixa->attribute;
     * @returns TipoContaCaixa instance
     */
    public function get_tipo_conta_caixa()
    {
    
        // loads the associated object
        if (empty($this->tipo_conta_caixa))
            $this->tipo_conta_caixa = new TipoContaCaixa($this->tipo_conta_caixa_id);
    
        // returns the associated object
        return $this->tipo_conta_caixa;
    }
    /**
     * Method set_banco
     * Sample of usage: $var->banco = $object;
     * @param $object Instance of Banco
     */
    public function set_banco(Banco $object)
    {
        $this->banco = $object;
        $this->banco_id = $object->id;
    }

    /**
     * Method get_banco
     * Sample of usage: $var->banco->attribute;
     * @returns Banco instance
     */
    public function get_banco()
    {
    
        // loads the associated object
        if (empty($this->banco))
            $this->banco = new Banco($this->banco_id);
    
        // returns the associated object
        return $this->banco;
    }

    /**
     * Method getExtratos
     */
    public function getExtratosByContaCaixas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('conta_caixa_id', '=', $this->id));
        return Extrato::getObjects( $criteria );
    }
    /**
     * Method getExtratos
     */
    public function getExtratosByTransferenciaContaCaixas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('transferencia_conta_caixa_id', '=', $this->id));
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
    
        $values = Extrato::where('transferencia_conta_caixa_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
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
    
        $values = Extrato::where('transferencia_conta_caixa_id', '=', $this->id)->getIndexedArray('conta_caixa_id','{conta_caixa->nome}');
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
    
        $values = Extrato::where('transferencia_conta_caixa_id', '=', $this->id)->getIndexedArray('lancamento_id','{lancamento->id}');
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
    
        $values = Extrato::where('transferencia_conta_caixa_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
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
    
        $values = Extrato::where('transferencia_conta_caixa_id', '=', $this->id)->getIndexedArray('tipo_extrato_id','{tipo_extrato->nome}');
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
    
        $values = Extrato::where('transferencia_conta_caixa_id', '=', $this->id)->getIndexedArray('transferencia_conta_caixa_id','{transferencia_conta_caixa->nome}');
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
    
        $values = Extrato::where('transferencia_conta_caixa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Extrato::where('transferencia_conta_caixa_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

