<?php

class AndamentoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Andamento';
    private static $primaryKey = 'id';
    private static $formName = 'form_AndamentoForm';

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
        $this->form->setFormTitle("Cadastro de andamento");

        $criteria_processo_id = new TCriteria();
        $criteria_tipo_andamento_id = new TCriteria();
        $criteria_criacao_user_id = new TCriteria();
        $criteria_modificacao_user_id = new TCriteria();

        $id = new TEntry('id');
        $tela = new THidden('tela');
        $processo_id = new TDBCombo('processo_id', 'escritorio', 'Processo', 'id', '{numero_cnj_numero}','numero_cnj_numero asc' , $criteria_processo_id );
        $tipo_andamento_id = new TDBCombo('tipo_andamento_id', 'escritorio', 'TipoAndamento', 'id', '{nome}','nome asc' , $criteria_tipo_andamento_id );
        $data_andamento = new TDateTime('data_andamento');
        $titulo = new TEntry('titulo');
        $texto = new TText('texto');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_id = new TDBCombo('criacao_user_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_criacao_user_id );
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_id = new TDBCombo('modificacao_user_id', 'escritorio', 'SystemUsers', 'id', '{name}','name asc' , $criteria_modificacao_user_id );

        $processo_id->addValidation("Processo id", new TRequiredValidator()); 
        $tipo_andamento_id->addValidation("Tipo andamento id", new TRequiredValidator()); 
        $data_andamento->addValidation("Data do andamento", new TRequiredValidator()); 
        $titulo->addValidation("Titulo", new TRequiredValidator()); 

        $titulo->setMaxLength(255);
        $tela->setValue($param['tela'] ?? "");
        $processo_id->setValue($param['processo_id'] ?? null);

        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_andamento->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_andamento->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $processo_id->enableSearch();
        $criacao_user_id->enableSearch();
        $tipo_andamento_id->enableSearch();
        $modificacao_user_id->enableSearch();

        $id->setEditable(false);
        $processo_id->setEditable(false);
        $data_criacao->setEditable(false);
        $criacao_user_id->setEditable(false);
        $data_modificacao->setEditable(false);
        $modificacao_user_id->setEditable(false);

        $id->setSize(100);
        $tela->setSize(200);
        $titulo->setSize('100%');
        $texto->setSize('100%', 70);
        $processo_id->setSize('100%');
        $data_criacao->setSize('100%');
        $data_andamento->setSize('100%');
        $criacao_user_id->setSize('100%');
        $data_modificacao->setSize('100%');
        $tipo_andamento_id->setSize('100%');
        $modificacao_user_id->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id,$tela],[new TLabel("Processo:", '#ff0000', '14px', null, '100%'),$processo_id]);
        $row1->layout = [' col-sm-6',' col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Tipo de andamento:", '#ff0000', '14px', null, '100%'),$tipo_andamento_id],[new TLabel("Data do andamento:", '#FF0000', '14px', null, '100%'),$data_andamento]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Titulo:", '#ff0000', '14px', null, '100%'),$titulo]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addFields([new TLabel("Texto:", null, '14px', null, '100%'),$texto]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row6 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_id],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_id]);
        $row6->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AndamentoForm]');
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

            $object = new Andamento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

            if($param['tela'] == "Aba de andamentos do processo"){

                $pageParam = []; 

                TApplication::loadPage('ProcessoFormView', 'onShow', ['key'=>$object->processo_id, 'current_tab_abas' => 3]);
            }

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

                $object = new Andamento($key); // instantiates the Active Record 

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

