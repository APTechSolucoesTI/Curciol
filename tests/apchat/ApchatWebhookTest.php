<?php
// tests/apchat/ApchatWebhookTest.php
// Webhook do APChat (10/11/2026): o APChat chama a Curciol a cada evento (POST JSON em HTTPS). A Curciol so aceita com a chave
// secreta do escritorio no endereco, guarda o evento como chegou e responde na hora. Nada e enviado. Tudo dentro de transacao desfeita.
require __DIR__ . '/../bootstrap.php';

TTransaction::open('escritorio');
try
{
    $pdo = TTransaction::get();
    $chave = (string) $pdo->query("SELECT webhook_apchat_chave FROM escritorio WHERE coalesce(webhook_apchat_chave, '') <> '' ORDER BY id LIMIT 1")->fetchColumn();
    ok('escritorio tem chave do webhook (32+ caracteres)', strlen($chave) >= 32, $chave);
    $total = function () use ($pdo) { return (int) $pdo->query('SELECT count(*) FROM apchat_webhook_evento')->fetchColumn(); };
    $antes = $total();
    $json = '{"event":"message","data":{"ticketId":1,"body":"Oi"}}';

    igual('GET: metodo nao permitido', 405, ApchatWebhookService::receber('GET', $chave, $json, '1.2.3.4'));
    igual('sem chave: recusado', 403, ApchatWebhookService::receber('POST', null, $json, '1.2.3.4'));
    igual('chave errada: recusado', 403, ApchatWebhookService::receber('POST', $chave . 'x', $json, '1.2.3.4'));
    igual('chave vazia: recusado', 403, ApchatWebhookService::receber('POST', '', $json, '1.2.3.4'));
    igual('corpo vazio: invalido', 400, ApchatWebhookService::receber('POST', $chave, '  ', '1.2.3.4'));
    igual('corpo grande demais', 413, ApchatWebhookService::receber('POST', $chave, str_repeat('a', ApchatWebhookService::MAXIMO_BYTES + 1), '1.2.3.4'));
    igual('nada recusado foi guardado', $antes, $total());

    igual('chave certa: aceito', 200, ApchatWebhookService::receber('POST', $chave, $json, '1.2.3.4'));
    $ev = $pdo->query('SELECT corpo, ip, processado_em FROM apchat_webhook_evento ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    igual('guardado como chegou, com o IP, ainda nao processado', ['corpo' => $json, 'ip' => '1.2.3.4', 'processado_em' => null], $ev);

    // o APChat manda os tokens dos canais e chaves de IA junto: nunca ficam guardados na Curciol
    $comSegredo = '{"method":"message","msg":{"body":"Olá/tchau"},"ticket":{"id":7,"chatgptApiKey":"k1","difyKey":"k2","apiConfig":{"x":1},'
        . '"whatsapp":{"id":2,"name":"Canal","tokenAPI":"t1","bmToken":"t2","wavoipToken":"t3"}}}';
    igual('com segredos: aceito', 200, ApchatWebhookService::receber('POST', $chave, $comSegredo, '1.2.3.4'));
    $guardado = json_decode((string) $pdo->query('SELECT corpo FROM apchat_webhook_evento ORDER BY id DESC LIMIT 1')->fetchColumn(), true);
    igual('segredos fora, o resto intacto', ['method' => 'message', 'msg' => ['body' => 'Olá/tchau'], 'ticket' => ['id' => 7, 'whatsapp' => ['id' => 2, 'name' => 'Canal']]], $guardado);
    igual('corpo que nao e JSON fica como chegou', 200, ApchatWebhookService::receber('POST', $chave, 'texto solto', '1.2.3.4'));
}
finally
{
    TTransaction::rollback();
}

fim();
