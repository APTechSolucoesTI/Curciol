<?php

class EscritoriosList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Escritorio';
    private static $primaryKey = 'id';
    private static $formName = 'formList_Escritorio';
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
        $this->form->setFormTitle("Listagem de escritórios");
        $this->limit = 20;

        $criteria_cidade_nome_col = new TCriteria();

        $nome = new TEntry('nome');
        $cnpj = new TEntry('cnpj');
        $email = new TEntry('email');
        $nome_col = new TEntry('nome_col');
        $cidade_nome_col = new TDBCombo('cidade_nome_col', 'escritorio', 'Cidade', 'id', '{nome}','nome asc' , $criteria_cidade_nome_col );
        $cnpj_col = new TEntry('cnpj_col');
        $telefone_col = new TEntry('telefone_col');
        $email_col = new TEntry('email_col');

        $nome_col->exitOnEnter();
        $cnpj_col->exitOnEnter();
        $telefone_col->exitOnEnter();
        $email_col->exitOnEnter();

        $nome_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $cnpj_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $telefone_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $email_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $cidade_nome_col->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $nome->forceUpperCase();
        $cnpj->setMask('99.999.999/9999-99');
        $email->forceLowerCase();
        $cidade_nome_col->enableSearch();
        $nome->setSize('100%');
        $cnpj->setSize('100%');
        $email->setSize('100%');
        $nome_col->setSize('100%');
        $cnpj_col->setSize('100%');
        $email_col->setSize('100%');
        $telefone_col->setSize('100%');
        $cidade_nome_col->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Nome:", null, '14px', null, '100%'),$nome]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([new TLabel("CNPJ:", null, '14px', null, '100%'),$cnpj],[new TLabel("E-mail:", null, '14px', null, '100%'),$email]);
        $row2->layout = ['col-sm-6','col-sm-6'];

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

        $column_nome = new TDataGridColumn('nome', "Nome", 'left');
        $column_cidade_nome = new TDataGridColumn('cidade->nome', "Cidade", 'left');
        $column_cnpj_transformed = new TDataGridColumn('cnpj', "CNPJ", 'left');
        $column_telefone_transformed = new TDataGridColumn('telefone', "Telefone", 'left');
        $column_email = new TDataGridColumn('email', "E-mail", 'left');

        $column_cnpj_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(strlen($value)==11){
                return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $value);
            } 

            return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $value);

        });

        $column_telefone_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if($value!=NULL && $value!="" && isset($value) && !empty($value)){
                $number="(".substr($value,0,2).") ".substr($value,2,-4)."-".substr($value,-4);
                // primeiro substr pega apenas o DDD e coloca dentro do (), segundo subtr pega os números do 3º até faltar 4, insere o hifem, e o ultimo pega apenas o 4 ultimos digitos

                return $number;
            }
        });        

        $order_nome = new TAction(array($this, 'onReload'));
        $order_nome->setParameter('order', 'nome');
        $column_nome->setAction($order_nome);
        $order_cnpj_transformed = new TAction(array($this, 'onReload'));
        $order_cnpj_transformed->setParameter('order', 'cnpj');
        $column_cnpj_transformed->setAction($order_cnpj_transformed);
        $order_telefone_transformed = new TAction(array($this, 'onReload'));
        $order_telefone_transformed->setParameter('order', 'telefone');
        $column_telefone_transformed->setAction($order_telefone_transformed);
        $order_email = new TAction(array($this, 'onReload'));
        $order_email->setParameter('order', 'email');
        $column_email->setAction($order_email);

        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_cidade_nome);
        $this->datagrid->addColumn($column_cnpj_transformed);
        $this->datagrid->addColumn($column_telefone_transformed);
        $this->datagrid->addColumn($column_email);

        $action_onEdit = new TDataGridAction(array('EscritorioForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Editar");
        $action_onEdit->setImage('far:edit #478fca');
        $action_onEdit->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onEdit);

        $action_onDelete = new TDataGridAction(array('EscritoriosList', 'onDelete'));
        $action_onDelete->setUseButton(false);
        $action_onDelete->setButtonClass('btn btn-default btn-sm');
        $action_onDelete->setLabel("Excluir");
        $action_onDelete->setImage('fas:trash-alt #dd5a43');
        $action_onDelete->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onDelete);

        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);

        if(!$action_onEdit->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        if(!$action_onDelete->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        $td_nome_col = TElement::tag('td', $nome_col);
        $tr->add($td_nome_col);
        $td_cidade_nome_col = TElement::tag('td', $cidade_nome_col);
        $tr->add($td_cidade_nome_col);
        $td_cnpj_col = TElement::tag('td', $cnpj_col);
        $tr->add($td_cnpj_col);
        $td_telefone_col = TElement::tag('td', $telefone_col);
        $tr->add($td_telefone_col);
        $td_email_col = TElement::tag('td', $email_col);
        $tr->add($td_email_col);

        $this->datagrid_form->addField($nome_col);
        $this->datagrid_form->addField($cidade_nome_col);
        $this->datagrid_form->addField($cnpj_col);
        $this->datagrid_form->addField($telefone_col);
        $this->datagrid_form->addField($email_col);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Listagem de escritórios");
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
        $button_cadastrar->setAction(new TAction(['EscritorioForm', 'onShow']), "Cadastrar");
        $button_cadastrar->addStyleClass('btn-default');
        $button_cadastrar->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($button_cadastrar);

        $button_filtros = new TButton('button_button_filtros');
        $button_filtros->setAction(new TAction(['EscritoriosList', 'onShowCurtainFilters']), "Filtros");
        $button_filtros->addStyleClass('btn-default');
        $button_filtros->setImage('fas:filter #000000');

        $this->datagrid_form->addField($button_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['EscritoriosList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['EscritoriosList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['EscritoriosList', 'onExportCsv'],['static' => 1]), 'datagrid_'.self::$formName, 'fas:table #00b894' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['EscritoriosList', 'onExportPdf'],['static' => 1]), 'datagrid_'.self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['EscritoriosList', 'onExportXls']), 'datagrid_'.self::$formName, 'fas:file-excel #4CAF50' );

        $head_left_actions->add($button_cadastrar);
        $head_left_actions->add($button_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($button_limpar_filtros);

        $head_right_actions->add($dropdown_button_exportar);

        $this->datagrid_form->add($this->datagrid);

        $this->button_filtros = $button_filtros;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Configurações","Escritórios"]));
        }

        $container->add($panel);

        parent::add($container);

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
                $object = new Escritorio($key, FALSE); 

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
                                $column_name = (strpos($column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
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
    public static function onShowCurtainFilters($param = null) 
    {
        try 
        {
            $object = new stdClass();
            $object->nome = null;
            $object->cnpj = null;
            $object->email = null;

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
            $page->setProperty('page-name', 'EscritoriosListSearch');
            $page->setProperty('page_name', 'EscritoriosListSearch');
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

        if (isset($data->nome) AND ( (is_scalar($data->nome) AND $data->nome !== '') OR (is_array($data->nome) AND (!empty($data->nome)) )) )
        {

            $filters[] = new TFilter('unaccent(nome)', 'ilike', "%{$data->nome}%");// create the filter 
        }

        if (isset($data->cnpj) AND ( (is_scalar($data->cnpj) AND $data->cnpj !== '') OR (is_array($data->cnpj) AND (!empty($data->cnpj)) )) )
        {

            $filters[] = new TFilter('cnpj', 'like', "%{$data->cnpj}%");// create the filter 
        }

        if (isset($data->email) AND ( (is_scalar($data->email) AND $data->email !== '') OR (is_array($data->email) AND (!empty($data->email)) )) )
        {

            $filters[] = new TFilter('email', 'like', "%{$data->email}%");// create the filter 
        }

        if (isset($data->nome_col) AND ( (is_scalar($data->nome_col) AND $data->nome_col !== '') OR (is_array($data->nome_col) AND (!empty($data->nome_col)) )) )
        {

            $filters[] = new TFilter('unaccent(nome)', 'ilike', "%{$data->nome_col}%");// create the filter 
        }

        if (isset($data->cidade_nome_col) AND ( (is_scalar($data->cidade_nome_col) AND $data->cidade_nome_col !== '') OR (is_array($data->cidade_nome_col) AND (!empty($data->cidade_nome_col)) )) )
        {

            $filters[] = new TFilter('cidade_id', '=', $data->cidade_nome_col);// create the filter 
        }

        if (isset($data->cnpj_col) AND ( (is_scalar($data->cnpj_col) AND $data->cnpj_col !== '') OR (is_array($data->cnpj_col) AND (!empty($data->cnpj_col)) )) )
        {

            $filters[] = new TFilter('cnpj', 'like', "%{$data->cnpj_col}%");// create the filter 
        }

        if (isset($data->telefone_col) AND ( (is_scalar($data->telefone_col) AND $data->telefone_col !== '') OR (is_array($data->telefone_col) AND (!empty($data->telefone_col)) )) )
        {

            $filters[] = new TFilter('telefone', 'like', "%{$data->telefone_col}%");// create the filter 
        }

        if (isset($data->email_col) AND ( (is_scalar($data->email_col) AND $data->email_col !== '') OR (is_array($data->email_col) AND (!empty($data->email_col)) )) )
        {

            $filters[] = new TFilter('email', 'like', "%{$data->email_col}%");// create the filter 
        }

        $this->button_filtros->style = 'position: relative';
        $countFiltros = count($filters);

        if ($countFiltros)
        {
            $this->button_filtros->setLabel('Filtros'. "<span class='badge badge-success' style='position: absolute'>{$countFiltros}<span>");
        }
         if (isset($data->nome_col) AND ( (is_scalar($data->nome_col) AND $data->nome_col !== '') OR (is_array($data->nome_col) AND (!empty($data->nome_col)) )) ){
            $nome_col = $data->nome_col;
            $data->nome_col = str_replace(' ','%',TratamentosService::removerAcentos($data->nome_col));
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

            // creates a repository for Escritorio
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'nome';    
            }

            if (empty($param['direction']))
            {
                $param['direction'] = 'asc';
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

    public static function manageRow($id, $param = [])
    {
        $list = new self($param);

        $openTransaction = TTransaction::getDatabase() != self::$database ? true : false;

        if($openTransaction)
        {
            TTransaction::open(self::$database);    
        }

        $object = new Escritorio($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

