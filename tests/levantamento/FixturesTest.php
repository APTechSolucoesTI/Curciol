<?php
// tests/levantamento/FixturesTest.php  (smoke das fixtures: criar + limpar nao deixam nada)
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';

igual('sem sobras antes', [], Fixtures::sobras());

$f = Fixtures::criar(['clientes' => 2, 'contratos' => 2]);
$s = Fixtures::sobras();
igual('criou 3 pessoas (devedor + 2 clientes)', 3, $s['pessoa'] ?? 0);
igual('criou 2 processos', 2, $s['processo'] ?? 0);
igual('criou 3 contratos', 3, $s['contrato'] ?? 0);
igual('criou a conta-caixa', 1, $s['conta_caixa'] ?? 0);
igual('repasse padrao tem 5 pessoas', 5, count($f['repasse']));
Fixtures::limpar($f);
igual('nada sobrou apos limpar', [], Fixtures::sobras());

TTransaction::open('escritorio');
$conn = TTransaction::get();
foreach (['requisicao_pagamento' => $f['requisicao_id'], 'requisicao_pagamento_etapa3' => $f['etapa3_id'], 'requisicao_pagamento_cliente' => $f['rpc_id']] as $t => $id) {
    igual("{$t} apagada", 0, (int) $conn->query("SELECT COUNT(*) FROM {$t} WHERE id = " . (int) $id)->fetchColumn());
}
igual('vinculo apagado', 0, (int) $conn->query('SELECT COUNT(*) FROM processo_vinculo WHERE processo_incidente_id = ' . (int) $f['processo_id'])->fetchColumn());
igual('contrato_repasse apagado', 0, (int) $conn->query('SELECT COUNT(*) FROM contrato_repasse WHERE contrato_id IN (' . implode(',', $f['contratos_ids']) . ')')->fetchColumn());
TTransaction::close();

// nome de pessoa decomposto (NFD) resolve; nome inexistente falha sem deixar nada
$g = Fixtures::criar(['repasse' => ["JOSE\u{0301} ALMIR CURCIOL" => 50, 'KLEBER CURCIOL' => 50]]);
igual('NFC/NFD resolve as 2 pessoas', 2, count($g['repasse']));
$h = Fixtures::criar(['repasse' => ["JOS\u{00C9} ALMIR CURCIOL" => 50, 'KLEBER CURCIOL' => 50]]);
igual('forma NFC resolve as mesmas pessoas', array_keys($g['repasse']), array_keys($h['repasse']));
Fixtures::limpar($h);
Fixtures::limpar($g);
lanca('pessoa inexistente falha', function () { Fixtures::criar(['repasse' => ['PESSOA QUE NAO EXISTE XYZ' => 100]]); }, 'não encontrada');
igual('falha nao deixa sobras', [], Fixtures::sobras());
ok('falha nao deixa transacao aberta', TTransaction::get() === null);
fim();
