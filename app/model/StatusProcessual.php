<?php

class StatusProcessual extends TRecord
{
    const TABLENAME  = 'status_processual';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private TipoProcesso $tipo_processo;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tipo_processo_id');
        parent::addAttribute('nome');
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
     * Method set_tipo_processo
     * Sample of usage: $var->tipo_processo = $object;
     * @param $object Instance of TipoProcesso
     */
    public function set_tipo_processo(TipoProcesso $object)
    {
        $this->tipo_processo = $object;
        $this->tipo_processo_id = $object->id;
    }

    /**
     * Method get_tipo_processo
     * Sample of usage: $var->tipo_processo->attribute;
     * @returns TipoProcesso instance
     */
    public function get_tipo_processo()
    {
    
        // loads the associated object
        if (empty($this->tipo_processo))
            $this->tipo_processo = new TipoProcesso($this->tipo_processo_id);
    
        // returns the associated object
        return $this->tipo_processo;
    }

    /**
     * Method getProcessos
     */
    public function getProcessos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('status_processual_id', '=', $this->id));
        return Processo::getObjects( $criteria );
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('tribunal_id','{tribunal->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('foro_id','{foro->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('comarca_id','{comarca->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('vara_id','{vara->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('orgao_id','{orgao->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('assunto_id','{assunto->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('status_processual_id','{status_processual->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('responsavel_id','{responsavel->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('envolvimento_id','{envolvimento->nome}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Processo::where('status_processual_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

