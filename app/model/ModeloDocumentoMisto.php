<?php

class ModeloDocumentoMisto extends TRecord
{
    const TABLENAME  = 'modelo_documento_misto';
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
        parent::addAttribute('pf_cpf');
        parent::addAttribute('pf_rg');
        parent::addAttribute('pf_data_nascimento');
        parent::addAttribute('pf_nacionalidade');
        parent::addAttribute('pf_estado_civil');
        parent::addAttribute('pf_profissao');
        parent::addAttribute('pf_endereco');
        parent::addAttribute('pj_cnpj');
        parent::addAttribute('pj_data_abertura');
        parent::addAttribute('pj_endereco');
        parent::addAttribute('pj_rep_cpf');
        parent::addAttribute('pj_rep_rg');
        parent::addAttribute('pj_rep_data_nascimento');
        parent::addAttribute('pj_rep_nacionalidade');
        parent::addAttribute('pj_rep_estado_civil');
        parent::addAttribute('pj_rep_profissao');
        parent::addAttribute('pj_rep_endereco');
            
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

