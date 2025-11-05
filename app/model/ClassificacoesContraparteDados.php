<?php

class ClassificacoesContraparteDados extends TRecord
{
    const TABLENAME  = 'classificacoes_contraparte_dados';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
        parent::addAttribute('nome');
            
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
     * Method getClassificacoesContrapartes
     */
    public function getClassificacoesContrapartes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('classificacoes_contraparte_dados_id', '=', $this->id));
        return ClassificacoesContraparte::getObjects( $criteria );
    }

    public function set_classificacoes_contraparte_contraparte_to_string($classificacoes_contraparte_contraparte_to_string)
    {
        if(is_array($classificacoes_contraparte_contraparte_to_string))
        {
            $values = Contraparte::where('id', 'in', $classificacoes_contraparte_contraparte_to_string)->getIndexedArray('id', 'id');
            $this->classificacoes_contraparte_contraparte_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_contraparte_contraparte_to_string = $classificacoes_contraparte_contraparte_to_string;
        }

        $this->vdata['classificacoes_contraparte_contraparte_to_string'] = $this->classificacoes_contraparte_contraparte_to_string;
    }

    public function get_classificacoes_contraparte_contraparte_to_string()
    {
        if(!empty($this->classificacoes_contraparte_contraparte_to_string))
        {
            return $this->classificacoes_contraparte_contraparte_to_string;
        }
    
        $values = ClassificacoesContraparte::where('classificacoes_contraparte_dados_id', '=', $this->id)->getIndexedArray('contraparte_id','{contraparte->id}');
        return implode(', ', $values);
    }

    public function set_classificacoes_contraparte_pessoa_to_string($classificacoes_contraparte_pessoa_to_string)
    {
        if(is_array($classificacoes_contraparte_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $classificacoes_contraparte_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->classificacoes_contraparte_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_contraparte_pessoa_to_string = $classificacoes_contraparte_pessoa_to_string;
        }

        $this->vdata['classificacoes_contraparte_pessoa_to_string'] = $this->classificacoes_contraparte_pessoa_to_string;
    }

    public function get_classificacoes_contraparte_pessoa_to_string()
    {
        if(!empty($this->classificacoes_contraparte_pessoa_to_string))
        {
            return $this->classificacoes_contraparte_pessoa_to_string;
        }
    
        $values = ClassificacoesContraparte::where('classificacoes_contraparte_dados_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_classificacoes_contraparte_classificacoes_contraparte_dados_to_string($classificacoes_contraparte_classificacoes_contraparte_dados_to_string)
    {
        if(is_array($classificacoes_contraparte_classificacoes_contraparte_dados_to_string))
        {
            $values = ClassificacoesContraparteDados::where('id', 'in', $classificacoes_contraparte_classificacoes_contraparte_dados_to_string)->getIndexedArray('nome', 'nome');
            $this->classificacoes_contraparte_classificacoes_contraparte_dados_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_contraparte_classificacoes_contraparte_dados_to_string = $classificacoes_contraparte_classificacoes_contraparte_dados_to_string;
        }

        $this->vdata['classificacoes_contraparte_classificacoes_contraparte_dados_to_string'] = $this->classificacoes_contraparte_classificacoes_contraparte_dados_to_string;
    }

    public function get_classificacoes_contraparte_classificacoes_contraparte_dados_to_string()
    {
        if(!empty($this->classificacoes_contraparte_classificacoes_contraparte_dados_to_string))
        {
            return $this->classificacoes_contraparte_classificacoes_contraparte_dados_to_string;
        }
    
        $values = ClassificacoesContraparte::where('classificacoes_contraparte_dados_id', '=', $this->id)->getIndexedArray('classificacoes_contraparte_dados_id','{classificacoes_contraparte_dados->nome}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(ClassificacoesContraparte::where('classificacoes_contraparte_dados_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

