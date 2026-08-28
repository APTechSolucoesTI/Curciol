<?php

class ContratoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Contrato';
    private static $primaryKey = 'id';
    private static $formName = 'form_ContratoList';
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
        $this->form->setFormTitle("Listagem de contratos");
        $this->limit = 20;

        $criteria_contrato_pessoa_pessoa_id = new TCriteria();
        $criteria_contrato_profissional_profissional_id = new TCriteria();
        $criteria_area_id = new TCriteria();
        $criteria_contrato_status_nome = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_contrato_pessoa_pessoa_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $numero = new TEntry('numero');
        $contrato_pessoa_pessoa_id = new TDBUniqueSearch('contrato_pessoa_pessoa_id', 'escritorio', 'Pessoa', 'nome', 'nome','id desc' , $criteria_contrato_pessoa_pessoa_id );
        $contrato_profissional_profissional_id = new TDBCombo('contrato_profissional_profissional_id', 'escritorio', 'Pessoa', 'nome', '{nome}','nome asc' , $criteria_contrato_profissional_profissional_id );
        $area_id = new TDBCombo('area_id', 'escritorio', 'Area', 'id', '{nome}','nome asc' , $criteria_area_id );
        $assunto_id = new TCombo('assunto_id');
        $objeto = new TText('objeto');
        $numero_col = new TEntry('numero_col');
        $cliente_col = new TEntry('cliente_col');
        $parceiro_col = new TEntry('parceiro_col');
        $contrato_status_nome = new TDBCombo('contrato_status_nome', 'escritorio', 'ContratoStatus', 'id', '{nome}','nome asc' , $criteria_contrato_status_nome );

        $area_id->setChangeAction(new TAction([$this,'onChangearea_id']));

        $numero_col->exitOnEnter();
        $cliente_col->exitOnEnter();
        $parceiro_col->exitOnEnter();

        $numero_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $cliente_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $parceiro_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $contrato_status_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $numero->setMaxLength(30);
        $contrato_pessoa_pessoa_id->setMinLength(3);
        $contrato_pessoa_pessoa_id->setMask('{nome}');
        $parceiro_col->enableToggleVisibility(false);
        $cliente_col->forceUpperCase();
        $parceiro_col->forceUpperCase();

        $area_id->enableSearch();
        $assunto_id->enableSearch();
        $contrato_status_nome->enableSearch();
        $contrato_profissional_profissional_id->enableSearch();

        $numero->setSize('100%');
        $area_id->setSize('100%');
        $assunto_id->setSize('100%');
        $objeto->setSize('100%', 70);
        $numero_col->setSize('100%');
        $cliente_col->setSize('100%');
        $parceiro_col->setSize('100%');
        $contrato_status_nome->setSize('100%');
        $contrato_pessoa_pessoa_id->setSize('100%');
        $contrato_profissional_profissional_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Numero:", null, '14px', null, '100%'),$numero]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([new TLabel("Cliente:", null, '14px', null, '100%'),$contrato_pessoa_pessoa_id],[new TLabel("Parceiro:", null, '14px', null, '100%'),$contrato_profissional_profissional_id]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Área:", null, '14px', null, '100%'),$area_id],[new TLabel("Assunto:", null, '14px', null, '100%'),$assunto_id]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        $row4 = $this->form->addFields([new TLabel("Objeto:", null, '14px', null, '100%'),$objeto]);
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

        $column_numero = new TDataGridColumn('numero', "Numero", 'left');
        $column_contrato_pessoa_cliente_to_string = new TDataGridColumn('contrato_pessoa_cliente_to_string', "Cliente", 'left');
        $column_contrato_repasse_pessoa_to_string = new TDataGridColumn('contrato_repasse_pessoa_to_string', "Parceiro", 'left');
        $column_contrato_status_nome_transformed = new TDataGridColumn('contrato_status->nome', "Status", 'left');

        $column_contrato_status_nome_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return "<span class='label' style='width:100%;max-width:200px;background-color:{$object->contrato_status->cor}'> {$value} </span>"; 

        });        

        $order_numero = new TAction(array($this, 'onReload'));
        $order_numero->setParameter('order', 'numero');
        $column_numero->setAction($order_numero);

        $column_contrato_repasse_pessoa_to_string->disableHtmlConversion();

        $this->datagrid->addColumn($column_numero);
        $this->datagrid->addColumn($column_contrato_pessoa_cliente_to_string);
        $this->datagrid->addColumn($column_contrato_repasse_pessoa_to_string);
        $this->datagrid->addColumn($column_contrato_status_nome_transformed);

        $action_onShow = new TDataGridAction(array('ContratoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar Contrato");
        $action_onShow->setImage('fas:search-plus #9C27B0');
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
        $td_numero_col = TElement::tag('td', $numero_col);
        $tr->add($td_numero_col);
        $td_cliente_col = TElement::tag('td', $cliente_col);
        $tr->add($td_cliente_col);
        $td_parceiro_col = TElement::tag('td', $parceiro_col);
        $tr->add($td_parceiro_col);
        $td_contrato_status_nome = TElement::tag('td', $contrato_status_nome);
        $tr->add($td_contrato_status_nome);
        $tr->add(TElement::tag('td', ''));

        $this->datagrid_form->addField($numero_col);
        $this->datagrid_form->addField($cliente_col);
        $this->datagrid_form->addField($parceiro_col);
        $this->datagrid_form->addField($contrato_status_nome);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Listagem de contratos");
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

        $botaoCadastrar = new TButton('button_botaoCadastrar');
        $botaoCadastrar->setAction(new TAction(['ContratoForm', 'onShow']), "Cadastrar");
        $botaoCadastrar->addStyleClass('btn-default');
        $botaoCadastrar->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($botaoCadastrar);

        $btnShowCurtainFilters = new TButton('button_btnShowCurtainFilters');
        $btnShowCurtainFilters->setAction(new TAction(['ContratoList', 'onShowCurtainFilters']), "Filtros");
        $btnShowCurtainFilters->addStyleClass('btn-default');
        $btnShowCurtainFilters->setImage('fas:filter #000000');

        $this->datagrid_form->addField($btnShowCurtainFilters);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['ContratoList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['ContratoList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $button_gerar_contrato = new TButton('button_button_gerar_contrato');
        $button_gerar_contrato->setAction(new TAction(['GerarContratoForm', 'onShow']), "Gerar Contrato");
        $button_gerar_contrato->addStyleClass('btn-default');
        $button_gerar_contrato->setImage('fas:file-alt #000000');

        $this->datagrid_form->addField($button_gerar_contrato);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['ContratoList', 'onExportCsv'],['static' => 1]), 'datagrid_'.self::$formName, 'fas:file-csv #00b894' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['ContratoList', 'onExportXls'],['static' => 1]), 'datagrid_'.self::$formName, 'fas:file-excel #4CAF50' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['ContratoList', 'onExportPdf'],['static' => 1]), 'datagrid_'.self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XML", new TAction(['ContratoList', 'onExportXml'],['static' => 1]), 'datagrid_'.self::$formName, 'far:file-code #95a5a6' );

        $head_left_actions->add($botaoCadastrar);
        $head_left_actions->add($btnShowCurtainFilters);
        $head_left_actions->add($button_limpar_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($dropdown_button_exportar);
        $head_left_actions->add($button_gerar_contrato);

        $this->datagrid_form->add($this->datagrid);

        $this->btnShowCurtainFilters = $btnShowCurtainFilters;

        $usuariosPermitidosCadastrar = [1, 3, 4, 5, 17];
        $usuarioAtual = (int) TSession::getValue('userid');

        if (!in_array($usuarioAtual, $usuariosPermitidosCadastrar)) {
            $botaoCadastrar->style = 'display: none !important;';
        }

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Contratos","Contratos"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    public static function onChangearea_id($param)
    {
        try
        {

            if (isset($param['area_id']) && $param['area_id'])
            { 
                $criteria = TCriteria::create(['area_id' => $param['area_id']]);
                TDBCombo::reloadFromModel(self::$formName, 'assunto_id', 'escritorio', 'Assunto', 'id', '{nome}', 'nome asc', $criteria, TRUE); 
            } 
            else 
            { 
                TCombo::clearField(self::$formName, 'assunto_id'); 
            }  

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
            $object = new stdClass();
            $object->numero = null;
            $object->objeto = null;
            $object->contrato_pessoa_pessoa_id = null;
            $object->contrato_profissional_profissional_id = null;

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
            $page->setProperty('page-name', 'ContratoListSearch');
            $page->setProperty('page_name', 'ContratoListSearch');
            $page->adianti_target_container = 'adianti_right_panel';
            $page->target_container = 'adianti_right_panel';
            $page->add($filter->form);
            $page->setIsWrapped(true);
            $page->show();

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

    public function fireEvents( $object )
    {
        $obj = new stdClass;
        if(is_object($object) && get_class($object) == 'stdClass')
        {
            if(isset($object->area_id))
            {
                $value = $object->area_id;

                $obj->area_id = $value;
            }
            if(isset($object->assunto_id))
            {
                $value = $object->assunto_id;

                $obj->assunto_id = $value;
            }
        }
        elseif(is_object($object))
        {
            if(isset($object->area_id))
            {
                $value = $object->area_id;

                $obj->area_id = $value;
            }
            if(isset($object->assunto_id))
            {
                $value = $object->assunto_id;

                $obj->assunto_id = $value;
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

        //REMOVER ACENTUAÇÃO 

        if (isset($data->parceiro_col) AND !empty($data->parceiro_col)){

            $valor = $data->parceiro_col;

            $filters[] = new TFilter(
                'id',
                'IN',
                "(SELECT contrato_id 
                FROM contrato_repasse 
                WHERE pessoa_id IN (
                        SELECT id 
                        FROM pessoa 
                        WHERE unaccent(nome) ILIKE '%{$valor}%'
                ))"
            );

        }

        if (isset($data->cliente_col) AND !empty($data->cliente_col)){
            $valor = $data->cliente_col;

            $filters[] = new TFilter(
                'id',
                'IN',
                "(SELECT contrato_id 
                FROM contrato_pessoa 
                WHERE cliente_id IN (
                        SELECT id 
                        FROM pessoa 
                        WHERE unaccent(nome) ILIKE '%{$valor}%'
                ))"
            );
        }

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->numero) AND ( (is_scalar($data->numero) AND $data->numero !== '') OR (is_array($data->numero) AND (!empty($data->numero)) )) )
        {

            $filters[] = new TFilter('numero', 'like', "%{$data->numero}%");// create the filter 
        }

        if (isset($data->contrato_pessoa_pessoa_id) AND ( (is_scalar($data->contrato_pessoa_pessoa_id) AND $data->contrato_pessoa_pessoa_id !== '') OR (is_array($data->contrato_pessoa_pessoa_id) AND (!empty($data->contrato_pessoa_pessoa_id)) )) )
        {

            $filters[] = new TFilter('id', 'in', "(SELECT contrato_id FROM contrato_pessoa WHERE cliente_id in  (SELECT id FROM pessoa WHERE nome like '%{$data->contrato_pessoa_pessoa_id}%') )");// create the filter 
        }

        if (isset($data->contrato_profissional_profissional_id) AND ( (is_scalar($data->contrato_profissional_profissional_id) AND $data->contrato_profissional_profissional_id !== '') OR (is_array($data->contrato_profissional_profissional_id) AND (!empty($data->contrato_profissional_profissional_id)) )) )
        {

            $filters[] = new TFilter('id', 'in', "(SELECT contrato_id FROM contrato_repasse WHERE pessoa_id in  (SELECT id FROM pessoa WHERE nome like '%{$data->contrato_profissional_profissional_id}%') )");// create the filter 
        }

        if (isset($data->area_id) AND ( (is_scalar($data->area_id) AND $data->area_id !== '') OR (is_array($data->area_id) AND (!empty($data->area_id)) )) )
        {

            $filters[] = new TFilter('area_id', '=', $data->area_id);// create the filter 
        }

        if (isset($data->assunto_id) AND ( (is_scalar($data->assunto_id) AND $data->assunto_id !== '') OR (is_array($data->assunto_id) AND (!empty($data->assunto_id)) )) )
        {

            $filters[] = new TFilter('assunto_id', '=', $data->assunto_id);// create the filter 
        }

        if (isset($data->objeto) AND ( (is_scalar($data->objeto) AND $data->objeto !== '') OR (is_array($data->objeto) AND (!empty($data->objeto)) )) )
        {

            $filters[] = new TFilter('objeto', 'like', "%{$data->objeto}%");// create the filter 
        }

        if (isset($data->numero_col) AND ( (is_scalar($data->numero_col) AND $data->numero_col !== '') OR (is_array($data->numero_col) AND (!empty($data->numero_col)) )) )
        {

            $filters[] = new TFilter('numero', 'like', "%{$data->numero_col}%");// create the filter 
        }

        $this->fireEvents($data);

                if (isset($data->parceiro_col) AND ( 
                    (is_scalar($data->parceiro_col) AND $data->parceiro_col !== '') 
                    OR (is_array($data->parceiro_col) AND (!empty($data->parceiro_col))) 
                )) 
            {
               // $parceiro_col = $data->parceiro_col;  
               // $data->parceiro_col = $parceiro_col;
            }

            if (isset($data->cliente_col) AND ( 
                    (is_scalar($data->cliente_col) AND $data->cliente_col !== '') 
                    OR (is_array($data->cliente_col) AND (!empty($data->cliente_col))) 
                )) 
            {
              //  $cliente_col = $data->cliente_col;    
              //  $data->cliente_col = $cliente_col;
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

            // creates a repository for Contrato
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'data_criacao';    
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

    $permitidos = [1, 3, 4, 5, 17]; // IDs de SystemUsers que veem tudo
    $userid = (int) TSession::getValue('userid');

    // NUNCA limpar filtros aqui, senão apaga o que você setar abaixo
    // $this->onClearFilters($param);

    if (!in_array($userid, $permitidos, true)) {
        TTransaction::open(self::$database);

        // Pessoa vinculada ao usuário logado
        $pessoa = Pessoa::where('system_users_id', '=', $userid)->first();
        $pessoa_id = $pessoa ? (int) $pessoa->id : null;

        TTransaction::close();

        // Se achou a pessoa, filtra pelos contratos onde ela aparece no contrato_repasse
        if ($pessoa_id) {
            $this->filter_criteria->add(
                new TFilter(
                    'id',
                    'IN',
                    "(SELECT contrato_id
                       FROM contrato_repasse
                      WHERE pessoa_id = {$pessoa_id})"
                )
            );
        } else {
            // Sem pessoa vinculada? então não mostra nada (bloqueia)
            $this->filter_criteria->add(new TFilter('id', '=', -1));
        }
    }

    // Recarrega pela via oficial (usa filter_criteria + paginação/ordenção)
    $this->onReload($param);

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

        $object = new Contrato($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

