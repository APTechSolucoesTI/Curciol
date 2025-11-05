<?php

class ExtratoFormView extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Extrato';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Extrato';

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

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $extrato = new Extrato($param['key']);
        // define the form title
        $this->form->setFormTitle("Visualizar movimentação");

        $transformed_extrato_compensado = call_user_func(function($value, $object, $row)
        {

            $label = new TElement('span');
            $label->{'class'} = 'label label-';

            if ($value == 'S' || $value == 'T') {
                $label->{'class'} .= 'success';
                $label->add('Sim');    

                return $label;
            }

            $label->{'class'} .= 'danger';
            $label->add('Não');

            return $label;
        }, $extrato->compensado, $extrato, null);

        $label1 = new TLabel("Id:", '', '14px', 'B', '100%');
        $text1 = new TTextDisplay($extrato->id, '', '14px', '');
        $label3 = new TLabel("Conta caixa:", '', '14px', 'B', '100%');
        $text3 = new TTextDisplay($extrato->conta_caixa->nome, '', '14px', '');
        $label2 = new TLabel("Escritório:", '', '14px', 'B', '100%');
        $text2 = new TTextDisplay($extrato->escritorio->nome, '', '14px', '');
        $label6 = new TLabel("Tipo de extrato:", '', '14px', 'B', '100%');
        $text6 = new TTextDisplay($extrato->tipo_extrato->nome, '', '14px', '');
        $label5 = new TLabel("Categoria de conta:", '', '14px', 'B', '100%');
        $text5 = new TTextDisplay($extrato->categoria_conta->nome, '', '14px', '');
        $label7 = new TLabel("Relacionado a conta caixa:", '', '14px', 'B', '100%');
        $text7 = new TTextDisplay($extrato->transferencia_conta_caixa->nome, '', '14px', '');
        $label8 = new TLabel("Movimentação vinculada:", '', '14px', 'B', '100%');
        $text8 = new TTextDisplay($extrato->extrato_vinculado, '', '14px', '');
        $label4 = new TLabel("Referente a parcela:", '', '14px', 'B', '100%');
        $text4 = new TTextDisplay($extrato->lancamento->parcela, '', '14px', '');
        $label28 = new TLabel("Da conta: ", '', '14px', 'B', '100%');
        $text168 = new TTextDisplay($extrato->lancamento->conta_id, '', '14px', '');
        $label46 = new TLabel("-", '', '16px', '');
        $text148 = new TTextDisplay($extrato->lancamento->conta->descricao, '', '14px', '');
        $label299 = new TLabel("Tipo de pagamento:", '', '14px', 'B', '100%');
        $text149 = new TTextDisplay($extrato->lancamento->tipo_pagamento->nome, '', '14px', '');
        $text20 = new TTextDisplay($extrato->lancamento->cheque_numero, '', '14px', '');
        $label499 = new TLabel("Número:", '', '14px', 'B', '100%');
        $label699 = new TLabel("Banco:", '', '14px', 'B', '100%');
        $text22 = new TTextDisplay($extrato->lancamento->cheque_banco->nome, '', '14px', '');
        $label899 = new TLabel("Previsão de compensação:", '', '14px', 'B', '100%');
        $datetext499 = new TTextDisplay(TDate::convertToMask($extrato->lancamento->dt_vencimento, 'yyyy-mm-dd', 'dd/mm/yyyy'), '', '14px', '');
        $label14 = new TLabel("Histórico:", '', '14px', 'B', '100%');
        $text14 = new TTextDisplay($extrato->historico, '', '16px', '');
        $label11 = new TLabel("Movimentado em:", '', '14px', 'B', '100%');
        $text11 = new TTextDisplay(TDate::convertToMask($extrato->data_lancamento, 'yyyy-mm-dd', 'dd/mm/yyyy'), '', '14px', '');
        $label9 = new TLabel("Valor da entrada:", '', '14px', 'B', '100%');
        $label29 = new TLabel("R$ ", '', '16px', '');
        $text9 = new TTextDisplay(number_format((double)$extrato->entrada_valor, '2', ',', '.'), '#000000', '14px', '');
        $label10 = new TLabel("Valor da saída:", '', '14px', 'B', '100%');
        $label49 = new TLabel("R$ ", '', '16px', '');
        $text10 = new TTextDisplay(number_format((double)$extrato->saida_valor, '2', ',', '.'), '', '14px', '');
        $label12 = new TLabel("Compensado:", '', '14px', 'B', '100%');
        $text12 = new TTextDisplay($transformed_extrato_compensado, '', '16px', '');
        $label13 = new TLabel("Data da compensação:", '', '14px', 'B', '100%');
        $text13 = new TTextDisplay(TDate::convertToMask($extrato->data_compensacao, 'yyyy-mm-dd', 'dd/mm/yyyy'), '', '14px', '');
        $label15 = new TLabel("Criado em:", '', '14px', 'B', '100%');
        $text15 = new TTextDisplay(TDateTime::convertToMask($extrato->data_criacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '16px', '');
        $label16 = new TLabel("Criado por:", '', '14px', 'B', '100%');
        $text16 = new TTextDisplay($extrato->criacao_user->name, '', '14px', '');
        $label17 = new TLabel("Atualizado em:", '', '14px', 'B', '100%');
        $text17 = new TTextDisplay(TDateTime::convertToMask($extrato->data_modificacao, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '16px', '');
        $label18 = new TLabel("Atualizado por:", '', '14px', 'B', '100%');
        $text18 = new TTextDisplay($extrato->modificacao_user->name, '', '14px', '');



        $row1 = $this->form->addFields([$label1,$text1],[$label3,$text3],[$label2,$text2]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([$label6,$text6],[$label5,$text5]);
        $row2->layout = ['col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([$label7,$text7],[$label8,$text8]);
        $row3->layout = [' col-sm-4',' col-sm-4'];

        $row4 = $this->form->addFields([$label4,$text4],[$label28,$text168,$label46,$text148]);
        $row4->layout = [' col-sm-3',' col-sm-9'];

        $row5 = $this->form->addFields([$label299,$text149],[$text20,$label499],[$label699,$text22],[]);
        $row5->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-xs-3'];

        $row6 = $this->form->addFields([$label899,$datetext499],[],[]);
        $row6->layout = ['col-sm-3','col-sm-3', 'col-sm-6'];

        $row7 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row8 = $this->form->addFields([$label14,$text14],[$label11,$text11]);
        $row8->layout = ['col-sm-6','col-sm-6'];

        $row9 = $this->form->addFields([$label9,$label29,$text9],[$label10,$label49,$text10]);
        $row9->layout = ['col-sm-6','col-sm-6'];

        $row10 = $this->form->addFields([$label12,$text12],[$label13,$text13]);
        $row10->layout = ['col-sm-6','col-sm-6'];

        $row11 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#EEEEEE')]);
        $row12 = $this->form->addFields([$label15,$text15],[$label16,$text16],[$label17,$text17],[$label18,$text18]);
        $row12->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        TTransaction::close();
        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ExtratoFormView]');
        $style->width = '60% !important';   
        $style->show(true);

    }

    public function onShow($param = null)
    {     

        TTransaction::open(self::$database);
        $movimentacao = Extrato::find($param['key']);
        if($movimentacao->tipo_extrato_id != TipoExtrato::TRANSFERENCIA){
            TScript::create("$('label:contains(\"Relacionado a conta caixa:\")').hide();");
            TScript::create("$('label:contains(\"Movimentação vinculada:\")').hide();");
        }
        if($movimentacao->tipo_extrato_id != TipoExtrato::RECEBER && $movimentacao->tipo_extrato_id != TipoExtrato::PAGAR){
            TScript::create("$('label:contains(\"Referente a parcela:\")').hide();");
            TScript::create("$('label:contains(\"Da conta:\")').hide();");
            TScript::create("$('label:contains(\"-\")').hide();");
            TScript::create("$('label:contains(\"Tipo de pagamento:\")').hide();");
            TScript::create("$('label:contains(\"Previsão de compensação:\")').hide();");
            TScript::create("$('label:contains(\"Número:\")').hide();");
            TScript::create("$('label:contains(\"Banco:\")').hide();");
        }else{
            if($movimentacao->lancamento->tipo_pagamento_id != TipoPagamento::CHEQUE){
                TScript::create("$('label:contains(\"Número:\")').hide();");
                TScript::create("$('label:contains(\"Banco:\")').hide();");
            }
        }
        if(!isset($movimentacao->categoria_conta_id)){
            TScript::create("$('label:contains(\"Categoria de conta:\")').hide();");
        }

        TTransaction::close();
    }

}

