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

        $conta_caixa = new ContaCaixa($param['key']);
        // define the form title
        $this->form->setFormTitle("Relatório de conta caixa");

        $dias = $param['periodo'];

        $data = date('Y-m-d', strtotime("-$dias days", strtotime(date('Y-m-d'))));

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


        TTransaction::close();
        parent::add($this->form);

    }

    public static function onExport($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $key = TSession::getValue('key');

            $dias = TSession::getValue('periodo');
            $data = date('Y-m-d', strtotime("-$dias days", strtotime(date('Y-m-d'))));

            $contaCaixa = ContaCaixa::find($key);

            $arquivo = 'app/output/'.$contaCaixa->nome.'_'.uniqid().'.xls';
            $larguras = [130,100,100,100,100];

            $table = new TTableWriterXLS($larguras);
            $table->addStyle('titleAll',  'Helvetica', '14', 'B', '#FFFFFF', '#122945');
            $table->addStyle('title',     'Helvetica', '10', 'B', '#FFFFFF', '#005284');
            $table->addStyle('data',      'Helvetica', '10', ' ', '#000000', '#FFFFFF');
            $table->addStyle('dataSaldo', 'Helvetica', '10', 'B', '#000000', '#FFFFFF');
            $table->addStyle('calcSaldo', 'Helvetica', '12', 'B', '#000000', '#FFFFFF');

            //Linha titulo principal
            $table->addRow();
            $table->addCell('Conta caixa: '.$contaCaixa->nome, 'center', 'titleAll',5);

            //Linha com informações
            $table->addRow();
            $table->addCell('Tipo: '.$contaCaixa->tipo_conta_caixa->nome, 'left', 'data',2);
            $table->addCell('Data inicial: '.TDate::date2br($contaCaixa->dt_inicial), 'left', 'data',3);

            //Linha para dados do banco
            if($contaCaixa->tipo_conta_caixa_id == TipoContaCaixa::BANCO){
                $table->addRow();
                $table->addCell('Agencia: '.$contaCaixa->codigo_agencia, 'left', 'data');
                $table->addCell('Conta: '.$contaCaixa->codigo_conta, 'left', 'data');
                $table->addCell('Banco: '.$contaCaixa->banco->nome, 'left', 'data',3);
            }

            //Linha com saldo
            $table->addRow();
            $table->addCell('', 'left', 'data',5);
            $table->addRow();
            $table->addCell('Saldo anterior: ', 'center', 'calcSaldo');
            $table->addCell(number_format(TSession::getValue('saldoAnt'),2,',','.'), 'center', 'calcSaldo');
            $table->addCell('', 'left', 'data',3);
            $table->addRow();
            $table->addCell('', 'left', 'data',5);

            //Linha titulo dos extratos
            $table->addRow();
            $table->addCell('Data da compensação', 'center', 'title');
            $table->addCell('Tipo', 'center', 'title');
            $table->addCell('Histórico', 'center', 'title');
            $table->addCell('Valor da entrada', 'center', 'title');
            $table->addCell('Valor da saída', 'center', 'title');

            $movimentacoes = Extrato::where('conta_caixa_id','=',$key)
                                ->where('compensado','=','S')
                                ->where('data_compensacao','>=',$data)
                                ->load();
            $movimentado = 0;
            if ($movimentacoes)
            {
                foreach ($movimentacoes as $movimentacao)
                {
                    //Linhas de movimentaçao
                    $table->addRow();
                    $table->addCell(TDate::date2br($movimentacao->data_compensacao), 'center', 'data');
                    $table->addCell($movimentacao->tipo_extrato->nome, 'left', 'data');
                    $table->addCell($movimentacao->historico, 'left', 'data');
                    $table->addCell(number_format($movimentacao->entrada_valor,2,',','.'), 'right', 'data');        
                    $table->addCell(number_format($movimentacao->saida_valor,2,',','.'), 'right', 'data');
                    $movimentado = $movimentado + $movimentacao->entrada_valor - $movimentacao->saida_valor;
                }
            }
            $table->addRow();
            $table->addCell('', 'left', 'data',5);
            $table->addRow();
            $table->addCell('Movimentação: ', 'center', 'dataSaldo');
            $table->addCell(number_format($movimentado,2,',','.'), 'center', 'dataSaldo');
            $table->addCell('', 'left', 'data',3);
            $table->addRow();
            $table->addCell('', 'left', 'title',5);
            $table->addRow();
            $table->addCell('', 'left', 'data',5);
            $table->addRow();
            $table->addCell('Saldo posterior: ', 'center', 'calcSaldo');
            $table->addCell(number_format(TSession::getValue('saldoPost'),2,',','.'), 'center', 'calcSaldo');
            $table->addCell('', 'left', 'data',3);

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

            $key = TSession::getValue('key');

            $dias = TSession::getValue('periodo');
            $data = date('Y-m-d', strtotime("-$dias days", strtotime(date('Y-m-d'))));

            $contaCaixa = ContaCaixa::find($key);

            $arquivo = 'app/output/'.$contaCaixa->nome.'_'.uniqid().'.xls';
            $larguras = [130,100,100,100,100];

            $table = new TTableWriterXLS($larguras);
            $table->addStyle('titleAll',  'Helvetica', '14', 'B', '#FFFFFF', '#122945');
            $table->addStyle('title',     'Helvetica', '10', 'B', '#FFFFFF', '#005284');
            $table->addStyle('data',      'Helvetica', '10', ' ', '#000000', '#FFFFFF');
            $table->addStyle('dataSaldo', 'Helvetica', '10', 'B', '#000000', '#FFFFFF');
            $table->addStyle('calcSaldo', 'Helvetica', '12', 'B', '#000000', '#FFFFFF');

            //Linha titulo principal
            $table->addRow();
            $table->addCell('Conta caixa: '.$contaCaixa->nome, 'center', 'titleAll',5);

            //Linha com informações
            $table->addRow();
            $table->addCell('Tipo: ', 'left', 'data');
            $table->addCell($contaCaixa->tipo_conta_caixa->nome, 'left', 'data');
            $table->addCell('', 'left', 'data');
            $table->addCell('Data inicial: ', 'left', 'data');
            $table->addCell(TDate::date2br($contaCaixa->dt_inicial), 'left', 'data');

            //Linha para dados do banco
            if($contaCaixa->tipo_conta_caixa_id == TipoContaCaixa::BANCO){
                $table->addRow();
                $table->addCell('Agencia: '.$contaCaixa->codigo_agencia, 'left', 'data');
                $table->addCell('Conta: '.$contaCaixa->codigo_conta, 'left', 'data');
                $table->addCell('Banco: '.$contaCaixa->banco->nome, 'left', 'data');
                $table->addCell('', 'left', 'data',2);
            }

            //Linha com saldo
            $saldoAnterior = TSession::getValue('saldoAnt');
            $table->addRow();
            $table->addCell('', 'left', 'data',5);
            $table->addRow();
            $table->addCell('Saldo anterior: ', 'left', 'calcSaldo');
            $table->addCell(number_format($saldoAnterior,2,',','.'), 'center', 'calcSaldo');
            $table->addCell('', 'left', 'data',3);
            $table->addRow();
            $table->addCell('', 'left', 'data',5);

            //Array de dia por dia
            $movimentacoes = Extrato::where('conta_caixa_id','=',$key)
                                ->where('compensado','=','S')
                                ->where('data_compensacao','>=',$data)
                                ->load();
            $dias = [];
            foreach($movimentacoes as $movimentacao){
                $dias[] = $movimentacao->data_compensacao;
            }
            $dias = array_unique($dias);

            $saldo = $saldoAnterior;

            foreach($dias as $dia){

                $table->addRow();
                $table->addCell('Data da compensação: ', 'center', 'title');
                $table->addCell(TDate::date2br($dia), 'center', 'dataSaldo');
                $table->addCell('', 'center', 'data',3);

                $table->addRow();
                $table->addCell('', 'left', 'data',5);

                //Linha titulo dos extratos
                $table->addRow();
                $table->addCell('Tipo', 'center', 'title');
                $table->addCell('Histórico', 'center', 'title',2);
                $table->addCell('Valor da entrada', 'center', 'title');
                $table->addCell('Valor da saída', 'center', 'title');

                $movimentacoes = Extrato::where('conta_caixa_id','=',$key)
                                        ->where('compensado','=','S')
                                        ->where('data_compensacao','=',$dia)
                                        ->load();

                $movimentado = 0;

                foreach($movimentacoes as $movimentacao){
                    //Linhas de movimentaçao
                    $table->addRow();
                    $table->addCell($movimentacao->tipo_extrato->nome, 'left', 'data');
                    $table->addCell($movimentacao->historico, 'left', 'data',2);
                    $table->addCell(number_format($movimentacao->entrada_valor,2,',','.'), 'right', 'data');        
                    $table->addCell(number_format($movimentacao->saida_valor,2,',','.'), 'right', 'data');
                    $movimentado = $movimentado + $movimentacao->entrada_valor - $movimentacao->saida_valor;
                }

                $table->addRow();
                $table->addCell('', 'left', 'data',5);

                $saldo = $saldo + $movimentado;
                $table->addRow();
                $table->addCell('Movimentação: ', 'left', 'title');
                $table->addCell(number_format($movimentado,2,',','.'), 'center', 'dataSaldo');
                $table->addCell('', 'left', 'data');
                $table->addCell('Saldo: ', 'left', 'title');
                $table->addCell(number_format($saldo,2,',','.'), 'right', 'dataSaldo');
                $table->addRow();
                $table->addCell('', 'left', 'data',5);
            }

            $table->addRow();
            $table->addCell('', 'left', 'data',5);
            $table->addRow();
            $table->addCell('Saldo posterior: ', 'center', 'calcSaldo');
            $table->addCell(number_format(TSession::getValue('saldoPost'),2,',','.'), 'center', 'calcSaldo');
            $table->addCell('', 'left', 'data',3);

            $table->save($arquivo);

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

        TTransaction::open(self::$database);

        TSession::setValue('periodo',$param['periodo']);
        TSession::setValue('key',$param['key']);

        $contaCaixa = ContaCaixa::find($param['key']);

        if($contaCaixa->tipo_conta_caixa_id != TipoContaCaixa::BANCO){
            TScript::create("$('label:contains(\"Banco:\")').hide();");
            TScript::create("$('label:contains(\"Descrição da agencia:\")').hide();");
            TScript::create("$('label:contains(\"Código da agencia:\")').hide();");
            TScript::create("$('label:contains(\"Código da conta:\")').hide();");
        }

        $dias = $param['periodo'];

        $data = date('Y-m-d', strtotime("-$dias days", strtotime(date('Y-m-d'))));

        $movimentacoes = Extrato::where('conta_caixa_id','=',$param['key'])
                                ->where('compensado','=','S')
                                ->where('data_compensacao','<',$data)
                                ->load();

        $saldo = (float) $contaCaixa->saldo_inicial;

        if($movimentacoes){
            foreach ($movimentacoes as $movimentacao) {
                if($movimentacao->entrada_valor){
                    $saldo = $saldo + (float) $movimentacao->entrada_valor;
                }
                if($movimentacao->saida_valor){
                    $saldo = $saldo - (float) $movimentacao->saida_valor;
                }
            }
        }

        $saldoAnt = number_format($saldo, 2, ',', '.');
        TSession::setValue('saldoAnt',$saldo);

        TScript::create("$('label:contains(\"SALDO ANTERIOR\")').html('R$ $saldoAnt')");

        $movimentacoes = Extrato::where('conta_caixa_id','=',$param['key'])
                                ->where('compensado','=','S')
                                ->where('data_compensacao','>=',$data)
                                ->load();

        if($movimentacoes){
            foreach ($movimentacoes as $movimentacao) {
                if($movimentacao->entrada_valor){
                    $saldo = $saldo + (float) $movimentacao->entrada_valor;
                }
                if($movimentacao->saida_valor){
                    $saldo = $saldo - (float) $movimentacao->saida_valor;
                }
            }
        }

        $saldoPost = number_format($saldo, 2, ',', '.');
        TSession::setValue('saldoPost',$saldo);

        TScript::create("$('label:contains(\"SALDO POSTERIOR\")').html('R$ $saldoPost')");

        TTransaction::close();
    }

}

