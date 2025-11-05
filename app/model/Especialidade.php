<?php

class Especialidade extends TRecord
{
    const TABLENAME  = 'especialidade';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

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
        parent::addAttribute('descricao');
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
     * Method getAgendamentos
     */
    public function getAgendamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('especialidade_id', '=', $this->id));
        return Agendamento::getObjects( $criteria );
    }
    /**
     * Method getPessoaEspecialidades
     */
    public function getPessoaEspecialidades()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('especialidade_id', '=', $this->id));
        return PessoaEspecialidade::getObjects( $criteria );
    }

    public function set_agendamento_cliente_to_string($agendamento_cliente_to_string)
    {
        if(is_array($agendamento_cliente_to_string))
        {
            $values = Pessoa::where('id', 'in', $agendamento_cliente_to_string)->getIndexedArray('nome', 'nome');
            $this->agendamento_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_cliente_to_string = $agendamento_cliente_to_string;
        }

        $this->vdata['agendamento_cliente_to_string'] = $this->agendamento_cliente_to_string;
    }

    public function get_agendamento_cliente_to_string()
    {
        if(!empty($this->agendamento_cliente_to_string))
        {
            return $this->agendamento_cliente_to_string;
        }
    
        $values = Agendamento::where('especialidade_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
        return implode(', ', $values);
    }

    public function set_agendamento_estado_agenda_to_string($agendamento_estado_agenda_to_string)
    {
        if(is_array($agendamento_estado_agenda_to_string))
        {
            $values = EstadoAgenda::where('id', 'in', $agendamento_estado_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->agendamento_estado_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_estado_agenda_to_string = $agendamento_estado_agenda_to_string;
        }

        $this->vdata['agendamento_estado_agenda_to_string'] = $this->agendamento_estado_agenda_to_string;
    }

    public function get_agendamento_estado_agenda_to_string()
    {
        if(!empty($this->agendamento_estado_agenda_to_string))
        {
            return $this->agendamento_estado_agenda_to_string;
        }
    
        $values = Agendamento::where('especialidade_id', '=', $this->id)->getIndexedArray('estado_agenda_id','{estado_agenda->nome}');
        return implode(', ', $values);
    }

    public function set_agendamento_agenda_to_string($agendamento_agenda_to_string)
    {
        if(is_array($agendamento_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $agendamento_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->agendamento_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_agenda_to_string = $agendamento_agenda_to_string;
        }

        $this->vdata['agendamento_agenda_to_string'] = $this->agendamento_agenda_to_string;
    }

    public function get_agendamento_agenda_to_string()
    {
        if(!empty($this->agendamento_agenda_to_string))
        {
            return $this->agendamento_agenda_to_string;
        }
    
        $values = Agendamento::where('especialidade_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_agendamento_especialidade_to_string($agendamento_especialidade_to_string)
    {
        if(is_array($agendamento_especialidade_to_string))
        {
            $values = Especialidade::where('id', 'in', $agendamento_especialidade_to_string)->getIndexedArray('descricao', 'descricao');
            $this->agendamento_especialidade_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_especialidade_to_string = $agendamento_especialidade_to_string;
        }

        $this->vdata['agendamento_especialidade_to_string'] = $this->agendamento_especialidade_to_string;
    }

    public function get_agendamento_especialidade_to_string()
    {
        if(!empty($this->agendamento_especialidade_to_string))
        {
            return $this->agendamento_especialidade_to_string;
        }
    
        $values = Agendamento::where('especialidade_id', '=', $this->id)->getIndexedArray('especialidade_id','{especialidade->descricao}');
        return implode(', ', $values);
    }

    public function set_pessoa_especialidade_pessoa_to_string($pessoa_especialidade_pessoa_to_string)
    {
        if(is_array($pessoa_especialidade_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $pessoa_especialidade_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_especialidade_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_especialidade_pessoa_to_string = $pessoa_especialidade_pessoa_to_string;
        }

        $this->vdata['pessoa_especialidade_pessoa_to_string'] = $this->pessoa_especialidade_pessoa_to_string;
    }

    public function get_pessoa_especialidade_pessoa_to_string()
    {
        if(!empty($this->pessoa_especialidade_pessoa_to_string))
        {
            return $this->pessoa_especialidade_pessoa_to_string;
        }
    
        $values = PessoaEspecialidade::where('especialidade_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_especialidade_especialidade_to_string($pessoa_especialidade_especialidade_to_string)
    {
        if(is_array($pessoa_especialidade_especialidade_to_string))
        {
            $values = Especialidade::where('id', 'in', $pessoa_especialidade_especialidade_to_string)->getIndexedArray('descricao', 'descricao');
            $this->pessoa_especialidade_especialidade_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_especialidade_especialidade_to_string = $pessoa_especialidade_especialidade_to_string;
        }

        $this->vdata['pessoa_especialidade_especialidade_to_string'] = $this->pessoa_especialidade_especialidade_to_string;
    }

    public function get_pessoa_especialidade_especialidade_to_string()
    {
        if(!empty($this->pessoa_especialidade_especialidade_to_string))
        {
            return $this->pessoa_especialidade_especialidade_to_string;
        }
    
        $values = PessoaEspecialidade::where('especialidade_id', '=', $this->id)->getIndexedArray('especialidade_id','{especialidade->descricao}');
        return implode(', ', $values);
    }

    
}

