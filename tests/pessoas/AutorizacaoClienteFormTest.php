<?php
// tests/pessoas/AutorizacaoClienteFormTest.php
// 05/11/2026, LGPD: tirar a autorizacao de quem tem lembrete agendado pede confirmacao no cadastro do cliente. Confirmado, os
// lembretes sao cancelados e, ao autorizar de novo, e preciso agendar outra vez. Cliente sem telefone: nao chama o APChat.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_AC = 'TESTE AUTORIZACAO CADASTRO';
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
function limparAC(): void
{
    $ags = array_column(q("SELECT a.id FROM agendamento a JOIN pessoa p ON p.id = a.cliente_id WHERE p.nome LIKE '" . MARCA_AC . "%'"), 'id');
    $lista = $ags ? implode(',', array_map('intval', $ags)) : '0';
    x("DELETE FROM mensagem_agendada WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM agendamento WHERE id IN ({$lista})");
    x("DELETE FROM apchat_contato_log WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_AC . "%')");
    x("DELETE FROM pessoa_grupo WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_AC . "%')");
    x("DELETE FROM pessoa WHERE nome LIKE '" . MARCA_AC . "%'");
}
// segura a trava: o cron real da homologacao nao pode pegar o lembrete do teste
$travaCron = fopen(MensagemAgendadaService::arquivoDaTrava(), 'c');
flock($travaCron, LOCK_EX);
limparAC();

$GLOBALS['__salvar_sem_exit'] = true;
TSession::setValue('userid', 1);
$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);
$agendaId = (int) q("SELECT a.id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id WHERE e.system_unit_id = {$unidade} ORDER BY a.id LIMIT 1")[0]['id'];

TTransaction::open('escritorio');
$st = TTransaction::get()->prepare("INSERT INTO pessoa (nome, tipo_pessoa_id, aceita_receber_mensagen_whatsapp) VALUES (?, 1, 'T') RETURNING id");
$st->execute([MARCA_AC]);
$cliente = (int) $st->fetchColumn();
$st = TTransaction::get()->prepare('INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES (?, ?, ?, ?, ?) RETURNING id');
$st->execute([$cliente, EstadoAgenda::CONFIRMADO, $agendaId, date('Y-m-d H:i:00', strtotime('+3 days')), date('Y-m-d H:i:00', strtotime('+3 days 30 minutes'))]);
$ag = (int) $st->fetchColumn();
TTransaction::close();

$lembrete = function (string $quando) use ($ag) {
    x("INSERT INTO mensagem_agendada (agendamento_id, tipo_mensagem, destino, texto, enviar_em, situacao, tentativas) VALUES ({$ag}, 'WHATSAPP', '5519912340060', 'lembrete', '" . date('Y-m-d H:i:00', strtotime($quando)) . "', 'PENDENTE', 0)");
};
$salvar = function (array $extra) use ($cliente) {
    $p = ['id' => $cliente, 'nome' => MARCA_AC, 'tipo_pessoa_id' => TipoPessoa::FISICA, 'email' => '', 'cpf_cnpj' => '', 'rg_ie' => '', 'telefone' => '', 'dt_nascimento_abertura' => '', 'dt_falecimento' => ''] + $extra;
    $_POST = $p; $_REQUEST = $p;
    return saida(function () use ($p) { (new ClienteForm($p))->onSave($p); });
};
$autoriza = fn() => q("SELECT aceita_receber_mensagen_whatsapp a FROM pessoa WHERE id = {$cliente}")[0]['a'];
$pendentes = fn() => count(q("SELECT 1 FROM mensagem_agendada WHERE agendamento_id = {$ag} AND situacao = 'PENDENTE'"));

try {
    // ---------- com lembrete agendado: pergunta e nao grava nada ----------
    $lembrete('+1 day');
    $s = $salvar([]);
    ok('tirar com lembrete: pergunta', strpos($s, 'lembrete agendado para ' . date('d/m/Y', strtotime('+1 day'))) !== false && strpos($s, 'Tirar a autorização') !== false && strpos($s, 'Manter a autorização') !== false, substr($s, 0, 400));
    igual('sem confirmar: continua autorizado', 'T', $autoriza());
    igual('sem confirmar: lembrete continua', 1, $pendentes());

    $lembrete('+1 day 2 hours');
    $s = $salvar([]);
    ok('dois lembretes: pergunta no plural', strpos($s, 'lembretes agendados') !== false && strpos($s, 'eles serão cancelados') !== false, substr($s, 0, 400));

    // ---------- manter a autorizacao: salvar com ela marcada nao pergunta ----------
    $s = $salvar(['aceita_receber_mensagen_whatsapp' => 'T']);
    ok('com a opcao marcada: nao pergunta', strpos($s, 'lembrete agendado') === false, substr($s, 0, 300));
    igual('com a opcao marcada: lembretes ficam', 2, $pendentes());

    // ---------- confirmado: tira a autorizacao e cancela ----------
    $s = $salvar(['autorizacao_retirada_confirmada' => '1']);
    igual('confirmado: autorizacao sai', 'F', $autoriza());
    igual('confirmado: nenhum pendente', 0, $pendentes());
    igual('confirmado: os dois cancelados com o motivo', 2, count(q("SELECT 1 FROM mensagem_agendada WHERE agendamento_id = {$ag} AND situacao = 'CANCELADA' AND motivo = 'O cliente tirou a autorização'")));
    ok('confirmado: o campo de confirmacao volta vazio', strpos($s, "'autorizacao_retirada_confirmada', ''") !== false, substr($s, 0, 300));

    // ---------- autorizar de novo: nao volta, e preciso agendar ----------
    $salvar(['aceita_receber_mensagen_whatsapp' => 'T']);
    igual('autorizou de novo', 'T', $autoriza());
    igual('autorizou de novo: lembrete nao volta', 0, $pendentes());

    // ---------- sem lembrete: nao pergunta ----------
    $s = $salvar([]);
    ok('sem lembrete: nao pergunta', strpos($s, 'lembrete agendado') === false);
    igual('sem lembrete: tira direto', 'F', $autoriza());

    // ---------- ja sem autorizacao: nao pergunta ----------
    $lembrete('+1 day');
    $s = $salvar([]);
    ok('ja sem autorizacao: nao pergunta', strpos($s, 'lembrete agendado') === false);
    x("DELETE FROM mensagem_agendada WHERE agendamento_id = {$ag} AND situacao = 'PENDENTE'");

    // ---------- fora do cadastro do cliente: barra e manda para la ----------
    x("UPDATE pessoa SET aceita_receber_mensagen_whatsapp = 'T' WHERE id = {$cliente}");
    $lembrete('+1 day');
    $s = saida(function () use ($cliente) { PessoaEditHeaderList::onSaveInline(['_field_name' => "{$cliente}_aceita_receber_mensagen_whatsapp", '_field_value' => 'F', 'column' => 'aceita_receber_mensagen_whatsapp']); });
    ok('lista: barra e manda para o cadastro', strpos($s, 'use o cadastro do cliente') !== false, substr($s, 0, 300));
    igual('lista: continua autorizado', 'T', $autoriza());
    igual('lista: lembrete continua', 1, $pendentes());
    foreach (['ContraparteForm', 'RepresentanteLegalForm'] as $f) {
        ok("{$f} barra tirar a autorizacao com lembrete", strpos(file_get_contents("app/control/pessoas/{$f}.php"), 'MensagemAgendadaService::exigirCadastroDoCliente($data->id') !== false);
    }

    // ---------- cadastro publico ----------
    ok('cadastro publico: WhatsApp e e-mail', strpos(file_get_contents('app/control/agendamentos/NovoClienteForm.php'), 'Autorizo receber mensagens do escritório pelo WhatsApp e por e-mail') !== false);
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    $_POST = []; $_REQUEST = [];
    limparAC();
    flock($travaCron, LOCK_UN);
    fclose($travaCron);
}

fim();
