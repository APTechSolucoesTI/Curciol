<?php
// tests/levantamento/ExtratoParametrosTest.php
// Extrato: janela unica de parametros/filtros (Buscar recarrega a lista), processo pai traz os filhos,
// pessoa, situacao e periodo; relatorio PDF/Excel no layout da conta-caixa com saldos; lista sem Lote e sem botoes antigos.
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
/** linhas do extrato com os parametros (como a listagem aplica) */
function contar(array $p): array
{
    TTransaction::open('escritorio');
    $crit = new TCriteria();
    foreach (ExtratoParametrosService::filtros($p) as $f) { $crit->add($f); }
    $r = array_map(function ($x) { return (int) $x->id; }, (new TRepository('Extrato'))->load($crit, false) ?: []);
    TTransaction::close();
    sort($r);
    return $r;
}

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$unidade = (int) TTransaction::get()->query('SELECT system_unit_id FROM escritorio WHERE id = 1')->fetchColumn();
TSession::setValue('userunitid', $unidade); TSession::setValue('userunitids', [$unidade]);
$benef = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
$parcJuan = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
$pid = function (string $nome) { $st = TTransaction::get()->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?, NFC) ORDER BY id LIMIT 1'); $st->execute([$nome]); return (int) $st->fetchColumn(); };
$jac = $pid('JOSÉ ALMIR CURCIOL');
TTransaction::close();
$hoje = date('Y-m-d');

$f = Fixtures::criar();
$cc = (int) $f['conta_caixa_id'];
$arquivos = [];
try {
    $lf = LevantamentoFinanceiroService::gerar($f['etapa3_id'], ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $benef, 'data_levantamento' => $hoje, 'valor_levantado' => 12019.57,
        'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $parcJuan, 'data_distribuicao' => null, 'conta_caixa_id' => $cc,
        'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S', 'vencimento_cliente' => $hoje,
        'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8]);
    $repasses = array_map('intval', array_column(q('SELECT conta_pagar_id FROM repasse_colaborador WHERE conta_receber_id = ' . (int) $lf->conta_receber_id), 'conta_pagar_id'));
    // paga os repasses em lote, sem compensar
    LoteFinanceiroService::quitar($repasses, (string) TipoConta::PAGAR, ['data_pagamento' => $hoje, 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'N']);
    $todas = array_map('intval', array_column(q("SELECT id FROM extrato WHERE conta_caixa_id = {$cc} ORDER BY id"), 'id'));
    $recebimento = array_map('intval', array_column(q("SELECT x.id FROM extrato x JOIN lancamento l ON l.id = x.lancamento_id WHERE l.conta_id = " . (int) $lf->conta_receber_id), 'id'));
    igual('massa: recebimento (1) + 4 pagamentos de repasse', 5, count($todas));

    // ---------- normalizar ----------
    $p = ExtratoParametrosService::normalizar(['conta_caixa_ids' => [(string) $cc], 'situacao' => 'S', 'periodo_tipo' => 'compensacao', 'data_de' => date('d/m/Y'), 'data_ate' => $hoje, 'entrada_valor' => '1.234,50']);
    igual('normalizar: datas e valor', [$hoje, $hoje, 1234.5], [$p['data_de'], $p['data_ate'], $p['entrada_valor']]);
    lanca('data inicial maior que a final', function () { ExtratoParametrosService::normalizar(['data_de' => '10/10/2026', 'data_ate' => '01/10/2026']); }, 'maior que a final');

    // ---------- filtros ----------
    $base = ['conta_caixa_ids' => [$cc]];
    igual('conta-caixa: todas as 5 linhas', $todas, contar(ExtratoParametrosService::normalizar($base)));
    igual('so compensados: o recebimento', $recebimento, contar(ExtratoParametrosService::normalizar($base + ['situacao' => 'S'])));
    igual('so nao compensados: os 4 repasses', 4, count(contar(ExtratoParametrosService::normalizar($base + ['situacao' => 'N']))));
    igual('periodo de lancamento ate ontem: nada', [], contar(ExtratoParametrosService::normalizar($base + ['periodo_tipo' => 'lancamento', 'data_ate' => date('Y-m-d', strtotime('-1 day'))])));
    igual('pessoa (colaborador Jac): 1 linha', 1, count(contar(ExtratoParametrosService::normalizar($base + ['pessoa_id' => $jac]))));
    $procConta = (int) q('SELECT processo_id FROM conta WHERE id = ' . (int) $lf->conta_receber_id)[0]['processo_id'];
    ok('conta do levantamento esta no processo incidente', $procConta === (int) $f['processo_id'], (string) $procConta);
    igual('processo pai (raiz) traz o filho: 5 linhas', $todas, contar(ExtratoParametrosService::normalizar($base + ['processo_id' => $f['processo_raiz_id']])));
    igual('processo filho direto: 5 linhas', $todas, contar(ExtratoParametrosService::normalizar($base + ['processo_id' => $f['processo_id']])));
    TTransaction::open('escritorio');
    $outro = (int) TTransaction::get()->query('SELECT MIN(id) FROM processo WHERE id NOT IN (' . (int) $f['processo_raiz_id'] . ',' . (int) $f['processo_id'] . ')')->fetchColumn();
    $txt = ExtratoParametrosService::descrever(ExtratoParametrosService::normalizar($base + ['processo_id' => $f['processo_raiz_id'], 'situacao' => 'N']));
    TTransaction::close();
    igual('outro processo: nada desta conta-caixa', [], contar(ExtratoParametrosService::normalizar($base + ['processo_id' => $outro])));
    ok('descricao cita os filhos e a situacao', strpos($txt, '(e 1 processo(s) filho(s))') !== false && strpos($txt, 'Não compensados') !== false, $txt);

    // ---------- janela: Buscar grava os parametros e recarrega a lista ----------
    $janela = saida(function () { (new ExtratoParametrosForm([]))->show(); });
    ok('janela abre com os campos', erro($janela) === '' && strpos($janela, 'processo_id') !== false && strpos($janela, 'Relatório (PDF)') !== false, erro($janela));
    post(['conta_caixa_ids' => [(string) $cc], 'situacao' => 'N', 'periodo_tipo' => 'compensacao', 'data_de' => '', 'data_ate' => '', 'tipo_extrato_ids' => [], 'historico' => '',
          'entrada_valor' => '', 'saida_valor' => '', 'pessoa_id' => '', 'processo_id' => (string) $f['processo_raiz_id']]);
    $s = saida(function () { (new ExtratoParametrosForm($_POST))->onBuscar($_POST); });
    igual('buscar: sem erro', '', erro($s));
    $sess = ExtratoParametrosService::atuais();
    igual('buscar: parametros na sessao', [[$cc], 'N', (int) $f['processo_raiz_id']], [$sess['conta_caixa_ids'], $sess['situacao'], (int) $sess['processo_id']]);
    $lista = saida(function () { (new ExtratoList([]))->show(); });
    igual('lista: sem erro', '', erro($lista));
    ok('lista: botao unico, sem os antigos e sem coluna Lote', strpos($lista, 'Parâmetros e filtros') !== false && strpos($lista, 'Trocar parâmetros') === false
        && strpos($lista, 'Gerar relatório de Conta Caixa') === false && strpos($lista, '>Lote<') === false);
    igual('lista: 4 linhas (nao compensadas do processo)', 4, substr_count($lista, 'Repasse a ') + substr_count($lista, 'PAGAMENTO - '));
    saida(function () { (new ExtratoList([]))->onClearFilters([]); });
    ok('limpar filtros zera os parametros', ExtratoParametrosService::atuais() === []);

    // ---------- relatorio ----------
    $pRel = ExtratoParametrosService::normalizar(['conta_caixa_ids' => [$cc], 'periodo_tipo' => 'compensacao', 'data_de' => date('Y-m-01'), 'data_ate' => $hoje]);
    $d = ExtratoRelatorioService::dados($pRel);
    $ent = round((float) q('SELECT SUM(entrada_valor) s FROM extrato WHERE id IN (' . implode(',', $recebimento) . ')')[0]['s'], 2);
    igual('relatorio: 1 conta-caixa, saldo anterior 0 e posterior = recebido compensado', [1, 0.0, $ent], [count($d['contas']), $d['contas'][0]['saldo_anterior'], $d['contas'][0]['saldo_posterior']]);
    igual('relatorio: so a linha compensada no periodo de compensacao', 1, count($d['contas'][0]['linhas']));
    $pdf = ExtratoRelatorioService::gerarPdf($pRel); $arquivos[] = $pdf['arquivo'];
    ok('relatorio PDF', is_file($pdf['arquivo']) && substr(file_get_contents($pdf['arquivo']), 0, 4) === '%PDF');
    $xls = ExtratoRelatorioService::gerarXls(ExtratoParametrosService::normalizar(['conta_caixa_ids' => [$cc], 'situacao' => 'N', 'periodo_tipo' => 'lancamento', 'data_de' => $hoje])); $arquivos[] = $xls['arquivo'];
    ok('relatorio Excel (periodo por lancamento)', is_file($xls['arquivo']) && filesize($xls['arquivo']) > 0);
    post(['conta_caixa_ids' => [(string) $cc], 'situacao' => '', 'periodo_tipo' => 'compensacao', 'data_de' => date('d/m/Y'), 'data_ate' => date('d/m/Y'), 'tipo_extrato_ids' => [], 'historico' => '', 'entrada_valor' => '', 'saida_valor' => '', 'pessoa_id' => '', 'processo_id' => '']);
    $s = saida(function () { (new ExtratoParametrosForm($_POST))->onRelatorioPdf($_POST); });
    ok('botao do relatorio na janela baixa o arquivo', strpos($s, 'download.php') !== false || strpos($s, '__adianti_download_file') !== false, erro($s) ?: substr($s, 0, 200));
    if (preg_match('/file=([^&\'"]+)/', $s, $mm)) { $arquivos[] = urldecode($mm[1]); }
} finally {
    foreach ($arquivos as $a) { if (is_file($a)) { unlink($a); } }
    TSession::setValue(ExtratoParametrosService::SESSAO, null);
    Fixtures::limpar($f);
}
fim();
