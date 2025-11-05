<?php

class EstadoAgendaForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'EstadoAgenda';
    private static $primaryKey = 'id';
    private static $formName = 'form_EstadoAgenda';

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
        $this->form->setFormTitle("Cadastro de estado de agendamento");


        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $cor = new TColor('cor');
        $estado_inicial = new TRadioGroup('estado_inicial');
        $estado_final = new TRadioGroup('estado_final');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $cor->addValidation("Cor", new TRequiredValidator()); 
        $estado_inicial->addValidation("Estado inicial", new TRequiredValidator()); 
        $estado_final->addValidation("Estado final", new TRequiredValidator()); 

        $nome->forceUpperCase();
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $estado_final->addItems(["S"=>"Sim","N"=>"Não"]);
        $estado_inicial->addItems(["S"=>"Sim","N"=>"Não"]);

        $estado_final->setLayout('horizontal');
        $estado_inicial->setLayout('horizontal');

        $estado_final->setUseButton();
        $estado_inicial->setUseButton();

        $estado_final->setValue('N');
        $estado_inicial->setValue('N');
        $modificacao_user_name->setValue(' ');

        $id->setEditable(false);
        $nome->setEditable(false);
        $estado_final->setEditable(false);
        $estado_inicial->setEditable(false);
        $data_modificacao->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $cor->setSize('100%');
        $nome->setSize('100%');
        $estado_final->setSize(125);
        $estado_inicial->setSize(125);
        $data_modificacao->setSize('100%');
        $modificacao_user_name->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#FF0000', '14px', null, '100%'),$nome],[new TLabel("Cor:", '#ff0000', '14px', null, '100%'),$cor]);
        $row2->layout = [' col-sm-8',' col-sm-3'];

        $row3 = $this->form->addFields([new TLabel("Estado inicial:", '#ff0000', '14px', null, '100%'),$estado_inicial],[new TLabel("Estado final:", '#ff0000', '14px', null, '100%'),$estado_final]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        $row4 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row5 = $this->form->addFields([new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row5->layout = ['col-sm-3','col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['EstadoAgendaHeaderList', 'onShow']), 'fas:arrow-left #000000');
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

            $object = new EstadoAgenda(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if(!$data->id){
                //$object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('EstadoAgendaHeaderList', 'onShow', $loadPageParam); 

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

                $object = new EstadoAgenda($key); // instantiates the Active Record 

                                $object->modificacao_user_name = $object->modificacao_user->name;

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

