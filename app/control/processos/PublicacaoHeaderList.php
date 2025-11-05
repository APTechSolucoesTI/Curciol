<?php

class PublicacaoHeaderList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'ViewPublicacao';
    private static $primaryKey = 'id';
    private static $formName = 'formList_ViewPublicacao';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    use BuilderDatagridTrait;

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

        $this->limit = 50;

        $criteria_jornal = new TCriteria();

        $responsavel = new TEntry('responsavel');
        $jornal = new TDBCombo('jornal', 'escritorio', 'Jornal', 'nome', '{nome}','nome asc' , $criteria_jornal );
        $numero_processo_principal = new TEntry('numero_processo_principal');
        $titulo = new TEntry('titulo');
        $data_disponibilizacao = new TDate('data_disponibilizacao');
        $numero_arquivo = new TEntry('numero_arquivo');
        $numero_publicacao = new TEntry('numero_publicacao');
        $prazo = new TDate('prazo');
        $data_entrega = new TDate('data_entrega');
        $global_filter = new TEntry('global_filter');

        $responsavel->exitOnEnter();
        $numero_processo_principal->exitOnEnter();
        $titulo->exitOnEnter();
        $numero_arquivo->exitOnEnter();
        $numero_publicacao->exitOnEnter();

        $global_filter->setEnterAction(new TAction([$this, 'onGlobalSearch']));

        $responsavel->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $numero_processo_principal->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $titulo->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $data_disponibilizacao->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $numero_arquivo->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $numero_publicacao->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $prazo->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));
        $data_entrega->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $jornal->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1', 'target_container' => $param['target_container'] ?? null]));

        $jornal->enableSearch();
        $global_filter->setInnerIcon(new TImage('fas:search #9E9E9E'), 'left');
        $responsavel->forceUpperCase();
        $global_filter->forceUpperCase();

        $prazo->setMask('dd/mm/yyyy');
        $data_entrega->setMask('dd/mm/yyyy');
        $data_disponibilizacao->setMask('dd/mm/yyyy');

        $prazo->setDatabaseMask('yyyy-mm-dd');
        $data_entrega->setDatabaseMask('yyyy-mm-dd');
        $data_disponibilizacao->setDatabaseMask('yyyy-mm-dd');

        $prazo->setSize('100%');
        $jornal->setSize('100%');
        $titulo->setSize('100%');
        $data_entrega->setSize(110);
        $global_filter->setSize(200);
        $responsavel->setSize('100%');
        $numero_arquivo->setSize('100%');
        $numero_publicacao->setSize('100%');
        $data_disponibilizacao->setSize('100%');
        $numero_processo_principal->setSize('100%');

        // creates a Datagrid
        $this->datagrid = new TDataGrid;
        $this->datagrid->enableUserProperties('fa fa-cog', 'btn btn-default', new TAction([$this, 'setDatagridProperties']));
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm(self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid_form->addField($global_filter);
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $column_responsavel = new TDataGridColumn('responsavel', "Responsável", 'left');
        $column_jornal = new TDataGridColumn('jornal', "Jornal", 'left');
        $column_processo_id_transformed = new TDataGridColumn('processo_id', "", 'left');
        $column_numero_unico_processo = new TDataGridColumn('numero_unico_processo', "Número do processo", 'left');
        $column_titulo_transformed = new TDataGridColumn('titulo', "Título", 'left');
        $column_data_disponibilizacao_transformed = new TDataGridColumn('data_disponibilizacao', "Data da disponibilização", 'left');
        $column_numero_arquivo = new TDataGridColumn('numero_arquivo', "Número do arquivo", 'left');
        $column_numero_publicacao = new TDataGridColumn('numero_publicacao', "Número da publicação", 'left');
        $column_prazo_transformed = new TDataGridColumn('prazo', "Prazo", 'left');
        $column_data_entrega_transformed = new TDataGridColumn('data_entrega', "Data de entrega", 'left');
        $column_id_transformed = new TDataGridColumn('id', "Status", 'left');

        $column_processo_id_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if ($value==null) {
                $cor = "#5B5B5B";
            }else{
                $cor = "#4CAF50";
            }

            return "<div style='position: relative;text-align: center;'><span class='estado_agendamento' style='background-color: {$cor}'></span></div>";

        });

        $column_titulo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return substr(str_replace(";","<br/>",$value),0,200);

        });

        $column_data_disponibilizacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_prazo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $column_id_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $cor = "#FFFFFFFF";
            $texto = "";
            $span2 = "";

            $qtdeTarefas = Tarefa::where('publicacao_id','=',$object->id)->count();    

            if($object->data_entrega){
                $cor = "#D1FFD1";
                $texto = "Tarefa concluída"; 
            }elseif(!$object->prazo && $object->confirma_prazo=="S"){
                $cor = "#EDE6DB";
                $texto = "Sem prazo";
            }elseif(!$object->prazo){
                $cor = "#FFFAA6";
                $texto = "Não tratado";
            }elseif($object->prazo && $qtdeTarefas == 0){
                $cor = "#FFCECE";
                $texto = "Adicionar tarefa";
            }elseif($object->prazo && $qtdeTarefas > 0){
                $cor = "#DEE9FF";
                $texto = "Em andamento";
            }

            if(!$object->data_entrega && $object->prazo && $object->prazo<date('Y-m-d')){
                $span2 = "<br/><span class='label' style='color:#B800B8;width:120px;background-color:#FEBCFF'> Prazo expirado <span> ";
            }elseif(!$object->data_entrega && $object->prazo && $object->prazo>=date('Y-m-d') && $object->prazo<=date('Y-m-d', strtotime("+5 days",strtotime(date('Y-m-d'))))){
                $span2 = "<br/><span class='label' style='color:#FF7C00;width:120px;background-color:#FFDDBD'> Prazo a expirar <span> ";
            }    

            $span = "<span class='label' style='color:#000000;width:120px;background-color:$cor;'> {$texto} </span> " . $span2;

            return $span;
        });        

        $order_responsavel = new TAction(array($this, 'onReload'));
        $order_responsavel->setParameter('order', 'responsavel');
        $column_responsavel->setAction($order_responsavel);
        $order_jornal = new TAction(array($this, 'onReload'));
        $order_jornal->setParameter('order', 'jornal');
        $column_jornal->setAction($order_jornal);
        $order_numero_unico_processo = new TAction(array($this, 'onReload'));
        $order_numero_unico_processo->setParameter('order', 'numero_unico_processo');
        $column_numero_unico_processo->setAction($order_numero_unico_processo);
        $order_titulo_transformed = new TAction(array($this, 'onReload'));
        $order_titulo_transformed->setParameter('order', 'titulo');
        $column_titulo_transformed->setAction($order_titulo_transformed);
        $order_data_disponibilizacao_transformed = new TAction(array($this, 'onReload'));
        $order_data_disponibilizacao_transformed->setParameter('order', 'data_disponibilizacao');
        $column_data_disponibilizacao_transformed->setAction($order_data_disponibilizacao_transformed);
        $order_numero_arquivo = new TAction(array($this, 'onReload'));
        $order_numero_arquivo->setParameter('order', 'numero_arquivo');
        $column_numero_arquivo->setAction($order_numero_arquivo);
        $order_numero_publicacao = new TAction(array($this, 'onReload'));
        $order_numero_publicacao->setParameter('order', 'numero_publicacao');
        $column_numero_publicacao->setAction($order_numero_publicacao);
        $order_prazo_transformed = new TAction(array($this, 'onReload'));
        $order_prazo_transformed->setParameter('order', 'prazo');
        $column_prazo_transformed->setAction($order_prazo_transformed);
        $order_data_entrega_transformed = new TAction(array($this, 'onReload'));
        $order_data_entrega_transformed->setParameter('order', 'data_entrega');
        $column_data_entrega_transformed->setAction($order_data_entrega_transformed);
        $order_id_transformed = new TAction(array($this, 'onReload'));
        $order_id_transformed->setParameter('order', 'id');
        $column_id_transformed->setAction($order_id_transformed);

        $this->datagrid->addColumn($column_responsavel);
        $this->datagrid->addColumn($column_jornal);
        $this->datagrid->addColumn($column_processo_id_transformed);
        $this->datagrid->addColumn($column_numero_unico_processo);
        $this->datagrid->addColumn($column_titulo_transformed);
        $this->datagrid->addColumn($column_data_disponibilizacao_transformed);
        $this->datagrid->addColumn($column_numero_arquivo);
        $this->datagrid->addColumn($column_numero_publicacao);
        $this->datagrid->addColumn($column_prazo_transformed);
        $this->datagrid->addColumn($column_data_entrega_transformed);
        $this->datagrid->addColumn($column_id_transformed);

        $action_onShow = new TDataGridAction(array('PublicacaoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField(self::$primaryKey);

        $this->datagrid->addAction($action_onShow);

        TTransaction::open(self::$database);
        $nConfirmado = Publicacao::where('prazo','is',null)->where('confirma_prazo','=','N')->count() ?? 0;

        $publicacoes = Publicacao::where('prazo','is not',null)->where('data_entrega','is',null)->load();
        $sTarefa = count($publicacoes);
        $cTarefa = 0;

        $expirados = 0;
        $aExpirar = 0;

        foreach($publicacoes as $publicacao){
            if($publicacao->prazo < date('Y-m-d')){
                $expirados++;
            }elseif($publicacao->prazo>=date('Y-m-d') && ($publicacao->prazo<=date('Y-m-d', strtotime("+5 days",strtotime(date('Y-m-d')))))){
                $aExpirar++;
            }
            $publicacao_tarefa = Tarefa::where('publicacao_id','=',$publicacao->id)->first();
            if($publicacao_tarefa){
                $sTarefa--;
                if($publicacao_tarefa->publicacao_id == $publicacao->id){
                    $cTarefa++;
                }
            }

        }
        TTransaction::close();

        $this->applyDatagridProperties();

        // create the datagrid model
        $this->datagrid->createModel();

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);

        if(!$action_onShow->isHidden())
        {
            $tr->add(TElement::tag('td', ''));
        }
        $td_responsavel = TElement::tag('td', $responsavel);
        $tr->add($td_responsavel);
        $td_jornal = TElement::tag('td', $jornal);
        $tr->add($td_jornal);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_numero_processo_principal = TElement::tag('td', $numero_processo_principal);
        $tr->add($td_numero_processo_principal);
        $td_titulo = TElement::tag('td', $titulo);
        $tr->add($td_titulo);
        $td_data_disponibilizacao = TElement::tag('td', $data_disponibilizacao);
        $tr->add($td_data_disponibilizacao);
        $td_numero_arquivo = TElement::tag('td', $numero_arquivo);
        $tr->add($td_numero_arquivo);
        $td_numero_publicacao = TElement::tag('td', $numero_publicacao);
        $tr->add($td_numero_publicacao);
        $td_prazo = TElement::tag('td', $prazo);
        $tr->add($td_prazo);
        $td_data_entrega = TElement::tag('td', $data_entrega);
        $tr->add($td_data_entrega);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $tr->add(TElement::tag('td', ''));

        $this->datagrid_form->addField($responsavel);
        $this->datagrid_form->addField($jornal);
        $this->datagrid_form->addField($numero_processo_principal);
        $this->datagrid_form->addField($titulo);
        $this->datagrid_form->addField($data_disponibilizacao);
        $this->datagrid_form->addField($numero_arquivo);
        $this->datagrid_form->addField($numero_publicacao);
        $this->datagrid_form->addField($prazo);
        $this->datagrid_form->addField($data_entrega);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;
        $panel->getHeader()->style = ' display:none !important; ';
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

        $button_buscar = new TButton('button_button_buscar');
        $button_buscar->setAction(new TAction(['PublicacaoHeaderList', 'onGlobalSearch']), "Buscar");
        $button_buscar->addStyleClass('btn-default');
        $button_buscar->setImage('fas:search #9E9E9E');

        $this->datagrid_form->addField($button_buscar);

        $button_nao_tratado_nconfirmado = new TButton('button_button_nao_tratado_nconfirmado');
        $button_nao_tratado_nconfirmado->setAction(new TAction(['PublicacaoHeaderList', 'onFilterNConfirmado']), "Não tratado ($nConfirmado)");
        $button_nao_tratado_nconfirmado->addStyleClass('publicacao_nao_tratado');
        $button_nao_tratado_nconfirmado->setImage('fas:filter #000000');
        $button_nao_tratado_nconfirmado->getAction()->setParameter("nConfirmado", $nConfirmado);

        $this->datagrid_form->addField($button_nao_tratado_nconfirmado);

        $button_adicionar_tarefa_starefa = new TButton('button_button_adicionar_tarefa_starefa');
        $button_adicionar_tarefa_starefa->setAction(new TAction(['PublicacaoHeaderList', 'onFilterAddTarefa']), "Adicionar tarefa ($sTarefa)");
        $button_adicionar_tarefa_starefa->addStyleClass('publicacao_adicionar_tarefa');
        $button_adicionar_tarefa_starefa->setImage('fas:filter #000000');
        $button_adicionar_tarefa_starefa->getAction()->setParameter("sTarefa", $sTarefa);

        $this->datagrid_form->addField($button_adicionar_tarefa_starefa);

        $button_em_andamento_ctarefa = new TButton('button_button_em_andamento_ctarefa');
        $button_em_andamento_ctarefa->setAction(new TAction(['PublicacaoHeaderList', 'onFilterAndamento']), "Em andamento ($cTarefa)");
        $button_em_andamento_ctarefa->addStyleClass('publicacao_em_andamento');
        $button_em_andamento_ctarefa->setImage('fas:filter #000000');
        $button_em_andamento_ctarefa->getAction()->setParameter("cTarefa", $cTarefa);

        $this->datagrid_form->addField($button_em_andamento_ctarefa);

        $button_prazos_expirados_expirados = new TButton('button_button_prazos_expirados_expirados');
        $button_prazos_expirados_expirados->setAction(new TAction(['PublicacaoHeaderList', 'onFilterExpirados']), "Prazos expirados ($expirados)");
        $button_prazos_expirados_expirados->addStyleClass('publicacao_prazo_expirado');
        $button_prazos_expirados_expirados->setImage('fas:filter #000000');
        $button_prazos_expirados_expirados->getAction()->setParameter("expirados", $expirados);

        $this->datagrid_form->addField($button_prazos_expirados_expirados);

        $button_prazo_a_expirar_em_5_dias_aexpirar = new TButton('button_button_prazo_a_expirar_em_5_dias_aexpirar');
        $button_prazo_a_expirar_em_5_dias_aexpirar->setAction(new TAction(['PublicacaoHeaderList', 'onFilterAExpirar']), "Prazo a expirar em 5 dias ($aExpirar)");
        $button_prazo_a_expirar_em_5_dias_aexpirar->addStyleClass('publicacao_a_expirar');
        $button_prazo_a_expirar_em_5_dias_aexpirar->setImage('fas:filter #000000');
        $button_prazo_a_expirar_em_5_dias_aexpirar->getAction()->setParameter("aExpirar", $aExpirar);

        $this->datagrid_form->addField($button_prazo_a_expirar_em_5_dias_aexpirar);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['PublicacaoHeaderList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['PublicacaoHeaderList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $button_imprimir = new TButton('button_button_imprimir');
        $button_imprimir->setAction(new TAction(['PrintPublicacaoProcessoList', 'onShow']), "Imprimir");
        $button_imprimir->addStyleClass('btn-default');
        $button_imprimir->setImage('fas:print #000000');

        $this->datagrid_form->addField($button_imprimir);

        $button_sincronizar = new TButton('button_button_sincronizar');
        $button_sincronizar->setAction(new TAction(['SincronizarPublicacoesAPIForm', 'onShow']), "Sincronizar");
        $button_sincronizar->addStyleClass('btn-default');
        $button_sincronizar->setImage('fas:spinner #000000');

        $this->datagrid_form->addField($button_sincronizar);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['PublicacaoHeaderList', 'onExportCsv'],['static' => 1]), self::$formName, 'fas:file-csv #00b894' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['PublicacaoHeaderList', 'onExportXls'],['static' => 1]), self::$formName, 'fas:file-excel #4CAF50' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['PublicacaoHeaderList', 'onExportPdf'],['static' => 1]), self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XML", new TAction(['PublicacaoHeaderList', 'onExportXml'],['static' => 1]), self::$formName, 'far:file-code #95a5a6' );

        $head_left_actions->add($button_nao_tratado_nconfirmado);
        $head_left_actions->add($button_adicionar_tarefa_starefa);
        $head_left_actions->add($button_em_andamento_ctarefa);
        $head_left_actions->add($button_prazos_expirados_expirados);
        $head_left_actions->add($button_prazo_a_expirar_em_5_dias_aexpirar);
        $head_left_actions->add($button_limpar_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($button_imprimir);
        $head_left_actions->add($button_sincronizar);
        $head_left_actions->add($dropdown_button_exportar);

        $head_right_actions->add($global_filter);
        $head_right_actions->add($button_buscar);

        $this->datagrid_form->add($this->datagrid);

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Processos","Publicações"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    public function onGlobalSearch($param = null) 
    {

                $param['globalSearch'] = true;
        $this->onSearch($param);

    }
    public function onFilterNConfirmado($param = null) 
    {
        try 
        {
            if($param['nConfirmado']>0){
                TTransaction::open(self::$database);

                $filters = [];
                $filters[] = new TFilter('prazo', 'is', null);
                $filters[] = new TFilter('confirma_prazo', '=', "N");
                $filters[] = new TFilter('data_entrega','is',null);

                TSession::setValue(__CLASS__.'_filters', $filters);

                $this->onReload(['offset' => 0, 'first_page' => 1]);

                TTransaction::close();
            }

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onFilterAddTarefa($param = null) 
    {
        try 
        {
            if($param['sTarefa']>0){
                TTransaction::open(self::$database);

                $filters = [];
                $filters[] = new TFilter('prazo', 'is not', null);

                $publicacoes = Publicacao::where('prazo','is not',null)->where('data_entrega','is',null)->load();

                $ids = [];
                foreach($publicacoes as $publicacao){
                    $ids[$publicacao->id] = $publicacao->id;
                }

                $publicacao_tarefas = Tarefa::where('publicacao_id','in',$ids)->load();
                foreach($publicacao_tarefas as $tarefa){
                    unset($ids[$tarefa->publicacao_id]);
                }

                $filters[] = new TFilter('id', 'in', $ids);

                TSession::setValue(__CLASS__.'_filters', $filters);

                $this->onReload(['offset' => 0, 'first_page' => 1]);

                TTransaction::close();
            }

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onFilterAndamento($param = null) 
    {
        try 
        {
            if($param['cTarefa']>0){
                TTransaction::open(self::$database);

                $filters = [];
                $filters[] = new TFilter('prazo', 'is not', null);

                $publicacoes = Publicacao::where('prazo','is not',null)->where('data_entrega','is',null)->load();

                $idsConsulta = [];
                foreach($publicacoes as $publicacao){
                    $idsConsulta[$publicacao->id] = $publicacao->id;
                }

                $publicacao_tarefas = Tarefa::where('publicacao_id','in',$idsConsulta)->load();

                foreach($publicacao_tarefas as $tarefa){
                    $ids[$tarefa->publicacao_id] = $tarefa->publicacao_id;
                }

                $filters[] = new TFilter('id', 'in', $ids);

                TSession::setValue(__CLASS__.'_filters', $filters);

                $this->onReload(['offset' => 0, 'first_page' => 1]);

                TTransaction::close();
            }

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onFilterExpirados($param = null) 
    {
        try 
        {
            if($param['expirados']>0){
                TTransaction::open(self::$database);

                $filters = [];
                $filters[] = new TFilter('prazo', 'is not', null);

                $publicacoes = Publicacao::where('prazo','is not',null)->where('data_entrega','is',null)->load();

                $ids = [];
                foreach($publicacoes as $publicacao){
                    if($publicacao->prazo < date('Y-m-d')){
                        $ids[$publicacao->id] = $publicacao->id;
                    }
                }

                $filters[] = new TFilter('id', 'in', $ids);

                TSession::setValue(__CLASS__.'_filters', $filters);

                $this->onReload(['offset' => 0, 'first_page' => 1]);

                TTransaction::close();
            }

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onFilterAExpirar($param = null) 
    {
        try 
        {
            if($param['aExpirar']>0){
                TTransaction::open(self::$database);

                $filters = [];
                $filters[] = new TFilter('prazo', 'is not', null);

                $publicacoes = Publicacao::where('prazo','is not',null)->where('data_entrega','is',null)->load();

                $ids = [];
                foreach($publicacoes as $publicacao){
                    if($publicacao->prazo>=date('Y-m-d') && ($publicacao->prazo<=date('Y-m-d', strtotime("+5 days",strtotime(date('Y-m-d')))))){
                        $ids[$publicacao->id] = $publicacao->id;
                    }
                }

                $filters[] = new TFilter('id', 'in', $ids);

                TSession::setValue(__CLASS__.'_filters', $filters);

                $this->onReload(['offset' => 0, 'first_page' => 1]);

                TTransaction::close();
            }

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onClearFilters($param = null) 
    {
        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }
    public function onRefresh($param = null) 
    {
        $this->onReload([]);
    }
    public function onExportCsv($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.csv';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $objects = $this->onReload();

                if ($objects)
                {
                    $handler = fopen($output, 'w');
                    TTransaction::open(self::$database);

                    foreach ($objects as $object)
                    {
                        $row = [];
                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();

                            if (isset($object->$column_name))
                            {
                                $row[] = is_scalar($object->$column_name) ? $object->$column_name : '';
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $row[] = $object->render($column_name);
                            }
                        }

                        fputcsv($handler, $row);
                    }

                    fclose($handler);
                    TTransaction::close();
                }
                else
                {
                    throw new Exception(_t('No records found'));
                }

                TPage::openFile($output);
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
    public function onExportXls($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.xls';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $widths = [];
                $titles = [];

                foreach ($this->datagrid->getColumns() as $column)
                {
                    $titles[] = $column->getLabel();
                    $width    = 100;

                    if (is_null($column->getWidth()))
                    {
                        $width = 100;
                    }
                    else if (strpos((string)$column->getWidth(), '%') !== false)
                    {
                        $width = ((int) $column->getWidth()) * 5;
                    }
                    else if (is_numeric($column->getWidth()))
                    {
                        $width = $column->getWidth();
                    }

                    $widths[] = $width;
                }

                $table = new \TTableWriterXLS($widths);
                $table->addStyle('title',  'Helvetica', '10', 'B', '#ffffff', '#617FC3');
                $table->addStyle('data',   'Helvetica', '10', '',  '#000000', '#FFFFFF', 'LR');

                $table->addRow();

                foreach ($titles as $title)
                {
                    $table->addCell($title, 'center', 'title');
                }

                $this->limit = 0;
                $objects = $this->onReload();

                TTransaction::open(self::$database);
                if ($objects)
                {
                    foreach ($objects as $object)
                    {
                        $table->addRow();
                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();
                            $value = '';
                            if (isset($object->$column_name))
                            {
                                $value = is_scalar($object->$column_name) ? $object->$column_name : '';
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $value = $object->render($column_name);
                            }

                            $transformer = $column->getTransformer();
                            if ($transformer)
                            {
                                $value = strip_tags((string)call_user_func($transformer, $value, $object, null));
                            }

                            $table->addCell($value, 'center', 'data');
                        }
                    }
                }
                $table->save($output);
                TTransaction::close();

                TPage::openFile($output);
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
    public function onExportXml($param = null) 
    {
        try
        {
            $output = 'app/output/'.uniqid().'.xml';

            if ( (!file_exists($output) && is_writable(dirname($output))) OR is_writable($output))
            {
                $this->limit = 0;
                $objects = $this->onReload();

                if ($objects)
                {
                    TTransaction::open(self::$database);

                    $dom = new DOMDocument('1.0', 'UTF-8');
                    $dom->{'formatOutput'} = true;
                    $dataset = $dom->appendChild( $dom->createElement('dataset') );

                    foreach ($objects as $object)
                    {
                        $row = $dataset->appendChild( $dom->createElement( self::$activeRecord ) );

                        foreach ($this->datagrid->getColumns() as $column)
                        {
                            $column_name = $column->getName();
                            $column_name_raw = str_replace(['(','{','->', '-','>','}',')', ' '], ['','','_','','','','','_'], $column_name);

                            if (isset($object->$column_name))
                            {
                                $value = is_scalar($object->$column_name) ? $object->$column_name : '';
                                $row->appendChild($dom->createElement($column_name_raw, $value)); 
                            }
                            else if (method_exists($object, 'render'))
                            {
                                $column_name = (strpos((string)$column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
                                $value = $object->render($column_name);
                                $row->appendChild($dom->createElement($column_name_raw, $value));
                            }
                        }
                    }

                    $dom->save($output);

                    TTransaction::close();
                }
                else
                {
                    throw new Exception(_t('No records found'));
                }

                TPage::openFile($output);
            }
            else
            {
                throw new Exception(_t('Permission denied') . ': ' . $output);
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
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

        if (isset($data->responsavel) AND ( (is_scalar($data->responsavel) AND $data->responsavel !== '') OR (is_array($data->responsavel) AND (!empty($data->responsavel)) )) )
        {

            $filters[] = new TFilter('unaccent(responsavel)', 'ilike', "%{$data->responsavel}%");// create the filter 
        }

        if (isset($data->jornal) AND ( (is_scalar($data->jornal) AND $data->jornal !== '') OR (is_array($data->jornal) AND (!empty($data->jornal)) )) )
        {

            $filters[] = new TFilter('jornal', 'ilike', "%{$data->jornal}%");// create the filter 
        }

        if (isset($data->numero_processo_principal) AND ( (is_scalar($data->numero_processo_principal) AND $data->numero_processo_principal !== '') OR (is_array($data->numero_processo_principal) AND (!empty($data->numero_processo_principal)) )) )
        {

            $filters[] = new TFilter('numero_unico_processo', 'ilike', "%{$data->numero_processo_principal}%");// create the filter 
        }

        if (isset($data->titulo) AND ( (is_scalar($data->titulo) AND $data->titulo !== '') OR (is_array($data->titulo) AND (!empty($data->titulo)) )) )
        {

            $filters[] = new TFilter('unaccent(titulo)', 'ilike', "%{$data->titulo}%");// create the filter 
        }

        if (isset($data->data_disponibilizacao) AND ( (is_scalar($data->data_disponibilizacao) AND $data->data_disponibilizacao !== '') OR (is_array($data->data_disponibilizacao) AND (!empty($data->data_disponibilizacao)) )) )
        {

            $filters[] = new TFilter('data_disponibilizacao', '=', $data->data_disponibilizacao);// create the filter 
        }

        if (isset($data->numero_arquivo) AND ( (is_scalar($data->numero_arquivo) AND $data->numero_arquivo !== '') OR (is_array($data->numero_arquivo) AND (!empty($data->numero_arquivo)) )) )
        {

            $filters[] = new TFilter('numero_arquivo', '=', $data->numero_arquivo);// create the filter 
        }

        if (isset($data->numero_publicacao) AND ( (is_scalar($data->numero_publicacao) AND $data->numero_publicacao !== '') OR (is_array($data->numero_publicacao) AND (!empty($data->numero_publicacao)) )) )
        {

            $filters[] = new TFilter('numero_publicacao', '=', $data->numero_publicacao);// create the filter 
        }

        if (isset($data->prazo) AND ( (is_scalar($data->prazo) AND $data->prazo !== '') OR (is_array($data->prazo) AND (!empty($data->prazo)) )) )
        {

            $filters[] = new TFilter('prazo', '=', $data->prazo);// create the filter 
        }

        if (isset($data->data_entrega) AND ( (is_scalar($data->data_entrega) AND $data->data_entrega !== '') OR (is_array($data->data_entrega) AND (!empty($data->data_entrega)) )) )
        {

            $filters[] = new TFilter('data_entrega', '=', $data->data_entrega);// create the filter 
        }

        if (isset($data->global_filter) AND ( (is_scalar($data->global_filter) AND $data->global_filter !== '') OR (is_array($data->global_filter) AND (!empty($data->global_filter)) )) )
        {
            $globalCriteria = new TCriteria();

            $globalCriteria->add(new TFilter('unaccent(responsavel)', 'ilike', "%{$data->global_filter}%"), ' OR ');

            $globalCriteria->add(new TFilter('numero_unico_processo', 'ilike', "%{$data->global_filter}%"), ' OR ');

            $filters[] = $globalCriteria;
        }

         if (isset($data->titulo) AND ( (is_scalar($data->titulo) AND $data->titulo !== '') OR (is_array($data->titulo) AND (!empty($data->titulo)) )) ){
            $data->titulo = $titulo;
        }
        if (isset($data->responsavel) AND ( (is_scalar($data->responsavel) AND $data->responsavel !== '') OR (is_array($data->responsavel) AND (!empty($data->responsavel)) )) ){
            $data->responsavel = $responsavel;
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
            if(!empty($data->global_filter)){
                TScript::create('$("[name=global_filter]").focus().each(function() {
                    if (this.setSelectionRange) {
                        this.setSelectionRange(this.value.length, this.value.length);
                    }
                });');
            }
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

            // creates a repository for ViewPublicacao
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'data_disponibilizacao';    
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

            $criteria->setProperty('order', '
                CASE 
                    WHEN processo_id IS NULL THEN 0
                    ELSE 1
                END,
                CASE 
                    WHEN processo_id IS NOT NULL AND responsavel IS NULL THEN 1
                    ELSE 2
                END,
                data_disponibilizacao desc,
                numero_unico_processo
            ');

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

