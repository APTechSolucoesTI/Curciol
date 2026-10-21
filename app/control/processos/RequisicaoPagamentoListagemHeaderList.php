<?php

class RequisicaoPagamentoListagemHeaderList extends TPage
{
    private $form; // form (filtros da cortina)
    private $button_filtros;
    private static $formFiltros = 'formFiltros_RequisicaoPagamentoListagem';
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'RequisicaoPagamentoListagem';
    private static $primaryKey = 'requisicao_pagamento_id';
    private static $formName = 'formList_RequisicaoPagamentoListagem';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();
        // creates the form

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->limit = 50;

        $criteria_tipo_requisicao = new TCriteria();
        $criteria_status = new TCriteria();

        $numero_processo = new TEntry('numero_processo');
        $tipo_requisicao = new TDBCombo('tipo_requisicao', 'escritorio', 'TiposRequisicaoPagamento', 'id', '{nome}','id asc' , $criteria_tipo_requisicao );
        // autocomplete: a lista sugere as pessoas pelo nome (o filtro continua sendo pelo nome)
        $cliente = new TDBUniqueSearch('cliente', 'escritorio', 'Pessoa', 'nome', 'nome', 'nome asc', FiltroLista::criterioGrupo(Grupo::CLIENTE));
        $cliente->setMinLength(2);
        $data_requerimento = new BDateRange('data_requerimento', 'data_requerimento1');
        $data_deferimento_expedicao_requisitorio = new BDateRange('data_deferimento_expedicao_requisitorio', 'data_deferimento_expedicao_requisitorio1');
        $data_pedido_mle = new BDateRange('data_pedido_mle', 'data_pedido_mle1');
        $data_deferimento_mle = new BDateRange('data_deferimento_mle', 'data_deferimento_mle2');
        $status = new TDBCombo('status', 'escritorio', 'StatusRequisicaoPagamento', 'id', '{nome}','id asc' , $criteria_status );

        $numero_processo->exitOnEnter();

        $numero_processo->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $cliente->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $data_requerimento->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $data_deferimento_expedicao_requisitorio->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $data_pedido_mle->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $data_deferimento_mle->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $tipo_requisicao->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $status->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $status->enableSearch();
        $tipo_requisicao->enableSearch();

        $data_pedido_mle->setMask('dd/mm/yyyy');
        $data_requerimento->setMask('dd/mm/yyyy');
        $data_deferimento_mle->setMask('dd/mm/yyyy');
        $data_deferimento_expedicao_requisitorio->setMask('dd/mm/yyyy');

        $data_pedido_mle->setDatabaseMask('yyyy-mm-dd');
        $data_requerimento->setDatabaseMask('yyyy-mm-dd');
        $data_deferimento_mle->setDatabaseMask('yyyy-mm-dd');
        $data_deferimento_expedicao_requisitorio->setDatabaseMask('yyyy-mm-dd');

        $status->setSize('100%');
        $cliente->setSize('100%');
        $data_pedido_mle->setSize(220);
        $data_requerimento->setSize(220);
        $numero_processo->setSize('100%');
        $tipo_requisicao->setSize('100%');
        $data_deferimento_mle->setSize(220);
        $data_deferimento_expedicao_requisitorio->setSize(220);

        /*
            Filtros na cortina (botao "Filtros"), no padrao das listagens do
            sistema. Os campos tem sufixo _f para nao colidir com os filtros
            das colunas; onSearch tira o sufixo e usa a mesma regra de filtro.
        */
        $this->form = new BootstrapFormBuilder(self::$formFiltros);
        $this->form->setFormTitle("Filtros de requisições de pagamento");

        $numero_processo_f = new TEntry('numero_processo_f');
        $tipo_requisicao_f = new TDBCombo('tipo_requisicao_f', 'escritorio', 'TiposRequisicaoPagamento', 'id', '{nome}', 'id asc');
        // autocomplete: a lista sugere as pessoas pelo nome (o filtro continua sendo pelo nome)
        $cliente_f = new TDBUniqueSearch('cliente_f', 'escritorio', 'Pessoa', 'nome', 'nome', 'nome asc', FiltroLista::criterioGrupo(Grupo::CLIENTE));
        $cliente_f->setMinLength(2);
        $status_f = new TDBCombo('status_f', 'escritorio', 'StatusRequisicaoPagamento', 'id', '{nome}', 'id asc');
        $data_requerimento_f = new BDateRange('data_requerimento_f', 'data_requerimento1_f');
        $data_deferimento_expedicao_requisitorio_f = new BDateRange('data_deferimento_expedicao_requisitorio_f', 'data_deferimento_expedicao_requisitorio1_f');
        $data_pedido_mle_f = new BDateRange('data_pedido_mle_f', 'data_pedido_mle1_f');
        $data_deferimento_mle_f = new BDateRange('data_deferimento_mle_f', 'data_deferimento_mle2_f');

        foreach ([$data_requerimento_f, $data_deferimento_expedicao_requisitorio_f, $data_pedido_mle_f, $data_deferimento_mle_f] as $periodo_f)
        {
            $periodo_f->setMask('dd/mm/yyyy');
            $periodo_f->setDatabaseMask('yyyy-mm-dd');
            $periodo_f->setSize('100%');
        }
        foreach ([$numero_processo_f, $tipo_requisicao_f, $cliente_f, $status_f] as $campo_f)
        {
            $campo_f->setSize('100%');
        }
        $tipo_requisicao_f->enableSearch();
        $status_f->enableSearch();

        $row_f1 = $this->form->addFields([new TLabel("Processo:", null, '14px', null, '100%'), $numero_processo_f], [new TLabel("Cliente:", null, '14px', null, '100%'), $cliente_f]);
        $row_f1->layout = ['col-sm-6', 'col-sm-6'];
        $row_f2 = $this->form->addFields([new TLabel("Tipo de requisição:", null, '14px', null, '100%'), $tipo_requisicao_f], [new TLabel("Status:", null, '14px', null, '100%'), $status_f]);
        $row_f2->layout = ['col-sm-6', 'col-sm-6'];
        $row_f3 = $this->form->addFields([new TLabel("Data do requerimento:", null, '14px', null, '100%'), $data_requerimento_f], [new TLabel("Data de deferimento da expedição:", null, '14px', null, '100%'), $data_deferimento_expedicao_requisitorio_f]);
        $row_f3->layout = ['col-sm-6', 'col-sm-6'];
        $row_f4 = $this->form->addFields([new TLabel("Data do pedido de MLE:", null, '14px', null, '100%'), $data_pedido_mle_f], [new TLabel("Data de deferimento do MLE:", null, '14px', null, '100%'), $data_deferimento_mle_f]);
        $row_f4->layout = ['col-sm-6', 'col-sm-6'];

        $this->form->setData(self::comSufixo(TSession::getValue(__CLASS__.'_filter_data')));

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $btn_onsearch->addStyleClass('btn-primary');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_numero_processo = new TDataGridColumn('numero_processo', "Processo", 'left');
        $column_tipo_requisicao_transformed = new TDataGridColumn('tipo_requisicao', "Tipo de Requisição", 'center');
        $column_cliente = new TDataGridColumn('cliente', "Clientes", 'left');
        $column_data_requerimento_transformed = new TDataGridColumn('data_requerimento', "Data do Requerimento", 'left');
        $column_data_deferimento_expedicao_requisitorio_transformed = new TDataGridColumn('data_deferimento_expedicao_requisitorio', "Data de Deferimento da Expedição", 'left');
        $column_data_pedido_mle_transformed = new TDataGridColumn('data_pedido_mle', "Data do Pedido MLE", 'left');
        $column_data_deferimento_mle_transformed = new TDataGridColumn('data_deferimento_mle', "Data de Deferimento do MLE", 'left');
        $column_status_transformed = new TDataGridColumn('status', "Status", 'center');

        $column_tipo_requisicao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {   
            TTransaction::open(self::$database);
            $trp = TiposRequisicaoPagamento::find($value);
            TTransaction::close();

            return $trp->nome;    
        });

        $column_data_requerimento_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_data_deferimento_expedicao_requisitorio_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_data_pedido_mle_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_data_deferimento_mle_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_status_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            if (empty($value)) {
                return '';
            }

            try {
                TTransaction::open(self::$database);

                $srp = StatusRequisicaoPagamento::find($value);

                TTransaction::close();

                if (empty($srp)) {
                    return '';
                }

                $nome = htmlspecialchars($srp->nome ?? '', ENT_QUOTES, 'UTF-8');

                $cor = trim($srp->cor ?? '');

                if (empty($cor)) {
                    $cor = '#6c757d';
                }

                if ($cor[0] !== '#') {
                    $cor = '#' . $cor;
                }

                return "
                    <span style=\"
                        display: inline-flex;
                        align-items: center;
                        justify-content: center;
                        padding: 5px 12px;
                        border-radius: 999px;
                        background: {$cor}
                        ;
                        color: #ffffff;
                        font-size: 12px;
                        font-weight: 600;
                        line-height: 1;
                        min-width: 90px;
                        box-shadow: 0 2px 6px rgba(0,0,0,.12);
                        white-space: nowrap;
                    \">
                        {$nome}
                    </span>
                ";
            }
            catch (Exception $e) {
                if (TTransaction::get()) {
                    TTransaction::rollback();
                }

                return '';
            }

        });        

        $order_numero_processo = new TAction(array($this, 'onReload'));
        $order_numero_processo->setParameter('order', 'numero_processo');
        $column_numero_processo->setAction($order_numero_processo);
        $order_tipo_requisicao_transformed = new TAction(array($this, 'onReload'));
        $order_tipo_requisicao_transformed->setParameter('order', 'tipo_requisicao');
        $column_tipo_requisicao_transformed->setAction($order_tipo_requisicao_transformed);
        $order_cliente = new TAction(array($this, 'onReload'));
        $order_cliente->setParameter('order', 'cliente');
        $column_cliente->setAction($order_cliente);
        $order_data_requerimento_transformed = new TAction(array($this, 'onReload'));
        $order_data_requerimento_transformed->setParameter('order', 'data_requerimento');
        $column_data_requerimento_transformed->setAction($order_data_requerimento_transformed);
        $order_data_deferimento_expedicao_requisitorio_transformed = new TAction(array($this, 'onReload'));
        $order_data_deferimento_expedicao_requisitorio_transformed->setParameter('order', 'data_deferimento_expedicao_requisitorio');
        $column_data_deferimento_expedicao_requisitorio_transformed->setAction($order_data_deferimento_expedicao_requisitorio_transformed);
        $order_data_pedido_mle_transformed = new TAction(array($this, 'onReload'));
        $order_data_pedido_mle_transformed->setParameter('order', 'data_pedido_mle');
        $column_data_pedido_mle_transformed->setAction($order_data_pedido_mle_transformed);
        $order_data_deferimento_mle_transformed = new TAction(array($this, 'onReload'));
        $order_data_deferimento_mle_transformed->setParameter('order', 'data_deferimento_mle');
        $column_data_deferimento_mle_transformed->setAction($order_data_deferimento_mle_transformed);
        $order_status_transformed = new TAction(array($this, 'onReload'));
        $order_status_transformed->setParameter('order', 'status');
        $column_status_transformed->setAction($order_status_transformed);

        $column_status_transformed->disableHtmlConversion();

        $this->datagrid->addColumn($column_numero_processo);
        $this->datagrid->addColumn($column_tipo_requisicao_transformed);
        $this->datagrid->addColumn($column_cliente);
        $this->datagrid->addColumn($column_data_requerimento_transformed);
        $this->datagrid->addColumn($column_data_deferimento_expedicao_requisitorio_transformed);
        $this->datagrid->addColumn($column_data_pedido_mle_transformed);
        $this->datagrid->addColumn($column_data_deferimento_mle_transformed);
        $this->datagrid->addColumn($column_status_transformed);

        $action_onVisualizarRequisicao = new TDataGridAction(array('RequisicaoPagamentoListagemHeaderList', 'onVisualizarRequisicao'));
        $action_onVisualizarRequisicao->setUseButton(false);
        $action_onVisualizarRequisicao->setButtonClass('btn btn-default btn-sm');
        $action_onVisualizarRequisicao->setLabel("");
        $action_onVisualizarRequisicao->setImage('fas:search-plus #000000');
        $action_onVisualizarRequisicao->setField(self::$primaryKey);

        $action_onVisualizarRequisicao->setParameter('key', '{requisicao_pagamento_id}');

        $this->datagrid->addAction($action_onVisualizarRequisicao);

        $action_onEditar = new TDataGridAction(array('RequisicaoPagamentoListagemHeaderList', 'onEditar'));
        $action_onEditar->setUseButton(false);
        $action_onEditar->setButtonClass('btn btn-default btn-sm');
        $action_onEditar->setLabel("Editar Requisição");
        $action_onEditar->setImage('fas:edit #000000');
        $action_onEditar->setField(self::$primaryKey);
        $action_onEditar->setDisplayCondition('RequisicaoPagamentoListagemHeaderList::sePodeEditar');

        $this->datagrid->addAction($action_onEditar);

        $action_onVisualizarRequisicao->setParameter('key', '{requisicao_pagamento_id}');
        $action_onVisualizarRequisicao->setParameter('requisicao_pagamento_id', '{requisicao_pagamento_id}');
        $action_onVisualizarRequisicao->setParameter('pessoa_id', '{pessoa_id}');
        $action_onVisualizarRequisicao->setParameter('requisicao_pagamento_cliente_id', '{requisicao_pagamento_cliente_id}');

        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);

        if(!$action_onVisualizarRequisicao->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        if(!$action_onEditar->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        $td_numero_processo = TElement::tag('td', $numero_processo);
        $tr->add($td_numero_processo);
        $td_tipo_requisicao = TElement::tag('td', $tipo_requisicao);
        $tr->add($td_tipo_requisicao);
        $td_cliente = TElement::tag('td', $cliente);
        $tr->add($td_cliente);
        $td_data_requerimento = TElement::tag('td', $data_requerimento);
        $tr->add($td_data_requerimento);
        $td_data_deferimento_expedicao_requisitorio = TElement::tag('td', $data_deferimento_expedicao_requisitorio);
        $tr->add($td_data_deferimento_expedicao_requisitorio);
        $td_data_pedido_mle = TElement::tag('td', $data_pedido_mle);
        $tr->add($td_data_pedido_mle);
        $td_data_deferimento_mle = TElement::tag('td', $data_deferimento_mle);
        $tr->add($td_data_deferimento_mle);
        $td_status = TElement::tag('td', $status);
        $tr->add($td_status);

        $this->datagrid_form->addField($numero_processo);
        $this->datagrid_form->addField($tipo_requisicao);
        $this->datagrid_form->addField($cliente);
        $this->datagrid_form->addField($data_requerimento);
        $this->datagrid_form->addField($data_deferimento_expedicao_requisitorio);
        $this->datagrid_form->addField($data_pedido_mle);
        $this->datagrid_form->addField($data_deferimento_mle);
        $this->datagrid_form->addField($status);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $panel->getHeader()->style = ' display:none !important; ';
        $panel->getBody()->class .= ' table-responsive';

        $panel->addFooter($this->pageNavigation);

        $headerActions = new TElement('div');
        $headerActions->class = ' datagrid-header-actions ';

        $head_left_actions = new TElement('div');
        $head_left_actions->class = ' datagrid-header-actions-left-actions ';

        $head_right_actions = new TElement('div');
        $head_right_actions->class = ' datagrid-header-actions-left-actions ';

        $headerActions->add($head_left_actions);
        $headerActions->add($head_right_actions);

        $this->datagrid_form->add($headerActions);
        $panel->add($this->datagrid_form);

        $button_nova_requisicao = new TButton('button_button_nova_requisicao');
        $button_nova_requisicao->setAction(new TAction(['RequisicaoPagamentoListagemHeaderList', 'onNovaRequisicao']), "Nova Requisição");
        $button_nova_requisicao->addStyleClass('btn-default');
        $button_nova_requisicao->setImage('fas:plus #000000');

        $this->datagrid_form->addField($button_nova_requisicao);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['RequisicaoPagamentoListagemHeaderList', 'onExportCsv'],['static' => 1]), self::$formName, 'fas:file-csv #00b894' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['RequisicaoPagamentoListagemHeaderList', 'onExportXls'],['static' => 1]), self::$formName, 'fas:file-excel #4CAF50' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['RequisicaoPagamentoListagemHeaderList', 'onExportPdf'],['static' => 1]), self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XML", new TAction(['RequisicaoPagamentoListagemHeaderList', 'onExportXml'],['static' => 1]), self::$formName, 'far:file-code #95a5a6' );

        $button_filtros = new TButton('button_button_filtros');
        $button_filtros->setAction(new TAction(['RequisicaoPagamentoListagemHeaderList', 'onShowCurtainFilters']), "Filtros");
        $button_filtros->addStyleClass('btn-default');
        $button_filtros->setImage('fas:filter #000000');
        $this->datagrid_form->addField($button_filtros);
        $this->button_filtros = $button_filtros;

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['RequisicaoPagamentoListagemHeaderList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');
        $this->datagrid_form->addField($button_atualizar);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['RequisicaoPagamentoListagemHeaderList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');
        $this->datagrid_form->addField($button_limpar_filtros);

        $head_left_actions->add($button_nova_requisicao);
        $head_left_actions->add($button_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($button_limpar_filtros);

        $this->marcarQuantidadeDeFiltros(TSession::getValue(__CLASS__.'_filters') ?: []);

        $head_right_actions->add($dropdown_button_exportar);

        $this->datagrid_form->add($this->datagrid);

        $button_relatorio_dinamico = new TButton('button_relatorio_dinamico');

            $button_relatorio_dinamico->setAction(
                new TAction(['RequisicaoPagamentoListagemHeaderList', 'onRelatorioDinamico']),
                'Relatório Dinâmico'
            );

            $button_relatorio_dinamico->addStyleClass('btn-default');
            $button_relatorio_dinamico->setImage('fas:file-excel #218838');

            $this->datagrid_form->addField($button_relatorio_dinamico);

        $head_right_actions->add($button_relatorio_dinamico);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Processos","Requisição de Pagamento"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    public function onVisualizarRequisicao($param = null) 
    {
    try 
        {
            $requisicaoId = $param['requisicao_pagamento_id'] ?? $param['key'] ?? null;
            $pessoaId = $param['pessoa_id'] ?? null;
            $rpcId = $param['requisicao_pagamento_cliente_id'] ?? null;

            if (empty($requisicaoId)) {
                throw new Exception('Requisição de pagamento não informada.');
            }

            $params = [
                'key' => $requisicaoId,
                'register_state' => 'false'
            ];

            if (!empty($pessoaId)) {
                $params['pessoa_id'] = $pessoaId;
            }

            if (!empty($rpcId)) {
                $params['requisicao_pagamento_cliente_id'] = $rpcId;
            }

            TApplication::loadPage('RequisicaoPagamentoVisualizacao', 'onShow', $params);
            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onEditar($param = null) 
    {
      try 
        {
            $requisicao_id = $param['key'] ?? $param['id'] ?? null;

            if (empty($requisicao_id)) {
                throw new Exception('Requisição não informada.');
            }

            TApplication::loadPage('RequisicaoPagamentos', 'onShow', [
                'key' => $requisicao_id,
                'requisicao_pagamento_id' => $requisicao_id,
                'modo_edicao' => 1,
                'step' => 'selecionar_clientes',
                'register_state' => 'false'
            ]);

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function sePodeEditar($object)
    {
       if (empty($object)) {
            return false;
        }

        $requisicaoId = (int) ($object->requisicao_pagamento_id ?? $object->id ?? 0);

        if (empty($requisicaoId)) {
            return false;
        }

        $conn = TTransaction::get();

        $sql = "
            SELECT 
                CASE 
                    WHEN rp.tipos_requisicao_pagamento_id = 3 THEN
                        CASE 
                            WHEN EXISTS (
                                SELECT 1
                                FROM requisicao_pagamento_cliente rpc
                                JOIN requisicao_pagamento_etapa3 e3
                                    ON e3.requisicao_pagamento_cliente_id = rpc.id
                                WHERE rpc.requisicao_pagamento_id = rp.id
                            ) THEN 0
                            ELSE 1
                        END
                    ELSE
                        CASE 
                            WHEN EXISTS (
                                SELECT 1
                                FROM requisicao_pagamento_cliente rpc
                                JOIN requisicao_pagamento_etapa2 e2
                                    ON e2.requisicao_pagamento_cliente_id = rpc.id
                                WHERE rpc.requisicao_pagamento_id = rp.id
                            ) THEN 0
                            ELSE 1
                        END
                END AS pode_editar
            FROM requisicao_pagamento rp
            WHERE rp.id = :requisicao_pagamento_id
            LIMIT 1
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':requisicao_pagamento_id', $requisicaoId);
        $stmt->execute();

        return ((int) $stmt->fetchColumn()) === 1;
    }
    public function onExportCsv($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.csv';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $objects = $this->onReload();

                if ($objects)
                {
                    $handler = fopen($output, 'w');
                    TTransaction::open(self::$database);

                    foreach ($objects as $object)
                    {
                        $row = [];
                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();

                            if (isset($object->$column_name))
                            {
                                $row[] = is_scalar($object->$column_name) ? $object->$column_name : '';
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $row[] = $object->render($column_name);
                            }
                        }

                        fputcsv($handler, $row);
                    }

                    fclose($handler);
                    TTransaction::close();
                }
                else
                {
                    throw new Exception(_t('No records found'));
                }

                TPage::openFile($output);
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public function onExportXls($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.xls';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $widths = [];
                $titles = [];

                foreach ($this->datagrid->getColumns() as $column)
                {
                    $titles[] = $column->getLabel();
                    $width    = 100;

                    if (is_null($column->getWidth()))
                    {
                        $width = 100;
                    }
                    else if (strpos((string)$column->getWidth(), '%') !== false)
                    {
                        $width = ((int) $column->getWidth()) * 5;
                    }
                    else if (is_numeric($column->getWidth()))
                    {
                        $width = $column->getWidth();
                    }

                    $widths[] = $width;
                }

                $table = new \TTableWriterXLS($widths);
                $table->addStyle('title',  'Helvetica', '10', 'B', '#ffffff', '#617FC3');
                $table->addStyle('data',   'Helvetica', '10', '',  '#000000', '#FFFFFF', 'LR');

                $table->addRow();

                foreach ($titles as $title)
                {
                    $table->addCell($title, 'center', 'title');
                }

                $this->limit = 0;
                $objects = $this->onReload();

                TTransaction::open(self::$database);
                if ($objects)
                {
                    foreach ($objects as $object)
                    {
                        $table->addRow();
                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();
                            $value = '';
                            if (isset($object->$column_name))
                            {
                                $value = is_scalar($object->$column_name) ? $object->$column_name : '';
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $value = $object->render($column_name);
                            }

                            $transformer = $column->getTransformer();
                            if ($transformer)
                            {
                                $value = strip_tags((string)call_user_func($transformer, $value, $object, null));
                            }

                            $table->addCell($value, 'center', 'data');
                        }
                    }
                }
                $table->save($output);
                TTransaction::close();

                TPage::openFile($output);
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public function onExportPdf($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.pdf';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $this->datagrid->prepareForPrinting();
                $this->onReload();

                $html = clone $this->datagrid;
                $contents = file_get_contents('app/resources/styles-print.html') . $html->getContents();

                $dompdf = new \Dompdf\Dompdf;
                $dompdf->loadHtml($contents);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                file_put_contents($output, $dompdf->output());

                $window = TWindow::create('PDF', 0.8, 0.8);
                $object = new TElement('iframe');
                $object->src  = $output;
                $object->type  = 'application/pdf';
                $object->style = "width: 100%; height:calc(100% - 10px)";

                $window->add($object);
                $window->show();
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public function onExportXml($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.xml';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $objects = $this->onReload();

                if ($objects)
                {
                    TTransaction::open(self::$database);

                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->{'formatOutput'} = true;
                    $dataset = $dom->appendChild( $dom->createElement('dataset') );

                    foreach ($objects as $object)
                    {
                        $row = $dataset->appendChild( $dom->createElement( self::$activeRecord ) );

                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();
                            $column_name_raw = str_replace(['(','{','->', '-','>','}',')', ' '], ['','','_','','','','','_'], $column_name);

                            if (isset($object->$column_name))
                            {
                                $value = is_scalar($object->$column_name) ? $object->$column_name : '';
                                $row->appendChild($dom->createElement($column_name_raw, $value)); 
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $value = $object->render($column_name);
                                $row->appendChild($dom->createElement($column_name_raw, $value));
                            }
                        }
                    }

                    $dom->save($output);

                    TTransaction::close();
                }
                else
                {
                    throw new Exception(_t('No records found'));
                }

                TPage::openFile($output);
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }
    public function onNovaRequisicao($param = null) 
    {
        try 
        {
           TApplication::loadPage('RequisicaoPagamentos', 'onShow', [
            'register_state' => 'false'
            ]);

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
        // filtros das colunas (static) ou da cortina (sufixo _f)
        if (isset($param['static']) && ($param['static'] == '1'))
        {
            $formulario = $this->datagrid_form->getData();
        }
        else
        {
            $formulario = self::semSufixo($this->form->getData());
        }
        // filtros que se somam: cabecalho das colunas e cortina (FiltroLista)
        $data = FiltroLista::acumular(__CLASS__, $formulario, $param);
        $filters = [];

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->numero_processo) AND ( (is_scalar($data->numero_processo) AND $data->numero_processo !== '') OR (is_array($data->numero_processo) AND (!empty($data->numero_processo)) )) )
        {

            $filters[] = new TFilter('unaccent(CAST(numero_processo AS TEXT))', 'ilike', '%' . TratamentosService::removerAcentos((string) $data->numero_processo) . '%');// create the filter 
        }

        if (isset($data->tipo_requisicao) AND ( (is_scalar($data->tipo_requisicao) AND $data->tipo_requisicao !== '') OR (is_array($data->tipo_requisicao) AND (!empty($data->tipo_requisicao)) )) )
        {

            $filters[] = new TFilter('tipo_requisicao', '=', $data->tipo_requisicao);// create the filter 
        }

        if (isset($data->cliente) AND ( (is_scalar($data->cliente) AND $data->cliente !== '') OR (is_array($data->cliente) AND (!empty($data->cliente)) )) )
        {

            $filters[] = new TFilter('cliente', 'ilike', "%{$data->cliente}%");// create the filter 
        }

        if (isset($data->data_requerimento1) AND ( (is_scalar($data->data_requerimento1) AND $data->data_requerimento1 !== '') OR (is_array($data->data_requerimento1) AND (!empty($data->data_requerimento1)) )) )
        {

            $filters[] = new TFilter('data_requerimento', '<=', $data->data_requerimento1);// create the filter 
        }

        if (isset($data->data_requerimento) AND ( (is_scalar($data->data_requerimento) AND $data->data_requerimento !== '') OR (is_array($data->data_requerimento) AND (!empty($data->data_requerimento)) )) )
        {

            $filters[] = new TFilter('data_requerimento', '>=', $data->data_requerimento);// create the filter 
        }

        if (isset($data->data_deferimento_expedicao_requisitorio1) AND ( (is_scalar($data->data_deferimento_expedicao_requisitorio1) AND $data->data_deferimento_expedicao_requisitorio1 !== '') OR (is_array($data->data_deferimento_expedicao_requisitorio1) AND (!empty($data->data_deferimento_expedicao_requisitorio1)) )) )
        {

            $filters[] = new TFilter('data_deferimento_expedicao_requisitorio', '<=', $data->data_deferimento_expedicao_requisitorio1);// create the filter 
        }

        if (isset($data->data_deferimento_expedicao_requisitorio) AND ( (is_scalar($data->data_deferimento_expedicao_requisitorio) AND $data->data_deferimento_expedicao_requisitorio !== '') OR (is_array($data->data_deferimento_expedicao_requisitorio) AND (!empty($data->data_deferimento_expedicao_requisitorio)) )) )
        {

            $filters[] = new TFilter('data_deferimento_expedicao_requisitorio', '>=', $data->data_deferimento_expedicao_requisitorio);// create the filter 
        }

        if (isset($data->data_pedido_mle1) AND ( (is_scalar($data->data_pedido_mle1) AND $data->data_pedido_mle1 !== '') OR (is_array($data->data_pedido_mle1) AND (!empty($data->data_pedido_mle1)) )) )
        {

            $filters[] = new TFilter('data_pedido_mle', '<=', $data->data_pedido_mle1);// create the filter 
        }

        if (isset($data->data_pedido_mle) AND ( (is_scalar($data->data_pedido_mle) AND $data->data_pedido_mle !== '') OR (is_array($data->data_pedido_mle) AND (!empty($data->data_pedido_mle)) )) )
        {

            $filters[] = new TFilter('data_pedido_mle', '>=', $data->data_pedido_mle);// create the filter 
        }

        if (isset($data->data_deferimento_mle2) AND ( (is_scalar($data->data_deferimento_mle2) AND $data->data_deferimento_mle2 !== '') OR (is_array($data->data_deferimento_mle2) AND (!empty($data->data_deferimento_mle2)) )) )
        {

            $filters[] = new TFilter('data_deferimento_mle', '<=', $data->data_deferimento_mle2);// create the filter 
        }

        if (isset($data->data_deferimento_mle) AND ( (is_scalar($data->data_deferimento_mle) AND $data->data_deferimento_mle !== '') OR (is_array($data->data_deferimento_mle) AND (!empty($data->data_deferimento_mle)) )) )
        {

            $filters[] = new TFilter('data_deferimento_mle', '>=', $data->data_deferimento_mle);// create the filter 
        }

        if (isset($data->status) AND ( (is_scalar($data->status) AND $data->status !== '') OR (is_array($data->status) AND (!empty($data->status)) )) )
        {

            $filters[] = new TFilter('status', '=', $data->status);// create the filter 
        }

        // fill the forms with data again
        $this->datagrid_form->setData($data);
        $this->form->setData(self::comSufixo($data));
        $this->marcarQuantidadeDeFiltros($filters);

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

            // creates a repository for RequisicaoPagamentoListagem
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'requisicao_pagamento_id';    
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

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {

                    $row = $this->datagrid->addItem($object);
                    $row->id = "row_{$object->requisicao_pagamento_id}";

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

    }

    public static function onShowCurtainFilters($param = null)
    {
        try
        {
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
            $page->setProperty('page-name', 'RequisicaoPagamentoListagemSearch');
            $page->setProperty('page_name', 'RequisicaoPagamentoListagemSearch');
            $page->adianti_target_container = 'adianti_right_panel';
            $page->target_container = 'adianti_right_panel';
            $page->add($filter->form);
            $page->setIsWrapped(true);
            $page->show();
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

    public function onClearFilters($param = null)
    {
        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        $this->datagrid_form->clear();
        $this->form->clear();
        $this->marcarQuantidadeDeFiltros([]);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    /** Mostra no botao "Filtros" quantos filtros estao ativos. */
    private function marcarQuantidadeDeFiltros(array $filters)
    {
        if (!$this->button_filtros) { return; }
        $this->button_filtros->style = 'position: relative';
        $qtd = count($filters);
        $this->button_filtros->setLabel($qtd ? "Filtros<span class='badge badge-success' style='position: absolute'>{$qtd}</span>" : "Filtros");
    }

    /** Dados da cortina (campos com _f) -> nomes usados nos filtros. */
    private static function semSufixo($dados)
    {
        $saida = new stdClass;
        foreach ((array) $dados as $chave => $valor)
        {
            $saida->{preg_replace('/_f$/', '', $chave)} = $valor;
        }
        return $saida;
    }

    /** Dados da sessao -> campos da cortina (com _f). */
    private static function comSufixo($dados)
    {
        if (empty($dados)) { return null; }
        $saida = new stdClass;
        foreach ((array) $dados as $chave => $valor)
        {
            $saida->{$chave . '_f'} = $valor;
        }
        return $saida;
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

        $object = new RequisicaoPagamentoListagem($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->requisicao_pagamento_id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

    public static function onRelatorioDinamico($param = null)
    {
        try
        {
            TApplication::loadPage(
                'RequisicaoPagamentoRelatorioDinamico',
                'onShow',
                [
                    'register_state' => 'false'
                ]
            );
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

}

