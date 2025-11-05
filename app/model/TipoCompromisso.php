<?php

class TipoCompromisso extends TRecord
{
    const TABLENAME  = 'tipo_compromisso';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    const REUNIAO = '1';
    const PERICIA = '2';
    const AUDIENCIA = '3';
    const OUTROS = '4';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
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
     * Method getCompromissos
     */
    public function getCompromissos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_compromisso_id', '=', $this->id));
        return Compromisso::getObjects( $criteria );
    }

    public function set_compromisso_agenda_to_string($compromisso_agenda_to_string)
    {
        if(is_array($compromisso_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $compromisso_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->compromisso_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_agenda_to_string = $compromisso_agenda_to_string;
        }

        $this->vdata['compromisso_agenda_to_string'] = $this->compromisso_agenda_to_string;
    }

    public function get_compromisso_agenda_to_string()
    {
        if(!empty($this->compromisso_agenda_to_string))
        {
            return $this->compromisso_agenda_to_string;
        }
    
        $values = Compromisso::where('tipo_compromisso_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_compromisso_tipo_compromisso_to_string($compromisso_tipo_compromisso_to_string)
    {
        if(is_array($compromisso_tipo_compromisso_to_string))
        {
            $values = TipoCompromisso::where('id', 'in', $compromisso_tipo_compromisso_to_string)->getIndexedArray('nome', 'nome');
            $this->compromisso_tipo_compromisso_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_tipo_compromisso_to_string = $compromisso_tipo_compromisso_to_string;
        }

        $this->vdata['compromisso_tipo_compromisso_to_string'] = $this->compromisso_tipo_compromisso_to_string;
    }

    public function get_compromisso_tipo_compromisso_to_string()
    {
        if(!empty($this->compromisso_tipo_compromisso_to_string))
        {
            return $this->compromisso_tipo_compromisso_to_string;
        }
    
        $values = Compromisso::where('tipo_compromisso_id', '=', $this->id)->getIndexedArray('tipo_compromisso_id','{tipo_compromisso->nome}');
        return implode(', ', $values);
    }

    public function set_compromisso_criacao_user_to_string($compromisso_criacao_user_to_string)
    {
        if(is_array($compromisso_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $compromisso_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->compromisso_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_criacao_user_to_string = $compromisso_criacao_user_to_string;
        }

        $this->vdata['compromisso_criacao_user_to_string'] = $this->compromisso_criacao_user_to_string;
    }

    public function get_compromisso_criacao_user_to_string()
    {
        if(!empty($this->compromisso_criacao_user_to_string))
        {
            return $this->compromisso_criacao_user_to_string;
        }
    
        $values = Compromisso::where('tipo_compromisso_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_compromisso_modificacao_user_to_string($compromisso_modificacao_user_to_string)
    {
        if(is_array($compromisso_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $compromisso_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->compromisso_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_modificacao_user_to_string = $compromisso_modificacao_user_to_string;
        }

        $this->vdata['compromisso_modificacao_user_to_string'] = $this->compromisso_modificacao_user_to_string;
    }

    public function get_compromisso_modificacao_user_to_string()
    {
        if(!empty($this->compromisso_modificacao_user_to_string))
        {
            return $this->compromisso_modificacao_user_to_string;
        }
    
        $values = Compromisso::where('tipo_compromisso_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

