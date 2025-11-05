<?php

class ContratoDocumento extends TRecord
{
    const TABLENAME  = 'contrato_documento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private ModeloDocumento $modelo_documento;
    private Contrato $contrato;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('contrato_id');
        parent::addAttribute('modelo_documento_id');
        parent::addAttribute('filename');
        parent::addAttribute('dt_preenchimento');
        parent::addAttribute('autenticador');
        parent::addAttribute('dt_validade');
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
     * Method set_contrato
     * Sample of usage: $var->contrato = $object;
     * @param $object Instance of Contrato
     */
    public function set_contrato(Contrato $object)
    {
        $this->contrato = $object;
        $this->contrato_id = $object->id;
    }

    /**
     * Method get_contrato
     * Sample of usage: $var->contrato->attribute;
     * @returns Contrato instance
     */
    public function get_contrato()
    {
    
        // loads the associated object
        if (empty($this->contrato))
            $this->contrato = new Contrato($this->contrato_id);
    
        // returns the associated object
        return $this->contrato;
    }

    
}

