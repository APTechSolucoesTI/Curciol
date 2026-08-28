<?php

class ProcessoSeekWindow extends TWindow
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Processo';
    private static $primaryKey = 'id';
    private static $formName = 'form_ProcessoSeekWindow';
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
        parent::setTitle("Vincular processo principal");
        parent::setProperty('class', 'window_modal');

        $param['_seek_window_id'] = $this->id;
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);

        $this->limit = 20;

        // define the form title
        $this->form->setFormTitle("Vincular processo principal");

        $criteria_cliente_id = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $numero_cnj_numero = new TEntry('numero_cnj_numero');
        $cliente_id = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );

        $numero_cnj_numero->setMaxLength(30);
        $cliente_id->setMinLength(2);
        $cliente_id->setMask('{nome}');
        $cliente_id->setFilterColumns(["cpf_cnpj","nome"]);
        $cliente_id->setSize('100%');
        $numero_cnj_numero->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Número:", null, '14px', null, '100%'),$numero_cnj_numero],[new TLabel("Cliente:", null, '14px', null, '100%'),$cliente_id]);
        $row1->layout = ['col-sm-6',' col-sm-6'];

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

        $column_numero_cnj_numero = new TDataGridColumn('numero_cnj_numero', "Número", 'left');
        $column_id_transformed = new TDataGridColumn('id', "Clientes", 'center' , '70px');

        $column_id_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $order_id_transformed = new TAction(array($this, 'onReload'));
        $order_id_transformed->setParameter('order', 'id');
        $this->setSeekParameters($order_id_transformed, $param);
        $column_id_transformed->setAction($order_id_transformed);

        $this->datagrid->addColumn($column_numero_cnj_numero);
        $this->datagrid->addColumn($column_id_transformed);

        $action_onVincular = new TDataGridAction(array('ProcessoSeekWindow', 'onVincular'));
        $action_onVincular->setUseButton(true);
        $action_onVincular->setButtonClass('btn btn-default btn-sm');
        $action_onVincular->setLabel("Selecionar");
        $action_onVincular->setImage('far:hand-pointer #44bd32');
        $action_onVincular->setField(self::$primaryKey);
        $this->setSeekParameters($action_onVincular, $param);

        $action_onVincular->setParameter('key', '{id}');
        $this->datagrid->addAction($action_onVincular);

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

        if (isset($data->numero_cnj_numero) AND ( (is_scalar($data->numero_cnj_numero) AND $data->numero_cnj_numero !== '') OR (is_array($data->numero_cnj_numero) AND (!empty($data->numero_cnj_numero)) )) )
        {

            $filters[] = new TFilter('numero_cnj_numero', 'like', "%{$data->numero_cnj_numero}%");// create the filter 
        }

        if (isset($data->cliente_id) AND ( (is_scalar($data->cliente_id) AND $data->cliente_id !== '') OR (is_array($data->cliente_id) AND (!empty($data->cliente_id)) )) )
        {

            $filters[] = new TFilter('modificacao_user_id', 'in', "(SELECT id FROM system_users WHERE password != '{$data->cliente_id}')");// create the filter 
        }

        if (isset($data->cliente_id) AND ( (is_scalar($data->cliente_id) AND $data->cliente_id !== '') OR (is_array($data->cliente_id) AND (!empty($data->cliente_id)) )) )
        {
            $filters[] = new TFilter('id', 'in', "(SELECT processo_id FROM contrato_processo WHERE contrato_id in (SELECT contrato_id FROM contrato_pessoa WHERE cliente_id = $data->cliente_id))");
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

            // creates a repository for Processo
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

    public  function onVincular($param = null) 
    {
        try 
        {

            TTransaction::open(self::$database);

            if(TSession::getValue('nivel_processo')=="PROCESSO"){

                $publicacao = Publicacao::find(TSession::getValue('publicacao_id'));
                $publicacao->processo_id = (int) $param['key'];
                if(!$publicacao->numero_unico_processo){
                    $publicacao->numero_unico_processo = (Processo::find((int) $param['key']))->numero;
                }
                $publicacao->store();

            }else if(TSession::getValue('nivel_processo')=="PRINCIPAL"){

                $vinculo = new ProcessoVinculo();
                $vinculo->processo_incidente_id = TSession::getValue('processo_id');
                $vinculo->processo_principal_id = (int) $param['key'];
                $vinculo->store();

                $publicacoes = Publicacao::where('processo_id','=',TSession::getValue('processo_id'))->load();
                foreach($publicacoes as $publicacao){
                    if($publicacao->numero_processo_principal == null || !$publicacao->numero_processo_principal || empty($publicacao->numero_processo_principal)){
                        $publicacao->numero_processo_principal = (Processo::find((int) $param['key']))->numero;
                        $publicacao->store();
                    }
                }

            }

            TScript::create("$(\"[page_name='ProcessoSeekWindow']\").remove()");

            $pageParam = ['key' => TSession::getValue('publicacao_id')];

            TApplication::loadPage('PublicacaoHeaderList', 'onShow');

            TApplication::loadPage('PublicacaoFormView', 'onShow', $pageParam);

            TTransaction::close();
            APIPublicacaoController::onVerificaPublicacaoEtapa();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

}

