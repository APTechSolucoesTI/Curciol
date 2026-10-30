<?php
// tests/agendamentos/AgendaVisualTest.php
// Agenda repaginada (29/10/2026): cores pela agenda, faixa das agendas e legenda, filtros com varios clientes/agendas e
// periodo; compromisso, bloqueio e excecao novos em varias agendas (um registro por agenda marcada).
// Grava na homologacao (registros marcados com MARCA_AV, em 2030) e apaga tudo no fim.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_AV = 'TESTE AGENDA MULTI';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function post(array $dados): void { $_POST = $dados; $_REQUEST = $dados; }
function saida(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function limparAgendaMulti(): void
{
    $m = MARCA_AV;
    x("DELETE FROM convidado_compromisso WHERE compromisso_id IN (SELECT id FROM compromisso WHERE observacao LIKE '{$m}%')");
    x("DELETE FROM compromisso WHERE observacao LIKE '{$m}%'");
    x("DELETE FROM bloqueio WHERE observacao LIKE '{$m}%'");
    $ags = "(SELECT id FROM agendamento WHERE cliente_id IN (SELECT id FROM pessoa WHERE nome LIKE '{$m}%'))";
    x("DELETE FROM agendamento_procedimento WHERE agendamento_id IN {$ags}");
    x("DELETE FROM estado_agendamento WHERE agendamento_id IN {$ags}");
    x("DELETE FROM convidado WHERE agendamento_id IN {$ags}");
    x("DELETE FROM agendamento WHERE id IN {$ags}");
    x("DELETE FROM pessoa WHERE nome LIKE '{$m}%'");
}

if (q('SELECT current_database() d')[0]['d'] !== 'curciol_homologacao') { echo "ABORTADO: so na homologacao\n"; exit(1); }
$par = q("SELECT a1.id a1, a2.id a2, e.system_unit_id u FROM agenda a1 JOIN agenda a2 ON a2.escritorio_id = a1.escritorio_id AND a2.id > a1.id
            JOIN escritorio e ON e.id = a1.escritorio_id ORDER BY a1.id LIMIT 1")[0] ?? null;
if (!$par) { echo "ABORTADO: precisa de duas agendas no mesmo escritorio\n"; exit(1); }
[$a1, $a2, $unidade] = [(int) $par['a1'], (int) $par['a2'], (int) $par['u']];
TSession::setValue('userid', (int) q('SELECT min(id) i FROM system_users')[0]['i']);
TSession::setValue('userunitid', $unidade);
$tipoCompromisso = (int) (q('SELECT id FROM tipo_compromisso ORDER BY id LIMIT 1')[0]['id'] ?? 0);
$proc = q('SELECT procedimento_id, parceiro_id FROM agendamento_procedimento ORDER BY id LIMIT 1')[0];

limparAgendaMulti();
try {
    // ---------- cores ----------
    $c = AgendaVisual::cores('#2563eb');
    igual('cores: a cor da agenda, fundo claro e texto escuro', ['#2563eb', '#dee8fc', '#0f285e'], [$c['cor'], $c['fundo'], $c['texto']]);
    igual('cores: cor invalida usa a padrao', '#528bb7', AgendaVisual::cores('azul')['cor']);
    $ev = AgendaVisual::estilizar(['title' => 'x', 'color' => '#f00'], 'compromisso', '#2563eb', 7);
    igual('compromisso: fundo branco, borda um tom mais escuro que o fundo, classe tracejada', ['#ffffff', '#87a9f4', ['agd-compromisso'], '7', false], [$ev['backgroundColor'], $ev['borderColor'], $ev['classNames'], $ev['agenda_id'], isset($ev['color'])]);

    // ---------- tela e filtros ----------
    $html = saida(function () { (new AgendamentosFilterForm([]))->show(); });
    ok('tela: Adicionar (compromisso, bloqueio, excecao) e Limpar filtros no cabecalho; clientes, agendas, periodo e legenda', strpos($html, 'Adicionar') !== false && strpos($html, 'CompromissoForm') !== false && strpos($html, 'BloqueioForm') !== false
        && strpos($html, 'AgendamentoExcecao') !== false && strpos($html, 'onLimparFiltros') !== false && strpos($html, 'data-nome="cliente_id"') !== false && strpos($html, 'data-nome="agenda_id"') !== false
        && strpos($html, 'dt_busca_fim') !== false && strpos($html, 'agd-alternar') !== false && strpos($html, 'Estado do agendamento') !== false, substr(strip_tags($html), 0, 200));
    $pos = function ($t) use ($html) { $p = strpos($html, $t); return $p === false ? -1 : $p; };
    ok('tela: Limpar filtros entre Novo agendamento e Atualizar', $pos('Novo agendamento') > 0 && $pos('Novo agendamento') < $pos('Limpar filtros') && $pos('Limpar filtros') < $pos('Atualizar'));
    ok('tela: filtros numa linha so (clientes, agendas, periodo) e a legenda no canto direito', $pos('class="agd-filtros"') > 0 && $pos('agd-filtro-largo') < $pos('agd-filtro-periodo') && $pos('agd-filtro-periodo') < $pos('agd-filtro-legenda') && $pos('agd-filtro-legenda') < $pos('id="agd-legenda"'));
    ok('tela: periodo vem com hoje e hoje', substr_count($html, 'value="' . date('d/m/Y') . '"') >= 2);
    ok('Visualizar como listagem e Agenda (na listagem) abrem a outra tela pelo menu (aba, endereco e menu)', strpos($html, 'method=onVerListagem') !== false
        && strpos(saida(function () { AgendamentosFilterForm::onVerListagem([]); }), "curciolAbrirPeloMenu('AgendaAgendamentoList'") !== false
        && strpos(saida(function () { AgendaAgendamentoList::onVerAgenda([]); }), "curciolAbrirPeloMenu('AgendamentosFilterForm'") !== false
        && strpos((string) file_get_contents('app/lib/include/curciol-navegacao.js'), 'window.curciolAbrirPeloMenu') !== false);
    ok('tela: agendas na lista com a cor e "Todas" no topo', strpos($html, 'Todas as agendas') !== false && preg_match('/class="fm-item" data-id="\d+"[^>]*data-cor="#[0-9a-f]{6}"/', $html) === 1);

    $json = saida(function () { AgendamentosFilterForm::onBuscarClientes(['q' => 'sil']); });
    $lista = json_decode($json, true);
    ok('busca de clientes: lista {id, nome} com o texto', is_array($lista) && (!$lista || (isset($lista[0]['id'], $lista[0]['nome']) && stripos(TratamentosService::removerAcentos($lista[0]['nome']), 'sil') !== false)), substr($json, 0, 200));
    igual('busca de clientes: menos de 3 letras nao busca', '[]', saida(function () { AgendamentosFilterForm::onBuscarClientes(['q' => 'si']); }));

    saida(function () use ($a1, $a2) { AgendamentosFilterForm::onFilter(['cliente_id' => ['5', ''], 'agenda_id' => [(string) $a1, (string) $a2], 'dt_busca' => '01/10/2026', 'dt_busca_fim' => '31/10/2026']); });
    $f = TSession::getValue('agendamentos_filter_data');
    igual('filtro: varios clientes, varias agendas e periodo na sessao', [[5], [$a1, $a2], '2026-10-01', '2026-10-31'], [$f->cliente_id, $f->agenda_id, $f->dt_busca, $f->dt_busca_fim]);
    $html = saida(function () { (new AgendamentosFilterForm([]))->show(); });
    ok('tela: agendas filtradas empilhadas abaixo do campo (com a cor)', strpos($html, 'class="fm-marcado" data-id="' . $a1 . '"') !== false && strpos($html, 'name="agenda_id[]" value="' . $a2 . '"') !== false);
    saida(function () { AgendamentosFilterForm::onFilter(['dt_busca' => date('d/m/Y'), 'dt_busca_fim' => date('d/m/Y')]); });
    igual('filtro: hoje a hoje (padrao do campo) nao filtra', ['', ''], [TSession::getValue('agendamentos_filter_data')->dt_busca, TSession::getValue('agendamentos_filter_data')->dt_busca_fim]);

    $html = saida(function () { (new AgendamentoCalendarFormView([]))->show(); });
    ok('calendario: abre na semana, altura natural (rola com a pagina) e hora atual', strpos($html, 'timeGridWeek') !== false && strpos($html, '"height":"auto"') !== false && strpos($html, 'nowIndicator') !== false && strpos($html, 'agd-faixa') === false, substr(strip_tags($html), 0, 200));
    ok('calendario: semana de domingo a sabado; Mes da semana atual em diante; Agenda comeca hoje; faixa Dia inteiro', strpos($html, '"firstDay":0') !== false
        && strpos($html, '"mesHoje":{"type":"dayGrid","duration":{"weeks":5},"dateAlignment":"week"') !== false
        && strpos($html, '"agendaHoje":{"type":"list","duration":{"weeks":1},"dateAlignment":"day"') !== false && strpos($html, 'timeGridDay,timeGridWeek,mesHoje,agendaHoje') !== false
        && strpos($html, '"allDaySlot":false') !== false && strpos($html, 'eventDidMount') !== false);
    ok('legenda: botao "Expandir legenda" com a largura da legenda aberta', strpos(AgendaVisual::botaoLegenda(), 'Expandir legenda') !== false && strpos(AgendaVisual::estilo(), '.agd-filtro-legenda { margin-left: auto; align-items: flex-start; width: 470px;') !== false);

    // eventos: so as agendas e o periodo filtrados (sem filtro de cliente, para ter o que contar)
    saida(function () use ($a1, $a2) { AgendamentosFilterForm::onFilter(['agenda_id' => [(string) $a1, (string) $a2], 'dt_busca' => '01/01/2020', 'dt_busca_fim' => '31/12/2030']); });
    $json = saida(function () { AgendamentoCalendarFormView::getEvents(['start' => '2026-10-01T00:00:00', 'end' => '2026-11-01T00:00:00']); });
    $eventos = json_decode($json, true);
    ok('eventos: json valido', is_array($eventos), substr($json, 0, 200));
    $agendamentos = array_values(array_filter((array) $eventos, function ($e) { return in_array('agd-agendamento', $e['classNames'] ?? [], true) && is_numeric($e['id'] ?? null) && empty($e['convidado']); }));
    $esperado = (int) q("SELECT count(*) n FROM agendamento WHERE ativo = 'T' AND agenda_id IN ({$a1}, {$a2}) AND dt_inicial <= '2026-10-31 23:59:59' AND dt_final >= '2026-10-01 00:00:00'")[0]['n'];
    ok('eventos: agendamentos das agendas filtradas, na cor da agenda', count($agendamentos) >= $esperado && !array_filter($agendamentos, function ($e) use ($a1, $a2) { return !in_array((int) $e['agenda_id'], [$a1, $a2], true) || empty($e['backgroundColor']); }),
        count($agendamentos) . ' x ' . $esperado);
    saida(function () { AgendamentosFilterForm::onLimparFiltros([]); });
    igual('Limpar filtros zera clientes, agendas e periodo', [[], [], ''], [TSession::getValue('agendamentos_filter_data')->cliente_id, TSession::getValue('agendamentos_filter_data')->agenda_id, TSession::getValue('agendamentos_filter_data')->dt_busca]);

    // ---------- compromisso em varias agendas ----------
    $html = saida(function () { (new CompromissoForm([]))->show(); });
    ok('compromisso novo: agendas no mesmo campo do filtro (lista com Todas e cor)', strpos($html, 'data-nome="agendas"') !== false && strpos($html, 'Todas as agendas') !== false && preg_match('/class="fm-item" data-id="' . $a1 . '"[^>]*data-cor="#[0-9a-f]{6}"/', $html) === 1, substr(strip_tags($html), 0, 160));
    $base = ['id' => '', 'tipo_compromisso_id' => (string) $tipoCompromisso, 'dt_inicio' => '15/01/2030 10:00', 'dt_final' => '15/01/2030 11:00', 'habilitar_repeticao' => 'N',
             'convidado_compromisso_compromisso_id' => [''], 'convidado_compromisso_compromisso___row__id' => ['1'], 'convidado_compromisso_compromisso___row__data' => [''], 'convidado_compromisso_compromisso_agenda_id' => ['']];
    post($base + ['agendas' => [(string) $a1, (string) $a2], 'observacao' => MARCA_AV . ' compromisso']);
    $s = saida(function () { (new CompromissoForm($_POST))->onSave($_POST); });
    igual('compromisso: um em cada agenda marcada', [$a1, $a2], array_map('intval', array_column(q("SELECT agenda_id FROM compromisso WHERE observacao = '" . MARCA_AV . " compromisso' ORDER BY agenda_id"), 'agenda_id')));
    ok('compromisso: aviso de quantos foram salvos', strpos($s, base64_encode('2 compromissos salvos, um em cada agenda marcada')) !== false, substr(strip_tags($s), 0, 200));
    post($base + ['agendas' => [], 'observacao' => MARCA_AV . ' sem agenda']);
    $s = saida(function () { (new CompromissoForm($_POST))->onSave($_POST); });
    ok('compromisso: sem agenda marcada e recusado', strpos($s, 'Marque pelo menos uma agenda') !== false && !q("SELECT 1 FROM compromisso WHERE observacao = '" . MARCA_AV . " sem agenda'"), substr(strip_tags($s), 0, 200));
    $id = (int) q("SELECT id FROM compromisso WHERE observacao = '" . MARCA_AV . " compromisso' ORDER BY id LIMIT 1")[0]['id'];
    $html = saida(function () use ($id) { $t = new CompromissoForm(['key' => $id]); $t->onEdit(['key' => $id]); $t->show(); });
    ok('compromisso existente: edita com a agenda unica dele', strpos($html, 'name="agenda_id"') !== false && strpos($html, 'data-nome="agendas"') === false);

    // ---------- bloqueio em varias agendas ----------
    post(['id' => '', 'dt_inicio' => '16/01/2030 08:00', 'dt_final' => '16/01/2030 12:00', 'habilitar_repeticao' => 'N', 'agendas' => [(string) $a1, (string) $a2], 'observacao' => MARCA_AV . ' bloqueio']);
    $s = saida(function () { (new BloqueioForm($_POST))->onSave($_POST); });
    igual('bloqueio: um em cada agenda marcada', [$a1, $a2], array_map('intval', array_column(q("SELECT agenda_id FROM bloqueio WHERE observacao = '" . MARCA_AV . " bloqueio' ORDER BY agenda_id"), 'agenda_id')), substr(strip_tags($s), 0, 200));

    // ---------- excecao (agendamento) em varias agendas ----------
    x("INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES ('" . MARCA_AV . " CLIENTE', 1)");
    $cliente = (int) q("SELECT id FROM pessoa WHERE nome = '" . MARCA_AV . " CLIENTE'")[0]['id'];
    $html = saida(function () { (new AgendamentoExcecao([]))->show(); });
    ok('excecao nova: agendas no mesmo campo do filtro, que atualiza o profissional ao fechar', strpos($html, 'data-nome="agendas"') !== false && strpos($html, 'method=onChange') !== false, substr(strip_tags($html), 0, 160));
    post(['id' => '', 'cliente_id' => (string) $cliente, 'especialidade_id' => '', 'dt_inicial' => '17/01/2030 09:00', 'dt_final' => '17/01/2030 09:30', 'online' => 'F', 'habilitar_repeticao' => 'N',
          'observacao' => MARCA_AV, 'agendas' => [(string) $a1, (string) $a2],
          'agendamento_procedimento_agendamento_id' => [''], 'agendamento_procedimento_agendamento___row__id' => ['1'], 'agendamento_procedimento_agendamento___row__data' => [''],
          'agendamento_procedimento_agendamento_parceiro_id' => [(string) $proc['parceiro_id']], 'agendamento_procedimento_agendamento_procedimento_id' => [(string) $proc['procedimento_id']],
          'agendamento_procedimento_agendamento_valor' => ['100,00'], 'agendamento_procedimento_agendamento_quantidade' => ['1,00'], 'agendamento_procedimento_agendamento_valor_total' => ['100,00']]);
    $s = saida(function () { (new AgendamentoExcecao($_POST))->onSave($_POST); });
    $criados = q("SELECT a.agenda_id, (SELECT count(*) FROM agendamento_procedimento p WHERE p.agendamento_id = a.id) procs FROM agendamento a WHERE a.cliente_id = {$cliente} ORDER BY a.agenda_id");
    igual('excecao: um agendamento em cada agenda, com os procedimentos', [[$a1, 1], [$a2, 1]], array_map(function ($r) { return [(int) $r['agenda_id'], (int) $r['procs']]; }, $criados), substr(strip_tags($s), 0, 300));

    // ---------- horario livre na agenda (30/10/2026) ----------
    $livre = function (int $agenda, string $ini, string $fim) {
        TTransaction::open('escritorio');
        try { AgendaConflitoService::exigirLivre($agenda, $ini, $fim); TTransaction::close(); return ''; }
        catch (Throwable $e) { TTransaction::rollback(); return $e->getMessage(); }
    };
    $choque = q("SELECT fl_permite_choque_horario f FROM agenda WHERE id = {$a1}")[0]['f'];
    if ($choque !== 'T') {
        ok('conflito: outro agendamento no mesmo horario', strpos($livre($a1, '2030-01-17 09:15', '2030-01-17 09:45'), 'Já existe um agendamento') !== false);
        $s = saida(function () { (new AgendamentoExcecao($_POST))->onSave($_POST); });
        ok('conflito: a excecao de novo no mesmo horario e recusada, com o motivo', strpos($s, 'Já existe um agendamento') !== false && count(q("SELECT 1 FROM agendamento WHERE cliente_id = {$cliente}")) === 2, substr(strip_tags($s), 0, 300));
    }
    $m = $livre($a1, '2030-01-16 11:00', '2030-01-16 13:00');
    ok('conflito: bloqueio no horario (com data e horas na mensagem)', strpos($m, 'Já existe um bloqueio') !== false && strpos($m, '16/01/2030 das 08:00 às 12:00') !== false, $m);
    ok('conflito: compromisso no horario', strpos($livre($a2, '2030-01-15 10:30', '2030-01-15 10:45'), 'Já existe um compromisso') !== false);
    igual('conflito: encostar (comeca quando o bloqueio termina) pode', '', $livre($a1, '2030-01-16 12:00', '2030-01-16 12:30'));
    ok('conflito: fim antes do inicio e recusado', strpos($livre($a1, '2030-01-16 13:00', '2030-01-16 12:30'), 'depois do início') !== false);

    // ---------- cartoes e sobreposicao (30/10/2026) ----------
    $ev = AgendaVisual::estilizar(['title' => "<div class='agd-ev'><span class='agd-ev-l1'>NOME</span><span class='agd-ev-l2'>DETALHE</span></div>", 'start' => '2030-01-01T09:00:00', 'end' => '2030-01-01T09:30:00'], 'agendamento', '#2563eb', 1);
    ok('cartao: nome, depois sempre o horario de inicio e fim, depois o detalhe', strpos($ev['title'], "NOME</span><span class='agd-ev-hora'>09:00 – 09:30</span><span class='agd-ev-l2'>DETALHE") !== false, $ev['title']);
    $ag = new Agendamento();
    $ag->dt_inicial = '2030-01-01 14:30:00'; $ag->dt_final = '2030-01-01 15:00:00';
    $mesmoDia = $ag->horario_texto;
    $ag->dt_final = '2030-01-02 01:00:00';
    igual('resumo ao passar o mouse: inicio e fim (data duas vezes se mudar o dia)', ['01/01/2030 14:30 às 15:00', '01/01/2030 14:30 às 02/01/2030 01:00'], [$mesmoDia, $ag->horario_texto]);
    $cartao = AgendaVisual::resumo("<div class='agd-ev'>X</div>", 'Bloqueio', ['Horário' => AgendaVisual::horarioTexto('2030-01-01 08:00', '2030-01-01 12:00'), 'Agenda' => "Dra. O'Neil", 'Observação' => '']);
    preg_match("/data-resumo-titulo='([^']*)' data-resumo='([^']*)'/", $cartao, $m);
    $conteudo = base64_decode($m[2] ?? '');
    ok('resumo ao passar o mouse tambem no bloqueio/compromisso (horario, agenda; vazio nao aparece; aspas escapadas; sem popover do bootstrap)',
        strpos($cartao, 'data-popover') === false && base64_decode($m[1] ?? '') === 'Bloqueio' && strpos($conteudo, '01/01/2030 08:00 às 12:00') !== false
        && strpos($conteudo, 'O&#039;Neil') !== false && strpos($conteudo, 'Observação') === false && strpos($cartao, "<div class='agd-ev'>X</div>") !== false, $cartao);
    ok('resumo: o calendario mostra junto do cursor (eventDidMount)', strpos(AgendaVisual::scriptCalendario(), "getElementById('agd-resumo')") !== false && strpos(AgendaVisual::scriptCalendario(), 'mousemove') !== false);
    $org = AgendaVisual::organizar([
        ['id' => 'ag', 'camada' => 1, 'start' => '2030-01-01T09:00:00', 'end' => '2030-01-01T10:00:00', 'classNames' => ['agd-agendamento']],
        ['id' => 'bl', 'camada' => 3, 'start' => '2030-01-01T13:00:00', 'end' => '2030-01-01T15:00:00', 'classNames' => ['agd-bloqueio']],
        ['id' => 'cg', 'agenda_id' => '1', 'camada' => 2, 'start' => '2030-01-02T08:00:00', 'end' => '2030-01-03T17:00:00', 'classNames' => ['agd-compromisso'], 'cor_fundo' => '#dee8fc'],
        ['id' => 'in', 'agenda_id' => '1', 'camada' => 4, 'display' => 'background', 'start' => '2030-01-03T12:00:00', 'end' => '2030-01-03T13:00:00'],
        ['id' => 'b9', 'camada' => 3, 'start' => '2030-01-04T08:00:00', 'end' => '2030-01-04T17:00:00', 'classNames' => ['agd-bloqueio']],
    ]);
    $resumoOrg = array_map(function ($e) {
        $onde = !empty($e['so_na_grade']) ? 'grade' : (!empty($e['so_fora_da_grade']) ? 'mes/lista' : 'todas');
        return $e['id'] . ' ' . ($e['display'] ?? '-') . ' ' . $onde . ' ' . $e['start'] . '/' . $e['end'];
    }, $org);
    igual('dia/semana: longo vira um bloco por dia (do comeco ao fim do dia, cortado pelo intervalo da agenda); mes/agenda: uma barra so',
        ['ag - todas 2030-01-01T09:00:00/2030-01-01T10:00:00', 'bl background todas 2030-01-01T13:00:00/2030-01-01T15:00:00',
         'cg - mes/lista 2030-01-02/2030-01-04', 'cg background grade 2030-01-02T08:00:00/2030-01-03T00:00:00',
         'cg background grade 2030-01-03T00:00:00/2030-01-03T12:00:00', 'cg background grade 2030-01-03T13:00:00/2030-01-03T17:00:00',
         'in background todas 2030-01-03T12:00:00/2030-01-03T13:00:00',
         'b9 - mes/lista 2030-01-04/2030-01-05', 'b9 background grade 2030-01-04T08:00:00/2030-01-04T17:00:00'],
        $resumoOrg);
    ok('script: cada visao esconde o que nao e dela', strpos(AgendaVisual::scriptCalendario(), 'e.so_na_grade') !== false && strpos(AgendaVisual::scriptCalendario(), 'e.so_fora_da_grade') !== false);
    $camadas = AgendaVisual::camadas([
        ['id' => 'ag', 'agenda_id' => '1', 'camada' => 1, 'start' => '2030-01-01T09:00:00', 'end' => '2030-01-01T11:00:00'],
        ['id' => 'bl', 'agenda_id' => '1', 'camada' => 3, 'start' => '2030-01-01T10:00:00', 'end' => '2030-01-01T12:00:00'],
        ['id' => 'in', 'agenda_id' => '1', 'camada' => 4, 'start' => '2030-01-01T11:00:00', 'end' => '2030-01-01T11:30:00'],
        ['id' => 'outra', 'agenda_id' => '2', 'camada' => 1, 'start' => '2030-01-01T10:00:00', 'end' => '2030-01-01T11:00:00'],
        ['id' => 'tudo', 'agenda_id' => '1', 'camada' => 2, 'start' => '2030-01-01T10:15:00', 'end' => '2030-01-01T10:45:00'],
    ]);
    igual('sobreposicao: intervalo > bloqueio > compromisso > agendamento na mesma agenda; outra agenda nao muda',
        ['ag 09:00-10:00', 'bl 10:00-11:00', 'bl 11:30-12:00', 'in 11:00-11:30', 'outra 10:00-11:00'],
        array_map(function ($e) { return $e['id'] . ' ' . substr($e['start'], 11, 5) . '-' . substr($e['end'], 11, 5); }, $camadas));
    $html = saida(function () { (new AgendamentoCalendarFormView([]))->show(); });
    ok('calendario: cartoes lado a lado (sem cascata) e cabecalho dos dias sem ficar fixo', strpos($html, '"slotEventOverlap":false') !== false && strpos($html, '"stickyHeaderDates":false') !== false && strpos($html, '"eventMinHeight":28') !== false);
} finally {
    limparAgendaMulti();
    FiltroLista::limparSessao('AgendamentosFilterForm');
}
igual('sem sobras do teste', 0, count(q("SELECT 1 FROM compromisso WHERE observacao LIKE '" . MARCA_AV . "%' UNION ALL SELECT 1 FROM bloqueio WHERE observacao LIKE '" . MARCA_AV . "%' UNION ALL SELECT 1 FROM pessoa WHERE nome LIKE '" . MARCA_AV . "%'")));
fim();
