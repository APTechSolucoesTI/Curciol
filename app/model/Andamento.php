<?php

class Andamento extends TRecord
{
    const TABLENAME  = 'andamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private TipoAndamento $tipo_andamento;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private Processo $processo;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('processo_id');
        parent::addAttribute('tipo_andamento_id');
        parent::addAttribute('data_andamento');
        parent::addAttribute('titulo');
        parent::addAttribute('texto');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
    }

    /**
     * Method set_tipo_andamento
     * Sample of usage: $var->tipo_andamento = $object;
     * @param $object Instance of TipoAndamento
     */
    public function set_tipo_andamento(TipoAndamento $object)
    {
        $this->tipo_andamento = $object;
        $this->tipo_andamento_id = $object->id;
    }

    /**
     * Method get_tipo_andamento
     * Sample of usage: $var->tipo_andamento->attribute;
     * @returns TipoAndamento instance
     */
    public function get_tipo_andamento()
    {
    
        // loads the associated object
        if (empty($this->tipo_andamento))
            $this->tipo_andamento = new TipoAndamento($this->tipo_andamento_id);
    
        // returns the associated object
        return $this->tipo_andamento;
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
     * Method set_processo
     * Sample of usage: $var->processo = $object;
     * @param $object Instance of Processo
     */
    public function set_processo(Processo $object)
    {
        $this->processo = $object;
        $this->processo_id = $object->id;
    }

    /**
     * Method get_processo
     * Sample of usage: $var->processo->attribute;
     * @returns Processo instance
     */
    public function get_processo()
    {
    
        // loads the associated object
        if (empty($this->processo))
            $this->processo = new Processo($this->processo_id);
    
        // returns the associated object
        return $this->processo;
    }

    
}

