<?php

class ExtratoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Extrato';
    private static $primaryKey = 'id';
    private static $formName = 'form_ExtratoList';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);

        // define the form title
        $this->form->setFormTitle("Listagem de extratos");
        $this->limit = 20;

        $criteria_conta_caixa_id = new TCriteria();
        $criteria_tipo_extrato_id = new TCriteria();
        $criteria_conta_caixa_id_check = new TCriteria();
        $criteria_lancamento_tipo_pagamento_nome = new TCriteria();
        $criteria_tipo_extrato_nome = new TCriteria();

        $conta_caixa_id = new TDBMultiSearch('conta_caixa_id', 'escritorio', 'ContaCaixa', 'id', 'codigo_agencia','nome asc' , $criteria_conta_caixa_id );
        $tipo_extrato_id = new TDBMultiSearch('tipo_extrato_id', 'escritorio', 'TipoExtrato', 'id', 'nome','id asc' , $criteria_tipo_extrato_id );
        $compensado = new TCombo('compensado');
        $data_compensacao_inicio = new TDate('data_compensacao_inicio');
        $ate_compensacao = new TLabel("até", null, '12px', null);
        $data_compensacao_fim = new TDate('data_compensacao_fim');
        $prev_compensacao_inicio = new TDate('prev_compensacao_inicio');
        $ate_previsao = new TLabel("até", null, '12px', null);
        $prev_compensacao_fim = new TDate('prev_compensacao_fim');
        $historico = new TEntry('historico');
        $entrada_valor = new TNumeric('entrada_valor', '2', ',', '.' );
        $saida_valor = new TNumeric('saida_valor', '2', ',', '.' );
        $compensado_col = new TCombo('compensado_col');
        $data_compensacao = new TDate('data_compensacao');
        $conta_caixa_id_check = new BDBSelectCheck('conta_caixa_id_check', 'escritorio', 'ContaCaixa', 'id', '{nome}','nome asc' , $criteria_conta_caixa_id_check );
        $lancamento_tipo_pagamento_nome = new BDBSelectCheck('lancamento_tipo_pagamento_nome', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_lancamento_tipo_pagamento_nome );
        $tipo_extrato_nome = new BDBSelectCheck('tipo_extrato_nome', 'escritorio', 'TipoExtrato', 'id', '{nome}','nome asc' , $criteria_tipo_extrato_nome );
        $historico_col = new TEntry('historico_col');

        $compensado->setChangeAction(new TAction([$this,'onTrocaCompensado']));

        $historico_col->exitOnEnter();

        $data_compensacao->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $historico_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $compensado_col->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $conta_caixa_id_check->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $lancamento_tipo_pagamento_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $tipo_extrato_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $tipo_extrato_id->setFilterColumns(["nome"]);
        $compensado->setValue('N');
        $compensado->setDefaultOption(false);
        $historico->setMaxLength(3000);
        $entrada_valor->setAllowNegative(false);
        $conta_caixa_id->setMinLength(0);
        $tipo_extrato_id->setMinLength(0);

        $compensado->addItems(["S"=>"Sim","N"=>"Não"]);
        $compensado_col->addItems(["S"=>"Sim","N"=>"Não"]);

        $compensado->enableSearch();
        $compensado_col->enableSearch();

        $data_compensacao->setDatabaseMask('yyyy-mm-dd');
        $data_compensacao_fim->setDatabaseMask('yyyy-mm-dd');
        $prev_compensacao_fim->setDatabaseMask('yyyy-mm-dd');
        $data_compensacao_inicio->setDatabaseMask('yyyy-mm-dd');
        $prev_compensacao_inicio->setDatabaseMask('yyyy-mm-dd');

        $conta_caixa_id->setMask('{nome}');
        $tipo_extrato_id->setMask('{nome}');
        $data_compensacao->setMask('dd/mm/yyyy');
        $data_compensacao_fim->setMask('dd/mm/yyyy');
        $prev_compensacao_fim->setMask('dd/mm/yyyy');
        $data_compensacao_inicio->setMask('dd/mm/yyyy');
        $prev_compensacao_inicio->setMask('dd/mm/yyyy');

        $historico->setSize('100%');
        $compensado->setSize('100%');
        $saida_valor->setSize('100%');
        $entrada_valor->setSize('100%');
        $data_compensacao->setSize(110);
        $historico_col->setSize('100%');
        $compensado_col->setSize('100%');
        $conta_caixa_id->setSize('97%', 30);
        $tipo_extrato_nome->setSize('100%');
        $tipo_extrato_id->setSize('100%', 70);
        $data_compensacao_fim->setSize('20%');
        $prev_compensacao_fim->setSize('20%');
        $conta_caixa_id_check->setSize('100%');
        $data_compensacao_inicio->setSize('20%');
        $prev_compensacao_inicio->setSize('20%');
        $lancamento_tipo_pagamento_nome->setSize('100%');

        $ate_compensacao->name = "ate_compensacao";
        $ate_previsao->name = "ate_previsao";

        $row1 = $this->form->addFields([new TLabel("Conta caixa:", null, '12px', null, '100%'),$conta_caixa_id],[new TLabel("Tipo de extrato:", null, '12px', null, '100%'),$tipo_extrato_id]);
        $row1->layout = [' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Compensado:", null, '12px', null, '100%'),$compensado],[new TLabel("Data da compensação:", null, '12px', null, '100%'),$data_compensacao_inicio,$ate_compensacao,$data_compensacao_fim,$prev_compensacao_inicio,$ate_previsao,$prev_compensacao_fim]);
        $row2->layout = ['col-sm-4',' col-sm-8'];

        $row3 = $this->form->addFields([new TLabel("Histórico:", null, '12px', null, '100%'),$historico],[new TLabel("Valor da entrada (R$):", null, '12px', null, '100%'),$entrada_valor],[new TLabel("Valor da saída (R$):", null, '12px', null, '100%'),$saida_valor]);
        $row3->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        // TTransaction::open('escritorio');
        // $conta_caixa_id = $param['conta_caixa_id'] ?? TSession::getValue('conta_caixa_id');
        // $param['conta_caixa'] = (ContaCaixa::find($conta_caixa_id))->nome;
        // TTransaction::close();

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary'); 

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(250);

        $column_compensado_transformed = new TDataGridColumn('compensado', "Compensado", 'center' , '10%');
        $column_data_compensacao_transformed = new TDataGridColumn('data_compensacao', "Data da compensação", 'center' , '10%');
        $column_conta_caixa_nome = new TDataGridColumn('conta_caixa->nome', "Conta Caixa", 'left');
        $column_lancamento_tipo_pagamento_nome = new TDataGridColumn('lancamento->tipo_pagamento->nome', "Tipo de pagamento", 'center' , '10%');
        $column_tipo_extrato_nome = new TDataGridColumn('tipo_extrato->nome', "Tipo de extrato", 'center' , '15%');
        $column_historico = new TDataGridColumn('historico', "Historico", 'center' , '20%');
        $column_entrada_valor_transformed = new TDataGridColumn('entrada_valor', "Entrada", 'center');
        $column_saida_valor_transformed = new TDataGridColumn('saida_valor', "Saída", 'center');

        $column_entrada_valor_transformed->setTotalFunction( function($values) { 
            return array_sum((array) $values); 
        }); 

        $column_saida_valor_transformed->setTotalFunction( function($values) { 
            return array_sum((array) $values); 
        }); 

        $column_compensado_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
        });

        $column_data_compensacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_entrada_valor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_saida_valor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $order_compensado_transformed = new TAction(array($this, 'onReload'));
        $order_compensado_transformed->setParameter('order', 'compensado');
        $column_compensado_transformed->setAction($order_compensado_transformed);
        $order_data_compensacao_transformed = new TAction(array($this, 'onReload'));
        $order_data_compensacao_transformed->setParameter('order', 'data_compensacao');
        $column_data_compensacao_transformed->setAction($order_data_compensacao_transformed);

        $this->builder_datagrid_check_all = new TCheckButton('builder_datagrid_check_all');
        $this->builder_datagrid_check_all->setIndexValue('on');
        $this->builder_datagrid_check_all->onclick = "Builder.checkAll(this)";
        $this->builder_datagrid_check_all->style = 'cursor:pointer';
        $this->builder_datagrid_check_all->setProperty('class', 'filled-in');
        $this->builder_datagrid_check_all->id = 'builder_datagrid_check_all';

        $label = new TLabel('');
        $label->style = 'margin:0';
        $label->class = 'checklist-label';
        $this->builder_datagrid_check_all->after($label);
        $label->for = 'builder_datagrid_check_all';

        $this->builder_datagrid_check = $this->datagrid->addColumn( new TDataGridColumn('builder_datagrid_check', $this->builder_datagrid_check_all, 'center',  '1%') );

        $this->datagrid->addColumn($column_compensado_transformed);
        $this->datagrid->addColumn($column_data_compensacao_transformed);
        $this->datagrid->addColumn($column_conta_caixa_nome);
        $this->datagrid->addColumn($column_lancamento_tipo_pagamento_nome);
        $this->datagrid->addColumn($column_tipo_extrato_nome);
        $this->datagrid->addColumn($column_historico);
        $this->datagrid->addColumn($column_entrada_valor_transformed);
        $this->datagrid->addColumn($column_saida_valor_transformed);

        $action_onClickEdit = new TDataGridAction(array('ExtratoList', 'onClickEdit'));
        $action_onClickEdit->setUseButton(false);
        $action_onClickEdit->setButtonClass('btn btn-default btn-sm');
        $action_onClickEdit->setLabel("Editar");
        $action_onClickEdit->setImage('far:edit #478fca');
        $action_onClickEdit->setField(self::$primaryKey);
        $action_onClickEdit->setDisplayCondition('ExtratoList::canEdit');

        $this->datagrid->addAction($action_onClickEdit);

        $action_onDelete = new TDataGridAction(array('ExtratoList', 'onDelete'));
        $action_onDelete->setUseButton(false);
        $action_onDelete->setButtonClass('btn btn-default btn-sm');
        $action_onDelete->setLabel("Excluir");
        $action_onDelete->setImage('fas:trash-alt #dd5a43');
        $action_onDelete->setField(self::$primaryKey);
        $action_onDelete->setDisplayCondition('ExtratoList::canDelete');

        $this->datagrid->addAction($action_onDelete);

        $action_onShow = new TDataGridAction(array('ExtratoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField(self::$primaryKey);

        $action_onShow->setParameter('key', '{id}');

        $this->datagrid->addAction($action_onShow);

        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);

        if(!$action_onClickEdit->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        if(!$action_onDelete->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        if(!$action_onShow->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        $tr->add(TElement::tag('td', ''));
        $td_compensado_col = TElement::tag('td', $compensado_col);
        $tr->add($td_compensado_col);
        $td_data_compensacao = TElement::tag('td', $data_compensacao);
        $tr->add($td_data_compensacao);
        $td_conta_caixa_id_check = TElement::tag('td', $conta_caixa_id_check);
        $tr->add($td_conta_caixa_id_check);
        $td_lancamento_tipo_pagamento_nome = TElement::tag('td', $lancamento_tipo_pagamento_nome);
        $tr->add($td_lancamento_tipo_pagamento_nome);
        $td_tipo_extrato_nome = TElement::tag('td', $tipo_extrato_nome);
        $tr->add($td_tipo_extrato_nome);
        $td_historico_col = TElement::tag('td', $historico_col);
        $tr->add($td_historico_col);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);

        $this->datagrid_form->addField($compensado_col);
        $this->datagrid_form->addField($data_compensacao);
        $this->datagrid_form->addField($conta_caixa_id_check);
        $this->datagrid_form->addField($lancamento_tipo_pagamento_nome);
        $this->datagrid_form->addField($tipo_extrato_nome);
        $this->datagrid_form->addField($historico_col);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Listagem de extratos");
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;

        $panel->add($this->datagrid_form);

        $panel->getBody()->class .= ' table-responsive';

        $panel->addFooter($this->pageNavigation);

        $headerActions = new TElement('div');
        $headerActions->class = ' datagrid-header-actions ';
        $headerActions->style = 'justify-content: space-between;';

        $head_left_actions = new TElement('div');
        $head_left_actions->class = ' datagrid-header-actions-left-actions ';

        $head_right_actions = new TElement('div');
        $head_right_actions->class = ' datagrid-header-actions-left-actions ';

        $headerActions->add($head_left_actions);
        $headerActions->add($head_right_actions);

        $this->datagrid_form->add($headerActions);

        $button_trocar_parametros = new TButton('button_button_trocar_parametros');
        $button_trocar_parametros->setAction(new TAction(['ModalFiltroExtrato', 'onShow']), "Trocar parâmetros");
        $button_trocar_parametros->addStyleClass('btn-default');
        $button_trocar_parametros->setImage('far:window-restore #000000');

        $this->datagrid_form->addField($button_trocar_parametros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['ExtratoList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $btnShowCurtainFilters = new TButton('button_btnShowCurtainFilters');
        $btnShowCurtainFilters->setAction(new TAction(['ExtratoList', 'onShowCurtainFilters']), "Filtros");
        $btnShowCurtainFilters->addStyleClass('btn-default');
        $btnShowCurtainFilters->setImage('fas:filter #000000');

        $this->datagrid_form->addField($btnShowCurtainFilters);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['ExtratoList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $button_gerar_relatorio_de_conta_caixa = new TButton('button_button_gerar_relatorio_de_conta_caixa');
        $button_gerar_relatorio_de_conta_caixa->setAction(new TAction(['ModalSelecionarContaCaixa', 'onShow']), "Gerar relatório de Conta Caixa");
        $button_gerar_relatorio_de_conta_caixa->addStyleClass('btn-default');
        $button_gerar_relatorio_de_conta_caixa->setImage('fas:file-export #000000');

        $this->datagrid_form->addField($button_gerar_relatorio_de_conta_caixa);

        $button_nova_entrada = new TButton('button_button_nova_entrada');
        $button_nova_entrada->setAction(new TAction(['ExtratoEntradaForm', 'onShow']), "Nova Entrada");
        $button_nova_entrada->addStyleClass('btn-success');
        $button_nova_entrada->setImage('fas:money-bill-wave #FFFFFF');

        $this->datagrid_form->addField($button_nova_entrada);

        $button_nova_saida = new TButton('button_button_nova_saida');
        $button_nova_saida->setAction(new TAction(['ExtratoSaidaForm', 'onShow']), "Nova Saída");
        $button_nova_saida->addStyleClass('btn-danger');
        $button_nova_saida->setImage('fas:money-bill-wave #FFFFFF');

        $this->datagrid_form->addField($button_nova_saida);

        $button_transferir = new TButton('button_button_transferir');
        $button_transferir->setAction(new TAction(['ExtratoTransferenciaForm', 'onShow']), "Transferir");
        $button_transferir->addStyleClass('btn-primary');
        $button_transferir->setImage('fas:exchange-alt #FFFFFF');

        $this->datagrid_form->addField($button_transferir);

        $button_compensar = new TButton('button_button_compensar');
        $button_compensar->setAction(new TAction(['ExtratoList', 'onCompensar']), "Compensar");
        $button_compensar->addStyleClass('btn-default');
        $button_compensar->setImage('fas:dollar-sign #4CAF50');

        $this->datagrid_form->addField($button_compensar);

        $button_descompensar = new TButton('button_button_descompensar');
        $button_descompensar->setAction(new TAction(['ExtratoList', 'onDescompensar']), "Descompensar");
        $button_descompensar->addStyleClass('btn-default');
        $button_descompensar->setImage('fas:ban #F44336');

        $this->datagrid_form->addField($button_descompensar);

        $head_left_actions->add($button_nova_entrada);
        $head_left_actions->add($button_nova_saida);
        $head_left_actions->add($button_transferir);
        $head_left_actions->add($button_compensar);
        $head_left_actions->add($button_descompensar);

        $head_right_actions->add($button_trocar_parametros);
        $head_right_actions->add($button_atualizar);
        $head_right_actions->add($btnShowCurtainFilters);
        $head_right_actions->add($button_limpar_filtros);
        $head_right_actions->add($button_gerar_relatorio_de_conta_caixa);

        $this->datagrid_form->add($this->datagrid);

        $this->btnShowCurtainFilters = $btnShowCurtainFilters;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Financeiro","Extratos"]));
        }

        $container->add($panel);

        TScript::create("$('label:contains(\"Data da compensação:\")').show();");
        TScript::create("$(\"[name='ate_compensacao']\").closest('.fb-inline-field-container').show()");
        TScript::create("$(\"[name='data_compensacao_inicio']\").closest('.fb-inline-field-container').show()");
        TScript::create("$(\"[name='data_compensacao_fim']\").closest('.fb-inline-field-container').show()");

        TScript::create("$(\"[name='ate_previsao']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='prev_compensacao_inicio']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='prev_compensacao_fim']\").closest('.fb-inline-field-container').hide()");

        parent::add($container);

    }

    public static function onTrocaCompensado($param = null) 
    {
         try 
         {
            TScript::create("$('label:contains(\"Data da compensação:\")').show();");
            TScript::create("$(\"[name='ate_compensacao']\").closest('.fb-inline-field-container').show()");
            TScript::create("$(\"[name='data_compensacao_inicio']\").closest('.fb-inline-field-container').show()");
            TScript::create("$(\"[name='data_compensacao_fim']\").closest('.fb-inline-field-container').show()");

            TScript::create("$(\"[name='ate_previsao']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='prev_compensacao_inicio']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='prev_compensacao_fim']\").closest('.fb-inline-field-container').hide()");

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onClickEdit($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $extrato = Extrato::find($param['id']);

            $pageParam['key'] = $extrato->id;

            if($extrato->tipo_extrato_id == TipoExtrato::ENTRADA){
                TApplication::loadPage('ExtratoEntradaForm', 'onEdit', $pageParam);
            }else if($extrato->tipo_extrato_id == TipoExtrato::SAIDA){
                TApplication::loadPage('ExtratoSaidaForm', 'onEdit', $pageParam);
            }else if($extrato->tipo_extrato_id == TipoExtrato::TRANSFERENCIA){
                TApplication::loadPage('ExtratoTransferenciaForm', 'onEdit', $pageParam);
            }else if($extrato->tipo_extrato_id == TipoExtrato::PAGAR){

            }else if($extrato->tipo_extrato_id == TipoExtrato::RECEBER){

            }

            TTransaction::close();

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canEdit($object)
    {
        try 
        {
            if($object->tipo_extrato_id == TipoExtrato::ENTRADA || $object->tipo_extrato_id == TipoExtrato::SAIDA || $object->tipo_extrato_id == TipoExtrato::TRANSFERENCIA){
                if($object->compensado=='N')
                {
                    return true;
                }
            }

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onDelete($param = null) 
    { 
        if(isset($param['delete']) && $param['delete'] == 1)
        {
            try
            {
                // get the paramseter $key
                $key = $param['key'];
                // open a transaction with database
                TTransaction::open(self::$database);

                // instantiates object
                $object = new Extrato($key, FALSE); 

                if($object->extrato_vinculado){
                    $objectVinculado = new Extrato($object->extrato_vinculado, FALSE);
                    $objectVinculado->delete();
                }
                // deletes the object from the database
                $object->delete();

                // close the transaction
                TTransaction::close();

                // reload the listing
                $this->onReload( $param );
                // shows the success message
                TToast::show("success", AdiantiCoreTranslator::translate('Record deleted'), "topRight", "fas:check");
            }
            catch (Exception $e) // in case of exception
            {
                // shows the exception error message
                new TMessage('error', $e->getMessage());
                // undo all pending operations
                TTransaction::rollback();
            }
        }
        else
        {
            // define the delete action
            $action = new TAction(array($this, 'onDelete'));
            $action->setParameters($param); // pass the key paramseter ahead
            $action->setParameter('delete', 1);
            // shows a dialog to the user
            new TQuestion(AdiantiCoreTranslator::translate('Do you really want to delete ?'), $action);   
        }
    }
    public static function canDelete($object)
    {
        try 
        {
            if($object->tipo_extrato_id == TipoExtrato::ENTRADA || $object->tipo_extrato_id == TipoExtrato::SAIDA || $object->tipo_extrato_id == TipoExtrato::TRANSFERENCIA){
                if($object->compensado=='N')
                {
                    return true;
                }
            }

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onRefresh($param = null) 
    {
        $this->onReload([]);
    }
    public static function onShowCurtainFilters($param = null) 
    {
        try 
        {
            $object = new stdClass();
            $object->conta_caixa_id = null;
            $object->tipo_extrato_id = null;
            $object->compensado = null;
            $object->data_compensacao_inicio = null;
            $object->data_compensacao_fim = null;
            $object->prev_compensacao_inicio = null;
            $object->prev_compensacao_fim = null;
            $object->historico = null;
            $object->entrada_valor = null;
            $object->saida_valor = null;

            TForm::sendData(self::$formName, $object);

                        $filter = new self([]);

            $btnClose = new TButton('closeCurtain');
            $btnClose->class = 'btn btn-sm btn-default';
            $btnClose->style = 'margin-right:10px;';
            $btnClose->onClick = "Template.closeRightPanel();";
            $btnClose->setLabel("Fechar");
            $btnClose->setImage('fas:times');

            $filter->form->addHeaderWidget($btnClose);

            $page = new TPage();
            $page->setTargetContainer('adianti_right_panel');
            $page->setProperty('page-name', 'ExtratoListSearch');
            $page->setProperty('page_name', 'ExtratoListSearch');
            $page->adianti_target_container = 'adianti_right_panel';
            $page->target_container = 'adianti_right_panel';
            $page->add($filter->form);
            $page->setIsWrapped(true);
            $page->show();

            $style = new TStyle('right-panel > .container-part[page-name=ExtratoListSearch]');
            $style->width = '50% !important';
            $style->show(true);

            TScript::create("$('label:contains(\"Data da compensação:\")').show();");
            TScript::create("$(\"[name='ate_compensacao']\").closest('.fb-inline-field-container').show()");
            TScript::create("$(\"[name='data_compensacao_inicio']\").closest('.fb-inline-field-container').show()");
            TScript::create("$(\"[name='data_compensacao_fim']\").closest('.fb-inline-field-container').show()");

            TScript::create("$(\"[name='ate_previsao']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='prev_compensacao_inicio']\").closest('.fb-inline-field-container').hide()");
            TScript::create("$(\"[name='prev_compensacao_fim']\").closest('.fb-inline-field-container').hide()");
            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onClearFilters($param = null) 
    {
        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }
    public function onCompensar($param = null) 
    {
        try 
        {
            if(!isset($param['builder_datagrid_check'])){
                throw new Exception("Selecione uma transação para compensar.");
            }
            $ids = [];

            TSession::setValue('paramSelecionados',$param['builder_datagrid_check']);

            TApplication::loadPage('ModalCompensarExtrato', 'onShow');

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onDescompensar($param = null) 
    {
        try 
        {
            if(!isset($param['builder_datagrid_check'])){
                throw new Exception("Selecione uma transação para descompensar.");
            }
            $ids = [];
            foreach($param['builder_datagrid_check'] as $info){
                $ids[] = $info;
            }

            TTransaction::open(self::$database);
            foreach($ids as $id){
                $aux = Extrato::find($id);
                if($aux->compensado=='N'){
                    throw new Exception("Selecione uma transação que esteja compensada.");
                }
                $aux->modificacao_user_id = TSession::getValue('userid');
                $aux->compensado = 'N';
                $aux->data_compensacao = null;
                $aux->ano = null;
                $aux->mes = null;
                $aux->ano_mes = null;
                $aux->store();

                //saldo_instantaneo
                $contaCaixa = ContaCaixa::find($aux->conta_caixa_id);

                $contaCaixa->saldo_instantaneo = (float) $contaCaixa->saldo_instantaneo - (float) $aux->entrada_valor + (float) $aux->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();

                //saldo_nao_compensado
                $contaCaixa = ContaCaixa::find($aux->conta_caixa_id);

                $contaCaixa->saldo_nao_compensado = (float) $contaCaixa->saldo_nao_compensado + (float) $aux->entrada_valor - (float) $aux->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();
            }
            TTransaction::close();

            TApplication::loadPage('ExtratoList', 'onReload');

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    /**
     * Register the filter in the session
     */
    public function onSearch($param = null)
    {
        if ((isset($param['static']) && ($param['static'] == '1')) || !empty($param['globalSearch']))
        {
            $data = $this->datagrid_form->getData();
        }
        else
        {
            $data = $this->form->getData();
        }
        $filters = [];

            $data->prev_compensacao_inicio = null;
            $data->prev_compensacao_fim = null; 

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->conta_caixa_id) AND ( (is_scalar($data->conta_caixa_id) AND $data->conta_caixa_id !== '') OR (is_array($data->conta_caixa_id) AND (!empty($data->conta_caixa_id)) )) )
        {

            $filters[] = new TFilter('conta_caixa_id', 'in', $data->conta_caixa_id);// create the filter 
        }

        if (isset($data->tipo_extrato_id) AND ( (is_scalar($data->tipo_extrato_id) AND $data->tipo_extrato_id !== '') OR (is_array($data->tipo_extrato_id) AND (!empty($data->tipo_extrato_id)) )) )
        {

            $filters[] = new TFilter('tipo_extrato_id', 'in', $data->tipo_extrato_id);// create the filter 
        }

        if (isset($data->compensado) AND ( (is_scalar($data->compensado) AND $data->compensado !== '') OR (is_array($data->compensado) AND (!empty($data->compensado)) )) )
        {

            $filters[] = new TFilter('compensado', '=', $data->compensado);// create the filter 
        }

        if (isset($data->data_compensacao_inicio) AND ( (is_scalar($data->data_compensacao_inicio) AND $data->data_compensacao_inicio !== '') OR (is_array($data->data_compensacao_inicio) AND (!empty($data->data_compensacao_inicio)) )) )
        {

            $filters[] = new TFilter('data_compensacao', '>=', $data->data_compensacao_inicio);// create the filter 
        }

        if (isset($data->data_compensacao_fim) AND ( (is_scalar($data->data_compensacao_fim) AND $data->data_compensacao_fim !== '') OR (is_array($data->data_compensacao_fim) AND (!empty($data->data_compensacao_fim)) )) )
        {

            $filters[] = new TFilter('data_compensacao', '<=', $data->data_compensacao_fim);// create the filter 
        }

        if (isset($data->prev_compensacao_inicio) AND ( (is_scalar($data->prev_compensacao_inicio) AND $data->prev_compensacao_inicio !== '') OR (is_array($data->prev_compensacao_inicio) AND (!empty($data->prev_compensacao_inicio)) )) )
        {

            $filters[] = new TFilter('lancamento_id', 'in', "(SELECT id FROM lancamento WHERE dt_vencimento >= '{$data->prev_compensacao_inicio}')");// create the filter 
        }

        if (isset($data->prev_compensacao_fim) AND ( (is_scalar($data->prev_compensacao_fim) AND $data->prev_compensacao_fim !== '') OR (is_array($data->prev_compensacao_fim) AND (!empty($data->prev_compensacao_fim)) )) )
        {

            $filters[] = new TFilter('lancamento_id', 'in', "(SELECT id FROM lancamento WHERE dt_vencimento <= '{$data->prev_compensacao_fim}')");// create the filter 
        }

        if (isset($data->historico) AND ( (is_scalar($data->historico) AND $data->historico !== '') OR (is_array($data->historico) AND (!empty($data->historico)) )) )
        {

            $filters[] = new TFilter('historico', 'like', "%{$data->historico}%");// create the filter 
        }

        if (isset($data->entrada_valor) AND ( (is_scalar($data->entrada_valor) AND $data->entrada_valor !== '') OR (is_array($data->entrada_valor) AND (!empty($data->entrada_valor)) )) )
        {

            $filters[] = new TFilter('entrada_valor', '=', $data->entrada_valor);// create the filter 
        }

        if (isset($data->saida_valor) AND ( (is_scalar($data->saida_valor) AND $data->saida_valor !== '') OR (is_array($data->saida_valor) AND (!empty($data->saida_valor)) )) )
        {

            $filters[] = new TFilter('saida_valor', '=', $data->saida_valor);// create the filter 
        }

        if (isset($data->compensado_col) AND ( (is_scalar($data->compensado_col) AND $data->compensado_col !== '') OR (is_array($data->compensado_col) AND (!empty($data->compensado_col)) )) )
        {

            $filters[] = new TFilter('compensado', '=', $data->compensado_col);// create the filter 
        }

        if (isset($data->data_compensacao) AND ( (is_scalar($data->data_compensacao) AND $data->data_compensacao !== '') OR (is_array($data->data_compensacao) AND (!empty($data->data_compensacao)) )) )
        {

            $filters[] = new TFilter('data_compensacao', '=', $data->data_compensacao);// create the filter 
        }

        if (isset($data->conta_caixa_id_check) AND ( (is_scalar($data->conta_caixa_id_check) AND $data->conta_caixa_id_check !== '') OR (is_array($data->conta_caixa_id_check) AND (!empty($data->conta_caixa_id_check)) )) )
        {

            $filters[] = new TFilter('conta_caixa_id', 'in', $data->conta_caixa_id_check);// create the filter 
        }

        if (isset($data->lancamento_tipo_pagamento_nome) AND ( (is_scalar($data->lancamento_tipo_pagamento_nome) AND $data->lancamento_tipo_pagamento_nome !== '') OR (is_array($data->lancamento_tipo_pagamento_nome) AND (!empty($data->lancamento_tipo_pagamento_nome)) )) )
        {

            $filters[] = new TFilter('lancamento_id', 'in', $data->lancamento_tipo_pagamento_nome);// create the filter 
        }

        if (isset($data->tipo_extrato_nome) AND ( (is_scalar($data->tipo_extrato_nome) AND $data->tipo_extrato_nome !== '') OR (is_array($data->tipo_extrato_nome) AND (!empty($data->tipo_extrato_nome)) )) )
        {

            $filters[] = new TFilter('tipo_extrato_id', 'in', $data->tipo_extrato_nome);// create the filter 
        }

        if (isset($data->historico_col) AND ( (is_scalar($data->historico_col) AND $data->historico_col !== '') OR (is_array($data->historico_col) AND (!empty($data->historico_col)) )) )
        {

            $filters[] = new TFilter('historico', 'like', "%{$data->historico_col}%");// create the filter 
        }

        // fill the form with data again
        if ((isset($param['static']) && ($param['static'] == '1')) || !empty($param['globalSearch']))
        {
            $this->datagrid_form->setData($data);
        }
        else
        {
            $this->form->setData($data);
        }

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        if (isset($param['static']) && ($param['static'] == '1') )
        {
            $class = get_class($this);
            $onReloadParam = ['offset' => 0, 'first_page' => 1, 'target_container' => $param['target_container'] ?? null];
            AdiantiCoreApplication::loadPage($class, 'onReload', $onReloadParam);
            TScript::create('$(".select2").prev().select2("close");');
        }
        else
        {
            $this->onReload(['offset' => 0, 'first_page' => 1]);
        }
    }

    /**
     * Load the datagrid with data
     */
    public function onReload($param = NULL)
    {
        try
        {
            // open a transaction with database 'escritorio'
            TTransaction::open(self::$database);

            // creates a repository for Extrato
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'data_compensacao';    
            }

            if (empty($param['direction']))
            {
                $param['direction'] = 'desc';
            }

            $criteria->setProperties($param); // order, offset
            $criteria->setProperty('limit', $this->limit);

            if($filters = TSession::getValue(__CLASS__.'_filters'))
            {
                foreach ($filters as $filter) 
                {
                    $criteria->add($filter);       
                }
            }

            //</blockLine><btnShowCurtainFiltersAutoCode>
            if(!empty($this->btnShowCurtainFilters) && empty($this->btnShowCurtainFiltersAdjusted))
            {
                $this->btnShowCurtainFiltersAdjusted = true;
                $this->btnShowCurtainFilters->style = 'position: relative';
                $countFilters = count($filters ?? []);
                $this->btnShowCurtainFilters->setLabel($this->btnShowCurtainFilters->getLabel(). "<span class='badge badge-success' style='position: absolute'>{$countFilters}<span>");
            }
            //</blockLine></btnShowCurtainFiltersAutoCode>

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    $check = new TCheckButton('builder_datagrid_check[]');
                    $check->setIndexValue($object->id);
                    $check->onclick = 'event.stopPropagation();';
                    $object->builder_datagrid_check = $check;

                    $row = $this->datagrid->addItem($object);
                    $row->id = "row_{$object->id}";

                }
            }

            // reset the criteria for record count
            $criteria->resetProperties();
            $count= $repository->count($criteria);

            $this->pageNavigation->setCount($count); // count of records
            $this->pageNavigation->setProperties($param); // order, page
            $this->pageNavigation->setLimit($this->limit); // limit

            $this->datagrid->initPopoverHeaderFilters();

            // close the transaction
            TTransaction::close();
            $this->loaded = true;

            return $objects;
        }
        catch (Exception $e) // in case of exception
        {
            // shows the exception error message
            new TMessage('error', $e->getMessage());
            // undo all pending operations
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {

        $this->onClearFilters($param);

        if(isset($param['conta_caixa_id']) || isset($param['periodo']) || isset($param['visualizarNCompensados'])){

            $object = TSession::getValue(__CLASS__.'_filter_data') ?? new stdClass;
            $filters = [];

            $object->conta_caixa_id = $param['conta_caixa_id'] ?? null;
            if($object->conta_caixa_id!='' && $object->conta_caixa_id!=null){
                $filters[] = new TFilter('conta_caixa_id', '=', $object->conta_caixa_id);
                TSession::setValue('conta_caixa_id',$object->conta_caixa_id);
            }

            $visualizarNCompensados = $param['visualizarNCompensados'] ?? null;

            $object->compensado = '';

            $dias = $param['periodo'] ?? null;

            if(isset($dias)){
                if($visualizarNCompensados == 'T'){
                    $object->compensado = '';

                    $object->data_lancamento_inicio = date('Y-m-d', strtotime("-$dias days", strtotime(date('Y-m-d'))));
                    $object->data_lancamento_fim = date('Y-m-d');

                    if($object->data_lancamento_inicio!='' && $object->data_lancamento_inicio!=null){
                        $filters[] = new TFilter('data_lancamento', '>=', $object->data_lancamento_inicio);
                        $filters[] = new TFilter('data_lancamento', '<=', $object->data_lancamento_fim);
                    }
                }else{
                    $object->compensado = 'S';
                    if($object->conta_caixa_id!='' && $object->conta_caixa_id!=null)
                        $filters[] = new TFilter('compensado', '=', 'S');

                    $object->data_lancamento_inicio = '';
                    $object->data_lancamento_fim = '';

                    $object->data_compensacao_inicio = date('Y-m-d', strtotime("-$dias days", strtotime(date('Y-m-d'))));
                    $object->data_compensacao_fim = date('Y-m-d');

                    if($object->data_compensacao_inicio!='' && $object->data_compensacao_inicio!=null){
                        $filters[] = new TFilter('data_compensacao', '>=', $object->data_compensacao_inicio);
                        $filters[] = new TFilter('data_compensacao', '<=', $object->data_compensacao_fim);
                    }
                }
            }

            TForm::sendData(self::$formName, $object);

            TSession::setValue(__CLASS__.'_filter_data',$object);
            TSession::setValue(__CLASS__.'_filters',$filters);

            $this->onReload(['offset' => 0, 'first_page' => 1]);

        }
    }

    /**
     * method show()
     * Shows the page
     */
    public function show()
    {
        // check if the datagrid is already loaded
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  $this->showMethods))) )
        {
            if (func_num_args() > 0)
            {
                $this->onReload( func_get_arg(0) );
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }

    public static function manageRow($id, $param = [])
    {
        $list = new self($param);

        $openTransaction = TTransaction::getDatabase() != self::$database ? true : false;

        if($openTransaction)
        {
            TTransaction::open(self::$database);    
        }

        $object = new Extrato($id);

        $check = new TCheckButton('builder_datagrid_check[]');
        $check->setIndexValue($object->id);
        $check->onclick = 'event.stopPropagation();';
        $object->builder_datagrid_check = $check;

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

