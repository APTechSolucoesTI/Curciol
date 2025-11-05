<?php

class Publicacao extends TRecord
{
    const TABLENAME  = 'publicacao';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Processo $processo;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;
    private Jornal $jornal;

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('numero_arquivo');
        parent::addAttribute('numero_publicacao');
        parent::addAttribute('titulo');
        parent::addAttribute('texto');
        parent::addAttribute('cabecalho');
        parent::addAttribute('rodape');
        parent::addAttribute('processo_id');
        parent::addAttribute('numero_unico_processo');
        parent::addAttribute('numero_processo_principal');
        parent::addAttribute('jornal_id');
        parent::addAttribute('data_tratamento');
        parent::addAttribute('data_disponibilizacao');
        parent::addAttribute('termo_ref_data');
        parent::addAttribute('prazo');
        parent::addAttribute('confirma_prazo');
        parent::addAttribute('data_entrega');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    
    }

    /**
     * Method set_processo
     * Sample of usage: $var->processo = $object;
     * @param $object Instance of Processo
     */
    public function set_processo(Processo $object)
    {
        $this->processo = $object;
        $this->processo_id = $object->id;
    }

    /**
     * Method get_processo
     * Sample of usage: $var->processo->attribute;
     * @returns Processo instance
     */
    public function get_processo()
    {
    
        // loads the associated object
        if (empty($this->processo))
            $this->processo = new Processo($this->processo_id);
    
        // returns the associated object
        return $this->processo;
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
     * Method set_jornal
     * Sample of usage: $var->jornal = $object;
     * @param $object Instance of Jornal
     */
    public function set_jornal(Jornal $object)
    {
        $this->jornal = $object;
        $this->jornal_id = $object->id;
    }

    /**
     * Method get_jornal
     * Sample of usage: $var->jornal->attribute;
     * @returns Jornal instance
     */
    public function get_jornal()
    {
    
        // loads the associated object
        if (empty($this->jornal))
            $this->jornal = new Jornal($this->jornal_id);
    
        // returns the associated object
        return $this->jornal;
    }

    /**
     * Method getPublicacaoMovimentacaos
     */
    public function getPublicacaoMovimentacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('publicacao_id', '=', $this->id));
        return PublicacaoMovimentacao::getObjects( $criteria );
    }
    /**
     * Method getPublicacaoProfissionals
     */
    public function getPublicacaoProfissionals()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('publicacao_id', '=', $this->id));
        return PublicacaoProfissional::getObjects( $criteria );
    }
    /**
     * Method getPublicacaoSugestaoPrazos
     */
    public function getPublicacaoSugestaoPrazos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('publicacao_id', '=', $this->id));
        return PublicacaoSugestaoPrazo::getObjects( $criteria );
    }
    /**
     * Method getTarefas
     */
    public function getTarefas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('publicacao_id', '=', $this->id));
        return Tarefa::getObjects( $criteria );
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
    
        $values = PublicacaoMovimentacao::where('publicacao_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
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
    
        $values = PublicacaoMovimentacao::where('publicacao_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = PublicacaoMovimentacao::where('publicacao_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
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
    
        $values = PublicacaoMovimentacao::where('publicacao_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = PublicacaoProfissional::where('publicacao_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
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
    
        $values = PublicacaoProfissional::where('publicacao_id', '=', $this->id)->getIndexedArray('profissional_id','{profissional->nome}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_publicacao_to_string($publicacao_sugestao_prazo_publicacao_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_publicacao_to_string))
        {
            $values = Publicacao::where('id', 'in', $publicacao_sugestao_prazo_publicacao_to_string)->getIndexedArray('id', 'id');
            $this->publicacao_sugestao_prazo_publicacao_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_publicacao_to_string = $publicacao_sugestao_prazo_publicacao_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_publicacao_to_string'] = $this->publicacao_sugestao_prazo_publicacao_to_string;
    }

    public function get_publicacao_sugestao_prazo_publicacao_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_publicacao_to_string))
        {
            return $this->publicacao_sugestao_prazo_publicacao_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('publicacao_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_config_busca_prazo_to_string($publicacao_sugestao_prazo_config_busca_prazo_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_config_busca_prazo_to_string))
        {
            $values = ConfigBuscaPrazo::where('id', 'in', $publicacao_sugestao_prazo_config_busca_prazo_to_string)->getIndexedArray('titulo', 'titulo');
            $this->publicacao_sugestao_prazo_config_busca_prazo_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_config_busca_prazo_to_string = $publicacao_sugestao_prazo_config_busca_prazo_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_config_busca_prazo_to_string'] = $this->publicacao_sugestao_prazo_config_busca_prazo_to_string;
    }

    public function get_publicacao_sugestao_prazo_config_busca_prazo_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_config_busca_prazo_to_string))
        {
            return $this->publicacao_sugestao_prazo_config_busca_prazo_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('publicacao_id', '=', $this->id)->getIndexedArray('config_busca_prazo_id','{config_busca_prazo->titulo}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_criacao_user_to_string($publicacao_sugestao_prazo_criacao_user_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_sugestao_prazo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_sugestao_prazo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_criacao_user_to_string = $publicacao_sugestao_prazo_criacao_user_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_criacao_user_to_string'] = $this->publicacao_sugestao_prazo_criacao_user_to_string;
    }

    public function get_publicacao_sugestao_prazo_criacao_user_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_criacao_user_to_string))
        {
            return $this->publicacao_sugestao_prazo_criacao_user_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('publicacao_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_publicacao_sugestao_prazo_modificacao_user_to_string($publicacao_sugestao_prazo_modificacao_user_to_string)
    {
        if(is_array($publicacao_sugestao_prazo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $publicacao_sugestao_prazo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->publicacao_sugestao_prazo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->publicacao_sugestao_prazo_modificacao_user_to_string = $publicacao_sugestao_prazo_modificacao_user_to_string;
        }

        $this->vdata['publicacao_sugestao_prazo_modificacao_user_to_string'] = $this->publicacao_sugestao_prazo_modificacao_user_to_string;
    }

    public function get_publicacao_sugestao_prazo_modificacao_user_to_string()
    {
        if(!empty($this->publicacao_sugestao_prazo_modificacao_user_to_string))
        {
            return $this->publicacao_sugestao_prazo_modificacao_user_to_string;
        }
    
        $values = PublicacaoSugestaoPrazo::where('publicacao_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
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
    
        $values = Tarefa::where('publicacao_id', '=', $this->id)->getIndexedArray('tarefa_status_id','{tarefa_status->nome}');
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
    
        $values = Tarefa::where('publicacao_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
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
    
        $values = Tarefa::where('publicacao_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = Tarefa::where('publicacao_id', '=', $this->id)->getIndexedArray('usuario_destinatario_id','{usuario_destinatario->name}');
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
    
        $values = Tarefa::where('publicacao_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = Tarefa::where('publicacao_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function get_titulo_formatado(){
        if($this->titulo){
            return str_replace(';','<br/>',$this->titulo);
        }
    }
    public function get_data_disponibilizacao_formatada(){
        if($this->data_disponibilizacao)
        {
            try
            {
                $date = new DateTime($this->data_disponibilizacao);
                $diaSemana = DateService::getDayWeek($this->data_disponibilizacao);
                $mes = DateService::getMonthName($this->data_disponibilizacao);
            
                return $diaSemana.", ".$date->format('d')." de ". $mes . " de ".$date->format('Y');
            }
            catch (Exception $e)
            {
                return $this->data_disponibilizacao;
            }
        }

        return $this->data_disponibilizacao;
    }
    public function get_ordenacao_prioritaria()
    {
        // Se não houver processo vinculado
        if (is_null($this->processo_id)) {
            return 0;
        }

        // Se houver processo, mas sem responsável
        if (is_null($this->responsavel_id)) {
            return 1;
        }

        // Caso contrário
        return 2;
    }
                    
}

