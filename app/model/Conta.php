<?php

class Conta extends TRecord
{
    const TABLENAME  = 'conta';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private TipoConta $tipo_conta;
    private Pessoa $pessoa;
    private Atendimento $atendimento;
    private Escritorio $escritorio;
    private CategoriaConta $categoria_conta;
    private TipoDocumentoFinanceiro $tipo_documento_financeiro;
    private Pessoa $profissional;
    private Contrato $contrato;
    private Processo $processo;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pessoa_id');
        parent::addAttribute('categoria_conta_id');
        parent::addAttribute('tipo_conta_id');
        parent::addAttribute('escritorio_id');
        parent::addAttribute('tipo_documento_financeiro_id');
        parent::addAttribute('atendimento_id');
        parent::addAttribute('contrato_id');
        parent::addAttribute('profissional_id');
        parent::addAttribute('processo_id');
        parent::addAttribute('numero_documento');
        parent::addAttribute('data_emissao');
        parent::addAttribute('total_parcelas');
        parent::addAttribute('quitada');
        parent::addAttribute('descricao');
        parent::addAttribute('conta_origem_id');
        parent::addAttribute('total_conta');
        parent::addAttribute('mes');
        parent::addAttribute('ano');
        parent::addAttribute('ano_mes');
        parent::addAttribute('proximo_vencimento_lancamento');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
        parent::addAttribute('tipo_lancamento');
    
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
     * Method set_tipo_conta
     * Sample of usage: $var->tipo_conta = $object;
     * @param $object Instance of TipoConta
     */
    public function set_tipo_conta(TipoConta $object)
    {
        $this->tipo_conta = $object;
        $this->tipo_conta_id = $object->id;
    }

    /**
     * Method get_tipo_conta
     * Sample of usage: $var->tipo_conta->attribute;
     * @returns TipoConta instance
     */
    public function get_tipo_conta()
    {
    
        // loads the associated object
        if (empty($this->tipo_conta))
            $this->tipo_conta = new TipoConta($this->tipo_conta_id);
    
        // returns the associated object
        return $this->tipo_conta;
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
     * Method set_atendimento
     * Sample of usage: $var->atendimento = $object;
     * @param $object Instance of Atendimento
     */
    public function set_atendimento(Atendimento $object)
    {
        $this->atendimento = $object;
        $this->atendimento_id = $object->id;
    }

    /**
     * Method get_atendimento
     * Sample of usage: $var->atendimento->attribute;
     * @returns Atendimento instance
     */
    public function get_atendimento()
    {
    
        // loads the associated object
        if (empty($this->atendimento))
            $this->atendimento = new Atendimento($this->atendimento_id);
    
        // returns the associated object
        return $this->atendimento;
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
     * Method set_tipo_documento_financeiro
     * Sample of usage: $var->tipo_documento_financeiro = $object;
     * @param $object Instance of TipoDocumentoFinanceiro
     */
    public function set_tipo_documento_financeiro(TipoDocumentoFinanceiro $object)
    {
        $this->tipo_documento_financeiro = $object;
        $this->tipo_documento_financeiro_id = $object->id;
    }

    /**
     * Method get_tipo_documento_financeiro
     * Sample of usage: $var->tipo_documento_financeiro->attribute;
     * @returns TipoDocumentoFinanceiro instance
     */
    public function get_tipo_documento_financeiro()
    {
    
        // loads the associated object
        if (empty($this->tipo_documento_financeiro))
            $this->tipo_documento_financeiro = new TipoDocumentoFinanceiro($this->tipo_documento_financeiro_id);
    
        // returns the associated object
        return $this->tipo_documento_financeiro;
    }
    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_profissional(Pessoa $object)
    {
        $this->profissional = $object;
        $this->profissional_id = $object->id;
    }

    /**
     * Method get_profissional
     * Sample of usage: $var->profissional->attribute;
     * @returns Pessoa instance
     */
    public function get_profissional()
    {
    
        // loads the associated object
        if (empty($this->profissional))
            $this->profissional = new Pessoa($this->profissional_id);
    
        // returns the associated object
        return $this->profissional;
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
     * Method set_processo
     * Sample of usage: $var->processo = $object;
     * @param $object Instance of Processo
     */
    public function set_processo(Processo $object)
    {
        $this->processo = $object;
        $this->processo_id = $object->id;
    }

    /**
     * Method get_processo
     * Sample of usage: $var->processo->attribute;
     * @returns Processo instance
     */
    public function get_processo()
    {
    
        // loads the associated object
        if (empty($this->processo))
            $this->processo = new Processo($this->processo_id);
    
        // returns the associated object
        return $this->processo;
    }

    /**
     * Method getLancamentos
     */
    public function getLancamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('conta_id', '=', $this->id));
        return Lancamento::getObjects( $criteria );
    }
    /**
     * Method getContaProfissionals
     */
    public function getContaProfissionals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('conta_id', '=', $this->id));
        return ContaProfissional::getObjects( $criteria );
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
    
        $values = Lancamento::where('conta_id', '=', $this->id)->getIndexedArray('conta_id','{conta->descricao}');
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
    
        $values = Lancamento::where('conta_id', '=', $this->id)->getIndexedArray('tipo_pagamento_id','{tipo_pagamento->nome}');
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
    
        $values = Lancamento::where('conta_id', '=', $this->id)->getIndexedArray('cheque_banco_id','{cheque_banco->nome}');
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
    
        $values = Lancamento::where('conta_id', '=', $this->id)->getIndexedArray('extrato_id','{extrato->id}');
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
    
        $values = Lancamento::where('conta_id', '=', $this->id)->getIndexedArray('contrato_parcela_id','{contrato_parcela->id}');
        return implode(', ', $values);
    }

    public function set_conta_profissional_conta_to_string($conta_profissional_conta_to_string)
    {
        if(is_array($conta_profissional_conta_to_string))
        {
            $values = Conta::where('id', 'in', $conta_profissional_conta_to_string)->getIndexedArray('descricao', 'descricao');
            $this->conta_profissional_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_profissional_conta_to_string = $conta_profissional_conta_to_string;
        }

        $this->vdata['conta_profissional_conta_to_string'] = $this->conta_profissional_conta_to_string;
    }

    public function get_conta_profissional_conta_to_string()
    {
        if(!empty($this->conta_profissional_conta_to_string))
        {
            return $this->conta_profissional_conta_to_string;
        }
    
        $values = ContaProfissional::where('conta_id', '=', $this->id)->getIndexedArray('conta_id','{conta->descricao}');
        return implode(', ', $values);
    }

    public function set_conta_profissional_pessoa_to_string($conta_profissional_pessoa_to_string)
    {
        if(is_array($conta_profissional_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $conta_profissional_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_profissional_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_profissional_pessoa_to_string = $conta_profissional_pessoa_to_string;
        }

        $this->vdata['conta_profissional_pessoa_to_string'] = $this->conta_profissional_pessoa_to_string;
    }

    public function get_conta_profissional_pessoa_to_string()
    {
        if(!empty($this->conta_profissional_pessoa_to_string))
        {
            return $this->conta_profissional_pessoa_to_string;
        }
    
        $values = ContaProfissional::where('conta_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function onBeforeStore($object)
    {
        if (!empty($object->data_emissao)) {
            $object->ano = date('Y', strtotime($object->data_emissao));
            $object->mes = date('m', strtotime($object->data_emissao));
            $object->ano_mes = date('Ym', strtotime($object->data_emissao));
        }
    }
            
}

