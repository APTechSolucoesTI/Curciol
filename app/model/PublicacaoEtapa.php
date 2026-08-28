<?php

class PublicacaoEtapa extends TRecord
{
    const TABLENAME  = 'publicacao_etapa';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    const TESTE = '3';

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('etapa_nome');
        parent::addAttribute('ordem_prioridade');
        parent::addAttribute('descricao');
        parent::addAttribute('cor');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
        parent::addAttribute('extrajudicial');
        parent::addAttribute('judicial');
            
    }

    /**
     * Method getProcessoPublicacoess
     */
    public function getProcessoPublicacoess()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('publicacao_etapa_id', '=', $this->id));
        return ProcessoPublicacoes::getObjects( $criteria );
    }
    /**
     * Method getPublicacaos
     */
    public function getPublicacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('publicacao_etapa_id', '=', $this->id));
        return Publicacao::getObjects( $criteria );
    }
    /**
     * Method getEtapaPalavrasChavess
     */
    public function getEtapaPalavrasChavess()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('publicacao_etapa_id', '=', $this->id));
        return EtapaPalavrasChaves::getObjects( $criteria );
    }
    /**
     * Method getAndamentos
     */
    public function getAndamentos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('publicacao_etapa_id', '=', $this->id));
        return Andamento::getObjects( $criteria );
    }

    public function set_processo_publicacoes_processo_to_string($processo_publicacoes_processo_to_string)
    {
        if(is_array($processo_publicacoes_processo_to_string))
        {
            $values = Processo::where('id', 'in', $processo_publicacoes_processo_to_string)->getIndexedArray('numero_cnj_numero', 'numero_cnj_numero');
            $this->processo_publicacoes_processo_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_publicacoes_processo_to_string = $processo_publicacoes_processo_to_string;
        }

        $this->vdata['processo_publicacoes_processo_to_string'] = $this->processo_publicacoes_processo_to_string;
    }

    public function get_processo_publicacoes_processo_to_string()
    {
        if(!empty($this->processo_publicacoes_processo_to_string))
        {
            return $this->processo_publicacoes_processo_to_string;
        }
    
        $values = ProcessoPublicacoes::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
        return implode(', ', $values);
    }

    public function set_processo_publicacoes_publicacao_to_string($processo_publicacoes_publicacao_to_string)
    {
        if(is_array($processo_publicacoes_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $processo_publicacoes_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->processo_publicacoes_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_publicacoes_publicacao_to_string = $processo_publicacoes_publicacao_to_string;
        }

        $this->vdata['processo_publicacoes_publicacao_to_string'] = $this->processo_publicacoes_publicacao_to_string;
    }

    public function get_processo_publicacoes_publicacao_to_string()
    {
        if(!empty($this->processo_publicacoes_publicacao_to_string))
        {
            return $this->processo_publicacoes_publicacao_to_string;
        }
    
        $values = ProcessoPublicacoes::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_processo_publicacoes_publicacao_etapa_to_string($processo_publicacoes_publicacao_etapa_to_string)
    {
        if(is_array($processo_publicacoes_publicacao_etapa_to_string))
        {
            $values = PublicacaoEtapa::where('id', 'in', $processo_publicacoes_publicacao_etapa_to_string)->getIndexedArray('id', 'id');
            $this->processo_publicacoes_publicacao_etapa_to_string = implode(', ', $values);
        }
        else
        {
            $this->processo_publicacoes_publicacao_etapa_to_string = $processo_publicacoes_publicacao_etapa_to_string;
        }

        $this->vdata['processo_publicacoes_publicacao_etapa_to_string'] = $this->processo_publicacoes_publicacao_etapa_to_string;
    }

    public function get_processo_publicacoes_publicacao_etapa_to_string()
    {
        if(!empty($this->processo_publicacoes_publicacao_etapa_to_string))
        {
            return $this->processo_publicacoes_publicacao_etapa_to_string;
        }
    
        $values = ProcessoPublicacoes::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('publicacao_etapa_id','{publicacao_etapa->id}');
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
    
        $values = Publicacao::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = Publicacao::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('jornal_id','{jornal->nome}');
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
    
        $values = Publicacao::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Publicacao::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_publicacao_etapa_to_string($publicacao_publicacao_etapa_to_string)
    {
        if(is_array($publicacao_publicacao_etapa_to_string))
        {
            $values = PublicacaoEtapa::where('id', 'in', $publicacao_publicacao_etapa_to_string)->getIndexedArray('id', 'id');
            $this->publicacao_publicacao_etapa_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_publicacao_etapa_to_string = $publicacao_publicacao_etapa_to_string;
        }

        $this->vdata['publicacao_publicacao_etapa_to_string'] = $this->publicacao_publicacao_etapa_to_string;
    }

    public function get_publicacao_publicacao_etapa_to_string()
    {
        if(!empty($this->publicacao_publicacao_etapa_to_string))
        {
            return $this->publicacao_publicacao_etapa_to_string;
        }
    
        $values = Publicacao::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('publicacao_etapa_id','{publicacao_etapa->id}');
        return implode(', ', $values);
    }

    public function set_etapa_palavras_chaves_publicacao_etapa_to_string($etapa_palavras_chaves_publicacao_etapa_to_string)
    {
        if(is_array($etapa_palavras_chaves_publicacao_etapa_to_string))
        {
            $values = PublicacaoEtapa::where('id', 'in', $etapa_palavras_chaves_publicacao_etapa_to_string)->getIndexedArray('id', 'id');
            $this->etapa_palavras_chaves_publicacao_etapa_to_string = implode(', ', $values);
        }
        else
        {
            $this->etapa_palavras_chaves_publicacao_etapa_to_string = $etapa_palavras_chaves_publicacao_etapa_to_string;
        }

        $this->vdata['etapa_palavras_chaves_publicacao_etapa_to_string'] = $this->etapa_palavras_chaves_publicacao_etapa_to_string;
    }

    public function get_etapa_palavras_chaves_publicacao_etapa_to_string()
    {
        if(!empty($this->etapa_palavras_chaves_publicacao_etapa_to_string))
        {
            return $this->etapa_palavras_chaves_publicacao_etapa_to_string;
        }
    
        $values = EtapaPalavrasChaves::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('publicacao_etapa_id','{publicacao_etapa->id}');
        return implode(', ', $values);
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
    
        $values = Andamento::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = Andamento::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('tipo_andamento_id','{tipo_andamento->nome}');
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
    
        $values = Andamento::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Andamento::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_andamento_publicacao_etapa_to_string($andamento_publicacao_etapa_to_string)
    {
        if(is_array($andamento_publicacao_etapa_to_string))
        {
            $values = PublicacaoEtapa::where('id', 'in', $andamento_publicacao_etapa_to_string)->getIndexedArray('id', 'id');
            $this->andamento_publicacao_etapa_to_string = implode(', ', $values);
        }
        else
        {
            $this->andamento_publicacao_etapa_to_string = $andamento_publicacao_etapa_to_string;
        }

        $this->vdata['andamento_publicacao_etapa_to_string'] = $this->andamento_publicacao_etapa_to_string;
    }

    public function get_andamento_publicacao_etapa_to_string()
    {
        if(!empty($this->andamento_publicacao_etapa_to_string))
        {
            return $this->andamento_publicacao_etapa_to_string;
        }
    
        $values = Andamento::where('publicacao_etapa_id', '=', $this->id)->getIndexedArray('publicacao_etapa_id','{publicacao_etapa->id}');
        return implode(', ', $values);
    }

    /**
     * Method onBeforeDelete
     */
    public function onBeforeDelete()
    {
            

        if(ProcessoPublicacoes::where('publicacao_etapa_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
        if(Publicacao::where('publicacao_etapa_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
        if(EtapaPalavrasChaves::where('publicacao_etapa_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
        if(Andamento::where('publicacao_etapa_id', '=', $this->id)->first())
        {
            throw new Exception("Não é possível deletar este registro pois ele está sendo utilizado em outra parte do sistema");
        }
    
    }

    
}

