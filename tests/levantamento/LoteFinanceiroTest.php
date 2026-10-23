<?php
// tests/levantamento/LoteFinanceiroTest.php
// Acoes em lote em Contas a pagar/receber: quitar (pessoas diferentes no mesmo lote, uma linha no extrato por parcela),
// compensar, desfazer como Ctrl+Z (so do proprio usuario, ultima acao, 30 min, nada mexido depois),
// recebimento em lote gera repasses e o desfazer recusa se algum repasse ja foi pago, pagamento parcial volta ao desfazer.
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

function saida(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function erro(string $s): string
{
    if (preg_match('/EXCECAO: (.*)/', $s, $m)) { return $m[1]; }
    if (preg_match("/__adianti_error\\(['\"][^'\"]*['\"],\\s*['\"]([^'\"]+)/", $s, $m)) { return $m[1]; }
    return '';
}
function post(array $dados): void { $_POST = $dados; $_REQUEST = $dados; }
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

$L = 'LoteFinanceiroService';
$PAGAR = (string) TipoConta::PAGAR; $RECEBER = (string) TipoConta::RECEBER;
TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$unidade = (int) TTransaction::get()->query('SELECT system_unit_id FROM escritorio WHERE id = 1')->fetchColumn();
TSession::setValue('userunitid', $unidade); TSession::setValue('userunitids', [$unidade]); // listagens filtram pela unidade do usuario
$pid = function (string $nome) { $st = TTransaction::get()->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?, NFC) ORDER BY id LIMIT 1'); $st->execute([$nome]); return (int) $st->fetchColumn(); };
$jac = $pid('JOSÉ ALMIR CURCIOL'); $juan = $pid('JUAN WILLIAN DE OLIVEIRA'); $curciol = RepasseColaboradorService::pessoaDoEscritorio(1);
$benef = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
$parcJuan = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
TTransaction::close();
$hoje = date('Y-m-d');

$f = Fixtures::criar();
$cc = (int) $f['conta_caixa_id'];
try {
    // levantamento: conta a receber quitada, 4 contas a pagar de repasse e a conta a pagar ao cliente
    $lf = LevantamentoFinanceiroService::gerar($f['etapa3_id'], ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $benef, 'data_levantamento' => $hoje, 'valor_levantado' => 12019.57,
        'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $parcJuan, 'data_distribuicao' => null, 'conta_caixa_id' => $cc,
        'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S', 'vencimento_cliente' => $hoje,
        'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8]);
    $repasses = array_map('intval', array_column(q('SELECT conta_pagar_id FROM repasse_colaborador WHERE conta_receber_id = ' . (int) $lf->conta_receber_id . ' ORDER BY id'), 'conta_pagar_id'));
    $pagarCliente = (int) $lf->conta_pagar_id;
    $contasPagar = array_merge($repasses, [$pagarCliente]);
    $total = round((float) q('SELECT SUM(l.valor) s FROM lancamento l WHERE l.conta_id IN (' . implode(',', $contasPagar) . ')')[0]['s'], 2);

    // ---------- 1) quitar em lote: pessoas diferentes (4 colaboradores + cliente) ----------
    $janela = saida(function () use ($contasPagar, $PAGAR) { (new LoteQuitarForm(['ids' => implode(',', $contasPagar), 'tipo' => $PAGAR]))->show(); });
    ok('janela mostra 5 parcelas e o total', strpos($janela, '5 parcela(s)') !== false && strpos($janela, LoteQuitarForm::moeda($total)) !== false, erro($janela));
    lanca('conta-caixa obrigatoria', function () use ($L, $contasPagar, $PAGAR, $hoje) { $L::quitar($contasPagar, $PAGAR, ['data_pagamento' => $hoje, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA]); }, 'conta-caixa');
    lanca('data futura recusada', function () use ($L, $contasPagar, $PAGAR, $cc) { $L::quitar($contasPagar, $PAGAR, ['data_pagamento' => date('Y-m-d', strtotime('+1 day')), 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA]); }, 'futura');
    lanca('contas da outra tela recusadas', function () use ($L, $lf, $RECEBER, $cc, $hoje, $contasPagar) { $L::quitar($contasPagar, $RECEBER, ['data_pagamento' => $hoje, 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA]); }, 'desta tela');
    $lote1 = $L::quitar($contasPagar, $PAGAR, ['data_pagamento' => $hoje, 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'N', 'historico' => 'TESTE LEVANTAMENTO lote']);
    $linhas = q('SELECT x.id, x.saida_valor, x.compensado, l.conta_id FROM extrato x JOIN lancamento l ON l.id = x.lancamento_id WHERE x.lote_pagamento_id = ' . (int) $lote1->id);
    igual('uma linha no extrato por parcela, todas no lote', 5, count($linhas));
    igual('lote: total e sem pessoa unica', [$total, null], [round((float) $lote1->total, 2), $lote1->pessoa_id]);
    igual('contas quitadas', array_fill(0, 5, 'S'), array_column(q('SELECT quitada FROM conta WHERE id IN (' . implode(',', $contasPagar) . ')'), 'quitada'));
    ok('historico com lote e complemento', strpos(q('SELECT historico FROM extrato WHERE id = ' . (int) $linhas[0]['id'])[0]['historico'], 'LOTE Nº ' . $lote1->id . ' - TESTE LEVANTAMENTO lote') !== false);

    // ---------- 2) quem pode desfazer ----------
    igual('ultimo lote do usuario 1 nesta tela', (int) $lote1->id, (int) ($L::ultimoDesfazivel(1, $PAGAR)->id ?? 0));
    ok('outro usuario nao ve o lote para desfazer', $L::ultimoDesfazivel(2, $PAGAR) === null);
    ok('a outra tela nao ve o lote', $L::ultimoDesfazivel(1, $RECEBER) === null);
    lanca('outro usuario nao desfaz', function () use ($L, $lote1) { $L::desfazer((int) $lote1->id, 2); }, 'última ação em lote');

    // ---------- 3) compensar em lote e desfazer em sequencia (Ctrl+Z) ----------
    lanca('compensar antes da baixa recusado', function () use ($L, $contasPagar, $PAGAR) { $L::compensar($contasPagar, $PAGAR, date('Y-m-d', strtotime('-1 day'))); }, 'posterior à baixa');
    $lote2 = $L::compensar($contasPagar, $PAGAR, $hoje);
    igual('compensar: 5 linhas compensadas', array_fill(0, 5, 'S'), array_column(q('SELECT compensado FROM extrato WHERE lote_pagamento_id = ' . (int) $lote1->id), 'compensado'));
    lanca('nada mais a compensar', function () use ($L, $contasPagar, $PAGAR, $hoje) { $L::compensar($contasPagar, $PAGAR, $hoje); }, 'a compensar');
    igual('agora o ultimo e a compensacao', (int) $lote2->id, (int) $L::ultimoDesfazivel(1, $PAGAR)->id);
    lanca('a quitacao nao desfaz antes da compensacao', function () use ($L, $lote1) { $L::desfazer((int) $lote1->id, 1); }, 'última ação em lote');
    $L::desfazer((int) $lote2->id, 1);
    igual('desfazer compensacao: linhas voltam a nao compensadas', array_fill(0, 5, 'N'), array_column(q('SELECT compensado FROM extrato WHERE lote_pagamento_id = ' . (int) $lote1->id), 'compensado'));
    igual('depois dela, a quitacao volta a ser a ultima', (int) $lote1->id, (int) $L::ultimoDesfazivel(1, $PAGAR)->id);

    // ---------- 4) mexido depois: compensar uma linha a mao bloqueia o desfazer da quitacao ----------
    x("UPDATE extrato SET compensado = 'S', data_compensacao = '{$hoje}' WHERE id = " . (int) $linhas[0]['id']);
    lanca('linha compensada depois bloqueia', function () use ($L, $lote1) { $L::desfazer((int) $lote1->id, 1); }, 'compensado depois');
    x("UPDATE extrato SET compensado = 'N', data_compensacao = NULL WHERE id = " . (int) $linhas[0]['id']);
    $L::desfazer((int) $lote1->id, 1);
    igual('desfazer quitacao: contas em aberto e sem linhas', [array_fill(0, 5, 'N'), 0],
        [array_column(q('SELECT quitada FROM conta WHERE id IN (' . implode(',', $contasPagar) . ')'), 'quitada'), count(q('SELECT 1 FROM extrato WHERE lote_pagamento_id = ' . (int) $lote1->id))]);
    ok('nada mais para desfazer', $L::ultimoDesfazivel(1, $PAGAR) === null);

    // ---------- 5) prazo de 30 minutos ----------
    $lote3 = $L::quitar([$repasses[0]], $PAGAR, ['data_pagamento' => $hoje, 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA]);
    x("UPDATE lote_pagamento SET data_criacao = '" . date('Y-m-d H:i:s', time() - 31 * 60) . "' WHERE id = " . (int) $lote3->id);
    ok('passados 30 minutos o botao some', $L::ultimoDesfazivel(1, $PAGAR) === null);
    lanca('passados 30 minutos nao desfaz', function () use ($L, $lote3) { $L::desfazer((int) $lote3->id, 1); }, 'minutos');
    // a conta do lote vencido se desquita normalmente, uma a uma
    TTransaction::open('escritorio'); $lancR0 = (int) Lancamento::where('conta_id', '=', $repasses[0])->first()->id; TTransaction::close();
    igual('depois do prazo desquita individualmente', '', erro(saida(function () use ($lancR0, $repasses) { (new ContaFormView(['key' => $repasses[0]]))->onDesquitar(['id' => $lancR0, 'key' => $repasses[0], 'confirmado' => 1]); })));

    // ---------- 6) contas a receber: recebimento em lote gera repasses; repasse pago bloqueia o desfazer ----------
    TTransaction::open('escritorio');
    $ct = new Conta();
    $ct->tipo_conta_id = TipoConta::RECEBER; $ct->pessoa_id = $f['cliente_id']; $ct->escritorio_id = $f['escritorio_id']; $ct->contrato_id = $f['contrato_id'];
    $ct->categoria_conta_id = 24; $ct->tipo_documento_financeiro_id = 2; $ct->data_emissao = $hoje; $ct->total_conta = 1000; $ct->total_parcelas = 1;
    $ct->quitada = 'N'; $ct->descricao = 'TESTE LEVANTAMENTO receber em lote'; $ct->proximo_vencimento_lancamento = $hoje;
    $ct->store();
    $l1 = new Lancamento(); $l1->conta_id = $ct->id; $l1->parcela = 1; $l1->valor = 1000; $l1->valor_total = 1000; $l1->dt_vencimento = $hoje; $l1->tipo_pagamento_id = TipoPagamento::TRANSFERENCIA; $l1->store();
    RepasseColaboradorService::dividirParcela((int) $l1->id, [[$curciol, 30], [$jac, 50], [$juan, 20]]);
    TTransaction::close();
    $contaRec = (int) $ct->id; $L1 = (int) $l1->id;

    // pagamento parcial de 400 antes do lote
    post(['id' => $L1, 'conta_caixa_id' => $cc, 'valor' => '400,00', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'dt_pagamento' => date('d/m/Y'), 'compensado' => 'S', 'historico' => 'TESTE LEVANTAMENTO parcial']);
    igual('parcial: sem erro', '', erro(saida(function () use ($L1) { (new ModalQuitarParcelaParcial(['key' => $L1, 'id' => $L1]))->onPagar(['id' => $L1, 'key' => $L1]); })));
    igual('a pagar no lote: o saldo de 600', 600.0, round((float) $L::proximasParcelas([$contaRec])[$contaRec]->valor ?? 0, 2));
    $lote4 = $L::quitar([$contaRec], $RECEBER, ['data_pagamento' => $hoje, 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S']);
    $rep = q('SELECT pessoa_id, valor, conta_pagar_id FROM repasse_colaborador WHERE lancamento_receber_id = ' . $L1 . ' ORDER BY id');
    igual('recebimento em lote: repasses do parcial (200/80) e da quitacao (300/120)', [[$jac, 200.0], [$juan, 80.0], [$jac, 300.0], [$juan, 120.0]],
        array_map(function ($r) { return [(int) $r['pessoa_id'], round((float) $r['valor'], 2)]; }, $rep));
    // paga um repasse da quitacao (em outra tela) -> desfazer o recebimento e recusado
    $lote5 = $L::quitar([(int) $rep[2]['conta_pagar_id']], $PAGAR, ['data_pagamento' => $hoje, 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA]);
    lanca('repasse pago bloqueia desfazer o recebimento', function () use ($L, $lote4) { $L::desfazer((int) $lote4->id, 1); }, 'Estorne o pagamento ao colaborador');
    $L::desfazer((int) $lote5->id, 1);
    $L::desfazer((int) $lote4->id, 1);
    $depois = q('SELECT dt_pagamento, saldo FROM lancamento WHERE id = ' . $L1)[0];
    igual('desfazer recebimento: parcela em aberto devendo os 600 do parcial', [null, 600.0], [$depois['dt_pagamento'], round((float) $depois['saldo'], 2)]);
    igual('desfazer recebimento: so os repasses do parcial ficam', [[$jac, 200.0], [$juan, 80.0]],
        array_map(function ($r) { return [(int) $r['pessoa_id'], round((float) $r['valor'], 2)]; }, q('SELECT pessoa_id, valor FROM repasse_colaborador WHERE lancamento_receber_id = ' . $L1 . ' ORDER BY id')));

    // ---------- 7) listagens: coluna de selecao, botoes e "Desfazer ultimo lote" ----------
    $lote6 = $L::quitar([$repasses[1]], $PAGAR, ['data_pagamento' => $hoje, 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA]);
    $html = saida(function () { (new ContaPagarList([]))->show(); });
    igual('contas a pagar: sem erro', '', erro($html));
    ok('contas a pagar: botoes do lote e desfazer o ultimo', strpos($html, 'Quitar selecionadas') !== false && strpos($html, 'Compensar selecionadas') !== false && strpos($html, 'Desfazer último lote') !== false);
    ok('contas a pagar: linhas marcaveis e selecionar todas', substr_count($html, 'class="lote-sel"') > 0 && strpos($html, "id='lote-todas'") !== false && strpos($html, 'loteMarcarUma') !== false);
    $html = saida(function () { (new ContaReceberList([]))->show(); });
    ok('contas a receber: botoes do lote, sem desfazer (ultimo lote ja desfeito)', erro($html) === '' && strpos($html, 'Quitar selecionadas') !== false && strpos($html, 'Desfazer último lote') === false, erro($html));
    $s = saida(function () use ($contaRec) { (new LoteCompensarForm(['ids' => (string) $contaRec, 'tipo' => (string) TipoConta::RECEBER]))->show(); });
    ok('janela de compensar: parcial ja compensado, nada a compensar', strpos($s, 'Nenhuma das contas selecionadas tem pagamento a compensar') !== false, erro($s));
    $L::desfazer((int) $lote6->id, 1);
} finally {
    Fixtures::limpar($f);
}
fim();
