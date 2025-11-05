<?php

class EstadoAgenda extends TRecord
{
    const TABLENAME  = 'estado_agenda';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const UPDATEDAT  = 'data_modificacao';

    const AGENDADO = '1';
    const CANCELADO = '2';
    const NAO_COMPARECEU = '3';
    const ATENDIDO = '4';
    const EM_ATENDIMENTO = '5';
    const CONFIRMADO = '6';
    const AGUARDANDO_VALIDACAO_ESCRITORIO = '7';

    private SystemUsers $modificacao_user;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
        parent::addAttribute('estado_inicial');
        parent::addAttribute('estado_final');
        parent::addAttribute('cor');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
            
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
        $criteria->add(new TFilter('estado_agenda_id', '=', $this->id));
        return Agendamento::getObjects( $criteria );
    }
    /**
     * Method getEstadoAgendamentos
     */
    public function getEstadoAgendamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('estado_agenda_id', '=', $this->id));
        return EstadoAgendamento::getObjects( $criteria );
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
    
        $values = Agendamento::where('estado_agenda_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
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
    
        $values = Agendamento::where('estado_agenda_id', '=', $this->id)->getIndexedArray('estado_agenda_id','{estado_agenda->nome}');
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
    
        $values = Agendamento::where('estado_agenda_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
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
    
        $values = Agendamento::where('estado_agenda_id', '=', $this->id)->getIndexedArray('especialidade_id','{especialidade->descricao}');
        return implode(', ', $values);
    }

    public function set_estado_agendamento_agendamento_to_string($estado_agendamento_agendamento_to_string)
    {
        if(is_array($estado_agendamento_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $estado_agendamento_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->estado_agendamento_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->estado_agendamento_agendamento_to_string = $estado_agendamento_agendamento_to_string;
        }

        $this->vdata['estado_agendamento_agendamento_to_string'] = $this->estado_agendamento_agendamento_to_string;
    }

    public function get_estado_agendamento_agendamento_to_string()
    {
        if(!empty($this->estado_agendamento_agendamento_to_string))
        {
            return $this->estado_agendamento_agendamento_to_string;
        }
    
        $values = EstadoAgendamento::where('estado_agenda_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_estado_agendamento_estado_agenda_to_string($estado_agendamento_estado_agenda_to_string)
    {
        if(is_array($estado_agendamento_estado_agenda_to_string))
        {
            $values = EstadoAgenda::where('id', 'in', $estado_agendamento_estado_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->estado_agendamento_estado_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->estado_agendamento_estado_agenda_to_string = $estado_agendamento_estado_agenda_to_string;
        }

        $this->vdata['estado_agendamento_estado_agenda_to_string'] = $this->estado_agendamento_estado_agenda_to_string;
    }

    public function get_estado_agendamento_estado_agenda_to_string()
    {
        if(!empty($this->estado_agendamento_estado_agenda_to_string))
        {
            return $this->estado_agendamento_estado_agenda_to_string;
        }
    
        $values = EstadoAgendamento::where('estado_agenda_id', '=', $this->id)->getIndexedArray('estado_agenda_id','{estado_agenda->nome}');
        return implode(', ', $values);
    }

    public function set_estado_agendamento_system_users_to_string($estado_agendamento_system_users_to_string)
    {
        if(is_array($estado_agendamento_system_users_to_string))
        {
            $values = SystemUsers::where('id', 'in', $estado_agendamento_system_users_to_string)->getIndexedArray('name', 'name');
            $this->estado_agendamento_system_users_to_string = implode(', ', $values);
        }
        else
        {
            $this->estado_agendamento_system_users_to_string = $estado_agendamento_system_users_to_string;
        }

        $this->vdata['estado_agendamento_system_users_to_string'] = $this->estado_agendamento_system_users_to_string;
    }

    public function get_estado_agendamento_system_users_to_string()
    {
        if(!empty($this->estado_agendamento_system_users_to_string))
        {
            return $this->estado_agendamento_system_users_to_string;
        }
    
        $values = EstadoAgendamento::where('estado_agenda_id', '=', $this->id)->getIndexedArray('system_users_id','{system_users->name}');
        return implode(', ', $values);
    }

    
}

