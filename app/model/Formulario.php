<?php

class Formulario extends TRecord
{
    const TABLENAME  = 'formulario';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

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
        parent::addAttribute('nome');
        parent::addAttribute('ativo');
        parent::addAttribute('ordem');
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
     * Method getQuestaos
     */
    public function getQuestaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('formulario_id', '=', $this->id));
        return Questao::getObjects( $criteria );
    }
    /**
     * Method getRespostaFormularios
     */
    public function getRespostaFormularios()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('formulario_id', '=', $this->id));
        return RespostaFormulario::getObjects( $criteria );
    }

    public function set_questao_formulario_to_string($questao_formulario_to_string)
    {
        if(is_array($questao_formulario_to_string))
        {
            $values = Formulario::where('id', 'in', $questao_formulario_to_string)->getIndexedArray('nome', 'nome');
            $this->questao_formulario_to_string = implode(', ', $values);
        }
        else
        {
            $this->questao_formulario_to_string = $questao_formulario_to_string;
        }

        $this->vdata['questao_formulario_to_string'] = $this->questao_formulario_to_string;
    }

    public function get_questao_formulario_to_string()
    {
        if(!empty($this->questao_formulario_to_string))
        {
            return $this->questao_formulario_to_string;
        }
    
        $values = Questao::where('formulario_id', '=', $this->id)->getIndexedArray('formulario_id','{formulario->nome}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_formulario_to_string($resposta_formulario_formulario_to_string)
    {
        if(is_array($resposta_formulario_formulario_to_string))
        {
            $values = Formulario::where('id', 'in', $resposta_formulario_formulario_to_string)->getIndexedArray('nome', 'nome');
            $this->resposta_formulario_formulario_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_formulario_to_string = $resposta_formulario_formulario_to_string;
        }

        $this->vdata['resposta_formulario_formulario_to_string'] = $this->resposta_formulario_formulario_to_string;
    }

    public function get_resposta_formulario_formulario_to_string()
    {
        if(!empty($this->resposta_formulario_formulario_to_string))
        {
            return $this->resposta_formulario_formulario_to_string;
        }
    
        $values = RespostaFormulario::where('formulario_id', '=', $this->id)->getIndexedArray('formulario_id','{formulario->nome}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_atendimento_to_string($resposta_formulario_atendimento_to_string)
    {
        if(is_array($resposta_formulario_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $resposta_formulario_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->resposta_formulario_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_atendimento_to_string = $resposta_formulario_atendimento_to_string;
        }

        $this->vdata['resposta_formulario_atendimento_to_string'] = $this->resposta_formulario_atendimento_to_string;
    }

    public function get_resposta_formulario_atendimento_to_string()
    {
        if(!empty($this->resposta_formulario_atendimento_to_string))
        {
            return $this->resposta_formulario_atendimento_to_string;
        }
    
        $values = RespostaFormulario::where('formulario_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_criacao_user_to_string($resposta_formulario_criacao_user_to_string)
    {
        if(is_array($resposta_formulario_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $resposta_formulario_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->resposta_formulario_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_criacao_user_to_string = $resposta_formulario_criacao_user_to_string;
        }

        $this->vdata['resposta_formulario_criacao_user_to_string'] = $this->resposta_formulario_criacao_user_to_string;
    }

    public function get_resposta_formulario_criacao_user_to_string()
    {
        if(!empty($this->resposta_formulario_criacao_user_to_string))
        {
            return $this->resposta_formulario_criacao_user_to_string;
        }
    
        $values = RespostaFormulario::where('formulario_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_modificacao_user_to_string($resposta_formulario_modificacao_user_to_string)
    {
        if(is_array($resposta_formulario_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $resposta_formulario_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->resposta_formulario_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_modificacao_user_to_string = $resposta_formulario_modificacao_user_to_string;
        }

        $this->vdata['resposta_formulario_modificacao_user_to_string'] = $this->resposta_formulario_modificacao_user_to_string;
    }

    public function get_resposta_formulario_modificacao_user_to_string()
    {
        if(!empty($this->resposta_formulario_modificacao_user_to_string))
        {
            return $this->resposta_formulario_modificacao_user_to_string;
        }
    
        $values = RespostaFormulario::where('formulario_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

