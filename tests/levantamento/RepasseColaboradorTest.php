<?php
// tests/levantamento/RepasseColaboradorTest.php
// Repasses a colaboradores: geracao no levantamento, no pagamento parcial e na quitacao (pelas telas reais),
// estorno ao desquitar/desfazer, pagamento em lote, estorno do lote e protecoes.
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

function tela(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $saida = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    if (preg_match('/EXCECAO: (.*)/', $saida, $m)) { return $m[1]; }
    if (preg_match("/__adianti_error\\(['\"][^'\"]*['\"],\\s*['\"]([^'\"]+)/", $saida, $m)) { return $m[1]; }
    return '';
}
function post(array $dados): void { $_POST = $dados; $_REQUEST = $dados; }
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }

$S = 'LevantamentoFinanceiroService';
$R = 'RepasseColaboradorService';
TSession::setValue('userid', null);
TTransaction::open('escritorio');
$escritorio = RepasseColaboradorService::pessoaDoEscritorio(1);
$pid = function (string $nome) { $st = TTransaction::get()->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?, NFC) ORDER BY id LIMIT 1'); $st->execute([$nome]); return (int) $st->fetchColumn(); };
$jac = $pid('JOSÉ ALMIR CURCIOL'); $juan = $pid('JUAN WILLIAN DE OLIVEIRA'); $curciol = $pid('CURCIOL SOCIEDADE DE ADVOGADOS');
$cfg = FinanceiroConfiguracao::repasse();
$benef = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
$parcJuan = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
TTransaction::close();
igual('a pessoa do escritorio e a CURCIOL SOCIEDADE', $curciol, $escritorio);

$f = Fixtures::criar();
$cc = (int) $f['conta_caixa_id'];
$contaComum = null;
try {
    // ---------- 1) levantamento: Parceria Juan (5 x 20%), a parte da Curciol nao vira conta a pagar ----------
    $lf = $S::gerar($f['etapa3_id'], ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $benef, 'data_levantamento' => '2026-02-10', 'valor_levantado' => 12019.57,
        'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $parcJuan, 'data_distribuicao' => null, 'conta_caixa_id' => $cc,
        'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S', 'vencimento_cliente' => '2026-02-15',
        'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8]);
    $rep = q('SELECT r.pessoa_id, r.valor, r.levantamento_financeiro_id, l.dt_vencimento, c.pessoa_id cp, c.tipo_conta_id, c.categoria_conta_id, c.tipo_documento_financeiro_id, c.conta_origem_id, c.requisicao_pagamento_id
                FROM repasse_colaborador r JOIN conta c ON c.id = r.conta_pagar_id JOIN lancamento l ON l.id = r.lancamento_pagar_id WHERE r.conta_receber_id = ' . (int) $lf->conta_receber_id . ' ORDER BY r.pessoa_id');
    igual('levantamento: 4 contas a pagar (sem a Curciol)', 4, count($rep));
    ok('levantamento: nenhuma para o escritorio', !in_array($curciol, array_map('intval', array_column($rep, 'pessoa_id')), true));
    igual('levantamento: cada uma 464,20', [464.2, 464.2, 464.2, 464.2], array_map(function ($x) { return round((float) $x['valor'], 2); }, $rep));
    // regra da configuracao (padrao: ultimo dia do mes do recebimento)
    $vencEsperado = FinanceiroConfiguracao::vencimentoRepasse('2026-02-10', $cfg);
    ok('levantamento: vencimento pela regra da configuracao, conta a pagar em nome do colaborador, ligada a origem',
        substr($rep[0]['dt_vencimento'], 0, 10) === $vencEsperado && (int) $rep[0]['cp'] === (int) $rep[0]['pessoa_id'] && (string) $rep[0]['tipo_conta_id'] === (string) TipoConta::PAGAR
        && (int) $rep[0]['conta_origem_id'] === (int) $lf->conta_receber_id && (int) $rep[0]['requisicao_pagamento_id'] === (int) $f['requisicao_id']
        && (int) $rep[0]['categoria_conta_id'] === $cfg['categoria_id'] && (int) $rep[0]['tipo_documento_financeiro_id'] === $cfg['documento_id'] && (int) $rep[0]['levantamento_financeiro_id'] === (int) $lf->id,
        json_encode($rep[0]));
    $repJacLev = (int) q('SELECT conta_pagar_id FROM repasse_colaborador WHERE pessoa_id = ' . $jac . ' AND conta_receber_id = ' . (int) $lf->conta_receber_id)[0]['conta_pagar_id'];

    // ---------- 2) conta a receber comum (contrato) com repasse na parcela: Curciol 30%, Jac 50%, Juan 20% de 1.000 ----------
    TTransaction::open('escritorio');
    $ct = new Conta();
    $ct->tipo_conta_id = TipoConta::RECEBER; $ct->pessoa_id = $f['cliente_id']; $ct->escritorio_id = $f['escritorio_id']; $ct->contrato_id = $f['contrato_id'];
    $ct->categoria_conta_id = 24; $ct->tipo_documento_financeiro_id = 2; $ct->data_emissao = '2026-03-01'; $ct->total_conta = 2000; $ct->total_parcelas = 2;
    $ct->quitada = 'N'; $ct->descricao = 'TESTE LEVANTAMENTO contrato com repasse'; $ct->proximo_vencimento_lancamento = '2026-03-10';
    $ct->requisicao_pagamento_id = $f['requisicao_id']; // so para a limpeza das fixtures achar a conta
    $ct->store(); $contaComum = (int) $ct->id;
    $l1 = new Lancamento(); $l1->conta_id = $contaComum; $l1->parcela = 1; $l1->valor = 1000; $l1->valor_total = 1000; $l1->dt_vencimento = '2026-03-10'; $l1->tipo_pagamento_id = TipoPagamento::TRANSFERENCIA; $l1->store();
    $l2 = new Lancamento(); $l2->conta_id = $contaComum; $l2->parcela = 2; $l2->valor = 1000; $l2->valor_total = 1000; $l2->dt_vencimento = '2026-04-10'; $l2->tipo_pagamento_id = TipoPagamento::TRANSFERENCIA; $l2->store();
    foreach ([[$curciol, 30, 300], [$jac, 50, 500], [$juan, 20, 200]] as [$p, $pct, $v]) {
        $lp = new LancamentoProfissional(); $lp->lancamento_id = $l1->id; $lp->pessoa_id = $p; $lp->percentual = $pct; $lp->valor = $v; $lp->store();
    }
    TTransaction::close();
    $L1 = (int) $l1->id;

    // pagamento parcial de 400 -> Jac 200, Juan 80
    post(['id' => $L1, 'conta_caixa_id' => $cc, 'valor' => '400,00', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'dt_pagamento' => '10/03/2026', 'compensado' => 'S', 'historico' => 'TESTE LEVANTAMENTO parcial']);
    igual('parcial: sem erro', '', tela(function () use ($L1) { (new ModalQuitarParcelaParcial(['key' => $L1, 'id' => $L1]))->onPagar(['id' => $L1, 'key' => $L1]); }));
    $parc = q('SELECT pessoa_id, valor FROM repasse_colaborador WHERE lancamento_receber_id = ' . $L1 . ' ORDER BY pessoa_id');
    igual('parcial: repasses proporcionais (Jac 200, Juan 80)', [[$jac, 200.0], [$juan, 80.0]], array_map(function ($x) { return [(int) $x['pessoa_id'], round((float) $x['valor'], 2)]; }, $parc));

    // quitacao do restante com acrescimo de 10 rateado entre os profissionais -> Jac 500+5-200 = 305, Juan 200+2-80 = 122
    post(['id' => $L1, 'conta_caixa_id' => $cc, 'acrescimo' => '10,00', 'desconto' => '0,00', 'distribuicao_ajuste' => 'T', 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA,
          'dt_pagamento' => '20/03/2026', 'compensado' => 'S', 'historico' => 'TESTE LEVANTAMENTO quitacao', 'valor' => '1.000,00', 'valor_total' => '1000', 'dt_vencimento' => '10/03/2026']);
    igual('quitacao: sem erro', '', tela(function () use ($L1) { (new ModalQuitarParcela(['key' => $L1, 'id' => $L1]))->onPagar(['id' => $L1, 'key' => $L1, 'confirmar_profissional_negativo' => null]); }));
    $todos = q('SELECT pessoa_id, valor, extrato_recebimento_id FROM repasse_colaborador WHERE lancamento_receber_id = ' . $L1 . ' ORDER BY id');
    $soma = []; foreach ($todos as $x) { $soma[(int) $x['pessoa_id']] = round(($soma[(int) $x['pessoa_id']] ?? 0) + (float) $x['valor'], 2); }
    igual('quitacao: total por colaborador = parte na parcela + acrescimo rateado (Jac 505, Juan 202)', [$jac => 505.0, $juan => 202.0], [$jac => $soma[$jac] ?? null, $juan => $soma[$juan] ?? null]);
    igual('quitacao: 4 repasses na parcela (2 parciais + 2 da quitacao)', 4, count($todos));

    // desquitar a quitacao -> apaga so os repasses da quitacao; os do parcial ficam
    igual('desquitar a receber: sem erro', '', tela(function () use ($L1, $contaComum) { (new ContaFormView(['key' => $contaComum]))->onDesquitar(['id' => $L1, 'key' => $contaComum, 'confirmado' => 1]); }));
    igual('desquitar: ficam so os 2 repasses do parcial', [[$jac, 200.0], [$juan, 80.0]],
        array_map(function ($x) { return [(int) $x['pessoa_id'], round((float) $x['valor'], 2)]; }, q('SELECT pessoa_id, valor FROM repasse_colaborador WHERE lancamento_receber_id = ' . $L1 . ' ORDER BY pessoa_id')));

    // ---------- 3) quitar em lote (Contas a pagar): os dois repasses do Jac (levantamento + parcial do contrato) ----------
    TSession::setValue('userid', 1);
    $repJacContrato = (int) q('SELECT conta_pagar_id FROM repasse_colaborador WHERE pessoa_id = ' . $jac . ' AND lancamento_receber_id = ' . $L1)[0]['conta_pagar_id'];
    $repJuanContrato = (int) q('SELECT conta_pagar_id FROM repasse_colaborador WHERE pessoa_id = ' . $juan . ' AND lancamento_receber_id = ' . $L1)[0]['conta_pagar_id'];
    $saldoAntes = q("SELECT saldo_instantaneo i, saldo_nao_compensado n FROM conta_caixa WHERE id = {$cc}")[0];
    $lote = LoteFinanceiroService::quitar([$repJacLev, $repJacContrato], (string) TipoConta::PAGAR, ['data_pagamento' => '2026-03-25', 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'N', 'historico' => 'PIX unico']);
    igual('lote: total = 464,20 + 200,00', 664.2, round((float) $lote->total, 2));
    $linhas = q('SELECT saida_valor, compensado FROM extrato WHERE lote_pagamento_id = ' . (int) $lote->id . ' ORDER BY id');
    igual('lote: uma linha por conta no extrato, nao compensadas', [[464.2, 'N'], [200.0, 'N']], array_map(function ($x) { return [round((float) $x['saida_valor'], 2), $x['compensado']]; }, $linhas));
    $saldoDepois = q("SELECT saldo_instantaneo i, saldo_nao_compensado n FROM conta_caixa WHERE id = {$cc}")[0];
    igual('lote: saldo nao compensado da conta-caixa cai 664,20', round((float) $saldoAntes['n'] - 664.2, 2), round((float) $saldoDepois['n'], 2));
    lanca('quitar de novo e recusado', function () use ($repJacLev, $cc) { LoteFinanceiroService::quitar([$repJacLev], (string) TipoConta::PAGAR, ['data_pagamento' => '2026-03-25', 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA]); }, 'parcela em aberto');

    // protecoes
    lanca('conta de repasse nao pode ser editada/excluida', function () use ($repJuanContrato) { LevantamentoFinanceiroService::exigirContaNaoGerada($repJuanContrato); }, 'repasse a colaborador');
    ok('desfazer o levantamento recusado com repasse pago', mb_stripos((string) $S::podeDesfazer($f['etapa3_id']), 'colaborador') !== false, (string) $S::podeDesfazer($f['etapa3_id']));
    lanca('desfazer o levantamento lanca o mesmo motivo', function () use ($S, $f) { $S::desfazer($f['etapa3_id']); }, 'colaborador');

    // desfazer o lote (ultima acao em lote do usuario): contas voltam a aberto, linhas saem do extrato, saldo volta
    LoteFinanceiroService::desfazer((int) $lote->id, 1);
    igual('desfazer o lote: sem linhas no extrato', 0, count(q('SELECT id FROM extrato WHERE lote_pagamento_id = ' . (int) $lote->id)));
    igual('desfazer o lote: contas em aberto', ['N', 'N'], array_column(q("SELECT quitada FROM conta WHERE id IN ({$repJacLev}, {$repJacContrato}) ORDER BY id"), 'quitada'));
    igual('desfazer o lote: saldo volta', round((float) $saldoAntes['n'], 2), round((float) q("SELECT saldo_nao_compensado n FROM conta_caixa WHERE id = {$cc}")[0]['n'], 2));

    // listagem com filtros (tela/relatorio)
    TTransaction::open('escritorio');
    $lista = RepasseColaboradorService::listar(['pessoa_id' => $jac, 'requisicao_id' => $f['requisicao_id'], 'situacao' => 'aberto']);
    TTransaction::close();
    igual('listar: repasses em aberto do Jac na requisicao', 2, count($lista));
    ok('listar: origem com cliente e processo', strpos(RepasseColaboradorService::origem($lista[0]), 'Processo') !== false, RepasseColaboradorService::origem($lista[0]));

    // desfazer o levantamento agora apaga os repasses dele
    $S::desfazer($f['etapa3_id']);
    igual('desfazer: repasses do levantamento apagados', 0, count(q('SELECT id FROM repasse_colaborador WHERE levantamento_financeiro_id = ' . (int) $lf->id)));
} finally {
    Fixtures::limpar($f);
}
echo 'sobras: ' . json_encode(Fixtures::sobras()) . "\n";
fim();
