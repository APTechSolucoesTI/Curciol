<?php

class AtendimentoHistoricoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'AtendimentoHistorico';
    private static $primaryKey = 'id';
    private static $formName = 'form_AtendimentoHistoricoForm';

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
        $this->form->setFormTitle("Histórico do atendimento");

        TTransaction::open(self::$database);
        if(!isset($param['atendimento_id'])){
            $param['atendimento_id'] = (AtendimentoHistorico::find($param['key']))->atendimento_id;
        }
        $this->form->setFormTitle("Histórico do atendimento {$param['atendimento_id']}");
        TTransaction::close();

        $id = new TEntry('id');
        $atendimento_id = new TEntry('atendimento_id');
        $historico = new THtmlEditor('historico');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $atendimento_id->addValidation("Atendimento Id", new TRequiredValidator()); 

        $atendimento_id->setValue($param['atendimento_id'] ?? null);
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $atendimento_id->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize('100%');
        $data_criacao->setSize('100%');
        $atendimento_id->setSize('100%');
        $historico->setSize('100%', 400);
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');

        $row1 = $this->form->addFields([$id],[$atendimento_id]);
        $row1->layout = [' col-sm-3',' col-sm-3'];

        $row2 = $this->form->addFields([$historico]);
        $row2->layout = ['col-sm-12'];

        $row3 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row4 = $this->form->addFields([new TLabel("Criado em:", null, '11px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '12px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '12px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '12px', null, '100%'),$modificacao_user_name]);
        $row4->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        BootstrapFormBuilder::hideField(self::$formName, 'atendimento_id');

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AtendimentoHistoricoForm]');
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

            $object = new AtendimentoHistorico(); // create an empty object 

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

            $loadPageParam["current_tab_abas"] = "0";
            if(!empty($object->atendimento_id))
            {
                $loadPageParam["key"] = $object->atendimento_id;
            }

            if(!empty($object->atendimento_id))
            {
                $loadPageParam["id"] = $object->atendimento_id;
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data

            $objetoAtendimento = Atendimento::find($data->atendimento_id);
            if($objetoAtendimento)
            {
                $objetoAtendimento->modificacao_user_id = TSession::getValue('userid');
                $objetoAtendimento->data_modificacao = date('Y-m-d H:i:s');
                $objetoAtendimento->store(); 
            }

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
            TTransaction::open(self::$database);
                $key = $param['key'];
                $object = new AtendimentoHistorico($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->form->setData($object); // fill the form 

              TTransaction::close();
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

        TTransaction::open(self::$database);
        if(!isset($param['atendimento_id'])){
            $param['atendimento_id'] = (AtendimentoHistorico::find($param['key']))->atendimento_id;
        }
        $object = new stdClass();
        $object->atendimento_id = $param['atendimento_id'];
        TForm::sendData(self::$formName, $object);
        BootstrapFormBuilder::hideField(self::$formName, 'atendimento_id');
        TTransaction::close();

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

