<?php
// tests/email/EnvioEmailTest.php
// Envio de e-mail e lembrete com assinatura e anexos (09/11/2026). NUNCA abre SMTP: transporte falso do EmailClient.
// Dados com a marca TESTE ENVIO EMAIL, apagados no fim (o lembrete precisa de dados gravados: a rodada abre transacao propria).
require __DIR__ . '/../bootstrap.php';

const MARCA_ENVIO = 'TESTE ENVIO EMAIL';
const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
const DESTINO = 'teste.envio@curciol.invalid';

function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

$travaCron = fopen(MensagemAgendadaService::arquivoDaTrava(), 'c');
flock($travaCron, LOCK_EX);

$enviados = [];
EmailClient::usarTransporte(function ($tos, $assunto, $corpo, $tipo, $anexos) use (&$enviados) {
    $enviados[] = compact('tos', 'assunto', 'corpo', 'tipo', 'anexos');
});
AmbienteService::forcarProducao(false);
TSession::setValue('userid', 1);

$tmp = sys_get_temp_dir() . '/curciol-envio-teste-' . getmypid();
@mkdir($tmp, 0777, true);
file_put_contents("{$tmp}/logo.png", base64_decode(PNG_1X1));
file_put_contents("{$tmp}/proc.pdf", '%PDF-1.4 teste');

$pessoa = $agendamentoId = null;
$assinaturas = $anexos = [];
$configOriginal = null;

try
{
    $agenda = q('SELECT a.id, a.escritorio_id, e.url_sistema FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0];
    $base = rtrim((string) $agenda['url_sistema'], '/');
    ok('escritorio da agenda tem url_sistema', $base !== '');

    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $st = $c->prepare("INSERT INTO pessoa (nome, tipo_pessoa_id, aceita_receber_mensagen_whatsapp) VALUES (?, 1, 'T') RETURNING id");
    $st->execute([MARCA_ENVIO]);
    $pessoa = (int) $st->fetchColumn();
    $ini = date('Y-m-d 10:00:00', strtotime('+3 days'));
    $st = $c->prepare('INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES (?, ?, ?, ?, ?) RETURNING id');
    $st->execute([$pessoa, (int) EstadoAgenda::CONFIRMADO, (int) $agenda['id'], $ini, date('Y-m-d 10:30:00', strtotime('+3 days'))]);
    $agendamentoId = (int) $st->fetchColumn();
    $a1 = EmailAssinaturaService::criarDeArquivo("{$tmp}/logo.png", 'logo.png');
    $a2 = EmailAssinaturaService::criarDeArquivo("{$tmp}/logo.png", 'outra.png');
    $assinaturas = [$a1, $a2];
    $anexo = EmailAnexoService::criarDeArquivo("{$tmp}/proc.pdf", 'Procuração.pdf');
    $anexos = [$anexo];
    TTransaction::close();

    preg_match('#assinaturas/[0-9a-f]{40}\.png#', $a1->html, $m1);
    preg_match('#assinaturas/[0-9a-f]{40}\.png#', $a2->html, $m2);

    $conf = new TemplateEscritorio();
    $conf->tipo_template = TemplateEscritorio::EMAIL;
    $conf->categoria = TemplateEscritorio::CONFIRMACAO;
    $conf->template = '<p>Olá {$cliente}</p><p>{$botoes}</p>';
    $conf->botao_confirmar_texto = 'Vou comparecer';
    $conf->botao_confirmar_cor = '#2e7d32';
    $conf->botao_cancelar_texto = 'Não vou';
    $conf->botao_cancelar_cor = '#c62828';
    $texto = EmailMontagemService::comBotoes($conf->template, $conf);

    // ---------- envio com assinatura e anexo ----------
    TTransaction::open('escritorio');
    $ag = Agendamento::find($agendamentoId);
    $m = MensagemService::enviarEmail($ag, 'Confirmação {$cliente}', $texto, null, DESTINO, (int) $a1->id, [(int) $anexo->id]);
    igual('um e-mail saiu', 1, count($enviados));
    $e = $enviados[0] ?? ['corpo' => '', 'anexos' => [], 'tos' => '', 'assunto' => '', 'tipo' => ''];
    igual('destino digitado', DESTINO, $e['tos']);
    igual('assunto com variaveis trocadas', 'Confirmação ' . MARCA_ENVIO, $e['assunto']);
    igual('corpo em html', 'html', $e['tipo']);
    ok('corpo enviado tem o texto', strpos($e['corpo'], '<p>Olá ' . MARCA_ENVIO . '</p>') === 0, $e['corpo']);
    ok('botoes com os links trocados', strpos($e['corpo'], "href=\"{$base}/confirmar-agendamento-{$agendamentoId}-") !== false
        && strpos($e['corpo'], "href=\"{$base}/cancelar-agendamento-{$agendamentoId}-") !== false && strpos($e['corpo'], '{$link') === false, $e['corpo']);
    ok('assinatura com src absoluto do url_sistema', isset($m1[0]) && strpos($e['corpo'], "src=\"{$base}/{$m1[0]}\"") !== false, $e['corpo']);
    ok('assinatura depois dos botoes', strpos($e['corpo'], 'assinaturas/') > strpos($e['corpo'], 'cancelar-agendamento'));
    igual('anexos chegam ao transporte com caminho e nome', [[realpath($anexo->caminho), 'Procuração.pdf']], $e['anexos']);
    igual('mensagem guarda assinatura_id', (int) $a1->id, (int) Mensagem::find($m->id)->assinatura_id);
    igual('mensagem guarda os anexos', [(int) $anexo->id], array_map('intval', array_column(TTransaction::get()->query("SELECT email_anexo_id FROM mensagem_anexo WHERE mensagem_id = {$m->id}")->fetchAll(PDO::FETCH_ASSOC), 'email_anexo_id')));
    ok('mensagem guarda o texto sem a assinatura', strpos((string) Mensagem::find($m->id)->template, 'assinaturas/') === false);

    // ---------- sem assinatura ----------
    $enviados = [];
    MensagemService::enviarEmail($ag, 'Sem', '<p>Só texto</p>', null, DESTINO);
    igual('sem assinatura: corpo so com o texto', '<p>Só texto</p>', $enviados[0]['corpo'] ?? null);
    igual('sem anexos: lista vazia', [], $enviados[0]['anexos'] ?? null);

    // ---------- total acima de 20 MB ----------
    $enviados = [];
    $grandes = [];
    foreach ([1, 2] as $i)
    {
        $grandes[] = (int) TTransaction::get()->query("INSERT INTO email_anexo (nome_original, caminho, tamanho) VALUES ('g{$i}.pdf', 'files/email/anexos/x/g{$i}.pdf', " . (11 * 1024 * 1024) . ') RETURNING id')->fetchColumn();
    }
    lanca('total acima de 20 MB nao envia', fn() => MensagemService::enviarEmail($ag, 'T', '<p>x</p>', null, DESTINO, null, $grandes), '20 MB');
    igual('nada saiu com anexos demais', 0, count($enviados));

    // ---------- sem url_sistema ----------
    TTransaction::get()->exec("UPDATE escritorio SET url_sistema = '' WHERE id = " . (int) $agenda['escritorio_id']);
    $ag2 = Agendamento::find($agendamentoId);
    lanca('sem url_sistema e com assinatura: erro claro', fn() => MensagemService::enviarEmail($ag2, 'T', '<p>x</p>', null, DESTINO, (int) $a1->id), 'Informe a URL do sistema');
    igual('nada saiu sem url_sistema', 0, count($enviados));
    TTransaction::rollback();

    // ---------- lembrete agendado guarda e usa o que foi escolhido ----------
    TTransaction::open('escritorio');
    $l = MensagemAgendadaService::agendar(Agendamento::find($agendamentoId), TemplateEscritorio::EMAIL, 'Lembrete {$cliente}', '<p>Até lá</p>', null,
                                          DESTINO, date('d/m/Y H:i', strtotime('+1 day')), (int) $a2->id, [(int) $anexo->id]);
    TTransaction::close();
    $lid = (int) $l->id;
    igual('lembrete guarda assinatura', (int) $a2->id, (int) q("SELECT assinatura_id FROM mensagem_agendada WHERE id = {$lid}")[0]['assinatura_id']);
    igual('lembrete guarda anexos', [(int) $anexo->id], array_map('intval', array_column(q("SELECT email_anexo_id FROM mensagem_agendada_anexo WHERE mensagem_agendada_id = {$lid}"), 'email_anexo_id')));

    // troca a assinatura do escritorio depois de agendar: o lembrete continua com a dele
    $configOriginal = q('SELECT id, assinatura_id FROM email_config WHERE escritorio_id = ' . (int) $agenda['escritorio_id'])[0] ?? null;
    if ($configOriginal) { x("UPDATE email_config SET assinatura_id = {$a1->id} WHERE id = {$configOriginal['id']}"); }

    x("UPDATE mensagem_agendada SET enviar_em = '" . date('Y-m-d H:i:s', strtotime('-1 minute')) . "' WHERE id = {$lid}");
    $enviados = [];
    ob_start(); MensagemAgendadaService::rodada(); $log = ob_get_clean();
    igual('lembrete enviado', 'ENVIADA', q("SELECT situacao FROM mensagem_agendada WHERE id = {$lid}")[0]['situacao']);
    $e = $enviados[0] ?? ['corpo' => '', 'anexos' => []];
    ok('lembrete saiu com a assinatura escolhida ao agendar', isset($m2[0]) && strpos($e['corpo'], "{$base}/{$m2[0]}") !== false && (!isset($m1[0]) || strpos($e['corpo'], $m1[0]) === false), $e['corpo'] . ' | ' . $log);
    igual('lembrete saiu com os anexos', [[realpath($anexo->caminho), 'Procuração.pdf']], $e['anexos']);
    $mid = (int) q("SELECT mensagem_id FROM mensagem_agendada WHERE id = {$lid}")[0]['mensagem_id'];
    igual('mensagem do lembrete guarda a assinatura', (int) $a2->id, (int) q("SELECT assinatura_id FROM mensagem WHERE id = {$mid}")[0]['assinatura_id']);
}
catch (Throwable $e)
{
    ok('sem excecao inesperada', false, $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
finally
{
    while (TTransaction::get()) { TTransaction::rollback(); }
    if ($configOriginal) { x('UPDATE email_config SET assinatura_id = ' . ($configOriginal['assinatura_id'] ? (int) $configOriginal['assinatura_id'] : 'NULL') . " WHERE id = {$configOriginal['id']}"); }
    if ($agendamentoId)
    {
        x("DELETE FROM mensagem_agendada WHERE agendamento_id = {$agendamentoId}");
        x("DELETE FROM mensagem WHERE agendamento_id = {$agendamentoId}");
        x("DELETE FROM estado_agendamento WHERE agendamento_id = {$agendamentoId}");
        x("DELETE FROM agendamento WHERE id = {$agendamentoId}");
    }
    if ($pessoa) { x("DELETE FROM pessoa WHERE id = {$pessoa}"); }
    foreach ($anexos as $a) { x("DELETE FROM email_anexo WHERE id = {$a->id}"); @unlink($a->caminho); @rmdir(dirname($a->caminho)); }
    foreach ($assinaturas as $a)
    {
        x("DELETE FROM email_assinatura WHERE id = {$a->id}");
        if (preg_match('#assinaturas/[0-9a-f]{40}\.png#', $a->html, $m)) { @unlink($m[0]); }
    }
    exec('rm -rf ' . escapeshellarg($tmp));
    flock($travaCron, LOCK_UN);
}

fim();
