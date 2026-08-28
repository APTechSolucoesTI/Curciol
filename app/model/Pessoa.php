<?php

class Pessoa extends TRecord
{
    const TABLENAME  = 'pessoa';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private TipoProfissional $tipo_profissional;
    private SystemUsers $system_users;
    private TipoPessoa $tipo_pessoa;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private Sexo $sexo;
    private Nacionalidade $nacionalidade;
    private EstadoCivil $estado_civil;
    private SituacaoProfissional $situacao_profissional;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tipo_pessoa_id');
        parent::addAttribute('nome');
        parent::addAttribute('nome_busca');
        parent::addAttribute('email');
        parent::addAttribute('telefone');
        parent::addAttribute('aceita_receber_mensagen_whatsapp');
        parent::addAttribute('system_users_id');
        parent::addAttribute('dt_nascimento_abertura');
        parent::addAttribute('dt_falecimento');
        parent::addAttribute('cpf_cnpj');
        parent::addAttribute('rg_ie');
        parent::addAttribute('orgao_emissor');
        parent::addAttribute('sexo_id');
        parent::addAttribute('nacionalidade_id');
        parent::addAttribute('estado_civil_id');
        parent::addAttribute('profissao');
        parent::addAttribute('nit');
        parent::addAttribute('ctps');
        parent::addAttribute('situacao_profissional_id');
        parent::addAttribute('orgao');
        parent::addAttribute('unidade');
        parent::addAttribute('observacao');
        parent::addAttribute('assinatura');
        parent::addAttribute('tratamento');
        parent::addAttribute('tipo_profissional_id');
        parent::addAttribute('orgao_registro_profissional');
        parent::addAttribute('registro_profissional');
        parent::addAttribute('usuario');
        parent::addAttribute('senha');
        parent::addAttribute('foto');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
        parent::addAttribute('chave_aasp');
    
    }

    /**
     * Method set_tipo_profissional
     * Sample of usage: $var->tipo_profissional = $object;
     * @param $object Instance of TipoProfissional
     */
    public function set_tipo_profissional(TipoProfissional $object)
    {
        $this->tipo_profissional = $object;
        $this->tipo_profissional_id = $object->id;
    }

    /**
     * Method get_tipo_profissional
     * Sample of usage: $var->tipo_profissional->attribute;
     * @returns TipoProfissional instance
     */
    public function get_tipo_profissional()
    {
    
        // loads the associated object
        if (empty($this->tipo_profissional))
            $this->tipo_profissional = new TipoProfissional($this->tipo_profissional_id);
    
        // returns the associated object
        return $this->tipo_profissional;
    }
    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_system_users(SystemUsers $object)
    {
        $this->system_users = $object;
        $this->system_users_id = $object->id;
    }

    /**
     * Method get_system_users
     * Sample of usage: $var->system_users->attribute;
     * @returns SystemUsers instance
     */
    public function get_system_users()
    {
    
        // loads the associated object
        if (empty($this->system_users))
            $this->system_users = new SystemUsers($this->system_users_id);
    
        // returns the associated object
        return $this->system_users;
    }
    /**
     * Method set_tipo_pessoa
     * Sample of usage: $var->tipo_pessoa = $object;
     * @param $object Instance of TipoPessoa
     */
    public function set_tipo_pessoa(TipoPessoa $object)
    {
        $this->tipo_pessoa = $object;
        $this->tipo_pessoa_id = $object->id;
    }

    /**
     * Method get_tipo_pessoa
     * Sample of usage: $var->tipo_pessoa->attribute;
     * @returns TipoPessoa instance
     */
    public function get_tipo_pessoa()
    {
    
        // loads the associated object
        if (empty($this->tipo_pessoa))
            $this->tipo_pessoa = new TipoPessoa($this->tipo_pessoa_id);
    
        // returns the associated object
        return $this->tipo_pessoa;
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
     * Method set_sexo
     * Sample of usage: $var->sexo = $object;
     * @param $object Instance of Sexo
     */
    public function set_sexo(Sexo $object)
    {
        $this->sexo = $object;
        $this->sexo_id = $object->id;
    }

    /**
     * Method get_sexo
     * Sample of usage: $var->sexo->attribute;
     * @returns Sexo instance
     */
    public function get_sexo()
    {
    
        // loads the associated object
        if (empty($this->sexo))
            $this->sexo = new Sexo($this->sexo_id);
    
        // returns the associated object
        return $this->sexo;
    }
    /**
     * Method set_nacionalidade
     * Sample of usage: $var->nacionalidade = $object;
     * @param $object Instance of Nacionalidade
     */
    public function set_nacionalidade(Nacionalidade $object)
    {
        $this->nacionalidade = $object;
        $this->nacionalidade_id = $object->id;
    }

    /**
     * Method get_nacionalidade
     * Sample of usage: $var->nacionalidade->attribute;
     * @returns Nacionalidade instance
     */
    public function get_nacionalidade()
    {
    
        // loads the associated object
        if (empty($this->nacionalidade))
            $this->nacionalidade = new Nacionalidade($this->nacionalidade_id);
    
        // returns the associated object
        return $this->nacionalidade;
    }
    /**
     * Method set_estado_civil
     * Sample of usage: $var->estado_civil = $object;
     * @param $object Instance of EstadoCivil
     */
    public function set_estado_civil(EstadoCivil $object)
    {
        $this->estado_civil = $object;
        $this->estado_civil_id = $object->id;
    }

    /**
     * Method get_estado_civil
     * Sample of usage: $var->estado_civil->attribute;
     * @returns EstadoCivil instance
     */
    public function get_estado_civil()
    {
    
        // loads the associated object
        if (empty($this->estado_civil))
            $this->estado_civil = new EstadoCivil($this->estado_civil_id);
    
        // returns the associated object
        return $this->estado_civil;
    }
    /**
     * Method set_situacao_profissional
     * Sample of usage: $var->situacao_profissional = $object;
     * @param $object Instance of SituacaoProfissional
     */
    public function set_situacao_profissional(SituacaoProfissional $object)
    {
        $this->situacao_profissional = $object;
        $this->situacao_profissional_id = $object->id;
    }

    /**
     * Method get_situacao_profissional
     * Sample of usage: $var->situacao_profissional->attribute;
     * @returns SituacaoProfissional instance
     */
    public function get_situacao_profissional()
    {
    
        // loads the associated object
        if (empty($this->situacao_profissional))
            $this->situacao_profissional = new SituacaoProfissional($this->situacao_profissional_id);
    
        // returns the associated object
        return $this->situacao_profissional;
    }

    /**
     * Method getAtendimentos
     */
    public function getAtendimentosByProfissionals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('profissional_id', '=', $this->id));
        return Atendimento::getObjects( $criteria );
    }
    /**
     * Method getAgendas
     */
    public function getAgendas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('profissional_id', '=', $this->id));
        return Agenda::getObjects( $criteria );
    }
    /**
     * Method getAgendamentos
     */
    public function getAgendamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('cliente_id', '=', $this->id));
        return Agendamento::getObjects( $criteria );
    }
    /**
     * Method getAgendaProfissionals
     */
    public function getAgendaProfissionals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('profissional_id', '=', $this->id));
        return AgendaProfissional::getObjects( $criteria );
    }
    /**
     * Method getAtendimentos
     */
    public function getAtendimentosByClientes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('cliente_id', '=', $this->id));
        return Atendimento::getObjects( $criteria );
    }
    /**
     * Method getClassificacoesClientes
     */
    public function getClassificacoesClientes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return ClassificacoesCliente::getObjects( $criteria );
    }
    /**
     * Method getClassificacoesContrapartes
     */
    public function getClassificacoesContrapartes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return ClassificacoesContraparte::getObjects( $criteria );
    }
    /**
     * Method getContas
     */
    public function getContasByPessoas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return Conta::getObjects( $criteria );
    }
    /**
     * Method getContas
     */
    public function getContasByProfissionals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('profissional_id', '=', $this->id));
        return Conta::getObjects( $criteria );
    }
    /**
     * Method getContrapartes
     */
    public function getContrapartes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return Contraparte::getObjects( $criteria );
    }
    /**
     * Method getContratoPessoas
     */
    public function getContratoPessoas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('cliente_id', '=', $this->id));
        return ContratoPessoa::getObjects( $criteria );
    }
    /**
     * Method getContratoRepasses
     */
    public function getContratoRepasses()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return ContratoRepasse::getObjects( $criteria );
    }
    /**
     * Method getContratoRepresentantes
     */
    public function getContratoRepresentantes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('representante_id', '=', $this->id));
        return ContratoRepresentante::getObjects( $criteria );
    }
    /**
     * Method getParceiros
     */
    public function getParceiros()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return Parceiro::getObjects( $criteria );
    }
    /**
     * Method getPessoaContatos
     */
    public function getPessoaContatos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return PessoaContato::getObjects( $criteria );
    }
    /**
     * Method getPessoaEnderecos
     */
    public function getPessoaEnderecos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return PessoaEndereco::getObjects( $criteria );
    }
    /**
     * Method getPessoaEspecialidades
     */
    public function getPessoaEspecialidades()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return PessoaEspecialidade::getObjects( $criteria );
    }
    /**
     * Method getPessoaGrupos
     */
    public function getPessoaGrupos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return PessoaGrupo::getObjects( $criteria );
    }
    /**
     * Method getPessoaRepresentantesLegaiss
     */
    public function getPessoaRepresentantesLegaissByPessoaJuridicas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_juridica_id', '=', $this->id));
        return PessoaRepresentantesLegais::getObjects( $criteria );
    }
    /**
     * Method getPessoaRepresentantesLegaiss
     */
    public function getPessoaRepresentantesLegaissByRepresentantes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('representante_id', '=', $this->id));
        return PessoaRepresentantesLegais::getObjects( $criteria );
    }
    /**
     * Method getProcessos
     */
    public function getProcessos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('responsavel_id', '=', $this->id));
        return Processo::getObjects( $criteria );
    }
    /**
     * Method getPublicacaoProfissionals
     */
    public function getPublicacaoProfissionals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('profissional_id', '=', $this->id));
        return PublicacaoProfissional::getObjects( $criteria );
    }
    /**
     * Method getTarefaClientes
     */
    public function getTarefaClientes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('cliente_id', '=', $this->id));
        return TarefaCliente::getObjects( $criteria );
    }
    /**
     * Method getLancamentoProfissionals
     */
    public function getLancamentoProfissionals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return LancamentoProfissional::getObjects( $criteria );
    }
    /**
     * Method getRequisicaoPagamentoClientes
     */
    public function getRequisicaoPagamentoClientesByPessoas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return RequisicaoPagamentoCliente::getObjects( $criteria );
    }
    /**
     * Method getRequisicaoPagamentoClientes
     */
    public function getRequisicaoPagamentoClientesByEntidadeDevedoras()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('entidade_devedora_id', '=', $this->id));
        return RequisicaoPagamentoCliente::getObjects( $criteria );
    }
    /**
     * Method getContaProfissionals
     */
    public function getContaProfissionals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('pessoa_id', '=', $this->id));
        return ContaProfissional::getObjects( $criteria );
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
    
        $values = Atendimento::where('cliente_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
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
    
        $values = Atendimento::where('cliente_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
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
    
        $values = Atendimento::where('cliente_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
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
    
        $values = Atendimento::where('cliente_id', '=', $this->id)->getIndexedArray('tipo_atendimento_id','{tipo_atendimento->nome}');
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
    
        $values = Atendimento::where('cliente_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Atendimento::where('cliente_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_agenda_escritorio_to_string($agenda_escritorio_to_string)
    {
        if(is_array($agenda_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $agenda_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_escritorio_to_string = $agenda_escritorio_to_string;
        }

        $this->vdata['agenda_escritorio_to_string'] = $this->agenda_escritorio_to_string;
    }

    public function get_agenda_escritorio_to_string()
    {
        if(!empty($this->agenda_escritorio_to_string))
        {
            return $this->agenda_escritorio_to_string;
        }
    
        $values = Agenda::where('profissional_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_profissional_to_string($agenda_profissional_to_string)
    {
        if(is_array($agenda_profissional_to_string))
        {
            $values = Pessoa::where('id', 'in', $agenda_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_profissional_to_string = $agenda_profissional_to_string;
        }

        $this->vdata['agenda_profissional_to_string'] = $this->agenda_profissional_to_string;
    }

    public function get_agenda_profissional_to_string()
    {
        if(!empty($this->agenda_profissional_to_string))
        {
            return $this->agenda_profissional_to_string;
        }
    
        $values = Agenda::where('profissional_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_procedimento_to_string($agenda_procedimento_to_string)
    {
        if(is_array($agenda_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $agenda_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->agenda_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_procedimento_to_string = $agenda_procedimento_to_string;
        }

        $this->vdata['agenda_procedimento_to_string'] = $this->agenda_procedimento_to_string;
    }

    public function get_agenda_procedimento_to_string()
    {
        if(!empty($this->agenda_procedimento_to_string))
        {
            return $this->agenda_procedimento_to_string;
        }
    
        $values = Agenda::where('profissional_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_agenda_criacao_user_to_string($agenda_criacao_user_to_string)
    {
        if(is_array($agenda_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $agenda_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->agenda_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_criacao_user_to_string = $agenda_criacao_user_to_string;
        }

        $this->vdata['agenda_criacao_user_to_string'] = $this->agenda_criacao_user_to_string;
    }

    public function get_agenda_criacao_user_to_string()
    {
        if(!empty($this->agenda_criacao_user_to_string))
        {
            return $this->agenda_criacao_user_to_string;
        }
    
        $values = Agenda::where('profissional_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_agenda_modificacao_user_to_string($agenda_modificacao_user_to_string)
    {
        if(is_array($agenda_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $agenda_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->agenda_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->agenda_modificacao_user_to_string = $agenda_modificacao_user_to_string;
        }

        $this->vdata['agenda_modificacao_user_to_string'] = $this->agenda_modificacao_user_to_string;
    }

    public function get_agenda_modificacao_user_to_string()
    {
        if(!empty($this->agenda_modificacao_user_to_string))
        {
            return $this->agenda_modificacao_user_to_string;
        }
    
        $values = Agenda::where('profissional_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = Agendamento::where('cliente_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
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
    
        $values = Agendamento::where('cliente_id', '=', $this->id)->getIndexedArray('estado_agenda_id','{estado_agenda->nome}');
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
    
        $values = Agendamento::where('cliente_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
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
    
        $values = Agendamento::where('cliente_id', '=', $this->id)->getIndexedArray('especialidade_id','{especialidade->descricao}');
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
    
        $values = AgendaProfissional::where('profissional_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
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
    
        $values = AgendaProfissional::where('profissional_id', '=', $this->id)->getIndexedArray('agenda_id','{agenda->nome}');
        return implode(', ', $values);
    }

    public function set_classificacoes_cliente_pessoa_to_string($classificacoes_cliente_pessoa_to_string)
    {
        if(is_array($classificacoes_cliente_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $classificacoes_cliente_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->classificacoes_cliente_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_cliente_pessoa_to_string = $classificacoes_cliente_pessoa_to_string;
        }

        $this->vdata['classificacoes_cliente_pessoa_to_string'] = $this->classificacoes_cliente_pessoa_to_string;
    }

    public function get_classificacoes_cliente_pessoa_to_string()
    {
        if(!empty($this->classificacoes_cliente_pessoa_to_string))
        {
            return $this->classificacoes_cliente_pessoa_to_string;
        }
    
        $values = ClassificacoesCliente::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_classificacoes_cliente_classificacoes_to_string($classificacoes_cliente_classificacoes_to_string)
    {
        if(is_array($classificacoes_cliente_classificacoes_to_string))
        {
            $values = Classificacoes::where('id', 'in', $classificacoes_cliente_classificacoes_to_string)->getIndexedArray('nome', 'nome');
            $this->classificacoes_cliente_classificacoes_to_string = implode(', ', $values);
        }
        else
        {
            $this->classificacoes_cliente_classificacoes_to_string = $classificacoes_cliente_classificacoes_to_string;
        }

        $this->vdata['classificacoes_cliente_classificacoes_to_string'] = $this->classificacoes_cliente_classificacoes_to_string;
    }

    public function get_classificacoes_cliente_classificacoes_to_string()
    {
        if(!empty($this->classificacoes_cliente_classificacoes_to_string))
        {
            return $this->classificacoes_cliente_classificacoes_to_string;
        }
    
        $values = ClassificacoesCliente::where('pessoa_id', '=', $this->id)->getIndexedArray('classificacoes_id','{classificacoes->nome}');
        return implode(', ', $values);
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
    
        $values = ClassificacoesContraparte::where('pessoa_id', '=', $this->id)->getIndexedArray('contraparte_id','{contraparte->id}');
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
    
        $values = ClassificacoesContraparte::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
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
    
        $values = ClassificacoesContraparte::where('pessoa_id', '=', $this->id)->getIndexedArray('classificacoes_contraparte_dados_id','{classificacoes_contraparte_dados->nome}');
        return implode(', ', $values);
    }

    public function set_conta_pessoa_to_string($conta_pessoa_to_string)
    {
        if(is_array($conta_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $conta_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_pessoa_to_string = $conta_pessoa_to_string;
        }

        $this->vdata['conta_pessoa_to_string'] = $this->conta_pessoa_to_string;
    }

    public function get_conta_pessoa_to_string()
    {
        if(!empty($this->conta_pessoa_to_string))
        {
            return $this->conta_pessoa_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_conta_categoria_conta_to_string($conta_categoria_conta_to_string)
    {
        if(is_array($conta_categoria_conta_to_string))
        {
            $values = CategoriaConta::where('id', 'in', $conta_categoria_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_categoria_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_categoria_conta_to_string = $conta_categoria_conta_to_string;
        }

        $this->vdata['conta_categoria_conta_to_string'] = $this->conta_categoria_conta_to_string;
    }

    public function get_conta_categoria_conta_to_string()
    {
        if(!empty($this->conta_categoria_conta_to_string))
        {
            return $this->conta_categoria_conta_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
        return implode(', ', $values);
    }

    public function set_conta_tipo_conta_to_string($conta_tipo_conta_to_string)
    {
        if(is_array($conta_tipo_conta_to_string))
        {
            $values = TipoConta::where('id', 'in', $conta_tipo_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_tipo_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_tipo_conta_to_string = $conta_tipo_conta_to_string;
        }

        $this->vdata['conta_tipo_conta_to_string'] = $this->conta_tipo_conta_to_string;
    }

    public function get_conta_tipo_conta_to_string()
    {
        if(!empty($this->conta_tipo_conta_to_string))
        {
            return $this->conta_tipo_conta_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
        return implode(', ', $values);
    }

    public function set_conta_escritorio_to_string($conta_escritorio_to_string)
    {
        if(is_array($conta_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $conta_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_escritorio_to_string = $conta_escritorio_to_string;
        }

        $this->vdata['conta_escritorio_to_string'] = $this->conta_escritorio_to_string;
    }

    public function get_conta_escritorio_to_string()
    {
        if(!empty($this->conta_escritorio_to_string))
        {
            return $this->conta_escritorio_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_conta_tipo_documento_financeiro_to_string($conta_tipo_documento_financeiro_to_string)
    {
        if(is_array($conta_tipo_documento_financeiro_to_string))
        {
            $values = TipoDocumentoFinanceiro::where('id', 'in', $conta_tipo_documento_financeiro_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_tipo_documento_financeiro_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_tipo_documento_financeiro_to_string = $conta_tipo_documento_financeiro_to_string;
        }

        $this->vdata['conta_tipo_documento_financeiro_to_string'] = $this->conta_tipo_documento_financeiro_to_string;
    }

    public function get_conta_tipo_documento_financeiro_to_string()
    {
        if(!empty($this->conta_tipo_documento_financeiro_to_string))
        {
            return $this->conta_tipo_documento_financeiro_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('tipo_documento_financeiro_id','{tipo_documento_financeiro->nome}');
        return implode(', ', $values);
    }

    public function set_conta_atendimento_to_string($conta_atendimento_to_string)
    {
        if(is_array($conta_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $conta_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->conta_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_atendimento_to_string = $conta_atendimento_to_string;
        }

        $this->vdata['conta_atendimento_to_string'] = $this->conta_atendimento_to_string;
    }

    public function get_conta_atendimento_to_string()
    {
        if(!empty($this->conta_atendimento_to_string))
        {
            return $this->conta_atendimento_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_conta_contrato_to_string($conta_contrato_to_string)
    {
        if(is_array($conta_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $conta_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->conta_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_contrato_to_string = $conta_contrato_to_string;
        }

        $this->vdata['conta_contrato_to_string'] = $this->conta_contrato_to_string;
    }

    public function get_conta_contrato_to_string()
    {
        if(!empty($this->conta_contrato_to_string))
        {
            return $this->conta_contrato_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_conta_profissional_to_string($conta_profissional_to_string)
    {
        if(is_array($conta_profissional_to_string))
        {
            $values = Pessoa::where('id', 'in', $conta_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_profissional_to_string = $conta_profissional_to_string;
        }

        $this->vdata['conta_profissional_to_string'] = $this->conta_profissional_to_string;
    }

    public function get_conta_profissional_to_string()
    {
        if(!empty($this->conta_profissional_to_string))
        {
            return $this->conta_profissional_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_conta_processo_to_string($conta_processo_to_string)
    {
        if(is_array($conta_processo_to_string))
        {
            $values = Processo::where('id', 'in', $conta_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->conta_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_processo_to_string = $conta_processo_to_string;
        }

        $this->vdata['conta_processo_to_string'] = $this->conta_processo_to_string;
    }

    public function get_conta_processo_to_string()
    {
        if(!empty($this->conta_processo_to_string))
        {
            return $this->conta_processo_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_conta_criacao_user_to_string($conta_criacao_user_to_string)
    {
        if(is_array($conta_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $conta_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->conta_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_criacao_user_to_string = $conta_criacao_user_to_string;
        }

        $this->vdata['conta_criacao_user_to_string'] = $this->conta_criacao_user_to_string;
    }

    public function get_conta_criacao_user_to_string()
    {
        if(!empty($this->conta_criacao_user_to_string))
        {
            return $this->conta_criacao_user_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_conta_modificacao_user_to_string($conta_modificacao_user_to_string)
    {
        if(is_array($conta_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $conta_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->conta_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_modificacao_user_to_string = $conta_modificacao_user_to_string;
        }

        $this->vdata['conta_modificacao_user_to_string'] = $this->conta_modificacao_user_to_string;
    }

    public function get_conta_modificacao_user_to_string()
    {
        if(!empty($this->conta_modificacao_user_to_string))
        {
            return $this->conta_modificacao_user_to_string;
        }
    
        $values = Conta::where('profissional_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contraparte_processo_to_string($contraparte_processo_to_string)
    {
        if(is_array($contraparte_processo_to_string))
        {
            $values = Processo::where('id', 'in', $contraparte_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->contraparte_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->contraparte_processo_to_string = $contraparte_processo_to_string;
        }

        $this->vdata['contraparte_processo_to_string'] = $this->contraparte_processo_to_string;
    }

    public function get_contraparte_processo_to_string()
    {
        if(!empty($this->contraparte_processo_to_string))
        {
            return $this->contraparte_processo_to_string;
        }
    
        $values = Contraparte::where('pessoa_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_contraparte_pessoa_to_string($contraparte_pessoa_to_string)
    {
        if(is_array($contraparte_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $contraparte_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->contraparte_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->contraparte_pessoa_to_string = $contraparte_pessoa_to_string;
        }

        $this->vdata['contraparte_pessoa_to_string'] = $this->contraparte_pessoa_to_string;
    }

    public function get_contraparte_pessoa_to_string()
    {
        if(!empty($this->contraparte_pessoa_to_string))
        {
            return $this->contraparte_pessoa_to_string;
        }
    
        $values = Contraparte::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_contraparte_criacao_user_to_string($contraparte_criacao_user_to_string)
    {
        if(is_array($contraparte_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contraparte_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contraparte_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contraparte_criacao_user_to_string = $contraparte_criacao_user_to_string;
        }

        $this->vdata['contraparte_criacao_user_to_string'] = $this->contraparte_criacao_user_to_string;
    }

    public function get_contraparte_criacao_user_to_string()
    {
        if(!empty($this->contraparte_criacao_user_to_string))
        {
            return $this->contraparte_criacao_user_to_string;
        }
    
        $values = Contraparte::where('pessoa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contraparte_modificacao_user_to_string($contraparte_modificacao_user_to_string)
    {
        if(is_array($contraparte_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contraparte_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contraparte_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contraparte_modificacao_user_to_string = $contraparte_modificacao_user_to_string;
        }

        $this->vdata['contraparte_modificacao_user_to_string'] = $this->contraparte_modificacao_user_to_string;
    }

    public function get_contraparte_modificacao_user_to_string()
    {
        if(!empty($this->contraparte_modificacao_user_to_string))
        {
            return $this->contraparte_modificacao_user_to_string;
        }
    
        $values = Contraparte::where('pessoa_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pessoa_contrato_to_string($contrato_pessoa_contrato_to_string)
    {
        if(is_array($contrato_pessoa_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_pessoa_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_pessoa_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pessoa_contrato_to_string = $contrato_pessoa_contrato_to_string;
        }

        $this->vdata['contrato_pessoa_contrato_to_string'] = $this->contrato_pessoa_contrato_to_string;
    }

    public function get_contrato_pessoa_contrato_to_string()
    {
        if(!empty($this->contrato_pessoa_contrato_to_string))
        {
            return $this->contrato_pessoa_contrato_to_string;
        }
    
        $values = ContratoPessoa::where('cliente_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_pessoa_cliente_to_string($contrato_pessoa_cliente_to_string)
    {
        if(is_array($contrato_pessoa_cliente_to_string))
        {
            $values = Pessoa::where('id', 'in', $contrato_pessoa_cliente_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pessoa_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pessoa_cliente_to_string = $contrato_pessoa_cliente_to_string;
        }

        $this->vdata['contrato_pessoa_cliente_to_string'] = $this->contrato_pessoa_cliente_to_string;
    }

    public function get_contrato_pessoa_cliente_to_string()
    {
        if(!empty($this->contrato_pessoa_cliente_to_string))
        {
            return $this->contrato_pessoa_cliente_to_string;
        }
    
        $values = ContratoPessoa::where('cliente_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_repasse_contrato_to_string($contrato_repasse_contrato_to_string)
    {
        if(is_array($contrato_repasse_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_repasse_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_repasse_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_repasse_contrato_to_string = $contrato_repasse_contrato_to_string;
        }

        $this->vdata['contrato_repasse_contrato_to_string'] = $this->contrato_repasse_contrato_to_string;
    }

    public function get_contrato_repasse_contrato_to_string()
    {
        if(!empty($this->contrato_repasse_contrato_to_string))
        {
            return $this->contrato_repasse_contrato_to_string;
        }
    
        $values = ContratoRepasse::where('pessoa_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_repasse_pessoa_to_string($contrato_repasse_pessoa_to_string)
    {
        if(is_array($contrato_repasse_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $contrato_repasse_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_repasse_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_repasse_pessoa_to_string = $contrato_repasse_pessoa_to_string;
        }

        $this->vdata['contrato_repasse_pessoa_to_string'] = $this->contrato_repasse_pessoa_to_string;
    }

    public function get_contrato_repasse_pessoa_to_string()
    {
        if(!empty($this->contrato_repasse_pessoa_to_string))
        {
            return $this->contrato_repasse_pessoa_to_string;
        }
    
        $values = ContratoRepasse::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_representante_contrato_to_string($contrato_representante_contrato_to_string)
    {
        if(is_array($contrato_representante_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_representante_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_representante_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_representante_contrato_to_string = $contrato_representante_contrato_to_string;
        }

        $this->vdata['contrato_representante_contrato_to_string'] = $this->contrato_representante_contrato_to_string;
    }

    public function get_contrato_representante_contrato_to_string()
    {
        if(!empty($this->contrato_representante_contrato_to_string))
        {
            return $this->contrato_representante_contrato_to_string;
        }
    
        $values = ContratoRepresentante::where('representante_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_representante_representante_to_string($contrato_representante_representante_to_string)
    {
        if(is_array($contrato_representante_representante_to_string))
        {
            $values = Pessoa::where('id', 'in', $contrato_representante_representante_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_representante_representante_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_representante_representante_to_string = $contrato_representante_representante_to_string;
        }

        $this->vdata['contrato_representante_representante_to_string'] = $this->contrato_representante_representante_to_string;
    }

    public function get_contrato_representante_representante_to_string()
    {
        if(!empty($this->contrato_representante_representante_to_string))
        {
            return $this->contrato_representante_representante_to_string;
        }
    
        $values = ContratoRepresentante::where('representante_id', '=', $this->id)->getIndexedArray('representante_id','{representante->nome}');
        return implode(', ', $values);
    }

    public function set_parceiro_pessoa_to_string($parceiro_pessoa_to_string)
    {
        if(is_array($parceiro_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $parceiro_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->parceiro_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->parceiro_pessoa_to_string = $parceiro_pessoa_to_string;
        }

        $this->vdata['parceiro_pessoa_to_string'] = $this->parceiro_pessoa_to_string;
    }

    public function get_parceiro_pessoa_to_string()
    {
        if(!empty($this->parceiro_pessoa_to_string))
        {
            return $this->parceiro_pessoa_to_string;
        }
    
        $values = Parceiro::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_parceiro_criacao_user_to_string($parceiro_criacao_user_to_string)
    {
        if(is_array($parceiro_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $parceiro_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->parceiro_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->parceiro_criacao_user_to_string = $parceiro_criacao_user_to_string;
        }

        $this->vdata['parceiro_criacao_user_to_string'] = $this->parceiro_criacao_user_to_string;
    }

    public function get_parceiro_criacao_user_to_string()
    {
        if(!empty($this->parceiro_criacao_user_to_string))
        {
            return $this->parceiro_criacao_user_to_string;
        }
    
        $values = Parceiro::where('pessoa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_parceiro_modificacao_user_to_string($parceiro_modificacao_user_to_string)
    {
        if(is_array($parceiro_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $parceiro_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->parceiro_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->parceiro_modificacao_user_to_string = $parceiro_modificacao_user_to_string;
        }

        $this->vdata['parceiro_modificacao_user_to_string'] = $this->parceiro_modificacao_user_to_string;
    }

    public function get_parceiro_modificacao_user_to_string()
    {
        if(!empty($this->parceiro_modificacao_user_to_string))
        {
            return $this->parceiro_modificacao_user_to_string;
        }
    
        $values = Parceiro::where('pessoa_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_pessoa_contato_pessoa_to_string($pessoa_contato_pessoa_to_string)
    {
        if(is_array($pessoa_contato_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $pessoa_contato_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_contato_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_contato_pessoa_to_string = $pessoa_contato_pessoa_to_string;
        }

        $this->vdata['pessoa_contato_pessoa_to_string'] = $this->pessoa_contato_pessoa_to_string;
    }

    public function get_pessoa_contato_pessoa_to_string()
    {
        if(!empty($this->pessoa_contato_pessoa_to_string))
        {
            return $this->pessoa_contato_pessoa_to_string;
        }
    
        $values = PessoaContato::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_endereco_pessoa_to_string($pessoa_endereco_pessoa_to_string)
    {
        if(is_array($pessoa_endereco_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $pessoa_endereco_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_endereco_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_endereco_pessoa_to_string = $pessoa_endereco_pessoa_to_string;
        }

        $this->vdata['pessoa_endereco_pessoa_to_string'] = $this->pessoa_endereco_pessoa_to_string;
    }

    public function get_pessoa_endereco_pessoa_to_string()
    {
        if(!empty($this->pessoa_endereco_pessoa_to_string))
        {
            return $this->pessoa_endereco_pessoa_to_string;
        }
    
        $values = PessoaEndereco::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_endereco_cidade_to_string($pessoa_endereco_cidade_to_string)
    {
        if(is_array($pessoa_endereco_cidade_to_string))
        {
            $values = Cidade::where('id', 'in', $pessoa_endereco_cidade_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_endereco_cidade_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_endereco_cidade_to_string = $pessoa_endereco_cidade_to_string;
        }

        $this->vdata['pessoa_endereco_cidade_to_string'] = $this->pessoa_endereco_cidade_to_string;
    }

    public function get_pessoa_endereco_cidade_to_string()
    {
        if(!empty($this->pessoa_endereco_cidade_to_string))
        {
            return $this->pessoa_endereco_cidade_to_string;
        }
    
        $values = PessoaEndereco::where('pessoa_id', '=', $this->id)->getIndexedArray('cidade_id','{cidade->nome}');
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
    
        $values = PessoaEspecialidade::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
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
    
        $values = PessoaEspecialidade::where('pessoa_id', '=', $this->id)->getIndexedArray('especialidade_id','{especialidade->descricao}');
        return implode(', ', $values);
    }

    public function set_pessoa_grupo_pessoa_to_string($pessoa_grupo_pessoa_to_string)
    {
        if(is_array($pessoa_grupo_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $pessoa_grupo_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_grupo_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_grupo_pessoa_to_string = $pessoa_grupo_pessoa_to_string;
        }

        $this->vdata['pessoa_grupo_pessoa_to_string'] = $this->pessoa_grupo_pessoa_to_string;
    }

    public function get_pessoa_grupo_pessoa_to_string()
    {
        if(!empty($this->pessoa_grupo_pessoa_to_string))
        {
            return $this->pessoa_grupo_pessoa_to_string;
        }
    
        $values = PessoaGrupo::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_grupo_grupo_to_string($pessoa_grupo_grupo_to_string)
    {
        if(is_array($pessoa_grupo_grupo_to_string))
        {
            $values = Grupo::where('id', 'in', $pessoa_grupo_grupo_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_grupo_grupo_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_grupo_grupo_to_string = $pessoa_grupo_grupo_to_string;
        }

        $this->vdata['pessoa_grupo_grupo_to_string'] = $this->pessoa_grupo_grupo_to_string;
    }

    public function get_pessoa_grupo_grupo_to_string()
    {
        if(!empty($this->pessoa_grupo_grupo_to_string))
        {
            return $this->pessoa_grupo_grupo_to_string;
        }
    
        $values = PessoaGrupo::where('pessoa_id', '=', $this->id)->getIndexedArray('grupo_id','{grupo->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_representantes_legais_pessoa_juridica_to_string($pessoa_representantes_legais_pessoa_juridica_to_string)
    {
        if(is_array($pessoa_representantes_legais_pessoa_juridica_to_string))
        {
            $values = Pessoa::where('id', 'in', $pessoa_representantes_legais_pessoa_juridica_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_representantes_legais_pessoa_juridica_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_representantes_legais_pessoa_juridica_to_string = $pessoa_representantes_legais_pessoa_juridica_to_string;
        }

        $this->vdata['pessoa_representantes_legais_pessoa_juridica_to_string'] = $this->pessoa_representantes_legais_pessoa_juridica_to_string;
    }

    public function get_pessoa_representantes_legais_pessoa_juridica_to_string()
    {
        if(!empty($this->pessoa_representantes_legais_pessoa_juridica_to_string))
        {
            return $this->pessoa_representantes_legais_pessoa_juridica_to_string;
        }
    
        $values = PessoaRepresentantesLegais::where('representante_id', '=', $this->id)->getIndexedArray('pessoa_juridica_id','{pessoa_juridica->nome}');
        return implode(', ', $values);
    }

    public function set_pessoa_representantes_legais_representante_to_string($pessoa_representantes_legais_representante_to_string)
    {
        if(is_array($pessoa_representantes_legais_representante_to_string))
        {
            $values = Pessoa::where('id', 'in', $pessoa_representantes_legais_representante_to_string)->getIndexedArray('nome', 'nome');
            $this->pessoa_representantes_legais_representante_to_string = implode(', ', $values);
        }
        else
        {
            $this->pessoa_representantes_legais_representante_to_string = $pessoa_representantes_legais_representante_to_string;
        }

        $this->vdata['pessoa_representantes_legais_representante_to_string'] = $this->pessoa_representantes_legais_representante_to_string;
    }

    public function get_pessoa_representantes_legais_representante_to_string()
    {
        if(!empty($this->pessoa_representantes_legais_representante_to_string))
        {
            return $this->pessoa_representantes_legais_representante_to_string;
        }
    
        $values = PessoaRepresentantesLegais::where('representante_id', '=', $this->id)->getIndexedArray('representante_id','{representante->nome}');
        return implode(', ', $values);
    }

    public function set_processo_tipo_processo_to_string($processo_tipo_processo_to_string)
    {
        if(is_array($processo_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $processo_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_tipo_processo_to_string = $processo_tipo_processo_to_string;
        }

        $this->vdata['processo_tipo_processo_to_string'] = $this->processo_tipo_processo_to_string;
    }

    public function get_processo_tipo_processo_to_string()
    {
        if(!empty($this->processo_tipo_processo_to_string))
        {
            return $this->processo_tipo_processo_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_processo_tribunal_to_string($processo_tribunal_to_string)
    {
        if(is_array($processo_tribunal_to_string))
        {
            $values = Tribunal::where('id', 'in', $processo_tribunal_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_tribunal_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_tribunal_to_string = $processo_tribunal_to_string;
        }

        $this->vdata['processo_tribunal_to_string'] = $this->processo_tribunal_to_string;
    }

    public function get_processo_tribunal_to_string()
    {
        if(!empty($this->processo_tribunal_to_string))
        {
            return $this->processo_tribunal_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('tribunal_id','{tribunal->nome}');
        return implode(', ', $values);
    }

    public function set_processo_foro_to_string($processo_foro_to_string)
    {
        if(is_array($processo_foro_to_string))
        {
            $values = Foro::where('id', 'in', $processo_foro_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_foro_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_foro_to_string = $processo_foro_to_string;
        }

        $this->vdata['processo_foro_to_string'] = $this->processo_foro_to_string;
    }

    public function get_processo_foro_to_string()
    {
        if(!empty($this->processo_foro_to_string))
        {
            return $this->processo_foro_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('foro_id','{foro->nome}');
        return implode(', ', $values);
    }

    public function set_processo_comarca_to_string($processo_comarca_to_string)
    {
        if(is_array($processo_comarca_to_string))
        {
            $values = Comarca::where('id', 'in', $processo_comarca_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_comarca_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_comarca_to_string = $processo_comarca_to_string;
        }

        $this->vdata['processo_comarca_to_string'] = $this->processo_comarca_to_string;
    }

    public function get_processo_comarca_to_string()
    {
        if(!empty($this->processo_comarca_to_string))
        {
            return $this->processo_comarca_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('comarca_id','{comarca->nome}');
        return implode(', ', $values);
    }

    public function set_processo_vara_to_string($processo_vara_to_string)
    {
        if(is_array($processo_vara_to_string))
        {
            $values = Vara::where('id', 'in', $processo_vara_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_vara_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_vara_to_string = $processo_vara_to_string;
        }

        $this->vdata['processo_vara_to_string'] = $this->processo_vara_to_string;
    }

    public function get_processo_vara_to_string()
    {
        if(!empty($this->processo_vara_to_string))
        {
            return $this->processo_vara_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('vara_id','{vara->nome}');
        return implode(', ', $values);
    }

    public function set_processo_orgao_to_string($processo_orgao_to_string)
    {
        if(is_array($processo_orgao_to_string))
        {
            $values = Orgao::where('id', 'in', $processo_orgao_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_orgao_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_orgao_to_string = $processo_orgao_to_string;
        }

        $this->vdata['processo_orgao_to_string'] = $this->processo_orgao_to_string;
    }

    public function get_processo_orgao_to_string()
    {
        if(!empty($this->processo_orgao_to_string))
        {
            return $this->processo_orgao_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('orgao_id','{orgao->nome}');
        return implode(', ', $values);
    }

    public function set_processo_area_to_string($processo_area_to_string)
    {
        if(is_array($processo_area_to_string))
        {
            $values = Area::where('id', 'in', $processo_area_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_area_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_area_to_string = $processo_area_to_string;
        }

        $this->vdata['processo_area_to_string'] = $this->processo_area_to_string;
    }

    public function get_processo_area_to_string()
    {
        if(!empty($this->processo_area_to_string))
        {
            return $this->processo_area_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
        return implode(', ', $values);
    }

    public function set_processo_assunto_to_string($processo_assunto_to_string)
    {
        if(is_array($processo_assunto_to_string))
        {
            $values = Assunto::where('id', 'in', $processo_assunto_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_assunto_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_assunto_to_string = $processo_assunto_to_string;
        }

        $this->vdata['processo_assunto_to_string'] = $this->processo_assunto_to_string;
    }

    public function get_processo_assunto_to_string()
    {
        if(!empty($this->processo_assunto_to_string))
        {
            return $this->processo_assunto_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('assunto_id','{assunto->nome}');
        return implode(', ', $values);
    }

    public function set_processo_status_processual_to_string($processo_status_processual_to_string)
    {
        if(is_array($processo_status_processual_to_string))
        {
            $values = StatusProcessual::where('id', 'in', $processo_status_processual_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_status_processual_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_status_processual_to_string = $processo_status_processual_to_string;
        }

        $this->vdata['processo_status_processual_to_string'] = $this->processo_status_processual_to_string;
    }

    public function get_processo_status_processual_to_string()
    {
        if(!empty($this->processo_status_processual_to_string))
        {
            return $this->processo_status_processual_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('status_processual_id','{status_processual->nome}');
        return implode(', ', $values);
    }

    public function set_processo_responsavel_to_string($processo_responsavel_to_string)
    {
        if(is_array($processo_responsavel_to_string))
        {
            $values = Pessoa::where('id', 'in', $processo_responsavel_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_responsavel_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_responsavel_to_string = $processo_responsavel_to_string;
        }

        $this->vdata['processo_responsavel_to_string'] = $this->processo_responsavel_to_string;
    }

    public function get_processo_responsavel_to_string()
    {
        if(!empty($this->processo_responsavel_to_string))
        {
            return $this->processo_responsavel_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('responsavel_id','{responsavel->nome}');
        return implode(', ', $values);
    }

    public function set_processo_envolvimento_to_string($processo_envolvimento_to_string)
    {
        if(is_array($processo_envolvimento_to_string))
        {
            $values = Envolvimento::where('id', 'in', $processo_envolvimento_to_string)->getIndexedArray('nome', 'nome');
            $this->processo_envolvimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_envolvimento_to_string = $processo_envolvimento_to_string;
        }

        $this->vdata['processo_envolvimento_to_string'] = $this->processo_envolvimento_to_string;
    }

    public function get_processo_envolvimento_to_string()
    {
        if(!empty($this->processo_envolvimento_to_string))
        {
            return $this->processo_envolvimento_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('envolvimento_id','{envolvimento->nome}');
        return implode(', ', $values);
    }

    public function set_processo_criacao_user_to_string($processo_criacao_user_to_string)
    {
        if(is_array($processo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $processo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->processo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_criacao_user_to_string = $processo_criacao_user_to_string;
        }

        $this->vdata['processo_criacao_user_to_string'] = $this->processo_criacao_user_to_string;
    }

    public function get_processo_criacao_user_to_string()
    {
        if(!empty($this->processo_criacao_user_to_string))
        {
            return $this->processo_criacao_user_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_processo_modificacao_user_to_string($processo_modificacao_user_to_string)
    {
        if(is_array($processo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $processo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->processo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_modificacao_user_to_string = $processo_modificacao_user_to_string;
        }

        $this->vdata['processo_modificacao_user_to_string'] = $this->processo_modificacao_user_to_string;
    }

    public function get_processo_modificacao_user_to_string()
    {
        if(!empty($this->processo_modificacao_user_to_string))
        {
            return $this->processo_modificacao_user_to_string;
        }
    
        $values = Processo::where('responsavel_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_profissional_publicacao_to_string($publicacao_profissional_publicacao_to_string)
    {
        if(is_array($publicacao_profissional_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $publicacao_profissional_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->publicacao_profissional_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_profissional_publicacao_to_string = $publicacao_profissional_publicacao_to_string;
        }

        $this->vdata['publicacao_profissional_publicacao_to_string'] = $this->publicacao_profissional_publicacao_to_string;
    }

    public function get_publicacao_profissional_publicacao_to_string()
    {
        if(!empty($this->publicacao_profissional_publicacao_to_string))
        {
            return $this->publicacao_profissional_publicacao_to_string;
        }
    
        $values = PublicacaoProfissional::where('profissional_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_publicacao_profissional_profissional_to_string($publicacao_profissional_profissional_to_string)
    {
        if(is_array($publicacao_profissional_profissional_to_string))
        {
            $values = Pessoa::where('id', 'in', $publicacao_profissional_profissional_to_string)->getIndexedArray('nome', 'nome');
            $this->publicacao_profissional_profissional_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_profissional_profissional_to_string = $publicacao_profissional_profissional_to_string;
        }

        $this->vdata['publicacao_profissional_profissional_to_string'] = $this->publicacao_profissional_profissional_to_string;
    }

    public function get_publicacao_profissional_profissional_to_string()
    {
        if(!empty($this->publicacao_profissional_profissional_to_string))
        {
            return $this->publicacao_profissional_profissional_to_string;
        }
    
        $values = PublicacaoProfissional::where('profissional_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_cliente_tarefa_to_string($tarefa_cliente_tarefa_to_string)
    {
        if(is_array($tarefa_cliente_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_cliente_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_cliente_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_cliente_tarefa_to_string = $tarefa_cliente_tarefa_to_string;
        }

        $this->vdata['tarefa_cliente_tarefa_to_string'] = $this->tarefa_cliente_tarefa_to_string;
    }

    public function get_tarefa_cliente_tarefa_to_string()
    {
        if(!empty($this->tarefa_cliente_tarefa_to_string))
        {
            return $this->tarefa_cliente_tarefa_to_string;
        }
    
        $values = TarefaCliente::where('cliente_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_cliente_cliente_to_string($tarefa_cliente_cliente_to_string)
    {
        if(is_array($tarefa_cliente_cliente_to_string))
        {
            $values = Pessoa::where('id', 'in', $tarefa_cliente_cliente_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_cliente_cliente_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_cliente_cliente_to_string = $tarefa_cliente_cliente_to_string;
        }

        $this->vdata['tarefa_cliente_cliente_to_string'] = $this->tarefa_cliente_cliente_to_string;
    }

    public function get_tarefa_cliente_cliente_to_string()
    {
        if(!empty($this->tarefa_cliente_cliente_to_string))
        {
            return $this->tarefa_cliente_cliente_to_string;
        }
    
        $values = TarefaCliente::where('cliente_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
        return implode(', ', $values);
    }

    public function set_lancamento_profissional_lancamento_to_string($lancamento_profissional_lancamento_to_string)
    {
        if(is_array($lancamento_profissional_lancamento_to_string))
        {
            $values = Lancamento::where('id', 'in', $lancamento_profissional_lancamento_to_string)->getIndexedArray('id', 'id');
            $this->lancamento_profissional_lancamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->lancamento_profissional_lancamento_to_string = $lancamento_profissional_lancamento_to_string;
        }

        $this->vdata['lancamento_profissional_lancamento_to_string'] = $this->lancamento_profissional_lancamento_to_string;
    }

    public function get_lancamento_profissional_lancamento_to_string()
    {
        if(!empty($this->lancamento_profissional_lancamento_to_string))
        {
            return $this->lancamento_profissional_lancamento_to_string;
        }
    
        $values = LancamentoProfissional::where('pessoa_id', '=', $this->id)->getIndexedArray('lancamento_id','{lancamento->id}');
        return implode(', ', $values);
    }

    public function set_lancamento_profissional_pessoa_to_string($lancamento_profissional_pessoa_to_string)
    {
        if(is_array($lancamento_profissional_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $lancamento_profissional_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->lancamento_profissional_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->lancamento_profissional_pessoa_to_string = $lancamento_profissional_pessoa_to_string;
        }

        $this->vdata['lancamento_profissional_pessoa_to_string'] = $this->lancamento_profissional_pessoa_to_string;
    }

    public function get_lancamento_profissional_pessoa_to_string()
    {
        if(!empty($this->lancamento_profissional_pessoa_to_string))
        {
            return $this->lancamento_profissional_pessoa_to_string;
        }
    
        $values = LancamentoProfissional::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_cliente_pessoa_to_string($requisicao_pagamento_cliente_pessoa_to_string)
    {
        if(is_array($requisicao_pagamento_cliente_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $requisicao_pagamento_cliente_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->requisicao_pagamento_cliente_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_cliente_pessoa_to_string = $requisicao_pagamento_cliente_pessoa_to_string;
        }

        $this->vdata['requisicao_pagamento_cliente_pessoa_to_string'] = $this->requisicao_pagamento_cliente_pessoa_to_string;
    }

    public function get_requisicao_pagamento_cliente_pessoa_to_string()
    {
        if(!empty($this->requisicao_pagamento_cliente_pessoa_to_string))
        {
            return $this->requisicao_pagamento_cliente_pessoa_to_string;
        }
    
        $values = RequisicaoPagamentoCliente::where('entidade_devedora_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_cliente_entidade_devedora_to_string($requisicao_pagamento_cliente_entidade_devedora_to_string)
    {
        if(is_array($requisicao_pagamento_cliente_entidade_devedora_to_string))
        {
            $values = Pessoa::where('id', 'in', $requisicao_pagamento_cliente_entidade_devedora_to_string)->getIndexedArray('nome', 'nome');
            $this->requisicao_pagamento_cliente_entidade_devedora_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_cliente_entidade_devedora_to_string = $requisicao_pagamento_cliente_entidade_devedora_to_string;
        }

        $this->vdata['requisicao_pagamento_cliente_entidade_devedora_to_string'] = $this->requisicao_pagamento_cliente_entidade_devedora_to_string;
    }

    public function get_requisicao_pagamento_cliente_entidade_devedora_to_string()
    {
        if(!empty($this->requisicao_pagamento_cliente_entidade_devedora_to_string))
        {
            return $this->requisicao_pagamento_cliente_entidade_devedora_to_string;
        }
    
        $values = RequisicaoPagamentoCliente::where('entidade_devedora_id', '=', $this->id)->getIndexedArray('entidade_devedora_id','{entidade_devedora->nome}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_cliente_requisicao_pagamento_to_string($requisicao_pagamento_cliente_requisicao_pagamento_to_string)
    {
        if(is_array($requisicao_pagamento_cliente_requisicao_pagamento_to_string))
        {
            $values = RequisicaoPagamento::where('id', 'in', $requisicao_pagamento_cliente_requisicao_pagamento_to_string)->getIndexedArray('id', 'id');
            $this->requisicao_pagamento_cliente_requisicao_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_cliente_requisicao_pagamento_to_string = $requisicao_pagamento_cliente_requisicao_pagamento_to_string;
        }

        $this->vdata['requisicao_pagamento_cliente_requisicao_pagamento_to_string'] = $this->requisicao_pagamento_cliente_requisicao_pagamento_to_string;
    }

    public function get_requisicao_pagamento_cliente_requisicao_pagamento_to_string()
    {
        if(!empty($this->requisicao_pagamento_cliente_requisicao_pagamento_to_string))
        {
            return $this->requisicao_pagamento_cliente_requisicao_pagamento_to_string;
        }
    
        $values = RequisicaoPagamentoCliente::where('entidade_devedora_id', '=', $this->id)->getIndexedArray('requisicao_pagamento_id','{requisicao_pagamento->id}');
        return implode(', ', $values);
    }

    public function set_requisicao_pagamento_cliente_status_requisicao_pagamento_to_string($requisicao_pagamento_cliente_status_requisicao_pagamento_to_string)
    {
        if(is_array($requisicao_pagamento_cliente_status_requisicao_pagamento_to_string))
        {
            $values = StatusRequisicaoPagamento::where('id', 'in', $requisicao_pagamento_cliente_status_requisicao_pagamento_to_string)->getIndexedArray('id', 'id');
            $this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string = $requisicao_pagamento_cliente_status_requisicao_pagamento_to_string;
        }

        $this->vdata['requisicao_pagamento_cliente_status_requisicao_pagamento_to_string'] = $this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string;
    }

    public function get_requisicao_pagamento_cliente_status_requisicao_pagamento_to_string()
    {
        if(!empty($this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string))
        {
            return $this->requisicao_pagamento_cliente_status_requisicao_pagamento_to_string;
        }
    
        $values = RequisicaoPagamentoCliente::where('entidade_devedora_id', '=', $this->id)->getIndexedArray('status_requisicao_pagamento_id','{status_requisicao_pagamento->id}');
        return implode(', ', $values);
    }

    public function set_conta_profissional_conta_to_string($conta_profissional_conta_to_string)
    {
        if(is_array($conta_profissional_conta_to_string))
        {
            $values = Conta::where('id', 'in', $conta_profissional_conta_to_string)->getIndexedArray('descricao', 'descricao');
            $this->conta_profissional_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_profissional_conta_to_string = $conta_profissional_conta_to_string;
        }

        $this->vdata['conta_profissional_conta_to_string'] = $this->conta_profissional_conta_to_string;
    }

    public function get_conta_profissional_conta_to_string()
    {
        if(!empty($this->conta_profissional_conta_to_string))
        {
            return $this->conta_profissional_conta_to_string;
        }
    
        $values = ContaProfissional::where('pessoa_id', '=', $this->id)->getIndexedArray('conta_id','{conta->descricao}');
        return implode(', ', $values);
    }

    public function set_conta_profissional_pessoa_to_string($conta_profissional_pessoa_to_string)
    {
        if(is_array($conta_profissional_pessoa_to_string))
        {
            $values = Pessoa::where('id', 'in', $conta_profissional_pessoa_to_string)->getIndexedArray('nome', 'nome');
            $this->conta_profissional_pessoa_to_string = implode(', ', $values);
        }
        else
        {
            $this->conta_profissional_pessoa_to_string = $conta_profissional_pessoa_to_string;
        }

        $this->vdata['conta_profissional_pessoa_to_string'] = $this->conta_profissional_pessoa_to_string;
    }

    public function get_conta_profissional_pessoa_to_string()
    {
        if(!empty($this->conta_profissional_pessoa_to_string))
        {
            return $this->conta_profissional_pessoa_to_string;
        }
    
        $values = ContaProfissional::where('pessoa_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
        return implode(', ', $values);
    }

    public function get_nome_formatado()
    {
        $tratamento = $this->tratamento ? "{$this->tratamento} " : '';
        $nome = $this->nome_civel ? $this->nome_civel : $this->nome;
    
        return $tratamento . $nome;
    }

    public function get_cor()
    {
        $pessoasGrupos = $this->getPessoaGrupos();
    
        $cor = '#ffffff';
    
        if ($pessoasGrupos)
        {
            foreach($pessoasGrupos as $pessoaGrupo)
            {
                if($pessoaGrupo->grupo_id === Grupo::PROFISSIONAL)
                {
                    $cor = $pessoaGrupo->cor;
                }
            }
        }
    
        return $cor;
    }

    public function get_foto_icone()
    {
        if (empty($this->foto))
            return "app/images/favicon.png";
    
        return $this->foto;
    }

    public function get_cpf_cnpj_formatado(){
        if(strlen($this->cpf_cnpj)==11){
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $this->cpf_cnpj);
        } 
  
        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $this->cpf_cnpj);
    }

    public function get_rg_ie_formatado(){
        if($this->tipo_pessoa_id == TipoPessoa::FISICA){
            $rg = preg_replace("/\D/", "", $this->rg_ie);
            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{1})/", "\$1.\$2.\$3-\$4", $rg);
        }
        return $this->rg_ie;
    }

    public function get_dt_nasci_formatada(){
        $data = new DateTime($this->dt_nascimento_abertura);
        return $data->format('d/m/Y');
    }
                                
}

