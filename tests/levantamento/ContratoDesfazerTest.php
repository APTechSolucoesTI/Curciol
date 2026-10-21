<?php
// tests/levantamento/ContratoDesfazerTest.php
// Desfazer financeiro de um pagamento do contrato (como na requisicao de pagamento): apaga so as parcelas geradas por
// aquele pagamento, ajusta a conta (ou apaga, se ficar vazia) e o pagamento volta a "Em aberto" sem ser apagado.
// Pagamento com financeiro nao sai do cadastro nem muda valor/data/opcao/parcelas (mensagem clara em vez do erro de FK).
// Parcela ja recebida bloqueia o desfazer, com o motivo.
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

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$curciol = RepasseColaboradorService::pessoaDoEscritorio(1);
TTransaction::close();

// dois pagamentos no contrato (A e B), pagamento solidario: as parcelas dos dois vao para a mesma conta a receber
$f = Fixtures::criar(['pct_contrato' => ['20', '10']]);
$cc = (int) $f['conta_caixa_id'];
$ct = (int) $f['contrato_id'];
try {
    x("UPDATE contrato SET area_id = (SELECT MIN(id) FROM area) WHERE id = {$ct}");
    x("UPDATE contrato_pagamento_parcela SET valor = 1000, numero_parcelas = 2, data_pagamento = '2026-03-10', status_contrato_pagamento_id = 1 WHERE contrato_id = {$ct}");
    [$A, $B] = array_map(function ($r) { return (int) $r['id']; }, q("SELECT id FROM contrato_pagamento_parcela WHERE contrato_id = {$ct} ORDER BY id"));

    $gerar = function (int $pagamento, int $parcelas, string $valor) use ($ct, $f, $curciol) {
        post(['contrato_id' => $ct, 'escritorio_id' => $f['escritorio_id'], 'contrato_parcela_id' => $pagamento, 'categoria_conta_id' => 20, 'pessoa_id' => $f['cliente_id'],
              'descricao' => 'TESTE LEVANTAMENTO contrato - cliente.', 'valor' => $valor, 'dt_vencimento' => '10/03/2026', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA,
              'numero_parcelas' => (string) $parcelas, 'pagamento_solidario' => 'S', 'conta_caixa_id' => '',
              'coluna' => [(string) $curciol], 'valor_profissional' => [$valor], 'valor_repasse' => ['100,00']]);
        return saida(function () { (new ModalContratoGerarFinanceiro($_POST))->onGerar($_POST); });
    };
    // linha da lista do cadastro do contrato, como o formulario manda (__row__data)
    $linha = function (int $id, array $troca = []) {
        $r = q("SELECT * FROM contrato_pagamento_parcela WHERE id = {$id}")[0];
        $o = (object) array_merge($r, $troca);
        $o->__row__id = 'x' . $id;
        $o->__display__ = (object) ['valor' => 'R$ ' . number_format((float) $o->valor, 2, ',', '.')];
        return base64_encode(serialize($o));
    };
    $preservar = function (array $linhas) use ($ct) {
        return saida(function () use ($ct, $linhas) {
            TTransaction::open('escritorio');
            ContratoFinanceiroService::exigirPagamentosPreservados($ct, $linhas);
            TTransaction::close();
        });
    };

    igual('gerar A (2 parcelas): sem erro', '', erro($gerar($A, 2, '1.000,00')));
    igual('gerar B (1 parcela): sem erro', '', erro($gerar($B, 1, '1.000,00')));
    $contaId = (int) q("SELECT id FROM conta WHERE contrato_id = {$ct} AND tipo_conta_id = '1'")[0]['id'];
    igual('conta com 3 parcelas (A, A, B)', [[1, $A], [2, $A], [3, $B]],
        array_map(function ($l) { return [(int) $l['parcela'], (int) $l['contrato_parcela_id']]; }, q("SELECT parcela, contrato_parcela_id FROM lancamento WHERE conta_id = {$contaId} ORDER BY parcela")));

    // ---------- cadastro do contrato: pagamento com financeiro nao sai nem muda ----------
    $s = $preservar([$linha($A)]);
    ok('excluir B (com financeiro) no cadastro: mensagem clara', strpos(erro($s), 'não pode ser excluído') !== false && strpos(erro($s), 'Desfazer financeiro') !== false, $s);
    $s = $preservar([$linha($A, ['valor' => '1500']), $linha($B)]);
    ok('mudar o valor de A: mensagem diz o que mudou', strpos(erro($s), 'valor') !== false && strpos(erro($s), 'não podem ser alterados') !== false, $s);
    $s = $preservar([$linha($A, ['data_pagamento' => '2026-04-10', 'numero_parcelas' => 3]), $linha($B)]);
    ok('mudar data e parcelas de A: as duas aparecem', strpos(erro($s), 'número de parcelas') !== false && strpos(erro($s), 'data') !== false, $s);
    igual('lista sem mudancas: passa', '', erro($preservar([$linha($A), $linha($B, ['complemento_indexador' => '15'])])));
    ok('botao Excluir da lista: motivo', strpos((string) ContratoFinanceiroService::motivoNaoExcluir($B), 'não pode ser excluído') !== false);
    $s = saida(function () use ($linha, $B) { ContratoForm::onDeleteDetailContratoPagamentoParcela(['__row__data' => $linha($B), '__row__id' => 'xB']); });
    ok('botao Excluir da lista: mostra o motivo e nao tira a linha', strpos($s, '__adianti_error') !== false && strpos($s, 'não pode ser excluído') !== false && strpos($s, 'removeRowById') === false && strpos($s, 'remove_row') === false, $s);

    // ---------- desfazer B: so a parcela de B sai; a conta fica com as de A ----------
    $s = saida(function () use ($B, $ct) { ContratoFormView::onDesfazerFinanceiro(['contrato_parcela_id' => $B, 'contrato_id' => $ct]); });
    ok('desfazer pede confirmacao (com o total)', strpos($s, 'R$ 1.000,00') !== false && erro($s) === '', $s);
    $s = saida(function () use ($B, $ct) { ContratoFormView::onDesfazerFinanceiro(['contrato_parcela_id' => $B, 'contrato_id' => $ct, 'confirmado' => 1]); });
    igual('desfazer B: sem erro', '', erro($s));
    igual('B voltou para Em aberto, sem saldo, e continua no contrato', [['status_contrato_pagamento_id' => 1, 'saldo' => null]],
        array_map(function ($r) { return ['status_contrato_pagamento_id' => (int) $r['status_contrato_pagamento_id'], 'saldo' => $r['saldo']]; }, q("SELECT status_contrato_pagamento_id, saldo FROM contrato_pagamento_parcela WHERE id = {$B}")));
    igual('conta ficou so com as 2 parcelas de A', [[1, $A], [2, $A]],
        array_map(function ($l) { return [(int) $l['parcela'], (int) $l['contrato_parcela_id']]; }, q("SELECT parcela, contrato_parcela_id FROM lancamento WHERE conta_id = {$contaId} ORDER BY parcela")));
    igual('total e numero de parcelas da conta ajustados', [1000.0, 2], array_map(function ($v) { return is_numeric($v) && strpos((string) $v, '.') !== false ? round((float) $v, 2) : (int) $v; }, array_values(q("SELECT total_conta, total_parcelas FROM conta WHERE id = {$contaId}")[0])));
    igual('B sem financeiro: agora sai do cadastro', '', erro($preservar([$linha($A)])));
    igual('desfazer de novo: motivo', 'Não é possível desfazer o financeiro deste pagamento. Este pagamento não tem financeiro gerado.',
        erro(saida(function () use ($B, $ct) { ContratoFormView::onDesfazerFinanceiro(['contrato_parcela_id' => $B, 'contrato_id' => $ct]); })));

    // ---------- parcela de A recebida: bloqueia com o motivo; sem recebimento, desfaz e a conta vazia sai ----------
    $L1 = (int) q("SELECT id FROM lancamento WHERE contrato_parcela_id = {$A} ORDER BY parcela LIMIT 1")[0]['id'];
    post(['id' => $L1, 'conta_caixa_id' => $cc, 'acrescimo' => '0,00', 'desconto' => '0,00', 'distribuicao_ajuste' => 'T', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA,
          'dt_pagamento' => '10/03/2026', 'compensado' => 'S', 'historico' => 'TESTE LEVANTAMENTO quitacao contrato', 'valor' => '500,00', 'valor_total' => '500', 'dt_vencimento' => '10/03/2026']);
    igual('quitar parcela 1 de A: sem erro', '', erro(saida(function () use ($L1) { (new ModalQuitarParcela(['key' => $L1, 'id' => $L1]))->onPagar(['id' => $L1, 'key' => $L1, 'confirmar_profissional_negativo' => null]); })));
    $s = erro(saida(function () use ($A, $ct) { ContratoFormView::onDesfazerFinanceiro(['contrato_parcela_id' => $A, 'contrato_id' => $ct, 'confirmado' => 1]); }));
    ok('A com parcela recebida: bloqueado, com o motivo', strpos($s, 'A parcela 1 da conta a receber (vencimento') !== false && strpos($s, 'já foi recebida em 10/03/2026') !== false && strpos($s, 'Desquite') !== false, $s);
    igual('A continua com as 2 parcelas', 2, (int) q("SELECT count(*) n FROM lancamento WHERE contrato_parcela_id = {$A}")[0]['n']);

    // tira o recebimento direto no banco (o desquitar tem teste proprio) e desfaz A: a conta fica vazia e sai
    x("DELETE FROM repasse_colaborador WHERE lancamento_receber_id = {$L1}");
    x("UPDATE extrato SET lancamento_id = NULL WHERE lancamento_id = {$L1}");
    x("UPDATE lancamento SET dt_pagamento = NULL, extrato_id = NULL, saldo = valor WHERE id = {$L1}");
    igual('desfazer A: sem erro', '', erro(saida(function () use ($A, $ct) { ContratoFormView::onDesfazerFinanceiro(['contrato_parcela_id' => $A, 'contrato_id' => $ct, 'confirmado' => 1]); })));
    igual('conta sem parcelas foi apagada', 0, (int) q("SELECT count(*) n FROM conta WHERE id = {$contaId}")[0]['n']);
    igual('A voltou para Em aberto', 1, (int) q("SELECT status_contrato_pagamento_id s FROM contrato_pagamento_parcela WHERE id = {$A}")[0]['s']);
    igual('gerar A de novo depois de desfazer: sem erro', '', erro($gerar($A, 1, '1.000,00')));
} finally {
    Fixtures::limpar($f);
}
fim();
