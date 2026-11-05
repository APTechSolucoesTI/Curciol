<?php
// tests/agendamentos/AgendaSobreposicaoTest.php
// 05/11/2026: remarcar registra no historico; compromisso nao cai sobre agendamento nem outro compromisso; agenda convidada segue
// as mesmas regras; bloqueio sobre confirmado nao passa e sobre o resto pede confirmacao.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_AS = 'TESTE AGENDA SOBREPOSICAO';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string
{
    ob_start();
    try { $f(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function limparAS(): void
{
    $obs = "'" . MARCA_AS . "%'";
    $ags = array_column(q("SELECT a.id FROM agendamento a JOIN pessoa p ON p.id = a.cliente_id WHERE p.nome LIKE {$obs}"), 'id');
    $lista = $ags ? implode(',', array_map('intval', $ags)) : '0';
    x("DELETE FROM convidado WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM estado_agendamento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM agendamento WHERE id IN ({$lista})");
    x("DELETE FROM convidado_compromisso WHERE compromisso_id IN (SELECT id FROM compromisso WHERE observacao LIKE {$obs})");
    x("DELETE FROM compromisso WHERE observacao LIKE {$obs}");
    x("DELETE FROM bloqueio WHERE observacao LIKE {$obs}");
    x("DELETE FROM pessoa WHERE nome LIKE {$obs}");
}
limparAS();
TSession::setValue('userid', 1);
$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);
$agendas = array_column(q("SELECT a.id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id WHERE e.system_unit_id = {$unidade} ORDER BY a.id LIMIT 2"), 'id');
$a1 = (int) $agendas[0];
$a2 = (int) ($agendas[1] ?? 0);
$original = q("SELECT id, dias, horario_inicial, horario_final, horario_inicio_intervalo, horario_fim_intervalo, fl_permite_choque_horario FROM agenda WHERE id IN ({$a1}, {$a2})");
// agendas de teste abertas: seg a sex, 08 as 18, sem intervalo, sem choque (o que muda aqui e so a sobreposicao)
x("UPDATE agenda SET dias = '1,2,3,4,5', horario_inicial = '08:00', horario_final = '18:00', horario_inicio_intervalo = NULL, horario_fim_intervalo = NULL, fl_permite_choque_horario = 'F' WHERE id IN ({$a1}, {$a2})");

$dia = date('Y-m-d', strtotime('+370 days'));
while (in_array((int) date('w', strtotime($dia)), [0, 6], true)) { $dia = date('Y-m-d', strtotime($dia . ' +1 day')); }
$h = fn(string $hora) => "{$dia} {$hora}";

TTransaction::open('escritorio');
$st = TTransaction::get()->prepare('INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES (?, 1) RETURNING id');
$st->execute([MARCA_AS]);
$cliente = (int) $st->fetchColumn();
TTransaction::close();

$agendamento = function (int $agenda, string $ini, string $fim, $estado = EstadoAgenda::AGENDADO) use ($cliente) {
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare('INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES (?, ?, ?, ?, ?) RETURNING id');
    $st->execute([$cliente, $estado, $agenda, $ini, $fim]);
    $id = (int) $st->fetchColumn();
    TTransaction::close();
    return $id;
};
$compromisso = function (int $agenda, string $ini, string $fim) {
    TTransaction::open('escritorio');
    $tipo = (int) TTransaction::get()->query('SELECT id FROM tipo_compromisso ORDER BY id LIMIT 1')->fetchColumn();
    $st = TTransaction::get()->prepare('INSERT INTO compromisso (agenda_id, tipo_compromisso_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?, ?) RETURNING id');
    $st->execute([$agenda, $tipo, $ini, $fim, MARCA_AS]);
    $id = (int) $st->fetchColumn();
    TTransaction::close();
    return $id;
};
$bloqueio = function (int $agenda, string $ini, string $fim) {
    TTransaction::open('escritorio');
    TTransaction::get()->prepare('INSERT INTO bloqueio (agenda_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?)')->execute([$agenda, $ini, $fim, MARCA_AS]);
    TTransaction::close();
};
$erro = function (callable $f) { TTransaction::open('escritorio'); try { $f(); TTransaction::close(); return null; } catch (Exception $e) { TTransaction::rollback(); return $e->getMessage(); } };

try {
    // ---------- remarcar ----------
    $rem = $agendamento($a1, $h('08:00'), $h('08:30'));
    $msg = $erro(function () use ($rem, $h) {
        $o = new Agendamento();
        $o->id = $rem;
        $o->dt_inicial = $h('08:30');
        $o->dt_final = $h('09:00');
        $o->store();
    });
    igual('remarcar: salva, mesmo sem a tela mandar o estado', null, $msg);
    $hist = q("SELECT estado_agenda_id, observacao FROM estado_agendamento WHERE agendamento_id = {$rem} AND evento = 'HORARIO'");
    igual('remarcar: uma linha no historico', 1, count($hist));
    igual('remarcar: historico com o estado do agendamento', (int) EstadoAgenda::AGENDADO, (int) ($hist[0]['estado_agenda_id'] ?? 0));
    igual('remarcar: o estado do agendamento nao muda', (int) EstadoAgenda::AGENDADO, (int) q("SELECT estado_agenda_id e FROM agendamento WHERE id = {$rem}")[0]['e']);
    x("UPDATE agendamento SET estado_agenda_id = " . EstadoAgenda::CANCELADO . " WHERE id = {$rem}");

    // ---------- compromisso sobre agendamento e compromisso ----------
    $ag = $agendamento($a1, $h('09:00'), $h('10:00'));
    $cp = $compromisso($a1, $h('11:00'), $h('12:00'));
    ok('compromisso sobre agendamento: barra', strpos((string) $erro(fn() => AgendaConflitoService::exigirLivreCompromisso($a1, $h('09:30'), $h('10:30'))), 'um agendamento') !== false);
    ok('compromisso sobre compromisso: barra', strpos((string) $erro(fn() => AgendaConflitoService::exigirLivreCompromisso($a1, $h('11:30'), $h('12:30'))), 'um compromisso') !== false);
    igual('compromisso encostado: passa', null, $erro(fn() => AgendaConflitoService::exigirLivreCompromisso($a1, $h('10:00'), $h('11:00'))));
    igual('compromisso editado no proprio horario: passa', null, $erro(fn() => AgendaConflitoService::exigirLivreCompromisso($a1, $h('11:00'), $h('12:00'), $cp)));
    x("UPDATE agenda SET fl_permite_choque_horario = 'T' WHERE id = {$a1}");
    ok('compromisso em agenda com choque: barra igual', strpos((string) $erro(fn() => AgendaConflitoService::exigirLivreCompromisso($a1, $h('09:30'), $h('10:30'))), 'um agendamento') !== false);
    x("UPDATE agenda SET fl_permite_choque_horario = 'F' WHERE id = {$a1}");
    ok('compromisso longo sobre agendamento: barra', strpos((string) $erro(fn() => AgendaConflitoService::exigirLivreCompromisso($a1, $h('08:00'), $h('17:00'))), 'um agendamento') !== false);
    x("UPDATE agendamento SET estado_agenda_id = " . EstadoAgenda::CANCELADO . " WHERE id = {$ag}");
    igual('compromisso sobre cancelado: passa', null, $erro(fn() => AgendaConflitoService::exigirLivreCompromisso($a1, $h('09:30'), $h('10:30'))));
    x("UPDATE agendamento SET estado_agenda_id = " . EstadoAgenda::AGENDADO . " WHERE id = {$ag}");

    // ---------- agenda convidada ----------
    if ($a2)
    {
        $bloqueio($a2, $h('14:00'), $h('15:00'));
        igual('convidados: tira a principal e repetidos', [$a2], AgendaConflitoService::convidadosPostados(['c' => [$a1, $a2, $a2, '']], 'c', $a1));
        ok('agendamento convidando agenda com bloqueio: barra', strpos((string) $erro(fn() => AgendaConflitoService::exigirLivreConvidados([$a2], $h('14:30'), $h('15:30'))), 'um bloqueio') !== false);
        ok('compromisso convidando agenda com bloqueio: barra', strpos((string) $erro(fn() => AgendaConflitoService::exigirLivreConvidadosCompromisso([$a2], $h('14:30'), $h('15:30'))), 'um bloqueio') !== false);
        igual('convidada livre: passa', null, $erro(fn() => AgendaConflitoService::exigirLivreConvidados([$a2], $h('15:00'), $h('15:30'))));
        $marcadoNa2 = $agendamento($a2, $h('16:00'), $h('16:30'));
        ok('agendamento convidando agenda ocupada: barra', strpos((string) $erro(fn() => AgendaConflitoService::exigirLivreConvidados([$a2], $h('16:00'), $h('16:30'))), 'um agendamento') !== false);
    }

    // ---------- bloqueio sobre o que ja esta marcado ----------
    $sobre = function (string $i, string $f) use ($a1) { TTransaction::open('escritorio'); $r = AgendaConflitoService::sobreposicoesDoBloqueio([[$a1, $i, $f]]); TTransaction::close(); return $r; };
    $r = $sobre($h('09:00'), $h('10:00'));
    igual('bloqueio sobre agendado: so avisa', [0, 1], [count($r['impedem']), count($r['avisam'])]);
    ok('bloqueio: aviso com o cliente', strpos($r['avisam'][0], MARCA_AS) !== false, $r['avisam'][0]);
    $r = $sobre($h('11:00'), $h('12:00'));
    igual('bloqueio sobre compromisso: so avisa', [0, 1], [count($r['impedem']), count($r['avisam'])]);
    foreach ([EstadoAgenda::CONFIRMADO => 'confirmado', EstadoAgenda::EM_ATENDIMENTO => 'em atendimento'] as $estado => $nome)
    {
        x("UPDATE agendamento SET estado_agenda_id = {$estado} WHERE id = {$ag}");
        $r = $sobre($h('09:00'), $h('10:00'));
        igual("bloqueio sobre {$nome}: impede", [1, 0], [count($r['impedem']), count($r['avisam'])]);
    }
    x("UPDATE agendamento SET estado_agenda_id = " . EstadoAgenda::CANCELADO . " WHERE id = {$ag}");
    igual('bloqueio sobre cancelado: nada', ['impedem' => [], 'avisam' => []], $sobre($h('09:00'), $h('10:00')));

    // ---------- pelo onSave real do bloqueio ----------
    x("UPDATE agendamento SET estado_agenda_id = " . EstadoAgenda::AGENDADO . " WHERE id = {$ag}");
    $form = function (array $extra) use ($a1, $h) {
        $p = ['id' => '', 'agenda_id' => $a1, 'agendas' => [$a1], 'dt_inicio' => date('d/m/Y H:i', strtotime($h('09:00'))), 'dt_final' => date('d/m/Y H:i', strtotime($h('10:00'))), 'observacao' => MARCA_AS . ' FORM', 'habilitar_repeticao' => 'N'] + $extra;
        $_POST = $p; $_REQUEST = $p;
        return saida(function () use ($p) { (new BloqueioForm($p))->onSave($p); });
    };
    $qtd = fn() => (int) q("SELECT count(*) c FROM bloqueio WHERE observacao = '" . MARCA_AS . " FORM'")[0]['c'];
    $s = $form([]);
    ok('tela: sobre agendado pergunta', strpos($s, 'Deseja bloquear mesmo assim') !== false, substr($s, 0, 400));
    igual('tela: sem confirmar nao grava', 0, $qtd());
    $s = $form(['bloqueio_sobreposicao_confirmada' => '1']);
    igual('tela: confirmado grava', 1, $qtd());
    x("DELETE FROM bloqueio WHERE observacao = '" . MARCA_AS . " FORM'");
    x("UPDATE agendamento SET estado_agenda_id = " . EstadoAgenda::CONFIRMADO . " WHERE id = {$ag}");
    $s = $form(['bloqueio_sobreposicao_confirmada' => '1']);
    ok('tela: sobre confirmado nao passa, nem confirmando', strpos($s, 'há agendamento confirmado') !== false, substr($s, 0, 400));
    igual('tela: sobre confirmado nada gravado', 0, $qtd());
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    $_POST = []; $_REQUEST = [];
    foreach ($original as $o)
    {
        TTransaction::open('escritorio');
        TTransaction::get()->prepare('UPDATE agenda SET dias = ?, horario_inicial = ?, horario_final = ?, horario_inicio_intervalo = ?, horario_fim_intervalo = ?, fl_permite_choque_horario = ? WHERE id = ?')
            ->execute([$o['dias'], $o['horario_inicial'], $o['horario_final'], $o['horario_inicio_intervalo'], $o['horario_fim_intervalo'], $o['fl_permite_choque_horario'], $o['id']]);
        TTransaction::close();
    }
    limparAS();
}

fim();
