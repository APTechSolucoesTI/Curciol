<?php
require __DIR__ . '/../agenda-fixture.php';
if (($argv[1] ?? '') === 'editar') {
    $id = (int) $argv[2]; $hora = $argv[3]; $marcador = $argv[4];
    TTransaction::open('escritorio');
    file_put_contents($marcador, (string) TTransaction::get()->query('SELECT pg_backend_pid()')->fetchColumn());
    $a = Agendamento::find($id);
    $a->dt_inicial = substr($a->dt_inicial, 0, 10) . " {$hora}:00";
    $a->dt_final = date('Y-m-d H:i:s', strtotime($a->dt_inicial . ' +30 minutes'));
    $a->store(); TTransaction::close(); exit;
}
$f = new AgendaTesteFixture();
$id = $f->agendar($f->dia . ' 09:00', $f->dia . ' 09:30');
$trava = TConnection::openArray(array_merge(TConnection::getDatabaseInfo('escritorio'), ['keep' => 0]));
$processos = []; $marcadores = [];
try {
    $trava->beginTransaction();
    $trava->query('SELECT id FROM agendamento WHERE id = ' . $id . ' FOR UPDATE');
    foreach (['10:00', '11:00'] as $hora) {
        $marcador = tempnam(sys_get_temp_dir(), 'curciol-auditoria-'); $marcadores[] = $marcador;
        $processos[] = proc_open([PHP_BINARY, __FILE__, 'editar', (string) $id, $hora, $marcador], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    }
    $bloqueados = 0;
    for ($i = 0; $i < 250; $i++) {
        $pids = array_filter(array_map(fn($p) => (int) file_get_contents($p), $marcadores));
        if (count($pids) === 2) {
            $trava->query('SELECT pg_stat_clear_snapshot()');
            $bloqueados = (int) $trava->query("SELECT count(*) FROM pg_stat_activity WHERE pid IN (" . implode(',', $pids) . ") AND wait_event_type = 'Lock'")->fetchColumn();
            if ($bloqueados === 2) { break; }
        }
        usleep(20000);
    }
    igual('duas edicoes chegaram simultaneamente a linha bloqueada', 2, $bloqueados);
    $trava->commit();
    foreach ($processos as $p) { igual('edicao concorrente termina', 0, proc_close($p)); }
    $processos = [];
    TTransaction::open('log');
    $st = TTransaction::get()->prepare("SELECT oldvalue, newvalue FROM system_change_log WHERE tablename = 'agendamento' AND pkvalue = ? AND columnname = 'dt_inicial' AND operation = 'changed' ORDER BY id");
    $st->execute([(string) $id]); $logs = $st->fetchAll(PDO::FETCH_ASSOC); TTransaction::close();
    igual('duas mudancas auditadas', 2, count($logs));
    igual('primeiro valor anterior e o horario original', $f->dia . ' 09:00:00', $logs[0]['oldvalue'] ?? null);
    igual('segunda edicao usa o resultado da primeira', $logs[0]['newvalue'] ?? null, $logs[1]['oldvalue'] ?? null);
} finally {
    if ($trava->inTransaction()) { $trava->rollBack(); }
    foreach ($processos as $p) { proc_terminate($p); proc_close($p); }
    foreach ($marcadores as $p) { unlink($p); }
    $f->limpar();
}
fim();
