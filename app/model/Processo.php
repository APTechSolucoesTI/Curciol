<?php

class Processo extends TRecord
{
    const TABLENAME  = 'processo';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private Envolvimento $envolvimento;
    private TipoProcesso $tipo_processo;
    private Tribunal $tribunal;
    private Foro $foro;
    private Comarca $comarca;
    private Assunto $assunto;
    private Area $area;
    private Pessoa $responsavel;
    private StatusProcessual $status_processual;
    private Vara $vara;
    private Orgao $orgao;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tipo_processo_id');
        parent::addAttribute('numero_cnj_numero');
        parent::addAttribute('numero_outro');
        parent::addAttribute('tribunal_id');
        parent::addAttribute('foro_id');
        parent::addAttribute('comarca_id');
        parent::addAttribute('vara_id');
        parent::addAttribute('orgao_id');
        parent::addAttribute('data_distribuicao_protocolo');
        parent::addAttribute('valor_causa');
        parent::addAttribute('area_id');
        parent::addAttribute('assunto_id');
        parent::addAttribute('gratuidade_processual');
        parent::addAttribute('status_processual_id');
        parent::addAttribute('responsavel_id');
        parent::addAttribute('envolvimento_id');
        parent::addAttribute('observacao');
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
     * Method set_envolvimento
     * Sample of usage: $var->envolvimento = $object;
     * @param $object Instance of Envolvimento
     */
    public function set_envolvimento(Envolvimento $object)
    {
        $this->envolvimento = $object;
        $this->envolvimento_id = $object->id;
    }

    /**
     * Method get_envolvimento
     * Sample of usage: $var->envolvimento->attribute;
     * @returns Envolvimento instance
     */
    public function get_envolvimento()
    {
    
        // loads the associated object
        if (empty($this->envolvimento))
            $this->envolvimento = new Envolvimento($this->envolvimento_id);
    
        // returns the associated object
        return $this->envolvimento;
    }
    /**
     * Method set_tipo_processo
     * Sample of usage: $var->tipo_processo = $object;
     * @param $object Instance of TipoProcesso
     */
    public function set_tipo_processo(TipoProcesso $object)
    {
        $this->tipo_processo = $object;
        $this->tipo_processo_id = $object->id;
    }

    /**
     * Method get_tipo_processo
     * Sample of usage: $var->tipo_processo->attribute;
     * @returns TipoProcesso instance
     */
    public function get_tipo_processo()
    {
    
        // loads the associated object
        if (empty($this->tipo_processo))
            $this->tipo_processo = new TipoProcesso($this->tipo_processo_id);
    
        // returns the associated object
        return $this->tipo_processo;
    }
    /**
     * Method set_tribunal
     * Sample of usage: $var->tribunal = $object;
     * @param $object Instance of Tribunal
     */
    public function set_tribunal(Tribunal $object)
    {
        $this->tribunal = $object;
        $this->tribunal_id = $object->id;
    }

    /**
     * Method get_tribunal
     * Sample of usage: $var->tribunal->attribute;
     * @returns Tribunal instance
     */
    public function get_tribunal()
    {
    
        // loads the associated object
        if (empty($this->tribunal))
            $this->tribunal = new Tribunal($this->tribunal_id);
    
        // returns the associated object
        return $this->tribunal;
    }
    /**
     * Method set_foro
     * Sample of usage: $var->foro = $object;
     * @param $object Instance of Foro
     */
    public function set_foro(Foro $object)
    {
        $this->foro = $object;
        $this->foro_id = $object->id;
    }

    /**
     * Method get_foro
     * Sample of usage: $var->foro->attribute;
     * @returns Foro instance
     */
    public function get_foro()
    {
    
        // loads the associated object
        if (empty($this->foro))
            $this->foro = new Foro($this->foro_id);
    
        // returns the associated object
        return $this->foro;
    }
    /**
     * Method set_comarca
     * Sample of usage: $var->comarca = $object;
     * @param $object Instance of Comarca
     */
    public function set_comarca(Comarca $object)
    {
        $this->comarca = $object;
        $this->comarca_id = $object->id;
    }

    /**
     * Method get_comarca
     * Sample of usage: $var->comarca->attribute;
     * @returns Comarca instance
     */
    public function get_comarca()
    {
    
        // loads the associated object
        if (empty($this->comarca))
            $this->comarca = new Comarca($this->comarca_id);
    
        // returns the associated object
        return $this->comarca;
    }
    /**
     * Method set_assunto
     * Sample of usage: $var->assunto = $object;
     * @param $object Instance of Assunto
     */
    public function set_assunto(Assunto $object)
    {
        $this->assunto = $object;
        $this->assunto_id = $object->id;
    }

    /**
     * Method get_assunto
     * Sample of usage: $var->assunto->attribute;
     * @returns Assunto instance
     */
    public function get_assunto()
    {
    
        // loads the associated object
        if (empty($this->assunto))
            $this->assunto = new Assunto($this->assunto_id);
    
        // returns the associated object
        return $this->assunto;
    }
    /**
     * Method set_area
     * Sample of usage: $var->area = $object;
     * @param $object Instance of Area
     */
    public function set_area(Area $object)
    {
        $this->area = $object;
        $this->area_id = $object->id;
    }

    /**
     * Method get_area
     * Sample of usage: $var->area->attribute;
     * @returns Area instance
     */
    public function get_area()
    {
    
        // loads the associated object
        if (empty($this->area))
            $this->area = new Area($this->area_id);
    
        // returns the associated object
        return $this->area;
    }
    /**
     * Method set_pessoa
     * Sample of usage: $var->pessoa = $object;
     * @param $object Instance of Pessoa
     */
    public function set_responsavel(Pessoa $object)
    {
        $this->responsavel = $object;
        $this->responsavel_id = $object->id;
    }

    /**
     * Method get_responsavel
     * Sample of usage: $var->responsavel->attribute;
     * @returns Pessoa instance
     */
    public function get_responsavel()
    {
    
        // loads the associated object
        if (empty($this->responsavel))
            $this->responsavel = new Pessoa($this->responsavel_id);
    
        // returns the associated object
        return $this->responsavel;
    }
    /**
     * Method set_status_processual
     * Sample of usage: $var->status_processual = $object;
     * @param $object Instance of StatusProcessual
     */
    public function set_status_processual(StatusProcessual $object)
    {
        $this->status_processual = $object;
        $this->status_processual_id = $object->id;
    }

    /**
     * Method get_status_processual
     * Sample of usage: $var->status_processual->attribute;
     * @returns StatusProcessual instance
     */
    public function get_status_processual()
    {
    
        // loads the associated object
        if (empty($this->status_processual))
            $this->status_processual = new StatusProcessual($this->status_processual_id);
    
        // returns the associated object
        return $this->status_processual;
    }
    /**
     * Method set_vara
     * Sample of usage: $var->vara = $object;
     * @param $object Instance of Vara
     */
    public function set_vara(Vara $object)
    {
        $this->vara = $object;
        $this->vara_id = $object->id;
    }

    /**
     * Method get_vara
     * Sample of usage: $var->vara->attribute;
     * @returns Vara instance
     */
    public function get_vara()
    {
    
        // loads the associated object
        if (empty($this->vara))
            $this->vara = new Vara($this->vara_id);
    
        // returns the associated object
        return $this->vara;
    }
    /**
     * Method set_orgao
     * Sample of usage: $var->orgao = $object;
     * @param $object Instance of Orgao
     */
    public function set_orgao(Orgao $object)
    {
        $this->orgao = $object;
        $this->orgao_id = $object->id;
    }

    /**
     * Method get_orgao
     * Sample of usage: $var->orgao->attribute;
     * @returns Orgao instance
     */
    public function get_orgao()
    {
    
        // loads the associated object
        if (empty($this->orgao))
            $this->orgao = new Orgao($this->orgao_id);
    
        // returns the associated object
        return $this->orgao;
    }

    /**
     * Method getAndamentos
     */
    public function getAndamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('processo_id', '=', $this->id));
        return Andamento::getObjects( $criteria );
    }
    /**
     * Method getContas
     */
    public function getContas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('processo_id', '=', $this->id));
        return Conta::getObjects( $criteria );
    }
    /**
     * Method getContrapartes
     */
    public function getContrapartes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('processo_id', '=', $this->id));
        return Contraparte::getObjects( $criteria );
    }
    /**
     * Method getContratoProcessos
     */
    public function getContratoProcessos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('processo_id', '=', $this->id));
        return ContratoProcesso::getObjects( $criteria );
    }
    /**
     * Method getProcessoVinculos
     */
    public function getProcessoVinculosByProcessoPrincipals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('processo_principal_id', '=', $this->id));
        return ProcessoVinculo::getObjects( $criteria );
    }
    /**
     * Method getProcessoVinculos
     */
    public function getProcessoVinculosByProcessoIncidentes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('processo_incidente_id', '=', $this->id));
        return ProcessoVinculo::getObjects( $criteria );
    }
    /**
     * Method getPublicacaos
     */
    public function getPublicacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('processo_id', '=', $this->id));
        return Publicacao::getObjects( $criteria );
    }
    /**
     * Method getPublicacaoMovimentacaos
     */
    public function getPublicacaoMovimentacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('processo_id', '=', $this->id));
        return PublicacaoMovimentacao::getObjects( $criteria );
    }
    /**
     * Method getTarefas
     */
    public function getTarefas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('processo_id', '=', $this->id));
        return Tarefa::getObjects( $criteria );
    }

    public function set_andamento_processo_to_string($andamento_processo_to_string)
    {
        if(is_array($andamento_processo_to_string))
        {
            $values = Processo::where('id', 'in', $andamento_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->andamento_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_processo_to_string = $andamento_processo_to_string;
        }

        $this->vdata['andamento_processo_to_string'] = $this->andamento_processo_to_string;
    }

    public function get_andamento_processo_to_string()
    {
        if(!empty($this->andamento_processo_to_string))
        {
            return $this->andamento_processo_to_string;
        }
    
        $values = Andamento::where('processo_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_andamento_tipo_andamento_to_string($andamento_tipo_andamento_to_string)
    {
        if(is_array($andamento_tipo_andamento_to_string))
        {
            $values = TipoAndamento::where('id', 'in', $andamento_tipo_andamento_to_string)->getIndexedArray('nome', 'nome');
            $this->andamento_tipo_andamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_tipo_andamento_to_string = $andamento_tipo_andamento_to_string;
        }

        $this->vdata['andamento_tipo_andamento_to_string'] = $this->andamento_tipo_andamento_to_string;
    }

    public function get_andamento_tipo_andamento_to_string()
    {
        if(!empty($this->andamento_tipo_andamento_to_string))
        {
            return $this->andamento_tipo_andamento_to_string;
        }
    
        $values = Andamento::where('processo_id', '=', $this->id)->getIndexedArray('tipo_andamento_id','{tipo_andamento->nome}');
        return implode(', ', $values);
    }

    public function set_andamento_criacao_user_to_string($andamento_criacao_user_to_string)
    {
        if(is_array($andamento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $andamento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->andamento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_criacao_user_to_string = $andamento_criacao_user_to_string;
        }

        $this->vdata['andamento_criacao_user_to_string'] = $this->andamento_criacao_user_to_string;
    }

    public function get_andamento_criacao_user_to_string()
    {
        if(!empty($this->andamento_criacao_user_to_string))
        {
            return $this->andamento_criacao_user_to_string;
        }
    
        $values = Andamento::where('processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_andamento_modificacao_user_to_string($andamento_modificacao_user_to_string)
    {
        if(is_array($andamento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $andamento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->andamento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_modificacao_user_to_string = $andamento_modificacao_user_to_string;
        }

        $this->vdata['andamento_modificacao_user_to_string'] = $this->andamento_modificacao_user_to_string;
    }

    public function get_andamento_modificacao_user_to_string()
    {
        if(!empty($this->andamento_modificacao_user_to_string))
        {
            return $this->andamento_modificacao_user_to_string;
        }
    
        $values = Andamento::where('processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('tipo_documento_financeiro_id','{tipo_documento_financeiro->nome}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Conta::where('processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = Contraparte::where('processo_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = Contraparte::where('processo_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
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
    
        $values = Contraparte::where('processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Contraparte::where('processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_processo_contrato_to_string($contrato_processo_contrato_to_string)
    {
        if(is_array($contrato_processo_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_processo_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_processo_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_processo_contrato_to_string = $contrato_processo_contrato_to_string;
        }

        $this->vdata['contrato_processo_contrato_to_string'] = $this->contrato_processo_contrato_to_string;
    }

    public function get_contrato_processo_contrato_to_string()
    {
        if(!empty($this->contrato_processo_contrato_to_string))
        {
            return $this->contrato_processo_contrato_to_string;
        }
    
        $values = ContratoProcesso::where('processo_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_processo_processo_to_string($contrato_processo_processo_to_string)
    {
        if(is_array($contrato_processo_processo_to_string))
        {
            $values = Processo::where('id', 'in', $contrato_processo_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->contrato_processo_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_processo_processo_to_string = $contrato_processo_processo_to_string;
        }

        $this->vdata['contrato_processo_processo_to_string'] = $this->contrato_processo_processo_to_string;
    }

    public function get_contrato_processo_processo_to_string()
    {
        if(!empty($this->contrato_processo_processo_to_string))
        {
            return $this->contrato_processo_processo_to_string;
        }
    
        $values = ContratoProcesso::where('processo_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_contrato_processo_criacao_user_to_string($contrato_processo_criacao_user_to_string)
    {
        if(is_array($contrato_processo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_processo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_processo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_processo_criacao_user_to_string = $contrato_processo_criacao_user_to_string;
        }

        $this->vdata['contrato_processo_criacao_user_to_string'] = $this->contrato_processo_criacao_user_to_string;
    }

    public function get_contrato_processo_criacao_user_to_string()
    {
        if(!empty($this->contrato_processo_criacao_user_to_string))
        {
            return $this->contrato_processo_criacao_user_to_string;
        }
    
        $values = ContratoProcesso::where('processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_processo_modificacao_user_to_string($contrato_processo_modificacao_user_to_string)
    {
        if(is_array($contrato_processo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_processo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_processo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_processo_modificacao_user_to_string = $contrato_processo_modificacao_user_to_string;
        }

        $this->vdata['contrato_processo_modificacao_user_to_string'] = $this->contrato_processo_modificacao_user_to_string;
    }

    public function get_contrato_processo_modificacao_user_to_string()
    {
        if(!empty($this->contrato_processo_modificacao_user_to_string))
        {
            return $this->contrato_processo_modificacao_user_to_string;
        }
    
        $values = ContratoProcesso::where('processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_processo_vinculo_processo_principal_to_string($processo_vinculo_processo_principal_to_string)
    {
        if(is_array($processo_vinculo_processo_principal_to_string))
        {
            $values = Processo::where('id', 'in', $processo_vinculo_processo_principal_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->processo_vinculo_processo_principal_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_vinculo_processo_principal_to_string = $processo_vinculo_processo_principal_to_string;
        }

        $this->vdata['processo_vinculo_processo_principal_to_string'] = $this->processo_vinculo_processo_principal_to_string;
    }

    public function get_processo_vinculo_processo_principal_to_string()
    {
        if(!empty($this->processo_vinculo_processo_principal_to_string))
        {
            return $this->processo_vinculo_processo_principal_to_string;
        }
    
        $values = ProcessoVinculo::where('processo_incidente_id', '=', $this->id)->getIndexedArray('processo_principal_id','{processo_principal->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_processo_vinculo_processo_incidente_to_string($processo_vinculo_processo_incidente_to_string)
    {
        if(is_array($processo_vinculo_processo_incidente_to_string))
        {
            $values = Processo::where('id', 'in', $processo_vinculo_processo_incidente_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->processo_vinculo_processo_incidente_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_vinculo_processo_incidente_to_string = $processo_vinculo_processo_incidente_to_string;
        }

        $this->vdata['processo_vinculo_processo_incidente_to_string'] = $this->processo_vinculo_processo_incidente_to_string;
    }

    public function get_processo_vinculo_processo_incidente_to_string()
    {
        if(!empty($this->processo_vinculo_processo_incidente_to_string))
        {
            return $this->processo_vinculo_processo_incidente_to_string;
        }
    
        $values = ProcessoVinculo::where('processo_incidente_id', '=', $this->id)->getIndexedArray('processo_incidente_id','{processo_incidente->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_publicacao_processo_to_string($publicacao_processo_to_string)
    {
        if(is_array($publicacao_processo_to_string))
        {
            $values = Processo::where('id', 'in', $publicacao_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->publicacao_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_processo_to_string = $publicacao_processo_to_string;
        }

        $this->vdata['publicacao_processo_to_string'] = $this->publicacao_processo_to_string;
    }

    public function get_publicacao_processo_to_string()
    {
        if(!empty($this->publicacao_processo_to_string))
        {
            return $this->publicacao_processo_to_string;
        }
    
        $values = Publicacao::where('processo_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_publicacao_jornal_to_string($publicacao_jornal_to_string)
    {
        if(is_array($publicacao_jornal_to_string))
        {
            $values = Jornal::where('id', 'in', $publicacao_jornal_to_string)->getIndexedArray('nome', 'nome');
            $this->publicacao_jornal_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_jornal_to_string = $publicacao_jornal_to_string;
        }

        $this->vdata['publicacao_jornal_to_string'] = $this->publicacao_jornal_to_string;
    }

    public function get_publicacao_jornal_to_string()
    {
        if(!empty($this->publicacao_jornal_to_string))
        {
            return $this->publicacao_jornal_to_string;
        }
    
        $values = Publicacao::where('processo_id', '=', $this->id)->getIndexedArray('jornal_id','{jornal->nome}');
        return implode(', ', $values);
    }

    public function set_publicacao_criacao_user_to_string($publicacao_criacao_user_to_string)
    {
        if(is_array($publicacao_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_criacao_user_to_string = $publicacao_criacao_user_to_string;
        }

        $this->vdata['publicacao_criacao_user_to_string'] = $this->publicacao_criacao_user_to_string;
    }

    public function get_publicacao_criacao_user_to_string()
    {
        if(!empty($this->publicacao_criacao_user_to_string))
        {
            return $this->publicacao_criacao_user_to_string;
        }
    
        $values = Publicacao::where('processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_modificacao_user_to_string($publicacao_modificacao_user_to_string)
    {
        if(is_array($publicacao_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_modificacao_user_to_string = $publicacao_modificacao_user_to_string;
        }

        $this->vdata['publicacao_modificacao_user_to_string'] = $this->publicacao_modificacao_user_to_string;
    }

    public function get_publicacao_modificacao_user_to_string()
    {
        if(!empty($this->publicacao_modificacao_user_to_string))
        {
            return $this->publicacao_modificacao_user_to_string;
        }
    
        $values = Publicacao::where('processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_movimentacao_publicacao_to_string($publicacao_movimentacao_publicacao_to_string)
    {
        if(is_array($publicacao_movimentacao_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $publicacao_movimentacao_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->publicacao_movimentacao_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_movimentacao_publicacao_to_string = $publicacao_movimentacao_publicacao_to_string;
        }

        $this->vdata['publicacao_movimentacao_publicacao_to_string'] = $this->publicacao_movimentacao_publicacao_to_string;
    }

    public function get_publicacao_movimentacao_publicacao_to_string()
    {
        if(!empty($this->publicacao_movimentacao_publicacao_to_string))
        {
            return $this->publicacao_movimentacao_publicacao_to_string;
        }
    
        $values = PublicacaoMovimentacao::where('processo_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_publicacao_movimentacao_processo_to_string($publicacao_movimentacao_processo_to_string)
    {
        if(is_array($publicacao_movimentacao_processo_to_string))
        {
            $values = Processo::where('id', 'in', $publicacao_movimentacao_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->publicacao_movimentacao_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_movimentacao_processo_to_string = $publicacao_movimentacao_processo_to_string;
        }

        $this->vdata['publicacao_movimentacao_processo_to_string'] = $this->publicacao_movimentacao_processo_to_string;
    }

    public function get_publicacao_movimentacao_processo_to_string()
    {
        if(!empty($this->publicacao_movimentacao_processo_to_string))
        {
            return $this->publicacao_movimentacao_processo_to_string;
        }
    
        $values = PublicacaoMovimentacao::where('processo_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_publicacao_movimentacao_tarefa_to_string($publicacao_movimentacao_tarefa_to_string)
    {
        if(is_array($publicacao_movimentacao_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $publicacao_movimentacao_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->publicacao_movimentacao_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_movimentacao_tarefa_to_string = $publicacao_movimentacao_tarefa_to_string;
        }

        $this->vdata['publicacao_movimentacao_tarefa_to_string'] = $this->publicacao_movimentacao_tarefa_to_string;
    }

    public function get_publicacao_movimentacao_tarefa_to_string()
    {
        if(!empty($this->publicacao_movimentacao_tarefa_to_string))
        {
            return $this->publicacao_movimentacao_tarefa_to_string;
        }
    
        $values = PublicacaoMovimentacao::where('processo_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_publicacao_movimentacao_criacao_user_to_string($publicacao_movimentacao_criacao_user_to_string)
    {
        if(is_array($publicacao_movimentacao_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_movimentacao_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_movimentacao_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_movimentacao_criacao_user_to_string = $publicacao_movimentacao_criacao_user_to_string;
        }

        $this->vdata['publicacao_movimentacao_criacao_user_to_string'] = $this->publicacao_movimentacao_criacao_user_to_string;
    }

    public function get_publicacao_movimentacao_criacao_user_to_string()
    {
        if(!empty($this->publicacao_movimentacao_criacao_user_to_string))
        {
            return $this->publicacao_movimentacao_criacao_user_to_string;
        }
    
        $values = PublicacaoMovimentacao::where('processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_tarefa_status_to_string($tarefa_tarefa_status_to_string)
    {
        if(is_array($tarefa_tarefa_status_to_string))
        {
            $values = TarefaStatus::where('id', 'in', $tarefa_tarefa_status_to_string)->getIndexedArray('nome', 'nome');
            $this->tarefa_tarefa_status_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_tarefa_status_to_string = $tarefa_tarefa_status_to_string;
        }

        $this->vdata['tarefa_tarefa_status_to_string'] = $this->tarefa_tarefa_status_to_string;
    }

    public function get_tarefa_tarefa_status_to_string()
    {
        if(!empty($this->tarefa_tarefa_status_to_string))
        {
            return $this->tarefa_tarefa_status_to_string;
        }
    
        $values = Tarefa::where('processo_id', '=', $this->id)->getIndexedArray('tarefa_status_id','{tarefa_status->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_publicacao_to_string($tarefa_publicacao_to_string)
    {
        if(is_array($tarefa_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $tarefa_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->tarefa_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_publicacao_to_string = $tarefa_publicacao_to_string;
        }

        $this->vdata['tarefa_publicacao_to_string'] = $this->tarefa_publicacao_to_string;
    }

    public function get_tarefa_publicacao_to_string()
    {
        if(!empty($this->tarefa_publicacao_to_string))
        {
            return $this->tarefa_publicacao_to_string;
        }
    
        $values = Tarefa::where('processo_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_tarefa_processo_to_string($tarefa_processo_to_string)
    {
        if(is_array($tarefa_processo_to_string))
        {
            $values = Processo::where('id', 'in', $tarefa_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->tarefa_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_processo_to_string = $tarefa_processo_to_string;
        }

        $this->vdata['tarefa_processo_to_string'] = $this->tarefa_processo_to_string;
    }

    public function get_tarefa_processo_to_string()
    {
        if(!empty($this->tarefa_processo_to_string))
        {
            return $this->tarefa_processo_to_string;
        }
    
        $values = Tarefa::where('processo_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_tarefa_usuario_destinatario_to_string($tarefa_usuario_destinatario_to_string)
    {
        if(is_array($tarefa_usuario_destinatario_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_usuario_destinatario_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_usuario_destinatario_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_usuario_destinatario_to_string = $tarefa_usuario_destinatario_to_string;
        }

        $this->vdata['tarefa_usuario_destinatario_to_string'] = $this->tarefa_usuario_destinatario_to_string;
    }

    public function get_tarefa_usuario_destinatario_to_string()
    {
        if(!empty($this->tarefa_usuario_destinatario_to_string))
        {
            return $this->tarefa_usuario_destinatario_to_string;
        }
    
        $values = Tarefa::where('processo_id', '=', $this->id)->getIndexedArray('usuario_destinatario_id','{usuario_destinatario->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_criacao_user_to_string($tarefa_criacao_user_to_string)
    {
        if(is_array($tarefa_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_criacao_user_to_string = $tarefa_criacao_user_to_string;
        }

        $this->vdata['tarefa_criacao_user_to_string'] = $this->tarefa_criacao_user_to_string;
    }

    public function get_tarefa_criacao_user_to_string()
    {
        if(!empty($this->tarefa_criacao_user_to_string))
        {
            return $this->tarefa_criacao_user_to_string;
        }
    
        $values = Tarefa::where('processo_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_modificacao_user_to_string($tarefa_modificacao_user_to_string)
    {
        if(is_array($tarefa_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_modificacao_user_to_string = $tarefa_modificacao_user_to_string;
        }

        $this->vdata['tarefa_modificacao_user_to_string'] = $this->tarefa_modificacao_user_to_string;
    }

    public function get_tarefa_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_modificacao_user_to_string))
        {
            return $this->tarefa_modificacao_user_to_string;
        }
    
        $values = Tarefa::where('processo_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function get_contrapartes_nome(){
    
        $nomes = array();
    
        foreach($this->getContrapartes() as $contraparte){
            $pessoa = Pessoa::find($contraparte->pessoa_id);
            $nomes[] = $pessoa->nome;
        }
        $nomes = array_unique($nomes);
        sort($nomes);
        return implode(", ",$nomes);
    }
        
}

