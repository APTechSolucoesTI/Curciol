<?php
// tests/email/EnviarEmailFormTest.php
// Envio de e-mail pela tela do agendamento (09/11/2026): sem Manual vai o que o template tem (assinatura, anexos, botoes);
// com Manual o usuario troca a assinatura so neste envio (outro arquivo, padrao, nenhuma) e tira/acrescenta anexos.
// Visualizar = corpo enviado; historico mostra assinatura e anexos. NUNCA abre SMTP (transporte falso).
// Dados com a marca TESTE ENVIAR EMAIL FORM, apagados no fim (o onSend grava de verdade).
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_FORM = 'TESTE ENVIAR EMAIL FORM';
const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
const DESTINO = 'teste.form@curciol.invalid';

function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string { ob_start(); $f(); return ob_get_clean(); }

$travaCron = fopen(MensagemAgendadaService::arquivoDaTrava(), 'c');
flock($travaCron, LOCK_EX);

$enviados = [];
EmailClient::usarTransporte(function ($tos, $assunto, $corpo, $tipo, $anexos) use (&$enviados) { $enviados[] = compact('tos', 'assunto', 'corpo', 'anexos'); });
AmbienteService::forcarProducao(false);
TSession::setValue('userid', 1);

@mkdir('tmp', 0775, true);
$tmpCriados = [];
$enviado = function (string $nome, string $conteudo) use (&$tmpCriados): string { $n = uniqid() . '_' . $nome; file_put_contents("tmp/{$n}", $conteudo); $tmpCriados[] = "tmp/{$n}"; return $n; };
$antesAss = glob('assinaturas/*') ?: [];
$pessoa = $ag = $tConf = $tLemb = null;
$anexosCriados = [];
$configOriginal = null;

try
{
    $agenda = q('SELECT a.id, a.escritorio_id, e.system_unit_id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0];
    TSession::setValue('userunitid', (int) $agenda['system_unit_id']);
    TSession::setValue('userunitids', [(int) $agenda['system_unit_id']]);

    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $st = $c->prepare("INSERT INTO pessoa (nome, tipo_pessoa_id, aceita_receber_mensagen_whatsapp) VALUES (?, 1, 'T') RETURNING id");
    $st->execute([MARCA_FORM]);
    $pessoa = (int) $st->fetchColumn();
    $st = $c->prepare('INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES (?, ?, ?, ?, ?) RETURNING id');
    $st->execute([$pessoa, (int) EstadoAgenda::AGENDADO, (int) $agenda['id'], date('Y-m-d 10:00:00', strtotime('+3 days')), date('Y-m-d 10:30:00', strtotime('+3 days'))]);
    $ag = (int) $st->fetchColumn();

    $aTemplate = EmailAssinaturaService::criarDeArquivo('tmp/' . $enviado('do-template.png', base64_decode(PNG_1X1)), 'do-template.png');
    $aEscritorio = EmailAssinaturaService::criarDeArquivo('tmp/' . $enviado('do-escritorio.png', base64_decode(PNG_1X1)), 'do-escritorio.png');
    $anexoT = EmailAnexoService::criarDeArquivo('tmp/' . $enviado('termo.pdf', '%PDF termo'), 'Termo.pdf');
    $anexosCriados[] = $anexoT;

    $novo = function (string $categoria, string $texto) use ($agenda, $aTemplate, $anexoT) {
        $t = new TemplateEscritorio();
        $t->escritorio_id = (int) $agenda['escritorio_id'];
        $t->chave = 'TESTE_' . strtoupper(uniqid());
        $t->descricao = MARCA_FORM . ' ' . $categoria;
        $t->habilitado = 'T';
        $t->titulo = 'Assunto {$cliente}';
        $t->template = $texto;
        $t->tipo_template = TemplateEscritorio::EMAIL;
        $t->categoria = $categoria;
        $t->assinatura_modo = TemplateEscritorio::ASSINATURA_OUTRA;
        $t->assinatura_id = $aTemplate->id;
        $t->botao_confirmar_texto = 'Vou sim';
        $t->botao_confirmar_cor = '#2e7d32';
        $t->botao_cancelar_texto = 'Não vou';
        $t->botao_cancelar_cor = '#c62828';
        $t->store();
        $l = new TemplateEscritorioAnexo();
        $l->template_escritorio_id = $t->id;
        $l->email_anexo_id = $anexoT->id;
        $l->ordem = 0;
        $l->store();
        return $t;
    };
    $tConf = $novo(TemplateEscritorio::CONFIRMACAO, '<p>Olá {$cliente}</p>');
    $tLemb = $novo(TemplateEscritorio::LEMBRETE, '<p>Lembrete</p>');

    $configOriginal = q('SELECT id, assinatura_id FROM email_config WHERE escritorio_id = ' . (int) $agenda['escritorio_id'])[0] ?? null;
    if ($configOriginal) { $c->exec("UPDATE email_config SET assinatura_id = {$aEscritorio->id} WHERE id = {$configOriginal['id']}"); }
    TTransaction::close();

    preg_match('#assinaturas/[0-9a-f]{40}\.png#', $aTemplate->html, $mT);
    preg_match('#assinaturas/[0-9a-f]{40}\.png#', $aEscritorio->html, $mE);

    // ---------- servico: opcoes do combo, anexos do template ----------
    TTransaction::open('escritorio');
    $opcoes = EnvioEmailService::opcoesAssinatura(TemplateEscritorio::find($tConf->id));
    igual('combo: opcoes com a do template', ['PADRAO', 'TEMPLATE', 'ARQUIVO', 'NENHUMA'], array_keys($opcoes));
    ok('combo: rotulo do template com o nome', strpos($opcoes['TEMPLATE'], 'do-template.png') !== false);
    igual('combo: valor inicial do template OUTRA', 'TEMPLATE', EnvioEmailService::modoInicial(TemplateEscritorio::find($tConf->id)));
    $semOutra = new TemplateEscritorio();
    $semOutra->assinatura_modo = 'NENHUMA';
    igual('combo: sem opcao do template quando nao e OUTRA', ['PADRAO', 'ARQUIVO', 'NENHUMA'], array_keys(EnvioEmailService::opcoesAssinatura($semOutra)));
    igual('combo: valor inicial NENHUMA', 'NENHUMA', EnvioEmailService::modoInicial($semOutra));
    igual('anexos do template', [(int) $anexoT->id], array_keys(EnvioEmailService::anexosDoTemplate(TemplateEscritorio::find($tConf->id))));
    TTransaction::close();

    $param = ['agendamento_id' => $ag, 'tipo' => 'EMAIL', 'template' => $tConf->id, 'destino' => DESTINO, 'contato' => ''];

    // ---------- sem Manual: o que o template tem ----------
    $s = saida(fn() => EnviarMensagemForm::onSend($param));
    igual('sem Manual: um e-mail', 1, count($enviados), $s);
    $e = $enviados[0] ?? ['corpo' => '', 'anexos' => []];
    ok('sem Manual: assinatura do template', isset($mT[0]) && strpos($e['corpo'], $mT[0]) !== false, $e['corpo']);
    ok('sem Manual: botoes', strpos($e['corpo'], 'Vou sim') !== false && strpos($e['corpo'], 'confirmar-agendamento-' . $ag) !== false);
    igual('sem Manual: anexos do template', ['Termo.pdf'], array_column($e['anexos'], 1));
    $mSem = (int) (q("SELECT id FROM mensagem WHERE agendamento_id = {$ag} ORDER BY id DESC LIMIT 1")[0]['id'] ?? 0);

    // ---------- Manual: outro arquivo so neste envio, anexo trocado, {$botoes} no meio ----------
    $enviados = [];
    $manual = ['manual' => 'S', 'titulo' => 'Manual {$cliente}', 'content_email' => '<p>Antes</p><p>{$botoes}</p><p>Depois</p>',
               'assinatura_modo' => 'ARQUIVO', 'assinatura_arquivo' => $enviado('so-hoje.png', base64_decode(PNG_1X1)),
               'anexos_template_enviado' => '1', 'anexos_novos' => [$enviado('extra.pdf', '%PDF extra')]] + $param;
    $s = saida(fn() => EnviarMensagemForm::onSend($manual));
    $e = $enviados[0] ?? ['corpo' => '', 'anexos' => []];
    ok('Manual: e-mail saiu', count($enviados) === 1, preg_replace('#<script.*?</script>#s', '', $s));
    ok('Manual: outro arquivo de assinatura (nem a do template nem a do escritorio)', preg_match('#assinaturas/[0-9a-f]{40}\.png#', $e['corpo'], $mN) === 1
        && (!isset($mT[0]) || $mN[0] !== $mT[0]) && (!isset($mE[0]) || $mN[0] !== $mE[0]), $e['corpo']);
    igual('Manual: template continua com a assinatura dele', (int) $aTemplate->id, (int) q("SELECT assinatura_id FROM template_escritorio WHERE id = {$tConf->id}")[0]['assinatura_id']);
    igual('Manual: anexo do template desmarcado, novo acrescentado', ['extra.pdf'], array_column($e['anexos'], 1));
    $pAntes = strpos($e['corpo'], '<p>Antes</p>'); $pBot = strpos($e['corpo'], 'Vou sim'); $pDepois = strpos($e['corpo'], '<p>Depois</p>');
    ok('Manual: botoes no lugar de {$botoes}', $pAntes !== false && $pBot > $pAntes && $pDepois > $pBot, $e['corpo']);

    // ---------- Manual: arquivo de assinatura pedido mas nao enviado ----------
    $enviados = [];
    $s = saida(fn() => EnviarMensagemForm::onSend(['assinatura_arquivo' => ''] + $manual));
    ok('Manual ARQUIVO sem arquivo: nao envia e avisa', count($enviados) === 0 && strpos($s, 'Escolha o arquivo da assinatura') !== false);

    // ---------- Manual: sem assinatura, mantendo o anexo do template ----------
    $enviados = [];
    saida(fn() => EnviarMensagemForm::onSend(['assinatura_modo' => 'NENHUMA', 'anexos_template' => [(string) $anexoT->id], 'anexos_novos' => []] + $manual));
    $e = $enviados[0] ?? ['corpo' => 'x assinaturas/', 'anexos' => []];
    ok('Manual NENHUMA: sem assinatura', strpos($e['corpo'], 'assinaturas/') === false, $e['corpo']);
    igual('Manual: anexo do template mantido', ['Termo.pdf'], array_column($e['anexos'], 1));

    // anexo que nao e do template, mandado a mao no POST, e ignorado
    $enviados = [];
    saida(fn() => EnviarMensagemForm::onSend(['assinatura_modo' => 'PADRAO', 'anexos_template' => ['999999999'], 'anexos_novos' => []] + $manual));
    igual('Manual: anexo de fora do template ignorado', [], array_column($enviados[0]['anexos'] ?? [['', 'x']], 1));
    ok('Manual PADRAO: assinatura do escritorio', isset($mE[0]) && strpos($enviados[0]['corpo'] ?? '', $mE[0]) !== false);

    // ---------- Visualizar = corpo enviado (base relativa) ----------
    TTransaction::open('escritorio');
    $agObj = Agendamento::find($ag);
    $tObj = TemplateEscritorio::find($tConf->id);
    $previa = EnvioEmailService::previa($agObj, $tObj, true, 'Manual {$cliente}', '<p>Antes</p><p>{$botoes}</p><p>Depois</p>', ['assinatura_modo' => 'TEMPLATE', 'anexos_template_enviado' => '1', 'anexos_template' => [(string) $anexoT->id]]);
    $corpoEsperado = EmailMontagemService::corpo(TemplateEscritorio::replace($agObj, EmailMontagemService::comBotoes('<p>Antes</p><p>{$botoes}</p><p>Depois</p>', $tObj)), $aTemplate, '');
    ok('Visualizar mostra o corpo que sai (assinatura relativa)', strpos($previa, $corpoEsperado) !== false, $previa);
    ok('Visualizar mostra o assunto trocado', strpos($previa, 'Manual ' . MARCA_FORM) !== false);
    ok('Visualizar lista o anexo', strpos($previa, 'Termo.pdf') !== false);
    $previaArq = EnvioEmailService::previa($agObj, $tObj, true, 'T', '<p>x</p>', ['assinatura_modo' => 'ARQUIVO', 'assinatura_arquivo' => $enviado('p.png', base64_decode(PNG_1X1)), 'anexos_novos' => [$enviado('n.pdf', '%PDF')]]);
    ok('Visualizar com arquivo novo: aviso e sem criar assinatura', strpos($previaArq, 'arquivo') !== false && strpos($previaArq, 'n.pdf') !== false);
    $previaSem = EnvioEmailService::previa($agObj, $tObj, false, 'T', '<p>x</p>', []);
    ok('previa sem Manual: assinatura e anexo do template', isset($mT[0]) && strpos($previaSem, $mT[0]) !== false && strpos($previaSem, 'Termo.pdf') !== false);

    // ---------- historico ----------
    $hist = Mensagem::find($mSem)->template_formatado;
    ok('historico: assinatura', isset($mT[0]) && strpos($hist, $mT[0]) !== false, $hist);
    ok('historico: anexo com link de download', strpos($hist, 'Termo.pdf') !== false && strpos($hist, 'download.php?file=') !== false, $hist);
    TTransaction::close();

    // ---------- lembrete agendado pela tela guarda assinatura e anexos ----------
    x("UPDATE agendamento SET estado_agenda_id = " . (int) EstadoAgenda::CONFIRMADO . " WHERE id = {$ag}");
    $s = saida(fn() => EnviarMensagemForm::onSend(['template' => $tLemb->id, 'agendar' => 'S', 'enviar_em' => date('d/m/Y H:i', strtotime('+1 day')),
                                                      'manual' => 'S', 'titulo' => 'Lembrete', 'content_email' => '<p>Lembrete</p>', 'assinatura_modo' => 'NENHUMA',
                                                      'anexos_template_enviado' => '1', 'anexos_template' => [(string) $anexoT->id]] + $param));
    $l = q("SELECT id, assinatura_id FROM mensagem_agendada WHERE agendamento_id = {$ag} ORDER BY id DESC LIMIT 1")[0] ?? null;
    ok('agendar: lembrete criado', (bool) $l, preg_replace('#<script.*?</script>#s', '', $s));
    igual('agendar: sem assinatura (NENHUMA)', null, $l ? $l['assinatura_id'] : 'sem lembrete');
    igual('agendar: anexo escolhido', [(int) $anexoT->id], array_map('intval', array_column(q('SELECT email_anexo_id FROM mensagem_agendada_anexo WHERE mensagem_agendada_id = ' . (int) ($l['id'] ?? 0)), 'email_anexo_id')));
}
catch (Throwable $e)
{
    ok('sem excecao inesperada', false, $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
finally
{
    while (TTransaction::get()) { TTransaction::rollback(); }
    if ($configOriginal) { x('UPDATE email_config SET assinatura_id = ' . ($configOriginal['assinatura_id'] ? (int) $configOriginal['assinatura_id'] : 'NULL') . " WHERE id = {$configOriginal['id']}"); }
    $anexoIds = [];
    if ($ag)
    {
        foreach (q("SELECT email_anexo_id FROM mensagem_anexo WHERE mensagem_id IN (SELECT id FROM mensagem WHERE agendamento_id = {$ag})
                    UNION SELECT email_anexo_id FROM mensagem_agendada_anexo WHERE mensagem_agendada_id IN (SELECT id FROM mensagem_agendada WHERE agendamento_id = {$ag})") as $r) { $anexoIds[] = (int) $r['email_anexo_id']; }
        $assIds = array_map('intval', array_column(q("SELECT assinatura_id FROM mensagem WHERE agendamento_id = {$ag} AND assinatura_id IS NOT NULL"), 'assinatura_id'));
        x("DELETE FROM mensagem_agendada WHERE agendamento_id = {$ag}");
        x("DELETE FROM mensagem WHERE agendamento_id = {$ag}");
        x("DELETE FROM estado_agendamento WHERE agendamento_id = {$ag}");
        x("DELETE FROM agendamento WHERE id = {$ag}");
    }
    foreach ([$tConf, $tLemb] as $t) { if ($t) { x("DELETE FROM template_escritorio WHERE id = {$t->id}"); } }
    if ($pessoa) { x("DELETE FROM pessoa WHERE id = {$pessoa}"); }
    foreach ($anexosCriados as $a) { if (is_object($a)) { $anexoIds[] = (int) $a->id; } }
    foreach (array_unique($anexoIds) as $id)
    {
        $r = q("SELECT caminho FROM email_anexo WHERE id = {$id}")[0] ?? null;
        if ($r) { @unlink($r['caminho']); @rmdir(dirname($r['caminho'])); }
        x("DELETE FROM email_anexo WHERE id = {$id}");
    }
    x("DELETE FROM email_assinatura WHERE nome_original IN ('do-template.png', 'do-escritorio.png', 'so-hoje.png') AND id NOT IN (SELECT assinatura_id FROM email_config WHERE assinatura_id IS NOT NULL) AND id NOT IN (SELECT assinatura_id FROM mensagem WHERE assinatura_id IS NOT NULL) AND id NOT IN (SELECT assinatura_id FROM template_escritorio WHERE assinatura_id IS NOT NULL)");
    foreach ($tmpCriados as $f) { @unlink($f); }
    foreach (array_diff(glob('assinaturas/*') ?: [], $antesAss) as $f) { @unlink($f); }
    flock($travaCron, LOCK_UN);
}

fim();
