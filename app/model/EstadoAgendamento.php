<?php

class EstadoAgendamento extends TRecord
{
    const TABLENAME  = 'estado_agendamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Agendamento $agendamento;
    private EstadoAgenda $estado_agenda;
    private SystemUsers $system_users;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('agendamento_id');
        parent::addAttribute('estado_agenda_id');
        parent::addAttribute('system_users_id');
        parent::addAttribute('atribuido_em');
            
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
     * Method set_estado_agenda
     * Sample of usage: $var->estado_agenda = $object;
     * @param $object Instance of EstadoAgenda
     */
    public function set_estado_agenda(EstadoAgenda $object)
    {
        $this->estado_agenda = $object;
        $this->estado_agenda_id = $object->id;
    }

    /**
     * Method get_estado_agenda
     * Sample of usage: $var->estado_agenda->attribute;
     * @returns EstadoAgenda instance
     */
    public function get_estado_agenda()
    {
    
        // loads the associated object
        if (empty($this->estado_agenda))
            $this->estado_agenda = new EstadoAgenda($this->estado_agenda_id);
    
        // returns the associated object
        return $this->estado_agenda;
    }
    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_system_users(SystemUsers $object)
    {
        $this->system_users = $object;
        $this->system_users_id = $object->id;
    }

    /**
     * Method get_system_users
     * Sample of usage: $var->system_users->attribute;
     * @returns SystemUsers instance
     */
    public function get_system_users()
    {
    
        // loads the associated object
        if (empty($this->system_users))
            $this->system_users = new SystemUsers($this->system_users_id);
    
        // returns the associated object
        return $this->system_users;
    }

    
}

