<?php
// tests/levantamento/DiferencaCurciolTest.php
// Regra de 22/10/2026: a diferenca de centavos de qualquer divisao entre colaboradores fica sempre com a Curciol.
require __DIR__ . '/../bootstrap.php';
$R = 'RepasseColaboradorService';

TTransaction::open('escritorio');
// nomes antigos tem acento decomposto (NFD): compara normalizado
$id = function ($nome) {
    $st = TTransaction::get()->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?, NFC) ORDER BY id LIMIT 1');
    $st->execute([$nome]);
    return (int) $st->fetchColumn();
};
$curciol = $id('CURCIOL SOCIEDADE DE ADVOGADOS'); $jac = $id('JOSÉ ALMIR CURCIOL'); $kleber = $id('KLEBER CURCIOL');
TTransaction::close();

ok('Curciol esta entre as pessoas da Curciol', in_array($curciol, $R::pessoasCurciol(), true));
igual('indice da diferenca = posicao da Curciol', 1, $R::indiceDaDiferenca([$jac, $curciol, $kleber], 2));
igual('sem Curciol usa o padrao', 1, $R::indiceDaDiferenca([$jac, $kleber], 1));

// levantamento: mesmo com outro participante marcado "recebe a diferenca", os centavos vao para a Curciol
$base = ['bruto' => 1000, 'mle' => 1000, 'valor_levantado' => 1000, 'regra' => BeneficiarioCredito::REGRA_SOMENTE_HONORARIOS,
         'data_deposito' => '2026-01-10', 'data_levantamento' => '2026-01-20', 'pct_honorarios' => null, 'pct_nf' => 0,
         'participantes' => [['pessoa_id' => $jac, 'percentual' => 1/3, 'recebe_diferenca' => 'S'],
                             ['pessoa_id' => $curciol, 'percentual' => 1/3, 'recebe_diferenca' => 'N'],
                             ['pessoa_id' => $kleber, 'percentual' => 1/3, 'recebe_diferenca' => 'N']]];
$r = LevantamentoCalculoService::calcular($base + ['pessoas_curciol' => $R::pessoasCurciol()]);
$v = []; foreach ($r['repasses'] as $x) { $v[$x['pessoa_id']] = $x['valor']; }
igual('levantamento: Curciol fica com o centavo', [$jac => 333.33, $curciol => 333.34, $kleber => 333.33], $v);
$r = LevantamentoCalculoService::calcular($base);
$v = []; foreach ($r['repasses'] as $x) { $v[$x['pessoa_id']] = $x['valor']; }
igual('levantamento sem a lista da Curciol: usa o marcado', [$jac => 333.34, $curciol => 333.33, $kleber => 333.33], $v);

// divisao da parcela (financeiro do contrato): Curciol no meio da lista, nao o ultimo
TTransaction::open('escritorio');
try {
    $parcela = Lancamento::where('valor', '>', 0)->orderBy('id', 'desc')->first();
    TTransaction::get()->exec('UPDATE lancamento SET valor = 100 WHERE id = ' . (int) $parcela->id);
    $R::dividirParcela((int) $parcela->id, [[$jac, 100 / 3], [$curciol, 100 / 3], [$kleber, 100 / 3]]);
    $v = []; foreach (LancamentoProfissional::where('lancamento_id', '=', $parcela->id)->load() as $lp) { $v[(int) $lp->pessoa_id] = round((float) $lp->valor, 2); }
    igual('parcela: Curciol fica com o centavo', [$jac => 33.33, $curciol => 33.34, $kleber => 33.33], $v);
    igual('parcela: soma = valor da parcela', 100.0, round(array_sum($v), 2));
} finally {
    TTransaction::rollback(); // nada fica gravado
}
fim();
