<?php

class ProcedimentoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Procedimento';
    private static $primaryKey = 'id';
    private static $formName = 'form_Procedimento';

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
        $this->form->setFormTitle("Cadastro de procedimento");

        $criteria_procedimento_preco_procedimento_parceiro_id = new TCriteria();

        $id = new TEntry('id');
        $cor = new TColor('cor');
        $ativo = new TRadioGroup('ativo');
        $nome = new TEntry('nome');
        $procedimento_preco_procedimento_id = new THidden('procedimento_preco_procedimento_id[]');
        $procedimento_preco_procedimento___row__id = new THidden('procedimento_preco_procedimento___row__id[]');
        $procedimento_preco_procedimento___row__data = new THidden('procedimento_preco_procedimento___row__data[]');
        $procedimento_preco_procedimento_parceiro_id = new TDBCombo('procedimento_preco_procedimento_parceiro_id[]', 'escritorio', 'Parceiro', 'id', '{nome}','nome asc' , $criteria_procedimento_preco_procedimento_parceiro_id );
        $procedimento_preco_procedimento_valor = new TNumeric('procedimento_preco_procedimento_valor[]', '2', ',', '.' );
        $this->fieldList_60f87238c3d31 = new TFieldList();
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $this->fieldList_60f87238c3d31->addField(null, $procedimento_preco_procedimento_id, []);
        $this->fieldList_60f87238c3d31->addField(null, $procedimento_preco_procedimento___row__id, ['uniqid' => true]);
        $this->fieldList_60f87238c3d31->addField(null, $procedimento_preco_procedimento___row__data, []);
        $this->fieldList_60f87238c3d31->addField(new TLabel("Parceiro", null, '14px', null), $procedimento_preco_procedimento_parceiro_id, ['width' => '70%']);
        $this->fieldList_60f87238c3d31->addField(new TLabel("Valor", null, '14px', null), $procedimento_preco_procedimento_valor, ['width' => '70%']);

        $this->fieldList_60f87238c3d31->width = '100%';
        $this->fieldList_60f87238c3d31->setFieldPrefix('procedimento_preco_procedimento');
        $this->fieldList_60f87238c3d31->name = 'fieldList_60f87238c3d31';

        $this->criteria_fieldList_60f87238c3d31 = new TCriteria();
        $this->default_item_fieldList_60f87238c3d31 = new stdClass();

        $this->form->addField($procedimento_preco_procedimento_id);
        $this->form->addField($procedimento_preco_procedimento___row__id);
        $this->form->addField($procedimento_preco_procedimento___row__data);
        $this->form->addField($procedimento_preco_procedimento_parceiro_id);
        $this->form->addField($procedimento_preco_procedimento_valor);

        $this->fieldList_60f87238c3d31->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $cor->addValidation("Cor", new TRequiredValidator()); 
        $nome->addValidation("Nome", new TRequiredValidator()); 
        $procedimento_preco_procedimento_parceiro_id->addValidation("Convenio id", new TRequiredListValidator()); 
        $procedimento_preco_procedimento_valor->addValidation("Valor", new TRequiredListValidator()); 

        $ativo->addItems(["S"=>"Sim","N"=>"Não"]);
        $ativo->setLayout('horizontal');
        $ativo->setUseButton();
        $nome->forceUpperCase();
        $ativo->setValue('S');
        $cor->setValue('#ffffff');

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
        $cor->setSize('100%');
        $nome->setSize('100%');
        $ativo->setSize('100%');
        $data_criacao->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $procedimento_preco_procedimento_valor->setSize('100%');
        $procedimento_preco_procedimento_parceiro_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id],[new TLabel("Cor:", '#ff0000', '14px', null, '100%'),$cor],[new TLabel("Ativo:", null, '14px', null, '100%'),$ativo]);
        $row1->layout = [' col-sm-6','col-sm-3',' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([new TFormSeparator("Preços", '#333', '18', '#eee')]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addFields([$this->fieldList_60f87238c3d31]);
        $row4->layout = [' col-sm-6'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row6 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row6->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['ProcedimentosList', 'onShow']), 'fas:arrow-left #000000');
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

        $style = new TStyle('right-panel > .container-part[page-name=ProcedimentoForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Procedimento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            $procedimento_preco_procedimento_items = $this->storeItems('ProcedimentoPreco', 'procedimento_id', $object, $this->fieldList_60f87238c3d31, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_60f87238c3d31); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ProcedimentosList', 'onShow', $loadPageParam); 

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

                $object = new Procedimento($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->fieldList_60f87238c3d31_items = $this->loadItems('ProcedimentoPreco', 'procedimento_id', $object, $this->fieldList_60f87238c3d31, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_60f87238c3d31); 

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

        $this->fieldList_60f87238c3d31->addHeader();
        $this->fieldList_60f87238c3d31->addDetail($this->default_item_fieldList_60f87238c3d31);

        $this->fieldList_60f87238c3d31->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_60f87238c3d31->addHeader();
        $this->fieldList_60f87238c3d31->addDetail($this->default_item_fieldList_60f87238c3d31);

        $this->fieldList_60f87238c3d31->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

