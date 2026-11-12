<?php
// tests/apmail/ApmailSincronizacaoTest.php
// Sincronizacao do APMail para o historico (12/11/2026): cursor (so avanca depois de gravar), conversa completa, sem duplicar,
// so as conversas dos e-mails liberados (homologacao), sem os e-mails das caixas, erros sem perder nada, trava. APMail falso.
require __DIR__ . '/../bootstrap.php';

function qs(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function xs(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

const PFX = '00000000-0000-4000-8000-0000000000';
const TOKEN_TESTE = 'token-de-teste-sincronizacao';
$limpar = function () {
    xs("DELETE FROM apmail_conversa WHERE thread_id::text LIKE '" . PFX . "%'");
    xs("DELETE FROM apmail_sincronizacao WHERE chave = '" . sha1(TOKEN_TESTE . '|integra@escritorio.com|all') . "'");
};
$limpar();

$msg = function (string $id, string $thread, string $dir, string $quando, string $de, array $para, ?array $quem = null, int $anexos = 0) {
    return ['id' => PFX . $id, 'threadId' => PFX . $thread, 'direction' => $dir, 'subject' => 'Assunto ' . $thread, 'messageAt' => $quando,
            'from' => ['name' => 'De ' . $de, 'email' => $de], 'to' => array_map(function ($e) { return ['name' => '', 'email' => $e]; }, $para), 'cc' => [],
            'mailbox' => ['id' => PFX . 'f1', 'name' => 'Suporte', 'email' => 'suporte@escritorio.com'], 'status' => 'done',
            'assignedToUser' => ['id' => 'u1', 'name' => 'Atendente Um', 'email' => 'um@escritorio.com'], 'sentByUser' => $quem,
            'body' => ['text' => 'Texto ' . $id, 'sanitizedHtml' => '<p>Corpo ' . $id . '</p>', 'html' => '<p>orig</p>'], 'bodyIncluded' => true,
            'attachmentCount' => $anexos, 'hasAttachments' => $anexos > 0];
};
// conversas: a1 (cliente1, recebido + resposta), a2 (cliente2), a3 (outra pessoa: ignorada na homologacao)
$conversas = [
    'a1' => [$msg('b1', 'a1', 'inbound', '2026-11-10T12:00:00.000Z', 'Cliente1@Teste.com', ['suporte@escritorio.com'], null, 2),
             $msg('b2', 'a1', 'outbound', '2026-11-10T13:30:00.000Z', 'suporte@escritorio.com', ['cliente1@teste.com'], ['id' => 'u2', 'name' => 'Atendente Dois', 'email' => 'dois@escritorio.com'])],
    'a2' => [$msg('b3', 'a2', 'inbound', '2026-11-11T09:00:00.000Z', 'cliente2@teste.com', ['suporte@escritorio.com'])],
    'a3' => [$msg('b4', 'a3', 'inbound', '2026-11-11T10:00:00.000Z', 'outro@fora.com', ['suporte@escritorio.com'])],
];
$paginas = []; $chamadas = []; $erroNa = null;
$ok200 = function (array $data) { return ['status' => 200, 'corpo' => json_encode(['success' => true, 'data' => $data]), 'erro' => '', 'retry_after' => null]; };
ApmailApiService::usarTransporte(function (string $url, string $token) use (&$paginas, &$chamadas, &$erroNa, $conversas, $ok200) {
    $chamadas[] = $url;
    parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
    if (strpos($url, '/api/integrations/messages?') !== false)
    {
        $c = $q['cursor'] ?? '';
        if ($erroNa !== null && $c === $erroNa) { return ['status' => 503, 'corpo' => '', 'erro' => '', 'retry_after' => null]; }
        return $ok200($paginas[$c] ?? ['items' => [], 'nextCursor' => $c ?: null, 'hasMore' => false]);
    }
    if (preg_match('#/threads/' . PFX . '([a-z0-9]+)\?#', $url, $m)) { return $ok200(['threadId' => PFX . $m[1], 'items' => $conversas[$m[1]] ?? [], 'nextCursor' => null, 'hasMore' => false]); }
    return ['status' => 404, 'corpo' => '{"error":{"code":"not_found"}}', 'erro' => '', 'retry_after' => null];
});
$item = function (array $m) { $m['body'] = null; $m['bodyIncluded'] = false; return $m; };
$paginas = [
    ''   => ['items' => [$item($conversas['a1'][0]), $item($conversas['a3'][0])], 'nextCursor' => 'C1', 'hasMore' => true],
    'C1' => ['items' => [$item($conversas['a1'][1]), $item($conversas['a2'][0])], 'nextCursor' => 'C2', 'hasMore' => false],
];
$cfg = tempnam(sys_get_temp_dir(), 'apmail-cfg-');
$trava = tempnam(sys_get_temp_dir(), 'apmail-trava-');
$cursor = function () { $r = qs("SELECT cursor, ultimo_erro FROM apmail_sincronizacao WHERE chave = '" . sha1(TOKEN_TESTE . '|integra@escritorio.com|all') . "'"); return $r[0] ?? null; };

try
{
    file_put_contents($cfg, "<?php return ['historico_somente_emails' => ['cliente1@teste.com', 'CLIENTE2@teste.com']];");
    ApmailSincronizacaoService::usarConfig($cfg);
    ApmailSincronizacaoService::usarTrava($trava);
    ApmailSincronizacaoService::usarCredenciais(['url' => 'https://app.apmail.com.br', 'token' => TOKEN_TESTE, 'email' => 'integra@escritorio.com']);
    igual('somenteEmails: em minusculas', ['cliente1@teste.com', 'cliente2@teste.com'], ApmailSincronizacaoService::somenteEmails());

    // 1) primeira rodada: 2 conversas liberadas gravadas, 1 ignorada, cursor final salvo
    $r = ApmailSincronizacaoService::processar();
    igual('rodada 1: conversas, mensagens e ignoradas', [2, 3, 1, null], [$r['conversas'], $r['mensagens'], $r['ignoradas'], $r['erro']]);
    igual('rodada 1: cursor final salvo', 'C2', $cursor()['cursor'] ?? null);
    $conv = qs("SELECT thread_id::text t, assunto, caixa_nome, to_char(inicio_em,'YYYY-MM-DD HH24:MI') i, to_char(fim_em,'YYYY-MM-DD HH24:MI') f, responsavel_nome, qtd_mensagens
                  FROM apmail_conversa WHERE thread_id::text LIKE '" . PFX . "%' ORDER BY thread_id");
    igual('conversas gravadas (a1 e a2; a3 de fora ignorada)', [PFX . 'a1', PFX . 'a2'], array_column($conv, 't'));
    igual('a1: assunto, caixa, inicio e fim em Brasilia, responsavel, 2 e-mails', ['Assunto a1', 'Suporte', '2026-11-10 09:00', '2026-11-10 10:30', 'Atendente Um', 2],
          [$conv[0]['assunto'], $conv[0]['caixa_nome'], $conv[0]['i'], $conv[0]['f'], $conv[0]['responsavel_nome'], (int) $conv[0]['qtd_mensagens']]);
    $m = qs("SELECT apmail_id::text a, direcao, de_email, enviado_por_nome, corpo_html, corpo_texto, qtd_anexos, para::text para FROM apmail_mensagem
              WHERE apmail_id::text LIKE '" . PFX . "%' ORDER BY apmail_id");
    igual('mensagens: direcao, quem enviou, corpo do APMail (sanitizedHtml), anexos so a quantidade',
          [['inbound', null, '<p>Corpo b1</p>', 2], ['outbound', 'Atendente Dois', '<p>Corpo b2</p>', 0]],
          [[$m[0]['direcao'], $m[0]['enviado_por_nome'], $m[0]['corpo_html'], (int) $m[0]['qtd_anexos']], [$m[1]['direcao'], $m[1]['enviado_por_nome'], $m[1]['corpo_html'], (int) $m[1]['qtd_anexos']]]);
    ok('mensagens: para em JSON com nome e e-mail', strpos($m[1]['para'], 'cliente1@teste.com') !== false);
    $emails = array_column(qs("SELECT e.email FROM apmail_conversa_email e JOIN apmail_conversa c ON c.id = e.conversa_id WHERE c.thread_id = '" . PFX . "a1' ORDER BY 1"), 'email');
    igual('e-mails da conversa: so os participantes, em minusculas, sem o da caixa', ['cliente1@teste.com'], $emails);

    // 2) mesma pagina de novo (cursor apagado): nao duplica
    xs("UPDATE apmail_sincronizacao SET cursor = NULL WHERE chave = '" . sha1(TOKEN_TESTE . '|integra@escritorio.com|all') . "'");
    ApmailSincronizacaoService::processar();
    igual('rodada repetida: sem duplicar mensagens', 3, (int) qs("SELECT count(*) n FROM apmail_mensagem WHERE apmail_id::text LIKE '" . PFX . "%'")[0]['n']);

    // 3) falha na 2a pagina: cursor fica o da 1a
    xs("UPDATE apmail_sincronizacao SET cursor = NULL WHERE chave = '" . sha1(TOKEN_TESTE . '|integra@escritorio.com|all') . "'");
    $erroNa = 'C1';
    $r = ApmailSincronizacaoService::processar();
    igual('falha na 2a pagina: cursor da 1a pagina', 'C1', $cursor()['cursor'] ?? null);
    ok('falha: erro registrado, sem excecao', $r['erro'] !== null && trim((string) ($cursor()['ultimo_erro'] ?? '')) !== '', json_encode($r));
    $erroNa = null;

    // 4) 429: registra e nao lanca
    $paginas['C1'] = null;
    ApmailApiService::usarTransporte(function () { return ['status' => 429, 'corpo' => '{"error":{"code":"rate_limited"}}', 'erro' => '', 'retry_after' => 60]; });
    $r = ApmailSincronizacaoService::processar();
    ok('429: erro registrado e cursor mantido', $r['erro'] !== null && ($cursor()['cursor'] ?? null) === 'C1', json_encode($r));

    // 5) trava ocupada: nao chama a API
    $chamadas = [];
    ApmailApiService::usarTransporte(function () use (&$chamadas) { $chamadas[] = 1; return ['status' => 500, 'corpo' => '', 'erro' => '', 'retry_after' => null]; });
    $h = fopen($trava, 'c'); flock($h, LOCK_EX);
    $r = ApmailSincronizacaoService::processar();
    flock($h, LOCK_UN); fclose($h);
    ok('trava ocupada: sai sem chamar a API', count($chamadas) === 0 && $r['erro'] === 'ocupado', json_encode($r));

    // 6) sem historico_somente_emails (producao): grava a de fora tambem
    file_put_contents($cfg, "<?php return ['host' => 'x'];");
    igual('sem a chave: somenteEmails nulo', null, ApmailSincronizacaoService::somenteEmails());
    ApmailApiService::usarTransporte(function (string $url) use ($conversas, $ok200) {
        if (strpos($url, '/api/integrations/messages?') !== false) { return $ok200(['items' => [$conversas['a3'][0]], 'nextCursor' => 'C3', 'hasMore' => false]); }
        return $ok200(['items' => $conversas['a3'], 'nextCursor' => null, 'hasMore' => false]);
    });
    $r = ApmailSincronizacaoService::processar();
    igual('sem a chave: a conversa de fora e gravada', [1, 0], [$r['conversas'], $r['ignoradas']]);
}
finally
{
    ApmailSincronizacaoService::usarConfig(null);
    ApmailSincronizacaoService::usarTrava(null);
    ApmailSincronizacaoService::usarCredenciais(null);
    ApmailApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso do APMail.'); });
    @unlink($cfg); @unlink($trava);
    $limpar();
}

fim();
