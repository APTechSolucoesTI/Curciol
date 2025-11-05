<?php

class ModalCompensarExtrato extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalCompensarExtrato';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(0.20, null);
        parent::setTitle("Compensar");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Compensar");


        $data_compensacao = new TDate('data_compensacao');

        $data_compensacao->addValidation("Data da compensação", new TRequiredValidator()); 

        $data_compensacao->setSize('100%');
        $data_compensacao->setMask('dd/mm/yyyy');
        $data_compensacao->setValue(date('d-m-Y'));
        $data_compensacao->setDatabaseMask('yyyy-mm-dd');


        $row1 = $this->form->addFields([new TLabel("Data da compensação:", '#FF0000', '14px', null, '100%'),$data_compensacao]);
        $row1->layout = [' col-sm-12'];

        // create the form actions
        $btn_onaction = $this->form->addAction("Compensar", new TAction([$this, 'onAction']), 'fas:dollar-sign #ffffff');
        $this->btn_onaction = $btn_onaction;
        $btn_onaction->addStyleClass('btn-success'); 

        parent::add($this->form);

    }

    public function onAction($param = null) 
    {
        try
        {
            $selecionados = TSession::getValue('paramSelecionados');

            $this->form->validate(); // validate form data

            $data = $this->form->getData(); 

            $ids = [];
            foreach($selecionados as $info){
                $ids[] = $info;
            }

            TTransaction::open('escritorio');
            foreach($ids as $id){
                $aux = Extrato::find($id);

                if($aux->compensado=='S'){
                    throw new Exception("Selecione uma transação que não esteja compensada.");
                }

                $data_compensacao = $data->data_compensacao;
                $contaCaixa_dtInicial = substr($aux->conta_caixa->dt_inicial, 0, -9);

                if($data_compensacao>date('Y-m-d')){
                    throw new Exception("A data da compensação não pode ser superior a data de hoje.");
                }

                if($data_compensacao<=$contaCaixa_dtInicial){
                    throw new Exception("A data da compensação deve ser mais recente que a data do inicio da conta caixa.");
                }

                if($aux->lancamento_id){
                    if($data_compensacao<$aux->lancamento->dt_pagamento){
                        throw new Exception("A data da compensação deve ser mais recente que a data da baixa do lançamento.");
                    }
                }

                $aux->modificacao_user_id = TSession::getValue('userid');
                $aux->compensado = 'S';
                $aux->data_compensacao = $data->data_compensacao;
                $aux->ano = date('Y', strtotime($data->data_compensacao));
                $aux->mes = date('m', strtotime($data->data_compensacao));
                $aux->ano_mes = date('Ym', strtotime($data->data_compensacao));
                $aux->store();

                //saldo_instantaneo
                $contaCaixa = ContaCaixa::find($aux->conta_caixa_id);

                $contaCaixa->saldo_instantaneo = (float) $contaCaixa->saldo_instantaneo + (float) $aux->entrada_valor - (float) $aux->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();

                //saldo_nao_compensado
                $contaCaixa = ContaCaixa::find($aux->conta_caixa_id);

                $contaCaixa->saldo_nao_compensado = (float) $contaCaixa->saldo_nao_compensado - (float) $aux->entrada_valor + (float) $aux->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();
            }
            TTransaction::close();

            TToast::show("success", "Compensação concluída", "topRight", "");
            TApplication::loadPage('ExtratoList', 'onReload');
            TScript::create("$(\"[page_name='ModalCompensarExtrato']\").remove()");
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

