<?php

class AgendaForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Agenda';
    private static $primaryKey = 'id';
    private static $formName = 'form_Agenda';

    use BuilderMasterDetailTrait;

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Cadastro de agenda");

        $criteria_profissional_id = new TCriteria();
        $criteria_escritorio_id = new TCriteria();
        $criteria_procedimento_id = new TCriteria();
        $criteria_agenda_profissional_agenda_profissional_id = new TCriteria();

        $filterVar = Grupo::PROFISSIONAL;
        $criteria_profissional_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = TSession::getValue("userunitids");
        $criteria_escritorio_id->add(new TFilter('system_unit_id', 'in', $filterVar)); 
        $filterVar = Grupo::PROFISSIONAL;
        $criteria_agenda_profissional_agenda_profissional_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $profissional_id = new TDBCombo('profissional_id', 'escritorio', 'Pessoa', 'id', '{nome_formatado}','nome asc' , $criteria_profissional_id );
        $escritorio_id = new TDBCombo('escritorio_id', 'escritorio', 'Escritorio', 'id', '{nome}','nome asc' , $criteria_escritorio_id );
        $dias = new TCheckGroup('dias');
        $horario_inicial = new TTime('horario_inicial');
        $horario_final = new TTime('horario_final');
        $horario_inicio_intervalo = new TTime('horario_inicio_intervalo');
        $horario_fim_intervalo = new TTime('horario_fim_intervalo');
        $duracao = new TSpinner('duracao');
        $visualizacao_inicial = new TCombo('visualizacao_inicial');
        $cor = new TColor('cor');
        $procedimento_id = new TDBCombo('procedimento_id', 'escritorio', 'Procedimento', 'id', '{nome}','nome asc' , $criteria_procedimento_id );
        $publica = new TRadioGroup('publica');
        $aceita_agendamento_online = new TRadioGroup('aceita_agendamento_online');
        $fl_permite_choque_horario = new TRadioGroup('fl_permite_choque_horario');
        $agenda_profissional_agenda_profissional_id = new TDBCombo('agenda_profissional_agenda_profissional_id', 'escritorio', 'Pessoa', 'id', '{nome_formatado}','nome asc' , $criteria_agenda_profissional_agenda_profissional_id );
        $agenda_profissional_agenda_id = new THidden('agenda_profissional_agenda_id');
        $agenda_profissional_agenda_fl_manipula_atendimento = new TRadioGroup('agenda_profissional_agenda_fl_manipula_atendimento');
        $button_adicionar_agenda_profissional_agenda = new TButton('button_adicionar_agenda_profissional_agenda');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $profissional_id->addValidation("Profissional", new TRequiredValidator()); 
        $escritorio_id->addValidation("Clínica", new TRequiredValidator()); 
        $dias->addValidation("Atendimento em", new TRequiredValidator()); 
        $horario_inicial->addValidation("Horário inicial", new TRequiredValidator()); 
        $horario_final->addValidation("Horário final", new TRequiredValidator()); 
        $duracao->addValidation("Duração", new TRequiredValidator()); 
        $visualizacao_inicial->addValidation("Visualização inicial", new TRequiredValidator()); 
        $procedimento_id->addValidation("Procedimento padrão", new TRequiredValidator()); 
        $fl_permite_choque_horario->addValidation("Permite choque de horário", new TRequiredValidator()); 

        $nome->forceUpperCase();
        $dias->setValueSeparator(',');
        $duracao->setRange(1, 2000, 1);
        $visualizacao_inicial->setDefaultOption(false);
        $agenda_profissional_agenda_fl_manipula_atendimento->setBreakItems(2);
        $button_adicionar_agenda_profissional_agenda->setAction(new TAction([$this, 'onAddDetailAgendaProfissionalAgenda'],['static' => 1]), "Adicionar");
        $button_adicionar_agenda_profissional_agenda->addStyleClass('btn-default');
        $button_adicionar_agenda_profissional_agenda->setImage('fas:plus #2ecc71');
        $profissional_id->enableSearch();
        $agenda_profissional_agenda_profissional_id->enableSearch();

        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $dias->setLayout('horizontal');
        $publica->setLayout('horizontal');
        $aceita_agendamento_online->setLayout('horizontal');
        $fl_permite_choque_horario->setLayout('horizontal');
        $agenda_profissional_agenda_fl_manipula_atendimento->setLayout('horizontal');

        $dias->setUseButton();
        $publica->setUseButton();
        $aceita_agendamento_online->setUseButton();
        $fl_permite_choque_horario->setUseButton();
        $agenda_profissional_agenda_fl_manipula_atendimento->setUseButton();

        $publica->addItems(["T"=>"Sim","F"=>"Não"]);
        $aceita_agendamento_online->addItems(["T"=>"Sim","F"=>"Não"]);
        $fl_permite_choque_horario->addItems(["T"=>"Sim","F"=>"Não"]);
        $agenda_profissional_agenda_fl_manipula_atendimento->addItems(["S"=>"Sim","N"=>"Não"]);
        $dias->addItems(["1"=>"Segunda","2"=>"Terça","3"=>"Quarta","4"=>"Quinta","5"=>"Sexta","6"=>"Sábado","0"=>"Domingo"]);
        $visualizacao_inicial->addItems(["month"=>"Mês","agendaWeek"=>"Semana","agendaDay"=>"Dia","listWeeky"=>"Agendamentos"]);

        $publica->setValue('F');
        $duracao->setValue('60');
        $horario_final->setValue('18:00');
        $horario_inicial->setValue('08:00');
        $aceita_agendamento_online->setValue('F');
        $visualizacao_inicial->setValue('agendaWeek');
        $escritorio_id->setValue(TSession::getValue('userunitid'));
        $agenda_profissional_agenda_fl_manipula_atendimento->setValue('N');

        $id->setSize(100);
        $dias->setSize(150);
        $cor->setSize('100%');
        $nome->setSize('100%');
        $duracao->setSize('100%');
        $publica->setSize('100%');
        $horario_final->setSize(110);
        $horario_inicial->setSize(110);
        $data_criacao->setSize('100%');
        $escritorio_id->setSize('100%');
        $profissional_id->setSize('100%');
        $procedimento_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $horario_fim_intervalo->setSize(110);
        $visualizacao_inicial->setSize('100%');
        $horario_inicio_intervalo->setSize(110);
        $modificacao_user_name->setSize('100%');
        $aceita_agendamento_online->setSize('100%');
        $fl_permite_choque_horario->setSize('100%');
        $agenda_profissional_agenda_id->setSize(200);
        $agenda_profissional_agenda_profissional_id->setSize('100%');
        $agenda_profissional_agenda_fl_manipula_atendimento->setSize('100%');

        $button_adicionar_agenda_profissional_agenda->id = '611d6c5e6b603';

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id],[]);
        $row1->layout = ['col-sm-6','col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome],[new TLabel("Profissional:", '#ff0000', '14px', null, '100%'),$profissional_id],[new TLabel("Escritório:", '#ff0000', '14px', null, '100%'),$escritorio_id]);
        $row2->layout = [' col-sm-6','col-sm-3',' col-sm-3'];

        $row3 = $this->form->addFields([new TLabel("Atendimento em:", '#ff0000', '14px', null, '100%'),$dias]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addFields([new TLabel("Horário de atendimento:", '#ff0000', '14px', null, '100%'),$horario_inicial,new TLabel("até", null, '14px', null),$horario_final],[new TLabel("Intervalo:", null, '14px', null, '100%'),$horario_inicio_intervalo,new TLabel("até", null, '14px', null),$horario_fim_intervalo]);
        $row4->layout = [' col-sm-6',' col-sm-6'];

        $row5 = $this->form->addFields([new TLabel("Duração <small>(minutos)</small>:", '#ff0000', '14px', null, '100%'),$duracao],[new TLabel("Visualização inicial:", '#ff0000', '14px', null, '100%'),$visualizacao_inicial],[new TLabel("Cor:", null, '14px', null, '100%'),$cor]);
        $row5->layout = ['col-sm-4','col-sm-4',' col-sm-4'];

        $row6 = $this->form->addFields([new TLabel("Procedimento padrão:", null, '14px', null, '100%'),$procedimento_id],[new TLabel("Pública?", null, '14px', null, '100%'),$publica],[new TLabel("Permite agendamento online:", null, '14px', null, '100%'),$aceita_agendamento_online],[new TLabel("Permite choque de horário:", null, '14px', null),$fl_permite_choque_horario,new TLabel("<span style='color: #607D8B;font-size: 11px;font-weight: normal; width:100%;'>Ao marcar que sim, os agendamentos podem ser sobrepostos</span>", null, '14px', null)]);
        $row6->layout = [' col-sm-3','col-sm-3','col-sm-3',' col-sm-3'];

        $this->detailFormAgendaProfissionalAgenda = new BootstrapFormBuilder('detailFormAgendaProfissionalAgenda');
        $this->detailFormAgendaProfissionalAgenda->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormAgendaProfissionalAgenda->setProperty('class', 'form-horizontal builder-detail-form');

        $row7 = $this->detailFormAgendaProfissionalAgenda->addFields([new TFormSeparator("Profissionais que podem interagir com a agenda", '#333', '18', '#eee')]);
        $row7->layout = [' col-sm-12'];

        $row8 = $this->detailFormAgendaProfissionalAgenda->addFields([new TLabel("Profissional:", '#ff0000', '14px', null, '100%'),$agenda_profissional_agenda_profissional_id,$agenda_profissional_agenda_id],[new TLabel("Pode manipular atendimentos:<br/>", '#FF0000', '14px', null, '100%'),$agenda_profissional_agenda_fl_manipula_atendimento,new TLabel("<span style='color: #607D8B;font-size: 11px;font-weight: normal; width:100%;'>Ao marcar que sim, o profissional poderá inicialiar, acessar e finalizar atendimentos</span>", null, '14px', null)]);
        $row8->layout = [' col-sm-8',' col-sm-4'];

        $row9 = $this->detailFormAgendaProfissionalAgenda->addFields([$button_adicionar_agenda_profissional_agenda]);
        $row9->layout = [' col-sm-12'];

        $row10 = $this->detailFormAgendaProfissionalAgenda->addFields([new THidden('agenda_profissional_agenda__row__id')]);
        $this->agenda_profissional_agenda_criteria = new TCriteria();

        $this->agenda_profissional_agenda_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->agenda_profissional_agenda_list->generateHiddenFields();
        $this->agenda_profissional_agenda_list->setId('agenda_profissional_agenda_list');

        $this->agenda_profissional_agenda_list->style = 'width:100%';
        $this->agenda_profissional_agenda_list->class .= ' table-bordered';

        $column_agenda_profissional_agenda_profissional_nome = new TDataGridColumn('profissional->nome', "Profissional", 'left');
        $column_agenda_profissional_agenda_fl_manipula_atendimento_transformed = new TDataGridColumn('fl_manipula_atendimento', "Pode manipular atendimentos", 'left');

        $column_agenda_profissional_agenda__row__data = new TDataGridColumn('__row__data', '', 'center');
        $column_agenda_profissional_agenda__row__data->setVisibility(false);

        $action_onEditDetailAgendaProfissional = new TDataGridAction(array('AgendaForm', 'onEditDetailAgendaProfissional'));
        $action_onEditDetailAgendaProfissional->setUseButton(false);
        $action_onEditDetailAgendaProfissional->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailAgendaProfissional->setLabel("Editar");
        $action_onEditDetailAgendaProfissional->setImage('far:edit #478fca');
        $action_onEditDetailAgendaProfissional->setFields(['__row__id', '__row__data']);

        $this->agenda_profissional_agenda_list->addAction($action_onEditDetailAgendaProfissional);
        $action_onDeleteDetailAgendaProfissional = new TDataGridAction(array('AgendaForm', 'onDeleteDetailAgendaProfissional'));
        $action_onDeleteDetailAgendaProfissional->setUseButton(false);
        $action_onDeleteDetailAgendaProfissional->setButtonClass('btn btn-default btn-sm');
        $action_onDeleteDetailAgendaProfissional->setLabel("Excluir");
        $action_onDeleteDetailAgendaProfissional->setImage('fas:trash-alt #dd5a43');
        $action_onDeleteDetailAgendaProfissional->setFields(['__row__id', '__row__data']);

        $this->agenda_profissional_agenda_list->addAction($action_onDeleteDetailAgendaProfissional);

        $this->agenda_profissional_agenda_list->addColumn($column_agenda_profissional_agenda_profissional_nome);
        $this->agenda_profissional_agenda_list->addColumn($column_agenda_profissional_agenda_fl_manipula_atendimento_transformed);

        $this->agenda_profissional_agenda_list->addColumn($column_agenda_profissional_agenda__row__data);

        $this->agenda_profissional_agenda_list->createModel();
        $this->detailFormAgendaProfissionalAgenda->addContent([$this->agenda_profissional_agenda_list]);

        $column_agenda_profissional_agenda_fl_manipula_atendimento_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
        });        $row11 = $this->form->addFields([$this->detailFormAgendaProfissionalAgenda]);
        $row11->layout = [' col-sm-12'];

        $row12 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row13 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row13->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['AgendaList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AgendaForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public  function onAddDetailAgendaProfissionalAgenda($param = null) 
    {
        try
        {
            $data = $this->form->getData();

            $errors = [];
            $requiredFields = [];
            $requiredFields[] = ['label'=>"Profissional", 'name'=>"agenda_profissional_agenda_profissional_id", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Pode manipular atendimentos", 'name'=>"agenda_profissional_agenda_fl_manipula_atendimento", 'class'=>'TRequiredValidator', 'value'=>[]];
            foreach($requiredFields as $requiredField)
            {
                try
                {
                    (new $requiredField['class'])->validate($requiredField['label'], $data->{$requiredField['name']}, $requiredField['value']);
                }
                catch(Exception $e)
                {
                    $errors[] = $e->getMessage() . '.';
                }
             }
             if(count($errors) > 0)
             {
                 throw new Exception(implode('<br>', $errors));
             }

            $__row__id = !empty($data->agenda_profissional_agenda__row__id) ? $data->agenda_profissional_agenda__row__id : 'b'.uniqid();

            TTransaction::open(self::$database);

            $grid_data = new AgendaProfissional();
            $grid_data->__row__id = $__row__id;
            $grid_data->profissional_id = $data->agenda_profissional_agenda_profissional_id;
            $grid_data->id = $data->agenda_profissional_agenda_id;
            $grid_data->fl_manipula_atendimento = $data->agenda_profissional_agenda_fl_manipula_atendimento;

            $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
            $__row__data['__row__id'] = $__row__id;
            $__row__data['__display__']['profissional_id'] =  $param['agenda_profissional_agenda_profissional_id'] ?? null;
            $__row__data['__display__']['id'] =  $param['agenda_profissional_agenda_id'] ?? null;
            $__row__data['__display__']['fl_manipula_atendimento'] =  $param['agenda_profissional_agenda_fl_manipula_atendimento'] ?? null;

            $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
            $row = $this->agenda_profissional_agenda_list->addItem($grid_data);
            $row->id = $grid_data->__row__id;

            TDataGrid::replaceRowById('agenda_profissional_agenda_list', $grid_data->__row__id, $row);

            TTransaction::close();

            $data = new stdClass;
            $data->agenda_profissional_agenda_profissional_id = '';
            $data->agenda_profissional_agenda_id = '';
            $data->agenda_profissional_agenda_fl_manipula_atendimento = 'N';
            $data->agenda_profissional_agenda__row__id = '';

            TForm::sendData(self::$formName, $data);
            TScript::create("
               var element = $('#611d6c5e6b603');
               if(typeof element.attr('add') != 'undefined')
               {
                   element.html(base64_decode(element.attr('add')));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }

    public static function onEditDetailAgendaProfissional($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));
            $__row__data->__display__ = is_array($__row__data->__display__) ? (object) $__row__data->__display__ : $__row__data->__display__;
            $fireEvents = true;
            $aggregate = false;

            $data = new stdClass;
            $data->agenda_profissional_agenda_profissional_id = $__row__data->__display__->profissional_id ?? null;
            $data->agenda_profissional_agenda_id = $__row__data->__display__->id ?? null;
            $data->agenda_profissional_agenda_fl_manipula_atendimento = $__row__data->__display__->fl_manipula_atendimento ?? null;
            $data->agenda_profissional_agenda__row__id = $__row__data->__row__id;

            TForm::sendData(self::$formName, $data, $aggregate, $fireEvents);
            TScript::create("
               var element = $('#611d6c5e6b603');
               if(!element.attr('add')){
                   element.attr('add', base64_encode(element.html()));
               }
               element.html(\"<span><i class='far fa-edit' style='color:#478fca;padding-right:4px;'></i>Editar</span>\");
               if(!element.attr('edit')){
                   element.attr('edit', base64_encode(element.html()));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public static function onDeleteDetailAgendaProfissional($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));

            $data = new stdClass;
            $data->agenda_profissional_agenda_profissional_id = '';
            $data->agenda_profissional_agenda_id = '';
            $data->agenda_profissional_agenda_fl_manipula_atendimento = '';
            $data->agenda_profissional_agenda__row__id = '';

            TForm::sendData(self::$formName, $data);

            TDataGrid::removeRowById('agenda_profissional_agenda_list', $__row__data->__row__id);
            TScript::create("
               var element = $('#611d6c5e6b603');
               if(typeof element.attr('add') != 'undefined')
               {
                   element.html(base64_decode(element.attr('add')));
               }
            ");

        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
        }
    }
    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Agenda(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object 

            TForm::sendData(self::$formName, (object)['id' => $object->id]);

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            $agenda_profissional_agenda_items = $this->storeMasterDetailItems('AgendaProfissional', 'agenda_id', 'agenda_profissional_agenda', $object, $param['agenda_profissional_agenda_list___row__data'] ?? [], $this->form, $this->agenda_profissional_agenda_list, function($masterObject, $detailObject){ 

                //code here

            }, $this->agenda_profissional_agenda_criteria); 

            if (($data->horario_inicio_intervalo AND empty($data->horario_fim_intervalo)) || (empty($data->horario_inicio_intervalo) AND $data->horario_fim_intervalo))
            {
                throw new Exception('Para preencher um horário de intervalo, é necessário preencher inicio e fim');
            }

            if ($data->horario_inicio_intervalo AND $data->horario_fim_intervalo)
            {
                if ($data->horario_inicio_intervalo >= $data->horario_fim_intervalo)
                {
                    throw new Exception('O inicio do Intervalo deve ser menor que o fim dele');
                }
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('AgendaList', 'onShow', $loadPageParam); 

                        TScript::create("Template.closeRightPanel();"); 

        }
        catch (Exception $e) // in case of exception
        {

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = (int) $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Agenda($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $agenda_profissional_agenda_items = $this->loadMasterDetailItems('AgendaProfissional', 'agenda_id', 'agenda_profissional_agenda', $object, $this->form, $this->agenda_profissional_agenda_list, $this->agenda_profissional_agenda_criteria, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }); 

                $this->form->setData($object); // fill the form 

                TTransaction::close(); // close the transaction 
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(true);

    }

    public function onShow($param = null)
    {

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

