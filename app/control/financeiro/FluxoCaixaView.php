<?php

class FluxoCaixaView extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_FluxoCaixaView';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Fluxo de Caixa");


        $page_fluxo_analitico = new BPageContainer();
        $page_fluxo_sintetico = new BPageContainer();


        $page_fluxo_analitico->setSize('100%');
        $page_fluxo_sintetico->setSize('100%');

        $page_fluxo_analitico->setAction(new TAction(['FluxoCaixaAnaliticoSimpleList', 'onShow']));
        $page_fluxo_sintetico->setAction(new TAction(['FluxoCaixaSinteticoSimpleList', 'onShow']));

        $page_fluxo_analitico->setId('b65049c55454ff');
        $page_fluxo_sintetico->setId('b65084d4fe7dc9');

        $this->page_fluxo_analitico = $page_fluxo_analitico;
        $this->page_fluxo_sintetico = $page_fluxo_sintetico;

        $row1 = $this->form->addFields([new TLabel(" ", null, '14px', null, '100%'),new TLabel("Vencidos:", null, '14px', 'B', '100%')],[new TLabel("A receber:", null, '14px', 'B', '100%'),new TLabel("RECEBER VENCIDO", null, '14px', null)],[new TLabel("A pagar:", null, '14px', 'B', '100%'),new TLabel("PAGAR VENCIDO", null, '14px', null)],[new TLabel("Saldo:", null, '14px', 'B', '100%'),new TLabel("SALDO VENCIDO", null, '14px', null)]);
        $row1->layout = [' col-sm-2',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row2 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#eee')]);
        $row3 = $this->form->addFields([new TLabel("Saldo de hoje:", null, '14px', 'B')],[new TLabel("SALDO ATUAL", null, '14px', null)]);
        $row3->layout = ['col-sm-2','col-sm-3'];

        $tab_65049bb594f50 = new BootstrapFormBuilder('tab_65049bb594f50');
        $this->tab_65049bb594f50 = $tab_65049bb594f50;
        $tab_65049bb594f50->setProperty('style', 'border:none; box-shadow:none;');

        $tab_65049bb594f50->appendPage("Analítico");

        $tab_65049bb594f50->addFields([new THidden('current_tab_tab_65049bb594f50')]);
        $tab_65049bb594f50->setTabFunction("$('[name=current_tab_tab_65049bb594f50]').val($(this).attr('data-current_page'));");

        $row4 = $tab_65049bb594f50->addFields([$page_fluxo_analitico]);
        $row4->layout = [' col-sm-12'];

        $tab_65049bb594f50->appendPage("Sintético");
        $row5 = $tab_65049bb594f50->addFields([$page_fluxo_sintetico]);
        $row5->layout = [' col-sm-12'];

        $row6 = $this->form->addFields([$tab_65049bb594f50]);
        $row6->layout = [' col-sm-12'];

        // create the form actions

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Financeiro","Fluxo de Caixa"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public function onShow($param = null)
    {               

        TTransaction::open('escritorio');

        $receberVencido = 0; 
        $pagarVencido   = 0; 
        $saldoVencido   = 0; 

        $lancamentosVencidos = Lancamento::where('dt_vencimento','<=',date('Y-m-d'))
                                            ->where('cancelado','=','N')
                                            ->load();

        foreach($lancamentosVencidos as $lancamentoVencido){
            $pesquisaLancamento = Extrato::where('lancamento_id','=',$lancamentoVencido->id)->load();
            if(count($pesquisaLancamento)<=0){
                if($lancamentoVencido->conta->tipo_conta_id == TipoConta::RECEBER){
                    $receberVencido = (float) $receberVencido + $lancamentoVencido->valor;
                }else if($lancamentoVencido->conta->tipo_conta_id == TipoConta::PAGAR){
                    $pagarVencido = (float) $pagarVencido + $lancamentoVencido->valor;
                }
            }
        }

        $extratosVencidos = Extrato::where('data_previsao_compensacao','<=',date('Y-m-d'))
                                            ->where('compensado','=','N')
                                            ->load();

        foreach($extratosVencidos as $extratoVencido){
            if($extratoVencido->lancamento->conta->tipo_conta_id == TipoConta::RECEBER || $extratoVencido->tipo_extrato_id==TipoExtrato::ENTRADA){
                $receberVencido = (float) $receberVencido + $extratoVencido->entrada_valor;
            }else if($extratoVencido->lancamento->conta->tipo_conta_id == TipoConta::PAGAR|| $extratoVencido->tipo_extrato_id==TipoExtrato::SAIDA){
                $pagarVencido = (float) $pagarVencido + $extratoVencido->saida_valor;
            }
        }

        $saldoVencido = (float) $receberVencido - $pagarVencido;
        TScript::create("$('label:contains(\"RECEBER VENCIDO\")').html('R$ ".number_format($receberVencido,2,",",".")."')");
        TScript::create("$('label:contains(\"PAGAR VENCIDO\")').html('R$ ".number_format($pagarVencido,2,",",".")."')");
        TScript::create("$('label:contains(\"SALDO VENCIDO\")').html('R$ ".number_format($saldoVencido,2,",",".")."')");

        $contasCaixa = ContaCaixa::where('ativo','=','S')->load();
        $saldoInicial = 0;
        foreach($contasCaixa as $contaCaixa){
            $saldoInicial = $saldoInicial + $contaCaixa->saldo_instantaneo;
        }
        TScript::create("$('label:contains(\"SALDO ATUAL\")').html('R$ ".number_format($saldoInicial,2,",",".")."')");

        TTransaction::close();
    } 

}

