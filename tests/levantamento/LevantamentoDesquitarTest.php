<?php
// tests/levantamento/LevantamentoDesquitarTest.php
// Desquitar a conta a receber do levantamento (permitido se nada pago a jusante; apaga os repasses, que voltam ao quitar
// de novo ligados a ficha), quadro da conta a pagar ao cliente e ordem dos colaboradores da planilha.
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
function valor(string $sql, array $p = []) { TTransaction::open('escritorio'); $st = TTransaction::get()->prepare($sql); $st->execute($p); $r = $st->fetchColumn(); TTransaction::close(); return $r; }
$desquitar = function (int $contaId) {
    $lanc = (int) valor('SELECT id FROM lancamento WHERE conta_id = ? ORDER BY parcela LIMIT 1', [$contaId]);
    $tela = (new ReflectionClass('ContaFormView'))->newInstanceWithoutConstructor();
    return saida(function () use ($tela, $lanc, $contaId) { $tela->onDesquitar(['id' => $lanc, 'key' => $contaId, 'confirmado' => 1]); });
};

// ---------- vencimento das contas de repasse ----------
igual('repasse vence no ultimo dia do mes (padrao)', '2026-02-28', FinanceiroConfiguracao::vencimentoRepasse('2026-02-10', ['tipo' => FinanceiroConfiguracao::VENCIMENTO_ULTIMO_DIA_MES, 'dias' => 5]));
igual('repasse vence N dias depois (opcao)', '2026-02-15', FinanceiroConfiguracao::vencimentoRepasse('2026-02-10', ['tipo' => FinanceiroConfiguracao::VENCIMENTO_DIAS, 'dias' => 5]));

// ---------- ordem dos colaboradores ----------
TTransaction::open('escritorio');
$id = function ($nome) { $st = TTransaction::get()->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?, NFC) ORDER BY id LIMIT 1'); $st->execute([$nome]); return (int) $st->fetchColumn(); };
$pessoas = ['JUAN WILLIAN DE OLIVEIRA', 'LUMES GESTAO IMOBILIARIA LTDA', 'CURCIOL SOCIEDADE DE ADVOGADOS', 'DANIELLE DOS SANTOS MARQUES CURCIOL',
            'ALLIANCE ADMINISTRAÇÃO COMÉRCIO E ENGENHARIA LTDA', 'KLEBER CURCIOL', 'JOSÉ ALMIR CURCIOL', 'IVAN PAULO FIORANI', 'KBÇA'];
$itens = array_map(function ($n) use ($id) { return ['pessoa_id' => $id($n), 'nome' => $n]; }, $pessoas);
TTransaction::close();
$itens[] = ['pessoa_id' => 999999999, 'nome' => 'AAA FORA DA LISTA'];
$ordem = array_column(RepasseColaboradorService::ordenarPorColaborador($itens, function ($x) { return $x['pessoa_id']; }, function ($x) { return $x['nome']; }), 'nome');
igual('ordem da planilha (Curciol, Jac, Kcur, Dani, Ivan, Juan, Kbça, Alliance, Estrutura; depois os outros)', [
    'CURCIOL SOCIEDADE DE ADVOGADOS', 'JOSÉ ALMIR CURCIOL', 'KLEBER CURCIOL', 'DANIELLE DOS SANTOS MARQUES CURCIOL', 'IVAN PAULO FIORANI',
    'JUAN WILLIAN DE OLIVEIRA', 'KBÇA', 'ALLIANCE ADMINISTRAÇÃO COMÉRCIO E ENGENHARIA LTDA', 'LUMES GESTAO IMOBILIARIA LTDA', 'AAA FORA DA LISTA'], $ordem);
ok('Alliance e Lumes no grupo Parceiro', (int) valor("SELECT COUNT(*) FROM pessoa_grupo g JOIN pessoa p ON p.id = g.pessoa_id WHERE g.grupo_id = 6
    AND normalize(p.nome, NFC) IN (normalize('ALLIANCE ADMINISTRAÇÃO COMÉRCIO E ENGENHARIA LTDA', NFC), 'LUMES GESTAO IMOBILIARIA LTDA')") === 2);
igual('cadastros provisorios ALLIANCE/ESTRUTURA nao existem mais', 0, (int) valor("SELECT COUNT(*) FROM pessoa WHERE nome IN ('ALLIANCE', 'ESTRUTURA')"));

$dados = function ($f) {
    TTransaction::open('escritorio');
    $b = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
    $p = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
    TTransaction::close();
    return ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $b, 'data_levantamento' => '2026-02-10', 'valor_levantado' => 12019.57,
            'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $p, 'data_distribuicao' => null, 'conta_caixa_id' => $f['conta_caixa_id'],
            'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S', 'vencimento_cliente' => '2026-02-15',
            'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8,
            'desconto_previdenciario' => 1715.39, 'desconto_assistencial' => 0, 'desconto_irrf' => 0];
};

$f = Fixtures::criar();
try {
    $lf = LevantamentoFinanceiroService::gerar($f['etapa3_id'], $dados($f));
    $cr = (int) $lf->conta_receber_id;
    $repassesAntes = (int) valor('SELECT COUNT(*) FROM repasse_colaborador WHERE conta_receber_id = ?', [$cr]);
    ok('gerar criou repasses', $repassesAntes > 0);

    // ---------- tela da conta a receber: quadro do cliente e repasses em ordem ----------
    $v = saida(function () use ($cr) { (new ContaFormView(['key' => $cr, 'id' => $cr]))->show(); });
    ok('quadro Conta a pagar ao cliente com link', strpos($v, 'Conta a pagar ao cliente') !== false && strpos($v, 'Abrir a conta a pagar ao cliente') !== false
        && strpos($v, 'key=' . (int) $lf->conta_pagar_id) !== false);
    ok('quadro do cliente vem antes dos repasses', strpos($v, ' Conta a pagar ao cliente') < strpos($v, 'Repasses aos colaboradores'));
    $v = Normalizer::normalize($v, Normalizer::FORM_C);
    $posJac = strpos($v, 'JOSÉ ALMIR CURCIOL'); $posKleber = strpos($v, 'KLEBER CURCIOL'); $posDani = strpos($v, 'DANIELLE DOS SANTOS'); $posJuan = strpos($v, 'JUAN WILLIAN');
    ok('repasses na ordem Jac, Kcur, Dani, Juan', $posJac !== false && $posJac < $posKleber && $posKleber < $posDani && $posDani < $posJuan, json_encode([$posJac, $posKleber, $posDani, $posJuan]));

    // ---------- conta do cliente paga: desquitar recusado ----------
    TTransaction::open('escritorio');
    $parc = Lancamento::where('conta_id', '=', $lf->conta_pagar_id)->first(); $parc->dt_pagamento = '2026-02-15'; $parc->store();
    TTransaction::close();
    $msg = $desquitar($cr);
    ok('cliente pago: desquitar recusado', strpos($msg, 'conta a pagar ao cliente') !== false, $msg);
    TTransaction::open('escritorio'); $parc = Lancamento::where('conta_id', '=', $lf->conta_pagar_id)->first(); $parc->dt_pagamento = null; $parc->store(); TTransaction::close();

    // ---------- desquitar ----------
    igual('requisicao estava Quitado', 7, (int) valor('SELECT status_requisicao_pagamento_id FROM requisicao_pagamento_cliente WHERE id = ?', [$f['rpc_id']]));
    $msg = $desquitar($cr);
    igual('conta a receber desquitada', 'N', valor('SELECT quitada FROM conta WHERE id = ?', [$cr]));
    igual('repasses apagados', 0, (int) valor('SELECT COUNT(*) FROM repasse_colaborador WHERE conta_receber_id = ?', [$cr]));
    igual('extrato do recebimento apagado e ficha sem extrato', [0, null], [
        (int) valor('SELECT COUNT(*) FROM extrato WHERE id = ?', [(int) $lf->extrato_id]),
        valor('SELECT extrato_id FROM levantamento_financeiro WHERE id = ?', [$lf->id]),
    ]);
    igual('conta a pagar ao cliente apagada junto (padrao dos repasses)', [0, null], [(int) valor('SELECT COUNT(*) FROM conta WHERE id = ?', [(int) $lf->conta_pagar_id]),
        valor('SELECT conta_pagar_id FROM levantamento_financeiro WHERE id = ?', [$lf->id])]);
    igual('requisicao volta ao status anterior', 5, (int) valor('SELECT status_requisicao_pagamento_id FROM requisicao_pagamento_cliente WHERE id = ?', [$f['rpc_id']]));

    // ---------- quitar de novo (em lote): repasses voltam ligados a ficha ----------
    LoteFinanceiroService::quitar([$cr], TipoConta::RECEBER, ['data_pagamento' => date('Y-m-d'), 'conta_caixa_id' => $f['conta_caixa_id'], 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA]);
    igual('repasses gerados de novo', $repassesAntes, (int) valor('SELECT COUNT(*) FROM repasse_colaborador WHERE conta_receber_id = ?', [$cr]));
    igual('repasses ligados a ficha', $repassesAntes, (int) valor('SELECT COUNT(*) FROM repasse_colaborador WHERE conta_receber_id = ? AND levantamento_financeiro_id = ?', [$cr, $lf->id]));
    ok('ficha aponta para o novo extrato', (int) valor('SELECT extrato_id FROM levantamento_financeiro WHERE id = ?', [$lf->id]) > 0);
    igual('quitada de novo: Quitado', 7, (int) valor('SELECT status_requisicao_pagamento_id FROM requisicao_pagamento_cliente WHERE id = ?', [$f['rpc_id']]));
    $novaCliente = (int) valor('SELECT conta_pagar_id FROM levantamento_financeiro WHERE id = ?', [$lf->id]);
    ok('conta do cliente recriada', $novaCliente > 0 && $novaCliente !== (int) $lf->conta_pagar_id);
    igual('conta do cliente recriada com o valor e o vencimento original', [round((float) $lf->z, 2), '2026-02-15'], [
        round((float) valor('SELECT total_conta FROM conta WHERE id = ?', [$novaCliente]), 2),
        valor('SELECT dt_vencimento FROM lancamento WHERE conta_id = ?', [$novaCliente]),
    ]);
    ok('conta do cliente ligada ao novo recebimento', (int) valor('SELECT extrato_recebimento_id FROM levantamento_conta_cliente WHERE conta_pagar_id = ?', [$novaCliente])
        === (int) valor('SELECT extrato_id FROM levantamento_financeiro WHERE id = ?', [$lf->id]));
    igual('repasses vencem no ultimo dia do mes do recebimento', date('Y-m-t'), valor('SELECT MIN(l.dt_vencimento) FROM repasse_colaborador r JOIN lancamento l ON l.id = r.lancamento_pagar_id WHERE r.conta_receber_id = ?', [$cr]));

    // ---------- vencimento: qualquer conta em aberto pode mudar; paga nao ----------
    $lancCliente = (int) valor('SELECT id FROM lancamento WHERE conta_id = ?', [$novaCliente]);
    saida(function () use ($lancCliente) { ContaFormView::onSalvarVencimento(['lancamento_id' => $lancCliente, 'novo_vencimento' => '31/12/2026']); });
    igual('vencimento da conta do cliente alterado', ['2026-12-31', '2026-12-31'], [valor('SELECT dt_vencimento FROM lancamento WHERE id = ?', [$lancCliente]),
        valor('SELECT proximo_vencimento_lancamento FROM conta WHERE id = ?', [$novaCliente])]);
    $lancReceber = (int) valor('SELECT id FROM lancamento WHERE conta_id = ?', [$cr]);
    $msg = saida(function () use ($lancReceber) { ContaFormView::onSalvarVencimento(['lancamento_id' => $lancReceber, 'novo_vencimento' => '31/12/2026']); });
    ok('parcela quitada nao muda o vencimento', strpos($msg, 'ainda não foi quitada') !== false, $msg);

    // ---------- recebimento parcial: conta do cliente proporcional; a quitacao gera o restante ----------
    $desquitar($cr);
    igual('desquitada de novo: sem conta do cliente', 0, (int) valor('SELECT COUNT(*) FROM levantamento_conta_cliente WHERE levantamento_financeiro_id = ?', [$lf->id]));
    TTransaction::open('escritorio');
    $receber = new Conta($cr);
    $parcelaReceber = Lancamento::where('conta_id', '=', $cr)->first();
    $meio = round((float) $lf->valor_levantado / 2, 2);
    $parcial = LevantamentoFinanceiroService::gerarContaClientePorRecebimento((int) $lf->id, $receber, $parcelaReceber, 0, $meio, date('Y-m-d'), false);
    $resto = LevantamentoFinanceiroService::gerarContaClientePorRecebimento((int) $lf->id, $receber, $parcelaReceber, 0, $meio, date('Y-m-d'), true);
    $nada = LevantamentoFinanceiroService::gerarContaClientePorRecebimento((int) $lf->id, $receber, $parcelaReceber, 0, 1.0, date('Y-m-d'), true);
    TTransaction::close();
    $vParcial = round((float) valor('SELECT total_conta FROM conta WHERE id = ?', [$parcial]), 2);
    $vResto = round((float) valor('SELECT total_conta FROM conta WHERE id = ?', [$resto]), 2);
    igual('parcial proporcional ao recebido', round((float) $lf->z * $meio / (float) $lf->valor_levantado, 2), $vParcial);
    igual('quitacao gera o restante (soma = valor ao cliente)', round((float) $lf->z, 2), round($vParcial + $vResto, 2));
    igual('nada mais a gerar depois de completar', null, $nada);
    $v = saida(function () use ($cr) { (new ContaFormView(['key' => $cr, 'id' => $cr]))->show(); });
    igual('quadro lista as duas contas do cliente', 2, substr_count($v, 'Abrir a conta a pagar ao cliente'));
    LevantamentoFinanceiroService::desfazer((int) $f['etapa3_id']);
    igual('desfazer apaga todas as contas do cliente', 0, (int) valor('SELECT COUNT(*) FROM conta WHERE id IN (?, ?)', [$parcial, $resto]));
} finally { Fixtures::limpar($f); }
fim();
