<?php
require __DIR__ . '/../agenda-fixture.php';
$f = new AgendaTesteFixture();
$sabado = date('Y-m-d', strtotime('next saturday'));
$segunda = date('Y-m-d', strtotime('next monday'));
$capturar = function (callable $fn): string { ob_start(); $fn(); return ob_get_clean(); };
try {
    $f->sql('INSERT INTO agenda_excecao (agenda_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?)', [$f->agenda, "$sabado 19:00", "$sabado 20:00", 'TESTE MODAL EXCECAO']);
    $f->sql('INSERT INTO agenda_excecao (agenda_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?)', [$f->agenda, "$segunda 19:00", "$segunda 20:00", 'TESTE MODAL EXCECAO']);
    TSession::setValue('agenda_id', $f->agenda);
    TSession::setValue('convidado_agendamento_id', null);
    TSession::setValue('formName', 'form_Agendamento');
    $html = $capturar(fn() => (new ModalBuscarHorariosDisponiveis([]))->show());
    ok('modal oferece dia que so atende por excecao', strpos($html, $sabado) !== false);
    $html = $capturar(fn() => ModalBuscarHorariosDisponiveis::onSelectDia(['btnRadioDia' => $segunda]));
    ok('modal oferece horario da excecao depois do expediente', strpos($html, '19:00') !== false && strpos($html, '19:30') !== false);
    $html = $capturar(fn() => ModalBuscarHorariosDisponiveis::onSelectDia(['btnRadioDia' => $sabado]));
    ok('modal oferece horario da excecao no dia sem expediente', strpos($html, '19:00') !== false);
    TTransaction::open('escritorio');
    igual('convidada sem excecao impede horario fora do expediente', [], AgendaHorarioDisponivelService::listar($f->agenda, $sabado, [$f->outra]));
    TTransaction::close();
    $seguinte = date('Y-m-d', strtotime($sabado . ' +1 day'));
    $f->sql('INSERT INTO agenda_excecao (agenda_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?)', [$f->agenda, "$sabado 23:00", "$seguinte 00:00", 'TESTE MODAL VIRADA']);
    TSession::setValue('btnRadioDia', $sabado);
    $html = $capturar(fn() => (new ModalBuscarHorariosDisponiveis([]))->avancar(['btnRadioHorario' => ['23:30']]));
    ok('excecao ate meia noite devolve data final do dia seguinte', strpos($html, "'dt_final', '$seguinte 00:00'") !== false);
} finally { $f->limpar(); }
fim();
