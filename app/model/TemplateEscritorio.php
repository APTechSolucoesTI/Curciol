<?php

class TemplateEscritorio extends TRecord
{
    const TABLENAME  = 'template_escritorio';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    const CREATEDAT  = 'data_criacao';
    const UPDATEDAT  = 'data_modificacao';

    private Escritorio $escritorio;
    private SystemUsers $criacao_user;
    private SystemUsers $modificacao_user;

    const EMAIL = 'EMAIL';
    const WHATSAPP = 'WHATSAPP';
                                        

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('escritorio_id');
        parent::addAttribute('chave');
        parent::addAttribute('descricao');
        parent::addAttribute('habilitado');
        parent::addAttribute('template');
        parent::addAttribute('titulo');
        parent::addAttribute('tipo_template');
        parent::addAttribute('readonly');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
    
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
     * Method getMensagems
     */
    public function getMensagems()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('template_escritorio_id', '=', $this->id));
        return Mensagem::getObjects( $criteria );
    }
    /**
     * Method getTemplateAcaos
     */
    public function getTemplateAcaos()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('template_escritorio_id', '=', $this->id));
        return TemplateAcao::getObjects( $criteria );
    }

    public function set_mensagem_agendamento_to_string($mensagem_agendamento_to_string)
    {
        if(is_array($mensagem_agendamento_to_string))
        {
            $values = Agendamento::where('id', 'in', $mensagem_agendamento_to_string)->getIndexedArray('id', 'id');
            $this->mensagem_agendamento_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_agendamento_to_string = $mensagem_agendamento_to_string;
        }

        $this->vdata['mensagem_agendamento_to_string'] = $this->mensagem_agendamento_to_string;
    }

    public function get_mensagem_agendamento_to_string()
    {
        if(!empty($this->mensagem_agendamento_to_string))
        {
            return $this->mensagem_agendamento_to_string;
        }
    
        $values = Mensagem::where('template_escritorio_id', '=', $this->id)->getIndexedArray('agendamento_id','{agendamento->id}');
        return implode(', ', $values);
    }

    public function set_mensagem_template_escritorio_to_string($mensagem_template_escritorio_to_string)
    {
        if(is_array($mensagem_template_escritorio_to_string))
        {
            $values = TemplateEscritorio::where('id', 'in', $mensagem_template_escritorio_to_string)->getIndexedArray('chave', 'chave');
            $this->mensagem_template_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_template_escritorio_to_string = $mensagem_template_escritorio_to_string;
        }

        $this->vdata['mensagem_template_escritorio_to_string'] = $this->mensagem_template_escritorio_to_string;
    }

    public function get_mensagem_template_escritorio_to_string()
    {
        if(!empty($this->mensagem_template_escritorio_to_string))
        {
            return $this->mensagem_template_escritorio_to_string;
        }
    
        $values = Mensagem::where('template_escritorio_id', '=', $this->id)->getIndexedArray('template_escritorio_id','{template_escritorio->chave}');
        return implode(', ', $values);
    }

    public function set_mensagem_system_user_to_string($mensagem_system_user_to_string)
    {
        if(is_array($mensagem_system_user_to_string))
        {
            $values = SystemUsers::where('id', 'in', $mensagem_system_user_to_string)->getIndexedArray('name', 'name');
            $this->mensagem_system_user_to_string = implode(', ', $values);
        }
        else
        {
            $this->mensagem_system_user_to_string = $mensagem_system_user_to_string;
        }

        $this->vdata['mensagem_system_user_to_string'] = $this->mensagem_system_user_to_string;
    }

    public function get_mensagem_system_user_to_string()
    {
        if(!empty($this->mensagem_system_user_to_string))
        {
            return $this->mensagem_system_user_to_string;
        }
    
        $values = Mensagem::where('template_escritorio_id', '=', $this->id)->getIndexedArray('system_user_id','{system_user->name}');
        return implode(', ', $values);
    }

    public function set_template_acao_template_escritorio_to_string($template_acao_template_escritorio_to_string)
    {
        if(is_array($template_acao_template_escritorio_to_string))
        {
            $values = TemplateEscritorio::where('id', 'in', $template_acao_template_escritorio_to_string)->getIndexedArray('chave', 'chave');
            $this->template_acao_template_escritorio_to_string = implode(', ', $values);
        }
        else
        {
            $this->template_acao_template_escritorio_to_string = $template_acao_template_escritorio_to_string;
        }

        $this->vdata['template_acao_template_escritorio_to_string'] = $this->template_acao_template_escritorio_to_string;
    }

    public function get_template_acao_template_escritorio_to_string()
    {
        if(!empty($this->template_acao_template_escritorio_to_string))
        {
            return $this->template_acao_template_escritorio_to_string;
        }
    
        $values = TemplateAcao::where('template_escritorio_id', '=', $this->id)->getIndexedArray('template_escritorio_id','{template_escritorio->chave}');
        return implode(', ', $values);
    }

    public static function getTemplate($chave, $unitId)
    {
        $escritorio = Escritorio::findByUnitId($unitId);
    
        return self::where('chave', '=', $chave)->where('escritorio_id', '=', $escritorio->id)->first();
    }

    public function parserTitulo($agendamento)
    {
        return self::replace($agendamento, $this->titulo);
    }

    public function parserTemplate($agendamento)
    {
        return self::replace($agendamento, $this->template);
    }

    public static function replace($object, $content, $escritorio = null)
    {
        if ($object instanceof Agendamento)
        {
            return self::replaceAgendamento($object, $content);
        }
    
        return self::replacePessoa($object, $content, $escritorio);
    }

    public static function replacePessoa($pessoa, $content, $escritorio)
    {
        $content = str_replace('{$nome}', $pessoa->nome, $content);
        $content = str_replace('{$escritorio}', $escritorio->nome, $content);
        $content = str_replace('{$endereco_escritorio}', $escritorio->endereco_formatado, $content);
        $content = str_replace('{$telefone_escritorio}', $escritorio->telefone, $content);
        $content = str_replace('{$email_escritorio}', $escritorio->email, $content);
        $content = str_replace('{$usuario}', $pessoa->usuario , $content);
        $content = str_replace('{$senha}', $pessoa->senha , $content);
    
        return $content;
    }

    public static function replaceAgendamento($agendamento, $content)
    {
        $token = AgendamentoService::getToken($agendamento->id);
    
        $content = str_replace('{$id}', $agendamento->id, $content);
        $content = str_replace('{$estado}', $agendamento->estado_agenda->nome, $content);
        $content = str_replace('{$observacao}', $agendamento->observacao, $content);
    
        $content = str_replace('{$profissional_foto}', $agendamento->agenda->profissional->foto, $content);
        $content = str_replace('{$profissional}', $agendamento->agenda->profissional->nome, $content);
        $content = str_replace('{$data_inicial}', date('d/m/Y H:i', strtotime($agendamento->dt_inicial)), $content);
        $content = str_replace('{$data_consulta}', date('d/m/Y H:i', strtotime($agendamento->dt_inicial)), $content);
    
        $content = str_replace('{$cliente}', $agendamento->cliente->nome, $content);
        $content = str_replace('{$agenda_nome}', $agendamento->agenda->nome, $content);
    
        if ($agendamento->link_atendimento_online)
        {
            $content = str_replace('{$link_atendimento_online}', "{$agendamento->agenda->escritorio->url_sistema}/atendimento/{$agendamento->link_atendimento_online}" , $content);
        }
    
        $content = str_replace('{$link_detalhe}', "{$agendamento->agenda->escritorio->url_sistema}/agendamento-{$agendamento->id}-{$token}", $content);
        $content = str_replace('{$link_cancelamento}', "{$agendamento->agenda->escritorio->url_sistema}/cancelar-agendamento-{$agendamento->id}-{$token}", $content);
        $content = str_replace('{$link_confirmacao}', "{$agendamento->agenda->escritorio->url_sistema}/confirmar-agendamento-{$agendamento->id}-{$token}", $content);
    
    
        $content = str_replace('{$escritorio}', $agendamento->agenda->escritorio->nome, $content);
        $content = str_replace('{$endereco_escritorio}', $agendamento->agenda->escritorio->endereco_formatado, $content);
        $content = str_replace('{$telefone_escritorio}', $agendamento->agenda->escritorio->telefone, $content);
        $content = str_replace('{$email_escritorio}', $agendamento->agenda->escritorio->email, $content);
    
    
        $content = str_replace('{$usuario}', $agendamento->cliente->usuario , $content);
        $content = str_replace('{$senha}', $agendamento->cliente->senha , $content);
    
        return $content;
    }
                                                
}

