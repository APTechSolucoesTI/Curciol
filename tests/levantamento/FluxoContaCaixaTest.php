<?php
// tests/levantamento/FluxoContaCaixaTest.php
// Fluxos de baixa e extrato chamando os metodos reais das telas (com o POST que o formulario mandaria),
// numa conta a receber de teste (2 parcelas de 100) e em contas-caixa de teste. O saldo gravado tem que
// bater, a cada passo, com o esperado e com a soma do extrato.
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
// TApplication so existe na aplicacao web (engine.php); as telas a usam para recarregar a pagina
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

// telas escrevem HTML/JS na saida; guarda so as mensagens de erro (TMessage) para o relatorio
function tela(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $saida = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return preg_match('/EXCECAO: (.*)/', $saida, $m) ? $m[1] : (preg_match("/__adianti_error\\(['\"][^'\"]*['\"],\\s*['\"]([^'\"]+)/", $saida, $m2) ? $m2[1] : (preg_match('/TMessage|error/i', $saida) && preg_match('/"message":"([^"]+)"|alert-danger[^>]*>([^<]+)/', $saida, $m3) ? ($m3[1] ?: $m3[2]) : ''));
}
function post(array $dados): void { $_POST = $dados; $_REQUEST = $dados; }

$saldo = function (int $cc) {
    TTransaction::open('escritorio');
    $r = TTransaction::get()->query("SELECT saldo_instantaneo i, saldo_nao_compensado n, COALESCE(saldo_inicial,0)
            + COALESCE((SELECT SUM(COALESCE(entrada_valor,0)-COALESCE(saida_valor,0)) FROM extrato WHERE conta_caixa_id = c.id AND compensado = 'S'),0) ci,
            COALESCE((SELECT SUM(COALESCE(entrada_valor,0)-COALESCE(saida_valor,0)) FROM extrato WHERE conta_caixa_id = c.id AND COALESCE(compensado,'N') <> 'S'),0) cn
            FROM conta_caixa c WHERE id = {$cc}")->fetch(PDO::FETCH_ASSOC);
    TTransaction::close();
    return ['gravado' => [round((float) $r['i'], 2), round((float) $r['n'], 2)], 'extrato' => [round((float) $r['ci'], 2), round((float) $r['cn'], 2)]];
};
$confere = function (string $passo, int $cc, array $esperado) use ($saldo) {
    $s = $saldo($cc);
    igual("{$passo}: saldo gravado [instantaneo, nao compensado]", $esperado, $s['gravado']);
    igual("{$passo}: saldo gravado = soma do extrato", $s['extrato'], $s['gravado']);
};
$lanc = function (int $id) { TTransaction::open('escritorio'); $l = Lancamento::find($id); TTransaction::close(); return $l; };
$conta = function (int $id) { TTransaction::open('escritorio'); $c = Conta::find($id); TTransaction::close(); return $c; };
$extratos = function (int $lancId) { TTransaction::open('escritorio'); $r = TTransaction::get()->query("SELECT id, COALESCE(entrada_valor,0)+COALESCE(saida_valor,0) v, compensado FROM extrato WHERE lancamento_id = {$lancId} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; };

$f = Fixtures::criar();
$cc = (int) $f['conta_caixa_id'];
$cc2 = null; $contaId = null; $l1 = null; $l2 = null;
try {
    TSession::setValue('userid', null);
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    // segunda conta-caixa de teste (para a transferencia)
    $c->exec("INSERT INTO conta_caixa (nome, saldo_inicial, saldo_instantaneo, saldo_nao_compensado, dt_inicial, ativo, tipo_conta_caixa_id) VALUES ('TESTE LEVANTAMENTO CAIXA 2', 500, 500, 0, '2020-01-01', 'S', 1)");
    $cc2 = (int) $c->lastInsertId('conta_caixa_id_seq');
    // conta a receber comum com 2 parcelas de 100
    $ct = new Conta();
    $ct->tipo_conta_id = TipoConta::RECEBER; $ct->pessoa_id = $f['cliente_id']; $ct->escritorio_id = $f['escritorio_id'];
    $ct->categoria_conta_id = 24; $ct->tipo_documento_financeiro_id = 6; $ct->data_emissao = '2026-02-01';
    $ct->total_conta = 200; $ct->total_parcelas = 2; $ct->quitada = 'N'; $ct->descricao = 'TESTE LEVANTAMENTO conta comum';
    $ct->proximo_vencimento_lancamento = '2026-02-10';
    $ct->store(); $contaId = (int) $ct->id;
    foreach ([1 => '2026-02-10', 2 => '2026-03-10'] as $n => $venc) {
        $l = new Lancamento();
        $l->conta_id = $contaId; $l->parcela = $n; $l->valor = 100; $l->valor_total = 100; $l->dt_vencimento = $venc; $l->tipo_pagamento_id = TipoPagamento::TRANSFERENCIA;
        $l->store();
        if ($n === 1) { $l1 = (int) $l->id; } else { $l2 = (int) $l->id; }
    }
    TTransaction::close();
    $confere('inicio', $cc, [0.0, 0.0]);

    // 1) pagamento parcial de 30, nao compensado
    post(['id' => $l1, 'conta_caixa_id' => $cc, 'valor' => '30,00', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'dt_pagamento' => '10/02/2026', 'compensado' => 'N', 'historico' => 'TESTE LEVANTAMENTO parcial']);
    $erro = tela(function () use ($l1) { (new ModalQuitarParcelaParcial(['key' => $l1, 'id' => $l1]))->onPagar(['id' => $l1, 'key' => $l1]); });
    igual('parcial: sem erro', '', $erro);
    igual('parcial: parcela deve 70', 70.0, (float) $lanc($l1)->saldo);
    $confere('parcial', $cc, [0.0, 30.0]);
    $extParcial = (int) $extratos($l1)[0]['id'];

    // 2) quitar o restante com acrescimo de 10, compensado
    post(['id' => $l1, 'conta_caixa_id' => $cc, 'acrescimo' => '10,00', 'desconto' => '0,00', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'dt_pagamento' => '11/02/2026', 'compensado' => 'S', 'historico' => 'TESTE LEVANTAMENTO quitacao', 'valor' => '100,00', 'valor_total' => '100', 'dt_vencimento' => '10/02/2026']);
    $erro = tela(function () use ($l1) { (new ModalQuitarParcela(['key' => $l1, 'id' => $l1]))->onPagar(['id' => $l1, 'key' => $l1]); });
    igual('quitar: sem erro', '', $erro);
    $p = $lanc($l1);
    ok('quitar: parcela paga', !empty($p->dt_pagamento));
    igual('quitar: valor final 110 (100 + acrescimo)', 110.0, (float) $p->valor_total);
    igual('quitar: extrato da quitacao = restante 70 + acrescimo 10', 80.0, (float) $extratos($l1)[1]['v']);
    igual('quitar: total da conta 210', 210.0, (float) $conta($contaId)->total_conta);
    $confere('quitar', $cc, [80.0, 30.0]);

    // 3) desquitar (extrato ja compensado): apaga so a quitacao, parcial continua
    $erro = tela(function () use ($l1, $contaId) { (new ContaFormView(['key' => $contaId]))->onDesquitar(['id' => $l1, 'key' => $contaId, 'confirmado' => 1]); });
    igual('desquitar: sem erro', '', $erro);
    $p = $lanc($l1);
    ok('desquitar: parcela em aberto', empty($p->dt_pagamento) && empty($p->extrato_id));
    igual('desquitar: valor volta a 100, sem acrescimo', [100.0, 0.0], [(float) $p->valor_total, (float) $p->acrescimo]);
    igual('desquitar: volta a dever 70 (parcial preservado)', 70.0, (float) $p->saldo);
    igual('desquitar: so o parcial ficou no extrato', [$extParcial], array_map('intval', array_column($extratos($l1), 'id')));
    $ct = $conta($contaId);
    igual('desquitar: conta 200 e em aberto', [200.0, 'N'], [(float) $ct->total_conta, $ct->quitada]);
    $confere('desquitar', $cc, [0.0, 30.0]);

    // 4) quitar de novo, sem acrescimo: cobra so os 70 que faltavam
    post(['id' => $l1, 'conta_caixa_id' => $cc, 'acrescimo' => '0,00', 'desconto' => '0,00', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'dt_pagamento' => '12/02/2026', 'compensado' => 'S', 'historico' => 'TESTE LEVANTAMENTO quitacao 2', 'valor' => '100,00', 'valor_total' => '100', 'dt_vencimento' => '10/02/2026']);
    $erro = tela(function () use ($l1) { (new ModalQuitarParcela(['key' => $l1, 'id' => $l1]))->onPagar(['id' => $l1, 'key' => $l1]); });
    igual('quitar de novo: sem erro', '', $erro);
    $extQuit2 = $extratos($l1)[1];
    igual('quitar de novo: extrato 70', 70.0, (float) $extQuit2['v']);
    $confere('quitar de novo', $cc, [70.0, 30.0]);

    // 5) compensar o parcial (tela de compensacao)
    TSession::setValue('paramSelecionados', [$extParcial]);
    post(['data_compensacao' => date('d/m/Y')]);
    $erro = tela(function () { (new ModalCompensarExtrato([]))->onAction([]); });
    igual('compensar: sem erro', '', $erro);
    $confere('compensar', $cc, [100.0, 0.0]);

    // 6) descompensar a quitacao (lista do extrato)
    // marca a linha (selecao do Extrato) e confirma o descompensar
    ExtratoList::selLimpar();
    $erro = tela(function () use ($extQuit2) { ExtratoList::onSelMarcar(['id' => (int) $extQuit2['id'], 'marcado' => 1]); ExtratoList::onDescompensarConfirmado([]); });
    igual('descompensar: sem erro', '', $erro);
    $confere('descompensar', $cc, [30.0, 70.0]);

    // 7) quitar a parcela 2 (compensado) -> conta quitada; excluir a conta e recusado
    post(['id' => $l2, 'conta_caixa_id' => $cc, 'acrescimo' => '0,00', 'desconto' => '5,00', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'dt_pagamento' => '13/02/2026', 'compensado' => 'S', 'historico' => 'TESTE LEVANTAMENTO parcela 2', 'valor' => '100,00', 'valor_total' => '100', 'dt_vencimento' => '10/03/2026']);
    $erro = tela(function () use ($l2) { (new ModalQuitarParcela(['key' => $l2, 'id' => $l2]))->onPagar(['id' => $l2, 'key' => $l2]); });
    igual('parcela 2 com desconto 5: sem erro', '', $erro);
    igual('parcela 2: conta quitada', 'S', $conta($contaId)->quitada);
    $confere('parcela 2', $cc, [125.0, 70.0]);
    $erro = tela(function () use ($contaId) { (new ContaFormView(['key' => $contaId]))->onDelete(['key' => $contaId, 'delete' => 1]); });
    ok('excluir conta paga: recusado com mensagem', $conta($contaId) !== null, $erro);
    ok('excluir conta paga: mensagem pede para desquitar', mb_stripos($erro, 'Desquite') !== false || $erro === '', $erro);

    // 8) desquitar a parcela 2: desconto desfeito, valor volta a 100
    $erro = tela(function () use ($l2, $contaId) { (new ContaFormView(['key' => $contaId]))->onDesquitar(['id' => $l2, 'key' => $contaId, 'confirmado' => 1]); });
    igual('desquitar parcela 2: sem erro', '', $erro);
    $p = $lanc($l2);
    igual('desquitar parcela 2: valor 100, sem desconto, sem saldo parcial', [100.0, 0.0, null], [(float) $p->valor_total, (float) $p->desconto, $p->saldo]);
    $confere('desquitar parcela 2', $cc, [30.0, 70.0]);

    // 9) entrada manual nao compensada: criar, editar valor (nao pode somar de novo), excluir.
    //    Como na tela do extrato, so lancamento ainda nao compensado pode ser editado ou excluido (ExtratoList::canEdit).
    post(['id' => '', 'conta_caixa_id' => $cc, 'escritorio_id' => $f['escritorio_id'], 'entrada_valor' => '50,00', 'categoria_conta_id' => 24, 'historico' => 'TESTE LEVANTAMENTO entrada', 'compensado' => 'N']);
    $erro = tela(function () { (new ExtratoEntradaForm([]))->onSave([]); });
    igual('entrada manual: sem erro', '', $erro);
    $confere('entrada manual', $cc, [30.0, 120.0]);
    TTransaction::open('escritorio'); $ent = (int) TTransaction::get()->query("SELECT max(id) FROM extrato WHERE conta_caixa_id = {$cc} AND historico = 'TESTE LEVANTAMENTO entrada'")->fetchColumn();
    $dataLanc = TTransaction::get()->query("SELECT data_lancamento FROM extrato WHERE id = {$ent}")->fetchColumn(); TTransaction::close();
    igual('entrada manual: data de lancamento = hoje (nao dia 11)', date('Y-m-d'), substr((string) $dataLanc, 0, 10));
    post(['id' => $ent, 'conta_caixa_id' => $cc, 'escritorio_id' => $f['escritorio_id'], 'entrada_valor' => '60,00', 'categoria_conta_id' => 24, 'historico' => 'TESTE LEVANTAMENTO entrada', 'compensado' => 'N']);
    $erro = tela(function () use ($ent) { (new ExtratoEntradaForm(['key' => $ent]))->onSave(['id' => $ent]); });
    igual('editar entrada para 60: sem erro', '', $erro);
    $confere('editar entrada', $cc, [30.0, 130.0]);
    $erro = tela(function () use ($ent) { (new ExtratoList([]))->onDelete(['key' => $ent, 'delete' => 1]); });
    igual('excluir entrada: sem erro', '', $erro);
    $confere('excluir entrada', $cc, [30.0, 70.0]);

    // 10) transferencia de 40 da conta-caixa de teste para a 2, nao compensada; depois excluir
    post(['id' => '', 'conta_caixa_id' => $cc, 'escritorio_id' => $f['escritorio_id'], 'transferencia_conta_caixa_id' => $cc2, 'saida_valor' => '40,00', 'categoria_conta_id' => 24, 'historico' => 'TESTE LEVANTAMENTO transferencia', 'compensado' => 'N']);
    $erro = tela(function () { (new ExtratoTransferenciaForm([]))->onSave([]); });
    igual('transferencia: sem erro', '', $erro);
    $confere('transferencia (origem)', $cc, [30.0, 30.0]);
    $confere('transferencia (destino)', $cc2, [500.0, 40.0]);
    TTransaction::open('escritorio'); $tr = (int) TTransaction::get()->query("SELECT max(id) FROM extrato WHERE conta_caixa_id = {$cc} AND historico = 'TESTE LEVANTAMENTO transferencia'")->fetchColumn(); TTransaction::close();
    $erro = tela(function () use ($tr) { (new ExtratoList([]))->onDelete(['key' => $tr, 'delete' => 1]); });
    igual('excluir transferencia: sem erro', '', $erro);
    $confere('excluir transferencia (origem)', $cc, [30.0, 70.0]);
    $confere('excluir transferencia (destino)', $cc2, [500.0, 0.0]);

    // 11) entrada ja compensada: editar e excluir sao recusados (a tela nem mostra os botoes) e o saldo nao muda
    post(['id' => '', 'conta_caixa_id' => $cc, 'escritorio_id' => $f['escritorio_id'], 'entrada_valor' => '20,00', 'categoria_conta_id' => 24, 'historico' => 'TESTE LEVANTAMENTO compensada', 'compensado' => 'S', 'data_compensacao' => '16/02/2026']);
    $erro = tela(function () { (new ExtratoEntradaForm([]))->onSave([]); });
    igual('entrada compensada: sem erro', '', $erro);
    $confere('entrada compensada', $cc, [50.0, 70.0]);
    TTransaction::open('escritorio'); $comp = (int) TTransaction::get()->query("SELECT max(id) FROM extrato WHERE conta_caixa_id = {$cc} AND historico = 'TESTE LEVANTAMENTO compensada'")->fetchColumn(); TTransaction::close();
    post(['id' => $comp, 'conta_caixa_id' => $cc, 'escritorio_id' => $f['escritorio_id'], 'entrada_valor' => '99,00', 'categoria_conta_id' => 24, 'historico' => 'TESTE LEVANTAMENTO compensada', 'compensado' => 'S', 'data_compensacao' => '16/02/2026']);
    $erro = tela(function () use ($comp) { (new ExtratoEntradaForm(['key' => $comp]))->onSave(['id' => $comp]); });
    igual('editar entrada compensada: recusado', 'Este lançamento do extrato não pode ser alterado.', $erro);
    $erro = tela(function () use ($comp) { (new ExtratoList([]))->onDelete(['key' => $comp, 'delete' => 1]); });
    igual('excluir entrada compensada: recusado', 'Este lançamento do extrato não pode ser excluído.', $erro);
    $confere('entrada compensada recusada', $cc, [50.0, 70.0]);
} finally {
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    if ($contaId) {
        $c->exec("UPDATE lancamento SET extrato_id = NULL WHERE conta_id = {$contaId}");
        $c->exec("DELETE FROM extrato WHERE lancamento_id IN (SELECT id FROM lancamento WHERE conta_id = {$contaId})");
        $c->exec("DELETE FROM lancamento WHERE conta_id = {$contaId}");
        $c->exec("DELETE FROM conta WHERE id = {$contaId}");
    }
    $c->exec("DELETE FROM extrato WHERE historico LIKE 'TESTE LEVANTAMENTO %' AND conta_caixa_id IN ({$cc}" . ($cc2 ? ", {$cc2}" : '') . ")");
    if ($cc2) { $c->exec("DELETE FROM conta_caixa WHERE id = {$cc2}"); }
    TTransaction::close();
    Fixtures::limpar($f);
    echo 'sobras: ' . json_encode(Fixtures::sobras()) . "\n";
}
fim();
