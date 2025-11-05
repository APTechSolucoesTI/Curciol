<?php

class AtendimentoProcedimento extends TRecord
{
    const TABLENAME  = 'atendimento_procedimento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Atendimento $atendimento;
    private Procedimento $procedimento;
    private Parceiro $parceiro;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('parceiro_id');
        parent::addAttribute('atendimento_id');
        parent::addAttribute('procedimento_id');
        parent::addAttribute('quantidade');
        parent::addAttribute('valor');
        parent::addAttribute('valor_total');
            
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

