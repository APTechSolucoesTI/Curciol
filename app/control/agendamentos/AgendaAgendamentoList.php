<?php

class AgendaAgendamentoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Agendamento';
    private static $primaryKey = 'id';
    private static $formName = 'form_AgendaAgendamentoList';
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
        $this->form->setFormTitle("Listagem");
        $this->limit = 20;

        $criteria_cliente_id = new TCriteria();
        $criteria_profissional_id = new TCriteria();
        $criteria_especialidade_id = new TCriteria();
        $criteria_agenda_id = new TCriteria();
        $criteria_estado_agenda_id = new TCriteria();
        $criteria_especialidade_descricao = new TCriteria();
        $criteria_estado_agenda_nome = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = Grupo::PROFISSIONAL;
        $criteria_profissional_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_agenda_id->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}')")); 

        $cliente_id = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );
        $profissional_id = new TDBCombo('profissional_id', 'escritorio', 'Pessoa', 'id', '{nome_formatado}','nome asc' , $criteria_profissional_id );
        $especialidade_id = new TDBCombo('especialidade_id', 'escritorio', 'Especialidade', 'id', '{descricao}','descricao asc' , $criteria_especialidade_id );
        $agenda_id = new TDBCombo('agenda_id', 'escritorio', 'Agenda', 'id', '{nome}','nome asc' , $criteria_agenda_id );
        $estado_agenda_id = new TDBCombo('estado_agenda_id', 'escritorio', 'EstadoAgenda', 'id', '{nome}','nome asc' , $criteria_estado_agenda_id );
        $dt_inicial = new TDate('dt_inicial');
        $dt_final = new TDate('dt_final');
        $cliente_col = new TEntry('cliente_col');
        $especialidade_descricao = new TDBCombo('especialidade_descricao', 'escritorio', 'Especialidade', 'id', '{descricao}','descricao asc' , $criteria_especialidade_descricao );
        $is_online = new TCombo('is_online');
        $estado_agenda_nome = new TDBCombo('estado_agenda_nome', 'escritorio', 'EstadoAgenda', 'id', '{nome}','nome asc' , $criteria_estado_agenda_nome );

        $cliente_col->exitOnEnter();

        $cliente_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $especialidade_descricao->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $is_online->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $estado_agenda_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $cliente_id->setMinLength(3);
        $cliente_col->forceUpperCase();
        $is_online->addItems(["T"=>"Sim","F"=>"Não"]);
        $dt_final->setDatabaseMask('yyyy-mm-dd');
        $dt_inicial->setDatabaseMask('yyyy-mm-dd');

        $cliente_id->setMask('{nome}');
        $dt_final->setMask('dd/mm/yyyy');
        $dt_inicial->setMask('dd/mm/yyyy');

        $agenda_id->enableSearch();
        $is_online->enableSearch();
        $profissional_id->enableSearch();
        $especialidade_id->enableSearch();
        $estado_agenda_nome->enableSearch();
        $especialidade_descricao->enableSearch();

        $dt_final->setSize(150);
        $dt_inicial->setSize(150);
        $agenda_id->setSize('100%');
        $is_online->setSize('100%');
        $cliente_id->setSize('100%');
        $cliente_col->setSize('100%');
        $profissional_id->setSize('100%');
        $especialidade_id->setSize('100%');
        $estado_agenda_id->setSize('100%');
        $estado_agenda_nome->setSize('100%');
        $especialidade_descricao->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Cliente:", null, '14px', null),$cliente_id],[new TLabel("Profissional:", null, '14px', null),$profissional_id],[new TLabel("Especialidade:", null, '14px', null),$especialidade_id]);
        $row1->layout = [' col-sm-4','col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Agenda:", null, '14px', null, '100%'),$agenda_id],[new TLabel("Estado:", null, '14px', null),$estado_agenda_id],[new TLabel("Data:", null, '14px', null, '100%'),$dt_inicial,new TLabel("até", null, '14px', null),$dt_final]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

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

        $filterVar = TSession::getValue("userunitid");
        $this->filter_criteria->add(new TFilter('agenda_id', 'in', "(SELECT id FROM agenda WHERE escritorio_id in (SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}'))"));

        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(250);

        $column_estado_agenda_cor_transformed = new TDataGridColumn('estado_agenda->cor', "", 'left' , '50px');
        $column_dt_inicial_transformed = new TDataGridColumn('dt_inicial', "Data", 'left' , '175px');
        $column_dt_inicial_transformed1 = new TDataGridColumn('dt_inicial', "Início", 'left');
        $column_dt_final_transformed = new TDataGridColumn('dt_final', "Fim", 'left' , '175px');
        $column_cliente_nome = new TDataGridColumn('cliente->nome', "Cliente", 'left');
        $column_especialidade_descricao = new TDataGridColumn('especialidade->descricao', "Especialidade", 'left');
        $column_is_online_transformed = new TDataGridColumn('is_online', "Online", 'left');
        $column_estado_agenda_nome_transformed = new TDataGridColumn('estado_agenda->nome', "Estado", 'left' , '150px');

        $column_estado_agenda_cor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if ($value)
            {
                return "<div style='position: relative;text-align: center;'><span class='estado_agendamento' style='background-color: {$value}'></span></div>";
            }
            return '';
        });

        $column_dt_inicial_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_dt_inicial_transformed1->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $date = new DateTime($value);
            return $date->format('H:i');

        });

        $column_dt_final_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            $date = new DateTime($value);
            return $date->format('H:i');

        });

        $column_is_online_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_estado_agenda_nome_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            return "<span class='label' style='width:235px;background-color:{$object->estado_agenda->cor}'> {$value} <span> "; 

        });        

        $order_dt_inicial_transformed = new TAction(array($this, 'onReload'));
        $order_dt_inicial_transformed->setParameter('order', 'dt_inicial');
        $column_dt_inicial_transformed->setAction($order_dt_inicial_transformed);
        $order_dt_final_transformed = new TAction(array($this, 'onReload'));
        $order_dt_final_transformed->setParameter('order', 'dt_final');
        $column_dt_final_transformed->setAction($order_dt_final_transformed);

        $filterVar = TSession::getValue("userid");

        $criteriaAcesso = new TCriteria;
        // está como um profissional relacionado com a agenda
        $criteriaAcesso->add(new TFilter('agenda_id', 'in', "(SELECT agenda.id FROM agenda, agenda_profissional, pessoa WHERE pessoa.id = agenda_profissional.profissional_id AND agenda.id = agenda_profissional.agenda_id AND system_users_id = '{$filterVar}')"));
        // oyu é o profissional responsável da agenda
        $criteriaAcesso->add(new TFilter('agenda_id', 'in', "(SELECT agenda.id FROM agenda, pessoa WHERE pessoa.id = agenda.profissional_id AND system_users_id = '{$filterVar}')"), TExpression::OR_OPERATOR); 

        $this->filter_criteria->add($criteriaAcesso);

        $this->datagrid->addColumn($column_estado_agenda_cor_transformed);
        $this->datagrid->addColumn($column_dt_inicial_transformed);
        $this->datagrid->addColumn($column_dt_inicial_transformed1);
        $this->datagrid->addColumn($column_dt_final_transformed);
        $this->datagrid->addColumn($column_cliente_nome);
        $this->datagrid->addColumn($column_especialidade_descricao);
        $this->datagrid->addColumn($column_is_online_transformed);
        $this->datagrid->addColumn($column_estado_agenda_nome_transformed);

        $action_onShow = new TDataGridAction(array('AgendamentoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Visualizar");
        $action_onShow->setImage('fas:search #2196F3');
        $action_onShow->setField(self::$primaryKey);

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
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_cliente_col = TElement::tag('td', $cliente_col);
        $tr->add($td_cliente_col);
        $td_especialidade_descricao = TElement::tag('td', $especialidade_descricao);
        $tr->add($td_especialidade_descricao);
        $td_is_online = TElement::tag('td', $is_online);
        $tr->add($td_is_online);
        $td_estado_agenda_nome = TElement::tag('td', $estado_agenda_nome);
        $tr->add($td_estado_agenda_nome);
        $tr->add(TElement::tag('td', ''));

        $this->datagrid_form->addField($cliente_col);
        $this->datagrid_form->addField($especialidade_descricao);
        $this->datagrid_form->addField($is_online);
        $this->datagrid_form->addField($estado_agenda_nome);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Agendamentos");
        $panel->datagrid = 'datagrid-container';
        $this->datagridPanel = $panel;

        $panel->add($this->datagrid_form);

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

        $button_dia = new TButton('button_button_dia');
        $button_dia->setAction(new TAction(['AgendaAgendamentoList', 'onFilterDia']), "Dia");
        $button_dia->addStyleClass('btn-primary');
        $button_dia->setImage('fas:calendar-day #FFFFFF');

        $this->datagrid_form->addField($button_dia);

        $button_semana = new TButton('button_button_semana');
        $button_semana->setAction(new TAction(['AgendaAgendamentoList', 'onFilterSemana']), "Semana");
        $button_semana->addStyleClass('btn-primary');
        $button_semana->setImage('fas:calendar-week #FFFFFF');

        $this->datagrid_form->addField($button_semana);

        $button_mes = new TButton('button_button_mes');
        $button_mes->setAction(new TAction(['AgendaAgendamentoList', 'onFilterMes']), "Mês");
        $button_mes->addStyleClass('btn-primary');
        $button_mes->setImage('fas:calendar-alt #FFFFFF');

        $this->datagrid_form->addField($button_mes);

        $button_agenda = new TButton('button_button_agenda');
        $button_agenda->setAction(new TAction(['AgendamentosFilterForm', 'onShow']), "Agenda");
        $button_agenda->addStyleClass('btn-default');
        $button_agenda->setImage('fas:calendar-alt #FF9800');

        $this->datagrid_form->addField($button_agenda);

        $button_imprimir = new TButton('button_button_imprimir');
        $button_imprimir->setAction(new TAction(['AgendaAgendamentoList', 'onExportPdf'],['static' => 1]), "Imprimir");
        $button_imprimir->addStyleClass('btn-default');
        $button_imprimir->setImage('far:file-pdf #e74c3c');

        $this->datagrid_form->addField($button_imprimir);

        $button_novo_agendamento = new TButton('button_button_novo_agendamento');
        $button_novo_agendamento->setAction(new TAction(['AgendamentoFormBtn', 'onShow']), "Novo agendamento");
        $button_novo_agendamento->addStyleClass('btn-default');
        $button_novo_agendamento->setImage('fas:plus #4CAF50');
        $button_novo_agendamento->getAction()->setParameter("origin", 'list');

        $this->datagrid_form->addField($button_novo_agendamento);

        $button_filtros = new TButton('button_button_filtros');
        $button_filtros->setAction(new TAction(['AgendaAgendamentoList', 'onShowCurtainFilters']), "Filtros");
        $button_filtros->addStyleClass('btn-default');
        $button_filtros->setImage('fas:filter #000000');

        $this->datagrid_form->addField($button_filtros);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['AgendaAgendamentoList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['AgendaAgendamentoList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03A9F4');

        $this->datagrid_form->addField($button_atualizar);

        $head_left_actions->add($button_novo_agendamento);
        $head_left_actions->add($button_filtros);
        $head_left_actions->add($button_limpar_filtros);
        $head_left_actions->add($button_atualizar);

        $head_right_actions->add($button_dia);
        $head_right_actions->add($button_semana);
        $head_right_actions->add($button_mes);
        $head_right_actions->add($button_agenda);
        $head_right_actions->add($button_imprimir);

        $this->datagrid_form->add($this->datagrid);

        $button_novo_agendamento->getAction()->setParameter('origin', 'list');

        $this->button_filtros = $button_filtros;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Agendamentos","Listagem"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    public function onFilterDia($param = null) 
    {
        try 
        {
            $data = TSession::getValue(__CLASS__.'_filter_data') ?? new stdClass;
            $data->dt_inicial = date('Y-m-d 00:00:00');
            $data->dt_final = date('Y-m-d 23:59:59');

            $filters = [];

            $data->agenda_id = self::getProfissionalLogado() ?? '';

            if($data->agenda_id!='' && $data->agenda_id!=null)
                $filters[] = new TFilter('agenda_id', '=', $data->agenda_id);

            $filters[] = new TFilter('dt_inicial', '>=', date('Y-m-d 00:00:00'));
            $filters[] = new TFilter('dt_final', '<=', date('Y-m-d 23:59:59'));

            TSession::setValue(__CLASS__.'_filters', $filters);
            TSession::setValue(__CLASS__.'_filter_data', $data);

            $this->onReload(['offset' => 0, 'first_page' => 1]);

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onFilterSemana($param = null) 
    {
        try 
        {
            $firstday = date('Y-m-d', strtotime("this week"));
            $lastday = date('Y-m-d', strtotime("+7 days",strtotime($firstday))); 

            $data = TSession::getValue(__CLASS__.'_filter_data') ?? new stdClass;
            $data->dt_inicial = $firstday;
            $data->dt_final = $lastday;

            $filters = [];

            $data->agenda_id = self::getProfissionalLogado() ?? '';

            if($data->agenda_id!='' && $data->agenda_id!=null)
                $filters[] = new TFilter('agenda_id', '=', $data->agenda_id);

            $filters[] = new TFilter('dt_inicial', '>=', $firstday);
            $filters[] = new TFilter('dt_final', '<=', $lastday);

            TSession::setValue(__CLASS__.'_filters', $filters);
            TSession::setValue(__CLASS__.'_filter_data', $data);

            $this->onReload(['offset' => 0, 'first_page' => 1]);

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onFilterMes($param = null) 
    {
        try 
        {
            $diasMesAtual = date("t");

            $data = TSession::getValue(__CLASS__.'_filter_data') ?? new stdClass;
            $data->dt_inicial = date('Y-m-01 00:00:00');
            $data->dt_final = date("Y-m-$diasMesAtual 23:59:59");

            $filters = [];

            $data->agenda_id = self::getProfissionalLogado() ?? '';

            if($data->agenda_id!='' && $data->agenda_id!=null)
                $filters[] = new TFilter('agenda_id', '=', $data->agenda_id);

            $filters[] = new TFilter('dt_inicial', '>=', date('Y-m-01 00:00:00'));
            $filters[] = new TFilter('dt_final', '<=', date("Y-m-$diasMesAtual 23:59:59"));

            TSession::setValue(__CLASS__.'_filters', $filters);
            TSession::setValue(__CLASS__.'_filter_data', $data);

            $this->onReload(['offset' => 0, 'first_page' => 1]);

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
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
                $contentsHTML = str_replace('width="50px"','',$html->getContents());
                $contentsHTML = str_replace('width="175px"','',$contentsHTML);
                $contentsHTML = str_replace('width="150px"','',$contentsHTML);
                $contentsHTML = str_replace('width:235px;','',$contentsHTML);

                TTransaction::open(self::$database);

                $estadosAgenda = EstadoAgenda::where('id','>',0)->load();
                foreach($estadosAgenda as $estadoAgenda){
                    $contentsHTML = str_replace("style='background-color:$estadoAgenda->cor'",'',$contentsHTML);
                }

                TTransaction::close();

                $contents = file_get_contents('app/resources/styles-print.html') . file_get_contents('app/resources/cabecalho_print.html') . $contentsHTML;

                $dompdf = new \Dompdf\Dompdf;
                $dompdf->loadHtml($contents);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                file_put_contents($output, $dompdf->output());

                $window = TWindow::create('PDF', 0.8, 0.8);
                $object = new TElement('object');
                $object->data  = $output;
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
    public static function onShowCurtainFilters($param = null) 
    {
        try 
        {
            $object = new stdClass();
            $object->dt_inicial = null;
            $object->cliente_id = null;
            $object->profissional_id = null;
            $object->especialidade_id = null;
            $object->agenda_id = null;
            $object->estado_agenda_id = null;

            TForm::sendData(self::$formName, $object);

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
            $page->setProperty('page-name', 'AgendaAgendamentoListSearch');
            $page->setProperty('page_name', 'AgendaAgendamentoListSearch');
            $page->adianti_target_container = 'adianti_right_panel';
            $page->target_container = 'adianti_right_panel';
            $page->add($filter->form);
            $page->setIsWrapped(true);
            $page->show();

            $style = new TStyle('right-panel > .container-part[page-name=AgendaAgendamentoListSearch]');
            $style->width = '50% !important';
            $style->show(true);

            //</autoCode>
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }
    public function onClearFilters($param = null) 
    {
        $objeto = (TSession::getValue(__CLASS__.'_filter_data')) ?? new stdClass;

        $objeto->cliente_id = null;
        $objeto->especialidade_id = null;
        $objeto->agenda_id = null;
        $objeto->estado_agenda_id = null;
        $objeto->dt_inicial = null;
        $objeto->dt_final = null;

        TSession::setValue(__CLASS__.'_filter_data',$objeto);
        TSession::setValue(__CLASS__.'_filters',NULL);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }
    public function onRefresh($param = null) 
    {
        try 
        {
            $this->onReload(['offset' => 0, 'first_page' => 1]);
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

        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        if (isset($data->cliente_id) AND ( (is_scalar($data->cliente_id) AND $data->cliente_id !== '') OR (is_array($data->cliente_id) AND (!empty($data->cliente_id)) )) )
        {

            $filters[] = new TFilter('cliente_id', '=', $data->cliente_id);// create the filter 
        }

        if (isset($data->profissional_id) AND ( (is_scalar($data->profissional_id) AND $data->profissional_id !== '') OR (is_array($data->profissional_id) AND (!empty($data->profissional_id)) )) )
        {

            $filters[] = new TFilter('agenda_id', 'in', "(SELECT id FROM agenda WHERE profissional_id = '{$data->profissional_id}')");// create the filter 
        }

        if (isset($data->especialidade_id) AND ( (is_scalar($data->especialidade_id) AND $data->especialidade_id !== '') OR (is_array($data->especialidade_id) AND (!empty($data->especialidade_id)) )) )
        {

            $filters[] = new TFilter('especialidade_id', '=', $data->especialidade_id);// create the filter 
        }

        if (isset($data->agenda_id) AND ( (is_scalar($data->agenda_id) AND $data->agenda_id !== '') OR (is_array($data->agenda_id) AND (!empty($data->agenda_id)) )) )
        {

            $filters[] = new TFilter('agenda_id', '=', $data->agenda_id);// create the filter 
        }

        if (isset($data->estado_agenda_id) AND ( (is_scalar($data->estado_agenda_id) AND $data->estado_agenda_id !== '') OR (is_array($data->estado_agenda_id) AND (!empty($data->estado_agenda_id)) )) )
        {

            $filters[] = new TFilter('estado_agenda_id', '=', $data->estado_agenda_id);// create the filter 
        }

        if (isset($data->dt_inicial) AND ( (is_scalar($data->dt_inicial) AND $data->dt_inicial !== '') OR (is_array($data->dt_inicial) AND (!empty($data->dt_inicial)) )) )
        {

            $filters[] = new TFilter('dt_inicial', '>=', $data->dt_inicial);// create the filter 
        }

        if (isset($data->dt_final) AND ( (is_scalar($data->dt_final) AND $data->dt_final !== '') OR (is_array($data->dt_final) AND (!empty($data->dt_final)) )) )
        {

            $filters[] = new TFilter('dt_inicial', '<=', $data->dt_final);// create the filter 
        }

        if (isset($data->especialidade_descricao) AND ( (is_scalar($data->especialidade_descricao) AND $data->especialidade_descricao !== '') OR (is_array($data->especialidade_descricao) AND (!empty($data->especialidade_descricao)) )) )
        {

            $filters[] = new TFilter('especialidade_id', '=', $data->especialidade_descricao);// create the filter 
        }

        if (isset($data->is_online) AND ( (is_scalar($data->is_online) AND $data->is_online !== '') OR (is_array($data->is_online) AND (!empty($data->is_online)) )) )
        {

            $filters[] = new TFilter('online', '=', $data->is_online);// create the filter 
        }

        if (isset($data->estado_agenda_nome) AND ( (is_scalar($data->estado_agenda_nome) AND $data->estado_agenda_nome !== '') OR (is_array($data->estado_agenda_nome) AND (!empty($data->estado_agenda_nome)) )) )
        {

            $filters[] = new TFilter('estado_agenda_id', '=', $data->estado_agenda_nome);// create the filter 
        }

        $this->button_filtros->style = 'position: relative';
        $countFiltros = count($filters);

        if ($countFiltros)
        {
            $this->button_filtros->setLabel('Filtros'. "<span class='badge badge-success' style='position: absolute'>{$countFiltros}<span>");
        }

        if (isset($data->cliente_col) && !empty($data->cliente_col)) {
            $valor = str_replace("'", "''", trim($data->cliente_col)); // escapa aspas

            $filters[] = new TFilter(
                'cliente_id',
                'IN',
                "(SELECT id 
                FROM pessoa 
                WHERE unaccent(nome) ILIKE unaccent('%{$valor}%'))"
            );
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

            // creates a repository for Agendamento
            $repository = new TRepository(self::$activeRecord);

            $criteria = clone $this->filter_criteria;

            if (empty($param['order']))
            {
                $param['order'] = 'dt_inicial';    
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

        $this->onClearFilters($param);
        TTransaction::open(self::$database);
        $object = TSession::getValue(__CLASS__.'_filter_data') ?? new stdClass;
        $object->cliente_id = '';
        $object->profissional_id = '';
        $object->especialidade_id = '';
        $object->estado_agenda_id = '';
        $object->agenda_id = '';
        $object->dt_inicial = '';
        $object->dt_final = '';

        $filters = [];

        $object->agenda_id = self::getProfissionalLogado() ?? '';

        if($object->agenda_id!='' && $object->agenda_id!=null)
            $filters[] = new TFilter('agenda_id', '=', $object->agenda_id);

        TForm::sendData(self::$formName, $object);

        TSession::setValue(__CLASS__.'_filter_data',$object);
        TSession::setValue(__CLASS__.'_filters',$filters);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
        TTransaction::close();

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

        $object = new Agendamento($id);

        $row = $list->datagrid->addItem($object);
        $row->id = "row_{$object->id}";

        if($openTransaction)
        {
            TTransaction::close();    
        }

        TDataGrid::replaceRowById(__CLASS__.'_datagrid', $row->id, $row);
    }

    public function getProfissionalLogado(){
        TTransaction::open(self::$database);
        $profissional = Pessoa::where('system_users_id','=',TSession::getValue('userid'))->first();
        $grupo = PessoaGrupo::where('grupo_id','=',Grupo::PROFISSIONAL)->where('pessoa_id','=',$profissional->id)->count();
        if($grupo>0){
            $agenda = Agenda::where('profissional_id','=',$profissional->id)->first();
            if($agenda){
                return $agenda->id;
            }else
                return null;
        }else 
            return null;

        TTransaction::close();
    }

}

