<?php
// tests/apchat/ApchatContatoNovoTest.php
// Contato novo no APChat (10/11/2026): numero (ou variante com/sem 9) que a Curciol ja tem no espelho nasce com os dados da
// Curciol e trancado; numero desconhecido nasce livre. So tenant 21; erro nunca impede o APChat de gravar. Visoes ampliadas.
// Roda no APChat de teste, dentro de transacao desfeita.
require __DIR__ . '/apchat-teste.php';

$db = ApchatTeste::pdoAdmin();
$novo = function (string $nome, string $numero, int $tenant = 21) use ($db): array {
    $st = $db->prepare('INSERT INTO public."Contacts" (name, number, email, "tenantId", "isGroup", "createdAt", "updatedAt") VALUES (?, ?, ?, ?, false, now(), now()) RETURNING id, name, email, cpf, "firstName"');
    $st->execute([$nome, $numero, 'whatsapp@app.com', $tenant]);
    return $st->fetch();
};
$espelho = function (int $id) use ($db) { $st = $db->prepare('SELECT * FROM curciol.contato WHERE contato_id = ?'); $st->execute([$id]); return $st->fetch(); };
$log = function (int $id) use ($db) { $st = $db->prepare('SELECT acao, contato_novo_id FROM curciol.log WHERE contato_id = ? ORDER BY id DESC LIMIT 1'); $st->execute([$id]); return $st->fetch() ?: []; };

$db->beginTransaction();
try
{
    // contato da Curciol ja no APChat (como a sincronizacao deixa)
    $db->exec("INSERT INTO public.\"Contacts\" (id, name, number, \"tenantId\", \"isGroup\", \"createdAt\", \"updatedAt\") VALUES (880001, 'JOSE DA SILVA', '5519981234567', 21, false, now(), now())");
    $db->exec("INSERT INTO curciol.contato (contato_id, nome, numero, email, cpf, primeiro_nome, ultimo_nome, ligado) VALUES (880001, 'JOSE DA SILVA', '5519981234567', 'jose@curciol.test', '12345678909', 'JOSE', 'DA SILVA', true)");

    // mesmo numero escrito sem o 9 (variante): segundo contato do mesmo cliente
    $v = $novo('Zé do WhatsApp', '551981234567');
    igual('variante sem 9: nasce com o nome da Curciol', 'JOSE DA SILVA', $v['name']);
    igual('variante sem 9: e-mail da Curciol', 'jose@curciol.test', $v['email']);
    igual('variante sem 9: CPF da Curciol', '12345678909', $v['cpf']);
    $e = $espelho((int) $v['id']);
    ok('variante sem 9: entra no espelho, ligada, com o proprio numero', $e && $e['ligado'] && $e['numero'] === '551981234567' && $e['nome'] === 'JOSE DA SILVA', json_encode($e));
    igual('variante sem 9: log NOVO_LIGADO apontando o contato achado', ['acao' => 'NOVO_LIGADO', 'contato_novo_id' => 880001], $log((int) $v['id']));

    // trancado: o APChat tenta renomear e a trava devolve o nome da Curciol
    $db->exec("UPDATE public.\"Contacts\" SET name = 'Outro nome' WHERE id = {$v['id']}");
    igual('variante trancada pela trava de edicao', 'JOSE DA SILVA', $db->query("SELECT name FROM public.\"Contacts\" WHERE id = {$v['id']}")->fetchColumn());

    // variante com o 9 a partir de um espelho sem o 9
    $db->exec("INSERT INTO curciol.contato (contato_id, nome, numero, ligado) VALUES (880002, 'MARIA SEM NOVE', '551933334444', true)");
    $c9 = $novo('Maria', '5519933334444');
    igual('variante com 9: nasce com o nome da Curciol', 'MARIA SEM NOVE', $c9['name']);

    // telefone fixo (comeca com 2 a 5) nao ganha o 9: nao e variante de um celular com o mesmo final
    $db->exec("INSERT INTO curciol.contato (contato_id, nome, numero, ligado) VALUES (880004, 'CELULAR 9 + FIXO', '5519933221100', true)");
    $fixo = $novo('Fixo', '551933221100');
    igual('fixo com o mesmo final de um celular: nao liga', 'Fixo', $fixo['name']);

    // desconhecido: livre, fora do espelho
    $d = $novo('Fulano WhatsApp', '5511900001111');
    igual('desconhecido: fica com o nome do WhatsApp', 'Fulano WhatsApp', $d['name']);
    ok('desconhecido: fora do espelho', $espelho((int) $d['id']) === false);
    igual('desconhecido: log NOVO_SEM_VINCULO', 'NOVO_SEM_VINCULO', $log((int) $d['id'])['acao'] ?? null);

    // ex-contato (desligado) nao serve de fonte
    $db->exec("INSERT INTO curciol.contato (contato_id, nome, numero, ligado) VALUES (880003, 'EX CONTATO', '5519955556666', false)");
    $x = $novo('Novo dono', '5519955556666');
    igual('espelho desligado nao liga', 'Novo dono', $x['name']);

    // outro tenant: nada
    $o = $novo('Outro tenant', '5519981234567', 99);
    igual('tenant 99: nada muda', 'Outro tenant', $o['name']);
    ok('tenant 99: fora do espelho e sem log', $espelho((int) $o['id']) === false && $log((int) $o['id']) === []);

    // sem numero: nada
    $st = $db->query("INSERT INTO public.\"Contacts\" (name, \"tenantId\", \"isGroup\", \"createdAt\", \"updatedAt\") VALUES ('Sem numero', 21, false, now(), now()) RETURNING name");
    igual('sem numero: grava normalmente', 'Sem numero', $st->fetchColumn());

    // ---------- visoes ampliadas ----------
    $colunas = function (string $visao) use ($db) {
        $st = $db->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = 'curciol' AND table_name = ?");
        $st->execute([$visao]);
        return array_column($st->fetchAll(), 'column_name');
    };
    ok('tickets: canal e quem fechou', !array_diff(['whatsappId', 'closedByUserId'], $colunas('tickets')));
    ok('mensagens: tiques, situacao, id e fila', !array_diff(['ack', 'status', 'messageId', 'queueId'], $colunas('mensagens')));
    ok('atendentes: e-mail e inativo', !array_diff(['email', 'inactive'], $colunas('atendentes')));
    igual('canais', ['id', 'name', 'type', 'isDeleted'], $colunas('canais'));
    igual('filas_atendentes', ['userId', 'queueId'], $colunas('filas_atendentes'));
    igual('canais_atendentes', ['userId', 'whatsappId', 'isActive'], $colunas('canais_atendentes'));

    $db->exec('SET LOCAL ROLE ' . ApchatTeste::PAPEL);
    // o APChat de teste tambem tem os canais reais copiados como tenant 21 (10/11/2026): confere os de teste e o de outra empresa
    $canais = array_map('intval', array_column($db->query('SELECT id FROM curciol.canais ORDER BY id')->fetchAll(), 'id'));
    ok('papel da Curciol: so canais do tenant 21', in_array(112, $canais, true) && in_array(113, $canais, true) && !in_array(199, $canais, true), json_encode($canais));
    ok('papel da Curciol: acessos so de atendentes do tenant 21', (int) $db->query('SELECT count(*) FROM curciol.filas_atendentes WHERE "userId" = 9099')->fetchColumn() === 0
        && (int) $db->query('SELECT count(*) FROM curciol.canais_atendentes WHERE "userId" IN (9001, 9002, 9003)')->fetchColumn() === 3);
    igual('papel da Curciol: e-mail dos atendentes', 'atendente.um@teste.local', $db->query('SELECT email FROM curciol.atendentes WHERE id = 9001')->fetchColumn());
}
finally
{
    $db->rollBack();
}

// erro dentro da regra nao impede gravar: espelho indisponivel para a funcao
$db->beginTransaction();
try
{
    $db->exec('ALTER TABLE curciol.contato RENAME TO contato_fora');
    $st = $db->query("INSERT INTO public.\"Contacts\" (name, number, \"tenantId\", \"isGroup\", \"createdAt\", \"updatedAt\") VALUES ('Com erro', '5519900002222', 21, false, now(), now()) RETURNING name");
    igual('erro na regra: o APChat grava mesmo assim', 'Com erro', $st->fetchColumn());
}
catch (Throwable $e)
{
    ok('erro na regra: o APChat grava mesmo assim', false, $e->getMessage());
}
finally
{
    $db->rollBack();
}

fim();
