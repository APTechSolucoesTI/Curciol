<?php

class ModeloDocAplicacao extends TRecord
{
    const TABLENAME  = 'modelo_doc_aplicacao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private ModeloDocumento $modelo_documento;
    private ModeloDocTipoAplicacao $tipo_aplicacao;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('modelo_documento_id');
        parent::addAttribute('tipo_aplicacao_id');
            
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
    /**
     * Method set_modelo_doc_tipo_aplicacao
     * Sample of usage: $var->modelo_doc_tipo_aplicacao = $object;
     * @param $object Instance of ModeloDocTipoAplicacao
     */
    public function set_tipo_aplicacao(ModeloDocTipoAplicacao $object)
    {
        $this->tipo_aplicacao = $object;
        $this->tipo_aplicacao_id = $object->id;
    }

    /**
     * Method get_tipo_aplicacao
     * Sample of usage: $var->tipo_aplicacao->attribute;
     * @returns ModeloDocTipoAplicacao instance
     */
    public function get_tipo_aplicacao()
    {
    
        // loads the associated object
        if (empty($this->tipo_aplicacao))
            $this->tipo_aplicacao = new ModeloDocTipoAplicacao($this->tipo_aplicacao_id);
    
        // returns the associated object
        return $this->tipo_aplicacao;
    }

    
}

