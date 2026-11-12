<?php
// tests/apmail/ApmailTest.php
// APMail na Curciol (10/11/2026): envelope do topo com as caixas do usuario (pendentes = contador; nao lidas em cada caixa),
// e-mail do APMail por usuario (o do cadastro ou outro), avisos liga/desliga, endereco e token no escritorio. API so le.
// Cache de 30 s por e-mail; 404 = e-mail fora da integracao; 401/403/5xx = integracao indisponivel (60 s sem tentar);
// 429 = espera o Retry-After. API falsa; dados gravados dentro de transacao desfeita.
require __DIR__ . '/../bootstrap.php';
LinkApp::forcar('apmail', false); // links pelo app instalado tem teste proprio (LinkAppTest)
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }

$chamadas = [];
$respostas = [];
ApmailApiService::usarTransporte(function (string $url, string $token) use (&$chamadas, &$respostas) {
    $chamadas[] = ['url' => $url, 'token' => $token];
    parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
    return $respostas[$q['email'] ?? ''] ?? ['status' => 404, 'corpo' => '{"error":{"code":"notification_scope_unavailable","message":"Consulta indisponível para o escopo desta integração."}}', 'erro' => '', 'retry_after' => null];
});
$ok = function (string $email, array $caixas) {
    $p = array_sum(array_column($caixas, 'pending')); $u = array_sum(array_column($caixas, 'unread'));
    return ['status' => 200, 'retry_after' => null, 'erro' => '', 'corpo' => json_encode(['success' => true, 'data' => ['email' => $email, 'unread' => $u, 'pending' => $p,
            'totals' => ['unread' => $u, 'pending' => $p], 'hasNotifications' => ($u + $p) > 0, 'mailboxes' => $caixas]])];
};

TTransaction::open('escritorio');
try
{
    $c = TTransaction::get();
    $us = $c->query("SELECT id, email FROM system_users WHERE active = 'Y' ORDER BY id LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
    [$u1, $u2] = [(int) $us[0]['id'], (int) $us[1]['id']];
    $c->exec("UPDATE system_users SET email = 'cadastro.um@teste.local' WHERE id = {$u1}");
    $c->exec("UPDATE system_users SET email = 'cadastro.dois@teste.local' WHERE id = {$u2}");
    $c->exec('DELETE FROM apmail_usuario');
    $c->exec("UPDATE escritorio SET url_apmail = NULL, token_apmail = NULL");

    // ---------- e-mail do APMail por usuario ----------
    igual('sem configuracao: o e-mail do cadastro', 'cadastro.um@teste.local', ApmailUsuarioService::email($u1));
    ok('sem configuracao: avisos ligados', ApmailUsuarioService::avisos($u1));
    ApmailUsuarioService::salvar($u2, ' Outro.Email@Empresa.com.br ', true);
    igual('e-mail diferente do cadastro: o informado (minusculo, sem espacos)', 'outro.email@empresa.com.br', ApmailUsuarioService::email($u2));
    ApmailUsuarioService::salvar($u2, 'cadastro.dois@teste.local', false);
    igual('igual ao do cadastro: nao guarda (segue o cadastro)', null, $c->query("SELECT email FROM apmail_usuario WHERE system_user_id = {$u2}")->fetchColumn() ?: null);
    ok('avisos desligados', !ApmailUsuarioService::avisos($u2));
    lanca('e-mail invalido', function () use ($u1) { ApmailUsuarioService::salvar($u1, 'nao-e-email', true); }, 'e-mail');

    // ---------- sem integracao ----------
    ApmailEnvelopeService::limparCache();
    igual('sem token no escritorio: sem integracao (e nao chama a API)', ['sem_integracao', 0], [ApmailEnvelopeService::caixas($u1)['situacao'], count($chamadas)]);

    $c->exec("UPDATE escritorio SET token_apmail = 'token-teste'");
    $respostas['cadastro.um@teste.local'] = $ok('cadastro.um@teste.local', [
        ['id' => 'a1', 'name' => 'Comercial', 'email' => 'comercial@x.com', 'unread' => 0, 'pending' => 0, 'url' => '/mail/a1?assigned=any'],
        ['id' => 'a2', 'name' => 'Financeiro', 'email' => 'financeiro@x.com', 'unread' => 3, 'pending' => 2, 'url' => '/mail/a2?assigned=any'],
        ['id' => 'a3', 'name' => 'Pessoal', 'email' => 'eu@x.com', 'unread' => 5, 'pending' => 1, 'url' => '/mail/a3?assigned=any'],
    ]);

    // ---------- API ----------
    $agora = time();
    ApmailEnvelopeService::usarRelogio($agora);
    $r = ApmailEnvelopeService::caixas($u1);
    igual('consulta: endereco padrao, rota e e-mail codificado', 'https://app.apmail.com.br/api/integrations/notifications?email=cadastro.um%40teste.local', $chamadas[0]['url'] ?? null);
    igual('consulta: token do escritorio', 'token-teste', $chamadas[0]['token'] ?? null);
    igual('situacao ok', 'ok', $r['situacao']);
    igual('contador = pendentes (nao soma com nao lidas)', [3, 8], [$r['pendentes'], $r['nao_lidas']]);
    igual('caixas: mais pendentes primeiro, zeradas por ultimo', ['Financeiro', 'Pessoal', 'Comercial'], array_column($r['caixas'], 'nome'));
    igual('caixa: endereco completo no APMail', 'https://app.apmail.com.br/mail/a2?assigned=any', $r['caixas'][0]['url']);
    ok('caixa: pendentes, nao lidas e e-mail', $r['caixas'][0]['pendentes'] === 2 && $r['caixas'][0]['nao_lidas'] === 3 && $r['caixas'][0]['email'] === 'financeiro@x.com', json_encode($r['caixas'][0]));

    // ---------- cache ----------
    ApmailEnvelopeService::caixas($u1);
    ApmailEnvelopeService::usarRelogio($agora + 8);
    ApmailEnvelopeService::caixas($u1);
    igual('cache de 10 s por e-mail: uma consulta so', 1, count($chamadas));
    ApmailEnvelopeService::usarRelogio($agora += 11);
    ApmailEnvelopeService::caixas($u1);
    igual('depois de 10 s: consulta de novo', 2, count($chamadas));

    // voltar para a aba da Curciol (fresco): busca direto no APMail, mas nunca mais de uma vez a cada 3 s por e-mail
    ApmailEnvelopeService::usarRelogio($agora + 2);
    ApmailEnvelopeService::caixas($u1, true);
    igual('fresco com menos de 3 s da ultima consulta: usa o que tem', 2, count($chamadas));
    $respostas['cadastro.um@teste.local'] = $ok('cadastro.um@teste.local', [['id' => 'a2', 'name' => 'Financeiro', 'email' => 'financeiro@x.com', 'unread' => 0, 'pending' => 1, 'url' => '/mail/a2?assigned=any']]);
    ApmailEnvelopeService::usarRelogio($agora + 4);
    $rv = ApmailEnvelopeService::caixas($u1, true);
    ok('fresco: consulta na hora e traz o que mudou no APMail (e-mail lido/concluido)', count($chamadas) === 3 && $rv['pendentes'] === 1 && $rv['nao_lidas'] === 0, json_encode([count($chamadas), $rv['pendentes'], $rv['nao_lidas']]));
    ApmailEnvelopeService::caixas($u1);
    igual('depois do fresco: os outros pedidos usam a resposta nova', 3, count($chamadas));
    $respostas['cadastro.um@teste.local'] = $ok('cadastro.um@teste.local', [
        ['id' => 'a1', 'name' => 'Comercial', 'email' => 'comercial@x.com', 'unread' => 0, 'pending' => 0, 'url' => '/mail/a1?assigned=any'],
        ['id' => 'a2', 'name' => 'Financeiro', 'email' => 'financeiro@x.com', 'unread' => 3, 'pending' => 2, 'url' => '/mail/a2?assigned=any'],
        ['id' => 'a3', 'name' => 'Pessoal', 'email' => 'eu@x.com', 'unread' => 5, 'pending' => 1, 'url' => '/mail/a3?assigned=any'],
    ]);
    ApmailEnvelopeService::usarRelogio($agora += 15);
    ApmailEnvelopeService::caixas($u1);
    $chamadas = array_slice($chamadas, 0, 2);

    // ---------- avisos desligados / e-mail fora da integracao ----------
    igual('avisos desligados: nada (e nao chama a API)', ['desligado', 2], [ApmailEnvelopeService::caixas($u2)['situacao'], count($chamadas)]);
    ApmailUsuarioService::salvar($u2, 'fora@teste.local', true);
    $rf = ApmailEnvelopeService::caixas($u2);
    igual('404: e-mail fora da integracao', 'nao_liberado', $rf['situacao']);
    ApmailEnvelopeService::usarRelogio($agora += 40);
    ApmailEnvelopeService::caixas($u2);
    igual('404 fica guardado por 5 min (nao consulta a cada 30 s)', 3, count($chamadas));

    // ---------- 429: espera o Retry-After ----------
    ApmailEnvelopeService::usarRelogio($agora += 31);
    $respostas['cadastro.um@teste.local'] = ['status' => 429, 'corpo' => '{"error":{"code":"rate_limited","message":"Limite"}}', 'erro' => '', 'retry_after' => 120];
    $rl = ApmailEnvelopeService::caixas($u1);
    ok('429: mostra o que ja tinha (sem erro)', $rl['situacao'] === 'ok' && $rl['pendentes'] === 3, json_encode($rl));
    $n = count($chamadas);
    ApmailEnvelopeService::usarRelogio($agora += 60);
    ApmailEnvelopeService::caixas($u1);
    igual('429: nao consulta antes do Retry-After', $n, count($chamadas));
    ApmailEnvelopeService::usarRelogio($agora += 61);
    $respostas['cadastro.um@teste.local'] = ['status' => 401, 'corpo' => '{"error":{"code":"invalid_token","message":"Token"}}', 'erro' => '', 'retry_after' => null];
    $ri = ApmailEnvelopeService::caixas($u1);
    igual('401: integracao indisponivel', 'indisponivel', $ri['situacao']);
    $n = count($chamadas);
    ApmailEnvelopeService::usarRelogio($agora += 31);
    ApmailEnvelopeService::caixas($u1);
    igual('indisponivel: 60 s sem tentar', $n, count($chamadas));
    lanca('teste da tela: mensagem do APMail', function () { ApmailApiService::testar('https://app.apmail.com.br', 'token-teste', 'cadastro.um@teste.local'); }, 'token');

}
finally
{
    TTransaction::rollback();
    ApmailApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso do APMail.'); });
    ApmailEnvelopeService::usarRelogio(null);
    ApmailEnvelopeService::limparCache();
}

// ---------- telas (abrem a propria transacao: dados gravados de verdade e devolvidos no fim) ----------
TTransaction::open('escritorio');
$c = TTransaction::get();
$us = $c->query("SELECT id, email FROM system_users WHERE active = 'Y' ORDER BY id LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
[$u1, $u2] = [(int) $us[0]['id'], (int) $us[1]['id']];
$antes = ['usuarios' => $us, 'escritorio' => $c->query('SELECT id, url_apmail, token_apmail FROM escritorio ORDER BY id')->fetchAll(PDO::FETCH_ASSOC),
          'apmail' => $c->query("SELECT system_user_id, email, avisos FROM apmail_usuario WHERE system_user_id IN ({$u1}, {$u2})")->fetchAll(PDO::FETCH_ASSOC)];
$c->exec("UPDATE system_users SET email = 'cadastro.um@teste.local' WHERE id = {$u1}");
$c->exec("UPDATE escritorio SET url_apmail = NULL, token_apmail = 'token-teste'");
$c->exec("DELETE FROM apmail_usuario WHERE system_user_id IN ({$u1}, {$u2})");
$c->exec("INSERT INTO apmail_usuario (system_user_id, email, avisos) VALUES ({$u2}, 'fora@teste.local', 'S')");
$unidade = (int) $c->query('SELECT system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetchColumn();
TTransaction::close();
$respostas = [];
ApmailApiService::usarTransporte(function (string $url, string $token) use (&$respostas) {
    parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
    return $respostas[$q['email'] ?? ''] ?? ['status' => 404, 'corpo' => '{"error":{"code":"notification_scope_unavailable","message":"x"}}', 'erro' => '', 'retry_after' => null];
});
try
{
    TSession::setValue('userunitid', $unidade);
    // ---------- tela do envelope ----------
    $respostas['cadastro.um@teste.local'] = $ok('cadastro.um@teste.local', [['id' => 'a2', 'name' => 'Financeiro', 'email' => 'financeiro@x.com', 'unread' => 3, 'pending' => 2, 'url' => '/mail/a2?assigned=any']]);
    ApmailEnvelopeService::limparCache();
    TSession::setValue('userid', $u1);
    $mail = saida(function () { (new MailList(['theme' => 'theme-builder']))->show(); });
    ok('envelope: logo do APMail e contador de pendentes', strpos($mail, 'apmail-logo') !== false && strpos($mail, 'data-count="2"') !== false, substr($mail, 0, 400));
    ok('envelope: caixa com pendentes e e-mails nao lidos (termo do APMail: e-mail, nunca mensagem)', strpos($mail, '>Financeiro<') !== false && strpos($mail, '2 pendentes') !== false && strpos($mail, '3 e-mails não lidos') !== false
        && stripos($mail, 'mensage') === false, $mail);
    ok('envelope: legenda do que cada numero conta', strpos($mail, 'Pendentes = conversas de e-mail em A Responder + Em Atendimento') !== false
        && strpos($mail, 'Não lidos = e-mails não lidos') !== false && strpos($mail, 'uma conversa com 3 e-mails não lidos conta 3') !== false, $mail);
    ok('envelope: abre a caixa no APMail, sempre na mesma aba', strpos($mail, LinkExterno::onclick('https://app.apmail.com.br/mail/a2?assigned=any', 'apmail')) !== false, $mail);
    ok('envelope: sem texto ao parar o mouse no icone', preg_match('/class="button-circle"[^>]*title=/', $mail) === 0);
    ok('envelope: fica aberto ao atravessar ate a lista', strpos($mail, '.fast-drop.apmail-aberto > .fast-drop-open') !== false);
    TSession::setValue('userid', $u2);
    $fora = saida(function () { (new MailList(['theme' => 'theme-builder']))->show(); });
    ok('envelope: e-mail fora da integracao avisa', strpos($fora, 'não está liberado no APMail') !== false, $fora);

    // ---------- telas de configuracao ----------
    $tela = saida(function () { $t = new ApchatUsuarioList([]); $t->show(); });
    ok('tela unica: titulos das colunas', strpos($tela, 'Usuários das integrações') !== false && strpos($tela, 'Usuário do APChat') !== false && strpos($tela, 'Notificações APChat') !== false
        && strpos($tela, 'E-mail de usuário do APMail') !== false && strpos($tela, 'Notificações APMail') !== false && strpos($tela, 'Verificação APMail') !== false
        && strpos($tela, 'Avisos') === false && strpos($tela, 'Atendente do APChat') === false && strpos($tela, 'Conferir no APMail') !== false && stripos($tela, 'fatal error') === false, substr($tela, 0, 500));
    ok('tela unica: no submenu Administracao', strpos($tela, 'Administração') !== false);
    ok('tela unica: e-mail do cadastro como sugestao', strpos($tela, 'cadastro.um@teste.local') !== false);
    TSession::setValue('usergroupids', [1]); // integracoes do escritorio: so Admin (11/11/2026)
    $esc = saida(function () { $t = new EscritorioForm([]); $t->show(); });
    ok('escritorio: endereco e token do APMail, com Testar', strpos($esc, 'url_apmail') !== false && strpos($esc, 'token_apmail') !== false && strpos($esc, 'onTestarApmail') !== false, '');
}
finally
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    foreach ($antes['usuarios'] as $u) { $c->prepare('UPDATE system_users SET email = ? WHERE id = ?')->execute([$u['email'], $u['id']]); }
    foreach ($antes['escritorio'] as $e) { $c->prepare('UPDATE escritorio SET url_apmail = ?, token_apmail = ? WHERE id = ?')->execute([$e['url_apmail'], $e['token_apmail'], $e['id']]); }
    $c->exec("DELETE FROM apmail_usuario WHERE system_user_id IN ({$u1}, {$u2})");
    foreach ($antes['apmail'] as $m) { $c->prepare('INSERT INTO apmail_usuario (system_user_id, email, avisos) VALUES (?, ?, ?)')->execute([$m['system_user_id'], $m['email'], $m['avisos']]); }
    TTransaction::close();
    ApmailApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso do APMail.'); });
    ApmailEnvelopeService::limparCache();
}

// permissao da tela unica: grupo Admin + socios (3, 4, 5)
TTransaction::open('escritorio');
$perm = TTransaction::get()->query("SELECT (SELECT string_agg(gp.system_group_id::text, ',' ORDER BY gp.system_group_id) FROM system_group_program gp WHERE gp.system_program_id = p.id) grupos,
       (SELECT string_agg(up.system_user_id::text, ',' ORDER BY up.system_user_id) FROM system_user_program up WHERE up.system_program_id = p.id) usuarios, p.name
  FROM system_program p WHERE p.controller = 'ApchatUsuarioList'")->fetch(PDO::FETCH_ASSOC);
TTransaction::close();
igual('tela unica: so o grupo Admin (sem permissao direta)', ['grupos' => '1', 'usuarios' => null, 'name' => 'Usuários das integrações'], $perm);
$menu = (string) file_get_contents('menu.xml');
$adm = substr($menu, strpos($menu, '<menuitem label="_t{Administration}">'));
$adm = substr($adm, 0, strpos($adm, '<menuitem label="Logs">'));
ok('menu: Usuarios das integracoes no submenu Administracao (e so la)', strpos($adm, 'ApchatUsuarioList') !== false && substr_count($menu, 'ApchatUsuarioList') === 1);

$js = (string) file_get_contents('app/lib/include/builder/theme-builder/BuilderTemplate.js');
ok('JS: envelope atualiza a cada 10 s, pausa com a aba escondida e nao redesenha com o mouse em cima', strpos($js, 'apmail-aberto') !== false && strpos($js, 'updateTopo, 10000') !== false && strpos($js, 'mailPendente') !== false);
ok('JS: ao voltar para a aba, envelope (direto no APMail) e balao atualizam na hora', strpos($js, '&fresco=1') !== false && strpos($js, 'updateTopo(true)') !== false && strpos($js, 'balaoVoltou') !== false);

fim();
