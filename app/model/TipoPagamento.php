<?php

class TipoPagamento extends TRecord
{
    const TABLENAME  = 'tipo_pagamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    const PIX = '1';
    const BOLETO = '2';
    const DINHEIRO = '3';
    const CREDITO = '4';
    const DEBITO = '5';
    const CHEQUE = '6';
    const TRANSFERENCIA = '7';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('ativo');
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
     * Method getLancamentos
     */
    public function getLancamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_pagamento_id', '=', $this->id));
        return Lancamento::getObjects( $criteria );
    }

    public function set_lancamento_conta_to_string($lancamento_conta_to_string)
    {
        if(is_array($lancamento_conta_to_string))
        {
            $values = Conta::where('id', 'in', $lancamento_conta_to_string)->getIndexedArray('descricao', 'descricao');
            $this->lancamento_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->lancamento_conta_to_string = $lancamento_conta_to_string;
        }

        $this->vdata['lancamento_conta_to_string'] = $this->lancamento_conta_to_string;
    }

    public function get_lancamento_conta_to_string()
    {
        if(!empty($this->lancamento_conta_to_string))
        {
            return $this->lancamento_conta_to_string;
        }
    
        $values = Lancamento::where('tipo_pagamento_id', '=', $this->id)->getIndexedArray('conta_id','{conta->descricao}');
        return implode(', ', $values);
    }

    public function set_lancamento_tipo_pagamento_to_string($lancamento_tipo_pagamento_to_string)
    {
        if(is_array($lancamento_tipo_pagamento_to_string))
        {
            $values = TipoPagamento::where('id', 'in', $lancamento_tipo_pagamento_to_string)->getIndexedArray('nome', 'nome');
            $this->lancamento_tipo_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->lancamento_tipo_pagamento_to_string = $lancamento_tipo_pagamento_to_string;
        }

        $this->vdata['lancamento_tipo_pagamento_to_string'] = $this->lancamento_tipo_pagamento_to_string;
    }

    public function get_lancamento_tipo_pagamento_to_string()
    {
        if(!empty($this->lancamento_tipo_pagamento_to_string))
        {
            return $this->lancamento_tipo_pagamento_to_string;
        }
    
        $values = Lancamento::where('tipo_pagamento_id', '=', $this->id)->getIndexedArray('tipo_pagamento_id','{tipo_pagamento->nome}');
        return implode(', ', $values);
    }

    public function set_lancamento_cheque_banco_to_string($lancamento_cheque_banco_to_string)
    {
        if(is_array($lancamento_cheque_banco_to_string))
        {
            $values = Banco::where('id', 'in', $lancamento_cheque_banco_to_string)->getIndexedArray('nome', 'nome');
            $this->lancamento_cheque_banco_to_string = implode(', ', $values);
        }
        else
        {
            $this->lancamento_cheque_banco_to_string = $lancamento_cheque_banco_to_string;
        }

        $this->vdata['lancamento_cheque_banco_to_string'] = $this->lancamento_cheque_banco_to_string;
    }

    public function get_lancamento_cheque_banco_to_string()
    {
        if(!empty($this->lancamento_cheque_banco_to_string))
        {
            return $this->lancamento_cheque_banco_to_string;
        }
    
        $values = Lancamento::where('tipo_pagamento_id', '=', $this->id)->getIndexedArray('cheque_banco_id','{cheque_banco->nome}');
        return implode(', ', $values);
    }

    public function set_lancamento_extrato_to_string($lancamento_extrato_to_string)
    {
        if(is_array($lancamento_extrato_to_string))
        {
            $values = Extrato::where('id', 'in', $lancamento_extrato_to_string)->getIndexedArray('id', 'id');
            $this->lancamento_extrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->lancamento_extrato_to_string = $lancamento_extrato_to_string;
        }

        $this->vdata['lancamento_extrato_to_string'] = $this->lancamento_extrato_to_string;
    }

    public function get_lancamento_extrato_to_string()
    {
        if(!empty($this->lancamento_extrato_to_string))
        {
            return $this->lancamento_extrato_to_string;
        }
    
        $values = Lancamento::where('tipo_pagamento_id', '=', $this->id)->getIndexedArray('extrato_id','{extrato->id}');
        return implode(', ', $values);
    }

    public function set_lancamento_contrato_parcela_to_string($lancamento_contrato_parcela_to_string)
    {
        if(is_array($lancamento_contrato_parcela_to_string))
        {
            $values = ContratoPagamentoParcela::where('id', 'in', $lancamento_contrato_parcela_to_string)->getIndexedArray('id', 'id');
            $this->lancamento_contrato_parcela_to_string = implode(', ', $values);
        }
        else
        {
            $this->lancamento_contrato_parcela_to_string = $lancamento_contrato_parcela_to_string;
        }

        $this->vdata['lancamento_contrato_parcela_to_string'] = $this->lancamento_contrato_parcela_to_string;
    }

    public function get_lancamento_contrato_parcela_to_string()
    {
        if(!empty($this->lancamento_contrato_parcela_to_string))
        {
            return $this->lancamento_contrato_parcela_to_string;
        }
    
        $values = Lancamento::where('tipo_pagamento_id', '=', $this->id)->getIndexedArray('contrato_parcela_id','{contrato_parcela->id}');
        return implode(', ', $values);
    }

    
}

