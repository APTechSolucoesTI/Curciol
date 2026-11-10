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
    ok('aberto: lastMessageAt tambem em data', preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}/', (string) ($abertos[0]['lastMessageAt'] ?? '')) === 1, json_encode($abertos));
    $ac = ApchatBancoService::acessos(9001);
    ok('acessos do atendente', in_array(112, $ac['canais'], true) && in_array(9101, $ac['filas'], true), json_encode($ac));
    igual('conversa do ticket', ['Oi', 'Resposta'], array_column(ApchatBancoService::mensagensDoTicket(990100, false), 'body'));
}
finally
{
    $limpar();
    ApchatBancoService::desligar(true);
}

fim();
