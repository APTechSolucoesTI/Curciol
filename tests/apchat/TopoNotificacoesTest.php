<?php
// tests/apchat/TopoNotificacoesTest.php
// Topo em um pedido so (11/11/2026): balao do APChat, envelope do APMail e sino de notificacoes juntos, a cada 10 s, so com a aba a
// vista; cada parte com a assinatura do conteudo (o navegador so redesenha o que mudou); "fresco" (voltar para a aba) chega ao APMail.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

TTransaction::open('escritorio');
$u = (int) TTransaction::get()->query("SELECT id FROM system_users WHERE active = 'Y' ORDER BY id LIMIT 1")->fetchColumn();
TTransaction::close();
TSession::setValue('userid', $u);

$chamadas = [];
ApmailApiService::usarTransporte(function (string $url, string $token) use (&$chamadas) {
    $chamadas[] = $url;
    return ['status' => 404, 'corpo' => '{"error":{"code":"notification_scope_unavailable","message":"x"}}', 'erro' => '', 'retry_after' => null];
});
ApchatBancoService::usarFalso(new class {
    public function disponivel() { return false; }
});

try
{
    ApchatBalaoService::limparCache();
    ApmailEnvelopeService::limparCache();
    ob_start();
    TopoNotificacoes::onCarregar(['fresco' => '1']);
    $saida = ob_get_clean();
    $r = json_decode($saida, true);
    ok('responde JSON com as tres partes do topo', is_array($r) && isset($r['mensagens'], $r['mail'], $r['notificacoes']), substr($saida, 0, 300));
    $assinaturas = true;
    foreach (['mensagens', 'mail', 'notificacoes'] as $k)
    {
        $assinaturas = $assinaturas && isset($r[$k]['html'], $r[$k]['assinatura']) && $r[$k]['assinatura'] === md5($r[$k]['html']);
    }
    ok('cada parte com o html e a assinatura do conteudo', $assinaturas, json_encode(array_map(function ($p) { return array_keys((array) $p); }, (array) $r)));
    ok('sino de notificacoes incluido', strpos((string) ($r['notificacoes']['html'] ?? ''), 'fast-drop') !== false, substr((string) ($r['notificacoes']['html'] ?? ''), 0, 200));
    ok('parte que falha nao derruba as outras', is_string($r['mensagens']['html'] ?? null) && is_string($r['mail']['html'] ?? null));
}
finally
{
    ApchatBancoService::usarFalso(null);
    ApmailApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso do APMail.'); });
    ApchatBalaoService::limparCache();
    ApmailEnvelopeService::limparCache();
}

ok('rota liberada para usuario logado (como o balao)', strpos((string) file_get_contents('engine.php'), "'TopoNotificacoes' => TRUE") !== false);
$js = (string) file_get_contents('app/lib/include/builder/theme-builder/BuilderTemplate.js');
ok('JS: um pedido so a cada 10 s', strpos($js, 'class=TopoNotificacoes&method=onCarregar&static=1') !== false && strpos($js, 'setInterval(BuilderTemplate.updateTopo, 10000)') !== false);
ok('JS: so redesenha a parte cuja assinatura mudou', strpos($js, "data('assinatura')") !== false);
ok('JS: aba escondida nao pergunta nada (inclusive o sino); ao voltar, tudo na hora e o APMail fresco', strpos($js, 'topoPendente') !== false && strpos($js, 'updateTopo(true)') !== false);
ok('JS: os tres intervalos antigos sairam', strpos($js, 'setInterval( BuilderTemplate.updateMessagesMenu') === false && strpos($js, 'setInterval( BuilderTemplate.updateNotificationsMenu') === false
    && strpos($js, 'setInterval(BuilderTemplate.updateMailMenu') === false);
ok('JS: versao nova do arquivo no layout', strpos((string) file_get_contents('app/templates/theme-builder/libraries_builder.html'), 'BuilderTemplate.js?appver=523987') !== false);
ok('JS: um balao por vez (mouse num icone fecha na hora o balao do outro)', strpos($js, "on('mouseenter', '.fast-drop'") !== false
    && strpos($js, "closest('#envelope_messages').length) { fecharBalao(); }") !== false && strpos($js, "closest('#envelope_mail').length) { fecharMail(); }") !== false);

fim();
