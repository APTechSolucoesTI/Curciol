<?php

class ContaCaixaFormView extends TWindow
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'ContaCaixa';
    private static $primaryKey = 'id';
    private static $formName = 'formView_ContaCaixa';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        parent::setSize(0.60, null);
        parent::setTitle("Relatório de conta caixa");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $contasSelecionadas = self::normalizarContas($param['contas'] ?? ($param['conta_caixa_id'] ?? ($param['key'] ?? [])));

        if(!$contasSelecionadas){
            throw new Exception('Nenhuma conta caixa foi informada.');
        }

        $param['key'] = $contasSelecionadas[0];
        $param['contas'] = implode(',', $contasSelecionadas);

        $conta_caixa = new ContaCaixa($param['key']);
        // define the form title
        $this->form->setFormTitle("Relatório de conta caixa");

        [$dataInicial, $dataFinal] = self::resolverPeriodo($param);

        $param['data_inicial'] = $dataInicial;
        $param['data_final'] = $dataFinal;

        $data = $dataInicial;
        $dadosContaAtual = self::carregarDadosConta($conta_caixa->id, $dataInicial, $dataFinal);

        $label2 = new TLabel("Nome:", '', '14px', 'B', '100%');
        $text2 = new TTextDisplay($conta_caixa->nome, '', '14px', '');
        $label3 = new TLabel("Tipo de conta caixa:", '', '14px', 'B', '100%');
        $text3 = new TTextDisplay($conta_caixa->tipo_conta_caixa->nome, '', '14px', '');
        $label4 = new TLabel("Data inicial:", '', '14px', 'B', '100%');
        $text4 = new TTextDisplay(TDateTime::convertToMask($conta_caixa->dt_inicial, 'yyyy-mm-dd hh:ii', 'dd/mm/yyyy hh:ii'), '', '14px', '');
        $label25 = new TLabel("Exportar:", '', '14px', 'B', '100%');
        $tbutton2 = new TButton('tbutton2');
        $tbutton4 = new TButton('tbutton4');
        $label9 = new TLabel("Código da agencia:", '', '14px', 'B', '100%');
        $text9 = new TTextDisplay($conta_caixa->codigo_agencia, '', '14px', '');
        $label10 = new TLabel("Código da conta:", '', '14px', 'B', '100%');
        $text10 = new TTextDisplay($conta_caixa->codigo_conta, '', '14px', '');
        $label8 = new TLabel("Banco:", '', '14px', 'B', '100%');
        $text8 = new TTextDisplay($conta_caixa->banco->nome, '', '14px', '');
        $label11 = new TLabel("Descrição da agencia:", '', '14px', 'B', '100%');
        $text11 = new TTextDisplay($conta_caixa->descricao_agencia, '', '14px', '');
        $labelEspaco = new TLabel(" ", '', '12px', '');
        $label5 = new TLabel("Saldo anterior:", '', '14px', 'B', '100%');
        $label6 = new TLabel("SALDO ANTERIOR", '', '14px', 'B', '100%');
        $label12 = new TLabel("Saldo posterior:", '', '14px', 'B', '100%');
        $label13 = new TLabel("SALDO POSTERIOR", '', '14px', 'B', '100%');

        $tbutton2->setAction(new TAction([$this, 'onExport']), "Sintético");
        $tbutton4->setAction(new TAction([$this, 'onExportAnalitico'],['static' => 1]), "Analítico");

        $tbutton2->addStyleClass('btn-default');
        $tbutton4->addStyleClass('btn-default');

        $tbutton2->setImage('fas:file-export #000000');
        $tbutton4->setImage('fas:file-export #000000');


        $conta = $conta_caixa->nome;

        $label4 = new TLabel("Período:", '', '14px', 'B', '100%');
        $text4 = new TTextDisplay(TDate::date2br($dataInicial)." até ".TDate::date2br($dataFinal), '', '14px', '');
        $labelEspaco = new TTextDisplay("Período selecionado: ".TDate::date2br($dataInicial)." até ".TDate::date2br($dataFinal), '#333333', '13px', 'B');

        $label6 = new TLabel("R$ ".number_format($dadosContaAtual['saldo_anterior'], 2, ',', '.'), '', '14px', 'B', '100%');
        $label13 = new TLabel("R$ ".number_format($dadosContaAtual['saldo_posterior'], 2, ',', '.'), '', '14px', 'B', '100%');

        if($conta_caixa->tipo_conta_caixa_id != TipoContaCaixa::BANCO){
            $label9 = new TLabel("", '', '14px', 'B', '100%');
            $text9 = new TTextDisplay('', '', '14px', '');
            $label10 = new TLabel("", '', '14px', 'B', '100%');
            $text10 = new TTextDisplay('', '', '14px', '');
            $label8 = new TLabel("", '', '14px', 'B', '100%');
            $text8 = new TTextDisplay('', '', '14px', '');
            $label11 = new TLabel("", '', '14px', 'B', '100%');
            $text11 = new TTextDisplay('', '', '14px', '');
        }

        $tbutton2->setAction(new TAction([__CLASS__, 'onExport'], [
            'key'=>$conta_caixa->id,
            'data_inicial'=>$dataInicial,
            'data_final'=>$dataFinal
        ]), "Sintético");

        $tbutton4->setAction(new TAction([__CLASS__, 'onExportAnalitico'], [
            'key'=>$conta_caixa->id,
            'data_inicial'=>$dataInicial,
            'data_final'=>$dataFinal
        ]), "Analítico");

        $this->form->appendPage("$conta");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([$label2,$text2],[$label3,$text3],[$label4,$text4],[$label25,$tbutton2,$tbutton4]);
        $row1->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row2 = $this->form->addFields([$label9,$text9],[$label10,$text10],[$label8,$text8],[$label11,$text11]);
        $row2->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row3 = $this->form->addFields([],[],[$labelEspaco]);
        $row3->layout = ['col-sm-3','col-sm-3','col-sm-6'];

        $row4 = $this->form->addFields([],[],[],[$label5,$label6]);
        $row4->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $this->extrato_conta_caixa_id_list = new TQuickGrid;
        $this->extrato_conta_caixa_id_list->style = 'width:100%';
        $this->extrato_conta_caixa_id_list->disableDefaultClick();

        $column_data_compensacao_transformed = $this->extrato_conta_caixa_id_list->addQuickColumn("Data da compensação", 'data_compensacao', 'left');
        $column_tipo_extrato_nome = $this->extrato_conta_caixa_id_list->addQuickColumn("Tipo", 'tipo_extrato->nome', 'left');
        $column_historico = $this->extrato_conta_caixa_id_list->addQuickColumn("Histórico", 'historico', 'left');
        $column_entrada_valor_transformed = $this->extrato_conta_caixa_id_list->addQuickColumn("Valor de entrada", 'entrada_valor', 'left');
        $column_saida_valor_transformed = $this->extrato_conta_caixa_id_list->addQuickColumn("Valor de saída", 'saida_valor', 'left');
        $column_calculated_1 = $this->extrato_conta_caixa_id_list->addQuickColumn("Saldo", '=( {entrada_valor} - {saida_valor}  )', 'left');

        $column_data_compensacao_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value)))
            {
                try
                {
                    $date = new DateTime($value);
                    return $date->format('d/m/Y');
                }
                catch (Exception $e)
                {
                    return $value;
                }
            }
        });

        $column_entrada_valor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });

        $column_saida_valor_transformed->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });

        $column_calculated_1->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!$value)
            {
                $value = 0;
            }

            if(is_numeric($value))
            {
                return "R$ " . number_format($value, 2, ",", ".");
            }
            else
            {
                return $value;
            }
        });

        $this->extrato_conta_caixa_id_list->createModel();

        $criteria_extrato_conta_caixa_id = new TCriteria();
        $criteria_extrato_conta_caixa_id->add(new TFilter('conta_caixa_id', '=', $conta_caixa->id));

        $criteria_extrato_conta_caixa_id->setProperty('order', 'data_compensacao asc');

        $filterVar = "S";
        $criteria_extrato_conta_caixa_id->add(new TFilter('compensado', '=', $filterVar));
        $filterVar = $data;
        $criteria_extrato_conta_caixa_id->add(new TFilter('data_compensacao', '>=', $filterVar));

        $extrato_conta_caixa_id_items = Extrato::getObjects($criteria_extrato_conta_caixa_id);

        $this->extrato_conta_caixa_id_list->addItems($extrato_conta_caixa_id_items);

        $icon = new TImage('fas:align-left #000000');
        $title = new TTextDisplay("{$icon} Extrato", '#333', '14px', '{$fontStyle}');

        $panel = new TPanelGroup($title, '#FFFFFF');
        $panel->class = 'panel panel-default formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->extrato_conta_caixa_id_list));

        $this->form->addContent([$panel]);
        $row5 = $this->form->addFields([],[],[],[$label12,$label13]);
        $row5->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        $this->extrato_conta_caixa_id_list->clear();
        $this->extrato_conta_caixa_id_list->addItems($dadosContaAtual['movimentacoes']);

        foreach(array_slice($contasSelecionadas, 1) as $contaId){
            $dadosConta = self::carregarDadosConta($contaId, $dataInicial, $dataFinal);
            $contaExtra = $dadosConta['conta'];

            $this->form->appendPage($contaExtra->nome);

            $buttonSintetico = new TButton('button_sintetico_'.$contaExtra->id);
            $buttonAnalitico = new TButton('button_analitico_'.$contaExtra->id);

            $buttonSintetico->setAction(new TAction([__CLASS__, 'onExport'], [
                'key'=>$contaExtra->id,
                'data_inicial'=>$dataInicial,
                'data_final'=>$dataFinal
            ]), "Sintético");

            $buttonAnalitico->setAction(new TAction([__CLASS__, 'onExportAnalitico'], [
                'key'=>$contaExtra->id,
                'data_inicial'=>$dataInicial,
                'data_final'=>$dataFinal
            ]), "Analítico");

            $buttonSintetico->addStyleClass('btn-default');
            $buttonAnalitico->addStyleClass('btn-default');

            $buttonSintetico->setImage('fas:file-export #000000');
            $buttonAnalitico->setImage('fas:file-export #000000');

            $rowConta = $this->form->addFields(
                [
                    new TLabel("Nome:", '', '14px', 'B', '100%'),
                    new TTextDisplay($contaExtra->nome, '', '14px', '')
                ],
                [
                    new TLabel("Tipo de conta caixa:", '', '14px', 'B', '100%'),
                    new TTextDisplay($dadosConta['tipo_nome'], '', '14px', '')
                ],
                [
                    new TLabel("Período:", '', '14px', 'B', '100%'),
                    new TTextDisplay(TDate::date2br($dataInicial)." até ".TDate::date2br($dataFinal), '', '14px', '')
                ],
                [
                    new TLabel("Exportar:", '', '14px', 'B', '100%'),
                    $buttonSintetico,
                    $buttonAnalitico
                ]
            );

            $rowConta->layout = ['col-sm-3','col-sm-3','col-sm-3','col-sm-3'];

            if($contaExtra->tipo_conta_caixa_id == TipoContaCaixa::BANCO){
                $rowBanco = $this->form->addFields(
                    [
                        new TLabel("Código da agência:", '', '14px', 'B', '100%'),
                        new TTextDisplay($contaExtra->codigo_agencia, '', '14px', '')
                    ],
                    [
                        new TLabel("Código da conta:", '', '14px', 'B', '100%'),
                        new TTextDisplay($contaExtra->codigo_conta, '', '14px', '')
                    ],
                    [
                        new TLabel("Banco:", '', '14px', 'B', '100%'),
                        new TTextDisplay($dadosConta['banco_nome'], '', '14px', '')
                    ],
                    [
                        new TLabel("Descrição da agência:", '', '14px', 'B', '100%'),
                        new TTextDisplay($contaExtra->descricao_agencia, '', '14px', '')
                    ]
                );

                $rowBanco->layout = ['col-sm-3','col-sm-3','col-sm-3','col-sm-3'];
            }

            $rowSaldoAnterior = $this->form->addFields([],[],[],[
                new TLabel("Saldo anterior:", '', '14px', 'B', '100%'),
                new TLabel("R$ ".number_format($dadosConta['saldo_anterior'], 2, ',', '.'), '', '14px', 'B', '100%')
            ]);

            $rowSaldoAnterior->layout = ['col-sm-3','col-sm-3','col-sm-3','col-sm-3'];

            $gridConta = self::criarGridExtrato($dadosConta['movimentacoes']);

            $iconConta = new TImage('fas:align-left #000000');
            $titleConta = new TTextDisplay("{$iconConta} Extrato", '#333', '14px', '');

            $panelConta = new TPanelGroup($titleConta, '#FFFFFF');
            $panelConta->class = 'panel panel-default formView-detail';
            $panelConta->add(new BootstrapDatagridWrapper($gridConta));

            $this->form->addContent([$panelConta]);

            $rowSaldoPosterior = $this->form->addFields([],[],[],[
                new TLabel("Saldo posterior:", '', '14px', 'B', '100%'),
                new TLabel("R$ ".number_format($dadosConta['saldo_posterior'], 2, ',', '.'), '', '14px', 'B', '100%')
            ]);

            $rowSaldoPosterior->layout = ['col-sm-3','col-sm-3','col-sm-3','col-sm-3'];
        }

        if(!empty($param['current_tab'])){
            $this->form->setCurrentPage($param['current_tab']);
        }

        $btn_onexportartudoAction = new TAction([$this, 'onExportarTudo'],['key'=>$conta_caixa->id]);
        $btn_onexportartudoLabel = new TLabel("Exportar Tudo");

        $btn_onexportartudo = $this->form->addHeaderAction($btn_onexportartudoLabel, $btn_onexportartudoAction, 'fas:file-excel #4CAF50'); 
        $btn_onexportartudoLabel->setFontSize('12px'); 
        $btn_onexportartudoLabel->setFontColor('#333'); 

        TTransaction::close();
        parent::add($this->form);

    }

    public static function onExport($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $key = (int) ($param['key'] ?? TSession::getValue(__CLASS__.'_key'));
            $dataInicial = $param['data_inicial'] ?? TSession::getValue(__CLASS__.'_data_inicial');
            $dataFinal = $param['data_final'] ?? TSession::getValue(__CLASS__.'_data_final');

            $dados = self::carregarDadosConta($key, $dataInicial, $dataFinal);
            $contaCaixa = $dados['conta'];

            $arquivo = 'app/output/'.self::nomeArquivo($contaCaixa->nome).'_sintetico_'.uniqid().'.xls';
            $larguras = [130,100,100,100,100];

            $table = new TTableWriterXLS($larguras);
            $table->addStyle('titleAll', 'Helvetica', '14', 'B', '#FFFFFF', '#122945');
            $table->addStyle('title', 'Helvetica', '10', 'B', '#FFFFFF', '#005284');
            $table->addStyle('data', 'Helvetica', '10', ' ', '#000000', '#FFFFFF');
            $table->addStyle('dataSaldo', 'Helvetica', '10', 'B', '#000000', '#FFFFFF');
            $table->addStyle('calcSaldo', 'Helvetica', '12', 'B', '#000000', '#FFFFFF');

            $table->addRow();
            $table->addCell('Conta caixa: '.$contaCaixa->nome, 'center', 'titleAll', 5);

            $table->addRow();
            $table->addCell('Tipo: '.$dados['tipo_nome'], 'left', 'data', 2);
            $table->addCell('Data inicial da conta: '.TDate::date2br(substr($contaCaixa->dt_inicial, 0, 10)), 'left', 'data', 3);

            $table->addRow();
            $table->addCell('Período: '.TDate::date2br($dataInicial).' até '.TDate::date2br($dataFinal), 'left', 'data', 5);

            if($contaCaixa->tipo_conta_caixa_id == TipoContaCaixa::BANCO){
                $table->addRow();
                $table->addCell('Agência: '.$contaCaixa->codigo_agencia, 'left', 'data');
                $table->addCell('Conta: '.$contaCaixa->codigo_conta, 'left', 'data');
                $table->addCell('Banco: '.$dados['banco_nome'], 'left', 'data', 3);
            }

            $table->addRow();
            $table->addCell('', 'left', 'data', 5);

            $table->addRow();
            $table->addCell('Saldo anterior:', 'center', 'calcSaldo');
            $table->addCell(number_format($dados['saldo_anterior'], 2, ',', '.'), 'center', 'calcSaldo');
            $table->addCell('', 'left', 'data', 3);

            $table->addRow();
            $table->addCell('', 'left', 'data', 5);

            $table->addRow();
            $table->addCell('Data da compensação', 'center', 'title');
            $table->addCell('Tipo', 'center', 'title');
            $table->addCell('Histórico', 'center', 'title');
            $table->addCell('Valor da entrada', 'center', 'title');
            $table->addCell('Valor da saída', 'center', 'title');

            $movimentado = 0;

            foreach($dados['movimentacoes'] as $movimentacao){
                $entrada = (float) $movimentacao->entrada_valor;
                $saida = (float) $movimentacao->saida_valor;

                $table->addRow();
                $table->addCell(TDate::date2br($movimentacao->data_compensacao), 'center', 'data');
                $table->addCell($movimentacao->tipo_extrato->nome, 'left', 'data');
                $table->addCell($movimentacao->historico, 'left', 'data');
                $table->addCell(number_format($entrada, 2, ',', '.'), 'right', 'data');
                $table->addCell(number_format($saida, 2, ',', '.'), 'right', 'data');

                $movimentado += $entrada - $saida;
            }

            $table->addRow();
            $table->addCell('', 'left', 'data', 5);

            $table->addRow();
            $table->addCell('Movimentação:', 'center', 'dataSaldo');
            $table->addCell(number_format($movimentado, 2, ',', '.'), 'center', 'dataSaldo');
            $table->addCell('', 'left', 'data', 3);

            $table->addRow();
            $table->addCell('', 'left', 'title', 5);

            $table->addRow();
            $table->addCell('', 'left', 'data', 5);

            $table->addRow();
            $table->addCell('Saldo posterior:', 'center', 'calcSaldo');
            $table->addCell(number_format($dados['saldo_posterior'], 2, ',', '.'), 'center', 'calcSaldo');
            $table->addCell('', 'left', 'data', 3);

            $table->save($arquivo);

            TTransaction::close();
            TPage::openFile($arquivo);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onExportAnalitico($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $key = (int) ($param['key'] ?? TSession::getValue(__CLASS__.'_key'));
            $dataInicial = $param['data_inicial'] ?? TSession::getValue(__CLASS__.'_data_inicial');
            $dataFinal = $param['data_final'] ?? TSession::getValue(__CLASS__.'_data_final');

            $dados = self::carregarDadosConta($key, $dataInicial, $dataFinal);
            $contaCaixa = $dados['conta'];

            $arquivo = 'app/output/'.self::nomeArquivo($contaCaixa->nome).'_analitico_'.uniqid().'.xls';
            $larguras = [130,100,100,100,100];

            $table = new TTableWriterXLS($larguras);
            $table->addStyle('titleAll', 'Helvetica', '14', 'B', '#FFFFFF', '#122945');
            $table->addStyle('title', 'Helvetica', '10', 'B', '#FFFFFF', '#005284');
            $table->addStyle('data', 'Helvetica', '10', ' ', '#000000', '#FFFFFF');
            $table->addStyle('dataSaldo', 'Helvetica', '10', 'B', '#000000', '#FFFFFF');
            $table->addStyle('calcSaldo', 'Helvetica', '12', 'B', '#000000', '#FFFFFF');

            $table->addRow();
            $table->addCell('Conta caixa: '.$contaCaixa->nome, 'center', 'titleAll', 5);

            $table->addRow();
            $table->addCell('Tipo:', 'left', 'data');
            $table->addCell($dados['tipo_nome'], 'left', 'data');
            $table->addCell('', 'left', 'data');
            $table->addCell('Data inicial da conta:', 'left', 'data');
            $table->addCell(TDate::date2br(substr($contaCaixa->dt_inicial, 0, 10)), 'left', 'data');

            $table->addRow();
            $table->addCell('Período: '.TDate::date2br($dataInicial).' até '.TDate::date2br($dataFinal), 'left', 'data', 5);

            if($contaCaixa->tipo_conta_caixa_id == TipoContaCaixa::BANCO){
                $table->addRow();
                $table->addCell('Agência: '.$contaCaixa->codigo_agencia, 'left', 'data');
                $table->addCell('Conta: '.$contaCaixa->codigo_conta, 'left', 'data');
                $table->addCell('Banco: '.$dados['banco_nome'], 'left', 'data');
                $table->addCell('', 'left', 'data', 2);
            }

            $table->addRow();
            $table->addCell('', 'left', 'data', 5);

            $table->addRow();
            $table->addCell('Saldo anterior:', 'left', 'calcSaldo');
            $table->addCell(number_format($dados['saldo_anterior'], 2, ',', '.'), 'center', 'calcSaldo');
            $table->addCell('', 'left', 'data', 3);

            $table->addRow();
            $table->addCell('', 'left', 'data', 5);

            $movimentacoesPorDia = [];

            foreach($dados['movimentacoes'] as $movimentacao){
                $movimentacoesPorDia[$movimentacao->data_compensacao][] = $movimentacao;
            }

            ksort($movimentacoesPorDia);

            $saldo = $dados['saldo_anterior'];

            foreach($movimentacoesPorDia as $dia=>$movimentacoes){
                $table->addRow();
                $table->addCell('Data da compensação:', 'center', 'title');
                $table->addCell(TDate::date2br($dia), 'center', 'dataSaldo');
                $table->addCell('', 'center', 'data', 3);

                $table->addRow();
                $table->addCell('', 'left', 'data', 5);

                $table->addRow();
                $table->addCell('Tipo', 'center', 'title');
                $table->addCell('Histórico', 'center', 'title', 2);
                $table->addCell('Valor da entrada', 'center', 'title');
                $table->addCell('Valor da saída', 'center', 'title');

                $movimentado = 0;

                foreach($movimentacoes as $movimentacao){
                    $entrada = (float) $movimentacao->entrada_valor;
                    $saida = (float) $movimentacao->saida_valor;

                    $table->addRow();
                    $table->addCell($movimentacao->tipo_extrato->nome, 'left', 'data');
                    $table->addCell($movimentacao->historico, 'left', 'data', 2);
                    $table->addCell(number_format($entrada, 2, ',', '.'), 'right', 'data');
                    $table->addCell(number_format($saida, 2, ',', '.'), 'right', 'data');

                    $movimentado += $entrada - $saida;
                }

                $saldo += $movimentado;

                $table->addRow();
                $table->addCell('', 'left', 'data', 5);

                $table->addRow();
                $table->addCell('Movimentação:', 'left', 'title');
                $table->addCell(number_format($movimentado, 2, ',', '.'), 'center', 'dataSaldo');
                $table->addCell('', 'left', 'data');
                $table->addCell('Saldo:', 'left', 'title');
                $table->addCell(number_format($saldo, 2, ',', '.'), 'right', 'dataSaldo');

                $table->addRow();
                $table->addCell('', 'left', 'data', 5);
            }

            $table->addRow();
            $table->addCell('', 'left', 'data', 5);

            $table->addRow();
            $table->addCell('Saldo posterior:', 'center', 'calcSaldo');
            $table->addCell(number_format($dados['saldo_posterior'], 2, ',', '.'), 'center', 'calcSaldo');
            $table->addCell('', 'left', 'data', 3);

            $table->save($arquivo);

            TTransaction::close();
            TPage::openFile($arquivo);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onExportarTudo($param = null) 
    {
        try 
        {
           TTransaction::open(self::$database);

            $contas = self::normalizarContas(
                $param['contas']
                ?? TSession::getValue(__CLASS__.'_contas')
                ?? ($param['key'] ?? [])
            );

            $dataInicial = $param['data_inicial'] ?? TSession::getValue(__CLASS__.'_data_inicial');
            $dataFinal = $param['data_final'] ?? TSession::getValue(__CLASS__.'_data_final');

            if(!$contas){
                throw new Exception('Nenhuma conta caixa foi informada para exportação.');
            }

            if(empty($dataInicial) || empty($dataFinal)){
                throw new Exception('O período da exportação não foi informado.');
            }

            $arquivo = self::gerarExcelTodasContas($contas, $dataInicial, $dataFinal);

            TTransaction::close();
            TPage::openFile($arquivo);

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {     

        $contas = self::normalizarContas($param['contas'] ?? ($param['conta_caixa_id'] ?? ($param['key'] ?? [])));
        [$dataInicial, $dataFinal] = self::resolverPeriodo($param);

        TSession::setValue(__CLASS__.'_contas', $contas);
        TSession::setValue(__CLASS__.'_key', $contas[0] ?? null);
        TSession::setValue(__CLASS__.'_data_inicial', $dataInicial);
        TSession::setValue(__CLASS__.'_data_final', $dataFinal);

    }

    private static function normalizarContas($contas)
    {
        if(!is_array($contas)){
            $contas = preg_split('/[^0-9]+/', (string) $contas, -1, PREG_SPLIT_NO_EMPTY);
        }

        $retorno = [];

        foreach($contas as $contaId){
            $contaId = (int) $contaId;

            if($contaId > 0){
                $retorno[$contaId] = $contaId;
            }
        }

        return array_values($retorno);
    }

    private static function resolverPeriodo($param)
    {
        $dataInicial = $param['data_inicial'] ?? ($param['data_periodo'] ?? null);
        $dataFinal = $param['data_final'] ?? ($param['data_periodo_final'] ?? null);

        if(empty($dataInicial) || empty($dataFinal)){
            $periodo = (int) ($param['periodo'] ?? 0);

            if($periodo > 0){
                $dataFinal = date('Y-m-d');
                $dataInicial = date('Y-m-d', strtotime("-{$periodo} days", strtotime($dataFinal)));
            }
        }

        if(empty($dataInicial) || empty($dataFinal)){
            throw new Exception('O período do relatório não foi informado.');
        }

        if($dataInicial > $dataFinal){
            throw new Exception('A data inicial não pode ser maior que a data final.');
        }

        return [$dataInicial, $dataFinal];
    }

    private static function carregarDadosConta($contaId, $dataInicial, $dataFinal)
    {
        $contaCaixa = ContaCaixa::find($contaId);

        if(!$contaCaixa){
            throw new Exception('Conta caixa não encontrada.');
        }

        $criteriaAnterior = new TCriteria();
        $criteriaAnterior->add(new TFilter('conta_caixa_id', '=', $contaId));
        $criteriaAnterior->add(new TFilter('compensado', '=', 'S'));
        $criteriaAnterior->add(new TFilter('data_compensacao', '<', $dataInicial));
        $criteriaAnterior->setProperty('order', 'data_compensacao asc, id asc');

        $saldoAnterior = (float) $contaCaixa->saldo_inicial;
        $movimentacoesAnteriores = Extrato::getObjects($criteriaAnterior);

        foreach($movimentacoesAnteriores as $movimentacao){
            $saldoAnterior += (float) $movimentacao->entrada_valor;
            $saldoAnterior -= (float) $movimentacao->saida_valor;
        }

        $criteriaPeriodo = new TCriteria();
        $criteriaPeriodo->add(new TFilter('conta_caixa_id', '=', $contaId));
        $criteriaPeriodo->add(new TFilter('compensado', '=', 'S'));
        $criteriaPeriodo->add(new TFilter('data_compensacao', 'between', $dataInicial, $dataFinal));
        $criteriaPeriodo->setProperty('order', 'data_compensacao asc, id asc');

        $movimentacoes = Extrato::getObjects($criteriaPeriodo);
        $saldoPosterior = $saldoAnterior;

        foreach($movimentacoes as $movimentacao){
            $saldoPosterior += (float) $movimentacao->entrada_valor;
            $saldoPosterior -= (float) $movimentacao->saida_valor;
        }

        $tipoConta = $contaCaixa->tipo_conta_caixa;
        $banco = $contaCaixa->tipo_conta_caixa_id == TipoContaCaixa::BANCO ? $contaCaixa->banco : null;

        return [
            'conta'=>$contaCaixa,
            'tipo_nome'=>$tipoConta ? $tipoConta->nome : '',
            'banco_nome'=>$banco ? $banco->nome : '',
            'saldo_anterior'=>$saldoAnterior,
            'saldo_posterior'=>$saldoPosterior,
            'movimentacoes'=>$movimentacoes
        ];
    }

    private static function criarGridExtrato($movimentacoes)
    {
        $grid = new TQuickGrid();
        $grid->style = 'width:100%';
        $grid->disableDefaultClick();

        $columnData = $grid->addQuickColumn("Data da compensação", 'data_compensacao', 'left');
        $grid->addQuickColumn("Tipo", 'tipo_extrato->nome', 'left');
        $grid->addQuickColumn("Histórico", 'historico', 'left');

        $columnEntrada = $grid->addQuickColumn("Valor de entrada", 'entrada_valor', 'left');
        $columnSaida = $grid->addQuickColumn("Valor de saída", 'saida_valor', 'left');
        $columnSaldo = $grid->addQuickColumn("Saldo", '=( {entrada_valor} - {saida_valor} )', 'left');

        $columnData->setTransformer(function($value, $object, $row, $cell = null, $last_row = null)
        {
            if(!empty(trim((string) $value))){
                try{
                    return (new DateTime($value))->format('d/m/Y');
                }catch(Exception $e){
                    return $value;
                }
            }
        });

        $formatarValor = function($value, $object, $row, $cell = null, $last_row = null)
        {
            $value = $value ?: 0;

            if(is_numeric($value)){
                return "R$ ".number_format($value, 2, ',', '.');
            }

            return $value;
        };

        $columnEntrada->setTransformer($formatarValor);
        $columnSaida->setTransformer($formatarValor);
        $columnSaldo->setTransformer($formatarValor);

        $grid->createModel();
        $grid->addItems($movimentacoes);

        return $grid;
    }

    private static function nomeArquivo($nome)
    {
        $nomeAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nome);
        $nomeAscii = $nomeAscii !== false ? $nomeAscii : $nome;
        $nomeAscii = preg_replace('/[^A-Za-z0-9_-]+/', '_', $nomeAscii);

        return trim($nomeAscii, '_') ?: 'conta_caixa';
    }

    private static function escaparXml($valor)
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function nomeAbaExcel($nome, $id, &$nomesUsados)
    {
        $nomeAscii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $nome);
        $nome = $nomeAscii !== false ? $nomeAscii : (string) $nome;
        $nome = preg_replace('/[\\\\\/\?\*\[\]:]/', '-', trim($nome));
        $nome = $nome ?: 'Conta '.$id;

        $base = substr($nome, 0, 31);
        $nomeFinal = $base;
        $contador = 2;

        while(in_array(strtolower($nomeFinal), $nomesUsados)){
            $sufixo = ' '.$contador;
            $nomeFinal = substr($base, 0, 31 - strlen($sufixo)).$sufixo;
            $contador++;
        }

        $nomesUsados[] = strtolower($nomeFinal);

        return $nomeFinal;
    }

    private static function celulaExcelXml($valor, $tipo = 'String', $estilo = null, $mergeAcross = null)
    {
        $atributos = '';

        if($estilo){
            $atributos .= ' ss:StyleID="'.$estilo.'"';
        }

        if($mergeAcross !== null){
            $atributos .= ' ss:MergeAcross="'.(int) $mergeAcross.'"';
        }

        return '<Cell'.$atributos.'><Data ss:Type="'.$tipo.'">'.self::escaparXml($valor).'</Data></Cell>';
    }

    private static function gerarExcelTodasContas($contas, $dataInicial, $dataFinal)
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
        $xml .= "<?mso-application progid=\"Excel.Sheet\"?>";
        $xml .= "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\" xmlns:html=\"http://www.w3.org/TR/REC-html40\">";
        $xml .= "<Styles>";
        $xml .= "<Style ss:ID=\"Default\" ss:Name=\"Normal\"><Alignment ss:Vertical=\"Bottom\"/><Borders/><Font ss:FontName=\"Helvetica\" ss:Size=\"10\"/><Interior/><NumberFormat/><Protection/></Style>";
        $xml .= "<Style ss:ID=\"TituloGeral\"><Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/><Font ss:FontName=\"Helvetica\" ss:Size=\"14\" ss:Bold=\"1\" ss:Color=\"#FFFFFF\"/><Interior ss:Color=\"#122945\" ss:Pattern=\"Solid\"/></Style>";
        $xml .= "<Style ss:ID=\"Titulo\"><Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/><Font ss:FontName=\"Helvetica\" ss:Size=\"10\" ss:Bold=\"1\" ss:Color=\"#FFFFFF\"/><Interior ss:Color=\"#005284\" ss:Pattern=\"Solid\"/></Style>";
        $xml .= "<Style ss:ID=\"Negrito\"><Font ss:FontName=\"Helvetica\" ss:Size=\"10\" ss:Bold=\"1\"/></Style>";
        $xml .= "<Style ss:ID=\"Moeda\"><NumberFormat ss:Format=\"Currency\"/></Style>";
        $xml .= "<Style ss:ID=\"MoedaNegrito\"><Font ss:FontName=\"Helvetica\" ss:Size=\"10\" ss:Bold=\"1\"/><NumberFormat ss:Format=\"Currency\"/></Style>";
        $xml .= "</Styles>";

        $nomesUsados = [];

        foreach($contas as $contaId){
            $dados = self::carregarDadosConta($contaId, $dataInicial, $dataFinal);
            $contaCaixa = $dados['conta'];
            $nomeAba = self::nomeAbaExcel($contaCaixa->nome, $contaCaixa->id, $nomesUsados);
            $movimentado = 0;

            $xml .= '<Worksheet ss:Name="'.self::escaparXml($nomeAba).'">';
            $xml .= '<Table>';
            $xml .= '<Column ss:Width="110"/><Column ss:Width="100"/><Column ss:Width="260"/><Column ss:Width="100"/><Column ss:Width="100"/>';

            $xml .= '<Row>'.self::celulaExcelXml('Conta caixa: '.$contaCaixa->nome, 'String', 'TituloGeral', 4).'</Row>';
            $xml .= '<Row>'.self::celulaExcelXml('Tipo: '.$dados['tipo_nome'], 'String', null, 1).self::celulaExcelXml('Data inicial da conta: '.TDate::date2br(substr($contaCaixa->dt_inicial, 0, 10)), 'String', null, 2).'</Row>';
            $xml .= '<Row>'.self::celulaExcelXml('Período: '.TDate::date2br($dataInicial).' até '.TDate::date2br($dataFinal), 'String', null, 4).'</Row>';

            if($contaCaixa->tipo_conta_caixa_id == TipoContaCaixa::BANCO){
                $xml .= '<Row>'.self::celulaExcelXml('Agência: '.$contaCaixa->codigo_agencia).self::celulaExcelXml('Conta: '.$contaCaixa->codigo_conta).self::celulaExcelXml('Banco: '.$dados['banco_nome'], 'String', null, 2).'</Row>';
            }

            $xml .= '<Row></Row>';
            $xml .= '<Row>'.self::celulaExcelXml('Saldo anterior:', 'String', 'Negrito').self::celulaExcelXml($dados['saldo_anterior'], 'Number', 'MoedaNegrito').'</Row>';
            $xml .= '<Row></Row>';
            $xml .= '<Row>'.self::celulaExcelXml('Data da compensação', 'String', 'Titulo').self::celulaExcelXml('Tipo', 'String', 'Titulo').self::celulaExcelXml('Histórico', 'String', 'Titulo').self::celulaExcelXml('Valor da entrada', 'String', 'Titulo').self::celulaExcelXml('Valor da saída', 'String', 'Titulo').'</Row>';

            foreach($dados['movimentacoes'] as $movimentacao){
                $entrada = (float) $movimentacao->entrada_valor;
                $saida = (float) $movimentacao->saida_valor;
                $movimentado += $entrada - $saida;

                $xml .= '<Row>';
                $xml .= self::celulaExcelXml(TDate::date2br($movimentacao->data_compensacao));
                $xml .= self::celulaExcelXml($movimentacao->tipo_extrato->nome);
                $xml .= self::celulaExcelXml($movimentacao->historico);
                $xml .= self::celulaExcelXml($entrada, 'Number', 'Moeda');
                $xml .= self::celulaExcelXml($saida, 'Number', 'Moeda');
                $xml .= '</Row>';
            }

            $xml .= '<Row></Row>';
            $xml .= '<Row>'.self::celulaExcelXml('Movimentação:', 'String', 'Negrito').self::celulaExcelXml($movimentado, 'Number', 'MoedaNegrito').'</Row>';
            $xml .= '<Row>'.self::celulaExcelXml('Saldo posterior:', 'String', 'Negrito').self::celulaExcelXml($dados['saldo_posterior'], 'Number', 'MoedaNegrito').'</Row>';
            $xml .= '</Table>';
            $xml .= '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>8</SplitHorizontal><TopRowBottomPane>8</TopRowBottomPane><ActivePane>2</ActivePane></WorksheetOptions>';
            $xml .= '</Worksheet>';
        }

        $xml .= '</Workbook>';

        $arquivo = 'app/output/contas_caixa_'.date('Ymd_His').'.xls';

        if(file_put_contents($arquivo, $xml) === false){
            throw new Exception('Não foi possível gerar o arquivo de exportação.');
        }

        return $arquivo;
    }

}

