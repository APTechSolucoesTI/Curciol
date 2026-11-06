<?php
// tests/agendamentos/MensagemEstadoTest.php
// 06/11/2026: Nao compareceu so a partir de Confirmado; mensagens por estado (Agendado: Confirmacao; Confirmado: Lembrete; o resto
// nao envia), inclusive POST montado; cada lembrete no historico e na tela; em producao, destino so do proprio cliente; o cadastro
// novo volta selecionado na conta. API falsa: nunca fala com o APChat de verdade.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_ME = 'TESTE MENSAGEM ESTADO';
const NUM_CLIENTE = '19912340101';
const NUM_OUTRO = '19912340102';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string
{
    ob_start();
    try { $f(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function limparME(): void
{
    $ags = array_column(q("SELECT a.id FROM agendamento a JOIN pessoa p ON p.id = a.cliente_id WHERE p.nome LIKE '" . MARCA_ME . "%'"), 'id');
    $lista = $ags ? implode(',', array_map('intval', $ags)) : '0';
    x("DELETE FROM mensagem_agendada WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM mensagem WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM atendimento_procedimento WHERE atendimento_id IN (SELECT id FROM atendimento WHERE agendamento_id IN ({$lista}))");
    x("DELETE FROM atendimento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM estado_agendamento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM agendamento WHERE id IN ({$lista})");
    x("DELETE FROM pessoa_contato WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_ME . "%')");
    x("DELETE FROM pessoa WHERE nome LIKE '" . MARCA_ME . "%'");
}
$travaCron = fopen(MensagemAgendadaService::arquivoDaTrava(), 'c');
flock($travaCron, LOCK_EX);
limparME();

$enviadas = [];
ApchatApiService::usarTransporte(function ($url, $corpo, $token) use (&$enviadas) { $enviadas[] = $corpo; return ['status' => 201, 'corpo' => '{"success":true}', 'erro' => '']; });
AmbienteService::forcarProducao(false);
TSession::setValue('userid', 1);
$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);
$agendaId = (int) q("SELECT a.id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id WHERE e.system_unit_id = {$unidade} ORDER BY a.id LIMIT 1")[0]['id'];
$tpl = function (string $categoria) use ($unidade) {
    return (int) q("SELECT t.id FROM template_escritorio t JOIN escritorio e ON e.id = t.escritorio_id WHERE e.system_unit_id = {$unidade} AND t.tipo_template = 'WHATSAPP' AND t.categoria = '{$categoria}' AND t.habilitado = 'T' ORDER BY t.id LIMIT 1")[0]['id'];
};
$confirmacao = $tpl('CONFIRMACAO');
$lembreteT = $tpl('LEMBRETE');

TTransaction::open('escritorio');
$st = TTransaction::get()->prepare("INSERT INTO pessoa (nome, tipo_pessoa_id, telefone, email, aceita_receber_mensagen_whatsapp) VALUES (?, 1, ?, ?, 'T') RETURNING id");
$st->execute([MARCA_ME, NUM_CLIENTE, 'cliente.me@example.invalid']);
$cliente = (int) $st->fetchColumn();
TTransaction::get()->prepare("INSERT INTO pessoa_contato (pessoa_id, nome, telefone, email, principal) VALUES (?, ?, ?, ?, 'S')")->execute([$cliente, MARCA_ME, NUM_CLIENTE, 'cliente.me@example.invalid']);
$contato = (int) TTransaction::get()->query("SELECT id FROM pessoa_contato WHERE pessoa_id = {$cliente}")->fetchColumn();
$st = TTransaction::get()->prepare('INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES (?, ?, ?, ?, ?) RETURNING id');
$novo = function ($estado) use ($st, $cliente, $agendaId) { $st->execute([$cliente, $estado, $agendaId, date('Y-m-d H:i:00', strtotime('+5 days')), date('Y-m-d H:i:00', strtotime('+5 days 30 minutes'))]); return (int) $st->fetchColumn(); };
$ag = $novo(EstadoAgenda::AGENDADO);
TTransaction::close();
$estado = fn($e) => x("UPDATE agendamento SET estado_agenda_id = {$e} WHERE id = {$ag}");
$botao = function (string $html, string $nome) { preg_match('~<button[^>]*name="' . $nome . '"[^>]*>~', $html, $m); return $m[0] ?? ''; };
$tela = function () use ($ag) { return saida(function () use ($ag) { (new AgendamentoFormView(['key' => $ag]))->show(); }); };
$enviar = function (array $p) use ($ag) {
    $p += ['agendamento_id' => $ag, 'tipo' => 'WHATSAPP', 'destino' => NUM_OUTRO];
    return saida(function () use ($p) { EnviarMensagemForm::onSend($p); });
};
$erroDe = function (string $s) { return preg_match("/__adianti_error\\(['\"][^'\"]*['\"],\\s*['\"]([^'\"]+)/", $s, $m) ? $m[1] : ''; };
$historico = fn() => array_column(q("SELECT evento FROM estado_agendamento WHERE agendamento_id = {$ag} AND evento LIKE 'LEMBRETE%' ORDER BY id"), 'evento');
$ainda = fn() => (int) q("SELECT estado_agenda_id e FROM agendamento WHERE id = {$ag}")[0]['e'];

try {
    // ---------- botoes e templates por estado ----------
    foreach ([EstadoAgenda::AGENDADO => 'Agendado', EstadoAgenda::CONFIRMADO => 'Confirmado'] as $e => $nome)
    {
        $estado($e);
        $html = $tela();
        ok("{$nome}: botoes liberados", strpos($botao($html, 'enviar_whatsapp'), 'disabled') === false && strpos($botao($html, 'enviar_email'), 'disabled') === false);
    }
    foreach ([EstadoAgenda::CANCELADO => 'Cancelado', EstadoAgenda::NAO_COMPARECEU => 'Nao compareceu', EstadoAgenda::ATENDIDO => 'Atendido', EstadoAgenda::EM_ATENDIMENTO => 'Em atendimento'] as $e => $nome)
    {
        $estado($e);
        $html = $tela();
        ok("{$nome}: botoes bloqueados", strpos($botao($html, 'enviar_whatsapp'), 'disabled') !== false && strpos($botao($html, 'enviar_email'), 'disabled') !== false, $botao($html, 'enviar_whatsapp'));
        $enviadas = [];
        $s = $enviar(['template' => $confirmacao]);
        ok("{$nome}: POST direto nao envia", $enviadas === [] && strpos($erroDe($s), 'Agendado ou Confirmado') !== false, $erroDe($s));
    }

    // ---------- Agendado so Confirmacao ----------
    $estado(EstadoAgenda::AGENDADO);
    $enviadas = [];
    $s = $enviar(['template' => $lembreteT]);
    ok('Agendado: template de Lembrete recusado', $enviadas === [], $erroDe($s));
    $s = $enviar(['template' => '', 'manual' => 'S', 'content_whatsapp' => 'Oi']);
    ok('Agendado: avulso sem template recusado', $enviadas === [], $erroDe($s));
    $s = $enviar(['template' => $confirmacao, 'manual' => 'S', 'content_whatsapp' => 'Bom dia, tudo bem?']);
    ok('Agendado: Manual sem pedir resposta recusado', $enviadas === [] && strpos($erroDe($s), 'respostas do template') !== false, $erroDe($s));
    $s = $enviar(['template' => $confirmacao, 'manual' => 'S', 'content_whatsapp' => 'Responda SIM ou NAO']);
    igual('Agendado: Manual pedindo resposta sai', 1, count($enviadas), $erroDe($s));
    $enviadas = [];
    $s = $enviar(['template' => $confirmacao]);
    igual('Agendado: template de Confirmacao sai', 1, count($enviadas), $erroDe($s));
    igual('Confirmacao nao entra no historico de lembrete', [], $historico());
    $htmlEnvio = saida(function () use ($ag) { $p = ['agendamento_id' => $ag, 'tipo' => 'WHATSAPP']; $f = new EnviarMensagemForm($p); $f->onShow($p); $f->show(); });
    ok('tela de envio: sem a opcao de escrever do zero', strpos($htmlEnvio, 'escrever do zero') === false);

    // ---------- Confirmado so Lembrete ----------
    $estado(EstadoAgenda::CONFIRMADO);
    $enviadas = [];
    $s = $enviar(['template' => $confirmacao]);
    ok('Confirmado: template de Confirmacao recusado', $enviadas === [], $erroDe($s));
    $s = $enviar(['template' => $lembreteT]);
    igual('Confirmado: lembrete na hora sai', 1, count($enviadas), $erroDe($s));
    $s = $enviar(['template' => $lembreteT]);
    igual('Confirmado: botao continua, segundo lembrete sai', 2, count($enviadas), $erroDe($s));
    igual('lembrete na hora: uma linha enviada no historico para cada', ['LEMBRETE_ENVIADO', 'LEMBRETE_ENVIADO'], $historico());
    $html = $tela();
    igual('tela: mostra os dois lembretes enviados', 2, substr_count($html, 'Lembrete por WhatsApp enviado em'));
    ok('historico: rotulo do lembrete', strpos($html, 'Lembrete enviado') !== false);

    // agendado: registros distintos e cron com o usuario responsavel
    $quando = date('d/m/Y H:i', strtotime('+1 day'));
    $s = $enviar(['template' => $lembreteT, 'agendar' => 'S', 'enviar_em' => $quando]);
    igual('agendar: nao envia na hora', 2, count($enviadas), $erroDe($s));
    igual('agendar: linha de agendado no historico', 'LEMBRETE_AGENDADO', end($hist = $historico()) ?: '');
    x("UPDATE mensagem_agendada SET enviar_em = '" . date('Y-m-d H:i:s', strtotime('-1 minute')) . "' WHERE agendamento_id = {$ag}");
    TSession::setValue('userid', null);
    saida(function () { MensagemAgendadaService::rodada(); });
    TSession::setValue('userid', 1);
    igual('cron: enviou', 3, count($enviadas));
    igual('cron: enviado no historico, com o usuario responsavel', [1], array_map('intval', array_column(q("SELECT system_users_id u FROM estado_agendamento WHERE agendamento_id = {$ag} AND evento = 'LEMBRETE_ENVIADO' ORDER BY id DESC LIMIT 1"), 'u')));
    saida(function () { MensagemAgendadaService::rodada(); });
    igual('cron: outra rodada nao repete', 3, count($enviadas));
    igual('tela: agendado enviado nao aparece em dobro', 3, substr_count($tela(), 'Lembrete por WhatsApp enviado em'));

    // ---------- estado mudou depois de abrir ----------
    $estado(EstadoAgenda::CANCELADO);
    $enviadas = [];
    $s = $enviar(['template' => $lembreteT]);
    ok('estado mudou depois de abrir: nao envia', $enviadas === [], $erroDe($s));

    // ---------- nao compareceu ----------
    $falta = function () use ($ag) { return saida(function () use ($ag) { AgendamentoFormView::onNaoCompareceu(['key' => $ag]); }); };
    $htmlBotao = function () use ($tela) { preg_match('~<a[^>]*method=onNaoCompareceu[^>]*>~', $tela(), $m); return $m[0] ?? ''; };
    foreach ([EstadoAgenda::AGENDADO => 'Agendado', EstadoAgenda::CANCELADO => 'Cancelado', EstadoAgenda::EM_ATENDIMENTO => 'Em atendimento'] as $e => $nome)
    {
        $estado($e);
        ok("{$nome}: botao Nao compareceu escondido", strpos($htmlBotao(), 'display: none') !== false, $htmlBotao());
        $s = $falta();
        igual("{$nome}: falta recusada no servidor", (int) $e, $ainda(), $erroDe($s));
    }
    $estado(EstadoAgenda::CONFIRMADO);
    ok('Confirmado: botao Nao compareceu aparece', $htmlBotao() !== '' && strpos($htmlBotao(), 'display: none') === false, $htmlBotao());
    $s = $enviar(['template' => $lembreteT, 'agendar' => 'S', 'enviar_em' => $quando]);
    $f = $falta();
    igual('Confirmado: marca falta', (int) EstadoAgenda::NAO_COMPARECEU, $ainda(), $erroDe($f));
    igual('falta: lembrete pendente cancelado', [MensagemAgendada::CANCELADA], array_column(q("SELECT situacao FROM mensagem_agendada WHERE agendamento_id = {$ag} ORDER BY id DESC LIMIT 1"), 'situacao'));
    $linhas = fn() => (int) q("SELECT count(*) c FROM estado_agendamento WHERE agendamento_id = {$ag} AND estado_agenda_id = " . EstadoAgenda::NAO_COMPARECEU . " AND evento IS NULL")[0]['c'];
    igual('falta: uma linha no historico', 1, $linhas());
    $falta();
    igual('falta: clique repetido nao duplica', 1, $linhas());

    // ---------- producao: destino so do proprio cliente ----------
    AmbienteService::forcarProducao(true);
    $ag2 = (function () use ($novo) { TTransaction::open('escritorio'); $id = $novo(EstadoAgenda::AGENDADO); TTransaction::close(); return $id; })();
    $enviadas = [];
    $tentar = function (callable $f) use ($ag2) { TTransaction::open('escritorio'); try { $f(Agendamento::find($ag2)); TTransaction::close(); return ''; } catch (Exception $e) { TTransaction::rollback(); return $e->getMessage(); } };
    ok('producao: WhatsApp para numero de fora recusado', strpos($tentar(fn($a) => MensagemService::destinoWhatsapp($a, NUM_OUTRO)), 'contato deste cliente') !== false);
    igual('producao: numero do cliente aceito', '', $tentar(fn($a) => MensagemService::destinoWhatsapp($a, NUM_CLIENTE)));
    ok('producao: e-mail de fora recusado', strpos($tentar(fn($a) => MensagemService::destinoEmail($a, 'outro@example.invalid')), 'contato deste cliente') !== false);
    igual('producao: e-mail do cliente aceito', '', $tentar(fn($a) => MensagemService::destinoEmail($a, 'cliente.me@example.invalid')));
    $s = $enviar(['agendamento_id' => $ag2, 'template' => $confirmacao, 'contato' => '999999999', 'destino' => NUM_OUTRO]);
    ok('producao: POST com contato de fora nao envia', $enviadas === [], $erroDe($s));
    $s = $enviar(['agendamento_id' => $ag2, 'template' => $confirmacao, 'destino' => NUM_OUTRO]);
    ok('producao: POST sem contato, com numero digitado, nao envia', $enviadas === [], $erroDe($s));
    $s = $enviar(['agendamento_id' => $ag2, 'template' => $confirmacao, 'contato' => (string) $contato]);
    igual('producao: contato do cliente envia', 1, count($enviadas), $erroDe($s));
    AmbienteService::forcarProducao(false);

    // ---------- cadastro novo volta selecionado na conta ----------
    $s = saida(function () use ($cliente) { Navegacao::continuarNoRegistro('ClienteForm', $cliente, ['fechar_ao_salvar' => '1', 'retorno_pessoa' => 'form_Conta']); });
    ok('conta: fecha e seleciona a pessoa nova', strpos($s, 'Template.closeRightPanel()') !== false && strpos($s, 'form[name=\\"form_Conta\\"]') !== false || strpos($s, 'form[name="form_Conta"]') !== false, substr($s, 0, 300));
    ok('conta: leva o id e o nome', strpos($s, "'{$cliente}'") !== false && strpos($s, MARCA_ME) !== false, substr($s, 0, 400));
    $s = saida(function () use ($cliente) { Navegacao::continuarNoRegistro('ClienteForm', $cliente, ['fechar_ao_salvar' => '1']); });
    ok('sem conta: so fecha', strpos($s, 'pessoa_id') === false);
    $s = saida(function () use ($cliente) { Navegacao::continuarNoRegistro('ClienteForm', $cliente, ['fechar_ao_salvar' => '1', 'retorno_pessoa' => "x');alert('1"]); });
    ok('retorno invalido e ignorado', strpos($s, 'alert') === false && strpos($s, 'pessoa_id') === false);

    // ---------- permissoes dos contatos sem vinculo ----------
    $grupos = array_column(q("SELECT DISTINCT g.name FROM system_group_program gp JOIN system_group g ON g.id = gp.system_group_id JOIN system_program p ON p.id = gp.system_program_id WHERE p.controller = 'ApchatContatoSemVinculoList' ORDER BY 1"), 'name');
    igual('sem vinculo: so Admin e Financeiro por grupo', ['Admin', 'Financeiro'], $grupos);
    foreach (['ApchatContatoSemVinculoList', 'ApchatContatoConversaView', 'ApchatVincularForm'] as $tela2)
    {
        igual("{$tela2}: mesmos grupos", 2, count(q("SELECT 1 FROM system_group_program gp JOIN system_program p ON p.id = gp.system_program_id WHERE p.controller = '{$tela2}'")));
        igual("{$tela2}: usuarios do Financeiro do agendamento por usuario", 0, count(q("SELECT 1 FROM system_user_group ug JOIN system_group g ON g.id = ug.system_group_id AND g.name = 'Financeiro do agendamento' WHERE NOT EXISTS (SELECT 1 FROM system_user_program up JOIN system_program p ON p.id = up.system_program_id WHERE up.system_user_id = ug.system_user_id AND p.controller = '{$tela2}')")));
    }
    igual('grupo Financeiro do agendamento nao ganha as telas', 0, count(q("SELECT 1 FROM system_group_program gp JOIN system_group g ON g.id = gp.system_group_id JOIN system_program p ON p.id = gp.system_program_id WHERE g.name = 'Financeiro do agendamento' AND p.controller LIKE 'Apchat%'")));
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    limparME();
    ApchatApiService::usarTransporte(null);
    AmbienteService::forcarProducao(null);
    TSession::setValue('userid', 1);
    flock($travaCron, LOCK_UN);
    fclose($travaCron);
}

fim();
