<?php

class ContratoPagamentoParcela extends TRecord
{
    const TABLENAME  = 'contrato_pagamento_parcela';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private ContratoPagamentoOpcao $contrato_opcao_pagamento;
    private ContratoPagamentoEvento $contrato_evento;
    private ContratoPagamentoIndexador $contrato_indexador;
    private Contrato $contrato;
    private UnidadeIndexador $unidade_indexador;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('contrato_id');
        parent::addAttribute('contrato_opcao_pagamento_id');
        parent::addAttribute('valor');
        parent::addAttribute('data_pagamento');
        parent::addAttribute('contrato_evento_id');
        parent::addAttribute('unidade_indexador_id');
        parent::addAttribute('complemento_indexador');
        parent::addAttribute('contrato_indexador_id');
        parent::addAttribute('descritivo');
        parent::addAttribute('numero_parcelas');
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
     * Method set_contrato_pagamento_opcao
     * Sample of usage: $var->contrato_pagamento_opcao = $object;
     * @param $object Instance of ContratoPagamentoOpcao
     */
    public function set_contrato_opcao_pagamento(ContratoPagamentoOpcao $object)
    {
        $this->contrato_opcao_pagamento = $object;
        $this->contrato_opcao_pagamento_id = $object->id;
    }

    /**
     * Method get_contrato_opcao_pagamento
     * Sample of usage: $var->contrato_opcao_pagamento->attribute;
     * @returns ContratoPagamentoOpcao instance
     */
    public function get_contrato_opcao_pagamento()
    {
    
        // loads the associated object
        if (empty($this->contrato_opcao_pagamento))
            $this->contrato_opcao_pagamento = new ContratoPagamentoOpcao($this->contrato_opcao_pagamento_id);
    
        // returns the associated object
        return $this->contrato_opcao_pagamento;
    }
    /**
     * Method set_contrato_pagamento_evento
     * Sample of usage: $var->contrato_pagamento_evento = $object;
     * @param $object Instance of ContratoPagamentoEvento
     */
    public function set_contrato_evento(ContratoPagamentoEvento $object)
    {
        $this->contrato_evento = $object;
        $this->contrato_evento_id = $object->id;
    }

    /**
     * Method get_contrato_evento
     * Sample of usage: $var->contrato_evento->attribute;
     * @returns ContratoPagamentoEvento instance
     */
    public function get_contrato_evento()
    {
    
        // loads the associated object
        if (empty($this->contrato_evento))
            $this->contrato_evento = new ContratoPagamentoEvento($this->contrato_evento_id);
    
        // returns the associated object
        return $this->contrato_evento;
    }
    /**
     * Method set_contrato_pagamento_indexador
     * Sample of usage: $var->contrato_pagamento_indexador = $object;
     * @param $object Instance of ContratoPagamentoIndexador
     */
    public function set_contrato_indexador(ContratoPagamentoIndexador $object)
    {
        $this->contrato_indexador = $object;
        $this->contrato_indexador_id = $object->id;
    }

    /**
     * Method get_contrato_indexador
     * Sample of usage: $var->contrato_indexador->attribute;
     * @returns ContratoPagamentoIndexador instance
     */
    public function get_contrato_indexador()
    {
    
        // loads the associated object
        if (empty($this->contrato_indexador))
            $this->contrato_indexador = new ContratoPagamentoIndexador($this->contrato_indexador_id);
    
        // returns the associated object
        return $this->contrato_indexador;
    }
    /**
     * Method set_contrato
     * Sample of usage: $var->contrato = $object;
     * @param $object Instance of Contrato
     */
    public function set_contrato(Contrato $object)
    {
        $this->contrato = $object;
        $this->contrato_id = $object->id;
    }

    /**
     * Method get_contrato
     * Sample of usage: $var->contrato->attribute;
     * @returns Contrato instance
     */
    public function get_contrato()
    {
    
        // loads the associated object
        if (empty($this->contrato))
            $this->contrato = new Contrato($this->contrato_id);
    
        // returns the associated object
        return $this->contrato;
    }
    /**
     * Method set_unidade_indexador
     * Sample of usage: $var->unidade_indexador = $object;
     * @param $object Instance of UnidadeIndexador
     */
    public function set_unidade_indexador(UnidadeIndexador $object)
    {
        $this->unidade_indexador = $object;
        $this->unidade_indexador_id = $object->id;
    }

    /**
     * Method get_unidade_indexador
     * Sample of usage: $var->unidade_indexador->attribute;
     * @returns UnidadeIndexador instance
     */
    public function get_unidade_indexador()
    {
    
        // loads the associated object
        if (empty($this->unidade_indexador))
            $this->unidade_indexador = new UnidadeIndexador($this->unidade_indexador_id);
    
        // returns the associated object
        return $this->unidade_indexador;
    }

    /**
     * Method getLancamentos
     */
    public function getLancamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_parcela_id', '=', $this->id));
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
    
        $values = Lancamento::where('contrato_parcela_id', '=', $this->id)->getIndexedArray('conta_id','{conta->descricao}');
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
    
        $values = Lancamento::where('contrato_parcela_id', '=', $this->id)->getIndexedArray('tipo_pagamento_id','{tipo_pagamento->nome}');
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
    
        $values = Lancamento::where('contrato_parcela_id', '=', $this->id)->getIndexedArray('cheque_banco_id','{cheque_banco->nome}');
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
    
        $values = Lancamento::where('contrato_parcela_id', '=', $this->id)->getIndexedArray('extrato_id','{extrato->id}');
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
    
        $values = Lancamento::where('contrato_parcela_id', '=', $this->id)->getIndexedArray('contrato_parcela_id','{contrato_parcela->id}');
        return implode(', ', $values);
    }

    
}

