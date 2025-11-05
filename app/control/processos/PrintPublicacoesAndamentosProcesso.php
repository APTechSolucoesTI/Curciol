<?php

class PrintPublicacoesAndamentosProcesso extends TWindow
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
        parent::setSize(1000, null);
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

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_id_transformed = new TDataGridColumn('id', "", 'center' , '70px');

        $column_id_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            TTransaction::open('escritorio');

            $vinculado = [];

            if($object->origem === "Publicação"){
                $publicacao = Publicacao::find($object->id);
                if($publicacao->processo_id != null) $responsavel = $publicacao->processo->responsavel->nome; else $responsavel = '';

                return '
                    <p style="margin: 1px 0;"> </p>
                    <p style="margin: 1px 0px; text-align: left;">
                        <strong style="text-align: start;">'.$publicacao->jornal->nome.'</strong>
                        <br style="text-align: start;" />
                        <strong style="text-align: start;">Disponibilização: </strong>
                        <span style="text-align: start;">'.$publicacao->data_disponibilizacao_formatada.'</span>
                        <br style="text-align: start;" />
                        <strong style="text-align: start;">Arquivo: </strong>
                        <span style="text-align: start;">'.$publicacao->numero_arquivo.'</span>
                        <br style="text-align: start;" />
                        <strong style="text-align: start;">Publicação: </strong>
                        <span style="text-align: start;">'.$publicacao->numero_publicacao.'</span>
                        <br style="text-align: start;" />
                        <strong style="text-align: start;">Responsável: </strong>
                        <span style="text-align: start;">'.$responsavel.'</span>
                    </p>
                    <p style="margin: 1px 0px; text-align: center;"><strong>'.$publicacao->titulo_formatado.'</strong></p>
                    <p style="margin: 1px 0px; text-align: center;"> </p>
                    <p style="margin: 1px 0px; text-align: justify;">'.$publicacao->texto.'</p>
                    <p style="margin: 1px 0px; text-align: justify;">'.$publicacao->cabecalho.'</p>
                    <p style="margin: 1px 0px; text-align: justify;">'.$publicacao->rodape.'</p>
                    <hr />
                ';

            }
            if($object->origem === "Andamento"){
                $andamento = Andamento::find($object->id);

                return '
                    <p style="margin: 1px 0;"> </p>
                    <p style="margin: 1px 0px; text-align: left;">
                        <strong style="text-align: start;">'.$andamento->tipo_andamento->nome.'</strong>
                        <br style="text-align: start;" />
                        <strong style="text-align: start;">Disponibilização: </strong>
                        <span style="text-align: start;">'.$andamento->data_andamento_formatado.'</span>
                        <br style="text-align: start;" />
                        <strong style="text-align: start;">Autor: </strong>
                        <span style="text-align: start;">'.$andamento->criacao_user->nome.'</span>
                    </p>
                    <p style="margin: 1px 0px; text-align: center;"><strong>'.$andamento->titulo.'</strong></p>
                    <p style="margin: 1px 0px; text-align: center;"> </p>
                    <p style="margin: 1px 0px; text-align: justify;">'.$andamento->texto.'</p>
                    <hr />
                ';
            }

            TTransaction::close();

        });        

        $this->datagrid->addColumn($column_id_transformed);

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

        $button_gerar_pdf = new TButton('button_button_gerar_pdf');
        $button_gerar_pdf->setAction(new TAction(['PrintPublicacoesAndamentosProcesso', 'onExportPdf'],['static' => 1]), "Gerar PDF");
        $button_gerar_pdf->addStyleClass('btn-default');
        $button_gerar_pdf->setImage('far:file-pdf #e74c3c');

        $this->datagrid_form->addField($button_gerar_pdf);

        $head_right_actions->add($button_gerar_pdf);

        $this->datagrid->makeScrollable();
        $this->datagrid->setHeight(500);

        parent::add($this->form);
        parent::add($panel);

    }

    public function onExportPdf($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.pdf';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $this->datagrid->prepareForPrinting();
                $this->onReload();

                $html = clone $this->datagrid;
                $contents = file_get_contents('app/resources/styles-print.html') . $html->getContents();

                $dompdf = new \Dompdf\Dompdf;
                $dompdf->loadHtml($contents);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                file_put_contents($output, $dompdf->output());

                $window = TWindow::create('PDF', 0.8, 0.8);
                $object = new TElement('iframe');
                $object->src  = $output;
                $object->type  = 'application/pdf';
                $object->style = "width: 100%; height:calc(100% - 10px)";

                $window->add($object);
                $window->show();
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
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

            if(!empty($param['key'] ?? 0))
        {
            TSession::setValue(__CLASS__.'load_filter_processo_id', $param['key'] ?? 0);
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

}

