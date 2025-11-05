<?php

class RespostaFormulario extends TRecord
{
    const TABLENAME  = 'resposta_formulario';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Formulario $formulario;
    private Atendimento $atendimento;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('formulario_id');
        parent::addAttribute('atendimento_id');
        parent::addAttribute('dt_resposta');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
    }

    /**
     * Method set_formulario
     * Sample of usage: $var->formulario = $object;
     * @param $object Instance of Formulario
     */
    public function set_formulario(Formulario $object)
    {
        $this->formulario = $object;
        $this->formulario_id = $object->id;
    }

    /**
     * Method get_formulario
     * Sample of usage: $var->formulario->attribute;
     * @returns Formulario instance
     */
    public function get_formulario()
    {
    
        // loads the associated object
        if (empty($this->formulario))
            $this->formulario = new Formulario($this->formulario_id);
    
        // returns the associated object
        return $this->formulario;
    }
    /**
     * Method set_atendimento
     * Sample of usage: $var->atendimento = $object;
     * @param $object Instance of Atendimento
     */
    public function set_atendimento(Atendimento $object)
    {
        $this->atendimento = $object;
        $this->atendimento_id = $object->id;
    }

    /**
     * Method get_atendimento
     * Sample of usage: $var->atendimento->attribute;
     * @returns Atendimento instance
     */
    public function get_atendimento()
    {
    
        // loads the associated object
        if (empty($this->atendimento))
            $this->atendimento = new Atendimento($this->atendimento_id);
    
        // returns the associated object
        return $this->atendimento;
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

