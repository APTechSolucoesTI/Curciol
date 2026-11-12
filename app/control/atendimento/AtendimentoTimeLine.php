<?php

class AtendimentoTimeLine extends TPage
{
    private static $database = 'escritorio';
    private static $activeRecord = 'Atendimento';
    private static $primaryKey = 'id';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null )
    {
        try
        {
            parent::__construct();

            TTransaction::open(self::$database);

            if(!empty($param['target_container']))
            {
                $this->adianti_target_container = $param['target_container'];
            }

            $this->timeline = new TTimeline;
            $this->timeline->setItemDatabase(self::$database);
            $this->timelineCriteria = new TCriteria;

            if(!empty($param['cliente_id']??''))
        {
            TSession::setValue(__CLASS__.'load_filter_cliente_id', $param['cliente_id']??'');
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_cliente_id');
            $this->timelineCriteria->add(new TFilter('cliente_id', '=', $filterVar));

            $limit = 0;

            $this->timelineCriteria->setProperty('limit', $limit);
            $this->timelineCriteria->setProperty('order', 'dt_inicio desc');

            $objects = Atendimento::getObjects($this->timelineCriteria) ?: [];

            // APChat (10/11/2026): tickets fechados do APChat na mesma linha do tempo, pela data e hora de abertura (11/11)
            foreach ($objects as $objeto) { self::prepararAtendimento($objeto); }
            // APMail (12/11/2026): conversas de e-mail do cliente, pela data e hora do primeiro e-mail
            foreach (self::conversasDoApmail((int) $filterVar) as $conversa) { $objects[] = $conversa; }
            foreach (self::ticketsDoWhatsapp((int) $filterVar) as $ticket)
            {
                $objects[] = $ticket;
            }
            usort($objects, function ($a, $b) { return strcmp((string) $b->dt_inicio, (string) $a->dt_inicio); });

            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $object->dt_inicio = call_user_func(function($value, $object, $row)
                    {
                        if(!empty(trim((string) $value)))
                        {
                            try
                            {
                                $date = new DateTime($value);
                                return $date->format('d/m/Y H:i');
                            }
                            catch (Exception $e)
                            {
                                return $value;
                            }
                        }
                    }, $object->dt_inicio, $object, null);

                    $id = $object->id;
                    $title = "Atendimento #{id}";
                    if (!empty($object->ticket_id))
                    {
                        $title = "APChat · Ticket #{ticket_id}";
                    }
                    if (!empty($object->conversa_id))
                    {
                        $title = "APMail · {assunto_html}";
                    }
                    $htmlTemplate = (!empty($object->ticket_id) || !empty($object->conversa_id)) ? "{detalhes_html}" : "Agenda: <b>{agendamento->agenda->nome}</b><br/>
Parceiro: <b>{agendamento->agendamento_procedimento_convenio_to_string} </b><br/>
Profissional: <b>{profissional->nome_formatado}</b><br/>
Escritório: <b>{agendamento->agenda->escritorio->nome}</b><br/>
Tipo: <b>{tipo_atendimento->nome}</b>";
                    $date = $object->dt_inicio;
                    $icon = 'fa:arrow-left bg-green';
                    $position = 'left';

                    if(empty($positionValue[$object->dt_inicio]))
                    {
                        $lastPosition = (empty($lastPosition) || $lastPosition == 'right') ? 'left' : 'right';
                        $bg = ($lastPosition == 'left') ? 'bg-green' : 'bg-blue';

                        $positionValue[$object->dt_inicio]['position'] = $lastPosition;
                        $positionValue[$object->dt_inicio]['bg'] = $bg;
                        $position = $positionValue[$object->dt_inicio]['position'];
                        $icon = "fa:arrow-{$lastPosition} {$bg}";
                    }
                    else
                    {
                        $position = $positionValue[$object->dt_inicio]['position'];
                        $lastPosition = $position;
                        $icon = "fa:arrow-{$lastPosition} {$positionValue[$object->dt_inicio]['bg']}";
                    }

                    $this->timeline->addItem($id, $title, $htmlTemplate, $date, $icon, $position, $object);

                }
            }

            $this->timeline->setUseBothSides();
            $this->timeline->setTimeDisplayMask('dd/mm/yyyy');
            $this->timeline->setFinalIcon( 'fas:flag-checkered #ffffff #528BB7' );

            $action_AtendimentoFormView_onShow = new TAction(['AtendimentoFormView', 'onShow'], ['key'=> '{id}']);
            $action_AtendimentoFormView_onShow->setParameter('id', '{id}');
            $action_AtendimentoFormView_onShow->setProperty('btn-class', 'btn btn-default');
            $this->timeline->addAction($action_AtendimentoFormView_onShow, "Acessar atendimento", 'fas:dot-circle #FF9800','AtendimentoTimeLine::podeExibir');

            $action_ticket = new TAction(['ApchatTicketView', 'onShow'], ['key' => '{ticket_id}', 'pessoa_id' => '{pessoa_id}']);
            $action_ticket->setProperty('btn-class', 'btn btn-default');
            $this->timeline->addAction($action_ticket, 'Ver conversa', 'fas:comments #1B4A6E', 'AtendimentoTimeLine::ehTicket');

            $action_email = new TAction(['ApmailConversaView', 'onShow'], ['key' => '{conversa_id}', 'pessoa_id' => '{pessoa_id}']);
            $action_email->setProperty('btn-class', 'btn btn-default');
            $this->timeline->addAction($action_email, 'Ver conversa', 'fas:envelope-open-text #13799C', 'AtendimentoTimeLine::ehConversaApmail');

            $container = new TVBox;

            $container->style = 'width: 100%';
            $container->class = 'form-container';
            if(empty($param['target_container']))
            {    
                $container->add(TBreadCrumb::create(["Atendimento","TimeLine de Atendimentos"]));
            }
            $container->add($this->timeline);

            TTransaction::close();

            parent::add($container);
        }
        catch(Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function ehTicket($object)
    {
        return !empty($object->ticket_id);
    }

    public static function ehConversaApmail($object)
    {
        return !empty($object->conversa_id);
    }

    /**
     * Conversas do APMail do cliente (ApmailHistoricoService, 12/11/2026) no formato da lista e da linha do tempo: dt_inicio = primeiro
     * e-mail, dt_final = ultimo; Profissional = responsavel atual; Fila = caixa. So le o banco da Curciol. Erro: nenhuma.
     */
    public static function conversasDoApmail(int $pessoaId): array
    {
        try
        {
            $conversas = $pessoaId ? ApmailHistoricoService::conversas($pessoaId) : [];
        }
        catch (Throwable $e)
        {
            return [];
        }
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $lista = [];
        foreach ($conversas as $c)
        {
            $o = new stdClass;
            $o->id = 'm' . $c['id'];
            $o->ticket_id = 0;
            $o->conversa_id = (int) $c['id'];
            $o->pessoa_id = $pessoaId;
            $o->tipo = 'APMail';
            $o->id_texto = (string) $c['id'];
            $o->dt_inicio = $c['inicio_em'];
            $o->dt_final = $c['fim_em'];
            $o->inicio_texto = self::dataHora($o->dt_inicio);
            $o->fim_texto = self::dataHora($o->dt_final);
            $o->profissional_texto = $h($c['responsavel_nome']);
            $o->especialidade_texto = '';
            $o->fila_texto = $h($c['caixa_nome']);
            $o->assunto_html = $h($c['assunto']) ?: '(sem assunto)';
            $o->atendimento_historico_historico_to_string = '';
            $n = (int) $c['qtd_mensagens'];
            $o->detalhes_html = implode('<br/>', array_filter([
                trim((string) $c['caixa_nome']) !== '' ? 'Caixa: <b>' . $h($c['caixa_nome']) . '</b>' : '',
                trim((string) $c['responsavel_nome']) !== '' ? 'Responsável: <b>' . $h($c['responsavel_nome']) . '</b>' : '',
                $n . ($n === 1 ? ' e-mail' : ' e-mails'),
                'Início: <b>' . $o->inicio_texto . '</b> · Fim: <b>' . $o->fim_texto . '</b>',
            ], 'strlen'));
            $lista[] = $o;
        }
        return $lista;
    }

    /**
     * Tickets fechados do APChat do cliente (ApchatHistoricoService) no formato da lista e da linha do tempo: dt_inicio = abertura,
     * dt_final = encerramento (11/11/2026). Usa a transacao aberta. APChat fora: nenhum (a aba continua com os atendimentos).
     */
    public static function ticketsDoWhatsapp(int $pessoaId): array
    {
        try
        {
            if (!$pessoaId || !ApchatBancoService::configurado() || !ApchatBancoService::disponivel()) { return []; }
            $tickets = ApchatHistoricoService::tickets($pessoaId);
        }
        catch (Throwable $e)
        {
            return [];
        }
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $lista = [];
        foreach ($tickets as $t)
        {
            $o = new stdClass;
            $o->id = 'w' . $t['id'];
            $o->ticket_id = $t['id'];
            $o->conversa_id = 0;
            $o->pessoa_id = $pessoaId;
            $o->tipo = 'APChat';
            $o->dt_inicio = $t['aberto_em'] ?: $t['fechado_em'];
            $o->dt_final = $t['fechado_em'];
            $o->inicio_texto = self::dataHora($o->dt_inicio);
            $o->fim_texto = self::dataHora($o->dt_final);
            $o->profissional_texto = $h($t['atribuido']);
            $o->id_texto = (string) $t['id'];
            $o->especialidade_texto = ''; // ticket do APChat nao tem especialidade (11/11/2026)
            $o->fila_texto = $h($t['fila']);
            $o->atendimento_historico_historico_to_string = ''; // na lista so o atribuido; todos aparecem ao abrir o ticket
            // linha do tempo: so as linhas com valor (sem traco)
            $o->detalhes_html = implode('<br/>', array_filter([
                $t['canal'] !== '' ? 'Canal: <b>' . $h($t['canal']) . '</b>' : '',
                $t['fila'] !== '' ? 'Fila: <b>' . $h($t['fila']) . '</b>' : '',
                $t['profissionais'] ? 'Profissional: <b>' . $h(implode(', ', $t['profissionais'])) . '</b>' : '',
                'Início: <b>' . $o->inicio_texto . '</b> · Fim: <b>' . $o->fim_texto . '</b>',
            ], 'strlen'));
            $lista[] = $o;
        }
        return $lista;
    }

    /**
     * Atendimento da Curciol com os mesmos campos de tela dos tickets do APChat (11/11/2026): ticket_id = 0 (a acao "Ver conversa"
     * da lista le o campo antes de ver se aparece - sem ele dava "Campo ticket_id nao existe"); especialidade do agendamento,
     * senao a do profissional (atendimento avulso).
     */
    public static function prepararAtendimento($a)
    {
        $a->ticket_id = 0;
        $a->conversa_id = 0;
        $a->pessoa_id = $a->cliente_id;
        $a->tipo = 'Atendimento';
        $a->id_texto = (string) $a->id;
        $a->inicio_texto = self::dataHora($a->dt_inicio);
        $a->fim_texto = self::dataHora($a->dt_final);
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $profissional = $a->profissional_id ? $a->profissional : null;
        $a->profissional_texto = $profissional ? $h($profissional->nome) : '';
        $agendamento = $a->agendamento_id ? $a->agendamento : null;
        $especialidade = ($agendamento && $agendamento->especialidade_id) ? (string) $agendamento->especialidade->descricao : '';
        if ($especialidade === '') { $especialidade = ApchatHistoricoService::especialidadesDoProfissional($a->profissional_id ? (int) $a->profissional_id : null); }
        $a->especialidade_texto = $h($especialidade);
        $a->fila_texto = '';
        return $a;
    }

    public static function dataHora($valor): string
    {
        return trim((string) $valor) !== '' ? date('d/m/Y H:i', strtotime((string) $valor)) : '';
    }

    public static function podeExibir($object)
    {
        if (!empty($object->ticket_id) || !empty($object->conversa_id))
        {
            return false;
        }
        try 
        {
            TTransaction::open(self::$database);
            if($object->tipo_atendimento_id == TipoAtendimento::AGENDADO){
                return AtendimentoService::podeManipular($object, TSession::getValue('userid'));
            }elseif ($object->tipo_atendimento_id == TipoAtendimento::AVULSO){
                return true;
            }
            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {

    } 

}

