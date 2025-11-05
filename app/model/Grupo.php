<?php

class Grupo extends TRecord
{
    const TABLENAME  = 'grupo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    const PROFISSIONAL = '1';
    const CLIENTE = '2';
    const FORNECEDOR = '3';
    const REPRESENTANTE_LEGAL = '4';
    const CONTRAPARTE = '5';
    const PARCEIRO = '6';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('cor');
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
     * Method getPessoaGrupos
     */
    public function getPessoaGrupos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('grupo_id', '=', $this->id));
        return PessoaGrupo::getObjects( $criteria );
    }

    public function set_pessoa_grupo_pessoa_to_string($pessoa_grupo_pessoa_to_string)
    {
        if(is_array($pessoa_grupo_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $pessoa_grupo_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_grupo_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_grupo_pessoa_to_string = $pessoa_grupo_pessoa_to_string;
        }

        $this->vdata['pessoa_grupo_pessoa_to_string'] = $this->pessoa_grupo_pessoa_to_string;
    }

    public function get_pessoa_grupo_pessoa_to_string()
    {
        if(!empty($this->pessoa_grupo_pessoa_to_string))
        {
            return $this->pessoa_grupo_pessoa_to_string;
        }
    
        $values = PessoaGrupo::where('grupo_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_grupo_grupo_to_string($pessoa_grupo_grupo_to_string)
    {
        if(is_array($pessoa_grupo_grupo_to_string))
        {
            $values = Grupo::where('id', 'in', $pessoa_grupo_grupo_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_grupo_grupo_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_grupo_grupo_to_string = $pessoa_grupo_grupo_to_string;
        }

        $this->vdata['pessoa_grupo_grupo_to_string'] = $this->pessoa_grupo_grupo_to_string;
    }

    public function get_pessoa_grupo_grupo_to_string()
    {
        if(!empty($this->pessoa_grupo_grupo_to_string))
        {
            return $this->pessoa_grupo_grupo_to_string;
        }
    
        $values = PessoaGrupo::where('grupo_id', '=', $this->id)->getIndexedArray('grupo_id','{grupo->nome}');
        return implode(', ', $values);
    }

    
}

