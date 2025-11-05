<?php

class Cidade extends TRecord
{
    const TABLENAME  = 'cidade';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Estado $estado;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('estado_id');
        parent::addAttribute('nome');
        parent::addAttribute('codigo_ibge');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    
    }

    /**
     * Method set_estado
     * Sample of usage: $var->estado = $object;
     * @param $object Instance of Estado
     */
    public function set_estado(Estado $object)
    {
        $this->estado = $object;
        $this->estado_id = $object->id;
    }

    /**
     * Method get_estado
     * Sample of usage: $var->estado->attribute;
     * @returns Estado instance
     */
    public function get_estado()
    {
    
        // loads the associated object
        if (empty($this->estado))
            $this->estado = new Estado($this->estado_id);
    
        // returns the associated object
        return $this->estado;
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
     * Method getEscritorios
     */
    public function getEscritorios()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('cidade_id', '=', $this->id));
        return Escritorio::getObjects( $criteria );
    }
    /**
     * Method getPessoaEnderecos
     */
    public function getPessoaEnderecos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('cidade_id', '=', $this->id));
        return PessoaEndereco::getObjects( $criteria );
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
    
        $values = Escritorio::where('cidade_id', '=', $this->id)->getIndexedArray('system_unit_id','{system_unit->name}');
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
    
        $values = Escritorio::where('cidade_id', '=', $this->id)->getIndexedArray('cidade_id','{cidade->nome}');
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
    
        $values = Escritorio::where('cidade_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Escritorio::where('cidade_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_pessoa_endereco_pessoa_to_string($pessoa_endereco_pessoa_to_string)
    {
        if(is_array($pessoa_endereco_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $pessoa_endereco_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_endereco_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_endereco_pessoa_to_string = $pessoa_endereco_pessoa_to_string;
        }

        $this->vdata['pessoa_endereco_pessoa_to_string'] = $this->pessoa_endereco_pessoa_to_string;
    }

    public function get_pessoa_endereco_pessoa_to_string()
    {
        if(!empty($this->pessoa_endereco_pessoa_to_string))
        {
            return $this->pessoa_endereco_pessoa_to_string;
        }
    
        $values = PessoaEndereco::where('cidade_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_endereco_cidade_to_string($pessoa_endereco_cidade_to_string)
    {
        if(is_array($pessoa_endereco_cidade_to_string))
        {
            $values = Cidade::where('id', 'in', $pessoa_endereco_cidade_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_endereco_cidade_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_endereco_cidade_to_string = $pessoa_endereco_cidade_to_string;
        }

        $this->vdata['pessoa_endereco_cidade_to_string'] = $this->pessoa_endereco_cidade_to_string;
    }

    public function get_pessoa_endereco_cidade_to_string()
    {
        if(!empty($this->pessoa_endereco_cidade_to_string))
        {
            return $this->pessoa_endereco_cidade_to_string;
        }
    
        $values = PessoaEndereco::where('cidade_id', '=', $this->id)->getIndexedArray('cidade_id','{cidade->nome}');
        return implode(', ', $values);
    }

    public static function fromIBGE($codigo)
    {
        return self::where('codigo_ibge', '=', $codigo)->first();
    }

    
}

