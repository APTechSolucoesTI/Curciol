<?php
// APChat FALSO para testes (php -S 127.0.0.1:PORTA este-arquivo). Mesmas rotas e respostas da API de verdade
// (showcontact, createContact, updateContact), cada resposta demora ~0,3 s como a de verdade. Estado num arquivo
// temporario por porta. Nunca fala com o APChat real.
$arquivo = sys_get_temp_dir() . '/apchat-falso-' . $_SERVER['SERVER_PORT'] . '.json';
$rota = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
$corpo = json_decode((string) file_get_contents('php://input'), true) ?: [];
usleep(300000);

$fp = fopen($arquivo, 'c+');
flock($fp, LOCK_EX);
$estado = json_decode((string) stream_get_contents($fp), true) ?: ['contatos' => [], 'proximo' => 50001, 'chamadas' => []];
$estado['chamadas'][] = $rota;
$numero = (string) ($corpo['number'] ?? '');

if ($rota === 'showcontact') {
    $status = isset($estado['contatos'][$numero]) ? 200 : 404;
    $resposta = $status === 200 ? ['success' => true, 'data' => $estado['contatos'][$numero]] : ['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND'];
} elseif ($rota === 'createContact') {
    $c = $corpo + ['id' => $estado['proximo']++];
    $estado['contatos'][$numero] = $c;
    $status = 201;
    $resposta = ['success' => true, 'data' => $c];
} elseif ($rota === 'updateContact') {
    if (isset($estado['contatos'][$numero])) {
        $estado['contatos'][$numero] = array_merge($estado['contatos'][$numero], $corpo);
        $status = 200;
        $resposta = ['success' => true, 'data' => $estado['contatos'][$numero]];
    } else {
        $status = 404;
        $resposta = ['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND'];
    }
} else {
    $status = 404;
    $resposta = ['success' => false, 'error' => 'ROTA'];
}

ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($estado));
flock($fp, LOCK_UN);
fclose($fp);

http_response_code($status);
header('Content-Type: application/json');
echo json_encode($resposta);
