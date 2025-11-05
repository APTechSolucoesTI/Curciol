<?php

class AtendimentoProcedimentoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'AtendimentoProcedimento';
    private static $primaryKey = 'id';
    private static $formName = 'form_AtendimentoProcedimento';

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
        $this->form->setFormTitle("Procedimento");

        $criteria_procedimento_id = new TCriteria();
        $criteria_parceiro_id = new TCriteria();

        $id = new TEntry('id');
        $atendimento_id = new TEntry('atendimento_id');
        $procedimento_id = new TDBCombo('procedimento_id', 'escritorio', 'Procedimento', 'id', '{nome}','nome asc' , $criteria_procedimento_id );
        $parceiro_id = new TDBCombo('parceiro_id', 'escritorio', 'Parceiro', 'id', '{nome}','nome asc' , $criteria_parceiro_id );
        $quantidade = new TNumeric('quantidade', '2', ',', '.', true, true );
        $valor = new TNumeric('valor', '2', ',', '.', true, true );
        $valor_total = new TNumeric('valor_total', '2', ',', '.', true, true );

        $procedimento_id->setChangeAction(new TAction([$this,'onChangeProcedimento']));
        $parceiro_id->setChangeAction(new TAction([$this,'onChangeConvenio']));

        $quantidade->setExitAction(new TAction([$this,'onChangeQuantidade']));
        $valor->setExitAction(new TAction([$this,'onChangeValor']));

        $procedimento_id->addValidation("Procedimento", new TRequiredValidator()); 
        $parceiro_id->addValidation("Convênio", new TRequiredValidator()); 
        $quantidade->addValidation("Quantidade", new TRequiredValidator()); 
        $valor_total->addValidation("Valor total", new TRequiredValidator()); 

        $id->setEditable(false);
        $procedimento_id->enableSearch();
        $quantidade->setValue('1,00');
        $atendimento_id->setValue($param['atendimento_id']??'');

        $id->setSize(100);
        $valor->setSize('100%');
        $quantidade->setSize('100%');
        $parceiro_id->setSize('100%');
        $valor_total->setSize('100%');
        $atendimento_id->setSize('100%');
        $procedimento_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id],[new TLabel("Atendimento", null, '14px', null, '100%'),$atendimento_id]);
        $row1->layout = ['col-sm-6','col-sm-2'];

        $row2 = $this->form->addFields([new TLabel("Procedimento:", '#ff0000', '14px', null, '100%'),$procedimento_id],[new TLabel("Convênio:", '#FF0000', '14px', null),$parceiro_id]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Quantidade:", '#ff0000', '14px', null, '100%'),$quantidade],[new TLabel("Valor:", '#FF0000', '14px', null),$valor],[new TLabel("Valor total:", '#FF0000', '14px', null),$valor_total]);
        $row3->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AtendimentoProcedimentoForm]');
        $style->width = '50% !important';   
        $style->show(true);

    }

    public static function onChangeQuantidade($param = null) 
    {
        try 
        {
            $valor = (float) str_replace(',', '.', str_replace('.', '', $param['valor']??'1'));
            $quantidade = (float) str_replace(',', '.', str_replace('.', '', $param['quantidade']??'1'));

            $data = new stdClass;
            $data->valor_total = number_format($valor * $quantidade, 2, ',', '.');

            TForm::sendData(self::$formName, $data);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeValor($param = null) 
    {
        try 
        {
            self::onChangeQuantidade($param);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeProcedimento($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            if (! empty($param['procedimento_id']) && ! empty($param['convenio_id']))
            {
                $preco = ProcedimentoPreco::where('procedimento_id', '=', $param['procedimento_id'])->where('convenio_id', '=', $param['convenio_id'])->first();

                $data = new stdClass;
                $data->valor = number_format($preco->valor, 2, ',', '.');

                TForm::sendData(self::$formName, $data);
            }
            TTransaction::close();

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onChangeConvenio($param = null) 
    {
        try 
        {
            self::onChangeProcedimento($param);

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

            $object = new AtendimentoProcedimento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            if(!empty($object->atendimento_id))
            {
                $loadPageParam["key"] = $object->atendimento_id;
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
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
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new AtendimentoProcedimento($key); // instantiates the Active Record 

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

