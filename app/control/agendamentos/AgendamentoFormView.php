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
        $enviar_whatsapp = new TButton('enviar_whatsapp');
        $enviar_email = new TButton('enviar_email');
        $btncancelar = new TActionLink(" Cancelar", new TAction(['AgendamentoFormView', 'onCancelar'], ['key'=> $agendamento->id]), '#F44336', '14px', 'B', 'fas:calendar-times #F44336');
        $btnconfirmar = new TActionLink("Confirmar", new TAction(['AgendamentoFormView', 'onConfirmar'], ['key'=> $agendamento->id]), '#4CAF50', '14px', 'B', 'fas:calendar-check #4CAF50');
        $btnnaocompareceu = new TActionLink(" Não compareceu", new TAction(['AgendamentoFormView', 'onNaoCompareceu'], ['key'=> $agendamento->id]), '#9E9E9E', '14px', 'B', 'fas:user-alt-slash #9E9E9E');
        $btniniciar = new TActionLink("Iniciar Atendimento", new TAction(['AgendamentoFormView', 'onIniciar'], ['key'=> $agendamento->id]), '#FF9800', '14px', 'B', 'fas:play #FF9800');
        $btnentrar = new TActionLink("Acessar atendimento", new TAction(['AgendamentoFormView', 'onAbrirAtendimento'], ['key'=> $agendamento->id]), '#009688', '14px', 'B', 'fas:pencil-alt #009688');
        $btnvalidar = new TActionLink("Validar agendamento", new TAction(['AgendamentoFormView', 'onValidarAgendamento'], ['key'=> $agendamento->id]), '#673AB7', '14px', 'B', 'fas:calendar-plus #673AB7');
        $btncancelarconfirmacao = new TActionLink(" Cancelar confirmação", new TAction(['AgendamentoFormView', 'onCancelarConfirmacao'], ['key'=> $agendamento->id, 'static' => 1]), '#F44336', '14px', 'B', 'fas:calendar-minus #F44336');

        $enviar_email->setAction(new TAction(['EnviarMensagemForm', 'onShow'],['tipo' => 'EMAIL']), "Enviar e-mail");
        $enviar_whatsapp->setAction(new TAction(['EnviarMensagemForm', 'onShow'],['tipo' => 'WHATSAPP']), "Enviar WhatsApp");
        // um botao de cada canal: a cortina escolhe contato, template e Manual (04/11/2026)

        $enviar_email->addStyleClass('btn-default');
        $enviar_whatsapp->addStyleClass('btn-default');

        $enviar_email->setImage('far:envelope #F44336');
        $enviar_whatsapp->setImage('fab:whatsapp #4CAF50');

        $btnentrar->class = 'btn btn-default';
        $btniniciar->class = 'btn btn-default';
        $btnvalidar->class = 'btn btn-default';
        $btncancelarconfirmacao->class = 'btn btn-default';
        $btncancelarconfirmacao->style = 'display: none;';
        $btncancelar->class = 'btn btn-default';
        $btnconfirmar->class = 'btn btn-default';
        $btnnaocompareceu->class = 'btn btn-default';


        $enviar_email->getAction()->setParameter('agendamento_id', $agendamento->id);
        $enviar_whatsapp->getAction()->setParameter('agendamento_id', $agendamento->id);




        $this->form->appendPage("Atendimento");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([$label8,$text5],[$label10,$text3,$telefone],[$label22,$text23]);
        $row1->layout = [' col-sm-3',' col-sm-6',' col-sm-3'];

        $row2 = $this->form->addFields([$label14,$text6,$label16,$text7],[$label20,$text21],[$labelEspecialidade,$textEspecialidade],[$label2,$labelOnline]);
        $row2->layout = ['col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row3 = $this->form->addFields([$label18,$text9]);
        $row3->layout = [' col-sm-12'];

        $row5 = $this->form->addFields([$enviar_whatsapp,$enviar_email]);
        $row5->layout = [' col-sm-12'];

        foreach (self::linhasDaConfirmacao($agendamento) as $linha)
        {
            $rowConfirmacao = $this->form->addFields([new TLabel($linha, '#555', '13px', null, '100%')]);
            $rowConfirmacao->layout = [' col-sm-12'];
        }

        $row6 = $this->form->addContent([new TFormSeparator("Ajustar estado do agendamento", '#333', '18', '#eee')]);
        $row7 = $this->form->addFields([$btncancelar,$btnconfirmar,$btncancelarconfirmacao,$btnnaocompareceu,$btniniciar,$btnentrar,$btnvalidar]);
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

        // Curciol (29/10/2026): financeiro do agendamento numa aba propria (30/10/2026: so para quem gera o financeiro)
        $this->adicionarFinanceiro($agendamento);

        $this->form->appendPage("Histórico");

        $this->estado_agendamento_agendamento_id_list = new TQuickGrid;
        $this->estado_agendamento_agendamento_id_list->datatable = 'true';
        $this->estado_agendamento_agendamento_id_list->style = 'width:100%';
        $this->estado_agendamento_agendamento_id_list->disableDefaultClick();

        $column_estado_agenda_cor_transformed = $this->estado_agendamento_agendamento_id_list->addQuickColumn("", 'estado_agenda->cor', 'left' , '50px');
        $column_estado_agenda_nome = $this->estado_agendamento_agendamento_id_list->addQuickColumn("Estado", 'estado_agenda->nome', 'left');
        $column_system_users_name = $this->estado_agendamento_agendamento_id_list->addQuickColumn("Usuário", 'system_users->name', 'left' , '250px');
        $column_atribuido_em_transformed = $this->estado_agendamento_agendamento_id_list->addQuickColumn("Data", 'atribuido_em', 'center' , '200px');
        $column_observacao = $this->estado_agendamento_agendamento_id_list->addQuickColumn("Observação", 'observacao', 'left');

        // sem usuario = o cliente respondeu (WhatsApp ou link do e-mail)
        $column_system_users_name->setTransformer(function($value) { return trim((string) $value) !== '' ? $value : 'Automático'; });
        $column_observacao->setTransformer(function($value) { return htmlspecialchars((string) $value); });

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
             $btncancelarconfirmacao->style = '';
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

    public function onShow($param = null)
    {     

    }

    /**
     * Aba "Financeiro" (separada do atendimento): parceria de repasse do agendamento e a divisao por colaborador; sem
     * financeiro, o botao "Gerar financeiro" (janela AgendamentoFinanceiroForm); com financeiro, a conta a receber gerada
     * (situacao e link) e o "Desfazer financeiro". A aba (30/10/2026) so aparece para quem gera o financeiro (quem tem a
     * janela: grupos Financeiro e Financeiro do agendamento).
     */
    private function adicionarFinanceiro(Agendamento $agendamento): void
    {
        $pode = AgendamentoFinanceiroForm::podeGerar();
        if (!$pode) { return; }
        $ficha = AgendamentoFinanceiroService::ficha((int) $agendamento->id);

        $this->form->appendPage("Financeiro");

        // parceria e divisao: a do financeiro gerado (valor da conta) ou a do agendamento (total dos procedimentos)
        $parceriaId = (int) ($ficha->parceria_levantamento_id ?? $agendamento->parceria_levantamento_id);
        $valor = $ficha ? (float) $ficha->valor : AgendamentoFinanceiroService::valorDoAgendamento((int) $agendamento->id);
        $parceria = $parceriaId > 0 ? ParceriaLevantamento::find($parceriaId) : null;
        $row = $this->form->addFields(
            [new TLabel("Parceria de repasse:", '', '14px', 'B', '100%'), new TTextDisplay($parceria ? htmlspecialchars((string) $parceria->nome) : 'Não informada (escolha ao editar o agendamento ou no Gerar financeiro)', '', '14px', '')],
            [new TLabel($ficha ? "Valor da conta a receber:" : "Total dos procedimentos:", '', '14px', 'B', '100%'), new TTextDisplay('R$ ' . number_format($valor, 2, ',', '.'), '', '14px', '')]
        );
        $row->layout = [' col-sm-6', ' col-sm-6'];

        if ($parceria) {
            $linhas = AgendamentoFinanceiroService::divisao($parceriaId, $valor, AgendamentoFinanceiroService::escritorioDoAgendamento((int) $agendamento->id));
            $tabela = new TElement('table');
            $tabela->class = 'table table-sm';
            $tabela->style = 'width:100%; max-width:720px; font-size:13px';
            $tabela->add('<thead><tr><th>Colaborador</th><th style="text-align:right">Porcentagem</th><th style="text-align:right">Valor</th></tr></thead>');
            foreach ($linhas as $l) {
                $tabela->add('<tr><td>' . htmlspecialchars($l['nome']) . '</td><td style="text-align:right">' . number_format($l['percentual'], 2, ',', '.') . '%</td><td style="text-align:right">R$ '
                    . number_format($l['valor'], 2, ',', '.') . '</td></tr>');
            }
            $row = $this->form->addFields([new TLabel("Divisão por colaborador:", '', '14px', 'B', '100%'), $tabela]);
            $row->layout = [' col-sm-12'];
        }

        $this->form->addContent([new TFormSeparator("Conta a receber", '#333', '18', '#eee')]);
        if (!$ficha) {
            if (!$pode) {
                $this->form->addFields([new TTextDisplay('Financeiro ainda não gerado.', '#6f7d92', '14px', '')]);
                return;
            }
            if ((string) $agendamento->estado_agenda_id === (string) EstadoAgenda::CANCELADO) {
                $this->form->addFields([new TTextDisplay('Agendamento cancelado não gera financeiro.', '#6f7d92', '14px', '')]);
                return;
            }
            $gerar = new TActionLink(" Gerar financeiro", new TAction(['AgendamentoFinanceiroForm', 'onShow'], ['agendamento_id' => $agendamento->id]), '#1565C0', '14px', 'B', 'fas:hand-holding-usd #1565C0');
            $gerar->class = 'btn btn-default';
            $row = $this->form->addFields([$gerar]);
            $row->layout = [' col-sm-12'];
            return;
        }

        $conta = Conta::find((int) $ficha->conta_receber_id);
        $quitada = $conta && in_array($conta->quitada, ['S', 'T'], true);
        $situacao = $quitada ? "<span class='label label-success'>Recebida</span>" : "<span class='label label-warning'>Em aberto</span>";
        $texto = new TElement('div');
        $texto->style = 'font-size:14px; padding:6px 0';
        $texto->add('Conta a receber nº ' . (int) $ficha->conta_receber_id . ' - R$ ' . number_format((float) $ficha->valor, 2, ',', '.') . ' ' . $situacao);

        $campos = [$texto];
        // a conta a receber so abre para quem tem a tela (grupo Financeiro)
        if (SystemPermission::checkPermission('ContaFormView')) {
            $ver = new TActionLink(" Ver conta a receber", new TAction(['ContaFormView', 'onShow'], ['key' => (int) $ficha->conta_receber_id]), '#1565C0', '14px', 'B', 'fas:file-invoice-dollar #1565C0');
            $ver->class = 'btn btn-default';
            $campos[] = $ver;
        }
        if ($pode) {
            $desfazer = new TActionLink(" Desfazer financeiro", new TAction(['AgendamentoFinanceiroForm', 'onDesfazer'], ['key' => $agendamento->id]), '#F44336', '14px', 'B', 'fas:undo #F44336');
            $desfazer->class = 'btn btn-default';
            $campos[] = $desfazer;
        }
        $row = $this->form->addFields($campos);
        $row->layout = [' col-sm-12'];
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
            // com financeiro gerado, desfazer antes (29/10/2026)
            AgendamentoFinanceiroService::exigirCancelavel((int) $agendamento->id);

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

            $agendamento->atendimento = AgendamentoService::criarAtendimento($agendamento);

            // Curciol (29/10/2026): a conta a receber do agendamento nao nasce mais no Confirmar - so pelo
            // "Gerar financeiro" (com parceria de repasse; AgendamentoFinanceiroService)

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

    /**
     * Linhas acima dos botoes de estado (04/11/2026). Confirmacao cancelada: so o cancelamento, e o que veio antes dele nao
     * aparece mais; confirmou de novo: o cancelamento sai da tela (fica no historico).
     */
    private static function linhasDaConfirmacao(Agendamento $agendamento): array
    {
        $c = TTransaction::get();
        $id = (int) $agendamento->id;
        $estado = (int) $agendamento->estado_agenda_id;
        $data = fn($d) => date('d/m/Y H:i', strtotime($d));

        $evento = function (array $eventos) use ($c, $id) {
            $st = $c->query("SELECT e.atribuido_em, e.observacao, e.evento, u.name
                               FROM estado_agendamento e LEFT JOIN system_users u ON u.id = e.system_users_id
                              WHERE e.agendamento_id = {$id} AND e.evento IN ('" . implode("','", $eventos) . "')
                              ORDER BY e.atribuido_em DESC, e.id DESC LIMIT 1");
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        };

        $cancelada = $evento([EstadoAgendamento::EVENTO_CONFIRMACAO_CANCELADA]);
        $desde = $cancelada ? $cancelada['atribuido_em'] : '';
        $linhas = [];

        $email = $evento([EstadoAgendamento::EVENTO_CONFIRMADO_EMAIL, EstadoAgendamento::EVENTO_CANCELADO_EMAIL]);
        if ($email && $email['atribuido_em'] > $desde
            && $estado === (int) ($email['evento'] === EstadoAgendamento::EVENTO_CONFIRMADO_EMAIL ? EstadoAgenda::CONFIRMADO : EstadoAgenda::CANCELADO))
        {
            $linhas[] = '<i class="far fa-envelope" style="color:#F44336"></i> <b>Agendamento ' . ($email['evento'] === EstadoAgendamento::EVENTO_CONFIRMADO_EMAIL ? 'confirmado' : 'cancelado')
                      . ' pelo cliente</b> - pelo link do e-mail em ' . $data($email['atribuido_em']);
        }
        else
        {
            $confirmacao = $c->query("SELECT m.enviado_em, m.destino, m.resposta, m.resposta_texto, m.respondido_em, m.resposta_obs
                                        FROM mensagem m JOIN template_escritorio t ON t.id = m.template_escritorio_id
                                       WHERE m.agendamento_id = {$id} AND m.tipo_mensagem = 'WHATSAPP' AND t.categoria = '" . TemplateEscritorio::CONFIRMACAO . "'
                                       ORDER BY m.enviado_em DESC, m.id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($confirmacao && $confirmacao['enviado_em'] > $desde)
            {
                $linhas[] = '<i class="fab fa-whatsapp" style="color:#4CAF50"></i> ' . self::textoDaConfirmacao($agendamento, $confirmacao);
            }
        }

        if ($cancelada && $estado !== (int) EstadoAgenda::CONFIRMADO)
        {
            $linhas[] = '<i class="fas fa-calendar-minus" style="color:#F44336"></i> Confirmação cancelada por ' . htmlspecialchars((string) $cancelada['name'])
                      . ' em ' . $data($cancelada['atribuido_em']) . ': ' . htmlspecialchars((string) $cancelada['observacao']);
        }

        return $linhas;
    }

    /** Linha da confirmacao por WhatsApp na tela. */
    private static function textoDaConfirmacao(Agendamento $agendamento, array $c): string
    {
        $quando = fn($d) => $d ? date('d/m/Y H:i', strtotime($d)) : '';
        $numero = PessoaContatoService::formatarTelefone($c['destino']);
        $estado = (int) $agendamento->estado_agenda_id;
        $resposta = $c['resposta'];
        $respondeu = htmlspecialchars($numero . ' respondeu "' . ApchatRespostaService::semAssinatura($c['resposta_texto']) . '"') . ' em ' . $quando($c['respondido_em']);

        if ($resposta === null)
        {
            return 'Confirmação por WhatsApp enviada em ' . $quando($c['enviado_em']) . ' para ' . htmlspecialchars($numero) . ': <span style="color:#FF9800"><b>aguardando resposta</b></span>';
        }
        if ($resposta === ApchatRespostaService::SIM && $estado === (int) EstadoAgenda::CONFIRMADO)
        {
            return '<b>Agendamento confirmado pelo cliente</b> - ' . $respondeu;
        }
        if ($resposta === ApchatRespostaService::NAO && $estado === (int) EstadoAgenda::CANCELADO)
        {
            return '<b>Agendamento cancelado pelo cliente</b> - ' . $respondeu;
        }
        if ($resposta === ApchatRespostaService::SIM)
        {
            return $respondeu;
        }
        if (in_array($resposta, [ApchatRespostaService::NAO, ApchatRespostaService::OUTRA], true))
        {
            return $respondeu . ($c['resposta_obs'] ? ' - ' . htmlspecialchars($c['resposta_obs']) : '');
        }

        return 'Confirmação por WhatsApp enviada em ' . $quando($c['enviado_em']) . ' para ' . htmlspecialchars($numero) . ($c['resposta_obs'] ? ' - ' . htmlspecialchars($c['resposta_obs']) : '');
    }

    /** Janela do "Cancelar confirmacao": confirma a acao e pede a observacao (04/11/2026). */
    public static function onCancelarConfirmacao($param)
    {
        $form = new BootstrapFormBuilder('form_cancelar_confirmacao');
        $form->setFieldSizes('100%');

        $observacao = new TText('observacao');
        $observacao->setSize('100%', 100);

        $form->addContent(['<div style="margin-bottom:8px">O agendamento volta para <b>Agendado</b>. Depois você pode remarcar mudando o horário ou cancelar. O cliente não é avisado automaticamente.</div>']);
        $row = $form->addFields([new TLabel('Observação:', '#ff0000', '14px', null, '100%'), $observacao]);
        $row->layout = ['col-sm-12'];

        $btn = $form->addAction('Cancelar confirmação', new TAction([__CLASS__, 'onSalvarCancelarConfirmacao'], ['key' => (int) ($param['key'] ?? 0), 'static' => 1]), 'fas:calendar-minus #ffffff');
        $btn->addStyleClass('btn-danger');

        $window = TWindow::create('Cancelar confirmação', 0.5, null);
        $window->setProperty('class', 'window_modal');
        $window->add($form);
        $window->show();
    }

    public static function onSalvarCancelarConfirmacao($param)
    {
        try
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find((int) ($param['key'] ?? 0));

            if (!$agendamento)
            {
                throw new Exception('Agendamento não encontrado.');
            }

            AgendamentoService::cancelarConfirmacao($agendamento, (string) ($param['observacao'] ?? ''));

            TTransaction::close();

            TWindow::closeWindow();
            TToast::show('success', 'Confirmação cancelada: o agendamento voltou para Agendado.');

            TApplication::loadPage('AgendamentoFormView', 'onShow', ['key' => $agendamento->id]);

            TScript::create("if ($('#agendamentos').data('fullcalendar')) { $('#agendamentos').data('fullcalendar').refetchEvents(); }");
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

