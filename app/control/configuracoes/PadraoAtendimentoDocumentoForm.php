<?php

class PadraoAtendimentoDocumentoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'PadraoAtendimentoDocumento';
    private static $primaryKey = 'id';
    private static $formName = 'form_PadraoAtendimentoDocumentoForm';

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
        $this->form->setFormTitle("Cadastro de padrão para documento no atendimento");

        $criteria_padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id = new TCriteria();

        $filterVar = ModeloDocTipoAplicacao::ATENDIMENTO;
        $criteria_padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id->add(new TFilter('id', 'in', "(SELECT modelo_documento_id FROM modelo_doc_aplicacao WHERE tipo_aplicacao_id = '{$filterVar}')")); 

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_id = new THidden('padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_id[]');
        $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento___row__id = new THidden('padrao_atend_modelo_doc_tipo_padrao_doc_atendimento___row__id[]');
        $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento___row__data = new THidden('padrao_atend_modelo_doc_tipo_padrao_doc_atendimento___row__data[]');
        $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id = new TDBCombo('padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id[]', 'escritorio', 'ModeloDocumento', 'id', '{nome}','nome asc' , $criteria_padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id );
        $this->fieldList_6566168820062 = new TFieldList();
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $this->fieldList_6566168820062->addField(null, $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_id, []);
        $this->fieldList_6566168820062->addField(null, $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento___row__id, ['uniqid' => true]);
        $this->fieldList_6566168820062->addField(null, $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento___row__data, []);
        $this->fieldList_6566168820062->addField(new TLabel("Modelo de documento", null, '14px', null), $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id, ['width' => '100%']);

        $this->fieldList_6566168820062->width = '100%';
        $this->fieldList_6566168820062->setFieldPrefix('padrao_atend_modelo_doc_tipo_padrao_doc_atendimento');
        $this->fieldList_6566168820062->name = 'fieldList_6566168820062';

        $this->criteria_fieldList_6566168820062 = new TCriteria();
        $this->default_item_fieldList_6566168820062 = new stdClass();

        $this->form->addField($padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_id);
        $this->form->addField($padrao_atend_modelo_doc_tipo_padrao_doc_atendimento___row__id);
        $this->form->addField($padrao_atend_modelo_doc_tipo_padrao_doc_atendimento___row__data);
        $this->form->addField($padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id);

        $this->fieldList_6566168820062->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id->addValidation("Tipo documento id", new TRequiredListValidator()); 

        $nome->setMaxLength(255);
        $nome->forceUpperCase();
        $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id->enableSearch();
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $nome->setSize('100%');
        $data_criacao->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_modelo_documento_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([$this->fieldList_6566168820062]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row5 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row5->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['PadraoAtendimentoDocumentoList', 'onShow']), 'fas:arrow-left #000000');
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

            $object = new PadraoAtendimentoDocumento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 
            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            $padrao_atend_modelo_doc_tipo_padrao_doc_atendimento_items = $this->storeItems('PadraoAtendModeloDoc', 'tipo_padrao_doc_atendimento_id', $object, $this->fieldList_6566168820062, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_6566168820062); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('PadraoAtendimentoDocumentoList', 'onShow', $loadPageParam); 

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

                $object = new PadraoAtendimentoDocumento($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->fieldList_6566168820062_items = $this->loadItems('PadraoAtendModeloDoc', 'tipo_padrao_doc_atendimento_id', $object, $this->fieldList_6566168820062, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_6566168820062); 

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

        $this->fieldList_6566168820062->addHeader();
        $this->fieldList_6566168820062->addDetail($this->default_item_fieldList_6566168820062);

        $this->fieldList_6566168820062->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_6566168820062->addHeader();
        $this->fieldList_6566168820062->addDetail($this->default_item_fieldList_6566168820062);

        $this->fieldList_6566168820062->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

