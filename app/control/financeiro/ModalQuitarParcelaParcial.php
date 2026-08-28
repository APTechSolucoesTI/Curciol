<?php

class ModalQuitarParcelaParcial extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalQuitarParcelaParcial';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(0.40, null);
        parent::setTitle("Quitar Parcialmente");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Quitar Parcialmente");

        $criteria_conta_caixa_id = new TCriteria();
        $criteria_tipo_pagamento_id = new TCriteria();
        $criteria_cheque_banco_id = new TCriteria();

        $id = new THidden('id');
        $conta_caixa_id = new TDBCombo('conta_caixa_id', 'escritorio', 'ContaCaixa', 'id', '{nome}','nome asc' , $criteria_conta_caixa_id );
        $valor = new TNumeric('valor', '2', ',', '.', true, true );
        $dt_vencimento = new TDate('dt_vencimento');
        $tipo_pagamento_id = new TDBCombo('tipo_pagamento_id', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_tipo_pagamento_id );
        $dt_pagamento = new TDate('dt_pagamento');
        $compensado = new TCheckButton('compensado');
        $cheque_numero = new TEntry('cheque_numero');
        $cheque_banco_id = new TDBCombo('cheque_banco_id', 'escritorio', 'Banco', 'id', '{nome}','nome asc' , $criteria_cheque_banco_id );
        $historico = new TEntry('historico');

        $tipo_pagamento_id->setChangeAction(new TAction([$this,'onChangeTipo']));

        $conta_caixa_id->addValidation("Conta caixa", new TRequiredValidator()); 
        $valor->addValidation("Digite um valor para quitar a parcela.", new TRequiredValidator()); 
        $tipo_pagamento_id->addValidation("Tipo de pagamento", new TRequiredValidator()); 
        $dt_pagamento->addValidation("Data de pagamento", new TRequiredValidator()); 

        $id->setValue($param['lancamento_id'] ?? null);
        $dt_vencimento->setEditable(false);
        $tipo_pagamento_id->setDefaultOption(false);
        $compensado->setUseSwitch(true, 'blue');
        $compensado->setIndexValue("S");
        $dt_pagamento->setMask('dd/mm/yyyy');
        $dt_vencimento->setMask('dd/mm/yyyy');

        $dt_pagamento->setDatabaseMask('yyyy-mm-dd');
        $dt_vencimento->setDatabaseMask('yyyy-mm-dd');

        $conta_caixa_id->enableSearch();
        $cheque_banco_id->enableSearch();
        $tipo_pagamento_id->enableSearch();

        $id->setSize(200);
        $valor->setSize('100%');
        $historico->setSize('100%');
        $dt_pagamento->setSize('100%');
        $dt_vencimento->setSize('100%');
        $cheque_numero->setSize('100%');
        $conta_caixa_id->setSize('100%');
        $cheque_banco_id->setSize('100%');
        $tipo_pagamento_id->setSize('100%');

        $row1 = $this->form->addFields([$id]);
        $row1->layout = [' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Conta caixa:", '#FF0000', '14px', null, '100%'),$conta_caixa_id]);
        $row2->layout = ['col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Valor:", null, '14px', null, '100%'),$valor],[new TLabel("Data de vencimento:", null, '14px', null, '100%'),$dt_vencimento]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        $row4 = $this->form->addFields([new TLabel("Tipo de pagamento:", '#FF0000', '14px', null, '100%'),$tipo_pagamento_id],[new TLabel("Data da baixa:", '#FF0000', '14px', null, '100%'),$dt_pagamento],[new TLabel("Compensar:", null, '14px', null, '100%'),$compensado]);
        $row4->layout = ['col-sm-4','col-sm-4','col-sm-2'];

        $row5 = $this->form->addFields([new TLabel("Número do cheque:", '#FF0000', '14px', null, '100%'),$cheque_numero],[new TLabel("Banco:", '#FF0000', '14px', null, '100%'),$cheque_banco_id]);
        $row5->layout = [' col-sm-4',' col-sm-4'];

        $row6 = $this->form->addFields([new TLabel("Histórico:", null, '14px', null, '100%'),$historico]);
        $row6->layout = [' col-sm-12'];

        // create the form actions
        $btn_onpagar = $this->form->addAction("Quitar Parcialmente", new TAction([$this, 'onPagar']), 'fas:check-circle #ffffff');
        $this->btn_onpagar = $btn_onpagar;
        $btn_onpagar->addStyleClass('btn-success'); 


        TScript::create("$(\"[name='cheque_numero']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='cheque_banco_id']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$('label:contains(\"Numero do cheque:\")').hide();");
        TScript::create("$('label:contains(\"Banco:\")').hide();");

        parent::add($this->form);

    }

    public static function onChangeTipo($param = null) 
    {
        try 
        {
            TTransaction::open('escritorio');
            if($param['tipo_pagamento_id'] != null){
                if($param['tipo_pagamento_id'] == TipoPagamento::CHEQUE){
                    TScript::create("$(\"[name='cheque_numero']\").closest('.fb-inline-field-container').show()");
                    TScript::create("$(\"[name='cheque_banco_id']\").closest('.fb-inline-field-container').show()");
                    TScript::create("$('label:contains(\"Número do cheque:\")').show();");
                    TScript::create("$('label:contains(\"Banco:\")').show();");
                }else{
                    TScript::create("$(\"[name='cheque_numero']\").closest('.fb-inline-field-container').hide()");
                    TScript::create("$(\"[name='cheque_banco_id']\").closest('.fb-inline-field-container').hide()");
                    TScript::create("$('label:contains(\"Número do cheque:\")').hide();");
                    TScript::create("$('label:contains(\"Banco:\")').hide();");
                }
            }
            TTransaction::close();

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onPagar($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');

            $this->form->validate();
            $object = $this->form->getData();

            if(empty($object->id)){
                throw new Exception("Lançamento não informado.");
            }

            if(empty($object->conta_caixa_id)){
                throw new Exception("O campo Conta caixa é obrigatório.");
            }

            if(empty($object->tipo_pagamento_id)){
                throw new Exception("O campo Tipo de pagamento é obrigatório.");
            }

            if(empty($object->dt_pagamento)){
                throw new Exception("O campo Data da baixa é obrigatório.");
            }

            $lancamento = Lancamento::find($object->id);

            if(!$lancamento){
                throw new Exception("Lançamento não encontrado.");
            }

            if($lancamento->dt_pagamento){
                throw new Exception("Esse lançamento já foi quitado em ".TDate::date2br($lancamento->dt_pagamento));
            }

            // arruma o valor que vem do campo numeric
            $valorPago = str_replace(['R$', ' '], '', (string) $object->valor);

            if(strpos($valorPago, ',') !== false){
                $valorPago = str_replace('.', '', $valorPago);
                $valorPago = str_replace(',', '.', $valorPago);
            }

            $valorPago = round((float) $valorPago, 2);

            if($valorPago <= 0){
                throw new Exception("O valor pago deve ser maior que zero.");
            }

            // se ja teve pagamento parcial usa o saldo, senao usa o valor total
            if((float) $lancamento->saldo > 0){
                $valorDisponivel = round((float) $lancamento->saldo, 2);
            }else{
                $valorDisponivel = $lancamento->valor_total !== null && $lancamento->valor_total !== ''
                    ? round((float) $lancamento->valor_total, 2)
                    : round((float) $lancamento->valor, 2);
            }

            // trabalha com centavos pra nao dar problema de arredondamento
            $valorPagoCentavos = (int) round($valorPago * 100);
            $valorDisponivelCentavos = (int) round($valorDisponivel * 100);

            if($valorPagoCentavos > $valorDisponivelCentavos){
                throw new Exception("O valor pago não pode ser maior que o saldo disponível de R$ ".number_format($valorDisponivel, 2, ',', '.').".");
            }

            // aqui nao deixa pagar o valor inteiro porque essa tela é só pra parcial
            if($valorPagoCentavos == $valorDisponivelCentavos){
                throw new Exception("Esse valor quita completamente a parcela. Utilize a opção Quitar parcela.");
            }

            $novoSaldoCentavos = $valorDisponivelCentavos - $valorPagoCentavos;
            $novoSaldo = round($novoSaldoCentavos / 100, 2);

            if($object->tipo_pagamento_id == TipoPagamento::CHEQUE){
                if(empty($object->cheque_numero) || empty($object->cheque_banco_id)){
                    throw new Exception("Os campos Número do cheque e Banco são obrigatórios para pagamento em cheque.");
                }

                $lancamento->cheque_numero = $object->cheque_numero;
                $lancamento->cheque_banco_id = $object->cheque_banco_id;
            }else{
                $lancamento->cheque_numero = null;
                $lancamento->cheque_banco_id = null;
            }

            // salva só o que ainda falta pagar
            $lancamento->saldo = $novoSaldo;
            $lancamento->tipo_pagamento_id = $object->tipo_pagamento_id;

            // nao seta dt_pagamento aqui, senao o sistema entende que quitou tudo
            $lancamento->store();

            $conta = $lancamento->conta;
            $conta->quitada = 'N';
            $conta->proximo_vencimento_lancamento = $lancamento->dt_vencimento;
            $conta->store();

            $dataPagamento = $object->dt_pagamento;

            if(strpos($dataPagamento, '/') !== false){
                $dataPagamento = implode('-', array_reverse(explode('/', $dataPagamento)));
            }

            // cada pagamento parcial gera um extrato separado
            $extrato = new Extrato();
            $extrato->escritorio_id = $conta->escritorio_id;
            $extrato->conta_caixa_id = $object->conta_caixa_id;
            $extrato->lancamento_id = $lancamento->id;
            $extrato->categoria_conta_id = $conta->categoria_conta_id;

            if($conta->tipo_conta_id == TipoConta::RECEBER){
                $extrato->tipo_extrato_id = TipoExtrato::RECEBER;
                $extrato->entrada_valor = $valorPago;
                $extrato->saida_valor = 0;
            }elseif($conta->tipo_conta_id == TipoConta::PAGAR){
                $extrato->tipo_extrato_id = TipoExtrato::PAGAR;
                $extrato->entrada_valor = 0;
                $extrato->saida_valor = $valorPago;
            }else{
                throw new Exception("Tipo da conta não encontrado.");
            }

            $extrato->data_lancamento = date('Y-m-d');
            $extrato->data_previsao_compensacao = $lancamento->dt_vencimento;

           // ve se o usuario marcou pra compensar na hora
            $compensado = $object->compensado ?? 'N';

            if(in_array($compensado, ['S', '1', 1, true, 'on'], true)){
                $compensado = 'S';
            }else{
                $compensado = 'N';
            }

            if($compensado == 'S'){
                $extrato->compensado = 'S';
                $extrato->data_compensacao = $dataPagamento;
                $extrato->ano = date('Y', strtotime($dataPagamento));
                $extrato->mes = date('m', strtotime($dataPagamento));
                $extrato->ano_mes = date('Ym', strtotime($dataPagamento));

                $contaCaixa = ContaCaixa::find($extrato->conta_caixa_id);

                if(!$contaCaixa){
                    throw new Exception("Conta caixa não encontrada.");
                }

                $contaCaixa->saldo_instantaneo = (float) $contaCaixa->saldo_instantaneo + (float) $extrato->entrada_valor - (float) $extrato->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();
            }else{
                $extrato->compensado = 'N';

                $contaCaixa = ContaCaixa::find($extrato->conta_caixa_id);

                if(!$contaCaixa){
                    throw new Exception("Conta caixa não encontrada.");
                }

                $contaCaixa->saldo_nao_compensado = (float) $contaCaixa->saldo_nao_compensado + (float) $extrato->entrada_valor - (float) $extrato->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();
            }

            $historico = trim((string) $object->historico);

            $extrato->historico = $historico.
                " VALOR PAGO: R$ ".number_format($valorPago, 2, ',', '.').
                ". SALDO RESTANTE: R$ ".number_format($novoSaldo, 2, ',', '.').".";

            $extrato->criacao_user_id = TSession::getValue('userid');
            $extrato->store();

            // nao liga o extrato no extrato_id do lancamento porque agora pode ter varios parciais
            $contaId = $conta->id;
            $tipoContaId = $conta->tipo_conta_id;

            TTransaction::close();

            TToast::show('success', "Pagamento parcial realizado. Saldo restante: R$ ".number_format($novoSaldo, 2, ',', '.'), 'bottom right');

            AdiantiCoreApplication::loadPage('ContaFormView', 'onShow', ['key' => $contaId]);
            TScript::create("$(\"[page_name='ModalQuitarParcelaParcial']\").remove();");

        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               

        try
    {
        $lancamentoId = $param['lancamento_id'] ?? null;

        if (empty($lancamentoId))
        {
            throw new Exception(
                'Não foi possível encontrar o lançamento, tente novamente.'
            );
        }

        TTransaction::open('escritorio');

        $lancamento = Lancamento::find($lancamentoId);

        if (!$lancamento)
        {
            throw new Exception('Lançamento não encontrado.');
        }

        if ($lancamento->dt_pagamento)
        {
            throw new Exception(
                'Esse lançamento já foi quitado em ' .
                TDate::date2br($lancamento->dt_pagamento)
            );
        }

        /*
         * Quando ainda não houve pagamento parcial,
         * o saldo estará nulo ou zerado.
         *
         * Nesse caso, o valor disponível é o valor original.
         */
        $saldoAtual = round(
            (float) ($lancamento->saldo ?? 0),
            2
        );

       if($saldoAtual > 0){
            $valorDisponivel = $saldoAtual;
        }else{
            $valorDisponivel = $lancamento->valor_total !== null && $lancamento->valor_total !== ''
                ? round((float) $lancamento->valor_total, 2)
                : round((float) $lancamento->valor, 2);
        }

        if ($valorDisponivel <= 0)
        {
            throw new Exception(
                'Esse lançamento não possui saldo disponível.'
            );
        }

        if ($lancamento->tipo_pagamento_id == TipoPagamento::CHEQUE)
        {
            TScript::create(
                "$(\"[name='cheque_numero']\")" .
                ".closest('.fb-inline-field-container').show();"
            );

            TScript::create(
                "$(\"[name='cheque_banco_id']\")" .
                ".closest('.fb-inline-field-container').show();"
            );

            TScript::create(
                "$('label:contains(\"Número do cheque:\")').show();"
            );

            TScript::create(
                "$('label:contains(\"Banco:\")').show();"
            );
        }
        else
        {
            TScript::create(
                "$(\"[name='cheque_numero']\")" .
                ".closest('.fb-inline-field-container').hide();"
            );

            TScript::create(
                "$(\"[name='cheque_banco_id']\")" .
                ".closest('.fb-inline-field-container').hide();"
            );

            TScript::create(
                "$('label:contains(\"Número do cheque:\")').hide();"
            );

            TScript::create(
                "$('label:contains(\"Banco:\")').hide();"
            );
        }

        $object = new stdClass();

        /*
         * Mostra o saldo disponível no campo,
         * mas o usuário poderá digitar um valor menor.
         */
        $object->valor = number_format($valorDisponivel,2,',','.');

        $dataVencimento = new DateTime($lancamento->dt_vencimento);

        $object->dt_vencimento = $dataVencimento->format('d/m/Y');

        $object->tipo_pagamento_id = $lancamento->tipo_pagamento_id;

        $object->dt_pagamento = date('d/m/Y');

        $categoriaConta = $lancamento->conta->categoria_conta->nome;

        $pessoa = strtoupper($lancamento->conta->pessoa->nome);

        $tipoDocumento = strtoupper($lancamento->conta->tipo_documento_financeiro->nome);

        $numeroDocumento = $lancamento->conta->numero_documento;

        $totalParcelas = $lancamento->conta->total_parcelas;

        if ($lancamento->conta->tipo_conta_id == TipoConta::RECEBER)
        {
            $object->historico =
                "RECEBIMENTO PARCIAL - {$categoriaConta} - " .
                "{$pessoa} - REF. {$tipoDocumento} " .
                "#{$numeroDocumento} - PARCELA " .
                "{$lancamento->parcela}/{$totalParcelas}.";
        }
        elseif (
            $lancamento->conta->tipo_conta_id ==
            TipoConta::PAGAR
        )
        {
            $object->historico =
                "PAGAMENTO PARCIAL - {$categoriaConta} - " .
                "{$pessoa} - REF. {$tipoDocumento} " .
                "#{$numeroDocumento} - PARCELA " .
                "{$lancamento->parcela}/{$totalParcelas}.";
        }
        else
        {
            $object->historico =
                "QUITAÇÃO PARCIAL - {$categoriaConta} - " .
                "{$pessoa} - REF. {$tipoDocumento} " .
                "#{$numeroDocumento} - PARCELA " .
                "{$lancamento->parcela}/{$totalParcelas}.";
        }

        TForm::sendData(
            self::$formName,
            $object
        );

        TTransaction::close();
    }
    catch (Exception $e)
    {
        TTransaction::rollback();

        new TMessage(
            'error',
            $e->getMessage()
        );

        TScript::create(
            "$(\"[page_name='ModalQuitarParcelaParcial']\")" .
            ".remove();"
        );
    }

    } 


}

