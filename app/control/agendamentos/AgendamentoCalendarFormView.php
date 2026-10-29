<?php
/**
 * Calendario da agenda (tela Agendamentos > Agendamentos, dentro de AgendamentosFilterForm).
 * Curciol (29/10/2026): visual repaginado (AgendaVisual): horarios na cor da agenda (agendamento com fundo claro,
 * compromisso tracejado, bloqueio listrado), altura natural (rola com a pagina); filtros e legenda em AgendamentosFilterForm.
 * Filtros (sessao agendamentos_filter_data): varios clientes, varias agendas e periodo (inicio e fim).
 */
class AgendamentoCalendarFormView extends TPage
{
    private $fc;

    /**
     * Page constructor
     */
    public function __construct($param = null)
    {
        parent::__construct();

        if (!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TSession::setValue(__CLASS__.'load_filter_agenda_id', null);
        TSession::setValue(__CLASS__.'load_filter_cliente_id', null);

        $this->fc = new TFullCalendar(date('Y-m-d'), 'month');
        $this->fc->enableDays([0,1,2,3,4,5,6]);
        $this->fc->setReloadAction(new TAction(array($this, 'getEvents'), $param));
        $this->fc->setCurrentView('agendaWeek');
        $this->fc->setTimeRange('08:00', '23:00');
        $this->fc->enablePopover('', "{detalhe_html}");
        $this->fc->setOption('slotTime', "00:30:00");
        $this->fc->setOption('slotDuration', "00:30:00");
        $this->fc->setOption('slotLabelInterval', '01:00:00');
        $this->fc->setOption('nowIndicator', true);
        // semana de domingo a sabado; Mes e Agenda (lista) comecam no dia de hoje (29/10/2026)
        $this->fc->setOption('firstDay', 0);
        $this->fc->setOption('views', [
            'mesHoje'    => ['type' => 'dayGrid', 'duration' => ['weeks' => 5], 'dateAlignment' => 'day', 'dateIncrement' => ['months' => 1], 'buttonText' => 'Mês'],
            'agendaHoje' => ['type' => 'list', 'duration' => ['weeks' => 1], 'dateAlignment' => 'day', 'buttonText' => 'Agenda'],
        ]);
        $this->fc->setOption('headerToolbar', ['left' => 'prev,next today', 'center' => 'title', 'right' => 'timeGridDay,timeGridWeek,mesHoje,agendaHoje']);

        $agendas_id = TSession::getValue('agendamentos_filter_agenda_id');

        // altura natural: a agenda rola junto com a pagina (antes tinha rolagem propria)
        $this->fc->setOption('height', 'auto');
        // cabecalho dos dias rola junto (fixo ele passava por cima do titulo ao rolar); cartoes lado a lado, sem cascata
        $this->fc->setOption('stickyHeaderDates', false);
        $this->fc->setOption('slotEventOverlap', false);
        $this->fc->setOption('eventMinHeight', 28);
        $this->fc->setEventClickAction(new TAction(array($this, 'onClick')));
        $this->fc->id = 'agendamentos';

        TTransaction::open('escritorio');
        if ($agendas_id)
        {
            foreach($agendas_id as  $key=>$agenda_id){

                $agenda = Agenda::find($agenda_id);
                if (!$agenda) { continue; }

                $horario_inicial[$key] =   $agenda->horario_inicial;
                $strohra_inicial[$key] = strtotime($horario_inicial[$key]);
                $horario_final[$key] =   $agenda->horario_final;
                $strohra_final[$key] = strtotime($horario_final[$key]);

                // a agenda sempre abre na semana (29/10/2026); linhas de no maximo 30 minutos (com uma agenda so e
                // horario menor, o dela), para o agendamento curto caber no bloco
                if(count($agendas_id)>1){
                    $duracao=30;
                }else{
                    $duracao = min(30, max(5, (int) $agenda->duracao ?: 30));
                }
            }

            if (!empty($strohra_inicial))
            {
                $keyInicial = array_search((min($strohra_inicial)), $strohra_inicial);
                $keyFinal = array_search((max($strohra_final)), $strohra_final);

                $this->fc->setTimeRange($horario_inicial[$keyInicial], $horario_final[$keyFinal]);

                $time = date('H:i:s', strtotime("2000-01-01 +{$duracao} minutes"));

                $this->fc->setOption('slotTime', $time);
                $this->fc->setOption('slotDuration', $time);
            }
        }

        TTransaction::close();

        parent::add(AgendaVisual::estilo());
        parent::add( $this->fc );
    }

    /** Ids do filtro da sessao (aceita o formato antigo, um id so). */
    private static function ids($valor): array
    {
        $lista = is_array($valor) ? $valor : (($valor === null || $valor === '') ? [] : [$valor]);
        return array_values(array_filter(array_map('intval', $lista)));
    }

    /**
     * Output events as an json
     */
    public static function getEvents($param=NULL)
    {
        $return = array();
        try
        {
            TTransaction::open('escritorio');

            $criteria = new TCriteria(); 

            $criteria->add(new TFilter('dt_inicial', '<=', substr($param['end'], 0, 10).' 23:59:59'));
            $criteria->add(new TFilter('dt_final', '>=', substr($param['start'], 0, 10).' 00:00:00'));

            if(!empty($param['clear_session_filters']))
            {
                TSession::setValue(__CLASS__.'load_filter_agenda_id', null);
                TSession::setValue(__CLASS__.'load_filter_cliente_id', null);
            }

            $filterVar = "T";
            $criteria->add(new TFilter('ativo', '=', $filterVar)); 
            if(!empty($param['profissional_id']))
            {
                TSession::setValue(__CLASS__.'load_filter_agenda_id', $param['profissional_id']);
            }
            $filterVar = TSession::getValue(__CLASS__.'load_filter_agenda_id');

            if (isset($filterVar) AND ( (is_scalar($filterVar) AND $filterVar !== '') OR (is_array($filterVar) AND (!empty($filterVar)))))
            {
                $criteria->add(new TFilter('agenda_id', 'in', "(SELECT id FROM agenda WHERE profissional_id = '" . (int) $filterVar . "')"));
            }
            if(!empty($param['cliente_id']))
            {
                TSession::setValue(__CLASS__.'load_filter_cliente_id', $param['cliente_id']);
            }
            $filterVar = TSession::getValue(__CLASS__.'load_filter_cliente_id');
            if (isset($filterVar) AND ( (is_scalar($filterVar) AND $filterVar !== '') OR (is_array($filterVar) AND (!empty($filterVar)))))
            {
                $criteria->add(new TFilter('cliente_id', '=', (int) $filterVar));
            }

            // --------------------- FILTROS DA TELA (varios clientes, varias agendas, periodo) -------------------------
            $filtro = TSession::getValue('agendamentos_filter_data');
            if(!empty($filtro)){
                if(TSession::getValue('agendamentos_filter_agenda_id')){
                    $criteria->add(new TFilter('agenda_id', 'in', self::ids(TSession::getValue('agendamentos_filter_agenda_id'))));
                }

                $de = SqlSeguro::data($filtro->dt_busca ?? '');
                $ate = SqlSeguro::data($filtro->dt_busca_fim ?? '') ?: $de;
                if($de){
                    $criteria->add(new TFilter('dt_inicial', '>=', "{$de} 00:00:00"));
                    $criteria->add(new TFilter('dt_final', '<=', "{$ate} 23:59:59"));
                }

                $clientes = self::ids($filtro->cliente_id ?? null);
                if($clientes){
                    $criteria->add(new TFilter('cliente_id', 'in', $clientes));
                }
            }
            $filterVar = TSession::getValue("userunitid");
            $criteria->add(new TFilter('agenda_id', 'in', "(SELECT agenda.id FROM agenda, escritorio WHERE escritorio_id = escritorio.id AND system_unit_id = '" . (int) $filterVar . "')"));

            $agenda_id = self::ids(TSession::getValue('agendamentos_filter_agenda_id'));

            if ($agenda_id)
            {

                $agendas = Agenda::where('id',  'in', $agenda_id)
                                 ->orderBy('id')
                                 ->load();

                foreach ($agendas as &$agenda) {

                    // get dias off
                    if ($agenda->dias)
                    {

                        $semanaTrabalho = [1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,0=>0];

                        $dias = explode(',',$agenda->dias);

                        foreach($dias as $dia)
                        {
                            $start = new DateTime($param['start']);

                            if($dia > 0)
                            {
                                $d = $dia - 1;
                                $start = $start->modify("+{$d} day");    
                            }
                            else
                            {
                                $d = 6;
                                $start = $start->modify("+{$d} day");  
                            }

                            if(isset($semanaTrabalho[$dia]))
                            {
                                unset($semanaTrabalho[$dia]);
                            }

                            $return[] = [
                                'type' => 'disponivel',
                                'title' => '',
                                'overlap' => false,
                                'rendering' => "background",
                                'display' => 'background',
                                'color' => '#fff',
                                'start' => $start->format('Y-m-d') .'T'. $agenda->horario_inicial,
                                'end' =>  $start->format('Y-m-d') .'T'. $agenda->horario_final
                            ];

                        }

                        foreach($semanaTrabalho as $naoTrabalho)
                        {
                            $start = new DateTime($param['start']);

                            if($naoTrabalho > 0)
                            {
                                $d = $naoTrabalho - 1;
                                $start = $start->modify("+{$d} day");    
                            }
                            else
                            {
                                $d = 6;
                                $start = $start->modify("+{$d} day");  
                            }

                            $return[] = [
                                'start' => $start->format('Y-m-d') .'T'. $agenda->horario_inicial,
                                'end' =>  $start->format('Y-m-d') .'T'. $agenda->horario_final,
                                'title' => 'Não atende',
                                'id' => 'nao-trabalha-'.uniqid(),
                                'overlap' => true,
                                'rendering' => 'block',
                                'display' => 'background',
                                'classNames' => ['agd-fundo'],
                                'fundo' => true,
                                'agenda_id' => (string) $agenda->id,
                            ];
                        }
                    }

                    // get Intervalo
                    if ($agenda->horario_fim_intervalo AND $agenda->horario_inicio_intervalo)
                    {
                        $start = new DateTime($param['start']);
                        $end = new DateTime($param['end']);

                        while($start->format('Y-m-d') <= $end->format('Y-m-d'))
                        {
                            if (! empty($semanaTrabalho[$start->format('w')]))
                            {
                                $start = $start->modify('+1 day');
                                continue;
                            }

                            $return[] = [
                                'type' => 'feriado',
                                'title' => 'Intervalo',
                                'camada' => 4, // por cima de bloqueio, compromisso e agendamento da agenda (AgendaVisual::camadas)
                                'overlap' => true,
                                'rendering' => "block",
                                'display' => 'background',
                                'classNames' => ['agd-fundo'],
                                'fundo' => true,
                                'agenda_id' => (string) $agenda->id,
                                'start' => $start->format('Y-m-d') .'T'. $agenda->horario_inicio_intervalo,
                                'end' =>  $start->format('Y-m-d') .'T'. $agenda->horario_fim_intervalo
                            ];

                            $start = $start->modify('+1 day');
                        }
                    }
                }
            }

            // --------------------- BLOQUEIO ---------------------------------------------------------------------------------------------------------
            $criteriaBloqueio = new TCriteria;
            $criteriaBloqueio->add(new TFilter('dt_inicio', '<=', substr($param['end'], 0, 10).' 23:59:59'));
            $criteriaBloqueio->add(new TFilter('dt_final', '>=', substr($param['start'], 0, 10).' 00:00:00'));

            if (! empty($agenda_id))
            {
                $criteriaBloqueio->add(new TFilter('agenda_id', 'in', $agenda_id));
            }

            // Get bloqueios
            $bloqueios = Bloqueio::getObjects($criteriaBloqueio);

            if ($bloqueios)
            {
                foreach($bloqueios as $bloqueio)
                {
                    if (! empty($semanaTrabalho[date('w', strtotime($bloqueio->dt_inicio))]))
                    {
                        continue;
                    }
                    $agenda = Agenda::find($bloqueio->agenda_id);
                    $event_array = [];
                    $event_array['start'] = str_replace( ' ', 'T', $bloqueio->dt_inicio);
                    $event_array['end'] = str_replace( ' ', 'T', $bloqueio->dt_final);
                    $event_array['title'] = "<div class='agd-ev'><span class='agd-ev-l1'><i class='fas fa-lock'></i> <span class='agd-ev-texto'>Bloqueado"
                        . ($agenda ? ' · ' . htmlspecialchars((string) $agenda->nome) : '') . "</span></span>"
                        . (trim((string) $bloqueio->observacao) !== '' ? "<span class='agd-ev-l2'><span class='agd-ev-texto'>" . htmlspecialchars((string) $bloqueio->observacao) . "</span></span>" : '') . "</div>";
                    $event_array['id'] = 'bloqueio-'.$bloqueio->id;

                    $return[] = AgendaVisual::estilizar($event_array, 'bloqueio', $agenda->cor ?? null, $bloqueio->agenda_id);
                }
            }

            // --------------------- COMPROMISSO ---------------------------------------------------------------------------------------------------------
            $criteriaCompromisso = new TCriteria;
            $criteriaCompromisso->add(new TFilter('dt_inicio', '<=', substr($param['end'], 0, 10).' 23:59:59'));
            $criteriaCompromisso->add(new TFilter('dt_final', '>=', substr($param['start'], 0, 10).' 00:00:00'));

            if (! empty($agenda_id))
            {
                $criteriaCompromisso->add(new TFilter('agenda_id', 'in', $agenda_id));
            }

            //get compromissos
            $compromissos = Compromisso::getObjects($criteriaCompromisso);

            if ($compromissos)
            {
                foreach($compromissos as $compromisso)
                {
                    if (! empty($semanaTrabalho[date('w', strtotime($compromisso->dt_inicio))]))
                    {
                        continue;
                    }
                    $agenda = Agenda::find($compromisso->agenda_id);
                    $event_array = [];
                    $event_array['start'] = str_replace( ' ', 'T', $compromisso->dt_inicio);
                    $event_array['end'] = str_replace( ' ', 'T', $compromisso->dt_final);
                    $event_array['title'] = "<div class='agd-ev'><span class='agd-ev-l1'><i class='fas fa-briefcase'></i> " . htmlspecialchars((string) ($compromisso->tipo_compromisso->nome ?? 'Compromisso')) . "</span>"
                        . "<span class='agd-ev-l2'>Compromisso" . ($agenda ? ' · ' . htmlspecialchars((string) $agenda->nome) : '')
                        . (trim((string) $compromisso->observacao) !== '' ? ' · ' . htmlspecialchars((string) $compromisso->observacao) : '') . "</span></div>";
                    $event_array['id'] = 'compromisso-'.$compromisso->id;

                    $return[] = AgendaVisual::estilizar($event_array, 'compromisso', $agenda->cor ?? null, $compromisso->agenda_id);
                }
            }

            // --------------------- CONVIDADOS ---------------------------------------------------------------------------------------------------------
            $criteriaConvidado = new TCriteria;

            if (! empty($agenda_id))
            {
                $criteriaConvidado->add(new TFilter('agenda_id', 'in', $agenda_id));
            }

            // Get convidados
            $convidados = Convidado::getObjects($criteriaConvidado);

            if ($convidados)
            {
                foreach($convidados as $convidado)
                {
                    if (! empty($semanaTrabalho[date('w', strtotime($convidado->agendamento->dt_inicial))]))
                    {
                        continue;
                    }
                    $agendaConvidada = Agenda::find($convidado->agenda_id);
                    $event_array = [];
                    $event_array['start'] = str_replace( ' ', 'T', $convidado->agendamento->dt_inicial);
                    $event_array['end'] = str_replace( ' ', 'T', $convidado->agendamento->dt_final);
                    $event_array['id'] = $convidado->agendamento->id;
                    $event_array['title'] = TFullCalendar::renderPopover($convidado->render("<div class='agd-ev'><span class='agd-ev-l1'>
                    <span title='{agendamento->estado_agenda->nome}' class='agd-ev-est' style='background-color: {agendamento->estado_agenda->cor}'></span>
                    {agendamento->cliente->nome_formatado}</span><span class='agd-ev-l2'>Convidado por {agendamento->agenda->profissional->nome_formatado}</span>
                    </div> "), $convidado->render(""), $convidado->render("<div>
                    <b>Data:</b><br/>{agendamento->dt_inicial}<br/><b>Agendamento #{agendamento->id}</b><br/><b>Convidado por:</b><br/>{agendamento->agenda->profissional->nome_formatado}<br/><br/></div>
                    "));

                    $return[] = AgendaVisual::estilizar($event_array, 'agendamento', $agendaConvidada->cor ?? null, $convidado->agenda_id);
                }
            }

            // --------------------- COMPROMISSO CONVIDADO ---------------------------------------------------------------------------------------------------------
            $criteriaCompromissoConvidado = new TCriteria;
            if (! empty($agenda_id))
            {
                $criteriaCompromissoConvidado->add(new TFilter('agenda_id', 'in', $agenda_id));
            }

            //get compromissos convidados
            $compromissosConvidados = ConvidadoCompromisso::getObjects($criteriaCompromissoConvidado);

            if ($compromissosConvidados)
            {
                foreach($compromissosConvidados as $compromissoConvidado)
                {
                    $compromisso = $compromissoConvidado->compromisso;
                    $agenda = Agenda::find($compromissoConvidado->agenda_id);
                    if($compromisso && $agenda){
                        if (! empty($semanaTrabalho[date('w', strtotime($compromisso->dt_inicio))]))
                        {
                            continue;
                        }

                        $event_array = [];
                        $event_array['start'] = str_replace( ' ', 'T', $compromisso->dt_inicio);
                        $event_array['end'] = str_replace( ' ', 'T', $compromisso->dt_final);
                        $event_array['title'] = "<div class='agd-ev'><span class='agd-ev-l1'><i class='fas fa-briefcase'></i> " . htmlspecialchars((string) ($compromisso->tipo_compromisso->nome ?? 'Compromisso')) . "</span>"
                            . "<span class='agd-ev-l2'>Compromisso convidado · " . htmlspecialchars((string) $agenda->nome) . "</span></div>";
                        $event_array['id'] = 'compromisso-'.$compromisso->id;

                        $return[] = AgendaVisual::estilizar($event_array, 'compromisso', $agenda->cor, $compromissoConvidado->agenda_id);
                    }
                }
            }

            $events = Agendamento::getObjects($criteria);

            if ($events)
            {
                // procedimentos de todos os agendamentos numa consulta so (segunda linha do cartao)
                $procedimentos = [];
                $ids = implode(',', array_map(function ($e) { return (int) $e->id; }, $events));
                $st = TTransaction::get()->query("SELECT ap.agendamento_id, string_agg(p.nome, ', ' ORDER BY ap.id) AS nomes FROM agendamento_procedimento ap JOIN procedimento p ON p.id = ap.procedimento_id WHERE ap.agendamento_id IN ({$ids}) GROUP BY ap.agendamento_id");
                foreach ($st->fetchAll(PDO::FETCH_OBJ) as $p) { $procedimentos[(int) $p->agendamento_id] = $p->nomes; }

                foreach ($events as $event)
                {
                    $event_array = $event->toArray();
                    $event_array['start'] = str_replace( ' ', 'T', $event_array['dt_inicial']);
                    $event_array['end'] = str_replace( ' ', 'T', $event_array['dt_final']);
                    $event_array['id'] = $event->id;

                    $online = ($event_array['online'] != 'F') ? " <span class='agd-ev-online'>online</span>" : '';
                    $detalhe = trim(implode(' · ', array_filter([$procedimentos[(int) $event->id] ?? '', (string) ($event->estado_agenda->nome ?? '')])));
                    $formatacao = "<div class='agd-ev'><span class='agd-ev-l1'>"
                        . "<span title='{estado_agenda->nome}' class='agd-ev-est' style='background-color: {estado_agenda->cor}'></span>"
                        . "{cliente->nome_formatado}{$online}</span>"
                        . "<span class='agd-ev-l2'>" . htmlspecialchars($detalhe) . "</span></div>";
                    $event_array['title'] = TFullCalendar::renderPopover($event->render($formatacao), $event->render(""), $event->render("{detalhe_html}"));

                    $return[] = AgendaVisual::estilizar($event_array, 'agendamento', $event->render("{agenda->cor}"), $event->agenda_id);
                }
            }
            TTransaction::close();
            // na mesma agenda: intervalo > bloqueio > compromisso > agendamento (o de cima corta o de baixo)
            echo json_encode(AgendaVisual::camadas($return));
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    /**
     * Reconfigure the callendar
     */
    public function onReload($param = null)
    {
        if (isset($param['view']))
        {
            $this->fc->setCurrentView($param['view']);
        }

        if (isset($param['date']))
        {
            $this->fc->setCurrentDate($param['date']);
        }
    }

    public static function onClick($param)
    {
        if (!empty($param['key']) && strpos($param['key'], 'bloqueio') !== false)
        {
            AdiantiCoreApplication::loadPage('BloqueioForm', 'onEdit', ['key' => str_replace('bloqueio-', '', $param['key'])]);
        }
        elseif (!empty($param['key']) && strpos($param['key'], 'trabalha') !== false)
        {

        }
        elseif (!empty($param['key']) && strpos($param['key'], 'intervalo') !== false)
        {

        }
        elseif (!empty($param['key']) && strpos($param['key'], 'compromisso') !== false)
        {
            AdiantiCoreApplication::loadPage('CompromissoForm', 'onEdit', ['key' => str_replace('compromisso-', '', $param['key'])]);
        }
        elseif(!empty($param['key']))
        {
            unset($param['static']);
            AdiantiCoreApplication::loadPage('AgendamentoFormView', 'onShow', $param);
        }
    }

    /** Periodo filtrado: abre no inicio dele (um dia: Dia; ate 7 dias: Semana; mais: Mes). */
    public function onShow($param){

        $filterData = TSession::getValue('agendamentos_filter_data');
        $de = SqlSeguro::data($filterData->dt_busca ?? '');
        if ($de) {
            $ate = SqlSeguro::data($filterData->dt_busca_fim ?? '') ?: $de;
            $dias = (int) round((strtotime($ate) - strtotime($de)) / 86400) + 1;
            $this->fc->setCurrentView($dias <= 1 ? 'agendaDay' : ($dias <= 7 ? 'agendaWeek' : 'mesHoje'));
            $this->fc->setCurrentDate($de);
        }
    }

}
