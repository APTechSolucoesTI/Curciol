<?php
// tests/apchat/ApchatHistoricoTest.php
// Historico do WhatsApp no cadastro do cliente (10/11/2026): numero do contato principal e dos auxiliares (enquanto nenhum outro
// cliente tiver o numero como principal); so tickets fechados, ordem pelo encerramento; principal = quem encerrou, senao o
// atendente do ticket; conversa so com texto e so do proprio cliente; botao "Abrir no APChat". APChat falso.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_HIST = 'TESTE HISTORICO WHATSAPP';
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }
$limpar = function () {
    foreach (q("SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_HIST . "%'") as $s)
    {
        x("DELETE FROM pessoa_contato WHERE pessoa_id = {$s['id']}");
        x("DELETE FROM pessoa WHERE id = {$s['id']}");
    }
    x("DELETE FROM mensagem WHERE template = '" . MARCA_HIST . "'");
};
$limpar();

$tk = function (int $id, string $numero, string $status, string $fechado, ?int $fechou, ?string $nomeFechou, string $atendentes, bool $doCliente = true) {
    return ['id' => $id, 'arquivado' => false, 'status' => $status, 'numero' => $numero, 'contato' => 'Contato ' . $id,
            'aberto_em' => date('Y-m-d H:i:s', strtotime($fechado . ' -2 hours')), 'fechado_em' => $status === 'closed' ? $fechado : null,
            'canal' => 'Curciol - Oficial', 'fila' => 'Fila Um', 'userId' => 9001, 'atendente' => 'Atendente Um',
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
            ['id' => 'c', 'body' => 'Claro, pode falar', 'mediaType' => 'chat', 'fileName' => null, 'fromMe' => true, 'isDeleted' => false, 'ticketId' => $ticketId, 'createdAt' => '2026-11-05 08:02:00', 'arquivada' => false, 'atendente' => 'Atendente Um', 'fila' => 'Fila Um'],
            ['id' => 'd', 'body' => '', 'mediaType' => 'chat', 'fileName' => null, 'fromMe' => true, 'isDeleted' => false, 'ticketId' => $ticketId, 'createdAt' => '2026-11-05 08:03:00', 'arquivada' => false, 'atendente' => 'Atendente Um', 'fila' => 'Fila Um'],
        ];
    }
    public function contato(int $id): ?array { return ['id' => $id, 'name' => 'Sem vinculo', 'number' => '5511900007777', 'createdAt' => '2026-11-06 16:52:00']; }
    public function conversa(int $contatoId, int $limite = 3000): array { return [['id' => 'z', 'body' => 'oi', 'mediaType' => 'chat', 'fileName' => null, 'fromMe' => false, 'isDeleted' => false, 'ticketId' => 55, 'createdAt' => '2026-11-06 17:00:00', 'arquivada' => false, 'atendente' => null, 'fila' => null, 'ticket_status' => 'closed']]; }
};
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
    igual('principal = quem encerrou', 'Atendente Dois', $t10['principal']);
    igual('todos os atendentes', ['Atendente Um', 'Atendente Dois'], $t10['atendentes']);
    ok('canal, fila, abertura e encerramento', $t10['canal'] === 'Curciol - Oficial' && $t10['fila'] === 'Fila Um' && $t10['fechado_em'] === '2026-11-05 10:00:00' && $t10['aberto_em'] !== '');
    igual('sem quem encerrou: o atendente do ticket', 'Atendente Um', $ta[2]['principal'] ?? null);

    $conv = ApchatHistoricoService::conversa(10, $a);
    igual('conversa: so mensagens de texto', ['Bom dia, preciso de ajuda', 'Claro, pode falar'], array_column($conv, 'body'));
    lanca('conversa de ticket de outro cliente: recusa', fn() => ApchatHistoricoService::conversa(12, $a), 'não é deste cliente');
    lanca('ticket aberto nao aparece', fn() => ApchatHistoricoService::conversa(13, $a), 'não é deste cliente');
    TTransaction::close();

    // telas
    $v = saida(function () use ($a) { $t = new ApchatTicketView(['key' => 10, 'pessoa_id' => $a]); $t->onShow(['key' => 10, 'pessoa_id' => $a]); $t->show(); });
    ok('ticket: informacoes no topo', strpos($v, 'Atendente Dois') !== false && strpos($v, 'Curciol - Oficial') !== false && strpos($v, '05/11/2026 10:00') !== false, substr($v, 0, 500));
    ok('ticket: botao Abrir no APChat', strpos($v, 'Abrir no APChat') !== false && strpos($v, 'atendimento?ticketId=10&quot;, &quot;apchat&quot;') !== false, $v);
    ok('ticket: conversa sem a imagem', strpos($v, 'Claro, pode falar') !== false && strpos($v, 'foto.jpg') === false);

    $h = saida(function () use ($a) { (new HistoricosAtendimentoCliente(['key' => $a]))->show(); });
    ok('historico do cliente: linhas do WhatsApp', strpos($h, 'WhatsApp') !== false && strpos($h, 'Atendente Dois') !== false && strpos($h, '07/11/2026') !== false, substr($h, 0, 300));
    $tl = saida(function () use ($a) { (new AtendimentoTimeLine(['cliente_id' => $a, 'target_container' => 'x']))->show(); });
    ok('timeline do cliente: tickets do WhatsApp', strpos($tl, 'WhatsApp #10') !== false && strpos($tl, 'WhatsApp #11') !== false && strpos($tl, 'WhatsApp #12') === false, substr($tl, 0, 300));

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
    $limpar();
}

fim();
