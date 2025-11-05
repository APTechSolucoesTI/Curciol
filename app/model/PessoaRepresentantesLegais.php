<?php

class PessoaRepresentantesLegais extends TRecord
{
    const TABLENAME  = 'pessoa_representantes_legais';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'created_at';

    private Pessoa $pessoa_juridica;
    private Pessoa $representante;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pessoa_juridica_id');
        parent::addAttribute('representante_id');
        parent::addAttribute('principal');
        parent::addAttribute('descricao');
        parent::addAttribute('created_at');
            
    }

    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_pessoa_juridica(Pessoa $object)
    {
        $this->pessoa_juridica = $object;
        $this->pessoa_juridica_id = $object->id;
    }

    /**
     * Method get_pessoa_juridica
     * Sample of usage: $var->pessoa_juridica->attribute;
     * @returns Pessoa instance
     */
    public function get_pessoa_juridica()
    {
    
        // loads the associated object
        if (empty($this->pessoa_juridica))
            $this->pessoa_juridica = new Pessoa($this->pessoa_juridica_id);
    
        // returns the associated object
        return $this->pessoa_juridica;
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

