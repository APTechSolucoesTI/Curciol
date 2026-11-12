<?php
// tests/apmail/ApmailConversaTelaTest.php
// Telas das conversas do APMail no historico do cliente (12/11/2026): linha APMail na lista (Tipo, ID, Inicio, Fim, responsavel,
// caixa) e na linha do tempo; lupa abre a conversa; tela da conversa no layout do APMail (De/Para/Cc, Enviado por, aviso de anexos,
// Profissional = todos), corpo limpo e isolado (iframe com sandbox), botao Abrir no APMail.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

function qt(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function xt(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saidat(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }

const MARCA_TELA = 'TESTE TELA APMAIL';
const PFT = '00000000-0000-4000-8000-0000000002';
$limpar = function () {
    xt("DELETE FROM apmail_conversa WHERE thread_id::text LIKE '" . PFT . "%'");
    foreach (qt("SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_TELA . "%'") as $p) { xt("DELETE FROM pessoa_contato WHERE pessoa_id = {$p['id']}"); xt("DELETE FROM pessoa WHERE id = {$p['id']}"); }
};
$limpar();
$semConfig = sys_get_temp_dir() . '/apmail-sem-config-' . getmypid() . '.php';
ApmailSincronizacaoService::usarConfig($semConfig);
LinkApp::forcar('apmail', true);
TSession::setValue('userid', 1);

try
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $a = (int) $c->query("INSERT INTO pessoa (nome, tipo_pessoa_id, email) VALUES ('" . MARCA_TELA . " A', 1, 'cliente.tela@teste.com') RETURNING id")->fetchColumn();
    $b = (int) $c->query("INSERT INTO pessoa (nome, tipo_pessoa_id, email) VALUES ('" . MARCA_TELA . " B', 1, 'outro.tela@teste.com') RETURNING id")->fetchColumn();
    $conv = (int) $c->query("INSERT INTO apmail_conversa (thread_id, assunto, caixa_nome, inicio_em, fim_em, responsavel_nome, qtd_mensagens)
                             VALUES ('" . PFT . "01', 'Pedido de <documento>', 'Suporte', '2026-11-10 09:00', '2026-11-10 10:30', 'Atendente Um', 2) RETURNING id")->fetchColumn();
    $c->exec("INSERT INTO apmail_conversa_email (conversa_id, email) VALUES ({$conv}, 'cliente.tela@teste.com')");
    $c->exec("INSERT INTO apmail_mensagem (apmail_id, conversa_id, direcao, assunto, de_nome, de_email, para, cc, mensagem_em, corpo_html, corpo_texto, qtd_anexos) VALUES
              ('" . PFT . "11', {$conv}, 'inbound', 'Pedido de <documento>', 'Cliente Tela', 'cliente.tela@teste.com', '[{\"nome\":\"Suporte\",\"email\":\"suporte@escritorio.com\"}]',
               '[{\"nome\":\"\",\"email\":\"copia@teste.com\"}]', '2026-11-10 09:00', '<p>Segue <b>pedido</b></p><script>alert(1)</script><img src=\"cid:logo\">', 'Segue pedido', 2),
              ('" . PFT . "12', {$conv}, 'outbound', 'Re: Pedido', 'Suporte', 'suporte@escritorio.com', '[{\"nome\":\"Cliente Tela\",\"email\":\"cliente.tela@teste.com\"}]',
               '[]', '2026-11-10 10:30', NULL, 'Recebido,\nobrigado.', 0)");
    $c->exec("UPDATE apmail_mensagem SET enviado_por_nome = 'Atendente Dois', qtd_imagens_corpo = 1, tem_convite = true WHERE apmail_id = '" . PFT . "12'");
    TTransaction::close();

    // lista
    $h = saidat(function () use ($a) { (new HistoricosAtendimentoCliente(['key' => $a]))->show(); });
    preg_match_all('#<tr popover[^>]*>(.*?)</tr>#s', $h, $trs);
    $linha = null; $acao = '';
    foreach ($trs[1] as $tr)
    {
        preg_match_all('#<td[^>]*class="tdatagrid_cell( action)?"[^>]*>(.*?)</td>#s', $tr, $tds, PREG_SET_ORDER);
        $dados = array_values(array_map(function ($td) { return trim(strip_tags($td[2])); }, array_filter($tds, function ($td) { return $td[1] === ''; })));
        if (($dados[0] ?? '') === 'APMail') { $linha = $dados; foreach ($tds as $td) { if ($td[1] === ' action') { $acao = $td[2]; } } }
    }
    igual('lista: linha APMail (Tipo, ID, inicio, fim, responsavel, sem especialidade, caixa)', ['APMail', (string) $conv, '10/11/2026 09:00', '10/11/2026 10:30', 'Atendente Um', '', 'Suporte'], $linha);
    ok('lista: lupa abre pela mesma acao, com o id da conversa', strpos($acao, 'method=onAbrir') !== false && strpos($acao, 'conversa_id=' . $conv) !== false, $acao);
    ok('lista: sem traco', strpos($h, '—') === false);
    ok('lista do outro cliente: sem a conversa', strpos(saidat(function () use ($b) { (new HistoricosAtendimentoCliente(['key' => $b]))->show(); }), '>APMail<') === false);
    $abrir = saidat(function () use ($conv, $a) { HistoricosAtendimentoCliente::onAbrir(['key' => 'm' . $conv, 'ticket_id' => 0, 'conversa_id' => $conv, 'pessoa_id' => $a]); });
    ok('onAbrir com conversa_id carrega a conversa de e-mail', strpos($abrir, 'ApmailConversaView') !== false, $abrir);

    // linha do tempo
    $tl = saidat(function () use ($a) { (new AtendimentoTimeLine(['cliente_id' => $a, 'target_container' => 'x']))->show(); });
    ok('linha do tempo: APMail · assunto (escapado)', strpos($tl, 'APMail · Pedido de &lt;documento&gt;') !== false, substr($tl, 0, 400));
    ok('linha do tempo: acao Ver conversa para a conversa de e-mail', strpos($tl, 'class=ApmailConversaView') !== false);

    // tela da conversa
    $v = saidat(function () use ($conv, $a) { $t = new ApmailConversaView(['key' => $conv, 'pessoa_id' => $a]); $t->onShow(['key' => $conv, 'pessoa_id' => $a]); $t->show(); });
    ok('conversa: assunto escapado, caixa e periodo', strpos($v, 'Pedido de &lt;documento&gt;') !== false && strpos($v, 'Suporte') !== false && strpos($v, '10/11/2026 09:00') !== false);
    ok('conversa: Profissional com todos (responsavel e quem enviou)', strpos($v, 'Atendente Um, Atendente Dois') !== false);
    ok('conversa: De, Para e Cc', strpos($v, 'cliente.tela@teste.com') !== false && strpos($v, 'suporte@escritorio.com') !== false && strpos($v, 'copia@teste.com') !== false);
    ok('conversa: Enviado por no e-mail enviado', strpos($v, 'Enviado por') !== false && strpos($v, 'Atendente Dois') !== false);
    ok('conversa: aviso de anexos', strpos($v, '2 anexos') !== false && strpos($v, 'abra no APMail') !== false);
    ok('conversa: corpo HTML num iframe com sandbox e CSP propria, ja limpo', strpos($v, 'sandbox="allow-same-origin"') !== false && strpos($v, 'pointer-events:none') !== false && strpos($v, 'allow-popups') === false
        && strpos($v, 'srcdoc=') !== false && strpos($v, 'default-src') !== false && strpos($v, 'alert(1)') === false && strpos($v, '[imagem do e-mail]') !== false);
    ok('conversa: e-mail so texto com quebra de linha', strpos($v, 'Recebido,<br />') !== false);
    ok('conversa: botao Abrir no APMail pelo app, no ultimo e-mail', strpos($v, 'Abrir no APMail') !== false && strpos($v, 'web+apmail://mensagem/' . PFT . '12') !== false);
    ok('conversa: lista de e-mails ate o fim da tela', strpos($v, 'apm-lista') !== false && strpos($v, 'innerHeight') !== false);
    ok('conversa: lista comeca no topo (mais antigo em cima, sem inverter)', preg_match('/.apm-lista{[^}]*flex-direction:column;/', $v) === 1 && preg_match('/.apm-lista{[^}]*column-reverse/', $v) === 0);
    ok('conversa: imagem da assinatura diferenciada (nao e anexo)', strpos($v, 'assinatura') !== false && strpos($v, '1 anexo') === false);
    ok('conversa: convite de agenda diferenciado', strpos($v, 'Convite de agenda') !== false);
    ok('conversa: sem traco', strpos($v, '—') === false);
    $x = saidat(function () use ($conv, $b) { $t = new ApmailConversaView(['key' => $conv, 'pessoa_id' => $b]); $t->onShow(['key' => $conv, 'pessoa_id' => $b]); $t->show(); });
    ok('conversa de outro cliente: mostra o erro', strpos($x, 'não é deste cliente') !== false && strpos($x, 'Segue') === false);
}
finally
{
    while (TTransaction::get()) { TTransaction::rollback(); }
    ApmailSincronizacaoService::usarConfig(null);
    LinkApp::forcar('apmail', null);
    $limpar();
}

fim();
