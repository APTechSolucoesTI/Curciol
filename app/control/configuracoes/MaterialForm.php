<?php

class MaterialForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Material';
    private static $primaryKey = 'id';
    private static $formName = 'form_Material';

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
        $this->form->setFormTitle("Cadastro de material");

        $criteria_unidade_medida_id = new TCriteria();

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $lote = new TEntry('lote');
        $estoque_minimo = new TNumeric('estoque_minimo', '2', ',', '.' );
        $dt_vencimento = new TDate('dt_vencimento');
        $ativo = new TRadioGroup('ativo');
        $estoque_atualizado = new TNumeric('estoque_atualizado', '2', ',', '.' );
        $unidade_medida_id = new TDBCombo('unidade_medida_id', 'escritorio', 'UnidadeMedida', 'id', '{nome}','nome asc' , $criteria_unidade_medida_id );

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $ativo->addValidation("Ativo", new TRequiredValidator()); 

        $id->setEditable(false);
        $nome->forceUpperCase();
        $dt_vencimento->setMask('dd/mm/yyyy');
        $dt_vencimento->setDatabaseMask('yyyy-mm-dd');
        $ativo->addItems(["S"=>"Sim","N"=>"Não"]);
        $ativo->setLayout('horizontal');
        $ativo->setValue('S');
        $ativo->setUseButton();
        $id->setSize(100);
        $ativo->setSize(80);
        $nome->setSize('100%');
        $lote->setSize('100%');
        $dt_vencimento->setSize(110);
        $estoque_minimo->setSize('100%');
        $unidade_medida_id->setSize('100%');
        $estoque_atualizado->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id]);
        $row1->layout = ['col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome],[new TLabel("Lote:", null, '14px', null, '100%'),$lote]);
        $row2->layout = [' col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Estoque mínimo:", null, '14px', null, '100%'),$estoque_minimo],[new TLabel("Vencimento:", null, '14px', null, '100%'),$dt_vencimento],[new TLabel("Ativo:", '#FF0000', '14px', null, '100%'),$ativo]);
        $row3->layout = ['col-sm-3','col-sm-3','col-sm-2'];

        $row4 = $this->form->addFields([new TLabel("Estoque inicial:", null, '14px', null),$estoque_atualizado],[new TLabel("Unidade de medida:", null, '14px', null),$unidade_medida_id]);
        $row4->layout = ['col-sm-3','col-sm-2'];

        if (! empty($param['key']))
        {
            $row4->style =  'display: none';
        }

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['MaterialList', 'onShow']), 'fas:arrow-left #000000');
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

        $style = new TStyle('right-panel > .container-part[page-name=MaterialForm]');
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

            $object = new Material(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            if ($data->id)
            {
                unset($object->unidade_medida_id);
                unset($object->estoque_minimo);
            }

            $object->store(); // save the object 

            if ($object->estoque_minimo)
            {
                $movimentacao = new Movimentacao();
                $movimentacao->material_id = $object->id;
                $movimentacao->system_user_id = TSession::getValue('userid');
                $movimentacao->quantidade = $object->estoque_minimo;
                $movimentacao->dt_movimentacao = date('Y-m-d');
                $movimentacao->store();
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle'); 

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

                $object = new Material($key); // instantiates the Active Record 

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

