<?php

class CepCacheHeaderList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'CepCache';
    private static $primaryKey = 'id';
    private static $formName = 'formList_CepCache';
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

        $this->limit = 20;

        $cep = new TEntry('cep');
        $rua = new TEntry('rua');
        $cidade = new TEntry('cidade');
        $uf = new TEntry('uf');
        $bairro = new TEntry('bairro');

        $cep->exitOnEnter();
        $rua->exitOnEnter();
        $cidade->exitOnEnter();
        $uf->exitOnEnter();
        $bairro->exitOnEnter();

        $cep->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $rua->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $cidade->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $uf->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $bairro->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $uf->forceUpperCase();
        $uf->setSize('100%');
        $cep->setSize('100%');
        $rua->setSize('100%');
        $cidade->setSize('100%');
        $bairro->setSize('100%');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_cep = new TDataGridColumn('cep', "CEP", 'left' , '150px');
        $column_rua = new TDataGridColumn('rua', "Rua", 'left');
        $column_cidade = new TDataGridColumn('cidade', "Cidade", 'left');
        $column_uf = new TDataGridColumn('uf', "UF", 'left' , '50px');
        $column_bairro = new TDataGridColumn('bairro', "Bairro", 'left' , '150px');

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

        $this->datagrid->addColumn($column_cep);
        $this->datagrid->addColumn($column_rua);
        $this->datagrid->addColumn($column_cidade);
        $this->datagrid->addColumn($column_uf);
        $this->datagrid->addColumn($column_bairro);

        $action_onDelete = new TDataGridAction(array('CepCacheHeaderList', 'onDelete'));
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

        if(!$action_onDelete->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        $tr->add(TElement::tag('td', ''));
        $td_cep = TElement::tag('td', $cep);
        $tr->add($td_cep);
        $td_rua = TElement::tag('td', $rua);
        $tr->add($td_rua);
        $td_cidade = TElement::tag('td', $cidade);
        $tr->add($td_cidade);
        $td_uf = TElement::tag('td', $uf);
        $tr->add($td_uf);
        $td_bairro = TElement::tag('td', $bairro);
        $tr->add($td_bairro);

        $this->datagrid_form->addField($cep);
        $this->datagrid_form->addField($rua);
        $this->datagrid_form->addField($cidade);
        $this->datagrid_form->addField($uf);
        $this->datagrid_form->addField($bairro);

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

        $button_excluir_em_lote = new TButton('button_button_excluir_em_lote');
        $button_excluir_em_lote->setAction(new TAction(['CepCacheHeaderList', 'onDeleteBatchSession']), "Excluir em lote");
        $button_excluir_em_lote->addStyleClass('btn-default');
        $button_excluir_em_lote->setImage('far:trash-alt #dd5a43');

        $this->datagrid_form->addField($button_excluir_em_lote);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['CepCacheHeaderList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['CepCacheHeaderList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['CepCacheHeaderList', 'onExportCsv'],['static' => 1]), self::$formName, 'fas:table #00b894' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['CepCacheHeaderList', 'onExportPdf'],['static' => 1]), self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['CepCacheHeaderList', 'onExportXls']), self::$formName, 'fas:file-excel #4CAF50' );

        $head_left_actions->add($button_excluir_em_lote);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($button_limpar_filtros);

        $head_right_actions->add($dropdown_button_exportar);

        $this->datagrid_form->add($this->datagrid);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Configurações","CEPs"]));
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
                $object = new CepCache($key, FALSE); 

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
    public static function onDeleteBatchSession($param = null) 
    {
        if(isset($param['confirmAction']) && $param['confirmAction'] == 1)
        {
            try
            {
                $session_checks = TSession::getValue(__CLASS__.'builder_datagrid_check');
                if($session_checks)
                {
                    TTransaction::open(self::$database);
                    foreach($session_checks as $check_id)
                    {
                        $object = new CepCache($check_id);
                        $object->delete();
                    }
                    TTransaction::close();

                    TToast::show("success", "Registros removidos", "topRight", "fas fa-info-circle");                
                    TApplication::loadPage(__CLASS__, 'onShow', []);
                }
            }
            catch (Exception $e) // in case of exception
            {
                new TMessage('error', $e->getMessage()); // shows the exception error message
                TTransaction::rollback(); // undo all pending operations
            }
        }
        else
        {
            // define the delete action
            $action = new TAction(array(__CLASS__, 'onDeleteBatchSession'));
            $action->setParameters($param); // pass the key paramseter ahead
            $action->setParameter('confirmAction', 1);

            new TQuestion('Apagar CEPs da cache?', $action);   
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
        // get the search form data
        $data = $this->datagrid_form->getData();
        $filters = [];

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->cep) AND ( (is_scalar($data->cep) AND $data->cep !== '') OR (is_array($data->cep) AND (!empty($data->cep)) )) )
        {

            $filters[] = new TFilter('cep', 'like', "%{$data->cep}%");// create the filter 
        }

        if (isset($data->rua) AND ( (is_scalar($data->rua) AND $data->rua !== '') OR (is_array($data->rua) AND (!empty($data->rua)) )) )
        {

            $filters[] = new TFilter('rua', 'like', "%{$data->rua}%");// create the filter 
        }

        if (isset($data->cidade) AND ( (is_scalar($data->cidade) AND $data->cidade !== '') OR (is_array($data->cidade) AND (!empty($data->cidade)) )) )
        {

            $filters[] = new TFilter('cidade', 'like', "%{$data->cidade}%");// create the filter 
        }

        if (isset($data->uf) AND ( (is_scalar($data->uf) AND $data->uf !== '') OR (is_array($data->uf) AND (!empty($data->uf)) )) )
        {

            $filters[] = new TFilter('uf', 'like', "%{$data->uf}%");// create the filter 
        }

        if (isset($data->bairro) AND ( (is_scalar($data->bairro) AND $data->bairro !== '') OR (is_array($data->bairro) AND (!empty($data->bairro)) )) )
        {

            $filters[] = new TFilter('bairro', 'like', "%{$data->bairro}%");// create the filter 
        }

        // fill the form with data again
        $this->datagrid_form->setData($data);

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

            // creates a repository for CepCache
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
            $session_checks = TSession::getValue(__CLASS__.'builder_datagrid_check');

            // load the objects according to criteria
            $objects = $repository->load($criteria, FALSE);

            $this->datagrid->clear();
            if ($objects)
            {
                // iterate the collection of active records
                foreach ($objects as $object)
                {
                    $check = new TCheckGroup('builder_datagrid_check');
                    $check->addItems([$object->id => '']);
                    $check->getButtons()[$object->id]->onclick = 'event.stopPropagation()';

                    if(!$this->datagrid_form->getField('builder_datagrid_check[]'))
                    {
                        $this->datagrid_form->setFields([$check]);
                    }

                    $check->setChangeAction(new TAction([$this, 'builderSelectCheck']));
                    $object->builder_datagrid_check = $check;

                    if(!empty($session_checks[$object->id]))
                    {
                        $object->builder_datagrid_check->setValue([$object->id=>$object->id]);
                    }

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

    public static function builderSelectCheck($param)
    {
        $session_checks = TSession::getValue(__CLASS__.'builder_datagrid_check');

        $valueOn = null;
        if(!empty($param['_field_data_json']))
        {
            $obj = json_decode($param['_field_data_json']);
            if($obj)
            {
                $valueOn = $obj->valueOn;
            }
        }

        $key = empty($param['key']) ? $valueOn : $param['key'];

        if(empty($param['builder_datagrid_check']) && !empty($session_checks[$key]))
        {
            unset($session_checks[$key]);
        }
        elseif(!empty($param['builder_datagrid_check']) && !in_array($key, $param['builder_datagrid_check']) && !empty($session_checks[$key]))
        {
            unset($session_checks[$key]);
        }
        elseif(!empty($param['builder_datagrid_check']) && in_array($key, $param['builder_datagrid_check']))
        {
            $session_checks[$key] = $key;
        }

        TSession::setValue(__CLASS__.'builder_datagrid_check', $session_checks);
    }

    public static function manageRow($id, $param = [])
    {
        $list = new self($param);

        $openTransaction = TTransaction::getDatabase() != self::$database ? true : false;

        if($openTransaction)
        {
            TTransaction::open(self::$database);    
        }

        $object = new CepCache($id);

        $session_checks = TSession::getValue(__CLASS__.'builder_datagrid_check');

        $check = new TCheckGroup('builder_datagrid_check');
        $check->addItems([$object->id => '']);
        $check->getButtons()[$object->id]->onclick = 'event.stopPropagation()';

        if(!$list->datagrid_form->getField('builder_datagrid_check[]'))
        {
            $list->datagrid_form->setFields([$check]);
        }

        $check->setChangeAction(new TAction([$list, 'builderSelectCheck']));
        $object->builder_datagrid_check = $check;

        if(!empty($session_checks[$object->id]))
        {
            $object->builder_datagrid_check->setValue([$object->id=>$object->id]);
        }

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

