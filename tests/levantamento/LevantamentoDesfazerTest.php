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
// a conta a receber nasce quitada: o desfazer so passa depois de desquitar o recebimento (29/10/2026)
$desquitarReceber = function ($etapa3Id) { return Fixtures::desquitarRecebimento((int) $etapa3Id); };
$saldo = function ($ccId) { TTransaction::open('escritorio'); $c = new ContaCaixa($ccId); TTransaction::close(); return [round((float) $c->saldo_instantaneo, 2), round((float) $c->saldo_nao_compensado, 2)]; };

$f = Fixtures::criar();
try {
    $S::gerar($f['etapa3_id'], $dados($f));
    $motivo = (string) $S::podeDesfazer($f['etapa3_id']);
    ok('quitada: motivo pede estornar/desquitar', strpos($motivo, 'Estorne/desquite o recebimento') !== false, $motivo);
    lanca('quitada: desfazer recusado', function () use ($S, $f) { $S::desfazer($f['etapa3_id']); }, 'Estorne/desquite');
    ok('quitada: ficha continua', $S::existePara($f['etapa3_id']) !== null);
    TSession::setValue('programs', ['LevantamentoFinanceiroForm' => true]); // usuario do grupo Financeiro
    ob_start(); LevantamentoFinanceiroView::onConfirmarDesfazer(['etapa3_id' => $f['etapa3_id']]); $tela = ob_get_clean();
    ok('quitada: clique no botao mostra o aviso, sem pergunta', strpos($tela, 'Estorne/desquite') !== false && strpos($tela, 'onDesfazer') === false, $tela);
    $desquitarReceber($f['etapa3_id']);
    igual('desquitada: pode desfazer', null, $S::podeDesfazer($f['etapa3_id']));
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
    $desquitarReceber($f2['etapa3_id']);
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

// geracao nao compensada: vai e volta pelo saldo nao compensado
$f4 = Fixtures::criar();
try {
    $d4 = ['compensado' => 'N'] + $dados($f4);
    $S::gerar($f4['etapa3_id'], $d4);
    igual('nao compensado: saldo apos gerar', [0.0, 12019.57], $saldo($f4['conta_caixa_id']));
    $desquitarReceber($f4['etapa3_id']);
    $S::desfazer($f4['etapa3_id']);
    igual('nao compensado: devolve do nao compensado', [0.0, 0.0], $saldo($f4['conta_caixa_id']));
} finally { Fixtures::limpar($f4); }

// pagamento PARCIAL ao cliente (saldo reduzido + extrato de saida, sem dt_pagamento/extrato_id): bloqueia e nada muda
$f5 = Fixtures::criar();
try {
    $lf = $S::gerar($f5['etapa3_id'], $dados($f5));
    TTransaction::open('escritorio');
    $parc = Lancamento::where('conta_id', '=', $lf->conta_pagar_id)->first();
    $parc->saldo = 4000; $parc->store();
    $ex = new Extrato();
    $ex->escritorio_id = $f5['escritorio_id']; $ex->conta_caixa_id = $f5['conta_caixa_id']; $ex->lancamento_id = $parc->id;
    $ex->tipo_extrato_id = TipoExtrato::PAGAR; $ex->saida_valor = 5256.45; $ex->data_lancamento = date('Y-m-d');
    $ex->compensado = 'S'; $ex->data_compensacao = '2026-02-16'; $ex->historico = 'TESTE LEVANTAMENTO pagamento parcial'; $ex->store();
    $cc = new ContaCaixa($f5['conta_caixa_id']); $cc->saldo_instantaneo -= 5256.45; $cc->store();
    TTransaction::close();
    $antes = $saldo($f5['conta_caixa_id']);
    ok('parcial: motivo informado', $S::podeDesfazer($f5['etapa3_id']) !== null);
    lanca('parcial: desfazer bloqueado', function () use ($S, $f5) { $S::desfazer($f5['etapa3_id']); }, 'cliente');
    igual('parcial: saldo intacto', $antes, $saldo($f5['conta_caixa_id']));
    igual('parcial: saldo esperado', [6763.12, 0.0], $antes);
    TTransaction::open('escritorio');
    igual('parcial: extratos intactos', 2, Extrato::where('conta_caixa_id', '=', $f5['conta_caixa_id'])->count());
    TTransaction::close();
    ok('parcial: ficha continua', $S::existePara($f5['etapa3_id']) !== null);
} finally { Fixtures::limpar($f5); }

// limpar() nao deixa sobras
ok('sem sobras da fixture', Fixtures::sobras() === [], json_encode(Fixtures::sobras()));
fim();
