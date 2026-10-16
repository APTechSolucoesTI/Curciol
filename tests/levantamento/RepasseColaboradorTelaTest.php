<?php
// tests/levantamento/RepasseColaboradorTelaTest.php
// Telas dos repasses a colaboradores: listagem so de consulta (filtros, totais), pagamento pela acao em lote de Contas a pagar
// (dois colaboradores no mesmo lote), desfazer, relatorio PDF/Excel, quadros na conta e no Ver financeiro, configuracao.
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

    // ---------- listagem: processo pai (raiz) na cortina traz os repasses do processo filho ----------
    post(['processo_id' => (string) $f['processo_raiz_id'], 'contrato_id' => '', 'situacao' => 'todos', 'periodo_tipo' => 'recebimento', 'data_de' => '01/02/2026', 'data_ate' => '28/02/2026']);
    saida(function () { (new RepasseColaboradorList([]))->onSearch($_POST); });
    $filtros = TSession::getValue('RepasseColaboradorList_filtros');
    ok('filtros da cortina vao para a sessao (processo, datas no formato do banco, "todos" = sem situacao)',
        $filtros['processo_id'] === (int) $f['processo_raiz_id'] && $filtros['situacao'] === '' && $filtros['data_de'] === '2026-02-01' && $filtros['data_ate'] === '2026-02-28' && !isset($filtros['requisicao_id']), json_encode($filtros));
    $html = saida(function () { (new RepasseColaboradorList([]))->show(); });
    igual('listagem: sem erro', '', erro($html));
    igual('listagem: 4 repasses, sem selecao nem pagamento (so consulta)', [4, 0, 0], [substr_count($html, "class='rc-origem'"), substr_count($html, 'rc-sel'), substr_count($html, 'Pagar selecionadas')]);
    ok('listagem: filtros no cabecalho das colunas', strpos($html, 'datagrid-header-filter-row') !== false && strpos($html, 'origem_col') !== false && strpos($html, 'tdatagrid_init_header_popover_filters') !== false);
    ok('listagem: total a pagar 1.856,80', strpos($html, 'R$ 1.856,80') !== false);
    ok('listagem: descreve os filtros', strpos($html, 'Processo: ') !== false && strpos($html, '(e filhos)') !== false && strpos($html, 'Recebimento: 01/02/2026 a 28/02/2026') !== false);
    $cortina = saida(function () { RepasseColaboradorList::onShowCurtainFilters([]); });
    ok('cortina: processo e contrato para escolher, sem requisicao', erro($cortina) === '' && strpos($cortina, 'Filtros de repasses') !== false
        && strpos($cortina, 'name="processo_id"') !== false && strpos($cortina, 'name="contrato_id"') !== false && strpos($cortina, 'requisicao_id') === false, erro($cortina));
    // filtros do cabecalho: colaborador e origem
    saida(function () use ($jac) { RepasseColaboradorList::onSearchColunas(['colaborador_col' => (string) $jac, 'origem_col' => '', 'recebido_col' => '', 'vencimento_col' => '', 'valor_col' => '', 'situacao_col' => '']); });
    $html = saida(function () { (new RepasseColaboradorList([]))->show(); });
    igual('cabecalho: colaborador deixa so o repasse dele', 1, substr_count($html, "class='rc-origem'"));
    saida(function () { RepasseColaboradorList::onSearchColunas(['colaborador_col' => '', 'origem_col' => 'NAO EXISTE XYZ', 'recebido_col' => '', 'vencimento_col' => '', 'valor_col' => '464,20', 'situacao_col' => '']); });
    $html = saida(function () { (new RepasseColaboradorList([]))->show(); });
    igual('cabecalho: origem que nao existe nao traz nada', 0, substr_count($html, "class='rc-origem'"));
    saida(function () { RepasseColaboradorList::onSearchColunas(['colaborador_col' => '', 'origem_col' => 'TESTE LEVANTAMENTO', 'recebido_col' => '', 'vencimento_col' => '', 'valor_col' => '464,20', 'situacao_col' => 'aberto']); });
    $html = saida(function () { (new RepasseColaboradorList([]))->show(); });
    igual('cabecalho: origem + valor + situacao', 4, substr_count($html, "class='rc-origem'"));
    TSession::setValue('RepasseColaboradorList_colunas', null);

    // ---------- pagamento pela acao em lote de Contas a pagar: dois colaboradores no mesmo lote ----------
    TSession::setValue('userid', 1);
    $s = saida(function () use ($contaJac, $contaJuan) { ContaPagarList::onQuitarSelecionadas(['lote_sel' => [(string) $contaJac, (string) $contaJuan]]); });
    ok('Quitar selecionadas abre a janela do lote', strpos($s, 'LoteQuitarForm') !== false, substr($s, 0, 300));
    $janela = saida(function () use ($contaJac, $contaJuan) { (new LoteQuitarForm(['ids' => "{$contaJac},{$contaJuan}", 'tipo' => (string) TipoConta::PAGAR]))->show(); });
    ok('janela soma as duas contas', strpos($janela, 'Quitar R$ 928,40') !== false && erro($janela) === '', erro($janela));
    $hoje = date('d/m/Y');
    post(['ids' => "{$contaJac},{$contaJuan}", 'tipo' => (string) TipoConta::PAGAR, 'conta_caixa_id' => (string) $cc, 'tipo_pagamento_id' => (string) TipoPagamento::TRANSFERENCIA, 'data_pagamento' => $hoje, 'compensado' => '', 'historico' => 'TESTE LEVANTAMENTO']);
    $s = saida(function () { (new LoteQuitarForm($_POST))->onQuitar($_POST); });
    igual('quitar pela janela: sem erro', '', erro($s));
    $x = q('SELECT l.conta_id, x.lote_pagamento_id, x.compensado, x.saida_valor FROM lancamento l JOIN extrato x ON x.id = l.extrato_id WHERE l.conta_id IN (' . $contaJac . ',' . $contaJuan . ') ORDER BY l.conta_id');
    ok('uma linha por conta, no mesmo lote, sem compensar', count($x) === 2 && $x[0]['lote_pagamento_id'] && $x[0]['lote_pagamento_id'] === $x[1]['lote_pagamento_id'] && $x[0]['compensado'] === 'N' && round((float) $x[0]['saida_valor'], 2) === 464.2, json_encode($x));
    $lote = (int) $x[0]['lote_pagamento_id'];

    // ---------- quadros na conta a receber, na conta do repasse e no Ver financeiro ----------
    $cr = (int) $lf->conta_receber_id;
    $v = saida(function () use ($cr) { (new ContaFormView(['key' => $cr, 'id' => $cr]))->show(); });
    ok('conta a receber mostra os repasses aos colaboradores', strpos($v, 'Repasses aos colaboradores') !== false && substr_count($v, 'Abrir a conta a pagar') === 4 && substr_count($v, 'Pago em ') === 2, erro($v));
    $v = saida(function () use ($contaJac) { (new ContaFormView(['key' => $contaJac, 'id' => $contaJac]))->show(); });
    ok('conta do repasse mostra a origem', strpos($v, 'Gerada pelo recebimento') !== false && strpos($v, 'abrir a conta a receber #' . $cr) !== false, erro($v));
    $carregar = new ReflectionMethod('LevantamentoFinanceiroView', 'carregarFicha'); $carregar->setAccessible(true);
    $bloco = new ReflectionMethod('LevantamentoFinanceiroView', 'bloco'); $bloco->setAccessible(true);
    $ver = saida(function () use ($carregar, $bloco, $f) { echo $bloco->invoke(null, $carregar->invoke(null, (int) $f['etapa3_id']))->getContents(); });
    ok('Ver financeiro mostra os repasses aos colaboradores', strpos($ver, 'Repasses aos colaboradores') !== false && substr_count($ver, 'Abrir a conta a pagar') === 4, erro($ver));

    // ---------- relatorio PDF e Excel com os filtros da tela ----------
    $dados = RepasseColaboradorRelatorioService::dados(['requisicao_id' => $req, 'situacao' => '']);
    igual('relatorio: 4 colaboradores', 4, count($dados['grupos']));
    igual('relatorio: aberto 928,40 e pago 928,40', [928.4, 928.4], [$dados['aberto'], $dados['pago']]);
    $pdf = RepasseColaboradorRelatorioService::gerarPdf(['requisicao_id' => $req, 'situacao' => '']); $arquivos[] = $pdf['arquivo'];
    ok('relatorio PDF gerado', is_file($pdf['arquivo']) && substr(file_get_contents($pdf['arquivo']), 0, 4) === '%PDF' && preg_match('/^Repasses_colaboradores_\d{8}\.pdf$/', $pdf['nome']));
    $xls = RepasseColaboradorRelatorioService::gerarXls(['pessoa_id' => $jac, 'situacao' => 'pago']); $arquivos[] = $xls['arquivo'];
    ok('relatorio Excel gerado', is_file($xls['arquivo']) && filesize($xls['arquivo']) > 0);
    $s = saida(function () { RepasseColaboradorList::onRelatorioPdf([]); });
    ok('botao do relatorio PDF baixa o arquivo', strpos($s, '__adianti_download_file') !== false || strpos($s, 'download.php') !== false, substr($s, 0, 200));

    // ---------- desfazer o lote (ultima acao em lote do usuario) pela listagem de Contas a pagar ----------
    $s = saida(function () use ($lote) { ContaPagarList::onConfirmarDesfazerLote(['lote_id' => $lote]); });
    ok('desfazer pede confirmacao', strpos($s, 'Desfazer a quitação do lote nº ' . $lote) !== false, substr(strip_tags($s), 0, 200));
    $s = saida(function () use ($lote) { ContaPagarList::onDesfazerLote(['lote_id' => $lote]); });
    igual('desfazer: sem erro', '', erro($s));
    igual('desfazer: contas voltam a ficar a pagar e o lote fica marcado como desfeito', [[null, null], 1],
        [array_column(q('SELECT dt_pagamento FROM lancamento WHERE conta_id IN (' . $contaJac . ',' . $contaJuan . ')'), 'dt_pagamento'), count(q('SELECT 1 FROM lote_pagamento WHERE id = ' . $lote . ' AND desfeito_em IS NOT NULL'))]);

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
    TSession::setValue('RepasseColaboradorList_colunas', null);
    Fixtures::limpar($f);
}
fim();
