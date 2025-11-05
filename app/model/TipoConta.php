<?php

class TipoConta extends TRecord
{
    const TABLENAME  = 'tipo_conta';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    const RECEBER = '1';
    const PAGAR = '2';
    const AMBOS = '3';

    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('nome');
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
     * Method getCategoriaContas
     */
    public function getCategoriaContas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_conta_id', '=', $this->id));
        return CategoriaConta::getObjects( $criteria );
    }
    /**
     * Method getContas
     */
    public function getContas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_conta_id', '=', $this->id));
        return Conta::getObjects( $criteria );
    }
    /**
     * Method getTipoDocumentoFinanceiros
     */
    public function getTipoDocumentoFinanceiros()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tipo_conta_id', '=', $this->id));
        return TipoDocumentoFinanceiro::getObjects( $criteria );
    }

    public function set_categoria_conta_tipo_conta_to_string($categoria_conta_tipo_conta_to_string)
    {
        if(is_array($categoria_conta_tipo_conta_to_string))
        {
            $values = TipoConta::where('id', 'in', $categoria_conta_tipo_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->categoria_conta_tipo_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->categoria_conta_tipo_conta_to_string = $categoria_conta_tipo_conta_to_string;
        }

        $this->vdata['categoria_conta_tipo_conta_to_string'] = $this->categoria_conta_tipo_conta_to_string;
    }

    public function get_categoria_conta_tipo_conta_to_string()
    {
        if(!empty($this->categoria_conta_tipo_conta_to_string))
        {
            return $this->categoria_conta_tipo_conta_to_string;
        }
    
        $values = CategoriaConta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
        return implode(', ', $values);
    }

    public function set_categoria_conta_criacao_user_to_string($categoria_conta_criacao_user_to_string)
    {
        if(is_array($categoria_conta_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $categoria_conta_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->categoria_conta_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->categoria_conta_criacao_user_to_string = $categoria_conta_criacao_user_to_string;
        }

        $this->vdata['categoria_conta_criacao_user_to_string'] = $this->categoria_conta_criacao_user_to_string;
    }

    public function get_categoria_conta_criacao_user_to_string()
    {
        if(!empty($this->categoria_conta_criacao_user_to_string))
        {
            return $this->categoria_conta_criacao_user_to_string;
        }
    
        $values = CategoriaConta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_categoria_conta_modificacao_user_to_string($categoria_conta_modificacao_user_to_string)
    {
        if(is_array($categoria_conta_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $categoria_conta_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->categoria_conta_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->categoria_conta_modificacao_user_to_string = $categoria_conta_modificacao_user_to_string;
        }

        $this->vdata['categoria_conta_modificacao_user_to_string'] = $this->categoria_conta_modificacao_user_to_string;
    }

    public function get_categoria_conta_modificacao_user_to_string()
    {
        if(!empty($this->categoria_conta_modificacao_user_to_string))
        {
            return $this->categoria_conta_modificacao_user_to_string;
        }
    
        $values = CategoriaConta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('pessoa_id','{pessoa->nome}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('categoria_conta_id','{categoria_conta->nome}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('escritorio_id','{escritorio->nome}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('tipo_documento_financeiro_id','{tipo_documento_financeiro->nome}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('atendimento_id','{atendimento->id}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('contrato_id','{contrato->objeto}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Conta::where('tipo_conta_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_tipo_conta_to_string($tipo_documento_financeiro_tipo_conta_to_string)
    {
        if(is_array($tipo_documento_financeiro_tipo_conta_to_string))
        {
            $values = TipoConta::where('id', 'in', $tipo_documento_financeiro_tipo_conta_to_string)->getIndexedArray('nome', 'nome');
            $this->tipo_documento_financeiro_tipo_conta_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_tipo_conta_to_string = $tipo_documento_financeiro_tipo_conta_to_string;
        }

        $this->vdata['tipo_documento_financeiro_tipo_conta_to_string'] = $this->tipo_documento_financeiro_tipo_conta_to_string;
    }

    public function get_tipo_documento_financeiro_tipo_conta_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_tipo_conta_to_string))
        {
            return $this->tipo_documento_financeiro_tipo_conta_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('tipo_conta_id', '=', $this->id)->getIndexedArray('tipo_conta_id','{tipo_conta->nome}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_padrao_to_string($tipo_documento_financeiro_padrao_to_string)
    {
        if(is_array($tipo_documento_financeiro_padrao_to_string))
        {
            $values = TipoDocFinanceiroPadrao::where('id', 'in', $tipo_documento_financeiro_padrao_to_string)->getIndexedArray('nome', 'nome');
            $this->tipo_documento_financeiro_padrao_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_padrao_to_string = $tipo_documento_financeiro_padrao_to_string;
        }

        $this->vdata['tipo_documento_financeiro_padrao_to_string'] = $this->tipo_documento_financeiro_padrao_to_string;
    }

    public function get_tipo_documento_financeiro_padrao_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_padrao_to_string))
        {
            return $this->tipo_documento_financeiro_padrao_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('tipo_conta_id', '=', $this->id)->getIndexedArray('padrao_id','{padrao->nome}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_criacao_user_to_string($tipo_documento_financeiro_criacao_user_to_string)
    {
        if(is_array($tipo_documento_financeiro_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_documento_financeiro_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_documento_financeiro_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_criacao_user_to_string = $tipo_documento_financeiro_criacao_user_to_string;
        }

        $this->vdata['tipo_documento_financeiro_criacao_user_to_string'] = $this->tipo_documento_financeiro_criacao_user_to_string;
    }

    public function get_tipo_documento_financeiro_criacao_user_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_criacao_user_to_string))
        {
            return $this->tipo_documento_financeiro_criacao_user_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('tipo_conta_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tipo_documento_financeiro_modificacao_user_to_string($tipo_documento_financeiro_modificacao_user_to_string)
    {
        if(is_array($tipo_documento_financeiro_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tipo_documento_financeiro_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tipo_documento_financeiro_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tipo_documento_financeiro_modificacao_user_to_string = $tipo_documento_financeiro_modificacao_user_to_string;
        }

        $this->vdata['tipo_documento_financeiro_modificacao_user_to_string'] = $this->tipo_documento_financeiro_modificacao_user_to_string;
    }

    public function get_tipo_documento_financeiro_modificacao_user_to_string()
    {
        if(!empty($this->tipo_documento_financeiro_modificacao_user_to_string))
        {
            return $this->tipo_documento_financeiro_modificacao_user_to_string;
        }
    
        $values = TipoDocumentoFinanceiro::where('tipo_conta_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    
}

