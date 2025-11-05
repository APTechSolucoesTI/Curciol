<?php

class ContratoStatus extends TRecord
{
    const TABLENAME  = 'contrato_status';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('cor');
            
    }

    /**
     * Method getContratos
     */
    public function getContratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_status_id', '=', $this->id));
        return Contrato::getObjects( $criteria );
    }

    public function set_contrato_escritorio_to_string($contrato_escritorio_to_string)
    {
        if(is_array($contrato_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $contrato_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_escritorio_to_string = $contrato_escritorio_to_string;
        }

        $this->vdata['contrato_escritorio_to_string'] = $this->contrato_escritorio_to_string;
    }

    public function get_contrato_escritorio_to_string()
    {
        if(!empty($this->contrato_escritorio_to_string))
        {
            return $this->contrato_escritorio_to_string;
        }
    
        $values = Contrato::where('contrato_status_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_tipo_processo_to_string($contrato_tipo_processo_to_string)
    {
        if(is_array($contrato_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $contrato_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_tipo_processo_to_string = $contrato_tipo_processo_to_string;
        }

        $this->vdata['contrato_tipo_processo_to_string'] = $this->contrato_tipo_processo_to_string;
    }

    public function get_contrato_tipo_processo_to_string()
    {
        if(!empty($this->contrato_tipo_processo_to_string))
        {
            return $this->contrato_tipo_processo_to_string;
        }
    
        $values = Contrato::where('contrato_status_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_area_to_string($contrato_area_to_string)
    {
        if(is_array($contrato_area_to_string))
        {
            $values = Area::where('id', 'in', $contrato_area_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_area_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_area_to_string = $contrato_area_to_string;
        }

        $this->vdata['contrato_area_to_string'] = $this->contrato_area_to_string;
    }

    public function get_contrato_area_to_string()
    {
        if(!empty($this->contrato_area_to_string))
        {
            return $this->contrato_area_to_string;
        }
    
        $values = Contrato::where('contrato_status_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_contrato_status_to_string($contrato_contrato_status_to_string)
    {
        if(is_array($contrato_contrato_status_to_string))
        {
            $values = ContratoStatus::where('id', 'in', $contrato_contrato_status_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_contrato_status_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_contrato_status_to_string = $contrato_contrato_status_to_string;
        }

        $this->vdata['contrato_contrato_status_to_string'] = $this->contrato_contrato_status_to_string;
    }

    public function get_contrato_contrato_status_to_string()
    {
        if(!empty($this->contrato_contrato_status_to_string))
        {
            return $this->contrato_contrato_status_to_string;
        }
    
        $values = Contrato::where('contrato_status_id', '=', $this->id)->getIndexedArray('contrato_status_id','{contrato_status->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_assunto_to_string($contrato_assunto_to_string)
    {
        if(is_array($contrato_assunto_to_string))
        {
            $values = Assunto::where('id', 'in', $contrato_assunto_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_assunto_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_assunto_to_string = $contrato_assunto_to_string;
        }

        $this->vdata['contrato_assunto_to_string'] = $this->contrato_assunto_to_string;
    }

    public function get_contrato_assunto_to_string()
    {
        if(!empty($this->contrato_assunto_to_string))
        {
            return $this->contrato_assunto_to_string;
        }
    
        $values = Contrato::where('contrato_status_id', '=', $this->id)->getIndexedArray('assunto_id','{assunto->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_envolvimento_to_string($contrato_envolvimento_to_string)
    {
        if(is_array($contrato_envolvimento_to_string))
        {
            $values = Envolvimento::where('id', 'in', $contrato_envolvimento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_envolvimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_envolvimento_to_string = $contrato_envolvimento_to_string;
        }

        $this->vdata['contrato_envolvimento_to_string'] = $this->contrato_envolvimento_to_string;
    }

    public function get_contrato_envolvimento_to_string()
    {
        if(!empty($this->contrato_envolvimento_to_string))
        {
            return $this->contrato_envolvimento_to_string;
        }
    
        $values = Contrato::where('contrato_status_id', '=', $this->id)->getIndexedArray('envolvimento_id','{envolvimento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_criacao_user_to_string($contrato_criacao_user_to_string)
    {
        if(is_array($contrato_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_criacao_user_to_string = $contrato_criacao_user_to_string;
        }

        $this->vdata['contrato_criacao_user_to_string'] = $this->contrato_criacao_user_to_string;
    }

    public function get_contrato_criacao_user_to_string()
    {
        if(!empty($this->contrato_criacao_user_to_string))
        {
            return $this->contrato_criacao_user_to_string;
        }
    
        $values = Contrato::where('contrato_status_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_modificacao_user_to_string($contrato_modificacao_user_to_string)
    {
        if(is_array($contrato_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_modificacao_user_to_string = $contrato_modificacao_user_to_string;
        }

        $this->vdata['contrato_modificacao_user_to_string'] = $this->contrato_modificacao_user_to_string;
    }

    public function get_contrato_modificacao_user_to_string()
    {
        if(!empty($this->contrato_modificacao_user_to_string))
        {
            return $this->contrato_modificacao_user_to_string;
        }
    
        $values = Contrato::where('contrato_status_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(Contrato::where('contrato_status_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

