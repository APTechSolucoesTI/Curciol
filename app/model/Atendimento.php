<?php

class Atendimento extends TRecord
{
    const TABLENAME  = 'atendimento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Pessoa $profissional;
    private Agendamento $agendamento;
    private TipoAtendimento $tipo_atendimento;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private Pessoa $cliente;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('agendamento_id');
        parent::addAttribute('cliente_id');
        parent::addAttribute('profissional_id');
        parent::addAttribute('tipo_atendimento_id');
        parent::addAttribute('informacoes');
        parent::addAttribute('dt_inicio');
        parent::addAttribute('dt_final');
        parent::addAttribute('valor_total');
        parent::addAttribute('ano_inicial');
        parent::addAttribute('mes_inicial');
        parent::addAttribute('ano_mes_inicial');
        parent::addAttribute('mes_final');
        parent::addAttribute('ano_final');
        parent::addAttribute('ano_mes_final');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    
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
     * Method set_agendamento
     * Sample of usage: $var->agendamento = $object;
     * @param $object Instance of Agendamento
     */
    public function set_agendamento(Agendamento $object)
    {
        $this->agendamento = $object;
        $this->agendamento_id = $object->id;
    }

    /**
     * Method get_agendamento
     * Sample of usage: $var->agendamento->attribute;
     * @returns Agendamento instance
     */
    public function get_agendamento()
    {
    
        // loads the associated object
        if (empty($this->agendamento))
            $this->agendamento = new Agendamento($this->agendamento_id);
    
        // returns the associated object
        return $this->agendamento;
    }
    /**
     * Method set_tipo_atendimento
     * Sample of usage: $var->tipo_atendimento = $object;
     * @param $object Instance of TipoAtendimento
     */
    public function set_tipo_atendimento(TipoAtendimento $object)
    {
        $this->tipo_atendimento = $object;
        $this->tipo_atendimento_id = $object->id;
    }

    /**
     * Method get_tipo_atendimento
     * Sample of usage: $var->tipo_atendimento->attribute;
     * @returns TipoAtendimento instance
     */
    public function get_tipo_atendimento()
    {
    
        // loads the associated object
        if (empty($this->tipo_atendimento))
            $this->tipo_atendimento = new TipoAtendimento($this->tipo_atendimento_id);
    
        // returns the associated object
        return $this->tipo_atendimento;
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
     * Method getAtendimentoContratos
     */
    public function getAtendimentoContratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('atendimento_id', '=', $this->id));
        return AtendimentoContrato::getObjects( $criteria );
    }
    /**
     * Method getAtendimentoHistoricos
     */
    public function getAtendimentoHistoricos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('atendimento_id', '=', $this->id));
        return AtendimentoHistorico::getObjects( $criteria );
    }
    /**
     * Method getAtendimentoMaterials
     */
    public function getAtendimentoMaterials()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('atendimento_id', '=', $this->id));
        return AtendimentoMaterial::getObjects( $criteria );
    }
    /**
     * Method getAtendimentoProcedimentos
     */
    public function getAtendimentoProcedimentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('atendimento_id', '=', $this->id));
        return AtendimentoProcedimento::getObjects( $criteria );
    }
    /**
     * Method getAnexos
     */
    public function getAnexos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('atendimento_id', '=', $this->id));
        return Anexo::getObjects( $criteria );
    }
    /**
     * Method getContas
     */
    public function getContas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('atendimento_id', '=', $this->id));
        return Conta::getObjects( $criteria );
    }
    /**
     * Method getDocumentos
     */
    public function getDocumentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('atendimento_id', '=', $this->id));
        return Documento::getObjects( $criteria );
    }
    /**
     * Method getRespostaFormularios
     */
    public function getRespostaFormularios()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('atendimento_id', '=', $this->id));
        return RespostaFormulario::getObjects( $criteria );
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
    
        $values = AtendimentoContrato::where('atendimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
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
    
        $values = AtendimentoContrato::where('atendimento_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
        return implode(', ', $values);
    }

    public function set_atendimento_historico_atendimento_to_string($atendimento_historico_atendimento_to_string)
    {
        if(is_array($atendimento_historico_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $atendimento_historico_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->atendimento_historico_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_historico_atendimento_to_string = $atendimento_historico_atendimento_to_string;
        }

        $this->vdata['atendimento_historico_atendimento_to_string'] = $this->atendimento_historico_atendimento_to_string;
    }

    public function get_atendimento_historico_atendimento_to_string()
    {
        if(!empty($this->atendimento_historico_atendimento_to_string))
        {
            return $this->atendimento_historico_atendimento_to_string;
        }
    
        $values = AtendimentoHistorico::where('atendimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_atendimento_historico_criacao_user_to_string($atendimento_historico_criacao_user_to_string)
    {
        if(is_array($atendimento_historico_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $atendimento_historico_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->atendimento_historico_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_historico_criacao_user_to_string = $atendimento_historico_criacao_user_to_string;
        }

        $this->vdata['atendimento_historico_criacao_user_to_string'] = $this->atendimento_historico_criacao_user_to_string;
    }

    public function get_atendimento_historico_criacao_user_to_string()
    {
        if(!empty($this->atendimento_historico_criacao_user_to_string))
        {
            return $this->atendimento_historico_criacao_user_to_string;
        }
    
        $values = AtendimentoHistorico::where('atendimento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_atendimento_historico_modificacao_user_to_string($atendimento_historico_modificacao_user_to_string)
    {
        if(is_array($atendimento_historico_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $atendimento_historico_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->atendimento_historico_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_historico_modificacao_user_to_string = $atendimento_historico_modificacao_user_to_string;
        }

        $this->vdata['atendimento_historico_modificacao_user_to_string'] = $this->atendimento_historico_modificacao_user_to_string;
    }

    public function get_atendimento_historico_modificacao_user_to_string()
    {
        if(!empty($this->atendimento_historico_modificacao_user_to_string))
        {
            return $this->atendimento_historico_modificacao_user_to_string;
        }
    
        $values = AtendimentoHistorico::where('atendimento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_atendimento_material_material_to_string($atendimento_material_material_to_string)
    {
        if(is_array($atendimento_material_material_to_string))
        {
            $values = Material::where('id', 'in', $atendimento_material_material_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_material_material_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_material_material_to_string = $atendimento_material_material_to_string;
        }

        $this->vdata['atendimento_material_material_to_string'] = $this->atendimento_material_material_to_string;
    }

    public function get_atendimento_material_material_to_string()
    {
        if(!empty($this->atendimento_material_material_to_string))
        {
            return $this->atendimento_material_material_to_string;
        }
    
        $values = AtendimentoMaterial::where('atendimento_id', '=', $this->id)->getIndexedArray('material_id','{material->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_material_atendimento_to_string($atendimento_material_atendimento_to_string)
    {
        if(is_array($atendimento_material_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $atendimento_material_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->atendimento_material_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_material_atendimento_to_string = $atendimento_material_atendimento_to_string;
        }

        $this->vdata['atendimento_material_atendimento_to_string'] = $this->atendimento_material_atendimento_to_string;
    }

    public function get_atendimento_material_atendimento_to_string()
    {
        if(!empty($this->atendimento_material_atendimento_to_string))
        {
            return $this->atendimento_material_atendimento_to_string;
        }
    
        $values = AtendimentoMaterial::where('atendimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_atendimento_procedimento_parceiro_to_string($atendimento_procedimento_parceiro_to_string)
    {
        if(is_array($atendimento_procedimento_parceiro_to_string))
        {
            $values = Parceiro::where('id', 'in', $atendimento_procedimento_parceiro_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_procedimento_parceiro_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_procedimento_parceiro_to_string = $atendimento_procedimento_parceiro_to_string;
        }

        $this->vdata['atendimento_procedimento_parceiro_to_string'] = $this->atendimento_procedimento_parceiro_to_string;
    }

    public function get_atendimento_procedimento_parceiro_to_string()
    {
        if(!empty($this->atendimento_procedimento_parceiro_to_string))
        {
            return $this->atendimento_procedimento_parceiro_to_string;
        }
    
        $values = AtendimentoProcedimento::where('atendimento_id', '=', $this->id)->getIndexedArray('parceiro_id','{parceiro->nome}');
        return implode(', ', $values);
    }

    public function set_atendimento_procedimento_atendimento_to_string($atendimento_procedimento_atendimento_to_string)
    {
        if(is_array($atendimento_procedimento_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $atendimento_procedimento_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->atendimento_procedimento_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_procedimento_atendimento_to_string = $atendimento_procedimento_atendimento_to_string;
        }

        $this->vdata['atendimento_procedimento_atendimento_to_string'] = $this->atendimento_procedimento_atendimento_to_string;
    }

    public function get_atendimento_procedimento_atendimento_to_string()
    {
        if(!empty($this->atendimento_procedimento_atendimento_to_string))
        {
            return $this->atendimento_procedimento_atendimento_to_string;
        }
    
        $values = AtendimentoProcedimento::where('atendimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_atendimento_procedimento_procedimento_to_string($atendimento_procedimento_procedimento_to_string)
    {
        if(is_array($atendimento_procedimento_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $atendimento_procedimento_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->atendimento_procedimento_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->atendimento_procedimento_procedimento_to_string = $atendimento_procedimento_procedimento_to_string;
        }

        $this->vdata['atendimento_procedimento_procedimento_to_string'] = $this->atendimento_procedimento_procedimento_to_string;
    }

    public function get_atendimento_procedimento_procedimento_to_string()
    {
        if(!empty($this->atendimento_procedimento_procedimento_to_string))
        {
            return $this->atendimento_procedimento_procedimento_to_string;
        }
    
        $values = AtendimentoProcedimento::where('atendimento_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_anexo_atendimento_to_string($anexo_atendimento_to_string)
    {
        if(is_array($anexo_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $anexo_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->anexo_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->anexo_atendimento_to_string = $anexo_atendimento_to_string;
        }

        $this->vdata['anexo_atendimento_to_string'] = $this->anexo_atendimento_to_string;
    }

    public function get_anexo_atendimento_to_string()
    {
        if(!empty($this->anexo_atendimento_to_string))
        {
            return $this->anexo_atendimento_to_string;
        }
    
        $values = Anexo::where('atendimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_anexo_criacao_user_to_string($anexo_criacao_user_to_string)
    {
        if(is_array($anexo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $anexo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->anexo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->anexo_criacao_user_to_string = $anexo_criacao_user_to_string;
        }

        $this->vdata['anexo_criacao_user_to_string'] = $this->anexo_criacao_user_to_string;
    }

    public function get_anexo_criacao_user_to_string()
    {
        if(!empty($this->anexo_criacao_user_to_string))
        {
            return $this->anexo_criacao_user_to_string;
        }
    
        $values = Anexo::where('atendimento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_anexo_modificacao_user_to_string($anexo_modificacao_user_to_string)
    {
        if(is_array($anexo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $anexo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->anexo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->anexo_modificacao_user_to_string = $anexo_modificacao_user_to_string;
        }

        $this->vdata['anexo_modificacao_user_to_string'] = $this->anexo_modificacao_user_to_string;
    }

    public function get_anexo_modificacao_user_to_string()
    {
        if(!empty($this->anexo_modificacao_user_to_string))
        {
            return $this->anexo_modificacao_user_to_string;
        }
    
        $values = Anexo::where('atendimento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('tipo_documento_financeiro_id','{tipo_documento_financeiro->nome}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Conta::where('atendimento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_documento_atendimento_to_string($documento_atendimento_to_string)
    {
        if(is_array($documento_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $documento_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->documento_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_atendimento_to_string = $documento_atendimento_to_string;
        }

        $this->vdata['documento_atendimento_to_string'] = $this->documento_atendimento_to_string;
    }

    public function get_documento_atendimento_to_string()
    {
        if(!empty($this->documento_atendimento_to_string))
        {
            return $this->documento_atendimento_to_string;
        }
    
        $values = Documento::where('atendimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_documento_modelo_documento_to_string($documento_modelo_documento_to_string)
    {
        if(is_array($documento_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $documento_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->documento_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_modelo_documento_to_string = $documento_modelo_documento_to_string;
        }

        $this->vdata['documento_modelo_documento_to_string'] = $this->documento_modelo_documento_to_string;
    }

    public function get_documento_modelo_documento_to_string()
    {
        if(!empty($this->documento_modelo_documento_to_string))
        {
            return $this->documento_modelo_documento_to_string;
        }
    
        $values = Documento::where('atendimento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_documento_procedimento_to_string($documento_procedimento_to_string)
    {
        if(is_array($documento_procedimento_to_string))
        {
            $values = Procedimento::where('id', 'in', $documento_procedimento_to_string)->getIndexedArray('nome', 'nome');
            $this->documento_procedimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_procedimento_to_string = $documento_procedimento_to_string;
        }

        $this->vdata['documento_procedimento_to_string'] = $this->documento_procedimento_to_string;
    }

    public function get_documento_procedimento_to_string()
    {
        if(!empty($this->documento_procedimento_to_string))
        {
            return $this->documento_procedimento_to_string;
        }
    
        $values = Documento::where('atendimento_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
        return implode(', ', $values);
    }

    public function set_documento_criacao_user_to_string($documento_criacao_user_to_string)
    {
        if(is_array($documento_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $documento_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->documento_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_criacao_user_to_string = $documento_criacao_user_to_string;
        }

        $this->vdata['documento_criacao_user_to_string'] = $this->documento_criacao_user_to_string;
    }

    public function get_documento_criacao_user_to_string()
    {
        if(!empty($this->documento_criacao_user_to_string))
        {
            return $this->documento_criacao_user_to_string;
        }
    
        $values = Documento::where('atendimento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_documento_modificacao_user_to_string($documento_modificacao_user_to_string)
    {
        if(is_array($documento_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $documento_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->documento_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_modificacao_user_to_string = $documento_modificacao_user_to_string;
        }

        $this->vdata['documento_modificacao_user_to_string'] = $this->documento_modificacao_user_to_string;
    }

    public function get_documento_modificacao_user_to_string()
    {
        if(!empty($this->documento_modificacao_user_to_string))
        {
            return $this->documento_modificacao_user_to_string;
        }
    
        $values = Documento::where('atendimento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_formulario_to_string($resposta_formulario_formulario_to_string)
    {
        if(is_array($resposta_formulario_formulario_to_string))
        {
            $values = Formulario::where('id', 'in', $resposta_formulario_formulario_to_string)->getIndexedArray('nome', 'nome');
            $this->resposta_formulario_formulario_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_formulario_to_string = $resposta_formulario_formulario_to_string;
        }

        $this->vdata['resposta_formulario_formulario_to_string'] = $this->resposta_formulario_formulario_to_string;
    }

    public function get_resposta_formulario_formulario_to_string()
    {
        if(!empty($this->resposta_formulario_formulario_to_string))
        {
            return $this->resposta_formulario_formulario_to_string;
        }
    
        $values = RespostaFormulario::where('atendimento_id', '=', $this->id)->getIndexedArray('formulario_id','{formulario->nome}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_atendimento_to_string($resposta_formulario_atendimento_to_string)
    {
        if(is_array($resposta_formulario_atendimento_to_string))
        {
            $values = Atendimento::where('id', 'in', $resposta_formulario_atendimento_to_string)->getIndexedArray('id', 'id');
            $this->resposta_formulario_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_atendimento_to_string = $resposta_formulario_atendimento_to_string;
        }

        $this->vdata['resposta_formulario_atendimento_to_string'] = $this->resposta_formulario_atendimento_to_string;
    }

    public function get_resposta_formulario_atendimento_to_string()
    {
        if(!empty($this->resposta_formulario_atendimento_to_string))
        {
            return $this->resposta_formulario_atendimento_to_string;
        }
    
        $values = RespostaFormulario::where('atendimento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_criacao_user_to_string($resposta_formulario_criacao_user_to_string)
    {
        if(is_array($resposta_formulario_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $resposta_formulario_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->resposta_formulario_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_criacao_user_to_string = $resposta_formulario_criacao_user_to_string;
        }

        $this->vdata['resposta_formulario_criacao_user_to_string'] = $this->resposta_formulario_criacao_user_to_string;
    }

    public function get_resposta_formulario_criacao_user_to_string()
    {
        if(!empty($this->resposta_formulario_criacao_user_to_string))
        {
            return $this->resposta_formulario_criacao_user_to_string;
        }
    
        $values = RespostaFormulario::where('atendimento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_resposta_formulario_modificacao_user_to_string($resposta_formulario_modificacao_user_to_string)
    {
        if(is_array($resposta_formulario_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $resposta_formulario_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->resposta_formulario_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->resposta_formulario_modificacao_user_to_string = $resposta_formulario_modificacao_user_to_string;
        }

        $this->vdata['resposta_formulario_modificacao_user_to_string'] = $this->resposta_formulario_modificacao_user_to_string;
    }

    public function get_resposta_formulario_modificacao_user_to_string()
    {
        if(!empty($this->resposta_formulario_modificacao_user_to_string))
        {
            return $this->resposta_formulario_modificacao_user_to_string;
        }
    
        $values = RespostaFormulario::where('atendimento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function onBeforeStore($object)
    {
        if (! empty($object->dt_inicio))
        {
            $object->ano_inicial = date('Y', strtotime($object->dt_inicio));
            $object->mes_inicial = date('m', strtotime($object->dt_inicio));
            $object->ano_mes_inicial = date('Ym', strtotime($object->dt_inicio));
        }
    
        if (! empty($object->dt_final))
        {
            $object->ano_final = date('Y', strtotime($object->dt_final));
            $object->mes_final = date('m', strtotime($object->dt_final));
            $object->ano_mes_final = date('Ym', strtotime($object->dt_final));
        }
    }

    public function get_data_atendimento()
    {
        return TDate::date2br($this->dt_inicio);
    }

    public function get_inicio_atendimento()
    {
        return date('d/m/Y H:i', strtotime($this->dt_inicio));
    }

    public function get_cidade_cliente(){
        $endereco = get_cliente_endereco();
        return $cidade = $endereco->get_cidade();
    }

    public function get_atendimento_historico_historico_to_string()
    {
        $value = AtendimentoHistorico::where('atendimento_id', '=', $this->id)->first();
        if($value){
            return $value->historico;
        }
        /*else{
            return null;
        }*/
    }

                                        
}

