<?php

class ContratoRepasse extends TRecord
{
    const TABLENAME  = 'contrato_repasse';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Pessoa $pessoa;
    private Contrato $contrato;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('contrato_id');
        parent::addAttribute('pessoa_id');
        parent::addAttribute('percentual');
            
    }

    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_pessoa(Pessoa $object)
    {
        $this->pessoa = $object;
        $this->pessoa_id = $object->id;
    }

    /**
     * Method get_pessoa
     * Sample of usage: $var->pessoa->attribute;
     * @returns Pessoa instance
     */
    public function get_pessoa()
    {
    
        // loads the associated object
        if (empty($this->pessoa))
            $this->pessoa = new Pessoa($this->pessoa_id);
    
        // returns the associated object
        return $this->pessoa;
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

