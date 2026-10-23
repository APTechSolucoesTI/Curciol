<?php
// tests/levantamento/NavegadorProtecao.php
// Apoio aos testes de navegador (T14/T15): "criar" monta fixture + financeiro gerado e imprime os ids; "limpar" apaga tudo.
// Uso: php tests/levantamento/NavegadorProtecao.php criar|limpar
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
$arq = sys_get_temp_dir() . '/fixture_protecao_levantamento.json';
$modo = $argv[1] ?? '';
if ($modo === 'criar') {
    $f = Fixtures::criar();
    file_put_contents($arq, json_encode($f));
    TTransaction::open('escritorio');
    $benef = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
    $juan  = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
    TTransaction::close();
    $d = ['contrato_id'=>$f['contrato_id'], 'beneficiario_credito_id'=>$benef, 'data_levantamento'=>'2026-02-10', 'valor_levantado'=>12019.57,
          'pct_honorarios'=>20, 'pct_nf'=>16, 'parceria_levantamento_id'=>$juan, 'data_distribuicao'=>null, 'conta_caixa_id'=>$f['conta_caixa_id'],
          'tipo_pagamento_id'=>TipoPagamento::TRANSFERENCIA, 'compensado'=>'S', 'vencimento_cliente'=>'2026-02-15',
          'categoria_conta_id'=>24, 'tipo_documento_receber_id'=>6, 'tipo_documento_pagar_id'=>8];
    $lf = LevantamentoFinanceiroService::gerar($f['etapa3_id'], $d);
    echo json_encode($f + ['receber' => (int) $lf->conta_receber_id, 'pagar' => (int) $lf->conta_pagar_id]) . "\n";
} elseif ($modo === 'limpar') {
    Fixtures::limpar(json_decode(file_get_contents($arq), true));
    @unlink($arq);
    echo 'sobras: ' . json_encode(Fixtures::sobras()) . "\nTUDO OK\n";
}
