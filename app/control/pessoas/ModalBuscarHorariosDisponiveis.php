<?php

class ModalBuscarHorariosDisponiveis extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalBuscarHorariosDisponiveis';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(670, null);
        parent::setTitle("Horários disponíveis");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setProperty('class','panel panel-default form-view-wrapper');
        // define the form title
        $this->form->setFormTitle("Horários disponíveis");

        if(!empty($param['convidado_agendamento_agenda_id'])){
            TSession::setValue('convidado_agendamento_id', $param['convidado_agendamento_agenda_id']);
        }

        $btnRadioDia = new TRadioGroup('btnRadioDia');
        $btnRadioHorario = new TCheckGroup('btnRadioHorario');

        $btnRadioDia->setChangeAction(new TAction([$this,'onSelectDia']));
        $btnRadioHorario->setChangeAction(new TAction([$this,'onSelectHorario']));

        $btnRadioHorario->addValidation("horario", new TRequiredValidator()); 

        $btnRadioDia->setBreakItems(3);
        $btnRadioDia->setSize(200);
        $btnRadioHorario->setSize(200);

        $btnRadioDia->setLayout('horizontal');
        $btnRadioHorario->setLayout('vertical');

        $btnRadioDia->setUseButton();
        $btnRadioHorario->setUseButton();


        TTransaction::open('escritorio');

        $agenda_id = TSession::getValue('agenda_id');
        $agendaPrincipal = Agenda::find($agenda_id);
        $dias[$agenda_id] = explode(",", $agendaPrincipal -> dias);

        $agendaConvidados = TSession::getValue('convidado_agendamento_id');

        if(isset($agendaConvidados[0]) && $agendaConvidados[0]!=null){
            foreach ($agendaConvidados as $key=>$agendaConvidado) {
                $agendaConvidadoSearch = Agenda::find($agendaConvidado);
                $dias[$key] = explode(",", $agendaConvidadoSearch->dias);
             }

            $chavesDias = array_keys($dias);
            $quantAgendas = count($chavesDias);
            for($i=0;$i<$quantAgendas;$i++){
                $combina = array_intersect($dias[$chavesDias[$i++]], $dias[$chavesDias[$i]]);
            }
        }else{
            $combina = $dias[$agenda_id];
        }

        TSession::setValue('convidado_agendamento_id', null);

        //SEG,TER,QUA,QUI,SEX,SAB,DOM
        //1,  2,  3,  4,  5  ,6  ,0

        $i=0;
        $data = date('Y-m-d');
        $data = date('Y-m-d', strtotime("-1 day",strtotime($data)));
        while($i<30){
            //adiciona um dia
            $date = date('Y-m-d', strtotime("+1 day",strtotime($data))); 

            $diasemana_numero = date('w', strtotime($date));
            $diasemana_nome = date('D', strtotime($date));

            switch ($diasemana_nome) {
                case 'Sun':
                    $diasemana_nome = 'Domingo';
                    break;
                case 'Mon':
                    $diasemana_nome = 'Segunda-Feira';
                    break;
                case 'Tue':
                    $diasemana_nome = 'Terca-Feira';
                    break;
                case 'Wed':
                    $diasemana_nome = 'Quarta-Feira';
                    break;
                case 'Thu':
                    $diasemana_nome = 'Quinta-Feira';
                    break;
                case 'Fri':
                    $diasemana_nome = 'Sexta-Feira';
                    break;
                case 'Sat':
                    $diasemana_nome = 'Sábado';
                    break;
            }

            $data = date( 'd-m-Y' , strtotime( $date ) );
                if(in_array($diasemana_numero,$combina)){

                    $format = str_replace('-','/',$data);
                    $radios[$date]=$diasemana_nome . " - " . $format;
                    $i++;
                }
        }
        //var_dump($radios);
        $btnRadioDia->addItems($radios);
        TTransaction::close();
        $row1 = $this->form->addFields([$btnRadioDia]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([$btnRadioHorario]);
        $row2->layout = [' col-sm-12'];

        // create the form actions
        $btn_onvoltar = $this->form->addAction("Voltar", new TAction([$this, 'onVoltar']), 'fas:arrow-left #FFFFFF');
        $this->btn_onvoltar = $btn_onvoltar;
        $btn_onvoltar->addStyleClass('btn-primary'); 

        $btnAvancar = $this->form->addAction("Avançar", new TAction([$this, 'avancar']), 'fas:arrow-right #FFFFFF');
        $this->btnAvancar = $btnAvancar;
        $btnAvancar->addStyleClass('btn-success'); 

        parent::add($this->form);

    }

    public static function onSelectDia($param = null) 
    {
        try 
        {

            $param['formName'] = TSession::getValue('formName');

            TTransaction::open('escritorio');

            $agenda_id = TSession::getValue('agenda_id');
            $agenda = Agenda::find($agenda_id);
            $horario_inicial = $agenda -> horario_inicial;
            $horario_final   = $agenda -> horario_final;
            $duracao         = $agenda -> duracao;
            $intervalo_ini   = $agenda -> horario_inicio_intervalo;
            $intervalo_fim   = $agenda -> horario_fim_intervalo;

            TSession::setValue('btnRadioDia', $param['btnRadioDia']);

            if(empty($param['btnRadioHorario'])){
                //$param['btnRadioDia'] ?? $data = date('Y-m-d');
                TScript::create("$(\"[name='btnRadioDia']\").closest('.fb-inline-field-container').hide()");
                TScript::create("$(\"[name='btnRadioHorario']\").closest('.fb-inline-field-container').show()");

                //SEG,TER,QUA,QUI,SEX,SAB,DOM
                //1,  2,  3,  4,  5  ,6  ,0

                $hora = date('H:i', strtotime($horario_inicial)); 
                while(date('H:i', strtotime("+$duracao minutes",strtotime($hora)))<=$horario_final){

                    $inputini = $param['btnRadioDia'].' '.$hora;
                    $dateini = strtotime($inputini);
                    $dateini = date('Y-m-d H:i', $dateini);

                    $inputfin = $param['btnRadioDia'].' '.date('H:i', strtotime("+$duracao minutes",strtotime($hora)));
                    $datefin = strtotime($inputfin);
                    $datefin = date('Y-m-d H:i', $datefin);

                    $agendamentos = Agendamento::where('agenda_id','=',$agenda_id)
                                                ->where('estado_agenda_id', 'NOT IN', "(SELECT id FROM estado_agenda WHERE estado_final = 'S')")
                                                ->where('dt_inicial', '<', $datefin)
                                                ->where('dt_final', '>', $dateini)
                                                ->count();

                    $bloqueios = Bloqueio::where('agenda_id','=',$agenda_id)
                                                ->where('dt_inicio', '<', $datefin)
                                                ->where('dt_final', '>', $dateini)
                                                ->count();

                    if ($agendamentos < 1){
                        if($bloqueios < 1){
                            if($intervalo_ini && $intervalo_fim){
                                if($intervalo_ini<date('H:i', strtotime("+$duracao minutes",strtotime($hora))) && $intervalo_fim>$hora){

                                    $hora = date('H:i', strtotime("+$duracao minutes",strtotime($hora)));

                                }else{

                                    $radios[$hora]=$hora;

                                    $hora = date('H:i', strtotime("+$duracao minutes",strtotime($hora)));
                                }
                            }else{
                                $radios[$hora]=$hora;

                                $hora = date('H:i', strtotime("+$duracao minutes",strtotime($hora))); 
                            }
                        }else{
                            $hora = date('H:i', strtotime("+$duracao minutes",strtotime($hora)));
                        }
                    }else{
                        $hora = date('H:i', strtotime("+$duracao minutes",strtotime($hora)));
                    }
                }

                TCheckGroup::reload(self::$formName, 'btnRadioHorario', $radios, $radios);
                TTransaction::close();
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onSelectHorario($param = null) 
    {
        try 
        {
            TButton::enableField(self::$formName, 'btnAvancar');
            TTransaction::open('escritorio');

            $agenda_id = TSession::getValue('agenda_id');
            $agenda = Agenda::find($agenda_id);
            $duracao = $agenda -> duracao;

            $hr_selec = $param['btnRadioHorario'];

            //$primeiro = $hr_selec[0];
            //$ultimo = end($hr_selec);

            //print($primeiro." - ".$ultimo);
            //var_dump($hr_selec);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onVoltar($param = null) 
    {
        try 
        {
            $param['formName'] = TSession::getValue('formName');
            ModalBuscarHorariosDisponiveis::onShow($param['formName']);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function avancar($param = null) 
    {
        try 
        {
            TTransaction::open('escritorio');

            $agenda_id = TSession::getValue('agenda_id');
            $agenda = Agenda::find($agenda_id);
            $duracao = $agenda -> duracao;

            if(isset($param['btnRadioHorario']))
                TSession::setValue('btnRadioHorario', $param['btnRadioHorario']);
            else
                throw new Exception('Selecione um horário!');

            if(TSession::getValue('btnRadioHorario') && TSession::getValue('btnRadioDia') && TSession::getValue('formName')){

                $hr_selec = $param['btnRadioHorario'];

                $primeiro = $hr_selec[0];
                $ultimo = end($hr_selec);

                $cHora = $primeiro;
                $quant = 1;

                while($cHora<$ultimo){
                    $cHora = date('H:i', strtotime("+$duracao minutes",strtotime($cHora)));
                    $quant++;
                }

                if(count($hr_selec)!=$quant){
                    throw new Exception('Selecione apenas horários sequênciais!');
                }

                $dia = date( 'd/m/Y' , strtotime( TSession::getValue('btnRadioDia') ) );
                $hr_ini = $primeiro;
                $hr_fin = date('H:i', strtotime("+$duracao minutes",strtotime($ultimo)));

                TScript::create("$(\"[page_name='ModalBuscarHorariosDisponiveis']\").remove()");
                TToast::show("show", "Horário adicionado!", "topRight", "fas:check-circle");

                $object = new stdClass();
                $object->mostra_horario = "$dia - $hr_ini até $hr_fin";
                $object->dt_inicial = date('Y-m-d H:i',strtotime(TSession::getValue('btnRadioDia')." ".$hr_ini));
                $object->dt_final = date('Y-m-d H:i',strtotime(TSession::getValue('btnRadioDia')." ".$hr_fin));

                $options = [];

                if (! empty($object->dt_inicial))
                {
                    $date = $object->dt_inicial;
                    $options['TD']  = 'Todos os dias';
                    $options['TDU'] = 'Todos os dias utéis';
                    $options['S']   = 'Semanal a cada: ' . DateService::getDayWeek($date);
                    $options['M']   = 'Toda a '. DateService::getWeekOfMonth($date) . ' do mês';
                    $options['A']   = 'Anual em ' . date('d', strtotime($date)) . ' de ' . DateService::getMonthName($date);
                }

                TCombo::reload(TSession::getValue('formName'), 'tipo_repeticao', $options, true, false);

                return TForm::sendData(TSession::getValue('formName'), $object);

            }else{

                TScript::create("$(\"[page_name='ModalBuscarHorariosDisponiveis']\").remove()");
                throw new Exception('Não foi possível selecionar, tente novamente!');
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {               

        if(TSession::getValue('formName')){}else TSession::setValue('formName', $param['formName']);
        if(!empty($param['convidado_agendamento_agenda_id'])){
            TSession::setValue('convidado_agendamento_id', $param['convidado_agendamento_agenda_id']);
        }
        TScript::create("$(\"[name='btnRadioHorario']\").closest('.fb-inline-field-container').hide()");
        TButton::disableField(self::$formName, 'btnAvancar');

    } 

}

