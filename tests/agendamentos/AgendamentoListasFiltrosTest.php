<?php
// tests/agendamentos/AgendamentoListasFiltrosTest.php
// Listagem de agendamentos e Atendimento (29/10/2026): filtro no topo de todas as colunas (data, inicio, fim, codigos),
// somando com a cortina; "Limpar filtros" limpa cortina, cabecalho e a faixa. So le o banco (nao grava nada).
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function post(array $dados): void { $_POST = $dados; $_REQUEST = $dados; }
function saida(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
/** Quantos registros a listagem acha com os filtros da sessao (mesma criteria do onReload). */
function contar(string $lista): int
{
    TTransaction::open('escritorio');
    $criteria = new TCriteria;
    $criteria->add(new TFilter('agenda_id', 'in', "(SELECT id FROM agenda WHERE escritorio_id in (SELECT id FROM escritorio WHERE system_unit_id = '" . (int) TSession::getValue('userunitid') . "'))"));
    foreach ((array) TSession::getValue($lista . '_filters') as $f) { $criteria->add($f); }
    $n = (new TRepository('Agendamento'))->count($criteria);
    TTransaction::close();
    return $n;
}

$base = q("SELECT a.id, a.dt_inicial, a.dt_final, ag.escritorio_id, e.system_unit_id,
                  (SELECT at.id FROM atendimento at WHERE at.agendamento_id = a.id ORDER BY at.id LIMIT 1) AS atendimento_id
             FROM agendamento a JOIN agenda ag ON ag.id = a.agenda_id JOIN escritorio e ON e.id = ag.escritorio_id
            WHERE EXISTS (SELECT 1 FROM atendimento at WHERE at.agendamento_id = a.id)
            ORDER BY a.id DESC LIMIT 1")[0] ?? null;
if (!$base) { echo "ABORTADO: sem agendamento com atendimento na base\n"; exit(1); }
TSession::setValue('userid', (int) q('SELECT min(id) i FROM system_users')[0]['i']);
TSession::setValue('userunitid', (int) $base['system_unit_id']);
$unidade = (int) $base['system_unit_id'];
$dia = substr($base['dt_inicial'], 0, 10);
$diaBr = date('d/m/Y', strtotime($dia));
$naUnidade = "agenda_id IN (SELECT id FROM agenda WHERE escritorio_id IN (SELECT id FROM escritorio WHERE system_unit_id = '{$unidade}'))";

// ---------- Listagem (AgendaAgendamentoList) ----------
FiltroLista::limparSessao('AgendaAgendamentoList');
$html = saida(function () { $p = new AgendaAgendamentoList([]); $p->onReload([]); $p->show(); });
ok('listagem: filtro no topo de data, inicio e fim', strpos($html, 'data_col') !== false && strpos($html, 'inicio_col') !== false && strpos($html, 'fim_col') !== false, substr(strip_tags($html), 0, 200));

post(['data_col' => $diaBr, 'data_col_fim' => $diaBr, 'inicio_col' => '', 'fim_col' => '', 'cliente_col' => '', 'especialidade_descricao' => '', 'is_online' => '', 'estado_agenda_nome' => '']);
saida(function () { (new AgendaAgendamentoList([]))->onSearch($_POST + ['static' => '1']); });
$esperado = (int) q("SELECT count(*) n FROM agendamento WHERE {$naUnidade} AND dt_inicial BETWEEN '{$dia} 00:00:00' AND '{$dia} 23:59:59'")[0]['n'];
igual('listagem: filtro de data (de/ate) no cabecalho', $esperado, contar('AgendaAgendamentoList'));

$hora = date('H:i', strtotime($base['dt_inicial']));
post(['data_col' => $diaBr, 'data_col_fim' => $diaBr, 'inicio_col' => $hora, 'fim_col' => '', 'cliente_col' => '', 'especialidade_descricao' => '', 'is_online' => '', 'estado_agenda_nome' => '']);
saida(function () { (new AgendaAgendamentoList([]))->onSearch($_POST + ['static' => '1']); });
$esperado = (int) q("SELECT count(*) n FROM agendamento WHERE {$naUnidade} AND dt_inicial BETWEEN '{$dia} 00:00:00' AND '{$dia} 23:59:59' AND CAST(dt_inicial AS TIME) >= '{$hora}:00'")[0]['n'];
igual('listagem: inicio "a partir de" soma com a data', $esperado, contar('AgendaAgendamentoList'));
ok('listagem: achou o agendamento de referencia', $esperado >= 1);

// cortina soma com o cabecalho
post(['estado_agenda_id' => '999999']);
saida(function () { (new AgendaAgendamentoList([]))->onSearch($_POST); });
$dados = (array) TSession::getValue('AgendaAgendamentoList_filter_data');
ok('listagem: cortina soma com o cabecalho (data continua)', ($dados['data_col'] ?? '') !== '' && ($dados['estado_agenda_id'] ?? '') === '999999', json_encode($dados));
igual('listagem: com os dois filtros, nada', 0, contar('AgendaAgendamentoList'));

$html = saida(function () { (new AgendaAgendamentoList([]))->onClearFilters([]); });
igual('listagem: Limpar filtros zera a sessao (cortina e cabecalho)', [null, null], [TSession::getValue('AgendaAgendamentoList_filter_data'), TSession::getValue('AgendaAgendamentoList_filters')]);
ok('listagem: Limpar filtros manda a faixa vazia', strpos($html, 'curciolFaixaFiltros') !== false && strpos($html, '[]') !== false && strpos($html, 'EXCECAO') === false, substr($html, 0, 300));

// botoes Dia/Semana/Mes: so o periodo, somando com os demais; a faixa mostra "de ate" uma vez so (30/10/2026)
FiltroLista::limparSessao('AgendaAgendamentoList');
post(['estado_agenda_id' => '999999']);
saida(function () { (new AgendaAgendamentoList([]))->onSearch($_POST); });
$html = saida(function () { $p = new AgendaAgendamentoList([]); $p->onFilterDia([]); $p->show(); });
$d = (array) TSession::getValue('AgendaAgendamentoList_filter_data');
igual('Dia: periodo de hoje e o filtro anterior continua', [date('Y-m-d'), date('Y-m-d'), '999999'], [$d['dt_inicial'] ?? null, $d['dt_final'] ?? null, $d['estado_agenda_id'] ?? null]);
$json = function ($t) { return trim(json_encode($t), '"'); }; // a faixa vai em json (acento e barra escapados)
ok('Dia: faixa com a data uma vez de cada lado (sem "ateate")', strpos($html, $json(date('d/m/Y') . ' até ' . date('d/m/Y'))) !== false && strpos($html, $json('atéaté')) === false,
    substr($html, (int) strpos($html, 'curciolFaixaFiltros('), 500));
saida(function () { $p = new AgendaAgendamentoList([]); $p->onFilterSemana([]); });
$d = (array) TSession::getValue('AgendaAgendamentoList_filter_data');
$domingo = date('Y-m-d', strtotime('-' . date('w') . ' days'));
igual('Semana: de domingo a sabado', [$domingo, date('Y-m-d', strtotime($domingo . ' +6 days'))], [$d['dt_inicial'] ?? null, $d['dt_final'] ?? null]);
FiltroLista::limparSessao('AgendaAgendamentoList');
$html = saida(function () { $p = new AgendaAgendamentoList([]); $p->onReload([]); $p->show(); });
ok('listagem: bolinha na cor da agenda com o nome ao passar o mouse', strpos($html, "title='Agenda: ") !== false, substr(strip_tags($html), 0, 200));
FiltroLista::limparSessao('AgendaAgendamentoList');

// ---------- Atendimento (TodosAgendamentoList) ----------
FiltroLista::limparSessao('TodosAgendamentoList');
$html = saida(function () { $p = new TodosAgendamentoList([]); $p->onReload([]); $p->show(); });
ok('atendimento: filtro no topo dos codigos, inicio e fim', strpos($html, 'id_col') !== false && strpos($html, 'atendimento_col') !== false && strpos($html, 'inicio_col') !== false && strpos($html, 'fim_col') !== false, substr(strip_tags($html), 0, 200));

$vazio = ['id_col' => '', 'atendimento_col' => '', 'inicio_col' => '', 'inicio_col_fim' => '', 'fim_col' => '', 'fim_col_fim' => '', 'cliente_col' => '', 'especialidade_descricao' => '', 'agenda_nome' => '', 'estado_agenda_nome' => ''];
post(['id_col' => (string) $base['id']] + $vazio);
saida(function () { (new TodosAgendamentoList([]))->onSearch($_POST + ['static' => '1']); });
igual('atendimento: codigo do agendamento', 1, contar('TodosAgendamentoList'));

post(['atendimento_col' => (string) $base['atendimento_id']] + $vazio);
saida(function () { (new TodosAgendamentoList([]))->onSearch($_POST + ['static' => '1']); });
igual('atendimento: codigo do atendimento acha o agendamento', 1, contar('TodosAgendamentoList'));

$diaFim = substr($base['dt_final'], 0, 10);
post(['inicio_col' => $diaBr, 'inicio_col_fim' => $diaBr, 'fim_col' => date('d/m/Y', strtotime($diaFim)), 'fim_col_fim' => date('d/m/Y', strtotime($diaFim))] + $vazio);
saida(function () { (new TodosAgendamentoList([]))->onSearch($_POST + ['static' => '1']); });
$esperado = (int) q("SELECT count(*) n FROM agendamento WHERE {$naUnidade} AND dt_inicial BETWEEN '{$dia} 00:00:00' AND '{$dia} 23:59:59' AND dt_final BETWEEN '{$diaFim} 00:00:00' AND '{$diaFim} 23:59:59'")[0]['n'];
igual('atendimento: periodo de inicio e de fim', $esperado, contar('TodosAgendamentoList'));

saida(function () { (new TodosAgendamentoList([]))->onClearFilters([]); });
igual('atendimento: Limpar filtros zera a sessao', [null, null], [TSession::getValue('TodosAgendamentoList_filter_data'), TSession::getValue('TodosAgendamentoList_filters')]);

FiltroLista::limparSessao('AgendaAgendamentoList');
FiltroLista::limparSessao('TodosAgendamentoList');
fim();
