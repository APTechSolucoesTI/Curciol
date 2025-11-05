<?php

class MeusDocumentoList extends TPage
{

    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private static $database = 'escritorio';
    private static $activeRecord = 'Documento';
    private static $primaryKey = 'id';
    private static $formName = 'formList_Documento';
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

        $column_modelo_documento_nome = new TDataGridColumn('modelo_documento->nome', "Tipo documento", 'left');
        $column_dt_validade_transformed = new TDataGridColumn('dt_validade', "Validade", 'center' , '150px');

        $column_dt_validade_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $this->datagrid->addColumn($column_modelo_documento_nome);
        $this->datagrid->addColumn($column_dt_validade_transformed);

        $action_onBaixarDocumento = new TDataGridAction(array('MeusDocumentoList', 'onBaixarDocumento'));
        $action_onBaixarDocumento->setUseButton(true);
        $action_onBaixarDocumento->setButtonClass('btn btn-default btn-sm');
        $action_onBaixarDocumento->setLabel("Baixar Documento");
        $action_onBaixarDocumento->setImage('fas:file-download #FF9800');
        $action_onBaixarDocumento->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onBaixarDocumento);

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
            // $container->add(new TXMLBreadCrumb('menu.xml', __CLASS__));
        }
        $container->add($panel);

        parent::add($container);

    }

    public static function onBaixarDocumento($param = null) 
    {
        try 
        {

            if(!empty($param['key']))
            {
                $file = DocumentoDocument::onGenerate(['key'=>$param['key'], 'returnFile'=>true]);

                TToast::show("success", "Documento gerado com sucesso!", "topRight", "fas:check");

                TPage::openFile($file);
            }

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

            // creates a repository for Documento
            $repository = new TRepository(self::$activeRecord);
            // creates a criteria
            $criteria = new TCriteria;

            $filterVar = TSession::getValue('portal_cliente_id')??0;
            $criteria->add(new TFilter('atendimento_id', 'in', "(SELECT id FROM atendimento WHERE cliente_id = '{$filterVar}')"));

            if (empty($param['order']))
            {
                $param['order'] = 'dt_preenchimento';    
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

        $object = new Documento($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

