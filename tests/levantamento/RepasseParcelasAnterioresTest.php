<?php
// tests/levantamento/RepasseParcelasAnterioresTest.php
// 06/11/2026: parcelas anteriores a publicacao passam a gerar repasse ao quitar; colaboradores vindos do contrato ou do profissional;
// recebimento parcial proporcional; recebidas desde 23/10 geram agora; pagas antes ficam de fora; salvar a conta nao perde a ligacao.
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$pid = function (string $nome) { $st = TTransaction::get()->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?, NFC) ORDER BY id LIMIT 1'); $st->execute([$nome]); return (int) $st->fetchColumn(); };
$jac = $pid('JOSÉ ALMIR CURCIOL'); $juan = $pid('JUAN WILLIAN DE OLIVEIRA BARROS'); $curciol = $pid('CURCIOL SOCIEDADE DE ADVOGADOS');
TTransaction::close();

$f = Fixtures::criar();
$cc = (int) $f['conta_caixa_id'];
$contas = [];
$pagamento = null;
$novaConta = function (bool $comColaborador, float $valor = 1000.0, array $extra = []) use ($f, $jac, $juan, $curciol, &$contas) {
    TTransaction::open('escritorio');
    $ct = new Conta();
    $ct->tipo_conta_id = TipoConta::RECEBER; $ct->pessoa_id = $f['cliente_id']; $ct->escritorio_id = $f['escritorio_id']; $ct->contrato_id = array_key_exists('contrato_id', $extra) ? $extra['contrato_id'] : $f['contrato_id'];
    $ct->categoria_conta_id = 24; $ct->tipo_documento_financeiro_id = 2; $ct->data_emissao = date('Y-m-d'); $ct->total_conta = $valor; $ct->total_parcelas = 1;
    $ct->quitada = 'N'; $ct->descricao = 'TESTE REPASSE ANTERIOR'; $ct->proximo_vencimento_lancamento = date('Y-m-d');
    $ct->requisicao_pagamento_id = $f['requisicao_id'];
    if (!empty($extra['atendimento_id'])) { $ct->atendimento_id = $extra['atendimento_id']; $ct->profissional_id = $extra['profissional_id']; }
    $ct->store();
    $l = new Lancamento(); $l->conta_id = $ct->id; $l->parcela = 1; $l->valor = $valor; $l->valor_total = $valor; $l->saldo = $extra['saldo'] ?? $valor; $l->dt_vencimento = date('Y-m-d'); $l->tipo_pagamento_id = TipoPagamento::TRANSFERENCIA; $l->store();
    if ($comColaborador) {
        foreach ([[$curciol, 30], [$jac, 50], [$juan, 20]] as [$p, $pct]) {
            $lp = new LancamentoProfissional(); $lp->lancamento_id = $l->id; $lp->pessoa_id = $p; $lp->percentual = $pct; $lp->valor = round($valor * $pct / 100, 2); $lp->store();
        }
    }
    TTransaction::get()->exec("UPDATE lancamento SET anterior_publicacao = true WHERE id = {$l->id}");
    TTransaction::close();
    $contas[] = (int) $ct->id;
    return [(int) $ct->id, (int) $l->id];
};
$quitar = function (int $conta) use ($cc) {
    LoteFinanceiroService::quitar([$conta], (string) TipoConta::RECEBER, ['data_pagamento' => date('Y-m-d'), 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S']);
};
$repasses = function (int $conta) { return array_map(function ($r) { return [(int) $r['pessoa_id'], round((float) $r['valor'], 2)]; }, q("SELECT pessoa_id, valor FROM repasse_colaborador WHERE conta_receber_id = {$conta} ORDER BY valor DESC")); };
$marca = function (int $id) { return q("SELECT anterior_publicacao::text a FROM lancamento WHERE id = {$id}")[0]['a']; };

try {
    x("DELETE FROM contrato_repasse WHERE contrato_id = " . (int) $f['contrato_id']);
    x("INSERT INTO contrato_repasse (contrato_id, pessoa_id, percentual) VALUES ({$f['contrato_id']}, {$curciol}, 30), ({$f['contrato_id']}, {$jac}, 50), ({$f['contrato_id']}, {$juan}, 20)");

    // ---------- simular nao grava ----------
    [$cAberta, $lAberta] = $novaConta(true);
    $r = RepasseParcelasAnterioresService::executar(false, [$cAberta]);
    igual('simulacao: conta a parcela', 1, $r['parcelas']);
    igual('simulacao: marca continua', 'true', $marca($lAberta));

    // ---------- em aberto com colaborador: repasse ao quitar ----------
    RepasseParcelasAnterioresService::executar(true, [$cAberta]);
    igual('aberta: marca sai', 'false', $marca($lAberta));
    igual('aberta: nenhum repasse antes de quitar', [], $repasses($cAberta));
    $quitar($cAberta);
    igual('aberta quitada: repasse igual ao de uma parcela nova', [[$jac, 500.0], [$juan, 200.0]], $repasses($cAberta));

    // ---------- sem colaborador, conta de contrato: vem da divisao do contrato ----------
    [$cContrato, $lContrato] = $novaConta(false, 100.01);
    $r = RepasseParcelasAnterioresService::executar(true, [$cContrato]);
    igual('contrato: origem', ['contrato' => 1], $r['colaboradores']);
    igual('contrato: colaboradores na parcela, com os centavos no escritorio', [[$jac, 50.01], [$curciol, 30.0], [$juan, 20.0]],
        array_map(function ($l) { return [(int) $l['pessoa_id'], round((float) $l['valor'], 2)]; }, q("SELECT pessoa_id, valor FROM lancamento_profissional WHERE lancamento_id = {$lContrato} ORDER BY percentual DESC")));
    igual('contrato: soma dos colaboradores = valor da parcela', 100.01, round((float) q("SELECT SUM(valor) s FROM lancamento_profissional WHERE lancamento_id = {$lContrato}")[0]['s'], 2));
    igual('contrato: divisao gravada tambem na conta, para a edicao nao apagar', 3, (int) q("SELECT count(*) n FROM conta_profissional WHERE conta_id = {$cContrato}")[0]['n']);

    // ---------- sem colaborador, atendimento: 100% para o profissional ----------
    $atendimento = (int) q('SELECT id FROM atendimento ORDER BY id LIMIT 1')[0]['id'];
    [$cAt, $lAt] = $novaConta(false, 300.0, ['contrato_id' => null, 'atendimento_id' => $atendimento, 'profissional_id' => $jac]);
    $r = RepasseParcelasAnterioresService::executar(true, [$cAt]);
    igual('atendimento: origem', ['profissional' => 1], $r['colaboradores']);
    $quitar($cAt);
    igual('atendimento quitado: tudo para o profissional', [[$jac, 300.0]], $repasses($cAt));

    // ---------- recebida em parte: proporcional ao que falta ----------
    [$cParcial, $lParcial] = $novaConta(true, 1000.0, ['saldo' => 400.0]);
    $r = RepasseParcelasAnterioresService::executar(true, [$cParcial]);
    igual('parcial: relatorio', 600.0, $r['parciais'][0]['recebido']);
    igual('parcial: guarda o recebido', 600.0, (float) q("SELECT recebido_sem_repasse r FROM lancamento WHERE id = {$lParcial}")[0]['r']);
    $quitar($cParcial);
    igual('parcial quitada: so 40%', [[$jac, 200.0], [$juan, 80.0]], $repasses($cParcial));

    // ---------- recebida desde 23/10: gera agora; rodar de novo nao repete ----------
    [$cRecebida, $lRecebida] = $novaConta(true);
    $quitar($cRecebida);
    igual('recebida ainda marcada: sem repasse', [], $repasses($cRecebida));
    $r = RepasseParcelasAnterioresService::executar(true, [$cRecebida]);
    igual('recebida: repasse gerado', [[$jac, 500.0], [$juan, 200.0]], $repasses($cRecebida));
    igual('recebida: no relatorio', 700.0, $r['repasses'][0]['valor']);
    $r = RepasseParcelasAnterioresService::executar(true, [$cRecebida]);
    igual('rodar de novo: nada a fazer', 0, $r['parcelas']);
    igual('rodar de novo: nao duplica', 2, count($repasses($cRecebida)));

    // ---------- paga antes de 23/10: fica de fora ----------
    [$cVelha, $lVelha] = $novaConta(true);
    $quitar($cVelha);
    x("UPDATE lancamento SET dt_pagamento = '2026-10-01' WHERE id = {$lVelha}");
    $r = RepasseParcelasAnterioresService::executar(true, [$cVelha]);
    igual('paga antes do corte: nao entra', 0, $r['parcelas']);
    igual('paga antes do corte: marca fica', 'true', $marca($lVelha));
    igual('paga antes do corte: sem repasse', [], $repasses($cVelha));

    // ---------- salvar a conta apaga e recria a parcela aberta: nada se perde ----------
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare('INSERT INTO contrato_pagamento_parcela (contrato_id, contrato_opcao_pagamento_id, valor, numero_parcelas) VALUES (?, (SELECT MIN(contrato_opcao_pagamento_id) FROM contrato_pagamento_parcela), 1000, 1) RETURNING id');
    $st->execute([$f['contrato_id']]);
    $pagamento = (int) $st->fetchColumn();
    TTransaction::close();
    [$cEdit, $lEdit] = $novaConta(true);
    x("UPDATE lancamento SET contrato_parcela_id = {$pagamento}, recebido_sem_repasse = 10 WHERE id = {$lEdit}");
    TTransaction::open('escritorio');
    $vinculos = ContratoFinanceiroService::vinculosDasParcelas($cEdit);
    TTransaction::get()->exec("DELETE FROM lancamento_profissional WHERE lancamento_id = {$lEdit}");
    $o = Lancamento::find($lEdit); $o->delete();
    $n = new Lancamento(); $n->id = $lEdit; $n->conta_id = $cEdit; $n->parcela = 1; $n->valor = 1000; $n->valor_total = 1000; $n->dt_vencimento = date('Y-m-d'); $n->tipo_pagamento_id = TipoPagamento::TRANSFERENCIA; $n->store();
    igual('recriada sem restaurar: perderia a ligacao', true, q2($lEdit)['contrato_parcela_id'] === null);
    ContratoFinanceiroService::restaurarVinculosDasParcelas($vinculos);
    TTransaction::close();
    $d = q("SELECT contrato_parcela_id, anterior_publicacao::text a, recebido_sem_repasse r FROM lancamento WHERE id = {$lEdit}")[0];
    igual('recriada: ligacao, marca e recebido voltam', [$pagamento, 'true', 10.0], [(int) $d['contrato_parcela_id'], $d['a'], (float) $d['r']]);

    // ---------- religar a conta ao pagamento do contrato ----------
    [$cRel, $lRel] = $novaConta(true);
    $religar = function (array $mapa) { TTransaction::open('escritorio'); $r = RepasseParcelasAnterioresService::religar($mapa); TTransaction::close(); return $r; };
    igual('religar: liga a parcela sem ligacao', 1, $religar([$cRel => $pagamento])['religadas']);
    igual('religar: parcela aponta para o pagamento', $pagamento, (int) q("SELECT contrato_parcela_id c FROM lancamento WHERE id = {$lRel}")[0]['c']);
    igual('religar de novo: nada muda', 0, $religar([$cRel => $pagamento])['religadas']);
    $r = $religar([$cAt => $pagamento]);
    ok('religar: conta de outro contrato e recusada', $r['religadas'] === 0 && strpos($r['avisos'][0] ?? '', 'mesmo contrato') !== false);

    // ---------- parcela com recebimento parcial no extrato fica como esta ao salvar a conta ----------
    [$cPar, $lPar] = $novaConta(true);
    TTransaction::open('escritorio');
    ok('sem extrato: nao e parcial', !Lancamento::find($lPar)->temRecebimentoParcial());
    $ex = new Extrato(); $ex->escritorio_id = $f['escritorio_id']; $ex->conta_caixa_id = $cc; $ex->lancamento_id = $lPar; $ex->categoria_conta_id = 24; $ex->tipo_extrato_id = 1; $ex->entrada_valor = 100; $ex->data_lancamento = date('Y-m-d'); $ex->store();
    ok('com extrato e aberta: e parcial', Lancamento::find($lPar)->temRecebimentoParcial());
    TTransaction::get()->exec("UPDATE lancamento SET dt_pagamento = CURRENT_DATE WHERE id = {$lPar}");
    ok('quitada: nao e parcial', !Lancamento::find($lPar)->temRecebimentoParcial());
    TTransaction::rollback();
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    if ($contas) {
        $lista = implode(',', $contas);
        x("DELETE FROM conta_profissional WHERE conta_id IN ({$lista})");
        x("UPDATE lancamento SET contrato_parcela_id = NULL WHERE conta_id IN ({$lista})");
    }
    if ($pagamento) { x("DELETE FROM contrato_pagamento_parcela WHERE id = {$pagamento}"); }
    x("DELETE FROM contrato_repasse WHERE contrato_id = " . (int) $f['contrato_id']);
    Fixtures::limpar($f);
}

/** Leitura dentro da transacao aberta. */
function q2(int $id): array { return TTransaction::get()->query("SELECT contrato_parcela_id FROM lancamento WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC); }

fim();
