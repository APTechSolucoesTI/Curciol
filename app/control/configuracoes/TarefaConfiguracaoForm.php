<?php

class TarefaConfiguracaoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'TarefaConfiguracao';
    private static $primaryKey = 'id';
    private static $formName = 'form_TarefaConfiguracaoForm';

    use BuilderMasterDetailFieldListTrait;

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
        $this->form->setFormTitle("Configuração de tarefa");

        $criteria_status_inicial_id = new TCriteria();
        $criteria_status_final_id = new TCriteria();
        $criteria_status_cancelado_id = new TCriteria();
        $criteria_tarefa_usuario_master_tarefa_configuracao_usuario_master_id = new TCriteria();
        $criteria_modificacao_user_id = new TCriteria();

        $filterVar = "1";
        $criteria_status_inicial_id->add(new TFilter('kanban', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_status_final_id->add(new TFilter('fim', '=', $filterVar)); 
        $filterVar = "S";
        $criteria_status_cancelado_id->add(new TFilter('fim', '=', $filterVar)); 

        TTransaction::open(self::$database);
        $criteria_status_inicial_id = new TCriteria();
        $criteria_status_final_id = new TCriteria();

        $filterVar = TarefaStatus::where('kanban','>',0)->orderby('kanban')->first();
        $criteria_status_inicial_id->add(new TFilter('kanban', '=', $filterVar->kanban)); 
        $filterVar = TarefaStatus::where('kanban','>',0)->orderby('kanban')->last();
        $criteria_status_final_id->add(new TFilter('kanban', '=', $filterVar->kanban)); 

        TTransaction::close();

        $id = new THidden('id');
        $status_inicial_id = new TDBCombo('status_inicial_id', 'escritorio', 'TarefaStatus', 'id', '{nome}','nome asc' , $criteria_status_inicial_id );
        $status_final_id = new TDBCombo('status_final_id', 'escritorio', 'TarefaStatus', 'id', '{nome}','nome asc' , $criteria_status_final_id );
        $status_cancelado_id = new TDBCombo('status_cancelado_id', 'escritorio', 'TarefaStatus', 'id', '{nome}','nome asc' , $criteria_status_cancelado_id );
        $tem_dtvalidacao = new TCheckButton('tem_dtvalidacao');
        $dtvalidacao_obrigatoria = new TCheckButton('dtvalidacao_obrigatoria');
        $tarefa_usuario_master_tarefa_configuracao_id = new THidden('tarefa_usuario_master_tarefa_configuracao_id[]');
        $tarefa_usuario_master_tarefa_configuracao___row__id = new THidden('tarefa_usuario_master_tarefa_configuracao___row__id[]');
        $tarefa_usuario_master_tarefa_configuracao___row__data = new THidden('tarefa_usuario_master_tarefa_configuracao___row__data[]');
        $tarefa_usuario_master_tarefa_configuracao_usuario_master_id = new TDBCombo('tarefa_usuario_master_tarefa_configuracao_usuario_master_id[]', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_tarefa_usuario_master_tarefa_configuracao_usuario_master_id );
        $this->fieldList_master = new TFieldList();
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_id = new TDBCombo('modificacao_user_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_modificacao_user_id );

        $this->fieldList_master->addField(null, $tarefa_usuario_master_tarefa_configuracao_id, []);
        $this->fieldList_master->addField(null, $tarefa_usuario_master_tarefa_configuracao___row__id, ['uniqid' => true]);
        $this->fieldList_master->addField(null, $tarefa_usuario_master_tarefa_configuracao___row__data, []);
        $this->fieldList_master->addField(new TLabel("Usuário master", null, '14px', null), $tarefa_usuario_master_tarefa_configuracao_usuario_master_id, ['width' => '100%']);

        $this->fieldList_master->width = '100%';
        $this->fieldList_master->setFieldPrefix('tarefa_usuario_master_tarefa_configuracao');
        $this->fieldList_master->name = 'fieldList_master';

        $this->criteria_fieldList_master = new TCriteria();
        $this->default_item_fieldList_master = new stdClass();

        $this->form->addField($tarefa_usuario_master_tarefa_configuracao_id);
        $this->form->addField($tarefa_usuario_master_tarefa_configuracao___row__id);
        $this->form->addField($tarefa_usuario_master_tarefa_configuracao___row__data);
        $this->form->addField($tarefa_usuario_master_tarefa_configuracao_usuario_master_id);

        $this->fieldList_master->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $tem_dtvalidacao->setChangeAction(new TAction([$this,'onSelectTemValidacao']));

        $status_inicial_id->addValidation("Status inicial ao cadastrar tarefa", new TRequiredValidator()); 
        $status_final_id->addValidation("Status final", new TRequiredValidator()); 

        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $tem_dtvalidacao->setValue('N');
        $dtvalidacao_obrigatoria->setValue('N');

        $tem_dtvalidacao->setUseSwitch(true, 'blue');
        $dtvalidacao_obrigatoria->setUseSwitch(true, 'blue');

        $tem_dtvalidacao->setIndexValue("S");
        $dtvalidacao_obrigatoria->setIndexValue("S");

        $tem_dtvalidacao->setInactiveIndexValue("N");
        $dtvalidacao_obrigatoria->setInactiveIndexValue("N");

        $data_modificacao->setEditable(false);
        $modificacao_user_id->setEditable(false);

        $status_final_id->enableSearch();
        $status_inicial_id->enableSearch();
        $status_cancelado_id->enableSearch();
        $modificacao_user_id->enableSearch();
        $tarefa_usuario_master_tarefa_configuracao_usuario_master_id->enableSearch();

        $id->setSize(200);
        $status_final_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $status_inicial_id->setSize('100%');
        $status_cancelado_id->setSize('100%');
        $modificacao_user_id->setSize('100%');
        $tarefa_usuario_master_tarefa_configuracao_usuario_master_id->setSize('100%');

        $row1 = $this->form->addFields([$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Status inicial:", '#ff0000', '14px', null, '100%'),$status_inicial_id],[new TLabel("Status final:", '#ff0000', '14px', null, '100%'),$status_final_id],[new TLabel("Status cancelado:", '#FF0000', '14px', null, '100%'),$status_cancelado_id]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row3 = $this->form->addFields([new TLabel("Habilitar prazo de validação:", '#FF0000', '14px', null, '100%'),$tem_dtvalidacao],[new TLabel("Prazo de validação obrigatório:", '#FF0000', '14px', null, '100%'),$dtvalidacao_obrigatoria]);
        $row3->layout = ['col-sm-6','col-sm-6'];

        $row4 = $this->form->addFields([new TLabel("Usuários lideres de tarefas:", null, '14px', null, '100%'),$this->fieldList_master]);
        $row4->layout = [' col-12 col-sm-12 col-lg-12 col-xl-12 col-md-12'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row6 = $this->form->addFields([],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_id]);
        $row6->layout = ['col-sm-6','col-sm-3','col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=TarefaConfiguracaoForm]');
        $style->width = '60% !important';   
        $style->show(true);

    }

    public static function onSelectTemValidacao($param = null) 
    {
        try 
        {
            if($param['tem_dtvalidacao']!=="S"){
                TScript::create("$('label:contains(\"Data de validação obrigatória:\")').hide();");
                TScript::create("$(\"[name='dtvalidacao_obrigatoria']\").closest('.fb-inline-field-container').hide()");
            }else{
                TScript::create("$('label:contains(\"Data de validação obrigatória:\")').show();");
                TScript::create("$(\"[name='dtvalidacao_obrigatoria']\").closest('.fb-inline-field-container').show()");
            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new TarefaConfiguracao(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if($object->tem_dtvalidacao !== "S"){
                $object->dtvalidacao_obrigatoria = "N"; 
            }

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

//<generatedAutoCode>
            $this->criteria_fieldList_master->setProperty('order', 'id desc');
//</generatedAutoCode>
            $tarefa_usuario_master_tarefa_configuracao_items = $this->storeItems('TarefaUsuarioMaster', 'tarefa_configuracao_id', $object, $this->fieldList_master, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_master); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('TarefaList', 'onShow', $loadPageParam); 

                        TScript::create("Template.closeRightPanel();");
            TForm::sendData(self::$formName, (object)['id' => $object->id]);

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

                $object = new TarefaConfiguracao($key); // instantiates the Active Record 

                $this->criteria_fieldList_master->setProperty('order', 'id desc');
                $this->fieldList_master_items = $this->loadItems('TarefaUsuarioMaster', 'tarefa_configuracao_id', $object, $this->fieldList_master, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_master); 

                $this->form->setData($object); // fill the form 
                if($object->tem_dtvalidacao!=="S"){
                    TScript::create("$('label:contains(\"Data de validação obrigatória:\")').hide();");
                    TScript::create("$(\"[name='dtvalidacao_obrigatoria']\").closest('.fb-inline-field-container').hide()");
                }else{
                    TScript::create("$('label:contains(\"Data de validação obrigatória:\")').show();");
                    TScript::create("$(\"[name='dtvalidacao_obrigatoria']\").closest('.fb-inline-field-container').show()");
                }

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

        $this->fieldList_master->addHeader();
        $this->fieldList_master->addDetail($this->default_item_fieldList_master);

        $this->fieldList_master->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_master->addHeader();
        $this->fieldList_master->addDetail($this->default_item_fieldList_master);

        $this->fieldList_master->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        TScript::create("$('label:contains(\"Data de validação obrigatória:\")').hide();");
        TScript::create("$(\"[name='dtvalidacao_obrigatoria']\").closest('.fb-inline-field-container').hide()");
    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

