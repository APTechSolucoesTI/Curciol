<?php

class PadraoAtendModeloDoc extends TRecord
{
    const TABLENAME  = 'padrao_atend_modelo_doc';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private PadraoAtendimentoDocumento $tipo_padrao_doc_atendimento;
    private ModeloDocumento $modelo_documento;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tipo_padrao_doc_atendimento_id');
        parent::addAttribute('modelo_documento_id');
            
    }

    /**
     * Method set_padrao_atendimento_documento
     * Sample of usage: $var->padrao_atendimento_documento = $object;
     * @param $object Instance of PadraoAtendimentoDocumento
     */
    public function set_tipo_padrao_doc_atendimento(PadraoAtendimentoDocumento $object)
    {
        $this->tipo_padrao_doc_atendimento = $object;
        $this->tipo_padrao_doc_atendimento_id = $object->id;
    }

    /**
     * Method get_tipo_padrao_doc_atendimento
     * Sample of usage: $var->tipo_padrao_doc_atendimento->attribute;
     * @returns PadraoAtendimentoDocumento instance
     */
    public function get_tipo_padrao_doc_atendimento()
    {
    
        // loads the associated object
        if (empty($this->tipo_padrao_doc_atendimento))
            $this->tipo_padrao_doc_atendimento = new PadraoAtendimentoDocumento($this->tipo_padrao_doc_atendimento_id);
    
        // returns the associated object
        return $this->tipo_padrao_doc_atendimento;
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

