<?php

class StatusContratoPagamento extends TRecord
{
    const TABLENAME  = 'status_contrato_pagamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
            
    }

    /**
     * Method getContratoPagamentoParcelas
     */
    public function getContratoPagamentoParcelas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('status_contrato_pagamento_id', '=', $this->id));
        return ContratoPagamentoParcela::getObjects( $criteria );
    }

    public function set_contrato_pagamento_parcela_contrato_to_string($contrato_pagamento_parcela_contrato_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_pagamento_parcela_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_pagamento_parcela_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_to_string = $contrato_pagamento_parcela_contrato_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_to_string'] = $this->contrato_pagamento_parcela_contrato_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('status_contrato_pagamento_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_status_contrato_pagamento_to_string($contrato_pagamento_parcela_status_contrato_pagamento_to_string)
    {
        if(is_array($contrato_pagamento_parcela_status_contrato_pagamento_to_string))
        {
            $values = StatusContratoPagamento::where('id', 'in', $contrato_pagamento_parcela_status_contrato_pagamento_to_string)->getIndexedArray('id', 'id');
            $this->contrato_pagamento_parcela_status_contrato_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_status_contrato_pagamento_to_string = $contrato_pagamento_parcela_status_contrato_pagamento_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_status_contrato_pagamento_to_string'] = $this->contrato_pagamento_parcela_status_contrato_pagamento_to_string;
    }

    public function get_contrato_pagamento_parcela_status_contrato_pagamento_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_status_contrato_pagamento_to_string))
        {
            return $this->contrato_pagamento_parcela_status_contrato_pagamento_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('status_contrato_pagamento_id', '=', $this->id)->getIndexedArray('status_contrato_pagamento_id','{status_contrato_pagamento->id}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_opcao_pagamento_to_string($contrato_pagamento_parcela_contrato_opcao_pagamento_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_opcao_pagamento_to_string))
        {
            $values = ContratoPagamentoOpcao::where('id', 'in', $contrato_pagamento_parcela_contrato_opcao_pagamento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string = $contrato_pagamento_parcela_contrato_opcao_pagamento_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_opcao_pagamento_to_string'] = $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_opcao_pagamento_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('status_contrato_pagamento_id', '=', $this->id)->getIndexedArray('contrato_opcao_pagamento_id','{contrato_opcao_pagamento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_evento_to_string($contrato_pagamento_parcela_contrato_evento_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_evento_to_string))
        {
            $values = ContratoPagamentoEvento::where('id', 'in', $contrato_pagamento_parcela_contrato_evento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_contrato_evento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_evento_to_string = $contrato_pagamento_parcela_contrato_evento_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_evento_to_string'] = $this->contrato_pagamento_parcela_contrato_evento_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_evento_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_evento_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_evento_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('status_contrato_pagamento_id', '=', $this->id)->getIndexedArray('contrato_evento_id','{contrato_evento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_unidade_indexador_to_string($contrato_pagamento_parcela_unidade_indexador_to_string)
    {
        if(is_array($contrato_pagamento_parcela_unidade_indexador_to_string))
        {
            $values = UnidadeIndexador::where('id', 'in', $contrato_pagamento_parcela_unidade_indexador_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_unidade_indexador_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_unidade_indexador_to_string = $contrato_pagamento_parcela_unidade_indexador_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_unidade_indexador_to_string'] = $this->contrato_pagamento_parcela_unidade_indexador_to_string;
    }

    public function get_contrato_pagamento_parcela_unidade_indexador_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_unidade_indexador_to_string))
        {
            return $this->contrato_pagamento_parcela_unidade_indexador_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('status_contrato_pagamento_id', '=', $this->id)->getIndexedArray('unidade_indexador_id','{unidade_indexador->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_indexador_to_string($contrato_pagamento_parcela_contrato_indexador_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_indexador_to_string))
        {
            $values = ContratoPagamentoIndexador::where('id', 'in', $contrato_pagamento_parcela_contrato_indexador_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_contrato_indexador_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_indexador_to_string = $contrato_pagamento_parcela_contrato_indexador_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_indexador_to_string'] = $this->contrato_pagamento_parcela_contrato_indexador_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_indexador_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_indexador_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_indexador_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('status_contrato_pagamento_id', '=', $this->id)->getIndexedArray('contrato_indexador_id','{contrato_indexador->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_criacao_user_to_string($contrato_pagamento_parcela_criacao_user_to_string)
    {
        if(is_array($contrato_pagamento_parcela_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_parcela_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_parcela_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_criacao_user_to_string = $contrato_pagamento_parcela_criacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_criacao_user_to_string'] = $this->contrato_pagamento_parcela_criacao_user_to_string;
    }

    public function get_contrato_pagamento_parcela_criacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_criacao_user_to_string))
        {
            return $this->contrato_pagamento_parcela_criacao_user_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('status_contrato_pagamento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_modificacao_user_to_string($contrato_pagamento_parcela_modificacao_user_to_string)
    {
        if(is_array($contrato_pagamento_parcela_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_parcela_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_parcela_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_modificacao_user_to_string = $contrato_pagamento_parcela_modificacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_modificacao_user_to_string'] = $this->contrato_pagamento_parcela_modificacao_user_to_string;
    }

    public function get_contrato_pagamento_parcela_modificacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_modificacao_user_to_string))
        {
            return $this->contrato_pagamento_parcela_modificacao_user_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('status_contrato_pagamento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(ContratoPagamentoParcela::where('status_contrato_pagamento_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

