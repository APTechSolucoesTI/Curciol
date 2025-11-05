<?php

class ModeloDocumentoPj extends TRecord
{
    const TABLENAME  = 'modelo_documento_pj';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private ModeloDocumento $modelo_documento;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('modelo_documento_id');
        parent::addAttribute('filename');
        parent::addAttribute('objeto');
        parent::addAttribute('informacoes_pagamento');
        parent::addAttribute('cnpj');
        parent::addAttribute('endereco');
        parent::addAttribute('nacionalidade_rep');
        parent::addAttribute('estado_civil_rep');
        parent::addAttribute('profissao_rep');
        parent::addAttribute('rg_rep');
        parent::addAttribute('cpf_rep');
        parent::addAttribute('endereco_rep');
        parent::addAttribute('data_abertura');
        parent::addAttribute('data_nascimento_rep');
            
    }

    /**
     * Method set_modelo_documento
     * Sample of usage: $var->modelo_documento = $object;
     * @param $object Instance of ModeloDocumento
     */
    public function set_modelo_documento(ModeloDocumento $object)
    {
        $this->modelo_documento = $object;
        $this->modelo_documento_id = $object->id;
    }

    /**
     * Method get_modelo_documento
     * Sample of usage: $var->modelo_documento->attribute;
     * @returns ModeloDocumento instance
     */
    public function get_modelo_documento()
    {
    
        // loads the associated object
        if (empty($this->modelo_documento))
            $this->modelo_documento = new ModeloDocumento($this->modelo_documento_id);
    
        // returns the associated object
        return $this->modelo_documento;
    }

    
}

