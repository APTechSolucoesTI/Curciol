<?php

/**
 * Janela "Gerar financeiro" de um pagamento de requisicao (levantamento judicial).
 *
 * Mostra a origem do pagamento, os dados do levantamento e uma previa ao vivo do
 * calculo (LevantamentoFinanceiroService::simular, que nao grava). O botao
 * "Gerar financeiro" chama LevantamentoFinanceiroService::gerar, que recalcula tudo no servidor.
 *
 * Parametros: etapa3_id (obrigatorio), origem (janela | aba | detalhe) e url_recarga
 * (tela de origem a recarregar depois de gerar; so e aceita se for uma das telas conhecidas).
 */
class LevantamentoFinanceiroForm extends TWindow
{
    const COR_OBRIGATORIO = '#ff0000';

    protected $form;
    private static $database = 'escritorio';
    private static $formName = 'form_LevantamentoFinanceiro';

    public function __construct($param)
    {
        parent::__construct();
        parent::setSize(0.85, null);
        parent::setTitle('Gerar financeiro do levantamento');
        parent::setProperty('class', 'window_modal');
        parent::setDialogClass('curciol-janela-rolavel');

        $etapa3Id = (int) ($param['etapa3_id'] ?? 0);

        try {
            $ctx = LevantamentoContextoService::carregar($etapa3Id);

            // ja gerado: mostra a ficha em vez do formulario
            if ($ctx->ja_gerado) {
                LevantamentoFinanceiroView::montar($this, $param);
                return;
            }

            $this->montarFormulario($ctx, $param);
        } catch (Throwable $e) {
            parent::add(self::caixaAviso(htmlspecialchars(self::mensagem($e), ENT_QUOTES, 'UTF-8'), 'erro'));
        }
        self::estilo($this);
        self::trazerParaFrente($this);
    }

    public function onShow($param = null)
    {
    }

    // ---------------------------------------------------------------
    // Montagem
    // ---------------------------------------------------------------

    private function montarFormulario(object $ctx, array $param): void
    {
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('');

        $contas      = self::opcoes('SELECT id, nome FROM conta_caixa WHERE COALESCE(ativo, \'S\') <> \'N\' ORDER BY nome');
        $pagamentos  = self::opcoes('SELECT id, nome FROM tipo_pagamento WHERE COALESCE(ativo, \'S\') <> \'N\' ORDER BY nome');
        $categorias  = self::opcoes('SELECT id, nome FROM categoria_conta ORDER BY nome');
        $documentos  = self::opcoes('SELECT id, nome FROM tipo_documento_financeiro ORDER BY nome');
        $beneficiarios = self::opcoes('SELECT id, nome FROM beneficiario_credito WHERE ativo = \'S\' ORDER BY id');
        $parcerias   = self::opcoes('SELECT id, nome FROM parceria_levantamento WHERE ativo = \'S\' ORDER BY nome');

        $contratos = [];
        foreach ($ctx->contratos as $ct) { $contratos[$ct['id']] = $ct['numero'] ?: ('Contrato ' . $ct['id']); }

        // --- origem
        $this->form->addContent([self::titulo('Origem do pagamento')]);
        $row = $this->form->addFields(
            [self::rotulo('Cliente'), new TTextDisplay(htmlspecialchars($ctx->cliente_nome . ($ctx->cliente_documento ? ' - ' . self::documento($ctx->cliente_documento) : '')), '', '12px', '')],
            [self::rotulo('Entidade devedora'), new TTextDisplay(htmlspecialchars((string) $ctx->devedor_nome), '', '12px', '')],
            [self::rotulo('Requisição'), new TTextDisplay(htmlspecialchars('#' . $ctx->requisicao_id . ($ctx->tipo_requisicao ? ' - ' . $ctx->tipo_requisicao : '')), '', '12px', '')]
        );
        $row->layout = ['col-sm-5', 'col-sm-4', 'col-sm-3'];
        $campos = [[self::rotulo('Processo do pagamento'), new TTextDisplay(htmlspecialchars((string) $ctx->processo_numero), '', '12px', '')]];
        if (self::raizDiferente($ctx)) {
            $campos[] = [self::rotulo('Processo principal'), new TTextDisplay(htmlspecialchars((string) $ctx->processo_raiz_numero), '', '12px', '')];
        }
        $campos[] = [self::rotulo('Pagamento'), new TTextDisplay($ctx->numero_pagamento . 'º pagamento', '', '12px', '')];
        $campos[] = [self::rotulo('Data do depósito'), new TTextDisplay(self::dataBr($ctx->data_deposito), '', '12px', '')];
        $row = $this->form->addFields(...$campos);
        $row->layout = (count($campos) === 4) ? ['col-sm-3', 'col-sm-3', 'col-sm-3', 'col-sm-3'] : ['col-sm-5', 'col-sm-4', 'col-sm-3'];
        $row = $this->form->addFields(
            [self::rotulo('Bruto depositado'), new TTextDisplay(self::moeda($ctx->bruto), '', '12px', '')],
            [self::rotulo('Valor do MLE'), new TTextDisplay(self::moeda($ctx->mle), '', '12px', '')],
            [self::rotulo('Descontos (bruto - MLE)'), new TTextDisplay(self::moeda($ctx->descontos), '', '12px', '')],
            [self::rotulo('Saldo remanescente'), new TTextDisplay($ctx->saldo_bruto === null ? '-' : self::moeda($ctx->saldo_bruto), '', '12px', '')]
        );
        $row->layout = ['col-sm-3', 'col-sm-3', 'col-sm-3', 'col-sm-3'];

        // --- campos
        $etapa3 = new THidden('etapa3_id');
        $origem = new THidden('origem');
        $urlRecarga = new THidden('url_recarga');
        $origemPai = new THidden('origem_pai');
        $urlPai = new THidden('url_pai');

        $contrato = new TCombo('contrato_id');
        $contrato->addItems($contratos);
        $contrato->setSize('100%');
        if (!$contratos) { $contrato->setEditable(false); }

        $benef = new TCombo('beneficiario_credito_id');
        $benef->addItems($beneficiarios);
        $benef->setSize('100%');

        $dataLev = new TDate('data_levantamento');
        $dataLev->setMask('dd/mm/yyyy');
        $dataLev->setDatabaseMask('yyyy-mm-dd');
        $dataLev->setSize('100%');

        $valorLev = new TNumeric('valor_levantado', 2, ',', '.');
        $valorLev->setSize('100%');

        $pctHon = new TEntry('pct_honorarios');
        $pctHon->setSize('100%');
        $pctHon->setProperty('placeholder', 'ex.: 20 ou 17,5');

        $pctNf = new TEntry('pct_nf');
        $pctNf->setSize('100%');

        // descontos do deposito detalhados (vao para a prestacao de contas ao cliente); soma = bruto - MLE
        $descPrev = new TNumeric('desconto_previdenciario', 2, ',', '.');
        $descAssist = new TNumeric('desconto_assistencial', 2, ',', '.');
        $descIrrf = new TNumeric('desconto_irrf', 2, ',', '.');
        foreach ([$descPrev, $descAssist, $descIrrf] as $campo) { $campo->setSize('100%'); }

        $parceria = new TCombo('parceria_levantamento_id');
        $parceria->addItems($parcerias);
        $parceria->setSize('100%');

        $dataDist = new TDate('data_distribuicao');
        $dataDist->setMask('dd/mm/yyyy');
        $dataDist->setDatabaseMask('yyyy-mm-dd');
        $dataDist->setSize('100%');

        $contaCaixa = new TCombo('conta_caixa_id');
        $contaCaixa->addItems($contas);
        $contaCaixa->setSize('100%');

        $tipoPgto = new TCombo('tipo_pagamento_id');
        $tipoPgto->addItems($pagamentos);
        $tipoPgto->setSize('100%');

        $compensado = new TCombo('compensado');
        $compensado->addItems(['S' => 'Sim, já compensado', 'N' => 'Não, ainda a compensar']);
        $compensado->setSize('100%');

        $vencimento = new TDate('vencimento_cliente');
        $vencimento->setMask('dd/mm/yyyy');
        $vencimento->setDatabaseMask('yyyy-mm-dd');
        $vencimento->setSize('100%');

        $categoria = new TCombo('categoria_conta_id');
        $categoria->addItems($categorias);
        $categoria->setSize('100%');

        $docReceber = new TCombo('tipo_documento_receber_id');
        $docReceber->addItems($documentos);
        $docReceber->setSize('100%');

        $docPagar = new TCombo('tipo_documento_pagar_id');
        $docPagar->addItems($documentos);
        $docPagar->setSize('100%');

        // previa ao vivo: todos os campos que entram no calculo
        $acao = new TAction([__CLASS__, 'onSimular'], ['static' => '1']);
        foreach ([$benef, $dataLev, $parceria, $dataDist] as $campo) { $campo->setChangeAction($acao); }
        $contrato->setChangeAction(new TAction([__CLASS__, 'onMudarContrato'], ['static' => '1']));
        foreach ([$valorLev, $pctHon, $pctNf, $descPrev, $descAssist, $descIrrf] as $campo) { $campo->setExitAction($acao); }


        $this->form->addContent([self::titulo('Dados do levantamento')]);
        $this->form->addFields([$etapa3, $origem, $urlRecarga, $origemPai, $urlPai]);

        $row = $this->form->addFields(
            [self::rotulo('Contrato', true), $contrato],
            [self::rotulo('Beneficiário do crédito', true), $benef],
            [self::rotulo('Data do levantamento', true), $dataLev],
            [self::rotulo('Valor levantado (R$)', true), $valorLev]
        );
        $row->layout = ['col-sm-3', 'col-sm-4', 'col-sm-2', 'col-sm-3'];

        $row = $this->form->addFields(
            [self::rotulo($ctx->pct_honorarios_sugerido !== null ? '% honorários (do contrato)' : '% honorários (informado)', true), $pctHon],
            [self::rotulo('% custo de NF', true), $pctNf],
            [self::rotulo('Parceria de repasse'), $parceria],
            [self::rotulo('Data de distribuição', true), $dataDist]
        );
        $row->layout = ['col-sm-3', 'col-sm-2', 'col-sm-4', 'col-sm-3'];

        $totalDescontos = new TElement('div');
        $totalDescontos->class = 'lf-nota';
        $totalDescontos->style = 'padding-top:24px';
        $totalDescontos->add('A soma tem que ser o total de descontos do pagamento (bruto − MLE): <b>' . self::moeda($ctx->descontos) . '</b>');
        $row = $this->form->addFields(
            [self::rotulo('Desconto previdenciário (R$)', true), $descPrev],
            [self::rotulo('Desconto assistencial (R$)', true), $descAssist],
            [self::rotulo('Desconto IRRF (R$)', true), $descIrrf],
            [$totalDescontos]
        );
        $row->layout = ['col-sm-3', 'col-sm-3', 'col-sm-2', 'col-sm-4'];

        $this->form->addContent([self::titulo('Lançamento financeiro')]);
        $row = $this->form->addFields(
            [self::rotulo('Conta-caixa que recebeu', true), $contaCaixa],
            [self::rotulo('Forma de pagamento'), $tipoPgto],
            [self::rotulo('Valor já compensado?'), $compensado],
            [self::rotulo('Vencimento do pagamento ao cliente', true), $vencimento]
        );
        $row->layout = ['col-sm-4', 'col-sm-3', 'col-sm-2', 'col-sm-3'];
        $row = $this->form->addFields(
            [self::rotulo('Categoria das contas'), $categoria],
            [self::rotulo('Documento da conta a receber'), $docReceber],
            [self::rotulo('Documento da conta a pagar (cliente)'), $docPagar]
        );
        $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        // --- previa
        $this->form->addContent([self::titulo('Prévia do cálculo')]);
        $previa = new TElement('div');
        $previa->id = self::idPrevia($ctx->etapa3_id);
        $previa->class = 'lf-previa';
        $previa->add(self::caixaAviso('Escolha o contrato e o beneficiário para ver a prévia.', 'info'));
        $this->form->addContent([$previa]);

        $btn = $this->form->addAction('Gerar financeiro', new TAction([__CLASS__, 'onGerar'], ['static' => '1']), 'fas:check');
        $btn->class = 'btn btn-sm btn-primary';
        $btn->addFunction("if (this.disabled) { return false; } this.disabled = true; var botao = this; $(document).on('ajaxComplete.travarGerarFinanceiro', function (e, xhr, s) { if (s.url && s.url.indexOf('method=onGerar') >= 0) { botao.disabled = false; $(document).off('ajaxComplete.travarGerarFinanceiro'); } })");

        // valores iniciais
        $unico = (count($ctx->contratos) === 1);
        $padroes = self::padroes($contas, $pagamentos, $categorias, $documentos);
        $regraUnica = null;
        $qtd = 0;
        foreach (self::regrasPorBeneficiario() as $id => $regra) {
            if ($regra === BeneficiarioCredito::REGRA_CLIENTE_E_HONORARIOS) { $regraUnica = $id; $qtd++; }
        }

        $data = new stdClass;
        $data->etapa3_id = $ctx->etapa3_id;
        $data->origem = $param['origem'] ?? '';
        $data->url_recarga = $param['url_recarga'] ?? '';
        $data->origem_pai = $param['origem_pai'] ?? '';
        $data->url_pai = $param['url_pai'] ?? '';
        $data->contrato_id = $unico ? $ctx->contrato_sugerido_id : null;
        $data->beneficiario_credito_id = ($qtd === 1) ? $regraUnica : null;
        $data->data_levantamento = !empty($ctx->data_deferimento_mle) ? substr($ctx->data_deferimento_mle, 0, 10) : substr($ctx->data_deposito, 0, 10);
        // valor levantado = o que foi efetivamente recebido (maior ou menor que o MLE): o usuario informa
        $data->valor_levantado = null;
        $data->pct_honorarios = ($ctx->pct_honorarios_sugerido !== null) ? self::pctTexto($ctx->pct_honorarios_sugerido) : null;
        $data->pct_nf = self::pctTexto(FinanceiroConfiguracao::pctNfPadrao());
        if (round((float) $ctx->descontos, 2) == 0) {
            $data->desconto_previdenciario = $data->desconto_assistencial = $data->desconto_irrf = '0,00';
        }
        $data->parceria_levantamento_id = $ctx->parceria_sugerida_id;
        $data->data_distribuicao = $ctx->data_distribuicao;
        $data->conta_caixa_id = (count($contas) === 1) ? key($contas) : null;
        $data->tipo_pagamento_id = $padroes['tipo_pagamento_id'];
        $data->compensado = 'S';
        $data->vencimento_cliente = null;
        $data->categoria_conta_id = $padroes['categoria_conta_id'];
        $data->tipo_documento_receber_id = $padroes['tipo_documento_receber_id'];
        $data->tipo_documento_pagar_id = $padroes['tipo_documento_pagar_id'];
        $this->form->setData($data);

        parent::add($this->form);

        // primeira previa (depois que a janela e os campos existirem)
        TScript::create('setTimeout(function () { __adianti_post_data("' . self::$formName . '", "class=' . __CLASS__ . '&method=onSimular&static=1"); }, 400);');
    }

    // ---------------------------------------------------------------
    // Acoes
    // ---------------------------------------------------------------

    /** Muda de contrato: traz o % de honorarios e a parceria sugeridos para ele e refaz a previa. */
    public static function onMudarContrato($param)
    {
        try {
            $ctx = LevantamentoContextoService::carregar((int) ($param['etapa3_id'] ?? 0));
            $contrato = self::contratoDoCtx($ctx, (int) ($param['contrato_id'] ?? 0));

            $obj = new stdClass;
            if ($contrato) {
                $obj->pct_honorarios = ($contrato['pct_honorarios'] !== null) ? self::pctTexto($contrato['pct_honorarios']) : '';
                $obj->parceria_levantamento_id = LevantamentoContextoService::parceriaPorRepasse($contrato['repasse']);
                $param['pct_honorarios'] = $obj->pct_honorarios;
                $param['parceria_levantamento_id'] = $obj->parceria_levantamento_id;
                TForm::sendData(self::$formName, $obj, false, false);
            }
        } catch (Throwable $e) {
            // a previa mostra o problema
        }
        self::onSimular($param);
    }

    /** Recalcula (sem gravar) e troca o HTML da previa; ajusta quais campos aparecem. */
    public static function onSimular($param)
    {
        $etapa3Id = (int) ($param['etapa3_id'] ?? 0);
        $html = '';
        $zPositivo = true;
        $ctx = null;
        try {
            $ctx = LevantamentoContextoService::carregar($etapa3Id);
            $d = self::dadosDoForm($param, $ctx, false);
            $sim = LevantamentoFinanceiroService::simular($etapa3Id, $d);
            $zPositivo = ((float) $sim['resultado']['z'] > 0);
            $html = self::htmlPrevia($sim, $d);
        } catch (Throwable $e) {
            $html = self::avisoHtml(htmlspecialchars(self::mensagem($e), ENT_QUOTES, 'UTF-8'), 'aviso');
        }

        // visibilidade e rotulos dos campos
        $regra = self::regraDoBeneficiario((int) ($param['beneficiario_credito_id'] ?? 0));
        $contrato = $ctx ? self::contratoDoCtx($ctx, (int) ($param['contrato_id'] ?? 0)) : null;
        $mostrarHon = ($regra === null || $regra === BeneficiarioCredito::REGRA_CLIENTE_E_HONORARIOS);
        $mostrarParceria = ($regra !== BeneficiarioCredito::REGRA_SOMENTE_CLIENTE);
        $tipoParceria = $mostrarParceria ? self::tipoDaParceria((int) ($param['parceria_levantamento_id'] ?? 0)) : null;
        $mostrarDist = ($tipoParceria === ParceriaLevantamento::TIPO_TEMPORAL);
        $rotuloPct = ($contrato && $contrato['pct_honorarios'] !== null) ? '% honorários (do contrato)' : '% honorários (informado)';
        $pctObrigatorio = ($regra === null || $regra === BeneficiarioCredito::REGRA_CLIENTE_E_HONORARIOS);

        $js = 'var el = document.getElementById(' . json_encode(self::idPrevia($etapa3Id)) . '); if (el) { el.innerHTML = ' . json_encode($html) . '; }'
            . 'var f = $("#' . self::$formName . '");'
            . 'function lfCampo(n, mostrar) { var c = f.find("[name=" + n + "]").first().closest("[class*=col-sm-]"); if (mostrar) { c.show(); } else { c.hide(); } return c; }'
            . 'lfCampo("pct_honorarios", ' . json_encode($mostrarHon) . ').find("label").first().text(' . json_encode($rotuloPct) . ').css("color", ' . json_encode($pctObrigatorio ? self::COR_OBRIGATORIO : '') . ');'
            . 'lfCampo("parceria_levantamento_id", ' . json_encode($mostrarParceria) . ');'
            . 'lfCampo("data_distribuicao", ' . json_encode($mostrarDist) . ');'
            . 'lfCampo("vencimento_cliente", ' . json_encode($zPositivo) . ');'
            . 'lfCampo("tipo_documento_pagar_id", ' . json_encode($zPositivo) . ');';
        TScript::create($js);
    }

    public static function onGerar($param)
    {
        try {
            $etapa3Id = (int) ($param['etapa3_id'] ?? 0);
            $ctx = LevantamentoContextoService::carregar($etapa3Id);
            $d = self::dadosDoForm($param, $ctx, true);

            LevantamentoFinanceiroService::gerar($etapa3Id, $d);

            self::fecharJanela();
            TToast::show('success', 'Financeiro gerado', 'topRight', 'far:check-circle');
            self::recarregarOrigem($param);

            // abre o "Ver financeiro" ja com a prestacao de contas para baixar (o download sai do clique do usuario;
            // baixar direto daqui seria bloqueado pelo navegador como janela automatica)
            $ver = http_build_query(['class' => 'LevantamentoFinanceiroView', 'method' => 'onShow', 'etapa3_id' => $etapa3Id, 'recem_gerado' => 1,
                'origem' => $param['origem'] ?? '', 'url_recarga' => $param['url_recarga'] ?? '', 'origem_pai' => $param['origem_pai'] ?? '', 'url_pai' => $param['url_pai'] ?? '']);
            TScript::create('setTimeout(function () { __adianti_load_page(' . json_encode('engine.php?' . $ver) . '); }, 900);');
        } catch (Throwable $e) {
            new TMessage('error', self::mensagem($e));
        }
    }

    // ---------------------------------------------------------------
    // Leitura do formulario
    // ---------------------------------------------------------------

    /**
     * Monta o $d do servico a partir do post. $estrito (gerar) exige tambem conta-caixa, vencimento, etc.
     * Percentuais e valores sao lidos com parse estrito: "20", "20,5" e "16" valem; texto solto e recusado.
     */
    private static function dadosDoForm(array $param, object $ctx, bool $estrito): array
    {
        $contratoId = (int) ($param['contrato_id'] ?? 0);
        if ($contratoId <= 0) { throw new Exception('Escolha o contrato.'); }
        $beneficiarioId = (int) ($param['beneficiario_credito_id'] ?? 0);
        if ($beneficiarioId <= 0) { throw new Exception('Escolha o beneficiário do crédito.'); }
        $regra = self::regraDoBeneficiario($beneficiarioId);

        $dataLev = self::dataIso($param['data_levantamento'] ?? '', 'data do levantamento');
        if ($dataLev === null) { throw new Exception('Informe a data do levantamento.'); }

        $valor = self::numero($param['valor_levantado'] ?? '', 'valor levantado', true);
        if ($valor === null || $valor <= 0) { throw new Exception('Informe o valor levantado (o valor efetivamente recebido).'); }
        // so a regra "Cliente + honorarios" usa o % de honorarios; nas outras o campo e ignorado
        $pctHon = ($regra === BeneficiarioCredito::REGRA_CLIENTE_E_HONORARIOS) ? self::numero($param['pct_honorarios'] ?? '', '% de honorários', false) : null;
        $pctNf = self::numero($param['pct_nf'] ?? '', '% de custo de NF', false);

        if ($regra === BeneficiarioCredito::REGRA_CLIENTE_E_HONORARIOS && $pctHon === null) {
            $contrato = self::contratoDoCtx($ctx, $contratoId);
            $origem = ($contrato && $contrato['pct_honorarios'] !== null) ? '' : ' O contrato não define o percentual (por exemplo, honorários em multiplicador): digite o % acertado.';
            throw new Exception('Informe o % de honorários: é obrigatório quando o beneficiário é "Cliente + honorários".' . $origem);
        }
        if ($pctNf === null) { throw new Exception('Informe o % de custo de NF (entre 0 e 100).'); }

        $descontos = [];
        foreach (['desconto_previdenciario' => 'desconto previdenciário', 'desconto_assistencial' => 'desconto assistencial', 'desconto_irrf' => 'desconto IRRF'] as $campo => $nome) {
            $descontos[$campo] = self::numero($param[$campo] ?? '', $nome, true);
        }
        if ($estrito) {
            // no gerar os tres sao obrigatorios e a soma tem que bater (o servico confere de novo)
            foreach ($descontos as $campo => $valor) {
                if ($valor === null) { throw new Exception('Informe os descontos do depósito (previdenciário, assistencial e IRRF; use 0 quando não houver).'); }
            }
            LevantamentoFinanceiroService::descontosDetalhados($descontos, round((float) $ctx->descontos, 2));
        }

        $parceriaId = (int) ($param['parceria_levantamento_id'] ?? 0) ?: null;
        $dataDist = self::dataIso($param['data_distribuicao'] ?? '', 'data de distribuição');
        if ($regra !== BeneficiarioCredito::REGRA_SOMENTE_CLIENTE && $parceriaId
            && self::tipoDaParceria($parceriaId) === ParceriaLevantamento::TIPO_TEMPORAL && $dataDist === null) {
            throw new Exception('A parceria escolhida é por tempo: informe a data de distribuição do processo.');
        }

        $d = [
            'contrato_id' => $contratoId,
            'beneficiario_credito_id' => $beneficiarioId,
            'data_levantamento' => $dataLev,
            'valor_levantado' => $valor ?? 0,
            'pct_honorarios' => $pctHon,
            'pct_nf' => $pctNf,
            'desconto_previdenciario' => $descontos['desconto_previdenciario'],
            'desconto_assistencial' => $descontos['desconto_assistencial'],
            'desconto_irrf' => $descontos['desconto_irrf'],
            'parceria_levantamento_id' => $parceriaId,
            'data_distribuicao' => $dataDist,
            'conta_caixa_id' => (int) ($param['conta_caixa_id'] ?? 0),
            'tipo_pagamento_id' => (int) ($param['tipo_pagamento_id'] ?? 0),
            'compensado' => $param['compensado'] ?? '',
            'vencimento_cliente' => null,
            'categoria_conta_id' => (int) ($param['categoria_conta_id'] ?? 0),
            'tipo_documento_receber_id' => (int) ($param['tipo_documento_receber_id'] ?? 0),
            'tipo_documento_pagar_id' => (int) ($param['tipo_documento_pagar_id'] ?? 0),
        ];

        if ($estrito) {
            if ($d['conta_caixa_id'] <= 0) { throw new Exception('Escolha a conta-caixa que recebeu o valor.'); }
            if ($d['tipo_pagamento_id'] <= 0) { throw new Exception('Escolha a forma de pagamento.'); }
            if ($d['categoria_conta_id'] <= 0) { throw new Exception('Escolha a categoria das contas.'); }
            if ($d['tipo_documento_receber_id'] <= 0) { throw new Exception('Escolha o tipo de documento da conta a receber.'); }
            $d['vencimento_cliente'] = self::dataIso($param['vencimento_cliente'] ?? '', 'vencimento do pagamento ao cliente');
        } else {
            try { $d['vencimento_cliente'] = self::dataIso($param['vencimento_cliente'] ?? '', 'vencimento'); } catch (Exception $e) { $d['vencimento_cliente'] = null; }
        }
        return $d;
    }

    /** '20', '20,5', '20.5', '1.234,56' => float; vazio => null; resto => erro. */
    private static function numero($valor, string $rotulo, bool $dinheiro): ?float
    {
        $t = trim((string) $valor);
        if ($t === '') { return null; }
        if ($dinheiro) {
            if (strpos($t, ',') !== false) { $t = str_replace('.', '', $t); }   // 1.234,56
            $t = str_replace(',', '.', $t);
            if (!preg_match('/^\d+(\.\d+)?$/', $t)) { throw new Exception("Valor inválido em {$rotulo}: '" . trim((string) $valor) . "'."); }
        } else {
            if (!preg_match('/^\d+([.,]\d+)?$/', $t)) {
                throw new Exception("Valor inválido em {$rotulo}: '{$t}'. Use apenas números com vírgula ou ponto (ex.: 20 ou 17,5).");
            }
            $t = str_replace(',', '.', $t);
        }
        return (float) $t;
    }

    /** dd/mm/aaaa ou aaaa-mm-dd => aaaa-mm-dd; vazio => null; invalida => erro. */
    private static function dataIso($texto, string $rotulo): ?string
    {
        $t = trim((string) $texto);
        if ($t === '') { return null; }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $t, $m)) { $y = $m[3]; $mo = $m[2]; $d = $m[1]; }
        elseif (preg_match('#^(\d{4})-(\d{2})-(\d{2})$#', $t, $m)) { $y = $m[1]; $mo = $m[2]; $d = $m[3]; }
        else { throw new Exception("Data inválida em {$rotulo}: '{$t}'."); }
        if (!checkdate((int) $mo, (int) $d, (int) $y)) { throw new Exception("Data inválida em {$rotulo}: '{$t}'."); }
        return "{$y}-{$mo}-{$d}";
    }

    // ---------------------------------------------------------------
    // Previa
    // ---------------------------------------------------------------

    private static function htmlPrevia(array $sim, array $d): string
    {
        $r = $sim['resultado'];
        $per = $sim['percentuais'];
        $y = round((float) $sim['entrada']['valor_levantado'], 2);
        $z = round((float) $r['z'], 2);

        $h = '<div class="lf-grade">'
           . self::linhaValor('Valor levantado', $y)
           . self::linhaValor('Honorários', $r['aa'])
           . self::linhaValor('Custo de NF', $r['ac'])
           . self::linhaValor('Honorários líquidos', $r['ad'])
           . self::linhaValor('Valor ao cliente', $z, true)
           . '</div>';

        if (!empty($r['repasses'])) {
            $nomes = self::nomesDePessoas(array_column($r['repasses'], 'pessoa_id'));
            $h .= '<table class="lf-tabela"><thead><tr><th>Parceiro</th><th class="lf-num">%</th><th class="lf-num">Repasse (R$)</th></tr></thead><tbody>';
            foreach ($r['repasses'] as $rp) {
                $aj = ((float) $rp['ajuste_centavos'] != 0.0) ? ' <small>(inclui ' . self::moeda($rp['ajuste_centavos']) . ' de ajuste de centavos)</small>' : '';
                $h .= '<tr><td>' . htmlspecialchars($nomes[(int) $rp['pessoa_id']] ?? ('Pessoa ' . $rp['pessoa_id'])) . '</td>'
                    . '<td class="lf-num">' . number_format((float) $rp['percentual'] * 100, 4, ',', '.') . '%</td>'
                    . '<td class="lf-num">' . self::moeda($rp['valor']) . $aj . '</td></tr>';
            }
            $h .= '</tbody></table>';
            if ($per['a'] !== null) {
                $h .= '<div class="lf-nota">Parceria por tempo: fração antes do 1º marco = ' . number_format($per['a'] * 100, 2, ',', '.') . '%; antes do 2º marco = '
                    . number_format($per['p'] * 100, 2, ',', '.') . '%; peso do nível 1 = ' . number_format($per['d'] * 100, 2, ',', '.') . '%.</div>';
            }
        } else {
            $h .= '<div class="lf-nota">Sem repasse a parceiros (não há honorários líquidos a dividir).</div>';
        }

        $desc = [$d['desconto_previdenciario'] ?? null, $d['desconto_assistencial'] ?? null, $d['desconto_irrf'] ?? null];
        $totalDesc = round((float) $sim['contexto']->descontos, 2);
        if (in_array(null, $desc, true)) {
            $h .= self::avisoHtml('Informe os descontos do depósito (previdenciário, assistencial e IRRF) para a prestação de contas ao cliente. Total do pagamento: ' . self::moeda($totalDesc) . '.', 'aviso');
        } elseif (abs(round(array_sum($desc), 2) - $totalDesc) > 0.004) {
            $h .= self::avisoHtml('Os descontos informados somam ' . self::moeda(array_sum($desc)) . ', mas o total do pagamento (bruto − MLE) é ' . self::moeda($totalDesc) . '.', 'aviso');
        }

        $h .= '<div class="lf-contas">'
            . '<div><b>Conta a receber (quitada):</b> ' . self::moeda($y) . '</div>'
            . '<div>' . ($z > 0 ? '<b>Conta a pagar ao cliente (aberta):</b> ' . self::moeda($z) : '<b>Sem conta a pagar ao cliente.</b>') . '</div>'
            . '</div>';

        $okZ = (abs(round($z + (float) $r['aa'], 2) - $y) < 0.005);
        $soma = round(array_sum(array_map(function ($x) { return (float) $x['valor']; }, $r['repasses'])), 2);
        $okR = (abs($soma - round((float) $r['ad'], 2)) < 0.005);
        $h .= '<div class="lf-conf">'
            . self::conferencia('Valor ao cliente + honorários = valor levantado (' . self::moeda($z) . ' + ' . self::moeda($r['aa']) . ' = ' . self::moeda($y) . ')', $okZ)
            . self::conferencia('Soma dos repasses = honorários líquidos (' . self::moeda($soma) . ' = ' . self::moeda($r['ad']) . ')', $okR)
            . '</div>';

        return $h;
    }

    private static function linhaValor(string $rotulo, $valor, bool $destaque = false): string
    {
        return '<div class="lf-item' . ($destaque ? ' lf-destaque' : '') . '"><span>' . htmlspecialchars($rotulo) . '</span><strong>' . self::moeda($valor) . '</strong></div>';
    }

    private static function conferencia(string $texto, bool $ok): string
    {
        return '<div class="' . ($ok ? 'lf-ok' : 'lf-falha') . '">' . ($ok ? '&#10003; ' : '&#10007; ') . htmlspecialchars($texto) . '</div>';
    }

    private static function nomesDePessoas(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) { return []; }
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open(self::$database); }
        $st = TTransaction::get()->query('SELECT id, nome FROM pessoa WHERE id IN (' . implode(',', $ids) . ')');
        $nomes = [];
        foreach ($st->fetchAll(PDO::FETCH_OBJ) as $p) { $nomes[(int) $p->id] = $p->nome; }
        if ($propria) { TTransaction::close(); }
        return $nomes;
    }

    // ---------------------------------------------------------------
    // Consultas auxiliares
    // ---------------------------------------------------------------

    private static function opcoes(string $sql): array
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open(self::$database); }
        $lista = [];
        foreach (TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_OBJ) as $l) { $lista[(int) $l->id] = $l->nome; }
        if ($propria) { TTransaction::close(); }
        return $lista;
    }

    /** Padroes por nome (sem acento / caixa). Se nao achar, fica vazio: nunca assume o id. */
    private static function padroes(array $contas, array $pagamentos, array $categorias, array $documentos): array
    {
        $achar = function (array $lista, string $nome, bool $contem = false) {
            foreach ($lista as $id => $n) {
                $a = self::semAcento($n);
                if ($contem ? (strpos($a, $nome) !== false) : ($a === $nome)) { return $id; }
            }
            return null;
        };
        return [
            'tipo_pagamento_id' => $achar($pagamentos, 'transferencia', true),
            'categoria_conta_id' => $achar($categorias, 'entradas e repasses de clientes'),
            'tipo_documento_receber_id' => $achar($documentos, 'processo'),
            'tipo_documento_pagar_id' => $achar($documentos, 'repasse cliente'),
        ];
    }

    public static function semAcento(string $s): string
    {
        if (class_exists('Normalizer')) { $s = Normalizer::normalize($s, Normalizer::FORM_D); }
        $s = preg_replace('/\p{Mn}/u', '', $s);
        $s = strtr($s, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
                        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
                        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
                        'Á' => 'a', 'À' => 'a', 'Â' => 'a', 'Ã' => 'a', 'É' => 'e', 'Ê' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ô' => 'o', 'Õ' => 'o', 'Ú' => 'u', 'Ç' => 'c']);
        return trim(mb_strtolower($s, 'UTF-8'));
    }

    private static function regrasPorBeneficiario(): array
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open(self::$database); }
        $r = [];
        foreach (TTransaction::get()->query("SELECT id, regra FROM beneficiario_credito WHERE ativo = 'S'")->fetchAll(PDO::FETCH_OBJ) as $l) { $r[(int) $l->id] = $l->regra; }
        if ($propria) { TTransaction::close(); }
        return $r;
    }

    private static function regraDoBeneficiario(int $id): ?string
    {
        return $id > 0 ? (self::regrasPorBeneficiario()[$id] ?? null) : null;
    }

    private static function tipoDaParceria(int $id): ?string
    {
        if ($id <= 0) { return null; }
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open(self::$database); }
        $st = TTransaction::get()->prepare('SELECT tipo FROM parceria_levantamento WHERE id = ?');
        $st->execute([$id]);
        $t = $st->fetchColumn();
        if ($propria) { TTransaction::close(); }
        return $t ?: null;
    }

    private static function contratoDoCtx(object $ctx, int $id): ?array
    {
        foreach ($ctx->contratos as $ct) { if ((int) $ct['id'] === $id) { return $ct; } }
        return null;
    }

    // ---------------------------------------------------------------
    // Origem (recarregar depois de gerar / desfazer)
    // ---------------------------------------------------------------

    /**
     * Recarrega a tela de onde o botao foi clicado. origem: janela | aba | detalhe;
     * url_recarga: URL da propria tela (so aceita as telas conhecidas).
     */
    public static function recarregarOrigem(array $param): void
    {
        $origem = (string) ($param['origem'] ?? '');
        $url = (string) ($param['url_recarga'] ?? '');

        // classe => [seletor da janela antiga a remover, chaves permitidas na URL]
        $permitidas = [
            'janela'  => ['RequisicaoPagamentoVisualizacao', 'form[name=form_RequisicaoPagamentoVisualizacaoWindow], form#form_RequisicaoPagamentoVisualizacaoWindow',
                          ['key', 'pessoa_id', 'requisicao_pagamento_cliente_id', 'modo_embutido', 'register_state']],
            'aba'     => ['RequisicaoPagamentoVisualizacaoAba', null,
                          ['key', 'pessoa_id', 'processo_origem_id', 'exibir_voltar', 'target_container', 'register_state', 'show_loading']],
            'detalhe' => ['RequisicaoPagamentoEtapa3Visualizacao', '.e3-wrap', ['key', 'origem_pai', 'url_pai', 'register_state']],
        ];
        $url = self::urlSegura($url, $permitidas[$origem] ?? null);
        if ($url === null) { return; }

        // a tela que abriu o detalhe (historico da janela ou da aba) tambem fica desatualizada: recarrega primeiro
        $passos = [];
        $origemPai = (string) ($param['origem_pai'] ?? '');
        $urlPai = (string) ($param['url_pai'] ?? '');
        $urlPai = in_array($origemPai, ['janela', 'aba'], true) ? self::urlSegura($urlPai, $permitidas[$origemPai]) : null;
        if ($origem === 'detalhe' && $urlPai !== null) {
            $passos[] = [$permitidas[$origemPai][1], $urlPai];
        }
        $passos[] = [$permitidas[$origem][1], $url];

        $js = '';
        foreach ($passos as $i => $passo) {
            // fecha a janela antiga (a que ficou com o botao desatualizado) antes de abrir a nova
            $remover = $passo[0] ? '$(' . json_encode($passo[0]) . ').closest(".ui-dialog, .modal").remove();' : '';
            $js .= 'setTimeout(function () { ' . $remover . ' Adianti.waitMessage = false; __adianti_load_page(' . json_encode($passo[1]) . '); }, ' . (250 + $i * 900) . ');';
        }
        TScript::create($js);
    }

    /**
     * Reconstroi no servidor a URL de recarga: class unica da lista fixa, method onShow, so as chaves
     * permitidas da tela e valores escalares. Qualquer outra coisa => null (nao recarrega).
     */
    private static function urlSegura(string $url, ?array $tela): ?string
    {
        if ($tela === null || strpos($url, 'engine.php?') !== 0) { return null; }
        $query = substr($url, strlen('engine.php?'));
        // class/method repetidos (PHP usaria o ultimo) => recusa
        if (substr_count($query, 'class=') !== 1 || substr_count($query, 'method=') !== 1) { return null; }
        parse_str($query, $q);
        if (($q['class'] ?? null) !== $tela[0] || ($q['method'] ?? null) !== 'onShow') { return null; }
        $novo = ['class' => $tela[0], 'method' => 'onShow'];
        foreach ($tela[2] as $chave) {
            if (isset($q[$chave])) {
                if (!is_scalar($q[$chave])) { return null; }
                $novo[$chave] = (string) $q[$chave];
            }
        }
        return 'engine.php?' . http_build_query($novo);
    }

    /** O processo principal (raiz) e diferente do processo do pagamento? */
    public static function raizDiferente(object $ctx): bool
    {
        return !empty($ctx->processo_raiz_id) && (int) $ctx->processo_raiz_id !== (int) $ctx->processo_id && (string) ($ctx->processo_raiz_numero ?? '') !== '';
    }

    /** Mensagem legivel para o usuario: a da Exception; para outros erros, texto generico. */
    public static function mensagem(Throwable $e): string
    {
        if ($e instanceof PDOException) { return 'Não foi possível gravar o financeiro (erro no banco de dados). Nada foi gravado. Avise o suporte se persistir.'; }
        return ($e instanceof Exception) ? $e->getMessage() : 'Erro inesperado ao processar o financeiro. Tente novamente; se persistir, avise o suporte.';
    }

    // ---------------------------------------------------------------
    // Botoes nas outras telas (historico de pagamentos e detalhe do pagamento)
    // ---------------------------------------------------------------

    /** Ids (Etapa 3) que ja tem financeiro gerado: uma consulta so para a lista inteira. @return array id => true */
    public static function idsComFinanceiro(array $etapa3Ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $etapa3Ids))));
        if (!$ids) { return []; }

        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open(self::$database); }
        try {
            $st = TTransaction::get()->prepare('SELECT requisicao_pagamento_etapa3_id FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ANY(?::int[])');
            $st->execute(['{' . implode(',', $ids) . '}']);
            $r = array_fill_keys(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)), true);
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /**
     * HTML do botao "Gerar financeiro" / "Ver financeiro" de um pagamento, ou '' quando nao cabe
     * (pagamento sem deposito/MLE lancado e sem financeiro). $botaoTexto = false: so o icone (linha da tabela).
     *
     * @param object $pagamento  precisa de id, data_deposito, valor_bruto_depositado, valor_mle
     * @param array  $comFinanceiro resultado de idsComFinanceiro()
     * @param string $origem     janela | aba | detalhe
     * @param string $urlRecarga URL (engine.php?class=...) da tela de origem, para recarregar depois
     * @param array  $pai        no detalhe: ['origem' => janela|aba, 'url' => URL] da tela que abriu o detalhe
     */
    public static function botaoFinanceiro(object $pagamento, array $comFinanceiro, string $origem, string $urlRecarga, bool $botaoTexto = false, array $pai = []): string
    {
        $id = (int) $pagamento->id;
        $existe = isset($comFinanceiro[$id]);
        if (!$existe && (empty($pagamento->data_deposito) || (float) $pagamento->valor_bruto_depositado <= 0 || (float) $pagamento->valor_mle <= 0)) {
            return '';
        }

        $classe = $existe ? 'LevantamentoFinanceiroView' : 'LevantamentoFinanceiroForm';
        $url = 'engine.php?class=' . $classe . '&method=onShow&etapa3_id=' . $id . '&origem=' . urlencode($origem)
             . '&url_recarga=' . urlencode($urlRecarga)
             . (!empty($pai['origem']) ? '&origem_pai=' . urlencode($pai['origem']) . '&url_pai=' . urlencode((string) ($pai['url'] ?? '')) : '')
             . '&register_state=false';
        $titulo = $existe ? 'Ver financeiro' : 'Gerar financeiro';
        $icone = $existe ? 'fas fa-file-invoice-dollar' : 'fas fa-hand-holding-usd';
        $onclick = 'Adianti.waitMessage = false; __adianti_load_page(' . json_encode($url) . '); return false;';

        if ($botaoTexto) {
            $estilo = $existe ? 'background:#16a34a;border-color:#16a34a;color:#fff;' : '';
            return "<a href='javascript:void(0)' class='btn btn-sm btn-" . ($existe ? 'success' : 'primary') . "' style='" . $estilo . "' title='" . $titulo . "' onclick=\""
                . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . "\"><i class='" . $icone . "'></i> " . $titulo . "</a>";
        }
        return "<a href='javascript:void(0)' class='req-action-btn' title='" . $titulo . "' style='" . ($existe ? 'background:#16a34a;border-color:#16a34a;color:#fff;' : '') . "' onclick=\""
            . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . "\"><i class='" . $icone . "'></i></a>";
    }

    // ---------------------------------------------------------------
    // Apresentacao
    // ---------------------------------------------------------------

    public static function idPrevia(int $etapa3Id): string
    {
        return 'prev-levantamento-' . $etapa3Id;
    }

    public static function titulo(string $texto): TElement
    {
        $t = new TElement('div');
        $t->class = 'lf-titulo';
        $t->add(htmlspecialchars($texto));
        return $t;
    }

    /** Titulo de campo; obrigatorio = vermelho (padrao do sistema), sem asterisco. */
    public static function rotulo(string $texto, bool $obrigatorio = false): TLabel
    {
        return new TLabel($texto, $obrigatorio ? self::COR_OBRIGATORIO : null, '12px', 'B', '100%');
    }

    public static function caixaAviso(string $htmlSeguro, string $tipo = 'info'): TElement
    {
        $d = new TElement('div');
        $d->class = 'lf-aviso lf-aviso-' . $tipo;
        $d->add($htmlSeguro);
        return $d;
    }

    /** O mesmo aviso, como texto HTML (para trocar o conteudo da previa). */
    public static function avisoHtml(string $htmlSeguro, string $tipo = 'info'): string
    {
        return '<div class="lf-aviso lf-aviso-' . $tipo . '">' . $htmlSeguro . '</div>';
    }

    public static function moeda($v): string
    {
        return 'R$ ' . number_format((float) $v, 2, ',', '.');
    }

    public static function dataBr($d): string
    {
        if (empty($d)) { return '-'; }
        $d = substr((string) $d, 0, 10);
        $p = explode('-', $d);
        return (count($p) === 3) ? "{$p[2]}/{$p[1]}/{$p[0]}" : $d;
    }

    public static function pctTexto($v): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format((float) $v, 8, '.', ''), '0'), '.'));
    }

    public static function documento($v): string
    {
        $n = preg_replace('/\D/', '', (string) $v);
        if (strlen($n) === 11) { return substr($n, 0, 3) . '.' . substr($n, 3, 3) . '.' . substr($n, 6, 3) . '-' . substr($n, 9, 2); }
        if (strlen($n) === 14) { return substr($n, 0, 2) . '.' . substr($n, 2, 3) . '.' . substr($n, 5, 3) . '/' . substr($n, 8, 4) . '-' . substr($n, 12, 2); }
        return (string) $v;
    }

    /** CSS compartilhado pelas duas janelas (Gerar / Ver financeiro). */
    public static function estilo(TWindow $janela): void
    {
        $css = new TElement('style');
        $css->add("
            .lf-titulo { font-size: 13px; font-weight: 700; color: #334155; border-bottom: 1px solid #e2e8f0; margin: 14px 0 8px; padding-bottom: 4px; }
            .lf-aviso { padding: 10px 12px; border-radius: 6px; font-size: 13px; }
            .lf-aviso-info { background: #f1f5f9; color: #475569; }
            .lf-aviso-aviso { background: #fff7ed; color: #9a3412; border: 1px solid #fed7aa; }
            .lf-aviso-erro { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
            .lf-aviso-ok { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
            .lf-grade { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 8px; margin-bottom: 10px; }
            .lf-grade3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .lf-item { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; display: flex; flex-direction: column; font-size: 12px; color: #64748b; }
            .lf-item strong { font-size: 15px; color: #0f172a; }
            .lf-destaque { background: #ecfdf5; border-color: #a7f3d0; }
            .lf-tabela { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 8px; }
            .lf-tabela th, .lf-tabela td { border-bottom: 1px solid #e2e8f0; padding: 5px 8px; text-align: left; }
            .lf-tabela .lf-num { text-align: right; white-space: nowrap; }
            .lf-nota { font-size: 12px; color: #64748b; margin-bottom: 8px; }
            .lf-contas { font-size: 13px; margin: 6px 0; }
            .lf-conf { font-size: 12px; margin-top: 6px; }
            .lf-ok { color: #047857; }
            .lf-falha { color: #b91c1c; font-weight: 700; }
            .lf-linha-bloqueio { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; padding: 8px 12px; border-radius: 6px; font-size: 13px; margin: 8px 0; }
            .lf-contas-geradas a { margin-right: 14px; font-weight: 600; }
            .bootbox.modal { z-index: 70000 !important; }
            .bootbox.modal + .modal-backdrop, .modal-backdrop { z-index: 69990 !important; }
            @media (max-width: 900px) { .lf-grade { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        ");
        $janela->add($css);
    }

    /**
     * Fecha a janela do financeiro (Gerar ou Ver). Nao usa TWindow::closeWindow(): esta janela pode abrir
     * por baixo/ao lado de outra (historico da requisicao), e "a ultima" nao e necessariamente a nossa.
     */
    public static function fecharJanela(): void
    {
        TScript::create('$("#' . self::$formName . ', #' . LevantamentoFinanceiroView::NOME_FORM . '").closest(".ui-dialog").remove();');
    }

    /**
     * Abre a janela por cima das outras: as telas de origem (requisicao: z-index 20000; detalhe do
     * pagamento: 60000) forcam um z-index alto e esconderiam esta janela.
     */
    public static function trazerParaFrente(TWindow $janela): void
    {
        $marca = 'lf_marca_' . uniqid();
        $span = new TElement('span');
        $span->id = $marca;
        $span->style = 'display:none';
        $janela->add($span);

        TScript::create("
            (function () {
                function frente() {
                    var d = $('#{$marca}').closest('.ui-dialog');
                    if (!d.length) { return; }
                    var maior = 0;
                    $('.ui-dialog').not(d).each(function () { maior = Math.max(maior, parseInt($(this).css('z-index'), 10) || 0); });
                    if (maior >= (parseInt(d.css('z-index'), 10) || 0)) { d.css('z-index', maior + 10); }
                }
                setTimeout(frente, 100); setTimeout(frente, 600); setTimeout(frente, 1500);
            })();
        ");
    }
}
