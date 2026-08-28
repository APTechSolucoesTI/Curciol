<?php

class SincronizarPublicacoesAPIForm extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_SincronizarPublicacoesAPIForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(700, null);
        parent::setTitle("Sincronizar publicações");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Sincronizar publicações");


        $de = new TDate('de');
        $ate = new TDate('ate');

        $de->addValidation("Data inicial", new TRequiredValidator()); 
        $ate->addValidation("Data final", new TRequiredValidator()); 

        $de->setSize('100%');
        $ate->setSize('100%');

        $de->setMask('dd/mm/yyyy');
        $ate->setMask('dd/mm/yyyy');

        $de->setDatabaseMask('yyyy-mm-dd');
        $ate->setDatabaseMask('yyyy-mm-dd');


        $row1 = $this->form->addFields([new TLabel("Selecione o periodo para sincronização:", null, '14px', null)]);
        $row1->layout = [' col-sm-12'];

        $row2 = $this->form->addFields([new TLabel("Data inicial:", null, '14px', null)],[$de],[new TLabel("Data final:", null, '14px', null)],[$ate]);

        // create the form actions
        $btn_onsincronizar = $this->form->addAction("Sincronizar", new TAction([$this, 'onSincronizar']), 'fas:spinner #ffffff');
        $this->btn_onsincronizar = $btn_onsincronizar;
        $btn_onsincronizar->addStyleClass('btn-primary'); 

        parent::add($this->form);

    }

    public function onSincronizar($param = null) 
    {
        try
        {
            $this->form->validate(); // validate form data

            $objeto = $this->form->getData(); // get form data as array

            if($objeto->de > $objeto->ate){
                throw new Exception("A data inicial deve ser anterior a data final.");
            }

            APIPublicacaoController::buscarPublicacoes($objeto);
            APIPublicacaoController::onVerificaPublicacaoEtapa();

            TApplication::loadPage('PublicacaoHeaderList', 'onShow');

            TWindow::closeWindow();

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

