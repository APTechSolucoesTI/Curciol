<?php
// tests/email/MontagemEmailTest.php
// Montagem do e-mail (09/11/2026): botoes de confirmar/cancelar do template de confirmacao, corpo com a assinatura no fim
// e a previa fiel (assunto, corpo, anexos). Nao grava no banco.
require __DIR__ . '/../bootstrap.php';

$template = function (string $categoria, string $texto, array $extra = []) {
    $t = new TemplateEscritorio();
    $t->tipo_template = TemplateEscritorio::EMAIL;
    $t->categoria = $categoria;
    $t->template = $texto;
    $t->botao_confirmar_texto = 'Confirmar presença';
    $t->botao_confirmar_cor = '#2e7d32';
    $t->botao_cancelar_texto = 'Cancelar agendamento';
    $t->botao_cancelar_cor = '#c62828';
    foreach ($extra as $k => $v) { $t->$k = $v; }
    return $t;
};

// botoes (09/11/2026, 2a rodada): so onde o usuario poe {$botoes}, {$link_confirmacao} ou {$link_cancelamento}; nada entra sozinho
$conf = $template(TemplateEscritorio::CONFIRMACAO, '<p>Olá {$cliente}</p><p>{$botoes}</p>');
ok('confirmacao com {$botoes} tem botoes', EmailMontagemService::temBotoes($conf));
ok('confirmacao com so {$link_cancelamento} tem botoes', EmailMontagemService::temBotoes($template(TemplateEscritorio::CONFIRMACAO, '<p>{$link_cancelamento}</p>')));
ok('confirmacao sem nenhum link nao tem botoes', !EmailMontagemService::temBotoes($template(TemplateEscritorio::CONFIRMACAO, '<p>Olá</p>')));
ok('metodo dos links antigos nao existe mais', !method_exists('EmailMontagemService', 'usaLinksAntigos'));

igual('sem links: nada entra no fim', '<p>Olá {$cliente}</p>', EmailMontagemService::comBotoes('<p>Olá {$cliente}</p>', $conf));

$meio = EmailMontagemService::comBotoes('<p>Antes</p><p>{$botoes}</p><p>Depois</p>', $conf);
ok('botões vão no lugar de {$botoes}', strpos($meio, '<p>Antes</p>') === 0 && substr($meio, -strlen('<p>Depois</p>')) === '<p>Depois</p>' && strpos($meio, '{$botoes}') === false, $meio);
ok('{$botoes} sozinho no paragrafo: sem <p> em volta da tabela', stripos($meio, '<p><table') === false, $meio);
igual('{$botoes}: uma tabela so', 1, substr_count($meio, '<table'));
igual('{$botoes}: um botao de confirmar', 1, substr_count($meio, 'href="{$link_confirmacao}"'));
igual('{$botoes}: um botao de cancelar', 1, substr_count($meio, 'href="{$link_cancelamento}"'));
ok('{$botoes}: confirmar antes de cancelar', strpos($meio, '{$link_confirmacao}') < strpos($meio, '{$link_cancelamento}'));
ok('botoes com os textos', strpos($meio, 'Confirmar presença') !== false && strpos($meio, 'Cancelar agendamento') !== false);
ok('botoes em tabela (Outlook)', stripos($meio, '<table role="presentation"') !== false);

// um por um
$dois = EmailMontagemService::comBotoes('<p>Escolha:</p><p style="line-height: 1;">{$link_cancelamento}&nbsp; {$link_confirmacao}<br></p>', $conf);
igual('links individuais no mesmo paragrafo: uma tabela', 1, substr_count($dois, '<table'), $dois);
ok('links individuais: na ordem em que foram escritos', strpos($dois, 'href="{$link_cancelamento}"') !== false && strpos($dois, 'href="{$link_cancelamento}"') < strpos($dois, 'href="{$link_confirmacao}"'), $dois);
ok('links individuais: o paragrafo some', strpos($dois, '<p style="line-height: 1;">') === false && strpos($dois, '<p>Escolha:</p>') === 0, $dois);

$nbsp = EmailMontagemService::comBotoes('<p style="line-height: 1;">{$link_confirmacao}' . "\u{00a0}" . '{$link_cancelamento}</p>', $conf);
igual('espaco nao separavel de verdade (U+00A0) entre os links: uma tabela', 1, substr_count($nbsp, '<table'), $nbsp);

$so = EmailMontagemService::comBotoes('<p>Para confirmar: {$link_confirmacao}</p>', $conf);
ok('link no meio do texto vira botao', strpos($so, 'Para confirmar:') !== false && strpos($so, 'href="{$link_confirmacao}"') !== false && strpos($so, 'Confirmar presença') !== false, $so);
ok('link no meio do texto: so o de confirmar', strpos($so, '{$link_cancelamento}') === false);

// link que o usuario fez no editor (ou template antigo): vira o botao estilizado
$antigo = '<p style="line-height: 1;"><a style="border: 1px solid grey; padding: 10px 15px; background-color: #4caf50; color: white;" href="{$link_confirmacao}" target="_blank">Confirmar agendamento</a> '
        . '<a href="{$link_cancelamento}" target="_blank">Cancelar <b>agendamento</b></a><br></p>';
$conv = EmailMontagemService::comBotoes($antigo, $conf);
ok('link com href da variavel vira botao', strpos($conv, 'background-color: #4caf50') === false && strpos($conv, 'Confirmar presença') !== false, $conv);
igual('link com href: uma tabela', 1, substr_count($conv, '<table'), $conv);
igual('link com href: um href de cada', [1, 1], [substr_count($conv, 'href="{$link_confirmacao}"'), substr_count($conv, 'href="{$link_cancelamento}"')], $conv);

// fora da confirmacao por e-mail os links continuam endereco
$lembrete = $template(TemplateEscritorio::LEMBRETE, '<p>Até amanhã {$botoes} {$link_cancelamento}</p>');
ok('categoria Lembrete nao tem botoes', !EmailMontagemService::temBotoes($lembrete));
igual('Lembrete: {$botoes} some e o link fica como endereco', '<p>Até amanhã  {$link_cancelamento}</p>', EmailMontagemService::comBotoes($lembrete->template, $lembrete));
igual('sem template: texto como esta', '<p>x</p>', EmailMontagemService::comBotoes('<p>x</p>', null));
$whats = $template(TemplateEscritorio::CONFIRMACAO, 'Responda SIM {$link_confirmacao}', ['tipo_template' => TemplateEscritorio::WHATSAPP]);
ok('confirmacao por WhatsApp nao tem botoes', !EmailMontagemService::temBotoes($whats));
igual('WhatsApp: link continua endereco', 'Responda SIM {$link_confirmacao}', EmailMontagemService::comBotoes($whats->template, $whats));

// botao que o Outlook respeita: cor e espaco na celula (bgcolor + padding), link sem sublinhado
$b2 = EmailMontagemService::botoesHtml('Sim', '#2e7d32', 'Não', '#c62828');
ok('Outlook: bgcolor na celula', strpos($b2, 'bgcolor="#2e7d32"') !== false && strpos($b2, 'bgcolor="#c62828"') !== false, $b2);
ok('Outlook: espaco interno na celula (mso-padding-alt)', preg_match('/<td[^>]*bgcolor="#2e7d32"[^>]*style="[^"]*mso-padding-alt:\d+px \d+px/', $b2) === 1, $b2);
ok('demais leitores: espaco interno no link (botao inteiro clicavel)', preg_match('/<a [^>]*style="[^"]*display:inline-block;[^"]*padding:\d+px \d+px/', $b2) === 1, $b2);
ok('Outlook: link sem sublinhado e com a cor da letra', preg_match('/<a [^>]*style="[^"]*color:#ffffff;[^"]*text-decoration:none/', $b2) === 1, $b2);
ok('Outlook: texto do botao tambem pintado (span)', preg_match('#<span style="color:\#ffffff;[^"]*">Sim</span>#', $b2) === 1, $b2);

// cores e texto
$b = EmailMontagemService::botoesHtml('Sim <b>já</b>', '#ffeb3b', 'Não', 'vermelho');
ok('cor invalida volta para a padrão', stripos($b, '#c62828') !== false, $b);
ok('letra preta em fundo claro', preg_match('/bgcolor="#ffeb3b".*?color:#000000/s', $b) === 1, $b);
ok('letra branca em #2e7d32', preg_match('/bgcolor="#2e7d32".*?color:#ffffff/s', $b2) === 1, $b2);
ok('texto do botão escapado', strpos($b, '<b>') === false && strpos($b, 'Sim &lt;b&gt;já&lt;/b&gt;') !== false, $b);
ok('texto vazio volta para o padrao', strpos(EmailMontagemService::botoesHtml('', '#2e7d32', ' ', '#c62828'), 'Confirmar presença') !== false);

// corpo e previa
$assinatura = new EmailAssinatura();
$assinatura->tipo = EmailAssinatura::IMAGEM;
$assinatura->html = '<img src="assinaturas/' . str_repeat('a', 40) . '.png" alt="Assinatura">';
$corpo = EmailMontagemService::corpo('<p>Texto</p>', $assinatura, 'https://curciol.exemplo:90');
ok('corpo põe a assinatura depois do texto', strpos($corpo, '<p>Texto</p>') === 0 && strpos($corpo, 'src="https://curciol.exemplo:90/assinaturas/') > strlen('<p>Texto</p>'), $corpo);
igual('corpo sem assinatura: so o texto', '<p>Texto</p>', EmailMontagemService::corpo('<p>Texto</p>', null, 'https://x'));

TTransaction::open('escritorio');
try
{
    $c = TTransaction::get();
    $c->exec("INSERT INTO email_anexo (nome_original, caminho, tamanho) VALUES ('Procuração.pdf', 'files/email/anexos/x/p.pdf', 2048)");
    $anexoId = (int) $c->lastInsertId('email_anexo_id_seq');
    $previa = EmailMontagemService::previa('Assunto <i>x</i>', $corpo, [$anexoId]);
    ok('previa mostra o assunto escapado', strpos($previa, 'Assunto &lt;i&gt;x&lt;/i&gt;') !== false, $previa);
    ok('previa mostra o corpo', strpos($previa, '<p>Texto</p>') !== false);
    ok('previa lista anexos', strpos($previa, 'Procuração.pdf') !== false);
    ok('previa: anexos depois do corpo', strpos($previa, 'Procuração.pdf') > strpos($previa, '<p>Texto</p>'));
}
finally
{
    TTransaction::rollback();
}

ok('variavel {$botoes} na lista', array_key_exists('{$botoes}', MensagemService::variaveis()));
ok('{$link_detalhe} fora da lista de variaveis', !array_key_exists('{$link_detalhe}', MensagemService::variaveis()));

// a lista de variaveis e a mesma do WhatsApp: la {$botoes} nao pode chegar ao cliente como texto
TTransaction::open('escritorio');
$agQualquer = Agendamento::find((int) TTransaction::get()->query('SELECT id FROM agendamento ORDER BY id LIMIT 1')->fetchColumn());
igual('{$botoes} some no replace (WhatsApp)', 'Oi  tchau', TemplateEscritorio::replace($agQualquer, 'Oi {$botoes} tchau'));
// link de detalhe excluido (09/11/2026): template antigo nao manda a variavel crua nem um botao sem destino
igual('{$link_detalhe} some no replace, com o link em volta', '<p><br></p><p>Ver: </p>', TemplateEscritorio::replace($agQualquer, '<p><a style="border: 1px solid grey;" href="{$link_detalhe}" target="_blank">Abrir consulta</a><br></p><p>Ver: {$link_detalhe}</p>'));
TTransaction::rollback();

fim();
