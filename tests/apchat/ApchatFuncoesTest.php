<?php
// tests/apchat/ApchatFuncoesTest.php
// Leituras da Curciol no banco do APChat por FUNCAO (13/11/2026, 2026-11-13-curciol-funcoes.sql), no APChat de TESTE:
// - cada funcao so devolve o tenant da Curciol e filtra pelos parametros;
// - o papel da Curciol so executa as funcoes (nao le tabela do APChat; PUBLIC nao executa);
// - SIMULACAO DE ATUALIZACAO DO APCHAT: alterar o tipo, remover cada coluna e remover cada tabela que a Curciol usa passa
//   (nada da Curciol prende coluna); coluna/tabela que some vira NULL/vazio e curciol.conferir() acusa;
// - chat interno: nao lidas da conversa direta e da equipe.
// Tudo numa transacao desfeita no fim.
require __DIR__ . '/apchat-teste.php';

$db = ApchatTeste::pdoAdmin();
$papel = ApchatTeste::PAPEL;
$col = function (string $sql) use ($db) { return $db->query($sql)->fetchAll(PDO::FETCH_COLUMN); };

$db->beginTransaction();
try
{
    $db->exec("INSERT INTO public.\"Contacts\" (id, name, number, \"tenantId\", \"isGroup\", \"createdAt\", \"updatedAt\") VALUES
        (990200, 'FUNCOES T21', '5519900099200', 21, false, now(), now()), (990299, 'FUNCOES T99', '5519900099299', 99, false, now(), now())");
    $db->exec("INSERT INTO public.\"Tickets\" (id, status, \"contactId\", \"userId\", \"queueId\", \"whatsappId\", \"tenantId\", shared, \"userIdArray\", \"isGroup\", \"createdAt\", \"updatedAt\") VALUES
        (990200, 'pending', 990200, NULL, 9101, 112, 21, true, '[9003]', false, now(), now()),
        (990201, 'closed', 990200, 9001, 9101, 112, 21, false, NULL, false, now() - interval '1 day', now() - interval '1 day'),
        (990299, 'pending', 990299, NULL, 9199, 199, 99, false, NULL, false, now(), now())");
    $db->exec("INSERT INTO public.\"Messages\" (id, body, \"fromMe\", \"ticketId\", \"contactId\", \"tenantId\", \"isDeleted\", \"createdAt\", \"updatedAt\") VALUES
        ('fn-1', 'Ola', false, 990200, 990200, 21, false, now(), now()), ('fn-9', 'Outra empresa', false, 990299, 990299, 99, false, now(), now())");
    $db->exec("INSERT INTO public.\"ContactWallets\" (\"walletId\", \"contactId\", \"tenantId\") VALUES (9002, 990200, 21), (9002, 990299, 99)");
    $db->exec("INSERT INTO public.\"Tenants\" (id, name, \"nullTickets\", \"showClosedForAll\", \"supervisorAdmin\") VALUES (21, 'Curciol', 'enabled', 'disabled', 'disabled'), (99, 'Outra', 'disabled', 'enabled', 'enabled')
               ON CONFLICT (id) DO UPDATE SET \"nullTickets\" = EXCLUDED.\"nullTickets\", \"showClosedForAll\" = EXCLUDED.\"showClosedForAll\", \"supervisorAdmin\" = EXCLUDED.\"supervisorAdmin\"");
    $db->exec("INSERT INTO public.\"Settings\" (key, value, \"tenantId\") VALUES ('NotViewAssignedTickets', 'enabled', 21), ('NotViewAssignedTickets', 'disabled', 99), ('OutraChave', 'x', 21)");
    $db->exec("INSERT INTO public.\"CustomProfiles\" (id, \"tenantId\", name, \"customPermissions\") VALUES (990, 21, 'Ve tudo', '[\"tickets_view_all\"]'), (991, 21, 'Objeto', '{\"tickets_view_all\": true}')");
    $db->exec('UPDATE public."Users" SET "customProfileId" = 990 WHERE id = 9003');
    $db->exec('UPDATE public."Users" SET "customProfileId" = 991 WHERE id = 9002');

    // chat interno: 9002 -> 9001 (2 nao lidas), 9003 -> 9001 (lida), outro tenant -> 9001 (nao aparece); equipe 990 com 9001 e 9002
    $db->exec("INSERT INTO public.\"PrivateMessage\" (id, text, read, \"mediaType\", \"senderId\", \"receiverId\", \"groupId\", \"tenantId\", \"createdAt\", \"updatedAt\") VALUES
        (990001, 'oi 1', false, 'chat', 9002, 9001, NULL, 21, now() - interval '3 minutes', now()),
        (990002, 'oi 2', false, 'chat', 9002, 9001, NULL, 21, now() - interval '2 minutes', now()),
        (990003, 'ja lida', true, 'chat', 9003, 9001, NULL, 21, now(), now()),
        (990009, 'outro tenant', false, 'chat', 9099, 9001, NULL, 99, now(), now()),
        (990011, 'g1', NULL, 'chat', 9002, NULL, 990, 21, now() - interval '5 minutes', now()),
        (990012, 'g2', NULL, 'image', 9002, NULL, 990, 21, now() - interval '1 minute', now()),
        (990013, 'minha', NULL, 'chat', 9001, NULL, 990, 21, now(), now())");
    $db->exec("INSERT INTO public.\"GroupMessages\" (id, \"group\", \"tenantId\", \"isActive\") VALUES (990, 'Equipe Teste', 21, true)");
    $db->exec("INSERT INTO public.\"UsersPrivateGroups\" (id, \"groupId\", \"userId\") VALUES (99001, 990, 9001), (99002, 990, 9002)");
    $db->exec("INSERT INTO public.\"ReadPrivateMessageGroups\" (\"internalMessageId\", \"userGroupId\") VALUES (990011, 99001)");

    // ---- como o papel da Curciol
    $db->exec("SET LOCAL ROLE {$papel}");
    igual('contatos: so o tenant da Curciol', ['990200'], $col("SELECT id FROM curciol.f_contatos(NULL, ARRAY['5519900099200', '5519900099299'])"));
    igual('contatos por id', ['FUNCOES T21'], $col('SELECT name FROM curciol.f_contatos(ARRAY[990200, 990299])'));
    igual('tickets dos contatos', ['990200', '990201'], $col('SELECT id FROM curciol.f_tickets(NULL, ARRAY[990200, 990299]) ORDER BY id'));
    $abertos = $col('SELECT id FROM curciol.f_tickets(NULL, NULL, true) WHERE id BETWEEN 990200 AND 990299');
    igual('tickets abertos/pendentes: sem o fechado e sem outro tenant', ['990200'], $abertos);
    $t = $db->query('SELECT shared, "userIdArray"::text AS convidados, "isGroup", arquivado FROM curciol.f_tickets(ARRAY[990200])')->fetch();
    ok('ticket traz convite (shared + userIdArray) e grupo', $t && $t['shared'] === true && $t['convidados'] === '[9003]' && $t['isGroup'] === false && $t['arquivado'] === false, json_encode($t));
    igual('mensagens dos tickets: so o tenant da Curciol', ['fn-1'], $col('SELECT id FROM curciol.f_mensagens(ARRAY[990200, 990299])'));
    igual('mensagens por contato', ['fn-1'], $col('SELECT id FROM curciol.f_mensagens(NULL, ARRAY[990200, 990299])'));
    igual('mensagens no intervalo', [], $col("SELECT id FROM curciol.f_mensagens(ARRAY[990200], NULL, now() + interval '1 hour')"));
    igual('carteiras', ['990200:9002'], $col("SELECT \"contactId\" || ':' || \"walletId\" FROM curciol.f_carteiras(ARRAY[990200, 990299])"));
    $cfg = json_decode($db->query('SELECT curciol.f_configuracoes()')->fetchColumn(), true);
    ok('configuracoes da empresa (Tenants + Settings, so as que importam)', ($cfg['nullTickets'] ?? '') === 'enabled' && ($cfg['showClosedForAll'] ?? '') === 'disabled'
        && ($cfg['NotViewAssignedTickets'] ?? '') === 'enabled' && !isset($cfg['OutraChave']), json_encode($cfg));
    $at = $db->query('SELECT id, profile, ver_todos FROM curciol.f_atendentes() WHERE id IN (9001, 9002, 9003, 9099) ORDER BY id')->fetchAll();
    igual('atendentes: so o tenant, com perfil e "ver todos" (lista e objeto)', [[9001, 'admin', false], [9002, 'user', true], [9003, 'user', true]],
          array_map(function ($a) { return [(int) $a['id'], $a['profile'], $a['ver_todos']]; }, $at));
    $chat = $db->query('SELECT usuario, tipo, conversa_id, nome, qtd, ultima, ultima_tipo FROM curciol.f_chat_nao_lidas(ARRAY[9001]) ORDER BY tipo DESC')->fetchAll();
    igual('chat interno: direta (2 nao lidas, ultima) e equipe (g1 lida, a minha nao conta)',
          [[9001, 'usuario', 9002, 'TESTE - Atendente Dois', 2, 'oi 2', 'chat'], [9001, 'equipe', 990, 'Equipe Teste', 1, 'g2', 'image']],
          array_map(function ($c) { return [(int) $c['usuario'], $c['tipo'], (int) $c['conversa_id'], $c['nome'], (int) $c['qtd'], $c['ultima'], $c['ultima_tipo']]; }, $chat));
    igual('chat interno: quem nao tem nada', [], $col('SELECT tipo FROM curciol.f_chat_nao_lidas(ARRAY[9003])'));
    igual('visoes de antes continuam (codigo antigo)', ['990200'], $col("SELECT id FROM curciol.contatos WHERE number = '5519900099200'"));
    igual('conferir(): tudo OK', [], $col("SELECT item || ': ' || erro FROM curciol.conferir()"));
    $db->exec('SAVEPOINT negado');
    lanca('papel nao le tabela do APChat', function () use ($db) { $db->query('SELECT count(*) FROM public."Tickets"'); }, 'permission denied');
    $db->exec('ROLLBACK TO SAVEPOINT negado');
    $db->exec('RESET ROLE');

    $fn = $col("SELECT p.oid::regprocedure::text FROM pg_proc p JOIN pg_namespace n ON n.oid = p.pronamespace WHERE n.nspname = 'curciol'
                 AND has_function_privilege('public', p.oid, 'EXECUTE')");
    igual('nenhuma funcao da Curciol aberta para PUBLIC', [], $fn);
    igual('dependencias(): nada da Curciol prende coluna do APChat', [], $col("SELECT objeto || ' -> ' || prende FROM curciol.dependencias()"));

    // ---- simulacao de atualizacao do APChat: cada coluna e cada tabela que a Curciol toca
    $tabelas = ['Contacts', 'Tickets', 'TicketsAntigo', 'Messages', 'MessagesAntigo', 'Users', 'Queues', 'Whatsapps', 'UsersQueues', 'UserWhatsapps',
                'ContactWallets', 'Settings', 'Tenants', 'CustomProfiles', 'PrivateMessage', 'GroupMessages', 'UsersPrivateGroups', 'ReadPrivateMessageGroups'];
    $falhas = [];
    $passos = 0;
    $tenta = function (string $sql) use ($db, &$falhas, &$passos) {
        $db->exec('SAVEPOINT sim');
        try { $db->exec($sql); $passos++; }
        catch (Throwable $e) { $falhas[] = $sql . ' => ' . $e->getMessage(); }
        $db->exec('ROLLBACK TO SAVEPOINT sim');
    };
    foreach ($tabelas as $tab)
    {
        $colunas = $db->query("SELECT attname, format_type(atttypid, atttypmod) AS tipo FROM pg_attribute
                                WHERE attrelid = 'public.\"{$tab}\"'::regclass AND attnum > 0 AND NOT attisdropped")->fetchAll();
        foreach ($colunas as $c)
        {
            $tenta(sprintf('ALTER TABLE public."%s" ALTER COLUMN "%s" TYPE %s', $tab, $c['attname'], $c['tipo']));   // changeColumn
            $tenta(sprintf('ALTER TABLE public."%s" DROP COLUMN "%s"', $tab, $c['attname']));                         // removeColumn
        }
        $tenta(sprintf('ALTER TABLE public."%s" RENAME TO "%s_novo"', $tab, $tab));                                   // renameTable
        $tenta(sprintf('DROP TABLE public."%s"', $tab));                                                             // dropTable
    }
    igual('atualizacao simulada: nenhuma alteracao do APChat barrada pela Curciol', [], $falhas);
    ok('atualizacao simulada: centenas de alteracoes testadas', $passos > 500, (string) $passos);

    // controle: a simulacao pega uma visao antiga (prova que o teste detecta o problema)
    $db->exec('SAVEPOINT ctl');
    $db->exec('CREATE VIEW curciol.zz_visao_antiga AS SELECT status FROM public."Tickets"');
    ok('controle: visao antiga aparece em dependencias()', count($col('SELECT objeto FROM curciol.dependencias()')) > 0);
    lanca('controle: visao antiga barra o changeColumn', function () use ($db) { $db->exec('ALTER TABLE public."Tickets" ALTER COLUMN status TYPE varchar(255)'); }, 'used by a view');
    $db->exec('ROLLBACK TO SAVEPOINT ctl');

    // coluna que some: a leitura continua (NULL) e a conferencia acusa
    $db->exec('SAVEPOINT sumiu');
    $db->exec('ALTER TABLE public."Tickets" DROP COLUMN shared');
    $db->exec('ALTER TABLE public."Contacts" DROP COLUMN cidade');
    $db->exec("SET LOCAL ROLE {$papel}");
    $t = $db->query('SELECT id, shared FROM curciol.f_tickets(ARRAY[990200])')->fetch();
    ok('coluna removida: o ticket continua lido, com NULL', $t && (int) $t['id'] === 990200 && $t['shared'] === null, json_encode($t));
    igual('coluna removida: conferir() acusa', ['coluna: public."Tickets"."shared" nao existe'], $col("SELECT item || ': ' || erro FROM curciol.conferir()"));
    $db->exec('RESET ROLE');
    $db->exec("UPDATE public.\"Contacts\" SET name = 'MUDOU' WHERE id = 990200");
    igual('coluna da trava removida: o APChat continua gravando o contato', 'MUDOU', $db->query('SELECT name FROM public."Contacts" WHERE id = 990200')->fetchColumn());
    $db->exec('ROLLBACK TO SAVEPOINT sumiu');

    // tabela do chat que some (versao sem chat): vazio e a conferencia acusa
    $db->exec('SAVEPOINT chat');
    $db->exec('DROP TABLE public."PrivateMessage"');
    igual('sem a tabela do chat: nada, sem erro', [], $col('SELECT tipo FROM curciol.f_chat_nao_lidas(ARRAY[9001])'));
    igual('sem a tabela do chat: conferir() acusa', ['tabela: public."PrivateMessage" nao existe'], $col("SELECT item || ': ' || erro FROM curciol.conferir()"));
    $db->exec('ROLLBACK TO SAVEPOINT chat');

    // trava do outro tenant: a funcao nao mexe
    $db->exec("UPDATE public.\"Contacts\" SET name = 'OUTRO MUDOU' WHERE id = 990299");
    igual('trava: outro tenant grava livre', 'OUTRO MUDOU', $db->query('SELECT name FROM public."Contacts" WHERE id = 990299')->fetchColumn());
}
finally
{
    $db->rollBack();
}

fim();
