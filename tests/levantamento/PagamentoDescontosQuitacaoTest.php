<?php
// tests/levantamento/PagamentoDescontosQuitacaoTest.php
// Descontos no lancamento do pagamento (MLE = bruto - descontos), descontos do pagamento no gerar financeiro
// (so leitura, com o valor atual) e status Quitado da requisicao (trava, automatico e volta ao anterior).
require __DIR__ . '/../bootstrap.php';
TSession::setValue('programs', ['LevantamentoFinanceiroForm' => true, 'ModalContratoGerarFinanceiro' => true]); // usuario do grupo Financeiro (gera e desfaz o financeiro)
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
function um(string $sql, array $p = []) { TTransaction::open('escritorio'); $st = TTransaction::get()->prepare($sql); $st->execute($p); $r = $st->fetch(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function exec_sql(string $sql, array $p = []): void { TTransaction::open('escritorio'); TTransaction::get()->prepare($sql)->execute($p); TTransaction::close(); }

$C = 'RequisicaoPagamentoCampos';
$vazio = ['desconto_previdenciario' => null, 'desconto_assistencial' => null, 'desconto_irrf' => null];

// ---------- regra do MLE ----------
igual('MLE = bruto - descontos (em branco = 0)', ['valor_mle' => 10500.0, 'desconto_previdenciario' => 500.0, 'desconto_assistencial' => 0.0, 'desconto_irrf' => 0.0],
    $C::calcularMle(11000.0, ['desconto_previdenciario' => 500.0] + $vazio, null, null));
igual('sem descontos: MLE = bruto', 11000.0, $C::calcularMle(11000.0, $vazio, null, null)['valor_mle']);
igual('zero de desconto aceito', 0.0, $C::calcularMle(11000.0, ['desconto_irrf' => 0.0] + $vazio, null, null)['desconto_irrf']);
$antigo = ['valor_bruto_depositado' => 11000, 'valor_mle' => 10400, 'desconto_previdenciario' => null, 'desconto_assistencial' => null, 'desconto_irrf' => null];
igual('pagamento antigo sem descontos mantem o MLE gravado', ['valor_mle' => 10400.0] + $vazio, $C::calcularMle(11000.0, $vazio, 10400.0, $antigo));
igual('pagamento antigo com bruto alterado recalcula', 12000.0, $C::calcularMle(12000.0, $vazio, 10400.0, $antigo)['valor_mle']);
igual('pagamento antigo que recebe descontos recalcula', 10000.0, $C::calcularMle(11000.0, ['desconto_irrf' => 1000.0] + $vazio, 10400.0, $antigo)['valor_mle']);
igual('sem bruto: mantem o MLE informado', ['valor_mle' => 5000.0] + $vazio, $C::calcularMle(null, $vazio, 5000.0, null));
lanca('sem bruto nao aceita descontos', function () use ($C, $vazio) { $C::calcularMle(null, ['desconto_irrf' => 10.0] + $vazio, null, null); }, 'bruto');
lanca('descontos maiores que o bruto', function () use ($C, $vazio) { $C::calcularMle(100.0, ['desconto_irrf' => 100.01] + $vazio, null, null); }, 'não podem passar');
lanca('desconto negativo', function () use ($C, $vazio) { $C::calcularMle(100.0, ['desconto_assistencial' => -1.0] + $vazio, null, null); }, 'negativo');
igual('valores digitados com mascara', ['desconto_previdenciario' => 1234.56, 'desconto_assistencial' => null, 'desconto_irrf' => 0.0],
    $C::descontosDoPost(['etapa3_desconto_previdenciario_7' => '1.234,56', 'etapa3_desconto_assistencial_7' => '', 'etapa3_desconto_irrf_7' => '0,00'], 7));

$dados = function ($f) {
    TTransaction::open('escritorio');
    $b = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
    $p = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
    TTransaction::close();
    return ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $b, 'data_levantamento' => '2026-02-10', 'valor_levantado' => 12019.57,
            'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $p, 'data_distribuicao' => null, 'conta_caixa_id' => $f['conta_caixa_id'],
            'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S', 'vencimento_cliente' => '2026-02-15',
            'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8,
            // diferentes dos do pagamento: os do pagamento tem que prevalecer
            'desconto_previdenciario' => 1715.39, 'desconto_assistencial' => 0, 'desconto_irrf' => 0];
};
$status = function ($rpcId) { return um('SELECT status_requisicao_pagamento_id AS s, status_antes_quitado_id AS a FROM requisicao_pagamento_cliente WHERE id = ?', [$rpcId]); };
$quitar = function ($contaId, $q) { TTransaction::open('escritorio'); $c = new Conta($contaId); $c->quitada = $q; $c->store(); TTransaction::close(); };

$f = Fixtures::criar();   // bruto 13.195,26, MLE 11.479,87 (descontos 1.715,39), status 5, sem saldo
try {
    TTransaction::open('escritorio');
    $quitado = RequisicaoQuitacaoService::statusQuitadoId();
    TTransaction::close();
    igual('status Quitado encontrado', 7, $quitado);

    // ---------- lancar pagamento pela tela: descontos e MLE calculado ----------
    $rpc = $f['rpc_id'];
    $post = ['key' => $f['requisicao_id'], "status_{$rpc}" => 5, "etapa3_id_{$rpc}" => $f['etapa3_id'], "etapa3_numero_ciclo_{$rpc}" => 1,
             "etapa3_processo_filho_id_{$rpc}" => $f['processo_id'], "etapa3_data_deposito_{$rpc}" => '10/01/2026',
             "etapa3_valor_bruto_depositado_{$rpc}" => '13.195,26', "etapa3_valor_mle_{$rpc}" => '1,00',
             "etapa3_desconto_previdenciario_{$rpc}" => '1.000,00', "etapa3_desconto_assistencial_{$rpc}" => '500,00', "etapa3_desconto_irrf_{$rpc}" => '215,39',
             "etapa3_possui_saldo_{$rpc}" => 'N'];
    $tela = null;
    saida(function () use (&$tela, $f) { $tela = new RequisicaoPagamentoVisualizacao(['key' => $f['requisicao_id']]); });
    $msg = saida(function () use ($tela, $post) { $tela->onSave($post); });
    $e3 = um('SELECT valor_mle, desconto_previdenciario, desconto_assistencial, desconto_irrf FROM requisicao_pagamento_etapa3 WHERE id = ?', [$f['etapa3_id']]);
    igual('tela grava os descontos e o MLE = bruto - descontos', ['valor_mle' => 11479.87, 'desconto_previdenciario' => '1000.00', 'desconto_assistencial' => '500.00', 'desconto_irrf' => '215.39'],
        ['valor_mle' => round((float) $e3['valor_mle'], 2)] + array_slice($e3, 1));
    igual('sem financeiro: continua no status escolhido', ['s' => 5, 'a' => null], $status($rpc));

    $html = saida(function () use ($f) { (new RequisicaoPagamentoVisualizacao(['key' => $f['requisicao_id']]))->show(); });
    ok('tela mostra os campos de desconto', strpos($html, "etapa3_desconto_irrf_{$rpc}") !== false && strpos($html, 'Valor do MLE (bruto − descontos)') !== false);
    ok('Quitado travado com o motivo', strpos($html, 'Quitado indisponível: Gere o financeiro de todos os pagamentos.') !== false);

    $post2 = $post; $post2["status_{$rpc}"] = $quitado;
    $msg = saida(function () use ($tela, $post2) { $tela->onSave($post2); });
    ok('escolher Quitado sem a condicao e recusado', strpos($msg, 'Quitado ainda não pode ser escolhido') !== false, $msg);
    igual('status nao mudou', ['s' => 5, 'a' => null], $status($rpc));

    // ---------- gerar financeiro: descontos do pagamento, so leitura, com o valor atual ----------
    $janela = saida(function () use ($f) { (new LevantamentoFinanceiroForm(['etapa3_id' => $f['etapa3_id']]))->show(); });
    ok('gerar mostra os descontos do pagamento', strpos($janela, '1.000,00') !== false && strpos($janela, '215,39') !== false && substr_count($janela, 'lf-atual') >= 3);
    ok('descontos so leitura no gerar', preg_match('/name="desconto_irrf"[^>]*readonly/', $janela) || preg_match('/readonly[^>]*name="desconto_irrf"/', $janela));

    $lf = LevantamentoFinanceiroService::gerar($f['etapa3_id'], $dados($f));
    $ficha = um('SELECT desconto_previdenciario, desconto_assistencial, desconto_irrf FROM levantamento_financeiro WHERE id = ?', [$lf->id]);
    igual('ficha fica com os descontos do pagamento', ['desconto_previdenciario' => '1000.00', 'desconto_assistencial' => '500.00', 'desconto_irrf' => '215.39'], $ficha);

    // ---------- Quitado automatico e volta ao anterior ----------
    igual('gerado e quitado, sem saldo: vai sozinho para Quitado', ['s' => $quitado, 'a' => 5], $status($rpc));
    $quitar((int) $lf->conta_receber_id, 'N');
    igual('conta a receber desquitada: volta ao status anterior', ['s' => 5, 'a' => null], $status($rpc));
    $quitar((int) $lf->conta_receber_id, 'S');
    igual('quitada de novo: Quitado', ['s' => $quitado, 'a' => 5], $status($rpc));

    lanca('descontos nao mudam com financeiro gerado', function () use ($f) {
        LevantamentoFinanceiroService::garantirPagamentoEditavel((int) $f['etapa3_id'], ['desconto_irrf' => 1.0]);
    }, 'descontos');

    // usuario troca de Quitado para outro status: aceita e esquece o anterior; salvar sem trocar reaplica a regra
    TTransaction::open('escritorio');
    TTransaction::get()->prepare('UPDATE requisicao_pagamento_cliente SET status_requisicao_pagamento_id = 8 WHERE id = ?')->execute([$rpc]);
    RequisicaoQuitacaoService::aplicarEscolha($rpc, $quitado, 8);
    TTransaction::close();
    igual('troca manual de Quitado para outro status', ['s' => 8, 'a' => null], $status($rpc));
    TTransaction::open('escritorio'); RequisicaoQuitacaoService::aplicarEscolha($rpc, 8, 8); TTransaction::close();
    igual('salvar sem trocar o status reaplica o Quitado automatico', ['s' => $quitado, 'a' => 8], $status($rpc));

    LevantamentoFinanceiroService::desfazer($f['etapa3_id']);
    igual('financeiro desfeito: volta ao status anterior', ['s' => 8, 'a' => null], $status($rpc));

    // saldo remanescente bloqueia
    exec_sql("UPDATE requisicao_pagamento_etapa3 SET possui_saldo = 'S' WHERE id = ?", [$f['etapa3_id']]);
    TTransaction::open('escritorio'); $m = RequisicaoQuitacaoService::motivo($rpc); TTransaction::close();
    igual('com saldo remanescente nao pode quitar', 'Ainda há saldo remanescente a receber.', $m);

    // Quitado antigo (antes da regra, sem status anterior guardado) nao e mexido
    exec_sql('UPDATE requisicao_pagamento_cliente SET status_requisicao_pagamento_id = ?, status_antes_quitado_id = NULL WHERE id = ?', [$quitado, $rpc]);
    TTransaction::open('escritorio'); RequisicaoQuitacaoService::atualizar($rpc); TTransaction::close();
    igual('Quitado antigo continua Quitado', ['s' => $quitado, 'a' => null], $status($rpc));

    // cliente sem pagamento
    TTransaction::open('escritorio'); $m = RequisicaoQuitacaoService::motivo(0); TTransaction::close();
    igual('sem pagamento lancado', 'Nenhum pagamento lançado.', $m);
} finally {
    Fixtures::limpar($f);
}
fim();
