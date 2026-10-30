<?php
// tests/agendamentos/AgendamentoFinanceiroTest.php
// Gerar financeiro do agendamento (29/10/2026): conta a receber com a parceria de repasse (so fixa), quitada na hora
// gera os repasses, em aberto gera quando quitar; nao desquita com repasse pago; nao cancela/exclui com financeiro;
// desfazer so sem recebimento; conta gerada so muda pelo desfazer; coluna Origem mostra so a origem.
// 30/10/2026: parcerias so as do colaborador da agenda; com financeiro, agenda e parceria travadas; aba Financeiro so
// para quem gera (grupos Financeiro e Financeiro do agendamento, com a Vania).
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
$pix = (int) $c->query("SELECT id FROM tipo_pagamento WHERE COALESCE(ativo, 'S') <> 'N' AND id <> " . (int) TipoPagamento::CHEQUE . " ORDER BY id LIMIT 1")->fetchColumn();
$categoria = (int) $c->query("SELECT id FROM categoria_conta WHERE lower(nome) = 'recebimentos de agendamentos'")->fetchColumn();
$documento = (int) $c->query("SELECT id FROM tipo_documento_financeiro WHERE nome = 'Atendimento'")->fetchColumn();
$unidade = (int) $c->query("SELECT e.system_unit_id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id WHERE a.id = {$agendaId}")->fetchColumn();
TTransaction::close();
TSession::setValue('userid', $userId);
TSession::setValue('userunitid', $unidade);

/** Apaga tudo o que o teste criou (pelo nome dos clientes de teste): agendamentos, contas, repasses, lotes, extratos. */
function limparAgendamentoFinanceiro(): void
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $ids = function (string $sql) use ($c) { return array_map('intval', $c->query($sql)->fetchAll(PDO::FETCH_COLUMN)); };
    $lista = function (array $l) { return $l ? implode(',', $l) : '0'; };
    $pessoas = $ids("SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_AG . "%'");
    if (!$pessoas) { TTransaction::close(); return; }
    $ags = $ids('SELECT id FROM agendamento WHERE cliente_id IN (' . $lista($pessoas) . ')');
    $contas = $ids('SELECT id FROM conta WHERE agendamento_id IN (' . $lista($ags) . ') OR pessoa_id IN (' . $lista($pessoas) . ')');
    $lancs = $ids('SELECT id FROM lancamento WHERE conta_id IN (' . $lista($contas) . ')');
    $lotes = $ids('SELECT lote_pagamento_id FROM lote_pagamento_item WHERE lancamento_id IN (' . $lista($lancs) . ') UNION SELECT id FROM lote_pagamento WHERE pessoa_id IN (' . $lista($pessoas) . ')');
    $caixas = $ids('SELECT DISTINCT conta_caixa_id FROM extrato WHERE lancamento_id IN (' . $lista($lancs) . ')');
    $c->exec('DELETE FROM agendamento_financeiro WHERE agendamento_id IN (' . $lista($ags) . ')');
    $c->exec('DELETE FROM repasse_colaborador WHERE conta_receber_id IN (' . $lista($contas) . ') OR conta_pagar_id IN (' . $lista($contas) . ')');
    $c->exec('UPDATE lancamento SET extrato_id = NULL WHERE id IN (' . $lista($lancs) . ')');
    $c->exec('DELETE FROM lote_pagamento_item WHERE lote_pagamento_id IN (' . $lista($lotes) . ') OR lancamento_id IN (' . $lista($lancs) . ')');
    $c->exec('DELETE FROM extrato WHERE lancamento_id IN (' . $lista($lancs) . ') OR lote_pagamento_id IN (' . $lista($lotes) . ')');
    $c->exec('DELETE FROM lote_pagamento WHERE id IN (' . $lista($lotes) . ')');
    $c->exec('DELETE FROM lancamento_profissional WHERE lancamento_id IN (' . $lista($lancs) . ')');
    $c->exec('DELETE FROM lancamento WHERE id IN (' . $lista($lancs) . ')');
    $c->exec('DELETE FROM conta_profissional WHERE conta_id IN (' . $lista($contas) . ')');
    $c->exec('DELETE FROM conta WHERE id IN (' . $lista($contas) . ') AND conta_origem_id IS NOT NULL');
    $c->exec('DELETE FROM conta WHERE id IN (' . $lista($contas) . ')');
    $c->exec('DELETE FROM estado_agendamento WHERE agendamento_id IN (' . $lista($ags) . ')');
    $c->exec('DELETE FROM atendimento WHERE agendamento_id IN (' . $lista($ags) . ')');
    $c->exec('DELETE FROM agendamento_procedimento WHERE agendamento_id IN (' . $lista($ags) . ')');
    $c->exec('DELETE FROM agendamento WHERE id IN (' . $lista($ags) . ')');
    $c->exec('DELETE FROM pessoa WHERE id IN (' . $lista($pessoas) . ')');
    if ($caixas) { ContaCaixaSaldoService::recalcular(...$caixas); }
    TTransaction::close();
}
limparAgendamentoFinanceiro(); // sobras de uma rodada interrompida

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
        'categoria_conta_id' => $categoria, 'tipo_documento_financeiro_id' => $documento, 'vencimento' => date('Y-m-d'), 'quitacao' => 'N'];
};
$lotes = [];

try {
    ok('migracao: categoria do agendamento e documento Atendimento existem; parceria fixa de teste existe', $categoria > 0 && $documento > 0 && $parceria > 0 && $cc > 0 && $agendaId > 0);
    igual('migracao: o tipo de documento Agendamento saiu', 0, count(q("SELECT 1 FROM tipo_documento_financeiro WHERE nome = 'Agendamento' AND codigo = 'Agd'")));
    TTransaction::open('escritorio');
    $part = AgendamentoFinanceiroService::participantes($parceria);
    TTransaction::close();
    igual('participantes da parceria: somam 100% e um fica com a diferenca', [100.0, 1], [round(array_sum(array_column($part, 'percentual')), 2), count(array_filter(array_column($part, 'diferenca')))]);

    // ---------- gerar em aberto ----------
    $a1 = $novoAgendamento('ABERTO', 150);
    TTransaction::open('escritorio');
    igual('valor do agendamento = soma dos procedimentos', 150.0, AgendamentoFinanceiroService::valorDoAgendamento($a1));
    $div = AgendamentoFinanceiroService::divisao($parceria, 150.0);
    TTransaction::close();
    igual('previa: soma da divisao = valor', 150.0, round(array_sum(array_column($div, 'valor')), 2));

    lanca('parceria por tempo e recusada', function () use ($a1, $dados, $temporal) { AgendamentoFinanceiroService::gerar($a1, $dados(['parceria_levantamento_id' => $temporal])); }, 'percentual fixo');
    lanca('sem valor e recusado', function () use ($a1, $dados) { AgendamentoFinanceiroService::gerar($a1, $dados(['valor' => 0])); }, 'valor');
    lanca('sem informar se esta quitado e recusado', function () use ($a1, $dados) { AgendamentoFinanceiroService::gerar($a1, $dados(['quitacao' => 'X'])); }, 'quitado');

    $ficha = AgendamentoFinanceiroService::gerar($a1, $dados());
    igual('agendamento sem parceria: a escolhida no gerar fica gravada nele', $parceria, (int) q("SELECT parceria_levantamento_id p FROM agendamento WHERE id = {$a1}")[0]['p']);
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
    $ficha2 = AgendamentoFinanceiroService::gerar($a2, $dados(['valor' => 180.00, 'quitacao' => 'C']));
    $conta2 = q("SELECT c.quitada, l.dt_pagamento, l.extrato_id, x.compensado, x.entrada_valor FROM conta c JOIN lancamento l ON l.conta_id = c.id LEFT JOIN extrato x ON x.id = l.extrato_id WHERE c.id = {$ficha2->conta_receber_id}")[0];
    igual('gerar quitado: conta quitada, recebimento compensado no extrato com o valor editado', ['S', 'S', 180.0], [$conta2['quitada'], $conta2['compensado'], (float) $conta2['entrada_valor']]);
    $soma = (float) (q("SELECT COALESCE(SUM(valor), 0) s FROM repasse_colaborador WHERE conta_receber_id = {$ficha2->conta_receber_id}")[0]['s']);
    ok('gerar quitado: repasses gerados na hora', $soma > 0, (string) $soma);
    ok('gerar quitado: desfazer bloqueado (conta recebida)', strpos((string) AgendamentoFinanceiroService::motivoBloqueioDesfazer($a2), 'Estorne/desquite') !== false);
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
    ok('janela: campos e valor do agendamento', strpos($html, 'parceria_levantamento_id') !== false && strpos($html, 'conta_caixa_id') !== false && strpos($html, 'quitacao') !== false && strpos($html, 'Sim, já quitado e compensado') !== false && strpos($html, '99,90') !== false, substr(strip_tags($html), 0, 200));
    // ---------- parceria escolhida no agendamento ----------
    $a4 = $novoAgendamento('PARCERIA', 120);
    $outra = (int) q("SELECT id FROM parceria_levantamento WHERE tipo = 'FIXA' AND ativo = 'S' AND id <> {$parceria} ORDER BY id LIMIT 1")[0]['id'];
    TTransaction::open('escritorio'); TTransaction::get()->exec("UPDATE agendamento SET parceria_levantamento_id = {$parceria} WHERE id = {$a4}"); TTransaction::close();
    $html = saida(function () use ($a4) { (new AgendamentoFormView(['key' => $a4]))->show(); });
    ok('aba Financeiro: parceria do agendamento e divisao por colaborador', strpos($html, 'Previdência') !== false && strpos($html, 'Divisão por colaborador') !== false && strpos($html, 'Porcentagem') !== false, substr(strip_tags($html), 0, 200));
    $html = saida(function () use ($a4) { (new AgendamentoFinanceiroForm(['agendamento_id' => $a4]))->show(); });
    ok('janela: parceria do agendamento so mostrada (sem combo) e divisao ja aparece', strpos($html, 'Previdência') !== false && strpos($html, 'parceria_levantamento_id') !== false && !preg_match('/<select[^>]*name="parceria_levantamento_id"/', $html) && strpos($html, 'repasse (conta a pagar)') !== false, substr(strip_tags($html), 0, 200));
    $html = saida(function () use ($a4) { $t = new AgendamentoCalendarForm([]); $t->onEdit(['key' => $a4]); $t->show(); });
    ok('edicao do agendamento: Parceria de repasse junto dos procedimentos, com a divisao', strpos($html, 'Parceria de repasse') !== false && strpos($html, 'agp_divisao_form_') !== false && strpos($html, 'Porcentagem') !== false, substr(strip_tags($html), 0, 200));
    $html = saida(function () { (new AgendamentoFormBtn([]))->show(); });
    ok('novo agendamento: Parceria de repasse junto dos procedimentos', strpos($html, 'Parceria de repasse') !== false && strpos($html, 'agp_divisao_form_') !== false, substr(strip_tags($html), 0, 200));
    $ficha4 = AgendamentoFinanceiroService::gerar($a4, $dados(['valor' => 120.00, 'quitacao' => 'S', 'parceria_levantamento_id' => $outra]));
    igual('gerar usa a parceria do agendamento (nao a enviada)', $parceria, (int) $ficha4->parceria_levantamento_id);
    $conta4 = q("SELECT c.quitada, x.compensado FROM conta c JOIN lancamento l ON l.conta_id = c.id LEFT JOIN extrato x ON x.id = l.extrato_id WHERE c.id = {$ficha4->conta_receber_id}")[0];
    igual('Sim, ja quitado: quitada e a compensar', ['S', 'N'], [$conta4['quitada'], $conta4['compensado']]);

    // ---------- agenda e parceria (30/10/2026) ----------
    $troca = function (int $ag, int $agenda, int $parc) {
        TTransaction::open('escritorio');
        try { AgendamentoFinanceiroService::exigirAgendaParceria($ag, $agenda, $parc); return ''; } catch (Throwable $e) { return $e->getMessage(); } finally { TTransaction::rollback(); }
    };
    $outraAgenda = (int) q("SELECT id FROM agenda WHERE id <> {$agendaId} ORDER BY id LIMIT 1")[0]['id'];
    ok('com financeiro: trocar a agenda pede para desfazer o financeiro antes', strpos($troca($a4, $outraAgenda, $parceria), 'Para alterar a agenda, desfaça o financeiro antes') !== false, $troca($a4, $outraAgenda, $parceria));
    ok('com financeiro: trocar a parceria pede para desfazer o financeiro antes', strpos($troca($a4, $agendaId, $outra), 'Para alterar a parceria, desfaça o financeiro antes') !== false);
    igual('com financeiro: salvar sem trocar agenda nem parceria passa', '', $troca($a4, $agendaId, $parceria));
    $html = saida(function () use ($a4) { $t = new AgendamentoCalendarForm([]); $t->onEdit(['key' => $a4]); $t->show(); });
    ok('edicao com financeiro: agenda e parceria travadas, com o aviso', strpos($html, 'agp_aviso_form_') !== false && strpos($html, 'desfaça o financeiro antes') !== false, substr(strip_tags($html), 0, 200));
    $doColaborador = "SELECT parceria_levantamento_id FROM parceria_levantamento_participante WHERE pessoa_id = (SELECT profissional_id FROM agenda WHERE id = {$agendaId})";
    $esperadas = array_map('intval', array_column(q("SELECT id FROM parceria_levantamento WHERE ativo = 'S' AND tipo = 'FIXA' AND id IN ({$doColaborador}) ORDER BY nome"), 'id'));
    igual('parcerias do agendamento: so as fixas de que o colaborador da agenda participa', $esperadas, array_keys(AgendamentoFinanceiroService::parceriasFixas($agendaId)));
    $alheia = (int) (q("SELECT id FROM parceria_levantamento WHERE ativo = 'S' AND tipo = 'FIXA' AND id NOT IN ({$doColaborador}) ORDER BY id LIMIT 1")[0]['id'] ?? 0);
    if ($alheia > 0) {
        ok('parceria sem o colaborador da agenda e recusada no cadastro', strpos($troca(0, $agendaId, $alheia), 'não tem o colaborador da agenda') !== false);
        $a5 = $novoAgendamento('ALHEIA', 50);
        lanca('gerar com parceria sem o colaborador da agenda: recusado', function () use ($a5, $dados, $alheia) { AgendamentoFinanceiroService::gerar($a5, $dados(['parceria_levantamento_id' => $alheia])); }, 'colaborador da agenda');
        $html = saida(function () use ($a5) { (new AgendamentoFinanceiroForm(['agendamento_id' => $a5]))->show(); });
        preg_match('#<select[^>]*name="parceria_levantamento_id".*?</select>#s', $html, $combo);
        $combo = $combo[0] ?? '';
        ok('janela do gerar: so as parcerias do colaborador da agenda', $combo !== '' && strpos($combo, 'value="' . $alheia . '"') === false && strpos($combo, 'value="' . $parceria . '"') !== false, $combo);
    }

    // ---------- aba Financeiro so para quem gera (30/10/2026) ----------
    TSession::setValue('programs', []);
    $html = saida(function () use ($a4) { (new AgendamentoFormView(['key' => $a4]))->show(); });
    ok('sem a permissao: a aba Financeiro nao aparece', strpos($html, 'Parceria de repasse') === false && strpos($html, 'Conta a receber nº') === false && strpos($html, 'Desfazer financeiro') === false, substr(strip_tags($html), 0, 160));
    TSession::setValue('programs', ['AgendamentoFinanceiroForm' => true]);
    $grupo = q("SELECT (SELECT string_agg(p.controller, ',') FROM system_group_program gp JOIN system_program p ON p.id = gp.system_program_id WHERE gp.system_group_id = g.id) programas,
                       (SELECT string_agg(ug.system_user_id::text, ',') FROM system_user_group ug WHERE ug.system_group_id = g.id) usuarios
                  FROM system_group g WHERE g.name = 'Financeiro do agendamento'")[0] ?? [];
    igual('grupo Financeiro do agendamento: so a janela de gerar e a Vania (23)', ['AgendamentoFinanceiroForm', '23'], [$grupo['programas'] ?? null, $grupo['usuarios'] ?? null]);

    ok('Confirmar nao gera mais conta a receber', strpos((string) file_get_contents('app/control/agendamentos/AgendamentoFormView.php'), 'AtendimentoService::gerarContaReceber') === false);
    igual('coluna Origem: requisicao mostra so a requisicao (mesmo com contrato)', 0, substr_count(ContaListaColunas::origem((object) ['requisicao_pagamento_id' => 1, 'contrato_id' => 2]), 'Contrato'));
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    try { foreach (array_reverse($lotes) as $l) { LoteFinanceiroService::desfazer($l, $userId); } } catch (Throwable $e) { echo '  (limpeza lote: ', $e->getMessage(), ")\n"; }
    limparAgendamentoFinanceiro();
}
fim();
