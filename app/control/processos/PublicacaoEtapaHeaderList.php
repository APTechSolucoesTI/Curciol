<?php

class PublicacaoEtapaHeaderList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'PublicacaoEtapa';
    private static $primaryKey = 'id';
    private static $formName = 'formList_PublicacaoEtapa';
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

        $criteria_ordem_prioridade = new TCriteria();
        $criteria_etapa_nome = new TCriteria();

        $ordem_prioridade = new TDBCombo('ordem_prioridade', 'escritorio', 'PublicacaoEtapa', 'ordem_prioridade', '{ordem_prioridade}','id asc' , $criteria_ordem_prioridade );
        $etapa_nome = new TDBCombo('etapa_nome', 'escritorio', 'PublicacaoEtapa', 'etapa_nome', '{etapa_nome}','id asc' , $criteria_etapa_nome );

        $ordem_prioridade->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $etapa_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $etapa_nome->setSize('100%');
        $ordem_prioridade->setSize('100%');

        $etapa_nome->enableSearch();
        $ordem_prioridade->enableSearch();

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->disableDefaultClick();
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_ordem_prioridade = new TDataGridColumn('ordem_prioridade', "Ordem prioridade", 'left' , '10%');
        $column_cor_transformed = new TDataGridColumn('cor', "Cor", 'center' , '20%');
        $column_etapa_nome = new TDataGridColumn('etapa_nome', "Etapa", 'left');
        $column_extrajudicial_transformed = new TDataGridColumn('extrajudicial', "Extrajudicial", 'left');
        $column_judicial_transformed = new TDataGridColumn('judicial', "Judicial", 'left');

        $column_cor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return "<span class='label' style='width: 100%; max-width: 200px;background-color:".$value.";'> {$value} <span> ";
        });

        $column_extrajudicial_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $valor = strtoupper(trim((string) $value));

            if ($valor == 'S') {
                return "<span style='
                    display:inline-block;
                    width:12px;
                    height:12px;
                    border-radius:50%;
                    background:#16a34a;
                    box-shadow:0 0 4px rgba(22,163,74,.6);
                ' title='Etapa Verificada'></span>";
            }

            if ($valor == 'N') {
                return "<span style='
                    display:inline-block;
                    width:12px;
                    height:12px;
                    border-radius:50%;
                    background:#dc2626;
                    box-shadow:0 0 4px rgba(220,38,38,.6);
                ' title='Etapa Não Verificada'></span>";
            }

            return '';    
        });

        $column_judicial_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $valor = strtoupper(trim((string) $value));

            if ($valor == 'S') {
                return "<span style='
                    display:inline-block;
                    width:12px;
                    height:12px;
                    border-radius:50%;
                    background:#16a34a;
                    box-shadow:0 0 4px rgba(22,163,74,.6);
                ' title='Etapa Verificada'></span>";
            }

            if ($valor == 'N') {
                return "<span style='
                    display:inline-block;
                    width:12px;
                    height:12px;
                    border-radius:50%;
                    background:#dc2626;
                    box-shadow:0 0 4px rgba(220,38,38,.6);
                ' title='Etapa Não Verificada'></span>";
            }

            return '';    
        });        

        $this->datagrid->addColumn($column_ordem_prioridade);
        $this->datagrid->addColumn($column_cor_transformed);
        $this->datagrid->addColumn($column_etapa_nome);
        $this->datagrid->addColumn($column_extrajudicial_transformed);
        $this->datagrid->addColumn($column_judicial_transformed);

        $action_onEdit = new TDataGridAction(array('PublicacaoEtapaForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Editar");
        $action_onEdit->setImage('far:edit #478fca');
        $action_onEdit->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onEdit);

        $action_onDelete = new TDataGridAction(array('PublicacaoEtapaHeaderList', 'onDelete'));
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
        $td_ordem_prioridade = TElement::tag('td', $ordem_prioridade);
        $tr->add($td_ordem_prioridade);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_etapa_nome = TElement::tag('td', $etapa_nome);
        $tr->add($td_etapa_nome);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);

        $this->datagrid_form->addField($ordem_prioridade);
        $this->datagrid_form->addField($etapa_nome);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $this->datagrid->disableDefaultClick(); 

        $panel = new TPanelGroup("Listagem de etapas de andamentos");
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
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

        $button_cadastrar = new TButton('button_button_cadastrar');
        $button_cadastrar->setAction(new TAction(['PublicacaoEtapaForm', 'onShow']), "Cadastrar");
        $button_cadastrar->addStyleClass('btn-default');
        $button_cadastrar->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($button_cadastrar);

        $verificaetapa = new TButton('button_verificaetapa');
        $verificaetapa->setAction(new TAction(['PublicacaoEtapaHeaderList', 'onChama']), "Sincronizar Etapas");
        $verificaetapa->addStyleClass('btn-default');
        $verificaetapa->setImage('fas:sync-alt #000000');

        $this->datagrid_form->addField($verificaetapa);

        $deleteetapas = new TButton('button_deleteetapas');
        $deleteetapas->setAction(new TAction(['PublicacaoEtapaHeaderList', 'onDeleteSyncEtapas']), "Excluir Sincronização");
        $deleteetapas->addStyleClass('btn-default');
        $deleteetapas->setImage('fas:trash-alt #000000');

        $this->datagrid_form->addField($deleteetapas);

        $head_left_actions->add($button_cadastrar);
        $head_left_actions->add($verificaetapa);
        $head_left_actions->add($deleteetapas);

        $this->datagrid_form->add($this->datagrid);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Processos","Etapas de Publicação"]));
        }

        $container->add($panel);

        $user =TSession::getValue("userid");

        if ($user != 1 && $user != 3) {
            TScript::create("$(\"[name='button_verificaetapa']\").hide()");
            TScript::create("$(\"[name='button_deleteetapas']\").hide()");
        }            

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
                $object = new PublicacaoEtapa($key, FALSE); 
                if (!$object->id) {
                    throw new Exception('Registro não encontrado');
                }

                Publicacao::where('publicacao_etapa_id', '=', $key)
                          ->set('publicacao_etapa_id', null)
                          ->update();                

                EtapaPalavrasChaves::where('publicacao_etapa_id', '=', $key)->delete();

                // deletes the object from the database
                $object->delete();

                // close the transaction
                TTransaction::close();

                // reload the listing
                $this->onReload( $param );
                // shows the success message
                TToast::show('success', AdiantiCoreTranslator::translate('Record deleted'), 'topRight', 'far:check-circle');
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
    public function onChama($param = null) 
    {
        try 
        {
            APIPublicacaoController::onVerificaPublicacaoEtapa();

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onDeleteSyncEtapas($param = null) 
    {
        try 
        {
            APIPublicacaoController::onExcluirVerificacaoPublicacaoEtapa();
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
        // get the search form data
        $data = $this->datagrid_form->getData();
        $filters = [];

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->ordem_prioridade) AND ( (is_scalar($data->ordem_prioridade) AND $data->ordem_prioridade !== '') OR (is_array($data->ordem_prioridade) AND (!empty($data->ordem_prioridade)) )) )
        {

            $filters[] = new TFilter('ordem_prioridade', '=', $data->ordem_prioridade);// create the filter 
        }

        if (isset($data->etapa_nome) AND ( (is_scalar($data->etapa_nome) AND $data->etapa_nome !== '') OR (is_array($data->etapa_nome) AND (!empty($data->etapa_nome)) )) )
        {

            $filters[] = new TFilter('etapa_nome', '=', $data->etapa_nome);// create the filter 
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

            // creates a repository for PublicacaoEtapa
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'ordem_prioridade';    
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

        $object = new PublicacaoEtapa($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

