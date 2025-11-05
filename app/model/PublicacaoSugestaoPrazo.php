<?php

class PublicacaoSugestaoPrazo extends TRecord
{
    const TABLENAME  = 'publicacao_sugestao_prazo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Publicacao $publicacao;
    private ConfigBuscaPrazo $config_busca_prazo;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('publicacao_id');
        parent::addAttribute('config_busca_prazo_id');
        parent::addAttribute('resultado_busca');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
    }

    /**
     * Method set_publicacao
     * Sample of usage: $var->publicacao = $object;
     * @param $object Instance of Publicacao
     */
    public function set_publicacao(Publicacao $object)
    {
        $this->publicacao = $object;
        $this->publicacao_id = $object->id;
    }

    /**
     * Method get_publicacao
     * Sample of usage: $var->publicacao->attribute;
     * @returns Publicacao instance
     */
    public function get_publicacao()
    {
    
        // loads the associated object
        if (empty($this->publicacao))
            $this->publicacao = new Publicacao($this->publicacao_id);
    
        // returns the associated object
        return $this->publicacao;
    }
    /**
     * Method set_config_busca_prazo
     * Sample of usage: $var->config_busca_prazo = $object;
     * @param $object Instance of ConfigBuscaPrazo
     */
    public function set_config_busca_prazo(ConfigBuscaPrazo $object)
    {
        $this->config_busca_prazo = $object;
        $this->config_busca_prazo_id = $object->id;
    }

    /**
     * Method get_config_busca_prazo
     * Sample of usage: $var->config_busca_prazo->attribute;
     * @returns ConfigBuscaPrazo instance
     */
    public function get_config_busca_prazo()
    {
    
        // loads the associated object
        if (empty($this->config_busca_prazo))
            $this->config_busca_prazo = new ConfigBuscaPrazo($this->config_busca_prazo_id);
    
        // returns the associated object
        return $this->config_busca_prazo;
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

    
}

