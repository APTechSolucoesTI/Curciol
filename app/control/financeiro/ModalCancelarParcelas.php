<?php

class ModalCancelarParcelas extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalCancelarParcelas';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(0.30, null);
        parent::setTitle("Cancelar Parcelas");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Cancelar Parcelas");

        TSession::setValue('conta_id', (int) $param['conta_id']);

        $conta_id = new THidden('conta_id');
        $page = new THidden('page');
        $motivo_cancelamento = new TEntry('motivo_cancelamento');

        $motivo_cancelamento->addValidation("Motivo do cancelamento", new TRequiredValidator()); 

        $page->setValue($param['page']);
        $conta_id->setValue(TSession::getValue('conta_id'));

        $page->setSize(200);
        $conta_id->setSize(200);
        $motivo_cancelamento->setSize('100%');


        $row1 = $this->form->addFields([$conta_id,$page,new TLabel("Motivo do cancelamento:", null, '14px', null, '100%'),$motivo_cancelamento]);
        $row1->layout = [' col-sm-12'];

        // create the form actions
        $btn_oncancelar = $this->form->addAction("Cancelar", new TAction([$this, 'onCancelar']), 'fas:save #ffffff');
        $this->btn_oncancelar = $btn_oncancelar;
        $btn_oncancelar->addStyleClass('btn-primary'); 

        parent::add($this->form);

    }

    public function onCancelar($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');;

            $lancamentos = Lancamento::where('conta_id','=',$param['conta_id'])
                                ->load();

            foreach ($lancamentos as $lancamento) {
                if(!$lancamento->dt_pagamento){
                    $object = Lancamento::find($lancamento->id);
                    $object->cancelado = 'S';
                    $object->motivo_cancelamento = $param['motivo_cancelamento'];
                    $object->store();
                }
            }

            $pageParam = ['key'=>$param['conta_id']];
            TApplication::loadPage($param['page'], 'onEdit', $pageParam);
            TScript::create("$(\"[page_name='ModalCancelarParcelas']\").remove()");

            TTransaction::close();
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

