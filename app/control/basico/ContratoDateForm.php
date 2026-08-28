<?php

class ContratoDateForm extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ContratoDateForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(0.30, null);
        parent::setTitle("Defina um ano de referência para o contrato");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Defina um ano de referência para o contrato");


        $ano = new TCombo('ano');

        $ano->addValidation("Ano de referência", new TRequiredValidator()); 

        $ano->setSize('100%');
        $ano->enableSearch();


        $row1 = $this->form->addFields([new TLabel("Ano de referência:", null, '14px', null, '100%'),$ano]);
        $row1->layout = [' col-sm-12'];

        $date = (int) date('Y');

        $anos = [];
        for ($i = 0; $i <= 10; $i++) {
            $anoRef = $date - $i;
            $anos[$anoRef] = $anoRef;
        }

        $ano->addItems($anos);
        $ano->setValue($date);

        // create the form actions
        $btn_onsaveyear = $this->form->addAction("Confirmar", new TAction([$this, 'onSaveYear']), 'fas:check #ffffff');
        $this->btn_onsaveyear = $btn_onsaveyear;
        $btn_onsaveyear->addStyleClass('btn-primary'); 

        parent::add($this->form);

    }

    public function onSaveYear($param = null) 
    {
        try
        {
            $this->form->validate();

            $data = $this->form->getData();
            $ano = (int) $data->ano;

            TSession::setValue('contrato_save_ano_pendente', $ano);

            TWindow::closeWindow(parent::getId());

            TApplication::loadPage('ContratoForm', 'onSalvarContratoPendente');
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

    } 

}

