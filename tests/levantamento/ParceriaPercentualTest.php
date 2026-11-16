<?php
// tests/levantamento/ParceriaPercentualTest.php
require __DIR__ . '/../bootstrap.php';
$S = 'ParceriaPercentualService';

igual('meses 01/01/2010 a 27/09/2026', 200, $S::mesesCompletos('2010-01-01', '2026-09-27'));
igual('meses no mesmo mes', 0, $S::mesesCompletos('2026-09-02', '2026-09-27'));
igual('meses dia ainda nao completo', 0, $S::mesesCompletos('2026-08-28', '2026-09-27'));
igual('meses invertido vira 0', 0, $S::mesesCompletos('2026-10-01', '2026-09-01'));

// dias corridos (16/11/2026): o dia da distribuicao conta, o do levantamento nao; ano bissexto com 366
igual('dias 01/01/2010 a 27/09/2026', 6113, $S::diasCorridos('2010-01-01', '2026-09-27'));
igual('dias no ano bissexto de 2016', 366, $S::diasCorridos('2016-01-01', '2017-01-01'));
igual('dias invertido vira 0', 0, $S::diasCorridos('2026-10-01', '2026-09-01'));
// Exemplo ID 13 do arquivo: G 01/01/2010, X 27/09/2026 (6.113 dias)
igual('a (ate 01/11/2016) = 2.496 / 6.113 dias', 2496/6113, $S::fracaoAntes('2010-01-01', '2026-09-27', '2016-11-01'));
igual('p (ate 01/06/2018) = 3.073 / 6.113 dias', 3073/6113, $S::fracaoAntes('2010-01-01', '2026-09-27', '2018-06-01'));
igual('meio do mes conta (antes descartava): 10 dias de 20', 0.5, $S::fracaoAntes('2016-10-22', '2016-11-11', '2016-11-01'));
igual('G depois do marco -> 0', 0.0, $S::fracaoAntes('2019-01-01', '2026-09-27', '2018-06-01'));
igual('X antes do marco -> 1', 1.0, $S::fracaoAntes('2010-01-01', '2016-01-01', '2016-11-01'));
igual('mesmo mes antes do marco -> 1', 1.0, $S::fracaoAntes('2016-10-02', '2016-10-20', '2016-11-01'));
igual('mesmo mes depois do marco -> 0', 0.0, $S::fracaoAntes('2018-07-02', '2018-07-20', '2018-06-01'));
igual('levantamento no dia do marco -> 1', 1.0, $S::fracaoAntes('2016-10-02', '2016-11-01', '2016-11-01'));

// SERVIDORES (CAMPOS) convertida para periodos (13/11/2026): antes do 1o marco / entre os marcos / depois do 2o marco
$campos = [
    ['pessoa_id'=>1, 'nivel'=>'MARCO12', 'percentual_antes'=>8,    'percentual_entre'=>12,    'percentual_depois'=>22.5,  'recebe_diferenca'=>'S'], // Curciol
    ['pessoa_id'=>2, 'nivel'=>'MARCO12', 'percentual_antes'=>16.7, 'percentual_entre'=>25.05, 'percentual_depois'=>18.75, 'recebe_diferenca'=>'N'], // Jac
    ['pessoa_id'=>3, 'nivel'=>'MARCO1',  'percentual_antes'=>12.5, 'percentual_entre'=>null,  'percentual_depois'=>18.75, 'recebe_diferenca'=>'N'], // Kcur
    ['pessoa_id'=>4, 'nivel'=>'MARCO1',  'percentual_antes'=>50,   'percentual_entre'=>null,  'percentual_depois'=>25,    'recebe_diferenca'=>'N'], // Dani
    ['pessoa_id'=>5, 'nivel'=>'MARCO1',  'percentual_antes'=>10,   'percentual_entre'=>null,  'percentual_depois'=>15,    'recebe_diferenca'=>'N'], // Campos
    ['pessoa_id'=>6, 'nivel'=>'MARCO12', 'percentual_antes'=>1.4,  'percentual_entre'=>2.1,   'percentual_depois'=>0,     'recebe_diferenca'=>'N'], // Alliance
    ['pessoa_id'=>7, 'nivel'=>'MARCO12', 'percentual_antes'=>1.4,  'percentual_entre'=>2.1,   'percentual_depois'=>0,     'recebe_diferenca'=>'N'], // Estrutura
];
igual('nivel 1: entre = depois', 18.75, $S::entre($campos[2]));
igual('nivel 2: entre = antes', 50.0, $S::entre(['nivel'=>'MARCO2', 'percentual_antes'=>50, 'percentual_entre'=>7, 'percentual_depois'=>0]));
igual('nivel 1 e 2: entre informado', 25.05, $S::entre($campos[1]));

// Exemplo ID 13 do arquivo: G 01/01/2010, X 27/09/2026 -> pesos 2.496 / 577 / 3.040 dias
$r = $S::percentuais('TEMPORAL', $campos, '2016-11-01', '2018-06-01', '2010-01-01', '2026-09-27');
$pct = []; foreach ($r['participantes'] as $q) { $pct[$q['pessoa_id']] = $q['percentual']; }
igual('ID13 Dani 35,2078%', 0.352078, round($pct[4], 6));
igual('ID13 Curciol 15,5884%', 0.155884, round($pct[1], 6));
igual('ID13 Jac 18,5076%', 0.185076, round($pct[2], 6));
igual('ID13 Kcur 16,1981%', 0.161981, round($pct[3], 6));
igual('ID13 Campos 12,9584%', 0.129584, round($pct[5], 6));
igual('ID13 Alliance 0,7699%', 0.007699, round($pct[6], 6));
igual('soma 100%', 1.0, round(array_sum($pct), 10));
igual('sem fator D (cascata acabou)', null, $r['d']);

// Elisete (linha 63 da planilha, ficha 26 de producao): G 10/12/2010, X 05/11/2026 (5.809 dias: 2.153 / 577 / 3.079)
$r = $S::percentuais('TEMPORAL', $campos, '2016-11-01', '2018-06-01', '2010-12-10', '2026-11-05');
$pct = []; foreach ($r['participantes'] as $q) { $pct[$q['pessoa_id']] = $q['percentual']; }
igual('Elisete Curciol 16,0829%', 0.160829, round($pct[1], 6));
igual('Elisete Jac 18,616%', 0.18616, round($pct[2], 6));
igual('Elisete Dani 34,2658%', 0.342658, round($pct[4], 6));
igual('Elisete Alliance 0,7275%', 0.007275, round($pct[6], 6));
igual('Elisete soma 100%', 1.0, round(array_sum($pct), 10));

// datas que na planilha davam #NUM!/99,55%: sempre 100%
$r2 = $S::percentuais('TEMPORAL', $campos, '2016-11-01', '2018-06-01', '2013-10-04', '2026-09-21');
igual('soma 100% no caso 04/10/2013', 1.0, round(array_sum(array_column($r2['participantes'], 'percentual')), 10));

// POUPANCA (CAMPOS) do print de 13/11: Carlos 50% ate 2019, depois Ivan; sem repetir ninguem
$poupanca = [
    ['pessoa_id'=>1, 'nivel'=>'MARCO1', 'percentual_antes'=>7.67,  'percentual_depois'=>16.66, 'recebe_diferenca'=>'S'],
    ['pessoa_id'=>2, 'nivel'=>'MARCO1', 'percentual_antes'=>25.66, 'percentual_depois'=>16.67, 'recebe_diferenca'=>'N'],
    ['pessoa_id'=>3, 'nivel'=>'MARCO1', 'percentual_antes'=>16.67, 'percentual_depois'=>16.67, 'recebe_diferenca'=>'N'],
    ['pessoa_id'=>8, 'nivel'=>'MARCO2', 'percentual_antes'=>50,    'percentual_depois'=>0,     'recebe_diferenca'=>'N'],
    ['pessoa_id'=>9, 'nivel'=>'MARCO2', 'percentual_antes'=>0,     'percentual_depois'=>50,    'recebe_diferenca'=>'N'],
];
$rp = $S::percentuais('TEMPORAL', $poupanca, '2018-06-01', '2019-01-01', '2010-12-10', '2026-11-05');
$pp = array_column($rp['participantes'], 'percentual', 'pessoa_id');
igual('POUPANCA soma 100%', 1.0, round(array_sum($pp), 10));
igual('POUPANCA Carlos = 50% x tempo ate 01/01/2019', round(0.5 * $rp['p'], 10), round($pp[8], 10));
igual('POUPANCA Ivan = 50% x tempo depois de 01/01/2019', round(0.5 * (1 - $rp['p']), 10), round($pp[9], 10));

// nivel fixo na parceria por tempo: so o "antes" vale; entre e depois repetem (o depois digitado e ignorado)
$comFixo = $poupanca; $comFixo[2]['nivel'] = 'FIXO'; $comFixo[2]['percentual_depois'] = 999;
igual('fixo: entre e depois = antes', [16.67, 16.67], [$S::entre($comFixo[2]), $S::depois($comFixo[2])]);
$rf = array_column($S::percentuais('TEMPORAL', $comFixo, '2018-06-01', '2019-01-01', '2010-12-10', '2026-11-05')['participantes'], 'percentual', 'pessoa_id');
igual('fixo: Kleber 16,67% em qualquer data', 0.1667, round($rf[3], 10));
igual('fixo: soma 100%', 1.0, round(array_sum($rf), 10));
lanca('parceria por tempo aceita o nivel fixo', function () use ($S, $comFixo) { $S::validar('TEMPORAL', $comFixo); throw new Exception('validou'); }, 'validou');

$fixa = [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>20,'recebe_diferenca'=>'S'], ['pessoa_id'=>2,'nivel'=>'FIXO','percentual'=>80,'recebe_diferenca'=>'N']];
igual('fixa: percentual vira fracao', 0.8, $S::percentuais('FIXA', $fixa, null, null, null, null)['participantes'][1]['percentual']);

lanca('fixa somando 90% e recusada', function () use ($S) { $S::validar('FIXA', [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>90,'recebe_diferenca'=>'S']]); }, '100%');
lanca('sem quem recebe a diferenca', function () use ($S) { $S::validar('FIXA', [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>100,'recebe_diferenca'=>'N']]); }, 'diferença');
lanca('pessoa repetida', function () use ($S) { $S::validar('FIXA', [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>'S'], ['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>'N']]); }, 'repetid');
lanca('temporal sem 100% nas colunas', function () use ($S, $campos) { $S::validar('TEMPORAL', array_slice($campos, 0, 4)); }, 'Cada período');
lanca('temporal com o entre somando 99%', function () use ($S, $campos) { $c = $campos; $c[0]['percentual_entre'] = 11; $S::validar('TEMPORAL', $c); }, 'entre os marcos 99%');
lanca('nivel 1 e 2 com entre negativo', function () use ($S, $campos) { $c = $campos; $c[0]['percentual_entre'] = -1; $S::validar('TEMPORAL', $c); }, 'negativ');
lanca('temporal sem G', function () use ($S, $campos) { $S::percentuais('TEMPORAL', $campos, '2016-11-01', '2018-06-01', null, '2026-01-01'); }, 'distribuição');
lanca('temporal com G depois de X', function () use ($S, $campos) { $S::percentuais('TEMPORAL', $campos, '2016-11-01', '2018-06-01', '2026-02-01', '2026-01-01'); }, 'distribuição');

// Revisao round 1
// I2: marcos obrigatorios e em ordem
lanca('temporal sem marco1 (I2)', function () use ($S, $campos) { $S::percentuais('TEMPORAL', $campos, null, '2018-06-01', '2010-01-01', '2026-09-27'); }, 'marcos');
lanca('temporal com marco1 depois de marco2 (I2)', function () use ($S, $campos) { $S::percentuais('TEMPORAL', $campos, '2018-06-01', '2016-11-01', '2010-01-01', '2026-09-27'); }, 'anterior');

// M1: nivel desconhecido e percentual negativo
lanca('temporal com nivel desconhecido (M1)', function () use ($S, $campos) { $c = $campos; $c[] = ['pessoa_id'=>9, 'nivel'=>'MARCO3', 'percentual_antes'=>0, 'percentual_depois'=>0, 'recebe_diferenca'=>'N']; $S::validar('TEMPORAL', $c); }, 'nível');
lanca('fixa com percentual negativo (M1)', function () use ($S) { $S::validar('FIXA', [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>-10,'recebe_diferenca'=>'S'], ['pessoa_id'=>2,'nivel'=>'FIXO','percentual'=>110,'recebe_diferenca'=>'N']]); }, 'negativ');
lanca('temporal com percentual antes negativo (M1)', function () use ($S, $campos) { $c = $campos; $c[1]['percentual_antes'] = -1; $S::validar('TEMPORAL', $c); }, 'negativ');

// M2: so true/'S'/'t'/1 e verdadeiro; mais de um marcado e recusado
lanca('dois marcados para diferenca (M2)', function () use ($S) { $S::validar('FIXA', [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>'S'], ['pessoa_id'=>2,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>'S']]); }, 'exatamente um');
$mix = [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>'t'], ['pessoa_id'=>2,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>'N']];
$flags = array_column($S::percentuais('FIXA', $mix, null, null, null, null)['participantes'], 'recebe_diferenca', 'pessoa_id');
igual('recebe_diferenca t = verdadeiro (M2)', true, $flags[1]);
igual('recebe_diferenca N = falso (M2)', false, $flags[2]);
$um = [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>1], ['pessoa_id'=>2,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>'N']];
igual('recebe_diferenca 1 = verdadeiro (M2)', true, array_column($S::percentuais('FIXA', $um, null, null, null, null)['participantes'], 'recebe_diferenca', 'pessoa_id')[1]);

// M4: datas com hora sao normalizadas
igual('datas com hora (M4)', 2496/6113, $S::fracaoAntes('2010-01-01 00:00:00', '2026-09-27 12:00:00', '2016-11-01'));
fim();
