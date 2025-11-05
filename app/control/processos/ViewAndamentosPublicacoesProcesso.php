<?php

class ViewAndamentosPublicacoesProcesso extends TPage
{

    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private static $database = 'escritorio';
    private static $activeRecord = 'ViewAndamentos';
    private static $primaryKey = 'id';
    private static $formName = 'formList_ViewAndamentos';
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
        $this->datagrid->enablePopover("", " {texto_caracteres} ");

        $column_origem = new TDataGridColumn('origem', "Origem", 'left');
        $column_dt_transformed = new TDataGridColumn('dt', "Data", 'left');
        $column_jornal_tipo = new TDataGridColumn('jornal_tipo', "Jornal/Tipo", 'left');
        $column_titulo_transformed = new TDataGridColumn('titulo', "Titulo", 'left');

        $column_dt_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_titulo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            return str_replace(";","<br/>",$value);

        });        

        $order_dt_transformed = new TAction(array($this, 'onReload'));
        $order_dt_transformed->setParameter('order', 'dt');
        $column_dt_transformed->setAction($order_dt_transformed);
        $order_titulo_transformed = new TAction(array($this, 'onReload'));
        $order_titulo_transformed->setParameter('order', 'titulo');
        $column_titulo_transformed->setAction($order_titulo_transformed);

        $this->datagrid->enablePopover("Texto", "{texto_caracteres}", null, function($object){
            if(!$object->texto_caracteres)
            {
                return false;
            }
            return true;
        });
        $this->datagrid->addColumn($column_origem);
        $this->datagrid->addColumn($column_dt_transformed);
        $this->datagrid->addColumn($column_jornal_tipo);
        $this->datagrid->addColumn($column_titulo_transformed);

        $action_onShow = new TDataGridAction(array('PublicacaoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Consultar publicação");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField(self::$primaryKey);
        $action_onShow->setDisplayCondition('ViewAndamentosPublicacoesProcesso::canViewPublicacao');
        $action_onShow->setParameter('key', '{id}');

        $this->datagrid->addAction($action_onShow);

        $action_AndamentoFormView_onShow = new TDataGridAction(array('AndamentoFormView', 'onShow'));
        $action_AndamentoFormView_onShow->setUseButton(false);
        $action_AndamentoFormView_onShow->setButtonClass('btn btn-default btn-sm');
        $action_AndamentoFormView_onShow->setLabel("Consultar andamento");
        $action_AndamentoFormView_onShow->setImage('fas:search-plus #000000');
        $action_AndamentoFormView_onShow->setField(self::$primaryKey);
        $action_AndamentoFormView_onShow->setDisplayCondition('ViewAndamentosPublicacoesProcesso::canViewAndamento');
        $action_AndamentoFormView_onShow->setParameter('key', '{id}');

        $this->datagrid->addAction($action_AndamentoFormView_onShow);

        $action_onEdit = new TDataGridAction(array('AndamentoForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Editar");
        $action_onEdit->setImage('fas:edit #000000');
        $action_onEdit->setField(self::$primaryKey);
        $action_onEdit->setDisplayCondition('ViewAndamentosPublicacoesProcesso::canEditaAndamento');
        $action_onEdit->setParameter('key', '{id}');

        $this->datagrid->addAction($action_onEdit);

        // create the datagrid model
        $this->datagrid->createModel();

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $this->datagrid_form->add($this->datagrid);
        $panel->add($this->datagrid_form);

        $panel->getBody()->class .= ' table-responsive';

        $headerActions = new TElement('div');
        $headerActions->class = ' datagrid-header-actions ';
        $headerActions->style = 'justify-content: space-between;';

        $head_left_actions = new TElement('div');
        $head_left_actions->class = ' datagrid-header-actions-left-actions ';

        $head_right_actions = new TElement('div');
        $head_right_actions->class = ' datagrid-header-actions-left-actions ';

        $headerActions->add($head_left_actions);
        $headerActions->add($head_right_actions);

        $panel->getBody()->insert(0, $headerActions);

        $btnImprimir = new TButton('button_btnImprimir');
        $btnImprimir->setAction(new TAction(['PrintPublicacoesAndamentosProcesso', 'onShow']), "Imprimir");
        $btnImprimir->addStyleClass('btn-default');
        $btnImprimir->setImage('fas:print #000000');
        $btnImprimir->getAction()->setParameter("key", $param['key'] ?? 0);

        $this->datagrid_form->addField($btnImprimir);

        $btnAddAndamento = new TButton('button_btnAddAndamento');
        $btnAddAndamento->setAction(new TAction(['AndamentoForm', 'onShow']), "Adicionar andamento");
        $btnAddAndamento->addStyleClass('btn-default');
        $btnAddAndamento->setImage('fas:plus #4CAF50');
        $btnAddAndamento->getAction()->setParameter("processo_id", $param['key'] ?? null);
        $btnAddAndamento->getAction()->setParameter("tela", "Aba de andamentos do processo");

        $this->datagrid_form->addField($btnAddAndamento);

        $head_left_actions->add($btnAddAndamento);

        $head_right_actions->add($btnImprimir);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Processos","Visualizar andamentos e publicações do processo"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    public static function canViewPublicacao($object)
    {
       try {
        return self::equalsPT($object->origem ?? '', 'Publicação');
    } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
    }

    }
    public static function canViewAndamento($object)
    {
          try {
        return self::equalsPT($object->origem ?? '', 'Andamento');
    } catch (Exception $e) {
        new TMessage('error', $e->getMessage());
    }
    }
    public static function canEditaAndamento($object)
    {
        try 
        {
            if($object->origem === "Andamento")
            {
                return true;
            }

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
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

            // creates a repository for ViewAndamentos
            $repository = new TRepository(self::$activeRecord);
            // creates a criteria
            $criteria = new TCriteria;

            if(!empty($param['key'] ?? 1))
        {
            TSession::setValue(__CLASS__.'load_filter_processo_id', $param['key'] ?? 1);
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_processo_id');
            $criteria->add(new TFilter('processo_id', '=', $filterVar));

            if (empty($param['order']))
            {
                $param['order'] = 'dt';    
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

        $object = new ViewAndamentos($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

    private static function normPT(?string $s): string
    {
        $s = (string) $s;
        // troca NBSP por espaço normal e tira espaços extras
        $s = str_replace("\xC2\xA0", ' ', $s);
        $s = trim(preg_replace('/\s+/u', ' ', $s));

        // normaliza Unicode (NFC). disponível no ext-intl
        if (function_exists('normalizer_normalize')) {
            $s = normalizer_normalize($s, Normalizer::FORM_C);
        }

        return $s;
    }

    private static function equalsPT($a, $b): bool
    {
        $a = self::normPT($a);
        $b = self::normPT($b);

        // 1) tenta igual byte a byte após normalizar
        if ($a === $b) return true;

        // 2) fallback: ignora acentos e case
        $rmAcc = function ($x) {
            if (function_exists('normalizer_normalize')) {
                $x = normalizer_normalize($x, Normalizer::FORM_D);
            }
            // remove marcas de acento
            $x = preg_replace('/\p{Mn}+/u', '', $x);
            return mb_strtolower($x, 'UTF-8');
        };

        return $rmAcc($a) === $rmAcc($b);
    }

}

