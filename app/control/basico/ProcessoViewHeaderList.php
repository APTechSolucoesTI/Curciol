<?php

class ProcessoViewHeaderList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'ProcessoView';
    private static $primaryKey = 'id';
    private static $formName = 'formList_ProcessoView';
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

        $this->limit = 0;

        $tipo_processo = new TEntry('tipo_processo');
        $assunto = new TEntry('assunto');
        $numero = new TEntry('numero');

        $tipo_processo->exitOnEnter();
        $assunto->exitOnEnter();
        $numero->exitOnEnter();

        $tipo_processo->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $assunto->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $numero->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $numero->setSize('100%');
        $assunto->setSize('100%');
        $tipo_processo->setSize('100%');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        if(!empty($param["key"] ?? ""))
        {
            TSession::setValue(__CLASS__.'load_filter_pessoa_id', $param["key"] ?? "");
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_pessoa_id');
        $this->filter_criteria->add(new TFilter('pessoa_id', '=', $filterVar));
        $filterVar = "S";
        $this->filter_criteria->add(new TFilter('exibir_cliente', '=', $filterVar));

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_tipo_processo = new TDataGridColumn('tipo_processo', "Tipo", 'left');
        $column_assunto = new TDataGridColumn('assunto', "Assunto", 'left');
        $column_numero = new TDataGridColumn('numero', "Número", 'left');
        $column_ultima_etapa = new TDataGridColumn('ultima_etapa', "Ultima etapa", 'left');

        $this->datagrid->addColumn($column_tipo_processo);
        $this->datagrid->addColumn($column_assunto);
        $this->datagrid->addColumn($column_numero);
        $this->datagrid->addColumn($column_ultima_etapa);

        $action_onShow = new TDataGridAction(array('ProcessosFormViewInterno', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar Processo");
        $action_onShow->setImage('fas:folder-open #000000');
        $action_onShow->setField(self::$primaryKey);

        $action_onShow->setParameter('processo_id', '{id}');

        $this->datagrid->addAction($action_onShow);

        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);

        if(!$action_onShow->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        $td_tipo_processo = TElement::tag('td', $tipo_processo);
        $tr->add($td_tipo_processo);
        $td_assunto = TElement::tag('td', $assunto);
        $tr->add($td_assunto);
        $td_numero = TElement::tag('td', $numero);
        $tr->add($td_numero);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);

        $this->datagrid_form->addField($tipo_processo);
        $this->datagrid_form->addField($assunto);
        $this->datagrid_form->addField($numero);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $panel->getHeader()->style = ' display:none !important; ';
        $panel->getBody()->class .= ' table-responsive';

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

        $this->datagrid_form->add($this->datagrid);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Básico","Meus processos"]));
        }
        $container->add($panel);


        $container->class = trim(($container->class ?? '') . ' curciol-lista-processos');

        /*
            No celular a tabela de 4 colunas fica ilegível. As mesmas linhas
            viram cards empilhados, cada célula com seu próprio rótulo.
        */
        $style = new TElement('style');
        $style->add('
            @media (max-width: 768px) {
                .curciol-lista-processos .panel,
                .curciol-lista-processos .card,
                .curciol-lista-processos .panel-body,
                .curciol-lista-processos .card-body,
                .curciol-lista-processos .table-responsive {
                    padding: 0 !important;
                    margin: 0 !important;
                    background: transparent !important;
                    border: 0 !important;
                    border-radius: 0 !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                }

                .curciol-lista-processos table {
                    display: block !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    border: 0 !important;
                    box-shadow: none !important;
                }

                .curciol-lista-processos thead,
                .curciol-lista-processos #datagrid-header-filter-row {
                    display: none !important;
                }

                /*
                    O datagrid nasce com altura fixa e rolagem própria; num
                    telefone isso vira rolagem dentro de rolagem.
                */
                .curciol-lista-processos tbody {
                    display: block !important;
                    width: 100% !important;
                    height: auto !important;
                    max-height: none !important;
                    overflow: visible !important;
                }

                .curciol-lista-processos tbody tr {
                    display: block !important;
                    width: 100% !important;
                    margin: 0 0 12px 0 !important;
                    padding: 12px 14px !important;
                    background: #ffffff !important;
                    border: 1px solid #dfe3ea !important;
                    border-left: 4px solid #0D4069 !important;
                    border-radius: 14px !important;
                    box-shadow: 0 4px 14px rgba(30, 40, 67, .07) !important;
                    box-sizing: border-box !important;
                }

                .curciol-lista-processos tbody td {
                    display: block !important;
                    width: 100% !important;
                    padding: 5px 0 !important;
                    border: 0 !important;
                    text-align: left !important;
                    white-space: normal !important;
                    overflow-wrap: break-word !important;
                    color: #1E2843 !important;
                    font-family: Helvetica, Arial, sans-serif !important;
                    font-size: 14px !important;
                    font-weight: 600 !important;
                    line-height: 1.35 !important;
                }

                .curciol-lista-processos tbody td:nth-child(2):before,
                .curciol-lista-processos tbody td:nth-child(3):before,
                .curciol-lista-processos tbody td:nth-child(4):before,
                .curciol-lista-processos tbody td:nth-child(5):before {
                    display: block;
                    color: #7c8698;
                    font-size: 11px;
                    font-weight: 600;
                    letter-spacing: .03em;
                    margin-bottom: 1px;
                }

                .curciol-lista-processos tbody td:nth-child(2):before {
                    content: "Tipo";
                }

                .curciol-lista-processos tbody td:nth-child(3):before {
                    content: "Assunto";
                }

                .curciol-lista-processos tbody td:nth-child(4):before {
                    content: "Número";
                }

                .curciol-lista-processos tbody td:nth-child(5):before {
                    content: "Última etapa";
                }

                .curciol-lista-processos tbody td:nth-child(4) {
                    word-break: break-all !important;
                }

                /* a coluna de ação passa a ser o rodapé do card */
                .curciol-lista-processos tbody td.action {
                    margin-top: 10px !important;
                    padding: 10px 0 0 0 !important;
                    border-top: 1px solid #eef1f6 !important;
                }

                .curciol-lista-processos tbody td.action a {
                    display: inline-flex !important;
                    align-items: center !important;
                    gap: 8px !important;
                    color: #0D4069 !important;
                    font-size: 14px !important;
                    font-weight: 700 !important;
                    text-decoration: none !important;
                }

                .curciol-lista-processos tbody td.action a:after {
                    content: "Ver andamento";
                }

                .curciol-lista-processos .tdatagrid_container,
                .curciol-lista-processos .datagrid-header-actions {
                    width: 100% !important;
                    max-width: 100% !important;
                    overflow: visible !important;
                }
            }

            @media (max-width: 390px) {
                .curciol-lista-processos tbody tr {
                    padding: 11px 12px !important;
                    border-radius: 12px !important;
                }

                .curciol-lista-processos tbody td {
                    font-size: 13.5px !important;
                }
            }
        ');

        $container->add($style);
        parent::add($container);

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

        if (isset($data->tipo_processo) AND ( (is_scalar($data->tipo_processo) AND $data->tipo_processo !== '') OR (is_array($data->tipo_processo) AND (!empty($data->tipo_processo)) )) )
        {

            $filters[] = new TFilter('tipo_processo', 'like', "%{$data->tipo_processo}%");// create the filter 
        }

        if (isset($data->assunto) AND ( (is_scalar($data->assunto) AND $data->assunto !== '') OR (is_array($data->assunto) AND (!empty($data->assunto)) )) )
        {

            $filters[] = new TFilter('assunto', 'like', "%{$data->assunto}%");// create the filter 
        }

        if (isset($data->numero) AND ( (is_scalar($data->numero) AND $data->numero !== '') OR (is_array($data->numero) AND (!empty($data->numero)) )) )
        {

            $filters[] = new TFilter('numero', 'like', "%{$data->numero}%");// create the filter 
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

            // creates a repository for ProcessoView
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

        $object = new ProcessoView($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

