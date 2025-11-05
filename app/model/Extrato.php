<?php

class Extrato extends TRecord
{
    const TABLENAME  = 'extrato';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private ContaCaixa $conta_caixa;
    private Escritorio $escritorio;
    private Lancamento $lancamento;
    private CategoriaConta $categoria_conta;
    private TipoExtrato $tipo_extrato;
    private ContaCaixa $transferencia_conta_caixa;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('escritorio_id');
        parent::addAttribute('conta_caixa_id');
        parent::addAttribute('lancamento_id');
        parent::addAttribute('categoria_conta_id');
        parent::addAttribute('tipo_extrato_id');
        parent::addAttribute('transferencia_conta_caixa_id');
        parent::addAttribute('extrato_vinculado');
        parent::addAttribute('entrada_valor');
        parent::addAttribute('saida_valor');
        parent::addAttribute('data_lancamento');
        parent::addAttribute('data_previsao_compensacao');
        parent::addAttribute('compensado');
        parent::addAttribute('data_compensacao');
        parent::addAttribute('historico');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
        parent::addAttribute('mes');
        parent::addAttribute('ano');
        parent::addAttribute('ano_mes');
    
    }

    /**
     * Method set_conta_caixa
     * Sample of usage: $var->conta_caixa = $object;
     * @param $object Instance of ContaCaixa
     */
    public function set_conta_caixa(ContaCaixa $object)
    {
        $this->conta_caixa = $object;
        $this->conta_caixa_id = $object->id;
    }

    /**
     * Method get_conta_caixa
     * Sample of usage: $var->conta_caixa->attribute;
     * @returns ContaCaixa instance
     */
    public function get_conta_caixa()
    {
    
        // loads the associated object
        if (empty($this->conta_caixa))
            $this->conta_caixa = new ContaCaixa($this->conta_caixa_id);
    
        // returns the associated object
        return $this->conta_caixa;
    }
    /**
     * Method set_escritorio
     * Sample of usage: $var->escritorio = $object;
     * @param $object Instance of Escritorio
     */
    public function set_escritorio(Escritorio $object)
    {
        $this->escritorio = $object;
        $this->escritorio_id = $object->id;
    }

    /**
     * Method get_escritorio
     * Sample of usage: $var->escritorio->attribute;
     * @returns Escritorio instance
     */
    public function get_escritorio()
    {
    
        // loads the associated object
        if (empty($this->escritorio))
            $this->escritorio = new Escritorio($this->escritorio_id);
    
        // returns the associated object
        return $this->escritorio;
    }
    /**
     * Method set_lancamento
     * Sample of usage: $var->lancamento = $object;
     * @param $object Instance of Lancamento
     */
    public function set_lancamento(Lancamento $object)
    {
        $this->lancamento = $object;
        $this->lancamento_id = $object->id;
    }

    /**
     * Method get_lancamento
     * Sample of usage: $var->lancamento->attribute;
     * @returns Lancamento instance
     */
    public function get_lancamento()
    {
    
        // loads the associated object
        if (empty($this->lancamento))
            $this->lancamento = new Lancamento($this->lancamento_id);
    
        // returns the associated object
        return $this->lancamento;
    }
    /**
     * Method set_categoria_conta
     * Sample of usage: $var->categoria_conta = $object;
     * @param $object Instance of CategoriaConta
     */
    public function set_categoria_conta(CategoriaConta $object)
    {
        $this->categoria_conta = $object;
        $this->categoria_conta_id = $object->id;
    }

    /**
     * Method get_categoria_conta
     * Sample of usage: $var->categoria_conta->attribute;
     * @returns CategoriaConta instance
     */
    public function get_categoria_conta()
    {
    
        // loads the associated object
        if (empty($this->categoria_conta))
            $this->categoria_conta = new CategoriaConta($this->categoria_conta_id);
    
        // returns the associated object
        return $this->categoria_conta;
    }
    /**
     * Method set_tipo_extrato
     * Sample of usage: $var->tipo_extrato = $object;
     * @param $object Instance of TipoExtrato
     */
    public function set_tipo_extrato(TipoExtrato $object)
    {
        $this->tipo_extrato = $object;
        $this->tipo_extrato_id = $object->id;
    }

    /**
     * Method get_tipo_extrato
     * Sample of usage: $var->tipo_extrato->attribute;
     * @returns TipoExtrato instance
     */
    public function get_tipo_extrato()
    {
    
        // loads the associated object
        if (empty($this->tipo_extrato))
            $this->tipo_extrato = new TipoExtrato($this->tipo_extrato_id);
    
        // returns the associated object
        return $this->tipo_extrato;
    }
    /**
     * Method set_conta_caixa
     * Sample of usage: $var->conta_caixa = $object;
     * @param $object Instance of ContaCaixa
     */
    public function set_transferencia_conta_caixa(ContaCaixa $object)
    {
        $this->transferencia_conta_caixa = $object;
        $this->transferencia_conta_caixa_id = $object->id;
    }

    /**
     * Method get_transferencia_conta_caixa
     * Sample of usage: $var->transferencia_conta_caixa->attribute;
     * @returns ContaCaixa instance
     */
    public function get_transferencia_conta_caixa()
    {
    
        // loads the associated object
        if (empty($this->transferencia_conta_caixa))
            $this->transferencia_conta_caixa = new ContaCaixa($this->transferencia_conta_caixa_id);
    
        // returns the associated object
        return $this->transferencia_conta_caixa;
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
        $criteria->add(new TFilter('extrato_id', '=', $this->id));
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
    
        $values = Lancamento::where('extrato_id', '=', $this->id)->getIndexedArray('conta_id','{conta->descricao}');
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
    
        $values = Lancamento::where('extrato_id', '=', $this->id)->getIndexedArray('tipo_pagamento_id','{tipo_pagamento->nome}');
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
    
        $values = Lancamento::where('extrato_id', '=', $this->id)->getIndexedArray('cheque_banco_id','{cheque_banco->nome}');
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
    
        $values = Lancamento::where('extrato_id', '=', $this->id)->getIndexedArray('extrato_id','{extrato->id}');
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
    
        $values = Lancamento::where('extrato_id', '=', $this->id)->getIndexedArray('contrato_parcela_id','{contrato_parcela->id}');
        return implode(', ', $values);
    }

}

