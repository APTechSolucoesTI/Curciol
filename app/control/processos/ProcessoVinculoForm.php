<?php

class ProcessoVinculoForm extends TWindow
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'ProcessoVinculo';
    private static $primaryKey = 'id';
    private static $formName = 'form_ProcessoVinculoForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();
        parent::setSize(600, null);
        parent::setTitle("Vincular processos");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Vincular processos");

        $criteria_processo_principal_id = new TCriteria();
        $criteria_processo_incidente_id = new TCriteria();

        $id = new THidden('id');
        $tela = new THidden('tela');
        $processo_principal_id = new TDBUniqueSearch('processo_principal_id', 'escritorio', 'Processo', 'id', 'numero_cnj_numero','numero_cnj_numero asc' , $criteria_processo_principal_id );
        $processo_incidente_id = new TDBUniqueSearch('processo_incidente_id', 'escritorio', 'Processo', 'id', 'numero_cnj_numero','numero_cnj_numero asc' , $criteria_processo_incidente_id );

        $processo_principal_id->addValidation("Processo principal", new TRequiredValidator()); 
        $processo_incidente_id->addValidation("Processo incidente", new TRequiredValidator()); 

        $processo_principal_id->setFilterColumns(["numero_cnj_numero"]);
        $processo_principal_id->setMinLength(2);
        $processo_incidente_id->setMinLength(2);

        $processo_principal_id->setMask('{numero_cnj_numero}');
        $processo_incidente_id->setMask('{numero_cnj_numero}');

        $tela->setValue($param['tela'] ?? null);
        $processo_principal_id->setValue($param["processo_principal_id"] ?? null);
        $processo_incidente_id->setValue($param["processo_incidente_id"] ?? null);

        $id->setSize(200);
        $tela->setSize(200);
        $processo_principal_id->setSize('100%');
        $processo_incidente_id->setSize('100%');

        $row1 = $this->form->addFields([$id,$tela]);
        $row1->layout = ['col-sm-12'];

        $row2 = $this->form->addFields([new TLabel("Processo principal:", null, '14px', null, '100%'),$processo_principal_id]);
        $row2->layout = ['col-sm-12'];

        $row3 = $this->form->addFields([new TLabel("Processo incidente:", null, '14px', null, '100%'),$processo_incidente_id]);
        $row3->layout = ['col-sm-12'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        parent::add($this->form);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new ProcessoVinculo(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if($object->processo_principal_id === $object->processo_incidente_id){
                throw new Exception("Não é possível vincular um processo a ele mesmo.");
            }

            if($data->tela == "PRINCIPAL"){

                $loadPageParam['key'] = (int) $object->processo_incidente_id;
                $loadPageParam['current_tab'] = 2;

                if((ProcessoVinculo::where('processo_incidente_id','=',$object->processo_incidente_id)->count()) != 0){
                    throw new Exception("Não é possível adicionar mais de um processo principal.");
                }
            }else if($data->tela == "INCIDENTE"){

                $loadPageParam['key'] = (int) $object->processo_principal_id;
                $loadPageParam['current_tab'] = 2;

                if((ProcessoVinculo::where('processo_incidente_id','=',$object->processo_incidente_id)->where('processo_principal_id','=',$object->processo_principal_id)->count()) != 0){
                    throw new Exception("O processo informado já é um incidente deste processo.");
                }
            }

            $object->store(); // save the object 

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
            TWindow::closeWindow(parent::getId());
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

                $object = new ProcessoVinculo($key); // instantiates the Active Record 

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

        if(isset($param['processo_principal_id']) && $param['processo_principal_id'] != null){
            BootstrapFormBuilder::hideField(self::$formName, 'processo_principal_id');
        }else if(isset($param['processo_incidente_id']) && $param['processo_incidente_id'] != null){
            BootstrapFormBuilder::hideField(self::$formName, 'processo_incidente_id');
        }
    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

