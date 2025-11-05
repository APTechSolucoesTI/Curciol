<?php

class ConfigBuscaAPartir extends TRecord
{
    const TABLENAME  = 'config_busca_a_partir';
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
        parent::addAttribute('add_dias');
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
     * Method getConfigBuscaPrazos
     */
    public function getConfigBuscaPrazos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('config_busca_a_partir_id', '=', $this->id));
        return ConfigBuscaPrazo::getObjects( $criteria );
    }

    public function set_config_busca_prazo_tipo_prazo_to_string($config_busca_prazo_tipo_prazo_to_string)
    {
        if(is_array($config_busca_prazo_tipo_prazo_to_string))
        {
            $values = TipoPrazo::where('id', 'in', $config_busca_prazo_tipo_prazo_to_string)->getIndexedArray('nome', 'nome');
            $this->config_busca_prazo_tipo_prazo_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_prazo_tipo_prazo_to_string = $config_busca_prazo_tipo_prazo_to_string;
        }

        $this->vdata['config_busca_prazo_tipo_prazo_to_string'] = $this->config_busca_prazo_tipo_prazo_to_string;
    }

    public function get_config_busca_prazo_tipo_prazo_to_string()
    {
        if(!empty($this->config_busca_prazo_tipo_prazo_to_string))
        {
            return $this->config_busca_prazo_tipo_prazo_to_string;
        }
    
        $values = ConfigBuscaPrazo::where('config_busca_a_partir_id', '=', $this->id)->getIndexedArray('tipo_prazo_id','{tipo_prazo->nome}');
        return implode(', ', $values);
    }

    public function set_config_busca_prazo_config_busca_a_partir_to_string($config_busca_prazo_config_busca_a_partir_to_string)
    {
        if(is_array($config_busca_prazo_config_busca_a_partir_to_string))
        {
            $values = ConfigBuscaAPartir::where('id', 'in', $config_busca_prazo_config_busca_a_partir_to_string)->getIndexedArray('nome', 'nome');
            $this->config_busca_prazo_config_busca_a_partir_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_prazo_config_busca_a_partir_to_string = $config_busca_prazo_config_busca_a_partir_to_string;
        }

        $this->vdata['config_busca_prazo_config_busca_a_partir_to_string'] = $this->config_busca_prazo_config_busca_a_partir_to_string;
    }

    public function get_config_busca_prazo_config_busca_a_partir_to_string()
    {
        if(!empty($this->config_busca_prazo_config_busca_a_partir_to_string))
        {
            return $this->config_busca_prazo_config_busca_a_partir_to_string;
        }
    
        $values = ConfigBuscaPrazo::where('config_busca_a_partir_id', '=', $this->id)->getIndexedArray('config_busca_a_partir_id','{config_busca_a_partir->nome}');
        return implode(', ', $values);
    }

    public function set_config_busca_prazo_criacao_user_to_string($config_busca_prazo_criacao_user_to_string)
    {
        if(is_array($config_busca_prazo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $config_busca_prazo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->config_busca_prazo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_prazo_criacao_user_to_string = $config_busca_prazo_criacao_user_to_string;
        }

        $this->vdata['config_busca_prazo_criacao_user_to_string'] = $this->config_busca_prazo_criacao_user_to_string;
    }

    public function get_config_busca_prazo_criacao_user_to_string()
    {
        if(!empty($this->config_busca_prazo_criacao_user_to_string))
        {
            return $this->config_busca_prazo_criacao_user_to_string;
        }
    
        $values = ConfigBuscaPrazo::where('config_busca_a_partir_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_config_busca_prazo_modificacao_user_to_string($config_busca_prazo_modificacao_user_to_string)
    {
        if(is_array($config_busca_prazo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $config_busca_prazo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->config_busca_prazo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_prazo_modificacao_user_to_string = $config_busca_prazo_modificacao_user_to_string;
        }

        $this->vdata['config_busca_prazo_modificacao_user_to_string'] = $this->config_busca_prazo_modificacao_user_to_string;
    }

    public function get_config_busca_prazo_modificacao_user_to_string()
    {
        if(!empty($this->config_busca_prazo_modificacao_user_to_string))
        {
            return $this->config_busca_prazo_modificacao_user_to_string;
        }
    
        $values = ConfigBuscaPrazo::where('config_busca_a_partir_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(ConfigBuscaPrazo::where('config_busca_a_partir_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

