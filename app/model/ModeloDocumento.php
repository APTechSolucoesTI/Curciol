<?php

class ModeloDocumento extends TRecord
{
    const TABLENAME  = 'modelo_documento';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private TipoModeloDocumento $tipo_modelo_documento;

   const VARIAVEIS_PF = [
        '${nome_cliente}' => "cliente->nome_formatado",
        '${nome_profissional}' => "profissional->nome_formatado",
        '${data_nascimento}' => "cliente->dt_nasci_formatada",
        '${nome_escritorio}' => "agendamento->agenda->escritorio->nome",
        '${data_atendimento}' => "data_atendimento",
        '${inicio_atendimento}' => "inicio_atendimento",
        '${nacionalidade}' => "cliente->nacionalidade->nome",
        '${estado_civil}' => "cliente->estado_civil->nome",
        '${profissao}' => "cliente->profissao",
        '${rg}' => "cliente->rg_ie",
        '${orgao_emissor}' => "cliente->orgao_emissor",
        '${cpf}' => "cliente->cpf_cnpj",
        '${rua}' => "cliente_endereco->rua",
        '${numero}' => "cliente_endereco->numero",
        '${complemento}' => "cliente_endereco->complemento",
        '${bairro}' => "cliente_endereco->bairro",
        '${cidade}' => "cliente_endereco->cidade->nome",
        '${uf}' => "cliente_endereco->cidade->estado->sigla",
        '${cep}' => "cliente_endereco->cep",
        '${objeto}' => "contrato->objeto",
        '${informacoes_pagamento}' => "contrato->pagamento",
        '${informacoes_documento}' => "documento->autenticador"
    ];

    const VARIAVEIS_PJ = [
        '${nome_cliente}' => "cliente->nome_formatado",
        '${nome_profissional}' => "profissional->nome_formatado",
        '${data_abertura}' => "cliente->dt_nasci_formatada",
        '${nome_escritorio}' => "agendamento->agenda->escritorio->nome",
        '${data_atendimento}' => "data_atendimento",
        '${inicio_atendimento}' => "inicio_atendimento",
        '${cnpj}' => "cliente->cpf_cnpj",
        '${rua}' => "cliente_endereco->rua",
        '${numero}' => "cliente_endereco->numero",
        '${complemento}' => "cliente_endereco->complemento",
        '${bairro}' => "cliente_endereco->bairro",
        '${cidade}' => "cliente_endereco->cidade->nome",
        '${uf}' => "cliente_endereco->cidade->estado->sigla",
        '${cep}' => "cliente_endereco->cep",
        '${nome_representante}' => "representante->nome_formatado",
        '${data_nascimento_representante}' => "representante->dt_nasci_formatada",
        '${nacionalidade_representante}' => "representante->nacionalidade->nome",
        '${estado_civil_representante}' => "representante->estado_civil->nome",
        '${profissao_representante}' => "representante->profissao",
        '${rg_representante}' => "representante->rg",
        '${orgao_emissor_representante}' => "representante->orgao_emissor",
        '${cpf_representante}' => "representante->cpf_cnpj",
        '${rua_representante}' => "representante_endereco->rua",
        '${numero_representante}' => "representante_endereco->numero",
        '${complemento_representante}' => "representante_endereco->complemento",
        '${bairro_representante}' => "representante_endereco->bairro",
        '${cidade_representante}' => "representante_endereco->cidade->nome",
        '${uf_representante}' => "representante_endereco->cidade->estado->sigla",
        '${cep_representante}' => "representante_endereco->cep",
        '${objeto}' => "contrato->objeto",
        '${informacoes_pagamento}' => "contrato->pagamento",
        '${informacoes_documento}' => "documento->autenticador",
    ];

    const VARIAVEIS_PFR = [
        '${nome_cliente}' => "cliente->nome_formatado",
        '${nome_profissional}' => "profissional->nome_formatado",
        '${nome_escritorio}' => "agendamento->agenda->escritorio->nome",
        '${data_atendimento}' => "data_atendimento",
        '${inicio_atendimento}' => "inicio_atendimento",
        '${nacionalidade}' => "cliente->nacionalidade->nome",
        '${estado_civil}' => "cliente->estado_civil->nome",
        '${profissao}' => "cliente->profissao",
        '${rg}' => "cliente->rg_ie",
        '${orgao_emissor}' => "cliente->orgao_emissor",
        '${cpf}' => "cliente->cpf_cnpj",
        '${rua}' => "cliente_endereco->rua",
        '${numero}' => "cliente_endereco->numero",
        '${complemento}' => "cliente_endereco->complemento",
        '${bairro}' => "cliente_endereco->bairro",
        '${cidade}' => "cliente_endereco->cidade->nome",
        '${uf}' => "cliente_endereco->cidade->estado->sigla",
        '${cep}' => "cliente_endereco->cep",
        '${nome_representante}' => "representante->nome_formatado",
        '${data_nascimento_representante}' => "representante->dt_nasci_formatada",
        '${nacionalidade_representante}' => "representante->nacionalidade->nome",
        '${estado_civil_representante}' => "representante->estado_civil->nome",
        '${profissao_representante}' => "representante->profissao",
        '${rg_representante}' => "representante->rg",
        '${orgao_emissor_representante}' => "representante->orgao_emissor",
        '${cpf_representante}' => "representante->cpf_cnpj",
        '${rua_representante}' => "representante_endereco->rua",
        '${numero_representante}' => "representante_endereco->numero",
        '${complemento_representante}' => "representante_endereco->complemento",
        '${bairro_representante}' => "representante_endereco->bairro",
        '${cidade_representante}' => "representante_endereco->cidade->nome",
        '${uf_representante}' => "representante_endereco->cidade->estado->sigla",
        '${cep_representante}' => "representante_endereco->cep",
        '${objeto}' => "contrato->objeto",
        '${informacoes_pagamento}' => "contrato->pagamento",
        '${informacoes_documento}' => "documento->autenticador"
    ];

    private static $LAUDO = 1;

                                                                                        

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tipo_modelo_documento_id');
        parent::addAttribute('nome');
        parent::addAttribute('ativo');
        parent::addAttribute('clausula_pagamento');
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
     * Method set_tipo_modelo_documento
     * Sample of usage: $var->tipo_modelo_documento = $object;
     * @param $object Instance of TipoModeloDocumento
     */
    public function set_tipo_modelo_documento(TipoModeloDocumento $object)
    {
        $this->tipo_modelo_documento = $object;
        $this->tipo_modelo_documento_id = $object->id;
    }

    /**
     * Method get_tipo_modelo_documento
     * Sample of usage: $var->tipo_modelo_documento->attribute;
     * @returns TipoModeloDocumento instance
     */
    public function get_tipo_modelo_documento()
    {
    
        // loads the associated object
        if (empty($this->tipo_modelo_documento))
            $this->tipo_modelo_documento = new TipoModeloDocumento($this->tipo_modelo_documento_id);
    
        // returns the associated object
        return $this->tipo_modelo_documento;
    }

    /**
     * Method getContratoDocumentos
     */
    public function getContratoDocumentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modelo_documento_id', '=', $this->id));
        return ContratoDocumento::getObjects( $criteria );
    }
    /**
     * Method getDocumentos
     */
    public function getDocumentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modelo_documento_id', '=', $this->id));
        return Documento::getObjects( $criteria );
    }
    /**
     * Method getDocumentoBaseContratos
     */
    public function getDocumentoBaseContratos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modelo_documento_id', '=', $this->id));
        return DocumentoBaseContrato::getObjects( $criteria );
    }
    /**
     * Method getModeloDocAplicacaos
     */
    public function getModeloDocAplicacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modelo_documento_id', '=', $this->id));
        return ModeloDocAplicacao::getObjects( $criteria );
    }
    /**
     * Method getModeloDocumentoPfs
     */
    public function getModeloDocumentoPfs()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modelo_documento_id', '=', $this->id));
        return ModeloDocumentoPf::getObjects( $criteria );
    }
    /**
     * Method getModeloDocumentoPfreps
     */
    public function getModeloDocumentoPfreps()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modelo_documento_id', '=', $this->id));
        return ModeloDocumentoPfrep::getObjects( $criteria );
    }
    /**
     * Method getModeloDocumentoPjs
     */
    public function getModeloDocumentoPjs()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modelo_documento_id', '=', $this->id));
        return ModeloDocumentoPj::getObjects( $criteria );
    }
    /**
     * Method getPadraoAtendModeloDocs
     */
    public function getPadraoAtendModeloDocs()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('modelo_documento_id', '=', $this->id));
        return PadraoAtendModeloDoc::getObjects( $criteria );
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
    
        $values = ContratoDocumento::where('modelo_documento_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = ContratoDocumento::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
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
    
        $values = ContratoDocumento::where('modelo_documento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = ContratoDocumento::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = Documento::where('modelo_documento_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
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
    
        $values = Documento::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
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
    
        $values = Documento::where('modelo_documento_id', '=', $this->id)->getIndexedArray('procedimento_id','{procedimento->nome}');
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
    
        $values = Documento::where('modelo_documento_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Documento::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_documento_base_contrato_area_to_string($documento_base_contrato_area_to_string)
    {
        if(is_array($documento_base_contrato_area_to_string))
        {
            $values = Area::where('id', 'in', $documento_base_contrato_area_to_string)->getIndexedArray('nome', 'nome');
            $this->documento_base_contrato_area_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_base_contrato_area_to_string = $documento_base_contrato_area_to_string;
        }

        $this->vdata['documento_base_contrato_area_to_string'] = $this->documento_base_contrato_area_to_string;
    }

    public function get_documento_base_contrato_area_to_string()
    {
        if(!empty($this->documento_base_contrato_area_to_string))
        {
            return $this->documento_base_contrato_area_to_string;
        }
    
        $values = DocumentoBaseContrato::where('modelo_documento_id', '=', $this->id)->getIndexedArray('area_id','{area->nome}');
        return implode(', ', $values);
    }

    public function set_documento_base_contrato_modelo_documento_to_string($documento_base_contrato_modelo_documento_to_string)
    {
        if(is_array($documento_base_contrato_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $documento_base_contrato_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->documento_base_contrato_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->documento_base_contrato_modelo_documento_to_string = $documento_base_contrato_modelo_documento_to_string;
        }

        $this->vdata['documento_base_contrato_modelo_documento_to_string'] = $this->documento_base_contrato_modelo_documento_to_string;
    }

    public function get_documento_base_contrato_modelo_documento_to_string()
    {
        if(!empty($this->documento_base_contrato_modelo_documento_to_string))
        {
            return $this->documento_base_contrato_modelo_documento_to_string;
        }
    
        $values = DocumentoBaseContrato::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_modelo_doc_aplicacao_modelo_documento_to_string($modelo_doc_aplicacao_modelo_documento_to_string)
    {
        if(is_array($modelo_doc_aplicacao_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $modelo_doc_aplicacao_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->modelo_doc_aplicacao_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_doc_aplicacao_modelo_documento_to_string = $modelo_doc_aplicacao_modelo_documento_to_string;
        }

        $this->vdata['modelo_doc_aplicacao_modelo_documento_to_string'] = $this->modelo_doc_aplicacao_modelo_documento_to_string;
    }

    public function get_modelo_doc_aplicacao_modelo_documento_to_string()
    {
        if(!empty($this->modelo_doc_aplicacao_modelo_documento_to_string))
        {
            return $this->modelo_doc_aplicacao_modelo_documento_to_string;
        }
    
        $values = ModeloDocAplicacao::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_modelo_doc_aplicacao_tipo_aplicacao_to_string($modelo_doc_aplicacao_tipo_aplicacao_to_string)
    {
        if(is_array($modelo_doc_aplicacao_tipo_aplicacao_to_string))
        {
            $values = ModeloDocTipoAplicacao::where('id', 'in', $modelo_doc_aplicacao_tipo_aplicacao_to_string)->getIndexedArray('nome', 'nome');
            $this->modelo_doc_aplicacao_tipo_aplicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_doc_aplicacao_tipo_aplicacao_to_string = $modelo_doc_aplicacao_tipo_aplicacao_to_string;
        }

        $this->vdata['modelo_doc_aplicacao_tipo_aplicacao_to_string'] = $this->modelo_doc_aplicacao_tipo_aplicacao_to_string;
    }

    public function get_modelo_doc_aplicacao_tipo_aplicacao_to_string()
    {
        if(!empty($this->modelo_doc_aplicacao_tipo_aplicacao_to_string))
        {
            return $this->modelo_doc_aplicacao_tipo_aplicacao_to_string;
        }
    
        $values = ModeloDocAplicacao::where('modelo_documento_id', '=', $this->id)->getIndexedArray('tipo_aplicacao_id','{tipo_aplicacao->nome}');
        return implode(', ', $values);
    }

    public function set_modelo_documento_pf_modelo_documento_to_string($modelo_documento_pf_modelo_documento_to_string)
    {
        if(is_array($modelo_documento_pf_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $modelo_documento_pf_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->modelo_documento_pf_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_documento_pf_modelo_documento_to_string = $modelo_documento_pf_modelo_documento_to_string;
        }

        $this->vdata['modelo_documento_pf_modelo_documento_to_string'] = $this->modelo_documento_pf_modelo_documento_to_string;
    }

    public function get_modelo_documento_pf_modelo_documento_to_string()
    {
        if(!empty($this->modelo_documento_pf_modelo_documento_to_string))
        {
            return $this->modelo_documento_pf_modelo_documento_to_string;
        }
    
        $values = ModeloDocumentoPf::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_modelo_documento_pfrep_modelo_documento_to_string($modelo_documento_pfrep_modelo_documento_to_string)
    {
        if(is_array($modelo_documento_pfrep_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $modelo_documento_pfrep_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->modelo_documento_pfrep_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_documento_pfrep_modelo_documento_to_string = $modelo_documento_pfrep_modelo_documento_to_string;
        }

        $this->vdata['modelo_documento_pfrep_modelo_documento_to_string'] = $this->modelo_documento_pfrep_modelo_documento_to_string;
    }

    public function get_modelo_documento_pfrep_modelo_documento_to_string()
    {
        if(!empty($this->modelo_documento_pfrep_modelo_documento_to_string))
        {
            return $this->modelo_documento_pfrep_modelo_documento_to_string;
        }
    
        $values = ModeloDocumentoPfrep::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_modelo_documento_pj_modelo_documento_to_string($modelo_documento_pj_modelo_documento_to_string)
    {
        if(is_array($modelo_documento_pj_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $modelo_documento_pj_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->modelo_documento_pj_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->modelo_documento_pj_modelo_documento_to_string = $modelo_documento_pj_modelo_documento_to_string;
        }

        $this->vdata['modelo_documento_pj_modelo_documento_to_string'] = $this->modelo_documento_pj_modelo_documento_to_string;
    }

    public function get_modelo_documento_pj_modelo_documento_to_string()
    {
        if(!empty($this->modelo_documento_pj_modelo_documento_to_string))
        {
            return $this->modelo_documento_pj_modelo_documento_to_string;
        }
    
        $values = ModeloDocumentoPj::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

    public function set_padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string($padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string)
    {
        if(is_array($padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string))
        {
            $values = PadraoAtendimentoDocumento::where('id', 'in', $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string)->getIndexedArray('nome', 'nome');
            $this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string = implode(', ', $values);
        }
        else
        {
            $this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string = $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string;
        }

        $this->vdata['padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string'] = $this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string;
    }

    public function get_padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string()
    {
        if(!empty($this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string))
        {
            return $this->padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_to_string;
        }
    
        $values = PadraoAtendModeloDoc::where('modelo_documento_id', '=', $this->id)->getIndexedArray('tipo_padrao_doc_atendimento_id','{tipo_padrao_doc_atendimento->nome}');
        return implode(', ', $values);
    }

    public function set_padrao_atend_modelo_doc_modelo_documento_to_string($padrao_atend_modelo_doc_modelo_documento_to_string)
    {
        if(is_array($padrao_atend_modelo_doc_modelo_documento_to_string))
        {
            $values = ModeloDocumento::where('id', 'in', $padrao_atend_modelo_doc_modelo_documento_to_string)->getIndexedArray('nome', 'nome');
            $this->padrao_atend_modelo_doc_modelo_documento_to_string = implode(', ', $values);
        }
        else
        {
            $this->padrao_atend_modelo_doc_modelo_documento_to_string = $padrao_atend_modelo_doc_modelo_documento_to_string;
        }

        $this->vdata['padrao_atend_modelo_doc_modelo_documento_to_string'] = $this->padrao_atend_modelo_doc_modelo_documento_to_string;
    }

    public function get_padrao_atend_modelo_doc_modelo_documento_to_string()
    {
        if(!empty($this->padrao_atend_modelo_doc_modelo_documento_to_string))
        {
            return $this->padrao_atend_modelo_doc_modelo_documento_to_string;
        }
    
        $values = PadraoAtendModeloDoc::where('modelo_documento_id', '=', $this->id)->getIndexedArray('modelo_documento_id','{modelo_documento->nome}');
        return implode(', ', $values);
    }

}

