<?php

class MovimentacaoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Movimentacao';
    private static $primaryKey = 'id';
    private static $formName = 'form_Movimentacao';

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
        $this->form->setFormTitle("Movimentação");

        $criteria_material_id = new TCriteria();

        $tipo = new TRadioGroup('tipo');
        $material_id = new TDBUniqueSearch('material_id', 'escritorio', 'Material', 'id', 'nome','nome asc' , $criteria_material_id );
        $quantidade = new TNumeric('quantidade', '2', ',', '.' );

        $tipo->addValidation("Tipo de movimentação", new TRequiredValidator()); 
        $material_id->addValidation("Material", new TRequiredValidator()); 

        $tipo->addItems(["-1"=>"Baixa","1"=>"Reposição"]);
        $tipo->setLayout('horizontal');
        $tipo->setUseButton();
        $material_id->setMinLength(3);
        $material_id->setMask('{nome} ({unidade_medida->sigla})');
        $tipo->setValue('1');
        $material_id->setValue($param['key']??'');

        $tipo->setSize(150);
        $quantidade->setSize('100%');
        $material_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Tipo de movimentação:", '#FF0000', '14px', null, '100%'),$tipo]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([new TLabel("Material:", '#ff0000', '14px', null, '100%'),$material_id]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([new TLabel("Quantidade:", null, '14px', null, '100%'),$quantidade]);
        $row3->layout = ['col-sm-6'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Atualizar estoque", new TAction([$this, 'onSave']), 'fas:save #ffffff');
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

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Movimentacao(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->quantidade = $data->tipo * $object->quantidade;
            $object->system_user_id = TSession::getValue('userid');
            $object->dt_movimentacao = date('Y-m-d');

            $object->material->estoque_atualizado += $object->quantidade;
            $object->material->store();

            $object->store(); // save the object 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

            AdiantiCoreApplication::loadPage('MaterialList', 'onShow');
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

                $object = new Movimentacao($key); // instantiates the Active Record 

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

