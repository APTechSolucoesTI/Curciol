<?php
// tests/apchat/ApchatBancoVisoesTest.php
// Leituras do ApchatBancoService contra o APChat de TESTE (estrutura real): datas do APChat em milissegundos ("closedAt",
// "lastMessageAt") chegam como data e hora de Sao Paulo; tickets abertos e fechados; acessos do atendente. Grava com marca e apaga.
require __DIR__ . '/apchat-teste.php';

$db = ApchatTeste::pdoAdmin();
$limpar = function () use ($db) {
    $db->exec("DELETE FROM public.\"Messages\" WHERE id LIKE 'vis-%'");
    $db->exec('DELETE FROM public."Tickets" WHERE id BETWEEN 990100 AND 990109');
    $db->exec('DELETE FROM curciol.log WHERE contato_id = 990100');
    $db->exec('DELETE FROM public."Contacts" WHERE id = 990100');
    $db->exec('DELETE FROM public."ContactWallets" WHERE "contactId" = 990100');
    $db->exec('DELETE FROM public."PrivateMessage" WHERE id = 990101');
};
$limpar();
try
{
    $ms = function (string $quando) { return (int) (strtotime($quando) * 1000); };
    $db->exec("INSERT INTO public.\"Contacts\" (id, name, number, \"tenantId\", \"isGroup\", \"createdAt\", \"updatedAt\") VALUES (990100, 'VISOES TESTE', '5519900099100', 21, false, now(), now())");
    $st = $db->prepare('INSERT INTO public."Tickets" (id, status, "contactId", "userId", "queueId", "whatsappId", "tenantId", "createdAt", "updatedAt", "lastMessageAt", "closedAt", "closedByUserId")
                        VALUES (?, ?, 990100, 9001, 9101, 112, 21, ?, ?, ?, ?, ?)');
    $st->execute([990100, 'closed', '2026-11-07 14:00:00-03', '2026-11-07 15:00:00-03', $ms('2026-11-07 14:58:00 America/Sao_Paulo'), $ms('2026-11-07 15:00:00 America/Sao_Paulo'), 9002]);
    // fechado pelo isClosed do envio da API: o APChat nao preenche o closedAt (tickets 2967 e 2971 do app1, 10/11/2026)
    $st->execute([990102, 'closed', '2026-11-08 09:00:00-03', '2026-11-08 09:00:05-03', null, null, null]);
    $st->execute([990101, 'pending', date('Y-m-d H:i:sP', strtotime('-10 minutes')), date('Y-m-d H:i:sP'), $ms('-5 minutes'), null, null]);
    $db->exec("INSERT INTO public.\"Messages\" (id, body, \"mediaType\", \"fromMe\", \"ticketId\", \"contactId\", \"userId\", \"tenantId\", ack, \"isDeleted\", \"createdAt\", \"updatedAt\") VALUES
        ('vis-1', 'Oi', 'chat', false, 990100, 990100, NULL, 21, 3, false, '2026-11-07 14:00-03', now()),
        ('vis-2', 'Resposta', 'chat', true, 990100, 990100, 9001, 21, 3, false, '2026-11-07 14:10-03', now()),
        ('vis-3', 'Preciso de ajuda', 'chat', false, 990101, 990100, NULL, 21, 0, false, now() - interval '5 minutes', now())");

    ApchatTeste::ligarServico();
    $f = ApchatBancoService::ticketsFechadosDosNumeros(['5519900099100']);
    igual('so os fechados, do encerramento mais recente', [990102, 990100], array_map('intval', array_column($f, 'id')));
    igual('fechado sem closedAt: encerramento = ultima atualizacao do ticket', '2026-11-08 09:00:05', $f[0]['fechado_em'] ?? null);
    $f = array_values(array_filter($f, function ($t) { return (int) $t['id'] === 990100; }));
    igual('encerramento em data e hora de Sao Paulo (closedAt em milissegundos)', '2026-11-07 15:00:00', $f[0]['fechado_em'] ?? null);
    igual('quem encerrou', 'TESTE - Atendente Dois', $f[0]['fechou'] ?? null);
    ok('tem mensagem do cliente', ($f[0]['do_cliente'] ?? null) === true, json_encode($f[0]['do_cliente'] ?? null));
    igual('quem escreveu', 'TESTE - Atendente Um', $f[0]['atendentes'] ?? null);
    $abertos = array_values(array_filter(ApchatBancoService::ticketsAbertosComUltima(), function ($t) { return (int) $t['id'] === 990101; }));
    ok('aberto: ultima do cliente, com texto e hora', ($abertos[0]['ultima_nossa'] ?? true) === false && ($abertos[0]['ultima_texto'] ?? '') === 'Preciso de ajuda'
        && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', (string) ($abertos[0]['ultima_em'] ?? '')) === 1, json_encode($abertos));
    igual('aberto: nome do atendente do ticket', 'TESTE - Atendente Um', $abertos[0]['atendente'] ?? null);
    igual('aberto: mensagens do cliente sem resposta', 1, (int) ($abertos[0]['nao_respondidas'] ?? 0));
    // impressao digital dos abertos/pendentes: 32 hex; muda quando o ticket e pego/transferido (atendente) e volta igual
    $i1 = ApchatBancoService::impressaoTickets();
    $db->exec('UPDATE public."Tickets" SET "userId" = 9002 WHERE id = 990101');
    $i2 = ApchatBancoService::impressaoTickets();
    $db->exec('UPDATE public."Tickets" SET "userId" = 9001 WHERE id = 990101');
    ok('impressao digital: muda ao transferir e volta ao desfazer', preg_match('/^[0-9a-f]{32}$/', $i1) === 1 && $i1 !== $i2 && $i1 === ApchatBancoService::impressaoTickets(), json_encode([$i1, $i2]));
    $db->exec("UPDATE public.\"Tickets\" SET status = 'closed' WHERE id = 990101");
    ok('impressao digital: muda ao encerrar', ApchatBancoService::impressaoTickets() !== $i1);
    $db->exec("UPDATE public.\"Tickets\" SET status = 'pending' WHERE id = 990101");
    ok('aberto: lastMessageAt tambem em data', preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', (string) ($abertos[0]['lastMessageAt'] ?? '')) === 1, json_encode($abertos));
    $ac = ApchatBancoService::acessos(9001);
    ok('acessos do atendente', in_array(112, $ac['canais'], true) && in_array(9101, $ac['filas'], true), json_encode($ac));
    igual('conversa do ticket', ['Oi', 'Resposta'], array_column(ApchatBancoService::mensagensDoTicket(990100, false), 'body'));

    // 13/11/2026: todas as leituras pelas funcoes (2026-11-13-curciol-funcoes.sql)
    $db->exec('INSERT INTO public."ContactWallets" ("walletId", "contactId", "tenantId") VALUES (9003, 990100, 21)');
    $db->exec("INSERT INTO public.\"PrivateMessage\" (id, text, read, \"mediaType\", \"senderId\", \"receiverId\", \"tenantId\", \"createdAt\", \"updatedAt\")
               VALUES (990101, 'Bom dia, colega', false, 'chat', 9002, 9001, 21, now(), now())");
    igual('contatos por numero', 'VISOES TESTE', ApchatBancoService::contatosPorNumeros(['5519900099100'])['5519900099100']['name'] ?? null);
    igual('contato por id', 'VISOES TESTE', ApchatBancoService::contato(990100)['name'] ?? null);
    ok('contato que nao e da Curciol', ApchatBancoService::contato(999999999) === null);
    $conv = ApchatBancoService::conversa(990100);
    igual('conversa do contato (todos os tickets, da mais antiga)', ['Oi', 'Resposta', 'Preciso de ajuda'], array_column($conv, 'body'));
    ok('conversa do contato: fila e situacao do ticket', ($conv[0]['ticket_status'] ?? '') === 'closed' && ($conv[0]['fila'] ?? '') !== '', json_encode($conv[0] ?? null));
    igual('enviadas para o numero no intervalo', ['vis-2'], array_column(ApchatBancoService::mensagensEnviadasPara(['5519900099100'], '2026-11-07 00:00:00', '2026-11-08 00:00:00'), 'id'));
    igual('conversa depois de um momento (estritamente depois)', ['vis-2', 'vis-3'], array_column(ApchatBancoService::conversaDepois(['5519900099100'], '2026-11-07 14:00:00'), 'id'));
    ok('contatos fora do espelho', in_array(990100, array_map('intval', array_column(ApchatBancoService::contatosForaDoEspelho(1000), 'id')), true));
    [$linhas, $total] = ApchatBancoService::listar('SEM_VINCULO', ['ids' => [], 'numeros' => [], 'ignorados' => []], ['numero' => '5519900099100'], 0, 10);
    ok('contatos sem vinculo: o contato com o ultimo ticket', $total === 1 && (int) ($linhas[0]['ticket_id'] ?? 0) === 990101, json_encode([$total, $linhas]));
    $ac = ApchatBancoService::acessos(9001);
    ok('acessos: perfil do atendente', ($ac['perfil'] ?? null) === 'admin' && $ac['existe'] === true && $ac['ver_todos'] === false && is_array($ac['configs']), json_encode($ac));
    ok('acessos: atendente que nao existe', ApchatBancoService::acessos(999999)['existe'] === false);
    ok('configuracoes da empresa', is_array(ApchatBancoService::configuracoes()));
    igual('carteiras', [990100 => [9003]], ApchatBancoService::carteiras([990100]));
    $chat = array_values(array_filter(ApchatBancoService::chatNaoLidas([9001]), function ($c) { return $c['tipo'] === 'usuario' && (int) $c['conversa_id'] === 9002; }));
    ok('chat interno: nao lida de um colega', (int) ($chat[0]['qtd'] ?? 0) >= 1 && ($chat[0]['ultima'] ?? '') === 'Bom dia, colega', json_encode($chat));
    $f = array_values(array_filter(ApchatBancoService::ticketsFechadosDosNumeros(['5519900099100']), function ($t) { return (int) $t['id'] === 990100; }));
    ok('historico traz o que a visibilidade usa', (int) ($f[0]['queueId'] ?? 0) === 9101 && (int) ($f[0]['whatsappId'] ?? 0) === 112
        && (int) ($f[0]['contactId'] ?? 0) === 990100 && array_key_exists('shared', $f[0]) && array_key_exists('userIdArray', $f[0]), json_encode($f[0] ?? null));
    $ab = array_values(array_filter(ApchatBancoService::ticketsAbertosComUltima(), function ($t) { return (int) $t['id'] === 990101; }));
    ok('balao traz o que a visibilidade usa', array_key_exists('userIdArray', $ab[0] ?? []) && array_key_exists('chatFlowId', $ab[0] ?? []), json_encode($ab[0] ?? null));
}
finally
{
    $limpar();
    ApchatBancoService::desligar(true);
}

fim();
