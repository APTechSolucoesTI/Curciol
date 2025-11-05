<?php

class ConfigBuscaPrazo extends TRecord
{
    const TABLENAME  = 'config_busca_prazo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private TipoPrazo $tipo_prazo;
    private ConfigBuscaAPartir $config_busca_a_partir;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('titulo');
        parent::addAttribute('prazo');
        parent::addAttribute('tipo_prazo_id');
        parent::addAttribute('config_busca_a_partir_id');
        parent::addAttribute('pont');
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
     * Method set_tipo_prazo
     * Sample of usage: $var->tipo_prazo = $object;
     * @param $object Instance of TipoPrazo
     */
    public function set_tipo_prazo(TipoPrazo $object)
    {
        $this->tipo_prazo = $object;
        $this->tipo_prazo_id = $object->id;
    }

    /**
     * Method get_tipo_prazo
     * Sample of usage: $var->tipo_prazo->attribute;
     * @returns TipoPrazo instance
     */
    public function get_tipo_prazo()
    {
    
        // loads the associated object
        if (empty($this->tipo_prazo))
            $this->tipo_prazo = new TipoPrazo($this->tipo_prazo_id);
    
        // returns the associated object
        return $this->tipo_prazo;
    }
    /**
     * Method set_config_busca_a_partir
     * Sample of usage: $var->config_busca_a_partir = $object;
     * @param $object Instance of ConfigBuscaAPartir
     */
    public function set_config_busca_a_partir(ConfigBuscaAPartir $object)
    {
        $this->config_busca_a_partir = $object;
        $this->config_busca_a_partir_id = $object->id;
    }

    /**
     * Method get_config_busca_a_partir
     * Sample of usage: $var->config_busca_a_partir->attribute;
     * @returns ConfigBuscaAPartir instance
     */
    public function get_config_busca_a_partir()
    {
    
        // loads the associated object
        if (empty($this->config_busca_a_partir))
            $this->config_busca_a_partir = new ConfigBuscaAPartir($this->config_busca_a_partir_id);
    
        // returns the associated object
        return $this->config_busca_a_partir;
    }

    /**
     * Method getConfigBuscaPrazoTextos
     */
    public function getConfigBuscaPrazoTextos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('config_busca_prazo_id', '=', $this->id));
        return ConfigBuscaPrazoTexto::getObjects( $criteria );
    }
    /**
     * Method getPublicacaoSugestaoPrazos
     */
    public function getPublicacaoSugestaoPrazos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('config_busca_prazo_id', '=', $this->id));
        return PublicacaoSugestaoPrazo::getObjects( $criteria );
    }

    public function set_config_busca_prazo_texto_config_busca_prazo_to_string($config_busca_prazo_texto_config_busca_prazo_to_string)
    {
        if(is_array($config_busca_prazo_texto_config_busca_prazo_to_string))
        {
            $values = ConfigBuscaPrazo::where('id', 'in', $config_busca_prazo_texto_config_busca_prazo_to_string)->getIndexedArray('titulo', 'titulo');
            $this->config_busca_prazo_texto_config_busca_prazo_to_string = implode(', ', $values);
        }
        else
        {
            $this->config_busca_prazo_texto_config_busca_prazo_to_string = $config_busca_prazo_texto_config_busca_prazo_to_string;
        }

        $this->vdata['config_busca_prazo_texto_config_busca_prazo_to_string'] = $this->config_busca_prazo_texto_config_busca_prazo_to_string;
    }

    public function get_config_busca_prazo_texto_config_busca_prazo_to_string()
    {
        if(!empty($this->config_busca_prazo_texto_config_busca_prazo_to_string))
        {
            return $this->config_busca_prazo_texto_config_busca_prazo_to_string;
        }
    
        $values = ConfigBuscaPrazoTexto::where('config_busca_prazo_id', '=', $this->id)->getIndexedArray('config_busca_prazo_id','{config_busca_prazo->titulo}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_publicacao_to_string($publicacao_sugestao_prazo_publicacao_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $publicacao_sugestao_prazo_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->publicacao_sugestao_prazo_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_publicacao_to_string = $publicacao_sugestao_prazo_publicacao_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_publicacao_to_string'] = $this->publicacao_sugestao_prazo_publicacao_to_string;
    }

    public function get_publicacao_sugestao_prazo_publicacao_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_publicacao_to_string))
        {
            return $this->publicacao_sugestao_prazo_publicacao_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('config_busca_prazo_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_config_busca_prazo_to_string($publicacao_sugestao_prazo_config_busca_prazo_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_config_busca_prazo_to_string))
        {
            $values = ConfigBuscaPrazo::where('id', 'in', $publicacao_sugestao_prazo_config_busca_prazo_to_string)->getIndexedArray('titulo', 'titulo');
            $this->publicacao_sugestao_prazo_config_busca_prazo_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_config_busca_prazo_to_string = $publicacao_sugestao_prazo_config_busca_prazo_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_config_busca_prazo_to_string'] = $this->publicacao_sugestao_prazo_config_busca_prazo_to_string;
    }

    public function get_publicacao_sugestao_prazo_config_busca_prazo_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_config_busca_prazo_to_string))
        {
            return $this->publicacao_sugestao_prazo_config_busca_prazo_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('config_busca_prazo_id', '=', $this->id)->getIndexedArray('config_busca_prazo_id','{config_busca_prazo->titulo}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_criacao_user_to_string($publicacao_sugestao_prazo_criacao_user_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_sugestao_prazo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_sugestao_prazo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_criacao_user_to_string = $publicacao_sugestao_prazo_criacao_user_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_criacao_user_to_string'] = $this->publicacao_sugestao_prazo_criacao_user_to_string;
    }

    public function get_publicacao_sugestao_prazo_criacao_user_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_criacao_user_to_string))
        {
            return $this->publicacao_sugestao_prazo_criacao_user_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('config_busca_prazo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_modificacao_user_to_string($publicacao_sugestao_prazo_modificacao_user_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_sugestao_prazo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_sugestao_prazo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_modificacao_user_to_string = $publicacao_sugestao_prazo_modificacao_user_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_modificacao_user_to_string'] = $this->publicacao_sugestao_prazo_modificacao_user_to_string;
    }

    public function get_publicacao_sugestao_prazo_modificacao_user_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_modificacao_user_to_string))
        {
            return $this->publicacao_sugestao_prazo_modificacao_user_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('config_busca_prazo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(ConfigBuscaPrazoTexto::where('config_busca_prazo_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
        if(PublicacaoSugestaoPrazo::where('config_busca_prazo_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

