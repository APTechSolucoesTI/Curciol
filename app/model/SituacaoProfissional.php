<?php

class SituacaoProfissional extends TRecord
{
    const TABLENAME  = 'situacao_profissional';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
            
    }

    /**
     * Method getPessoas
     */
    public function getPessoas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('situacao_profissional_id', '=', $this->id));
        return Pessoa::getObjects( $criteria );
    }

    public function set_pessoa_tipo_pessoa_to_string($pessoa_tipo_pessoa_to_string)
    {
        if(is_array($pessoa_tipo_pessoa_to_string))
        {
            $values = TipoPessoa::where('id', 'in', $pessoa_tipo_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_tipo_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_tipo_pessoa_to_string = $pessoa_tipo_pessoa_to_string;
        }

        $this->vdata['pessoa_tipo_pessoa_to_string'] = $this->pessoa_tipo_pessoa_to_string;
    }

    public function get_pessoa_tipo_pessoa_to_string()
    {
        if(!empty($this->pessoa_tipo_pessoa_to_string))
        {
            return $this->pessoa_tipo_pessoa_to_string;
        }
    
        $values = Pessoa::where('situacao_profissional_id', '=', $this->id)->getIndexedArray('tipo_pessoa_id','{tipo_pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_system_users_to_string($pessoa_system_users_to_string)
    {
        if(is_array($pessoa_system_users_to_string))
        {
            $values = SystemUsers::where('id', 'in', $pessoa_system_users_to_string)->getIndexedArray('name', 'name');
            $this->pessoa_system_users_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_system_users_to_string = $pessoa_system_users_to_string;
        }

        $this->vdata['pessoa_system_users_to_string'] = $this->pessoa_system_users_to_string;
    }

    public function get_pessoa_system_users_to_string()
    {
        if(!empty($this->pessoa_system_users_to_string))
        {
            return $this->pessoa_system_users_to_string;
        }
    
        $values = Pessoa::where('situacao_profissional_id', '=', $this->id)->getIndexedArray('system_users_id','{system_users->name}');
        return implode(', ', $values);
    }

    public function set_pessoa_sexo_to_string($pessoa_sexo_to_string)
    {
        if(is_array($pessoa_sexo_to_string))
        {
            $values = Sexo::where('id', 'in', $pessoa_sexo_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_sexo_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_sexo_to_string = $pessoa_sexo_to_string;
        }

        $this->vdata['pessoa_sexo_to_string'] = $this->pessoa_sexo_to_string;
    }

    public function get_pessoa_sexo_to_string()
    {
        if(!empty($this->pessoa_sexo_to_string))
        {
            return $this->pessoa_sexo_to_string;
        }
    
        $values = Pessoa::where('situacao_profissional_id', '=', $this->id)->getIndexedArray('sexo_id','{sexo->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_nacionalidade_to_string($pessoa_nacionalidade_to_string)
    {
        if(is_array($pessoa_nacionalidade_to_string))
        {
            $values = Nacionalidade::where('id', 'in', $pessoa_nacionalidade_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_nacionalidade_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_nacionalidade_to_string = $pessoa_nacionalidade_to_string;
        }

        $this->vdata['pessoa_nacionalidade_to_string'] = $this->pessoa_nacionalidade_to_string;
    }

    public function get_pessoa_nacionalidade_to_string()
    {
        if(!empty($this->pessoa_nacionalidade_to_string))
        {
            return $this->pessoa_nacionalidade_to_string;
        }
    
        $values = Pessoa::where('situacao_profissional_id', '=', $this->id)->getIndexedArray('nacionalidade_id','{nacionalidade->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_estado_civil_to_string($pessoa_estado_civil_to_string)
    {
        if(is_array($pessoa_estado_civil_to_string))
        {
            $values = EstadoCivil::where('id', 'in', $pessoa_estado_civil_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_estado_civil_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_estado_civil_to_string = $pessoa_estado_civil_to_string;
        }

        $this->vdata['pessoa_estado_civil_to_string'] = $this->pessoa_estado_civil_to_string;
    }

    public function get_pessoa_estado_civil_to_string()
    {
        if(!empty($this->pessoa_estado_civil_to_string))
        {
            return $this->pessoa_estado_civil_to_string;
        }
    
        $values = Pessoa::where('situacao_profissional_id', '=', $this->id)->getIndexedArray('estado_civil_id','{estado_civil->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_situacao_profissional_to_string($pessoa_situacao_profissional_to_string)
    {
        if(is_array($pessoa_situacao_profissional_to_string))
        {
            $values = SituacaoProfissional::where('id', 'in', $pessoa_situacao_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_situacao_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_situacao_profissional_to_string = $pessoa_situacao_profissional_to_string;
        }

        $this->vdata['pessoa_situacao_profissional_to_string'] = $this->pessoa_situacao_profissional_to_string;
    }

    public function get_pessoa_situacao_profissional_to_string()
    {
        if(!empty($this->pessoa_situacao_profissional_to_string))
        {
            return $this->pessoa_situacao_profissional_to_string;
        }
    
        $values = Pessoa::where('situacao_profissional_id', '=', $this->id)->getIndexedArray('situacao_profissional_id','{situacao_profissional->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_tipo_profissional_to_string($pessoa_tipo_profissional_to_string)
    {
        if(is_array($pessoa_tipo_profissional_to_string))
        {
            $values = TipoProfissional::where('id', 'in', $pessoa_tipo_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_tipo_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_tipo_profissional_to_string = $pessoa_tipo_profissional_to_string;
        }

        $this->vdata['pessoa_tipo_profissional_to_string'] = $this->pessoa_tipo_profissional_to_string;
    }

    public function get_pessoa_tipo_profissional_to_string()
    {
        if(!empty($this->pessoa_tipo_profissional_to_string))
        {
            return $this->pessoa_tipo_profissional_to_string;
        }
    
        $values = Pessoa::where('situacao_profissional_id', '=', $this->id)->getIndexedArray('tipo_profissional_id','{tipo_profissional->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_criacao_user_to_string($pessoa_criacao_user_to_string)
    {
        if(is_array($pessoa_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $pessoa_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->pessoa_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_criacao_user_to_string = $pessoa_criacao_user_to_string;
        }

        $this->vdata['pessoa_criacao_user_to_string'] = $this->pessoa_criacao_user_to_string;
    }

    public function get_pessoa_criacao_user_to_string()
    {
        if(!empty($this->pessoa_criacao_user_to_string))
        {
            return $this->pessoa_criacao_user_to_string;
        }
    
        $values = Pessoa::where('situacao_profissional_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_pessoa_modificacao_user_to_string($pessoa_modificacao_user_to_string)
    {
        if(is_array($pessoa_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $pessoa_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->pessoa_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_modificacao_user_to_string = $pessoa_modificacao_user_to_string;
        }

        $this->vdata['pessoa_modificacao_user_to_string'] = $this->pessoa_modificacao_user_to_string;
    }

    public function get_pessoa_modificacao_user_to_string()
    {
        if(!empty($this->pessoa_modificacao_user_to_string))
        {
            return $this->pessoa_modificacao_user_to_string;
        }
    
        $values = Pessoa::where('situacao_profissional_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

