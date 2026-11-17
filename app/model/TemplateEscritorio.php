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
                                        
    const CONFIRMACAO = 'CONFIRMACAO';
    const CONFIRMADO = 'CONFIRMADO';
    const CANCELAMENTO = 'CANCELAMENTO';
    const LEMBRETE = 'LEMBRETE';
    const OUTROS = 'OUTROS';

    // assinatura do e-mail (09/11/2026): a padrao do escritorio, outra so do template ou nenhuma
    const ASSINATURA_PADRAO  = 'PADRAO';
    const ASSINATURA_OUTRA   = 'OUTRA';
    const ASSINATURA_NENHUMA = 'NENHUMA';

    const CATEGORIAS = [
        self::CONFIRMACAO  => 'Confirmação',
        self::CONFIRMADO   => 'Agendamento confirmado',
        self::CANCELAMENTO => 'Cancelamento',
        self::LEMBRETE     => 'Lembrete',
        self::OUTROS       => 'Outros',
    ];

    // origem (17/11/2026): criado na Curciol (qualquer canal, texto editavel) ou importado da Meta (texto aprovado, so canal oficial)
    const ORIGEM_CURCIOL = 'CURCIOL';
    const ORIGEM_META    = 'META';

    const ORIGENS = [
        self::ORIGEM_CURCIOL => 'Curciol',
        self::ORIGEM_META    => 'Meta',
    ];

    /** Template importado da Meta (envio pelo template aprovado no canal oficial). */
    public function ehMeta(): bool
    {
        return $this->origem === self::ORIGEM_META;
    }

    /**
     * Variaveis no padrao da Meta (17/11/2026): {{nome}}. Aceita as formas antigas e as que aparecem ao copiar de um lado para
     * o outro - {$nome}, {{$nome}}, {{ nome }} - e a forma codificada no link do editor (%7B%24nome%7D, %7B%7Bnome%7D%7D).
     */
    public static function normalizarVariaveis(?string $texto): string
    {
        $texto = (string) $texto;
        if ($texto === '' || (strpos($texto, '{') === false && stripos($texto, '%7B') === false))
        {
            return $texto;
        }
        $texto = preg_replace('/%7B%7B(?:%20)*(?:%24)?([a-z0-9_]+)(?:%20)*%7D%7D/i', '{{$1}}', $texto);
        $texto = preg_replace('/%7B%24([a-z0-9_]+)%7D/i', '{{$1}}', $texto);
        $texto = preg_replace('/\{\{\s*\$?([a-z0-9_]+)\s*\}\}/i', '{{$1}}', $texto);
        return preg_replace('/\{\$([a-z0-9_]+)\}/i', '{{$1}}', $texto);
    }

    /** Nomes das variaveis {{nome}} do texto, na ordem, sem repetir. */
    public static function variaveisDoTexto(?string $texto): array
    {
        preg_match_all('/\{\{([a-z0-9_]+)\}\}/i', self::normalizarVariaveis($texto), $m);
        return array_values(array_unique($m[1]));
    }


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
        parent::addAttribute('categoria');
        parent::addAttribute('respostas_confirmar');
        parent::addAttribute('respostas_cancelar');
        parent::addAttribute('ignorar_maiusculas');
        parent::addAttribute('template_confirmado_id');
        parent::addAttribute('template_cancelado_id');
        parent::addAttribute('readonly');
        parent::addAttribute('data_criacao');
        parent::addAttribute('criacao_user_id');
        parent::addAttribute('data_modificacao');
        parent::addAttribute('modificacao_user_id');
        parent::addAttribute('assinatura_modo');
        parent::addAttribute('assinatura_id');
        parent::addAttribute('botao_confirmar_texto');
        parent::addAttribute('botao_confirmar_cor');
        parent::addAttribute('botao_cancelar_texto');
        parent::addAttribute('botao_cancelar_cor');
        parent::addAttribute('origem');
        parent::addAttribute('meta_nome');
        parent::addAttribute('meta_idioma');
        parent::addAttribute('meta_id');
        parent::addAttribute('meta_waba_id');
        parent::addAttribute('meta_status');
        parent::addAttribute('meta_categoria');
        parent::addAttribute('meta_formato');
        parent::addAttribute('meta_componentes');
        parent::addAttribute('meta_sincronizado_em');

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

    /** Troca as {{variaveis}} (e as formas antigas) pelos valores [nome => valor]; variavel sem valor fica como esta. */
    public static function trocar(string $content, array $valores): string
    {
        $content = self::normalizarVariaveis($content);
        $pares = [];
        foreach ($valores as $nome => $valor)
        {
            $pares['{{' . $nome . '}}'] = (string) $valor;
        }
        return strtr($content, $pares);
    }

    public static function replacePessoa($pessoa, $content, $escritorio)
    {
        return self::trocar((string) $content, [
            'nome'                => $pessoa->nome,
            'escritorio'          => $escritorio->nome,
            'endereco_escritorio' => $escritorio->endereco_formatado,
            'telefone_escritorio' => $escritorio->telefone,
            'email_escritorio'    => $escritorio->email,
            'usuario'             => $pessoa->usuario,
            'senha'               => $pessoa->senha,
        ]);
    }

    /**
     * Valor de cada variavel do agendamento [nome => valor] (17/11/2026: o mesmo para o texto da Curciol e para os parametros do
     * template da Meta).
     */
    public static function valoresAgendamento($agendamento): array
    {
        $token = AgendamentoService::getToken($agendamento->id);
        $escritorio = $agendamento->agenda->escritorio;
        $data = date('d/m/Y H:i', strtotime($agendamento->dt_inicial));

        return [
            'id'                      => $agendamento->id,
            'estado'                  => $agendamento->estado_agenda->nome,
            'observacao'              => $agendamento->observacao,
            'profissional_foto'       => $agendamento->agenda->profissional->foto,
            'profissional'            => $agendamento->agenda->profissional->nome,
            'data_inicial'            => $data,
            'data_consulta'           => $data,
            'cliente'                 => $agendamento->cliente->nome,
            'agenda_nome'             => $agendamento->agenda->nome,
            // sem link de reuniao (04/11/2026): template antigo com a variavel nao manda o texto cru
            'link_atendimento_online' => '',
            // {{botoes}} so vale no template de confirmacao por e-mail (EmailMontagemService::comBotoes, antes do replace): no resto, some
            'botoes'                  => '',
            // pagina de detalhe excluida (09/11/2026)
            'link_detalhe'            => '',
            'link_cancelamento'       => "{$escritorio->url_sistema}/cancelar-agendamento-{$agendamento->id}-{$token}",
            'link_confirmacao'        => "{$escritorio->url_sistema}/confirmar-agendamento-{$agendamento->id}-{$token}",
            'escritorio'              => $escritorio->nome,
            'endereco_escritorio'     => $escritorio->endereco_formatado,
            'telefone_escritorio'     => $escritorio->telefone,
            'email_escritorio'        => $escritorio->email,
            'usuario'                 => $agendamento->cliente->usuario,
            'senha'                   => $agendamento->cliente->senha,
        ];
    }

    public static function replaceAgendamento($agendamento, $content)
    {
        $content = self::normalizarVariaveis((string) $content);

        // pagina de detalhe excluida (09/11/2026): template antigo perde o link inteiro (sem botao sem destino) e a variavel
        $content = preg_replace('#<a\b[^>]*?\bhref\s*=\s*(["\'])\s*\{\{link_detalhe\}\}\s*\1[^>]*>.*?</a>#is', '', $content);

        return self::trocar($content, self::valoresAgendamento($agendamento));
    }
                                                
}

