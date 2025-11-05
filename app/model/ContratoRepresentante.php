<?php

class ContratoRepresentante extends TRecord
{
    const TABLENAME  = 'contrato_representante';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Contrato $contrato;
    private Pessoa $representante;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('contrato_id');
        parent::addAttribute('representante_id');
            
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
    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_representante(Pessoa $object)
    {
        $this->representante = $object;
        $this->representante_id = $object->id;
    }

    /**
     * Method get_representante
     * Sample of usage: $var->representante->attribute;
     * @returns Pessoa instance
     */
    public function get_representante()
    {
    
        // loads the associated object
        if (empty($this->representante))
            $this->representante = new Pessoa($this->representante_id);
    
        // returns the associated object
        return $this->representante;
    }

    
}

