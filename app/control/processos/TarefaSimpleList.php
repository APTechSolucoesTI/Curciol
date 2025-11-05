<?php

class TarefaSimpleList extends TPage
{

    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private static $database = 'escritorio';
    private static $activeRecord = 'Tarefa';
    private static $primaryKey = 'id';
    private static $formName = 'formList_Tarefa';
    private $limit = 20;

    public function __construct($param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->limit = 0;

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_prazo_entrega_transformed = new TDataGridColumn('prazo_entrega', "Prazo de entrega", 'left');
        $column_titulo = new TDataGridColumn('titulo', "Titulo", 'left');
        $column_data_entrega_transformed = new TDataGridColumn('data_entrega', "Data de entrega", 'left');
        $column_tarefa_status_nome_transformed = new TDataGridColumn('tarefa_status->nome', "Status", 'left');

        $column_prazo_entrega_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_data_entrega_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y H:i');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $column_tarefa_status_nome_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $retorno = "<span class='label' style='width:100%;max-width:200px;background-color:{$object->tarefa_status->cor}'> {$value} </span>"; 

            if($object->tarefa_status->fim == 'N'){
                if($object->prazo_entrega >= date('Y-m-d') && $object->prazo_entrega <= date('Y-m-d', strtotime("+5 days",strtotime(date('Y-m-d'))))){
                    $retorno .= "<br/><span class='label' style='width:100%;max-width:200px;background-color:orange'> Prazo a expirar </span>";
                }elseif ($object->prazo_entrega < date('Y-m-d')) {
                    $retorno .= "<br/><span class='label' style='width:100%;max-width:200px;background-color:red'> Prazo expirado </span>";
                }
            }

            return $retorno;
        });        

        $this->datagrid->addColumn($column_prazo_entrega_transformed);
        $this->datagrid->addColumn($column_titulo);
        $this->datagrid->addColumn($column_data_entrega_transformed);
        $this->datagrid->addColumn($column_tarefa_status_nome_transformed);

        $action_onShow = new TDataGridAction(array('TarefaFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField(self::$primaryKey);

        $action_onShow->setParameter('key', '{id}');

        $this->datagrid->addAction($action_onShow);

        // create the datagrid model
        $this->datagrid->createModel();

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $this->datagrid_form->add($this->datagrid);
        $panel->add($this->datagrid_form);

        $panel->getBody()->class .= ' table-responsive';

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Processos","Tarefas do processo"]));
        }
        $container->add($panel);

        parent::add($container);

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

            // creates a repository for Tarefa
            $repository = new TRepository(self::$activeRecord);
            // creates a criteria
            $criteria = new TCriteria;

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

            $criteria1 = new TCriteria();
            $criteria1->add(new TFilter('processo_id', '=', $param['processo_id']), TExpression::OR_OPERATOR);
            $criteria1->add(new TFilter('publicacao_id', 'in', "(SELECT id FROM publicacao WHERE processo_id = '{$param['processo_id']}')"), TExpression::OR_OPERATOR);
            $criteria->add($criteria1);

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
        if (!$this->loaded AND (!isset($_GET['method']) OR !(in_array($_GET['method'],  array('onReload', 'onSearch')))) )
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

        $object = new Tarefa($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

