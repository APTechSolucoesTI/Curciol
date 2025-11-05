<?php

class FormularioForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Formulario';
    private static $primaryKey = 'id';
    private static $formName = 'form_Formulario';

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
        $this->form->setFormTitle("Cadastro de formulário");


        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $ativo = new TRadioGroup('ativo');
        $questao_formulario_nome = new TEntry('questao_formulario_nome');
        $questao_formulario_id = new THidden('questao_formulario_id');
        $questao_formulario_tipo_campo = new TCombo('questao_formulario_tipo_campo');
        $questao_formulario_fl_obrigatorio = new TRadioGroup('questao_formulario_fl_obrigatorio');
        $questao_formulario_ativo = new TRadioGroup('questao_formulario_ativo');
        $questao_formulario_opcoes = new TMultiEntry('questao_formulario_opcoes');
        $button_adicionar_questao_formulario = new TButton('button_adicionar_questao_formulario');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $questao_formulario_tipo_campo->setChangeAction(new TAction([$this,'onChange']));

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $ativo->addValidation("Ativo", new TRequiredValidator()); 

        $nome->forceUpperCase();
        $button_adicionar_questao_formulario->setAction(new TAction([$this, 'onAddDetailQuestaoFormulario'],['static' => 1]), "Adicionar");
        $button_adicionar_questao_formulario->addStyleClass('btn-default');
        $button_adicionar_questao_formulario->setImage('fas:plus #2ecc71');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $ativo->setLayout('horizontal');
        $questao_formulario_ativo->setLayout('horizontal');
        $questao_formulario_fl_obrigatorio->setLayout('horizontal');

        $ativo->setValue('S');
        $questao_formulario_ativo->setValue('S');
        $questao_formulario_fl_obrigatorio->setValue('S');

        $ativo->setUseButton();
        $questao_formulario_ativo->setUseButton();
        $questao_formulario_fl_obrigatorio->setUseButton();

        $ativo->addItems(["S"=>"Sim","N"=>"Não"]);
        $questao_formulario_ativo->addItems(["S"=>"Sim","N"=>"Não"]);
        $questao_formulario_fl_obrigatorio->addItems(["S"=>"Sim","N"=>"Não"]);
        $questao_formulario_tipo_campo->addItems(["TText"=>"Texto multiplas linhas","TDate"=>"Data","THtmlEditor"=>"Texto estilizado","TSpinner"=>"Números interios","TNumeric"=>"Números decimais","TEntry"=>"Texto simple","TRadioGroup"=>"Seleção única","TCheckGroup"=>"Seleção múltipla","TCombo"=>"Escolha única"]);

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $nome->setSize('100%');
        $ativo->setSize('100%');
        $data_criacao->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $questao_formulario_id->setSize(200);
        $modificacao_user_name->setSize('100%');
        $questao_formulario_nome->setSize('100%');
        $questao_formulario_ativo->setSize('100%');
        $questao_formulario_tipo_campo->setSize('100%');
        $questao_formulario_opcoes->setSize('100%', 100);
        $questao_formulario_fl_obrigatorio->setSize('100%');

        $button_adicionar_questao_formulario->id = '60fadb9a96256';

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome],[new TLabel("Ativo:", '#ff0000', '14px', null, '100%'),$ativo]);
        $row2->layout = [' col-sm-9',' col-sm-3'];

        $this->detailFormQuestaoFormulario = new BootstrapFormBuilder('detailFormQuestaoFormulario');
        $this->detailFormQuestaoFormulario->setProperty('style', 'border:none; box-shadow:none; width:100%;');

        $this->detailFormQuestaoFormulario->setProperty('class', 'form-horizontal builder-detail-form');

        $row3 = $this->detailFormQuestaoFormulario->addFields([new TFormSeparator("Campos dinâmicos do formulário", '#333', '18', '#FF9800')]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->detailFormQuestaoFormulario->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$questao_formulario_nome,$questao_formulario_id],[new TLabel("Tipo campo:", '#ff0000', '14px', null, '100%'),$questao_formulario_tipo_campo]);
        $row4->layout = [' col-sm-9',' col-sm-3'];

        $row5 = $this->detailFormQuestaoFormulario->addFields([new TLabel("Obrigatório:", '#ff0000', '14px', null, '100%'),$questao_formulario_fl_obrigatorio],[new TLabel("Ativo:", '#ff0000', '14px', null, '100%'),$questao_formulario_ativo],[new TLabel("Opções:", null, '14px', null, '100%'),$questao_formulario_opcoes]);
        $row5->layout = [' col-sm-3',' col-sm-3',' col-sm-6'];

        $row6 = $this->detailFormQuestaoFormulario->addFields([$button_adicionar_questao_formulario]);
        $row6->layout = [' col-sm-12'];

        $row7 = $this->detailFormQuestaoFormulario->addFields([new THidden('questao_formulario__row__id')]);
        $this->questao_formulario_criteria = new TCriteria();

        $this->questao_formulario_list = new BootstrapDatagridWrapper(new TDataGrid);
        $this->questao_formulario_list->generateHiddenFields();
        $this->questao_formulario_list->setId('questao_formulario_list');

        $this->questao_formulario_list->style = 'width:100%';
        $this->questao_formulario_list->class .= ' table-bordered';

        $column_questao_formulario_nome = new TDataGridColumn('nome', "Nome", 'left');
        $column_questao_formulario_tipo_campo_transformed = new TDataGridColumn('tipo_campo', "Tipo campo", 'left' , '200px');
        $column_questao_formulario_fl_obrigatorio_transformed = new TDataGridColumn('fl_obrigatorio', "Obrigatório", 'center' , '150px');
        $column_questao_formulario_ativo_transformed = new TDataGridColumn('ativo', "Ativo", 'center' , '150px');

        $column_questao_formulario__row__data = new TDataGridColumn('__row__data', '', 'center');
        $column_questao_formulario__row__data->setVisibility(false);

        $action_onEditDetailQuestao = new TDataGridAction(array('FormularioForm', 'onEditDetailQuestao'));
        $action_onEditDetailQuestao->setUseButton(false);
        $action_onEditDetailQuestao->setButtonClass('btn btn-default btn-sm');
        $action_onEditDetailQuestao->setLabel("Editar");
        $action_onEditDetailQuestao->setImage('far:edit #478fca');
        $action_onEditDetailQuestao->setFields(['__row__id', '__row__data']);

        $this->questao_formulario_list->addAction($action_onEditDetailQuestao);
        $action_onDeleteDetailQuestao = new TDataGridAction(array('FormularioForm', 'onDeleteDetailQuestao'));
        $action_onDeleteDetailQuestao->setUseButton(false);
        $action_onDeleteDetailQuestao->setButtonClass('btn btn-default btn-sm');
        $action_onDeleteDetailQuestao->setLabel("Excluir");
        $action_onDeleteDetailQuestao->setImage('fas:trash-alt #dd5a43');
        $action_onDeleteDetailQuestao->setFields(['__row__id', '__row__data']);

        $this->questao_formulario_list->addAction($action_onDeleteDetailQuestao);

        $this->questao_formulario_list->addColumn($column_questao_formulario_nome);
        $this->questao_formulario_list->addColumn($column_questao_formulario_tipo_campo_transformed);
        $this->questao_formulario_list->addColumn($column_questao_formulario_fl_obrigatorio_transformed);
        $this->questao_formulario_list->addColumn($column_questao_formulario_ativo_transformed);

        $this->questao_formulario_list->addColumn($column_questao_formulario__row__data);

        $this->questao_formulario_list->createModel();
        $this->detailFormQuestaoFormulario->addContent([$this->questao_formulario_list]);

        $column_questao_formulario_tipo_campo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            return FormularioService::getTipos()[$value]??$value;

        });

        $column_questao_formulario_fl_obrigatorio_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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

        $column_questao_formulario_ativo_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
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
        });        $row8 = $this->form->addFields([$this->detailFormQuestaoFormulario]);
        $row8->layout = [' col-sm-12'];

        $row9 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row10 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row10->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['FormularioHeaderList', 'onShow']), 'fas:arrow-left #000000');
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

        $style = new TStyle('right-panel > .container-part[page-name=FormularioForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onChange($param = null) 
    {
        try 
        {
            if (! empty($param['questao_formulario_tipo_campo']) AND FormularioService::needOptions($param['questao_formulario_tipo_campo']))
            {
                TScript::create("$(\"[name='questao_formulario_opcoes[]']\").closest('.fb-field-container').show()");
            }
            else
            {
                TScript::create("$(\"[name='questao_formulario_opcoes[]']\").closest('.fb-field-container').hide()");
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public  function onAddDetailQuestaoFormulario($param = null) 
    {
        try
        {
            $data = $this->form->getData();

            $errors = [];
            $requiredFields = [];
            $requiredFields[] = ['label'=>"Nome", 'name'=>"questao_formulario_nome", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Tipo campo", 'name'=>"questao_formulario_tipo_campo", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Obrigatório", 'name'=>"questao_formulario_fl_obrigatorio", 'class'=>'TRequiredValidator', 'value'=>[]];
            $requiredFields[] = ['label'=>"Ativo", 'name'=>"questao_formulario_ativo", 'class'=>'TRequiredValidator', 'value'=>[]];
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

            $__row__id = !empty($data->questao_formulario__row__id) ? $data->questao_formulario__row__id : 'b'.uniqid();

            TTransaction::open(self::$database);

            $grid_data = new Questao();
            $grid_data->__row__id = $__row__id;
            $grid_data->nome = $data->questao_formulario_nome;
            $grid_data->id = $data->questao_formulario_id;
            $grid_data->tipo_campo = $data->questao_formulario_tipo_campo;
            $grid_data->fl_obrigatorio = $data->questao_formulario_fl_obrigatorio;
            $grid_data->ativo = $data->questao_formulario_ativo;
            $grid_data->opcoes = $data->questao_formulario_opcoes;

            $__row__data = array_merge($grid_data->toArray(), (array)$grid_data->getVirtualData());
            $__row__data['__row__id'] = $__row__id;
            $__row__data['__display__']['nome'] =  $param['questao_formulario_nome'] ?? null;
            $__row__data['__display__']['id'] =  $param['questao_formulario_id'] ?? null;
            $__row__data['__display__']['tipo_campo'] =  $param['questao_formulario_tipo_campo'] ?? null;
            $__row__data['__display__']['fl_obrigatorio'] =  $param['questao_formulario_fl_obrigatorio'] ?? null;
            $__row__data['__display__']['ativo'] =  $param['questao_formulario_ativo'] ?? null;
            $__row__data['__display__']['opcoes'] =  $param['questao_formulario_opcoes'] ?? null;

            $grid_data->__row__data = base64_encode(serialize((object)$__row__data));
            $row = $this->questao_formulario_list->addItem($grid_data);
            $row->id = $grid_data->__row__id;

            TDataGrid::replaceRowById('questao_formulario_list', $grid_data->__row__id, $row);

            TTransaction::close();

            $data = new stdClass;
            $data->questao_formulario_nome = '';
            $data->questao_formulario_id = '';
            $data->questao_formulario_tipo_campo = '';
            $data->questao_formulario_fl_obrigatorio = 'S';
            $data->questao_formulario_ativo = 'S';
            $data->questao_formulario_opcoes = [];
            $data->questao_formulario__row__id = '';

            TForm::sendData(self::$formName, $data);
            TScript::create("
               var element = $('#60fadb9a96256');
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

    public static function onEditDetailQuestao($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));
            $__row__data->__display__ = is_array($__row__data->__display__) ? (object) $__row__data->__display__ : $__row__data->__display__;
            $fireEvents = true;
            $aggregate = false;

            $data = new stdClass;
            $data->questao_formulario_nome = $__row__data->__display__->nome ?? null;
            $data->questao_formulario_id = $__row__data->__display__->id ?? null;
            $data->questao_formulario_tipo_campo = $__row__data->__display__->tipo_campo ?? null;
            $data->questao_formulario_fl_obrigatorio = $__row__data->__display__->fl_obrigatorio ?? null;
            $data->questao_formulario_ativo = $__row__data->__display__->ativo ?? null;
            $data->questao_formulario_opcoes = $__row__data->__display__->opcoes ?? null;
            $data->questao_formulario__row__id = $__row__data->__row__id;

            TForm::sendData(self::$formName, $data, $aggregate, $fireEvents);
            TScript::create("
               var element = $('#60fadb9a96256');
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
    public static function onDeleteDetailQuestao($param = null) 
    {
        try
        {

            $__row__data = unserialize(base64_decode($param['__row__data']));

            $data = new stdClass;
            $data->questao_formulario_nome = '';
            $data->questao_formulario_id = '';
            $data->questao_formulario_tipo_campo = '';
            $data->questao_formulario_fl_obrigatorio = '';
            $data->questao_formulario_ativo = '';
            $data->questao_formulario_opcoes = '';
            $data->questao_formulario__row__id = '';

            TForm::sendData(self::$formName, $data);

            TDataGrid::removeRowById('questao_formulario_list', $__row__data->__row__id);
            TScript::create("
               var element = $('#60fadb9a96256');
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

            $object = new Formulario(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if (! $data->id)
            {
                $repository = new TRepository('Formulario');
                $ordem = $repository->maxBy('ordem');

                $object->ordem = $ordem ? $ordem + 1 : 1;
            }
            else
            {
                unset($object->ordem);
            }

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

            $questao_formulario_items = $this->storeMasterDetailItems('Questao', 'formulario_id', 'questao_formulario', $object, $param['questao_formulario_list___row__data'] ?? [], $this->form, $this->questao_formulario_list, function($masterObject, $detailObject){ 

                $detailObject->opcoes = implode(';', $detailObject->opcoes);

            }, $this->questao_formulario_criteria); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('FormularioHeaderList', 'onShow', $loadPageParam); 

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
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Formulario($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $questao_formulario_items = $this->loadMasterDetailItems('Questao', 'formulario_id', 'questao_formulario', $object, $this->form, $this->questao_formulario_list, $this->questao_formulario_criteria, function($masterObject, $detailObject, $objectItems){ 

                    //$objectItems->opcoes = explode(";", $detailObject->opcoes);

                    $objectItems->opcoes = explode(';', $detailObject->opcoes);

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

