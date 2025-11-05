<?php

class Compromisso extends TRecord
{
    const TABLENAME  = 'compromisso';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Agenda $agenda;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private TipoCompromisso $tipo_compromisso;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('agenda_id');
        parent::addAttribute('tipo_compromisso_id');
        parent::addAttribute('dt_inicio');
        parent::addAttribute('dt_final');
        parent::addAttribute('observacao');
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
    /**
     * Method set_tipo_compromisso
     * Sample of usage: $var->tipo_compromisso = $object;
     * @param $object Instance of TipoCompromisso
     */
    public function set_tipo_compromisso(TipoCompromisso $object)
    {
        $this->tipo_compromisso = $object;
        $this->tipo_compromisso_id = $object->id;
    }

    /**
     * Method get_tipo_compromisso
     * Sample of usage: $var->tipo_compromisso->attribute;
     * @returns TipoCompromisso instance
     */
    public function get_tipo_compromisso()
    {
    
        // loads the associated object
        if (empty($this->tipo_compromisso))
            $this->tipo_compromisso = new TipoCompromisso($this->tipo_compromisso_id);
    
        // returns the associated object
        return $this->tipo_compromisso;
    }

    /**
     * Method getConvidadoCompromissos
     */
    public function getConvidadoCompromissos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('compromisso_id', '=', $this->id));
        return ConvidadoCompromisso::getObjects( $criteria );
    }

    public function set_convidado_compromisso_compromisso_to_string($convidado_compromisso_compromisso_to_string)
    {
        if(is_array($convidado_compromisso_compromisso_to_string))
        {
            $values = Compromisso::where('id', 'in', $convidado_compromisso_compromisso_to_string)->getIndexedArray('id', 'id');
            $this->convidado_compromisso_compromisso_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_compromisso_to_string = $convidado_compromisso_compromisso_to_string;
        }

        $this->vdata['convidado_compromisso_compromisso_to_string'] = $this->convidado_compromisso_compromisso_to_string;
    }

    public function get_convidado_compromisso_compromisso_to_string()
    {
        if(!empty($this->convidado_compromisso_compromisso_to_string))
        {
            return $this->convidado_compromisso_compromisso_to_string;
        }
    
        $values = ConvidadoCompromisso::where('compromisso_id', '=', $this->id)->getIndexedArray('compromisso_id','{compromisso->id}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_agenda_to_string($convidado_compromisso_agenda_to_string)
    {
        if(is_array($convidado_compromisso_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $convidado_compromisso_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->convidado_compromisso_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_agenda_to_string = $convidado_compromisso_agenda_to_string;
        }

        $this->vdata['convidado_compromisso_agenda_to_string'] = $this->convidado_compromisso_agenda_to_string;
    }

    public function get_convidado_compromisso_agenda_to_string()
    {
        if(!empty($this->convidado_compromisso_agenda_to_string))
        {
            return $this->convidado_compromisso_agenda_to_string;
        }
    
        $values = ConvidadoCompromisso::where('compromisso_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_criacao_user_to_string($convidado_compromisso_criacao_user_to_string)
    {
        if(is_array($convidado_compromisso_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_compromisso_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_compromisso_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_criacao_user_to_string = $convidado_compromisso_criacao_user_to_string;
        }

        $this->vdata['convidado_compromisso_criacao_user_to_string'] = $this->convidado_compromisso_criacao_user_to_string;
    }

    public function get_convidado_compromisso_criacao_user_to_string()
    {
        if(!empty($this->convidado_compromisso_criacao_user_to_string))
        {
            return $this->convidado_compromisso_criacao_user_to_string;
        }
    
        $values = ConvidadoCompromisso::where('compromisso_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_modificacao_user_to_string($convidado_compromisso_modificacao_user_to_string)
    {
        if(is_array($convidado_compromisso_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_compromisso_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_compromisso_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_modificacao_user_to_string = $convidado_compromisso_modificacao_user_to_string;
        }

        $this->vdata['convidado_compromisso_modificacao_user_to_string'] = $this->convidado_compromisso_modificacao_user_to_string;
    }

    public function get_convidado_compromisso_modificacao_user_to_string()
    {
        if(!empty($this->convidado_compromisso_modificacao_user_to_string))
        {
            return $this->convidado_compromisso_modificacao_user_to_string;
        }
    
        $values = ConvidadoCompromisso::where('compromisso_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

