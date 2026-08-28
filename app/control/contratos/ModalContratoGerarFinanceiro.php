<?php

class ModalContratoGerarFinanceiro extends TWindow
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_ModalContratoGerarFinanceiro';

    use BuilderMasterDetailFieldListTrait;

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();
        parent::setSize(850, null);
        parent::setTitle("Gerar financeiro de contrato");
        parent::setProperty('class', 'window_modal');

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Gerar financeiro de contrato");

        $criteria_contrato_id = new TCriteria();
        $criteria_categoria_conta_id = new TCriteria();
        $criteria_tipo_pagamento_id = new TCriteria();
        $criteria_coluna = new TCriteria();

        $filterVar = [TipoConta::AMBOS, TipoConta::RECEBER];
        $filterVar = (is_array($filterVar) && $filterVar) ? "'".implode("','", $filterVar)."'" : $filterVar;
        $criteria_categoria_conta_id->add(new TFilter('tipo_conta_id', 'in', "(SELECT id FROM categoria_conta WHERE tipo_conta_id in ($filterVar))")); 
        $filterVar = [Grupo::PARCEIRO, Grupo::PROFISSIONAL];
        $filterVar = (is_array($filterVar) && $filterVar) ? "'".implode("','", $filterVar)."'" : $filterVar;
        $criteria_coluna->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id in ($filterVar))")); 

        $contrato_id = new TDBCombo('contrato_id', 'escritorio', 'Contrato', 'id', '{numero}','numero asc' , $criteria_contrato_id );
        $escritorio_id = new THidden('escritorio_id');
        $contrato_parcela_id = new THidden('contrato_parcela_id');
        $profissional_id = new THidden('profissional_id');
        $categoria_conta_id = new TDBCombo('categoria_conta_id', 'escritorio', 'CategoriaConta', 'id', '{nome}','nome asc' , $criteria_categoria_conta_id );
        $pessoa_id = new THidden('pessoa_id');
        $descricao = new TEntry('descricao');
        $valor = new TNumeric('valor', '2', ',', '.' );
        $dt_vencimento = new TDate('dt_vencimento');
        $tipo_pagamento_id = new TDBCombo('tipo_pagamento_id', 'escritorio', 'TipoPagamento', 'id', '{nome}','nome asc' , $criteria_tipo_pagamento_id );
        $numero_parcelas = new TSpinner('numero_parcelas');
        $coluna = new TDBCombo('coluna[]', 'escritorio', 'Pessoa', 'id', '{nome}','nome asc' , $criteria_coluna );
        $valor_profissional = new TNumeric('valor_profissional[]', '2', ',', '.' );
        $valor_repasse = new TNumeric('valor_repasse[]', '2', ',', '.' );
        $this->fieldList_6a39175d40e6e = new TFieldList();

        $this->fieldList_6a39175d40e6e->addField(new TLabel("Profissional", null, '14px', null), $coluna, ['width' => '35%']);
        $this->fieldList_6a39175d40e6e->addField(new TLabel("Valor", null, '14px', null), $valor_profissional, ['width' => '40%']);
        $this->fieldList_6a39175d40e6e->addField(new TLabel("Repasse", null, '14px', null), $valor_repasse, ['width' => '100%']);

        $this->fieldList_6a39175d40e6e->width = '100%';
        $this->fieldList_6a39175d40e6e->name = 'fieldList_6a39175d40e6e';

        $this->criteria_fieldList_6a39175d40e6e = new TCriteria();
        $this->default_item_fieldList_6a39175d40e6e = new stdClass();

        $this->form->addField($coluna);
        $this->form->addField($valor_profissional);
        $this->form->addField($valor_repasse);

        $this->fieldList_6a39175d40e6e->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $categoria_conta_id->addValidation("Categoria de conta", new TRequiredValidator()); 
        $descricao->addValidation("Descrição", new TRequiredValidator()); 
        $valor->addValidation("Valor", new TRequiredValidator()); 
        $dt_vencimento->addValidation("Data de vencimento", new TRequiredValidator()); 
        $tipo_pagamento_id->addValidation("Forma de pagamento", new TRequiredValidator()); 

        $contrato_id->setEditable(false);
        $dt_vencimento->setMask('dd/mm/yyyy');
        $dt_vencimento->setDatabaseMask('yyyy-mm-dd');
        $numero_parcelas->setRange(1, 2000, 1);
        $coluna->enableSearch();
        $contrato_id->enableSearch();
        $tipo_pagamento_id->enableSearch();
        $categoria_conta_id->enableSearch();

        $categoria_conta_id->setValue('20');
        $valor->setValue($param["valor"] ?? null);
        $descricao->setValue($param["desc"] ?? null);
        $dt_vencimento->setValue($param["dt"] ?? null);
        $pessoa_id->setValue($param['pessoa_id'] ?? null);
        $contrato_id->setValue($param["contrato_id"] ?? null);
        $escritorio_id->setValue($param['escritorio_id'] ?? null);
        $numero_parcelas->setValue($param['quant_parcela'] ?? null);
        $profissional_id->setValue($param['profissional_id'] ?? null);
        $contrato_parcela_id->setValue($param['contrato_parcela_id'] ?? null);

        $coluna->setSize(500);
        $valor->setSize('100%');
        $pessoa_id->setSize(200);
        $descricao->setSize('100%');
        $escritorio_id->setSize(200);
        $contrato_id->setSize('100%');
        $profissional_id->setSize(200);
        $dt_vencimento->setSize('100%');
        $valor_repasse->setSize('100%');
        $numero_parcelas->setSize('100%');
        $contrato_parcela_id->setSize(200);
        $tipo_pagamento_id->setSize('100%');
        $categoria_conta_id->setSize('100%');
        $valor_profissional->setSize('100%');

       $valorInicial = $param["valor"] ?? null;

        if (!empty($param['contrato_parcela_id']))
        {
            try
            {
                TTransaction::open('escritorio');

                $contratoPagamentoParcelaInicial = ContratoPagamentoParcela::find((int) $param['contrato_parcela_id']);

                if ($contratoPagamentoParcelaInicial)
                {
                    $statusInicial = empty($contratoPagamentoParcelaInicial->status_contrato_pagamento_id)
                        ? 1
                        : (int) $contratoPagamentoParcelaInicial->status_contrato_pagamento_id;

                    $saldoInicial = self::valorBrParaFloat($contratoPagamentoParcelaInicial->saldo ?? 0);
                    $valorOriginalInicial = self::valorBrParaFloat($contratoPagamentoParcelaInicial->valor ?? 0);

                    /*
                    * Status:
                    * null/1 = Em Aberto -> usa valor original
                    * 2      = Gerado com Saldo -> usa saldo
                    * 3      = Gerado -> mantém valor zerado, mas normalmente nem deveria abrir
                    */
                    if ($statusInicial === 2 && $saldoInicial > 0) {
                        $valorInicial = $saldoInicial;
                    } elseif ($statusInicial === 1) {
                        $valorInicial = $valorOriginalInicial;
                    }
                }

                TTransaction::close();
            }
            catch (Exception $e)
            {
                TTransaction::rollback();
            }
        }

        $valor->setValue(self::valorParaTelaBr($valorInicial));

        $profissionaisParam = [];
        $valoresProfissionaisParam = [];
        $repassesProfissionaisParam = [];

        $veioDoSubmit = false;

        if (isset($param['coluna']) || isset($param['valor_profissional']) || isset($param['valor_repasse']))
        {
            $veioDoSubmit = true;

            $profissionaisParam = $param['coluna'] ?? [];
            $valoresProfissionaisParam = $param['valor_profissional'] ?? [];
            $repassesProfissionaisParam = $param['valor_repasse'] ?? [];
        }
        else
        {
            $profissionaisParam = json_decode($param['profissionais_json'] ?? '[]', true);
            $valoresProfissionaisParam = json_decode($param['valores_profissionais_json'] ?? '[]', true);
            $repassesProfissionaisParam = json_decode($param['repasses_profissionais_json'] ?? '[]', true);
        }

        if (!is_array($profissionaisParam)) {
            $profissionaisParam = [$profissionaisParam];
        }

        if (!is_array($valoresProfissionaisParam)) {
            $valoresProfissionaisParam = [$valoresProfissionaisParam];
        }

        if (!is_array($repassesProfissionaisParam)) {
            $repassesProfissionaisParam = [$repassesProfissionaisParam];
        }

        $profissionaisParam = array_values($profissionaisParam);
        $valoresProfissionaisParam = array_values($valoresProfissionaisParam);
        $repassesProfissionaisParam = array_values($repassesProfissionaisParam);

        $formatarValorTela = function($valor) {
            if ($valor === null || $valor === '') {
                return $valor;
            }

            if (is_numeric($valor)) {
                return number_format((float) $valor, 2, ',', '.');
            }

            return $valor;
        };

        $this->fieldList_6a39175d40e6e->addHeader();

        $qtdLinhas = max(
            count($profissionaisParam),
            count($valoresProfissionaisParam),
            count($repassesProfissionaisParam)
        );

        $temLinha = false;

        for ($index = 0; $index < $qtdLinhas; $index++)
        {
            $profissionalId = $profissionaisParam[$index] ?? null;
            $valorProfissional = $valoresProfissionaisParam[$index] ?? null;
            $repasseProfissional = $repassesProfissionaisParam[$index] ?? null;

            if (!$veioDoSubmit && empty($profissionalId) && empty($valorProfissional) && empty($repasseProfissional)) {
                continue;
            }

            $temLinha = true;

            $item = new stdClass;
            $item->coluna = $profissionalId;
            $item->valor_profissional = $formatarValorTela($valorProfissional);
            $item->valor_repasse = $formatarValorTela($repasseProfissional);

            $this->fieldList_6a39175d40e6e->addDetail($item);
        }

        if (!$temLinha)
        {
            $this->fieldList_6a39175d40e6e->addDetail($this->default_item_fieldList_6a39175d40e6e);
        }

        $this->fieldList_6a39175d40e6e->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        $row1 = $this->form->addFields([new TLabel("Contrato:", null, '14px', null, '100%'),$contrato_id,$escritorio_id,$contrato_parcela_id],[$profissional_id,new TLabel("Categoria de conta:", '#FF0000', '14px', null, '100%'),$categoria_conta_id],[$pessoa_id]);
        $row1->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Descrição:", '#FF0000', '14px', null, '100%'),$descricao]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addContent([new TFormSeparator("Financeiro", '#333333', '18', '#EEEEEE')]);
        $row4 = $this->form->addFields([new TLabel("Valor:", '#FF0000', '14px', null, '100%'),$valor],[new TLabel("Data de vencimento:", '#FF0000', '14px', null, '100%'),$dt_vencimento],[new TLabel("Forma de pagamento", '#FF0000', '14px', null, '100%'),$tipo_pagamento_id],[new TLabel("Parcelas:", '#FF0000', '14px', null, '100%'),$numero_parcelas]);
        $row4->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row5 = $this->form->addContent([new TFormSeparator("Profissional", '#333333', '18', '#eee')]);
        $row6 = $this->form->addFields([$this->fieldList_6a39175d40e6e]);
        $row6->layout = [' col-sm-12'];

        TScript::create("
            $(document).off('.calcProfissionalFinanceiro');

            function calcProfissionalParaNumero(valor) {
                valor = valor || '0';
                valor = valor.toString().trim();
                valor = valor.replace(/[^0-9,.-]/g, '');

                if (valor === '' || valor === '-' || valor === ',' || valor === '.') {
                    return 0;
                }

                var ultimoPonto = valor.lastIndexOf('.');
                var ultimaVirgula = valor.lastIndexOf(',');

                if (ultimoPonto >= 0 && ultimaVirgula >= 0) {
                    if (ultimaVirgula > ultimoPonto) {
                        valor = valor.split('.').join('');
                        valor = valor.replace(',', '.');
                    } else {
                        valor = valor.split(',').join('');
                    }
                } else if (ultimaVirgula >= 0) {
                    valor = valor.split('.').join('');
                    valor = valor.replace(',', '.');
                }

                var numero = parseFloat(valor);

                if (isNaN(numero)) {
                    numero = 0;
                }

                return numero;
            }

            function calcProfissionalParaTela(numero) {
                numero = parseFloat(numero || 0);

                return numero.toLocaleString('pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function calcProfissionalTotal() {
                return calcProfissionalParaNumero($(\"[name='valor']\").val());
            }

            function calcProfissionalLinhas() {
                var linhas = [];

                $(\"[name='coluna[]']\").each(function() {
                    var profissional = String($(this).val() || '').trim();

                    if (profissional !== '') {
                        linhas.push($(this).closest('tr'));
                    }
                });

                return linhas;
            }

            function calcProfissionalDistribuirIgual() {
                var total = calcProfissionalTotal();
                var linhas = calcProfissionalLinhas();

                if (total <= 0 || linhas.length <= 0) {
                    return;
                }

                var qtd = linhas.length;
                var valorBase = Math.floor((total / qtd) * 100) / 100;
                var repasseBase = Math.floor((100 / qtd) * 100) / 100;

                var valorAcumulado = 0;
                var repasseAcumulado = 0;

                linhas.forEach(function(row, index) {
                    var valorLinha;
                    var repasseLinha;

                    if (index == qtd - 1) {
                        valorLinha = total - valorAcumulado;
                        repasseLinha = 100 - repasseAcumulado;
                    } else {
                        valorLinha = valorBase;
                        repasseLinha = repasseBase;

                        valorAcumulado += valorLinha;
                        repasseAcumulado += repasseLinha;
                    }

                    row.find(\"[name='valor_profissional[]']\").val(calcProfissionalParaTela(valorLinha));
                    row.find(\"[name='valor_repasse[]']\").val(calcProfissionalParaTela(repasseLinha));
                });
            }

            function calcProfissionalTemValorPreenchido() {
                var tem = false;

                calcProfissionalLinhas().forEach(function(row) {
                    var valor = calcProfissionalParaNumero(row.find(\"[name='valor_profissional[]']\").val());
                    var repasse = calcProfissionalParaNumero(row.find(\"[name='valor_repasse[]']\").val());

                    if (valor > 0 || repasse > 0) {
                        tem = true;
                    }
                });

                return tem;
            }

            $(document).on('input.calcProfissionalFinanceiro keyup.calcProfissionalFinanceiro', \"[name='valor_profissional[]']\", function() {
                var total = calcProfissionalTotal();

                if (total <= 0) {
                    return;
                }

                var row = $(this).closest('tr');
                var valorProfissional = calcProfissionalParaNumero($(this).val());
                var percentual = 0;

                if (valorProfissional > 0) {
                    percentual = (valorProfissional / total) * 100;
                }

                row.find(\"[name='valor_repasse[]']\").val(calcProfissionalParaTela(percentual));
            });

            $(document).on('input.calcProfissionalFinanceiro keyup.calcProfissionalFinanceiro', \"[name='valor_repasse[]']\", function() {
                var total = calcProfissionalTotal();

                if (total <= 0) {
                    return;
                }

                var row = $(this).closest('tr');
                var percentual = calcProfissionalParaNumero($(this).val());
                var valorProfissional = 0;

                if (percentual > 0) {
                    valorProfissional = (percentual / 100) * total;
                }

                row.find(\"[name='valor_profissional[]']\").val(calcProfissionalParaTela(valorProfissional));
            });

            $(document).on('change.calcProfissionalFinanceiro', \"[name='coluna[]']\", function() {
                setTimeout(function() {
                    calcProfissionalDistribuirIgual();
                }, 300);
            });

            $(document).on('click.calcProfissionalFinanceiro', '.tfieldlist button, .tfieldlist a', function() {
                setTimeout(function() {
                    calcProfissionalDistribuirIgual();
                }, 500);
            });

            $(document).on('input.calcProfissionalFinanceiro keyup.calcProfissionalFinanceiro change.calcProfissionalFinanceiro blur.calcProfissionalFinanceiro', \"[name='valor']\", function() {
                setTimeout(function() {
                    calcProfissionalDistribuirIgual();
                }, 300);
            });

            setTimeout(function() {
                if (!calcProfissionalTemValorPreenchido()) {
                    calcProfissionalDistribuirIgual();
                }
            }, 800);
        ");

        // create the form actions
        $btn_ongerar = $this->form->addAction("Gerar", new TAction([$this, 'onGerar']), 'fas:cog #ffffff');
        $this->btn_ongerar = $btn_ongerar;
        $btn_ongerar->addStyleClass('btn-success'); 

        parent::add($this->form);

    }

    public function onGerar($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');

            $messageAction = null;

            $this->form->validate(); // validate form data
            $data = $this->form->getData(); // get form data as array

            $profissionais = $data->coluna ?? [];
            $valoresProfissionais = $data->valor_profissional ?? [];
            $repassesProfissionais = $data->valor_repasse ?? [];

            if (!is_array($profissionais)) {
                $profissionais = [$profissionais];
            }

            if (!is_array($valoresProfissionais)) {
                $valoresProfissionais = [$valoresProfissionais];
            }

            if (!is_array($repassesProfissionais)) {
                $repassesProfissionais = [$repassesProfissionais];
            }

            $totalValorProfissionais = 0;
            $totalRepasseProfissionais = 0;
            $profissionaisSelecionados = [];
            $qtdProfissionaisSelecionados = 0;

            foreach ($profissionais as $index => $profissionalId)
            {
                if (empty($profissionalId)) {
                    continue;
                }

                if (isset($profissionaisSelecionados[$profissionalId])) {
                    throw new Exception('O mesmo profissional foi informado mais de uma vez.');
                }

                $profissionaisSelecionados[$profissionalId] = true;
                $qtdProfissionaisSelecionados++;

                $valorProfissional = self::valorBrParaFloat($valoresProfissionais[$index] ?? 0);
                $repasseProfissional = self::valorBrParaFloat($repassesProfissionais[$index] ?? 0);

                if ($valorProfissional <= 0) {
                    throw new Exception('Informe o valor do profissional.');
                }

                if ($repasseProfissional <= 0) {
                    throw new Exception('Informe o repasse do profissional.');
                }

                if ($repasseProfissional > 100) {
                    throw new Exception('O repasse de um profissional não pode passar de 100%.');
                }

                $totalValorProfissionais += $valorProfissional;
                $totalRepasseProfissionais += $repasseProfissional;
            }

            if ($qtdProfissionaisSelecionados <= 0) {
                throw new Exception('Informe pelo menos um profissional.');
            }

            $totalFinanceiro = self::valorBrParaFloat($data->valor);

            $totalValorProfissionaisCentavos = (int) round($totalValorProfissionais * 100);
            $totalFinanceiroCentavos = (int) round($totalFinanceiro * 100);

            if ($totalValorProfissionaisCentavos !== $totalFinanceiroCentavos) {
                throw new Exception('A soma dos valores dos profissionais deve ser exatamente igual ao valor financeiro.');
            }

            if (abs($totalRepasseProfissionais - 100) > 0.01) {
                throw new Exception('A soma dos repasses dos profissionais deve ser exatamente 100%.');
            }

            $contratoPagamentoParcela = null;

            if (!empty($data->contrato_parcela_id))
            {
                $contratoPagamentoParcela = ContratoPagamentoParcela::find((int) $data->contrato_parcela_id);

                if (!$contratoPagamentoParcela) {
                    throw new Exception('Parcela do contrato não encontrada.');
                }

                $statusAtual = empty($contratoPagamentoParcela->status_contrato_pagamento_id)
                ? 1
                : (int) $contratoPagamentoParcela->status_contrato_pagamento_id;

                if ($statusAtual === 3) {
                    throw new Exception('Esta parcela do contrato já foi gerada integralmente.');
                }

                $valorOriginalParcela = self::valorBrParaFloat($contratoPagamentoParcela->valor ?? 0);
                $saldoAtual = self::valorBrParaFloat($contratoPagamentoParcela->saldo ?? 0);

                if ($statusAtual === 2 && $saldoAtual <= 0) {
                    throw new Exception('Esta parcela está marcada como gerada com saldo, mas não possui saldo disponível.');
                }

                /*
                * Se já existe saldo, gera em cima do saldo.
                * Se não existe saldo, gera em cima do valor original da parcela.
                */
                $valorDisponivelParcela = $saldoAtual > 0 ? $saldoAtual : $valorOriginalParcela;

                if ($valorDisponivelParcela <= 0) {
                    throw new Exception('A parcela do contrato não possui valor disponível para gerar financeiro.');
                }

                $valorDisponivelCentavos = (int) round($valorDisponivelParcela * 100);
                $totalFinanceiroCentavos = (int) round($totalFinanceiro * 100);

                if ($totalFinanceiroCentavos > $valorDisponivelCentavos) {
                    throw new Exception(
                        'O valor financeiro não pode ser maior que o valor disponível da parcela do contrato. ' .
                        'Disponível: R$ ' . number_format($valorDisponivelParcela, 2, ',', '.')
                    );
                }

                $novoSaldoCentavos = $valorDisponivelCentavos - $totalFinanceiroCentavos;
                $novoSaldo = $novoSaldoCentavos / 100;

                /*
                * Status:
                * 1 = Em Aberto
                * 2 = Gerado com Saldo
                * 3 = Gerado
                */
                if ($novoSaldoCentavos === 0) {
                    $contratoPagamentoParcela->saldo = null;
                    $contratoPagamentoParcela->status_contrato_pagamento_id = 3;
                } else {
                    $contratoPagamentoParcela->saldo = $novoSaldo;
                    $contratoPagamentoParcela->status_contrato_pagamento_id = 2;
                }
            }

            $buscaContaContrato = Conta::where('contrato_id','=',$data->contrato_id)->first();
            if(!$buscaContaContrato){
                $conta = new Conta(); // create an empty object
            }else{
                $conta = Conta::find($buscaContaContrato->id); // create an empty object
            }

            $conta->fromArray( (array) $data); // load the object with data
            $conta->data_emissao = date('Y-m-d');
            $conta->tipo_conta_id = TipoConta::RECEBER;
            $conta->tipo_documento_financeiro_id = TipoDocumentoFinanceiro::CONTRATO;
            $conta->total_conta = self::valorBrParaFloat($conta->total_conta ?? 0) + $totalFinanceiro;
            $conta->store();

            // garante vínculo do contrato com a conta
            $conta->contrato_id = $data->contrato_id;
            $conta->store();

            // recria os profissionais vinculados à conta
            ContaProfissional::where('conta_id', '=', $conta->id)->delete();

            foreach ($profissionais as $index => $profissionalId)
            {
                if (empty($profissionalId)) {
                    continue;
                }

                $contaProfissional = new ContaProfissional();
                $contaProfissional->conta_id = $conta->id;
                $contaProfissional->pessoa_id = $profissionalId;
                $contaProfissional->valor = self::valorBrParaFloat($valoresProfissionais[$index] ?? 0);
                $contaProfissional->percentual = self::valorBrParaFloat($repassesProfissionais[$index] ?? 0);
                $contaProfissional->store();
            }

            $parcelas = (int) $data->numero_parcelas;

            if ($parcelas <= 0) {
                throw new Exception('Informe a quantidade de parcelas.');
            }

            $valor_parcela = $totalFinanceiro / $parcelas;

            /*
            * Busca a última parcela já criada para essa conta.
            * Assim, se já existe parcela 1, a próxima começa na 2.
            */
            $ultimoLancamento = Lancamento::where('conta_id', '=', $conta->id)
                ->orderBy('parcela', 'desc')
                ->first();

            $ultimaParcela = 0;

            if ($ultimoLancamento && !empty($ultimoLancamento->parcela)) {
                $ultimaParcela = (int) $ultimoLancamento->parcela;
            }

           for($i = 1; $i <= $parcelas; $i++){
                $lancamento = new Lancamento();
                $lancamento->fromArray((array) $data);
                $lancamento->valor = round((float) $valor_parcela, 2);
                $lancamento->valor_total = $lancamento->valor;
                $lancamento->conta_id = $conta->id;

                // continua a numeracao da ultima parcela da conta
                $lancamento->parcela = $ultimaParcela + $i;

                if($i == 1){
                    $lancamento->dt_vencimento = $data->dt_vencimento;
                }else{
                    $aux = $i - 1;
                    $lancamento->dt_vencimento = date('Y-m-d', strtotime("+{$aux} months", strtotime($data->dt_vencimento)));
                }

                $lancamento->store();
            }
            if ($contratoPagamentoParcela) {
                $contratoPagamentoParcela->store();
            }

            TScript::create("$(\"[page_name='ModalContratoGerarFinanceiro']\").remove()");
            TApplication::loadPage('ContratoFormView', 'onShow', ['key' => $data->contrato_id, 'id' => $data->contrato_id, 'current_tab_abas' => 3]);

            TTransaction::close();
        }
        catch (Exception $e)
        {

        $data = $this->form->getData();

            $profissionais = $data->coluna ?? [];
            $valoresProfissionais = $data->valor_profissional ?? [];
            $repassesProfissionais = $data->valor_repasse ?? [];

            if (!is_array($profissionais)) {
                $profissionais = [$profissionais];
            }

            if (!is_array($valoresProfissionais)) {
                $valoresProfissionais = [$valoresProfissionais];
            }

            if (!is_array($repassesProfissionais)) {
                $repassesProfissionais = [$repassesProfissionais];
            }

            $profissionais = array_values($profissionais);
            $valoresProfissionais = array_values($valoresProfissionais);
            $repassesProfissionais = array_values($repassesProfissionais);

            // Normaliza o valor principal para o formato que o TNumeric espera na tela
            if (isset($data->valor)) {
                $data->valor = self::valorParaTelaBr($data->valor);
            }

            // Normaliza os valores da fieldlist para não voltar 33.33 / 1000.00
            foreach ($valoresProfissionais as $index => $valorProfissional) {
                $valoresProfissionais[$index] = self::valorParaTelaBr($valorProfissional);
            }

            foreach ($repassesProfissionais as $index => $repasseProfissional) {
                $repassesProfissionais[$index] = self::valorParaTelaBr($repasseProfissional);
            }

            $data->coluna = $profissionais;
            $data->valor_profissional = $valoresProfissionais;
            $data->valor_repasse = $repassesProfissionais;

            $qtdLinhas = max(
                count($profissionais),
                count($valoresProfissionais),
                count($repassesProfissionais),
                1
            );

            TFieldList::clearRows('fieldList_6a39175d40e6e');

            if ($qtdLinhas > 1) {
                TFieldList::addRows('fieldList_6a39175d40e6e', $qtdLinhas - 1);
            }

            $this->form->setData($data);

            TForm::sendData(self::$formName, $data, false, false, 50 * $qtdLinhas);

            TTransaction::rollback();

            new TMessage('error', $e->getMessage());

    }

    }/*

    }

    public function onShow($param = null)
    {               
        $this->fieldList_6a39175d40e6e->addHeader();
        $this->fieldList_6a39175d40e6e->addDetail($this->default_item_fieldList_6a39175d40e6e);

        $this->fieldList_6a39175d40e6e->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

     */
     public function onShow($param = null){
        TTransaction::open('escritorio');

        $buscaContaContrato = Conta::where('contrato_id','=',$param['contrato_id'])->first();
        if($buscaContaContrato){
            $conta = Conta::find($buscaContaContrato->id);

            $data = new stdClass();

            if($conta){
                $data->descricao = $conta->descricao;
                $data->categoria_conta_id = $conta->categoria_conta_id;
            }
            TForm::sendData(self::$formName, $data);
        }

        TTransaction::close();

    } 

   private static function valorBrParaFloat($valor)
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $valor = trim((string) $valor);
        $valor = str_replace('R$', '', $valor);
        $valor = str_replace(' ', '', $valor);
        $valor = preg_replace('/[^0-9,.\-]/', '', $valor);

        if ($valor === '' || $valor === '-' || $valor === ',' || $valor === '.') {
            return 0;
        }

        $temVirgula = strpos($valor, ',') !== false;
        $temPonto = strpos($valor, '.') !== false;

        if ($temVirgula) {
            // Formato BR: 1.000,50
            $valor = str_replace('.', '', $valor);
            $valor = str_replace(',', '.', $valor);

            return (float) $valor;
        }

        if ($temPonto) {
            // Pode vir do TNumeric/banco como 1000.00 ou 33.33
            // Mas também pode vir como 1.000 representando milhar.
            if (substr_count($valor, '.') > 1) {
                $valor = str_replace('.', '', $valor);
                return (float) $valor;
            }

            $partes = explode('.', $valor);
            $decimais = $partes[1] ?? '';

            // Se for tipo 1.000, considera ponto como milhar.
            if (strlen($decimais) === 3 && strlen($partes[0]) <= 3) {
                $valor = str_replace('.', '', $valor);
                return (float) $valor;
            }

            // Se for 33.33 ou 1000.00, considera ponto como decimal.
            return (float) $valor;
        }

        return (float) $valor;
    }

    private static function valorBrParaCentavos($valor)
    {
        return (int) round(self::valorBrParaFloat($valor) * 100);
    }

    private static function valorParaTelaBr($valor)
    {
        if ($valor === null || $valor === '') {
            return $valor;
        }

        return number_format(self::valorBrParaFloat($valor), 2, ',', '.');
    }

}

