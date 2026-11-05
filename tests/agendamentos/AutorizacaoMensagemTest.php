<?php
// tests/agendamentos/AutorizacaoMensagemTest.php
// 05/11/2026, LGPD: sem "Autoriza receber mensagens pelo WhatsApp/E-mail?" os botoes de envio do agendamento ficam bloqueados, com
// aviso, e nada sai, nem lembrete ja agendado. NUNCA fala com o APChat de verdade nem manda e-mail: API falsa.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_AUT = 'TESTE AUTORIZACAO MENSAGEM';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string { ob_start(); $f(); return ob_get_clean(); }

function limparAut(): void
{
    $ags = array_column(q("SELECT a.id FROM agendamento a JOIN pessoa p ON p.id = a.cliente_id WHERE p.nome LIKE '" . MARCA_AUT . "%'"), 'id');
    $lista = $ags ? implode(',', array_map('intval', $ags)) : '0';
    x("DELETE FROM mensagem_agendada WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM mensagem WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM estado_agendamento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM agendamento WHERE id IN ({$lista})");
    x("DELETE FROM pessoa WHERE nome LIKE '" . MARCA_AUT . "%'");
}
// segura a trava: o cron real da homologacao nao pode pegar o que o teste cria
$travaCron = fopen(MensagemAgendadaService::arquivoDaTrava(), 'c');
flock($travaCron, LOCK_EX);
limparAut();

$enviadas = [];
ApchatApiService::usarTransporte(function ($url, $corpo, $token) use (&$enviadas) {
    $enviadas[] = $corpo;
    return ['status' => 201, 'corpo' => '{"success":true}', 'erro' => ''];
});
AmbienteService::forcarProducao(false);
TSession::setValue('userid', 1);
$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);
$agendaId = (int) q("SELECT a.id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id WHERE e.system_unit_id = {$unidade} ORDER BY a.id LIMIT 1")[0]['id'];

TTransaction::open('escritorio');
$st = TTransaction::get()->prepare("INSERT INTO pessoa (nome, tipo_pessoa_id, aceita_receber_mensagen_whatsapp) VALUES (?, 1, 'F') RETURNING id");
$st->execute([MARCA_AUT]);
$cliente = (int) $st->fetchColumn();
$st = TTransaction::get()->prepare('INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES (?, ?, ?, ?, ?) RETURNING id');
$st->execute([$cliente, EstadoAgenda::CONFIRMADO, $agendaId, date('Y-m-d H:i:00', strtotime('+3 days')), date('Y-m-d H:i:00', strtotime('+3 days 30 minutes'))]);
$ag = (int) $st->fetchColumn();
TTransaction::close();

$tela = function () use ($ag) { return saida(function () use ($ag) { (new AgendamentoFormView(['key' => $ag]))->show(); }); };
$botao = function (string $html, string $nome) { preg_match('~<button[^>]*name="' . $nome . '"[^>]*>~', $html, $m); return $m[0] ?? ''; };
$tentar = function (callable $f) { TTransaction::open('escritorio'); try { $f(Agendamento::find($GLOBALS['ag'])); TTransaction::close(); return null; } catch (Exception $e) { TTransaction::rollback(); return $e->getMessage(); } };
$autorizacao = fn(string $v) => x("UPDATE pessoa SET aceita_receber_mensagen_whatsapp = '{$v}' WHERE id = {$cliente}");
$quando = date('d/m/Y H:i', strtotime('+1 day'));
$numero = '5519912340050';

try {
    // ---------- sem autorizacao ----------
    $html = $tela();
    ok('tela: botao de WhatsApp bloqueado', strpos($botao($html, 'enviar_whatsapp'), 'disabled') !== false, $botao($html, 'enviar_whatsapp'));
    ok('tela: botao de e-mail bloqueado', strpos($botao($html, 'enviar_email'), 'disabled') !== false, $botao($html, 'enviar_email'));
    ok('tela: botoes sem acao', strpos($botao($html, 'enviar_whatsapp'), 'EnviarMensagemForm') === false && strpos($botao($html, 'enviar_email'), 'EnviarMensagemForm') === false);
    ok('tela: aviso curto', strpos($html, 'O cliente não autorizou receber mensagens.') !== false && strpos($html, 'Para enviar, clique') === false);
    ok('tela: botao Visualizar cliente continua no topo', strpos($html, 'Visualizar cliente') !== false);

    ok('WhatsApp barrado no servidor', strpos((string) $tentar(fn($a) => MensagemService::enviarWhatsapp($a, null, 'oi', null, $numero)), 'não autorizou') !== false);
    ok('e-mail barrado no servidor', strpos((string) $tentar(fn($a) => MensagemService::enviarEmail($a, 'T', 'oi', null, 'teste@example.invalid')), 'não autorizou') !== false);
    ok('agendar lembrete barrado', strpos((string) $tentar(fn($a) => MensagemAgendadaService::agendar($a, 'WHATSAPP', null, 'oi', null, $numero, $quando)), 'não autorizou') !== false);
    igual('nada enviado nem registrado', [0, 0, 0], [count($enviadas), count(q("SELECT 1 FROM mensagem WHERE agendamento_id = {$ag}")), count(q("SELECT 1 FROM mensagem_agendada WHERE agendamento_id = {$ag}"))]);

    // ---------- ativou no cadastro: volta liberado ----------
    $autorizacao('T');
    $html = $tela();
    ok('tela: WhatsApp liberado', strpos($botao($html, 'enviar_whatsapp'), 'disabled') === false && strpos($botao($html, 'enviar_whatsapp'), 'EnviarMensagemForm') !== false);
    ok('tela: e-mail liberado', strpos($botao($html, 'enviar_email'), 'disabled') === false && strpos($botao($html, 'enviar_email'), 'EnviarMensagemForm') !== false);
    ok('tela: sem aviso', strpos($html, 'O cliente não autorizou') === false);
    igual('WhatsApp sai', null, $tentar(fn($a) => MensagemService::enviarWhatsapp($a, null, 'oi', null, $numero)));
    igual('WhatsApp: passou pela API', 1, count($enviadas));

    // ---------- lembrete ja agendado: tirou a autorizacao, nao sai ----------
    $enviadas = [];
    igual('agenda o lembrete', null, $tentar(fn($a) => MensagemAgendadaService::agendar($a, 'WHATSAPP', null, 'lembrete', null, $numero, $quando)));
    x("UPDATE mensagem_agendada SET enviar_em = '" . date('Y-m-d H:i:s', strtotime('-1 minute')) . "' WHERE agendamento_id = {$ag}");
    $autorizacao('F');
    saida(function () { MensagemAgendadaService::rodada(); });
    igual('lembrete nao sai', 0, count($enviadas));
    $l = q("SELECT situacao, motivo FROM mensagem_agendada WHERE agendamento_id = {$ag}")[0];
    igual('lembrete cancelado com o motivo', [MensagemAgendada::CANCELADA, 'O cliente tirou a autorização'], [$l['situacao'], $l['motivo']]);

    // ---------- cadastro do cliente ----------
    $form = file_get_contents('app/control/pessoas/ClienteForm.php');
    ok('cadastro: titulo novo', strpos($form, 'Autoriza receber mensagens pelo WhatsApp/E-mail?') !== false);
    ok('cadastro: explicacao nova, sem o resto', strpos($form, 'Lembretes, confirmações de agendamento e atendimento via WhatsApp ou e-mail"') !== false && strpos($form, 'vai para o APChat sempre') === false);
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    limparAut();
    ApchatApiService::usarTransporte(null);
    AmbienteService::forcarProducao(null);
    flock($travaCron, LOCK_UN);
    fclose($travaCron);
}

fim();
