<?php

/**
 * Beneficiarios do credito (Configuracoes > Geral).
 *
 * Quem recebe o credito do levantamento judicial e a regra de calculo que
 * vale para ele (cliente e honorarios / somente honorarios / somente
 * cliente). Usados pela geracao do financeiro do levantamento.
 *
 * Estrutura igual as demais listagens (ClienteList): botoes no topo,
 * cortina de filtros, filtro nas colunas, busca global e exportacao.
 */
class BeneficiarioCreditoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'BeneficiarioCredito';
    private static $primaryKey = 'id';
    private static $formName = 'formList_BeneficiarioCredito';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    use BuilderDatagridTrait;

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
        $this->form->setFormTitle("Beneficiários do crédito");
        $this->limit = 20;

        $nome = new TEntry('nome');
        $regra = new TCombo('regra');
        $nome_col = new TEntry('nome_col');
        $regra_col = new TCombo('regra_col');
        $global_filter = new TEntry('global_filter');

        $regra->addItems(BeneficiarioCredito::regras());
        $regra_col->addItems(BeneficiarioCredito::regras());

        $nome_col->exitOnEnter();

        $global_filter->setEnterAction(new TAction([$this, 'onGlobalSearch']));

        $nome_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $regra_col->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $global_filter->setInnerIcon(new TImage('fas:search #9E9E9E'), 'left');

        $nome->forceUpperCase();
        $nome_col->forceUpperCase();
        $global_filter->forceUpperCase();

        $nome->setSize('100%');
        $regra->setSize('100%');
        $nome_col->setSize('100%');
        $regra_col->setSize('100%');
        $global_filter->setSize(200);

        $row1 = $this->form->addFields([new TLabel("Nome:", null, '14px', null, '100%'),$nome],[new TLabel("Regra de cálculo:", null, '14px', null),$regra]);
        $row1->layout = [' col-sm-6',' col-sm-6'];

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->enableUserProperties('fa fa-cog', 'btn btn-default', new TAction([$this, 'setDatagridProperties']));
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid_form->addField($global_filter);
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(400);

        $column_id = new TDataGridColumn('id', "Código", 'center' , '70px');
        $column_nome = new TDataGridColumn('nome', "Nome", 'left');
        $column_regra_transformed = new TDataGridColumn('regra', "Regra de cálculo", 'left');
        $column_ativo_transformed = new TDataGridColumn('ativo', "Ativo", 'center', '80px');

        $column_regra_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return BeneficiarioCredito::regras()[$value] ?? $value;
        });

        $column_ativo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return ($value === 'S') ? 'Sim' : 'Não';
        });

        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        $order_regra = new TAction(array($this, 'onReload'));
        $order_regra->setParameter('order', 'regra');
        $column_regra_transformed->setAction($order_regra);
        $order_ativo = new TAction(array($this, 'onReload'));
        $order_ativo->setParameter('order', 'ativo');
        $column_ativo_transformed->setAction($order_ativo);

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_regra_transformed);
        $this->datagrid->addColumn($column_ativo_transformed);

        $action_onEdit = new TDataGridAction(array('BeneficiarioCreditoForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Editar");
        $action_onEdit->setImage('far:edit #478fca');
        $action_onEdit->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onEdit);

        $this->applyDatagridProperties();
        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);

        if(!$action_onEdit->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $tr->add(TElement::tag('td', $nome_col));
        $tr->add(TElement::tag('td', $regra_col));
        $tr->add(TElement::tag('td', ''));

        $this->datagrid_form->addField($nome_col);
        $this->datagrid_form->addField($regra_col);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Beneficiários do crédito");
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

        $button_buscar = new TButton('button_button_buscar');
        $button_buscar->setAction(new TAction(['BeneficiarioCreditoList', 'onGlobalSearch']), "Buscar");
        $button_buscar->addStyleClass('btn-default');
        $button_buscar->setImage('fas:search #9E9E9E');

        $this->datagrid_form->addField($button_buscar);

        $button_cadastrar = new TButton('button_button_cadastrar');
        $button_cadastrar->setAction(new TAction(['BeneficiarioCreditoForm', 'onEdit']), "Cadastrar");
        $button_cadastrar->addStyleClass('btn-default');
        $button_cadastrar->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($button_cadastrar);

        $button_filtros = new TButton('button_button_filtros');
        $button_filtros->setAction(new TAction(['BeneficiarioCreditoList', 'onShowCurtainFilters']), "Filtros");
        $button_filtros->addStyleClass('btn-default');
        $button_filtros->setImage('fas:filter #000000');

        $this->datagrid_form->addField($button_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['BeneficiarioCreditoList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['BeneficiarioCreditoList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['BeneficiarioCreditoList', 'onExportCsv'],['static' => 1]), 'datagrid_'.self::$formName, 'fas:table #00b894' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['BeneficiarioCreditoList', 'onExportPdf'],['static' => 1]), 'datagrid_'.self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['BeneficiarioCreditoList', 'onExportXls']), 'datagrid_'.self::$formName, 'fas:file-excel #4CAF50' );

        $head_left_actions->add($button_cadastrar);
        $head_left_actions->add($button_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($button_limpar_filtros);
        $head_left_actions->add($dropdown_button_exportar);

        $head_right_actions->add($global_filter);
        $head_right_actions->add($button_buscar);

        $this->datagrid_form->add($this->datagrid);

        $this->button_filtros = $button_filtros;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Configurações","Financeiro","Beneficiários do crédito"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    // =================================================================
    // Padrao das listagens
    // =================================================================

    public function onGlobalSearch($param = null)
    {
        $param['globalSearch'] = true;
        $this->onSearch($param);
    }

    public static function onShowCurtainFilters($param = null)
    {
        try
        {
            $object = new stdClass();
            $object->nome = null;
            $object->regra = null;

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
            $page->setProperty('page-name', 'BeneficiarioCreditoListSearch');
            $page->setProperty('page_name', 'BeneficiarioCreditoListSearch');
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

        $this->onReload(['offset' => 0, 'first_page' => 1]);
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

                    fputcsv($handler, array_map(function ($column) { return $column->getLabel(); }, $this->datagrid->getColumns()));

                    foreach ($objects as $object)
                    {
                        fputcsv($handler, $this->linhaExportacao($object));
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
                $dompdf->setPaper('A4', 'landscape');
                $dompdf->render();

                file_put_contents($output, $dompdf->output());

                $window = TWindow::create('PDF', 0.8, 0.8);
                $object = new TElement('object');
                $object->data  = $output;
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

                        foreach ($this->linhaExportacao($object) as $value)
                        {
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

    /**
     * Valores de uma linha para CSV/XLS, na ordem das colunas e ja em texto.
     */
    private function linhaExportacao($object): array
    {
        return [
            $object->id,
            $object->nome,
            BeneficiarioCredito::regras()[$object->regra] ?? $object->regra,
            ($object->ativo === 'S') ? 'Sim' : 'Não',
        ];
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

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        $texto = function ($valor) {
            return isset($valor) && is_scalar($valor) && $valor !== '';
        };

        foreach (['nome', 'nome_col'] as $campo)
        {
            if ($texto($data->$campo ?? null))
            {
                $busca = str_replace(' ', '%', TratamentosService::removerAcentos($data->$campo));
                $filters[] = new TFilter('unaccent(nome)', 'ilike', "%{$busca}%");
            }
        }

        foreach (['regra', 'regra_col'] as $campo)
        {
            if ($texto($data->$campo ?? null))
            {
                $filters[] = new TFilter('regra', '=', $data->$campo);
            }
        }

        if ($texto($data->global_filter ?? null))
        {
            $busca = str_replace(' ', '%', TratamentosService::removerAcentos($data->global_filter));
            $filters[] = new TFilter('unaccent(nome)', 'ilike', "%{$busca}%");
        }

        $this->button_filtros->style = 'position: relative';
        $countFiltros = count($filters);

        if ($countFiltros)
        {
            $this->button_filtros->setLabel('Filtros'. "<span class='badge badge-success' style='position: absolute'>{$countFiltros}<span>");
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
        if(!empty($data->global_filter)){
            TScript::create('$("[name=global_filter]").focus().each(function() {
                if (this.setSelectionRange) {
                    this.setSelectionRange(this.value.length, this.value.length);
                }
            });');
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

            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'id';
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
}
