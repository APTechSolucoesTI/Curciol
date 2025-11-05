<?php

class ContratoProcessoForm extends TWindow
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'ContratoProcesso';
    private static $primaryKey = 'id';
    private static $formName = 'form_ContratoProcessoForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        parent::setSize(600, null);
        parent::setTitle("Adicionar Contrato ao Processo");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Adicionar Contrato ao Processo");

        $criteria_contrato_id = new TCriteria();

        $id = new THidden('id');
        $processo_id = new THidden('processo_id');
        $contrato_id = new TDBUniqueSearch('contrato_id', 'escritorio', 'Contrato', 'id', 'objeto','numero asc' , $criteria_contrato_id );

        $contrato_id->addValidation("Contrato", new TRequiredValidator()); 

        $processo_id->setValue($param['processo_id'] ?? null);
        $contrato_id->setMinLength(3);
        $contrato_id->setMask('{numero} - {objeto}');
        $contrato_id->setFilterColumns(["numero","objeto"]);
        $contrato_id->disableIdSearch();
        $id->setSize(200);
        $processo_id->setSize(200);
        $contrato_id->setSize('100%');

        $row1 = $this->form->addFields([$id,$processo_id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Contrato:", '#FF0000', '14px', null)],[$contrato_id]);

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        parent::add($this->form);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new ContratoProcesso(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            if(!empty($param['processo_id']))
            {
                $loadPageParam["key"] = $param['processo_id'];
            }

            $loadPageParam["current_tab"] = "1"; 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ProcessoFormView', 'onShow', $loadPageParam); 

                TWindow::closeWindow(parent::getId()); 

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

                $object = new ContratoProcesso($key); // instantiates the Active Record 

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

