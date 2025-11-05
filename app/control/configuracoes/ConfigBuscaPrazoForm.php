<?php

class ConfigBuscaPrazoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'ConfigBuscaPrazo';
    private static $primaryKey = 'id';
    private static $formName = 'form_ConfigBuscaPrazoForm';

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
        $this->form->setFormTitle("Cadastro de busca por prazo");

        $criteria_tipo_prazo_id = new TCriteria();
        $criteria_config_busca_a_partir_id = new TCriteria();
        $criteria_criacao_user_id = new TCriteria();
        $criteria_modificacao_user_id = new TCriteria();

        $id = new TEntry('id');
        $pont = new TEntry('pont');
        $titulo = new TEntry('titulo');
        $prazo = new TSpinner('prazo');
        $tipo_prazo_id = new TDBCombo('tipo_prazo_id', 'escritorio', 'TipoPrazo', 'id', '{nome}','nome asc' , $criteria_tipo_prazo_id );
        $config_busca_a_partir_id = new TDBCombo('config_busca_a_partir_id', 'escritorio', 'ConfigBuscaAPartir', 'id', '{nome} - Adicionar {add_dias} dias','nome asc' , $criteria_config_busca_a_partir_id );
        $config_busca_prazo_texto_config_busca_prazo_id = new THidden('config_busca_prazo_texto_config_busca_prazo_id[]');
        $config_busca_prazo_texto_config_busca_prazo___row__id = new THidden('config_busca_prazo_texto_config_busca_prazo___row__id[]');
        $config_busca_prazo_texto_config_busca_prazo___row__data = new THidden('config_busca_prazo_texto_config_busca_prazo___row__data[]');
        $config_busca_prazo_texto_config_busca_prazo_texto = new TText('config_busca_prazo_texto_config_busca_prazo_texto[]');
        $this->fieldList_texto = new TFieldList();
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_id = new TDBCombo('criacao_user_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_criacao_user_id );
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_id = new TDBCombo('modificacao_user_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_modificacao_user_id );

        $this->fieldList_texto->addField(null, $config_busca_prazo_texto_config_busca_prazo_id, []);
        $this->fieldList_texto->addField(null, $config_busca_prazo_texto_config_busca_prazo___row__id, ['uniqid' => true]);
        $this->fieldList_texto->addField(null, $config_busca_prazo_texto_config_busca_prazo___row__data, []);
        $this->fieldList_texto->addField(new TLabel("Texto", null, '14px', null), $config_busca_prazo_texto_config_busca_prazo_texto, ['width' => '100%']);

        $this->fieldList_texto->width = '100%';
        $this->fieldList_texto->setFieldPrefix('config_busca_prazo_texto_config_busca_prazo');
        $this->fieldList_texto->name = 'fieldList_texto';

        $this->criteria_fieldList_texto = new TCriteria();
        $this->default_item_fieldList_texto = new stdClass();

        $this->form->addField($config_busca_prazo_texto_config_busca_prazo_id);
        $this->form->addField($config_busca_prazo_texto_config_busca_prazo___row__id);
        $this->form->addField($config_busca_prazo_texto_config_busca_prazo___row__data);
        $this->form->addField($config_busca_prazo_texto_config_busca_prazo_texto);

        $this->fieldList_texto->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $titulo->addValidation("Título", new TRequiredValidator()); 
        $prazo->addValidation("Prazo", new TRequiredValidator()); 
        $tipo_prazo_id->addValidation("Tipo prazo id", new TRequiredValidator()); 
        $config_busca_a_partir_id->addValidation("A contar de", new TRequiredValidator()); 

        $titulo->setMaxLength(255);
        $prazo->setRange(1, 2000, 1);
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $tipo_prazo_id->enableSearch();
        $criacao_user_id->enableSearch();
        $modificacao_user_id->enableSearch();
        $config_busca_a_partir_id->enableSearch();

        $id->setEditable(false);
        $pont->setEditable(false);
        $data_criacao->setEditable(false);
        $criacao_user_id->setEditable(false);
        $data_modificacao->setEditable(false);
        $modificacao_user_id->setEditable(false);

        $id->setSize(100);
        $pont->setSize('100%');
        $prazo->setSize('100%');
        $titulo->setSize('100%');
        $data_criacao->setSize('100%');
        $tipo_prazo_id->setSize('100%');
        $criacao_user_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $modificacao_user_id->setSize('100%');
        $config_busca_a_partir_id->setSize('100%');
        $config_busca_prazo_texto_config_busca_prazo_texto->setSize('100%', 70);


        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id],[new TLabel("Peso:", null, '14px', null, '100%'),$pont]);
        $row1->layout = ['col-sm-6','col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Título:", '#ff0000', '14px', null, '100%'),$titulo]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([new TLabel("Prazo:", '#ff0000', '14px', null, '100%'),$prazo],[new TLabel("Tipo de prazo:", '#ff0000', '14px', null, '100%'),$tipo_prazo_id],[new TLabel("A contar de:", '#FF0000', '14px', null, '100%'),$config_busca_a_partir_id]);
        $row3->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row4 = $this->form->addFields([new TLabel("Buscas:", '#ff0000', '14px', null, '100%'),$this->fieldList_texto]);
        $row4->layout = [' col-12 col-sm-12 col-lg-12 col-xl-12 col-md-12'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row6 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_id],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_id]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['ConfigBuscaPrazoHeaderList', 'onShow']), 'fas:arrow-left #000000');
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

        $style = new TStyle('right-panel > .container-part[page-name=ConfigBuscaPrazoForm]');
        $style->width = '60% !important';   
        $style->show(true);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new ConfigBuscaPrazo(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

//<generatedAutoCode>
            $this->criteria_fieldList_texto->setProperty('order', 'id desc');
//</generatedAutoCode>
            $config_busca_prazo_texto_config_busca_prazo_items = $this->storeItems('ConfigBuscaPrazoTexto', 'config_busca_prazo_id', $object, $this->fieldList_texto, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_texto); 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ConfigBuscaPrazoHeaderList', 'onShow', $loadPageParam); 

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

                $object = new ConfigBuscaPrazo($key); // instantiates the Active Record 

                $this->criteria_fieldList_texto->setProperty('order', 'id desc');
                $this->fieldList_texto_items = $this->loadItems('ConfigBuscaPrazoTexto', 'config_busca_prazo_id', $object, $this->fieldList_texto, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_texto); 

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

        $this->fieldList_texto->addHeader();
        $this->fieldList_texto->addDetail($this->default_item_fieldList_texto);

        $this->fieldList_texto->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_texto->addHeader();
        $this->fieldList_texto->addDetail($this->default_item_fieldList_texto);

        $this->fieldList_texto->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

