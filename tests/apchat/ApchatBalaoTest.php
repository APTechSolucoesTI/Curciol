<?php
// tests/apchat/ApchatBalaoTest.php
// Balao do APChat no topo (10/11/2026), como as abas do APChat: Abertos (todos os tickets abertos com o atendente) e Pendentes
// (quem tem acesso ao canal e, se houver, a fila). Ticket cuja ultima mensagem e do cliente ganha o numero de mensagens sem
// resposta; o icone conta TICKETS esperando resposta. Pendente aguardando o SIM/NAO da Curciol fica de fora (ela mesma responde). Cache curto; APChat fora = balao vazio. Envelope do APMail reservado.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }

$t = function (int $id, string $status, ?int $user, ?int $fila, bool $nossa, string $numero, string $nome = '') {
    return ['id' => $id, 'status' => $status, 'contactId' => $id + 1000, 'userId' => $user, 'queueId' => $fila, 'whatsappId' => 112,
            'contato' => $nome ?: "Cliente {$id}", 'numero' => $numero, 'canal' => 'Oficial', 'fila' => $fila ? "Fila {$fila}" : null,
            'ultima_nossa' => $nossa, 'nao_respondidas' => $nossa ? 0 : ($id === 1 ? 2 : 1), 'ultima_texto' => $nossa ? "*Atendente Um*:
 Resposta do atendente" : "Mensagem do cliente {$id}",
            'ultima_em' => date('Y-m-d H:i:s', strtotime("-{$id} minutes")), 'lastMessageAt' => date('Y-m-d H:i:s')];
};
$falso = new class([
    $t(1, 'pending', null, 9101, false, '5519900000001', 'Cliente A'),
    $t(2, 'pending', null, null, false, '5519900000002'),
    $t(3, 'open', 9002, 9101, false, '5519900000003'),
    $t(4, 'open', 9001, 9102, false, '5519900000004'),
    $t(5, 'pending', null, 9101, true, '5519900000005'),
    $t(6, 'pending', null, 9101, false, '5519900000006'),
    $t(7, 'open', 9001, 9101, true, '5519900000007'),
]) {
    public $tickets; public $leituras = 0; public $fora = false; public $tentativas = 0; public $acessosLidos = 0;
    public function __construct($t) { $this->tickets = $t; }
    public function disponivel() { $this->tentativas++; return !$this->fora; }
    public function ticketsAbertosComUltima(): array { if ($this->fora) { throw new Exception('fora'); } $this->leituras++; return $this->tickets; }
    public function acessos(int $atendente): array {
        $this->acessosLidos++;
        $a = [9001 => ['canais' => [112], 'filas' => [9101, 9102]], 9002 => ['canais' => [112], 'filas' => [9101]], 9003 => ['canais' => [], 'filas' => [9101]]];
        return $a[$atendente] ?? ['canais' => [], 'filas' => []];
    }
};
ApchatBancoService::usarFalso($falso);

TTransaction::open('escritorio');
try
{
    $c = TTransaction::get();
    $us = array_map('intval', array_column($c->query("SELECT id FROM system_users WHERE active = 'Y' ORDER BY id LIMIT 4")->fetchAll(PDO::FETCH_ASSOC), 'id'));
    [$u1, $u2, $u3, $u4] = $us;
    $c->exec('DELETE FROM apchat_usuario');
    ApchatUsuarioService::ligar($u1, 9001);
    ApchatUsuarioService::ligar($u2, 9002);
    ApchatUsuarioService::ligar($u3, 9003);
    // numero 6 aguardando o SIM/NAO de uma confirmacao da Curciol
    $tpl = (int) $c->query("SELECT id FROM template_escritorio WHERE categoria = 'CONFIRMACAO' AND tipo_template = 'WHATSAPP' AND COALESCE(btrim(respostas_confirmar), '') <> '' ORDER BY id LIMIT 1")->fetchColumn();
    $ag = (int) $c->query('SELECT id FROM agendamento ORDER BY id DESC LIMIT 1')->fetchColumn();
    $c->exec("INSERT INTO mensagem (agendamento_id, template_escritorio_id, system_user_id, tipo_mensagem, destino, template, enviado_em) VALUES ({$ag}, {$tpl}, {$u1}, 'WHATSAPP', '19900000006', 'Confirma?', now())");

    ApchatBalaoService::limparCache();
    $ids = function (array $r, string $lista = 'pendentes') { return array_map(function ($i) { return (int) $i['ticket_id']; }, $r[$lista] ?? []); };

    $r1 = ApchatBalaoService::conversas($u1);
    igual('atendente 1: pendentes do canal/fila dele (inclusive o respondido; sem o do SIM/NAO)', [1, 2, 5], $ids($r1), json_encode($r1));
    igual('atendente 1: abertos com ele, respondidos ou nao', [4, 7], $ids($r1, 'abertos'));
    igual('atendente 1: icone conta os TICKETS esperando resposta (1, 2 e 4)', 3, $r1['total'] ?? null);
    $porId = [];
    foreach (array_merge($r1['pendentes'], $r1['abertos']) as $i) { $porId[$i['ticket_id']] = $i; }
    ok('esperando resposta: numero de mensagens do cliente sem resposta', $porId[1]['aguardando'] === true && $porId[1]['nao_lidas'] === 2 && $porId[4]['nao_lidas'] === 1, json_encode($porId[1]));
    ok('respondido: sem numero, marcado como nosso, sem a assinatura do APChat', $porId[7]['aguardando'] === false && $porId[7]['nao_lidas'] === 0
        && $porId[7]['nossa'] === true && $porId[7]['ultima'] === 'Resposta do atendente', json_encode($porId[7]));
    ok('pendente respondido: sem numero', $porId[5]['aguardando'] === false && $porId[5]['nao_lidas'] === 0);
    $r2 = ApchatBalaoService::conversas($u2);
    igual('atendente 2: pendentes da fila dele e sem fila', [1, 2, 5], $ids($r2));
    igual('atendente 2: o aberto dele', [3], $ids($r2, 'abertos'));
    igual('atendente 2: total', 3, $r2['total']);
    $r3 = ApchatBalaoService::conversas($u3);
    igual('atendente sem o canal: nada', [[], [], 0], [$ids($r3), $ids($r3, 'abertos'), $r3['total']]);
    igual('usuario sem atendente ligado', 'sem_ligacao', ApchatBalaoService::conversas($u4)['situacao'] ?? null);
    igual('APChat lido uma vez so (cache)', 1, $falso->leituras);
    igual('acessos de cada atendente lidos uma vez', 3, $falso->acessosLidos);
    ApchatBalaoService::conversas($u1);
    igual('acessos ficam em cache (60 s)', 3, $falso->acessosLidos);

    // campainha (webhook): mensagem nova no APChat -> o balao rele na proxima atualizacao, mesmo com o cache novo
    $c->exec('DELETE FROM apchat_webhook_evento');
    igual('sem webhook nas ultimas 24 h: rele o APChat a cada 10 s', 10, ApchatBalaoService::cacheSegundos());
    $c->exec("INSERT INTO apchat_webhook_evento (metodo, ticket_id, de_mim) VALUES ('message', 1, false)");
    igual('com webhook: rele a cada 60 s (encerrar, pegar e transferir nao tocam a campainha)', 60, ApchatBalaoService::cacheSegundos());
    $lidas = $falso->leituras;
    ApchatBalaoService::conversas($u1);
    igual('campainha tocou: rele o APChat', $lidas + 1, $falso->leituras);
    ApchatBalaoService::conversas($u2);
    igual('sem campainha nova: usa o cache', $lidas + 1, $falso->leituras);
    $c->exec("INSERT INTO apchat_webhook_evento (metodo) VALUES ('contact-create-update')");
    ApchatBalaoService::conversas($u1);
    igual('evento sem ticket (contato) nao faz reler', $lidas + 1, $falso->leituras);
    igual('intervalo do balao: 5 s', '5000', ApchatBalaoService::intervaloMs(['general' => ['verify_messages_menu' => '15000']]));
    igual('intervalo do balao desligado no ini continua desligado', 'false', ApchatBalaoService::intervaloMs(['general' => []]));

    $item = $r1['pendentes'][0];
    ok('item: contato, canal, fila e ultima mensagem', $item['contato'] === 'Cliente A' && $item['canal'] === 'Oficial' && $item['fila'] === 'Fila 9101' && $item['ultima'] === 'Mensagem do cliente 1', json_encode($item));
    igual('item: endereco do ticket no APChat', ApchatApiService::urlTicket(1), $item['url']);

    ApchatUsuarioService::preferencias($u1, false, false);
    $rd = ApchatBalaoService::conversas($u1);
    ok('avisos desligados: vazio', ($rd['situacao'] ?? '') === 'desligado' && $rd['pendentes'] === [] && $rd['abertos'] === []);
    ApchatUsuarioService::preferencias($u1, true, false);

    $esc = Escritorio::find((int) $c->query('SELECT id FROM escritorio ORDER BY id LIMIT 1')->fetchColumn());
    $esc->url_tela_apchat = 'https://chat.exemplo.com.br/';
    $esc->store();
    igual('endereco da tela do APChat configuravel', 'https://chat.exemplo.com.br/atendimento?ticketId=9', ApchatApiService::urlTicket(9));

    // APChat fora: vazio, sem erro
    $falso->fora = true;
    ApchatBalaoService::limparCache();
    $rf = ApchatBalaoService::conversas($u1);
    ok('APChat fora: balao vazio, sem excecao', ($rf['situacao'] ?? '') === 'erro' && $rf['pendentes'] === [] && $rf['abertos'] === []);
    // APChat fora: nao tenta conectar de novo a cada atualizacao (cada tentativa espera ate 5 s); tenta de novo depois de 60 s
    $falso->tentativas = 0;
    ApchatBalaoService::conversas($u1);
    ApchatBalaoService::conversas($u2);
    igual('APChat fora: nao tenta de novo logo em seguida', 0, $falso->tentativas);
}
finally
{
    TTransaction::rollback();
    ApchatBancoService::usarFalso(null);
    ApchatBalaoService::limparCache();
}

// ---------- telas (o balao abre a propria transacao: dados gravados de verdade e desfeitos no fim) ----------
$falso->fora = false;
ApchatBancoService::usarFalso($falso);
TTransaction::open('escritorio');
$c = TTransaction::get();
$u1 = (int) $c->query("SELECT id FROM system_users WHERE active = 'Y' ORDER BY id LIMIT 1")->fetchColumn();
$antes = $c->query("SELECT * FROM apchat_usuario WHERE system_user_id = {$u1}")->fetch(PDO::FETCH_ASSOC) ?: null;
$outro = $c->query("SELECT * FROM apchat_usuario WHERE apchat_usuario_id = 9001 AND system_user_id <> {$u1}")->fetch(PDO::FETCH_ASSOC) ?: null;
$c->exec("DELETE FROM apchat_usuario WHERE system_user_id = {$u1} OR apchat_usuario_id = 9001");
$c->exec("INSERT INTO apchat_usuario (system_user_id, apchat_usuario_id, avisos, mostrar_abertos) VALUES ({$u1}, 9001, 'S', 'N')");
$tpl = (int) $c->query("SELECT id FROM template_escritorio WHERE categoria = 'CONFIRMACAO' AND tipo_template = 'WHATSAPP' AND COALESCE(btrim(respostas_confirmar), '') <> '' ORDER BY id LIMIT 1")->fetchColumn();
$ag = (int) $c->query('SELECT id FROM agendamento ORDER BY id DESC LIMIT 1')->fetchColumn();
$msg = (int) $c->query("INSERT INTO mensagem (agendamento_id, template_escritorio_id, system_user_id, tipo_mensagem, destino, template, enviado_em) VALUES ({$ag}, {$tpl}, {$u1}, 'WHATSAPP', '19900000006', 'Confirma?', now()) RETURNING id")->fetchColumn();
$unidade = (int) $c->query('SELECT system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetchColumn();
$urlTicket1 = ApchatApiService::urlTicket(1);
TTransaction::close();
try
{
    TSession::setValue('userid', $u1);
    TSession::setValue('userunitid', $unidade);
    ApchatBalaoService::limparCache();
    $html = saida(function () { (new MessageList(['theme' => 'theme-builder']))->show(); });
    ok('balao: icone de conversa e contador', strpos($html, 'fa-comments') !== false && strpos($html, 'data-count="3"') !== false, substr($html, 0, 400));
    ok('balao: abre o ticket na aba do APChat', strpos($html, "window.open('{$urlTicket1}', 'apchat')") !== false, $html);
    ok('balao: nome do cliente', strpos($html, 'Cliente A') !== false);
    ok('balao: duas colunas lado a lado, Abertos e Pendentes', strpos($html, 'apchat-balao-colunas') !== false && substr_count($html, 'apchat-balao-coluna"') === 2
        && strpos($html, '>Abertos<') !== false && strpos($html, '>Pendentes<') !== false, $html);
    ok('balao: Abertos antes de Pendentes', strpos($html, '>Abertos<') < strpos($html, '>Pendentes<'));
    ok('balao: numero em cima do ticket esperando resposta', preg_match('/apchat-balao-nao-lidas[^>]*>2</', $html) === 1, $html);
    igual('balao: um numero por ticket esperando resposta (1, 2, 4)', 3, substr_count($html, 'apchat-balao-nao-lidas'));
    ok('balao: respondido aparece como Voce:', strpos($html, 'Você: Resposta do atendente') !== false, $html);
    $mail = saida(function () { (new MailList(['theme' => 'theme-builder']))->show(); });
    ok('envelope do APMail: em breve', strpos($mail, 'fa-envelope') !== false && strpos($mail, 'APMail') !== false, $mail);
}
finally
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $c->exec("DELETE FROM mensagem WHERE id = {$msg}");
    $c->exec("DELETE FROM apchat_usuario WHERE system_user_id = {$u1}");
    foreach ([$antes, $outro] as $linha)
    {
        if ($linha)
        {
            $c->prepare('INSERT INTO apchat_usuario (system_user_id, apchat_usuario_id, avisos, mostrar_abertos) VALUES (?, ?, ?, ?)')
              ->execute([$linha['system_user_id'], $linha['apchat_usuario_id'], $linha['avisos'], $linha['mostrar_abertos']]);
        }
    }
    TTransaction::close();
    ApchatBancoService::usarFalso(null);
    ApchatBalaoService::limparCache();
}

fim();
