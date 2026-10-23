<?php
// tests/levantamento/ExtratoSelecaoTest.php
// Extrato no padrao das contas (23/10/2026): selecao de todas as paginas (sem as linhas de lote), somas, compensar e
// descompensar as marcadas, acao unica Visualizar/Ver lote, filtros de Entrada/Saida no cabecalho, parametros somando
// com os filtros das colunas (e na faixa), permissao da cortina sem relogar e a cortina do lote com a mesma selecao.
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
/** ids selecionados (metodo protegido do trait) */
function sel(string $classe): array
{
    $m = new ReflectionMethod($classe, 'selIds');
    $m->setAccessible(true);
    $r = $m->invoke(null);
    sort($r);
    return $r;
}
function js(string $t): string { return substr(json_encode($t), 1, -1); }

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$unidade = (int) TTransaction::get()->query('SELECT system_unit_id FROM escritorio WHERE id = 1')->fetchColumn();
TSession::setValue('userunitid', $unidade); TSession::setValue('userunitids', [$unidade]);
$benef = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
$parcJuan = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
TTransaction::close();
$hoje = date('Y-m-d');

$f = Fixtures::criar();
$cc = (int) $f['conta_caixa_id'];
try {
    $lf = LevantamentoFinanceiroService::gerar($f['etapa3_id'], ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $benef, 'data_levantamento' => $hoje, 'valor_levantado' => 12019.57,
        'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $parcJuan, 'data_distribuicao' => null, 'conta_caixa_id' => $cc,
        'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S', 'vencimento_cliente' => $hoje,
        'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8]);
    $repasses = array_map('intval', array_column(q('SELECT conta_pagar_id FROM repasse_colaborador WHERE conta_receber_id = ' . (int) $lf->conta_receber_id), 'conta_pagar_id'));
    $lote = LoteFinanceiroService::quitar($repasses, (string) TipoConta::PAGAR, ['data_pagamento' => $hoje, 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'N', 'historico' => 'TESTE SELECAO']);
    $loteId = (int) $lote->id;
    $recebimento = (int) q("SELECT x.id FROM extrato x JOIN lancamento l ON l.id = x.lancamento_id WHERE l.conta_id = " . (int) $lf->conta_receber_id)[0]['id'];
    $doLote = array_map('intval', array_column(q("SELECT id FROM extrato WHERE lote_pagamento_id = {$loteId} ORDER BY id"), 'id'));
    igual('massa: recebimento compensado + 4 linhas do lote', [4, 'S'], [count($doLote), q("SELECT compensado FROM extrato WHERE id = {$recebimento}")[0]['compensado']]);

    // ---------- permissao da cortina sem sair e entrar ----------
    TSession::setValue('programs', ['ExtratoList' => true]);
    saida(function () { new ExtratoList([]); });
    ok('quem tem o Extrato na sessao ganha a cortina do lote', !empty(TSession::getValue('programs')['LoteExtratoView']));
    TSession::setValue('programs', ['ContaPagarList' => true]);
    saida(function () { new ExtratoList([]); });
    ok('sem o Extrato na sessao nao ganha nada', empty(TSession::getValue('programs')['LoteExtratoView']));

    // ---------- listagem: acao unica, selecao, filtros do cabecalho, botoes ----------
    TSession::setValue(ExtratoParametrosService::SESSAO, ExtratoParametrosService::normalizar(['conta_caixa_ids' => [$cc]]));
    TSession::setValue('ExtratoList_filter_data', null);
    TSession::setValue('ExtratoList_filters', null);
    ExtratoList::selLimpar();
    $html = saida(function () { (new ExtratoList([]))->show(); });
    igual('lista: sem erro', '', erro($html));
    ok('lista: 2 linhas (recebimento + lote) com Ver lote e Visualizar', substr_count($html, 'id="row_') === 2 && substr_count($html, 'LoteExtratoView') >= 1 && strpos($html, 'ExtratoFormView') !== false);
    ok('lista: checkbox de todas as paginas e um checkbox (linha do lote sem)', strpos($html, "id='sel-todas-ExtratoList'") !== false && substr_count($html, 'class="sel-ExtratoList"') === 1, (string) substr_count($html, 'class="sel-ExtratoList"'));
    ok('lista: filtros de Entrada e Saida no cabecalho', strpos($html, 'name="entrada_col"') !== false && strpos($html, 'name="saida_col"') !== false);
    $ths = preg_match('/<thead.*?<\/thead>/s', $html, $m) ? substr_count($m[0], '<th') : -1;
    $filtro = preg_match('/id="datagrid-header-filter-row".*?<\/tr>/s', $html, $m2) ? substr_count($m2[0], '<td') : -2;
    igual('lista: linha de filtros com uma celula por coluna (funil no lugar certo)', $ths, $filtro);
    ok('lista: Limpar filtros antes de Atualizar', strpos($html, 'Limpar filtros') !== false && strpos($html, 'Limpar filtros') < strpos($html, '>Atualizar'));
    ok('lista: parametro na faixa', strpos($html, js('Conta-caixa')) !== false && strpos($html, '__param_conta_caixa_ids') !== false);

    // selecionar todas: so as linhas fora de lote
    saida(function () { ExtratoList::onSelTodas(['marcado' => 1]); });
    igual('todas: so o recebimento (lote fica para a cortina)', [$recebimento], sel('ExtratoList'));
    $barra = saida(function () { ExtratoList::onSelMarcar(['id' => 0]); });
    ok('barra soma entradas e saidas', strpos($barra, js('1 selecionada(s) (todas as páginas)')) !== false && strpos($barra, js('· entradas R$ ')) !== false, $barra);

    // descompensar e compensar as marcadas
    $s = saida(function () { ExtratoList::onDescompensarSelecionadas([]); });
    ok('descompensar pede confirmacao com a quantidade', strpos($s, js('Descompensar 1 linha(s)')) !== false || strpos($s, 'Descompensar 1 linha(s)') !== false, substr($s, 0, 300));
    saida(function () { ExtratoList::onDescompensarConfirmado([]); });
    igual('descompensou e limpou a selecao', ['N', []], [q("SELECT COALESCE(compensado, 'N') c FROM extrato WHERE id = {$recebimento}")[0]['c'], sel('ExtratoList')]);
    saida(function () { ExtratoList::onSelTodas(['marcado' => 1]); });
    $s = saida(function () { ExtratoList::onCompensarSelecionadas([]); });
    ok('compensar abre a janela com as marcadas', strpos($s, 'ModalCompensarExtrato') !== false, substr($s, 0, 300));
    igual('janela recebe as marcadas e a origem', [[$recebimento], 'ExtratoList'], [array_map('intval', TSession::getValue('paramSelecionados')), TSession::getValue('paramSelecionados_origem')]);
    $janela = saida(function () { (new ModalCompensarExtrato([]))->show(); });
    ok('janela mostra quantas e quanto', strpos($janela, '1 linha(s) · entradas R$ ') !== false, erro($janela));
    post(['data_compensacao' => date('d/m/Y')]);
    saida(function () { (new ModalCompensarExtrato($_POST))->onAction($_POST); });
    igual('compensou e limpou a selecao', ['S', []], [q("SELECT compensado FROM extrato WHERE id = {$recebimento}")[0]['compensado'], sel('ExtratoList')]);

    // filtros: Entrada do cabecalho soma com os parametros; trocar filtro desmarca
    saida(function () { ExtratoList::onSelTodas(['marcado' => 1]); });
    $entrada = (float) q("SELECT entrada_valor FROM extrato WHERE id = {$recebimento}")[0]['entrada_valor'];
    post(['entrada_col' => number_format($entrada, 2, '.', ''), 'static' => '1']);
    saida(function () { (new ExtratoList($_POST))->onSearch($_POST); });
    ok('Entrada do cabecalho vira filtro', count((array) TSession::getValue('ExtratoList_filters')) === 1);
    igual('filtro mudou: selecao recomeca', [], sel('ExtratoList'));
    post(['conta_caixa_ids' => [(string) $cc], 'situacao' => '', 'periodo_tipo' => 'compensacao', 'data_de' => '', 'data_ate' => '', 'tipo_extrato_ids' => [], 'historico' => '',
          'entrada_valor' => '', 'saida_valor' => '', 'pessoa_id' => '', 'processo_id' => '']);
    saida(function () { (new ExtratoParametrosForm($_POST))->onBuscar($_POST); });
    ok('Buscar dos parametros mantem os filtros das colunas', count((array) TSession::getValue('ExtratoList_filters')) === 1);
    post(['__filtros_extra' => ['__param_conta_caixa_ids' => ''], '__sem_formulario' => '1', 'static' => '1']);
    saida(function () { (new ExtratoList($_POST))->onSearch($_POST); });
    igual('x da faixa tira so o parametro', [[], 1], [ExtratoParametrosService::atuais()['conta_caixa_ids'], count((array) TSession::getValue('ExtratoList_filters'))]);
    saida(function () { (new ExtratoList([]))->onClearFilters([]); });
    igual('Limpar filtros tira tudo', [[], null], [ExtratoParametrosService::atuais(), TSession::getValue('ExtratoList_filters')]);

    // ---------- cortina do lote ----------
    $html = saida(function () use ($loteId) { (new LoteExtratoView(['lote_id' => $loteId]))->show(); });
    igual('cortina: sem erro', '', erro($html));
    ok('cortina: 4 linhas, selecao, filtros e botoes', substr_count($html, 'class="sel-LoteExtratoView"') === 4 && strpos($html, "id='sel-todas-LoteExtratoView'") !== false
        && strpos($html, 'name="lote_pessoa_col"') !== false && strpos($html, 'Descompensar') !== false && strpos($html, 'Limpar filtros') < strpos($html, '>Atualizar'));
    ok('cortina: cabecalho do lote', strpos($html, 'TESTE SELECAO') !== false && strpos($html, 'Não compensado') !== false);
    saida(function () { LoteExtratoView::onSelTodas(['marcado' => 1]); });
    saida(function () use ($doLote) { LoteExtratoView::onSelMarcar(['id' => $doLote[0], 'marcado' => 0]); });
    igual('cortina: todas menos uma', array_slice($doLote, 1), sel('LoteExtratoView'));
    $s = saida(function () { LoteExtratoView::onCompensarSelecionadas([]); });
    igual('cortina: compensar leva as 3 com a origem', [array_slice($doLote, 1), 'LoteExtratoView'], [array_map('intval', TSession::getValue('paramSelecionados')), TSession::getValue('paramSelecionados_origem')]);
    post(['data_compensacao' => date('d/m/Y')]);
    $s = saida(function () { (new ModalCompensarExtrato($_POST))->onAction($_POST); });
    ok('cortina: depois de compensar volta para a cortina', strpos($s, 'LoteExtratoView') !== false, substr($s, 0, 300));
    igual('cortina: 3 compensadas, 1 nao', [3, 1], [(int) q("SELECT COUNT(*) n FROM extrato WHERE lote_pagamento_id = {$loteId} AND compensado = 'S'")[0]['n'],
        (int) q("SELECT COUNT(*) n FROM extrato WHERE lote_pagamento_id = {$loteId} AND COALESCE(compensado, 'N') <> 'S'")[0]['n']]);
    $html = saida(function () { (new ExtratoList([]))->show(); });
    ok('lista: lote compensado em parte aparece como Parcial', strpos($html, 'Parcial') !== false);

    // filtro de situacao na cortina + descompensar
    post(['lote_compensado_col' => 'S', 'static' => '1']);
    saida(function () { (new LoteExtratoView($_POST))->onSearch($_POST); });
    saida(function () { LoteExtratoView::onSelTodas(['marcado' => 1]); });
    igual('cortina: filtro Compensado = Sim marca as 3', array_slice($doLote, 1), sel('LoteExtratoView'));
    saida(function () { LoteExtratoView::onDescompensarConfirmado([]); });
    igual('cortina: descompensou as 3', 0, (int) q("SELECT COUNT(*) n FROM extrato WHERE lote_pagamento_id = {$loteId} AND compensado = 'S'")[0]['n']);
    saida(function () { (new LoteExtratoView([]))->onClearFilters([]); });
    igual('cortina: limpar filtros tira filtro e selecao', [null, []], [TSession::getValue('LoteExtratoView_filters'), sel('LoteExtratoView')]);

    $L = 'LoteFinanceiroService';
    $L::desfazer($loteId, 1);
} finally {
    TSession::setValue(ExtratoParametrosService::SESSAO, null);
    Fixtures::limpar($f);
}
