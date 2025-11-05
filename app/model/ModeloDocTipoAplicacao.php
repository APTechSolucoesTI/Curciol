<?php

class ModeloDocTipoAplicacao extends TRecord
{
    const TABLENAME  = 'modelo_doc_tipo_aplicacao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const FINANCEIRO = '1';
    const ATENDIMENTO = '2';
    const CONTRATO = '3';
    const PROCESSO = '4';
    const GERAR = '5';

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
            
    }

    /**
     * Method getModeloDocAplicacaos
     */
    public function getModeloDocAplicacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_aplicacao_id', '=', $this->id));
        return ModeloDocAplicacao::getObjects( $criteria );
    }

    public function set_modelo_doc_aplicacao_modelo_documento_to_string($modelo_doc_aplicacao_modelo_documento_to_string)
    {
        if(is_array($modelo_doc_aplicacao_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $modelo_doc_aplicacao_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->modelo_doc_aplicacao_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_doc_aplicacao_modelo_documento_to_string = $modelo_doc_aplicacao_modelo_documento_to_string;
        }

        $this->vdata['modelo_doc_aplicacao_modelo_documento_to_string'] = $this->modelo_doc_aplicacao_modelo_documento_to_string;
    }

    public function get_modelo_doc_aplicacao_modelo_documento_to_string()
    {
        if(!empty($this->modelo_doc_aplicacao_modelo_documento_to_string))
        {
            return $this->modelo_doc_aplicacao_modelo_documento_to_string;
        }
    
        $values = ModeloDocAplicacao::where('tipo_aplicacao_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_modelo_doc_aplicacao_tipo_aplicacao_to_string($modelo_doc_aplicacao_tipo_aplicacao_to_string)
    {
        if(is_array($modelo_doc_aplicacao_tipo_aplicacao_to_string))
        {
            $values = ModeloDocTipoAplicacao::where('id', 'in', $modelo_doc_aplicacao_tipo_aplicacao_to_string)->getIndexedArray('nome', 'nome');
            $this->modelo_doc_aplicacao_tipo_aplicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_doc_aplicacao_tipo_aplicacao_to_string = $modelo_doc_aplicacao_tipo_aplicacao_to_string;
        }

        $this->vdata['modelo_doc_aplicacao_tipo_aplicacao_to_string'] = $this->modelo_doc_aplicacao_tipo_aplicacao_to_string;
    }

    public function get_modelo_doc_aplicacao_tipo_aplicacao_to_string()
    {
        if(!empty($this->modelo_doc_aplicacao_tipo_aplicacao_to_string))
        {
            return $this->modelo_doc_aplicacao_tipo_aplicacao_to_string;
        }
    
        $values = ModeloDocAplicacao::where('tipo_aplicacao_id', '=', $this->id)->getIndexedArray('tipo_aplicacao_id','{tipo_aplicacao->nome}');
        return implode(', ', $values);
    }

    
}

