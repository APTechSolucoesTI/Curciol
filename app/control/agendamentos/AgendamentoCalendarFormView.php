<?php
/**
 * AgendamentoCalendarForm Form
 * @author  <your name here>
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

        TSession::setValue(__CLASS__.'load_filter_agenda_id', null);
        TSession::setValue(__CLASS__.'load_filter_cliente_id', null);

        $this->fc = new TFullCalendar(date('Y-m-d'), 'month');
        $this->fc->enableDays([0,1,2,3,4,5,6]);
        $this->fc->setReloadAction(new TAction(array($this, 'getEvents'), $param));
        $this->fc->setEventClickAction(new TAction(array('AgendamentoCalendarForm', 'onEdit')));
        $this->fc->setCurrentView('agendaWeek');
        $this->fc->setTimeRange('08:00', '23:00');
        $this->fc->enablePopover('', "{detalhe_html}");
        $this->fc->setOption('slotTime', "00:30:00");
        $this->fc->setOption('slotDuration', "00:30:00");
        $this->fc->setOption('slotLabelInterval', 30);

        $agendas_id = TSession::getValue('agendamentos_filter_agenda_id');

        $param['agenda_id']=$agendas_id[0] ?? NULL;
        $this->fc->enableFullHeight();
        $this->fc->setEventClickAction(new TAction(array($this, 'onClick')));
        //$this->fc->setDayClickAction(new TAction(array('AgendamentoCalendarForm', 'onStartEdit'), $param));
        $this->fc->id = 'agendamentos';

        if ($agendas_id)
        {
            TTransaction::open('escritorio');

            foreach($agendas_id as  $key=>$agenda_id){

                $duracao = array();

                $agenda = Agenda::find($agenda_id);

                $visualizacao_inicial[$key] = $agenda->visualizacao_inicial;

                $horario_inicial[$key] =   $agenda->horario_inicial;
                $strohra_inicial[$key] = strtotime($horario_inicial[$key]);
                $horario_final[$key] =   $agenda->horario_final;
                $strohra_final[$key] = strtotime($horario_final[$key]);

                $duracao[$key] = $agenda->duracao;
                if(count($agendas_id)>1){
                    $this->fc->setCurrentView('agendaWeek');
                    $duracao=60;
                }else{
                    $this->fc->setCurrentView($agenda->visualizacao_inicial);
                    $duracao = $agenda->duracao;
                }
            }

            $keyInicial = array_search((min($strohra_inicial)), $strohra_inicial);
            $keyFinal = array_search((max($strohra_final)), $strohra_final);

            $this->fc->setTimeRange($horario_inicial[$keyInicial], $horario_final[$keyFinal]);

            $time = date('H:i:s', strtotime("2000-01-01 +{$duracao} minutes"));

            $this->fc->setOption('slotTime', $time);
            $this->fc->setOption('slotDuration', $time);
            $this->fc->setOption('slotLabelInterval', $duracao);
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
                $criteria->add(new TFilter('agenda_id', 'in', "(SELECT id FROM agenda WHERE profissional_id = '{$filterVar}')")); 
            }
            if(!empty($param['cliente_id']))
        {
            TSession::setValue(__CLASS__.'load_filter_cliente_id', $param['cliente_id']);
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_cliente_id');
            if (isset($filterVar) AND ( (is_scalar($filterVar) AND $filterVar !== '') OR (is_array($filterVar) AND (!empty($filterVar)))))
            {
                $criteria->add(new TFilter('cliente_id', '=', $filterVar)); 
            }

            // --------------------- AGENDAMENTO ---------------------------------------------------------------------------------------------------------
            if(!empty(TSession::getValue('agendamentos_filter_data'))){
                if(TSession::getValue('agendamentos_filter_agenda_id')!=NULL){
                    $criteria->add(new TFilter('agenda_id', 'in', TSession::getValue('agendamentos_filter_agenda_id')));
                }
                //if(TSession::getValue('agendamentos_filter_data')->profissional_id!=''){
                //    $agenda = Agenda::where('profissional_id','=',(int) TSession::getValue('agendamentos_filter_data')->profissional_id)->first();
                //    if($agenda){
                //        $criteria->add(new TFilter('agenda_id', '=', $agenda->id));
                //    }
                //}

                if(TSession::getValue('agendamentos_filter_data')->dt_busca!=''){
                    $dataInicial = TSession::getValue('agendamentos_filter_data')->dt_busca . " 00:00:00";
                    $dataFinal = TSession::getValue('agendamentos_filter_data')->dt_busca . " 23:59:59";
                    $criteria->add(new TFilter('dt_inicial', '>=', $dataInicial));
                    $criteria->add(new TFilter('dt_final', '<=', $dataFinal));
                }

                if(TSession::getValue('agendamentos_filter_data')->cliente_id!=''){
                    $criteria->add(new TFilter('cliente_id', '=', TSession::getValue('agendamentos_filter_data')->cliente_id));
                }
            }
            $filterVar = TSession::getValue("userunitid");
            $criteria->add(new TFilter('agenda_id', 'in', "(SELECT agenda.id FROM agenda, escritorio WHERE escritorio_id = escritorio.id AND system_unit_id = '{$filterVar}')")); 

            $filterVar = TSession::getValue("userid");

            $criteriaAcesso = new TCriteria;
            // está como um profissional relacionado com a agenda
            $criteriaAcesso->add(new TFilter('agenda_id', 'in', "(SELECT agenda.id FROM agenda, agenda_profissional, pessoa WHERE pessoa.id = agenda_profissional.profissional_id AND agenda.id = agenda_profissional.agenda_id AND system_users_id = '{$filterVar}')"));
            // oyu é o profissional responsável da agenda
            $criteriaAcesso->add(new TFilter('agenda_id', 'in', "(SELECT agenda.id FROM agenda, pessoa WHERE pessoa.id = agenda.profissional_id AND system_users_id = '{$filterVar}')"), TExpression::OR_OPERATOR); 

            //$criteria->add($criteriaAcesso);

            $agenda_id = TSession::getValue('agendamentos_filter_agenda_id');

            if ($agenda_id)
            {

                $agendas = Agenda::where('id',  'in', $agenda_id)
                                 ->orderBy('id')
                                 ->load();

                foreach ($agendas as &$agenda) {

                    // get dias off
                    if ($agenda->dias)
                    {

                        $end = new DateTime($param['end']);

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
                                'title' =>'Disponível',
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

                            $event_array = [];
                            $event_array['start'] = $start->format('Y-m-d') .'T'. $agenda->horario_inicial;
                            $event_array['end'] =  $start->format('Y-m-d') .'T'. $agenda->horario_final;
                            $event_array['title'] = 'Não atende';
                            $event_array['id'] = 'nao-trabalha-'.uniqid();
                            $event_array['color'] = '#f2f7f6';
                            //$event_array['color'] = '#b8fffe';
                            $event_array['overlap'] = true;
                            $event_array['rendering'] = 'block';
                            $event_array['display'] = 'background';

                            $return[] = $event_array;
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
                                'title' =>"<p style='color:#333;'>Intervalo</p>",
                                'overlap' => true,
                                'rendering' => "block",
                                'display' => 'background',
                                'color' => '#f2f7f6',
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

                if(TSession::getValue('agendamentos_filter_data')->dt_busca!=''){
                    $dataInicial = TSession::getValue('agendamentos_filter_data')->dt_busca . " 00:00:00";
                    $dataFinal = TSession::getValue('agendamentos_filter_data')->dt_busca . " 23:59:59";
                    $criteria->add(new TFilter('dt_inicial', '>=', $dataInicial));
                    $criteria->add(new TFilter('dt_final', '<=', $dataFinal));
                }

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
                    $event_array['title'] = "<div style='display: flex; flex-direction: revert; justify-content: flex-start; align-items: center; gap: 5px;'>
                            <span title='{$agenda->profissional->nome}' class='estado_agendamento' style='background-color: {$agenda->cor}  ' ></span>
                    <span style='color:#333;'><b>Horário bloqueado</b><br>" .nl2br($bloqueio->observacao). "</span></div>";
                    $event_array['id'] = 'bloqueio-'.$bloqueio->id;
                    $event_array['color'] = '#f2f7f6';

                    $return[] = $event_array;
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
                    $event_array['title'] = "<div style='display: flex; flex-direction: revert; justify-content: flex-start; align-items: center; gap: 5px;'>
                            <span title='{$agenda->profissional->nome}' class='estado_agendamento' style='background-color: {$agenda->cor}  ' ></span>
                    <span style='color:#333;'><b>Compromisso agendado</b><br>" .nl2br( 'Compromisso: '.$compromisso->tipo_compromisso->nome.'<br>')."</span></div>";
                    $event_array['id'] = 'compromisso-'.$compromisso->id;
                    $event_array['color'] = '#f2f7f6';

                    $return[] = $event_array;
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
                    $event_array = [];
                    $event_array['start'] = str_replace( ' ', 'T', $convidado->agendamento->dt_inicial);
                    $event_array['end'] = str_replace( ' ', 'T', $convidado->agendamento->dt_final);
                    $event_array['id'] = $convidado->agendamento->id;
                    $event_array['color'] = '#f00';
                    $event_array['title'] = TFullCalendar::renderPopover($convidado->render("<div style='display: flex; flex-direction: revert; justify-content: flex-start; align-items: center; gap: 5px;'>
                    <span title='{agendamento->estado_agenda->nome}' class='estado_agendamento' style='background-color: {agenda->cor}  ' ></span>  
                    {agendamento->cliente->nome_formatado}
                    </div> "), $convidado->render(""), $convidado->render("<div>
                    <b>Data:</b><br/>{agendamento->dt_inicial}<br/><b>Agendamento #{agendamento->id}</b><br/><b>Convidado por:</b><br/>{agendamento->agenda->profissional->nome_formatado}<br/><br/></div>
                    "));

                    $return[] = $event_array;
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
                        $event_array['title'] = "<div style='display: flex; flex-direction: revert; justify-content: flex-start; align-items: center; gap: 5px;'>
                                <span title='{$agenda->profissional->nome}' class='estado_agendamento' style='background-color: {$agenda->cor}  ' ></span>
                        <span style='color:#333;'><b>Compromisso convidado</b><br>" .nl2br( 'Compromisso: '.$compromisso->tipo_compromisso->nome.'<br>')."</span></div>";
                        $event_array['id'] = 'compromisso-'.$compromisso->id;
                        $event_array['color'] = '#f2f7f6';

                        $return[] = $event_array;
                    }
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

                    if($event_array['online']!='F'){
                        $formatacao = "<div style='display: flex; flex-direction: revert; justify-content: flex-start; align-items: center; gap: 5px;'>
                            <span title='{estado_agenda->nome}' class='estado_agendamento' style='background-color: {estado_agenda->cor}  ' ></span>
                            {cliente->nome_formatado}
                            <span style='border-radius:2px; background-color:#ff0000;'> Online </span>
                            </div>";

                    }else{
                        $formatacao = "<div style='display: flex; flex-direction: revert; justify-content: flex-start; align-items: center; gap: 5px;'>
                            <span title='{estado_agenda->nome}' class='estado_agendamento' style='background-color: {estado_agenda->cor}  ' ></span>
                            {cliente->nome_formatado}
                            </div>";
                    }
                    $event_array['title'] = TFullCalendar::renderPopover($event->render($formatacao), $event->render(""), $event->render("{detalhe_html}"));

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

    public function onShow($param){

         $filterData = TSession::getValue('agendamentos_filter_data');

            if (!empty($filterData->dt_busca)) {
                $dt_busca = $filterData->dt_busca;

                // converte se vier em d/m/Y
                if (strpos($dt_busca, '/') !== false) {
                    $date = DateTime::createFromFormat('d/m/Y', $dt_busca);
                    $dt_busca = $date ? $date->format('Y-m-d') : $dt_busca;
                }

                // define visualização como "Dia"
                $this->fc->setCurrentView('agendaDay');

                // define a data atual da visualização
                $this->fc->setCurrentDate($dt_busca);
            }
    }

}

