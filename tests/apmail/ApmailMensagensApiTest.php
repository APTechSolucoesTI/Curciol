<?php
// tests/apmail/ApmailMensagensApiTest.php
// API do APMail para o historico (12/11/2026): sincronizacao por cursor (recebidos e enviados) e conversa completa. APMail falso.
require __DIR__ . '/../bootstrap.php';

$chamadas = [];
$respostas = [];
$falso = function (string $url, string $token) use (&$chamadas, &$respostas) {
    $chamadas[] = $url;
    return array_shift($respostas) ?? ['status' => 500, 'corpo' => '', 'erro' => 'sem resposta', 'retry_after' => null];
};
$ok200 = function (array $data) { return ['status' => 200, 'corpo' => json_encode(['success' => true, 'data' => $data]), 'erro' => '', 'retry_after' => null]; };
const T1 = '0aa125a2-6100-4fd2-b37a-09f0711280a2';

try
{
    ApmailApiService::usarTransporte($falso);

    $respostas = [$ok200(['items' => [['id' => 'a', 'threadId' => T1, 'direction' => 'inbound'], ['id' => 'b', 'threadId' => T1, 'direction' => 'outbound']], 'nextCursor' => 'C2', 'hasMore' => true])];
    $r = ApmailApiService::mensagens('https://app.apmail.com.br/', 'tk', 'Integra@Empresa.com.br', 'c/1+x');
    $u = $chamadas[0];
    ok('mensagens: rota com direction=all, limit=100, e-mail e cursor codificados', strpos($u, 'https://app.apmail.com.br/api/integrations/messages?') === 0
        && strpos($u, 'direction=all') !== false && strpos($u, 'limit=100') !== false && strpos($u, 'email=Integra%40Empresa.com.br') !== false && strpos($u, 'cursor=c%2F1%2Bx') !== false, $u);
    igual('mensagens: itens, proximo cursor e se ha mais', [2, 'C2', true], [count($r['itens']), $r['proximo'], $r['mais']]);

    $chamadas = [];
    $respostas = [$ok200(['items' => [], 'nextCursor' => 'C9', 'hasMore' => false])];
    ApmailApiService::mensagens('https://app.apmail.com.br', 'tk', 'i@e.com', null);
    ok('mensagens: sem cursor na primeira vez', strpos($chamadas[0], 'cursor=') === false, $chamadas[0]);

    // conversa: junta as paginas
    $chamadas = [];
    $respostas = [
        $ok200(['threadId' => T1, 'items' => [['id' => 'm1'], ['id' => 'm2']], 'nextCursor' => 'P2', 'hasMore' => true]),
        $ok200(['threadId' => T1, 'items' => [['id' => 'm3']], 'nextCursor' => null, 'hasMore' => false]),
    ];
    $c = ApmailApiService::conversa('https://app.apmail.com.br', 'tk', 'i@e.com', T1);
    igual('conversa: junta as duas paginas, em ordem', ['m1', 'm2', 'm3'], array_column($c, 'id'));
    ok('conversa: rota /threads com limit=50 e cursor da pagina', strpos($chamadas[0], '/api/integrations/threads/' . T1 . '?') !== false && strpos($chamadas[0], 'limit=50') !== false
        && strpos($chamadas[1], 'cursor=P2') !== false, json_encode($chamadas));

    $chamadas = [];
    lanca('conversa: threadId invalido nem chama a API', fn() => ApmailApiService::conversa('https://app.apmail.com.br', 'tk', 'i@e.com', '../x'), 'inválid');
    igual('conversa invalida: nenhuma chamada', 0, count($chamadas));

    $respostas = [['status' => 429, 'corpo' => '{"error":{"code":"rate_limited"}}', 'erro' => '', 'retry_after' => 30]];
    try { ApmailApiService::mensagens('https://app.apmail.com.br', 'tk', 'i@e.com', null); ok('429 lanca ApmailErro', false); }
    catch (ApmailErro $e) { ok('429 lanca ApmailErro com o Retry-After', $e->getCode() === 429 && $e->retryAfter === 30, $e->getCode() . ' ' . $e->retryAfter); }

    $respostas = [['status' => 404, 'corpo' => '{"error":{"code":"not_found","message":"x"}}', 'erro' => '', 'retry_after' => null]];
    try { ApmailApiService::conversa('https://app.apmail.com.br', 'tk', 'i@e.com', T1); ok('404 lanca', false); }
    catch (ApmailErro $e) { igual('404 lanca ApmailErro 404', 404, $e->getCode()); }

    $respostas = [['status' => 403, 'corpo' => '{"error":{"code":"integration_route_forbidden"}}', 'erro' => '', 'retry_after' => null]];
    lanca('403: avisa que a permissao de mensagens falta no token', fn() => ApmailApiService::mensagens('https://app.apmail.com.br', 'tk', 'i@e.com', null), 'messages:read');
}
finally
{
    ApmailApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso do APMail.'); });
}

fim();
