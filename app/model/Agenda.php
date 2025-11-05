<?php

class Agenda extends TRecord
{
    const TABLENAME  = 'agenda';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Procedimento $procedimento;
    private Escritorio $escritorio;
    private Pessoa $profissional;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('escritorio_id');
        parent::addAttribute('profissional_id');
        parent::addAttribute('nome');
        parent::addAttribute('horario_inicial');
        parent::addAttribute('horario_final');
        parent::addAttribute('visualizacao_inicial');
        parent::addAttribute('horario_inicio_intervalo');
        parent::addAttribute('horario_fim_intervalo');
        parent::addAttribute('duracao');
        parent::addAttribute('dias');
        parent::addAttribute('procedimento_id');
        parent::addAttribute('cor');
        parent::addAttribute('aceita_agendamento_online');
        parent::addAttribute('publica');
        parent::addAttribute('fl_permite_choque_horario');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    
    }

    /**
     * Method set_procedimento
     * Sample of usage: $var->procedimento = $object;
     * @param $object Instance of Procedimento
     */
    public function set_procedimento(Procedimento $object)
    {
        $this->procedimento = $object;
        $this->procedimento_id = $object->id;
    }

    /**
     * Method get_procedimento
     * Sample of usage: $var->procedimento->attribute;
     * @returns Procedimento instance
     */
    public function get_procedimento()
    {
    
        // loads the associated object
        if (empty($this->procedimento))
            $this->procedimento = new Procedimento($this->procedimento_id);
    
        // returns the associated object
        return $this->procedimento;
    }
    /**
     * Method set_escritorio
     * Sample of usage: $var->escritorio = $object;
     * @param $object Instance of Escritorio
     */
    public function set_escritorio(Escritorio $object)
    {
        $this->escritorio = $object;
        $this->escritorio_id = $object->id;
    }

    /**
     * Method get_escritorio
     * Sample of usage: $var->escritorio->attribute;
     * @returns Escritorio instance
     */
    public function get_escritorio()
    {
    
        // loads the associated object
        if (empty($this->escritorio))
            $this->escritorio = new Escritorio($this->escritorio_id);
    
        // returns the associated object
        return $this->escritorio;
    }
    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_profissional(Pessoa $object)
    {
        $this->profissional = $object;
        $this->profissional_id = $object->id;
    }

    /**
     * Method get_profissional
     * Sample of usage: $var->profissional->attribute;
     * @returns Pessoa instance
     */
    public function get_profissional()
    {
    
        // loads the associated object
        if (empty($this->profissional))
            $this->profissional = new Pessoa($this->profissional_id);
    
        // returns the associated object
        return $this->profissional;
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
     * Method getBloqueios
     */
    public function getBloqueios()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', $this->id));
        return Bloqueio::getObjects( $criteria );
    }
    /**
     * Method getAgendamentos
     */
    public function getAgendamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', $this->id));
        return Agendamento::getObjects( $criteria );
    }
    /**
     * Method getAgendaProfissionals
     */
    public function getAgendaProfissionals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', $this->id));
        return AgendaProfissional::getObjects( $criteria );
    }
    /**
     * Method getCompromissos
     */
    public function getCompromissos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', $this->id));
        return Compromisso::getObjects( $criteria );
    }
    /**
     * Method getConvidados
     */
    public function getConvidados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', $this->id));
        return Convidado::getObjects( $criteria );
    }
    /**
     * Method getConvidadoCompromissos
     */
    public function getConvidadoCompromissos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', $this->id));
        return ConvidadoCompromisso::getObjects( $criteria );
    }

    public function set_bloqueio_agenda_to_string($bloqueio_agenda_to_string)
    {
        if(is_array($bloqueio_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $bloqueio_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->bloqueio_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->bloqueio_agenda_to_string = $bloqueio_agenda_to_string;
        }

        $this->vdata['bloqueio_agenda_to_string'] = $this->bloqueio_agenda_to_string;
    }

    public function get_bloqueio_agenda_to_string()
    {
        if(!empty($this->bloqueio_agenda_to_string))
        {
            return $this->bloqueio_agenda_to_string;
        }
    
        $values = Bloqueio::where('agenda_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_bloqueio_criacao_user_to_string($bloqueio_criacao_user_to_string)
    {
        if(is_array($bloqueio_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $bloqueio_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->bloqueio_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->bloqueio_criacao_user_to_string = $bloqueio_criacao_user_to_string;
        }

        $this->vdata['bloqueio_criacao_user_to_string'] = $this->bloqueio_criacao_user_to_string;
    }

    public function get_bloqueio_criacao_user_to_string()
    {
        if(!empty($this->bloqueio_criacao_user_to_string))
        {
            return $this->bloqueio_criacao_user_to_string;
        }
    
        $values = Bloqueio::where('agenda_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_bloqueio_modificacao_user_to_string($bloqueio_modificacao_user_to_string)
    {
        if(is_array($bloqueio_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $bloqueio_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->bloqueio_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->bloqueio_modificacao_user_to_string = $bloqueio_modificacao_user_to_string;
        }

        $this->vdata['bloqueio_modificacao_user_to_string'] = $this->bloqueio_modificacao_user_to_string;
    }

    public function get_bloqueio_modificacao_user_to_string()
    {
        if(!empty($this->bloqueio_modificacao_user_to_string))
        {
            return $this->bloqueio_modificacao_user_to_string;
        }
    
        $values = Bloqueio::where('agenda_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
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
    
        $values = Agendamento::where('agenda_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
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
    
        $values = Agendamento::where('agenda_id', '=', $this->id)->getIndexedArray('estado_agenda_id','{estado_agenda->nome}');
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
    
        $values = Agendamento::where('agenda_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
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
    
        $values = Agendamento::where('agenda_id', '=', $this->id)->getIndexedArray('especialidade_id','{especialidade->descricao}');
        return implode(', ', $values);
    }

    public function set_agenda_profissional_profissional_to_string($agenda_profissional_profissional_to_string)
    {
        if(is_array($agenda_profissional_profissional_to_string))
        {
            $values = Pessoa::where('id', 'in', $agenda_profissional_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_profissional_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_profissional_profissional_to_string = $agenda_profissional_profissional_to_string;
        }

        $this->vdata['agenda_profissional_profissional_to_string'] = $this->agenda_profissional_profissional_to_string;
    }

    public function get_agenda_profissional_profissional_to_string()
    {
        if(!empty($this->agenda_profissional_profissional_to_string))
        {
            return $this->agenda_profissional_profissional_to_string;
        }
    
        $values = AgendaProfissional::where('agenda_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_profissional_agenda_to_string($agenda_profissional_agenda_to_string)
    {
        if(is_array($agenda_profissional_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $agenda_profissional_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_profissional_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_profissional_agenda_to_string = $agenda_profissional_agenda_to_string;
        }

        $this->vdata['agenda_profissional_agenda_to_string'] = $this->agenda_profissional_agenda_to_string;
    }

    public function get_agenda_profissional_agenda_to_string()
    {
        if(!empty($this->agenda_profissional_agenda_to_string))
        {
            return $this->agenda_profissional_agenda_to_string;
        }
    
        $values = AgendaProfissional::where('agenda_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_compromisso_agenda_to_string($compromisso_agenda_to_string)
    {
        if(is_array($compromisso_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $compromisso_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->compromisso_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_agenda_to_string = $compromisso_agenda_to_string;
        }

        $this->vdata['compromisso_agenda_to_string'] = $this->compromisso_agenda_to_string;
    }

    public function get_compromisso_agenda_to_string()
    {
        if(!empty($this->compromisso_agenda_to_string))
        {
            return $this->compromisso_agenda_to_string;
        }
    
        $values = Compromisso::where('agenda_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_compromisso_tipo_compromisso_to_string($compromisso_tipo_compromisso_to_string)
    {
        if(is_array($compromisso_tipo_compromisso_to_string))
        {
            $values = TipoCompromisso::where('id', 'in', $compromisso_tipo_compromisso_to_string)->getIndexedArray('nome', 'nome');
            $this->compromisso_tipo_compromisso_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_tipo_compromisso_to_string = $compromisso_tipo_compromisso_to_string;
        }

        $this->vdata['compromisso_tipo_compromisso_to_string'] = $this->compromisso_tipo_compromisso_to_string;
    }

    public function get_compromisso_tipo_compromisso_to_string()
    {
        if(!empty($this->compromisso_tipo_compromisso_to_string))
        {
            return $this->compromisso_tipo_compromisso_to_string;
        }
    
        $values = Compromisso::where('agenda_id', '=', $this->id)->getIndexedArray('tipo_compromisso_id','{tipo_compromisso->nome}');
        return implode(', ', $values);
    }

    public function set_compromisso_criacao_user_to_string($compromisso_criacao_user_to_string)
    {
        if(is_array($compromisso_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $compromisso_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->compromisso_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_criacao_user_to_string = $compromisso_criacao_user_to_string;
        }

        $this->vdata['compromisso_criacao_user_to_string'] = $this->compromisso_criacao_user_to_string;
    }

    public function get_compromisso_criacao_user_to_string()
    {
        if(!empty($this->compromisso_criacao_user_to_string))
        {
            return $this->compromisso_criacao_user_to_string;
        }
    
        $values = Compromisso::where('agenda_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_compromisso_modificacao_user_to_string($compromisso_modificacao_user_to_string)
    {
        if(is_array($compromisso_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $compromisso_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->compromisso_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->compromisso_modificacao_user_to_string = $compromisso_modificacao_user_to_string;
        }

        $this->vdata['compromisso_modificacao_user_to_string'] = $this->compromisso_modificacao_user_to_string;
    }

    public function get_compromisso_modificacao_user_to_string()
    {
        if(!empty($this->compromisso_modificacao_user_to_string))
        {
            return $this->compromisso_modificacao_user_to_string;
        }
    
        $values = Compromisso::where('agenda_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_convidado_agendamento_to_string($convidado_agendamento_to_string)
    {
        if(is_array($convidado_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $convidado_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->convidado_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_agendamento_to_string = $convidado_agendamento_to_string;
        }

        $this->vdata['convidado_agendamento_to_string'] = $this->convidado_agendamento_to_string;
    }

    public function get_convidado_agendamento_to_string()
    {
        if(!empty($this->convidado_agendamento_to_string))
        {
            return $this->convidado_agendamento_to_string;
        }
    
        $values = Convidado::where('agenda_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_convidado_agenda_to_string($convidado_agenda_to_string)
    {
        if(is_array($convidado_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $convidado_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->convidado_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_agenda_to_string = $convidado_agenda_to_string;
        }

        $this->vdata['convidado_agenda_to_string'] = $this->convidado_agenda_to_string;
    }

    public function get_convidado_agenda_to_string()
    {
        if(!empty($this->convidado_agenda_to_string))
        {
            return $this->convidado_agenda_to_string;
        }
    
        $values = Convidado::where('agenda_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_convidado_criacao_user_to_string($convidado_criacao_user_to_string)
    {
        if(is_array($convidado_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_criacao_user_to_string = $convidado_criacao_user_to_string;
        }

        $this->vdata['convidado_criacao_user_to_string'] = $this->convidado_criacao_user_to_string;
    }

    public function get_convidado_criacao_user_to_string()
    {
        if(!empty($this->convidado_criacao_user_to_string))
        {
            return $this->convidado_criacao_user_to_string;
        }
    
        $values = Convidado::where('agenda_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_convidado_modificacao_user_to_string($convidado_modificacao_user_to_string)
    {
        if(is_array($convidado_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_modificacao_user_to_string = $convidado_modificacao_user_to_string;
        }

        $this->vdata['convidado_modificacao_user_to_string'] = $this->convidado_modificacao_user_to_string;
    }

    public function get_convidado_modificacao_user_to_string()
    {
        if(!empty($this->convidado_modificacao_user_to_string))
        {
            return $this->convidado_modificacao_user_to_string;
        }
    
        $values = Convidado::where('agenda_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_compromisso_to_string($convidado_compromisso_compromisso_to_string)
    {
        if(is_array($convidado_compromisso_compromisso_to_string))
        {
            $values = Compromisso::where('id', 'in', $convidado_compromisso_compromisso_to_string)->getIndexedArray('id', 'id');
            $this->convidado_compromisso_compromisso_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_compromisso_to_string = $convidado_compromisso_compromisso_to_string;
        }

        $this->vdata['convidado_compromisso_compromisso_to_string'] = $this->convidado_compromisso_compromisso_to_string;
    }

    public function get_convidado_compromisso_compromisso_to_string()
    {
        if(!empty($this->convidado_compromisso_compromisso_to_string))
        {
            return $this->convidado_compromisso_compromisso_to_string;
        }
    
        $values = ConvidadoCompromisso::where('agenda_id', '=', $this->id)->getIndexedArray('compromisso_id','{compromisso->id}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_agenda_to_string($convidado_compromisso_agenda_to_string)
    {
        if(is_array($convidado_compromisso_agenda_to_string))
        {
            $values = Agenda::where('id', 'in', $convidado_compromisso_agenda_to_string)->getIndexedArray('nome', 'nome');
            $this->convidado_compromisso_agenda_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_agenda_to_string = $convidado_compromisso_agenda_to_string;
        }

        $this->vdata['convidado_compromisso_agenda_to_string'] = $this->convidado_compromisso_agenda_to_string;
    }

    public function get_convidado_compromisso_agenda_to_string()
    {
        if(!empty($this->convidado_compromisso_agenda_to_string))
        {
            return $this->convidado_compromisso_agenda_to_string;
        }
    
        $values = ConvidadoCompromisso::where('agenda_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_criacao_user_to_string($convidado_compromisso_criacao_user_to_string)
    {
        if(is_array($convidado_compromisso_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_compromisso_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_compromisso_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_criacao_user_to_string = $convidado_compromisso_criacao_user_to_string;
        }

        $this->vdata['convidado_compromisso_criacao_user_to_string'] = $this->convidado_compromisso_criacao_user_to_string;
    }

    public function get_convidado_compromisso_criacao_user_to_string()
    {
        if(!empty($this->convidado_compromisso_criacao_user_to_string))
        {
            return $this->convidado_compromisso_criacao_user_to_string;
        }
    
        $values = ConvidadoCompromisso::where('agenda_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_convidado_compromisso_modificacao_user_to_string($convidado_compromisso_modificacao_user_to_string)
    {
        if(is_array($convidado_compromisso_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $convidado_compromisso_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->convidado_compromisso_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->convidado_compromisso_modificacao_user_to_string = $convidado_compromisso_modificacao_user_to_string;
        }

        $this->vdata['convidado_compromisso_modificacao_user_to_string'] = $this->convidado_compromisso_modificacao_user_to_string;
    }

    public function get_convidado_compromisso_modificacao_user_to_string()
    {
        if(!empty($this->convidado_compromisso_modificacao_user_to_string))
        {
            return $this->convidado_compromisso_modificacao_user_to_string;
        }
    
        $values = ConvidadoCompromisso::where('agenda_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function get_cor_agenda()
    {
        if ($this->cor)
        {
            return $this->cor;
        }
    
        return $this->get_profissional()->cor;
    }
    
}

