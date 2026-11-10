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
            'contato' => $nome ?: "Cliente {$id}", 'atendente' => $user ? "Atendente {$user}" : null, 'numero' => $numero, 'canal' => 'Oficial', 'fila' => $fila ? "Fila {$fila}" : null,
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
    public $tickets; public $leituras = 0; public $fora = false; public $tentativas = 0; public $acessosLidos = 0; public $impressoes = 0;
    public function __construct($t) { $this->tickets = $t; }
    public function disponivel() { $this->tentativas++; return !$this->fora; }
    // impressao digital dos abertos/pendentes (situacao, atendente, fila, hora): muda quando pegam, transferem ou encerram
    public function impressaoTickets(): string { if ($this->fora) { throw new Exception('fora'); } $this->impressoes++; return md5(json_encode(array_map(function ($t) {
        return [$t['id'], $t['status'], $t['userId'], $t['queueId']]; }, $this->tickets))); }
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
    $agora = time();
    ApchatBalaoService::usarRelogio($agora);
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
    igual('APChat lido uma vez so para todos (cache compartilhado)', 1, $falso->leituras);
    igual('acessos de cada atendente lidos uma vez', 3, $falso->acessosLidos);
    $tentativas = $falso->tentativas;
    $impressoes = $falso->impressoes;
    ApchatBalaoService::conversas($u1);
    ApchatBalaoService::conversas($u2);
    igual('acessos ficam em cache (60 s)', 3, $falso->acessosLidos);
    ok('dentro de 5 s: nenhum pedido vai ao APChat (nem conecta)', $falso->tentativas === $tentativas && $falso->impressoes === $impressoes && $falso->leituras === 1,
        json_encode([$falso->tentativas, $tentativas, $falso->impressoes, $impressoes, $falso->leituras]));

    // a cada 5 s, UMA conferencia para todos: a impressao digital; igual = usa o que ja tem
    ApchatBalaoService::usarRelogio($agora += 6);
    ApchatBalaoService::conversas($u1);
    ApchatBalaoService::conversas($u2);
    igual('5 s depois: uma conferencia so, para todos', $impressoes + 1, $falso->impressoes);
    igual('nada mudou: nao rele os tickets', 1, $falso->leituras);

    // transferir (o ticket 4 vai para o atendente 2): a impressao muda e rele na hora
    $falso->tickets[3]['userId'] = 9002;
    ApchatBalaoService::usarRelogio($agora += 6);
    $rt = ApchatBalaoService::conversas($u1);
    igual('transferido: rele os tickets', 2, $falso->leituras);
    igual('transferido: sai dos abertos do atendente 1', [7], $ids($rt, 'abertos'));
    igual('transferido: entra nos abertos do atendente 2', [3, 4], $ids(ApchatBalaoService::conversas($u2), 'abertos'));

    // encerrar (o ticket 2 sai dos pendentes)
    $fechado = $falso->tickets[1];
    unset($falso->tickets[1]);
    $falso->tickets = array_values($falso->tickets);
    ApchatBalaoService::usarRelogio($agora += 6);
    igual('encerrado: sai dos pendentes', [1, 5], $ids(ApchatBalaoService::conversas($u1)));
    array_splice($falso->tickets, 1, 0, [$fechado]);
    $falso->tickets[3]['userId'] = 9001;

    // campainha do webhook: mensagem nova rele mesmo com a impressao igual (a hora do ticket pode levar um instante)
    ApchatBalaoService::usarRelogio($agora += 6);
    ApchatBalaoService::conversas($u1);
    $lidas = $falso->leituras;
    $c->exec("INSERT INTO apchat_webhook_evento (metodo, ticket_id, de_mim) VALUES ('message', 1, false)");
    ApchatBalaoService::usarRelogio($agora += 6);
    ApchatBalaoService::conversas($u1);
    igual('campainha tocou: rele o APChat', $lidas + 1, $falso->leituras);
    $c->exec("INSERT INTO apchat_webhook_evento (metodo) VALUES ('contact-create-update')");
    ApchatBalaoService::usarRelogio($agora += 6);
    ApchatBalaoService::conversas($u1);
    igual('evento sem ticket (contato) nao faz reler', $lidas + 1, $falso->leituras);

    // rede de seguranca: releitura completa a cada 60 s, mesmo sem mudanca
    ApchatBalaoService::usarRelogio($agora += 61);
    ApchatBalaoService::conversas($u1);
    igual('60 s: releitura completa de seguranca', $lidas + 2, $falso->leituras);

    // outro processo conferindo ao mesmo tempo: este usa o que ja tem, sem consultar o APChat de novo
    ApchatBalaoService::usarRelogio($agora += 6);
    $trava = fopen(ApchatBalaoService::arquivoTrava(), 'c');
    flock($trava, LOCK_EX);
    $impressoes = $falso->impressoes;
    $rl = ApchatBalaoService::conversas($u1);
    flock($trava, LOCK_UN);
    fclose($trava);
    ok('outro processo conferindo: usa o cache, sem consultar e sem erro', $falso->impressoes === $impressoes && ($rl['situacao'] ?? '') === 'ok' && $ids($rl, 'abertos') === [4, 7], json_encode($rl['situacao'] ?? null));
    ok('sem arquivo temporario sobrando', glob(sys_get_temp_dir() . '/curciol-apchat-balao-*.tmp*') === []);

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
    ApchatBalaoService::usarRelogio(null);
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
    ok('balao: logo do APChat (icone do anexo) no topo e contador', strpos($html, 'apchat-logo') !== false && strpos($html, 'app/images/apchat-icone.png') !== false && strpos($html, 'data-count="3"') !== false, substr($html, 0, 400));
    ok('balao: sem texto ao parar o mouse no icone', strpos($html, 'Tickets do APChat esperando') === false && preg_match('/class="button-circle"[^>]*title=/', $html) === 0, substr($html, 0, 400));
    ok('balao: fica aberto ao atravessar ate a lista (classe do JS)', strpos($html, '.fast-drop.apchat-aberto > .fast-drop-open') !== false);
    ok('balao: logo do WhatsApp no ticket (sem fone de ouvido)', strpos($html, 'fab fa-whatsapp') !== false && strpos($html, 'fa-headset') === false);
    ok('card: numero do ticket e tempo desde a ultima mensagem', strpos($html, '>#4<') !== false && preg_match('/apchat-balao-tempo[^>]*>1m</', $html) === 1, $html);
    ok('card: fila, atendente e canal como no APChat', strpos($html, 'apchat-balao-fila') !== false && strpos($html, '>Fila 9101<') !== false
        && strpos($html, 'apchat-balao-atendente') !== false && strpos($html, '>Atendente 9001<') !== false && strpos($html, 'apchat-balao-canal') !== false && strpos($html, '>Oficial<') !== false, $html);
    ok('balao: abre o ticket na aba do APChat', strpos($html, "window.open('{$urlTicket1}', 'apchat')") !== false, $html);
    ok('balao: nome do cliente', strpos($html, 'Cliente A') !== false);
    ok('balao: duas colunas lado a lado, Abertos e Pendentes', strpos($html, 'apchat-balao-colunas') !== false && substr_count($html, 'apchat-balao-coluna"') === 2
        && strpos($html, '>Abertos<') !== false && strpos($html, '>Pendentes<') !== false, $html);
    ok('balao: Abertos antes de Pendentes', strpos($html, '>Abertos<') < strpos($html, '>Pendentes<'));
    ok('balao: icones das abas como no APChat (conversa e relogio)', strpos($html, 'far fa-comment-alt') !== false && strpos($html, 'far fa-clock') !== false && strpos($html, 'far fa-clock') > strpos($html, 'far fa-comment-alt'), $html);
    ok('balao: numero em cima do ticket esperando resposta', preg_match('/apchat-balao-nao-lidas[^>]*>2</', $html) === 1, $html);
    igual('balao: um numero por ticket esperando resposta (1, 2, 4)', 3, substr_count($html, 'apchat-balao-nao-lidas'));
    ok('balao: respondido aparece como Voce:', strpos($html, 'Você: Resposta do atendente') !== false, $html);
    $mail = saida(function () { (new MailList(['theme' => 'theme-builder']))->show(); });
    ok('envelope do APMail: logo e em breve', strpos($mail, 'apmail-logo') !== false && strpos($mail, '<svg') !== false && strpos($mail, 'APMail') !== false, $mail);
    ok('envelope do APMail: sem texto ao parar o mouse', preg_match('/class="button-circle"[^>]*title=/', $mail) === 0, $mail);
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

// JS do topo: nao redesenha o balao com o mouse em cima (a lista sumia antes do clique) e espera 400 ms ao sair
$js = (string) file_get_contents('app/lib/include/builder/theme-builder/BuilderTemplate.js');
ok('JS: balao nao e redesenhado com o mouse em cima', strpos($js, 'apchat-aberto') !== false && strpos($js, 'balaoPendente') !== false);
ok('JS: balao pausa com a aba escondida e atualiza ao voltar', strpos($js, 'document.hidden') !== false && strpos($js, 'visibilitychange') !== false);
ok('JS: versao nova do arquivo no layout (sem cache)', strpos((string) file_get_contents('app/templates/theme-builder/libraries_builder.html'), 'BuilderTemplate.js?appver=523984') !== false);

fim();
