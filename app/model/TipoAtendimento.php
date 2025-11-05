<?php

class TipoAtendimento extends TRecord
{
    const TABLENAME  = 'tipo_atendimento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const AGENDADO = '1';
    const AVULSO = '2';

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
            
    }

    /**
     * Method getAtendimentos
     */
    public function getAtendimentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_atendimento_id', '=', $this->id));
        return Atendimento::getObjects( $criteria );
    }

    public function set_atendimento_agendamento_to_string($atendimento_agendamento_to_string)
    {
        if(is_array($atendimento_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $atendimento_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->atendimento_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_agendamento_to_string = $atendimento_agendamento_to_string;
        }

        $this->vdata['atendimento_agendamento_to_string'] = $this->atendimento_agendamento_to_string;
    }

    public function get_atendimento_agendamento_to_string()
    {
        if(!empty($this->atendimento_agendamento_to_string))
        {
            return $this->atendimento_agendamento_to_string;
        }
    
        $values = Atendimento::where('tipo_atendimento_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_atendimento_cliente_to_string($atendimento_cliente_to_string)
    {
        if(is_array($atendimento_cliente_to_string))
        {
            $values = Pessoa::where('id', 'in', $atendimento_cliente_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_cliente_to_string = $atendimento_cliente_to_string;
        }

        $this->vdata['atendimento_cliente_to_string'] = $this->atendimento_cliente_to_string;
    }

    public function get_atendimento_cliente_to_string()
    {
        if(!empty($this->atendimento_cliente_to_string))
        {
            return $this->atendimento_cliente_to_string;
        }
    
        $values = Atendimento::where('tipo_atendimento_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_profissional_to_string($atendimento_profissional_to_string)
    {
        if(is_array($atendimento_profissional_to_string))
        {
            $values = Pessoa::where('id', 'in', $atendimento_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_profissional_to_string = $atendimento_profissional_to_string;
        }

        $this->vdata['atendimento_profissional_to_string'] = $this->atendimento_profissional_to_string;
    }

    public function get_atendimento_profissional_to_string()
    {
        if(!empty($this->atendimento_profissional_to_string))
        {
            return $this->atendimento_profissional_to_string;
        }
    
        $values = Atendimento::where('tipo_atendimento_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_tipo_atendimento_to_string($atendimento_tipo_atendimento_to_string)
    {
        if(is_array($atendimento_tipo_atendimento_to_string))
        {
            $values = TipoAtendimento::where('id', 'in', $atendimento_tipo_atendimento_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_tipo_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_tipo_atendimento_to_string = $atendimento_tipo_atendimento_to_string;
        }

        $this->vdata['atendimento_tipo_atendimento_to_string'] = $this->atendimento_tipo_atendimento_to_string;
    }

    public function get_atendimento_tipo_atendimento_to_string()
    {
        if(!empty($this->atendimento_tipo_atendimento_to_string))
        {
            return $this->atendimento_tipo_atendimento_to_string;
        }
    
        $values = Atendimento::where('tipo_atendimento_id', '=', $this->id)->getIndexedArray('tipo_atendimento_id','{tipo_atendimento->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_criacao_user_to_string($atendimento_criacao_user_to_string)
    {
        if(is_array($atendimento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $atendimento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->atendimento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_criacao_user_to_string = $atendimento_criacao_user_to_string;
        }

        $this->vdata['atendimento_criacao_user_to_string'] = $this->atendimento_criacao_user_to_string;
    }

    public function get_atendimento_criacao_user_to_string()
    {
        if(!empty($this->atendimento_criacao_user_to_string))
        {
            return $this->atendimento_criacao_user_to_string;
        }
    
        $values = Atendimento::where('tipo_atendimento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_atendimento_modificacao_user_to_string($atendimento_modificacao_user_to_string)
    {
        if(is_array($atendimento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $atendimento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->atendimento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_modificacao_user_to_string = $atendimento_modificacao_user_to_string;
        }

        $this->vdata['atendimento_modificacao_user_to_string'] = $this->atendimento_modificacao_user_to_string;
    }

    public function get_atendimento_modificacao_user_to_string()
    {
        if(!empty($this->atendimento_modificacao_user_to_string))
        {
            return $this->atendimento_modificacao_user_to_string;
        }
    
        $values = Atendimento::where('tipo_atendimento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

