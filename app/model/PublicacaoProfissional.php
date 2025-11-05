<?php

class PublicacaoProfissional extends TRecord
{
    const TABLENAME  = 'publicacao_profissional';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Publicacao $publicacao;
    private Pessoa $profissional;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('publicacao_id');
        parent::addAttribute('profissional_id');
        parent::addAttribute('codigo_relacionamento');
            
    }

    /**
     * Method set_publicacao
     * Sample of usage: $var->publicacao = $object;
     * @param $object Instance of Publicacao
     */
    public function set_publicacao(Publicacao $object)
    {
        $this->publicacao = $object;
        $this->publicacao_id = $object->id;
    }

    /**
     * Method get_publicacao
     * Sample of usage: $var->publicacao->attribute;
     * @returns Publicacao instance
     */
    public function get_publicacao()
    {
    
        // loads the associated object
        if (empty($this->publicacao))
            $this->publicacao = new Publicacao($this->publicacao_id);
    
        // returns the associated object
        return $this->publicacao;
    }
    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_profissional(Pessoa $object)
    {
        $this->profissional = $object;
        $this->profissional_id = $object->id;
    }

    /**
     * Method get_profissional
     * Sample of usage: $var->profissional->attribute;
     * @returns Pessoa instance
     */
    public function get_profissional()
    {
    
        // loads the associated object
        if (empty($this->profissional))
            $this->profissional = new Pessoa($this->profissional_id);
    
        // returns the associated object
        return $this->profissional;
    }

    
}

