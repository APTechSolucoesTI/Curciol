<?php

class Contrato extends TRecord
{
    const TABLENAME  = 'contrato';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private Escritorio $escritorio;
    private Envolvimento $envolvimento;
    private Area $area;
    private Assunto $assunto;
    private TipoProcesso $tipo_processo;
    private ContratoStatus $contrato_status;

    

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('escritorio_id');
        parent::addAttribute('tipo_processo_id');
        parent::addAttribute('area_id');
        parent::addAttribute('contrato_status_id');
        parent::addAttribute('assunto_id');
        parent::addAttribute('numero');
        parent::addAttribute('objeto');
        parent::addAttribute('valor');
        parent::addAttribute('quantidade_parcelas');
        parent::addAttribute('envolvimento_id');
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
     * Method set_contrato_status
     * Sample of usage: $var->contrato_status = $object;
     * @param $object Instance of ContratoStatus
     */
    public function set_contrato_status(ContratoStatus $object)
    {
        $this->contrato_status = $object;
        $this->contrato_status_id = $object->id;
    }

    /**
     * Method get_contrato_status
     * Sample of usage: $var->contrato_status->attribute;
     * @returns ContratoStatus instance
     */
    public function get_contrato_status()
    {
    
        // loads the associated object
        if (empty($this->contrato_status))
            $this->contrato_status = new ContratoStatus($this->contrato_status_id);
    
        // returns the associated object
        return $this->contrato_status;
    }

    /**
     * Method getAtendimentoContratos
     */
    public function getAtendimentoContratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_id', '=', $this->id));
        return AtendimentoContrato::getObjects( $criteria );
    }
    /**
     * Method getContas
     */
    public function getContas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_id', '=', $this->id));
        return Conta::getObjects( $criteria );
    }
    /**
     * Method getContratoDocumentos
     */
    public function getContratoDocumentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_id', '=', $this->id));
        return ContratoDocumento::getObjects( $criteria );
    }
    /**
     * Method getContratoPagamentoParcelas
     */
    public function getContratoPagamentoParcelas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_id', '=', $this->id));
        return ContratoPagamentoParcela::getObjects( $criteria );
    }
    /**
     * Method getContratoPessoas
     */
    public function getContratoPessoas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_id', '=', $this->id));
        return ContratoPessoa::getObjects( $criteria );
    }
    /**
     * Method getContratoProcessos
     */
    public function getContratoProcessos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_id', '=', $this->id));
        return ContratoProcesso::getObjects( $criteria );
    }
    /**
     * Method getContratoRepasses
     */
    public function getContratoRepasses()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_id', '=', $this->id));
        return ContratoRepasse::getObjects( $criteria );
    }
    /**
     * Method getContratoRepresentantes
     */
    public function getContratoRepresentantes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('contrato_id', '=', $this->id));
        return ContratoRepresentante::getObjects( $criteria );
    }

    public function set_atendimento_contrato_atendimento_to_string($atendimento_contrato_atendimento_to_string)
    {
        if(is_array($atendimento_contrato_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $atendimento_contrato_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->atendimento_contrato_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_contrato_atendimento_to_string = $atendimento_contrato_atendimento_to_string;
        }

        $this->vdata['atendimento_contrato_atendimento_to_string'] = $this->atendimento_contrato_atendimento_to_string;
    }

    public function get_atendimento_contrato_atendimento_to_string()
    {
        if(!empty($this->atendimento_contrato_atendimento_to_string))
        {
            return $this->atendimento_contrato_atendimento_to_string;
        }
    
        $values = AtendimentoContrato::where('contrato_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_atendimento_contrato_contrato_to_string($atendimento_contrato_contrato_to_string)
    {
        if(is_array($atendimento_contrato_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $atendimento_contrato_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->atendimento_contrato_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_contrato_contrato_to_string = $atendimento_contrato_contrato_to_string;
        }

        $this->vdata['atendimento_contrato_contrato_to_string'] = $this->atendimento_contrato_contrato_to_string;
    }

    public function get_atendimento_contrato_contrato_to_string()
    {
        if(!empty($this->atendimento_contrato_contrato_to_string))
        {
            return $this->atendimento_contrato_contrato_to_string;
        }
    
        $values = AtendimentoContrato::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('tipo_documento_financeiro_id','{tipo_documento_financeiro->nome}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Conta::where('contrato_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_documento_contrato_to_string($contrato_documento_contrato_to_string)
    {
        if(is_array($contrato_documento_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_documento_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_documento_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_documento_contrato_to_string = $contrato_documento_contrato_to_string;
        }

        $this->vdata['contrato_documento_contrato_to_string'] = $this->contrato_documento_contrato_to_string;
    }

    public function get_contrato_documento_contrato_to_string()
    {
        if(!empty($this->contrato_documento_contrato_to_string))
        {
            return $this->contrato_documento_contrato_to_string;
        }
    
        $values = ContratoDocumento::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_documento_modelo_documento_to_string($contrato_documento_modelo_documento_to_string)
    {
        if(is_array($contrato_documento_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $contrato_documento_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_documento_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_documento_modelo_documento_to_string = $contrato_documento_modelo_documento_to_string;
        }

        $this->vdata['contrato_documento_modelo_documento_to_string'] = $this->contrato_documento_modelo_documento_to_string;
    }

    public function get_contrato_documento_modelo_documento_to_string()
    {
        if(!empty($this->contrato_documento_modelo_documento_to_string))
        {
            return $this->contrato_documento_modelo_documento_to_string;
        }
    
        $values = ContratoDocumento::where('contrato_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_documento_criacao_user_to_string($contrato_documento_criacao_user_to_string)
    {
        if(is_array($contrato_documento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_documento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_documento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_documento_criacao_user_to_string = $contrato_documento_criacao_user_to_string;
        }

        $this->vdata['contrato_documento_criacao_user_to_string'] = $this->contrato_documento_criacao_user_to_string;
    }

    public function get_contrato_documento_criacao_user_to_string()
    {
        if(!empty($this->contrato_documento_criacao_user_to_string))
        {
            return $this->contrato_documento_criacao_user_to_string;
        }
    
        $values = ContratoDocumento::where('contrato_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_documento_modificacao_user_to_string($contrato_documento_modificacao_user_to_string)
    {
        if(is_array($contrato_documento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_documento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_documento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_documento_modificacao_user_to_string = $contrato_documento_modificacao_user_to_string;
        }

        $this->vdata['contrato_documento_modificacao_user_to_string'] = $this->contrato_documento_modificacao_user_to_string;
    }

    public function get_contrato_documento_modificacao_user_to_string()
    {
        if(!empty($this->contrato_documento_modificacao_user_to_string))
        {
            return $this->contrato_documento_modificacao_user_to_string;
        }
    
        $values = ContratoDocumento::where('contrato_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_to_string($contrato_pagamento_parcela_contrato_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_to_string))
        {
            $values = Contrato::where('id', 'in', $contrato_pagamento_parcela_contrato_to_string)->getIndexedArray('objeto', 'objeto');
            $this->contrato_pagamento_parcela_contrato_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_to_string = $contrato_pagamento_parcela_contrato_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_to_string'] = $this->contrato_pagamento_parcela_contrato_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_opcao_pagamento_to_string($contrato_pagamento_parcela_contrato_opcao_pagamento_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_opcao_pagamento_to_string))
        {
            $values = ContratoPagamentoOpcao::where('id', 'in', $contrato_pagamento_parcela_contrato_opcao_pagamento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string = $contrato_pagamento_parcela_contrato_opcao_pagamento_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_opcao_pagamento_to_string'] = $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_opcao_pagamento_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_opcao_pagamento_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_opcao_pagamento_id','{contrato_opcao_pagamento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_evento_to_string($contrato_pagamento_parcela_contrato_evento_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_evento_to_string))
        {
            $values = ContratoPagamentoEvento::where('id', 'in', $contrato_pagamento_parcela_contrato_evento_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_contrato_evento_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_evento_to_string = $contrato_pagamento_parcela_contrato_evento_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_evento_to_string'] = $this->contrato_pagamento_parcela_contrato_evento_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_evento_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_evento_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_evento_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_evento_id','{contrato_evento->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_unidade_indexador_to_string($contrato_pagamento_parcela_unidade_indexador_to_string)
    {
        if(is_array($contrato_pagamento_parcela_unidade_indexador_to_string))
        {
            $values = UnidadeIndexador::where('id', 'in', $contrato_pagamento_parcela_unidade_indexador_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_unidade_indexador_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_unidade_indexador_to_string = $contrato_pagamento_parcela_unidade_indexador_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_unidade_indexador_to_string'] = $this->contrato_pagamento_parcela_unidade_indexador_to_string;
    }

    public function get_contrato_pagamento_parcela_unidade_indexador_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_unidade_indexador_to_string))
        {
            return $this->contrato_pagamento_parcela_unidade_indexador_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('contrato_id', '=', $this->id)->getIndexedArray('unidade_indexador_id','{unidade_indexador->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_contrato_indexador_to_string($contrato_pagamento_parcela_contrato_indexador_to_string)
    {
        if(is_array($contrato_pagamento_parcela_contrato_indexador_to_string))
        {
            $values = ContratoPagamentoIndexador::where('id', 'in', $contrato_pagamento_parcela_contrato_indexador_to_string)->getIndexedArray('nome', 'nome');
            $this->contrato_pagamento_parcela_contrato_indexador_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_contrato_indexador_to_string = $contrato_pagamento_parcela_contrato_indexador_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_contrato_indexador_to_string'] = $this->contrato_pagamento_parcela_contrato_indexador_to_string;
    }

    public function get_contrato_pagamento_parcela_contrato_indexador_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_contrato_indexador_to_string))
        {
            return $this->contrato_pagamento_parcela_contrato_indexador_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_indexador_id','{contrato_indexador->nome}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_criacao_user_to_string($contrato_pagamento_parcela_criacao_user_to_string)
    {
        if(is_array($contrato_pagamento_parcela_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_parcela_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_parcela_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_criacao_user_to_string = $contrato_pagamento_parcela_criacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_criacao_user_to_string'] = $this->contrato_pagamento_parcela_criacao_user_to_string;
    }

    public function get_contrato_pagamento_parcela_criacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_criacao_user_to_string))
        {
            return $this->contrato_pagamento_parcela_criacao_user_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('contrato_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_contrato_pagamento_parcela_modificacao_user_to_string($contrato_pagamento_parcela_modificacao_user_to_string)
    {
        if(is_array($contrato_pagamento_parcela_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $contrato_pagamento_parcela_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->contrato_pagamento_parcela_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->contrato_pagamento_parcela_modificacao_user_to_string = $contrato_pagamento_parcela_modificacao_user_to_string;
        }

        $this->vdata['contrato_pagamento_parcela_modificacao_user_to_string'] = $this->contrato_pagamento_parcela_modificacao_user_to_string;
    }

    public function get_contrato_pagamento_parcela_modificacao_user_to_string()
    {
        if(!empty($this->contrato_pagamento_parcela_modificacao_user_to_string))
        {
            return $this->contrato_pagamento_parcela_modificacao_user_to_string;
        }
    
        $values = ContratoPagamentoParcela::where('contrato_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = ContratoPessoa::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = ContratoPessoa::where('contrato_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
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
    
        $values = ContratoProcesso::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = ContratoProcesso::where('contrato_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = ContratoProcesso::where('contrato_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = ContratoProcesso::where('contrato_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = ContratoRepasse::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = ContratoRepasse::where('contrato_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
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
    
        $values = ContratoRepresentante::where('contrato_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = ContratoRepresentante::where('contrato_id', '=', $this->id)->getIndexedArray('representante_id','{representante->nome}');
        return implode(', ', $values);
    }

    
}

