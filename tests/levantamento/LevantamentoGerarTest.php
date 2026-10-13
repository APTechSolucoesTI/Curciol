<?php
// tests/levantamento/LevantamentoGerarTest.php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
$S = 'LevantamentoFinanceiroService';

$f = Fixtures::criar();
try {
    TTransaction::open('escritorio');
    $benef = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
    $juan  = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
    TTransaction::close();
    $d = ['contrato_id'=>$f['contrato_id'], 'beneficiario_credito_id'=>$benef, 'data_levantamento'=>'2026-02-10', 'valor_levantado'=>12019.57,
          'pct_honorarios'=>20, 'pct_nf'=>16, 'parceria_levantamento_id'=>$juan, 'data_distribuicao'=>null, 'conta_caixa_id'=>$f['conta_caixa_id'],
          'tipo_pagamento_id'=>TipoPagamento::TRANSFERENCIA, 'compensado'=>'S', 'vencimento_cliente'=>'2026-02-15',
          'categoria_conta_id'=>24, 'tipo_documento_receber_id'=>6, 'tipo_documento_pagar_id'=>8];

    $sim = $S::simular($f['etapa3_id'], $d);
    igual('simular nao grava', null, $S::existePara($f['etapa3_id']));
    igual('simular Z', 9256.45, $sim['resultado']['z']);

    $lf = $S::gerar($f['etapa3_id'], $d);
    TTransaction::open('escritorio');
    $rec = new Conta($lf->conta_receber_id); $pag = new Conta($lf->conta_pagar_id);
    $parcRec = Lancamento::where('conta_id', '=', $rec->id)->first();
    $parcPag = Lancamento::where('conta_id', '=', $pag->id)->first();
    $cc = new ContaCaixa($f['conta_caixa_id']);
    igual('receber: tipo', TipoConta::RECEBER, $rec->tipo_conta_id);
    igual('receber: pessoa = devedor', $f['devedor_id'], $rec->pessoa_id);
    igual('receber: total = Y', 12019.57, (float) $rec->total_conta);
    igual('receber: requisicao', $f['requisicao_id'], $rec->requisicao_pagamento_id);
    igual('receber: quitada', 'S', $rec->quitada);
    igual('receber: parcela paga em X', '2026-02-10', substr($parcRec->dt_pagamento, 0, 10));
    ok('receber: extrato ligado', !empty($parcRec->extrato_id));
    igual('saldo da conta-caixa de teste += Y', 12019.57, round((float) $cc->saldo_instantaneo, 2));
    igual('repasses na conta (5)', 5, ContaProfissional::where('conta_id', '=', $rec->id)->count());
    igual('repasses na parcela somam AD', 2321.02, round(array_sum(array_map(function ($x) { return (float) $x->valor; }, LancamentoProfissional::where('lancamento_id', '=', $parcRec->id)->load())), 2));
    igual('pagar: pessoa = cliente', $f['cliente_id'], $pag->pessoa_id);
    igual('pagar: total = Z', 9256.45, (float) $pag->total_conta);
    igual('pagar: aberta', 'N', $pag->quitada ?: 'N');
    igual('pagar: vencimento informado', '2026-02-15', substr($parcPag->dt_vencimento, 0, 10));
    igual('pagar: origem = receber', $rec->id, (int) $pag->conta_origem_id);
    igual('ficha: AD', 2321.02, (float) $lf->ad);
    igual('ficha: 5 repasses', 5, count($lf->repasses()));
    TTransaction::close();

    lanca('gerar de novo e recusado', function () use ($S, $f, $d) { $S::gerar($f['etapa3_id'], $d); }, 'Já existe');

    // contaDoLevantamento (ruling R4)
    $r = $S::contaDoLevantamento((int) $lf->conta_receber_id);
    igual('contaDoLevantamento: receber -> ficha', (int) $lf->id, $r['ficha_id'] ?? null);
    igual('contaDoLevantamento: receber -> papel', 'RECEBER', $r['papel'] ?? null);
    $r = $S::contaDoLevantamento((int) $lf->conta_pagar_id);
    igual('contaDoLevantamento: pagar -> ficha', (int) $lf->id, $r['ficha_id'] ?? null);
    igual('contaDoLevantamento: pagar -> papel', 'PAGAR', $r['papel'] ?? null);
    ok('contaDoLevantamento: conta inexistente -> null', $S::contaDoLevantamento(2000000000) === null);
} finally { Fixtures::limpar($f); }

// somente honorarios: sem conta a pagar
$f2 = Fixtures::criar();
try {
    TTransaction::open('escritorio'); $suc = BeneficiarioCredito::where('nome', '=', 'Sucumbência')->first()->id; TTransaction::close();
    $lf2 = $S::gerar($f2['etapa3_id'], ['beneficiario_credito_id'=>$suc, 'pct_honorarios'=>null, 'contrato_id'=>$f2['contrato_id'], 'conta_caixa_id'=>$f2['conta_caixa_id'], 'vencimento_cliente'=>null] + $d);
    ok('sucumbencia: sem conta a pagar', empty($lf2->conta_pagar_id));
} finally { Fixtures::limpar($f2); }

// somente cliente: sem honorarios, sem repasses, tudo para o cliente
$f4 = Fixtures::criar();
try {
    TTransaction::open('escritorio'); $cli = BeneficiarioCredito::where('nome', '=', 'Cliente')->first()->id; TTransaction::close();
    $lf4 = $S::gerar($f4['etapa3_id'], ['beneficiario_credito_id'=>$cli, 'pct_honorarios'=>null, 'contrato_id'=>$f4['contrato_id'], 'conta_caixa_id'=>$f4['conta_caixa_id']] + $d);
    igual('somente cliente: AA = 0', 0.0, (float) $lf4->aa);
    igual('somente cliente: Z = Y', 12019.57, (float) $lf4->z);
    TTransaction::open('escritorio'); igual('somente cliente: sem repasses', 0, count($lf4->repasses())); TTransaction::close();
    ok('somente cliente: tem conta a pagar', !empty($lf4->conta_pagar_id));
} finally { Fixtures::limpar($f4); }

// contrato que nao pertence ao pagamento e cadastros inexistentes: erro legivel
$f5 = Fixtures::criar();
try {
    lanca('contrato de fora', function () use ($S, $f5, $d) { $S::gerar($f5['etapa3_id'], ['contrato_id'=>2000000000, 'conta_caixa_id'=>$f5['conta_caixa_id']] + $d); }, 'Contrato inválido');
    lanca('categoria inexistente', function () use ($S, $f5, $d) { $S::gerar($f5['etapa3_id'], ['categoria_conta_id'=>2000000000, 'contrato_id'=>$f5['contrato_id'], 'conta_caixa_id'=>$f5['conta_caixa_id']] + $d); }, 'categoria');
    lanca('documento a pagar inexistente', function () use ($S, $f5, $d) { $S::gerar($f5['etapa3_id'], ['tipo_documento_pagar_id'=>2000000000, 'contrato_id'=>$f5['contrato_id'], 'conta_caixa_id'=>$f5['conta_caixa_id']] + $d); }, 'tipo de documento');
    lanca('vencimento invalido', function () use ($S, $f5, $d) { $S::gerar($f5['etapa3_id'], ['vencimento_cliente'=>'2026-13-45', 'contrato_id'=>$f5['contrato_id'], 'conta_caixa_id'=>$f5['conta_caixa_id']] + $d); }, 'vencimento');
    igual('nada gravado nos erros de validacao', null, $S::existePara($f5['etapa3_id']));
} finally { Fixtures::limpar($f5); }

// falha no meio nao deixa nada gravado
$f3 = Fixtures::criar();
try {
    lanca('conta-caixa invalida aborta', function () use ($S, $f3, $d) { $S::gerar($f3['etapa3_id'], ['contrato_id'=>$f3['contrato_id'], 'conta_caixa_id'=>-1] + $d); }, 'conta-caixa');
    igual('nada gravado apos falha', null, $S::existePara($f3['etapa3_id']));
    TTransaction::open('escritorio'); igual('nenhuma conta da requisicao', 0, Conta::where('requisicao_pagamento_id', '=', $f3['requisicao_id'])->count()); TTransaction::close();
} finally { Fixtures::limpar($f3); }

// limpar() nao deixa sobras
ok('sem sobras da fixture', Fixtures::sobras() === [], json_encode(Fixtures::sobras()));
fim();
