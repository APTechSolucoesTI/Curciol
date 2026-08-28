<?php

class EtapaPalavrasChaves extends TRecord
{
    const TABLENAME  = 'etapa_palavras_chaves';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private PublicacaoEtapa $publicacao_etapa;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('publicacao_etapa_id');
        parent::addAttribute('palavra_chave');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
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

    
}

