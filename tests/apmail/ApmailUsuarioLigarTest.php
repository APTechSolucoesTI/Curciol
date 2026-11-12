<?php
// tests/apmail/ApmailUsuarioLigarTest.php
// Usuarios das integracoes (12/11/2026): ligar automaticamente no APMail testando, na rota de notificacoes, o e-mail escolhido, o do
// cadastro e o login. Primeiro reconhecido fica gravado (S); nenhum = N. 429 para tudo sem gravar errado. APMail falso.
require __DIR__ . '/../bootstrap.php';

function qu(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function xu(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

$us = qu("SELECT id, lower(trim(email)) email FROM system_users WHERE active = 'Y' AND coalesce(trim(email), '') LIKE '%@%' ORDER BY id LIMIT 3");
$ids = array_map('intval', array_column($us, 'id'));
$lista = implode(',', $ids);
$backup = qu("SELECT * FROM apmail_usuario WHERE system_user_id IN ({$lista})");
$reconhecidos = [];
ApmailApiService::usarTransporte(function (string $url) use (&$reconhecidos) {
    parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
    if (($q['email'] ?? '') === 'limite@teste.com') { return ['status' => 429, 'corpo' => '{"error":{"code":"rate_limited"}}', 'erro' => '', 'retry_after' => 30]; }
    if (in_array($q['email'] ?? '', $reconhecidos, true))
    {
        return ['status' => 200, 'corpo' => json_encode(['success' => true, 'data' => ['pending' => 0, 'unread' => 0, 'mailboxes' => []]]), 'erro' => '', 'retry_after' => null];
    }
    return ['status' => 404, 'corpo' => '{"error":{"code":"notification_scope_unavailable"}}', 'erro' => '', 'retry_after' => null];
});

try
{
    ok('ha 3 usuarios ativos com e-mail para o teste', count($ids) === 3);
    xu("DELETE FROM apmail_usuario WHERE system_user_id IN ({$lista})");
    xu("INSERT INTO apmail_usuario (system_user_id, email) VALUES ({$ids[2]}, 'escolhido@teste.com')");
    $reconhecidos = [$us[0]['email'], 'escolhido@teste.com'];

    TTransaction::open('escritorio');
    $r = ApmailUsuarioService::ligarAutomatico($ids);
    TTransaction::close();
    igual('ligados e nao reconhecidos', ['ligados' => 2, 'nao_reconhecidos' => 1], $r);
    $g = [];
    foreach (qu("SELECT system_user_id, email, situacao, conferido_em FROM apmail_usuario WHERE system_user_id IN ({$lista})") as $l) { $g[(int) $l['system_user_id']] = $l; }
    igual('1o: reconhecido pelo e-mail do cadastro (nao precisa guardar o e-mail)', ['S', null], [$g[$ids[0]]['situacao'] ?? null, array_key_exists('email', $g[$ids[0]] ?? []) ? $g[$ids[0]]['email'] : 'sem linha']);
    igual('2o: nao reconhecido', 'N', $g[$ids[1]]['situacao'] ?? null);
    igual('3o: e-mail ja escolhido e reconhecido nao e trocado', ['S', 'escolhido@teste.com'], [$g[$ids[2]]['situacao'] ?? null, $g[$ids[2]]['email'] ?? null]);
    ok('data da conferencia gravada', !empty($g[$ids[0]]['conferido_em']));

    // 429: para e nao grava errado
    xu("UPDATE apmail_usuario SET email = 'limite@teste.com', situacao = 'S' WHERE system_user_id = {$ids[0]}");
    TTransaction::open('escritorio');
    lanca('429: para a rotina', fn() => ApmailUsuarioService::ligarAutomatico([$ids[0]]), 'Limite');
    TTransaction::rollback();
    igual('429: situacao anterior mantida', 'S', qu("SELECT situacao FROM apmail_usuario WHERE system_user_id = {$ids[0]}")[0]['situacao'] ?? null);

    // tela: situacao gravada aparece na coluna Verificacao APMail
    $col = ApchatUsuarioList::situacaoGravada('N', '2026-11-12 10:00:00');
    ok('tela: situacao gravada com a data', strpos($col, 'fa-times-circle') !== false && strpos($col, '12/11/2026') !== false, $col);
}
finally
{
    while (TTransaction::get()) { TTransaction::rollback(); }
    xu("DELETE FROM apmail_usuario WHERE system_user_id IN ({$lista})");
    TTransaction::open('escritorio');
    $ins = TTransaction::get()->prepare('INSERT INTO apmail_usuario (id, system_user_id, email, avisos, situacao, conferido_em, atualizado_em) VALUES (?, ?, ?, ?, ?, ?, ?)');
    foreach ($backup as $b) { $ins->execute([$b['id'], $b['system_user_id'], $b['email'], $b['avisos'], $b['situacao'], $b['conferido_em'], $b['atualizado_em']]); }
    TTransaction::close();
    ApmailApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso do APMail.'); });
}

fim();
