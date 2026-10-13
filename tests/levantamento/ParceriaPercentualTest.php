<?php
// tests/levantamento/ParceriaPercentualTest.php
require __DIR__ . '/../bootstrap.php';
$S = 'ParceriaPercentualService';

igual('meses 01/01/2010 a 27/09/2026', 200, $S::mesesCompletos('2010-01-01', '2026-09-27'));
igual('meses no mesmo mes', 0, $S::mesesCompletos('2026-09-02', '2026-09-27'));
igual('meses dia ainda nao completo', 0, $S::mesesCompletos('2026-08-28', '2026-09-27'));
igual('meses invertido vira 0', 0, $S::mesesCompletos('2026-10-01', '2026-09-01'));

// Exemplo ID 13 do arquivo: G 01/01/2010, X 27/09/2026
igual('a (ate 01/11/2016)', 82/200, $S::fracaoAntes('2010-01-01', '2026-09-27', '2016-11-01'));
igual('p (ate 01/06/2018)', 101/200, $S::fracaoAntes('2010-01-01', '2026-09-27', '2018-06-01'));
igual('G depois do marco -> 0', 0.0, $S::fracaoAntes('2019-01-01', '2026-09-27', '2018-06-01'));
igual('X antes do marco -> 1', 1.0, $S::fracaoAntes('2010-01-01', '2016-01-01', '2016-11-01'));
igual('mesmo mes antes do marco -> 1', 1.0, $S::fracaoAntes('2016-10-02', '2016-10-20', '2016-11-01'));
igual('mesmo mes depois do marco -> 0', 0.0, $S::fracaoAntes('2018-07-02', '2018-07-20', '2018-06-01'));

$campos = [
    ['pessoa_id'=>4, 'nivel'=>'MARCO1', 'percentual_antes'=>50, 'percentual_depois'=>25, 'recebe_diferenca'=>'N'], // Dani
    ['pessoa_id'=>1, 'nivel'=>'MARCO2', 'percentual_antes'=>16, 'percentual_depois'=>30, 'recebe_diferenca'=>'S'], // Curciol
    ['pessoa_id'=>2, 'nivel'=>'MARCO2', 'percentual_antes'=>33.4, 'percentual_depois'=>25, 'recebe_diferenca'=>'N'], // Jac
    ['pessoa_id'=>3, 'nivel'=>'MARCO2', 'percentual_antes'=>25, 'percentual_depois'=>25, 'recebe_diferenca'=>'N'], // Kcur
    ['pessoa_id'=>5, 'nivel'=>'MARCO2', 'percentual_antes'=>20, 'percentual_depois'=>20, 'recebe_diferenca'=>'N'], // Campos
    ['pessoa_id'=>6, 'nivel'=>'MARCO2', 'percentual_antes'=>2.8, 'percentual_depois'=>0, 'recebe_diferenca'=>'N'], // Alliance
    ['pessoa_id'=>7, 'nivel'=>'MARCO2', 'percentual_antes'=>2.8, 'percentual_depois'=>0, 'recebe_diferenca'=>'N'], // Estrutura
];
$r = $S::percentuais('TEMPORAL', $campos, '2016-11-01', '2018-06-01', '2010-01-01', '2026-09-27');
$pct = []; foreach ($r['participantes'] as $q) { $pct[$q['pessoa_id']] = $q['percentual']; }
igual('ID13 Dani 35,25%', 0.3525, round($pct[4], 6));
igual('ID13 Curciol 14,8472%', 0.148472, round($pct[1], 6));
igual('ID13 Jac 18,9342%', 0.189342, round($pct[2], 6));
igual('ID13 Kcur 16,1875%', 0.161875, round($pct[3], 6));
igual('ID13 Campos 12,95%', 0.1295, round($pct[5], 6));
igual('ID13 Alliance 0,9156%', 0.009156, round($pct[6], 6));
igual('soma 100%', 1.0, round(array_sum($pct), 10));

// datas que na planilha davam #NUM!/99,55%: sempre 100%
$r2 = $S::percentuais('TEMPORAL', $campos, '2016-11-01', '2018-06-01', '2013-10-04', '2026-09-21');
igual('soma 100% no caso 04/10/2013', 1.0, round(array_sum(array_column($r2['participantes'], 'percentual')), 10));

$fixa = [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>20,'recebe_diferenca'=>'S'], ['pessoa_id'=>2,'nivel'=>'FIXO','percentual'=>80,'recebe_diferenca'=>'N']];
igual('fixa: percentual vira fracao', 0.8, $S::percentuais('FIXA', $fixa, null, null, null, null)['participantes'][1]['percentual']);

lanca('fixa somando 90% e recusada', function () use ($S) { $S::validar('FIXA', [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>90,'recebe_diferenca'=>'S']]); }, '100%');
lanca('sem quem recebe a diferenca', function () use ($S) { $S::validar('FIXA', [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>100,'recebe_diferenca'=>'N']]); }, 'diferença');
lanca('pessoa repetida', function () use ($S) { $S::validar('FIXA', [['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>'S'], ['pessoa_id'=>1,'nivel'=>'FIXO','percentual'=>50,'recebe_diferenca'=>'N']]); }, 'repetid');
lanca('temporal sem 100% no nivel 2', function () use ($S, $campos) { $S::validar('TEMPORAL', array_slice($campos, 0, 4)); }, 'nível 2');
lanca('temporal sem G', function () use ($S, $campos) { $S::percentuais('TEMPORAL', $campos, '2016-11-01', '2018-06-01', null, '2026-01-01'); }, 'distribuição');
lanca('temporal com G depois de X', function () use ($S, $campos) { $S::percentuais('TEMPORAL', $campos, '2016-11-01', '2018-06-01', '2026-02-01', '2026-01-01'); }, 'distribuição');
fim();
