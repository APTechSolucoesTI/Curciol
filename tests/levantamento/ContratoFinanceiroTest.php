<?php
// tests/levantamento/ContratoFinanceiroTest.php
// Gerar financeiro pelo contrato: colaboradores da aba Profissionais (valor = disponivel x %), divisao gravada em cada
// parcela gerada, conta-caixa prevista guardada na conta e sugerida na quitacao, repasses no recebimento,
// e uma geracao nova nao sobrescreve a divisao das parcelas anteriores (pagamento solidario).
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
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$pid = function (string $nome) { $st = TTransaction::get()->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?, NFC) ORDER BY id LIMIT 1'); $st->execute([$nome]); return (int) $st->fetchColumn(); };
$jac = $pid('JOSÉ ALMIR CURCIOL'); $juan = $pid('JUAN WILLIAN DE OLIVEIRA'); $curciol = RepasseColaboradorService::pessoaDoEscritorio(1);
TTransaction::close();

$f = Fixtures::criar();
$cc = (int) $f['conta_caixa_id'];
$ct = (int) $f['contrato_id'];
try {
    x("UPDATE contrato SET area_id = (SELECT MIN(id) FROM area) WHERE id = {$ct}");
    x("UPDATE contrato_pagamento_parcela SET valor = 1000, numero_parcelas = 2, data_pagamento = '2026-03-10', status_contrato_pagamento_id = 1 WHERE contrato_id = {$ct}");
    $parcelaContrato = (int) q("SELECT id FROM contrato_pagamento_parcela WHERE contrato_id = {$ct} ORDER BY id LIMIT 1")[0]['id'];

    // ---------- 1) botao da parcela do contrato: colaboradores da aba Profissionais, valor = disponivel x % ----------
    $s = saida(function () use ($parcelaContrato) { ContratoFormView::onAddFinanceiro(['contrato_parcela_id' => $parcelaContrato]); });
    igual('abrir gerar financeiro do contrato: sem erro', '', erro($s));
    $u = html_entity_decode(urldecode($s));
    preg_match('/profissionais_json=(\[[^\]]*\])/', $u, $mp);
    preg_match('/valores_profissionais_json=(\[[^\]]*\])/', $u, $mv);
    preg_match('/repasses_profissionais_json=(\[[^\]]*\])/', $u, $mr);
    $pessoas = json_decode($mp[1] ?? '[]', true); $valores = json_decode($mv[1] ?? '[]', true); $pcts = json_decode($mr[1] ?? '[]', true);
    ok('colaboradores vem do contrato', count($pessoas) > 0 && count($pessoas) === count($valores), $u);
    $esperado = array_map(function ($p) { return round(1000 * (float) $p / 100, 2); }, $pcts);
    igual('valor de cada colaborador = 1.000 x % do contrato', $esperado, array_map(function ($v) { return round((float) $v, 2); }, $valores));

    // ---------- 2) gerar: 2 parcelas de 500, Curciol 30% / Jac 50% / Juan 20%, conta-caixa prevista ----------
    $gerar = function (array $profs, int $parcelas, string $valor, ?int $caixa, ?int $parcelaCt) use ($ct, $f) {
        post(['contrato_id' => $ct, 'escritorio_id' => $f['escritorio_id'], 'contrato_parcela_id' => $parcelaCt, 'categoria_conta_id' => 20, 'pessoa_id' => $f['cliente_id'],
              'descricao' => 'TESTE LEVANTAMENTO contrato - cliente.', 'valor' => $valor, 'dt_vencimento' => '10/03/2026', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA,
              'numero_parcelas' => (string) $parcelas, 'pagamento_solidario' => 'S', 'conta_caixa_id' => $caixa ? (string) $caixa : '',
              'coluna' => array_map('strval', array_column($profs, 0)), 'valor_profissional' => array_column($profs, 1), 'valor_repasse' => array_column($profs, 2)]);
        return saida(function () { (new ModalContratoGerarFinanceiro($_POST))->onGerar($_POST); });
    };
    $s = $gerar([[$curciol, '300,00', '30,00'], [$jac, '500,00', '50,00'], [$juan, '200,00', '20,00']], 2, '1.000,00', $cc, $parcelaContrato);
    igual('gerar: sem erro', '', erro($s));
    $conta = q("SELECT id, conta_caixa_id, quitada, tipo_conta_id FROM conta WHERE contrato_id = {$ct} AND requisicao_pagamento_id IS NULL");
    ok('uma conta a receber em aberto com a conta-caixa prevista', count($conta) === 1 && (int) $conta[0]['conta_caixa_id'] === $cc && $conta[0]['quitada'] === 'N' && (string) $conta[0]['tipo_conta_id'] === (string) TipoConta::RECEBER, json_encode($conta));
    $contaId = (int) $conta[0]['id'];
    $lancs = q("SELECT id, parcela, valor FROM lancamento WHERE conta_id = {$contaId} ORDER BY parcela");
    igual('2 parcelas de 500', [500.0, 500.0], array_map(function ($l) { return round((float) $l['valor'], 2); }, $lancs));
    $div = function (int $lancId) { return array_map(function ($r) { return [(int) $r['pessoa_id'], round((float) $r['valor'], 2)]; }, q("SELECT pessoa_id, valor FROM lancamento_profissional WHERE lancamento_id = {$lancId} ORDER BY id")); };
    igual('parcela 1: divisao gravada (150 / 250 / 100)', [[$curciol, 150.0], [$jac, 250.0], [$juan, 100.0]], $div((int) $lancs[0]['id']));
    igual('parcela 2: divisao gravada (150 / 250 / 100)', [[$curciol, 150.0], [$jac, 250.0], [$juan, 100.0]], $div((int) $lancs[1]['id']));
    igual('parcela do contrato marcada como gerada', 3, (int) q("SELECT status_contrato_pagamento_id s FROM contrato_pagamento_parcela WHERE id = {$parcelaContrato}")[0]['s']);

    // ---------- 3) quitar: conta-caixa vem sugerida; o recebimento gera os repasses (sem o escritorio) ----------
    $L1 = (int) $lancs[0]['id'];
    $s = saida(function () use ($L1) { (new ModalQuitarParcela(['lancamento_id' => $L1]))->onShow(['lancamento_id' => $L1]); });
    ok('quitacao sugere a conta-caixa prevista', strpos($s, "tform_send_data('form_ModalQuitarParcela', 'conta_caixa_id', '{$cc}'") !== false, substr($s, 0, 300));
    post(['id' => $L1, 'conta_caixa_id' => $cc, 'acrescimo' => '0,00', 'desconto' => '0,00', 'distribuicao_ajuste' => 'T', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA,
          'dt_pagamento' => '10/03/2026', 'compensado' => 'S', 'historico' => 'TESTE LEVANTAMENTO quitacao contrato', 'valor' => '500,00', 'valor_total' => '500', 'dt_vencimento' => '10/03/2026']);
    $s = saida(function () use ($L1) { (new ModalQuitarParcela(['key' => $L1, 'id' => $L1]))->onPagar(['id' => $L1, 'key' => $L1, 'confirmar_profissional_negativo' => null]); });
    igual('quitar parcela do contrato: sem erro', '', erro($s));
    $rep = q("SELECT pessoa_id, valor FROM repasse_colaborador WHERE lancamento_receber_id = {$L1} ORDER BY pessoa_id");
    igual('recebimento gera repasse para Jac (250) e Juan (100), sem o escritorio',
        [[$jac, 250.0], [$juan, 100.0]], array_map(function ($r) { return [(int) $r['pessoa_id'], round((float) $r['valor'], 2)]; }, $rep));

    // ---------- 4) nova geracao no mesmo contrato (solidario) nao mexe na divisao das parcelas anteriores ----------
    $s = $gerar([[$curciol, '250,00', '50,00'], [$jac, '250,00', '50,00']], 1, '500,00', null, null);
    igual('segunda geracao: sem erro', '', erro($s));
    $lancs2 = q("SELECT id, parcela FROM lancamento WHERE conta_id = {$contaId} ORDER BY parcela");
    igual('a conta ganhou a parcela 3', [1, 2, 3], array_map(function ($l) { return (int) $l['parcela']; }, $lancs2));
    igual('parcela 2 manteve a divisao (com Juan)', [[$curciol, 150.0], [$jac, 250.0], [$juan, 100.0]], $div((int) $lancs2[1]['id']));
    igual('parcela 3 tem a divisao nova', [[$curciol, 250.0], [$jac, 250.0]], $div((int) $lancs2[2]['id']));
    igual('conta-caixa prevista em branco na nova geracao fica vazia', null, q("SELECT conta_caixa_id FROM conta WHERE id = {$contaId}")[0]['conta_caixa_id']);
} finally {
    Fixtures::limpar($f);
}
fim();
