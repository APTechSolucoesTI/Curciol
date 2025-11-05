<?php

class EscritorioParceiro extends TRecord
{
    const TABLENAME  = 'escritorio_parceiro';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Escritorio $escritorio;
    private Parceiro $parceiro;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('parceiro_id');
        parent::addAttribute('escritorio_id');
            
    }

    /**
     * Method set_escritorio
     * Sample of usage: $var->escritorio = $object;
     * @param $object Instance of Escritorio
     */
    public function set_escritorio(Escritorio $object)
    {
        $this->escritorio = $object;
        $this->escritorio_id = $object->id;
    }

    /**
     * Method get_escritorio
     * Sample of usage: $var->escritorio->attribute;
     * @returns Escritorio instance
     */
    public function get_escritorio()
    {
    
        // loads the associated object
        if (empty($this->escritorio))
            $this->escritorio = new Escritorio($this->escritorio_id);
    
        // returns the associated object
        return $this->escritorio;
    }
    /**
     * Method set_parceiro
     * Sample of usage: $var->parceiro = $object;
     * @param $object Instance of Parceiro
     */
    public function set_parceiro(Parceiro $object)
    {
        $this->parceiro = $object;
        $this->parceiro_id = $object->id;
    }

    /**
     * Method get_parceiro
     * Sample of usage: $var->parceiro->attribute;
     * @returns Parceiro instance
     */
    public function get_parceiro()
    {
    
        // loads the associated object
        if (empty($this->parceiro))
            $this->parceiro = new Parceiro($this->parceiro_id);
    
        // returns the associated object
        return $this->parceiro;
    }

    
}

