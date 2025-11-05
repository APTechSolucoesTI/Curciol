<?php

class AgendamentoProcedimento extends TRecord
{
    const TABLENAME  = 'agendamento_procedimento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Agendamento $agendamento;
    private Procedimento $procedimento;
    private Parceiro $parceiro;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('agendamento_id');
        parent::addAttribute('procedimento_id');
        parent::addAttribute('parceiro_id');
        parent::addAttribute('quantidade');
        parent::addAttribute('valor');
        parent::addAttribute('valor_total');
            
    }

    /**
     * Method set_agendamento
     * Sample of usage: $var->agendamento = $object;
     * @param $object Instance of Agendamento
     */
    public function set_agendamento(Agendamento $object)
    {
        $this->agendamento = $object;
        $this->agendamento_id = $object->id;
    }

    /**
     * Method get_agendamento
     * Sample of usage: $var->agendamento->attribute;
     * @returns Agendamento instance
     */
    public function get_agendamento()
    {
    
        // loads the associated object
        if (empty($this->agendamento))
            $this->agendamento = new Agendamento($this->agendamento_id);
    
        // returns the associated object
        return $this->agendamento;
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

