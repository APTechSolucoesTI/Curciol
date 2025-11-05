<?php

class TarefaList extends TPage
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

    use BuilderDatagridTrait;

    /**
     * Class constructor
     * Creates the page, the form and the listing
     */
    public function __construct($param = null)
    {
        parent::__construct();

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
        $criteria_usuario_destinatario_id_col = new TCriteria();
        $criteria_tarefa_status_id_col = new TCriteria();

        $filterVar = "Y";
        $criteria_usuario_destinatario_id->add(new TFilter('active', '=', $filterVar)); 

        $arquivado = new TCheckGroup('arquivado');
        $data_entrega_de = new TDate('data_entrega_de');
        $label_ate_dt_entrega = new TLabel("até", null, '12px', null);
        $data_entrega_ate = new TDate('data_entrega_ate');
        $titulo = new TEntry('titulo');
        $tarefa_status_id = new TDBCombo('tarefa_status_id', 'escritorio', 'TarefaStatus', 'id', '{nome}','nome asc' , $criteria_tarefa_status_id );
        $usuario_destinatario_id = new TDBMultiSearch('usuario_destinatario_id', 'escritorio', 'SystemUsers', 'id', 'name','name asc' , $criteria_usuario_destinatario_id );
        $prazo_processual = new TCheckGroup('prazo_processual');
        $data_disponibilizacao_de = new TDate('data_disponibilizacao_de');
        $data_disponibilizacao_ate = new TDate('data_disponibilizacao_ate');
        $prazo_validacao_de = new TDate('prazo_validacao_de');
        $label_ate_prazo_validacao = new TLabel("até", null, '12px', null);
        $prazo_validacao_ate = new TDate('prazo_validacao_ate');
        $prazo_entrega_de = new TDate('prazo_entrega_de');
        $prazo_entrega_ate = new TDate('prazo_entrega_ate');
        $usuario_destinatario_id_col = new TDBUniqueSearch('usuario_destinatario_id_col', 'escritorio', 'SystemUsers', 'id', 'name','name asc' , $criteria_usuario_destinatario_id_col );
        $prazo_entrega = new TDate('prazo_entrega');
        $numero_processo = new TEntry('numero_processo');
        $cliente_vinculado_col = new TEntry('cliente_vinculado_col');
        $titulo_col = new TEntry('titulo_col');
        $data_entrega_col = new TDate('data_entrega_col');
        $tarefa_status_id_col = new TDBCombo('tarefa_status_id_col', 'escritorio', 'TarefaStatus', 'id', '{nome}','nome asc' , $criteria_tarefa_status_id_col );

        $numero_processo->exitOnEnter();
        $cliente_vinculado_col->exitOnEnter();
        $titulo_col->exitOnEnter();

        $prazo_entrega->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $numero_processo->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $cliente_vinculado_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $titulo_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $data_entrega_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $usuario_destinatario_id_col->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $tarefa_status_id_col->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

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
        $tarefa_status_id_col->enableSearch();

        $usuario_destinatario_id->setMinLength(3);
        $usuario_destinatario_id_col->setMinLength(2);

        $usuario_destinatario_id->setFilterColumns(["name"]);
        $usuario_destinatario_id_col->setFilterColumns(["name"]);

        $prazo_entrega->setDatabaseMask('yyyy-mm-dd');
        $data_entrega_de->setDatabaseMask('yyyy-mm-dd');
        $data_entrega_ate->setDatabaseMask('yyyy-mm-dd');
        $prazo_entrega_de->setDatabaseMask('yyyy-mm-dd');
        $data_entrega_col->setDatabaseMask('yyyy-mm-dd');
        $prazo_entrega_ate->setDatabaseMask('yyyy-mm-dd');
        $prazo_validacao_de->setDatabaseMask('yyyy-mm-dd');
        $prazo_validacao_ate->setDatabaseMask('yyyy-mm-dd');
        $data_disponibilizacao_de->setDatabaseMask('yyyy/mm/dd');
        $data_disponibilizacao_ate->setDatabaseMask('yyyy-mm-dd');

        $prazo_entrega->setMask('dd/mm/yyyy');
        $data_entrega_de->setMask('dd/mm/yyyy');
        $data_entrega_ate->setMask('dd/mm/yyyy');
        $prazo_entrega_de->setMask('dd/mm/yyyy');
        $data_entrega_col->setMask('dd/mm/yyyy');
        $prazo_entrega_ate->setMask('dd/mm/yyyy');
        $prazo_validacao_de->setMask('dd/mm/yyyy');
        $usuario_destinatario_id->setMask('{name}');
        $prazo_validacao_ate->setMask('dd/mm/yyyy');
        $usuario_destinatario_id_col->setMask('{name}');
        $data_disponibilizacao_de->setMask('dd/mm/yyyy');
        $data_disponibilizacao_ate->setMask('dd/mm/yyyy');

        $titulo->setSize('100%');
        $arquivado->setSize('100%');
        $titulo_col->setSize('100%');
        $prazo_processual->setSize(80);
        $prazo_entrega->setSize('100%');
        $data_entrega_col->setSize(110);
        $data_entrega_de->setSize('35%');
        $data_entrega_ate->setSize('35%');
        $prazo_entrega_de->setSize('35%');
        $numero_processo->setSize('100%');
        $tarefa_status_id->setSize('100%');
        $prazo_entrega_ate->setSize('35%');
        $prazo_validacao_de->setSize('35%');
        $prazo_validacao_ate->setSize('35%');
        $tarefa_status_id_col->setSize('100%');
        $cliente_vinculado_col->setSize('100%');
        $data_disponibilizacao_de->setSize('35%');
        $data_disponibilizacao_ate->setSize('35%');
        $usuario_destinatario_id->setSize('100%', 70);
        $usuario_destinatario_id_col->setSize('100%');

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
        $this->datagrid->enableUserProperties('fa fa-cog', 'btn btn-default', new TAction([$this, 'setDatagridProperties']));
        $this->datagrid->setId(__CLASS__.'_datagrid');

        $this->datagrid_form = new TForm('datagrid_'.self::$formName);
        $this->datagrid_form->onsubmit = 'return false';

        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->filter_criteria = new TCriteria;

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(250);
        $this->datagrid->enablePopover("Observação", " {observacao} ");

        $column_usuario_destinatario_name = new TDataGridColumn('usuario_destinatario->name', "Destinatário", 'left');
        $column_prazo_entrega_transformed = new TDataGridColumn('prazo_entrega', "Prazo", 'left');
        $column_prazo_processual_transformed = new TDataGridColumn('prazo_processual', " ", 'left');
        $column_numero_processo = new TDataGridColumn('numero_processo', "Processo", 'left');
        $column_cliente_vinculado = new TDataGridColumn('cliente_vinculado', "Cliente", 'left');
        $column_titulo_transformed = new TDataGridColumn('titulo', "Titulo", 'left');
        $column_data_entrega_transformed = new TDataGridColumn('data_entrega', "Entrega", 'left');
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

        $column_prazo_processual_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            TTransaction::open('escritorio');

            //Se ele estiver cadastrado como processo principal, significa que tem processos que herdaram dele
            if($value == 'S'){
                return "<div style='position: relative;text-align: center;'><span class='estado_agendamento' style='background-color: #4CAF50'></span></div>";
            }else{
                return "<div style='position: relative;text-align: center;'><span class='estado_agendamento' style='background-color: #5B5B5B'></span></div>";
            }

            TTransaction::close();

        });

        $column_titulo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

           TTransaction::open('escritorio');

            $titulo =   str_replace(";","<br/>",$value);

            $configuracao = TarefaConfiguracao::find(1);
            $status_finais = TarefaStatus::where('fim','=','S')->getIndexedArray('id','id');

            //Buscar se essa é uma subtarefa
            $vinculos = TarefaVinculo::where('subtarefa_id','=',$object->id)->first();
            if($vinculos){
                $titulo .= "<br/><span style='border-radius:2px; background-color:#ff0000; color:#ffffff'> Subtarefa de #".$vinculos->tarefa_id." </span>";
            }
            //SE NÃO FOR UMA SUBTAREFA E NÃO ESTIVER FINALIZADA/CANCELADA
            elseif(array_search($object->tarefa_status_id, $status_finais) === false){

                //Buscar as subtarefas desta tarefa
                $vinculos = TarefaVinculo::where('tarefa_id','=',$object->id)->load();

                //SE TIVER SUBTAREFAS
                if($vinculos){
                    $subtarefasFinalizadas = 0;
                    $subtarefasNFinalizadas = count($vinculos);
                    foreach($vinculos as $vinculo){
                        $subtarefa = Tarefa::find($vinculo->subtarefa_id);
                        if($subtarefa->tarefa_status->fim == 'S'){
                            $subtarefasFinalizadas++;
                            $subtarefasNFinalizadas--;
                        }
                        if($object->tarefa_status_id == $configuracao->status_cancelado_id){
                            $subtarefasFinalizadas--;
                            $subtarefasNFinalizadas--;
                        }
                    }
                    if($subtarefasFinalizadas == count($vinculos)){
                        if($object->tarefa_status_id != $configuracao->status_final_id){
                            $titulo .= "<br/><span style='border-radius:2px; background-color:".$configuracao->status_final->cor."; color:#ffffff'> Subtarefas finalizadas </span>";
                        }
                    }else{
                        $titulo .= "<br/><span style='border-radius:2px; background-color:#ff0000; color:#ffffff'> $subtarefasNFinalizadas subtarefas não finalizadas </span>";
                    }
                }
            }
            TTransaction::close();

            return $titulo;

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

        $order_prazo_entrega_transformed = new TAction(array($this, 'onReload'));
        $order_prazo_entrega_transformed->setParameter('order', 'prazo_entrega');
        $column_prazo_entrega_transformed->setAction($order_prazo_entrega_transformed);
        $order_titulo_transformed = new TAction(array($this, 'onReload'));
        $order_titulo_transformed->setParameter('order', 'titulo');
        $column_titulo_transformed->setAction($order_titulo_transformed);
        $order_data_entrega_transformed = new TAction(array($this, 'onReload'));
        $order_data_entrega_transformed->setParameter('order', 'data_entrega');
        $column_data_entrega_transformed->setAction($order_data_entrega_transformed);

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

        $this->datagrid->addColumn($column_usuario_destinatario_name);
        $this->datagrid->addColumn($column_prazo_entrega_transformed);
        $this->datagrid->addColumn($column_prazo_processual_transformed);
        $this->datagrid->addColumn($column_numero_processo);
        $this->datagrid->addColumn($column_cliente_vinculado);
        $this->datagrid->addColumn($column_titulo_transformed);
        $this->datagrid->addColumn($column_data_entrega_transformed);
        $this->datagrid->addColumn($column_tarefa_status_nome_transformed);

        $action_onShow = new TDataGridAction(array('TarefaFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search-plus #000000');
        $action_onShow->setField(self::$primaryKey);

        $action_onShow->setParameter('key', '{id}');
        $action_onShow->setParameter("origem", self::class);

        $this->datagrid->addAction($action_onShow);

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
        $tr->add(TElement::tag('td', ''));
        $td_usuario_destinatario_id_col = TElement::tag('td', $usuario_destinatario_id_col);
        $tr->add($td_usuario_destinatario_id_col);
        $td_prazo_entrega = TElement::tag('td', $prazo_entrega);
        $tr->add($td_prazo_entrega);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_numero_processo = TElement::tag('td', $numero_processo);
        $tr->add($td_numero_processo);
        $td_cliente_vinculado_col = TElement::tag('td', $cliente_vinculado_col);
        $tr->add($td_cliente_vinculado_col);
        $td_titulo_col = TElement::tag('td', $titulo_col);
        $tr->add($td_titulo_col);
        $td_data_entrega_col = TElement::tag('td', $data_entrega_col);
        $tr->add($td_data_entrega_col);
        $td_tarefa_status_id_col = TElement::tag('td', $tarefa_status_id_col);
        $tr->add($td_tarefa_status_id_col);
        $tr->add(TElement::tag('td', ''));

        $this->datagrid_form->addField($usuario_destinatario_id_col);
        $this->datagrid_form->addField($prazo_entrega);
        $this->datagrid_form->addField($numero_processo);
        $this->datagrid_form->addField($cliente_vinculado_col);
        $this->datagrid_form->addField($titulo_col);
        $this->datagrid_form->addField($data_entrega_col);
        $this->datagrid_form->addField($tarefa_status_id_col);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

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

        $btnKanban = new TButton('button_btnKanban');
        $btnKanban->setAction(new TAction(['TarefaKanbanHeader', 'onShow']), "Kanban");
        $btnKanban->addStyleClass('btn-default');
        $btnKanban->setImage('fas:columns #000000');

        $this->datagrid_form->addField($btnKanban);

        $button_imprimir = new TButton('button_button_imprimir');
        $button_imprimir->setAction(new TAction(['PrintTarefaList', 'onShow']), "Imprimir");
        $button_imprimir->addStyleClass('btn-default');
        $button_imprimir->setImage('far:file-pdf #e74c3c');

        $this->datagrid_form->addField($button_imprimir);

        $button_cadastrar = new TButton('button_button_cadastrar');
        $button_cadastrar->setAction(new TAction(['TarefaForm', 'onShow']), "Cadastrar");
        $button_cadastrar->addStyleClass('btn-default');
        $button_cadastrar->setImage('fas:plus #69aa46');

        $this->datagrid_form->addField($button_cadastrar);

        $btnShowCurtainFilters = new TButton('button_btnShowCurtainFilters');
        $btnShowCurtainFilters->setAction(new TAction(['TarefaList', 'onShowCurtainFilters']), "Filtros");
        $btnShowCurtainFilters->addStyleClass('btn-default');
        $btnShowCurtainFilters->setImage('fas:filter #000000');

        $this->datagrid_form->addField($btnShowCurtainFilters);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['TarefaList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['TarefaList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $btnPrazosExpirados = new TButton('button_btnPrazosExpirados');
        $btnPrazosExpirados->setAction(new TAction(['TarefaList', 'onFilterExpirados']), "Prazos Expirados");
        $btnPrazosExpirados->addStyleClass('btn-default');
        $btnPrazosExpirados->setImage('fas:filter #000000');
        $btnPrazosExpirados->getAction()->setParameter("expirados", TSession::getvalue('expirados'));

        $this->datagrid_form->addField($btnPrazosExpirados);

        $btnPrazosExpirar = new TButton('button_btnPrazosExpirar');
        $btnPrazosExpirar->setAction(new TAction(['TarefaList', 'onFilterAExpirar']), "Prazos a Expirar");
        $btnPrazosExpirar->addStyleClass('btn-default');
        $btnPrazosExpirar->setImage('fas:filter #000000');
        $btnPrazosExpirar->getAction()->setParameter("aExpirar", TSession::getvalue('aExpirar'));

        $this->datagrid_form->addField($btnPrazosExpirar);

        $btnPendentes = new TButton('button_btnPendentes');
        $btnPendentes->setAction(new TAction(['TarefaList', 'onFilterPendentes']), "Pendentes");
        $btnPendentes->addStyleClass('btn-default');
        $btnPendentes->setImage('fas:filter #000000');
        $btnPendentes->getAction()->setParameter("pendentes", TSession::getvalue('pendentes'));

        $this->datagrid_form->addField($btnPendentes);

        $head_left_actions->add($button_cadastrar);
        $head_left_actions->add($btnShowCurtainFilters);
        $head_left_actions->add($button_limpar_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($btnPrazosExpirados);
        $head_left_actions->add($btnPrazosExpirar);
        $head_left_actions->add($btnPendentes);

        $head_right_actions->add($btnKanban);
        $head_right_actions->add($button_imprimir);

        $this->datagrid_form->add($this->datagrid);

        $this->btnShowCurtainFilters = $btnShowCurtainFilters;

        $this->btnPrazosExpirados = $btnPrazosExpirados;
        $this->btnPrazosExpirar = $btnPrazosExpirar;
        $this->btnPendentes = $btnPendentes;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Tarefas","Tarefas"]));
        }

        $container->add($panel);

        $this->setProperty('id', self::class);

        parent::add($container);

    }

    public static function onShowCurtainFilters($param = null) 
    {
        try 
        {

                        $filter = new self([]);

            $btnClose = new TButton('closeCurtain');
            $btnClose->class = 'btn btn-sm btn-default';
            $btnClose->style = 'margin-right:10px;';
            $btnClose->onClick = "Template.closeRightPanel();";
            $btnClose->setLabel("Fechar");
            $btnClose->setImage('fas:times');

            $filter->form->addHeaderWidget($btnClose);

            $page = new TPage();
            $page->setTargetContainer('adianti_right_panel');
            $page->setProperty('page-name', 'TarefaListSearch');
            $page->setProperty('page_name', 'TarefaListSearch');
            $page->adianti_target_container = 'adianti_right_panel';
            $page->target_container = 'adianti_right_panel';
            $page->add($filter->form);
            $page->setIsWrapped(true);
            $page->show();

            $style = new TStyle('right-panel > .container-part[page-name=TarefaListSearch]');
            $style->width = '60% !important';
            $style->show(true);

            TTransaction::open(self::$database);

            $configuracao = TarefaConfiguracao::find(1);
            if($configuracao->tem_dtvalidacao!="S"){
                TScript::create("$('label:contains(\"Prazo de validação:\")').hide();");
                TScript::create("$(\"#label_ate_prazo_validacao\").hide()");

                TScript::create("$(\"[name='prazo_validacao_de']\").closest('.fb-inline-field-container').hide()");
                TScript::create("$(\"[name='prazo_validacao_ate']\").closest('.fb-inline-field-container').hide()");
            }else{
                TScript::create("$('label:contains(\"Prazo de validação:\")').show();");
                TScript::create("$(\"#label_ate_prazo_validacao\").show()");

                TScript::create("$(\"[name='prazo_validacao_de']\").closest('.fb-inline-field-container').show()");
                TScript::create("$(\"[name='prazo_validacao_ate']\").closest('.fb-inline-field-container').show()");
            }

            TTransaction::close();

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onClearFilters($param = null) 
    {
        $filters = [];

        $filters[] = new TFilter('arquivado', 'in', ["N"]);// create the filter 
        $filters[] = new TFilter('usuario_destinatario_id', 'in', [TSession::getValue('userid')]);

        $data = new stdClass();
        $data->arquivado = ["N"];
        $data->usuario_destinatario_id = [TSession::getValue('userid')];

        $this->form->setData($data);

        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        $this->onReload(['offset' => 0, 'first_page' => 1]);

    }
    public function onRefresh($param = null) 
    {
        $this->onReload(['offset' => 0, 'first_page' => 1]);

    }
    public function onFilterExpirados($param = null) 
    {
        try 
        {
            $id = TSession::getValue('id');
            if($param['expirados']>0){
                TTransaction::open(self::$database);

                $filters = [];
                $filters[] = new TFilter('prazo_entrega', 'is not', null);

                $status_finais = TarefaStatus::where('fim','=','S')->getIndexedArray('id','id');

                $tarefas = Tarefa::where('prazo_entrega','is not',null)
                                 ->where('usuario_destinatario_id','=',$id)
                                 ->where('data_entrega','is',null)
                                 ->where('tarefa_status_id','not in',$status_finais)
                                 ->load();

                $ids = [];
                foreach($tarefas as $tarefa){
                    if($tarefa->prazo_entrega < date('Y-m-d')){
                        $ids[$tarefa->id] = $tarefa->id;
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
            $id = TSession::getValue('id');
            if($param['aExpirar']>0){
                TTransaction::open(self::$database);

                $filters = [];
                $filters[] = new TFilter('prazo_entrega', 'is not', null);

                $status_finais = TarefaStatus::where('fim','=','S')->getIndexedArray('id','id');

                $tarefas = Tarefa::where('prazo_entrega','is not',null)
                                 ->where('usuario_destinatario_id','=',$id)
                                 ->where('data_entrega','is',null)
                                 ->where('tarefa_status_id','not in',$status_finais)
                                 ->load();

                $ids = [];
                foreach($tarefas as $tarefa){
                    if($tarefa->prazo_entrega >= date('Y-m-d') && $tarefa->prazo_entrega <= date('Y-m-d', strtotime("+5 days",strtotime(date('Y-m-d'))))){
                        $ids[$tarefa->id] = $tarefa->id;
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
    public function onFilterPendentes($param = null) 
    {
        try 
        {
            $id = TSession::getValue('id');
            if($param['pendentes']>0){
                TTransaction::open(self::$database);

                $filters = [];
                $filters[] = new TFilter('prazo_entrega', 'is not', null);
                $status_finais = TarefaStatus::where('fim','=','S')->getIndexedArray('id','id');

                $tarefas = Tarefa::where('prazo_entrega','is not',null)
                                 ->where('usuario_destinatario_id','=',$id)
                                 ->where('data_entrega','is',null)
                                 ->where('tarefa_status_id','not in',$status_finais)
                                 ->load();

                $ids = [];
                foreach($tarefas as $tarefa){
                    $ids[$tarefa->id] = $tarefa->id;
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

    /**
     * Register the filter in the session
     */
    public function onSearch($param = null)
    {
        if ((isset($param['static']) && ($param['static'] == '1')) || !empty($param['globalSearch']))
        {
            $data = $this->datagrid_form->getData();
        }
        else
        {
            $data = $this->form->getData();
        }
        $filters = [];

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
            if($data->tarefa_status_id_col == (TarefaConfiguracao::find(1))->status_final_id || $data->tarefa_status_id_col == (TarefaConfiguracao::find(1))->status_cancelado_id)
                $data->arquivado = ['S','N'];
        }
        TTransaction::close();

        if (isset($data->titulo) AND ( (is_scalar($data->titulo) AND $data->titulo !== '') OR (is_array($data->titulo) AND (!empty($data->titulo)) )) ){
            $titulo = $data->titulo;
            $data->titulo = str_replace(' ','%',TratamentosService::removerAcentos($data->titulo));
        }
        if (isset($data->titulo_col) AND ( (is_scalar($data->titulo_col) AND $data->titulo_col !== '') OR (is_array($data->titulo_col) AND (!empty($data->titulo_col)) )) ){
            $titulo_col = $data->titulo_col;
            $data->titulo_col = str_replace(' ','%',TratamentosService::removerAcentos($data->titulo_col));
        } 

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

        if (isset($data->usuario_destinatario_id_col) AND ( (is_scalar($data->usuario_destinatario_id_col) AND $data->usuario_destinatario_id_col !== '') OR (is_array($data->usuario_destinatario_id_col) AND (!empty($data->usuario_destinatario_id_col)) )) )
        {

            $filters[] = new TFilter('usuario_destinatario_id', '=', $data->usuario_destinatario_id_col);// create the filter 
        }

        if (isset($data->prazo_entrega) AND ( (is_scalar($data->prazo_entrega) AND $data->prazo_entrega !== '') OR (is_array($data->prazo_entrega) AND (!empty($data->prazo_entrega)) )) )
        {

            $filters[] = new TFilter('prazo_entrega', '=', $data->prazo_entrega);// create the filter 
        }

        if (isset($data->cliente_vinculado_col) AND ( (is_scalar($data->cliente_vinculado_col) AND $data->cliente_vinculado_col !== '') OR (is_array($data->cliente_vinculado_col) AND (!empty($data->cliente_vinculado_col)) )) )
        {

            $filters[] = new TFilter('arquivado', '<>', $data->cliente_vinculado_col);// create the filter 
        }

        if (isset($data->titulo_col) AND ( (is_scalar($data->titulo_col) AND $data->titulo_col !== '') OR (is_array($data->titulo_col) AND (!empty($data->titulo_col)) )) )
        {

            $filters[] = new TFilter('unaccent(titulo)', 'ilike', "%{$data->titulo_col}%");// create the filter 
        }

        if (isset($data->data_entrega_col) AND ( (is_scalar($data->data_entrega_col) AND $data->data_entrega_col !== '') OR (is_array($data->data_entrega_col) AND (!empty($data->data_entrega_col)) )) )
        {

            $filters[] = new TFilter('data_entrega', '=', $data->data_entrega_col);// create the filter 
        }

        if (isset($data->tarefa_status_id_col) AND ( (is_scalar($data->tarefa_status_id_col) AND $data->tarefa_status_id_col !== '') OR (is_array($data->tarefa_status_id_col) AND (!empty($data->tarefa_status_id_col)) )) )
        {

            $filters[] = new TFilter('tarefa_status_id', '=', $data->tarefa_status_id_col);// create the filter 
        }

        if (isset($data->titulo) AND ( (is_scalar($data->titulo) AND $data->titulo !== '') OR (is_array($data->titulo) AND (!empty($data->titulo)) )) ){
            $data->titulo = $titulo;
        }
        if (isset($data->titulo_col) AND ( (is_scalar($data->titulo_col) AND $data->titulo_col !== '') OR (is_array($data->titulo_col) AND (!empty($data->titulo_col)) )) ){
            $data->titulo_col = $titulo_col;
        }

        if(!(isset($data->arquivado) AND ((is_scalar($data->arquivado) AND $data->arquivado !== '') OR (is_array($data->arquivado) AND (!empty($data->arquivado)))))){
            $filters[] = new TFilter('arquivado', 'in', ["N"]);// create the filter 
        }

        // fill the form with data again
        if ((isset($param['static']) && ($param['static'] == '1')) || !empty($param['globalSearch']))
        {
            $this->datagrid_form->setData($data);
        }
        else
        {
            $this->form->setData($data);
        }

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

            // creates a repository for Tarefa
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'prazo_entrega';    
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
            $session_checks = TSession::getValue(__CLASS__.'builder_datagrid_check');

            if($param['order'] == "usuario_destinatario_id, prazo_entrega"){

                $configuracao = TarefaConfiguracao::find(1);
                if($configuracao->tem_dtvalidacao=="S" && $configuracao->dtvalidacao_obrigatoria=="S"){
                    if(empty($param['order'])){
                        $param['order'] = 'usuario_destinatario_id, prazo_validacao, prazo_entrega';    
                    }else if($param['order'] != 'usuario_destinatario_id, prazo_validacao, prazo_entrega'){
                        $param['order'] = "usuario_destinatario_id, prazo_validacao, prazo_entrega,{$param['order']}"; 
                    }
                }else{
                    if(empty($param['order'])){
                        $param['order'] = 'usuario_destinatario_id, prazo_entrega';    
                    }else if($param['order'] != 'usuario_destinatario_id, prazo_entrega'){
                        $param['order'] = "usuario_destinatario_id, prazo_entrega,{$param['order']}"; 
                    }
                }
                $criteria->setProperties($param); // order, offset
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

            if (isset($data->usuario_destinatario_id)) {
                  self::getQuantidadePorStatus($data->usuario_destinatario_id);

            // Corrigindo a sintaxe de interpolação de variáveis
            $expirados = TSession::getValue('expirados');
            $aExpirar = TSession::getValue('aExpirar');
            $pendentes = TSession::getValue('pendentes');

            // Definindo os rótulos dos botões com os valores da sessão
            $this->btnPrazosExpirados->setLabel("Prazos expirados ({$expirados})");
            $this->btnPrazosExpirar->setLabel("Prazo a expirar ({$aExpirar})");
            $this->btnPendentes->setLabel("Pendentes ({$pendentes})");

            }

            if (isset($data->numero_processo) AND ( (is_scalar($data->numero_processo) AND $data->numero_processo !== '') OR (is_array($data->numero_processo) AND (!empty($data->numero_processo)) )) )
            {

                $criteria1 = new TCriteria;
                $criteria1->add(new TFilter('processo_id',   'in', "(SELECT id FROM processo   WHERE numero_cnj_numero like '%{$data->numero_processo}%' )"), TExpression::OR_OPERATOR);
                $criteria1->add(new TFilter('publicacao_id', 'in', "(SELECT id FROM publicacao WHERE processo_id in (SELECT id FROM processo WHERE numero_cnj_numero like '%{$data->numero_processo}%'))"), TExpression::OR_OPERATOR);
                $criteria1->add(new TFilter('publicacao_id', 'in', "(SELECT id FROM publicacao WHERE numero_unico_processo like '%{$data->numero_processo}%')"), TExpression::OR_OPERATOR);
                $criteria1->add(new TFilter('publicacao_id', 'in', "(SELECT id FROM publicacao WHERE numero_processo_principal like '%{$data->numero_processo}%')"), TExpression::OR_OPERATOR);
                $criteria->add($criteria1); 
            }
            if (isset($data->cliente_vinculado_col) AND ( (is_scalar($data->cliente_vinculado_col) AND $data->cliente_vinculado_col !== '') OR (is_array($data->cliente_vinculado_col) AND (!empty($data->cliente_vinculado_col)) )) )
            {
                $criteria1 = new TCriteria;
                $criteria1->add(new TFilter('id', 'in', 
                    "(SELECT tarefa_id FROM tarefa_cliente WHERE cliente_id in (SELECT id FROM pessoa WHERE nome_busca ilike '%$data->cliente_vinculado_col%'))"
                    ), TExpression::OR_OPERATOR); 
                $criteria1->add(new TFilter('processo_id', 'in', 
                    "(SELECT processo_id FROM contrato_processo WHERE processo_id in (SELECT processo_id FROM contrato_processo WHERE contrato_id in 
                        (SELECT contrato_id FROM contrato_pessoa WHERE cliente_id in (SELECT id FROM pessoa WHERE nome_busca ilike '%$data->cliente_vinculado_col%'))))"
                    ), TExpression::OR_OPERATOR); 
                $criteria1->add(new TFilter('publicacao_id', 'in', 
                    "(SELECT id FROM publicacao WHERE processo_id in 
                		(SELECT processo_id FROM contrato_processo WHERE processo_id in (SELECT processo_id FROM contrato_processo WHERE contrato_id in 
                		(SELECT contrato_id FROM contrato_pessoa WHERE cliente_id in (SELECT id FROM pessoa WHERE nome_busca ilike '%$data->cliente_vinculado_col%')))))"
                    ), TExpression::OR_OPERATOR); 
                $criteria->add($criteria1); 
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
                    $check = new TCheckGroup('builder_datagrid_check');
                    $check->addItems([$object->id => '']);
                    $check->getButtons()[$object->id]->onclick = 'event.stopPropagation()';

                    if(!$this->datagrid_form->getField('builder_datagrid_check[]'))
                    {
                        $this->datagrid_form->setFields([$check]);
                    }

                    $check->setChangeAction(new TAction([$this, 'builderSelectCheck']));
                    $object->builder_datagrid_check = $check;

                    if(!empty($session_checks[$object->id]))
                    {
                        $object->builder_datagrid_check->setValue([$object->id=>$object->id]);
                    }

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

        $filters = [];

        $filters[] = new TFilter('arquivado', 'in', ["N"]);// create the filter 
        $filters[] = new TFilter('usuario_destinatario_id', 'in', [TSession::getValue('userid')]);

        $data = new stdClass();
        $data->arquivado = ["N"];
        $data->usuario_destinatario_id = [TSession::getValue('userid')];

        $this->form->setData($data);

        TSession::setValue(__CLASS__.'_filter_data', $data);
        TSession::setValue(__CLASS__.'_filters', $filters);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
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

    public static function builderSelectCheck($param)
    {
        $session_checks = TSession::getValue(__CLASS__.'builder_datagrid_check');

        $valueOn = null;
        if(!empty($param['_field_data_json']))
        {
            $obj = json_decode($param['_field_data_json']);
            if($obj)
            {
                $valueOn = $obj->valueOn;
            }
        }

        $key = empty($param['key']) ? $valueOn : $param['key'];

        if(empty($param['builder_datagrid_check']) && !empty($session_checks[$key]))
        {
            unset($session_checks[$key]);
        }
        elseif(!empty($param['builder_datagrid_check']) && !in_array($key, $param['builder_datagrid_check']) && !empty($session_checks[$key]))
        {
            unset($session_checks[$key]);
        }
        elseif(!empty($param['builder_datagrid_check']) && in_array($key, $param['builder_datagrid_check']))
        {
            $session_checks[$key] = $key;
        }

        TSession::setValue(__CLASS__.'builder_datagrid_check', $session_checks);
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

        $session_checks = TSession::getValue(__CLASS__.'builder_datagrid_check');

        $check = new TCheckGroup('builder_datagrid_check');
        $check->addItems([$object->id => '']);
        $check->getButtons()[$object->id]->onclick = 'event.stopPropagation()';

        if(!$list->datagrid_form->getField('builder_datagrid_check[]'))
        {
            $list->datagrid_form->setFields([$check]);
        }

        $check->setChangeAction(new TAction([$list, 'builderSelectCheck']));
        $object->builder_datagrid_check = $check;

        if(!empty($session_checks[$object->id]))
        {
            $object->builder_datagrid_check->setValue([$object->id=>$object->id]);
        }

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

    public function getQuantidadePorStatus($id)
    {
        TTransaction::open(self::$database);

        if($id)
        {
            $tarefas = Tarefa::where('prazo_entrega','is not',null)->where('data_entrega','is',null)->where('usuario_destinatario_id','in',$id)->load();
            $expirados = 0;
            $aExpirar = 0;
            $pendentes = count($tarefas);

            foreach($tarefas as $tarefa)
            {
                if($tarefa->prazo_entrega < date('Y-m-d')){
                    $expirados++;
                }elseif($tarefa->prazo_entrega>=date('Y-m-d') && ($tarefa->prazo_entrega<=date('Y-m-d', strtotime("+5 days",strtotime(date('Y-m-d')))))){
                    $aExpirar++;
                }
            }
        }else{
            $expirados = 0;
            $aExpirar = 0;
            $pendentes = 0;
        }
        TTransaction::close();
        TSession::setValue('id',$id);
        TSession::setValue('expirados', $expirados);
        TSession::setValue('aExpirar', $aExpirar);
        TSession::setValue('pendentes', $pendentes);
    }

}

