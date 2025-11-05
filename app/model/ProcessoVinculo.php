<?php

class ProcessoVinculo extends TRecord
{
    const TABLENAME  = 'processo_vinculo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Processo $processo_principal;
    private Processo $processo_incidente;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('processo_principal_id');
        parent::addAttribute('processo_incidente_id');
            
    }

    /**
     * Method set_processo
     * Sample of usage: $var->processo = $object;
     * @param $object Instance of Processo
     */
    public function set_processo_principal(Processo $object)
    {
        $this->processo_principal = $object;
        $this->processo_principal_id = $object->id;
    }

    /**
     * Method get_processo_principal
     * Sample of usage: $var->processo_principal->attribute;
     * @returns Processo instance
     */
    public function get_processo_principal()
    {
    
        // loads the associated object
        if (empty($this->processo_principal))
            $this->processo_principal = new Processo($this->processo_principal_id);
    
        // returns the associated object
        return $this->processo_principal;
    }
    /**
     * Method set_processo
     * Sample of usage: $var->processo = $object;
     * @param $object Instance of Processo
     */
    public function set_processo_incidente(Processo $object)
    {
        $this->processo_incidente = $object;
        $this->processo_incidente_id = $object->id;
    }

    /**
     * Method get_processo_incidente
     * Sample of usage: $var->processo_incidente->attribute;
     * @returns Processo instance
     */
    public function get_processo_incidente()
    {
    
        // loads the associated object
        if (empty($this->processo_incidente))
            $this->processo_incidente = new Processo($this->processo_incidente_id);
    
        // returns the associated object
        return $this->processo_incidente;
    }

    
}

