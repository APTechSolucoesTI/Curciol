<?php

class PrintPublicacaoProcessoList extends TWindow
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'ViewPublicacao';
    private static $primaryKey = 'id';
    private static $formName = 'form_PrintPublicacaoProcessoList';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.8, null);
        parent::setTitle("Imprimir publicações do processo");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);

        // define the form title
        $this->form->setFormTitle("Imprimir publicações do processo");
        $this->limit = 20;

        $criteria_jornal = new TCriteria();

        $numero_arquivo = new TEntry('numero_arquivo');
        $numero_publicacao = new TEntry('numero_publicacao');
        $titulo = new TEntry('titulo');
        $numero_unico_processo = new TEntry('numero_unico_processo');
        $data_disponibilizacao = new TDateTime('data_disponibilizacao');
        $prazo = new TDate('prazo');
        $data_entrega = new TDateTime('data_entrega');
        $responsavel = new TEntry('responsavel');
        $jornal = new TDBCombo('jornal', 'escritorio', 'Jornal', 'nome', '{nome}','nome asc' , $criteria_jornal );


        $jornal->enableSearch();
        $prazo->setMask('dd/mm/yyyy');
        $data_entrega->setMask('dd/mm/yyyy hh:ii');
        $data_disponibilizacao->setMask('dd/mm/yyyy hh:ii');

        $prazo->setDatabaseMask('yyyy-mm-dd');
        $data_entrega->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_disponibilizacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $prazo->setSize('100%');
        $titulo->setSize('100%');
        $jornal->setSize('100%');
        $responsavel->setSize('100%');
        $data_entrega->setSize('100%');
        $numero_arquivo->setSize('100%');
        $numero_publicacao->setSize('100%');
        $numero_unico_processo->setSize('100%');
        $data_disponibilizacao->setSize('100%');

        $row1 = $this->form->addFields([$numero_arquivo,$numero_publicacao,$titulo,$numero_unico_processo,$data_disponibilizacao,$prazo,$data_entrega,$responsavel,$jornal],[]);
        $row1->layout = [' col-sm-3','col-sm-2'];

        // keep the form filled during navigation with session data
        $this->form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        $btn_onsearch = $this->form->addAction("Buscar", new TAction([$this, 'onSearch']), 'fas:search #ffffff');
        $this->btn_onsearch = $btn_onsearch;
        $btn_onsearch->addStyleClass('btn-primary'); 

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(250);

        $column_id_transformed = new TDataGridColumn('id', " ", 'center' , '70px');

        $column_id_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return '
                <p style="margin: 1px 0;"> </p>
                <p style="margin: 1px 0px; text-align: left;">
                    <strong style="text-align: start;">'.$object->jornal.'</strong>
                    <br style="text-align: start;" />
                    <strong style="text-align: start;">Disponibilização: </strong>
                    <span style="text-align: start;">'.$object->data_disponibilizacao_formatada.'</span>
                    <br style="text-align: start;" />
                    <strong style="text-align: start;">Arquivo: </strong>
                    <span style="text-align: start;">'.$object->numero_arquivo.'</span>
                    <br style="text-align: start;" />
                    <strong style="text-align: start;">Publicação: </strong>
                    <span style="text-align: start;">'.$object->numero_publicacao.'</span>
                    <br style="text-align: start;" />
                    <strong style="text-align: start;">Resposável: </strong>
                    <span style="text-align: start;">'.$object->responsavel.'</span>
                </p>
                <p style="margin: 1px 0px; text-align: center;"><strong>'.$object->titulo_formatado.'</strong></p>
                <p style="margin: 1px 0px; text-align: center;"> </p>
                <p style="margin: 1px 0px; text-align: justify;">'.$object->texto.'</p>
                <p style="margin: 1px 0px; text-align: justify;">'.$object->cabecalho.'</p>
                <p style="margin: 1px 0px; text-align: justify;">'.$object->rodapé.'</p>
                <hr />

            ';

        });        

        $order_id_transformed = new TAction(array($this, 'onReload'));
        $order_id_transformed->setParameter('order', 'id');
        $column_id_transformed->setAction($order_id_transformed);

        $this->datagrid->addColumn($column_id_transformed);

        // create the datagrid model
        $this->datagrid->createModel();

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Imprimir publicações do processo");
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;

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

        $this->datagrid_form->add($headerActions);

        $button_gerar_pdf = new TButton('button_button_gerar_pdf');
        $button_gerar_pdf->setAction(new TAction(['PrintPublicacaoProcessoList', 'onExportPdf'],['static' => 1]), "Gerar PDF");
        $button_gerar_pdf->addStyleClass('btn-default');
        $button_gerar_pdf->setImage('far:file-pdf #e74c3c');

        $this->datagrid_form->addField($button_gerar_pdf);

        $head_right_actions->add($button_gerar_pdf);

        $this->datagrid_form->add($this->datagrid);


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
     * Register the filter in the session
     */
    public function onSearch($param = null)
    {
        $data = $this->form->getData();
        $filters = [];

        $data = TSession::getValue('PublicacaoHeaderList_filter_data');

        if (isset($data->titulo) AND ((is_scalar($data->titulo) AND $data->titulo !== '') OR (is_array($data->titulo) AND (!empty($data->titulo))))){
            $titulo = $data->titulo;
            $data->titulo = str_replace(' ','%',TratamentosService::removerAcentos($data->titulo));
        }
        if (isset($data->responsavel) AND ( (is_scalar($data->responsavel) AND $data->responsavel !== '') OR (is_array($data->responsavel) AND (!empty($data->responsavel)) )) ){
            $responsavel = $data->responsavel;
            $data->responsavel = str_replace(' ','%',TratamentosService::removerAcentos($data->responsavel));
        } 

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->numero_arquivo) AND ( (is_scalar($data->numero_arquivo) AND $data->numero_arquivo !== '') OR (is_array($data->numero_arquivo) AND (!empty($data->numero_arquivo)) )) )
        {

            $filters[] = new TFilter('numero_arquivo', '=', $data->numero_arquivo);// create the filter 
        }

        if (isset($data->numero_publicacao) AND ( (is_scalar($data->numero_publicacao) AND $data->numero_publicacao !== '') OR (is_array($data->numero_publicacao) AND (!empty($data->numero_publicacao)) )) )
        {

            $filters[] = new TFilter('numero_publicacao', '=', $data->numero_publicacao);// create the filter 
        }

        if (isset($data->titulo) AND ( (is_scalar($data->titulo) AND $data->titulo !== '') OR (is_array($data->titulo) AND (!empty($data->titulo)) )) )
        {

            $filters[] = new TFilter('unaccent(titulo)', 'ilike', "%{$data->titulo}%");// create the filter 
        }

        if (isset($data->numero_unico_processo) AND ( (is_scalar($data->numero_unico_processo) AND $data->numero_unico_processo !== '') OR (is_array($data->numero_unico_processo) AND (!empty($data->numero_unico_processo)) )) )
        {

            $filters[] = new TFilter('numero_unico_processo', 'ilike', "%{$data->numero_unico_processo}%");// create the filter 
        }

        if (isset($data->data_disponibilizacao) AND ( (is_scalar($data->data_disponibilizacao) AND $data->data_disponibilizacao !== '') OR (is_array($data->data_disponibilizacao) AND (!empty($data->data_disponibilizacao)) )) )
        {

            $filters[] = new TFilter('data_disponibilizacao', '=', $data->data_disponibilizacao);// create the filter 
        }

        if (isset($data->prazo) AND ( (is_scalar($data->prazo) AND $data->prazo !== '') OR (is_array($data->prazo) AND (!empty($data->prazo)) )) )
        {

            $filters[] = new TFilter('prazo', '=', $data->prazo);// create the filter 
        }

        if (isset($data->data_entrega) AND ( (is_scalar($data->data_entrega) AND $data->data_entrega !== '') OR (is_array($data->data_entrega) AND (!empty($data->data_entrega)) )) )
        {

            $filters[] = new TFilter('data_entrega', '=', $data->data_entrega);// create the filter 
        }

        if (isset($data->responsavel) AND ( (is_scalar($data->responsavel) AND $data->responsavel !== '') OR (is_array($data->responsavel) AND (!empty($data->responsavel)) )) )
        {

            $filters[] = new TFilter('unaccent(responsavel)', 'ilike', "%{$data->responsavel}%");// create the filter 
        }

        if (isset($data->jornal) AND ( (is_scalar($data->jornal) AND $data->jornal !== '') OR (is_array($data->jornal) AND (!empty($data->jornal)) )) )
        {

            $filters[] = new TFilter('jornal', 'ilike', "%{$data->jornal}%");// create the filter 
        }

        if (isset($data->titulo) AND ( (is_scalar($data->titulo) AND $data->titulo !== '') OR (is_array($data->titulo) AND (!empty($data->titulo)) )) ){
            $data->titulo = $titulo;
        }
        if (isset($data->responsavel) AND ( (is_scalar($data->responsavel) AND $data->responsavel !== '') OR (is_array($data->responsavel) AND (!empty($data->responsavel)) )) ){
            $data->responsavel = $responsavel;
        }

        // fill the form with data again
        $this->form->setData($data);

        // keep the search data in the session
        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
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

            // creates a repository for ViewPublicacao
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

            //</blockLine><btnShowCurtainFiltersAutoCode>
            if(!empty($this->btnShowCurtainFilters) && empty($this->btnShowCurtainFiltersAdjusted))
            {
                $this->btnShowCurtainFiltersAdjusted = true;
                $this->btnShowCurtainFilters->style = 'position: relative';
                $countFilters = count($filters ?? []);
                $this->btnShowCurtainFilters->setLabel($this->btnShowCurtainFilters->getLabel(). "<span class='badge badge-success' style='position: absolute'>{$countFilters}<span>");
            }
            //</blockLine></btnShowCurtainFiltersAutoCode>

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

        $this->onSearch();

        TSession::setValue(__CLASS__.'_filters', TSession::getValue('PublicacaoHeaderList_filters'));
        $this->onReload();
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

        $object = new ViewPublicacao($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

}

