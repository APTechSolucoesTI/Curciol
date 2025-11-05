<?php

class Tarefa extends TRecord
{
    const TABLENAME  = 'tarefa';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATED_BY_USER_ID  = 'criacao_user_id';
    const UPDATED_BY_USER_ID  = 'modificacao_user_id';

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Processo $processo;
    private TarefaStatus $tarefa_status;
    private Publicacao $publicacao;
    private SystemUsers $usuario_destinatario;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    use SystemChangeLogTrait;
    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('tarefa_status_id');
        parent::addAttribute('publicacao_id');
        parent::addAttribute('processo_id');
        parent::addAttribute('usuario_destinatario_id');
        parent::addAttribute('titulo');
        parent::addAttribute('data_disponibilizacao');
        parent::addAttribute('prazo_validacao');
        parent::addAttribute('prazo_entrega');
        parent::addAttribute('observacao');
        parent::addAttribute('data_entrega');
        parent::addAttribute('arquivado');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
        parent::addAttribute('prazo_processual');
    

        $this->processo = new Processo();

                                    
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
     * Method set_tarefa_status
     * Sample of usage: $var->tarefa_status = $object;
     * @param $object Instance of TarefaStatus
     */
    public function set_tarefa_status(TarefaStatus $object)
    {
        $this->tarefa_status = $object;
        $this->tarefa_status_id = $object->id;
    }

    /**
     * Method get_tarefa_status
     * Sample of usage: $var->tarefa_status->attribute;
     * @returns TarefaStatus instance
     */
    public function get_tarefa_status()
    {
    
        // loads the associated object
        if (empty($this->tarefa_status))
            $this->tarefa_status = new TarefaStatus($this->tarefa_status_id);
    
        // returns the associated object
        return $this->tarefa_status;
    }
    /**
     * Method set_publicacao
     * Sample of usage: $var->publicacao = $object;
     * @param $object Instance of Publicacao
     */
    public function set_publicacao(Publicacao $object)
    {
        $this->publicacao = $object;
        $this->publicacao_id = $object->id;
    }

    /**
     * Method get_publicacao
     * Sample of usage: $var->publicacao->attribute;
     * @returns Publicacao instance
     */
    public function get_publicacao()
    {
    
        // loads the associated object
        if (empty($this->publicacao))
            $this->publicacao = new Publicacao($this->publicacao_id);
    
        // returns the associated object
        return $this->publicacao;
    }
    /**
     * Method set_system_users
     * Sample of usage: $var->system_users = $object;
     * @param $object Instance of SystemUsers
     */
    public function set_usuario_destinatario(SystemUsers $object)
    {
        $this->usuario_destinatario = $object;
        $this->usuario_destinatario_id = $object->id;
    }

    /**
     * Method get_usuario_destinatario
     * Sample of usage: $var->usuario_destinatario->attribute;
     * @returns SystemUsers instance
     */
    public function get_usuario_destinatario()
    {
    
        // loads the associated object
        if (empty($this->usuario_destinatario))
            $this->usuario_destinatario = new SystemUsers($this->usuario_destinatario_id);
    
        // returns the associated object
        return $this->usuario_destinatario;
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
     * Method getPublicacaoMovimentacaos
     */
    public function getPublicacaoMovimentacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tarefa_id', '=', $this->id));
        return PublicacaoMovimentacao::getObjects( $criteria );
    }
    /**
     * Method getTarefaClientes
     */
    public function getTarefaClientes()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tarefa_id', '=', $this->id));
        return TarefaCliente::getObjects( $criteria );
    }
    /**
     * Method getTarefaComentarios
     */
    public function getTarefaComentarios()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tarefa_id', '=', $this->id));
        return TarefaComentario::getObjects( $criteria );
    }
    /**
     * Method getTarefaHorasTrabalhadass
     */
    public function getTarefaHorasTrabalhadass()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tarefa_id', '=', $this->id));
        return TarefaHorasTrabalhadas::getObjects( $criteria );
    }
    /**
     * Method getTarefaMovimentacaos
     */
    public function getTarefaMovimentacaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tarefa_id', '=', $this->id));
        return TarefaMovimentacao::getObjects( $criteria );
    }
    /**
     * Method getTarefaVinculos
     */
    public function getTarefaVinculosByTarefas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('tarefa_id', '=', $this->id));
        return TarefaVinculo::getObjects( $criteria );
    }
    /**
     * Method getTarefaVinculos
     */
    public function getTarefaVinculosBySubtarefas()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('subtarefa_id', '=', $this->id));
        return TarefaVinculo::getObjects( $criteria );
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
    
        $values = PublicacaoMovimentacao::where('tarefa_id', '=', $this->id)->getIndexedArray('publicacao_id','{publicacao->id}');
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
    
        $values = PublicacaoMovimentacao::where('tarefa_id', '=', $this->id)->getIndexedArray('processo_id','{processo->numero_cnj_numero}');
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
    
        $values = PublicacaoMovimentacao::where('tarefa_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
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
    
        $values = PublicacaoMovimentacao::where('tarefa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
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
    
        $values = TarefaCliente::where('tarefa_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
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
    
        $values = TarefaCliente::where('tarefa_id', '=', $this->id)->getIndexedArray('cliente_id','{cliente->nome}');
        return implode(', ', $values);
    }

    public function set_tarefa_comentario_tarefa_to_string($tarefa_comentario_tarefa_to_string)
    {
        if(is_array($tarefa_comentario_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_comentario_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_comentario_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_comentario_tarefa_to_string = $tarefa_comentario_tarefa_to_string;
        }

        $this->vdata['tarefa_comentario_tarefa_to_string'] = $this->tarefa_comentario_tarefa_to_string;
    }

    public function get_tarefa_comentario_tarefa_to_string()
    {
        if(!empty($this->tarefa_comentario_tarefa_to_string))
        {
            return $this->tarefa_comentario_tarefa_to_string;
        }
    
        $values = TarefaComentario::where('tarefa_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_comentario_criacao_user_to_string($tarefa_comentario_criacao_user_to_string)
    {
        if(is_array($tarefa_comentario_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_comentario_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_comentario_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_comentario_criacao_user_to_string = $tarefa_comentario_criacao_user_to_string;
        }

        $this->vdata['tarefa_comentario_criacao_user_to_string'] = $this->tarefa_comentario_criacao_user_to_string;
    }

    public function get_tarefa_comentario_criacao_user_to_string()
    {
        if(!empty($this->tarefa_comentario_criacao_user_to_string))
        {
            return $this->tarefa_comentario_criacao_user_to_string;
        }
    
        $values = TarefaComentario::where('tarefa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_comentario_modificacao_user_to_string($tarefa_comentario_modificacao_user_to_string)
    {
        if(is_array($tarefa_comentario_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_comentario_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_comentario_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_comentario_modificacao_user_to_string = $tarefa_comentario_modificacao_user_to_string;
        }

        $this->vdata['tarefa_comentario_modificacao_user_to_string'] = $this->tarefa_comentario_modificacao_user_to_string;
    }

    public function get_tarefa_comentario_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_comentario_modificacao_user_to_string))
        {
            return $this->tarefa_comentario_modificacao_user_to_string;
        }
    
        $values = TarefaComentario::where('tarefa_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_horas_trabalhadas_tarefa_to_string($tarefa_horas_trabalhadas_tarefa_to_string)
    {
        if(is_array($tarefa_horas_trabalhadas_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_horas_trabalhadas_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_horas_trabalhadas_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_horas_trabalhadas_tarefa_to_string = $tarefa_horas_trabalhadas_tarefa_to_string;
        }

        $this->vdata['tarefa_horas_trabalhadas_tarefa_to_string'] = $this->tarefa_horas_trabalhadas_tarefa_to_string;
    }

    public function get_tarefa_horas_trabalhadas_tarefa_to_string()
    {
        if(!empty($this->tarefa_horas_trabalhadas_tarefa_to_string))
        {
            return $this->tarefa_horas_trabalhadas_tarefa_to_string;
        }
    
        $values = TarefaHorasTrabalhadas::where('tarefa_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_horas_trabalhadas_criacao_user_to_string($tarefa_horas_trabalhadas_criacao_user_to_string)
    {
        if(is_array($tarefa_horas_trabalhadas_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_horas_trabalhadas_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_horas_trabalhadas_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_horas_trabalhadas_criacao_user_to_string = $tarefa_horas_trabalhadas_criacao_user_to_string;
        }

        $this->vdata['tarefa_horas_trabalhadas_criacao_user_to_string'] = $this->tarefa_horas_trabalhadas_criacao_user_to_string;
    }

    public function get_tarefa_horas_trabalhadas_criacao_user_to_string()
    {
        if(!empty($this->tarefa_horas_trabalhadas_criacao_user_to_string))
        {
            return $this->tarefa_horas_trabalhadas_criacao_user_to_string;
        }
    
        $values = TarefaHorasTrabalhadas::where('tarefa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_movimentacao_tarefa_to_string($tarefa_movimentacao_tarefa_to_string)
    {
        if(is_array($tarefa_movimentacao_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_movimentacao_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_movimentacao_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_movimentacao_tarefa_to_string = $tarefa_movimentacao_tarefa_to_string;
        }

        $this->vdata['tarefa_movimentacao_tarefa_to_string'] = $this->tarefa_movimentacao_tarefa_to_string;
    }

    public function get_tarefa_movimentacao_tarefa_to_string()
    {
        if(!empty($this->tarefa_movimentacao_tarefa_to_string))
        {
            return $this->tarefa_movimentacao_tarefa_to_string;
        }
    
        $values = TarefaMovimentacao::where('tarefa_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_movimentacao_criacao_user_to_string($tarefa_movimentacao_criacao_user_to_string)
    {
        if(is_array($tarefa_movimentacao_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_movimentacao_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_movimentacao_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_movimentacao_criacao_user_to_string = $tarefa_movimentacao_criacao_user_to_string;
        }

        $this->vdata['tarefa_movimentacao_criacao_user_to_string'] = $this->tarefa_movimentacao_criacao_user_to_string;
    }

    public function get_tarefa_movimentacao_criacao_user_to_string()
    {
        if(!empty($this->tarefa_movimentacao_criacao_user_to_string))
        {
            return $this->tarefa_movimentacao_criacao_user_to_string;
        }
    
        $values = TarefaMovimentacao::where('tarefa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_movimentacao_modificacao_user_to_string($tarefa_movimentacao_modificacao_user_to_string)
    {
        if(is_array($tarefa_movimentacao_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_movimentacao_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_movimentacao_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_movimentacao_modificacao_user_to_string = $tarefa_movimentacao_modificacao_user_to_string;
        }

        $this->vdata['tarefa_movimentacao_modificacao_user_to_string'] = $this->tarefa_movimentacao_modificacao_user_to_string;
    }

    public function get_tarefa_movimentacao_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_movimentacao_modificacao_user_to_string))
        {
            return $this->tarefa_movimentacao_modificacao_user_to_string;
        }
    
        $values = TarefaMovimentacao::where('tarefa_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_vinculo_tarefa_to_string($tarefa_vinculo_tarefa_to_string)
    {
        if(is_array($tarefa_vinculo_tarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_vinculo_tarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_vinculo_tarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_vinculo_tarefa_to_string = $tarefa_vinculo_tarefa_to_string;
        }

        $this->vdata['tarefa_vinculo_tarefa_to_string'] = $this->tarefa_vinculo_tarefa_to_string;
    }

    public function get_tarefa_vinculo_tarefa_to_string()
    {
        if(!empty($this->tarefa_vinculo_tarefa_to_string))
        {
            return $this->tarefa_vinculo_tarefa_to_string;
        }
    
        $values = TarefaVinculo::where('subtarefa_id', '=', $this->id)->getIndexedArray('tarefa_id','{tarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_vinculo_subtarefa_to_string($tarefa_vinculo_subtarefa_to_string)
    {
        if(is_array($tarefa_vinculo_subtarefa_to_string))
        {
            $values = Tarefa::where('id', 'in', $tarefa_vinculo_subtarefa_to_string)->getIndexedArray('titulo', 'titulo');
            $this->tarefa_vinculo_subtarefa_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_vinculo_subtarefa_to_string = $tarefa_vinculo_subtarefa_to_string;
        }

        $this->vdata['tarefa_vinculo_subtarefa_to_string'] = $this->tarefa_vinculo_subtarefa_to_string;
    }

    public function get_tarefa_vinculo_subtarefa_to_string()
    {
        if(!empty($this->tarefa_vinculo_subtarefa_to_string))
        {
            return $this->tarefa_vinculo_subtarefa_to_string;
        }
    
        $values = TarefaVinculo::where('subtarefa_id', '=', $this->id)->getIndexedArray('subtarefa_id','{subtarefa->titulo}');
        return implode(', ', $values);
    }

    public function set_tarefa_vinculo_criacao_user_to_string($tarefa_vinculo_criacao_user_to_string)
    {
        if(is_array($tarefa_vinculo_criacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_vinculo_criacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_vinculo_criacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_vinculo_criacao_user_to_string = $tarefa_vinculo_criacao_user_to_string;
        }

        $this->vdata['tarefa_vinculo_criacao_user_to_string'] = $this->tarefa_vinculo_criacao_user_to_string;
    }

    public function get_tarefa_vinculo_criacao_user_to_string()
    {
        if(!empty($this->tarefa_vinculo_criacao_user_to_string))
        {
            return $this->tarefa_vinculo_criacao_user_to_string;
        }
    
        $values = TarefaVinculo::where('subtarefa_id', '=', $this->id)->getIndexedArray('criacao_user_id','{criacao_user->name}');
        return implode(', ', $values);
    }

    public function set_tarefa_vinculo_modificacao_user_to_string($tarefa_vinculo_modificacao_user_to_string)
    {
        if(is_array($tarefa_vinculo_modificacao_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $tarefa_vinculo_modificacao_user_to_string)->getIndexedArray('name', 'name');
            $this->tarefa_vinculo_modificacao_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->tarefa_vinculo_modificacao_user_to_string = $tarefa_vinculo_modificacao_user_to_string;
        }

        $this->vdata['tarefa_vinculo_modificacao_user_to_string'] = $this->tarefa_vinculo_modificacao_user_to_string;
    }

    public function get_tarefa_vinculo_modificacao_user_to_string()
    {
        if(!empty($this->tarefa_vinculo_modificacao_user_to_string))
        {
            return $this->tarefa_vinculo_modificacao_user_to_string;
        }
    
        $values = TarefaVinculo::where('subtarefa_id', '=', $this->id)->getIndexedArray('modificacao_user_id','{modificacao_user->name}');
        return implode(', ', $values);
    }

    public function get_cliente_vinculado()
    {
        if(TarefaCliente::where('tarefa_id','=',$this->id)->count() > 0){
            $pessoa = Pessoa::where('id','in', "(SELECT cliente_id FROM tarefa_cliente WHERE tarefa_id = $this->id)")->first();
        }elseif($this->processo_id){
            $processo = Processo::find($this->processo_id);
            if($processo->resposavel_id){
                $pessoa = Pessoa::where('id','in', "(SELECT id FROM pessoa WHERE id in (SELECT cliente_id FROM contrato_pessoa WHERE contrato_id in 
                                                        (SELECT contrato_id FROM processo_contrato WHERE processo_id = $this->processo_id)))")->first();
            }
        }elseif($this->publicacao_id){
            $publicacao = Publicacao::find($this->publicacao_id);
            if($publicacao->processo_id){
                $processo = Processo::find($publicacao->processo_id);
                if($processo->resposavel_id){
                    $pessoa = Pessoa::where('id','in', "(SELECT id FROM pessoa WHERE id in (SELECT cliente_id FROM contrato_pessoa WHERE contrato_id in 
                                                        (SELECT contrato_id FROM contrato_processo WHERE processo_id = 
                                                        (SELECT processo_id FROM publicacao WHERE id = $this->publicacao_id))))")->first();
                }
            }                                                
        }
        return $pessoa->nome ?? null;
    }

    public function get_numero_processo() {
        if($this->processo_id){
            $processo = Processo::find($this->processo_id);
            if($processo){
                return $processo->numero_cnj_numero ?? $processo->numero_outro;
            }
        }elseif($this->publicacao_id){
            $publicacao = Publicacao::find($this->publicacao_id);
            if($publicacao){
                return $publicacao->processo->numero_cnj_numero ?? $publicacao->processo->numero_outro;
            }
        }
        return null;
    }

    public function alterarStatusTarefa($status_id){
        try{
        
            $proxStatus = TarefaStatus::find((int) $status_id);
            $atualStatus = TarefaStatus::find((int) $this->tarefa_status_id);
        
            if(!$proxStatus){
                throw new Exception("Status inválido!");
            }

            $configuracao          = TarefaConfiguracao::find(1);
            $status_final_id       = $configuracao->status_final_id;
            $status_final_nome     = $configuracao->status_final->nome;
            $status_cancelado_id   = $configuracao->status_cancelado_id;

            //Buscar as subtarefas desta tarefa
            $subtarefas = TarefaVinculo::where('tarefa_id','=',$this->id)->load();

            if($subtarefas){
                $subtarefasFinalizadas = 0;
                foreach($subtarefas as $vinculo){
                    $subtarefa = Tarefa::find($vinculo->subtarefa_id);
                    if($subtarefa->tarefa_status_id == $configuracao->status_final_id){
                        $subtarefasFinalizadas++;
                    }
                }
                if($subtarefasFinalizadas != count($subtarefas) && $proxStatus->id==$status_final_id){
                    TApplication::loadPage('TarefaKanbanHeader', 'onShow');
                    throw new Exception("Não é possível alterar o status para $status_final_nome até que todas as subtarefas sejam alteradas para $status_final_nome.");
                }
            }
        
            if($this->arquivado == "S"){
                throw new Exception("Não é possível alterar o status. Desarquive a tarefa e tente novamente.");
            }
        
            if($atualStatus->id == $status_cancelado_id){
                throw new Exception("Não é possível alterar o status de uma tarefa cancelada.");
            }

            $movimentacao = new TarefaMovimentacao();
            $movimentacao->tarefa_id = $this->id;
            $movimentacao->descricao = "Status alterado de ".$atualStatus->nome." para ".$proxStatus->nome. ".";
            $movimentacao->data_movimentacao = date('Y-m-d H:i:s');
            $movimentacao->store();
        
            if($proxStatus->id == $status_final_id){
                $this->data_entrega = date('Y-m-d H:i:s');
            }

            $this->tarefa_status_id = $proxStatus->id;
            if($proxStatus->fim == 'S'){
                $this->arquivado = "S";
            }
        
            if($proxStatus->id == $status_cancelado_id){
                $this->data_entrega = null;
            }
        
            $this->store();
            TScript::create("$(\"div[item_id='{$this->id}']\").css('border-top', '3px solid {$atualStatus->cor}');");

            $user_id = $this->criacao_user_id; // id do usuário que receberá a notificação
            $notificationParam = ['key'=>$this->id];
            $icon = 'fas fa-check';

            if($proxStatus->id == $status_final_id){
                SystemNotification::register(
                    $user_id, 'Tarefa finalizada', "A tarefa #$this->id atingiu o status final.", 
                    new TAction(['TarefaFormView', 'onShow'], $notificationParam), 'Ver tarefa', $icon);
        
            }elseif($proxStatus->id == $status_cancelado_id){
                SystemNotification::register(
                    $user_id, 'Tarefa cancelada', "A tarefa #$this->id atingiu o status de cancelamento.", 
                    new TAction(['TarefaFormView', 'onShow'], $notificationParam), 'Ver tarefa', $icon);
            
            }else{
                SystemNotification::register(
                    $user_id, 'Movimentação de tarefa', "Tarefa #$this->id. $movimentacao->descricao", 
                    new TAction(['TarefaFormView', 'onShow'], $notificationParam), 'Ver tarefa', $icon);
            }
            return;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }
                                                    
}

