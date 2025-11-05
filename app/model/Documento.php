<?php

class Documento extends TRecord
{
    const TABLENAME  = 'documento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private ModeloDocumento $modelo_documento;
    private Atendimento $atendimento;
    private Procedimento $procedimento;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('atendimento_id');
        parent::addAttribute('modelo_documento_id');
        parent::addAttribute('filename');
        parent::addAttribute('observacao');
        parent::addAttribute('dt_preenchimento');
        parent::addAttribute('autenticador');
        parent::addAttribute('dt_validade');
        parent::addAttribute('procedimento_id');
        parent::addAttribute('medico_assistente');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    
    }

    /**
     * Method set_modelo_documento
     * Sample of usage: $var->modelo_documento = $object;
     * @param $object Instance of ModeloDocumento
     */
    public function set_modelo_documento(ModeloDocumento $object)
    {
        $this->modelo_documento = $object;
        $this->modelo_documento_id = $object->id;
    }

    /**
     * Method get_modelo_documento
     * Sample of usage: $var->modelo_documento->attribute;
     * @returns ModeloDocumento instance
     */
    public function get_modelo_documento()
    {
    
        // loads the associated object
        if (empty($this->modelo_documento))
            $this->modelo_documento = new ModeloDocumento($this->modelo_documento_id);
    
        // returns the associated object
        return $this->modelo_documento;
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
     * Method set_procedimento
     * Sample of usage: $var->procedimento = $object;
     * @param $object Instance of Procedimento
     */
    public function set_procedimento(Procedimento $object)
    {
        $this->procedimento = $object;
        $this->procedimento_id = $object->id;
    }

    /**
     * Method get_procedimento
     * Sample of usage: $var->procedimento->attribute;
     * @returns Procedimento instance
     */
    public function get_procedimento()
    {
    
        // loads the associated object
        if (empty($this->procedimento))
            $this->procedimento = new Procedimento($this->procedimento_id);
    
        // returns the associated object
        return $this->procedimento;
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

    public function get_texto_formatado()
    {
        $variaveis = ModeloDocumento::VARIAVEIS;
    
        $text = $this->texto;
        $atendimento = $this->get_atendimento();
    
        foreach($variaveis as $chave => $atendimento_variavel)
        {
            $text = str_replace($chave, $atendimento->render("{{$atendimento_variavel}}"), $text);
        }
    
        return $text;
    }

    public function get_preenchimento()
    {
        return date('d/m/Y H:i', strtotime($this->dt_preenchimento));
    }
        
}

