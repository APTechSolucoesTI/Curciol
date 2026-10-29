<?php
// tests/levantamento/LevantamentoProtecaoTest.php
// Tarefas 14 e 15: pagamento e contas protegidos quando o financeiro do levantamento foi gerado.
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
$S = 'LevantamentoFinanceiroService';

$f = Fixtures::criar();
try {
    $base = ['data_deposito' => '2026-01-10', 'valor_bruto_depositado' => 13195.26, 'valor_mle' => 11479.87, 'processo_filho_id' => $f['processo_id']];

    // sem financeiro nada e bloqueado
    $S::garantirPagamentoEditavel($f['etapa3_id'], ['valor_bruto_depositado' => 1.00]);
    ok('sem financeiro: pagamento editavel', true);
    $S::garantirClienteRemovivel($f['rpc_id']);
    ok('sem financeiro: cliente removivel', true);

    TTransaction::open('escritorio');
    $benef = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
    $juan  = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
    TTransaction::close();
    $d = ['contrato_id'=>$f['contrato_id'], 'beneficiario_credito_id'=>$benef, 'data_levantamento'=>'2026-02-10', 'valor_levantado'=>12019.57,
          'pct_honorarios'=>20, 'pct_nf'=>16, 'parceria_levantamento_id'=>$juan, 'data_distribuicao'=>null, 'conta_caixa_id'=>$f['conta_caixa_id'],
          'tipo_pagamento_id'=>TipoPagamento::TRANSFERENCIA, 'compensado'=>'S', 'vencimento_cliente'=>'2026-02-15',
          'categoria_conta_id'=>24, 'tipo_documento_receber_id'=>6, 'tipo_documento_pagar_id'=>8];
    $lf = $S::gerar($f['etapa3_id'], $d);

    // pagamento com financeiro
    $S::garantirPagamentoEditavel($f['etapa3_id'], $base);
    ok('com financeiro: mesmos valores passam', true);
    $S::garantirPagamentoEditavel($f['etapa3_id'], ['data_deposito' => '2026-01-10 00:00:00', 'valor_bruto_depositado' => '13195.260', 'valor_mle' => 11479.874]);
    ok('com financeiro: data com hora e 2 casas passam', true);
    $S::garantirPagamentoEditavel($f['etapa3_id'], ['conta_indicada_mle' => 'x']);
    ok('com financeiro: campo nao protegido nao e comparado', true);
    lanca('bruto diferente', function () use ($S, $f, $base) { $S::garantirPagamentoEditavel($f['etapa3_id'], ['valor_bruto_depositado' => 13195.27] + $base); }, 'Desfaça o financeiro');
    lanca('mle diferente', function () use ($S, $f, $base) { $S::garantirPagamentoEditavel($f['etapa3_id'], ['valor_mle' => 1.0] + $base); }, 'Desfaça o financeiro');
    lanca('deposito diferente', function () use ($S, $f, $base) { $S::garantirPagamentoEditavel($f['etapa3_id'], ['data_deposito' => '2026-01-11'] + $base); }, 'Desfaça o financeiro');
    lanca('processo diferente', function () use ($S, $f, $base) { $S::garantirPagamentoEditavel($f['etapa3_id'], ['processo_filho_id' => $f['processo_raiz_id']] + $base); }, 'Desfaça o financeiro');
    lanca('processo removido', function () use ($S, $f) { $S::garantirPagamentoEditavel($f['etapa3_id'], ['processo_filho_id' => null]); }, 'Desfaça o financeiro');
    lanca('cliente nao removivel', function () use ($S, $f) { $S::garantirClienteRemovivel($f['rpc_id']); }, 'financeiro gerado');

    // dentro de transacao aberta pelo chamador (como nas telas)
    TTransaction::open('escritorio');
    lanca('em transacao: bruto diferente', function () use ($S, $f) { $S::garantirPagamentoEditavel($f['etapa3_id'], ['valor_bruto_depositado' => 1]); }, 'Desfaça o financeiro');
    ok('em transacao: continua aberta', TTransaction::get() !== null);
    TTransaction::rollback();

    // contas geradas
    $msg = 'Desfazer financeiro';
    lanca('receber: bloqueada', function () use ($S, $lf) { $S::exigirContaNaoGerada((int) $lf->conta_receber_id); }, $msg);
    lanca('pagar: bloqueada para editar/excluir', function () use ($S, $lf) { $S::exigirContaNaoGerada((int) $lf->conta_pagar_id); }, $msg);
    $S::exigirContaNaoGerada((int) $lf->conta_pagar_id, false);
    ok('pagar: liberada para quitar/desquitar', true);
    $S::exigirContaNaoGerada(2000000000);
    ok('conta comum: liberada', true);
    $r = $S::contaDoLevantamento((int) $lf->conta_receber_id);
    igual('contaDoLevantamento receber', 'RECEBER', $r['papel'] ?? null);
    $r = $S::contaDoLevantamento((int) $lf->conta_pagar_id);
    igual('contaDoLevantamento pagar', 'PAGAR', $r['papel'] ?? null);

    // depois de desfazer, tudo liberado
    Fixtures::desquitarRecebimento((int) $f['etapa3_id']); // o desfazer exige o recebimento estornado
    $S::desfazer($f['etapa3_id']);
    $S::garantirPagamentoEditavel($f['etapa3_id'], ['valor_bruto_depositado' => 1.00]);
    $S::garantirClienteRemovivel($f['rpc_id']);
    ok('apos desfazer: liberado', true);
} finally { Fixtures::limpar($f); }

ok('sem sobras da fixture', Fixtures::sobras() === [], json_encode(Fixtures::sobras()));
fim();
