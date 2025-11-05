<?php

class TemplateEscritorioHeaderList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'TemplateEscritorio';
    private static $primaryKey = 'id';
    private static $formName = 'formList_TemplateEscritorio';
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

        $tipo_template = new TCombo('tipo_template');
        $descricao = new TEntry('descricao');
        $habilitado = new TCombo('habilitado');

        $descricao->exitOnEnter();

        $descricao->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $tipo_template->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $habilitado->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $habilitado->addItems(["T"=>"Sim","F"=>"Não"]);
        $tipo_template->addItems(["EMAIL"=>"EMAIL","WHATSAPP"=>"WHATSAPP"]);

        $descricao->setSize('100%');
        $habilitado->setSize('100%');
        $tipo_template->setSize('100%');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $filterVar = TSession::getValue("userunitid");
        $this->filter_criteria->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}')"));

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_tipo_template = new TDataGridColumn('tipo_template', "Tipo", 'center' , '100px');
        $column_descricao = new TDataGridColumn('descricao', "Descrição", 'left');
        $column_habilitado_transformed = new TDataGridColumn('habilitado', "Habilitar", 'center' , '100px');

        $column_habilitado_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            $label = new TElement('span');
            $label->{'class'} = 'label label-';

            if ($value == 'S' || $value == 'T') {
                $label->{'class'} .= 'success';
                $label->add('Sim');    

                return $label;
            }

            $label->{'class'} .= 'danger';
            $label->add('Não');

            return $label;
        });        

        $this->datagrid->addColumn($column_tipo_template);
        $this->datagrid->addColumn($column_descricao);
        $this->datagrid->addColumn($column_habilitado_transformed);

        $action_onEdit = new TDataGridAction(array('TemplateEscritorioForm', 'onEdit'));
        $action_onEdit->setUseButton(false);
        $action_onEdit->setButtonClass('btn btn-default btn-sm');
        $action_onEdit->setLabel("Editar");
        $action_onEdit->setImage('far:edit #478fca');
        $action_onEdit->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onEdit);

        $action_onDelete = new TDataGridAction(array('TemplateEscritorioHeaderList', 'onDelete'));
        $action_onDelete->setUseButton(false);
        $action_onDelete->setButtonClass('btn btn-default btn-sm');
        $action_onDelete->setLabel("Deletar");
        $action_onDelete->setImage('fas:trash-alt #F44336');
        $action_onDelete->setField(self::$primaryKey);
        $action_onDelete->setDisplayCondition('TemplateEscritorioHeaderList::canDelete');

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
        $td_tipo_template = TElement::tag('td', $tipo_template);
        $tr->add($td_tipo_template);
        $td_descricao = TElement::tag('td', $descricao);
        $tr->add($td_descricao);
        $td_habilitado = TElement::tag('td', $habilitado);
        $tr->add($td_habilitado);

        $this->datagrid_form->addField($tipo_template);
        $this->datagrid_form->addField($descricao);
        $this->datagrid_form->addField($habilitado);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Listagem de template");
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

        $button_novo = new TButton('button_button_novo');
        $button_novo->setAction(new TAction(['TemplateEscritorioForm', 'onShow']), "Novo");
        $button_novo->addStyleClass('btn-default');
        $button_novo->setImage('fas:plus #4CAF50');

        $this->datagrid_form->addField($button_novo);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['TemplateEscritorioHeaderList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['TemplateEscritorioHeaderList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $head_left_actions->add($button_novo);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($button_limpar_filtros);

        $this->datagrid_form->add($this->datagrid);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Configurações","Template"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    public function onDelete($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            if (! empty($param['delete']) && $param['delete'] == 1)
            {
                $key = $param['key'];
                $object = new TemplateEscritorio($key, FALSE);

                if ($object->readonly == 'T')
                {
                    throw new Exception('Esse template não pode ser apagado');
                }

                if ($object->escritorio->system_unit_id != TSession::getValue('userunitid'))
                {
                    throw new Exception('Template de outra unidade');
                }

                Mensagem::where('template_escritorio_id', '=', $object->id)->set('template_escritorio_id', null)->update();
                TemplateAcao::where('template_escritorio_id', '=', $object->id)->delete();
                $object->delete();

                TTransaction::close();

                $this->onReload( $param );
                TToast::show('success', AdiantiCoreTranslator::translate('Record deleted'), 'topRight', 'far:check-circle');
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

            TTransaction::close();
            //</autoCode>
        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canDelete($object)
    {
        try 
        {
            if($object->readonly == 'T')
            {
                return false;
            }

            return true;
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
        // get the search form data
        $data = $this->datagrid_form->getData();
        $filters = [];

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->tipo_template) AND ( (is_scalar($data->tipo_template) AND $data->tipo_template !== '') OR (is_array($data->tipo_template) AND (!empty($data->tipo_template)) )) )
        {

            $filters[] = new TFilter('tipo_template', '=', $data->tipo_template);// create the filter 
        }

        if (isset($data->descricao) AND ( (is_scalar($data->descricao) AND $data->descricao !== '') OR (is_array($data->descricao) AND (!empty($data->descricao)) )) )
        {

            $filters[] = new TFilter('descricao', 'like', "%{$data->descricao}%");// create the filter 
        }

        if (isset($data->habilitado) AND ( (is_scalar($data->habilitado) AND $data->habilitado !== '') OR (is_array($data->habilitado) AND (!empty($data->habilitado)) )) )
        {

            $filters[] = new TFilter('habilitado', '=', $data->habilitado);// create the filter 
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

            // creates a repository for TemplateEscritorio
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

    public static function manageRow($id, $param = [])
    {
        $list = new self($param);

        $openTransaction = TTransaction::getDatabase() != self::$database ? true : false;

        if($openTransaction)
        {
            TTransaction::open(self::$database);    
        }

        $object = new TemplateEscritorio($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

