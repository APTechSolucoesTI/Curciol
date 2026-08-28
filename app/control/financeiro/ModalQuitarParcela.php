<?php

class ModalQuitarParcela extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalQuitarParcela';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(0.40, null);
        parent::setTitle("Quitar lançamento");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Quitar lançamento");

        $criteria_conta_caixa_id = new TCriteria();
        $criteria_tipo_pagamento_id = new TCriteria();
        $criteria_cheque_banco_id = new TCriteria();

        $distribuicao_ajuste = new TRadioGroup('distribuicao_ajuste');
        $lancamento_profissional_id = new TCombo('lancamento_profissional_id');

        $distribuicao_ajuste->addItems([
            'T' => 'Distribuir proporcionalmente para todos',
            'U' => 'Distribuir para um profissional'
        ]);

        $distribuicao_ajuste->setLayout('horizontal');
        $distribuicao_ajuste->setValue('T');
        $distribuicao_ajuste->setChangeAction(new TAction([__CLASS__, 'onChangeDistribuicaoAjuste']));

        $lancamento_profissional_id->enableSearch();
        $distribuicao_ajuste->setSize('100%');
        $lancamento_profissional_id->setSize('100%');

        $id = new THidden('id');
        $conta_caixa_id = new TDBCombo('conta_caixa_id', 'escritorio', 'ContaCaixa', 'id', '{nome}','nome asc' , $criteria_conta_caixa_id );
        $valor = new TNumeric('valor', '2', ',', '.', true, true );
        $valor_total = new THidden('valor_total');
        $dt_vencimento = new TDate('dt_vencimento');
        $acrescimo = new TNumeric('acrescimo', '2', ',', '.' );
        $desconto = new TNumeric('desconto', '2', ',', '.' );
        $tipo_pagamento_id = new TDBCombo('tipo_pagamento_id', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_tipo_pagamento_id );
        $dt_pagamento = new TDate('dt_pagamento');
        $compensado = new TCheckButton('compensado');
        $cheque_numero = new TEntry('cheque_numero');
        $cheque_banco_id = new TDBCombo('cheque_banco_id', 'escritorio', 'Banco', 'id', '{nome}','nome asc' , $criteria_cheque_banco_id );
        $historico = new TEntry('historico');

        $tipo_pagamento_id->setChangeAction(new TAction([$this,'onChangeTipo']));

        $conta_caixa_id->addValidation("Conta caixa", new TRequiredValidator()); 
        $tipo_pagamento_id->addValidation("Tipo de pagamento", new TRequiredValidator()); 
        $dt_pagamento->addValidation("Data de pagamento", new TRequiredValidator()); 
        $compensado->addValidation("Compensar", new TRequiredValidator()); 

        $id->setValue($param['lancamento_id'] ?? null);
        $tipo_pagamento_id->setDefaultOption(false);
        $compensado->setUseSwitch(true, 'blue');
        $compensado->setIndexValue("S");
        $valor->setEditable(false);
        $dt_vencimento->setEditable(false);

        $dt_pagamento->setMask('dd/mm/yyyy');
        $dt_vencimento->setMask('dd/mm/yyyy');

        $dt_pagamento->setDatabaseMask('yyyy-mm-dd');
        $dt_vencimento->setDatabaseMask('yyyy-mm-dd');

        $conta_caixa_id->enableSearch();
        $cheque_banco_id->enableSearch();
        $tipo_pagamento_id->enableSearch();

        $id->setSize(200);
        $valor->setSize('100%');
        $valor_total->setSize(200);
        $desconto->setSize('100%');
        $acrescimo->setSize('100%');
        $historico->setSize('100%');
        $dt_pagamento->setSize('100%');
        $dt_vencimento->setSize('100%');
        $cheque_numero->setSize('100%');
        $conta_caixa_id->setSize('100%');
        $cheque_banco_id->setSize('100%');
        $tipo_pagamento_id->setSize('100%');

        $row1 = $this->form->addFields([$id]);
        $row1->layout = [' col-sm-3'];

        $row2 = $this->form->addFields([new TLabel("Conta caixa:", '#FF0000', '14px', null, '100%'),$conta_caixa_id],[]);
        $row2->layout = [' col-sm-6',' col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Valor:", null, '14px', null, '100%'),$valor,$valor_total],[new TLabel("Data de vencimento:", null, '14px', null, '100%'),$dt_vencimento]);
        $row3->layout = [' col-sm-6',' col-sm-6'];

        $row4 = $this->form->addFields([new TLabel("Acréscimo:", null, '14px', null, '100%'),$acrescimo],[new TLabel("Desconto:", null, '14px', null, '100%'),$desconto]);
        $row4->layout = [' col-sm-3',' col-sm-3'];

        $row5 = $this->form->addFields([new TLabel("Tipo de pagamento:", '#FF0000', '14px', null, '100%'),$tipo_pagamento_id],[new TLabel("Data da baixa:", '#FF0000', '14px', null, '100%'),$dt_pagamento],[new TLabel("Compensar:", null, '14px', null, '100%'),$compensado]);
        $row5->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row6 = $this->form->addFields([new TLabel("Número do cheque:", '#FF0000', '14px', null, '100%'),$cheque_numero],[new TLabel("Banco:", '#FF0000', '14px', null, '100%'),$cheque_banco_id]);
        $row6->layout = [' col-sm-4',' col-sm-4'];

        $row7 = $this->form->addFields([new TLabel("Histórico:", null, '14px', null, '100%'),$historico]);
        $row7->layout = [' col-sm-12'];

       $rowAjusteProfissionais = $this->form->addFields(
            [new TLabel("Distribuição do desconto/acréscimo:", null, '14px', null, '100%'), $distribuicao_ajuste]
        );

        $rowAjusteProfissionais->layout = [' col-sm-12'];
        $rowAjusteProfissionais->id = 'row_ajuste_profissionais';

        $rowProfissionalAjuste = $this->form->addFields(
            [new TLabel("Profissional:", null, '14px', null, '100%'), $lancamento_profissional_id]
        );

        $rowProfissionalAjuste->layout = [' col-sm-12'];

        // create the form actions
        $btn_onpagar = $this->form->addAction("Quitar", new TAction([$this, 'onPagar']), 'fas:check-circle #ffffff');
        $this->btn_onpagar = $btn_onpagar;
        $btn_onpagar->addStyleClass('btn-success'); 


        TScript::create("$(\"[name='cheque_numero']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$(\"[name='cheque_banco_id']\").closest('.fb-inline-field-container').hide()");
        TScript::create("$('label:contains(\"Numero do cheque:\")').hide();");
        TScript::create("$('label:contains(\"Banco:\")').hide();");

        TScript::create("
            window.converterValorQuitacao = function(valor){
                valor = (valor || '0').toString().replace(/\\./g, '').replace(',', '.');
                return parseFloat(valor) || 0;
            };

            window.recalcularValorTotalQuitacao = function(){
                var valor = window.converterValorQuitacao($(\"[name='valor']\").val());
                var acrescimo = window.converterValorQuitacao($(\"[name='acrescimo']\").val());
                var desconto = window.converterValorQuitacao($(\"[name='desconto']\").val());
                var valorTotal = valor + acrescimo - desconto;

                $(\"[name='valor_total']\").val(valorTotal.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }));
            };

            $(document).off('input.quitarLancamento', \"[name='acrescimo'], [name='desconto']\");

            $(document).on(
                'input.quitarLancamento',
                \"[name='acrescimo'], [name='desconto']\",
                window.recalcularValorTotalQuitacao
            );
        ");

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
            $dadosConfirmacao = null;
            $chaveConfirmacaoNegativo = __CLASS__.'_confirmacao_negativo';
            $confirmouProfissionalNegativo = ($param['confirmar_profissional_negativo'] ?? null) == '1';

            if($confirmouProfissionalNegativo){
                $tokenConfirmacao = $param['token_confirmacao_negativo'] ?? null;

                if(empty($tokenConfirmacao)){
                    throw new Exception('Não foi possível recuperar os dados da confirmação.');
                }

                $dadosConfirmacao = TSession::getValue($chaveConfirmacaoNegativo);

                if(empty($dadosConfirmacao) || ($dadosConfirmacao['token'] ?? null) != $tokenConfirmacao){
                    throw new Exception('A confirmação expirou. Tente realizar a quitação novamente.');
                }

                TSession::delValue($chaveConfirmacaoNegativo);

                $param = $dadosConfirmacao['param'];
                $param['confirmar_profissional_negativo'] = '1';
            }

            TTransaction::open('escritorio');

            $lancamento = Lancamento::find($param['id']);

            if(!$lancamento){
                throw new Exception("Lançamento não encontrado.");
            }

            if($lancamento->dt_pagamento){
                throw new Exception('Esse lançamento já foi quitado em '.TDate::date2br($lancamento->dt_pagamento));
            }

            if($dadosConfirmacao){
                $object = (object) $dadosConfirmacao['object'];
            }else{
                $object = $this->form->getData();
            }

            if(!$object->conta_caixa_id){
                throw new Exception("O campo Conta caixa é obrigatório.");
            }

            // se teve pagamento parcial, aplica os ajustes sobre o saldo
            $valorBaseLancamento = $lancamento->valor_total !== null && $lancamento->valor_total !== ''
                ? round((float) $lancamento->valor_total, 2)
                : round((float) $lancamento->valor, 2);

            // se teve pagamento parcial, movimenta somente o saldo
            if((float) $lancamento->saldo > 0){
                $valorQuitacao = round((float) $lancamento->saldo, 2);
            }else{
                $valorQuitacao = $valorBaseLancamento;
            }

            $acrescimo = self::valorFloat($object->acrescimo);
            $desconto = self::valorFloat($object->desconto);

            // vai para o extrato agora
            $valorMovimentado = round($valorQuitacao + $acrescimo - $desconto, 2);

            // fica salvo como valor final completo do lancamento
            $novoValorTotal = round($valorBaseLancamento + $acrescimo - $desconto, 2);

            if($acrescimo < 0){
                throw new Exception("O acréscimo não pode ser negativo.");
            }

            if($desconto < 0){
                throw new Exception("O desconto não pode ser negativo.");
            }

            if($valorMovimentado <= 0){
                throw new Exception("O valor total da quitação deve ser maior que zero.");
            }

            if($novoValorTotal <= 0){
                throw new Exception("O valor total do lançamento deve ser maior que zero.");
            }

            if(!$confirmouProfissionalNegativo){
                $profissionaisNegativos = self::verificarProfissionaisNegativos($lancamento, $object, $acrescimo, $desconto);

                if($profissionaisNegativos){

                    $tokenConfirmacao = md5(uniqid('', true));

                    TSession::setValue($chaveConfirmacaoNegativo, [
                        'token' => $tokenConfirmacao,
                        'param' => $param,
                        'object' => (array) $object
                    ]);

                    $acaoSim = new TAction([$this, 'onPagar']);
                    $acaoSim->setParameter('confirmar_profissional_negativo', '1');
                    $acaoSim->setParameter('token_confirmacao_negativo', $tokenConfirmacao);

                    $mensagem = self::montarMensagemProfissionaisNegativos($profissionaisNegativos);

                    self::restaurarFormularioConfirmacaoNegativo($lancamento, $object);

                    TTransaction::close();

                    new TQuestion($mensagem, $acaoSim, null);
                    return;
                }
            }

            self::registrarAjustesProfissionais($lancamento, $object, $acrescimo, $desconto);

            $object->valor = $valorQuitacao;
            $object->valor_total = $valorMovimentado;

            $lancamento->dt_pagamento = implode('-', array_reverse(explode('/', $object->dt_pagamento)));
            $lancamento->tipo_pagamento_id = $object->tipo_pagamento_id;
            $lancamento->acrescimo = $acrescimo;
            $lancamento->desconto = $desconto;
            $lancamento->valor_total = $novoValorTotal;

            if($object->tipo_pagamento_id == TipoPagamento::CHEQUE){
                if(!$object->cheque_numero || !$object->cheque_banco_id){
                    throw new Exception("Os campos Número do cheque e Banco são obrigatórios para pagamento em cheque.");
                }

                $lancamento->cheque_numero = $object->cheque_numero;
                $lancamento->cheque_banco_id = $object->cheque_banco_id;
            }else{
                $lancamento->cheque_numero = null;
                $lancamento->cheque_banco_id = null;
            }

            // quitou o que faltava, então não existe mais saldo
            $lancamento->saldo = 0;
            $lancamento->store();

            $totalConta = 0;

            $lancamentosConta = Lancamento::where('conta_id', '=', $lancamento->conta_id)->load();

            foreach($lancamentosConta as $lancamentoConta){
                if($lancamentoConta->cancelado == 'S'){
                    continue;
                }

                $valorTotalLancamento = $lancamentoConta->valor_total !== null && $lancamentoConta->valor_total !== ''
                    ? $lancamentoConta->valor_total
                    : $lancamentoConta->valor;

                $totalConta += self::valorFloat($valorTotalLancamento);
            }

            $lancamento->conta->total_conta = round($totalConta, 2);

            $criteria = new TCriteria;
            $criteria->add(new TFilter('dt_pagamento', 'IS', null));
            $criteria->add(new TFilter('conta_id', '=', $lancamento->conta_id));

            $lancamentoAbertos = Lancamento::countObjects($criteria);

            if($lancamentoAbertos == 0){
                $lancamento->conta->quitada = 'S';
                $lancamento->conta->store();
            }

            $extrato = new Extrato();
            $extrato->escritorio_id = $lancamento->conta->escritorio_id;
            $extrato->conta_caixa_id = $object->conta_caixa_id;
            $extrato->lancamento_id = $lancamento->id;
            $extrato->categoria_conta_id = $lancamento->conta->categoria_conta_id;

            // o extrato recebe o valor já com desconto ou acréscimo
            if($lancamento->conta->tipo_conta_id == TipoConta::RECEBER){
                $extrato->tipo_extrato_id = TipoExtrato::RECEBER;
                $extrato->entrada_valor = $valorMovimentado;
            }else if($lancamento->conta->tipo_conta_id == TipoConta::PAGAR){
                $extrato->tipo_extrato_id = TipoExtrato::PAGAR;
                $extrato->saida_valor = $valorMovimentado;
            }

            $extrato->data_lancamento = date('Y-m-d');
            $extrato->data_previsao_compensacao = $lancamento->dt_vencimento;

            if($object->compensado == 'S'){
                $extrato->compensado = 'S';
                $extrato->data_compensacao = $lancamento->dt_pagamento;

                $extrato->ano = date('Y', strtotime($lancamento->dt_pagamento));
                $extrato->mes = date('m', strtotime($lancamento->dt_pagamento));
                $extrato->ano_mes = date('Ym', strtotime($lancamento->dt_pagamento));

                $contaCaixa = ContaCaixa::find($extrato->conta_caixa_id);
                $contaCaixa->saldo_instantaneo = (float) $contaCaixa->saldo_instantaneo + (float) $extrato->entrada_valor - (float) $extrato->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();
            }else{
                $contaCaixa = ContaCaixa::find($extrato->conta_caixa_id);
                $contaCaixa->saldo_nao_compensado = (float) $contaCaixa->saldo_nao_compensado + (float) $extrato->entrada_valor - (float) $extrato->saida_valor;
                $contaCaixa->modificacao_user_id = TSession::getValue('userid');
                $contaCaixa->store();
            }

            $extrato->historico = $object->historico;
            $extrato->criacao_user_id = TSession::getValue('userid');
            $extrato->store();

            $lancamento->extrato_id = $extrato->id;
            $lancamento->store();

            $proximoLancamento = Lancamento::where('conta_id', '=', $lancamento->conta->id)
                ->where('parcela', '=', ((int) $lancamento->parcela + 1))
                ->load();

            foreach($proximoLancamento as $value){
                if(empty($value->dt_pagamento)){
                    $lancamento->conta->proximo_vencimento_lancamento = $value->dt_vencimento;
                    break;
                }
            }

            $lancamento->conta->store();

            TToast::show('success', 'Lançamento quitado com sucesso', 'bottom right');

            AdiantiCoreApplication::loadPage('ContaFormView', 'onShow', ['key' => $lancamento->conta_id]);
            TScript::create("$(\"[page_name='ModalQuitarParcela']\").remove()");

            TTransaction::close();
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TScript::create("$(\"[page_name='ModalQuitarParcela']\").remove()");
        }
    }

    public function onShow($param = null)
    {               

        try
        {   

            if (!$param['lancamento_id'])
            {
                throw new Exception('Não foi possivel encontrar o lançamento, tente novamente.');
            }

            TTransaction::open('escritorio');

            $lancamento = Lancamento::find($param['lancamento_id']);

            if(!$lancamento){
                throw new Exception('Lançamento não encontrado.');
            }

            $lancamentosProfissionais = LancamentoProfissional::where('lancamento_id', '=', $lancamento->id)->load();

            $opcoesProfissionais = [];

            foreach($lancamentosProfissionais as $lancamentoProfissional){
                $profissional = Pessoa::find($lancamentoProfissional->pessoa_id);

                if(!$profissional){
                    continue;
                }

                $percentual = number_format((float) $lancamentoProfissional->percentual, 2, ',', '.');
                $valorProfissional = number_format((float) $lancamentoProfissional->valor, 2, ',', '.');

                $opcoesProfissionais[$lancamentoProfissional->id] = "{$profissional->nome} - {$percentual}% - R$ {$valorProfissional}";
            }

            TCombo::reload(self::$formName, 'lancamento_profissional_id', $opcoesProfissionais, true);

            TCombo::disableField(self::$formName, 'lancamento_profissional_id');
            BootstrapFormBuilder::hideField(self::$formName, 'lancamento_profissional_id');

            if ($lancamento->dt_pagamento)
            {
                throw new Exception('Esse lançamento já foi quitado em '. TDate::date2br($lancamento->dt_pagamento));
            }

            if($lancamento->tipo_pagamento_id == TipoPagamento::CHEQUE){
                TScript::create("$(\"[name='cheque_numero']\").closest('.fb-inline-field-container').show()");
                TScript::create("$(\"[name='cheque_banco_id']\").closest('.fb-inline-field-container').show()");
                TScript::create("$('label:contains(\"Numero do cheque:\")').show();");
                TScript::create("$('label:contains(\"Banco:\")').show();");
            }else{
                TScript::create("$(\"[name='cheque_numero']\").closest('.fb-inline-field-container').hide()");
                TScript::create("$(\"[name='cheque_banco_id']\").closest('.fb-inline-field-container').hide()");
                TScript::create("$('label:contains(\"Numero do cheque:\")').hide();");
                TScript::create("$('label:contains(\"Banco:\")').hide();");
            }

           $object = new stdClass();

           $object->distribuicao_ajuste = 'T';
            $object->lancamento_profissional_id = null;

            // se teve pagamento parcial, os ajustes serão aplicados sobre o saldo
            $valorBaseLancamento = $lancamento->valor_total !== null && $lancamento->valor_total !== ''
                ? round((float) $lancamento->valor_total, 2)
                : round((float) $lancamento->valor, 2);

            if((float) $lancamento->saldo > 0){
                $valorQuitacao = round((float) $lancamento->saldo, 2);
            }else{
                $valorQuitacao = $valorBaseLancamento;
            }

            $acrescimo = round((float) ($lancamento->acrescimo ?? 0), 2);
            $desconto = round((float) ($lancamento->desconto ?? 0), 2);
            $valorTotal = round($valorQuitacao + $acrescimo - $desconto, 2);

            $object->valor = number_format($valorQuitacao, 2, ',', '.');
            $object->acrescimo = number_format($acrescimo, 2, ',', '.');
            $object->desconto = number_format($desconto, 2, ',', '.');
            $object->valor_total = number_format($valorTotal, 2, ',', '.');

            $data = new DateTime($lancamento->dt_vencimento);
            $object->dt_vencimento = $data->format('d/m/Y');
            $object->tipo_pagamento_id = $lancamento->tipo_pagamento_id;
            $object->dt_pagamento = date('d/m/Y');

            $catConta = $lancamento->conta->categoria_conta->nome;
            $pessoa = strtoupper($lancamento->conta->pessoa->nome);
            $tipoDoc =strtoupper($lancamento->conta->tipo_documento_financeiro->nome);
            $numDoc = $lancamento->conta->numero_documento;
            $totalParcelas = $lancamento->conta->total_parcelas;

            if($lancamento->conta->tipo_conta_id == TipoConta::RECEBER){
                $object->historico = "RECEBIMENTO - $catConta - $pessoa - REF. $tipoDoc #$numDoc - PARCELA $lancamento->parcela/$totalParcelas.";
            }elseif ($lancamento->conta->tipo_conta_id == TipoConta::PAGAR) {
                $object->historico = "PAGAMENTO - $catConta - $pessoa - REF. $tipoDoc #$numDoc - PARCELA $lancamento->parcela/$totalParcelas.";
            }else{
                $object->historico = "CONTA - $catConta - $pessoa - REF. $tipoDoc #$numDoc - PARCELA $lancamento->parcela/$totalParcelas.";
            }

            TForm::sendData(self::$formName, $object);

            $temProfissionais = count($opcoesProfissionais) > 0 ? 'true' : 'false';

            TTransaction::close();

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TScript::create("$(\"[page_name='ModalQuitarParcela']\").remove()");
        }

    } 

    private static function valorFloat($valor)
    {
        if($valor === null || $valor === ''){
            return 0;
        }

        if(is_numeric($valor)){
            return round((float) $valor, 2);
        }

        $valor = str_replace(['R$', ' '], '', (string) $valor);
        $valor = str_replace('.', '', $valor);
        $valor = str_replace(',', '.', $valor);

        return round((float) $valor, 2);
    }

    private static function registrarAjustesProfissionais($lancamento, $object, $acrescimo, $desconto)
    {
        if($acrescimo <= 0 && $desconto <= 0){
            return;
        }

        $lancamentosProfissionais = LancamentoProfissional::where('lancamento_id', '=', $lancamento->id)->load();

        if(!$lancamentosProfissionais){
            return;
        }

        $tipoDistribuicao = $object->distribuicao_ajuste ?? null;

        if(!in_array($tipoDistribuicao, ['T', 'U'])){
            throw new Exception("Informe como o desconto ou acréscimo deve ser distribuído entre os profissionais.");
        }

        if($tipoDistribuicao == 'U'){
            if(empty($object->lancamento_profissional_id)){
                throw new Exception("Selecione o profissional que receberá o desconto ou acréscimo.");
            }

            $lancamentoProfissional = LancamentoProfissional::find($object->lancamento_profissional_id);

            if(!$lancamentoProfissional || (int) $lancamentoProfissional->lancamento_id != (int) $lancamento->id){
                throw new Exception("O profissional selecionado não pertence a esta parcela.");
            }

            if($acrescimo > 0){
                self::salvarAjusteProfissional($lancamentoProfissional, 'A', $acrescimo);
            }

            if($desconto > 0){
                self::salvarAjusteProfissional($lancamentoProfissional, 'D', $desconto);
            }

            return;
        }

        $profissionaisRateio = [];

        foreach($lancamentosProfissionais as $lancamentoProfissional){
            if((float) $lancamentoProfissional->percentual > 0){
                $profissionaisRateio[] = $lancamentoProfissional;
            }
        }

        if(!$profissionaisRateio){
            throw new Exception("Os profissionais desta parcela não possuem percentuais válidos para distribuição.");
        }

        if($acrescimo > 0){
            self::ratearAjusteProfissionais($profissionaisRateio, 'A', $acrescimo);
        }

        if($desconto > 0){
            self::ratearAjusteProfissionais($profissionaisRateio, 'D', $desconto);
        }
    }

    private static function ratearAjusteProfissionais($profissionais, $tipo, $valorAjuste)
    {
        $rateio = self::calcularRateioAjusteProfissionais($profissionais, $valorAjuste);

        foreach($rateio as $item){
            if($item['valor'] > 0){
                self::salvarAjusteProfissional($item['profissional'], $tipo, $item['valor']);
            }
        }
    }

    private static function calcularRateioAjusteProfissionais($profissionais, $valorAjuste)
    {
        $totalPercentual = 0;

        foreach($profissionais as $profissional){
            $totalPercentual += (float) $profissional->percentual;
        }

        if($totalPercentual <= 0){
            throw new Exception("A soma dos percentuais dos profissionais deve ser maior que zero.");
        }

        $rateio = [];
        $valorRestante = round((float) $valorAjuste, 2);
        $ultimoIndice = count($profissionais) - 1;

        foreach($profissionais as $indice => $lancamentoProfissional){
            if($indice == $ultimoIndice){
                $valorProfissional = $valorRestante;
            }else{
                $proporcao = (float) $lancamentoProfissional->percentual / $totalPercentual;
                $valorProfissional = round($valorAjuste * $proporcao, 2);
                $valorRestante = round($valorRestante - $valorProfissional, 2);
            }

            $rateio[] = [
                'profissional' => $lancamentoProfissional,
                'valor' => $valorProfissional
            ];
        }

        return $rateio;
    }

    private static function montarMensagemProfissionaisNegativos($profissionaisNegativos)
    {
        $linhas = '';

        foreach($profissionaisNegativos as $profissionalNegativo){
            $nome = htmlspecialchars($profissionalNegativo['nome'], ENT_QUOTES, 'UTF-8');

            $linhas .= "
                <div style='margin-top: 10px; padding: 10px 12px; background: #fff3cd; border: 1px solid #ffe69c; border-radius: 4px;'>
                    <b>{$nome}</b><br>
                    Valor atual do profissional: R$ ".number_format($profissionalNegativo['valor_atual'], 2, ',', '.')."<br>
                    Desconto aplicado: R$ ".number_format($profissionalNegativo['desconto'], 2, ',', '.')."<br>
                    <span style='color: #b42318; font-weight: bold;'>
                        Valor final: - R$ ".number_format(abs($profissionalNegativo['valor_final']), 2, ',', '.')."
                    </span>
                </div>
            ";
        }

        return "
            <div style='text-align: left;'>
                <div style='font-size: 16px; color: #b42318; font-weight: bold;'>
                    ATENÇÃO
                </div>

                <div style='margin-top: 8px;'>
                    O desconto informado irá negativar o valor que seria recebido por um ou mais profissionais.
                </div>

                {$linhas}

                <div style='margin-top: 12px; font-weight: bold;'>
                    Deseja confirmar a quitação mesmo assim?
                </div>
            </div>
        ";
    }

    private static function verificarProfissionaisNegativos($lancamento, $object, $acrescimo, $desconto)
    {
        if($desconto <= 0){
            return [];
        }

        $lancamentosProfissionais = LancamentoProfissional::where('lancamento_id', '=', $lancamento->id)->load();

        if(!$lancamentosProfissionais){
            return [];
        }

        $tipoDistribuicao = $object->distribuicao_ajuste ?? null;
        $ajustesAplicados = [];

        if($tipoDistribuicao == 'U'){
            if(empty($object->lancamento_profissional_id)){
                throw new Exception("Selecione o profissional que receberá o desconto ou acréscimo.");
            }

            $lancamentoProfissional = LancamentoProfissional::find($object->lancamento_profissional_id);

            if(!$lancamentoProfissional || (int) $lancamentoProfissional->lancamento_id != (int) $lancamento->id){
                throw new Exception("O profissional selecionado não pertence a esta parcela.");
            }

            $ajustesAplicados[$lancamentoProfissional->id] = [
                'profissional' => $lancamentoProfissional,
                'acrescimo' => $acrescimo,
                'desconto' => $desconto
            ];
        }else if($tipoDistribuicao == 'T'){
            $profissionaisRateio = [];

            foreach($lancamentosProfissionais as $lancamentoProfissional){
                if((float) $lancamentoProfissional->percentual > 0){
                    $profissionaisRateio[] = $lancamentoProfissional;
                    $ajustesAplicados[$lancamentoProfissional->id] = [
                        'profissional' => $lancamentoProfissional,
                        'acrescimo' => 0,
                        'desconto' => 0
                    ];
                }
            }

            if(!$profissionaisRateio){
                throw new Exception("Os profissionais desta parcela não possuem percentuais válidos para distribuição.");
            }

            if($acrescimo > 0){
                $rateioAcrescimo = self::calcularRateioAjusteProfissionais($profissionaisRateio, $acrescimo);

                foreach($rateioAcrescimo as $item){
                    $ajustesAplicados[$item['profissional']->id]['acrescimo'] = $item['valor'];
                }
            }

            $rateioDesconto = self::calcularRateioAjusteProfissionais($profissionaisRateio, $desconto);

            foreach($rateioDesconto as $item){
                $ajustesAplicados[$item['profissional']->id]['desconto'] = $item['valor'];
            }
        }else{
            throw new Exception("Informe como o desconto ou acréscimo deve ser distribuído entre os profissionais.");
        }

        $profissionaisNegativos = [];

        foreach($ajustesAplicados as $ajusteAplicado){
            $lancamentoProfissional = $ajusteAplicado['profissional'];
            $valorAtual = round((float) $lancamentoProfissional->valor, 2);

            $ajustesExistentes = LancamentoProfissionalAjuste::where(
                'lancamento_profissional_id',
                '=',
                $lancamentoProfissional->id
            )->load();

            foreach($ajustesExistentes as $ajusteExistente){
                $valorAjusteExistente = round((float) $ajusteExistente->valor, 2);

                if($ajusteExistente->tipo == 'A'){
                    $valorAtual += $valorAjusteExistente;
                }else if($ajusteExistente->tipo == 'D'){
                    $valorAtual -= $valorAjusteExistente;
                }
            }

            $novoValor = round(
                $valorAtual +
                $ajusteAplicado['acrescimo'] -
                $ajusteAplicado['desconto'],
                2
            );

            if($novoValor < 0){
                $profissional = Pessoa::find($lancamentoProfissional->pessoa_id);

                $profissionaisNegativos[] = [
                    'nome' => $profissional ? $profissional->nome : 'Profissional não encontrado',
                    'valor_atual' => $valorAtual,
                    'acrescimo' => $ajusteAplicado['acrescimo'],
                    'desconto' => $ajusteAplicado['desconto'],
                    'valor_final' => $novoValor
                ];
            }
        }

        return $profissionaisNegativos;
    }

    private static function salvarAjusteProfissional($lancamentoProfissional, $tipo, $valor)
    {
        $profissional = Pessoa::find($lancamentoProfissional->pessoa_id);
        $nomeProfissional = $profissional ? strtoupper($profissional->nome) : 'PROFISSIONAL NÃO ENCONTRADO';

        $ajuste = new LancamentoProfissionalAjuste();
        $ajuste->lancamento_profissional_id = $lancamentoProfissional->id;
        $ajuste->tipo = $tipo;
        $ajuste->valor = round((float) $valor, 2);

        if($tipo == 'D'){
            $ajuste->descricao = "DESCONTO APLICADO A: {$nomeProfissional}";
        }else{
            $ajuste->descricao = "ACRÉSCIMO APLICADO A: {$nomeProfissional}";
        }

        $ajuste->store();
    }

   public static function onChangeDistribuicaoAjuste($param = null)
    {
        try
        {
            $tipoDistribuicao = $param['distribuicao_ajuste'] ?? 'T';

            if($tipoDistribuicao == 'U'){
                $lancamentoId = (int) ($param['id'] ?? 0);

                if(empty($lancamentoId)){
                    throw new Exception('Lançamento não informado.');
                }

                TTransaction::open('escritorio');

                $lancamento = Lancamento::find($lancamentoId);

                if(!$lancamento){
                    throw new Exception('Lançamento não encontrado.');
                }

                $lancamentosProfissionais = LancamentoProfissional::where('lancamento_id', '=', $lancamento->id)
                    ->orderBy('id')
                    ->load();

                $opcoesProfissionais = [];

                foreach($lancamentosProfissionais as $lancamentoProfissional){
                    $profissional = Pessoa::find($lancamentoProfissional->pessoa_id);

                    if(!$profissional){
                        continue;
                    }

                    $percentual = number_format((float) $lancamentoProfissional->percentual, 2, ',', '.');
                    $valorProfissional = number_format((float) $lancamentoProfissional->valor, 2, ',', '.');

                    $opcoesProfissionais[$lancamentoProfissional->id] = "{$profissional->nome} - {$percentual}% - R$ {$valorProfissional}";
                }

                if(!$opcoesProfissionais){
                    throw new Exception('Nenhum profissional foi vinculado a esta parcela.');
                }

                TCombo::reload(
                    self::$formName,
                    'lancamento_profissional_id',
                    $opcoesProfissionais,
                    true
                );

                BootstrapFormBuilder::showField(
                    self::$formName,
                    'lancamento_profissional_id'
                );

                TCombo::enableField(
                    self::$formName,
                    'lancamento_profissional_id'
                );

                TTransaction::close();
            }else{
                $data = new stdClass();
                $data->lancamento_profissional_id = null;

                TForm::sendData(
                    self::$formName,
                    $data,
                    false,
                    false
                );

                TCombo::disableField(
                    self::$formName,
                    'lancamento_profissional_id'
                );

                BootstrapFormBuilder::hideField(
                    self::$formName,
                    'lancamento_profissional_id'
                );
            }
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    private static function restaurarFormularioConfirmacaoNegativo($lancamento, $object)
    {
        $opcoesProfissionais = [];

        $lancamentosProfissionais = LancamentoProfissional::where('lancamento_id', '=', $lancamento->id)
            ->orderBy('id')
            ->load();

        foreach($lancamentosProfissionais as $lancamentoProfissional){
            $profissional = Pessoa::find($lancamentoProfissional->pessoa_id);

            if(!$profissional){
                continue;
            }

            $percentual = number_format((float) $lancamentoProfissional->percentual, 2, ',', '.');
            $valorProfissional = number_format((float) $lancamentoProfissional->valor, 2, ',', '.');

            $opcoesProfissionais[$lancamentoProfissional->id] = "{$profissional->nome} - {$percentual}% - R$ {$valorProfissional}";
        }

        TCombo::reload(
            self::$formName,
            'lancamento_profissional_id',
            $opcoesProfissionais,
            true
        );

        if(($object->distribuicao_ajuste ?? 'T') == 'U'){
            BootstrapFormBuilder::showField(
                self::$formName,
                'lancamento_profissional_id'
            );

            TCombo::enableField(
                self::$formName,
                'lancamento_profissional_id'
            );
        }else{
            TCombo::disableField(
                self::$formName,
                'lancamento_profissional_id'
            );

            BootstrapFormBuilder::hideField(
                self::$formName,
                'lancamento_profissional_id'
            );
        }

        if(($object->tipo_pagamento_id ?? null) == TipoPagamento::CHEQUE){
            BootstrapFormBuilder::showField(self::$formName, 'cheque_numero');
            BootstrapFormBuilder::showField(self::$formName, 'cheque_banco_id');
        }else{
            BootstrapFormBuilder::hideField(self::$formName, 'cheque_numero');
            BootstrapFormBuilder::hideField(self::$formName, 'cheque_banco_id');
        }

        $object->id = $lancamento->id;

        TForm::sendData(
            self::$formName,
            $object,
            false,
            false
        );
    }

}

