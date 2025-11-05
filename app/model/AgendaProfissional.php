<?php

class AgendaProfissional extends TRecord
{
    const TABLENAME  = 'agenda_profissional';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Pessoa $profissional;
    private Agenda $agenda;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('profissional_id');
        parent::addAttribute('agenda_id');
        parent::addAttribute('fl_manipula_atendimento');
            
    }

    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_profissional(Pessoa $object)
    {
        $this->profissional = $object;
        $this->profissional_id = $object->id;
    }

    /**
     * Method get_profissional
     * Sample of usage: $var->profissional->attribute;
     * @returns Pessoa instance
     */
    public function get_profissional()
    {
    
        // loads the associated object
        if (empty($this->profissional))
            $this->profissional = new Pessoa($this->profissional_id);
    
        // returns the associated object
        return $this->profissional;
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

    
}

