<?php
// tests/agendamentos/AgendaExcecaoTest.php
// 05/11/2026: excecao e um periodo liberado, que so pode ser criado dentro de bloqueio, intervalo ou nao atende; durante ela se
// marcam agendamentos e compromissos, sem liberar a sobreposicao entre eles. Usa uma agenda ajustada so durante o teste.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_EX = 'TESTE AGENDA EXCECAO';
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
function limparEX(): void
{
    $obs = "'" . MARCA_EX . "%'";
    $ags = array_column(q("SELECT a.id FROM agendamento a JOIN pessoa p ON p.id = a.cliente_id WHERE p.nome LIKE {$obs}"), 'id');
    $lista = $ags ? implode(',', array_map('intval', $ags)) : '0';
    x("DELETE FROM estado_agendamento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM agendamento WHERE id IN ({$lista})");
    x("DELETE FROM compromisso WHERE observacao LIKE {$obs}");
    x("DELETE FROM bloqueio WHERE observacao LIKE {$obs}");
    x("DELETE FROM agenda_excecao WHERE observacao LIKE {$obs}");
    x("DELETE FROM pessoa WHERE nome LIKE {$obs}");
}
limparEX();
TSession::setValue('userid', 1);
$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);
$agenda = (int) q("SELECT a.id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id WHERE e.system_unit_id = {$unidade} ORDER BY a.id LIMIT 1")[0]['id'];
$original = q("SELECT dias, horario_inicial, horario_final, horario_inicio_intervalo, horario_fim_intervalo, fl_permite_choque_horario FROM agenda WHERE id = {$agenda}")[0];
// seg a sex, 09 as 18, intervalo de 12:01 a 12:59 (como as agendas reais)
x("UPDATE agenda SET dias = '1,2,3,4,5', horario_inicial = '09:00', horario_final = '18:00', horario_inicio_intervalo = '12:01', horario_fim_intervalo = '12:59', fl_permite_choque_horario = 'F' WHERE id = {$agenda}");

$seg = date('Y-m-d', strtotime('next monday +400 days'));
$sab = date('Y-m-d', strtotime($seg . ' +5 days'));
$h = fn(string $hora, ?string $dia = null) => ($dia ?? $seg) . " {$hora}";

TTransaction::open('escritorio');
$st = TTransaction::get()->prepare('INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES (?, 1) RETURNING id');
$st->execute([MARCA_EX]);
$cliente = (int) $st->fetchColumn();
$tipo = (int) TTransaction::get()->query('SELECT id FROM tipo_compromisso ORDER BY id LIMIT 1')->fetchColumn();
TTransaction::close();

$erro = function (callable $f) { TTransaction::open('escritorio'); try { $f(); TTransaction::close(); return null; } catch (Exception $e) { TTransaction::rollback(); return $e->getMessage(); } };
$periodo = fn(string $i, string $f) => $erro(fn() => AgendaConflitoService::exigirPeriodoDeExcecao($agenda, $i, $f));
$excecao = function (string $i, string $f) use ($agenda) {
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare('INSERT INTO agenda_excecao (agenda_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?) RETURNING id');
    $st->execute([$agenda, $i, $f, MARCA_EX]);
    $id = (int) $st->fetchColumn();
    TTransaction::close();
    return $id;
};
$bloqueio = fn(string $i, string $f) => x("INSERT INTO bloqueio (agenda_id, dt_inicio, dt_final, observacao) VALUES ({$agenda}, '{$i}', '{$f}', '" . MARCA_EX . "')");
$agendamento = function (string $i, string $f, $estado = EstadoAgenda::AGENDADO) use ($agenda, $cliente) {
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare('INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES (?, ?, ?, ?, ?) RETURNING id');
    $st->execute([$cliente, $estado, $agenda, $i, $f]);
    $id = (int) $st->fetchColumn();
    TTransaction::close();
    return $id;
};
$livre = fn(string $i, string $f) => $erro(fn() => AgendaConflitoService::exigirLivre($agenda, $i, $f));
$livreComp = fn(string $i, string $f) => $erro(fn() => AgendaConflitoService::exigirLivreCompromisso($agenda, $i, $f));
$avisos = function (string $i, string $f) use ($agenda) { TTransaction::open('escritorio'); $r = AgendaConflitoService::avisosAtendimento([[$agenda, $i, $f]]); TTransaction::close(); return $r; };

try {
    // ---------- onde a excecao pode ser criada ----------
    igual('criar: no intervalo', null, $periodo($h('12:01'), $h('12:59')));
    igual('criar: o intervalo de hora cheia (12:00 a 13:00), com a folga de 1 minuto de cada lado', null, $periodo($h('12:00'), $h('13:00')));
    igual('criar: antes de abrir', null, $periodo($h('07:00'), $h('08:30')));
    igual('criar: depois de fechar', null, $periodo($h('18:00'), $h('20:00')));
    igual('criar: no sabado (nao atende)', null, $periodo($h('10:00', $sab), $h('12:00', $sab)));
    ok('nao cria: em horario normal', strpos((string) $periodo($h('10:00'), $h('11:00')), 'só pode ser criada') !== false);
    ok('nao cria: comeca no normal e entra no intervalo, mostrando o trecho', strpos((string) ($m = $periodo($h('11:00'), $h('12:30'))), '11:00') !== false && strpos((string) $m, 'só pode ser criada') !== false, (string) $m);
    ok('nao cria: do fechado para o normal', strpos((string) $periodo($h('08:00'), $h('10:00')), 'só pode ser criada') !== false);
    ok('nao cria: fim antes do inicio', strpos((string) $periodo($h('13:00'), $h('12:00')), 'depois do início') !== false);

    $bloqueio($h('14:00'), $h('16:00'));
    igual('criar: dentro de bloqueio', null, $periodo($h('14:30'), $h('15:00')));
    ok('nao cria: passa do fim do bloqueio', strpos((string) $periodo($h('15:30'), $h('16:30')), 'só pode ser criada') !== false);
    $bloqueio($h('10:00'), $h('12:01'));
    igual('criar: bloqueio emendado com o intervalo', null, $periodo($h('11:00'), $h('12:30')));
    igual('criar: sexta a noite ate o sabado inteiro (varios dias, tudo fechado)', null, $periodo($h('19:00', date('Y-m-d', strtotime($sab . ' -1 day'))), $h('20:00', $sab)));

    $ex = $excecao($h('12:01'), $h('12:59'));
    ok('nao cria: em cima de outra excecao', strpos((string) $periodo($h('12:30'), $h('12:45')), 'uma exceção') !== false);
    x("DELETE FROM agenda_excecao WHERE id = {$ex}");

    // ---------- durante a excecao ----------
    ok('sem excecao: agendamento no intervalo barra e indica a excecao', strpos((string) $livre($h('12:15'), $h('12:45')), 'Exceção') !== false);
    ok('sem excecao: agendamento em bloqueio barra', strpos((string) $livre($h('14:30'), $h('15:00')), 'um bloqueio') !== false);
    ok('sem excecao: compromisso em bloqueio barra', strpos((string) $livreComp($h('14:30'), $h('15:00')), 'um bloqueio') !== false);

    $exI = $excecao($h('12:00'), $h('13:00'));
    $exB = $excecao($h('14:30'), $h('15:30'));
    $exF = $excecao($h('10:00', $sab), $h('12:00', $sab));
    $exC = $excecao($h('07:00'), $h('09:00'));
    igual('agendamento: no intervalo liberado', null, $livre($h('12:15'), $h('12:45')));
    igual('agendamento: o intervalo inteiro de hora cheia', null, $livre($h('12:00'), $h('13:00')));
    igual('agendamento: no bloqueio liberado', null, $livre($h('14:30'), $h('15:00')));
    igual('agendamento: no nao atende liberado (sabado)', null, $livre($h('10:30', $sab), $h('11:00', $sab)));
    igual('agendamento: antes de abrir, liberado', null, $livre($h('08:00'), $h('08:30')));
    igual('agendamento: comeca no normal e termina no intervalo liberado', null, $livre($h('11:30', date('Y-m-d', strtotime($seg . ' +0 day'))), $h('12:00')) === null ? null : 'erro');
    ok('agendamento: passa da excecao e entra no bloqueio: barra', strpos((string) $livre($h('15:00'), $h('15:45')), 'um bloqueio') !== false);
    ok('agendamento: passa do horario da excecao no sabado: barra', strpos((string) $livre($h('11:30', $sab), $h('12:30', $sab)), 'não atende') !== false);
    igual('compromisso: no bloqueio liberado', null, $livreComp($h('14:30'), $h('15:00')));
    igual('compromisso: dentro da excecao, sem aviso', [], $avisos($h('12:15'), $h('12:45')));
    ok('compromisso: fora da excecao continua avisando', strpos((string) (($avisos($h('18:30'), $h('19:00')))[0] ?? ''), 'fora do atendimento') !== false);

    // a excecao nao libera a sobreposicao entre marcados
    $marcado = $agendamento($h('12:15'), $h('12:30'));
    ok('dentro da excecao: agendamento sobre agendamento barra', strpos((string) $livre($h('12:20'), $h('12:40')), 'um agendamento') !== false);
    ok('dentro da excecao: compromisso sobre agendamento barra', strpos((string) $livreComp($h('12:20'), $h('12:40')), 'um agendamento') !== false);
    x("INSERT INTO compromisso (agenda_id, tipo_compromisso_id, dt_inicio, dt_final, observacao) VALUES ({$agenda}, {$tipo}, '" . $h('14:30') . "', '" . $h('15:00') . "', '" . MARCA_EX . "')");
    ok('dentro da excecao: agendamento sobre compromisso barra', strpos((string) $livre($h('14:45'), $h('15:15')), 'um compromisso') !== false);

    // ---------- bloqueio novo sobre excecao ----------
    ok('bloqueio novo sobre excecao: nao pode', strpos((string) $erro(fn() => AgendaConflitoService::exigirSemExcecao($agenda, $h('12:15'), $h('13:30'))), 'uma exceção') !== false);
    igual('bloqueio novo fora da excecao: pode', null, $erro(fn() => AgendaConflitoService::exigirSemExcecao($agenda, $h('16:00'), $h('17:00'))));

    // ---------- tela ----------
    $form = function (array $p) { $_POST = $p; $_REQUEST = $p; return saida(function () use ($p) { (new AgendaExcecaoForm($p))->onSave($p); }); };
    $html = saida(function () { (new AgendaExcecaoForm([]))->show(); });
    ok('tela nova: campo de varias agendas e sem cliente', strpos($html, 'data-nome="agendas"') !== false && strpos($html, 'cliente_id') === false, substr(strip_tags($html), 0, 200));
    $dt = fn($d) => date('d/m/Y H:i', strtotime($d));
    $qt = fn() => (int) q("SELECT count(*) c FROM agenda_excecao WHERE observacao = '" . MARCA_EX . " TELA'")[0]['c'];
    $s = $form(['id' => '', 'agendas' => [$agenda], 'dt_inicio' => $dt($h('16:30')), 'dt_final' => $dt($h('17:00')), 'observacao' => MARCA_EX . ' TELA']);
    ok('tela: recusa em horario normal, com o motivo', strpos($s, 'só pode ser criada') !== false && $qt() === 0, substr(strip_tags($s), -300));
    $s = $form(['id' => '', 'agendas' => [$agenda], 'dt_inicio' => $dt($h('19:00')), 'dt_final' => $dt($h('20:00')), 'observacao' => MARCA_EX . ' TELA']);
    igual('tela: grava no nao atende', 1, $qt());
    $tela = (int) q("SELECT id FROM agenda_excecao WHERE observacao = '" . MARCA_EX . " TELA'")[0]['id'];
    $html = saida(function () use ($tela) { $p = ['key' => $tela, 'id' => $tela]; $f = new AgendaExcecaoForm($p); $f->onEdit($p); $f->show(); });
    ok('tela: editando, com o botao de remover', strpos($html, 'Remover exceção') !== false && strpos($html, 'name="agenda_id"') !== false);
    $form(['id' => $tela, 'agenda_id' => $agenda, 'dt_inicio' => $dt($h('10:00')), 'dt_final' => $dt($h('11:00')), 'observacao' => MARCA_EX . ' TELA NOVA']);
    $g = q("SELECT to_char(dt_inicio, 'HH24:MI') i, observacao o FROM agenda_excecao WHERE id = {$tela}")[0];
    igual('tela: editando muda so a observacao', ['19:00', MARCA_EX . ' TELA NOVA'], [$g['i'], $g['o']]);

    // ---------- remover ----------
    $s = saida(function () use ($tela) { AgendaExcecaoForm::onRemover(['id' => $tela]); });
    ok('remover vazia: pergunta', strpos($s, 'Deseja remover esta exceção') !== false, substr($s, 0, 300));
    saida(function () use ($tela) { AgendaExcecaoForm::onRemoverConfirmado(['id' => $tela]); });
    igual('remover: sai', 0, count(q("SELECT 1 FROM agenda_excecao WHERE id = {$tela}")));
    $s = saida(function () use ($exI) { AgendaExcecaoForm::onRemover(['id' => $exI]); });
    ok('remover com agendamento dentro: avisa e pergunta', strpos($s, 'Há horários marcados') !== false && strpos($s, MARCA_EX) !== false, substr($s, 0, 300));
    x("UPDATE agendamento SET estado_agenda_id = " . EstadoAgenda::CONFIRMADO . " WHERE id = {$marcado}");
    $s = saida(function () use ($exI) { AgendaExcecaoForm::onRemover(['id' => $exI]); });
    ok('remover com confirmado dentro: nao pode', strpos($s, 'Não é possível remover') !== false, substr($s, 0, 300));
    igual('remover com confirmado dentro: continua la', 1, count(q("SELECT 1 FROM agenda_excecao WHERE id = {$exI}")));

    // ---------- calendario ----------
    saida(function () use ($agenda) { AgendamentosFilterForm::onFilter(['agenda_id' => [(string) $agenda], 'dt_busca' => '', 'dt_busca_fim' => '']); });
    $eventos = json_decode(saida(function () use ($seg) { AgendamentoCalendarFormView::getEvents(['start' => "{$seg}T00:00:00", 'end' => date('Y-m-d', strtotime($seg . ' +1 day')) . 'T00:00:00']); }), true) ?: [];
    $doTipo = fn(string $classe) => array_values(array_filter($eventos, fn($e) => in_array($classe, $e['classNames'] ?? [], true)));
    $vistas = $doTipo('agd-excecao');
    ok('calendario: a excecao aparece, com o codigo para abrir', count($vistas) === 3 && in_array('excecao-' . $exI, array_column($vistas, 'id'), true), json_encode(array_column($vistas, 'id')));
    $doAgendamento = array_values(array_filter($doTipo('agd-agendamento'), fn($e) => (string) $e['id'] === (string) $marcado));
    igual('calendario: agendamento dentro da excecao aparece inteiro (o intervalo nao corta)', [substr($h('12:15'), 11), substr($h('12:30'), 11)], [substr($doAgendamento[0]['start'] ?? '', 11, 5), substr($doAgendamento[0]['end'] ?? '', 11, 5)]);
    $blocos = array_map(fn($e) => substr($e['start'], 11, 5) . '-' . substr($e['end'], 11, 5), array_filter($doTipo('agd-bloqueio'), fn($e) => strpos($e['start'], '14:') !== false || strpos($e['end'], '16:') !== false));
    ok('calendario: o bloqueio fica cortado pela excecao', in_array('14:00-14:30', $blocos, true) && in_array('15:30-16:00', $blocos, true), json_encode(array_values($blocos)));
    $s = saida(function () use ($exI) { AgendamentoCalendarFormView::onClick(['key' => 'excecao-' . $exI]); });
    ok('calendario: clicar abre a exceção', strpos($s, 'AgendaExcecaoForm') !== false, substr($s, 0, 200));
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    $_POST = []; $_REQUEST = [];
    TTransaction::open('escritorio');
    TTransaction::get()->prepare('UPDATE agenda SET dias = ?, horario_inicial = ?, horario_final = ?, horario_inicio_intervalo = ?, horario_fim_intervalo = ?, fl_permite_choque_horario = ? WHERE id = ?')
        ->execute([$original['dias'], $original['horario_inicial'], $original['horario_final'], $original['horario_inicio_intervalo'], $original['horario_fim_intervalo'], $original['fl_permite_choque_horario'], $agenda]);
    TTransaction::close();
    limparEX();
}

fim();
