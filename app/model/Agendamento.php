<?php

class Agendamento extends TRecord
{
    const TABLENAME  = 'agendamento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private Pessoa $cliente;
    private EstadoAgenda $estado_agenda;
    private Agenda $agenda;
    private Especialidade $especialidade;

    private $atendimento;

                                                    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('cliente_id');
        parent::addAttribute('estado_agenda_id');
        parent::addAttribute('agenda_id');
        parent::addAttribute('especialidade_id');
        parent::addAttribute('dt_inicial');
        parent::addAttribute('dt_final');
        parent::addAttribute('agendamento_original_id');
        parent::addAttribute('observacao');
        parent::addAttribute('ativo');
        parent::addAttribute('ano_inicial');
        parent::addAttribute('mes_inicial');
        parent::addAttribute('ano_mes_inicial');
        parent::addAttribute('ano_final');
        parent::addAttribute('mes_final');
        parent::addAttribute('ano_mes_final');
        parent::addAttribute('online');
        parent::addAttribute('link_atendimento_online');
    
    }

    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_cliente(Pessoa $object)
    {
        $this->cliente = $object;
        $this->cliente_id = $object->id;
    }

    /**
     * Method get_cliente
     * Sample of usage: $var->cliente->attribute;
     * @returns Pessoa instance
     */
    public function get_cliente()
    {
    
        // loads the associated object
        if (empty($this->cliente))
            $this->cliente = new Pessoa($this->cliente_id);
    
        // returns the associated object
        return $this->cliente;
    }
    /**
     * Method set_estado_agenda
     * Sample of usage: $var->estado_agenda = $object;
     * @param $object Instance of EstadoAgenda
     */
    public function set_estado_agenda(EstadoAgenda $object)
    {
        $this->estado_agenda = $object;
        $this->estado_agenda_id = $object->id;
    }

    /**
     * Method get_estado_agenda
     * Sample of usage: $var->estado_agenda->attribute;
     * @returns EstadoAgenda instance
     */
    public function get_estado_agenda()
    {
    
        // loads the associated object
        if (empty($this->estado_agenda))
            $this->estado_agenda = new EstadoAgenda($this->estado_agenda_id);
    
        // returns the associated object
        return $this->estado_agenda;
    }
    /**
     * Method set_agenda
     * Sample of usage: $var->agenda = $object;
     * @param $object Instance of Agenda
     */
    public function set_agenda(Agenda $object)
    {
        $this->agenda = $object;
        $this->agenda_id = $object->id;
    }

    /**
     * Method get_agenda
     * Sample of usage: $var->agenda->attribute;
     * @returns Agenda instance
     */
    public function get_agenda()
    {
    
        // loads the associated object
        if (empty($this->agenda))
            $this->agenda = new Agenda($this->agenda_id);
    
        // returns the associated object
        return $this->agenda;
    }
    /**
     * Method set_especialidade
     * Sample of usage: $var->especialidade = $object;
     * @param $object Instance of Especialidade
     */
    public function set_especialidade(Especialidade $object)
    {
        $this->especialidade = $object;
        $this->especialidade_id = $object->id;
    }

    /**
     * Method get_especialidade
     * Sample of usage: $var->especialidade->attribute;
     * @returns Especialidade instance
     */
    public function get_especialidade()
    {
    
        // loads the associated object
        if (empty($this->especialidade))
            $this->especialidade = new Especialidade($this->especialidade_id);
    
        // returns the associated object
        return $this->especialidade;
    }

    /**
     * Method getAtendimentos
     */
    public function getAtendimentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agendamento_id', '=', $this->id));
        return Atendimento::getObjects( $criteria );
    }
    /**
     * Method getAgendamentoProcedimentos
     */
    public function getAgendamentoProcedimentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agendamento_id', '=', $this->id));
        return AgendamentoProcedimento::getObjects( $criteria );
    }
    /**
     * Method getConvidados
     */
    public function getConvidados()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agendamento_id', '=', $this->id));
        return Convidado::getObjects( $criteria );
    }
    /**
     * Method getEstadoAgendamentos
     */
    public function getEstadoAgendamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agendamento_id', '=', $this->id));
        return EstadoAgendamento::getObjects( $criteria );
    }
    /**
     * Method getMensagems
     */
    public function getMensagems()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agendamento_id', '=', $this->id));
        return Mensagem::getObjects( $criteria );
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
    
        $values = Atendimento::where('agendamento_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
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
    
        $values = Atendimento::where('agendamento_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
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
    
        $values = Atendimento::where('agendamento_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
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
    
        $values = Atendimento::where('agendamento_id', '=', $this->id)->getIndexedArray('tipo_atendimento_id','{tipo_atendimento->nome}');
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
    
        $values = Atendimento::where('agendamento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Atendimento::where('agendamento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_agendamento_procedimento_agendamento_to_string($agendamento_procedimento_agendamento_to_string)
    {
        if(is_array($agendamento_procedimento_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $agendamento_procedimento_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->agendamento_procedimento_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_procedimento_agendamento_to_string = $agendamento_procedimento_agendamento_to_string;
        }

        $this->vdata['agendamento_procedimento_agendamento_to_string'] = $this->agendamento_procedimento_agendamento_to_string;
    }

    public function get_agendamento_procedimento_agendamento_to_string()
    {
        if(!empty($this->agendamento_procedimento_agendamento_to_string))
        {
            return $this->agendamento_procedimento_agendamento_to_string;
        }
    
        $values = AgendamentoProcedimento::where('agendamento_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_agendamento_procedimento_procedimento_to_string($agendamento_procedimento_procedimento_to_string)
    {
        if(is_array($agendamento_procedimento_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $agendamento_procedimento_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->agendamento_procedimento_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_procedimento_procedimento_to_string = $agendamento_procedimento_procedimento_to_string;
        }

        $this->vdata['agendamento_procedimento_procedimento_to_string'] = $this->agendamento_procedimento_procedimento_to_string;
    }

    public function get_agendamento_procedimento_procedimento_to_string()
    {
        if(!empty($this->agendamento_procedimento_procedimento_to_string))
        {
            return $this->agendamento_procedimento_procedimento_to_string;
        }
    
        $values = AgendamentoProcedimento::where('agendamento_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_agendamento_procedimento_parceiro_to_string($agendamento_procedimento_parceiro_to_string)
    {
        if(is_array($agendamento_procedimento_parceiro_to_string))
        {
            $values = Parceiro::where('id', 'in', $agendamento_procedimento_parceiro_to_string)->getIndexedArray('nome', 'nome');
            $this->agendamento_procedimento_parceiro_to_string = implode(', ', $values);
        }
        else
        {
            $this->agendamento_procedimento_parceiro_to_string = $agendamento_procedimento_parceiro_to_string;
        }

        $this->vdata['agendamento_procedimento_parceiro_to_string'] = $this->agendamento_procedimento_parceiro_to_string;
    }

    public function get_agendamento_procedimento_parceiro_to_string()
    {
        if(!empty($this->agendamento_procedimento_parceiro_to_string))
        {
            return $this->agendamento_procedimento_parceiro_to_string;
        }
    
        $values = AgendamentoProcedimento::where('agendamento_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
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
    
        $values = Convidado::where('agendamento_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
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
    
        $values = Convidado::where('agendamento_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
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
    
        $values = Convidado::where('agendamento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Convidado::where('agendamento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = EstadoAgendamento::where('agendamento_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
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
    
        $values = EstadoAgendamento::where('agendamento_id', '=', $this->id)->getIndexedArray('estado_agenda_id','{estado_agenda->nome}');
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
    
        $values = EstadoAgendamento::where('agendamento_id', '=', $this->id)->getIndexedArray('system_users_id','{system_users->name}');
        return implode(', ', $values);
    }

    public function set_mensagem_agendamento_to_string($mensagem_agendamento_to_string)
    {
        if(is_array($mensagem_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $mensagem_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->mensagem_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_agendamento_to_string = $mensagem_agendamento_to_string;
        }

        $this->vdata['mensagem_agendamento_to_string'] = $this->mensagem_agendamento_to_string;
    }

    public function get_mensagem_agendamento_to_string()
    {
        if(!empty($this->mensagem_agendamento_to_string))
        {
            return $this->mensagem_agendamento_to_string;
        }
    
        $values = Mensagem::where('agendamento_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_mensagem_template_escritorio_to_string($mensagem_template_escritorio_to_string)
    {
        if(is_array($mensagem_template_escritorio_to_string))
        {
            $values = TemplateEscritorio::where('id', 'in', $mensagem_template_escritorio_to_string)->getIndexedArray('chave', 'chave');
            $this->mensagem_template_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_template_escritorio_to_string = $mensagem_template_escritorio_to_string;
        }

        $this->vdata['mensagem_template_escritorio_to_string'] = $this->mensagem_template_escritorio_to_string;
    }

    public function get_mensagem_template_escritorio_to_string()
    {
        if(!empty($this->mensagem_template_escritorio_to_string))
        {
            return $this->mensagem_template_escritorio_to_string;
        }
    
        $values = Mensagem::where('agendamento_id', '=', $this->id)->getIndexedArray('template_escritorio_id','{template_escritorio->chave}');
        return implode(', ', $values);
    }

    public function set_mensagem_system_user_to_string($mensagem_system_user_to_string)
    {
        if(is_array($mensagem_system_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $mensagem_system_user_to_string)->getIndexedArray('name', 'name');
            $this->mensagem_system_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_system_user_to_string = $mensagem_system_user_to_string;
        }

        $this->vdata['mensagem_system_user_to_string'] = $this->mensagem_system_user_to_string;
    }

    public function get_mensagem_system_user_to_string()
    {
        if(!empty($this->mensagem_system_user_to_string))
        {
            return $this->mensagem_system_user_to_string;
        }
    
        $values = Mensagem::where('agendamento_id', '=', $this->id)->getIndexedArray('system_user_id','{system_user->name}');
        return implode(', ', $values);
    }

    public function onBeforeStore($object)
    {
        if ($object->dt_inicial) {
            $object->ano_inicial = date('Y', strtotime($object->dt_inicial));
            $object->mes_inicial = date('m', strtotime($object->dt_inicial));
            $object->ano_mes_inicial = date('Ym', strtotime($object->dt_inicial));
        }
    
        if ($object->dt_final) {
            $object->ano_final = date('Y', strtotime($object->dt_final));
            $object->mes_final = date('m', strtotime($object->dt_final));
            $object->ano_mes_final = date('Ym', strtotime($object->dt_final));
        }
    }

    public function validate()
    {
        $agenda = $this->get_agenda();
    
        $dt_inicial = new DateTime($this->dt_inicial);
        $dt_final = new DateTime($this->dt_final);
    
        $hora_inicial = $dt_inicial->format('H:i');
        $hora_final = $dt_final->format('H:i');
        
        // valida horario intervalo
        if ($agenda->horario_inicio_intervalo && $agenda->horario_fim_intervalo)
        {
            if ($hora_final > $agenda->horario_inicio_intervalo && $hora_inicial < $agenda->horario_fim_intervalo)
            {
                return false;
            }
        }
    
        // valida bloqueios
        $criteria = new TCriteria;
        $criteria->add(new TFilter('agenda_id', '=', (int) $agenda->id));
    
    
        $criteriaTempoIni = new TCriteria;
        $criteriaTempoIni->add(new TFilter('dt_inicio', '<=', $dt_final->format('Y-m-d H:i')));
        $criteriaTempoIni->add(new TFilter('dt_final', '>=', $dt_inicial->format('Y-m-d H:i')));
        $criteria->add($criteriaTempoIni);
    
        $bloqueios = Bloqueio::countObjects($criteria);
    
        if ($bloqueios)
        {
            return false;
        }
    
        $semanaTrabalho = [0=>0,1=>1, 2=>2, 3=>3, 4=>4, 5=>5, 6=>6];
        $diasTrabalho = [];
        $dias = explode(',',$agenda->dias);

        foreach($dias as $dia)
        {
            $diasTrabalho[$semanaTrabalho[$dia]] = $semanaTrabalho[$dia];
        }
    
        $dataAgendamento = new DateTime($this->dt_inicial);
    
        if(! isset($diasTrabalho[$dataAgendamento->format('w')]))
        {
            return false;
        }
    
        if ($agenda->fl_permite_choque_horario != 'T')
        {
            $criteria = new TCriteria;
            $criteria->add(new TFilter('id', '<>', (int) $this->id));
            $criteria->add(new TFilter('agenda_id', '=', (int) $agenda->id));
            $criteria->add(new TFilter('estado_agenda_id', 'NOT IN', "(SELECT id FROM estado_agenda WHERE estado_final = 'S')"));
        
            $criteriaTempoIni = new TCriteria;
            $criteriaTempoIni->add(new TFilter('dt_inicial', '<', $dt_final->format('Y-m-d H:i')));
            $criteriaTempoIni->add(new TFilter('dt_final', '>', $dt_inicial->format('Y-m-d H:i')));
        
            $criteria->add($criteriaTempoIni);
        
            $agendamentos = Agendamento::countObjects($criteria);
        
            if ($agendamentos > 0)
            {
                return false;
            }
        }
    
        return true;
    }

    public function get_atendimento()
    {
        if (empty($this->atendimento))
        {
            $atendimentos = $this->getAtendimentos();
            $this->atendimento = $atendimentos ? $atendimentos[0] : null;
        }
    
        return $this->atendimento;
    }

    public function get_atendimento_id()
    {
        return $this->get_atendimento() ? $this->get_atendimento()->id : null;
    }

    public function get_hora_inicial_formatada()
    {
        return TDateTime::convertToMask($this->dt_inicial, 'yyyy-mm-dd hh:ii:ss','dd/mm/yyyy hh:ii');;
    }

    public function get_detalhe_html()
    {
        $inicio = TDateTime::convertToMask($this->dt_inicial, 'yyyy-mm-dd hh:ii:ss','dd/mm/yyyy hh:ii');
    
        $html = new TElement('div');
        $html->add("<b>Data:</b><br/>");
        $html->add($inicio);
        $html->add("<br/><b>Profissional:</b><br/>");
        $html->add($this->get_agenda()->profissional->nome_formatado);
        $html->add("<br/><b>Cliente:</b><br/>");
        $html->add($this->cliente->nome_formatado);
        $html->add('<br/><b>Procedimentos</b><br/>');
        $html->add($this->get_agendamento_procedimento_procedimento_to_string());
        if ($this->observacoes)
        {
            $html->add('<br/><b>Observações</b><br/>');
            $html->add($this->observacoes);
        }
    
        return $html;
    }

    public function get_is_online()
    {
        if($this->link_atendimento_online)
        {
            return true;
        }
    
        return false;
    }
                                                        
}

