<?php
// tests/levantamento/RepasseColaboradorTelaTest.php
// Telas dos repasses a colaboradores: listagem (filtros, totais, selecao), pagamento em lote pela janela,
// estorno do lote, relatorio PDF/Excel, filtros do extrato, quadros na conta e no Ver financeiro, configuracao.
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

function saida(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function erro(string $s): string
{
    if (preg_match('/EXCECAO: (.*)/', $s, $m)) { return $m[1]; }
    if (preg_match("/__adianti_error\\(['\"][^'\"]*['\"],\\s*['\"]([^'\"]+)/", $s, $m)) { return $m[1]; }
    return '';
}
function post(array $dados): void { $_POST = $dados; $_REQUEST = $dados; }
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }

TSession::setValue('userid', null);
TTransaction::open('escritorio');
$pid = function (string $nome) { $st = TTransaction::get()->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?, NFC) ORDER BY id LIMIT 1'); $st->execute([$nome]); return (int) $st->fetchColumn(); };
$jac = $pid('JOSÉ ALMIR CURCIOL'); $juan = $pid('JUAN WILLIAN DE OLIVEIRA');
$benef = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
$parcJuan = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
$cfgAntes = FinanceiroConfiguracao::find(FinanceiroConfiguracao::ID_UNICO)->toArray();
TTransaction::close();

$f = Fixtures::criar();
$cc = (int) $f['conta_caixa_id'];
$req = (int) $f['requisicao_id'];
$arquivos = [];
try {
    $lf = LevantamentoFinanceiroService::gerar($f['etapa3_id'], ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $benef, 'data_levantamento' => '2026-02-10', 'valor_levantado' => 12019.57,
        'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $parcJuan, 'data_distribuicao' => null, 'conta_caixa_id' => $cc,
        'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S', 'vencimento_cliente' => '2026-02-15',
        'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8]);
    $reps = q('SELECT conta_pagar_id, pessoa_id FROM repasse_colaborador WHERE conta_receber_id = ' . (int) $lf->conta_receber_id . ' ORDER BY pessoa_id');
    igual('4 repasses gerados', 4, count($reps));
    $contaJac = (int) array_values(array_filter($reps, function ($r) use ($jac) { return (int) $r['pessoa_id'] === $jac; }))[0]['conta_pagar_id'];
    $contaJuan = (int) array_values(array_filter($reps, function ($r) use ($juan) { return (int) $r['pessoa_id'] === $juan; }))[0]['conta_pagar_id'];

    // ---------- listagem: filtro pela requisicao (cortina -> onSearch -> sessao) ----------
    post(['requisicao_id' => (string) $req, 'situacao' => 'todos', 'periodo_tipo' => 'recebimento', 'data_de' => '01/02/2026', 'data_ate' => '28/02/2026']);
    saida(function () { (new RepasseColaboradorList([]))->onSearch($_POST); });
    $filtros = TSession::getValue('RepasseColaboradorList_filtros');
    ok('filtros da cortina vao para a sessao (datas no formato do banco, "todos" = sem situacao)',
        $filtros['requisicao_id'] === $req && $filtros['situacao'] === '' && $filtros['data_de'] === '2026-02-01' && $filtros['data_ate'] === '2026-02-28', json_encode($filtros));
    $html = saida(function () { (new RepasseColaboradorList([]))->show(); });
    igual('listagem: sem erro', '', erro($html));
    igual('listagem: 4 linhas marcaveis', 4, substr_count($html, "class='rc-sel'"));
    ok('listagem: total a pagar 1.856,80', strpos($html, 'R$ 1.856,80') !== false);
    ok('listagem: descreve os filtros', strpos($html, 'Requisição #' . $req) !== false && strpos($html, 'Recebimento: 01/02/2026 a 28/02/2026') !== false);
    $cortina = saida(function () { RepasseColaboradorList::onShowCurtainFilters([]); });
    ok('cortina de filtros abre com a requisicao preenchida', erro($cortina) === '' && strpos($cortina, 'Filtros de repasses') !== false, erro($cortina));

    // ---------- janela de pagamento: dois colaboradores e recusado; um colaborador paga ----------
    $janela = saida(function () use ($contaJac) { (new RepasseColaboradorPagarForm(['ids' => (string) $contaJac]))->show(); });
    ok('janela mostra o total a pagar', strpos($janela, 'Pagar R$ 464,20') !== false && erro($janela) === '', erro($janela));
    $hoje = date('d/m/Y');
    post(['ids' => "{$contaJac},{$contaJuan}", 'conta_caixa_id' => (string) $cc, 'tipo_pagamento_id' => (string) TipoPagamento::TRANSFERENCIA, 'data_pagamento' => $hoje, 'compensado' => '', 'historico' => 'TESTE LEVANTAMENTO']);
    $s = saida(function () { (new RepasseColaboradorPagarForm($_POST))->onPagar($_POST); });
    ok('dois colaboradores no mesmo lote e recusado', strpos(erro($s), 'único colaborador') !== false, erro($s));
    post(['ids' => (string) $contaJac, 'conta_caixa_id' => (string) $cc, 'tipo_pagamento_id' => (string) TipoPagamento::TRANSFERENCIA, 'data_pagamento' => $hoje, 'compensado' => '', 'historico' => 'TESTE LEVANTAMENTO']);
    $s = saida(function () { (new RepasseColaboradorPagarForm($_POST))->onPagar($_POST); });
    igual('pagar pela janela: sem erro', '', erro($s));
    $x = q('SELECT x.lote_pagamento_id, x.compensado, x.saida_valor FROM lancamento l JOIN extrato x ON x.id = l.extrato_id WHERE l.conta_id = ' . $contaJac);
    ok('pagou no lote, sem compensar, 464,20', count($x) === 1 && $x[0]['lote_pagamento_id'] && $x[0]['compensado'] === 'N' && round((float) $x[0]['saida_valor'], 2) === 464.2, json_encode($x));
    $lote = (int) $x[0]['lote_pagamento_id'];

    // ---------- extrato: filtros por lote, colaborador, requisicao e processo ----------
    $contarExtrato = function (array $campos) {
        post($campos);
        saida(function () { (new ExtratoList([]))->onSearch([]); });
        TTransaction::open('escritorio');
        $crit = new TCriteria();
        foreach ((array) TSession::getValue('ExtratoList_filters') as $flt) { $crit->add($flt); }
        $n = (new TRepository('Extrato'))->count($crit);
        TTransaction::close();
        return $n;
    };
    igual('extrato: filtro pelo lote', 1, $contarExtrato(['lote_filtro' => (string) $lote]));
    igual('extrato: colaborador + requisicao', 1, $contarExtrato(['pessoa_filtro_id' => (string) $jac, 'requisicao_filtro' => (string) $req]));
    igual('extrato: requisicao (recebimento + pagamento do repasse)', 2, $contarExtrato(['requisicao_filtro' => (string) $req]));
    $proc = q('SELECT COALESCE(p.numero_cnj_numero, p.numero_outro) n FROM conta c JOIN processo p ON p.id = c.processo_id WHERE c.id = ' . (int) $lf->conta_receber_id);
    if ($proc && $proc[0]['n']) {
        ok('extrato: processo (com mascara) acha as linhas', $contarExtrato(['processo_filtro' => $proc[0]['n'], 'requisicao_filtro' => (string) $req]) === 2);
    }
    $semColunas = saida(function () { (new ExtratoList([]))->onShowCurtainFilters([]); });
    ok('extrato: cortina com os novos filtros', strpos($semColunas, 'pessoa_filtro_id') !== false && strpos($semColunas, 'lote_filtro') !== false);

    // ---------- quadros na conta a receber, na conta do repasse e no Ver financeiro ----------
    $cr = (int) $lf->conta_receber_id;
    $v = saida(function () use ($cr) { (new ContaFormView(['key' => $cr, 'id' => $cr]))->show(); });
    ok('conta a receber mostra os repasses aos colaboradores', strpos($v, 'Repasses aos colaboradores') !== false && substr_count($v, 'Abrir a conta a pagar') === 4 && strpos($v, 'lote nº ' . $lote) !== false, erro($v));
    $v = saida(function () use ($contaJac) { (new ContaFormView(['key' => $contaJac, 'id' => $contaJac]))->show(); });
    ok('conta do repasse mostra a origem', strpos($v, 'Gerada pelo recebimento') !== false && strpos($v, 'abrir a conta a receber #' . $cr) !== false, erro($v));
    $carregar = new ReflectionMethod('LevantamentoFinanceiroView', 'carregarFicha'); $carregar->setAccessible(true);
    $bloco = new ReflectionMethod('LevantamentoFinanceiroView', 'bloco'); $bloco->setAccessible(true);
    $ver = saida(function () use ($carregar, $bloco, $f) { echo $bloco->invoke(null, $carregar->invoke(null, (int) $f['etapa3_id']))->getContents(); });
    ok('Ver financeiro mostra os repasses aos colaboradores', strpos($ver, 'Repasses aos colaboradores') !== false && substr_count($ver, 'Abrir a conta a pagar') === 4, erro($ver));

    // ---------- relatorio PDF e Excel com os filtros da tela ----------
    $dados = RepasseColaboradorRelatorioService::dados(['requisicao_id' => $req, 'situacao' => '']);
    igual('relatorio: 4 colaboradores', 4, count($dados['grupos']));
    igual('relatorio: aberto 1.392,60 e pago 464,20', [1392.6, 464.2], [$dados['aberto'], $dados['pago']]);
    $pdf = RepasseColaboradorRelatorioService::gerarPdf(['requisicao_id' => $req, 'situacao' => '']); $arquivos[] = $pdf['arquivo'];
    ok('relatorio PDF gerado', is_file($pdf['arquivo']) && substr(file_get_contents($pdf['arquivo']), 0, 4) === '%PDF' && preg_match('/^Repasses_colaboradores_\d{8}\.pdf$/', $pdf['nome']));
    $xls = RepasseColaboradorRelatorioService::gerarXls(['pessoa_id' => $jac, 'situacao' => 'pago', 'lote_id' => $lote]); $arquivos[] = $xls['arquivo'];
    ok('relatorio Excel gerado', is_file($xls['arquivo']) && filesize($xls['arquivo']) > 0);
    $s = saida(function () { RepasseColaboradorList::onRelatorioPdf([]); });
    ok('botao do relatorio PDF baixa o arquivo', strpos($s, '__adianti_download_file') !== false || strpos($s, 'download.php') !== false, substr($s, 0, 200));

    // ---------- estornar o lote pela tela ----------
    $s = saida(function () use ($lote) { RepasseColaboradorList::onConfirmarEstornoLote(['lote_id' => $lote]); });
    ok('estornar lote pede confirmacao', strpos($s, 'Estornar o lote de pagamento nº ' . $lote) !== false);
    $s = saida(function () use ($lote) { RepasseColaboradorList::onEstornarLote(['lote_id' => $lote]); });
    igual('estornar lote: sem erro', '', erro($s));
    igual('estornar lote: conta volta a ficar a pagar e o lote some', [[null], 0], [array_column(q('SELECT dt_pagamento FROM lancamento WHERE conta_id = ' . $contaJac), 'dt_pagamento'), count(q('SELECT 1 FROM lote_pagamento WHERE id = ' . $lote))]);

    // ---------- configuracao do repasse ----------
    $cfgPost = ['id' => '1', 'pct_nf_padrao' => LevantamentoFinanceiroForm::pctTexto($cfgAntes['pct_nf_padrao']), 'repasse_dias_vencimento' => 'x',
                'repasse_categoria_conta_id' => (string) $cfgAntes['repasse_categoria_conta_id'], 'repasse_tipo_documento_id' => (string) $cfgAntes['repasse_tipo_documento_id']];
    post($cfgPost);
    $s = saida(function () { (new FinanceiroConfiguracaoForm([]))->onSave($_POST); });
    ok('configuracao: dias invalidos recusados', strpos(erro($s), 'Vencimento do repasse') !== false, erro($s));
    $cfgPost['repasse_dias_vencimento'] = (string) (int) $cfgAntes['repasse_dias_vencimento'];
    post($cfgPost);
    $s = saida(function () { (new FinanceiroConfiguracaoForm([]))->onSave($_POST); });
    igual('configuracao: salva (mesmos valores)', '', erro($s));
    $depois = q('SELECT pct_nf_padrao, repasse_dias_vencimento, repasse_categoria_conta_id, repasse_tipo_documento_id FROM financeiro_configuracao WHERE id = 1')[0];
    igual('configuracao: valores mantidos', [(float) $cfgAntes['pct_nf_padrao'], (int) $cfgAntes['repasse_dias_vencimento'], (int) $cfgAntes['repasse_categoria_conta_id'], (int) $cfgAntes['repasse_tipo_documento_id']],
        [(float) $depois['pct_nf_padrao'], (int) $depois['repasse_dias_vencimento'], (int) $depois['repasse_categoria_conta_id'], (int) $depois['repasse_tipo_documento_id']]);
    $s = saida(function () { (new FinanceiroConfiguracaoForm([]))->onEdit([]); });
    igual('configuracao: tela abre', '', erro($s));
} finally {
    foreach ($arquivos as $a) { if (is_file($a)) { unlink($a); } }
    TSession::setValue('RepasseColaboradorList_filtros', null);
    Fixtures::limpar($f);
}
fim();
