<?php

class ContratoProcessoSimpleList extends TWindow
{

    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private static $database = 'escritorio';
    private static $activeRecord = 'ContratoProcesso';
    private static $primaryKey = 'id';
    private static $formName = 'formList_ContratoProcesso';
    private $limit = 20;

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.8, null);
        parent::setTitle("");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->limit = 20;

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);

        $this->datagrid->disableDefaultClick();
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_contrato_numero = new TDataGridColumn('{contrato->numero}', "Contrato", 'left');
        $column_contrato_contrato_pessoa_cliente_to_string = new TDataGridColumn('{contrato->contrato_pessoa_cliente_to_string}', "Clientes", 'left');

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

        $this->datagrid->addColumn($column_contrato_numero);
        $this->datagrid->addColumn($column_contrato_contrato_pessoa_cliente_to_string);

        // create the datagrid model
        $this->datagrid->createModel();

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $this->datagrid_form->add($this->datagrid);
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

        $panel->getBody()->insert(0, $headerActions);

        $button_remover = new TButton('button_button_remover');
        $button_remover->setAction(new TAction(['ContratoProcessoSimpleList', 'onRemove']), "Remover");
        $button_remover->addStyleClass('btn-default');
        $button_remover->setImage('fas:times #000000');

        $this->datagrid_form->addField($button_remover);

        $button_continuar = new TButton('button_button_continuar');
        $button_continuar->setAction(new TAction(['ContratoProcessoSimpleList', 'onContinuar']), "Continuar");
        $button_continuar->addStyleClass('btn-default');
        $button_continuar->setImage('fas:arrow-alt-circle-right #000000');
        if(!empty($param['processo_id']))
        {
            $button_continuar->getAction()->setParameter("key", $param['processo_id']);
        }

        $this->datagrid_form->addField($button_continuar);

        $head_right_actions->add($button_remover);
        $head_right_actions->add($button_continuar);


        parent::add($this->form);
        parent::add($panel);

    }

    public function onRemove($param = null) 
    {
        try 
        {
            if(isset($param['builder_datagrid_check']) && !empty($param['builder_datagrid_check'])){
                foreach($param['builder_datagrid_check'] as $contrato_id){
                    TTransaction::open(self::$database);

                    $contrato_processo = ContratoProcesso::find($contrato_id);
                    $pageParam['key'] = $contrato_processo->processo_id;

                    $contrato_processo->delete();
                    TTransaction::close();
                    $this->onReload();
                }
            }
            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onContinuar($param = null) 
    {
        try 
        {  
            TApplication::loadPage('ProcessoForm','onEdit',['key'=>TSession::getValue('processo_id')]);
            TSession::delValue('processo_id');
            TWindow::closeWindow();

            //</autoCode>
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

            // creates a repository for ContratoProcesso
            $repository = new TRepository(self::$activeRecord);
            // creates a criteria
            $criteria = new TCriteria;

            if(!empty($param['processo_id']))
        {
            TSession::setValue(__CLASS__.'load_filter_processo_id', $param['processo_id']);
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_processo_id');
            $criteria->add(new TFilter('processo_id', '=', $filterVar));

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
                    $check = new TCheckButton('builder_datagrid_check[]');
                    $check->setIndexValue($object->id);
                    $check->onclick = 'event.stopPropagation();';
                    $object->builder_datagrid_check = $check;

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

        TSession::setValue('processo_id',$param['processo_id']);
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

        $object = new ContratoProcesso($id);

        $check = new TCheckButton('builder_datagrid_check[]');
        $check->setIndexValue($object->id);
        $check->onclick = 'event.stopPropagation();';
        $object->builder_datagrid_check = $check;

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

