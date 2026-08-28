<?php

class ContaProfissional extends TRecord
{
    const TABLENAME  = 'conta_profissional';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Conta $conta;
    private Pessoa $pessoa;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('conta_id');
        parent::addAttribute('pessoa_id');
        parent::addAttribute('percentual');
        parent::addAttribute('valor');
            
    }

    /**
     * Method set_conta
     * Sample of usage: $var->conta = $object;
     * @param $object Instance of Conta
     */
    public function set_conta(Conta $object)
    {
        $this->conta = $object;
        $this->conta_id = $object->id;
    }

    /**
     * Method get_conta
     * Sample of usage: $var->conta->attribute;
     * @returns Conta instance
     */
    public function get_conta()
    {
    
        // loads the associated object
        if (empty($this->conta))
            $this->conta = new Conta($this->conta_id);
    
        // returns the associated object
        return $this->conta;
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

    
}

