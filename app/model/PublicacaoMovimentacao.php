<?php

class PublicacaoMovimentacao extends TRecord
{
    const TABLENAME  = 'publicacao_movimentacao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';

    const CREATEDAT  = 'data_criacao';

    private Publicacao $publicacao;
    private Processo $processo;
    private Tarefa $tarefa;
    private SystemUsers $criacao_user;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('publicacao_id');
        parent::addAttribute('descricao');
        parent::addAttribute('processo_id');
        parent::addAttribute('tarefa_id');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
            
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
    /**
     * Method set_tarefa
     * Sample of usage: $var->tarefa = $object;
     * @param $object Instance of Tarefa
     */
    public function set_tarefa(Tarefa $object)
    {
        $this->tarefa = $object;
        $this->tarefa_id = $object->id;
    }

    /**
     * Method get_tarefa
     * Sample of usage: $var->tarefa->attribute;
     * @returns Tarefa instance
     */
    public function get_tarefa()
    {
    
        // loads the associated object
        if (empty($this->tarefa))
            $this->tarefa = new Tarefa($this->tarefa_id);
    
        // returns the associated object
        return $this->tarefa;
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

    
}

