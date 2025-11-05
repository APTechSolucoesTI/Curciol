<?php

class ExtratoTransferenciaForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Extrato';
    private static $primaryKey = 'id';
    private static $formName = 'form_ExtratoTransferenciaForm';

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
        $this->form->setFormTitle("Cadastro de transferência");

        $criteria_conta_caixa_id = new TCriteria();
        $criteria_escritorio_id = new TCriteria();
        $criteria_transferencia_conta_caixa_id = new TCriteria();
        $criteria_categoria_conta_id = new TCriteria();

        $id = new TEntry('id');
        $extrato_vinculado = new THidden('extrato_vinculado');
        $conta_caixa_id = new TDBCombo('conta_caixa_id', 'escritorio', 'ContaCaixa', 'id', '{nome}','nome asc' , $criteria_conta_caixa_id );
        $escritorio_id = new TDBCombo('escritorio_id', 'escritorio', 'Escritorio', 'id', '{nome}','nome asc' , $criteria_escritorio_id );
        $transferencia_conta_caixa_id = new TDBCombo('transferencia_conta_caixa_id', 'escritorio', 'ContaCaixa', 'id', '{nome}','nome asc' , $criteria_transferencia_conta_caixa_id );
        $saida_valor = new TNumeric('saida_valor', '2', ',', '.' );
        $entrada_valor = new TNumeric('entrada_valor', '2', ',', '.' );
        $categoria_conta_id = new TDBCombo('categoria_conta_id', 'escritorio', 'CategoriaConta', 'id', '{nome}','nome asc' , $criteria_categoria_conta_id );
        $historico = new TEntry('historico');
        $compensado = new TCheckButton('compensado');
        $data_compensacao = new TDate('data_compensacao');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');


        $historico->setMaxLength(3000);
        $historico->forceUpperCase();
        $compensado->setUseSwitch(true, 'blue');
        $compensado->setIndexValue("S");
        $data_compensacao->setMask('dd/mm/yyyy');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_compensacao->setDatabaseMask('yyyy-mm-dd');
        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $escritorio_id->enableSearch();
        $conta_caixa_id->enableSearch();
        $categoria_conta_id->enableSearch();
        $transferencia_conta_caixa_id->enableSearch();

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $historico->setSize('100%');
        $saida_valor->setSize('100%');
        $data_criacao->setSize('100%');
        $escritorio_id->setSize('100%');
        $entrada_valor->setSize('100%');
        $extrato_vinculado->setSize(200);
        $conta_caixa_id->setSize('100%');
        $data_compensacao->setSize('100%');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $categoria_conta_id->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $transferencia_conta_caixa_id->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id,$extrato_vinculado],[new TLabel("Conta caixa:", '#ff0000', '14px', null, '100%'),$conta_caixa_id],[new TLabel("Escritório:", '#ff0000', '14px', null, '100%'),$escritorio_id]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Conta caixa de transferência: ", '#FF0000', '14px', null, '100%'),$transferencia_conta_caixa_id]);
        $row2->layout = ['col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Valor:", '#FF0000', '14px', null, '100%'),$saida_valor,$entrada_valor],[new TLabel("Categoria de conta:", null, '14px', null, '100%'),$categoria_conta_id]);
        $row3->layout = ['col-sm-6','col-sm-6'];

        $row4 = $this->form->addFields([new TLabel("Histórico:", null, '14px', null, '100%'),$historico],[new TLabel("Compensado:", null, '14px', null, '100%'),$compensado],[new TLabel("Data da compensação:", null, '14px', null, '100%'),$data_compensacao]);
        $row4->layout = ['col-sm-6',' col-sm-3',' col-sm-3'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row6 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['ExtratoList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TScript::create("$(\"[name='entrada_valor']\").closest('.fb-inline-field-container').hide()");

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ExtratoTransferenciaForm]');
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

            $object = new Extrato(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if(!$object->compensado){

                $object->compensado = 'N';
                $object->data_compensacao = '';

                //saldo_nao_compensado
                $contaCaixa = ContaCaixa::find($object->conta_caixa_id);

                $contaCaixa->saldo_nao_compensado = (float) $contaCaixa->saldo_nao_compensado + (float) $object->entrada_valor - (float) $object->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();

            }else if($object->compensado=='S'){

                $dt_inicio = substr($object->conta_caixa->dt_inicial, 0, 10);

                if($object->data_compensacao < $dt_inicio){
                    throw new Exception("Informe uma data de compensação superior a data inicial da conta caixa.");
                }

                //saldo_instantaneo
                $contaCaixa = ContaCaixa::find($object->conta_caixa_id);

                $contaCaixa->saldo_instantaneo = (float) $contaCaixa->saldo_instantaneo + (float) $object->entrada_valor - (float) $object->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();
            }

            if(!$object->id){
                $object->data_lancamento = date('Y-m-d');
                $object->tipo_extrato_id = TipoExtrato::TRANSFERENCIA;
            }else{
                $objeto = Extrato::find($object->id);
                $object->conta_caixa_id = $objeto->conta_caixa_id;
                $object->transferencia_conta_caixa_id = $objeto->transferencia_conta_caixa_id;
                $object->escritorio_id = $objeto->escritorio_id;
                $object->tipo_extrato_id = TipoExtrato::TRANSFERENCIA;
            }

            if(!$object->conta_caixa_id){
                throw new Exception("O campo Conta caixa é obrigatório.");
            }
            if(!$object->transferencia_conta_caixa_id){
                throw new Exception("O campo Conta caixa de transferência é obrigatório.");
            }
            if(!$object->escritorio_id){
                throw new Exception("O campo Escritório é obrigatório.");
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

            if(!$data->id){
                $extratoClone = clone $object;
                unset($extratoClone->id);
                $extratoClone->entrada_valor = $extratoClone->saida_valor;
                $extratoClone->transferencia_conta_caixa_id = $object->conta_caixa_id;
                $extratoClone->extrato_vinculado = $object->id;
                $extratoClone->conta_caixa_id = $object->transferencia_conta_caixa_id; 
                $extratoClone->criacao_user_id = TSession::getValue('userid');

                unset($extratoClone->saida_valor);
                $extratoClone->store();

                $object->extrato_vinculado = $extratoClone->id;
                $object->store();

                if($extratoClone->compensado=='S'){

                    //saldo_instantaneo
                    $contaCaixa = ContaCaixa::find($extratoClone->conta_caixa_id);

                    $contaCaixa->saldo_instantaneo = (float) $contaCaixa->saldo_instantaneo + (float) $extratoClone->entrada_valor - (float) $extratoClone->saida_valor;
                    $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                    $contaCaixa->store();
                }else{

                    //saldo_nao_compensado
                    $contaCaixa = ContaCaixa::find($extratoClone->conta_caixa_id);

                    $contaCaixa->saldo_nao_compensado = (float) $contaCaixa->saldo_nao_compensado + (float) $extratoClone->entrada_valor - (float) $extratoClone->saida_valor;
                    $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                    $contaCaixa->store();
                }
            }else{
                $extratoExistente = Extrato::find($object->extrato_vinculado);
                $extratoExistente->entrada_valor = $object->saida_valor ?? null;
                $extratoExistente->saida_valor = $object->entrada_valor ?? null;
                $extratoExistente->historico = $object->historico;
                $extratoExistente->categoria_conta_id = $object->categoria_conta_id;
                $extratoExistente->modificacao_user_id = TSession::getValue('userid');

                $extratoExistente->store();

                if($extratoExistente->compensado=='S'){

                    //saldo_instantaneo
                    $contaCaixa = ContaCaixa::find($extratoExistente->conta_caixa_id);

                    $contaCaixa->saldo_instantaneo = (float) $contaCaixa->saldo_instantaneo + (float) $extratoExistente->entrada_valor - (float) $extratoExistente->saida_valor;
                    $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                    $contaCaixa->store();
                }else{
                    //saldo_nao_compensado
                    $contaCaixa = ContaCaixa::find($extratoExistente->conta_caixa_id);

                    $contaCaixa->saldo_nao_compensado = (float) $contaCaixa->saldo_nao_compensado + (float) $extratoExistente->entrada_valor - (float) $extratoExistente->saida_valor;
                    $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                    $contaCaixa->store();
                }
            }
            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('ExtratoList', 'onShow', $loadPageParam); 

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

                $object = new Extrato($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                TScript::create(' $("select[name=\'conta_caixa_id\'").prop("disabled", true); ');
                TScript::create(' $("select[name=\'escritorio_id\'").prop("disabled", true); ');
                TScript::create(' $("select[name=\'transferencia_conta_caixa_id\'").prop("disabled", true); ');
                TScript::create(' $("input[name=\'compensado\'").prop("disabled", true); ');
                TDate::disableField(self::$formName, 'data_compensacao');

                if($object->entrada_valor){
                    TScript::create("$(\"[name='saida_valor']\").closest('.fb-inline-field-container').hide()");
                    TScript::create("$(\"[name='entrada_valor']\").closest('.fb-inline-field-container').show()");
                }else{
                    TScript::create("$(\"[name='entrada_valor']\").closest('.fb-inline-field-container').hide()");
                    TScript::create("$(\"[name='saida_valor']\").closest('.fb-inline-field-container').show()");
                }

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

