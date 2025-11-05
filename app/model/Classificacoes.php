<?php

class Classificacoes extends TRecord
{
    const TABLENAME  = 'classificacoes';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    const SERVIDOR_PUBLICO = '1';

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
     * Method getClassificacoesClientes
     */
    public function getClassificacoesClientes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('classificacoes_id', '=', $this->id));
        return ClassificacoesCliente::getObjects( $criteria );
    }

    public function set_classificacoes_cliente_pessoa_to_string($classificacoes_cliente_pessoa_to_string)
    {
        if(is_array($classificacoes_cliente_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $classificacoes_cliente_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->classificacoes_cliente_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_cliente_pessoa_to_string = $classificacoes_cliente_pessoa_to_string;
        }

        $this->vdata['classificacoes_cliente_pessoa_to_string'] = $this->classificacoes_cliente_pessoa_to_string;
    }

    public function get_classificacoes_cliente_pessoa_to_string()
    {
        if(!empty($this->classificacoes_cliente_pessoa_to_string))
        {
            return $this->classificacoes_cliente_pessoa_to_string;
        }
    
        $values = ClassificacoesCliente::where('classificacoes_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_classificacoes_cliente_classificacoes_to_string($classificacoes_cliente_classificacoes_to_string)
    {
        if(is_array($classificacoes_cliente_classificacoes_to_string))
        {
            $values = Classificacoes::where('id', 'in', $classificacoes_cliente_classificacoes_to_string)->getIndexedArray('nome', 'nome');
            $this->classificacoes_cliente_classificacoes_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_cliente_classificacoes_to_string = $classificacoes_cliente_classificacoes_to_string;
        }

        $this->vdata['classificacoes_cliente_classificacoes_to_string'] = $this->classificacoes_cliente_classificacoes_to_string;
    }

    public function get_classificacoes_cliente_classificacoes_to_string()
    {
        if(!empty($this->classificacoes_cliente_classificacoes_to_string))
        {
            return $this->classificacoes_cliente_classificacoes_to_string;
        }
    
        $values = ClassificacoesCliente::where('classificacoes_id', '=', $this->id)->getIndexedArray('classificacoes_id','{classificacoes->nome}');
        return implode(', ', $values);
    }

    
}

