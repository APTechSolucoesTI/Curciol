<?php
require __DIR__ . '/../agenda-fixture.php';
$f = new AgendaTesteFixture();
$dia = $f->dia;
$ids = [];
$logs = function (int $id, string $coluna): array {
    TTransaction::open('log');
    $st = TTransaction::get()->prepare("SELECT operation, oldvalue, newvalue FROM system_change_log WHERE tablename = 'agendamento' AND pkvalue = ? AND columnname = ? ORDER BY id");
    $st->execute([(string) $id, $coluna]); $r = $st->fetchAll(PDO::FETCH_ASSOC);
    TTransaction::close(); return $r;
};
try {
    TTransaction::open('escritorio');
    $a = new Agendamento();
    $a->cliente_id = $f->cliente; $a->agenda_id = $f->agenda;
    $a->dt_inicial = "$dia 10:00:00"; $a->dt_final = "$dia 10:30:00";
    $a->estado_agenda_id = EstadoAgenda::AGENDADO; $a->store();
    $ids[] = $id = (int) $a->id;
    TTransaction::close();
    igual('auditoria registra criacao', [['operation' => 'created', 'oldvalue' => '', 'newvalue' => "$dia 10:00:00"]], $logs($id, 'dt_inicial'));

    TTransaction::open('escritorio');
    $a = Agendamento::find($id); $a->dt_inicial = "$dia 11:00:00"; $a->dt_final = "$dia 11:30:00"; $a->store();
    // Uma segunda alteracao na mesma instancia deve usar o estado da primeira.
    $a->dt_inicial = "$dia 14:00:00"; $a->dt_final = "$dia 14:30:00"; $a->store();
    TTransaction::close();
    $l = $logs($id, 'dt_inicial');
    igual('auditoria registra as duas alteracoes', 3, count($l));
    igual('primeira alteracao tem valor anterior correto', ['operation' => 'changed', 'oldvalue' => "$dia 10:00:00", 'newvalue' => "$dia 11:00:00"], $l[1] ?? []);
    igual('segunda alteracao usa estado imediatamente anterior', ['operation' => 'changed', 'oldvalue' => "$dia 11:00:00", 'newvalue' => "$dia 14:00:00"], $l[2] ?? []);
    igual('historico de horario preservado', 2, count($f->sql('SELECT id FROM estado_agendamento WHERE agendamento_id = ? AND evento = ?', [$id, EstadoAgendamento::EVENTO_HORARIO])));

    // Objeto parcial usado por telas: colunas ausentes nao sao exclusoes.
    TTransaction::open('escritorio');
    $parcial = new Agendamento(null, false); $parcial->id = $id; $parcial->observacao = 'TESTE AUDITORIA PARCIAL'; $parcial->store();
    TTransaction::close();
    igual('salvar observacao nao inventa alteracao de horario', 3, count($logs($id, 'dt_inicial')));

    $cancelado = $f->agendar("$dia 15:00", "$dia 15:30", null, EstadoAgenda::CANCELADO); $ids[] = $cancelado;
    $f->sql('INSERT INTO bloqueio (agenda_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?)', [$f->agenda, "$dia 15:00", "$dia 15:30", 'TESTE AUDITORIA']);
    TTransaction::open('escritorio');
    try {
        $a = Agendamento::find($cancelado); $a->estado_agenda_id = EstadoAgenda::AGENDADO; $a->store();
        ok('cancelado sob bloqueio continua protegido', false);
    } catch (Exception $e) { ok('cancelado sob bloqueio continua protegido', strpos($e->getMessage(), 'bloqueio') !== false); }
    TTransaction::rollback();
    igual('guarda nao altera estado cancelado', (int) EstadoAgenda::CANCELADO, (int) $f->sql('SELECT estado_agenda_id FROM agendamento WHERE id = ?', [$cancelado])[0]['estado_agenda_id']);
} finally { $f->limpar(); }
fim();
