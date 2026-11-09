<?php
require __DIR__ . '/../agenda-fixture.php';
$f = new AgendaTesteFixture();
$dia = $f->dia;
$saida = function (callable $acao): string {
    ob_start();
    try { $acao(); return ob_get_contents(); }
    catch (Throwable $e) { return get_class($e) . ': ' . $e->getMessage(); }
    finally { ob_end_clean(); }
};
try {
    $f->sql('INSERT INTO bloqueio (agenda_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?)', [$f->agenda, "$dia 10:00", "$dia 11:00", 'TESTE CALENDARIO']);
    TTransaction::open('escritorio');
    $a = new Agendamento();
    $a->agenda_id = $f->agenda;
    $a->dt_inicial = "$dia 09:30";
    $a->dt_final = "$dia 10:00";
    ok('validacao do calendario aceita encostar em bloqueio', $a->validate());
    TTransaction::close();
    $f->sql("UPDATE agenda SET dias = '' WHERE id = ?", [$f->agenda]);
    TTransaction::open('escritorio');
    $a = new Agendamento();
    $a->agenda_id = $f->agenda;
    $a->dt_inicial = "$dia 14:00";
    $a->dt_final = "$dia 14:30";
    ok('agenda sem dias definidos atende todos', $a->validate());
    TTransaction::close();

    $id = $f->agendar("$dia 15:00", "$dia 15:30", null, EstadoAgenda::CANCELADO);
    $saida(fn() => AgendamentoFormView::onValidarAgendamento(['key' => $id]));
    $estado = fn() => $f->sql('SELECT estado_agenda_id FROM agendamento WHERE id = ?', [$id])[0]['estado_agenda_id'];
    igual('validar nao reativa cancelado', EstadoAgenda::CANCELADO, $estado());
    $f->sql('UPDATE agendamento SET estado_agenda_id = ? WHERE id = ?', [EstadoAgenda::AGUARDANDO_VALIDACAO_ESCRITORIO, $id]);
    $saida(fn() => AgendamentoFormView::onValidarAgendamento(['key' => $id]));
    igual('validar aceita quem aguarda escritorio', EstadoAgenda::AGENDADO, $estado());

    $f->sql('DELETE FROM estado_agendamento WHERE agendamento_id = ?', [$id]);
    $f->sql('DELETE FROM agendamento WHERE id = ?', [$id]);
    $p = ['key' => $id, 'token' => AgendamentoService::getToken($id)];
    // pagina de detalhe excluida (09/11/2026): so os links de confirmar e cancelar abrem a pagina do agendamento
    ok('sem a pagina de detalhe (onShow)', !method_exists('AgendamentoView', 'onShow'));
    ok('sem a rota agendamento-ID-token', !preg_match('/^RewriteRule \^(xhr-)?agendamento-/m', (string) file_get_contents('.htaccess')));
    ok('rotas de confirmar e cancelar continuam', substr_count((string) file_get_contents('.htaccess'), 'class=AgendamentoView&method=onConfirmar') === 2 && substr_count((string) file_get_contents('.htaccess'), 'class=AgendamentoView&method=onCancelar') === 2);
    foreach (['onConfirmar', 'onCancelar'] as $metodo) {
        $html = $saida(fn() => (new AgendamentoView($p))->$metodo($p));
        ok("link excluido: {$metodo} avisa sem erro interno", str_contains($html, 'Agendamento não encontrado'), $html);
        while (TTransaction::get()) { TTransaction::rollback(); }
    }
} finally { $f->limpar(); }
fim();
