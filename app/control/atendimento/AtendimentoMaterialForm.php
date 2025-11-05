<?php

class AtendimentoMaterialForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'AtendimentoMaterial';
    private static $primaryKey = 'id';
    private static $formName = 'form_AtendimentoMaterialForm';

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
        $this->form->setFormTitle("Material");

        $criteria_material_id = new TCriteria();

        $id = new TEntry('id');
        $atendimento_id = new THidden('atendimento_id');
        $material_id = new TDBCombo('material_id', 'escritorio', 'Material', 'id', '{nome} - {unidade_medida->sigla}','nome asc' , $criteria_material_id );
        $quantidade = new TNumeric('quantidade', '2', ',', '.' );

        $material_id->addValidation("Material", new TRequiredValidator()); 
        $quantidade->addValidation("Quantidade", new TRequiredValidator()); 

        $id->setEditable(false);
        $material_id->enableSearch();
        $quantidade->setValue('1,00');
        $atendimento_id->setValue($param['atendimento_id'] ?? null);

        $id->setSize(100);
        $quantidade->setSize('100%');
        $atendimento_id->setSize(200);
        $material_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id,$atendimento_id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Material:", '#ff0000', '14px', null, '100%'),$material_id],[new TLabel("Quantidade:", '#ff0000', '14px', null, '100%'),$quantidade]);
        $row2->layout = [' col-sm-9',' col-sm-3'];

        $row1->style = 'display: none';

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AtendimentoMaterialForm]');
        $style->width = '50% !important';   
        $style->show(true);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new AtendimentoMaterial(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            if(!empty($object->atendimento_id))
            {
                $loadPageParam["id"] = $object->atendimento_id;
            }

            if(!empty($object->atendimento_id))
            {
                $loadPageParam["key"] = $object->atendimento_id;
            }

            $loadPageParam["current_tab_abas"] = "2"; 

            AtendimentoService::subtrairEstoque($object, TSession::getValue('userid'));

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('AtendimentoFormView', 'onShow', $loadPageParam); 

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

                $object = new AtendimentoMaterial($key); // instantiates the Active Record 

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

