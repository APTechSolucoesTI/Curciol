<?php

class Lancamento extends TRecord
{
    const TABLENAME  = 'lancamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private ContratoPagamentoParcela $contrato_parcela;
    private Banco $cheque_banco;
    private Extrato $extrato;
    private Conta $conta;
    private TipoPagamento $tipo_pagamento;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('conta_id');
        parent::addAttribute('tipo_pagamento_id');
        parent::addAttribute('parcela');
        parent::addAttribute('dt_vencimento');
        parent::addAttribute('valor');
        parent::addAttribute('dt_pagamento');
        parent::addAttribute('ano_pagamento');
        parent::addAttribute('mes_pagamento');
        parent::addAttribute('ano_mes_pagamento');
        parent::addAttribute('ano_vencimento');
        parent::addAttribute('mes_vencimento');
        parent::addAttribute('ano_mes_vencimento');
        parent::addAttribute('cheque_numero');
        parent::addAttribute('cheque_banco_id');
        parent::addAttribute('extrato_id');
        parent::addAttribute('cancelado');
        parent::addAttribute('motivo_cancelamento');
        parent::addAttribute('contrato_parcela_id');
    
    }

    /**
     * Method set_contrato_pagamento_parcela
     * Sample of usage: $var->contrato_pagamento_parcela = $object;
     * @param $object Instance of ContratoPagamentoParcela
     */
    public function set_contrato_parcela(ContratoPagamentoParcela $object)
    {
        $this->contrato_parcela = $object;
        $this->contrato_parcela_id = $object->id;
    }

    /**
     * Method get_contrato_parcela
     * Sample of usage: $var->contrato_parcela->attribute;
     * @returns ContratoPagamentoParcela instance
     */
    public function get_contrato_parcela()
    {
    
        // loads the associated object
        if (empty($this->contrato_parcela))
            $this->contrato_parcela = new ContratoPagamentoParcela($this->contrato_parcela_id);
    
        // returns the associated object
        return $this->contrato_parcela;
    }
    /**
     * Method set_banco
     * Sample of usage: $var->banco = $object;
     * @param $object Instance of Banco
     */
    public function set_cheque_banco(Banco $object)
    {
        $this->cheque_banco = $object;
        $this->cheque_banco_id = $object->id;
    }

    /**
     * Method get_cheque_banco
     * Sample of usage: $var->cheque_banco->attribute;
     * @returns Banco instance
     */
    public function get_cheque_banco()
    {
    
        // loads the associated object
        if (empty($this->cheque_banco))
            $this->cheque_banco = new Banco($this->cheque_banco_id);
    
        // returns the associated object
        return $this->cheque_banco;
    }
    /**
     * Method set_extrato
     * Sample of usage: $var->extrato = $object;
     * @param $object Instance of Extrato
     */
    public function set_extrato(Extrato $object)
    {
        $this->extrato = $object;
        $this->extrato_id = $object->id;
    }

    /**
     * Method get_extrato
     * Sample of usage: $var->extrato->attribute;
     * @returns Extrato instance
     */
    public function get_extrato()
    {
    
        // loads the associated object
        if (empty($this->extrato))
            $this->extrato = new Extrato($this->extrato_id);
    
        // returns the associated object
        return $this->extrato;
    }
    /**
     * Method set_conta
     * Sample of usage: $var->conta = $object;
     * @param $object Instance of Conta
     */
    public function set_conta(Conta $object)
    {
        $this->conta = $object;
        $this->conta_id = $object->id;
    }

    /**
     * Method get_conta
     * Sample of usage: $var->conta->attribute;
     * @returns Conta instance
     */
    public function get_conta()
    {
    
        // loads the associated object
        if (empty($this->conta))
            $this->conta = new Conta($this->conta_id);
    
        // returns the associated object
        return $this->conta;
    }
    /**
     * Method set_tipo_pagamento
     * Sample of usage: $var->tipo_pagamento = $object;
     * @param $object Instance of TipoPagamento
     */
    public function set_tipo_pagamento(TipoPagamento $object)
    {
        $this->tipo_pagamento = $object;
        $this->tipo_pagamento_id = $object->id;
    }

    /**
     * Method get_tipo_pagamento
     * Sample of usage: $var->tipo_pagamento->attribute;
     * @returns TipoPagamento instance
     */
    public function get_tipo_pagamento()
    {
    
        // loads the associated object
        if (empty($this->tipo_pagamento))
            $this->tipo_pagamento = new TipoPagamento($this->tipo_pagamento_id);
    
        // returns the associated object
        return $this->tipo_pagamento;
    }

    /**
     * Method getExtratos
     */
    public function getExtratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('lancamento_id', '=', $this->id));
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
    
        $values = Extrato::where('lancamento_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
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
    
        $values = Extrato::where('lancamento_id', '=', $this->id)->getIndexedArray('conta_caixa_id','{conta_caixa->nome}');
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
    
        $values = Extrato::where('lancamento_id', '=', $this->id)->getIndexedArray('lancamento_id','{lancamento->id}');
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
    
        $values = Extrato::where('lancamento_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
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
    
        $values = Extrato::where('lancamento_id', '=', $this->id)->getIndexedArray('tipo_extrato_id','{tipo_extrato->nome}');
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
    
        $values = Extrato::where('lancamento_id', '=', $this->id)->getIndexedArray('transferencia_conta_caixa_id','{transferencia_conta_caixa->nome}');
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
    
        $values = Extrato::where('lancamento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Extrato::where('lancamento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function onBeforeStore($object)
    {
        if (! empty($object->dt_pagamento))
        {
            $object->ano_pagamento = date('Y', strtotime($object->dt_pagamento));
            $object->mes_pagamento = date('m', strtotime($object->dt_pagamento));
            $object->ano_mes_pagamento = date('Ym', strtotime($object->dt_pagamento));
        }
    
        if (! empty($object->dt_vencimento))
        {
            $object->ano_vencimento = date('Y', strtotime($object->dt_vencimento));
            $object->mes_vencimento = date('m', strtotime($object->dt_vencimento));
            $object->ano_mes_vencimento = date('Ym', strtotime($object->dt_vencimento));
        }
    }
    
}

