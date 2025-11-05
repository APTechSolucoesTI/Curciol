<?php

class TarefaCliente extends TRecord
{
    const TABLENAME  = 'tarefa_cliente';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Tarefa $tarefa;
    private Pessoa $cliente;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tarefa_id');
        parent::addAttribute('cliente_id');
            
    }

    /**
     * Method set_tarefa
     * Sample of usage: $var->tarefa = $object;
     * @param $object Instance of Tarefa
     */
    public function set_tarefa(Tarefa $object)
    {
        $this->tarefa = $object;
        $this->tarefa_id = $object->id;
    }

    /**
     * Method get_tarefa
     * Sample of usage: $var->tarefa->attribute;
     * @returns Tarefa instance
     */
    public function get_tarefa()
    {
    
        // loads the associated object
        if (empty($this->tarefa))
            $this->tarefa = new Tarefa($this->tarefa_id);
    
        // returns the associated object
        return $this->tarefa;
    }
    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_cliente(Pessoa $object)
    {
        $this->cliente = $object;
        $this->cliente_id = $object->id;
    }

    /**
     * Method get_cliente
     * Sample of usage: $var->cliente->attribute;
     * @returns Pessoa instance
     */
    public function get_cliente()
    {
    
        // loads the associated object
        if (empty($this->cliente))
            $this->cliente = new Pessoa($this->cliente_id);
    
        // returns the associated object
        return $this->cliente;
    }

    
}

