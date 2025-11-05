<?php

class ClassificacoesContraparte extends TRecord
{
    const TABLENAME  = 'classificacoes_contraparte';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Contraparte $contraparte;
    private Pessoa $pessoa;
    private ClassificacoesContraparteDados $classificacoes_contraparte_dados;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('contraparte_id');
        parent::addAttribute('pessoa_id');
        parent::addAttribute('classificacoes_contraparte_dados_id');
            
    }

    /**
     * Method set_contraparte
     * Sample of usage: $var->contraparte = $object;
     * @param $object Instance of Contraparte
     */
    public function set_contraparte(Contraparte $object)
    {
        $this->contraparte = $object;
        $this->contraparte_id = $object->id;
    }

    /**
     * Method get_contraparte
     * Sample of usage: $var->contraparte->attribute;
     * @returns Contraparte instance
     */
    public function get_contraparte()
    {
    
        // loads the associated object
        if (empty($this->contraparte))
            $this->contraparte = new Contraparte($this->contraparte_id);
    
        // returns the associated object
        return $this->contraparte;
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
     * Method set_classificacoes_contraparte_dados
     * Sample of usage: $var->classificacoes_contraparte_dados = $object;
     * @param $object Instance of ClassificacoesContraparteDados
     */
    public function set_classificacoes_contraparte_dados(ClassificacoesContraparteDados $object)
    {
        $this->classificacoes_contraparte_dados = $object;
        $this->classificacoes_contraparte_dados_id = $object->id;
    }

    /**
     * Method get_classificacoes_contraparte_dados
     * Sample of usage: $var->classificacoes_contraparte_dados->attribute;
     * @returns ClassificacoesContraparteDados instance
     */
    public function get_classificacoes_contraparte_dados()
    {
    
        // loads the associated object
        if (empty($this->classificacoes_contraparte_dados))
            $this->classificacoes_contraparte_dados = new ClassificacoesContraparteDados($this->classificacoes_contraparte_dados_id);
    
        // returns the associated object
        return $this->classificacoes_contraparte_dados;
    }

    
}

