<?php

class SystemUnit extends TRecord
{
    const TABLENAME  = 'system_unit';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'max'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('name');
        parent::addAttribute('connection_name');
            
    }

    /**
     * Method getEscritorios
     */
    public function getEscritorios()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('system_unit_id', '=', $this->id));
        return Escritorio::getObjects( $criteria );
    }
    /**
     * Method getLogCrontabs
     */
    public function getLogCrontabs()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('system_unit_id', '=', $this->id));
        return LogCrontab::getObjects( $criteria );
    }

    public function set_escritorio_system_unit_to_string($escritorio_system_unit_to_string)
    {
        if(is_array($escritorio_system_unit_to_string))
        {
            $values = SystemUnit::where('id', 'in', $escritorio_system_unit_to_string)->getIndexedArray('name', 'name');
            $this->escritorio_system_unit_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_system_unit_to_string = $escritorio_system_unit_to_string;
        }

        $this->vdata['escritorio_system_unit_to_string'] = $this->escritorio_system_unit_to_string;
    }

    public function get_escritorio_system_unit_to_string()
    {
        if(!empty($this->escritorio_system_unit_to_string))
        {
            return $this->escritorio_system_unit_to_string;
        }
    
        $values = Escritorio::where('system_unit_id', '=', $this->id)->getIndexedArray('system_unit_id','{system_unit->name}');
        return implode(', ', $values);
    }

    public function set_escritorio_cidade_to_string($escritorio_cidade_to_string)
    {
        if(is_array($escritorio_cidade_to_string))
        {
            $values = Cidade::where('id', 'in', $escritorio_cidade_to_string)->getIndexedArray('nome', 'nome');
            $this->escritorio_cidade_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_cidade_to_string = $escritorio_cidade_to_string;
        }

        $this->vdata['escritorio_cidade_to_string'] = $this->escritorio_cidade_to_string;
    }

    public function get_escritorio_cidade_to_string()
    {
        if(!empty($this->escritorio_cidade_to_string))
        {
            return $this->escritorio_cidade_to_string;
        }
    
        $values = Escritorio::where('system_unit_id', '=', $this->id)->getIndexedArray('cidade_id','{cidade->nome}');
        return implode(', ', $values);
    }

    public function set_escritorio_criacao_user_to_string($escritorio_criacao_user_to_string)
    {
        if(is_array($escritorio_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $escritorio_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->escritorio_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_criacao_user_to_string = $escritorio_criacao_user_to_string;
        }

        $this->vdata['escritorio_criacao_user_to_string'] = $this->escritorio_criacao_user_to_string;
    }

    public function get_escritorio_criacao_user_to_string()
    {
        if(!empty($this->escritorio_criacao_user_to_string))
        {
            return $this->escritorio_criacao_user_to_string;
        }
    
        $values = Escritorio::where('system_unit_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_escritorio_modificacao_user_to_string($escritorio_modificacao_user_to_string)
    {
        if(is_array($escritorio_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $escritorio_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->escritorio_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_modificacao_user_to_string = $escritorio_modificacao_user_to_string;
        }

        $this->vdata['escritorio_modificacao_user_to_string'] = $this->escritorio_modificacao_user_to_string;
    }

    public function get_escritorio_modificacao_user_to_string()
    {
        if(!empty($this->escritorio_modificacao_user_to_string))
        {
            return $this->escritorio_modificacao_user_to_string;
        }
    
        $values = Escritorio::where('system_unit_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_log_crontab_system_unit_to_string($log_crontab_system_unit_to_string)
    {
        if(is_array($log_crontab_system_unit_to_string))
        {
            $values = SystemUnit::where('id', 'in', $log_crontab_system_unit_to_string)->getIndexedArray('name', 'name');
            $this->log_crontab_system_unit_to_string = implode(', ', $values);
        }
        else
        {
            $this->log_crontab_system_unit_to_string = $log_crontab_system_unit_to_string;
        }

        $this->vdata['log_crontab_system_unit_to_string'] = $this->log_crontab_system_unit_to_string;
    }

    public function get_log_crontab_system_unit_to_string()
    {
        if(!empty($this->log_crontab_system_unit_to_string))
        {
            return $this->log_crontab_system_unit_to_string;
        }
    
        $values = LogCrontab::where('system_unit_id', '=', $this->id)->getIndexedArray('system_unit_id','{system_unit->name}');
        return implode(', ', $values);
    }

    
}

