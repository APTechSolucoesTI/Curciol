<?php
// tests/levantamento/LevantamentoCalculoTest.php
require __DIR__ . '/../bootstrap.php';
$C = 'LevantamentoCalculoService';
$cinco = []; foreach ([1,2,3,4,5] as $i) { $cinco[] = ['pessoa_id'=>$i, 'percentual'=>0.2, 'recebe_diferenca'=>$i === 1]; }

// Exemplo ID 9 do arquivo (MLE = N - S = 11.479,87)
$base = ['regra'=>'CLIENTE_E_HONORARIOS', 'bruto'=>13195.26, 'mle'=>11479.87, 'pct_honorarios'=>20, 'pct_nf'=>16,
         'data_deposito'=>'2026-01-10', 'data_levantamento'=>'2026-02-10', 'valor_levantado'=>12019.57, 'participantes'=>$cinco];
$r = $C::calcular($base);
igual('ID9 S', 1715.39, round($r['s'], 2));
igual('ID9 U', 2639.052, round($r['u'], 4));
igual('ID9 W', 11479.87, round($r['w'], 2));
igual('ID9 AA', 2763.12, $r['aa']);
igual('ID9 Z', 9256.45, $r['z']);
igual('ID9 AC', 442.10, $r['ac']);
igual('ID9 AD', 2321.02, $r['ad']);
igual('ID9 Z + AA = Y', 12019.57, round($r['z'] + $r['aa'], 2));
$vals = array_column($r['repasses'], 'valor', 'pessoa_id');
igual('ID9 Curciol absorve 0,02', 464.22, $vals[1]);
igual('ID9 demais 464,20', 464.20, $vals[2]);
igual('ID9 soma repasses = AD', 2321.02, round(array_sum($vals), 2));

$sh = $C::calcular(['regra'=>'SOMENTE_HONORARIOS', 'pct_honorarios'=>null] + $base);
igual('somente honorarios: Z = 0', 0.0, $sh['z']);
igual('somente honorarios: AA = Y', 12019.57, $sh['aa']);

$sc = $C::calcular(['regra'=>'SOMENTE_CLIENTE', 'pct_honorarios'=>null, 'participantes'=>[]] + $base);
igual('somente cliente: AA = 0', 0.0, $sc['aa']);
igual('somente cliente: Z = Y', 12019.57, $sc['z']);
igual('somente cliente: sem repasses', 0, count($sc['repasses']));

lanca('MLE maior que bruto', function () use ($C, $base) { $C::calcular(['mle'=>14000] + $base); }, 'MLE');
lanca('levantamento antes do deposito', function () use ($C, $base) { $C::calcular(['data_levantamento'=>'2026-01-01'] + $base); }, 'levantamento');
lanca('Y zero', function () use ($C, $base) { $C::calcular(['valor_levantado'=>0] + $base); }, 'valor levantado');
lanca('% honorarios ausente na regra mista', function () use ($C, $base) { $C::calcular(['pct_honorarios'=>null] + $base); }, 'honorários');
lanca('% honorarios fora de 0..100', function () use ($C, $base) { $C::calcular(['pct_honorarios'=>120] + $base); }, 'honorários');
lanca('MLE zero (W = 0) com Y > 0', function () use ($C, $base) { $C::calcular(['mle'=>0, 'regra'=>'SOMENTE_CLIENTE', 'pct_honorarios'=>null] + $base); }, 'MLE');
lanca('honorarios sem parceria', function () use ($C, $base) { $C::calcular(['participantes'=>[]] + $base); }, 'parceria');
lanca('V negativo (T alto e descontos)', function () use ($C, $base) { $C::calcular(['pct_honorarios'=>95] + $base); }, 'cliente');

// Revisao round 1
// I1: centavos que nao cabem nao podem ser descartados em silencio
$dez = []; foreach (range(1, 10) as $i) { $dez[] = ['pessoa_id'=>$i, 'percentual'=>0.1, 'recebe_diferenca'=>$i === 1]; }
lanca('centavos negativos viram erro (I1)', function () use ($C, $base, $dez) {
    $C::calcular(['regra'=>'SOMENTE_HONORARIOS', 'pct_honorarios'=>null, 'valor_levantado'=>0.05, 'pct_nf'=>0, 'participantes'=>$dez] + $base); }, 'centavos');
$um = $C::calcular(['regra'=>'SOMENTE_HONORARIOS', 'pct_honorarios'=>null, 'valor_levantado'=>0.01, 'pct_nf'=>0,
    'participantes'=>[['pessoa_id'=>1,'percentual'=>1/3,'recebe_diferenca'=>true],['pessoa_id'=>2,'percentual'=>1/3,'recebe_diferenca'=>false],['pessoa_id'=>3,'percentual'=>1/3,'recebe_diferenca'=>false]]] + $base);
igual('centavo unico vai so para quem recebe a diferenca (I1)', 1, count($um['repasses']));
igual('centavo unico valor 0,01 (I1)', 0.01, $um['repasses'][0]['valor']);

// M5: campos obrigatorios com mensagem legivel
lanca('sem data do deposito (M5)', function () use ($C, $base) { $b = $base; unset($b['data_deposito']); $C::calcular($b); }, 'depósito');
lanca('sem pct_nf (M5)', function () use ($C, $base) { $b = $base; unset($b['pct_nf']); $C::calcular($b); }, 'NF');

// Revisao final 1: data futura
lanca('levantamento em data futura', function () use ($C, $base) { $C::calcular(['data_levantamento'=>date('Y-m-d', strtotime('+1 day'))] + $base); }, 'futura');
igual('levantamento hoje e aceito', 9256.45, $C::calcular(['data_levantamento'=>date('Y-m-d')] + $base)['z']);

// M4: data com hora no mesmo dia do deposito nao e antes
igual('mesmo dia com hora nao e antes do deposito (M4)', 9256.45, $C::calcular(['data_deposito'=>'2026-01-10 10:00:00', 'data_levantamento'=>'2026-01-10'] + $base)['z']);
fim();
