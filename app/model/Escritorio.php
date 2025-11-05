<?php

class Escritorio extends TRecord
{
    const TABLENAME  = 'escritorio';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUnit $system_unit;
    private SystemUsers $modificacao_user;
    private Cidade $cidade;
    private SystemUsers $criacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('system_unit_id');
        parent::addAttribute('cidade_id');
        parent::addAttribute('nome');
        parent::addAttribute('cnpj');
        parent::addAttribute('telefone');
        parent::addAttribute('email');
        parent::addAttribute('endereco');
        parent::addAttribute('bairro');
        parent::addAttribute('cep');
        parent::addAttribute('numero');
        parent::addAttribute('complemento');
        parent::addAttribute('logo_documento');
        parent::addAttribute('url_sistema');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    
    }

    /**
     * Method set_system_unit
     * Sample of usage: $var->system_unit = $object;
     * @param $object Instance of SystemUnit
     */
    public function set_system_unit(SystemUnit $object)
    {
        $this->system_unit = $object;
        $this->system_unit_id = $object->id;
    }

    /**
     * Method get_system_unit
     * Sample of usage: $var->system_unit->attribute;
     * @returns SystemUnit instance
     */
    public function get_system_unit()
    {
    
        // loads the associated object
        if (empty($this->system_unit))
            $this->system_unit = new SystemUnit($this->system_unit_id);
    
        // returns the associated object
        return $this->system_unit;
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
     * Method set_cidade
     * Sample of usage: $var->cidade = $object;
     * @param $object Instance of Cidade
     */
    public function set_cidade(Cidade $object)
    {
        $this->cidade = $object;
        $this->cidade_id = $object->id;
    }

    /**
     * Method get_cidade
     * Sample of usage: $var->cidade->attribute;
     * @returns Cidade instance
     */
    public function get_cidade()
    {
    
        // loads the associated object
        if (empty($this->cidade))
            $this->cidade = new Cidade($this->cidade_id);
    
        // returns the associated object
        return $this->cidade;
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
     * Method getAgendas
     */
    public function getAgendas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('escritorio_id', '=', $this->id));
        return Agenda::getObjects( $criteria );
    }
    /**
     * Method getContas
     */
    public function getContas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('escritorio_id', '=', $this->id));
        return Conta::getObjects( $criteria );
    }
    /**
     * Method getContratos
     */
    public function getContratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('escritorio_id', '=', $this->id));
        return Contrato::getObjects( $criteria );
    }
    /**
     * Method getEscritorioParceiros
     */
    public function getEscritorioParceiros()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('escritorio_id', '=', $this->id));
        return EscritorioParceiro::getObjects( $criteria );
    }
    /**
     * Method getExtratos
     */
    public function getExtratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('escritorio_id', '=', $this->id));
        return Extrato::getObjects( $criteria );
    }
    /**
     * Method getTemplateEscritorios
     */
    public function getTemplateEscritorios()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('escritorio_id', '=', $this->id));
        return TemplateEscritorio::getObjects( $criteria );
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
    
        $values = Agenda::where('escritorio_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
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
    
        $values = Agenda::where('escritorio_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
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
    
        $values = Agenda::where('escritorio_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
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
    
        $values = Agenda::where('escritorio_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Agenda::where('escritorio_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('tipo_documento_financeiro_id','{tipo_documento_financeiro->nome}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Conta::where('escritorio_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_escritorio_to_string($contrato_escritorio_to_string)
    {
        if(is_array($contrato_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $contrato_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_escritorio_to_string = $contrato_escritorio_to_string;
        }

        $this->vdata['contrato_escritorio_to_string'] = $this->contrato_escritorio_to_string;
    }

    public function get_contrato_escritorio_to_string()
    {
        if(!empty($this->contrato_escritorio_to_string))
        {
            return $this->contrato_escritorio_to_string;
        }
    
        $values = Contrato::where('escritorio_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_tipo_processo_to_string($contrato_tipo_processo_to_string)
    {
        if(is_array($contrato_tipo_processo_to_string))
        {
            $values = TipoProcesso::where('id', 'in', $contrato_tipo_processo_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_tipo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_tipo_processo_to_string = $contrato_tipo_processo_to_string;
        }

        $this->vdata['contrato_tipo_processo_to_string'] = $this->contrato_tipo_processo_to_string;
    }

    public function get_contrato_tipo_processo_to_string()
    {
        if(!empty($this->contrato_tipo_processo_to_string))
        {
            return $this->contrato_tipo_processo_to_string;
        }
    
        $values = Contrato::where('escritorio_id', '=', $this->id)->getIndexedArray('tipo_processo_id','{tipo_processo->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_area_to_string($contrato_area_to_string)
    {
        if(is_array($contrato_area_to_string))
        {
            $values = Area::where('id', 'in', $contrato_area_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_area_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_area_to_string = $contrato_area_to_string;
        }

        $this->vdata['contrato_area_to_string'] = $this->contrato_area_to_string;
    }

    public function get_contrato_area_to_string()
    {
        if(!empty($this->contrato_area_to_string))
        {
            return $this->contrato_area_to_string;
        }
    
        $values = Contrato::where('escritorio_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_contrato_status_to_string($contrato_contrato_status_to_string)
    {
        if(is_array($contrato_contrato_status_to_string))
        {
            $values = ContratoStatus::where('id', 'in', $contrato_contrato_status_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_contrato_status_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_contrato_status_to_string = $contrato_contrato_status_to_string;
        }

        $this->vdata['contrato_contrato_status_to_string'] = $this->contrato_contrato_status_to_string;
    }

    public function get_contrato_contrato_status_to_string()
    {
        if(!empty($this->contrato_contrato_status_to_string))
        {
            return $this->contrato_contrato_status_to_string;
        }
    
        $values = Contrato::where('escritorio_id', '=', $this->id)->getIndexedArray('contrato_status_id','{contrato_status->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_assunto_to_string($contrato_assunto_to_string)
    {
        if(is_array($contrato_assunto_to_string))
        {
            $values = Assunto::where('id', 'in', $contrato_assunto_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_assunto_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_assunto_to_string = $contrato_assunto_to_string;
        }

        $this->vdata['contrato_assunto_to_string'] = $this->contrato_assunto_to_string;
    }

    public function get_contrato_assunto_to_string()
    {
        if(!empty($this->contrato_assunto_to_string))
        {
            return $this->contrato_assunto_to_string;
        }
    
        $values = Contrato::where('escritorio_id', '=', $this->id)->getIndexedArray('assunto_id','{assunto->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_envolvimento_to_string($contrato_envolvimento_to_string)
    {
        if(is_array($contrato_envolvimento_to_string))
        {
            $values = Envolvimento::where('id', 'in', $contrato_envolvimento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_envolvimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_envolvimento_to_string = $contrato_envolvimento_to_string;
        }

        $this->vdata['contrato_envolvimento_to_string'] = $this->contrato_envolvimento_to_string;
    }

    public function get_contrato_envolvimento_to_string()
    {
        if(!empty($this->contrato_envolvimento_to_string))
        {
            return $this->contrato_envolvimento_to_string;
        }
    
        $values = Contrato::where('escritorio_id', '=', $this->id)->getIndexedArray('envolvimento_id','{envolvimento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_criacao_user_to_string($contrato_criacao_user_to_string)
    {
        if(is_array($contrato_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_criacao_user_to_string = $contrato_criacao_user_to_string;
        }

        $this->vdata['contrato_criacao_user_to_string'] = $this->contrato_criacao_user_to_string;
    }

    public function get_contrato_criacao_user_to_string()
    {
        if(!empty($this->contrato_criacao_user_to_string))
        {
            return $this->contrato_criacao_user_to_string;
        }
    
        $values = Contrato::where('escritorio_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_modificacao_user_to_string($contrato_modificacao_user_to_string)
    {
        if(is_array($contrato_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_modificacao_user_to_string = $contrato_modificacao_user_to_string;
        }

        $this->vdata['contrato_modificacao_user_to_string'] = $this->contrato_modificacao_user_to_string;
    }

    public function get_contrato_modificacao_user_to_string()
    {
        if(!empty($this->contrato_modificacao_user_to_string))
        {
            return $this->contrato_modificacao_user_to_string;
        }
    
        $values = Contrato::where('escritorio_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_escritorio_parceiro_parceiro_to_string($escritorio_parceiro_parceiro_to_string)
    {
        if(is_array($escritorio_parceiro_parceiro_to_string))
        {
            $values = Parceiro::where('id', 'in', $escritorio_parceiro_parceiro_to_string)->getIndexedArray('nome', 'nome');
            $this->escritorio_parceiro_parceiro_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_parceiro_parceiro_to_string = $escritorio_parceiro_parceiro_to_string;
        }

        $this->vdata['escritorio_parceiro_parceiro_to_string'] = $this->escritorio_parceiro_parceiro_to_string;
    }

    public function get_escritorio_parceiro_parceiro_to_string()
    {
        if(!empty($this->escritorio_parceiro_parceiro_to_string))
        {
            return $this->escritorio_parceiro_parceiro_to_string;
        }
    
        $values = EscritorioParceiro::where('escritorio_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
        return implode(', ', $values);
    }

    public function set_escritorio_parceiro_escritorio_to_string($escritorio_parceiro_escritorio_to_string)
    {
        if(is_array($escritorio_parceiro_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $escritorio_parceiro_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->escritorio_parceiro_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->escritorio_parceiro_escritorio_to_string = $escritorio_parceiro_escritorio_to_string;
        }

        $this->vdata['escritorio_parceiro_escritorio_to_string'] = $this->escritorio_parceiro_escritorio_to_string;
    }

    public function get_escritorio_parceiro_escritorio_to_string()
    {
        if(!empty($this->escritorio_parceiro_escritorio_to_string))
        {
            return $this->escritorio_parceiro_escritorio_to_string;
        }
    
        $values = EscritorioParceiro::where('escritorio_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_escritorio_to_string($extrato_escritorio_to_string)
    {
        if(is_array($extrato_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $extrato_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_escritorio_to_string = $extrato_escritorio_to_string;
        }

        $this->vdata['extrato_escritorio_to_string'] = $this->extrato_escritorio_to_string;
    }

    public function get_extrato_escritorio_to_string()
    {
        if(!empty($this->extrato_escritorio_to_string))
        {
            return $this->extrato_escritorio_to_string;
        }
    
        $values = Extrato::where('escritorio_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_conta_caixa_to_string($extrato_conta_caixa_to_string)
    {
        if(is_array($extrato_conta_caixa_to_string))
        {
            $values = ContaCaixa::where('id', 'in', $extrato_conta_caixa_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_conta_caixa_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_conta_caixa_to_string = $extrato_conta_caixa_to_string;
        }

        $this->vdata['extrato_conta_caixa_to_string'] = $this->extrato_conta_caixa_to_string;
    }

    public function get_extrato_conta_caixa_to_string()
    {
        if(!empty($this->extrato_conta_caixa_to_string))
        {
            return $this->extrato_conta_caixa_to_string;
        }
    
        $values = Extrato::where('escritorio_id', '=', $this->id)->getIndexedArray('conta_caixa_id','{conta_caixa->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_lancamento_to_string($extrato_lancamento_to_string)
    {
        if(is_array($extrato_lancamento_to_string))
        {
            $values = Lancamento::where('id', 'in', $extrato_lancamento_to_string)->getIndexedArray('id', 'id');
            $this->extrato_lancamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_lancamento_to_string = $extrato_lancamento_to_string;
        }

        $this->vdata['extrato_lancamento_to_string'] = $this->extrato_lancamento_to_string;
    }

    public function get_extrato_lancamento_to_string()
    {
        if(!empty($this->extrato_lancamento_to_string))
        {
            return $this->extrato_lancamento_to_string;
        }
    
        $values = Extrato::where('escritorio_id', '=', $this->id)->getIndexedArray('lancamento_id','{lancamento->id}');
        return implode(', ', $values);
    }

    public function set_extrato_categoria_conta_to_string($extrato_categoria_conta_to_string)
    {
        if(is_array($extrato_categoria_conta_to_string))
        {
            $values = CategoriaConta::where('id', 'in', $extrato_categoria_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_categoria_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_categoria_conta_to_string = $extrato_categoria_conta_to_string;
        }

        $this->vdata['extrato_categoria_conta_to_string'] = $this->extrato_categoria_conta_to_string;
    }

    public function get_extrato_categoria_conta_to_string()
    {
        if(!empty($this->extrato_categoria_conta_to_string))
        {
            return $this->extrato_categoria_conta_to_string;
        }
    
        $values = Extrato::where('escritorio_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_tipo_extrato_to_string($extrato_tipo_extrato_to_string)
    {
        if(is_array($extrato_tipo_extrato_to_string))
        {
            $values = TipoExtrato::where('id', 'in', $extrato_tipo_extrato_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_tipo_extrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_tipo_extrato_to_string = $extrato_tipo_extrato_to_string;
        }

        $this->vdata['extrato_tipo_extrato_to_string'] = $this->extrato_tipo_extrato_to_string;
    }

    public function get_extrato_tipo_extrato_to_string()
    {
        if(!empty($this->extrato_tipo_extrato_to_string))
        {
            return $this->extrato_tipo_extrato_to_string;
        }
    
        $values = Extrato::where('escritorio_id', '=', $this->id)->getIndexedArray('tipo_extrato_id','{tipo_extrato->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_transferencia_conta_caixa_to_string($extrato_transferencia_conta_caixa_to_string)
    {
        if(is_array($extrato_transferencia_conta_caixa_to_string))
        {
            $values = ContaCaixa::where('id', 'in', $extrato_transferencia_conta_caixa_to_string)->getIndexedArray('nome', 'nome');
            $this->extrato_transferencia_conta_caixa_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_transferencia_conta_caixa_to_string = $extrato_transferencia_conta_caixa_to_string;
        }

        $this->vdata['extrato_transferencia_conta_caixa_to_string'] = $this->extrato_transferencia_conta_caixa_to_string;
    }

    public function get_extrato_transferencia_conta_caixa_to_string()
    {
        if(!empty($this->extrato_transferencia_conta_caixa_to_string))
        {
            return $this->extrato_transferencia_conta_caixa_to_string;
        }
    
        $values = Extrato::where('escritorio_id', '=', $this->id)->getIndexedArray('transferencia_conta_caixa_id','{transferencia_conta_caixa->nome}');
        return implode(', ', $values);
    }

    public function set_extrato_criacao_user_to_string($extrato_criacao_user_to_string)
    {
        if(is_array($extrato_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $extrato_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->extrato_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_criacao_user_to_string = $extrato_criacao_user_to_string;
        }

        $this->vdata['extrato_criacao_user_to_string'] = $this->extrato_criacao_user_to_string;
    }

    public function get_extrato_criacao_user_to_string()
    {
        if(!empty($this->extrato_criacao_user_to_string))
        {
            return $this->extrato_criacao_user_to_string;
        }
    
        $values = Extrato::where('escritorio_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_extrato_modificacao_user_to_string($extrato_modificacao_user_to_string)
    {
        if(is_array($extrato_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $extrato_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->extrato_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->extrato_modificacao_user_to_string = $extrato_modificacao_user_to_string;
        }

        $this->vdata['extrato_modificacao_user_to_string'] = $this->extrato_modificacao_user_to_string;
    }

    public function get_extrato_modificacao_user_to_string()
    {
        if(!empty($this->extrato_modificacao_user_to_string))
        {
            return $this->extrato_modificacao_user_to_string;
        }
    
        $values = Extrato::where('escritorio_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_template_escritorio_escritorio_to_string($template_escritorio_escritorio_to_string)
    {
        if(is_array($template_escritorio_escritorio_to_string))
        {
            $values = Escritorio::where('id', 'in', $template_escritorio_escritorio_to_string)->getIndexedArray('nome', 'nome');
            $this->template_escritorio_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->template_escritorio_escritorio_to_string = $template_escritorio_escritorio_to_string;
        }

        $this->vdata['template_escritorio_escritorio_to_string'] = $this->template_escritorio_escritorio_to_string;
    }

    public function get_template_escritorio_escritorio_to_string()
    {
        if(!empty($this->template_escritorio_escritorio_to_string))
        {
            return $this->template_escritorio_escritorio_to_string;
        }
    
        $values = TemplateEscritorio::where('escritorio_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
        return implode(', ', $values);
    }

    public function set_template_escritorio_criacao_user_to_string($template_escritorio_criacao_user_to_string)
    {
        if(is_array($template_escritorio_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $template_escritorio_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->template_escritorio_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->template_escritorio_criacao_user_to_string = $template_escritorio_criacao_user_to_string;
        }

        $this->vdata['template_escritorio_criacao_user_to_string'] = $this->template_escritorio_criacao_user_to_string;
    }

    public function get_template_escritorio_criacao_user_to_string()
    {
        if(!empty($this->template_escritorio_criacao_user_to_string))
        {
            return $this->template_escritorio_criacao_user_to_string;
        }
    
        $values = TemplateEscritorio::where('escritorio_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_template_escritorio_modificacao_user_to_string($template_escritorio_modificacao_user_to_string)
    {
        if(is_array($template_escritorio_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $template_escritorio_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->template_escritorio_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->template_escritorio_modificacao_user_to_string = $template_escritorio_modificacao_user_to_string;
        }

        $this->vdata['template_escritorio_modificacao_user_to_string'] = $this->template_escritorio_modificacao_user_to_string;
    }

    public function get_template_escritorio_modificacao_user_to_string()
    {
        if(!empty($this->template_escritorio_modificacao_user_to_string))
        {
            return $this->template_escritorio_modificacao_user_to_string;
        }
    
        $values = TemplateEscritorio::where('escritorio_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public static function findByUnitId($unitId)
    {
        return self::where('system_unit_id', '=', $unitId)->first();
    }

    public function get_endereco_formatado()
    {
        $endereco = $this->endereco;
    
        if ($this->complemento)
        {
            $endereco .= " {$this->complemento}";
        }
    
        $endereco .= ', ' . $this->numero .  ' - ' . $this->bairro . ' ' . $this->cep;
    
        $endereco .= " {$this->get_cidade()->nome} ({$this->get_cidade()->get_estado()->sigla})";
    
        return $endereco;
    }
        
}

