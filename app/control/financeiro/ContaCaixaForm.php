<?php

class ContaCaixaForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'ContaCaixa';
    private static $primaryKey = 'id';
    private static $formName = 'form_ContaCaixaForm';

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
        $this->form->setFormTitle("Cadastro de conta caixa");

        $criteria_tipo_conta_caixa_id = new TCriteria();
        $criteria_banco_id = new TCriteria();

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $tipo_conta_caixa_id = new TDBCombo('tipo_conta_caixa_id', 'escritorio', 'TipoContaCaixa', 'id', '{nome}','nome asc' , $criteria_tipo_conta_caixa_id );
        $saldo_inicial = new TNumeric('saldo_inicial', '2', ',', '.' );
        $dt_inicial = new TDate('dt_inicial');
        $ativo = new TCheckButton('ativo');
        $saldo_instantaneo = new TNumeric('saldo_instantaneo', '2', ',', '.' );
        $cor_compensado = new TColor('cor_compensado');
        $saldo_nao_compensado = new TNumeric('saldo_nao_compensado', '2', ',', '.' );
        $cor_nao_compensado = new TColor('cor_nao_compensado');
        $banco_id = new TDBCombo('banco_id', 'escritorio', 'Banco', 'id', '{nome}','nome asc' , $criteria_banco_id );
        $descricao_agencia = new TEntry('descricao_agencia');
        $codigo_agencia = new TEntry('codigo_agencia');
        $codigo_conta = new TEntry('codigo_conta');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $tipo_conta_caixa_id->setChangeAction(new TAction([$this,'onSelectTipo']));

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $tipo_conta_caixa_id->addValidation("Tipo", new TRequiredValidator()); 
        $saldo_inicial->addValidation("Saldo inicial", new TRequiredValidator()); 
        $dt_inicial->addValidation("Data inicial", new TRequiredValidator()); 

        $ativo->setUseSwitch(true, 'blue');
        $ativo->setIndexValue("S");
        $nome->forceUpperCase();
        $descricao_agencia->forceUpperCase();

        $banco_id->enableSearch();
        $tipo_conta_caixa_id->enableSearch();

        $cor_compensado->setValue('#4CAF50');
        $cor_nao_compensado->setValue('#F44336');

        $dt_inicial->setMask('dd/mm/yyyy');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $dt_inicial->setDatabaseMask('yyyy-mm-dd');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $nome->setMaxLength(255);
        $codigo_conta->setMaxLength(30);
        $codigo_agencia->setMaxLength(10);
        $descricao_agencia->setMaxLength(255);

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $saldo_instantaneo->setEditable(false);
        $criacao_user_name->setEditable(false);
        $saldo_nao_compensado->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize('100%');
        $nome->setSize('100%');
        $banco_id->setSize('100%');
        $dt_inicial->setSize('100%');
        $codigo_conta->setSize('100%');
        $data_criacao->setSize('100%');
        $saldo_inicial->setSize('100%');
        $cor_compensado->setSize('100%');
        $codigo_agencia->setSize('100%');
        $data_modificacao->setSize('100%');
        $saldo_instantaneo->setSize('100%');
        $descricao_agencia->setSize('100%');
        $criacao_user_name->setSize('100%');
        $cor_nao_compensado->setSize('100%');
        $tipo_conta_caixa_id->setSize('100%');
        $saldo_nao_compensado->setSize('100%');
        $modificacao_user_name->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id]);
        $row1->layout = [' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome],[new TLabel("Tipo:", '#ff0000', '14px', null, '100%'),$tipo_conta_caixa_id]);
        $row2->layout = ['col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Saldo inicial:", '#ff0000', '14px', null, '100%'),$saldo_inicial],[new TLabel("Data inicial:", '#ff0000', '14px', null, '100%'),$dt_inicial],[new TLabel("Ativo:", '#FF0000', '14px', null, '100%'),$ativo]);
        $row3->layout = ['col-sm-6','col-sm-3',' col-sm-3'];

        $row4 = $this->form->addFields([new TLabel("Saldo instantâneo:", null, '14px', null, '100%'),$saldo_instantaneo],[new TLabel("Cor instantâneo:", '#FF0000', '14px', null, '100%'),$cor_compensado],[new TLabel("Saldo não compensado:", null, '14px', null, '100%'),$saldo_nao_compensado],[new TLabel("Cor  não compensado:", '#FF0000', '14px', null, '100%'),$cor_nao_compensado]);
        $row4->layout = ['col-sm-4','col-sm-2','col-sm-4','col-sm-2'];

        $row5 = $this->form->addFields([new TLabel("Banco:", '#FF0000', '14px', null, '100%'),$banco_id],[new TLabel("Descrição da agencia:", '#000000', '14px', null, '100%'),$descricao_agencia]);
        $row5->layout = [' col-sm-3',' col-sm-9'];

        $row6 = $this->form->addFields([new TLabel("Agencia:", '#FF0000', '14px', null, '100%'),$codigo_agencia],[new TLabel("Conta:", '#FF0000', '14px', null, '100%'),$codigo_conta]);
        $row6->layout = ['col-sm-6','col-sm-6'];

        $row7 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row8 = $this->form->addFields([new TLabel("Data criacao:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criacao user id:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Data modificacao:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Modificacao user id:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row8->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['ContaCaixaList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        BootstrapFormBuilder::hideField(self::$formName,'banco_id');
        BootstrapFormBuilder::hideField(self::$formName,'codigo_agencia');

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ContaCaixaForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onSelectTipo($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            if(!$param['id'] && isset($param['tipo_conta_caixa_id'])){
                if($param['tipo_conta_caixa_id']==TipoContaCaixa::BANCO){
                    BootstrapFormBuilder::showField(self::$formName,'banco_id');
                    BootstrapFormBuilder::showField(self::$formName,'codigo_agencia');

                }else{
                    BootstrapFormBuilder::hideField(self::$formName,'banco_id');
                    BootstrapFormBuilder::hideField(self::$formName,'codigo_agencia');
                }
            }
            TTransaction::close();

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new ContaCaixa(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if(!$object->ativo){
                $object->ativo = 'N';
            }

            if($object->tipo_conta_caixa_id == TipoContaCaixa::BANCO){
                if(!$object->banco_id){
                    throw new Exception('O campo Banco é obrigatório');
                }
                if(!$object->codigo_agencia){
                    throw new Exception('O campo Agencia é obrigatório');
                }
                if(!$object->codigo_conta){
                    throw new Exception('O campo Conta é obrigatório');
                }
            }else{
                if($object->banco_id) $object->banco_id = '';
                if($object->codigo_agencia) $object->codigo_agencia = '';
                if($object->codigo_conta) $object->codigo_conta = '';
                if($object->descricao_agencia) $object->descricao_agencia = '';
            }

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
                $object->saldo_instantaneo = $object->saldo_inicial;
                $object->saldo_nao_compensado = 0;
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
            TApplication::loadPage('ContaCaixaList', 'onShow', $loadPageParam); 

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

                $object = new ContaCaixa($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                if(!empty($object->tipo_conta_caixa_id))
                {

                    if($object->tipo_conta_caixa_id==TipoContaCaixa::DINHEIRO){
                        BootstrapFormBuilder::hideField(self::$formName,'banco_id');
                        BootstrapFormBuilder::hideField(self::$formName,'codigo_agencia');
                    }else{
                        BootstrapFormBuilder::showField(self::$formName,'banco_id');
                        BootstrapFormBuilder::showField(self::$formName,'codigo_agencia');
                    }

                    TTransaction::open(self::$database);
                    $criteria = new TCriteria();
                    $criteria->add(new TFilter('id', '=', $object->tipo_conta_caixa_id));

                    TCombo::reload(self::$formName, 'tipo_conta_caixa_id', TipoContaCaixa::getIndexedArray('id', 'nome', $criteria));
                    TTransaction::close();        
                }

                TCombo::disableField(self::$formName, 'tipo_conta_caixa_id');
                TNumeric::disableField(self::$formName, 'saldo_inicial');
                TDate::disableField(self::$formName, 'dt_inicial');

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

        BootstrapFormBuilder::hideField(self::$formName,'banco_id');
        BootstrapFormBuilder::hideField(self::$formName,'codigo_agencia');

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

