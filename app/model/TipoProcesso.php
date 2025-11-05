<?php

class TipoProcesso extends TRecord
{
    const TABLENAME  = 'tipo_processo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const JUDICIAL = '1';
    const EXTRAJUDICIAL = '2';

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
            
    }

    /**
     * Method getContratos
     */
    public function getContratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_processo_id', '=', $this->id));
        return Contrato::getObjects( $criteria );
    }
    /**
     * Method getEnvolvimentos
     */
    public function getEnvolvimentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_processo_id', '=', $this->id));
        return Envolvimento::getObjects( $criteria );
    }
    /**
     * Method getProcessos
     */
    public function getProcessos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_processo_id', '=', $this->id));
        return Processo::getObjects( $criteria );
    }
    /**
     * Method getStatusProcessuals
     */
    public function getStatusProcessuals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_processo_id', '=', $this->id));
        return StatusProcessual::getObjects( $criteria );
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
    
        $values = Contrato::where('tipo_processo_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
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
    
        $values = Contrato::where('tipo_processo_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
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
    
        $values = Contrato::where('tipo_processo_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
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
    
        $values = Contrato::where('tipo_processo_id', '=', $this->id)->getIndexedArray('contrato_status_id','{contrato_status->nome}');
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
    
        $values = Contrato::where('tipo_processo_id', '=', $this->id)->getIndexedArray('assunto_id','{assunto->nome}');
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
    
        $values = Contrato::where('tipo_processo_id', '=', $this->id)->getIndexedArray('envolvimento_id','{envolvimento->nome}');
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
    
        $values = Contrato::where('tipo_processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Contrato::where('tipo_processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_envolvimento_tipo_processo_to_string($envolvimento_tipo_processo_to_string)
    {
        if(is_array($envolvimento_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $envolvimento_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->envolvimento_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->envolvimento_tipo_processo_to_string = $envolvimento_tipo_processo_to_string;
        }

        $this->vdata['envolvimento_tipo_processo_to_string'] = $this->envolvimento_tipo_processo_to_string;
    }

    public function get_envolvimento_tipo_processo_to_string()
    {
        if(!empty($this->envolvimento_tipo_processo_to_string))
        {
            return $this->envolvimento_tipo_processo_to_string;
        }
    
        $values = Envolvimento::where('tipo_processo_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_envolvimento_criacao_user_to_string($envolvimento_criacao_user_to_string)
    {
        if(is_array($envolvimento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $envolvimento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->envolvimento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->envolvimento_criacao_user_to_string = $envolvimento_criacao_user_to_string;
        }

        $this->vdata['envolvimento_criacao_user_to_string'] = $this->envolvimento_criacao_user_to_string;
    }

    public function get_envolvimento_criacao_user_to_string()
    {
        if(!empty($this->envolvimento_criacao_user_to_string))
        {
            return $this->envolvimento_criacao_user_to_string;
        }
    
        $values = Envolvimento::where('tipo_processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_envolvimento_modificacao_user_to_string($envolvimento_modificacao_user_to_string)
    {
        if(is_array($envolvimento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $envolvimento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->envolvimento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->envolvimento_modificacao_user_to_string = $envolvimento_modificacao_user_to_string;
        }

        $this->vdata['envolvimento_modificacao_user_to_string'] = $this->envolvimento_modificacao_user_to_string;
    }

    public function get_envolvimento_modificacao_user_to_string()
    {
        if(!empty($this->envolvimento_modificacao_user_to_string))
        {
            return $this->envolvimento_modificacao_user_to_string;
        }
    
        $values = Envolvimento::where('tipo_processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_processo_tipo_processo_to_string($processo_tipo_processo_to_string)
    {
        if(is_array($processo_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $processo_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_tipo_processo_to_string = $processo_tipo_processo_to_string;
        }

        $this->vdata['processo_tipo_processo_to_string'] = $this->processo_tipo_processo_to_string;
    }

    public function get_processo_tipo_processo_to_string()
    {
        if(!empty($this->processo_tipo_processo_to_string))
        {
            return $this->processo_tipo_processo_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_processo_tribunal_to_string($processo_tribunal_to_string)
    {
        if(is_array($processo_tribunal_to_string))
        {
            $values = Tribunal::where('id', 'in', $processo_tribunal_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_tribunal_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_tribunal_to_string = $processo_tribunal_to_string;
        }

        $this->vdata['processo_tribunal_to_string'] = $this->processo_tribunal_to_string;
    }

    public function get_processo_tribunal_to_string()
    {
        if(!empty($this->processo_tribunal_to_string))
        {
            return $this->processo_tribunal_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('tribunal_id','{tribunal->nome}');
        return implode(', ', $values);
    }

    public function set_processo_foro_to_string($processo_foro_to_string)
    {
        if(is_array($processo_foro_to_string))
        {
            $values = Foro::where('id', 'in', $processo_foro_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_foro_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_foro_to_string = $processo_foro_to_string;
        }

        $this->vdata['processo_foro_to_string'] = $this->processo_foro_to_string;
    }

    public function get_processo_foro_to_string()
    {
        if(!empty($this->processo_foro_to_string))
        {
            return $this->processo_foro_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('foro_id','{foro->nome}');
        return implode(', ', $values);
    }

    public function set_processo_comarca_to_string($processo_comarca_to_string)
    {
        if(is_array($processo_comarca_to_string))
        {
            $values = Comarca::where('id', 'in', $processo_comarca_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_comarca_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_comarca_to_string = $processo_comarca_to_string;
        }

        $this->vdata['processo_comarca_to_string'] = $this->processo_comarca_to_string;
    }

    public function get_processo_comarca_to_string()
    {
        if(!empty($this->processo_comarca_to_string))
        {
            return $this->processo_comarca_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('comarca_id','{comarca->nome}');
        return implode(', ', $values);
    }

    public function set_processo_vara_to_string($processo_vara_to_string)
    {
        if(is_array($processo_vara_to_string))
        {
            $values = Vara::where('id', 'in', $processo_vara_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_vara_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_vara_to_string = $processo_vara_to_string;
        }

        $this->vdata['processo_vara_to_string'] = $this->processo_vara_to_string;
    }

    public function get_processo_vara_to_string()
    {
        if(!empty($this->processo_vara_to_string))
        {
            return $this->processo_vara_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('vara_id','{vara->nome}');
        return implode(', ', $values);
    }

    public function set_processo_orgao_to_string($processo_orgao_to_string)
    {
        if(is_array($processo_orgao_to_string))
        {
            $values = Orgao::where('id', 'in', $processo_orgao_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_orgao_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_orgao_to_string = $processo_orgao_to_string;
        }

        $this->vdata['processo_orgao_to_string'] = $this->processo_orgao_to_string;
    }

    public function get_processo_orgao_to_string()
    {
        if(!empty($this->processo_orgao_to_string))
        {
            return $this->processo_orgao_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('orgao_id','{orgao->nome}');
        return implode(', ', $values);
    }

    public function set_processo_area_to_string($processo_area_to_string)
    {
        if(is_array($processo_area_to_string))
        {
            $values = Area::where('id', 'in', $processo_area_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_area_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_area_to_string = $processo_area_to_string;
        }

        $this->vdata['processo_area_to_string'] = $this->processo_area_to_string;
    }

    public function get_processo_area_to_string()
    {
        if(!empty($this->processo_area_to_string))
        {
            return $this->processo_area_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
        return implode(', ', $values);
    }

    public function set_processo_assunto_to_string($processo_assunto_to_string)
    {
        if(is_array($processo_assunto_to_string))
        {
            $values = Assunto::where('id', 'in', $processo_assunto_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_assunto_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_assunto_to_string = $processo_assunto_to_string;
        }

        $this->vdata['processo_assunto_to_string'] = $this->processo_assunto_to_string;
    }

    public function get_processo_assunto_to_string()
    {
        if(!empty($this->processo_assunto_to_string))
        {
            return $this->processo_assunto_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('assunto_id','{assunto->nome}');
        return implode(', ', $values);
    }

    public function set_processo_status_processual_to_string($processo_status_processual_to_string)
    {
        if(is_array($processo_status_processual_to_string))
        {
            $values = StatusProcessual::where('id', 'in', $processo_status_processual_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_status_processual_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_status_processual_to_string = $processo_status_processual_to_string;
        }

        $this->vdata['processo_status_processual_to_string'] = $this->processo_status_processual_to_string;
    }

    public function get_processo_status_processual_to_string()
    {
        if(!empty($this->processo_status_processual_to_string))
        {
            return $this->processo_status_processual_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('status_processual_id','{status_processual->nome}');
        return implode(', ', $values);
    }

    public function set_processo_responsavel_to_string($processo_responsavel_to_string)
    {
        if(is_array($processo_responsavel_to_string))
        {
            $values = Pessoa::where('id', 'in', $processo_responsavel_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_responsavel_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_responsavel_to_string = $processo_responsavel_to_string;
        }

        $this->vdata['processo_responsavel_to_string'] = $this->processo_responsavel_to_string;
    }

    public function get_processo_responsavel_to_string()
    {
        if(!empty($this->processo_responsavel_to_string))
        {
            return $this->processo_responsavel_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('responsavel_id','{responsavel->nome}');
        return implode(', ', $values);
    }

    public function set_processo_envolvimento_to_string($processo_envolvimento_to_string)
    {
        if(is_array($processo_envolvimento_to_string))
        {
            $values = Envolvimento::where('id', 'in', $processo_envolvimento_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_envolvimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_envolvimento_to_string = $processo_envolvimento_to_string;
        }

        $this->vdata['processo_envolvimento_to_string'] = $this->processo_envolvimento_to_string;
    }

    public function get_processo_envolvimento_to_string()
    {
        if(!empty($this->processo_envolvimento_to_string))
        {
            return $this->processo_envolvimento_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('envolvimento_id','{envolvimento->nome}');
        return implode(', ', $values);
    }

    public function set_processo_criacao_user_to_string($processo_criacao_user_to_string)
    {
        if(is_array($processo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $processo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->processo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_criacao_user_to_string = $processo_criacao_user_to_string;
        }

        $this->vdata['processo_criacao_user_to_string'] = $this->processo_criacao_user_to_string;
    }

    public function get_processo_criacao_user_to_string()
    {
        if(!empty($this->processo_criacao_user_to_string))
        {
            return $this->processo_criacao_user_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_processo_modificacao_user_to_string($processo_modificacao_user_to_string)
    {
        if(is_array($processo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $processo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->processo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_modificacao_user_to_string = $processo_modificacao_user_to_string;
        }

        $this->vdata['processo_modificacao_user_to_string'] = $this->processo_modificacao_user_to_string;
    }

    public function get_processo_modificacao_user_to_string()
    {
        if(!empty($this->processo_modificacao_user_to_string))
        {
            return $this->processo_modificacao_user_to_string;
        }
    
        $values = Processo::where('tipo_processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_status_processual_tipo_processo_to_string($status_processual_tipo_processo_to_string)
    {
        if(is_array($status_processual_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $status_processual_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->status_processual_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->status_processual_tipo_processo_to_string = $status_processual_tipo_processo_to_string;
        }

        $this->vdata['status_processual_tipo_processo_to_string'] = $this->status_processual_tipo_processo_to_string;
    }

    public function get_status_processual_tipo_processo_to_string()
    {
        if(!empty($this->status_processual_tipo_processo_to_string))
        {
            return $this->status_processual_tipo_processo_to_string;
        }
    
        $values = StatusProcessual::where('tipo_processo_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_status_processual_criacao_user_to_string($status_processual_criacao_user_to_string)
    {
        if(is_array($status_processual_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $status_processual_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->status_processual_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->status_processual_criacao_user_to_string = $status_processual_criacao_user_to_string;
        }

        $this->vdata['status_processual_criacao_user_to_string'] = $this->status_processual_criacao_user_to_string;
    }

    public function get_status_processual_criacao_user_to_string()
    {
        if(!empty($this->status_processual_criacao_user_to_string))
        {
            return $this->status_processual_criacao_user_to_string;
        }
    
        $values = StatusProcessual::where('tipo_processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_status_processual_modificacao_user_to_string($status_processual_modificacao_user_to_string)
    {
        if(is_array($status_processual_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $status_processual_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->status_processual_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->status_processual_modificacao_user_to_string = $status_processual_modificacao_user_to_string;
        }

        $this->vdata['status_processual_modificacao_user_to_string'] = $this->status_processual_modificacao_user_to_string;
    }

    public function get_status_processual_modificacao_user_to_string()
    {
        if(!empty($this->status_processual_modificacao_user_to_string))
        {
            return $this->status_processual_modificacao_user_to_string;
        }
    
        $values = StatusProcessual::where('tipo_processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

