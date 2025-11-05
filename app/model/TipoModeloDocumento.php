<?php

class TipoModeloDocumento extends TRecord
{
    const TABLENAME  = 'tipo_modelo_documento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

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
     * Method getModeloDocumentos
     */
    public function getModeloDocumentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_modelo_documento_id', '=', $this->id));
        return ModeloDocumento::getObjects( $criteria );
    }

    public function set_modelo_documento_tipo_modelo_documento_to_string($modelo_documento_tipo_modelo_documento_to_string)
    {
        if(is_array($modelo_documento_tipo_modelo_documento_to_string))
        {
            $values = TipoModeloDocumento::where('id', 'in', $modelo_documento_tipo_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->modelo_documento_tipo_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_documento_tipo_modelo_documento_to_string = $modelo_documento_tipo_modelo_documento_to_string;
        }

        $this->vdata['modelo_documento_tipo_modelo_documento_to_string'] = $this->modelo_documento_tipo_modelo_documento_to_string;
    }

    public function get_modelo_documento_tipo_modelo_documento_to_string()
    {
        if(!empty($this->modelo_documento_tipo_modelo_documento_to_string))
        {
            return $this->modelo_documento_tipo_modelo_documento_to_string;
        }
    
        $values = ModeloDocumento::where('tipo_modelo_documento_id', '=', $this->id)->getIndexedArray('tipo_modelo_documento_id','{tipo_modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_modelo_documento_criacao_user_to_string($modelo_documento_criacao_user_to_string)
    {
        if(is_array($modelo_documento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $modelo_documento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->modelo_documento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_documento_criacao_user_to_string = $modelo_documento_criacao_user_to_string;
        }

        $this->vdata['modelo_documento_criacao_user_to_string'] = $this->modelo_documento_criacao_user_to_string;
    }

    public function get_modelo_documento_criacao_user_to_string()
    {
        if(!empty($this->modelo_documento_criacao_user_to_string))
        {
            return $this->modelo_documento_criacao_user_to_string;
        }
    
        $values = ModeloDocumento::where('tipo_modelo_documento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_modelo_documento_modificacao_user_to_string($modelo_documento_modificacao_user_to_string)
    {
        if(is_array($modelo_documento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $modelo_documento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->modelo_documento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_documento_modificacao_user_to_string = $modelo_documento_modificacao_user_to_string;
        }

        $this->vdata['modelo_documento_modificacao_user_to_string'] = $this->modelo_documento_modificacao_user_to_string;
    }

    public function get_modelo_documento_modificacao_user_to_string()
    {
        if(!empty($this->modelo_documento_modificacao_user_to_string))
        {
            return $this->modelo_documento_modificacao_user_to_string;
        }
    
        $values = ModeloDocumento::where('tipo_modelo_documento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

