<?php
// tests/levantamento/ContaCaixaSaldoTest.php
// Saldo da conta-caixa recalculado pelo extrato (ContaCaixaSaldoService), na conta-caixa de teste das fixtures.
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';

$saldo = function (int $cc) {
    TTransaction::open('escritorio');
    $r = TTransaction::get()->query('SELECT saldo_instantaneo, saldo_nao_compensado FROM conta_caixa WHERE id = ' . $cc)->fetch(PDO::FETCH_ASSOC);
    TTransaction::close();
    return [round((float) $r['saldo_instantaneo'], 2), round((float) $r['saldo_nao_compensado'], 2)];
};
$extrato = function (int $cc, int $escritorio, float $entrada, float $saida, string $compensado) {
    $e = new Extrato();
    $e->escritorio_id = $escritorio;
    $e->conta_caixa_id = $cc;
    $e->tipo_extrato_id = $entrada > 0 ? TipoExtrato::ENTRADA : TipoExtrato::SAIDA;
    $e->entrada_valor = $entrada;
    $e->saida_valor = $saida;
    $e->data_lancamento = '2026-02-01';
    $e->data_previsao_compensacao = '2026-02-01';
    $e->compensado = $compensado;
    if ($compensado === 'S') { $e->data_compensacao = '2026-02-01'; }
    $e->historico = 'TESTE LEVANTAMENTO saldo';
    $e->store();
    return $e;
};

$f = Fixtures::criar();
try {
    $cc = (int) $f['conta_caixa_id'];

    TTransaction::open('escritorio');
    // saldo gravado errado de proposito: o recalculo tem que corrigir pelo extrato
    TTransaction::get()->exec("UPDATE conta_caixa SET saldo_inicial = 1000, saldo_instantaneo = 999999, saldo_nao_compensado = -5 WHERE id = {$cc}");
    $a = $extrato($cc, (int) $f['escritorio_id'], 300.10, 0, 'S');
    $b = $extrato($cc, (int) $f['escritorio_id'], 0, 50.05, 'S');
    $x = $extrato($cc, (int) $f['escritorio_id'], 80, 0, 'N');
    ContaCaixaSaldoService::recalcular($cc);
    TTransaction::close();
    igual('inicial + compensados; nao compensados a parte', [1250.05, 80.0], $saldo($cc));

    // compensar o nao compensado
    TTransaction::open('escritorio');
    $x = new Extrato($x->id); $x->compensado = 'S'; $x->data_compensacao = '2026-02-02'; $x->store();
    ContaCaixaSaldoService::recalcular($cc);
    TTransaction::close();
    igual('compensar move do nao compensado para o instantaneo', [1330.05, 0.0], $saldo($cc));

    // editar valor de um extrato (antes somava de novo)
    TTransaction::open('escritorio');
    $a = new Extrato($a->id); $a->entrada_valor = 200.10; $a->store();
    ContaCaixaSaldoService::recalcular($cc);
    TTransaction::close();
    igual('editar o valor nao duplica', [1230.05, 0.0], $saldo($cc));

    // apagar extrato (antes nao devolvia)
    TTransaction::open('escritorio');
    (new Extrato($b->id))->delete();
    ContaCaixaSaldoService::recalcular($cc);
    TTransaction::close();
    igual('apagar extrato devolve o saldo', [1280.10, 0.0], $saldo($cc));

    // recalcular de novo nao muda nada (idempotente) e lista de ids repetidos/vazios e aceita
    TTransaction::open('escritorio');
    ContaCaixaSaldoService::recalcular([$cc, $cc, 0, null]);
    TTransaction::close();
    igual('idempotente', [1280.10, 0.0], $saldo($cc));

    // conferencia (so leitura) traz a conta de teste batendo
    TTransaction::open('escritorio');
    $conf = array_values(array_filter(ContaCaixaSaldoService::conferir(), function ($l) use ($cc) { return $l['id'] === $cc; }))[0];
    TTransaction::close();
    igual('conferir: gravado = calculado', $conf['calculado_instantaneo'], $conf['gravado_instantaneo']);

    lanca('sem transacao aberta', function () use ($cc) { ContaCaixaSaldoService::recalcular($cc); }, 'transação aberta');
    lanca('conta-caixa inexistente', function () { TTransaction::open('escritorio'); try { ContaCaixaSaldoService::recalcular(987654321); } finally { TTransaction::rollback(); } }, 'não encontrada');
} finally {
    TTransaction::open('escritorio');
    TTransaction::get()->exec("DELETE FROM extrato WHERE conta_caixa_id = " . (int) $f['conta_caixa_id'] . " AND historico = 'TESTE LEVANTAMENTO saldo'");
    TTransaction::close();
    Fixtures::limpar($f);
}
fim();
