<?php

class Bloqueio extends TRecord
{
    const TABLENAME  = 'bloqueio';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Agenda $agenda;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('agenda_id');
        parent::addAttribute('dt_inicio');
        parent::addAttribute('dt_final');
        parent::addAttribute('observacao');
        parent::addAttribute('horario_bloqueio_original');
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

    public function validate()
    {
        $agenda = $this->get_agenda();
    
        $dt_inicial = new DateTime($this->dt_inicio);
        $dt_final = new DateTime($this->dt_final);
    
        $hora_inicial = $dt_inicial->format('H:i');
        $hora_final = $dt_final->format('H:i');
        
        // valida bloqueios
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', $agenda->id));
    
    
        $criteriaTempoIni = new TCriteria;
        $criteriaTempoIni->add(new TFilter('dt_inicio', '<=', $dt_final->format('Y-m-d H:i')));
        $criteriaTempoIni->add(new TFilter('dt_final', '>=', $dt_inicial->format('Y-m-d H:i')));
        $criteria->add($criteriaTempoIni);
    
        $bloqueios = Bloqueio::countObjects($criteria);
    
        if ($bloqueios)
        {
            return false;
        }
    
        // valida compromissos
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', $agenda->id));
    
    
        $criteriaTempoIni = new TCriteria;
        $criteriaTempoIni->add(new TFilter('dt_inicio', '<=', $dt_final->format('Y-m-d H:i')));
        $criteriaTempoIni->add(new TFilter('dt_final', '>=', $dt_inicial->format('Y-m-d H:i')));
        $criteria->add($criteriaTempoIni);
    
        $compromissos = Compromisso::countObjects($criteria);
    
        if ($compromissos)
        {
            return false;
        }
    
        // valida agendamento
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', $agenda->id));
    
    
        $criteriaTempoIni = new TCriteria;
        $criteriaTempoIni->add(new TFilter('dt_inicio', '<=', $dt_final->format('Y-m-d H:i')));
        $criteriaTempoIni->add(new TFilter('dt_final', '>=', $dt_inicial->format('Y-m-d H:i')));
        $criteria->add($criteriaTempoIni);
    
        $agendamentos = Agendamento::countObjects($criteria);
    
        if ($agendamentos)
        {
            return false;
        }
    
        return true;
    }

}

