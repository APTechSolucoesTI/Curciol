<?php

class TodosAgendamentoList extends TPage
{
    private $form; // form
    private $datagrid; // listing
    private $pageNavigation;
    private $loaded;
    private $filter_criteria;
    private static $database = 'escritorio';
    private static $activeRecord = 'Agendamento';
    private static $primaryKey = 'id';
    private static $formName = 'formList_Agendamento';
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
        $this->form->setFormTitle("Todos atendimentos");
        $this->limit = 20;

        $criteria_agenda_id = new TCriteria();
        $criteria_cliente_id = new TCriteria();
        $criteria_especialidade_descricao = new TCriteria();
        $criteria_agenda_nome = new TCriteria();
        $criteria_estado_agenda_nome = new TCriteria();

        $filterVar = TSession::getValue("userunitid");
        $criteria_agenda_id->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}')")); 
        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $agenda_id = new TDBCombo('agenda_id', 'escritorio', 'Agenda', 'id', '{nome}','nome asc' , $criteria_agenda_id );
        $cliente_id = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );
        $dt_inicial = new TDate('dt_inicial');
        $ate = new TDate('ate');
        $cliente_col = new TEntry('cliente_col');
        $especialidade_descricao = new TDBCombo('especialidade_descricao', 'escritorio', 'Especialidade', 'id', '{descricao}','descricao asc' , $criteria_especialidade_descricao );
        $agenda_nome = new TDBCombo('agenda_nome', 'escritorio', 'Agenda', 'id', '{nome}','nome asc' , $criteria_agenda_nome );
        $estado_agenda_nome = new TDBCombo('estado_agenda_nome', 'escritorio', 'EstadoAgenda', 'id', '{nome}','nome asc' , $criteria_estado_agenda_nome );

        $cliente_col->exitOnEnter();

        $cliente_col->setExitAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $especialidade_descricao->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $agenda_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));
        $estado_agenda_nome->setChangeAction(new TAction([$this, 'onSearch'], ['static'=>'1']));

        $cliente_id->setMinLength(3);
        $cliente_id->setFilterColumns(["email","nome"]);
        $ate->setDatabaseMask('yyyy-mm-dd');
        $dt_inicial->setDatabaseMask('yyyy-mm-dd');

        $ate->setMask('dd/mm/yyyy');
        $cliente_id->setMask('{nome}');
        $dt_inicial->setMask('dd/mm/yyyy');

        $agenda_id->enableSearch();
        $agenda_nome->enableSearch();
        $estado_agenda_nome->enableSearch();
        $especialidade_descricao->enableSearch();

        $ate->setSize(150);
        $dt_inicial->setSize(150);
        $agenda_id->setSize('100%');
        $cliente_id->setSize('100%');
        $cliente_col->setSize('100%');
        $agenda_nome->setSize('100%');
        $estado_agenda_nome->setSize('100%');
        $especialidade_descricao->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Agenda:", null, '14px', null),$agenda_id],[new TLabel("Cliente:", null, '14px', null),$cliente_id],[new TLabel("Período:", null, '14px', null, '100%'),$dt_inicial,new TLabel("até", null, '14px', null),$ate]);
        $row1->layout = ['col-sm-4',' col-sm-4','col-sm-4'];

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

        $column_id = new TDataGridColumn('id', "Cód. Agendamento", 'center' , '70px');
        $column_atendimento_id = new TDataGridColumn('atendimento_id', "Cód. Atendimento", 'center' , '70px');
        $column_cliente_nome = new TDataGridColumn('cliente->nome', "Cliente", 'left');
        $column_especialidade_descricao = new TDataGridColumn('especialidade->descricao', "Especialidade", 'left');
        $column_agenda_nome = new TDataGridColumn('agenda->nome', "Agenda", 'left');
        $column_dt_inicial_transformed = new TDataGridColumn('dt_inicial', "Início ", 'left' , '170px');
        $column_dt_final_transformed = new TDataGridColumn('dt_final', "Fim", 'left' , '170px');
        $column_estado_agenda_nome_transformed = new TDataGridColumn('estado_agenda->nome', "Estado", 'center' , '160px');

        $column_dt_inicial_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_dt_final_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_estado_agenda_nome_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {

            return "<span class='label' style='width:235px;background-color:{$object->estado_agenda->cor}'> {$value} <span> "; 

        });        

        $order_id = new TAction(array($this, 'onReload'));
        $order_id->setParameter('order', 'id');
        $column_id->setAction($order_id);
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

        $this->datagrid->addColumn($column_id);
        $this->datagrid->addColumn($column_atendimento_id);
        $this->datagrid->addColumn($column_cliente_nome);
        $this->datagrid->addColumn($column_especialidade_descricao);
        $this->datagrid->addColumn($column_agenda_nome);
        $this->datagrid->addColumn($column_dt_inicial_transformed);
        $this->datagrid->addColumn($column_dt_final_transformed);
        $this->datagrid->addColumn($column_estado_agenda_nome_transformed);

        $action_onShow = new TDataGridAction(array('AtendimentoFormView', 'onShow'));
        $action_onShow->setUseButton(false);
        $action_onShow->setButtonClass('btn btn-default btn-sm');
        $action_onShow->setLabel("Entrar no atendimento");
        $action_onShow->setImage('fas:search-plus #009688');
        $action_onShow->setField(self::$primaryKey);
        $action_onShow->setDisplayCondition('TodosAgendamentoList::temAtendimento');
        $action_onShow->setParameter('key', '{atendimento_id}');

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
        $td_cliente_col = TElement::tag('td', $cliente_col);
        $tr->add($td_cliente_col);
        $td_especialidade_descricao = TElement::tag('td', $especialidade_descricao);
        $tr->add($td_especialidade_descricao);
        $td_agenda_nome = TElement::tag('td', $agenda_nome);
        $tr->add($td_agenda_nome);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_empty = TElement::tag('td', "");
        $tr->add($td_empty);
        $td_estado_agenda_nome = TElement::tag('td', $estado_agenda_nome);
        $tr->add($td_estado_agenda_nome);
        $tr->add(TElement::tag('td', ''));

        $this->datagrid_form->addField($cliente_col);
        $this->datagrid_form->addField($especialidade_descricao);
        $this->datagrid_form->addField($agenda_nome);
        $this->datagrid_form->addField($estado_agenda_nome);

        $this->datagrid_form->setData( TSession::getValue(__CLASS__.'_filter_data') );

        // creates the page navigation
        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction(array($this, 'onReload')));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Todos atendimentos");
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

        $btnShowCurtainFilters = new TButton('button_btnShowCurtainFilters');
        $btnShowCurtainFilters->setAction(new TAction(['TodosAgendamentoList', 'onShowCurtainFilters']), "Filtros");
        $btnShowCurtainFilters->addStyleClass('btn-default');
        $btnShowCurtainFilters->setImage('fas:filter #000000');

        $this->datagrid_form->addField($btnShowCurtainFilters);

        $button_limpar_filtros = new TButton('button_button_limpar_filtros');
        $button_limpar_filtros->setAction(new TAction(['TodosAgendamentoList', 'onClearFilters']), "Limpar filtros");
        $button_limpar_filtros->addStyleClass('btn-default');
        $button_limpar_filtros->setImage('fas:eraser #f44336');

        $this->datagrid_form->addField($button_limpar_filtros);

        $button_atualizar = new TButton('button_button_atualizar');
        $button_atualizar->setAction(new TAction(['TodosAgendamentoList', 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');

        $this->datagrid_form->addField($button_atualizar);

        $button_nova_anotacao = new TButton('button_button_nova_anotacao');
        $button_nova_anotacao->setAction(new TAction(['AtendimentoAvulsoForm', 'onShow']), "Nova anotação");
        $button_nova_anotacao->addStyleClass('btn-default');
        $button_nova_anotacao->setImage('fas:plus #000000');

        $this->datagrid_form->addField($button_nova_anotacao);

        $dropdown_button_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_button_exportar->setPullSide('right');
        $dropdown_button_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_button_exportar->addPostAction( "CSV", new TAction(['TodosAgendamentoList', 'onExportCsv'],['static' => 1]), 'datagrid_'.self::$formName, 'fas:table #00b894' );
        $dropdown_button_exportar->addPostAction( "PDF", new TAction(['TodosAgendamentoList', 'onExportPdf'],['static' => 1]), 'datagrid_'.self::$formName, 'far:file-pdf #e74c3c' );
        $dropdown_button_exportar->addPostAction( "XLS", new TAction(['TodosAgendamentoList', 'onExportXls']), 'datagrid_'.self::$formName, 'fas:file-excel #4CAF50' );

        $head_left_actions->add($btnShowCurtainFilters);
        $head_left_actions->add($button_limpar_filtros);
        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($button_nova_anotacao);

        $head_right_actions->add($dropdown_button_exportar);

        $this->datagrid_form->add($this->datagrid);

        $this->btnShowCurtainFilters = $btnShowCurtainFilters;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Atendimento","Todos"]));
        }

        $container->add($panel);

        parent::add($container);

    }

    public static function temAtendimento($object)
    {
        try 
        {   
            if($object)
            {
                if ($object->atendimento && ! AtendimentoService::podeManipular($object->atendimento, TSession::getValue('userid')))
                {
                    return false;
                }
                if(!empty($object->atendimento)){
                    if(!empty($object->atendimento->dt_inicio) && $object->atendimento->dt_inicio!=null){
                        return true;
                    }
                }
            }

            return false;
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
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
                                $column_name = (strpos($column_name, '{') === FALSE) ? ( '{' . $column_name . '}') : $column_name;
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
    public static function onShowCurtainFilters($param = null) 
    {
        try 
        {
            $object = new stdClass();
            $object->agenda_id = null;
            $object->cliente_id = null;
            $object->dt_inicial = null;
            $object->ate = null;

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
            $page->setProperty('page-name', 'TodosAgendamentoListSearch');
            $page->setProperty('page_name', 'TodosAgendamentoListSearch');
            $page->adianti_target_container = 'adianti_right_panel';
            $page->target_container = 'adianti_right_panel';
            $page->add($filter->form);
            $page->setIsWrapped(true);
            $page->show();

            $style = new TStyle('right-panel > .container-part[page-name=TodosAgendamentoListSearch]');
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
        TSession::setValue(__CLASS__.'_filter_data', NULL);
        TSession::setValue(__CLASS__.'_filters', NULL);

        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }
    public function onRefresh($param = null) 
    {
        $this->onReload([]);
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

        if (isset($data->agenda_id) AND ( (is_scalar($data->agenda_id) AND $data->agenda_id !== '') OR (is_array($data->agenda_id) AND (!empty($data->agenda_id)) )) )
        {

            $filters[] = new TFilter('agenda_id', '=', $data->agenda_id);// create the filter 
        }

        if (isset($data->cliente_id) AND ( (is_scalar($data->cliente_id) AND $data->cliente_id !== '') OR (is_array($data->cliente_id) AND (!empty($data->cliente_id)) )) )
        {

            $filters[] = new TFilter('cliente_id', '=', $data->cliente_id);// create the filter 
        }

        if (isset($data->dt_inicial) AND ( (is_scalar($data->dt_inicial) AND $data->dt_inicial !== '') OR (is_array($data->dt_inicial) AND (!empty($data->dt_inicial)) )) )
        {

            $filters[] = new TFilter('dt_inicial', '>=', $data->dt_inicial);// create the filter 
        }

        if (isset($data->ate) AND ( (is_scalar($data->ate) AND $data->ate !== '') OR (is_array($data->ate) AND (!empty($data->ate)) )) )
        {

            $filters[] = new TFilter('dt_inicial', '<=', $data->ate);// create the filter 
        }

        if (isset($data->especialidade_descricao) AND ( (is_scalar($data->especialidade_descricao) AND $data->especialidade_descricao !== '') OR (is_array($data->especialidade_descricao) AND (!empty($data->especialidade_descricao)) )) )
        {

            $filters[] = new TFilter('especialidade_id', '=', $data->especialidade_descricao);// create the filter 
        }

        if (isset($data->agenda_nome) AND ( (is_scalar($data->agenda_nome) AND $data->agenda_nome !== '') OR (is_array($data->agenda_nome) AND (!empty($data->agenda_nome)) )) )
        {

            $filters[] = new TFilter('agenda_id', '=', $data->agenda_nome);// create the filter 
        }

        if (isset($data->estado_agenda_nome) AND ( (is_scalar($data->estado_agenda_nome) AND $data->estado_agenda_nome !== '') OR (is_array($data->estado_agenda_nome) AND (!empty($data->estado_agenda_nome)) )) )
        {

            $filters[] = new TFilter('estado_agenda_id', '=', $data->estado_agenda_nome);// create the filter 
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
            if(!empty($this->btnShowCurtainFilters))
            {
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

}

