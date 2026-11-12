<?php
// tests/seguranca/IntegracoesSegurancaTest.php
// Seguranca das integracoes (11/11/2026, revisao do APChat/APMail):
// 3) tokens do APChat e do APMail nunca vao para o navegador; vazio ao salvar mantem o salvo; so o grupo Admin ve e altera;
// 4) whatsapp.php antigo (Meta) removido;
// 5) webhook do APChat so dos IPs configurados e com limite de pedidos por minuto por IP;
// 6) CSV de Contatos sem vinculo sem formula (nome do WhatsApp e escolhido pelo proprio cliente);
// 7) links externos abertos com o texto protegido para JavaScript; do APMail so caminho relativo; HTTP so http/https.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }

// ---------- 7) links externos ----------
$js = LinkExterno::onclick("https://app.apmail.com.br/mail/x');alert(1);//", 'apmail');
ok('link: aspas viram texto do JavaScript (nao fecham a string)', strpos(html_entity_decode($js, ENT_QUOTES), "window.open(\"https://app.apmail.com.br/mail/x\\u0027);alert(1);//\", \"apmail\")") !== false, $js);
igual('link: so http/https (javascript: vira nada)', '', LinkExterno::onclick('javascript:alert(1)', 'apchat'));
igual('APMail: caminho relativo vira endereco da base', 'https://app.apmail.com.br/mail/a1?assigned=any', ApmailApiService::urlCaixa('https://app.apmail.com.br', '/mail/a1?assigned=any'));
igual('APMail: endereco absoluto de outro site e recusado (fica a base)', 'https://app.apmail.com.br/', ApmailApiService::urlCaixa('https://app.apmail.com.br', 'https://evil.example/x'));
igual('APMail: //outro-site tambem', 'https://app.apmail.com.br/', ApmailApiService::urlCaixa('https://app.apmail.com.br', '//evil.example/x'));
foreach (['app/service/ApmailApiService.php', 'app/service/ApchatApiService.php'] as $f)
{
    ok("{$f}: chamadas HTTP so por http/https", strpos((string) file_get_contents($f), 'CURLOPT_PROTOCOLS') !== false);
}
foreach (['app/control/MailList.class.php', 'app/control/MessageList.class.php', 'app/control/pessoas/ApchatTicketView.php'] as $f)
{
    ok("{$f}: abre pelo LinkExterno ou LinkApp", (strpos((string) file_get_contents($f), 'LinkExterno::onclick') !== false || strpos((string) file_get_contents($f), 'LinkApp::') !== false) && strpos((string) file_get_contents($f), "window.open('") === false);
}

// ---------- 6) CSV ----------
igual('CSV: formula vira texto', ["'=HYPERLINK(\"x\")", "'+5511", "'-1", "'@soma", 'Maria', '', "'\tx"],
      array_map(['ApchatContatoSemVinculoList', 'celulaCsv'], ['=HYPERLINK("x")', '+5511', '-1', '@soma', 'Maria', '', "\tx"]));

// ---------- 4) whatsapp.php ----------
ok('whatsapp.php antigo removido', !is_file('whatsapp.php'));

// ---------- 5) webhook ----------
TTransaction::open('escritorio');
try
{
    $c = TTransaction::get();
    $chave = (string) $c->query("SELECT webhook_apchat_chave FROM escritorio WHERE coalesce(webhook_apchat_chave, '') <> '' ORDER BY id LIMIT 1")->fetchColumn();
    $corpo = '{"method":"message","msg":{"fromMe":false},"ticket":{"id":1}}';
    ApchatWebhookService::usarRelogio(1800000000);
    $c->exec("UPDATE escritorio SET webhook_apchat_ips = '92.118.59.239, 10.0.0.5'");
    igual('IP fora da lista: recusado', 403, ApchatWebhookService::receber('POST', $chave, $corpo, '1.2.3.4'));
    igual('IP da lista: aceito', 200, ApchatWebhookService::receber('POST', $chave, $corpo, '10.0.0.5'));
    $c->exec("UPDATE escritorio SET webhook_apchat_ips = NULL");
    igual('sem lista de IPs: so a chave', 200, ApchatWebhookService::receber('POST', $chave, $corpo, '1.2.3.4'));
    ApchatWebhookService::limparLimite();
    $codigos = [];
    for ($i = 0; $i <= ApchatWebhookService::LIMITE_POR_MINUTO; $i++) { $codigos[] = ApchatWebhookService::receber('POST', $chave, $corpo, '5.6.7.8'); }
    igual('limite por minuto por IP: o excedente e recusado (429)', [ApchatWebhookService::LIMITE_POR_MINUTO, 429], [count(array_filter($codigos, function ($x) { return $x === 200; })), end($codigos)]);
    igual('outro IP nao e afetado', 200, ApchatWebhookService::receber('POST', $chave, $corpo, '5.6.7.9'));
    ApchatWebhookService::usarRelogio(1800000000 + 61);
    igual('no minuto seguinte libera', 200, ApchatWebhookService::receber('POST', $chave, $corpo, '5.6.7.8'));
}
finally
{
    TTransaction::rollback();
    ApchatWebhookService::usarRelogio(null);
    ApchatWebhookService::limparLimite();
}

// ---------- 3) tokens ----------
TTransaction::open('escritorio');
$esc = TTransaction::get()->query("SELECT id, token_apchat, token_apmail FROM escritorio WHERE coalesce(token_apchat, '') <> '' ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$unidade = (int) TTransaction::get()->query('SELECT system_unit_id FROM escritorio WHERE id = ' . (int) $esc['id'])->fetchColumn();
TTransaction::close();
TSession::setValue('userid', 1);
TSession::setValue('userunitid', $unidade);
TSession::setValue('usergroupids', [1]);
$admin = saida(function () use ($esc) { $t = new EscritorioForm([]); $t->onEdit(['key' => $esc['id']]); $t->show(); });
ok('admin: campos do APChat e do APMail aparecem', strpos($admin, 'token_apchat') !== false && strpos($admin, 'token_apmail') !== false, '');
ok('admin: o token salvo do APChat NAO vai para o navegador', strpos($admin, (string) $esc['token_apchat']) === false);
ok('admin: o token salvo do APMail NAO vai para o navegador', trim((string) $esc['token_apmail']) === '' || strpos($admin, (string) $esc['token_apmail']) === false);
ok('admin: dica de token salvo (so o final)', strpos($admin, 'Token salvo (termina em ' . substr((string) $esc['token_apchat'], -4) . ')') !== false, '');
TSession::setValue('usergroupids', [99]);
$outro = saida(function () use ($esc) { $t = new EscritorioForm([]); $t->onEdit(['key' => $esc['id']]); $t->show(); });
ok('nao admin: sem os campos do APChat e do APMail', strpos($outro, 'token_apchat') === false && strpos($outro, 'url_apchat') === false && strpos($outro, 'token_apmail') === false, '');

$salvo = (object) ['url_apchat' => 'https://a', 'token_apchat' => 'TOKEN-SALVO', 'url_tela_apchat' => 'https://t', 'url_apmail' => null, 'token_apmail' => 'MAIL-SALVO'];
$d = EscritorioForm::segredosAoSalvar((object) ['url_apchat' => 'https://a', 'token_apchat' => '', 'url_tela_apchat' => 'https://t', 'url_apmail' => '', 'token_apmail' => '  '], $salvo, true);
igual('admin: token vazio ao salvar mantem o salvo', ['TOKEN-SALVO', 'MAIL-SALVO'], [$d->token_apchat, $d->token_apmail]);
$d = EscritorioForm::segredosAoSalvar((object) ['url_apchat' => 'https://a', 'token_apchat' => 'NOVO', 'url_tela_apchat' => 'https://t', 'url_apmail' => '', 'token_apmail' => ''], $salvo, true);
igual('admin: token digitado substitui', 'NOVO', $d->token_apchat);
$d = EscritorioForm::segredosAoSalvar((object) ['url_apchat' => 'https://evil', 'token_apchat' => 'X', 'url_tela_apchat' => 'https://evil', 'url_apmail' => 'https://evil', 'token_apmail' => 'Y'], $salvo, false);
igual('nao admin: integracoes nao mudam, mesmo se enviadas', ['https://a', 'TOKEN-SALVO', 'https://t', null, 'MAIL-SALVO'],
      [$d->url_apchat, $d->token_apchat, $d->url_tela_apchat, $d->url_apmail, $d->token_apmail]);
lanca('nao admin: botao de teste do APChat recusado', function () { EscritorioForm::exigirAdmin(); }, 'administrador');
TSession::setValue('usergroupids', [1]);

fim();
