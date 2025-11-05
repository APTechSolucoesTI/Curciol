<?php

class PublicacaoFormView extends TWindow
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Publicacao';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Publicacao';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        parent::setSize(0.8, null);
        parent::setTitle("Consulta de publicação");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $publicacao = new Publicacao($param['key']);
        // define the form title
        $this->form->setFormTitle("Consulta de publicação");

        $transformed_publicacao_data_tratamento = call_user_func(function($value, $object, $row)
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
        }, $publicacao->data_tratamento, $publicacao, null);    

        $transformed_publicacao_data_disponibilizacao = call_user_func(function($value, $object, $row) 
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        }, $publicacao->data_disponibilizacao, $publicacao, null);    

        $transformed_publicacao_data_entrega = call_user_func(function($value, $object, $row)
        {

            $label = new TElement('span');
            $label->{'class'} = 'label label-';

            if ($value!=null) {
                $label->{'class'} .= 'success';

                $timestamp = strtotime($value);
                $dataHoraBrasileira = date("d/m/Y H:i", $timestamp);

                $label->add('Entregue em '.$dataHoraBrasileira);   

            }else if(!$object->prazo){
                $label->{'class'} .= 'success';
                $label->add('Sem prazo');

            }else if($value == null && strtotime($object->prazo) > strtotime(date('Y-m-d'))) {
                $label->{'class'} .= 'info';
                $label->add('Em aberto');

            }else if($value == null && strtotime($object->prazo) == strtotime(date('Y-m-d'))) {
                $label->{'class'} .= 'warning';
                $label->add('Vence hoje');

            }else if($value == null && strtotime($object->prazo) < strtotime(date('Y-m-d'))) {
                $label->{'class'} .= 'danger';
                $label->add('Vencido');
            }

            return $label;

        }, $publicacao->data_entrega, $publicacao, null);    

        $transformed_publicacao_titulo = call_user_func(function($value, $object, $row)
        {

            return str_replace(";","<br/>",$value);

        }, $publicacao->titulo, $publicacao, null);    

        $transformed_publicacao_texto = call_user_func(function($value, $object, $row)
        {

            return str_replace(";","<br/>",$value);

        }, $publicacao->texto, $publicacao, null);    

        $transformed_publicacao_cabecalho = call_user_func(function($value, $object, $row)
        {

            return str_replace(";","<br/>",$value);

        }, $publicacao->cabecalho, $publicacao, null);    

        $transformed_publicacao_rodape = call_user_func(function($value, $object, $row)
        {

            return str_replace(";","<br/>",$value);

        }, $publicacao->rodape, $publicacao, null);

        $label14 = new TLabel("Número do processo:", '', '13px', 'B', '100%');
        $text14 = new TTextDisplay($publicacao->numero_unico_processo, '', '12px', '');
        $btnCriarProcesso = new TButton('btnCriarProcesso');
        $btnVincularProcesso = new TButton('btnVincularProcesso');
        $btnVerProcesso = new TButton('btnVerProcesso');
        $label2 = new TLabel("Jornal", '', '13px', 'B', '100%');
        $text11asdsadsadsa = new TTextDisplay($publicacao->jornal->nome, '', '12px', '');
        $label28 = new TLabel("Data de tratamento:", '', '13px', 'B', '100%');
        $text15asdasda = new TTextDisplay($transformed_publicacao_data_tratamento, '', '12px', '');
        $label4 = new TLabel("Data de disponibilização:", '', '13px', 'B', '100%');
        $text13adsdadas = new TTextDisplay($transformed_publicacao_data_disponibilizacao, '', '12px', '');
        $label223 = new TLabel("Processo principal:", '', '13px', 'B', '100%');
        $text101 = new TTextDisplay($publicacao->numero_processo_principal, '', '12px', '');
        $btnCriarPrincipal = new TButton('btnCriarPrincipal');
        $btnVincularPrincipal = new TButton('btnVincularPrincipal');
        $btnVerPrincipal = new TButton('btnVerPrincipal');
        $labelPrazo = new TLabel("Prazo:", '', '13px', 'B', '100%');
        $datetext2 = new TTextDisplay(TDate::convertToMask($publicacao->prazo, 'yyyy-mm-dd', 'dd/mm/yyyy'), '', '12px', '');
        $btnAddPrazo = new TButton('btnAddPrazo');
        $btnRemoverPrazo = new TButton('btnRemoverPrazo');
        $labelAtencao = new TLabel("<b>Atenção:</b> confirme o prazo e altere se necessário.", '#FF0000', '12px', '', '100%');
        $btnConfirmarPrazo = new TButton('btnConfirmarPrazo');
        $btnSugestaoPrazo = new TButton('btnSugestaoPrazo');
        $labeldtEntrega = new TLabel("Status:", '', '13px', 'B', '100%');
        $text13 = new TTextDisplay($transformed_publicacao_data_entrega, '', '12px', '');
        $label9 = new TLabel("Número da publicação:", '', '13px', 'B', '100%');
        $text9 = new TTextDisplay($publicacao->numero_publicacao, '', '12px', '');
        $label10 = new TLabel("Número do arquivo:", '', '13px', 'B', '100%');
        $text10 = new TTextDisplay($publicacao->numero_arquivo, '', '12px', '');
        $label8 = new TLabel("Título:", '', '13px', 'B', '100%');
        $text8 = new TTextDisplay($transformed_publicacao_titulo, '', '12px', '');
        $label3 = new TLabel(" ", '', '13px', 'B', '100%');
        $text3 = new TTextDisplay($transformed_publicacao_texto, '', '12px', '');
        $label48 = new TLabel(" ", '', '12px', '', '100%');
        $text11 = new TTextDisplay($transformed_publicacao_cabecalho, '', '12px', '');
        $label69 = new TLabel(" ", '', '12px', '', '100%');
        $text12 = new TTextDisplay($transformed_publicacao_rodape, '', '12px', '');
        $btnAddTarefa = new TButton('btnAddTarefa');

        $btnAddTarefa->setAction(new TAction([$this, 'onAddTarefa']), "Adicionar tarefa");
        $btnVincularProcesso->setAction(new TAction([$this, 'onVincularProcesso']), "Vincular processo");
        $btnVincularPrincipal->setAction(new TAction([$this, 'onVincularPrincipal']), "Vincular principal");
        $btnRemoverPrazo->setAction(new TAction([$this, 'onRemoverPrazo'],['key' => 'key']), "Remover prazo");
        $btnVerPrincipal->setAction(new TAction(['ProcessoFormView', 'onShow'],['key' => 'id']), "Ver principal");
        $btnAddPrazo->setAction(new TAction(['PublicacaoPrazoForm', 'onEdit'],['key' => 'id']), "Adicionar prazo");
        $btnSugestaoPrazo->setAction(new TAction([$this, 'onSugerirPrazo'],['key' => 'key']), "Sugestão de prazo");
        $btnVerProcesso->setAction(new TAction(['ProcessoFormView', 'onShow'],['key' => 'processo_id']), "Ver processo");
        $btnConfirmarPrazo->setAction(new TAction([$this, 'onConfirma'],['key' => 'id']), "Confirmar status - Sem prazo");
        $btnCriarProcesso->setAction(new TAction(['PublicacaoFormView', 'onCriarProcesso'],['numero_processo' => 'numero_processo']), "Criar processo");
        $btnCriarPrincipal->setAction(new TAction(['PublicacaoFormView', 'onCriarProcesso'],['numero_principal' => 'numeroprincipal']), "Criar principal");

        $btnAddPrazo->addStyleClass('btn-default');
        $btnAddTarefa->addStyleClass('btn-default');
        $btnVerProcesso->addStyleClass('btn-default');
        $btnVerPrincipal->addStyleClass('btn-default');
        $btnRemoverPrazo->addStyleClass('btn-default');
        $btnCriarProcesso->addStyleClass('btn-default');
        $btnSugestaoPrazo->addStyleClass('btn-default');
        $btnCriarPrincipal->addStyleClass('btn-default');
        $btnConfirmarPrazo->addStyleClass('btn-default');
        $btnVincularProcesso->addStyleClass('btn-default');
        $btnVincularPrincipal->addStyleClass('btn-default');

        $btnAddTarefa->setImage('fas:plus #4CAF50');
        $btnVerProcesso->setImage('fas:gavel #000000');
        $btnCriarProcesso->setImage('fas:plus #4CAF50');
        $btnVerPrincipal->setImage('fas:gavel #000000');
        $btnCriarPrincipal->setImage('fas:plus #4CAF50');
        $btnAddPrazo->setImage('fas:calendar-plus #000000');
        $btnSugestaoPrazo->setImage('fas:lightbulb #000000');
        $btnRemoverPrazo->setImage('fas:calendar-times #000000');
        $btnConfirmarPrazo->setImage('fas:check-circle #4CAF50');
        $btnVincularProcesso->setImage('fas:exchange-alt #03A9F4');
        $btnVincularPrincipal->setImage('fas:exchange-alt #03A9F4');

        $btnVerPrincipal->name = 'btnVerPrincipal';

        if($publicacao->processo_id){
            $vinculo = ProcessoVinculo::where('processo_incidente_id','=',$publicacao->processo_id)->first();
            if($vinculo){
                $principal = Processo::find($vinculo->processo_principal_id);
                $text101 = new TTextDisplay($principal->numero_cnj_numero, '', '12px', '');
            }
        }elseif($publicacao->numero_processo_principal){
            $principal = Processo::where('numero_cnj_numero','=',$publicacao->numero_processo_principal)->first();
        }

        $paramCriarProcesso = [
            'key' => $param['key'], 
            'publicacao' => $param['key'], 
            'numero_processo' => $publicacao->numero_unico_processo ?? null
        ];

        if(isset($principal) && $publicacao->processo_id == null){
            $paramCriarProcesso['principal'] = $principal->id;
            $paramCriarProcesso['vinculo'] = "INCIDENTE";
        }

        $btnCriarProcesso->setAction(new TAction(['PublicacaoFormView', 'onCriarProcesso'],$paramCriarProcesso), "Criar processo");

        $paramCriarPrincipal = [
            'key' => $param['key'], 
            'publicacao' => $param['key'],
            'numero_processo' => $publicacao->numero_processo_principal ?? null,
            'area_id' => $publicacao->processo->area_id ?? null,
            'assunto_id' => $publicacao->processo->assunto_id ?? null,
            'tribunal_id' => $publicacao->processo->tribunal_id ?? null,
            'foro_id' => $publicacao->processo->foro_id ?? null,
            'comarca_id' => $publicacao->processo->comarca_id ?? null,
            'vara_id' => $publicacao->processo->vara_id ?? null,
            'vinculo' => "PRINCIPAL"
        ];

        $btnCriarPrincipal->setAction(new TAction(['PublicacaoFormView', 'onCriarProcesso'],$paramCriarPrincipal), "Criar principal");

        $btnVincularProcesso->setAction(new TAction([$this, 'onVincularProcesso'],['key' => $param['key']]), "Vincular processo");

        $btnVincularPrincipal->setAction(new TAction([$this, 'onVincularPrincipal'],['key' => $param['key']]), "Vincular principal");

        $btnRemoverPrazo->setAction(new TAction([$this, 'onRemoverPrazo'],['key' => $publicacao->id]), "Remover prazo");

        $btnSugestaoPrazo->setAction(new TAction([$this, 'onSugerirPrazo'],['key' => $publicacao->id]), "Sugestão de prazo");

        $btnVerProcesso->setAction(new TAction(['PublicacaoFormView', 'onVerProcesso'],['key' => $publicacao->processo_id ?? null]), "Ver processo");

        if(isset($principal->id)){
            $btnVerPrincipal->setAction(new TAction(['PublicacaoFormView', 'onVerProcesso'],['key' => $principal->id ?? null]), "Ver principal");
        }else{
            TScript::create("$(\"[name='btnVerPrincipal']\").hide()");
        }

        $btnAddTarefa->setAction(new TAction([$this, 'onAddTarefa'],['publicacao_id' => $publicacao->id, 'key' => $publicacao->id, 'numero_unico_processo' => $publicacao->numero_unico_processo, 'prazo' => $publicacao->prazo ?? null]), "Adicionar tarefa");

        if($publicacao->prazo){

            TScript::create("$(\"[name='btnSugestaoPrazo']\").hide()");

            $btnAddPrazo->setAction(new TAction(['PublicacaoPrazoForm', 'onEdit'],['key' => $publicacao->id, 'tela' => 'Prazo', 'prazo' => $publicacao->prazo]), "Alterar prazo");

            //Adiciona tarefa pois já tem prazo
            $btnConfirmarPrazo->setAction(new TAction([$this, 'onAddTarefa'],['publicacao_id' => $publicacao->id, 'key' => $publicacao->id, 'numero_unico_processo' => $publicacao->numero_unico_processo, 'prazo' => $publicacao->prazo ?? null]), "Adicionar tarefa");
            $btnConfirmarPrazo->setImage('fas:plus #4CAF50');
        }else{
            $btnAddPrazo->setAction(new TAction(['PublicacaoPrazoForm', 'onEdit'],['key' => $publicacao->id, 'tela' => 'Prazo', 'prazo' => null]), "Adicionar prazo");
            //Confirma que não existe prazo
            $btnConfirmarPrazo->setAction(new TAction([$this, 'onConfirma'],['publicacao_id' => $publicacao->id, 'key' => $publicacao->id]), "Confirmar status - Sem prazo");

            TScript::create("$(\"[name='btnAddDtEntrega']\").hide()");

            if($publicacao->confirma_prazo == "N"){
                TScript::create("$(\"[name='btnRemoverPrazo']\").hide()");
            }
        }

        if($publicacao->data_entrega){
            TScript::create("$(\"[name='btnAddDtEntrega']\").hide()");
            TScript::create("$(\"[name='btnAddTarefa']\").hide()");
            TScript::create("$(\"[name='btnAddPrazo']\").hide()");
            TScript::create("$(\"[name='btnSugestaoPrazo']\").hide()");
            TScript::create("$(\"[name='btnConfirmarPrazo']\").hide()");
            TScript::create("$(\"[name='btnRemoverPrazo']\").hide()");
        }else{
            TScript::create("$(\"[name='btnRemoverDtEntrega']\").hide()");
        }

        if($publicacao->confirma_prazo == "S"){
            TScript::create("$(\"[name='btnConfirmarPrazo']\").hide()");
            TScript::create("$(\"[name='btnAddPrazo']\").hide()");
            TScript::create("$('label:contains(\"Atenção: confirme o prazo e altere se necessário.\")').html('Sem prazo.')");
            TScript::create("$(\"[name='btnSugestaoPrazo']\").hide()");
        }

        $tarefaDePublicacao = Tarefa::where('publicacao_id','=',$publicacao->id)->first();
        if($tarefaDePublicacao){
            TScript::create("$(\"[name='btnRemoverPrazo']\").hide()");
            if($tarefaDePublicacao->tarefa_status_id == (TarefaConfiguracao::find(1))->status_final_id){
                TScript::create("$(\"[name='btnRemoverDtEntrega']\").hide()");
            }
        }

        $row1 = $this->form->addFields([$label14,$text14,$btnCriarProcesso,$btnVincularProcesso,$btnVerProcesso],[$label2,$text11asdsadsadsa],[$label28,$text15asdasda],[$label4,$text13adsdadas]);
        $row1->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row2 = $this->form->addFields([$label223,$text101,$btnCriarPrincipal,$btnVincularPrincipal,$btnVerPrincipal],[],[$labelPrazo,$datetext2,$btnAddPrazo,$btnRemoverPrazo,$labelAtencao,$btnConfirmarPrazo,$btnSugestaoPrazo],[$labeldtEntrega,$text13]);
        $row2->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row4 = $this->form->addFields([$label9,$text9],[$label10,$text10]);
        $row4->layout = [' col-sm-6',' col-sm-6'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row6 = $this->form->addFields([$label8,$text8]);
        $row6->layout = [' col-sm-12'];

        $row7 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);

        $tab_65afd56db7789 = new BootstrapFormBuilder('tab_65afd56db7789');
        $this->tab_65afd56db7789 = $tab_65afd56db7789;
        $tab_65afd56db7789->setProperty('style', 'border:none; box-shadow:none;');

        $tab_65afd56db7789->appendPage("Descrição");

        $tab_65afd56db7789->addFields([new THidden('current_tab_tab_65afd56db7789')]);
        $tab_65afd56db7789->setTabFunction("$('[name=current_tab_tab_65afd56db7789]').val($(this).attr('data-current_page'));");

        $row8 = $tab_65afd56db7789->addFields([$label3,$text3,$label48,$text11,$label69,$text12]);
        $row8->layout = [' col-sm-12'];

        $tab_65afd56db7789->appendPage("Profissionais");

        $this->publicacao_profissional_publicacao_id_list = new TQuickGrid;
        $this->publicacao_profissional_publicacao_id_list->style = 'width:100%';
        $this->publicacao_profissional_publicacao_id_list->disableDefaultClick();

        $column_profissional_nome = $this->publicacao_profissional_publicacao_id_list->addQuickColumn("Profissional", 'profissional->nome', 'left');
        $column_codigo_relacionamento = $this->publicacao_profissional_publicacao_id_list->addQuickColumn("Código de relacionamento", 'codigo_relacionamento', 'left');

        $this->publicacao_profissional_publicacao_id_list->createModel();

        $criteria_publicacao_profissional_publicacao_id = new TCriteria();
        $criteria_publicacao_profissional_publicacao_id->add(new TFilter('publicacao_id', '=', $publicacao->id));

        $criteria_publicacao_profissional_publicacao_id->setProperty('order', 'id desc');

        $publicacao_profissional_publicacao_id_items = PublicacaoProfissional::getObjects($criteria_publicacao_profissional_publicacao_id);

        $this->publicacao_profissional_publicacao_id_list->addItems($publicacao_profissional_publicacao_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->publicacao_profissional_publicacao_id_list));

        $tab_65afd56db7789->addContent([$panel]);

        $tab_65afd56db7789->appendPage("Tarefas");
        $row9 = $tab_65afd56db7789->addFields([$btnAddTarefa]);
        $row9->layout = [' col-sm-3'];

        $this->tarefa_publicacao_id_list = new TQuickGrid;
        $this->tarefa_publicacao_id_list->style = 'width:100%';
        $this->tarefa_publicacao_id_list->disableDefaultClick();

        $action_onShow = new TDataGridAction(array('TarefaFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField('id');

        $action_onShow->setParameter('key', '{id}');
        $this->tarefa_publicacao_id_list->addAction($action_onShow);

        $column_titulo = $this->tarefa_publicacao_id_list->addQuickColumn("Titulo", 'titulo', 'left');
        $column_data_disponibilizacao_transformed = $this->tarefa_publicacao_id_list->addQuickColumn("Data da disponibilização", 'data_disponibilizacao', 'left');
        $column_prazo_validacao_transformed = $this->tarefa_publicacao_id_list->addQuickColumn("Prazo de validação", 'prazo_validacao', 'left');
        $column_prazo_entrega_transformed = $this->tarefa_publicacao_id_list->addQuickColumn("Prazo de entrega", 'prazo_entrega', 'left');
        $column_tarefa_status_nome_transformed = $this->tarefa_publicacao_id_list->addQuickColumn("Status", 'tarefa_status->nome', 'left');

        $column_data_disponibilizacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_prazo_validacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_prazo_entrega_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $column_tarefa_status_nome_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $retorno = "<span class='label' style='width:100%;max-width:200px;background-color:{$object->tarefa_status->cor}'> {$value} </span>"; 

            if($object->tarefa_status->fim == 'N'){
                if($object->prazo_entrega >= date('Y-m-d') && $object->prazo_entrega <= date('Y-m-d', strtotime("+5 days",strtotime(date('Y-m-d'))))){
                    $retorno .= "<br/><span class='label' style='width:100%;max-width:200px;background-color:orange'> Prazo a expirar </span>";
                }elseif ($object->prazo_entrega < date('Y-m-d')) {
                    $retorno .= "<br/><span class='label' style='width:100%;max-width:200px;background-color:red'> Prazo expirado </span>";
                }
            }

            return $retorno;
        });

        $this->tarefa_publicacao_id_list->enablePopover("", "{usuario_destinatario->name} <br/> 
{observacao}  ");

        $this->tarefa_publicacao_id_list->createModel();

        $criteria_tarefa_publicacao_id = new TCriteria();
        $criteria_tarefa_publicacao_id->add(new TFilter('publicacao_id', '=', $publicacao->id));

        $criteria_tarefa_publicacao_id->setProperty('order', 'prazo_entrega desc');

        $tarefa_publicacao_id_items = Tarefa::getObjects($criteria_tarefa_publicacao_id);

        $this->tarefa_publicacao_id_list->addItems($tarefa_publicacao_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->tarefa_publicacao_id_list));

        $tab_65afd56db7789->addContent([$panel]);

        $tab_65afd56db7789->appendPage("Movimentações");

        $this->publicacao_movimentacao_publicacao_id_list = new TQuickGrid;
        $this->publicacao_movimentacao_publicacao_id_list->style = 'width:100%';
        $this->publicacao_movimentacao_publicacao_id_list->disableDefaultClick();

        $action_onVisualizarComplementoMovimentacao = new TDataGridAction(array('PublicacaoFormView', 'onVisualizarComplementoMovimentacao'));
        $action_onVisualizarComplementoMovimentacao->setUseButton(false);
        $action_onVisualizarComplementoMovimentacao->setButtonClass('btn btn-default btn-sm');
        $action_onVisualizarComplementoMovimentacao->setLabel("Visualizar");
        $action_onVisualizarComplementoMovimentacao->setImage('fas:search-plus #000000');
        $action_onVisualizarComplementoMovimentacao->setField('id');
        $action_onVisualizarComplementoMovimentacao->setDisplayCondition('PublicacaoFormView::canVisCompMovimentacao');
        $action_onVisualizarComplementoMovimentacao->setParameter('movimentacao_id', '{id}');
        $this->publicacao_movimentacao_publicacao_id_list->addAction($action_onVisualizarComplementoMovimentacao);

        $column_descricao = $this->publicacao_movimentacao_publicacao_id_list->addQuickColumn("Descrição", 'descricao', 'left');
        $column_data_criacao_transformed = $this->publicacao_movimentacao_publicacao_id_list->addQuickColumn("Criado em", 'data_criacao', 'left');
        $column_criacao_user_name = $this->publicacao_movimentacao_publicacao_id_list->addQuickColumn("Criado por", 'criacao_user->name', 'left');

        $column_data_criacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $this->publicacao_movimentacao_publicacao_id_list->createModel();

        $criteria_publicacao_movimentacao_publicacao_id = new TCriteria();
        $criteria_publicacao_movimentacao_publicacao_id->add(new TFilter('publicacao_id', '=', $publicacao->id));

        $criteria_publicacao_movimentacao_publicacao_id->setProperty('order', 'data_criacao desc');

        $publicacao_movimentacao_publicacao_id_items = PublicacaoMovimentacao::getObjects($criteria_publicacao_movimentacao_publicacao_id);

        $this->publicacao_movimentacao_publicacao_id_list->addItems($publicacao_movimentacao_publicacao_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->publicacao_movimentacao_publicacao_id_list));

        $tab_65afd56db7789->addContent([$panel]);
        $row10 = $this->form->addFields([$tab_65afd56db7789]);
        $row10->layout = [' col-sm-12'];

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        if(!empty($param['current_tab_tab_65afd56db7789']))
        {
            $this->tab_65afd56db7789->setCurrentPage($param['current_tab_tab_65afd56db7789']);
        }

        $this->tab_65afd56db7789->scrollable = TRUE;

        if (isset($this->tab_65afd56db7789->thead))
        {
            $this->tab_65afd56db7789->thead->{'style'} = 'display: block';
        }

        TTransaction::close();
        parent::add($this->form);

    }

    public  function onVincularProcesso($param = null) 
    {
        try 
        {
            TSession::setValue('nivel_processo',"PROCESSO");
            TWindow::closeWindow();
            TApplication::loadPage('ProcessoSeekWindow', 'onShow');

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onVincularPrincipal($param = null) 
    {
        try 
        {
            TSession::setValue('nivel_processo',"PRINCIPAL");
            TWindow::closeWindow();
            TApplication::loadPage('ProcessoSeekWindow', 'onShow');

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onRemoverPrazo($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $publicacao = Publicacao::find((int)$param['key']);
            $publicacao->prazo = null;
            $publicacao->confirma_prazo = "N";
            $publicacao->store();

            APIPublicacaoController::adicionarMovimentacao($publicacao->id, "Prazo removido.", null, null);

            TTransaction::close();
            TApplication::loadPage('PublicacaoHeaderList', 'onShow');
            TApplication::loadPage('PublicacaoFormView', 'onShow', ['key' => $param['key'], 'id' => $param['key']]);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onConfirma($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $publicacao = Publicacao::find((int)$param['key']);
            $publicacao->confirma_prazo = "S";
            $publicacao->store();

            APIPublicacaoController::adicionarMovimentacao($publicacao->id, "Prazo confirmado.", null, null);

            TTransaction::close();
            TApplication::loadPage('PublicacaoHeaderList', 'onShow');
            TApplication::loadPage('PublicacaoFormView', 'onShow', ['key' => $param['key']]);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onSugerirPrazo($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $sugestaoPrazoPublicacao = PublicacaoSugestaoPrazo::where('publicacao_id','=',(int)$param['key'])->first();
            TTransaction::close();

            if($sugestaoPrazoPublicacao){
                TWindow::closeWindow();
                TApplication::loadPage('PublicacaoSugestaoPrazoFormView', 'onShow', ['key' => $sugestaoPrazoPublicacao->id]);
            }else{
                TWindow::closeWindow();
                new TMessage('info', "Não há prazos sugeridos nessa publicação.");
                TApplication::loadPage('PublicacaoFormView', 'onShow', ['key' => $param['key'], 'id' => $param['key']]);
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onAddTarefa($param = null) 
    {
        try 
        {

        $pageParam['publicacao_id'] = $param['publicacao_id'];
        $pageParam['titulo_pub'] = $param['numero_unico_processo'];
        $pageParam['prazo'] = implode('/', array_reverse(explode('-', $param['prazo'])));
        $pageParam['retorno'] = self::class.','.$param['publicacao_id'];

        TWindow::closeWindow();
        TApplication::loadPage('TarefaForm', 'onShow', $pageParam);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onVisualizarComplementoMovimentacao($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $movimentacao = PublicacaoMovimentacao::find((int) $param['movimentacao_id']);
            if($movimentacao->tarefa_id!=null){
                TApplication::loadPage('TarefaFormView', 'onShow', ['key' => $movimentacao->tarefa_id]);
                TWindow::closeWindow();
            }else if($movimentacao->processo_id!=null){
                TApplication::loadPage('ProcessoFormView', 'onShow', ['key' => $movimentacao->processo_id]);
                TWindow::closeWindow();
            }
            TTransaction::close();

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canVisCompMovimentacao($object)
    {
        try 
        {
            if($object->tarefa_id!=null || $object->processo_id!=null)
            {
                return true;
            }

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {     

        TScript::create("$(\"[name='btnAddTarefa']\").closest('.fb-inline-field-container').show()");

        TTransaction::open(self::$database);
        $publicacao = Publicacao::find($param['key']);
        TSession::setValue('publicacao_id',$publicacao->id);

        if($publicacao->processo_id){
            TSession::setValue('processo_id',$publicacao->processo_id);

            TScript::create("$('label:contains(\"Processo principal:\")').show();");
            TScript::create("$(\"[name='btnVerProcesso']\").closest('.fb-inline-field-container').show()");
            TScript::create("$(\"[name='btnCriarProcesso']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='btnVincularProcesso']\").closest('.fb-inline-field-container').hide()");

            $vinculo = ProcessoVinculo::where('processo_incidente_id','=',$publicacao->processo_id)->first();

            if($vinculo){
                TScript::create("$(\"[name='btnVerPrincipal']\").closest('.fb-inline-field-container').show()");
                TScript::create("$(\"[name='btnCriarPrincipal']\").closest('.fb-inline-field-container').hide()");
                TScript::create("$(\"[name='btnVincularPrincipal']\").closest('.fb-inline-field-container').hide()");
            }else{
                TScript::create("$(\"[name='btnVerPrincipal']\").closest('.fb-inline-field-container').hide()");
                TScript::create("$(\"[name='btnCriarPrincipal']\").closest('.fb-inline-field-container').show()");
                TScript::create("$(\"[name='btnVincularPrincipal']\").closest('.fb-inline-field-container').show()");

                if($publicacao->numero_processo_principal){
                    TScript::create("$(\"[name='btnCriarPrincipal']\").closest('.fb-inline-field-container').show()");
                }
            }

        }else{

            if($publicacao->numero_processo_principal){
                TScript::create("$('label:contains(\"Processo principal:\")').show();");
            }else{
                TScript::create("$('label:contains(\"Processo principal:\")').hide();");
            }

            TScript::create("$(\"[name='btnVerProcesso']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='btnCriarProcesso']\").closest('.fb-inline-field-container').show()");
            TScript::create("$(\"[name='btnVincularProcesso']\").closest('.fb-inline-field-container').show()");

            TScript::create("$(\"[name='btnVerPrincipal']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='btnCriarPrincipal']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='btnVincularPrincipal']\").closest('.fb-inline-field-container').hide()");
        }

        TTransaction::close();
    }

    public  function onVerProcesso($param = null) 
    {
        try 
        {
            if($param['key']){
                TApplication::loadPage('ProcessoFormView', 'onShow', $param);
                TWindow::closeWindow();
            }
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onCriarProcesso($param = null) 
    {
        try 
        {
            if(isset($param['principal'])){
                TTransaction::open(self::$database);

                $publicacao = Publicacao::find($param['publicacao']);
                $principal = Processo::find($param['principal']);
                $principalContrato = ContratoProcesso::where('processo_id', '=', $principal->id)->getIndexedArray('contrato_id', 'contrato_id');
                $principalContraparte = Contraparte::where('processo_id', '=', $principal->id)->getIndexedArray('pessoa_id', 'pessoa_id');

                unset($principal->id);
                unset($principal->data_criacao);
                unset($principal->criacao_user_id);
                unset($principal->data_modificacao);
                unset($principal->modificacao_user_id);

                $processo = new Processo();
                $processo = clone $principal;
                $processo->numero_cnj_numero = $param['numero_processo'];
                $processo->criacao_user_id = TSession::getValue('userid');

                $processo_numero = Processo::where('numero_cnj_numero','=',$processo->numero_cnj_numero)->first();
                if($processo_numero){
                    throw new Exception("Número já cadastrado em outro processo. Não é possível adicionar.");
                }

                $processo->store();

                $vinculo = new ProcessoVinculo();
                $vinculo->processo_principal_id = $param['principal'];
                $vinculo->processo_incidente_id = $processo->id;
                $vinculo->store();

                $publicacoes = Publicacao::where('numero_unico_processo','=',$processo->numero_cnj_numero)->load();

                foreach ($publicacoes as $publicacao) {
                    $publicacao->processo_id = $processo->id;
                    $publicacao->store();

                    APIPublicacaoController::adicionarMovimentacao($publicacao->id, "Processo adicionado.", null, $processo->id);
                }

                $publicacoes = Publicacao::where('numero_processo_principal','=',$processo->numero_cnj_numero)->load();
                foreach ($publicacoes as $publicacao) {
                    if($publicacao->processo_id){
                        $vinculo = ProcessoVinculo::where('processo_principal_id','=',$processo->id)
                                                  ->where('processo_incidente_id','=',$publicacao->processo_id)
                                                  ->count();
                        if($vinculo<1){                      
                            $vinculo = new ProcessoVinculo();
                            $vinculo->processo_principal_id = $processo->id;
                            $vinculo->processo_incidente_id = $publicacao->processo_id;
                            $vinculo->store();
                        }
                    }
                }

                foreach($principalContrato as $key=>$value){
                    $processoContrato = new ContratoProcesso();
                    $processoContrato->processo_id = $processo->id;
                    $processoContrato->contrato_id = $key;
                    $processoContrato->store();
                }

                foreach($principalContraparte as $key=>$value){
                    $processoContraparte = new Contraparte();
                    $processoContraparte->processo_id = $processo->id;
                    $processoContraparte->pessoa_id = $key;
                    $processoContraparte->store();
                }
                unset($param);
                $param['key'] = $processo->id;

                TTransaction::close();
                if(!empty($principalContrato)){
                    TApplication::loadPage('ContratoProcessoSimpleList', 'onShow', ['processo_id'=>$processo->id]);
                }else{
                    TApplication::loadPage('ProcessoForm', 'onEdit', $param);
                }
            }else{
                TApplication::loadPage('ProcessoForm', 'onShow', $param);
            }

            TWindow::closeWindow();

        }
        catch (Exception $e) 
        {
            $this->onShow();
            new TMessage('error', $e->getMessage());    
        }
    }

}

