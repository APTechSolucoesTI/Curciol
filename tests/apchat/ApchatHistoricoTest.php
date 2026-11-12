<?php
// tests/apchat/ApchatHistoricoTest.php
// Historico do WhatsApp no cadastro do cliente (10/11/2026): numero do contato principal e dos auxiliares (enquanto nenhum outro
// cliente tiver o numero como principal); so tickets fechados, ordem pelo encerramento; atribuido = atendente do ticket; profissionais = ele e quem respondeu; ticket sem especialidade; o
// atendente do ticket; conversa so com texto e so do proprio cliente; botao "Abrir no APChat". APChat falso.
require __DIR__ . '/../bootstrap.php';
LinkApp::forcar('apchat', false); // links pelo app instalado tem teste proprio (LinkAppTest)
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_HIST = 'TESTE HISTORICO WHATSAPP';
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }
$limpar = function () {
    x("DELETE FROM apchat_usuario WHERE apchat_usuario_id = 987650");
    foreach (q("SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_HIST . "%'") as $s)
    {
        x("DELETE FROM atendimento WHERE cliente_id = {$s['id']} OR profissional_id = {$s['id']}");
        x("DELETE FROM pessoa_especialidade WHERE pessoa_id = {$s['id']}");
        x("DELETE FROM pessoa_contato WHERE pessoa_id = {$s['id']}");
        x("DELETE FROM pessoa WHERE id = {$s['id']}");
    }
    x("DELETE FROM mensagem WHERE template = '" . MARCA_HIST . "'");
};
$limpar();

$tk = function (int $id, string $numero, string $status, string $fechado, ?int $fechou, ?string $nomeFechou, string $atendentes, bool $doCliente = true) {
    return ['id' => $id, 'arquivado' => false, 'status' => $status, 'numero' => $numero, 'contato' => 'Contato ' . $id,
            'aberto_em' => date('Y-m-d H:i:s', strtotime($fechado . ' -2 hours')), 'fechado_em' => $status === 'closed' ? $fechado : null,
            'canal' => 'Curciol - Oficial', 'fila' => 'Fila Um', 'userId' => 987650, 'atendente' => 'Atendente Um',
            'closedByUserId' => $fechou, 'fechou' => $nomeFechou, 'atendentes' => $atendentes, 'do_cliente' => $doCliente];
};
$falso = new class([
    $tk(10, '5519982220001', 'closed', '2026-11-05 10:00:00', 9002, 'Atendente Dois', 'Atendente Um|Atendente Dois'),
    $tk(11, '5519982220002', 'closed', '2026-11-07 16:30:00', null, null, 'Atendente Um'),
    $tk(12, '5519982220003', 'closed', '2026-11-08 09:00:00', 9001, 'Atendente Um', 'Atendente Um'),
    $tk(13, '5519982220001', 'open', '2026-11-09 09:00:00', null, null, ''),
    // aberto so pelo envio da Curciol (isClosed), sem mensagem do cliente: ja aparece como mensagem da Curciol -> fora
    $tk(14, '5519982220001', 'closed', '2026-11-09 10:00:00', null, null, '', false),
    // envio da Curciol que o cliente respondeu no mesmo ticket: aparece
    $tk(15, '5519982220001', 'closed', '2026-11-09 11:00:00', null, null, ''),
    // so mensagem nossa, mas nao foi a Curciol que abriu (atendente pelo APChat): aparece
    $tk(16, '5519982220001', 'closed', '2026-11-09 12:00:00', 9001, 'Atendente Um', 'Atendente Um', false),
]) {
    public $t; public function __construct($t) { $this->t = $t; }
    public function disponivel() { return true; }
    public function ticketsFechadosDosNumeros(array $numeros): array { return array_values(array_filter($this->t, function ($t) use ($numeros) { return in_array($t['numero'], $numeros, true); })); }
    public function mensagensDoTicket(int $ticketId, bool $arquivado): array {
        return [
            ['id' => 'a', 'body' => 'Bom dia, preciso de ajuda', 'mediaType' => 'chat', 'fileName' => null, 'fromMe' => false, 'isDeleted' => false, 'ticketId' => $ticketId, 'createdAt' => '2026-11-05 08:00:00', 'arquivada' => false, 'atendente' => null, 'fila' => 'Fila Um'],
            ['id' => 'b', 'body' => 'foto.jpg', 'mediaType' => 'image', 'fileName' => 'foto.jpg', 'fromMe' => false, 'isDeleted' => false, 'ticketId' => $ticketId, 'createdAt' => '2026-11-05 08:01:00', 'arquivada' => false, 'atendente' => null, 'fila' => 'Fila Um'],
            ['id' => 'c', 'body' => "*Atendente Um*:\n Claro, _pode_ falar", 'mediaType' => 'chat', 'ack' => 3, 'fileName' => null, 'fromMe' => true, 'isDeleted' => false, 'ticketId' => $ticketId, 'createdAt' => '2026-11-05 08:02:00', 'arquivada' => false, 'atendente' => 'Atendente Um', 'fila' => 'Fila Um', 'canal' => 'Curciol - Oficial'],
            ['id' => 'e', 'body' => '{"body":"Escolha *uma* opção:","footer":"","button_text":"Ver opções","sections":[{"title":"","rows":[{"title":"Opção A"},{"title":"Opção B"}]}]}', 'mediaType' => 'list', 'fileName' => null, 'fromMe' => true, 'isDeleted' => false, 'ticketId' => $ticketId, 'createdAt' => '2026-11-05 08:02:30', 'arquivada' => false, 'atendente' => null, 'fila' => 'Fila Um'],
            ['id' => 'f', 'body' => 'Atendente Dois - Fila Um • 05/11/2026, 08:03', 'mediaType' => 'transfer', 'fileName' => null, 'fromMe' => true, 'isDeleted' => false, 'ticketId' => $ticketId, 'createdAt' => '2026-11-05 08:02:40', 'arquivada' => false, 'atendente' => 'Atendente Um', 'fila' => 'Fila Um'],
            ['id' => 'g', 'body' => '<script>alert(1)</script>', 'mediaType' => 'chat', 'fileName' => null, 'fromMe' => false, 'isDeleted' => false, 'ticketId' => $ticketId, 'createdAt' => '2026-11-06 07:00:00', 'arquivada' => false, 'atendente' => null, 'fila' => 'Fila Um'],
            ['id' => 'd', 'body' => '', 'mediaType' => 'chat', 'fileName' => null, 'fromMe' => true, 'isDeleted' => false, 'ticketId' => $ticketId, 'createdAt' => '2026-11-05 08:03:00', 'arquivada' => false, 'atendente' => 'Atendente Um', 'fila' => 'Fila Um'],
        ];
    }
    public function contato(int $id): ?array { return ['id' => $id, 'name' => 'Sem vinculo', 'number' => '5511900007777', 'createdAt' => '2026-11-06 16:52:00']; }
    public function conversa(int $contatoId, int $limite = 3000): array { return [['id' => 'z', 'body' => 'oi', 'mediaType' => 'chat', 'fileName' => null, 'fromMe' => false, 'isDeleted' => false, 'ticketId' => 55, 'createdAt' => '2026-11-06 17:00:00', 'arquivada' => false, 'atendente' => null, 'fila' => null, 'ticket_status' => 'closed']]; }
};
// a homologacao tem historico_somente_numeros no apchat.php real: o teste usa um arquivo inexistente (= todos os numeros)
$semConfig = sys_get_temp_dir() . "/apchat-sem-config-" . getmypid() . ".php";
ApchatBancoService::usarConfig($semConfig);
ApchatBancoService::usarFalso($falso);
TSession::setValue('userid', 1);

$a = $b = null;
try
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $a = (int) $c->query("INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES ('" . MARCA_HIST . " A', 1) RETURNING id")->fetchColumn();
    $b = (int) $c->query("INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES ('" . MARCA_HIST . " B', 1) RETURNING id")->fetchColumn();
    $c->exec("INSERT INTO pessoa_contato (pessoa_id, nome, telefone, principal) VALUES ({$a}, 'A', '(19) 98222-0001', 'S'), ({$a}, 'RECADO', '(19) 98222-0002', 'N'),
              ({$a}, 'FILHO', '(19) 98222-0003', 'N'), ({$b}, 'B', '(19) 98222-0003', 'S')");
    $unidade = (int) $c->query('SELECT system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetchColumn();
    $ag = (int) $c->query('SELECT id FROM agendamento ORDER BY id LIMIT 1')->fetchColumn();
    $c->exec("INSERT INTO mensagem (agendamento_id, system_user_id, tipo_mensagem, destino, template, enviado_em, apchat_ticket_id) VALUES
              ({$ag}, 1, 'WHATSAPP', '19982220001', '" . MARCA_HIST . "', now(), 14), ({$ag}, 1, 'WHATSAPP', '19982220001', '" . MARCA_HIST . "', now(), 15)");
    // profissional da Curciol ligado ao atendente do APChat (987650) com duas especialidades; e um atendimento avulso da Curciol
    // para o cliente A, com esse profissional (atendimento avulso nao tem agendamento: especialidade vem do profissional)
    $prof = (int) $c->query("INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES ('" . MARCA_HIST . " PROF', 1) RETURNING id")->fetchColumn();
    $livre = (int) $c->query('SELECT u.id FROM system_users u WHERE NOT EXISTS (SELECT 1 FROM apchat_usuario a WHERE a.system_user_id = u.id)
                               AND NOT EXISTS (SELECT 1 FROM pessoa p WHERE p.system_users_id = u.id) ORDER BY u.id LIMIT 1')->fetchColumn();
    $c->exec("UPDATE pessoa SET system_users_id = {$livre} WHERE id = {$prof}");
    $c->exec("INSERT INTO apchat_usuario (system_user_id, apchat_usuario_id) VALUES ({$livre}, 987650)");
    $esp = $c->query('SELECT id, descricao FROM especialidade ORDER BY descricao LIMIT 2')->fetchAll(PDO::FETCH_ASSOC);
    $c->exec("INSERT INTO pessoa_especialidade (pessoa_id, especialidade_id) VALUES ({$prof}, {$esp[1]['id']}), ({$prof}, {$esp[0]['id']})");
    $espTexto = $esp[0]['descricao'] . ', ' . $esp[1]['descricao'];
    $c->exec("INSERT INTO atendimento (cliente_id, profissional_id, tipo_atendimento_id, dt_inicio, dt_final) VALUES ({$a}, {$prof}, 2, '2026-11-06 09:00:00', '2026-11-06 09:30:00')");
    TTransaction::close();
    TSession::setValue('userunitid', $unidade);

    TTransaction::open('escritorio');
    $na = ApchatHistoricoService::numerosDoCliente($a);
    ok('cliente A: principal e auxiliar', in_array('5519982220001', $na, true) && in_array('5519982220002', $na, true), json_encode($na));
    ok('cliente A: com as variantes sem o 9', in_array('551982220001', $na, true));
    ok('cliente A: auxiliar que e principal de B fica de fora', !in_array('5519982220003', $na, true));
    ok('cliente B: o principal dele', in_array('5519982220003', ApchatHistoricoService::numerosDoCliente($b), true));

    $ta = ApchatHistoricoService::tickets($a);
    igual('cliente A: so fechados, ordem pelo encerramento, sem o ticket aberto so pelo envio da Curciol', [16, 15, 11, 10], array_column($ta, 'id'));
    igual('cliente B: o ticket do principal dele', [12], array_column(ApchatHistoricoService::tickets($b), 'id'));
    $t10 = $ta[3] ?? [];
    igual('atribuido = o atendente do ticket (nao quem encerrou)', 'Atendente Um', $t10['atribuido']);
    igual('todos os atendentes', ['Atendente Um', 'Atendente Dois'], $t10['atendentes']);
    igual('profissionais: o atribuido e quem respondeu, sem repetir', ['Atendente Um', 'Atendente Dois'], $t10['profissionais']);
    ok('ticket do APChat sem especialidade, mesmo com profissional ligado que tem especialidade (11/11 tarde)', ($t10['especialidade'] ?? '') === '');
    ok('canal, fila, abertura e encerramento', $t10['canal'] === 'Curciol - Oficial' && $t10['fila'] === 'Fila Um' && $t10['fechado_em'] === '2026-11-05 10:00:00' && $t10['aberto_em'] !== '');

    // homologacao (11/11/2026): 'historico_somente_numeros' no app/config/apchat.php limita o historico a esses numeros
    // (com ou sem o 9, com ou sem mascara); sem a chave (producao) vale para todos
    $cfg = tempnam(sys_get_temp_dir(), 'apchat-cfg-');
    try
    {
        file_put_contents($cfg, "<?php return ['historico_somente_numeros' => ['(19) 98222-0002', '551982220003']];");
        ApchatBancoService::usarConfig($cfg);
        ApchatBancoService::usarFalso($falso);
        igual('somente numeros: o cliente A so ve o ticket do numero liberado', [11], array_column(ApchatHistoricoService::tickets($a), 'id'));
        igual('somente numeros: liberado sem o 9 vale para o numero com o 9', [12], array_column(ApchatHistoricoService::tickets($b), 'id'));
        file_put_contents($cfg, "<?php return ['historico_somente_numeros' => []];");
        igual('somente numeros: lista vazia = nenhum historico', [], ApchatHistoricoService::tickets($a));
        file_put_contents($cfg, "<?php return ['host' => 'x'];");
        igual('sem a chave: todos', [16, 15, 11, 10], array_column(ApchatHistoricoService::tickets($a), 'id'));
    }
    finally
    {
        ApchatBancoService::usarConfig($semConfig);
        ApchatBancoService::usarFalso($falso);
        @unlink($cfg);
    }

    $conv = ApchatHistoricoService::conversa(10, $a);
    igual('conversa: texto, lista e transferencia (sem midia nem vazia)', ['Bom dia, preciso de ajuda', "*Atendente Um*:\n Claro, _pode_ falar", 'e', 'f', '<script>alert(1)</script>'],
          array_map(function ($m) { return in_array($m['id'], ['e', 'f'], true) ? $m['id'] : $m['body']; }, $conv));
    lanca('conversa de ticket de outro cliente: recusa', fn() => ApchatHistoricoService::conversa(12, $a), 'não é deste cliente');
    lanca('ticket aberto nao aparece', fn() => ApchatHistoricoService::conversa(13, $a), 'não é deste cliente');
    TTransaction::close();

    // telas
    $v = saida(function () use ($a) { $t = new ApchatTicketView(['key' => 10, 'pessoa_id' => $a]); $t->onShow(['key' => 10, 'pessoa_id' => $a]); $t->show(); });
    ok('ticket: informacoes no topo', strpos($v, 'Curciol - Oficial') !== false && strpos($v, '05/11/2026 10:00') !== false && strpos($v, '05/11/2026 08:00') !== false
        && strpos($v, '#10 · 5519982220001') !== false, substr($v, 0, 500));
    ok('ticket: Profissional com todos os atendentes no mesmo campo', strpos($v, 'Atendente Um, Atendente Dois') !== false && strpos($v, 'Atenderam') === false);
    ok('ticket: sem traco no campo vazio', strpos($v, '—') === false);
    ok('ticket: botao Abrir no APChat', strpos($v, 'Abrir no APChat') !== false && strpos($v, 'atendimento?ticketId=10&quot;, &quot;apchat&quot;') !== false, $v);
    ok('ticket: conversa sem a imagem', strpos($v, 'foto.jpg') === false);
    ok('ticket: formatacao do WhatsApp (negrito e italico)', strpos($v, '<b>Atendente Um</b>:<br>Claro, <i>pode</i> falar') !== false);
    ok('ticket: lista do robo desenhada, nao o JSON', strpos($v, 'Escolha <b>uma</b> opção:') !== false && strpos($v, 'Ver opções') !== false && strpos($v, 'Opção B') !== false
        && strpos($v, 'button_text') === false);
    ok('ticket: transferencia como aviso no meio da conversa', strpos($v, 'apc-aviso') !== false && strpos($v, 'Atendente Dois - Fila Um') !== false);
    ok('ticket: HTML da mensagem escapado', strpos($v, '<script>alert(1)') === false && strpos($v, '&lt;script&gt;alert(1)') !== false);
    ok('ticket: visto azul na mensagem lida', strpos($v, 'apc-lida') !== false);
    ok('ticket: separador de dia', strpos($v, '06/11/2026') !== false);
    ok('ticket: conversa ate o fim da tela, com as ultimas mensagens embaixo', strpos($v, 'apc-conversa apc-conversa-tela') !== false && strpos($v, 'innerHeight') !== false
        && strpos($v, 'apc-trilha') !== false);
    ok('ticket: so a conversa rola (desconta o que sobrar na rolagem do painel)', strpos($v, 'scrollHeight') !== false && strpos($v, 'clientHeight') !== false);

    $h = saida(function () use ($a) { (new HistoricosAtendimentoCliente(['key' => $a]))->show(); });
    ok('historico do cliente: abre com atendimento da Curciol e tickets juntos (sem erro de ticket_id)', strpos($h, 'não existe ou contém') === false && strpos($h, '>Atendimento<') !== false, substr($h, 0, 300));
    ok('historico: colunas Tipo, Inicio, Fim, Profissional, Especialidade e Fila', strpos($h, '>Início<') !== false && strpos($h, '>Fim<') !== false && strpos($h, '>Fila<') !== false
        && strpos($h, '>Data<') === false, substr($h, 0, 300));
    ok('historico: ticket como APChat, sem icone do WhatsApp', strpos($h, 'APChat') !== false && strpos($h, 'fa-whatsapp') === false);
    ok('historico: na lista so o atribuido (quem so respondeu nao aparece)', strpos($h, 'Atendente Dois') === false);
    ok('historico: sem traco', strpos($h, '—') === false);
    // 11/11/2026 (tarde): coluna ID entre Tipo e Inicio; uma coluna so de acao (lupa alinhada a esquerda); ticket sem especialidade
    preg_match_all('#<tr popover[^>]*>(.*?)</tr>#s', $h, $trs);
    $linhas = [];
    foreach ($trs[1] as $tr)
    {
        preg_match_all('#<td[^>]*class="tdatagrid_cell( action)?"[^>]*>(.*?)</td>#s', $tr, $tds, PREG_SET_ORDER);
        $acoes = array_values(array_filter($tds, function ($td) { return $td[1] === ' action'; }));
        $dados = array_values(array_map(function ($td) { return trim(strip_tags($td[2])); }, array_filter($tds, function ($td) { return $td[1] === ''; })));
        $linhas[$dados[1] ?? ''] = ['acoes' => count($acoes), 'acao' => $acoes[0][2] ?? '', 'dados' => $dados];
    }
    $atend = (int) q("SELECT id FROM atendimento WHERE cliente_id = {$a} ORDER BY id DESC LIMIT 1")[0]['id'];
    ok('historico: cabecalho Tipo, ID, Inicio, Fim, Profissional, Especialidade, Fila nessa ordem', preg_match('#>Tipo<.*>ID<.*>Início<.*>Fim<.*>Profissional<.*>Especialidade<.*>Fila<#s', $h) === 1);
    igual('historico: linha do ticket #10 (ID, inicio, fim, atribuido, sem especialidade, fila)', ['APChat', '10', '05/11/2026 08:00', '05/11/2026 10:00', 'Atendente Um', '', 'Fila Um'], $linhas['10']['dados'] ?? null);
    igual('historico: linha do atendimento da Curciol (especialidade do profissional)', ['Atendimento', (string) $atend, '06/11/2026 09:00', '06/11/2026 09:30', MARCA_HIST . ' PROF', $espTexto, ''],
          $linhas[(string) $atend]['dados'] ?? null);
    ok('historico: uma coluna so de acao em todas as linhas, com a lupa', count($linhas) === 5 && count(array_filter($linhas, function ($l) { return $l['acoes'] === 1 && strpos($l['acao'], 'fa-search') !== false; })) === 5,
       json_encode(array_map(function ($l) { return $l['acoes']; }, $linhas)));
    ok('historico: a acao abre pelo mesmo metodo (ticket ou atendimento)', strpos($linhas['10']['acao'] ?? '', 'method=onAbrir') !== false && strpos($linhas[(string) $atend]['acao'] ?? '', 'method=onAbrir') !== false);
    $abrir = function (array $p) { ob_start(); HistoricosAtendimentoCliente::onAbrir($p); return ob_get_clean(); };
    ok('abrir ticket: carrega a conversa do ticket', strpos($abrir(['key' => 'w10', 'ticket_id' => 10, 'pessoa_id' => $a]), 'ApchatTicketView') !== false);
    ok('abrir atendimento: carrega o atendimento', strpos($abrir(['key' => $atend, 'ticket_id' => 0, 'pessoa_id' => $a]), 'AtendimentoFormView') !== false);
    $tl = saida(function () use ($a) { (new AtendimentoTimeLine(['cliente_id' => $a, 'target_container' => 'x']))->show(); });
    ok('timeline do cliente: tickets do APChat', strpos($tl, 'APChat · Ticket #10') !== false && strpos($tl, 'APChat · Ticket #11') !== false && strpos($tl, 'Ticket #12') === false, substr($tl, 0, 300));
    ok('timeline: sem traco e sem WhatsApp', strpos($tl, '—') === false && strpos($tl, 'fa-whatsapp') === false);

    // permissao: quem abre o cadastro do cliente abre a conversa do ticket (grupos e permissao direta)
    $perm = q("SELECT (SELECT count(*) FROM system_program WHERE controller = 'ApchatTicketView') programa,
                      (SELECT count(*) FROM system_group_program g JOIN system_program p ON p.id = g.system_program_id AND p.controller = 'ClienteForm'
                        WHERE NOT EXISTS (SELECT 1 FROM system_group_program g2 JOIN system_program p2 ON p2.id = g2.system_program_id AND p2.controller = 'ApchatTicketView'
                                           WHERE g2.system_group_id = g.system_group_id)) grupos_sem,
                      (SELECT count(*) FROM system_user_program u JOIN system_program p ON p.id = u.system_program_id AND p.controller = 'ClienteForm'
                        WHERE NOT EXISTS (SELECT 1 FROM system_user_program u2 JOIN system_program p2 ON p2.id = u2.system_program_id AND p2.controller = 'ApchatTicketView'
                                           WHERE u2.system_user_id = u.system_user_id)) usuarios_sem")[0];
    ok('conversa do ticket cadastrada nas permissoes, com os mesmos grupos e usuarios do cadastro do cliente', (int) $perm['programa'] === 1 && (int) $perm['grupos_sem'] === 0 && (int) $perm['usuarios_sem'] === 0, json_encode($perm));

    $sv = saida(function () { $t = new ApchatContatoConversaView(['key' => 77]); $t->onShow(['key' => 77]); $t->show(); });
    ok('contatos sem vinculo: Abrir no APChat (ticket mais recente)', strpos($sv, 'Abrir no APChat') !== false && strpos($sv, 'atendimento?ticketId=55&quot;, &quot;apchat&quot;') !== false, substr($sv, 0, 300));
}
finally
{
    while (TTransaction::get()) { TTransaction::rollback(); }
    ApchatBancoService::usarFalso(null);
    ApchatBancoService::usarConfig(null);
    $limpar();
}

fim();
