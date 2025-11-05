<?php

class ClassificacoesCliente extends TRecord
{
    const TABLENAME  = 'classificacoes_cliente';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Pessoa $pessoa;
    private Classificacoes $classificacoes;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pessoa_id');
        parent::addAttribute('classificacoes_id');
            
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
     * Method set_classificacoes
     * Sample of usage: $var->classificacoes = $object;
     * @param $object Instance of Classificacoes
     */
    public function set_classificacoes(Classificacoes $object)
    {
        $this->classificacoes = $object;
        $this->classificacoes_id = $object->id;
    }

    /**
     * Method get_classificacoes
     * Sample of usage: $var->classificacoes->attribute;
     * @returns Classificacoes instance
     */
    public function get_classificacoes()
    {
    
        // loads the associated object
        if (empty($this->classificacoes))
            $this->classificacoes = new Classificacoes($this->classificacoes_id);
    
        // returns the associated object
        return $this->classificacoes;
    }

    
}

