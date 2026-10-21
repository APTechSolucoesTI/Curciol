<?php
// tests/levantamento/PagamentoExclusaoSaldoTest.php
// Saldo remanescente (data base opcional, proximo pagamento so pelo botao "Lancar outro pagamento") e exclusao de
// pagamento (bloqueada quando algo vindo dele foi compensado ou quitado; com financeiro, desfaz junto).
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
function valor(string $sql, array $p = []) { TTransaction::open('escritorio'); $st = TTransaction::get()->prepare($sql); $st->execute($p); $r = $st->fetchColumn(); TTransaction::close(); return $r; }
function exec_sql(string $sql, array $p = []): void { TTransaction::open('escritorio'); TTransaction::get()->prepare($sql)->execute($p); TTransaction::close(); }

$dados = function ($f, $compensado = 'N') {
    TTransaction::open('escritorio');
    $b = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
    $p = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
    TTransaction::close();
    return ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $b, 'data_levantamento' => '2026-02-10', 'valor_levantado' => 12019.57,
            'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $p, 'data_distribuicao' => null, 'conta_caixa_id' => $f['conta_caixa_id'],
            'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => $compensado, 'vencimento_cliente' => '2026-02-15',
            'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8,
            'desconto_previdenciario' => 1715.39, 'desconto_assistencial' => 0, 'desconto_irrf' => 0];
};
$E = 'PagamentoExclusaoService';

// ---------- saldo remanescente pela tela ----------
$f = Fixtures::criar();
try {
    $rpc = $f['rpc_id'];
    $base = ['key' => $f['requisicao_id'], "status_{$rpc}" => 5, "etapa3_processo_filho_id_{$rpc}" => $f['processo_id']];
    $tela = null;
    saida(function () use (&$tela, $f) { $tela = new RequisicaoPagamentoVisualizacao(['key' => $f['requisicao_id']]); });

    $msg = saida(function () use ($tela, $base, $rpc, $f) { $tela->onSave($base + [
        "etapa3_id_{$rpc}" => $f['etapa3_id'], "etapa3_numero_ciclo_{$rpc}" => 1, "etapa3_data_deposito_{$rpc}" => '10/01/2026',
        "etapa3_valor_bruto_depositado_{$rpc}" => '13.195,26', "etapa3_desconto_previdenciario_{$rpc}" => '1.715,39',
        "etapa3_possui_saldo_{$rpc}" => 'S', "etapa3_saldo_bruto_{$rpc}" => '2.000,00', "etapa3_data_base_saldo_{$rpc}" => '']); });
    ok('saldo sem data base e aceito', strpos($msg, 'EXCECAO') === false && strpos($msg, 'Data base') === false, $msg);
    igual('saldo gravado sem data', ['S', 2000.0, null], [
        valor('SELECT possui_saldo FROM requisicao_pagamento_etapa3 WHERE id = ?', [$f['etapa3_id']]),
        round((float) valor('SELECT saldo_bruto FROM requisicao_pagamento_etapa3 WHERE id = ?', [$f['etapa3_id']]), 2),
        valor('SELECT data_base_saldo FROM requisicao_pagamento_etapa3 WHERE id = ?', [$f['etapa3_id']]),
    ]);

    $html = saida(function () use ($f) { (new RequisicaoPagamentoVisualizacao(['key' => $f['requisicao_id']]))->show(); });
    ok('aviso de saldo com o botao Lancar outro pagamento', strpos($html, 'Existe saldo remanescente em aberto') !== false && strpos($html, 'Lançar outro pagamento') !== false);
    ok('campos do proximo pagamento escondidos ate o clique', preg_match('/req-novo-pag-' . $rpc . '[^>]*display:none|display:none[^>]*req-novo-pag-' . $rpc . '/', $html) === 1);
    ok('aviso sem data base nao mostra a data', strpos($html, 'Data base do saldo: <strong></strong>') === false);

    // salvar sem clicar no botao nao cria pagamento (mesmo com valores no post)
    $novo = $base + ["etapa3_id_{$rpc}" => '', "etapa3_numero_ciclo_{$rpc}" => 2, "etapa3_data_deposito_{$rpc}" => '20/01/2026',
        "etapa3_valor_bruto_depositado_{$rpc}" => '2.000,00', "etapa3_possui_saldo_{$rpc}" => 'N'];
    saida(function () use ($tela, $novo) { $tela->onSave($novo); });
    igual('sem o botao nao cria pagamento novo', 1, (int) valor('SELECT COUNT(*) FROM requisicao_pagamento_etapa3 WHERE requisicao_pagamento_cliente_id = ?', [$rpc]));
    saida(function () use ($tela, $novo, $rpc) { $tela->onSave($novo + ["etapa3_novo_{$rpc}" => '1']); });
    igual('com o botao cria o 2o pagamento', 2, (int) valor('SELECT COUNT(*) FROM requisicao_pagamento_etapa3 WHERE requisicao_pagamento_cliente_id = ?', [$rpc]));
    $segundo = (int) valor('SELECT MAX(id) FROM requisicao_pagamento_etapa3 WHERE requisicao_pagamento_cliente_id = ?', [$rpc]);
    igual('2o pagamento: MLE = bruto', 2000.0, round((float) valor('SELECT valor_mle FROM requisicao_pagamento_etapa3 WHERE id = ?', [$segundo]), 2));

    // ---------- excluir sem financeiro ----------
    igual('sem financeiro pode excluir', null, $E::motivoBloqueio($segundo));
    $html = saida(function () use ($f) { (new RequisicaoPagamentoVisualizacao(['key' => $f['requisicao_id']]))->show(); });
    ok('historico tem o botao de excluir', strpos($html, 'method=onExcluir') !== false);
    $E::excluir($segundo);
    igual('pagamento excluido', 0, (int) valor('SELECT COUNT(*) FROM requisicao_pagamento_etapa3 WHERE id = ?', [$segundo]));
    lanca('excluir de novo: nao encontrado', function () use ($E, $segundo) { $E::excluir($segundo); }, 'não encontrado');
} finally { Fixtures::limpar($f); }

// ---------- excluir com financeiro: recusado com aviso, mesmo sem nada pago (desfazer antes) ----------
$f = Fixtures::criar();
try {
    $lf = LevantamentoFinanceiroService::gerar($f['etapa3_id'], $dados($f, 'N'));
    ok('com financeiro: motivo pede desfazer antes', strpos((string) $E::motivoBloqueio($f['etapa3_id']), 'Desfazer financeiro') !== false);
    lanca('excluir recusado', function () use ($E, $f) { $E::excluir($f['etapa3_id']); }, 'financeiro gerado');
    igual('nada foi apagado', [1, 1], [
        (int) valor('SELECT COUNT(*) FROM requisicao_pagamento_etapa3 WHERE id = ?', [$f['etapa3_id']]),
        (int) valor('SELECT COUNT(*) FROM levantamento_financeiro WHERE id = ?', [$lf->id]),
    ]);
    $html = saida(function () use ($f) { (new RequisicaoPagamentoVisualizacao(['key' => $f['requisicao_id']]))->show(); });
    ok('botao de excluir apagado com o motivo', strpos($html, 'Não pode excluir: Este pagamento tem financeiro gerado') !== false);
    $s = saida(function () use ($f) { RequisicaoPagamentoEtapa3Visualizacao::onExcluir(['key' => $f['etapa3_id']]); });
    ok('clique no botao: aviso com o motivo, sem pergunta', strpos($s, 'Não é possível excluir este pagamento') !== false && strpos($s, 'onExcluirConfirmado') === false, $s);

    igual('requisicao estava Quitado', 7, (int) valor('SELECT status_requisicao_pagamento_id FROM requisicao_pagamento_cliente WHERE id = ?', [$f['rpc_id']]));
    LevantamentoFinanceiroService::desfazer($f['etapa3_id']);
    igual('desfeito o financeiro: pode excluir', null, $E::motivoBloqueio($f['etapa3_id']));
    $E::excluir($f['etapa3_id']);
    igual('pagamento excluido e contas do financeiro ja apagadas', [0, 0, 0], [
        (int) valor('SELECT COUNT(*) FROM requisicao_pagamento_etapa3 WHERE id = ?', [$f['etapa3_id']]),
        (int) valor('SELECT COUNT(*) FROM levantamento_financeiro WHERE id = ?', [$lf->id]),
        (int) valor('SELECT COUNT(*) FROM conta WHERE id IN (?, ?)', [$lf->conta_receber_id, $lf->conta_pagar_id]),
    ]);
    igual('status volta ao anterior', 5, (int) valor('SELECT status_requisicao_pagamento_id FROM requisicao_pagamento_cliente WHERE id = ?', [$f['rpc_id']]));
} finally { Fixtures::limpar($f); }
fim();
