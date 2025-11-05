<?php

class AtendimentoContratoForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'AtendimentoContrato';
    private static $primaryKey = 'id';
    private static $formName = 'form_AtendimentoContratoForm';

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
        $this->form->setFormTitle("Contrato");

        $criteria_contrato_pessoa_id = new TCriteria();
        $criteria_contrato_profissional_id = new TCriteria();
        $criteria_contrato_id = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_contrato_pessoa_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = Grupo::PROFISSIONAL;
        $criteria_contrato_profissional_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $id = new TEntry('id');
        $atendimento_id = new THidden('atendimento_id');
        $contrato_pessoa_id = new TDBUniqueSearch('contrato_pessoa_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_contrato_pessoa_id );
        $contrato_profissional_id = new TDBCombo('contrato_profissional_id', 'escritorio', 'Pessoa', 'id', '{nome}','nome asc' , $criteria_contrato_profissional_id );
        $contrato_id = new TDBCombo('contrato_id', 'escritorio', 'Contrato', 'id', '{numero}','numero asc' , $criteria_contrato_id );
        $contrato_objeto = new THtmlEditor('contrato_objeto');

        $contrato_pessoa_id->setChangeAction(new TAction([$this,'onchangeCliente']));
        $contrato_profissional_id->setChangeAction(new TAction([$this,'onchangeProfissional']));
        $contrato_id->setChangeAction(new TAction([$this,'onchangeNumero']));

        $contrato_id->addValidation("Número", new TRequiredValidator()); 

        $atendimento_id->setValue($param['atendimento_id']);
        $contrato_pessoa_id->setMinLength(3);
        $contrato_pessoa_id->setMask('{nome}');
        $contrato_id->setTip("Selecionar");
        $id->setEditable(false);
        $contrato_objeto->setEditable(false);

        $contrato_id->enableSearch();
        $contrato_profissional_id->enableSearch();

        $id->setSize(100);
        $atendimento_id->setSize(200);
        $contrato_id->setSize('100%');
        $contrato_pessoa_id->setSize('100%');
        $contrato_objeto->setSize('100%', 300);
        $contrato_profissional_id->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id],[$atendimento_id]);
        $row1->layout = [' col-sm-3','col-sm-2'];

        $row2 = $this->form->addFields([new TLabel("Cliente:", null, '14px', null, '100%'),$contrato_pessoa_id],[new TLabel("Profissional", null, '14px', null, '100%'),$contrato_profissional_id]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Número:", '#FF0000', '14px', null, '100%'),$contrato_id]);
        $row3->layout = [' col-sm-12'];

        $row4 = $this->form->addFields([new TLabel("Objeto:", null, '14px', null, '100%'),$contrato_objeto]);
        $row4->layout = [' col-sm-12'];

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

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AtendimentoContratoForm]');
        $style->width = '50% !important';   
        $style->show(true);

    }

    public static function onchangeCliente($param = null) 
    {
        try 
        {
            if(!empty($param['contrato_pessoa_id']))
            {
                TTransaction::open(self::$database);
                $criteria = new TCriteria();
                $criteria->add(new TFilter('id', 'in', "(SELECT contrato_id FROM contrato_pessoa WHERE cliente_id = '{$param['contrato_pessoa_id']}')"));

                TCombo::reload(self::$formName, 'contrato_id', Contrato::getIndexedArray('id', 'numero', $criteria));
                TTransaction::close();

                $object = new stdClass();
                $object->contrato_id = null;
                TForm::sendData(self::$formName, $object);

            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onchangeProfissional($param = null) 
    {
        try 
        {
            if(!empty($param['contrato_profissional_id']))
            {
                TTransaction::open(self::$database);
                $criteria = new TCriteria();
                $criteria->add(new TFilter('id', 'in', "(SELECT contrato_id FROM contrato_profissional WHERE profissional_id = '{$param['contrato_profissional_id']}')"));

                TCombo::reload(self::$formName, 'contrato_id', Contrato::getIndexedArray('id', 'numero', $criteria));
                TTransaction::close();

                $object = new stdClass();
                $object->contrato_id = null;
                TForm::sendData(self::$formName, $object);

            }

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onchangeNumero($param = null) 
    {
        try 
        {
            if(!empty($param['contrato_id'])){
                TTransaction::open(self::$database);

                $objeto = Contrato::find($param['contrato_id']); 

                $object = new stdClass();
                $object->contrato_objeto = $objeto->objeto;

                TForm::sendData(self::$formName, $object);

                TTransaction::close();
            }

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

            $object = new AtendimentoContrato(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

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

            if(!empty($object->atendimento_id))
            {
                $loadPageParam["key"] = $object->atendimento_id;
            }

            if(!empty($object->atendimento_id))
            {
                $loadPageParam["id"] = $object->atendimento_id;
            }

            $loadPageParam["current_tab_abas"] = "4"; 

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

                $object = new AtendimentoContrato($key); // instantiates the Active Record 

                                $object->contrato_id = $object->contrato->id;
                $object->contrato_objeto = $object->contrato->objeto;

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

