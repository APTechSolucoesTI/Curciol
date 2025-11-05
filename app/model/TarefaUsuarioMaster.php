<?php

class TarefaUsuarioMaster extends TRecord
{
    const TABLENAME  = 'tarefa_usuario_master';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $usuario_master;
    private TarefaConfiguracao $tarefa_configuracao;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tarefa_configuracao_id');
        parent::addAttribute('usuario_master_id');
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
    public function set_usuario_master(SystemUsers $object)
    {
        $this->usuario_master = $object;
        $this->usuario_master_id = $object->id;
    }

    /**
     * Method get_usuario_master
     * Sample of usage: $var->usuario_master->attribute;
     * @returns SystemUsers instance
     */
    public function get_usuario_master()
    {
    
        // loads the associated object
        if (empty($this->usuario_master))
            $this->usuario_master = new SystemUsers($this->usuario_master_id);
    
        // returns the associated object
        return $this->usuario_master;
    }
    /**
     * Method set_tarefa_configuracao
     * Sample of usage: $var->tarefa_configuracao = $object;
     * @param $object Instance of TarefaConfiguracao
     */
    public function set_tarefa_configuracao(TarefaConfiguracao $object)
    {
        $this->tarefa_configuracao = $object;
        $this->tarefa_configuracao_id = $object->id;
    }

    /**
     * Method get_tarefa_configuracao
     * Sample of usage: $var->tarefa_configuracao->attribute;
     * @returns TarefaConfiguracao instance
     */
    public function get_tarefa_configuracao()
    {
    
        // loads the associated object
        if (empty($this->tarefa_configuracao))
            $this->tarefa_configuracao = new TarefaConfiguracao($this->tarefa_configuracao_id);
    
        // returns the associated object
        return $this->tarefa_configuracao;
    }

    
}

