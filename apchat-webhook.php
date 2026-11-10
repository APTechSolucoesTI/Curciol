<?php
// Webhook do APChat (10/11/2026): https://<site>/apchat-webhook.php?chave=<escritorio.webhook_apchat_chave>
// So guarda o evento (ApchatWebhookService); nao abre sessao nem passa pelas telas.
require_once 'init.php';

$corpo = (string) file_get_contents('php://input', false, null, 0, ApchatWebhookService::MAXIMO_BYTES + 1);
try
{
    TTransaction::open('escritorio');
    $status = ApchatWebhookService::receber($_SERVER['REQUEST_METHOD'] ?? '', $_GET['chave'] ?? null, $corpo, $_SERVER['REMOTE_ADDR'] ?? '');
    TTransaction::close();
}
catch (Throwable $e)
{
    TTransaction::rollback();
    $status = 500;
}

http_response_code($status);
header('Content-Type: application/json');
echo json_encode(['ok' => $status === 200]);
