<?php
// tests/apchat/ApchatTesteBancoTest.php
// APChat de teste (10/11/2026): existe, tem a estrutura e o schema curciol, e o papel da Curciol so enxerga o tenant 21 pelas visoes.
require __DIR__ . '/apchat-teste.php';

lanca('recusa outro banco', fn() => ApchatTeste::pdoAdmin('curciol'), 'so conecta no banco');
$db = ApchatTeste::pdoAdmin();
igual('conectado no APChat de teste', ApchatTeste::BANCO, $db->query('SELECT current_database()')->fetchColumn());
ok('homologacao aponta para o APChat de teste', ApchatTeste::configNoTeste());

$tabelas = array_column($db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")->fetchAll(), 'table_name');
foreach (['Contacts', 'Tickets', 'TicketsAntigo', 'Messages', 'MessagesAntigo', 'Queues', 'Users', 'UsersQueues', 'UserWhatsapps', 'Whatsapps'] as $t)
{
    ok("tabela {$t}", in_array($t, $tabelas, true));
}
$visoes = array_column($db->query("SELECT table_name FROM information_schema.views WHERE table_schema = 'curciol'")->fetchAll(), 'table_name');
foreach (['contatos', 'tickets', 'mensagens', 'filas', 'atendentes'] as $v)
{
    ok("visao curciol.{$v}", in_array($v, $visoes, true));
}
$triggers = array_column($db->query("SELECT tgname FROM pg_trigger WHERE NOT tgisinternal")->fetchAll(), 'tgname');
ok('travas de edicao e exclusao instaladas', in_array('trg_curciol_proteger_contato', $triggers, true) && in_array('trg_curciol_proteger_exclusao', $triggers, true));

$db->beginTransaction();
try
{
    $db->exec("INSERT INTO public.\"Contacts\" (name, number, \"tenantId\", \"isGroup\", \"createdAt\", \"updatedAt\") VALUES ('TESTE T21', '5500000000021', 21, false, now(), now()), ('TESTE T99', '5500000000099', 99, false, now(), now())");
    $db->exec('SET LOCAL ROLE ' . ApchatTeste::PAPEL);
    $vistos = array_column($db->query("SELECT number FROM curciol.contatos WHERE number IN ('5500000000021', '5500000000099')")->fetchAll(), 'number');
    igual('papel da Curciol so ve o tenant 21', ['5500000000021'], $vistos);
    lanca('papel da Curciol nao le a tabela do APChat', fn() => $db->query('SELECT count(*) FROM public."Contacts"')->fetchColumn(), 'permission denied');
}
finally
{
    $db->rollBack();
}

fim();
