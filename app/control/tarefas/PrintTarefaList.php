<?php

use Dompdf\Dompdf;
use Dompdf\Options;

class PrintTarefaList extends TWindow
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Tarefa';
    private static $primaryKey = 'id';
    private static $formName = 'form_TarefaList';
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
        parent::setTitle("Tarefas");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);

        // define the form title
        $this->form->setFormTitle("Tarefas");
        $this->limit = 20;

        $criteria_tarefa_status_id = new TCriteria();
        $criteria_usuario_destinatario_id = new TCriteria();

        $filterVar = "Y";
        $criteria_usuario_destinatario_id->add(new TFilter('active', '=', $filterVar)); 

        $arquivado = new TCheckGroup('arquivado');
        $data_entrega_de = new TDate('data_entrega_de');
        $label_ate_dt_entrega = new TLabel("até", null, '12px', null);
        $data_entrega_ate = new TDate('data_entrega_ate');
        $titulo = new TEntry('titulo');
        $tarefa_status_id = new TDBCombo('tarefa_status_id', 'escritorio', 'TarefaStatus', 'id', '{nome}','nome asc' , $criteria_tarefa_status_id );
        $usuario_destinatario_id = new TDBCombo('usuario_destinatario_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_usuario_destinatario_id );
        $prazo_processual = new TCheckGroup('prazo_processual');
        $data_disponibilizacao_de = new TDate('data_disponibilizacao_de');
        $data_disponibilizacao_ate = new TDate('data_disponibilizacao_ate');
        $prazo_validacao_de = new TDate('prazo_validacao_de');
        $label_ate_prazo_validacao = new TLabel("até", null, '12px', null);
        $prazo_validacao_ate = new TDate('prazo_validacao_ate');
        $prazo_entrega_de = new TDate('prazo_entrega_de');
        $prazo_entrega_ate = new TDate('prazo_entrega_ate');


        $titulo->setMaxLength(255);
        $prazo_processual->addItems(["S"=>"Sim","N"=>"Não"]);
        $arquivado->addItems(["N"=>"Não arquivadas","S"=>"Arquivadas"]);

        $arquivado->setLayout('horizontal');
        $prazo_processual->setLayout('horizontal');

        $arquivado->setUseButton();
        $prazo_processual->setUseButton();

        $label_ate_dt_entrega->setId("label_ate_dt_entrega");
        $label_ate_prazo_validacao->setId("label_ate_prazo_validacao");

        $tarefa_status_id->enableSearch();
        $usuario_destinatario_id->enableSearch();

        $data_entrega_de->setMask('dd/mm/yyyy');
        $data_entrega_ate->setMask('dd/mm/yyyy');
        $prazo_entrega_de->setMask('dd/mm/yyyy');
        $prazo_entrega_ate->setMask('dd/mm/yyyy');
        $prazo_validacao_de->setMask('dd/mm/yyyy');
        $prazo_validacao_ate->setMask('dd/mm/yyyy');
        $data_disponibilizacao_de->setMask('dd/mm/yyyy');
        $data_disponibilizacao_ate->setMask('dd/mm/yyyy');

        $data_entrega_de->setDatabaseMask('yyyy-mm-dd');
        $data_entrega_ate->setDatabaseMask('yyyy-mm-dd');
        $prazo_entrega_de->setDatabaseMask('yyyy-mm-dd');
        $prazo_entrega_ate->setDatabaseMask('yyyy-mm-dd');
        $prazo_validacao_de->setDatabaseMask('yyyy-mm-dd');
        $prazo_validacao_ate->setDatabaseMask('yyyy-mm-dd');
        $data_disponibilizacao_de->setDatabaseMask('yyyy/mm/dd');
        $data_disponibilizacao_ate->setDatabaseMask('yyyy-mm-dd');

        $titulo->setSize('100%');
        $arquivado->setSize('100%');
        $prazo_processual->setSize(80);
        $data_entrega_de->setSize('35%');
        $data_entrega_ate->setSize('35%');
        $prazo_entrega_de->setSize('35%');
        $tarefa_status_id->setSize('100%');
        $prazo_entrega_ate->setSize('35%');
        $prazo_validacao_de->setSize('35%');
        $prazo_validacao_ate->setSize('35%');
        $usuario_destinatario_id->setSize('100%');
        $data_disponibilizacao_de->setSize('35%');
        $data_disponibilizacao_ate->setSize('35%');

        $row1 = $this->form->addFields([new TLabel("Ver tarefas:", null, '12px', null, '100%'),$arquivado],[new TLabel("Data de entrega:", null, '12px', null, '100%'),$data_entrega_de,$label_ate_dt_entrega,$data_entrega_ate]);
        $row1->layout = ['col-sm-4','col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Titulo:", null, '12px', null, '100%'),$titulo]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([new TLabel("Status:", null, '12px', null, '100%'),$tarefa_status_id],[new TLabel("Destinatário:", null, '12px', null, '100%'),$usuario_destinatario_id],[new TLabel("Prazo processual:", null, '12px', null, '100%'),$prazo_processual]);
        $row3->layout = ['col-sm-4','col-sm-4',' col-sm-4'];

        $row4 = $this->form->addFields([new TLabel("Data da disponibilização:", null, '12px', null, '100%'),$data_disponibilizacao_de,new TLabel("até", null, '12px', null),$data_disponibilizacao_ate],[new TLabel("Prazo de validação:", null, '12px', null, '100%'),$prazo_validacao_de,$label_ate_prazo_validacao,$prazo_validacao_ate],[new TLabel("Prazo de entrega:", null, '12px', null, '100%'),$prazo_entrega_de,new TLabel("até", null, '12px', null),$prazo_entrega_ate]);
        $row4->layout = ['col-sm-4',' col-sm-4','col-sm-4'];

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

        $this->datagrid->setGroupColumn('usuario_destinatario_id', "<h3> {usuario_destinatario->name}  </h3>");
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(250);
        $this->datagrid->enablePopover("Observação", " {observacao} ");

        $column_id_transformed = new TDataGridColumn('id', " ", 'center' , '70px');

        $column_id_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            $numeroProcesso = $object->get_numero_processo();

            // Transformação do atributo `prazo_processual` em "Sim" ou "Não"
            $prazo = ($object->prazo_processual === 'S') ? 'Sim' : 'Não';

            // Inicialização do HTML de retorno com valores obrigatórios
            $retorno = '
                <p style="margin: 1px 0px; text-align: justify;">
                    <strong>Prazo: </strong>'.
                    implode('/', array_reverse(explode('-', $object->prazo_entrega))).'<br/>
                    <strong>Prazo processual: </strong>'.$prazo.'<br/>';

            if(isset($numeroProcesso)){
                $retorno .= '<strong>Processo: </strong>'.$numeroProcesso.'<br/>';
            }
            $status = $object->get_tarefa_status();
            $retorno .= '
                <strong>Status: </strong>'.$status->nome.'</p><br/>
                <p style="margin: 1px 0px; text-align: justify;">'.$object->titulo.'</p><br/>
                <p style="margin: 1px 0px; text-align: justify;">'.$object->observacao.'</p>
                <hr/>
            ';

            return $retorno;
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

        $panel = new TPanelGroup("Tarefas");
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

        $button_imprimir = new TButton('button_button_imprimir');
        $button_imprimir->setAction(new TAction(['PrintTarefaList', 'onExportPdf']), "Imprimir");
        $button_imprimir->addStyleClass('btn-default');
        $button_imprimir->setImage('far:file-pdf #e74c3c');

        $this->datagrid_form->addField($button_imprimir);

        $head_right_actions->add($button_imprimir);

        $this->datagrid_form->add($this->datagrid);


        parent::add($panel);

    }

    public function onExportPdf($param = null) 
    {
         try {
        $output = 'app/output/'.uniqid().'.pdf';

        if ((!file_exists($output) && is_writable(dirname($output))) || is_writable($output)) {
            $this->limit = 0;
            $this->datagrid->prepareForPrinting();
            $this->onReload();

            $html = clone $this->datagrid;

            // garante HTML com meta charset + fonte DejaVu Sans
            $baseCss = file_get_contents('app/resources/styles-print.html') ?: '';
            // injeta meta charset se não existir
            if (stripos($baseCss, 'charset') === false) {
                $baseCss = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">".$baseCss;
            }
            // força uma fonte unicode (DejaVu Sans) no body
            $cssFonte = "<style>html,body{font-family:'DejaVu Sans', sans-serif;}</style>";

            // conteúdo final
            $contents = $baseCss . $cssFonte . $html->getContents();

            // se por algum motivo vier com outra codificação, converte
            if (!mb_check_encoding($contents, 'UTF-8')) {
                $contents = mb_convert_encoding($contents, 'UTF-8', 'auto');
            }

            // opções do Dompdf
            $options = new Options();
            $options->set('isRemoteEnabled', true);     // se tiver CSS/imagens externas
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isFontSubsetting', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($contents, 'UTF-8');      // <<< importante
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            file_put_contents($output, $dompdf->output());

            $window = TWindow::create('PDF', 0.8, 0.8);
            $object = new TElement('iframe');
            $object->src  = $output;
            $object->type = 'application/pdf';
            $object->style= "width: 100%; height:calc(100% - 10px)";
            $window->add($object);
            $window->show();

            TWindow::closeWindow(parent::getId());
        } else {
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

        $data = TSession::getValue('TarefaList_filter_data');

        if (isset($data->data_disponibilizacao_de) AND ( (is_scalar($data->data_disponibilizacao_de) AND $data->data_disponibilizacao_de !== '') OR (is_array($data->data_disponibilizacao_de) AND (!empty($data->data_disponibilizacao_de)) )) )
        {if(strlen($data->data_disponibilizacao_de)<12 )$data->data_disponibilizacao_de .= " 00:00:00";}

        if (isset($data->data_disponibilizacao_ate) AND ( (is_scalar($data->data_disponibilizacao_ate) AND $data->data_disponibilizacao_ate !== '') OR (is_array($data->data_disponibilizacao_ate) AND (!empty($data->data_disponibilizacao_ate)) )) )
        {if(strlen($data->data_disponibilizacao_ate)<12)$data->data_disponibilizacao_ate .= " 23:59:59";}

        if (isset($data->prazo_entrega_de) AND ( (is_scalar($data->prazo_entrega_de) AND $data->prazo_entrega_de !== '') OR (is_array($data->prazo_entrega_de) AND (!empty($data->prazo_entrega_de)) )) )
        {if(strlen($data->prazo_entrega_de)<12)$data->prazo_entrega_de .= " 00:00:00";}

        if (isset($data->prazo_entrega_ate) AND ( (is_scalar($data->prazo_entrega_ate) AND $data->prazo_entrega_ate !== '') OR (is_array($data->prazo_entrega_ate) AND (!empty($data->prazo_entrega_ate)) )) )
        {if(strlen($data->prazo_entrega_ate)<12)$data->prazo_entrega_ate .= " 23:59:59";}

        if (isset($data->data_entrega_de) AND ( (is_scalar($data->data_entrega_de) AND $data->data_entrega_de !== '') OR (is_array($data->data_entrega_de) AND (!empty($data->data_entrega_de)) )) )
        {if(strlen($data->data_entrega_de)<12)$data->data_entrega_de .= " 00:00:00";}

        if (isset($data->data_entrega_ate) AND ( (is_scalar($data->data_entrega_ate) AND $data->data_entrega_ate !== '') OR (is_array($data->data_entrega_ate) AND (!empty($data->data_entrega_ate)) )) )
        {if(strlen($data->data_entrega_ate)<12)$data->data_entrega_ate .= " 23:59:59";}

        if (isset($data->prazo_validacao_de) AND ( (is_scalar($data->prazo_validacao_de) AND $data->prazo_validacao_de !== '') OR (is_array($data->prazo_validacao_de) AND (!empty($data->prazo_validacao_de)) )) )
        {if(strlen($data->prazo_validacao_de)<12)$data->prazo_validacao_de .= " 00:00:00";}

        if (isset($data->prazo_validacao_ate) AND ( (is_scalar($data->prazo_validacao_ate) AND $data->prazo_validacao_ate !== '') OR (is_array($data->prazo_validacao_ate) AND (!empty($data->prazo_validacao_ate)) )) )
        {if(strlen($data->prazo_validacao_ate)<12)$data->prazo_validacao_ate .= " 23:59:59";}

        TTransaction::open(self::$database);
        if(isset($data->tarefa_status_id_col) AND ( (is_scalar($data->tarefa_status_id_col) AND $data->tarefa_status_id_col !== '') OR (is_array($data->tarefa_status_id_col) AND (!empty($data->tarefa_status_id_col)))))
        {
            if($data->tarefa_status_id_col == (TarefaConfiguracao::find(1))->status_final_id || $data->tarefa_status_id_col == (TarefaConfiguracao::find(1))->status_cancelado_id){
                $data->arquivado = ['S','N'];
                $data->tarefa_status_id = $data->tarefa_status_id_col;
            }
        }
        TTransaction::close();

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->arquivado) AND ( (is_scalar($data->arquivado) AND $data->arquivado !== '') OR (is_array($data->arquivado) AND (!empty($data->arquivado)) )) )
        {

            $filters[] = new TFilter('arquivado', 'in', $data->arquivado);// create the filter 
        }

        if (isset($data->data_entrega_de) AND ( (is_scalar($data->data_entrega_de) AND $data->data_entrega_de !== '') OR (is_array($data->data_entrega_de) AND (!empty($data->data_entrega_de)) )) )
        {

            $filters[] = new TFilter('data_entrega', '>=', $data->data_entrega_de);// create the filter 
        }

        if (isset($data->data_entrega_ate) AND ( (is_scalar($data->data_entrega_ate) AND $data->data_entrega_ate !== '') OR (is_array($data->data_entrega_ate) AND (!empty($data->data_entrega_ate)) )) )
        {

            $filters[] = new TFilter('data_entrega', '<=', $data->data_entrega_ate);// create the filter 
        }

        if (isset($data->titulo) AND ( (is_scalar($data->titulo) AND $data->titulo !== '') OR (is_array($data->titulo) AND (!empty($data->titulo)) )) )
        {

            $filters[] = new TFilter('titulo', 'like', "%{$data->titulo}%");// create the filter 
        }

        if (isset($data->tarefa_status_id) AND ( (is_scalar($data->tarefa_status_id) AND $data->tarefa_status_id !== '') OR (is_array($data->tarefa_status_id) AND (!empty($data->tarefa_status_id)) )) )
        {

            $filters[] = new TFilter('tarefa_status_id', '=', $data->tarefa_status_id);// create the filter 
        }

        if (isset($data->usuario_destinatario_id) AND ( (is_scalar($data->usuario_destinatario_id) AND $data->usuario_destinatario_id !== '') OR (is_array($data->usuario_destinatario_id) AND (!empty($data->usuario_destinatario_id)) )) )
        {

            $filters[] = new TFilter('usuario_destinatario_id', 'in', $data->usuario_destinatario_id);// create the filter 
        }

        if (isset($data->prazo_processual) AND ( (is_scalar($data->prazo_processual) AND $data->prazo_processual !== '') OR (is_array($data->prazo_processual) AND (!empty($data->prazo_processual)) )) )
        {

            $filters[] = new TFilter('prazo_processual', '=', $data->prazo_processual);// create the filter 
        }

        if (isset($data->data_disponibilizacao_de) AND ( (is_scalar($data->data_disponibilizacao_de) AND $data->data_disponibilizacao_de !== '') OR (is_array($data->data_disponibilizacao_de) AND (!empty($data->data_disponibilizacao_de)) )) )
        {

            $filters[] = new TFilter('data_disponibilizacao', '>=', $data->data_disponibilizacao_de);// create the filter 
        }

        if (isset($data->data_disponibilizacao_ate) AND ( (is_scalar($data->data_disponibilizacao_ate) AND $data->data_disponibilizacao_ate !== '') OR (is_array($data->data_disponibilizacao_ate) AND (!empty($data->data_disponibilizacao_ate)) )) )
        {

            $filters[] = new TFilter('data_disponibilizacao', '<=', $data->data_disponibilizacao_ate);// create the filter 
        }

        if (isset($data->prazo_validacao_de) AND ( (is_scalar($data->prazo_validacao_de) AND $data->prazo_validacao_de !== '') OR (is_array($data->prazo_validacao_de) AND (!empty($data->prazo_validacao_de)) )) )
        {

            $filters[] = new TFilter('prazo_validacao', '>=', $data->prazo_validacao_de);// create the filter 
        }

        if (isset($data->prazo_validacao_ate) AND ( (is_scalar($data->prazo_validacao_ate) AND $data->prazo_validacao_ate !== '') OR (is_array($data->prazo_validacao_ate) AND (!empty($data->prazo_validacao_ate)) )) )
        {

            $filters[] = new TFilter('prazo_validacao', '<=', $data->prazo_validacao_ate);// create the filter 
        }

        if (isset($data->prazo_entrega_de) AND ( (is_scalar($data->prazo_entrega_de) AND $data->prazo_entrega_de !== '') OR (is_array($data->prazo_entrega_de) AND (!empty($data->prazo_entrega_de)) )) )
        {

            $filters[] = new TFilter('prazo_entrega', '>=', $data->prazo_entrega_de);// create the filter 
        }

        if (isset($data->prazo_entrega_ate) AND ( (is_scalar($data->prazo_entrega_ate) AND $data->prazo_entrega_ate !== '') OR (is_array($data->prazo_entrega_ate) AND (!empty($data->prazo_entrega_ate)) )) )
        {

            $filters[] = new TFilter('prazo_entrega', '<=', $data->prazo_entrega_ate);// create the filter 
        }

        if(!(isset($data->arquivado) AND ((is_scalar($data->arquivado) AND $data->arquivado !== '') OR (is_array($data->arquivado) AND (!empty($data->arquivado)))))){
            $filters[] = new TFilter('arquivado', 'in', ["N"]);// create the filter 
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

            // creates a repository for Tarefa
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'usuario_destinatario_id, prazo_entrega';    
            }
            elseif($param['order'] != 'usuario_destinatario_id, prazo_entrega')
            {
                $param['order'] = "usuario_destinatario_id, prazo_entrega,{$param['order']}"; 
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

            $master = TarefaUsuarioMaster::where('usuario_master_id','=',TSession::getValue('userid'))->first();

            if(!$master){
                $criteria1 = new TCriteria;
                $criteria1->add(new TFilter('usuario_destinatario_id', '=', TSession::getValue('userid')), TExpression::OR_OPERATOR);
                $criteria1->add(new TFilter('criacao_user_id', '=', TSession::getValue('userid')), TExpression::OR_OPERATOR);
                $criteria->add($criteria1); 
            }

            $data = TSession::getValue(__CLASS__.'_filter_data');
            $filters = TSession::getValue(__CLASS__.'_filters');

            if (isset($data->numero_processo) AND ( (is_scalar($data->numero_processo) AND $data->numero_processo !== '') OR (is_array($data->numero_processo) AND (!empty($data->numero_processo)) )) )
            {
                $criteria1 = new TCriteria;
                $criteria1->add(new TFilter('andamento_id',  'in', "(SELECT id FROM andamento  WHERE processo_id in (SELECT id FROM processo WHERE numero_cnj_numero like '%{$data->numero_processo}%' ))"), TExpression::OR_OPERATOR);
                $criteria1->add(new TFilter('publicacao_id', 'in', "(SELECT id FROM publicacao WHERE processo_id in (SELECT id FROM processo WHERE numero_cnj_numero like '%{$data->numero_processo}%'))"), TExpression::OR_OPERATOR);
                $criteria1->add(new TFilter('publicacao_id', 'in', "(SELECT id FROM publicacao WHERE numero_unico_processo like '%{$data->numero_processo}%')"), TExpression::OR_OPERATOR);
                $criteria1->add(new TFilter('publicacao_id', 'in', "(SELECT id FROM publicacao WHERE numero_processo_principal like '%{$data->numero_processo}%')"), TExpression::OR_OPERATOR);
                $criteria->add($criteria1); 
            }

            //</blockLine><btnShowCurtainFiltersAutoCode>

            //</blockLine></btnShowCurtainFiltersAutoCode>
            if(TSession::getValue('TarefaList'.'builder_datagrid_check'))
            {
                $criteria =  new TCriteria();
                $criteria->add(new TFilter('id','in',TSession::getValue('TarefaList'.'builder_datagrid_check')));
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

        $this->onSearch();

        TSession::setValue(__CLASS__.'_filters', TSession::getValue('TarefaList_filters'));

        $this->onReload();

        $this->onExportPdf();
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

