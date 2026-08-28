<?php

class ProcessoPublicacoes extends TRecord
{
    const TABLENAME  = 'processo_publicacoes';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private PublicacaoEtapa $publicacao_etapa;
    private Processo $processo;
    private Publicacao $publicacao;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('processo_id');
        parent::addAttribute('publicacao_id');
        parent::addAttribute('andamento_id');
        parent::addAttribute('publicacao_etapa_id');
        parent::addAttribute('date_log');
        parent::addAttribute('complemento');
            
    }

    /**
     * Method set_publicacao_etapa
     * Sample of usage: $var->publicacao_etapa = $object;
     * @param $object Instance of PublicacaoEtapa
     */
    public function set_publicacao_etapa(PublicacaoEtapa $object)
    {
        $this->publicacao_etapa = $object;
        $this->publicacao_etapa_id = $object->id;
    }

    /**
     * Method get_publicacao_etapa
     * Sample of usage: $var->publicacao_etapa->attribute;
     * @returns PublicacaoEtapa instance
     */
    public function get_publicacao_etapa()
    {
    
        // loads the associated object
        if (empty($this->publicacao_etapa))
            $this->publicacao_etapa = new PublicacaoEtapa($this->publicacao_etapa_id);
    
        // returns the associated object
        return $this->publicacao_etapa;
    }
    /**
     * Method set_processo
     * Sample of usage: $var->processo = $object;
     * @param $object Instance of Processo
     */
    public function set_processo(Processo $object)
    {
        $this->processo = $object;
        $this->processo_id = $object->id;
    }

    /**
     * Method get_processo
     * Sample of usage: $var->processo->attribute;
     * @returns Processo instance
     */
    public function get_processo()
    {
    
        // loads the associated object
        if (empty($this->processo))
            $this->processo = new Processo($this->processo_id);
    
        // returns the associated object
        return $this->processo;
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

    
}

