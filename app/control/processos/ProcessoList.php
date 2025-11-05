<?php

class ProcessoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Processo';
    private static $primaryKey = 'id';
    private static $formName = 'form_ProcessoList';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    use BuilderDatagridTrait;

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
        $this->form->setFormTitle("Listagem de processos");
        $this->limit = 20;

        $criteria_tipo_processo_id = new TCriteria();
        $criteria_area_id = new TCriteria();
        $criteria_assunto_id = new TCriteria();
        $criteria_responsavel_id = new TCriteria();
        $criteria_cliente_id = new TCriteria();
        $criteria_tipo_processo_nome_col = new TCriteria();
        $criteria_area_nome_col = new TCriteria();
        $criteria_assunto_nome = new TCriteria();
        $criteria_status_processual_nome = new TCriteria();

        $filterVar = Grupo::PROFISSIONAL;
        $criteria_responsavel_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $tipo_processo_id = new TDBCombo('tipo_processo_id', 'escritorio', 'TipoProcesso', 'id', '{nome}','nome asc' , $criteria_tipo_processo_id );
        $numero_cnj_numero = new TEntry('numero_cnj_numero');
        $area_id = new TDBCombo('area_id', 'escritorio', 'Area', 'id', '{nome}','nome asc' , $criteria_area_id );
        $assunto_id = new TDBCombo('assunto_id', 'escritorio', 'Assunto', 'id', '{nome}','nome asc' , $criteria_assunto_id );
        $status_processual_id = new TCombo('status_processual_id');
        $responsavel_id = new TDBMultiSearch('responsavel_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_responsavel_id );
        $cliente_id = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );
        $tipo_processo_nome_col = new TDBCombo('tipo_processo_nome_col', 'escritorio', 'TipoProcesso', 'id', '{nome}','nome asc' , $criteria_tipo_processo_nome_col );
        $numero_cnj_numero_col = new TEntry('numero_cnj_numero_col');
        $cliente_col = new TEntry('cliente_col');
        $area_nome_col = new TDBCombo('area_nome_col', 'escritorio', 'Area', 'id', '{nome}','nome asc' , $criteria_area_nome_col );
        $assunto_nome = new TDBCombo('assunto_nome', 'escritorio', 'Assunto', 'id', '{nome}','nome asc' , $criteria_assunto_nome );
        $responsavel_col = new TEntry('responsavel_col');
        $status_processual_nome = new TDBCombo('status_processual_nome', 'escritorio', 'StatusProcessual', 'id', '{nome}','nome asc' , $criteria_status_processual_nome );

        $tipo_processo_id->setChangeAction(new TAction([$this,'onSelectTipoProcesso']));

        $numero_cnj_numero_col->exitOnEnter();
        $cliente_col->exitOnEnter();
        $responsavel_col->exitOnEnter();

        $numero_cnj_numero_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $cliente_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $responsavel_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $tipo_processo_nome_col->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $area_nome_col->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $assunto_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $status_processual_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $tipo_processo_id->setValue(TipoProcesso::JUDICIAL);
        $numero_cnj_numero->setMaxLength(30);
        $cliente_id->setMinLength(3);
        $responsavel_id->setMinLength(3);

        $cliente_id->setMask('{nome}');
        $responsavel_id->setMask('{nome}');

        $area_id->enableSearch();
        $assunto_id->enableSearch();
        $assunto_nome->enableSearch();
        $area_nome_col->enableSearch();
        $tipo_processo_id->enableSearch();
        $status_processual_id->enableSearch();
        $tipo_processo_nome_col->enableSearch();
        $status_processual_nome->enableSearch();

        $area_id->setSize('100%');
        $assunto_id->setSize('100%');
        $cliente_id->setSize('100%');
        $cliente_col->setSize('100%');
        $assunto_nome->setSize('100%');
        $area_nome_col->setSize('100%');
        $responsavel_col->setSize('100%');
        $tipo_processo_id->setSize('100%');
        $numero_cnj_numero->setSize('100%');
        $responsavel_id->setSize('100%', 70);
        $status_processual_id->setSize('100%');
        $numero_cnj_numero_col->setSize('100%');
        $tipo_processo_nome_col->setSize('100%');
        $status_processual_nome->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Tipo de processo:", null, '14px', null, '100%'),$tipo_processo_id],[new TLabel("Número padrão CNJ:", null, '14px', null, '100%'),$numero_cnj_numero]);
        $row1->layout = ['col-sm-6','col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Área:", null, '14px', null, '100%'),$area_id],[new TLabel("Assunto:", null, '14px', null, '100%'),$assunto_id]);
        $row2->layout = ['col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Status processual:", null, '14px', null, '100%'),$status_processual_id],[new TLabel("Responsável:", null, '14px', null, '100%'),$responsavel_id]);
        $row3->layout = ['col-sm-6','col-sm-6'];

        $row4 = $this->form->addFields([new TLabel("Cliente:", null, '14px', null, '100%'),$cliente_id]);
        $row4->layout = [' col-sm-12'];

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );
        $this->fireEvents( TSession::getValue(__CLASS__.'_filter_data') );

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary'); 

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->enableUserProperties('fa fa-cog', 'btn btn-default', new TAction([$this, 'setDatagridProperties']));
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(250);

        $column_id_transformed = new TDataGridColumn('id', " ", 'left');
        $column_tipo_processo_nome = new TDataGridColumn('tipo_processo->nome', "Tipo de processo", 'left');
        $column_numero_cnj_numero = new TDataGridColumn('numero_cnj_numero', "Número", 'left');
        $column_id_transformed1 = new TDataGridColumn('id', "Clientes", 'left');
        $column_area_nome = new TDataGridColumn('area->nome', "Área", 'left');
        $column_assunto_nome = new TDataGridColumn('assunto->nome', "Assunto", 'left');
        $column_id_transformed2 = new TDataGridColumn('id', "Responsável", 'left');
        $column_status_processual_nome = new TDataGridColumn('status_processual->nome', "Status processual", 'left');
        $column_id_transformed3 = new TDataGridColumn('id', " ", 'left');

        $column_id_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            TTransaction::open('escritorio');

            //Se tiver cadastrado como incidente, significa que ele herda de alguém, não é principal
            if( (ProcessoVinculo::where('processo_incidente_id','=',$value)->count()) == 0){
                return "<div style='position: relative;text-align: center;'><span class='estado_agendamento' style='background-color: #4CAF50'></span></div>";
            }else{
                return "<div style='position: relative;text-align: center;'><span class='estado_agendamento' style='background-color: #5B5B5B'></span></div>";
            }

            TTransaction::close();

        });

        $column_id_transformed1->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            TTransaction::open('escritorio');

            $pessoas = array();
            $contratos = ContratoProcesso::where('processo_id','=',$value)->load();
            if($contratos){
                foreach ($contratos as $contrato) {
                    $contratoPessoas = ContratoPessoa::where('contrato_id','=',$contrato->contrato_id)->orderby('percentual')->load();
                    foreach($contratoPessoas as $contratoPessoa){
                        $pessoas[$contratoPessoa->cliente_id] = (Pessoa::find($contratoPessoa->cliente_id))->nome;
                    }
                }
            }

            TTransaction::close();

            return implode(", ", $pessoas);
            });

        $column_id_transformed2->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
           TTransaction::open('escritorio');

            $nome = '';
            if (!empty($object->responsavel_id)) {
                $pessoa = Pessoa::find($object->responsavel_id);
                if ($pessoa && $pessoa->tipo_profissional_id == 1) {
                    $nome = $pessoa->nome;
                }
            }

            TTransaction::close();
            return $nome;

        });

        $column_id_transformed3->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            TTransaction::open('escritorio');

            //Se ele estiver cadastrado como processo principal, significa que tem processos que herdaram dele
            if( (ProcessoVinculo::where('processo_principal_id','=',$value)->count()) > 0){
                return "<div style='position: relative;text-align: center;'><span class='estado_agendamento' style='background-color: #4CAF50'></span></div>";
            }else{
                return "<div style='position: relative;text-align: center;'><span class='estado_agendamento' style='background-color: #5B5B5B'></span></div>";
            }

            TTransaction::close();

        });        

        $order_tipo_processo_nome = new TAction(array($this, 'onReload'));
        $order_tipo_processo_nome->setParameter('order', 'sort_tipo_processo_nome');
        $column_tipo_processo_nome->setAction($order_tipo_processo_nome);
        $order_numero_cnj_numero = new TAction(array($this, 'onReload'));
        $order_numero_cnj_numero->setParameter('order', 'numero_cnj_numero');
        $column_numero_cnj_numero->setAction($order_numero_cnj_numero);
        $order_id_transformed = new TAction(array($this, 'onReload'));
        $order_id_transformed->setParameter('order', 'id');
        $column_id_transformed->setAction($order_id_transformed);
        $order_assunto_nome = new TAction(array($this, 'onReload'));
        $order_assunto_nome->setParameter('order', 'sort_assunto_nome');
        $column_assunto_nome->setAction($order_assunto_nome);
        $order_id_transformed = new TAction(array($this, 'onReload'));
        $order_id_transformed->setParameter('order', 'id');
        $column_id_transformed->setAction($order_id_transformed);

        $this->datagrid->addColumn($column_id_transformed);
        $this->datagrid->addColumn($column_tipo_processo_nome);
        $this->datagrid->addColumn($column_numero_cnj_numero);
        $this->datagrid->addColumn($column_id_transformed1);
        $this->datagrid->addColumn($column_area_nome);
        $this->datagrid->addColumn($column_assunto_nome);
        $this->datagrid->addColumn($column_id_transformed2);
        $this->datagrid->addColumn($column_status_processual_nome);
        $this->datagrid->addColumn($column_id_transformed3);

        $action_onShow = new TDataGridAction(array('ProcessoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField(self::$primaryKey);

        $action_onShow->setParameter('key', '{id}');

        $this->datagrid->addAction($action_onShow);

        $this->applyDatagridProperties();
        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);

        if(!$action_onShow->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_tipo_processo_nome_col = TElement::tag('td', $tipo_processo_nome_col);
        $tr->add($td_tipo_processo_nome_col);
        $td_numero_cnj_numero_col = TElement::tag('td', $numero_cnj_numero_col);
        $tr->add($td_numero_cnj_numero_col);
        $td_cliente_col = TElement::tag('td', $cliente_col);
        $tr->add($td_cliente_col);
        $td_area_nome_col = TElement::tag('td', $area_nome_col);
        $tr->add($td_area_nome_col);
        $td_assunto_nome = TElement::tag('td', $assunto_nome);
        $tr->add($td_assunto_nome);
        $td_responsavel_col = TElement::tag('td', $responsavel_col);
        $tr->add($td_responsavel_col);
        $td_status_processual_nome = TElement::tag('td', $status_processual_nome);
        $tr->add($td_status_processual_nome);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $tr->add(TElement::tag('td', ''));

        $this->datagrid_form->addField($tipo_processo_nome_col);
        $this->datagrid_form->addField($numero_cnj_numero_col);
        $this->datagrid_form->addField($cliente_col);
        $this->datagrid_form->addField($area_nome_col);
        $this->datagrid_form->addField($assunto_nome);
        $this->datagrid_form->addField($responsavel_col);
        $this->datagrid_form->addField($status_processual_nome);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Listagem de processos");
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

        $button_cadastrar = new TButton('button_button_cadastrar');
        $button_cadastrar->setAction(new TAction(['ProcessoForm', 'onShow']), "Cadastrar");
        $button_cadastrar->addStyleClass('btn-default');
        $button_cadastrar->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($button_cadastrar);

        $btnShowCurtainFilters = new TButton('button_btnShowCurtainFilters');
        $btnShowCurtainFilters->setAction(new TAction(['ProcessoList', 'onShowCurtainFilters']), "Filtros");
        $btnShowCurtainFilters->addStyleClass('btn-default');
        $btnShowCurtainFilters->setImage('fas:filter #000000');

        $this->datagrid_form->addField($btnShowCurtainFilters);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['ProcessoList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['ProcessoList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['ProcessoList', 'onExportCsv'],['static' => 1]), 'datagrid_'.self::$formName, 'fas:file-csv #00b894' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['ProcessoList', 'onExportXls'],['static' => 1]), 'datagrid_'.self::$formName, 'fas:file-excel #4CAF50' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['ProcessoList', 'onExportPdf'],['static' => 1]), 'datagrid_'.self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XML", new TAction(['ProcessoList', 'onExportXml'],['static' => 1]), 'datagrid_'.self::$formName, 'far:file-code #95a5a6' );

        $head_left_actions->add($button_cadastrar);
        $head_left_actions->add($btnShowCurtainFilters);
        $head_left_actions->add($button_limpar_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($dropdown_button_exportar);

        $this->datagrid_form->add($this->datagrid);

        $this->btnShowCurtainFilters = $btnShowCurtainFilters;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Processos","Processos"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    public static function onSelectTipoProcesso($param = null) 
    {
        try 
        {

            if (isset($param['tipo_processo_id']) && $param['tipo_processo_id'])
            { 
                $criteria = TCriteria::create(['tipo_processo_id' => $param['tipo_processo_id']]);
                TDBCombo::reloadFromModel(self::$formName, 'status_processual_id', 'escritorio', 'StatusProcessual', 'id', '{nome}', 'nome asc', $criteria, TRUE); 
            } 
            else 
            { 
                TCombo::clearField(self::$formName, 'status_processual_id'); 
            }  

            $param['formulario'] = self::$formName;
            ProcessoForm::onSelectTipoProcesso($param);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
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
            $page->setProperty('page-name', 'ProcessoListSearch');
            $page->setProperty('page_name', 'ProcessoListSearch');
            $page->adianti_target_container = 'adianti_right_panel';
            $page->target_container = 'adianti_right_panel';
            $page->add($filter->form);
            $page->setIsWrapped(true);
            $page->show();

            $style = new TStyle('right-panel > .container-part[page-name=ProcessoListSearch]');
            $style->width = '60% !important';
            $style->show(true);

            $param['formulario'] = self::$formName;
            $param['tipo_processo_id'] = TipoProcesso::JUDICIAL;
            ProcessoForm::onSelectTipoProcesso($param);

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
    public function onRefresh($param = null) 
    {
        $this->onReload([]);
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
                                $value = strip_tags(call_user_func($transformer, $value, $object, null));
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
        try {
        $output = 'app/output/'.uniqid().'.pdf';
        if ((!file_exists($output) && is_writable(dirname($output))) || is_writable($output))
        {
            $this->limit = 0;

            // ===== Banco: abre/fecha UMA vez =====
            $contents = '';
            try {
                // use o mesmo conn da listagem
                TTransaction::open(self::$database);

                $this->datagrid->prepareForPrinting();

                // IMPORTANTE: onReload NÃO deve abrir/fechar transação se já houver uma aberta
                $this->onReload($param);

                $html = clone $this->datagrid;
                $contents = file_get_contents('app/resources/styles-print.html') . $html->getContents();
            } finally {
                if (TTransaction::get()) {
                    TTransaction::close();
                }
            }
            // ===== Daqui pra baixo: NENHUM acesso ao banco =====

            $dompdf = new \Dompdf\Dompdf;
            $dompdf->loadHtml($contents);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            file_put_contents($output, $dompdf->output());

            $window = TWindow::create('PDF', 0.8, 0.8);
            $object = new TElement('iframe');
            $object->src   = $output;
            $object->type  = 'application/pdf';
            $object->style = "width: 100%; height:calc(100% - 10px)";
            $window->add($object);
            $window->show();
        } else {
            throw new Exception(_t('Permission denied') . ': ' . $output);
        }
    } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
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

    public function fireEvents( $object )
    {
        $obj = new stdClass;
        if(is_object($object) && get_class($object) == 'stdClass')
        {
            if(isset($object->tipo_processo_id))
            {
                $value = $object->tipo_processo_id;

                $obj->tipo_processo_id = $value;
            }
            if(isset($object->status_processual_id))
            {
                $value = $object->status_processual_id;

                $obj->status_processual_id = $value;
            }
        }
        elseif(is_object($object))
        {
            if(isset($object->tipo_processo_id))
            {
                $value = $object->tipo_processo_id;

                $obj->tipo_processo_id = $value;
            }
            if(isset($object->status_processual_id))
            {
                $value = $object->status_processual_id;

                $obj->status_processual_id = $value;
            }
        }
        TForm::sendData(self::$formName, $obj);
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

        if (isset($data->tipo_processo_id) AND ( (is_scalar($data->tipo_processo_id) AND $data->tipo_processo_id !== '') OR (is_array($data->tipo_processo_id) AND (!empty($data->tipo_processo_id)) )) )
        {

            $filters[] = new TFilter('tipo_processo_id', '=', $data->tipo_processo_id);// create the filter 
        }

        if (isset($data->numero_cnj_numero) AND ( (is_scalar($data->numero_cnj_numero) AND $data->numero_cnj_numero !== '') OR (is_array($data->numero_cnj_numero) AND (!empty($data->numero_cnj_numero)) )) )
        {

            $filters[] = new TFilter('numero_cnj_numero', 'like', "%{$data->numero_cnj_numero}%");// create the filter 
        }

        if (isset($data->area_id) AND ( (is_scalar($data->area_id) AND $data->area_id !== '') OR (is_array($data->area_id) AND (!empty($data->area_id)) )) )
        {

            $filters[] = new TFilter('area_id', '=', $data->area_id);// create the filter 
        }

        if (isset($data->assunto_id) AND ( (is_scalar($data->assunto_id) AND $data->assunto_id !== '') OR (is_array($data->assunto_id) AND (!empty($data->assunto_id)) )) )
        {

            $filters[] = new TFilter('assunto_id', '=', $data->assunto_id);// create the filter 
        }

        if (isset($data->status_processual_id) AND ( (is_scalar($data->status_processual_id) AND $data->status_processual_id !== '') OR (is_array($data->status_processual_id) AND (!empty($data->status_processual_id)) )) )
        {

            $filters[] = new TFilter('status_processual_id', '=', $data->status_processual_id);// create the filter 
        }

        if (isset($data->responsavel_id) AND ( (is_scalar($data->responsavel_id) AND $data->responsavel_id !== '') OR (is_array($data->responsavel_id) AND (!empty($data->responsavel_id)) )) )
        {

            $filters[] = new TFilter('responsavel_id', 'in', $data->responsavel_id);// create the filter 
        }

        if (isset($data->cliente_id) AND ( (is_scalar($data->cliente_id) AND $data->cliente_id !== '') OR (is_array($data->cliente_id) AND (!empty($data->cliente_id)) )) )
        {

            $filters[] = new TFilter('modificacao_user_id', 'in', "(SELECT id FROM system_users WHERE password != '{$data->cliente_id}')");// create the filter 
        }

        if (isset($data->tipo_processo_nome_col) AND ( (is_scalar($data->tipo_processo_nome_col) AND $data->tipo_processo_nome_col !== '') OR (is_array($data->tipo_processo_nome_col) AND (!empty($data->tipo_processo_nome_col)) )) )
        {

            $filters[] = new TFilter('tipo_processo_id', '=', $data->tipo_processo_nome_col);// create the filter 
        }

        if (isset($data->numero_cnj_numero_col) AND ( (is_scalar($data->numero_cnj_numero_col) AND $data->numero_cnj_numero_col !== '') OR (is_array($data->numero_cnj_numero_col) AND (!empty($data->numero_cnj_numero_col)) )) )
        {

            $filters[] = new TFilter('numero_cnj_numero', 'like', "%{$data->numero_cnj_numero_col}%");// create the filter 
        }

        if (isset($data->area_nome_col) AND ( (is_scalar($data->area_nome_col) AND $data->area_nome_col !== '') OR (is_array($data->area_nome_col) AND (!empty($data->area_nome_col)) )) )
        {

            $filters[] = new TFilter('area_id', '=', $data->area_nome_col);// create the filter 
        }

        if (isset($data->assunto_nome) AND ( (is_scalar($data->assunto_nome) AND $data->assunto_nome !== '') OR (is_array($data->assunto_nome) AND (!empty($data->assunto_nome)) )) )
        {

            $filters[] = new TFilter('assunto_id', '=', $data->assunto_nome);// create the filter 
        }

        if (isset($data->status_processual_nome) AND ( (is_scalar($data->status_processual_nome) AND $data->status_processual_nome !== '') OR (is_array($data->status_processual_nome) AND (!empty($data->status_processual_nome)) )) )
        {

            $filters[] = new TFilter('status_processual_id', '=', $data->status_processual_nome);// create the filter 
        }

        $this->fireEvents($data);

        if (isset($data->cliente_id) AND ( (is_scalar($data->cliente_id) AND $data->cliente_id !== '') OR (is_array($data->cliente_id) AND (!empty($data->cliente_id)) )) )
        {
            $filters[] = new TFilter('id', 'in', "(SELECT processo_id FROM contrato_processo WHERE contrato_id in (SELECT contrato_id FROM contrato_pessoa WHERE cliente_id = $data->cliente_id))");
        }

        if (isset($data->responsavel_col) && !empty($data->responsavel_col)) {
                    $valor = str_replace("'", "''", trim($data->responsavel_col));

                $filters[] = new TFilter(
                    'id',
                    'IN',
                    "(SELECT pr.id 
                    FROM processo pr, pessoa p 
                    WHERE pr.responsavel_id = p.id 
                        AND p.tipo_profissional_id = 1 
                        AND unaccent(p.nome) ILIKE unaccent('%{$valor}%'))"
                );

                    $this->onReload();
        }

        if (isset($data->cliente_col) && !empty($data->cliente_col)) {
                $valor = str_replace("'", "''", trim($data->cliente_col));

                $filters[] = new TFilter('id', 'in', 
                    "(SELECT processo_id FROM contrato_processo WHERE contrato_id in 
                        (SELECT contrato_id FROM contrato_pessoa WHERE cliente_id IN 
                        (SELECT id FROM pessoa WHERE unaccent(nome) ILIKE unaccent('%{$valor}%'))))");
        }

         $this->fireEvents($data);

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

            // creates a repository for Processo
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'numero_cnj_numero';    
            }

            if (empty($param['direction']))
            {
                $param['direction'] = 'asc';
            }
            if (!empty($param['order']) && $param['order'] == 'sort_tipo_processo_nome')
            {
                $subQueryOrder = 'sort_tipo_processo_nome';
                $param['order'] = '(SELECT tipo_processo.nome FROM tipo_processo WHERE tipo_processo.id = processo.tipo_processo_id)';    
            }
            if (!empty($param['order']) && $param['order'] == 'sort_assunto_nome')
            {
                $subQueryOrder = 'sort_assunto_nome';
                $param['order'] = '(SELECT assunto.nome FROM assunto WHERE assunto.id = processo.assunto_id)';    
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

            if (!empty($param['order']) && isset($subQueryOrder))
            {
                $param['order'] = $subQueryOrder;
            }

            if (!empty($param['order']) && isset($subQueryOrder))
            {
                $param['order'] = $subQueryOrder;
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

        $object = new Processo($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

