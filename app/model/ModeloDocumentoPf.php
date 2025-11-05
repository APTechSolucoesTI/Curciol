<?php

class ModeloDocumentoPf extends TRecord
{
    const TABLENAME  = 'modelo_documento_pf';
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
        parent::addAttribute('nacionalidade');
        parent::addAttribute('estado_civil');
        parent::addAttribute('profissao');
        parent::addAttribute('rg');
        parent::addAttribute('cpf');
        parent::addAttribute('endereco');
        parent::addAttribute('data_nascimento');
            
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

