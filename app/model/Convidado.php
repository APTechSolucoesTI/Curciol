<?php

class Convidado extends TRecord
{
    const TABLENAME  = 'convidado';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Agenda $agenda;
    private Agendamento $agendamento;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('agendamento_id');
        parent::addAttribute('agenda_id');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
    }

    /**
     * Method set_agenda
     * Sample of usage: $var->agenda = $object;
     * @param $object Instance of Agenda
     */
    public function set_agenda(Agenda $object)
    {
        $this->agenda = $object;
        $this->agenda_id = $object->id;
    }

    /**
     * Method get_agenda
     * Sample of usage: $var->agenda->attribute;
     * @returns Agenda instance
     */
    public function get_agenda()
    {
    
        // loads the associated object
        if (empty($this->agenda))
            $this->agenda = new Agenda($this->agenda_id);
    
        // returns the associated object
        return $this->agenda;
    }
    /**
     * Method set_agendamento
     * Sample of usage: $var->agendamento = $object;
     * @param $object Instance of Agendamento
     */
    public function set_agendamento(Agendamento $object)
    {
        $this->agendamento = $object;
        $this->agendamento_id = $object->id;
    }

    /**
     * Method get_agendamento
     * Sample of usage: $var->agendamento->attribute;
     * @returns Agendamento instance
     */
    public function get_agendamento()
    {
    
        // loads the associated object
        if (empty($this->agendamento))
            $this->agendamento = new Agendamento($this->agendamento_id);
    
        // returns the associated object
        return $this->agendamento;
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

    
}

