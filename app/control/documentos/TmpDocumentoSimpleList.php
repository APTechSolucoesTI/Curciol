<?php

class TmpDocumentoSimpleList extends TWindow
{

    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private static $database = 'escritorio';
    private static $activeRecord = 'TmpDocumento';
    private static $primaryKey = 'id';
    private static $formName = 'formList_TmpDocumento';
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

        $this->limit = 0;

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_nome = new TDataGridColumn('nome', "Nome", 'left');

        $this->datagrid->addColumn($column_nome);

        $action_onImprimir = new TDataGridAction(array('TmpDocumentoSimpleList', 'onImprimir'));
        $action_onImprimir->setUseButton(false);
        $action_onImprimir->setButtonClass('btn btn-default btn-sm');
        $action_onImprimir->setLabel("Imprimir");
        $action_onImprimir->setImage('fas:print #000000');
        $action_onImprimir->setField(self::$primaryKey);
        $action_onImprimir->setDisplayCondition('TmpDocumentoSimpleList::canPdf');
        $action_onImprimir->setParameter('filename', '{filename}');

        $this->datagrid->addAction($action_onImprimir);

        $action_onDownload = new TDataGridAction(array('TmpDocumentoSimpleList', 'onDownload'));
        $action_onDownload->setUseButton(false);
        $action_onDownload->setButtonClass('btn btn-default btn-sm');
        $action_onDownload->setLabel("Download");
        $action_onDownload->setImage('fas:file-download #000000');
        $action_onDownload->setField(self::$primaryKey);

        $action_onDownload->setParameter('filename', '{filename}');

        $this->datagrid->addAction($action_onDownload);

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

        $btn = new TButton('button_btn');
        $btn->setAction(new TAction(['TmpDocumentoSimpleList', 'onConcluir']), "Finalizar");
        $btn->addStyleClass('btn-danger');
        $btn->setImage('fas:check #FFFFFF');

        $this->datagrid_form->addField($btn);

        $head_right_actions->add($btn);


        parent::add($this->form);
        parent::add($panel);

    }

    public function onImprimir($param = null) 
    {
        try 
        {
            TPage::openFile($param['filename'].".pdf");

                        //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public static function canPdf($object)
    {
        try 
        {
            //if($object)
            //{
            //    return true;
            //}

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onDownload($param = null) 
    {
        try 
        {
            TPage::openFile($param['filename'].".docx");

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onConcluir($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            TmpDocumento::where('id','>=',0)->delete();
            TTransaction::close();
            TWindow::closeWindow(parent::getId());
            TApplication::loadPage('DocumentoAvulsoForm', 'onShow');

            $dir = '/var/www/curciol.sislawyer.com.br/files/documents/temporario';
            $di = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
            $ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);

            foreach ( $ri as $file ) {
                $file->isDir() ?  rmdir($file) : unlink($file);
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

            // creates a repository for TmpDocumento
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

        $object = new TmpDocumento($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

