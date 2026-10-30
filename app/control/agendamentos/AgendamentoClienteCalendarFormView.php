<?php
/**
 * AgendamentoClienteCalendarForm Form
 * @author  <your name here>
 */
class AgendamentoClienteCalendarFormView extends TPage
{
    private $fc;

    /**
     * Page constructor
     */
    public function __construct($param = null)
    {
        parent::__construct();

        TSession::setValue(__CLASS__.'load_filter_agenda_id', null);

        $this->fc = new TFullCalendar(date('Y-m-d'), 'month');
        $this->fc->enableDays([0,1,2,3,4,5,6]);
        $this->fc->setReloadAction(new TAction(array($this, 'getEvents'), $param));
        $this->fc->setDayClickAction(new TAction(array('AgendamentoClienteCalendarForm', 'onStartEdit')));
        $this->fc->setCurrentView('agendaWeek');
        $this->fc->setTimeRange('07:00', '19:00');
        $this->fc->setOption('slotTime', "00:30:00");
        $this->fc->setOption('slotDuration', "00:30:00");
        $this->fc->setOption('slotLabelInterval', 30);

        $param['register_state'] = 'false';

        $this->fc->enableFullHeight();
        $this->fc->setDayClickAction(new TAction(array($this, 'onNew'), $param));
        $this->fc->id = 'agendamentos';
        $agenda_id = $param['agenda_id'];

        if ($agenda_id)
        {
            TTransaction::open('escritorio');
            $agenda = Agenda::find($agenda_id);

            $this->fc->setCurrentView($agenda->visualizacao_inicial);
            $this->fc->setTimeRange($agenda->horario_inicial, $agenda->horario_final);

            $time = date('H:i:s', strtotime("2000-01-01 +{$agenda->duracao} minutes"));

            $this->fc->setOption('slotTime', $time);
            $this->fc->setOption('slotDuration', $time);
            $this->fc->setOption('slotLabelInterval', (int) $agenda->duracao);

            TTransaction::close();
        }

        parent::add( $this->fc );
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
            }

            $filterVar = 'T';
            $criteria->add(new TFilter('agenda_id', 'in', "(SELECT id FROM agenda WHERE publica = '{$filterVar}')")); 
            if(!empty($param['agenda_id']))
        {
            TSession::setValue(__CLASS__.'load_filter_agenda_id', $param['agenda_id']);
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_agenda_id');
            if (isset($filterVar) AND ( (is_scalar($filterVar) AND $filterVar !== '') OR (is_array($filterVar) AND (!empty($filterVar)))))
            {
                $criteria->add(new TFilter('agenda_id', '=', $filterVar)); 
            }

            $agenda_id = TSession::getValue(__CLASS__.'load_filter_agenda_id');

            if ($agenda_id)
            {
                $agenda = Agenda::find($agenda_id);

                // dias da semana (0-6) em que a agenda nao atende
                $semanaTrabalho = [];

                // Curciol (30/10/2026): pela data de cada dia da visao. Antes contava a partir do primeiro dia como se fosse
                // segunda (a semana do calendario comeca no domingo: o "Nao atende" caia um dia adiantado) e so marcava a
                // primeira semana do periodo (no mes, as outras ficavam sem marcacao).
                if ($agenda->dias)
                {
                    $atende = array_map('intval', explode(',', $agenda->dias));
                    for ($w = 0; $w <= 6; $w++)
                    {
                        if (!in_array($w, $atende, true))
                        {
                            $semanaTrabalho[$w] = $w;
                        }
                    }

                    $dia = new DateTime(substr($param['start'], 0, 10));
                    $ultimo = new DateTime(substr($param['end'], 0, 10));
                    while ($dia < $ultimo)
                    {
                        if (isset($semanaTrabalho[(int) $dia->format('w')]))
                        {
                            $event_array = [];
                            $event_array['start'] = $dia->format('Y-m-d') .'T'. $agenda->horario_inicial;
                            $event_array['end'] =  $dia->format('Y-m-d') .'T'. $agenda->horario_final;
                            $event_array['title'] = 'Não atende';
                            $event_array['id'] = 'nao-trabalha-'.uniqid();
                            $event_array['color'] = '#0000004a';
                            $event_array['overlap'] = true;
                            $event_array['rendering'] = 'block';
                            $event_array['display'] = 'block';

                            $return[] = $event_array;
                        }
                        else
                        {
                            $return[] = [
                                'type' => 'disponivel',
                                'title' =>'Disponível',
                                'overlap' => false,
                                'rendering' => "background",
                                'display' => 'background',
                                'color' => '#00cec9',
                                'start' => $dia->format('Y-m-d') .'T'. $agenda->horario_inicial,
                                'end' =>  $dia->format('Y-m-d') .'T'. $agenda->horario_final
                            ];
                        }
                        $dia->modify('+1 day');
                    }
                }

                // get Intervalo
                if ($agenda->horario_fim_intervalo AND $agenda->horario_inicio_intervalo)
                {
                    $start = new DateTime($param['start']);
                    $end = new DateTime($param['end']);

                    while($start->format('Y-m-d') <= $end->format('Y-m-d'))
                    {
                        // isset: o domingo (0) tambem e dia sem atendimento (com empty o intervalo aparecia no domingo)
                        if (isset($semanaTrabalho[(int) $start->format('w')]))
                        {
                            $start = $start->modify('+1 day');
                            continue;
                        }

                        $return[] = [

                            'type' => 'feriado',
                            //'key' => '1',
                            'title' =>'Intervalo',
                            'overlap' => true,
                            'rendering' => "block",
                            'display' => 'block',
                            'color' => 'black',
                            'start' => $start->format('Y-m-d') .'T'. $agenda->horario_inicio_intervalo,
                            'end' =>  $start->format('Y-m-d') .'T'. $agenda->horario_fim_intervalo
                        ];

                        $start = $start->modify('+1 day');
                    }
                }
            }

            $criteriaBloqueio = new TCriteria;
            $criteriaBloqueio->add(new TFilter('dt_inicio', '<=', substr($param['end'], 0, 10).' 23:59:59'));
            $criteriaBloqueio->add(new TFilter('dt_final', '>=', substr($param['start'], 0, 10).' 00:00:00'));

            if (! empty($param['agenda_id']))
            {
                $criteriaBloqueio->add(new TFilter('agenda_id', '=', $param['agenda_id']));
            }

            // Get bloqueios
            $bloqueios = Bloqueio::getObjects($criteriaBloqueio);

            if ($bloqueios)
            {
                foreach($bloqueios as $bloqueio)
                {
                    $event_array = [];
                    $event_array['start'] = str_replace( ' ', 'T', $bloqueio->dt_inicio);
                    $event_array['end'] = str_replace( ' ', 'T', $bloqueio->dt_final);
                    $event_array['title'] = "<b>Horário bloqueado</b><br>" .nl2br($bloqueio->observacao);
                    $event_array['id'] = 'bloqueio-'.$bloqueio->id;
                    $event_array['color'] = '#555';

                    $return[] = $event_array;
                }
            }

            $events = Agendamento::getObjects($criteria);

            if ($events)
            {
                foreach ($events as $event)
                {
                    $event_array = $event->toArray();
                    $event_array['start'] = str_replace( ' ', 'T', $event_array['dt_inicial']);
                    $event_array['end'] = str_replace( ' ', 'T', $event_array['dt_final']);
                    $event_array['id'] = $event->id;
                    $event_array['color'] = $event->render("{agenda->cor}");
                    $event_array['title'] = $event->render("Ocupado");

                    $return[] = $event_array;
                }
            }
            TTransaction::close();
            echo json_encode($return);
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

    public static function onNew($param)
    {
        if (! empty(TSession::getValue('portal_cliente_id')))
        {
            unset($param['static']);
            TApplication::loadPage('AgendamentoClienteCalendarForm', 'onStartEdit', $param);
        }
        else
        {
            if (empty($param['usuario']) &&  empty($param['senha']))
            {
                $form = new BootstrapFormBuilder('input_form');
                $form->setFieldSizes('100%');

                $login = new TEntry('usuario');
                $pass  = new TPassword('senha');

                $form->addFields( [new TLabel('Usuário'), $login]);
                $form->addFields( [new TLabel('Senha'), $pass]);

                $form->addAction('Entrar', new TAction([__CLASS__, 'onNew'], $param), 'fa:sign-in-alt green');

                unset($param['static']);

                $form->addAction('Não sou cadastrado', new TAction(['NovoClienteForm', 'onShow'], $param), 'fa:user-plus blue');

                new TInputDialog('Informe o seu usuário e senha', $form);
            }
            else
            {
                // Curciol (seguranca 22/10/2026): limite de senhas erradas e sessao nova ao entrar (PortalLoginLimite)
                try
                {
                    PortalLoginLimite::exigirLiberado($param['usuario'] ?? '');
                }
                catch (Exception $e)
                {
                    new TMessage('error', $e->getMessage());
                    return false;
                }
                TTransaction::open('escritorio');
                $pessoa = Pessoa::where('usuario', '=', $param['usuario'])->where('senha', '=', $param['senha'])->first();
                TTransaction::close();
                if (empty($pessoa))
                {
                    PortalLoginLimite::registrarFalha($param['usuario'] ?? '');
                    new TMessage('error','Você ainda não é um cliente registrado. Verifique os dados informado!');
                    return false;
                }

                PortalLoginLimite::limpar($param['usuario'] ?? '');
                if (session_status() == PHP_SESSION_ACTIVE) { TSession::regenerate(); }
                TSession::setValue('portal_cliente_id', $pessoa->id);

                $param['register_state']  = 'false';

                TApplication::loadPage(__CLASS__, 'onNew', $param);
            }
        }
    }

}

