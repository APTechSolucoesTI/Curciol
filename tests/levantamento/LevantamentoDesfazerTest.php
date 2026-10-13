<?php
// tests/levantamento/LevantamentoDesfazerTest.php
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
$S = 'LevantamentoFinanceiroService';
$dados = function ($f) {
    TTransaction::open('escritorio');
    $b = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
    $p = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
    TTransaction::close();
    return ['contrato_id'=>$f['contrato_id'], 'beneficiario_credito_id'=>$b, 'data_levantamento'=>'2026-02-10', 'valor_levantado'=>12019.57,
            'pct_honorarios'=>20, 'pct_nf'=>16, 'parceria_levantamento_id'=>$p, 'data_distribuicao'=>null, 'conta_caixa_id'=>$f['conta_caixa_id'],
            'tipo_pagamento_id'=>TipoPagamento::TRANSFERENCIA, 'compensado'=>'S', 'vencimento_cliente'=>'2026-02-15',
            'categoria_conta_id'=>24, 'tipo_documento_receber_id'=>6, 'tipo_documento_pagar_id'=>8];
};
$saldo = function ($ccId) { TTransaction::open('escritorio'); $c = new ContaCaixa($ccId); TTransaction::close(); return [round((float) $c->saldo_instantaneo, 2), round((float) $c->saldo_nao_compensado, 2)]; };

$f = Fixtures::criar();
try {
    $S::gerar($f['etapa3_id'], $dados($f));
    $S::desfazer($f['etapa3_id']);
    igual('desfazer devolve o saldo compensado', [0.0, 0.0], $saldo($f['conta_caixa_id']));
    igual('ficha removida', null, $S::existePara($f['etapa3_id']));
    TTransaction::open('escritorio');
    igual('contas removidas', 0, Conta::where('requisicao_pagamento_id', '=', $f['requisicao_id'])->count());
    igual('extratos removidos', 0, Extrato::where('conta_caixa_id', '=', $f['conta_caixa_id'])->count());
    TTransaction::close();
    $S::gerar($f['etapa3_id'], $dados($f));
    ok('pode gerar de novo depois de desfazer', $S::existePara($f['etapa3_id']) !== null);
} finally { Fixtures::limpar($f); }

// extrato descompensado depois da geracao: devolve do saldo nao compensado
$f2 = Fixtures::criar();
try {
    $lf = $S::gerar($f2['etapa3_id'], $dados($f2));
    TTransaction::open('escritorio');
    $ex = new Extrato($lf->extrato_id); $ex->compensado = 'N'; $ex->store();
    $cc = new ContaCaixa($f2['conta_caixa_id']); $cc->saldo_instantaneo -= 12019.57; $cc->saldo_nao_compensado += 12019.57; $cc->store();
    TTransaction::close();
    $S::desfazer($f2['etapa3_id']);
    igual('descompensado: devolve do nao compensado', [0.0, 0.0], $saldo($f2['conta_caixa_id']));
} finally { Fixtures::limpar($f2); }

// conta do cliente paga: bloqueia
$f3 = Fixtures::criar();
try {
    $lf = $S::gerar($f3['etapa3_id'], $dados($f3));
    TTransaction::open('escritorio');
    $parc = Lancamento::where('conta_id', '=', $lf->conta_pagar_id)->first(); $parc->dt_pagamento = '2026-02-15'; $parc->store();
    TTransaction::close();
    ok('motivo informado', $S::podeDesfazer($f3['etapa3_id']) !== null);
    lanca('desfazer bloqueado', function () use ($S, $f3) { $S::desfazer($f3['etapa3_id']); }, 'cliente');
    TTransaction::open('escritorio'); $parc = Lancamento::where('conta_id', '=', $lf->conta_pagar_id)->first(); $parc->dt_pagamento = null; $parc->store(); TTransaction::close();
} finally { Fixtures::limpar($f3); }

// limpar() nao deixa sobras
ok('sem sobras da fixture', Fixtures::sobras() === [], json_encode(Fixtures::sobras()));
fim();
