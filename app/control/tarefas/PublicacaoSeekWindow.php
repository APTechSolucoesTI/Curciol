<?php

class PublicacaoSeekWindow extends TWindow
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Publicacao';
    private static $primaryKey = 'id';
    private static $formName = 'form_PublicacaoSeekWindow';
    private $showMethods = ['onReload', 'onSearch'];
    private $limit = 20;

    use BuilderSeekWindowTrait;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.8, null);
        parent::setTitle("Vincular publicação a tarefa");
        parent::setProperty('class', 'window_modal');

        $param['_seek_window_id'] = $this->id;
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);

        $this->limit = 20;

        // define the form title
        $this->form->setFormTitle("Vincular publicação a tarefa");

        $criteria_jornal_id = new TCriteria();

        $jornal_id = new TDBCombo('jornal_id', 'escritorio', 'Jornal', 'id', '{nome}','nome asc' , $criteria_jornal_id );
        $numero_arquivo = new TEntry('numero_arquivo');
        $numero_publicacao = new TEntry('numero_publicacao');
        $titulo = new TEntry('titulo');
        $numero_unico_processo = new TEntry('numero_unico_processo');

        $jornal_id->enableSearch();
        $titulo->setSize('100%');
        $jornal_id->setSize('100%');
        $numero_arquivo->setSize('100%');
        $numero_publicacao->setSize('100%');
        $numero_unico_processo->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Jornal:", null, '14px', null, '100%'),$jornal_id],[new TLabel("Número do arquivo:", null, '14px', null, '100%'),$numero_arquivo],[new TLabel("Número da publicação:", null, '14px', null, '100%'),$numero_publicacao]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Título:", null, '14px', null, '100%'),$titulo],[new TLabel("Número único do processo:", null, '14px', null, '100%'),$numero_unico_processo]);
        $row2->layout = ['col-sm-6','col-sm-6'];

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary'); 

        $this->setSeekParameters($btn_onsearch->getAction(), $param);

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = $this->getSeekFiltersCriteria($param);

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_jornal_nome = new TDataGridColumn('jornal->nome', "Jornal", 'left');
        $column_numero_unico_processo = new TDataGridColumn('numero_unico_processo', "Número único do processo", 'left');
        $column_titulo_transformed = new TDataGridColumn('titulo', "Título", 'left');
        $column_data_disponibilizacao_transformed = new TDataGridColumn('data_disponibilizacao', "Data da disponibilização", 'left');
        $column_numero_arquivo = new TDataGridColumn('numero_arquivo', "Número do arquivo", 'left');
        $column_numero_publicacao = new TDataGridColumn('numero_publicacao', "Número da publicação", 'left');

        $column_titulo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            return str_replace(";","<br/>",$value);

        });

        $column_data_disponibilizacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $this->datagrid->addColumn($column_jornal_nome);
        $this->datagrid->addColumn($column_numero_unico_processo);
        $this->datagrid->addColumn($column_titulo_transformed);
        $this->datagrid->addColumn($column_data_disponibilizacao_transformed);
        $this->datagrid->addColumn($column_numero_arquivo);
        $this->datagrid->addColumn($column_numero_publicacao);

        $action_onSelect = new TDataGridAction(array('PublicacaoSeekWindow', 'onSelect'));
        $action_onSelect->setUseButton(true);
        $action_onSelect->setButtonClass('btn btn-default btn-sm');
        $action_onSelect->setLabel("Selecionar");
        $action_onSelect->setImage('far:hand-pointer #44bd32');
        $action_onSelect->setField(self::$primaryKey);
        $this->setSeekParameters($action_onSelect, $param);

        $this->datagrid->addAction($action_onSelect);

        // create the datagrid model
        $this->datagrid->createModel();

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $navigationAction = new TAction(array($this, 'onReload'));
        $this->setSeekParameters($navigationAction, $param);
        $this->pageNavigation->setAction($navigationAction);
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $this->datagrid_form->add($this->datagrid);
        $panel->add($this->datagrid_form);

        $panel->getBody()->class .= ' table-responsive';

        $panel->addFooter($this->pageNavigation);

        parent::add($this->form);
        parent::add($panel);

    }

    public static function onSelect($param = null) 
    { 
        try 
        {   
            $seekFields = self::getSeekFields($param);
            $formData = new stdClass();

            if(!empty($param['key']))
            {
                TTransaction::open(self::$database);

                $repository = new TRepository(self::$activeRecord);

                $criteria = self::getSeekFiltersCriteria($param);

                if(!empty($param['_seek_filter_column']))
                {
                    $criteria->add(new TFilter($param['_seek_filter_column'], '=', $param['key']));   
                }
                else
                {
                    $criteria->add(new TFilter(self::$primaryKey, '=', $param['key']));
                }

                $objects = $repository->load($criteria);

                if($objects)
                {
                    $object = $objects[];
                    if($seekFields)
                    {
                        foreach ($seekFields as $seek_field) 
                        {

                            $formData->{"{$seek_field['name']}"} = $object->render("{$seek_field['column']}");
                        }
                    }
                }
                elseif($seekFields)
                {
                    foreach ($seekFields as $seek_field) 
                    {
                        $formData->{"{$seek_field['name']}"} = '';
                    }   
                }
                TTransaction::close();
            }
            else
            {
                if($seekFields)
                {
                    foreach ($seekFields as $seek_field) 
                    {
                        $formData->{"{$seek_field['name']}"} = '';
                    }   
                }
            }

            TForm::sendData($param['_form_name'], $formData);

            if(!empty($param['_seek_window_id']))
            {
                TWindow::closeWindow($param['_seek_window_id']);
            }
            else
            {
                //TScript::create("Template.closeRightPanel();");
            }
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
        // get the search form data
        $data = $this->form->getData();
        $filters = [];

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->jornal_id) AND ( (is_scalar($data->jornal_id) AND $data->jornal_id !== '') OR (is_array($data->jornal_id) AND (!empty($data->jornal_id)) )) )
        {

            $filters[] = new TFilter('jornal_id', '=', $data->jornal_id);// create the filter 
        }

        if (isset($data->numero_arquivo) AND ( (is_scalar($data->numero_arquivo) AND $data->numero_arquivo !== '') OR (is_array($data->numero_arquivo) AND (!empty($data->numero_arquivo)) )) )
        {

            $filters[] = new TFilter('numero_arquivo', 'like', "%{$data->numero_arquivo}%");// create the filter 
        }

        if (isset($data->numero_publicacao) AND ( (is_scalar($data->numero_publicacao) AND $data->numero_publicacao !== '') OR (is_array($data->numero_publicacao) AND (!empty($data->numero_publicacao)) )) )
        {

            $filters[] = new TFilter('numero_publicacao', 'like', "%{$data->numero_publicacao}%");// create the filter 
        }

        if (isset($data->titulo) AND ( (is_scalar($data->titulo) AND $data->titulo !== '') OR (is_array($data->titulo) AND (!empty($data->titulo)) )) )
        {

            $filters[] = new TFilter('titulo', 'like', "%{$data->titulo}%");// create the filter 
        }

        if (isset($data->numero_unico_processo) AND ( (is_scalar($data->numero_unico_processo) AND $data->numero_unico_processo !== '') OR (is_array($data->numero_unico_processo) AND (!empty($data->numero_unico_processo)) )) )
        {

            $filters[] = new TFilter('numero_unico_processo', 'like', "%{$data->numero_unico_processo}%");// create the filter 
        }

        // fill the form with data again
        $this->form->setData($data);

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        if (isset($param['static']) && ($param['static'] == '1') )
        {
            $class = get_class($this);
            AdiantiCoreApplication::loadPage($class, 'onReload', ['offset' => 0, 'first_page' => 1]);
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

            // creates a repository for Publicacao
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
                    // add the object inside the datagrid

                    $this->datagrid->addItem($object);

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

}

