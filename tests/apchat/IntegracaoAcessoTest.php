<?php
// tests/apchat/IntegracaoAcessoTest.php
// Bloqueio das integracoes (16/11/2026, IntegracaoAcessoService): APChat e APMail sem URL/token no escritorio, fora do ar, sem responder
// ou com token recusado, ou usuario sem vinculo = icone do topo limpo (sem contador, sem lista, sem levar a lugar nenhum) so com o
// motivo; clique de item (TopoAbrir) e botoes "Abrir no" do historico recusam. Usuario a usuario. NUNCA fala com o APChat/APMail de
// verdade: banco e APIs falsos. Grava de verdade (as telas abrem a propria transacao) e devolve tudo no fim.
require __DIR__ . '/../bootstrap.php';
LinkApp::forcar('apchat', false);
LinkApp::forcar('apmail', false);
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }
function q1(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x1(string $sql, array $p = []): void { TTransaction::open('escritorio'); TTransaction::get()->prepare($sql)->execute($p); TTransaction::close(); }

// ---------- APChat falso: um ticket pendente e um aberto com o atendente 9001; o 9003 nao existe mais ----------
$falso = new class {
    public $tentativas = 0; public $fora = false;
    private function t(int $id, string $status, ?int $user): array {
        return ['id' => $id, 'status' => $status, 'contactId' => $id + 1000, 'userId' => $user, 'queueId' => null, 'whatsappId' => 112, 'contato' => "Cliente {$id}",
                'atendente' => null, 'numero' => '551990000000' . $id, 'canal' => 'Oficial', 'fila' => null, 'ultima_nossa' => false, 'nao_respondidas' => 1,
                'ultima_texto' => "Mensagem {$id}", 'ultima_em' => date('Y-m-d H:i:s'), 'lastMessageAt' => date('Y-m-d H:i:s')];
    }
    public function tickets(): array { return [$this->t(1, 'pending', null), $this->t(2, 'open', 9001)]; }
    public function disponivel() { $this->tentativas++; return !$this->fora; }
    public function impressaoTickets(): string { if ($this->fora) { throw new Exception('fora'); } return md5(json_encode($this->tickets())); }
    public function ticketsAbertosComUltima(): array { if ($this->fora) { throw new Exception('fora'); } return $this->tickets(); }
    public function acessos(int $a): array { return ['canais' => [], 'filas' => [], 'perfil' => 'user', 'configs' => [], 'ver_todos' => false, 'existe' => $a !== 9003]; }
    public function configuracoes(): array { return ['nullTickets' => 'enabled']; }
    public function chatNaoLidas(array $atendentes): array { return []; }
    public function carteiras(array $ids): array { return []; }
    public function conferirTicket(int $ticket, int $atendente): ?array {
        foreach ($this->tickets() as $t) { if ($t['id'] === $ticket) { return ['ticket' => $t, 'acessos' => $this->acessos($atendente), 'config' => $this->configuracoes(), 'carteira' => []]; } }
        return null;
    }
};

// ---------- APMail falso ----------
$mailResposta = null;
$mailChamadas = 0;
$caixasOk = ['status' => 200, 'retry_after' => null, 'erro' => '', 'corpo' => json_encode(['success' => true, 'data' => ['unread' => 3, 'pending' => 2, 'totals' => ['unread' => 3, 'pending' => 2],
             'mailboxes' => [['id' => 'a2', 'name' => 'Financeiro', 'email' => 'financeiro@x.com', 'unread' => 3, 'pending' => 2, 'url' => '/mail/a2']]]])];
ApmailApiService::usarTransporte(function () use (&$mailResposta, &$mailChamadas) { $mailChamadas++; return $mailResposta; });

// ---------- dados: guarda o que existe e devolve no fim ----------
$usuarios = q1("SELECT id, email FROM system_users WHERE active = 'Y' ORDER BY id LIMIT 3");
[$u1, $u2, $u3] = array_map('intval', array_column($usuarios, 'id'));
$escritorios = q1('SELECT id, system_unit_id, url_apchat, token_apchat, url_apmail, token_apmail FROM escritorio ORDER BY id');
$apchatAntes = q1('SELECT * FROM apchat_usuario');
$apmailAntes = q1('SELECT * FROM apmail_usuario');
$sufixo = bin2hex(random_bytes(4));

$escritorio = function (?string $tokenApchat, ?string $tokenApmail) {
    x1('UPDATE escritorio SET url_apchat = ?, token_apchat = ?, url_apmail = ?, token_apmail = ?',
       [$tokenApchat === null ? null : 'https://apchat.teste.invalid/v2/api/external/x', $tokenApchat, 'https://apmail.teste.invalid', $tokenApmail]);
    ApchatBalaoService::limparCache();
    ApmailEnvelopeService::limparCache();
};
$balao = function (int $u) { TSession::setValue('userid', $u); return saida(function () { (new MessageList(['theme' => 'theme-builder']))->show(); }); };
$envelope = function (int $u) { TSession::setValue('userid', $u); return saida(function () { (new MailList(['theme' => 'theme-builder']))->show(); }); };
// icone bloqueado: sem contador, sem lista, sem endereco do sistema, com o motivo
$bloqueado = function (string $html, string $motivo) {
    return strpos($html, 'topo-integracao-bloqueada') !== false && strpos($html, 'ball-notice') === false && strpos($html, 'data-count') === false
        && strpos($html, 'LinkExterno') === false && strpos($html, 'CurciolAbrirNotificacao') === false && strpos($html, '.invalid') === false
        && strpos($html, 'web+ap') === false && strpos($html, 'apchat.com.br') === false && strpos($html, 'apmail.com.br') === false && strpos($html, htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8')) !== false;
};
$decidir = function (array $p, int $u) { TTransaction::open('escritorio'); $d = TopoAbrir::decidir($p, $u); TTransaction::close(); return $d; };

try
{
    ApchatBancoService::usarFalso($falso);
    TSession::setValue('userunitid', (int) $escritorios[0]['system_unit_id']);
    x1('DELETE FROM apchat_usuario WHERE system_user_id IN (?, ?, ?) OR apchat_usuario_id IN (9001, 9003)', [$u1, $u2, $u3]);
    x1("INSERT INTO apchat_usuario (system_user_id, apchat_usuario_id, avisos, mostrar_abertos) VALUES (?, 9001, 'S', 'N'), (?, 9003, 'S', 'N')", [$u1, $u3]);
    x1('DELETE FROM apmail_usuario WHERE system_user_id IN (?, ?, ?)', [$u1, $u2, $u3]);
    x1("UPDATE system_users SET email = 'integracao.um@teste.local' WHERE id = ?", [$u1]);
    x1("UPDATE system_users SET email = NULL WHERE id = ?", [$u2]);
    ApchatApiService::forcarSaude(true);

    // ========== APChat ==========
    $escritorio('tk-' . $sufixo, null);
    $normal = $balao($u1);
    ok('APChat no ar e usuario vinculado: balao normal com contador', preg_match('/data-count="[1-9]/', $normal) === 1 && strpos($normal, 'topo-integracao-bloqueada') === false, substr($normal, 0, 300));

    // usuario a usuario
    ok('APChat: usuario sem vinculo ve o icone limpo e o motivo', $bloqueado($balao($u2), IntegracaoAcessoService::APCHAT['sem_ligacao']), $balao($u2));
    ok('APChat: atendente vinculado que nao existe mais no APChat', $bloqueado($balao($u3), IntegracaoAcessoService::APCHAT['sem_atendente']), $balao($u3));
    igual('APChat: clique do usuario sem vinculo recusa', [false, IntegracaoAcessoService::APCHAT['sem_ligacao']], array_values(array_intersect_key($decidir(['tipo' => 'ticket', 'id' => '2'], $u2), ['ok' => 1, 'mensagem' => 1])));

    // token retirado do escritorio: nada conecta
    $escritorio(null, null);
    $falso->tentativas = 0;
    ok('APChat sem token: icone limpo com o motivo', $bloqueado($balao($u1), IntegracaoAcessoService::APCHAT['sem_integracao']), $balao($u1));
    igual('APChat sem token: nao conecta no banco do APChat', 0, $falso->tentativas);
    igual('APChat sem token: clique recusa', IntegracaoAcessoService::APCHAT['sem_integracao'], $decidir(['tipo' => 'ticket', 'id' => '2'], $u1)['mensagem'] ?? null);
    $d = $decidir(['tipo' => 'chat', 'id' => 'usuario:9002'], $u1);
    ok('APChat sem token: chat interno tambem recusa, sem endereco', ($d['ok'] ?? true) === false && !isset($d['url']) && !isset($d['link']));
    TSession::setValue('userid', $u1);
    $botao = saida(function () { ApchatTicketView::botaoAbrir(2, 'https://apchat.teste.invalid/atendimento?ticketId=2')->show(); });
    ok('APChat sem token: "Abrir no APChat" do historico desabilitado com o motivo', strpos($botao, 'disabled') !== false && strpos($botao, 'teste.invalid') === false
        && strpos($botao, htmlspecialchars(IntegracaoAcessoService::APCHAT['sem_integracao'], ENT_QUOTES, 'UTF-8')) !== false, $botao);

    // API sem responder ou token recusado
    $escritorio('tk-' . $sufixo, null);
    ApchatApiService::forcarSaude(false);
    ok('APChat sem responder: icone limpo com o motivo', $bloqueado($balao($u1), IntegracaoAcessoService::APCHAT['erro']), $balao($u1));
    igual('APChat sem responder: clique recusa', IntegracaoAcessoService::APCHAT['erro'], $decidir(['tipo' => 'ticket', 'id' => '2'], $u1)['mensagem'] ?? null);
    TSession::setValue('userid', $u1);
    $botao = saida(function () { ApchatTicketView::botaoAbrir(2, 'https://apchat.teste.invalid/atendimento?ticketId=2')->show(); });
    ok('APChat sem responder: botao do historico desabilitado', strpos($botao, 'disabled') !== false && strpos($botao, 'teste.invalid') === false, $botao);
    // banco do APChat fora (API no ar)
    ApchatApiService::forcarSaude(true);
    $falso->fora = true;
    ok('APChat com o banco fora: icone limpo com o motivo', $bloqueado($balao($u1), IntegracaoAcessoService::APCHAT['erro']), $balao($u1));
    $falso->fora = false;
    ApchatBalaoService::limparCache();
    ok('APChat de volta: balao normal de novo', preg_match('/data-count="[1-9]/', $balao($u1)) === 1);

    // conferencia de verdade da API (com transporte falso): consulta que nao altera nada, uma vez por minuto para todos
    ApchatApiService::forcarSaude(null);
    ApchatApiService::limparSaude();
    $apiChamadas = [];
    ApchatApiService::usarTransporte(function ($url, $corpo, $token) use (&$apiChamadas) {
        $apiChamadas[] = $url;
        return strpos($token, 'recusado') !== false ? ['status' => 403, 'corpo' => '{"error":"ERR_SESSION_NOT_AUTH_TOKEN"}', 'erro' => '']
                                                   : ['status' => 404, 'corpo' => '{"error":"' . APChatContactService::ERRO_NAO_ENCONTRADO . '"}', 'erro' => ''];
    });
    TTransaction::open('escritorio');
    ok('API: token aceito = no ar', ApchatApiService::noAr());
    ok('API: so consulta (showcontact)', count($apiChamadas) === 1 && substr($apiChamadas[0], -12) === '/showcontact', json_encode($apiChamadas));
    ApchatApiService::noAr();
    igual('API: 1 conferencia por minuto (a segunda usa a resposta guardada)', 1, count($apiChamadas));
    TTransaction::close();
    $escritorio('tk-recusado-' . $sufixo, null);
    TTransaction::open('escritorio');
    ok('API: token recusado = fora (bloqueia)', !ApchatApiService::noAr());
    igual('API: trocar o token confere de novo na hora', 2, count($apiChamadas));
    TTransaction::close();
    ok('API: token recusado deixa o icone limpo', $bloqueado($balao($u1), IntegracaoAcessoService::APCHAT['erro']), $balao($u1));
    ApchatApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso de mensagens.'); });
    ApchatApiService::limparSaude();
    ApchatApiService::forcarSaude(true);

    // ========== APMail ==========
    $escritorio(null, 'tk-apmail-' . $sufixo);
    $mailResposta = $caixasOk;
    $normal = $envelope($u1);
    ok('APMail no ar e usuario vinculado: envelope normal com contador', strpos($normal, 'data-count="2"') !== false && strpos($normal, '>Financeiro<') !== false, substr($normal, 0, 300));

    // fora do ar depois de ter mostrado contagem: nada da contagem velha
    ApmailEnvelopeService::usarRelogio(time() + 60);
    $mailResposta = ['status' => 503, 'corpo' => '', 'erro' => '', 'retry_after' => null];
    $fora = $envelope($u1);
    ok('APMail fora do ar: icone limpo, sem a contagem velha', $bloqueado($fora, IntegracaoAcessoService::APMAIL['indisponivel']) && strpos($fora, 'Financeiro') === false, $fora);
    ApmailEnvelopeService::limparCache();
    $mailResposta = ['status' => 0, 'corpo' => '', 'erro' => 'tempo esgotado', 'retry_after' => null];
    ok('APMail sem responder (tempo esgotado): icone limpo', $bloqueado($envelope($u1), IntegracaoAcessoService::APMAIL['indisponivel']));
    ApmailEnvelopeService::limparCache();
    $mailResposta = ['status' => 401, 'corpo' => '{"error":{"code":"invalid_token","message":"Token"}}', 'erro' => '', 'retry_after' => null];
    ok('APMail com token recusado: icone limpo', $bloqueado($envelope($u1), IntegracaoAcessoService::APMAIL['indisponivel']));
    ApmailEnvelopeService::limparCache();
    $mailResposta = ['status' => 503, 'corpo' => '', 'erro' => '', 'retry_after' => null];
    $d = $decidir(['tipo' => 'caixa', 'id' => 'a2'], $u1);
    ok('APMail fora: clique recusa com o motivo, sem endereco', ($d['ok'] ?? true) === false && !isset($d['url']) && ($d['mensagem'] ?? '') === IntegracaoAcessoService::APMAIL['indisponivel'], json_encode($d));
    ApmailEnvelopeService::usarRelogio(null);

    // 429: o APMail respondeu (so pediu para esperar) - continua com o que ja tinha
    ApmailEnvelopeService::limparCache();
    $mailResposta = $caixasOk;
    $envelope($u1);
    ApmailEnvelopeService::usarRelogio(time() + 60);
    $mailResposta = ['status' => 429, 'corpo' => '', 'erro' => '', 'retry_after' => 30];
    ok('APMail 429: continua com a ultima contagem', strpos($envelope($u1), 'data-count="2"') !== false);
    ApmailEnvelopeService::usarRelogio(null);

    // usuario a usuario
    ApmailEnvelopeService::limparCache();
    $mailResposta = $caixasOk;
    $mailChamadas = 0;
    ok('APMail: usuario sem e-mail vinculado ve o icone limpo e o motivo', $bloqueado($envelope($u2), IntegracaoAcessoService::APMAIL['sem_email']));
    igual('APMail: usuario sem vinculo nao consulta o APMail', 0, $mailChamadas);
    $mailResposta = ['status' => 404, 'corpo' => '{"error":{"code":"notification_scope_unavailable","message":"x"}}', 'erro' => '', 'retry_after' => null];
    ok('APMail: e-mail fora da integracao ve o icone limpo e o motivo', $bloqueado($envelope($u1), IntegracaoAcessoService::APMAIL['nao_liberado']));

    // token retirado do escritorio
    $escritorio(null, null);
    $mailChamadas = 0;
    ok('APMail sem token: icone limpo com o motivo', $bloqueado($envelope($u1), IntegracaoAcessoService::APMAIL['sem_integracao']));
    igual('APMail sem token: nao consulta o APMail', 0, $mailChamadas);
    TTransaction::open('escritorio');
    igual('APMail sem token: botao do historico bloqueado', IntegracaoAcessoService::APMAIL['sem_integracao'], IntegracaoAcessoService::bloqueioApmail($u1));
    TTransaction::close();

    // notificacoes desligadas pelo proprio usuario: so somem do topo; o botao do historico continua
    $escritorio(null, 'tk-apmail-' . $sufixo);
    $mailResposta = $caixasOk;
    TTransaction::open('escritorio');
    ApmailUsuarioService::salvar($u1, 'integracao.um@teste.local', false);
    TTransaction::close();
    ok('APMail com avisos desligados: sem icone', strpos($envelope($u1), 'fast-drop') === false);
    TTransaction::open('escritorio');
    igual('APMail com avisos desligados: o botao do historico continua liberado', null, IntegracaoAcessoService::bloqueioApmail($u1));
    TTransaction::close();
}
finally
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    foreach ($escritorios as $e)
    {
        $c->prepare('UPDATE escritorio SET url_apchat = ?, token_apchat = ?, url_apmail = ?, token_apmail = ? WHERE id = ?')
          ->execute([$e['url_apchat'], $e['token_apchat'], $e['url_apmail'], $e['token_apmail'], $e['id']]);
    }
    foreach ($usuarios as $u) { $c->prepare('UPDATE system_users SET email = ? WHERE id = ?')->execute([$u['email'], $u['id']]); }
    $c->exec('DELETE FROM apchat_usuario');
    foreach ($apchatAntes as $l)
    {
        $c->prepare('INSERT INTO apchat_usuario (' . implode(', ', array_keys($l)) . ') VALUES (' . implode(', ', array_fill(0, count($l), '?')) . ')')->execute(array_values($l));
    }
    $c->exec('DELETE FROM apmail_usuario');
    foreach ($apmailAntes as $l)
    {
        $c->prepare('INSERT INTO apmail_usuario (' . implode(', ', array_keys($l)) . ') VALUES (' . implode(', ', array_fill(0, count($l), '?')) . ')')->execute(array_values($l));
    }
    TTransaction::close();
    ApchatBancoService::usarFalso(null);
    ApchatApiService::forcarSaude(true);
    ApchatApiService::limparSaude();
    ApchatApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso de mensagens.'); });
    ApmailApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso do APMail.'); });
    ApmailEnvelopeService::usarRelogio(null);
    ApchatBalaoService::limparCache();
    ApmailEnvelopeService::limparCache();
}

fim();
