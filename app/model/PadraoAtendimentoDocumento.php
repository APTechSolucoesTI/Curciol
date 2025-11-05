<?php

class PadraoAtendimentoDocumento extends TRecord
{
    const TABLENAME  = 'padrao_atendimento_documento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
    }

    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_criacao_user(SystemUsers $object)
    {
        $this->criacao_user = $object;
        $this->criacao_user_id = $object->id;
    }

    /**
     * Method get_criacao_user
     * Sample of usage: $var->criacao_user->attribute;
     * @returns SystemUsers instance
     */
    public function get_criacao_user()
    {
    
        // loads the associated object
        if (empty($this->criacao_user))
            $this->criacao_user = new SystemUsers($this->criacao_user_id);
    
        // returns the associated object
        return $this->criacao_user;
    }
    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_modificacao_user(SystemUsers $object)
    {
        $this->modificacao_user = $object;
        $this->modificacao_user_id = $object->id;
    }

    /**
     * Method get_modificacao_user
     * Sample of usage: $var->modificacao_user->attribute;
     * @returns SystemUsers instance
     */
    public function get_modificacao_user()
    {
    
        // loads the associated object
        if (empty($this->modificacao_user))
            $this->modificacao_user = new SystemUsers($this->modificacao_user_id);
    
        // returns the associated object
        return $this->modificacao_user;
    }

    /**
     * Method getPadraoAtendModeloDocs
     */
    public function getPadraoAtendModeloDocs()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_padrao_doc_atendimento_id', '=', $this->id));
        return PadraoAtendModeloDoc::getObjects( $criteria );
    }

    public function set_padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string($padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string)
    {
        if(is_array($padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string))
        {
            $values = PadraoAtendimentoDocumento::where('id', 'in', $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string)->getIndexedArray('nome', 'nome');
            $this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string = $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string;
        }

        $this->vdata['padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string'] = $this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string;
    }

    public function get_padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string()
    {
        if(!empty($this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string))
        {
            return $this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string;
        }
    
        $values = PadraoAtendModeloDoc::where('tipo_padrao_doc_atendimento_id', '=', $this->id)->getIndexedArray('tipo_padrao_doc_atendimento_id','{tipo_padrao_doc_atendimento->nome}');
        return implode(', ', $values);
    }

    public function set_padrao_atend_modelo_doc_modelo_documento_to_string($padrao_atend_modelo_doc_modelo_documento_to_string)
    {
        if(is_array($padrao_atend_modelo_doc_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $padrao_atend_modelo_doc_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->padrao_atend_modelo_doc_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->padrao_atend_modelo_doc_modelo_documento_to_string = $padrao_atend_modelo_doc_modelo_documento_to_string;
        }

        $this->vdata['padrao_atend_modelo_doc_modelo_documento_to_string'] = $this->padrao_atend_modelo_doc_modelo_documento_to_string;
    }

    public function get_padrao_atend_modelo_doc_modelo_documento_to_string()
    {
        if(!empty($this->padrao_atend_modelo_doc_modelo_documento_to_string))
        {
            return $this->padrao_atend_modelo_doc_modelo_documento_to_string;
        }
    
        $values = PadraoAtendModeloDoc::where('tipo_padrao_doc_atendimento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    
}

