<?php

class TarefaStatus extends TRecord
{
    const TABLENAME  = 'tarefa_status';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('kanban');
        parent::addAttribute('inicio');
        parent::addAttribute('fim');
        parent::addAttribute('cor');
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
     * Method getTarefas
     */
    public function getTarefas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tarefa_status_id', '=', $this->id));
        return Tarefa::getObjects( $criteria );
    }
    /**
     * Method getTarefaConfiguracaos
     */
    public function getTarefaConfiguracaosByStatusInicials()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('status_inicial_id', '=', $this->id));
        return TarefaConfiguracao::getObjects( $criteria );
    }
    /**
     * Method getTarefaConfiguracaos
     */
    public function getTarefaConfiguracaosByStatusFinals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('status_final_id', '=', $this->id));
        return TarefaConfiguracao::getObjects( $criteria );
    }
    /**
     * Method getTarefaConfiguracaos
     */
    public function getTarefaConfiguracaosByStatusCancelados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('status_cancelado_id', '=', $this->id));
        return TarefaConfiguracao::getObjects( $criteria );
    }

    public function set_tarefa_tarefa_status_to_string($tarefa_tarefa_status_to_string)
    {
        if(is_array($tarefa_tarefa_status_to_string))
        {
            $values = TarefaStatus::where('id', 'in', $tarefa_tarefa_status_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_tarefa_status_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_tarefa_status_to_string = $tarefa_tarefa_status_to_string;
        }

        $this->vdata['tarefa_tarefa_status_to_string'] = $this->tarefa_tarefa_status_to_string;
    }

    public function get_tarefa_tarefa_status_to_string()
    {
        if(!empty($this->tarefa_tarefa_status_to_string))
        {
            return $this->tarefa_tarefa_status_to_string;
        }
    
        $values = Tarefa::where('tarefa_status_id', '=', $this->id)->getIndexedArray('tarefa_status_id','{tarefa_status->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_publicacao_to_string($tarefa_publicacao_to_string)
    {
        if(is_array($tarefa_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $tarefa_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->tarefa_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_publicacao_to_string = $tarefa_publicacao_to_string;
        }

        $this->vdata['tarefa_publicacao_to_string'] = $this->tarefa_publicacao_to_string;
    }

    public function get_tarefa_publicacao_to_string()
    {
        if(!empty($this->tarefa_publicacao_to_string))
        {
            return $this->tarefa_publicacao_to_string;
        }
    
        $values = Tarefa::where('tarefa_status_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_tarefa_processo_to_string($tarefa_processo_to_string)
    {
        if(is_array($tarefa_processo_to_string))
        {
            $values = Processo::where('id', 'in', $tarefa_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->tarefa_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_processo_to_string = $tarefa_processo_to_string;
        }

        $this->vdata['tarefa_processo_to_string'] = $this->tarefa_processo_to_string;
    }

    public function get_tarefa_processo_to_string()
    {
        if(!empty($this->tarefa_processo_to_string))
        {
            return $this->tarefa_processo_to_string;
        }
    
        $values = Tarefa::where('tarefa_status_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_tarefa_usuario_destinatario_to_string($tarefa_usuario_destinatario_to_string)
    {
        if(is_array($tarefa_usuario_destinatario_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_usuario_destinatario_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_usuario_destinatario_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_usuario_destinatario_to_string = $tarefa_usuario_destinatario_to_string;
        }

        $this->vdata['tarefa_usuario_destinatario_to_string'] = $this->tarefa_usuario_destinatario_to_string;
    }

    public function get_tarefa_usuario_destinatario_to_string()
    {
        if(!empty($this->tarefa_usuario_destinatario_to_string))
        {
            return $this->tarefa_usuario_destinatario_to_string;
        }
    
        $values = Tarefa::where('tarefa_status_id', '=', $this->id)->getIndexedArray('usuario_destinatario_id','{usuario_destinatario->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_criacao_user_to_string($tarefa_criacao_user_to_string)
    {
        if(is_array($tarefa_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_criacao_user_to_string = $tarefa_criacao_user_to_string;
        }

        $this->vdata['tarefa_criacao_user_to_string'] = $this->tarefa_criacao_user_to_string;
    }

    public function get_tarefa_criacao_user_to_string()
    {
        if(!empty($this->tarefa_criacao_user_to_string))
        {
            return $this->tarefa_criacao_user_to_string;
        }
    
        $values = Tarefa::where('tarefa_status_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_modificacao_user_to_string($tarefa_modificacao_user_to_string)
    {
        if(is_array($tarefa_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_modificacao_user_to_string = $tarefa_modificacao_user_to_string;
        }

        $this->vdata['tarefa_modificacao_user_to_string'] = $this->tarefa_modificacao_user_to_string;
    }

    public function get_tarefa_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_modificacao_user_to_string))
        {
            return $this->tarefa_modificacao_user_to_string;
        }
    
        $values = Tarefa::where('tarefa_status_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_configuracao_status_inicial_to_string($tarefa_configuracao_status_inicial_to_string)
    {
        if(is_array($tarefa_configuracao_status_inicial_to_string))
        {
            $values = TarefaStatus::where('id', 'in', $tarefa_configuracao_status_inicial_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_configuracao_status_inicial_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_configuracao_status_inicial_to_string = $tarefa_configuracao_status_inicial_to_string;
        }

        $this->vdata['tarefa_configuracao_status_inicial_to_string'] = $this->tarefa_configuracao_status_inicial_to_string;
    }

    public function get_tarefa_configuracao_status_inicial_to_string()
    {
        if(!empty($this->tarefa_configuracao_status_inicial_to_string))
        {
            return $this->tarefa_configuracao_status_inicial_to_string;
        }
    
        $values = TarefaConfiguracao::where('status_cancelado_id', '=', $this->id)->getIndexedArray('status_inicial_id','{status_inicial->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_configuracao_status_final_to_string($tarefa_configuracao_status_final_to_string)
    {
        if(is_array($tarefa_configuracao_status_final_to_string))
        {
            $values = TarefaStatus::where('id', 'in', $tarefa_configuracao_status_final_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_configuracao_status_final_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_configuracao_status_final_to_string = $tarefa_configuracao_status_final_to_string;
        }

        $this->vdata['tarefa_configuracao_status_final_to_string'] = $this->tarefa_configuracao_status_final_to_string;
    }

    public function get_tarefa_configuracao_status_final_to_string()
    {
        if(!empty($this->tarefa_configuracao_status_final_to_string))
        {
            return $this->tarefa_configuracao_status_final_to_string;
        }
    
        $values = TarefaConfiguracao::where('status_cancelado_id', '=', $this->id)->getIndexedArray('status_final_id','{status_final->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_configuracao_status_cancelado_to_string($tarefa_configuracao_status_cancelado_to_string)
    {
        if(is_array($tarefa_configuracao_status_cancelado_to_string))
        {
            $values = TarefaStatus::where('id', 'in', $tarefa_configuracao_status_cancelado_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_configuracao_status_cancelado_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_configuracao_status_cancelado_to_string = $tarefa_configuracao_status_cancelado_to_string;
        }

        $this->vdata['tarefa_configuracao_status_cancelado_to_string'] = $this->tarefa_configuracao_status_cancelado_to_string;
    }

    public function get_tarefa_configuracao_status_cancelado_to_string()
    {
        if(!empty($this->tarefa_configuracao_status_cancelado_to_string))
        {
            return $this->tarefa_configuracao_status_cancelado_to_string;
        }
    
        $values = TarefaConfiguracao::where('status_cancelado_id', '=', $this->id)->getIndexedArray('status_cancelado_id','{status_cancelado->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_configuracao_modificacao_user_to_string($tarefa_configuracao_modificacao_user_to_string)
    {
        if(is_array($tarefa_configuracao_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_configuracao_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_configuracao_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_configuracao_modificacao_user_to_string = $tarefa_configuracao_modificacao_user_to_string;
        }

        $this->vdata['tarefa_configuracao_modificacao_user_to_string'] = $this->tarefa_configuracao_modificacao_user_to_string;
    }

    public function get_tarefa_configuracao_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_configuracao_modificacao_user_to_string))
        {
            return $this->tarefa_configuracao_modificacao_user_to_string;
        }
    
        $values = TarefaConfiguracao::where('status_cancelado_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

}

