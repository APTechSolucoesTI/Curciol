<?php

class ContratoPagamentoOpcaoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'ContratoPagamentoOpcao';
    private static $primaryKey = 'id';
    private static $formName = 'form_ContratoPagamentoOpcaoForm';

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
        $this->form->setFormTitle("Cadastro de opção de pagamento de contrato");


        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $recebe_valor = new TCheckButton('recebe_valor');
        $recebe_data = new TCheckButton('recebe_data');
        $recebe_evento = new TCheckButton('recebe_evento');
        $recebe_indexador = new TCheckButton('recebe_indexador');
        $descricao1 = new TText('descricao1');
        $descricaon = new TText('descricaon');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $recebe_valor->addValidation("Recebe valor", new TRequiredValidator()); 
        $recebe_data->addValidation("Recebe data", new TRequiredValidator()); 
        $recebe_evento->addValidation("Recebe evento", new TRequiredValidator()); 
        $recebe_indexador->addValidation("Recebe indexador", new TRequiredValidator()); 
        $descricao1->addValidation("Descrição no documento", new TRequiredValidator()); 

        $nome->setMaxLength(255);
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $recebe_data->setValue('N');
        $recebe_valor->setValue('N');
        $recebe_evento->setValue('N');
        $recebe_indexador->setValue('N');

        $recebe_data->setUseSwitch(true, 'blue');
        $recebe_valor->setUseSwitch(true, 'blue');
        $recebe_evento->setUseSwitch(true, 'blue');
        $recebe_indexador->setUseSwitch(true, 'blue');

        $recebe_data->setIndexValue("S");
        $recebe_valor->setIndexValue("S");
        $recebe_evento->setIndexValue("S");
        $recebe_indexador->setIndexValue("S");

        $recebe_data->setInactiveIndexValue("N");
        $recebe_valor->setInactiveIndexValue("N");
        $recebe_evento->setInactiveIndexValue("N");
        $recebe_indexador->setInactiveIndexValue("N");

        $id->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $nome->setSize('100%');
        $data_criacao->setSize('100%');
        $descricao1->setSize('100%', 150);
        $descricaon->setSize('100%', 150);
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),new TLabel("[opcao_pagamento]", null, '14px', null),$nome]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([new TLabel("Recebe valor:", '#ff0000', '14px', null, '100%'),$recebe_valor,new TLabel("[valor] [valor_extenso] [numero_parcelas] [numero_parcelas_extenso]", null, '14px', null, '100%')],[new TLabel("Recebe data:", '#ff0000', '14px', null, '100%'),$recebe_data,new TLabel("[data] [data_extenso]", null, '14px', null, '100%')],[new TLabel("Recebe evento:", '#ff0000', '14px', null, '100%'),$recebe_evento,new TLabel("[evento]", null, '14px', null, '100%')],[new TLabel("Recebe indexador:", '#ff0000', '14px', null, '100%'),$recebe_indexador,new TLabel("[indexador] [numero_indexador] [numero_indexador_extenso] [unidade_indexador] [unidade_indexador_extenso]", null, '14px', null, '100%')]);
        $row3->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row4 = $this->form->addFields([new TLabel("Descrição no documento para uma parcela:", '#FF0000', '14px', null, '100%'),new TLabel("As tags descritas no campo acima podem ser utilizadas para preencher com o valor devido ao cadastrar o pagamento do contrato.", null, '10px', null, '100%'),$descricao1]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addFields([new TLabel("Descrição no documento para mais de uma parcela:", '#FF0000', '14px', null, '100%'),new TLabel("As tags descritas no campo acima podem ser utilizadas para preencher com o valor devido ao cadastrar o pagamento do contrato.", null, '10px', null, '100%'),$descricaon]);
        $row5->layout = [' col-sm-12'];

        $row6 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row7 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row7->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['ContratoPagamentoOpcaoList', 'onShow']), 'fas:arrow-left #000000');
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

        $style = new TStyle('right-panel > .container-part[page-name=ContratoPagamentoOpcaoForm]');
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

            $object = new ContratoPagamentoOpcao(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

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
            TApplication::loadPage('ContratoPagamentoOpcaoList', 'onShow', $loadPageParam); 

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

                $object = new ContratoPagamentoOpcao($key); // instantiates the Active Record 

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

