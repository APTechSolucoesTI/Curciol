<?php

class AgendamentoFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Agendamento';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Agendamento';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $agendamento = new Agendamento($param['key']);
        // define the form title
        $this->form->setFormTitle("Agendamento");

        $transformed_agendamento_estado_agenda_nome = call_user_func(function($value, $object, $row)
        {

            return "<span class='label' style='width:235px;background-color:{$object->estado_agenda->cor}'> {$value} <span> "; 

        }, $agendamento->estado_agenda->nome, $agendamento, null);    

        $transformed_agendamento_is_online = call_user_func(function($value, $object, $row)
        {

            $label = new TElement('span');
            $label->{'class'} = 'label label-';

            if ($value == 'S' || $value == 'T') {
                $label->{'class'} .= 'success';
                $label->add('Sim');    

                return $label;
            }

            $label->{'class'} .= 'danger';
            $label->add('Não');

            return $label;
        }, $agendamento->is_online, $agendamento, null);

        $agendamento->cliente->nome = $agendamento->cliente->nome_formatado;
        $agendamento->agenda->profissional->nome = $agendamento->agenda->profissional->nome_formatado;

        $label8 = new TLabel("Agenda:", '', '14px', 'B', '100%');
        $text5 = new TTextDisplay($agendamento->agenda->nome, '', '14px', '');
        $label10 = new TLabel("Cliente:", '', '14px', 'B', '100%');
        $text3 = new TTextDisplay($agendamento->cliente->nome, '', '14px', '');
        $telefone = new TTextDisplay($agendamento->cliente->telefone, '', '14px', '');
        $label22 = new TLabel("Estado:", '', '14px', 'B', '100%');
        $text23 = new TTextDisplay($transformed_agendamento_estado_agenda_nome, '', '14px', '');
        $label14 = new TLabel("Data:", '', '14px', 'B', '100%');
        $text6 = new TTextDisplay(TDateTime::convertToMask($agendamento->dt_inicial, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '14px', '');
        $label16 = new TLabel("até", '', '12px', '');
        $text7 = new TTextDisplay(TDateTime::convertToMask($agendamento->dt_final, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '14px', '');
        $label20 = new TLabel("Profissional:", '', '14px', 'B', '100%');
        $text21 = new TTextDisplay($agendamento->agenda->profissional->nome, '', '14px', '');
        $labelEspecialidade = new TLabel("Especialidade:", '', '14px', 'B', '100%');
        $textEspecialidade = new TTextDisplay($agendamento->especialidade->descricao, '', '14px', '');
        $label2 = new TLabel("Online:", '', '14px', 'B', '100%');
        $labelOnline = new TTextDisplay($transformed_agendamento_is_online, '', '14px', '');
        $label18 = new TLabel("Observação:", '', '14px', 'B', '100%');
        $text9 = new TTextDisplay($agendamento->observacao, '', '14px', '');
        $WHATSAPP_CONFIRMACAO_AGENDAMENTO = new TButton('WHATSAPP_CONFIRMACAO_AGENDAMENTO');
        $EMAIL_CONFIRMACAO_AGENDAMENTO = new TButton('EMAIL_CONFIRMACAO_AGENDAMENTO');
        $WHATSAPP_LEMBRETE_CONSULTA = new TButton('WHATSAPP_LEMBRETE_CONSULTA');
        $EMAIL_LEMBRETE_CONSULTA = new TButton('EMAIL_LEMBRETE_CONSULTA');
        $enviar_whatsapp = new TButton('enviar_whatsapp');
        $enviar_email = new TButton('enviar_email');
        $btncancelar = new TActionLink(" Cancelar", new TAction(['AgendamentoFormView', 'onCancelar'], ['key'=> $agendamento->id]), '#F44336', '14px', 'B', 'fas:calendar-times #F44336');
        $btnconfirmar = new TActionLink("Confirmar", new TAction(['AgendamentoFormView', 'onConfirmar'], ['key'=> $agendamento->id]), '#4CAF50', '14px', 'B', 'fas:calendar-check #4CAF50');
        $btnnaocompareceu = new TActionLink(" Não compareceu", new TAction(['AgendamentoFormView', 'onNaoCompareceu'], ['key'=> $agendamento->id]), '#9E9E9E', '14px', 'B', 'fas:user-alt-slash #9E9E9E');
        $btniniciar = new TActionLink("Iniciar Atendimento", new TAction(['AgendamentoFormView', 'onIniciar'], ['key'=> $agendamento->id]), '#FF9800', '14px', 'B', 'fas:play #FF9800');
        $btnentrar = new TActionLink("Acessar atendimento", new TAction(['AgendamentoFormView', 'onAbrirAtendimento'], ['key'=> $agendamento->id]), '#009688', '14px', 'B', 'fas:pencil-alt #009688');
        $btnvalidar = new TActionLink("Validar agendamento", new TAction(['AgendamentoFormView', 'onValidarAgendamento'], ['key'=> $agendamento->id]), '#673AB7', '14px', 'B', 'fas:calendar-plus #673AB7');

        $enviar_email->setAction(new TAction(['EnviarMensagemForm', 'onShow'],['tipo' => 'EMAIL']), "Enviar e-mail manual");
        $enviar_whatsapp->setAction(new TAction(['EnviarMensagemForm', 'onShow'],['tipo' => 'WHATSAPP']), "Enviar whatsapp manual");
        $EMAIL_LEMBRETE_CONSULTA->setAction(new TAction([$this, 'onEnviarEmailLembreteConsulta']), "Enviar e-mail de lembrete de consulta");
        $EMAIL_CONFIRMACAO_AGENDAMENTO->setAction(new TAction([$this, 'onEnviarEmailConfirmacao']), "Enviar email de confirmação do agendamento");
        $WHATSAPP_LEMBRETE_CONSULTA->setAction(new TAction([$this, 'onEnviarWhatsappLembreteConsulta']), "Enviar whatsapp de lembrete de consulta");
        $WHATSAPP_CONFIRMACAO_AGENDAMENTO->setAction(new TAction([$this, 'onEnviaWhatsaapConfirmacao']), "Enviar whatsapp de confirmação do agendamento");

        $enviar_email->addStyleClass('btn-default');
        $enviar_whatsapp->addStyleClass('btn-default');
        $EMAIL_LEMBRETE_CONSULTA->addStyleClass('btn-default');
        $WHATSAPP_LEMBRETE_CONSULTA->addStyleClass('btn-default');
        $EMAIL_CONFIRMACAO_AGENDAMENTO->addStyleClass('btn-default');
        $WHATSAPP_CONFIRMACAO_AGENDAMENTO->addStyleClass('btn-default');

        $enviar_email->setImage('far:envelope #F44336');
        $enviar_whatsapp->setImage('fab:whatsapp #4CAF50');
        $EMAIL_LEMBRETE_CONSULTA->setImage('fas:envelope #FF0000');
        $WHATSAPP_LEMBRETE_CONSULTA->setImage('fab:whatsapp #4CAF50');
        $EMAIL_CONFIRMACAO_AGENDAMENTO->setImage('fas:envelope #FF0000');
        $WHATSAPP_CONFIRMACAO_AGENDAMENTO->setImage('fab:whatsapp #4CAF50');

        $btnentrar->class = 'btn btn-default';
        $btniniciar->class = 'btn btn-default';
        $btnvalidar->class = 'btn btn-default';
        $btncancelar->class = 'btn btn-default';
        $btnconfirmar->class = 'btn btn-default';
        $btnnaocompareceu->class = 'btn btn-default';


        $enviar_email->getAction()->setParameter('agendamento_id', $agendamento->id);
        $enviar_whatsapp->getAction()->setParameter('agendamento_id', $agendamento->id);

        $EMAIL_LEMBRETE_CONSULTA->getAction()->setParameter('id', $agendamento->id);
        $EMAIL_CONFIRMACAO_AGENDAMENTO->getAction()->setParameter('id', $agendamento->id);
        $WHATSAPP_LEMBRETE_CONSULTA->getAction()->setParameter('id', $agendamento->id);
        $WHATSAPP_CONFIRMACAO_AGENDAMENTO->getAction()->setParameter('id', $agendamento->id);

        $EMAIL_LEMBRETE_CONSULTA->style = 'display: none';
        $EMAIL_CONFIRMACAO_AGENDAMENTO->style = 'display: none';
        $WHATSAPP_LEMBRETE_CONSULTA->style = 'display: none';
        $WHATSAPP_CONFIRMACAO_AGENDAMENTO->style = 'display: none';

        if (MensagemService::canEnviarWhatsappLembreteConsulta($agendamento))
        {
            $EMAIL_LEMBRETE_CONSULTA->style = 'display: block';
        }
        if (MensagemService::canEnviarEmailConfirmacao($agendamento))
        {
            $EMAIL_CONFIRMACAO_AGENDAMENTO->style = 'display: block';
        }
        if (MensagemService::canEnviarEmailLembreteConsulta($agendamento))
        {
            $WHATSAPP_LEMBRETE_CONSULTA->style = 'display: block';
        }
        if (MensagemService::canEnviarWhatsappConfirmacao($agendamento))
        {
            $WHATSAPP_CONFIRMACAO_AGENDAMENTO->style = 'display: block';
        }

        $this->form->appendPage("Informações gerais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([$label8,$text5],[$label10,$text3,$telefone],[$label22,$text23]);
        $row1->layout = [' col-sm-3',' col-sm-6',' col-sm-3'];

        $row2 = $this->form->addFields([$label14,$text6,$label16,$text7],[$label20,$text21],[$labelEspecialidade,$textEspecialidade],[$label2,$labelOnline]);
        $row2->layout = ['col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row3 = $this->form->addFields([$label18,$text9]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addFields([$WHATSAPP_CONFIRMACAO_AGENDAMENTO,$EMAIL_CONFIRMACAO_AGENDAMENTO,$WHATSAPP_LEMBRETE_CONSULTA,$EMAIL_LEMBRETE_CONSULTA]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addFields([$enviar_whatsapp,$enviar_email]);
        $row5->layout = [' col-sm-12'];

        $row6 = $this->form->addContent([new TFormSeparator("Ajustar estado do agendamento", '#333', '18', '#eee')]);
        $row7 = $this->form->addFields([$btncancelar,$btnconfirmar,$btnnaocompareceu,$btniniciar,$btnentrar,$btnvalidar]);
        $row7->layout = [' col-sm-12'];

        $this->form->appendPage("Procedimentos");

        $this->agendamento_procedimento_agendamento_id_list = new TQuickGrid;
        $this->agendamento_procedimento_agendamento_id_list->style = 'width:100%';
        $this->agendamento_procedimento_agendamento_id_list->disableDefaultClick();

        $column_procedimento_nome = $this->agendamento_procedimento_agendamento_id_list->addQuickColumn("Procedimento", 'procedimento->nome', 'left');
        $column_parceiro_nome = $this->agendamento_procedimento_agendamento_id_list->addQuickColumn("Convênio", 'parceiro->nome', 'left');
        $column_quantidade_transformed = $this->agendamento_procedimento_agendamento_id_list->addQuickColumn("Quantidade", 'quantidade', 'center' , '150px');
        $column_valor_transformed = $this->agendamento_procedimento_agendamento_id_list->addQuickColumn("Valor", 'valor', 'left');
        $column_valor_total_transformed = $this->agendamento_procedimento_agendamento_id_list->addQuickColumn("Total", 'valor_total', 'left');

        $column_quantidade_transformed->setTotalFunction( function($values) { 
            return array_sum((array) $values); 
        }); 

        $column_valor_transformed->setTotalFunction( function($values) { 
            return array_sum((array) $values); 
        }); 

        $column_valor_total_transformed->setTotalFunction( function($values) { 
            return array_sum((array) $values); 
        }); 

        $column_quantidade_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return number_format($value, 2, ',', '.');

        });

        $column_valor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });

        $column_valor_total_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });

        $this->agendamento_procedimento_agendamento_id_list->createModel();

        $criteria_agendamento_procedimento_agendamento_id = new TCriteria();
        $criteria_agendamento_procedimento_agendamento_id->add(new TFilter('agendamento_id', '=', $agendamento->id));

        $criteria_agendamento_procedimento_agendamento_id->setProperty('order', 'id desc');

        $agendamento_procedimento_agendamento_id_items = AgendamentoProcedimento::getObjects($criteria_agendamento_procedimento_agendamento_id);

        $this->agendamento_procedimento_agendamento_id_list->addItems($agendamento_procedimento_agendamento_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->agendamento_procedimento_agendamento_id_list));

        $this->form->addContent([$panel]);

        $this->form->appendPage("Histórico");

        $this->estado_agendamento_agendamento_id_list = new TQuickGrid;
        $this->estado_agendamento_agendamento_id_list->datatable = 'true';
        $this->estado_agendamento_agendamento_id_list->style = 'width:100%';
        $this->estado_agendamento_agendamento_id_list->disableDefaultClick();

        $column_estado_agenda_cor_transformed = $this->estado_agendamento_agendamento_id_list->addQuickColumn("", 'estado_agenda->cor', 'left' , '50px');
        $column_estado_agenda_nome = $this->estado_agendamento_agendamento_id_list->addQuickColumn("Estado", 'estado_agenda->nome', 'left');
        $column_system_users_name = $this->estado_agendamento_agendamento_id_list->addQuickColumn("Usuário", 'system_users->name', 'left' , '250px');
        $column_atribuido_em_transformed = $this->estado_agendamento_agendamento_id_list->addQuickColumn("Data", 'atribuido_em', 'center' , '200px');

        $column_estado_agenda_cor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if ($value)
            {
                return "<div style='position: relative;text-align: center;'><span class='estado_agendamento' style='background-color: {$value}'></span></div>";
            }
            return '';
        });

        $column_atribuido_em_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
        });

        $this->estado_agendamento_agendamento_id_list->createModel();

        $criteria_estado_agendamento_agendamento_id = new TCriteria();
        $criteria_estado_agendamento_agendamento_id->add(new TFilter('agendamento_id', '=', $agendamento->id));

        $criteria_estado_agendamento_agendamento_id->setProperty('order', 'atribuido_em desc');

        $estado_agendamento_agendamento_id_items = EstadoAgendamento::getObjects($criteria_estado_agendamento_agendamento_id);

        $this->estado_agendamento_agendamento_id_list->addItems($estado_agendamento_agendamento_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->estado_agendamento_agendamento_id_list));

        $this->form->addContent([$panel]);

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        if ($agendamento->estado_agenda->estado_final == 'S')
        {

             if($agendamento->estado_agenda_id != EstadoAgenda::ATENDIDO){
                 $btnentrar->style = 'display: none;';
             }
             $btniniciar->style = 'display: none;';
             $btncancelar->style = 'display: none;';
             $btnconfirmar->style = 'display: none;';
             $btnnaocompareceu->style = 'display: none;';
             $btnvalidar->style = 'display: none;';
        }
        else if ($agendamento->estado_agenda->estado_inicial == 'S')
        {
             $btnentrar->style = 'display: none;';
             $btniniciar->style = 'display: none;';
             $btnnaocompareceu->style = 'display: none;';
             $btnvalidar->style = 'display: none;';
        }
        else if ($agendamento->estado_agenda_id == EstadoAgenda::CONFIRMADO)
        {
             $btncancelar->style = 'display: none;';
             $btnconfirmar->style = 'display: none;';
             $btnnaocompareceu->style = 'display: none;';
             $btnentrar->style = 'display: none;';
             $btnvalidar->style = 'display: none;';
        }
        else if ($agendamento->estado_agenda_id == EstadoAgenda::EM_ATENDIMENTO)
        {
             $btniniciar->style = 'display: none;';
             $btncancelar->style = 'display: none;';
             $btnconfirmar->style = 'display: none;';
             $btnnaocompareceu->style = 'display: none;';
             $btnvalidar->style = 'display: none;';
        }
        else if ($agendamento->estado_agenda_id == EstadoAgenda::AGUARDANDO_VALIDACAO_ESCRITORIO)
        {
             $btniniciar->style = 'display: none;';
             $btnentrar->style = 'display: none;';

             $btnconfirmar->style = 'display: none;';
             $btnnaocompareceu->style = 'display: none;';

        }
        else
        {
             $btnentrar->style = 'display: none;';
             $btniniciar->style = 'display: none;';
             $btnnaocompareceu->style = 'display: none;';
             $btnvalidar->style = 'display: none;';
        }

        // Caso não for o responsavel pela agenda não deixa fazer o inicio e nem entrar
        $logado = Pessoa::where('system_users_id','=',TSession::getValue('userid'))->first();
        if($logado){
            $permitido = AgendaProfissional::where('agenda_id','=',$agendamento->agenda_id)->where('profissional_id','=',$logado->id);
            if (!$permitido)
            {
                $btnentrar->style = 'display: none;';
                $btniniciar->style = 'display: none;';
            }
        }

        $btnVisualizarClienteAction = new TAction(['AgendamentoFormView', 'onVisualizarCliente'],['key'=>$agendamento->id]);
        $btnVisualizarClienteLabel = new TLabel("Visualizar cliente");

        $btnVisualizarCliente = $this->form->addHeaderAction($btnVisualizarClienteLabel, $btnVisualizarClienteAction, 'fas:user #000000'); 
        $btnVisualizarClienteLabel->setFontSize('12px'); 
        $btnVisualizarClienteLabel->setFontColor('#333'); 

        $btnAgendamentoCalendarFormOnEditAction = new TAction(['AgendamentoCalendarForm', 'onEdit'],['key'=>$agendamento->id]);
        $btnAgendamentoCalendarFormOnEditLabel = new TLabel("Editar");

        $btnAgendamentoCalendarFormOnEdit = $this->form->addHeaderAction($btnAgendamentoCalendarFormOnEditLabel, $btnAgendamentoCalendarFormOnEditAction, 'fas:edit #03A9F4'); 
        $btnAgendamentoCalendarFormOnEditLabel->setFontSize('12px'); 
        $btnAgendamentoCalendarFormOnEditLabel->setFontColor('#333'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        $btnVisualizarClienteAction->setParameter('cliente_id',$agendamento->cliente_id);

        TTransaction::close();
        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AgendamentoFormView]');
        $style->width = '85% !important';   
        $style->show(true);

    }

    public static function onEnviaWhatsaapConfirmacao($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['id']);

            MensagemService::enviarWhatsappConfirmacao($agendamento);

            TTransaction::close();

            TToast::show('success', 'WhatsApp enviado');

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            TToast::show('error', $e->getMessage());
        }
    }

    public static function onEnviarEmailConfirmacao($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['id']);

            MensagemService::enviarEmailConfirmacao($agendamento);

            TTransaction::close();

            TToast::show('success', 'E-mail enviado');

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            TToast::show('error', $e->getMessage());
        }
    }

    public static function onEnviarWhatsappLembreteConsulta($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['id']);

            MensagemService::enviarWhatsappLembreteConsulta($agendamento);

            TTransaction::close();

            TToast::show('success', 'WhatsApp enviado');

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            TToast::show('error', $e->getMessage());
        }
    }

    public static function onEnviarEmailLembreteConsulta($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['id']);

            MensagemService::enviarEmailLembreteConsulta($agendamento);

            TTransaction::close();

            TToast::show('success', 'E-mail enviado');

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            TToast::show('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {     

    }

    public  function onVisualizarCliente($param = null) 
    {
        try 
        {
            TScript::create("$(\"[page_name='ClienteForm']\").remove()");
            TTransaction::open(self::$database);
            $param['cliente_id'] = (Agendamento::find($param['key']))->cliente_id;

            TApplication::loadPage('ClienteForm', 'onEdit', ['key' => $param['cliente_id'], 'agendamento' => $param['key']]);

            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onCancelar($param)
    {
        try{
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['key']);

            if (! in_array($agendamento->estado_agenda_id, [EstadoAgenda::AGENDADO, EstadoAgenda::AGUARDANDO_VALIDACAO_ESCRITORIO]))
            {
                throw new Exception('Você não pode cancelar esse agendamento!');
            }

            $agendamento->estado_agenda_id = EstadoAgenda::CANCELADO;
            $agendamento->store();

            $historico = new EstadoAgendamento();
            $historico->agendamento_id = $agendamento->id;
            $historico->estado_agenda_id = $agendamento->estado_agenda_id;
            $historico->system_users_id = TSession::getValue('userid');
            $historico->atribuido_em = date('Y-m-d H:i:s');
            $historico->store();

            TToast::show('success', 'Estado alterado com sucesso');

            TTransaction::close();

            $pageParam = ['key' => $agendamento->id];

            TApplication::loadPage('AgendamentoFormView', 'onShow', $pageParam);
            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onIniciar($param)
    {
        try{
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['key']);

            if ($agendamento->estado_agenda->estado_final == 'S')
            {
                throw new Exception('Esse agendamento já está finalizado!');
            }

            $agendamento->estado_agenda_id = EstadoAgenda::EM_ATENDIMENTO;
            $agendamento->store();

            $historico = new EstadoAgendamento();
            $historico->agendamento_id = $agendamento->id;
            $historico->estado_agenda_id = $agendamento->estado_agenda_id;
            $historico->system_users_id = TSession::getValue('userid');
            $historico->atribuido_em = date('Y-m-d H:i:s');
            $historico->store();

            $atendimento = AtendimentoService::iniciarAtendimento($agendamento);

            TTransaction::close();

            TToast::show('success', 'Estado alterado com sucesso');

            TTransaction::close();

            $pageParam = ['key' => $agendamento->id];

            TApplication::loadPage('AtendimentoFormView', 'onShow', ['key' => $atendimento->id, 'id' => $atendimento->id]);

            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onNaoCompareceu($param)
    {
        try{
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['key']);

            if ($agendamento->estado_agenda->estado_final == 'S')
            {
                throw new Exception('Esse agendamento já está finalizado!');
            }

            $agendamento->estado_agenda_id = EstadoAgenda::NAO_COMPARECEU;
            $agendamento->store();

            $historico = new EstadoAgendamento();
            $historico->agendamento_id = $agendamento->id;
            $historico->estado_agenda_id = $agendamento->estado_agenda_id;
            $historico->system_users_id = TSession::getValue('userid');
            $historico->atribuido_em = date('Y-m-d H:i:s');
            $historico->store();

            TToast::show('success', 'Estado alterado com sucesso');

            TTransaction::close();

            $pageParam = ['key' => $agendamento->id];

            TApplication::loadPage('AgendamentoFormView', 'onShow', $pageParam);
            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onConfirmar($param)
    {
        try{
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['key']);

            if ($agendamento->estado_agenda_id != EstadoAgenda::AGENDADO)
            {
                throw new Exception('Você não pode confirmar esse agendamento!');
            }

            if(!$agendamento->cliente->telefone){
                throw new Exception('Não foi possivel confirmar o agendamento! Cadastre um telefone e tente novamente.');
            }

            $objeto = PessoaEndereco::where('pessoa_id', '=', $agendamento->cliente_id)
                                    ->where('principal', '=', 'S')
                                    ->count();
            if($objeto<1){
                throw new Exception('Não foi possivel confirmar o agendamento! Cadastre um endereço principal e tente novamente.');
            }

            //PESSOA FISICA
            if($agendamento->cliente->tipo_pessoa_id == TipoPessoa::FISICA){
                if(!$agendamento->cliente->cpf_cnpj){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre o CPF e tente novamente.');
                }
                if(!$agendamento->cliente->rg_ie){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre o RG e tente novamente.');
                }
                if(!$agendamento->cliente->orgao_emissor){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre o orgao emissor do RG e tente novamente.');
                }
                if(!$agendamento->cliente->dt_nascimento_abertura){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre a data de nascimento e tente novamente.');
                }
                if(!$agendamento->cliente->nacionalidade){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre a nacionalidade e tente novamente.');
                }
                if(!$agendamento->cliente->sexo){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre o sexo e tente novamente.');
                }
                if(!$agendamento->cliente->estado_civil){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre o estado civil e tente novamente.');
                }
            }

            if($agendamento->cliente->tipo_pessoa_id == TipoPessoa::JURIDICA){
                if(!$agendamento->cliente->cpf_cnpj){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre o CNPJ e tente novamente.');
                }
                if(!$agendamento->cliente->dt_nascimento_abertura){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre a data de abertura e tente novamente.');
                }
                $objeto = PessoaRepresentantesLegais::where('pessoa_juridica_id', '=', $agendamento->cliente_id)
                                                    ->count();
                if($objeto<1){
                    throw new Exception('Não foi possivel confirmar o agendamento! Cadastre um representante legal e tente novamente.');
                }
            }

            $agendamento->estado_agenda_id = EstadoAgenda::CONFIRMADO;
            $agendamento->store();

            $historico = new EstadoAgendamento();
            $historico->agendamento_id = $agendamento->id;
            $historico->estado_agenda_id = $agendamento->estado_agenda_id;
            $historico->system_users_id = TSession::getValue('userid');
            $historico->atribuido_em = date('Y-m-d H:i:s');
            $historico->store();

            $atendimento = new Atendimento;
            $atendimento->agendamento_id = $agendamento->id;
            $atendimento->cliente_id = $agendamento->cliente_id;
            $atendimento->profissional_id = $agendamento->get_agenda()->profissional_id;
            $atendimento->tipo_atendimento_id = TipoAtendimento::AGENDADO;
            $atendimento->criacao_user_id = TSession::getValue('userid');
            $atendimento->store();

            $agendamentoProcedimentos = $agendamento->getAgendamentoProcedimentos();
            $total = 0;
            if ($agendamentoProcedimentos)
            {
                foreach($agendamentoProcedimentos as $agendamentoProcedimento)
                {
                    $atendimentoProcedimento = new AtendimentoProcedimento;
                    $atendimentoProcedimento->atendimento_id = $atendimento->id;
                    $atendimentoProcedimento->quantidade = $agendamentoProcedimento->quantidade;
                    $atendimentoProcedimento->valor = $agendamentoProcedimento->valor;
                    $atendimentoProcedimento->valor_total = $agendamentoProcedimento->valor_total;
                    $atendimentoProcedimento->procedimento_id = $agendamentoProcedimento->procedimento_id;
                    $atendimentoProcedimento->parceiro_id = $agendamentoProcedimento->parceiro_id;
                    $atendimento->criacao_user_id = TSession::getValue('userid');
                    $atendimentoProcedimento->store();

                    $total += $atendimentoProcedimento->valor;
                }
            }

            $atendimento->valor_total = $total;
            $atendimento->modificacao_user_id = TSession::getValue('userid');
            $atendimento->store();
            $agendamento->atendimento = $atendimento;

            if($atendimento->valor_total>0){
                AtendimentoService::gerarContaReceber($atendimento);
            }

            TToast::show('success', 'Estado alterado com sucesso');

            TTransaction::close();

            $pageParam = ['key' => $agendamento->id];

            TApplication::loadPage('AgendamentoFormView', 'onShow', $pageParam);

            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onValidarAgendamento($param)
    {
        try{

            TTransaction::open(self::$database);

            $agendamento = Agendamento::find($param['key']);
            $agendamento->estado_agenda_id = EstadoAgenda::AGENDADO;
            $agendamento->store();

            $historico = new EstadoAgendamento();
            $historico->agendamento_id = $agendamento->id;
            $historico->estado_agenda_id = $agendamento->estado_agenda_id;
            $historico->system_users_id = TSession::getValue('userid');
            $historico->atribuido_em = date('Y-m-d H:i:s');
            $historico->store();

            TToast::show('success', 'Estado alterado com sucesso');

            TTransaction::close();

            $pageParam = ['key' => $agendamento->id];

            TApplication::loadPage('AgendamentoFormView', 'onShow', $pageParam);
            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onAbrirAtendimento($param)
    {
        try 
        {

            TTransaction::open(self::$database);

            $atendimento = Atendimento::where('agendamento_id', '=', $param['key'])->first();

            TTransaction::close();

            $pageParam = ['key' => $atendimento->id]; // ex.: = ['key' => 10]

            TApplication::loadPage('AtendimentoFormView', 'onShow', $pageParam);
            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");

        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }

    }

}

