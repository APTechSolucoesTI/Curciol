<?php
// tests/levantamento/LevantamentoContextoTest.php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';

$f = Fixtures::criar(['distribuicao' => '2020-03-15']);
try {
    $c = LevantamentoContextoService::carregar($f['etapa3_id']);
    igual('cliente do pagamento', $f['cliente_id'], $c->cliente_id);
    igual('devedor', $f['devedor_id'], $c->devedor_id);
    igual('bruto', 13195.26, $c->bruto);
    igual('mle', 11479.87, $c->mle);
    igual('descontos = bruto - MLE', 1715.39, round($c->descontos, 2));
    igual('um contrato -> sugerido', $f['contrato_id'], $c->contrato_sugerido_id);
    igual('% do contrato', 20.0, $c->pct_honorarios_sugerido);
    igual('pagamento no incidente', $f['processo_id'], $c->processo_id);
    igual('raiz da familia', $f['processo_raiz_id'], $c->processo_raiz_id);
    igual('numero do processo principal', 'TESTE LEVANTAMENTO RAIZ', $c->processo_raiz_numero);
    ok('processo principal diferente do pagamento (tela mostra)', LevantamentoFinanceiroForm::raizDiferente($c));
    igual('distribuicao da raiz', '2020-03-15', $c->data_distribuicao);
    igual('numero do pagamento', 1, $c->numero_pagamento);
    igual('data do deposito', '2026-01-10', substr($c->data_deposito, 0, 10));
    igual('requisicao', $f['requisicao_id'], $c->requisicao_id);
    igual('tipo da requisicao', 'RPV', $c->tipo_requisicao);
    ok('nome do cliente', strpos($c->cliente_nome, 'TESTE LEVANTAMENTO') === 0);
    igual('repasse do contrato (5 pessoas)', 5, count($c->contratos[0]['repasse']));
    igual('escritorio do contrato sugerido', $f['escritorio_id'], $c->escritorio_id);
    TTransaction::open('escritorio');
    $juan = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first();
    TTransaction::close();
    igual('parceria sugerida pelo repasse 5 x 20%', (int) $juan->id, $c->parceria_sugerida_id);
    ok('ainda nao gerado', $c->ja_gerado === false);
    ok('nao deixou transacao aberta', TTransaction::get() === null);
} finally { Fixtures::limpar($f); }

$f2 = Fixtures::criar(['contratos' => 2]);
try {
    $c2 = LevantamentoContextoService::carregar($f2['etapa3_id']);
    igual('dois contratos -> lista com 2', 2, count($c2->contratos));
    ok('dois contratos -> sem sugestao', $c2->contrato_sugerido_id === null);
    ok('dois contratos -> sem % sugerido', $c2->pct_honorarios_sugerido === null);
    ok('dois contratos -> sem escritorio', $c2->escritorio_id === null);
} finally { Fixtures::limpar($f2); }

$f3 = Fixtures::criar(['clientes' => 2]);
try {
    $c3 = LevantamentoContextoService::carregar($f3['etapa3_id']);
    igual('requisicao com 2 clientes: cliente certo', $f3['cliente_id'], $c3->cliente_id);
    igual('so os contratos desse cliente', 1, count($c3->contratos));
    igual('e e o contrato do cliente certo', $f3['contrato_id'], $c3->contratos[0]['id']);
} finally { Fixtures::limpar($f3); }

$f4 = Fixtures::criar(['repasse' => ['CURCIOL SOCIEDADE DE ADVOGADOS' => 60, 'KLEBER CURCIOL' => 30]]);
try {
    ok('repasse que nao fecha 100 -> sem parceria sugerida', LevantamentoContextoService::carregar($f4['etapa3_id'])->parceria_sugerida_id === null);
} finally { Fixtures::limpar($f4); }

// contrato em multiplicador (unidade 'x'): % nao se aplica, o usuario informa no formulario
$f5 = Fixtures::criar(['unidade_indexador_id' => 2, 'pct_contrato' => '3']);
try {
    $c5 = LevantamentoContextoService::carregar($f5['etapa3_id']);
    ok('multiplicador -> pct do contrato nulo', $c5->contratos[0]['pct_honorarios'] === null);
    ok('multiplicador -> sem % sugerido', $c5->pct_honorarios_sugerido === null);
    igual('multiplicador -> contrato ainda sugerido', $f5['contrato_id'], $c5->contrato_sugerido_id);
} finally { Fixtures::limpar($f5); }

// % com virgula e sinal
$f6 = Fixtures::criar(['pct_contrato' => '17,5%']);
try {
    igual('% com virgula e sinal', 17.5, LevantamentoContextoService::carregar($f6['etapa3_id'])->pct_honorarios_sugerido);
} finally { Fixtures::limpar($f6); }

// duas condicoes % diferentes -> ambiguo, nulo
$fb = Fixtures::criar(['pct_contrato' => ['20', '25']]);
try {
    ok('duas condicoes % diferentes -> pct nulo', LevantamentoContextoService::carregar($fb['etapa3_id'])->contratos[0]['pct_honorarios'] === null);
} finally { Fixtures::limpar($fb); }
$fc = Fixtures::criar(['pct_contrato' => ['20', '20']]);
try {
    igual('duas condicoes % iguais -> pct', 20.0, LevantamentoContextoService::carregar($fc['etapa3_id'])->pct_honorarios_sugerido);
} finally { Fixtures::limpar($fc); }
// complemento fora de 0..100
$fd = Fixtures::criar(['pct_contrato' => '150']);
try {
    ok('% fora de 0..100 -> nulo', LevantamentoContextoService::carregar($fd['etapa3_id'])->contratos[0]['pct_honorarios'] === null);
} finally { Fixtures::limpar($fd); }

// parceriaPorRepasse direto: tolerancia de 0,01
TTransaction::open('escritorio');
$juanId = (int) ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
$pessoas = array_map(function ($p) { return (int) $p->pessoa_id; }, ParceriaLevantamento::find($juanId)->participantes());
TTransaction::close();
igual('parceriaPorRepasse: 20,005 aceito', $juanId, LevantamentoContextoService::parceriaPorRepasse(array_fill_keys($pessoas, 20.005)));
ok('parceriaPorRepasse: 20,02 recusado', LevantamentoContextoService::parceriaPorRepasse(array_fill_keys($pessoas, 20.02)) === null);
ok('parceriaPorRepasse: vazio -> nulo', LevantamentoContextoService::parceriaPorRepasse([]) === null);

// sem deposito lancado
$f7 = Fixtures::criar(['deposito' => null]);
try {
    lanca('sem data de deposito', function () use ($f7) { LevantamentoContextoService::carregar($f7['etapa3_id']); }, 'sem depósito');
    ok('erro nao deixa transacao aberta', TTransaction::get() === null);
} finally { Fixtures::limpar($f7); }
$f8 = Fixtures::criar(['bruto' => 0]);
try {
    lanca('bruto zero', function () use ($f8) { LevantamentoContextoService::carregar($f8['etapa3_id']); }, 'sem depósito');
} finally { Fixtures::limpar($f8); }
$f9 = Fixtures::criar(['mle' => 0]);
try {
    lanca('MLE zero', function () use ($f9) { LevantamentoContextoService::carregar($f9['etapa3_id']); }, 'sem depósito');
} finally { Fixtures::limpar($f9); }

// usa a transacao ja aberta, sem fechar
$fa = Fixtures::criar();
try {
    TTransaction::open('escritorio');
    LevantamentoContextoService::carregar($fa['etapa3_id']);
    ok('transacao do chamador continua aberta', TTransaction::get() !== null);
    TTransaction::close();
} finally { Fixtures::limpar($fa); }

lanca('pagamento inexistente', function () { LevantamentoContextoService::carregar(-1); }, 'não encontrado');
ok('inexistente nao deixa transacao aberta', TTransaction::get() === null);
// Revisao final 6: erro de banco vira mensagem generica; Exception normal mantem a propria
ok('PDOException -> mensagem generica', strpos(LevantamentoFinanceiroForm::mensagem(new PDOException('SQLSTATE[23505] duplicate key')), 'Nada foi gravado') !== false && strpos(LevantamentoFinanceiroForm::mensagem(new PDOException('SQLSTATE[23505] duplicate key')), 'SQLSTATE') === false);
igual('Exception normal -> propria mensagem', 'Falhou X', LevantamentoFinanceiroForm::mensagem(new Exception('Falhou X')));
fim();
