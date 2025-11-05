<?php

class TarefaConfiguracao extends TRecord
{
    const TABLENAME  = 'tarefa_configuracao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $modificacao_user;
    private TarefaStatus $status_inicial;
    private TarefaStatus $status_final;
    private TarefaStatus $status_cancelado;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('status_inicial_id');
        parent::addAttribute('status_final_id');
        parent::addAttribute('status_cancelado_id');
        parent::addAttribute('tem_dtvalidacao');
        parent::addAttribute('dtvalidacao_obrigatoria');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
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
     * Method set_tarefa_status
     * Sample of usage: $var->tarefa_status = $object;
     * @param $object Instance of TarefaStatus
     */
    public function set_status_inicial(TarefaStatus $object)
    {
        $this->status_inicial = $object;
        $this->status_inicial_id = $object->id;
    }

    /**
     * Method get_status_inicial
     * Sample of usage: $var->status_inicial->attribute;
     * @returns TarefaStatus instance
     */
    public function get_status_inicial()
    {
    
        // loads the associated object
        if (empty($this->status_inicial))
            $this->status_inicial = new TarefaStatus($this->status_inicial_id);
    
        // returns the associated object
        return $this->status_inicial;
    }
    /**
     * Method set_tarefa_status
     * Sample of usage: $var->tarefa_status = $object;
     * @param $object Instance of TarefaStatus
     */
    public function set_status_final(TarefaStatus $object)
    {
        $this->status_final = $object;
        $this->status_final_id = $object->id;
    }

    /**
     * Method get_status_final
     * Sample of usage: $var->status_final->attribute;
     * @returns TarefaStatus instance
     */
    public function get_status_final()
    {
    
        // loads the associated object
        if (empty($this->status_final))
            $this->status_final = new TarefaStatus($this->status_final_id);
    
        // returns the associated object
        return $this->status_final;
    }
    /**
     * Method set_tarefa_status
     * Sample of usage: $var->tarefa_status = $object;
     * @param $object Instance of TarefaStatus
     */
    public function set_status_cancelado(TarefaStatus $object)
    {
        $this->status_cancelado = $object;
        $this->status_cancelado_id = $object->id;
    }

    /**
     * Method get_status_cancelado
     * Sample of usage: $var->status_cancelado->attribute;
     * @returns TarefaStatus instance
     */
    public function get_status_cancelado()
    {
    
        // loads the associated object
        if (empty($this->status_cancelado))
            $this->status_cancelado = new TarefaStatus($this->status_cancelado_id);
    
        // returns the associated object
        return $this->status_cancelado;
    }

    /**
     * Method getTarefaUsuarioMasters
     */
    public function getTarefaUsuarioMasters()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tarefa_configuracao_id', '=', $this->id));
        return TarefaUsuarioMaster::getObjects( $criteria );
    }

    public function set_tarefa_usuario_master_tarefa_configuracao_to_string($tarefa_usuario_master_tarefa_configuracao_to_string)
    {
        if(is_array($tarefa_usuario_master_tarefa_configuracao_to_string))
        {
            $values = TarefaConfiguracao::where('id', 'in', $tarefa_usuario_master_tarefa_configuracao_to_string)->getIndexedArray('id', 'id');
            $this->tarefa_usuario_master_tarefa_configuracao_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_usuario_master_tarefa_configuracao_to_string = $tarefa_usuario_master_tarefa_configuracao_to_string;
        }

        $this->vdata['tarefa_usuario_master_tarefa_configuracao_to_string'] = $this->tarefa_usuario_master_tarefa_configuracao_to_string;
    }

    public function get_tarefa_usuario_master_tarefa_configuracao_to_string()
    {
        if(!empty($this->tarefa_usuario_master_tarefa_configuracao_to_string))
        {
            return $this->tarefa_usuario_master_tarefa_configuracao_to_string;
        }
    
        $values = TarefaUsuarioMaster::where('tarefa_configuracao_id', '=', $this->id)->getIndexedArray('tarefa_configuracao_id','{tarefa_configuracao->id}');
        return implode(', ', $values);
    }

    public function set_tarefa_usuario_master_usuario_master_to_string($tarefa_usuario_master_usuario_master_to_string)
    {
        if(is_array($tarefa_usuario_master_usuario_master_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_usuario_master_usuario_master_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_usuario_master_usuario_master_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_usuario_master_usuario_master_to_string = $tarefa_usuario_master_usuario_master_to_string;
        }

        $this->vdata['tarefa_usuario_master_usuario_master_to_string'] = $this->tarefa_usuario_master_usuario_master_to_string;
    }

    public function get_tarefa_usuario_master_usuario_master_to_string()
    {
        if(!empty($this->tarefa_usuario_master_usuario_master_to_string))
        {
            return $this->tarefa_usuario_master_usuario_master_to_string;
        }
    
        $values = TarefaUsuarioMaster::where('tarefa_configuracao_id', '=', $this->id)->getIndexedArray('usuario_master_id','{usuario_master->name}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(TarefaUsuarioMaster::where('tarefa_configuracao_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

