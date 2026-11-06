<?php
require __DIR__ . '/../agenda-fixture.php';
$f = new AgendaTesteFixture();
$dia = $f->dia;
$capturar = function (callable $fn): string {
    ob_start();
    try { $fn(); return ob_get_clean(); }
    catch (Throwable $e) { ob_end_clean(); throw $e; }
};
try {
    $f->sql('INSERT INTO compromisso (agenda_id, dt_inicio, dt_final, observacao, tipo_compromisso_id) VALUES (?, ?, ?, ?, (SELECT min(id) FROM tipo_compromisso))', [$f->agenda, "$dia 10:00", "$dia 10:30", 'TESTE PORTAL']);
    $comp = (int) $f->sql('INSERT INTO compromisso (agenda_id, dt_inicio, dt_final, observacao, tipo_compromisso_id) VALUES (?, ?, ?, ?, (SELECT min(id) FROM tipo_compromisso)) RETURNING id', [$f->outra, "$dia 11:00", "$dia 11:30", 'TESTE PORTAL'])[0]['id'];
    $f->sql('INSERT INTO convidado_compromisso (compromisso_id, agenda_id) VALUES (?, ?)', [$comp, $f->agenda]);
    $ag = $f->agendar("$dia 14:00", "$dia 14:30", $f->outra);
    $f->sql('INSERT INTO convidado (agendamento_id, agenda_id) VALUES (?, ?)', [$ag, $f->agenda]);
    $f->sql('INSERT INTO agenda_excecao (agenda_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?)', [$f->agenda, "$dia 08:00", "$dia 09:30", 'TESTE PORTAL']);
    $mover = $f->agendar("$dia 16:00", "$dia 16:30");
    TSession::setValue('logged', true);
    foreach (['salvar', 'mover'] as $acao) {
        foreach (['compromisso' => "$dia 10:00", 'compromisso convidado' => "$dia 11:00", 'agendamento convidado' => "$dia 14:00", 'fora do atendimento' => "$dia 07:00", 'trecho normal da excecao' => "$dia 09:00", 'dia sem atendimento' => date('Y-m-d', strtotime($dia . ' +5 days')) . ' 10:00'] as $caso => $inicio) {
            $antes = count($f->sql('SELECT id FROM agendamento WHERE cliente_id = ?', [$f->cliente]));
            if ($acao === 'salvar') {
                $_POST = $_REQUEST = $p = ['id' => '', 'agenda_id' => $f->agenda, 'cliente_id' => $f->cliente, 'dt_inicial' => $inicio, 'online' => 'F', 'view' => 'agendaWeek'];
                $html = $capturar(fn() => (new AgendamentoClienteCalendarForm($p))->onSave($p));
                igual("salvar: recusa {$caso}", $antes, count($f->sql('SELECT id FROM agendamento WHERE cliente_id = ?', [$f->cliente])));
            } else {
                $html = $capturar(fn() => AgendamentoClienteCalendarForm::onUpdateEvent(['id' => $mover, 'start_time' => $inicio, 'end_time' => date('Y-m-d H:i', strtotime($inicio . ' +30 minutes'))]));
                igual("mover: recusa {$caso}", "$dia 16:00:00", $f->sql('SELECT dt_inicial FROM agendamento WHERE id = ?', [$mover])[0]['dt_inicial']);
            }
            ok("{$acao}: aviso amigavel sem dados de terceiros ({$caso})", strpos($html, 'Escolha outro horário') !== false && strpos($html, 'TESTE PORTAL') === false);
            // A versao antiga pode ter gravado; limpa so a tentativa deste teste.
            if ($acao === 'salvar') { $f->sql('DELETE FROM estado_agendamento WHERE agendamento_id IN (SELECT id FROM agendamento WHERE cliente_id = ? AND id NOT IN (?, ?))', [$f->cliente, $ag, $mover]); $f->sql('DELETE FROM agendamento WHERE cliente_id = ? AND id NOT IN (?, ?)', [$f->cliente, $ag, $mover]); }
            else { $f->sql('UPDATE agendamento SET dt_inicial = ?, dt_final = ? WHERE id = ?', ["$dia 16:00", "$dia 16:30", $mover]); }
        }
    }
    $capturar(fn() => AgendamentoClienteCalendarForm::onUpdateEvent(['id' => $mover, 'start_time' => "$dia 16:00", 'end_time' => "$dia 16:30"]));
    igual('edicao ignora o proprio agendamento', "$dia 16:00:00", $f->sql('SELECT dt_inicial FROM agendamento WHERE id = ?', [$mover])[0]['dt_inicial']);
    $capturar(fn() => AgendamentoClienteCalendarForm::onUpdateEvent(['id' => $mover, 'start_time' => "$dia 10:30", 'end_time' => "$dia 11:00"]));
    igual('encostar em compromisso e permitido', "$dia 10:30:00", $f->sql('SELECT dt_inicial FROM agendamento WHERE id = ?', [$mover])[0]['dt_inicial']);
} finally { $f->limpar(); }
fim();
