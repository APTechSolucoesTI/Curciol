<?php

class Estado extends TRecord
{
    const TABLENAME  = 'estado';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('sigla');
        parent::addAttribute('codigo_ibge');
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
     * Method getCidades
     */
    public function getCidades()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('estado_id', '=', $this->id));
        return Cidade::getObjects( $criteria );
    }

    public function set_cidade_estado_to_string($cidade_estado_to_string)
    {
        if(is_array($cidade_estado_to_string))
        {
            $values = Estado::where('id', 'in', $cidade_estado_to_string)->getIndexedArray('nome', 'nome');
            $this->cidade_estado_to_string = implode(', ', $values);
        }
        else
        {
            $this->cidade_estado_to_string = $cidade_estado_to_string;
        }

        $this->vdata['cidade_estado_to_string'] = $this->cidade_estado_to_string;
    }

    public function get_cidade_estado_to_string()
    {
        if(!empty($this->cidade_estado_to_string))
        {
            return $this->cidade_estado_to_string;
        }
    
        $values = Cidade::where('estado_id', '=', $this->id)->getIndexedArray('estado_id','{estado->nome}');
        return implode(', ', $values);
    }

    public function set_cidade_criacao_user_to_string($cidade_criacao_user_to_string)
    {
        if(is_array($cidade_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $cidade_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->cidade_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->cidade_criacao_user_to_string = $cidade_criacao_user_to_string;
        }

        $this->vdata['cidade_criacao_user_to_string'] = $this->cidade_criacao_user_to_string;
    }

    public function get_cidade_criacao_user_to_string()
    {
        if(!empty($this->cidade_criacao_user_to_string))
        {
            return $this->cidade_criacao_user_to_string;
        }
    
        $values = Cidade::where('estado_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_cidade_modificacao_user_to_string($cidade_modificacao_user_to_string)
    {
        if(is_array($cidade_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $cidade_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->cidade_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->cidade_modificacao_user_to_string = $cidade_modificacao_user_to_string;
        }

        $this->vdata['cidade_modificacao_user_to_string'] = $this->cidade_modificacao_user_to_string;
    }

    public function get_cidade_modificacao_user_to_string()
    {
        if(!empty($this->cidade_modificacao_user_to_string))
        {
            return $this->cidade_modificacao_user_to_string;
        }
    
        $values = Cidade::where('estado_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public static function fromIBGE($codigo)
    {
        return self::where('codigo_ibge', '=', $codigo)->first();
    }

}

