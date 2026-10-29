<?php
// tests/agendamentos/AgendamentoFinanceiroTest.php
// Gerar financeiro do agendamento (29/10/2026): conta a receber com a parceria de repasse (so fixa), quitada na hora
// gera os repasses, em aberto gera quando quitar; nao desquita com repasse pago; nao cancela/exclui com financeiro;
// desfazer so sem recebimento; conta gerada so muda pelo desfazer; coluna Origem mostra so a origem.
// Grava de verdade na homologacao (agendamentos de teste) e apaga tudo no fim.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_AG = 'TESTE AGENDAMENTO FINANCEIRO';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}

TTransaction::open('escritorio');
$c = TTransaction::get();
if ($c->query('SELECT current_database()')->fetchColumn() !== 'curciol_homologacao') { echo "ABORTADO: so na homologacao\n"; exit(1); }
$userId = (int) $c->query('SELECT min(id) FROM system_users')->fetchColumn();
$agendaId = (int) $c->query('SELECT id FROM agenda WHERE escritorio_id IS NOT NULL ORDER BY id LIMIT 1')->fetchColumn();
$proc = $c->query('SELECT procedimento_id, parceiro_id FROM agendamento_procedimento ORDER BY id LIMIT 1')->fetch(PDO::FETCH_OBJ);
$especialidade = (int) $c->query('SELECT especialidade_id FROM agendamento WHERE especialidade_id IS NOT NULL ORDER BY id DESC LIMIT 1')->fetchColumn();
$parceria = (int) $c->query("SELECT id FROM parceria_levantamento WHERE nome = 'Previdência' AND tipo = 'FIXA'")->fetchColumn();
$temporal = (int) $c->query("SELECT id FROM parceria_levantamento WHERE tipo = 'TEMPORAL' ORDER BY id LIMIT 1")->fetchColumn();
$cc = (int) $c->query("SELECT id FROM conta_caixa WHERE COALESCE(ativo, 'S') <> 'N' AND (dt_inicial IS NULL OR dt_inicial < current_date - 1) ORDER BY id LIMIT 1")->fetchColumn();
$pix = (int) TipoPagamento::PIX;
$categoria = (int) $c->query("SELECT id FROM categoria_conta WHERE lower(nome) = 'recebimentos de agendamentos'")->fetchColumn();
$documento = (int) $c->query("SELECT id FROM tipo_documento_financeiro WHERE lower(nome) = 'agendamento'")->fetchColumn();
TTransaction::close();
TSession::setValue('userid', $userId);

$criados = ['pessoa' => [], 'agendamento' => []];
$novoAgendamento = function (string $nome, float $valor) use (&$criados, $agendaId, $proc, $especialidade) {
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $st = $c->prepare('INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES (?, 1) RETURNING id');
    $st->execute([MARCA_AG . ' ' . $nome]);
    $pessoa = (int) $st->fetchColumn();
    $st = $c->prepare("INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, especialidade_id, dt_inicial, dt_final) VALUES (?, ?, ?, ?, now(), now() + interval '30 minutes') RETURNING id");
    $st->execute([$pessoa, (int) EstadoAgenda::AGENDADO, $agendaId, $especialidade ?: null]);
    $id = (int) $st->fetchColumn();
    $c->prepare('INSERT INTO agendamento_procedimento (agendamento_id, procedimento_id, parceiro_id, quantidade, valor, valor_total) VALUES (?, ?, ?, 1, ?, ?)')
      ->execute([$id, (int) $proc->procedimento_id, (int) $proc->parceiro_id, $valor, $valor]);
    TTransaction::close();
    $criados['pessoa'][] = $pessoa;
    $criados['agendamento'][] = $id;
    return $id;
};
$dados = function (array $extra = []) use ($parceria, $cc, $pix, $categoria, $documento) {
    return $extra + ['valor' => 150.00, 'parceria_levantamento_id' => $parceria, 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => $pix,
        'categoria_conta_id' => $categoria, 'tipo_documento_financeiro_id' => $documento, 'vencimento' => date('Y-m-d'), 'quitar' => 'N', 'compensado' => 'N'];
};
$lotes = [];
$cc_saldo_recalcular = [$cc];

try {
    ok('migracao: categoria e documento do agendamento existem; parceria fixa de teste existe', $categoria > 0 && $documento > 0 && $parceria > 0 && $cc > 0 && $agendaId > 0);

    // ---------- gerar em aberto ----------
    $a1 = $novoAgendamento('ABERTO', 150);
    TTransaction::open('escritorio');
    igual('valor do agendamento = soma dos procedimentos', 150.0, AgendamentoFinanceiroService::valorDoAgendamento($a1));
    $div = AgendamentoFinanceiroService::divisao($parceria, 150.0);
    TTransaction::close();
    igual('previa: soma da divisao = valor', 150.0, round(array_sum(array_column($div, 'valor')), 2));

    lanca('parceria por tempo e recusada', function () use ($a1, $dados, $temporal) { AgendamentoFinanceiroService::gerar($a1, $dados(['parceria_levantamento_id' => $temporal])); }, 'percentual fixo');
    lanca('sem valor e recusado', function () use ($a1, $dados) { AgendamentoFinanceiroService::gerar($a1, $dados(['valor' => 0])); }, 'valor');

    $ficha = AgendamentoFinanceiroService::gerar($a1, $dados());
    $conta = q("SELECT id, quitada, total_conta, agendamento_id, tipo_conta_id FROM conta WHERE id = {$ficha->conta_receber_id}")[0];
    igual('gerar em aberto: conta a receber em aberto, ligada ao agendamento', ['N', 150.0, $a1], [$conta['quitada'], (float) $conta['total_conta'], (int) $conta['agendamento_id']]);
    $lps = q("SELECT pessoa_id, valor FROM lancamento_profissional WHERE lancamento_id = {$ficha->lancamento_id} ORDER BY id");
    igual('gerar em aberto: divisao da parceria gravada na parcela (soma = valor)', [count($div), 150.0], [count($lps), round(array_sum(array_column($lps, 'valor')), 2)]);
    igual('gerar em aberto: nenhum repasse ainda', 0, count(q("SELECT 1 FROM repasse_colaborador WHERE conta_receber_id = {$ficha->conta_receber_id}")));
    lanca('gerar de novo: recusado', function () use ($a1, $dados) { AgendamentoFinanceiroService::gerar($a1, $dados()); }, 'já tem financeiro');

    // travas
    $s = saida(function () use ($a1) { AgendamentoFormView::onCancelar(['key' => $a1]); });
    ok('com financeiro nao cancela (tela do agendamento)', strpos($s, 'não pode ser cancelado') !== false && (int) q("SELECT estado_agenda_id e FROM agendamento WHERE id = {$a1}")[0]['e'] === (int) EstadoAgenda::AGENDADO, substr(strip_tags($s), 0, 200));
    lanca('conta a receber gerada nao se edita nem exclui pela conta', function () use ($ficha) { TTransaction::open('escritorio'); try { LevantamentoFinanceiroService::exigirContaNaoGerada((int) $ficha->conta_receber_id); } finally { TTransaction::rollback(); } }, 'agendamento nº');
    ok('coluna Origem: so o agendamento', strpos(ContaListaColunas::origem((object) $conta), 'Agendamento nº ' . $a1) !== false);

    // quitar depois (fluxo normal da conta, em lote): gera os repasses, ligados ao agendamento
    $lote = LoteFinanceiroService::quitar([(int) $ficha->conta_receber_id], TipoConta::RECEBER, ['data_pagamento' => date('Y-m-d'), 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => $pix, 'compensado' => 'N']);
    $lotes[] = (int) $lote->id;
    $repasses = q("SELECT r.conta_pagar_id, r.valor, c.agendamento_id FROM repasse_colaborador r JOIN conta c ON c.id = r.conta_pagar_id WHERE r.conta_receber_id = {$ficha->conta_receber_id}");
    ok('quitar depois: gera os repasses (contas a pagar) com a origem do agendamento', count($repasses) > 0 && !array_filter($repasses, function ($r) { return (int) $r['agendamento_id'] <= 0; }), json_encode($repasses));
    igual('coluna Origem do repasse: so o agendamento', true, strpos(ContaListaColunas::origem((object) ['id' => $repasses[0]['conta_pagar_id'], 'agendamento_id' => $repasses[0]['agendamento_id']]), 'Agendamento nº') !== false);
    igual('desfazer bloqueado com a conta recebida', true, (bool) AgendamentoFinanceiroService::motivoBloqueioDesfazer($a1));

    // repasse pago: nao desquita a conta a receber
    $lotePagar = LoteFinanceiroService::quitar([(int) $repasses[0]['conta_pagar_id']], TipoConta::PAGAR, ['data_pagamento' => date('Y-m-d'), 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => $pix, 'compensado' => 'N']);
    lanca('repasse ja pago: desquitar a conta a receber e recusado', function () use ($lote, $userId) { LoteFinanceiroService::desfazer((int) $lote->id, $userId); }, 'já foi pago');
    LoteFinanceiroService::desfazer((int) $lotePagar->id, $userId);
    LoteFinanceiroService::desfazer((int) $lote->id, $userId);
    $lotes = [];
    igual('desquitado: repasses estornados e desfazer liberado', [0, null], [count(q("SELECT 1 FROM repasse_colaborador WHERE conta_receber_id = {$ficha->conta_receber_id}")), AgendamentoFinanceiroService::motivoBloqueioDesfazer($a1)]);

    AgendamentoFinanceiroService::desfazer($a1);
    igual('desfazer: conta e ficha apagadas', [0, 0], [count(q("SELECT 1 FROM conta WHERE id = {$ficha->conta_receber_id}")), count(q("SELECT 1 FROM agendamento_financeiro WHERE agendamento_id = {$a1}"))]);
    $s = saida(function () use ($a1) { AgendamentoFormView::onCancelar(['key' => $a1]); });
    igual('sem financeiro: cancela normalmente', (int) EstadoAgenda::CANCELADO, (int) q("SELECT estado_agenda_id e FROM agendamento WHERE id = {$a1}")[0]['e']);
    igual('cancelado nao gera financeiro', 'Agendamento cancelado não gera financeiro.', AgendamentoFinanceiroService::motivoNaoGerar($a1));

    // ---------- gerar ja quitado ----------
    $a2 = $novoAgendamento('QUITADO', 200);
    $ficha2 = AgendamentoFinanceiroService::gerar($a2, $dados(['valor' => 180.00, 'quitar' => 'S', 'compensado' => 'S']));
    $conta2 = q("SELECT c.quitada, l.dt_pagamento, l.extrato_id, x.compensado, x.entrada_valor FROM conta c JOIN lancamento l ON l.conta_id = c.id LEFT JOIN extrato x ON x.id = l.extrato_id WHERE c.id = {$ficha2->conta_receber_id}")[0];
    igual('gerar quitado: conta quitada, recebimento compensado no extrato com o valor editado', ['S', 'S', 180.0], [$conta2['quitada'], $conta2['compensado'], (float) $conta2['entrada_valor']]);
    $soma = (float) (q("SELECT COALESCE(SUM(valor), 0) s FROM repasse_colaborador WHERE conta_receber_id = {$ficha2->conta_receber_id}")[0]['s']);
    ok('gerar quitado: repasses gerados na hora', $soma > 0, (string) $soma);
    ok('gerar quitado: desfazer bloqueado (conta recebida)', strpos((string) AgendamentoFinanceiroService::motivoBloqueioDesfazer($a2), 'já foi recebida') !== false);
    $s = saida(function () use ($a2) { (new AgendamentoCalendarForm([]))->onDelete(['key' => $a2, 'id' => $a2, 'delete' => 1, 'view' => 'month', 'dt_inicial' => date('Y-m-d H:i')]); });
    ok('com financeiro nao exclui o agendamento', strpos($s, 'não pode ser excluído') !== false && count(q("SELECT 1 FROM agendamento WHERE id = {$a2}")) === 1, substr(strip_tags($s), 0, 200));

    // ---------- tela ----------
    TSession::setValue('programs', []);
    ok('sem a permissao (grupo Financeiro) nao pode gerar', AgendamentoFinanceiroForm::podeGerar() === false);
    TSession::setValue('programs', ['AgendamentoFinanceiroForm' => true]);
    ok('com a permissao pode gerar', AgendamentoFinanceiroForm::podeGerar() === true);
    $html = saida(function () use ($a2) { (new AgendamentoFormView(['key' => $a2]))->show(); });
    ok('tela do agendamento com financeiro: conta gerada e Desfazer financeiro', strpos($html, 'Conta a receber nº ' . $ficha2->conta_receber_id) !== false && strpos($html, 'Desfazer financeiro') !== false, substr(strip_tags($html), 0, 160));
    $a3 = $novoAgendamento('TELA', 99.9);
    $html = saida(function () use ($a3) { (new AgendamentoFormView(['key' => $a3]))->show(); });
    ok('tela do agendamento sem financeiro: botao Gerar financeiro', strpos($html, 'Gerar financeiro') !== false);
    $html = saida(function () use ($a3) { (new AgendamentoFinanceiroForm(['agendamento_id' => $a3]))->show(); });
    ok('janela: campos e valor do agendamento', strpos($html, 'parceria_levantamento_id') !== false && strpos($html, 'conta_caixa_id') !== false && strpos($html, 'quitar') !== false && strpos($html, '99,90') !== false, substr(strip_tags($html), 0, 200));
    ok('Confirmar nao gera mais conta a receber', strpos((string) file_get_contents('app/control/agendamentos/AgendamentoFormView.php'), 'AtendimentoService::gerarContaReceber') === false);
    igual('coluna Origem: requisicao mostra so a requisicao (mesmo com contrato)', 0, substr_count(ContaListaColunas::origem((object) ['requisicao_pagamento_id' => 1, 'contrato_id' => 2]), 'Contrato'));
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    try { foreach (array_reverse($lotes) as $l) { LoteFinanceiroService::desfazer($l, $userId); } } catch (Throwable $e) { echo '  (limpeza lote: ', $e->getMessage(), ")\n"; }
    if ($criados['agendamento']) {
        $ags = implode(',', $criados['agendamento']);
        TTransaction::open('escritorio');
        $c = TTransaction::get();
        $contas = array_map('intval', $c->query("SELECT id FROM conta WHERE agendamento_id IN ({$ags})")->fetchAll(PDO::FETCH_COLUMN));
        if ($contas) {
            $lista = implode(',', $contas);
            $c->exec("DELETE FROM agendamento_financeiro WHERE agendamento_id IN ({$ags})");
            $c->exec("DELETE FROM repasse_colaborador WHERE conta_receber_id IN ({$lista}) OR conta_pagar_id IN ({$lista})");
            $c->exec("UPDATE lancamento SET extrato_id = NULL WHERE conta_id IN ({$lista})");
            $c->exec("DELETE FROM lote_pagamento_item WHERE lancamento_id IN (SELECT id FROM lancamento WHERE conta_id IN ({$lista}))");
            $c->exec("DELETE FROM extrato WHERE lancamento_id IN (SELECT id FROM lancamento WHERE conta_id IN ({$lista}))");
            $c->exec("DELETE FROM lancamento_profissional WHERE lancamento_id IN (SELECT id FROM lancamento WHERE conta_id IN ({$lista}))");
            $c->exec("DELETE FROM lancamento WHERE conta_id IN ({$lista})");
            $c->exec("DELETE FROM conta_profissional WHERE conta_id IN ({$lista})");
            $c->exec("DELETE FROM conta WHERE id IN ({$lista}) AND conta_origem_id IS NOT NULL");
            $c->exec("DELETE FROM conta WHERE id IN ({$lista})");
        }
        $c->exec("DELETE FROM agendamento_financeiro WHERE agendamento_id IN ({$ags})");
        $c->exec("DELETE FROM estado_agendamento WHERE agendamento_id IN ({$ags})");
        $c->exec("DELETE FROM agendamento_procedimento WHERE agendamento_id IN ({$ags})");
        $c->exec("DELETE FROM agendamento WHERE id IN ({$ags})");
        $c->exec('DELETE FROM pessoa WHERE id IN (' . implode(',', $criados['pessoa']) . ')');
        ContaCaixaSaldoService::recalcular(...$cc_saldo_recalcular);
        TTransaction::close();
    }
}
fim();
