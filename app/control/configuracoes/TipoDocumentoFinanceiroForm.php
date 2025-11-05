<?php

class TipoDocumentoFinanceiroForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'TipoDocumentoFinanceiro';
    private static $primaryKey = 'id';
    private static $formName = 'form_TipoDocumentoFinanceiroForm';

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
        $this->form->setFormTitle("Cadastro de documento financeiro");

        $criteria_tipo_conta_id = new TCriteria();
        $criteria_padrao_id = new TCriteria();

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $codigo = new TEntry('codigo');
        $tipo_conta_id = new TDBCombo('tipo_conta_id', 'escritorio', 'TipoConta', 'id', '{nome}','nome asc' , $criteria_tipo_conta_id );
        $padrao_id = new TDBCombo('padrao_id', 'escritorio', 'TipoDocFinanceiroPadrao', 'id', '{nome}','nome asc' , $criteria_padrao_id );
        $gera_codigo = new TRadioGroup('gera_codigo');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $padrao_id->setChangeAction(new TAction([$this,'onSelectPadrao']));

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $codigo->addValidation("Sigla", new TRequiredValidator()); 
        $tipo_conta_id->addValidation("Tipo conta id", new TRequiredValidator()); 
        $padrao_id->addValidation("Padrao id", new TRequiredValidator()); 
        $gera_codigo->addValidation("Número automático", new TRequiredValidator()); 

        $codigo->setValue('Man');
        $gera_codigo->addItems(["S"=>"Sim","N"=>"Não"]);
        $gera_codigo->setLayout('horizontal');
        $nome->setMaxLength(255);
        $codigo->setMaxLength(4);

        $padrao_id->enableSearch();
        $tipo_conta_id->enableSearch();

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
        $codigo->setSize('100%');
        $padrao_id->setSize('100%');
        $gera_codigo->setSize('100%');
        $data_criacao->setSize('100%');
        $tipo_conta_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id]);
        $row1->layout = [' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome],[new TLabel("Sigla:", '#ff0000', '14px', null, '100%'),$codigo]);
        $row2->layout = [' col-sm-8',' col-sm-4'];

        $row3 = $this->form->addFields([new TLabel("Tipo de conta:", '#ff0000', '14px', null, '100%'),$tipo_conta_id],[new TLabel("Padrão:", '#ff0000', '14px', null, '100%'),$padrao_id],[new TLabel("Número automático:", '#ff0000', '14px', null, '100%'),$gera_codigo]);
        $row3->layout = ['col-sm-4','col-sm-4','col-sm-4'];

        $row4 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row4->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['TipoDocumentoFinanceiroList', 'onShow']), 'fas:arrow-left #000000');
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

    public static function onSelectPadrao($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            if($param['padrao_id']==TipoDocFinanceiroPadrao::ATENDIMENTO || $param['padrao_id']==TipoDocFinanceiroPadrao::CONTRATO || $param['padrao_id']==TipoDocFinanceiroPadrao::PROCESSO){

                $object = new stdClass();
                $object->gera_codigo = 'S';
                TForm::sendData(self::$formName, $object);

                TRadioGroup::disableField(self::$formName, 'gera_codigo');
            }else{
                $object = new stdClass();
                $object->gera_codigo = 'N';
                TForm::sendData(self::$formName, $object);

                TRadioGroup::enableField(self::$formName, 'gera_codigo');
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

            $object = new TipoDocumentoFinanceiro(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if($object->padrao_id!=TipoDocFinanceiroPadrao::NENHUM){
                if($data->id){
                    $search = TipoDocumentoFinanceiro::where('padrao_id','=',(int) $object->padrao_id)
                                                    ->where('id','!=',(int) $data->id)
                                                    ->load();

                   $searchPadAten = TipoDocumentoFinanceiro::where('padrao_id','=',TipoDocFinanceiroPadrao::ATENDIMENTO)->where('id','!=',(int) $data->id)->load();
                   $searchPadCont = TipoDocumentoFinanceiro::where('padrao_id','=',TipoDocFinanceiroPadrao::CONTRATO)->where('id','!=',(int) $data->id)->load();
                   $searchPadProc = TipoDocumentoFinanceiro::where('padrao_id','=',TipoDocFinanceiroPadrao::PROCESSO)->where('id','!=',(int) $data->id)->load();
                }else{
                    $search = TipoDocumentoFinanceiro::where('padrao_id','=',(int) $object->padrao_id)
                                                    ->load();
                    $searchPadAten = TipoDocumentoFinanceiro::where('padrao_id','=',TipoDocFinanceiroPadrao::ATENDIMENTO)->load();
                    $searchPadCont = TipoDocumentoFinanceiro::where('padrao_id','=',TipoDocFinanceiroPadrao::CONTRATO)->load();
                    $searchPadProc = TipoDocumentoFinanceiro::where('padrao_id','=',TipoDocFinanceiroPadrao::PROCESSO)->load();
                }

                if($object->padrao_id!=TipoDocFinanceiroPadrao::ATENDIMENTO && count($searchPadAten)<1)
                    throw new Exception("É necessario cadastrar pelo menos um Tipo de Documento como padrão para Atendimento.");
                if($object->padrao_id!=TipoDocFinanceiroPadrao::CONTRATO && count($searchPadCont)<1)
                    throw new Exception("É necessario cadastrar pelo menos um Tipo de Documento como padrão para Contrato.");
                if($object->padrao_id!=TipoDocFinanceiroPadrao::PROCESSO && count($searchPadProc)<1)
                    throw new Exception("É necessario cadastrar pelo menos um Tipo de Documento como padrão para Processo.");

                $nomePadrao = $object->padrao->nome;
                $nomeTipoConta = $object->tipo_conta->nome;
                if($object->padrao_id==TipoDocFinanceiroPadrao::ATENDIMENTO || $object->padrao_id==TipoDocFinanceiroPadrao::CONTRATO || $object->padrao_id==TipoDocFinanceiroPadrao::PROCESSO){
                    if(count($search)>0){
                        throw new Exception("Não é possivel utilizar $nomePadrao como automático em multiplos cadastros.");
                    }
                }else{
                    foreach($search as $result){
                        if($result->tipo_conta_id==TipoConta::AMBOS || $result->tipo_conta_id==$object->tipo_conta_id){
                            throw new Exception("Não é possivel adicionar tipo de conta '$nomeTipoConta' para o padrão '$nomePadrao'.");
                        }
                    }
                }

                if($object->tipo_conta_id==TipoConta::PAGAR){
                    if($object->padrao_id==TipoDocFinanceiroPadrao::ATENDIMENTO){
                        $recPadAtend = false;
                        foreach($searchPadAten as $resultPadAten){
                            if($resultPadAten->tipo_conta_id==TipoConta::RECEBER || $resultPadAten->tipo_conta_id==TipoConta::AMBOS)
                                $recPadAtend = true;
                        }
                        if(!$recPadAtend)
                            throw new Exception("É necessario cadastrar pelo menos um Tipo de Documento 'A receber' ou 'Ambos' para Atendimento.");
                    }

                    if($object->padrao_id==TipoDocFinanceiroPadrao::CONTRATO){
                        $recPadCont = false;
                        foreach($searchPadCont as $resultPadCont){
                            if($resultPadCont->tipo_conta_id==TipoConta::RECEBER || $resultPadCont->tipo_conta_id==TipoConta::AMBOS)
                                $recPadCont = true;
                        }
                        if(!$recPadCont)
                            throw new Exception("É necessario cadastrar pelo menos um Tipo de Documento 'A receber' ou 'Ambos' para Contrato.");
                    }

                    if($object->padrao_id==TipoDocFinanceiroPadrao::PROCESSO){
                        $recPadProc = false;
                        foreach($searchPadProc as $resultPadProc){
                            if($resultPadProc->tipo_conta_id==TipoConta::RECEBER || $resultPadProc->tipo_conta_id==TipoConta::AMBOS)
                                $recPadProc = true;
                        }
                        if(!$recPadProc)
                            throw new Exception("É necessario cadastrar pelo menos um Tipo de Documento 'A receber' ou 'Ambos' para Processo.");
                    }
                }
            }

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

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('TipoDocumentoFinanceiroList', 'onShow', $loadPageParam); 

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

                $object = new TipoDocumentoFinanceiro($key); // instantiates the Active Record 

                if($object->padrao_id==TipoDocFinanceiroPadrao::ATENDIMENTO || $object->padrao_id==TipoDocFinanceiroPadrao::CONTRATO || $object->padrao_id==TipoDocFinanceiroPadrao::PROCESSO){
                    TRadioGroup::disableField(self::$formName, 'gera_codigo');
                }

                                $object->criacao_user_name = $object->criacao_user->name;
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

