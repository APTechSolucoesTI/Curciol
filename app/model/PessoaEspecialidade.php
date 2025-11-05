<?php

class PessoaEspecialidade extends TRecord
{
    const TABLENAME  = 'pessoa_especialidade';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Pessoa $pessoa;
    private Especialidade $especialidade;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pessoa_id');
        parent::addAttribute('especialidade_id');
            
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
     * Method set_especialidade
     * Sample of usage: $var->especialidade = $object;
     * @param $object Instance of Especialidade
     */
    public function set_especialidade(Especialidade $object)
    {
        $this->especialidade = $object;
        $this->especialidade_id = $object->id;
    }

    /**
     * Method get_especialidade
     * Sample of usage: $var->especialidade->attribute;
     * @returns Especialidade instance
     */
    public function get_especialidade()
    {
    
        // loads the associated object
        if (empty($this->especialidade))
            $this->especialidade = new Especialidade($this->especialidade_id);
    
        // returns the associated object
        return $this->especialidade;
    }

    
}

