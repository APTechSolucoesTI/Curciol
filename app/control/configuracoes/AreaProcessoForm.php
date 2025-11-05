<?php

class AreaProcessoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Area';
    private static $primaryKey = 'id';
    private static $formName = 'form_AreaProcessoForm';

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
        $this->form->setFormTitle("Cadastro de área de processo");

        $criteria_documento_base_contrato_area_modelo_documento_id = new TCriteria();
        $criteria_criacao_user_id = new TCriteria();
        $criteria_modificacao_user_id = new TCriteria();

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $documento_base_contrato_area_id = new THidden('documento_base_contrato_area_id[]');
        $documento_base_contrato_area___row__id = new THidden('documento_base_contrato_area___row__id[]');
        $documento_base_contrato_area___row__data = new THidden('documento_base_contrato_area___row__data[]');
        $documento_base_contrato_area_modelo_documento_id = new TDBCombo('documento_base_contrato_area_modelo_documento_id[]', 'escritorio', 'ModeloDocumento', 'id', '{nome}','nome asc' , $criteria_documento_base_contrato_area_modelo_documento_id );
        $this->fieldList_65e9ce91161b8 = new TFieldList();
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_id = new TDBCombo('criacao_user_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_criacao_user_id );
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_id = new TDBCombo('modificacao_user_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_modificacao_user_id );

        $this->fieldList_65e9ce91161b8->addField(null, $documento_base_contrato_area_id, []);
        $this->fieldList_65e9ce91161b8->addField(null, $documento_base_contrato_area___row__id, ['uniqid' => true]);
        $this->fieldList_65e9ce91161b8->addField(null, $documento_base_contrato_area___row__data, []);
        $this->fieldList_65e9ce91161b8->addField(new TLabel("Modelo de documento", null, '14px', null), $documento_base_contrato_area_modelo_documento_id, ['width' => '100%']);

        $this->fieldList_65e9ce91161b8->width = '100%';
        $this->fieldList_65e9ce91161b8->setFieldPrefix('documento_base_contrato_area');
        $this->fieldList_65e9ce91161b8->name = 'fieldList_65e9ce91161b8';

        $this->criteria_fieldList_65e9ce91161b8 = new TCriteria();
        $this->default_item_fieldList_65e9ce91161b8 = new stdClass();

        $this->form->addField($documento_base_contrato_area_id);
        $this->form->addField($documento_base_contrato_area___row__id);
        $this->form->addField($documento_base_contrato_area___row__data);
        $this->form->addField($documento_base_contrato_area_modelo_documento_id);

        $this->fieldList_65e9ce91161b8->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $documento_base_contrato_area_modelo_documento_id->addValidation("Modelo documento id", new TRequiredListValidator()); 

        $nome->setMaxLength(255);
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $criacao_user_id->enableSearch();
        $modificacao_user_id->enableSearch();
        $documento_base_contrato_area_modelo_documento_id->enableSearch();

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $criacao_user_id->setEditable(false);
        $data_modificacao->setEditable(false);
        $modificacao_user_id->setEditable(false);

        $id->setSize(100);
        $nome->setSize('100%');
        $data_criacao->setSize('100%');
        $criacao_user_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $modificacao_user_id->setSize('100%');
        $documento_base_contrato_area_modelo_documento_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row4 = $this->form->addFields([new TLabel("Documentos obrigatórios:", null, '14px', null, '100%'),$this->fieldList_65e9ce91161b8]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row6 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_id],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_id]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['AreaProcessoList', 'onShow']), 'fas:arrow-left #000000');
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

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Area(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

//<generatedAutoCode>
            $this->criteria_fieldList_65e9ce91161b8->setProperty('order', 'id asc');
//</generatedAutoCode>
            $documento_base_contrato_area_items = $this->storeItems('DocumentoBaseContrato', 'area_id', $object, $this->fieldList_65e9ce91161b8, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_65e9ce91161b8); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('AreaProcessoList', 'onShow', $loadPageParam); 

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

                $object = new Area($key); // instantiates the Active Record 

                $this->criteria_fieldList_65e9ce91161b8->setProperty('order', 'id asc');
                $this->fieldList_65e9ce91161b8_items = $this->loadItems('DocumentoBaseContrato', 'area_id', $object, $this->fieldList_65e9ce91161b8, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_65e9ce91161b8); 

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

        $this->fieldList_65e9ce91161b8->addHeader();
        $this->fieldList_65e9ce91161b8->addDetail($this->default_item_fieldList_65e9ce91161b8);

        $this->fieldList_65e9ce91161b8->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_65e9ce91161b8->addHeader();
        $this->fieldList_65e9ce91161b8->addDetail($this->default_item_fieldList_65e9ce91161b8);

        $this->fieldList_65e9ce91161b8->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

