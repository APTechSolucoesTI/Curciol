<?php

class TarefaHorasTrabalhadas extends TRecord
{
    const TABLENAME  = 'tarefa_horas_trabalhadas';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';

    const CREATEDAT  = 'data_criacao';

    private Tarefa $tarefa;
    private SystemUsers $criacao_user;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tarefa_id');
        parent::addAttribute('data_inicio');
        parent::addAttribute('data_fim');
        parent::addAttribute('observacao');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
            
    }

    /**
     * Method set_tarefa
     * Sample of usage: $var->tarefa = $object;
     * @param $object Instance of Tarefa
     */
    public function set_tarefa(Tarefa $object)
    {
        $this->tarefa = $object;
        $this->tarefa_id = $object->id;
    }

    /**
     * Method get_tarefa
     * Sample of usage: $var->tarefa->attribute;
     * @returns Tarefa instance
     */
    public function get_tarefa()
    {
    
        // loads the associated object
        if (empty($this->tarefa))
            $this->tarefa = new Tarefa($this->tarefa_id);
    
        // returns the associated object
        return $this->tarefa;
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

    
}

