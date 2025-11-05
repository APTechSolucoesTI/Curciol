<?php

class PessoaEndereco extends TRecord
{
    const TABLENAME  = 'pessoa_endereco';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Pessoa $pessoa;
    private Cidade $cidade;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('pessoa_id');
        parent::addAttribute('cidade_id');
        parent::addAttribute('cep');
        parent::addAttribute('rua');
        parent::addAttribute('bairro');
        parent::addAttribute('numero');
        parent::addAttribute('complemento');
        parent::addAttribute('principal');
    
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
     * Method set_cidade
     * Sample of usage: $var->cidade = $object;
     * @param $object Instance of Cidade
     */
    public function set_cidade(Cidade $object)
    {
        $this->cidade = $object;
        $this->cidade_id = $object->id;
    }

    /**
     * Method get_cidade
     * Sample of usage: $var->cidade->attribute;
     * @returns Cidade instance
     */
    public function get_cidade()
    {
    
        // loads the associated object
        if (empty($this->cidade))
            $this->cidade = new Cidade($this->cidade_id);
    
        // returns the associated object
        return $this->cidade;
    }

    public function get_cep_formatado(){
        return preg_replace("/(\d{5})(\d{3})/", "\$1-\$2", $this->cep);
    }
    public function get_endereco_completo()
    {
        $this->get_cidade();
        return "{$this->rua} {$this->numero} {$this->complemento}, {$this->cep_formatado}.Bairro {$this->bairro}, {$this->cidade->nome} - {$this->cidade->estado->nome}";
    }
        
}

