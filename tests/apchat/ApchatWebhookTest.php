<?php
// tests/apchat/ApchatWebhookTest.php
// Webhook do APChat (10/11/2026): o APChat chama a Curciol a cada evento (POST JSON em HTTPS). A Curciol so aceita com a chave
// secreta do escritorio no endereco e responde na hora. E so a "campainha" do balao: guarda o tipo do evento, o ticket, se a
// mensagem e nossa e a hora - nunca o texto, o contato nem os tokens que o APChat manda junto. Tudo dentro de transacao desfeita.
require __DIR__ . '/../bootstrap.php';

TTransaction::open('escritorio');
try
{
    $pdo = TTransaction::get();
    $chave = (string) $pdo->query("SELECT webhook_apchat_chave FROM escritorio WHERE coalesce(webhook_apchat_chave, '') <> '' ORDER BY id LIMIT 1")->fetchColumn();
    ok('escritorio tem chave do webhook (32+ caracteres)', strlen($chave) >= 32, $chave);
    $total = function () use ($pdo) { return (int) $pdo->query('SELECT count(*) FROM apchat_webhook_evento')->fetchColumn(); };
    $ultimo = function () use ($pdo) { return $pdo->query('SELECT metodo, ticket_id, de_mim, ip FROM apchat_webhook_evento ORDER BY id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC); };
    // IP: o primeiro liberado em escritorio.webhook_apchat_ips (a homologacao so aceita o do APChat); sem lista, qualquer um
    $lista = (string) $pdo->query("SELECT coalesce(webhook_apchat_ips, '') FROM escritorio WHERE webhook_apchat_chave = " . $pdo->quote($chave))->fetchColumn();
    $ip = trim((string) (preg_split('/[\s,;]+/', trim($lista))[0] ?? '')) ?: '1.2.3.4';
    $antes = $total();
    $cliente = '{"method":"message","msg":{"fromMe":false,"body":"Oi","content":"Oi"},"ticket":{"id":2972,"status":"pending","whatsapp":{"id":2,"tokenAPI":"t1"},'
             . '"contact":{"number":"5519971045673"}}}';

    igual('GET: metodo nao permitido', 405, ApchatWebhookService::receber('GET', $chave, $cliente, $ip));
    igual('sem chave: recusado', 403, ApchatWebhookService::receber('POST', null, $cliente, $ip));
    igual('chave errada: recusado', 403, ApchatWebhookService::receber('POST', $chave . 'x', $cliente, $ip));
    igual('chave vazia: recusado', 403, ApchatWebhookService::receber('POST', '', $cliente, $ip));
    igual('corpo vazio: invalido', 400, ApchatWebhookService::receber('POST', $chave, '  ', $ip));
    igual('corpo que nao e JSON: invalido', 400, ApchatWebhookService::receber('POST', $chave, 'texto solto', $ip));
    igual('corpo grande demais', 413, ApchatWebhookService::receber('POST', $chave, str_repeat('a', ApchatWebhookService::MAXIMO_BYTES + 1), $ip));
    if (trim($lista) !== '')
    {
        igual('IP fora da lista do escritorio: recusado', 403, ApchatWebhookService::receber('POST', $chave, $cliente, '203.0.113.9'));
    }
    igual('nada recusado foi guardado', $antes, $total());

    $evento = ApchatWebhookService::ultimoEvento();
    igual('mensagem do cliente: aceita', 200, ApchatWebhookService::receber('POST', $chave, $cliente, $ip));
    igual('guarda so o tipo, o ticket, se e nossa e o IP (sem texto, contato nem token)',
          ['metodo' => 'message', 'ticket_id' => 2972, 'de_mim' => false, 'ip' => $ip], $ultimo());
    ok('campainha tocou: ultimo evento de mensagem mudou', ApchatWebhookService::ultimoEvento() > $evento);

    // resposta do atendente pela tela do APChat: o APChat nao manda fromMe, mas e nossa
    $atendente = '{"method":"message_send_uazapi","msg":{"message":"*Gabriel*: oi"},"ticket":{"id":2972,"whatsappId":2}}';
    igual('resposta do atendente: aceita', 200, ApchatWebhookService::receber('POST', $chave, $atendente, $ip));
    igual('resposta do atendente: nossa', ['metodo' => 'message_send_uazapi', 'ticket_id' => 2972, 'de_mim' => true, 'ip' => $ip], $ultimo());

    // contato atualizado: guarda o tipo, mas nao toca a campainha do balao
    $evento = ApchatWebhookService::ultimoEvento();
    igual('contato: aceito', 200, ApchatWebhookService::receber('POST', $chave, '{"method":"contact-create-update","contact":{"id":171,"number":"5519971045673"}}', $ip));
    igual('contato: so o tipo', ['metodo' => 'contact-create-update', 'ticket_id' => null, 'de_mim' => null, 'ip' => $ip], $ultimo());
    igual('contato nao toca a campainha', $evento, ApchatWebhookService::ultimoEvento());

    // limpeza: eventos com mais de 3 dias saem
    $pdo->exec("INSERT INTO apchat_webhook_evento (recebido_em, metodo, ticket_id) VALUES (now() - interval '4 days', 'message', 1), (now() - interval '2 days', 'message', 2)");
    $antigos = (int) $pdo->query("SELECT count(*) FROM apchat_webhook_evento WHERE recebido_em < now() - interval '3 days'")->fetchColumn();
    igual('limpeza apaga os de mais de 3 dias', $antigos, ApchatWebhookService::limparAntigos());
    igual('sobram os recentes', 0, (int) $pdo->query("SELECT count(*) FROM apchat_webhook_evento WHERE recebido_em < now() - interval '3 days'")->fetchColumn());
    ok('webhook ativo (evento nas ultimas 24 h)', ApchatWebhookService::ativo());
}
finally
{
    TTransaction::rollback();
}

fim();
