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

$conf = $template(TemplateEscritorio::CONFIRMACAO, '<p>Olá {$cliente}</p>');
ok('confirmacao por e-mail tem botoes', EmailMontagemService::temBotoes($conf));
ok('confirmacao nova nao usa links antigos', !EmailMontagemService::usaLinksAntigos($conf));

$fim = EmailMontagemService::comBotoes('<p>Olá {$cliente}</p>', $conf);
ok('botões entram no fim', strpos($fim, '<p>Olá {$cliente}</p>') === 0 && strpos($fim, 'href="{$link_confirmacao}"') > 0 && strpos($fim, 'href="{$link_cancelamento}"') > 0, $fim);
ok('botoes com os textos', strpos($fim, 'Confirmar presença') !== false && strpos($fim, 'Cancelar agendamento') !== false);
ok('botoes em tabela (Outlook)', stripos($fim, '<table role="presentation"') !== false);

$meio = EmailMontagemService::comBotoes('<p>Antes</p><p>{$botoes}</p><p>Depois</p>', $conf);
ok('botões vão no lugar de {$botoes}', strpos($meio, '<p>Antes</p>') === 0 && substr($meio, -strlen('<p>Depois</p>')) === '<p>Depois</p>' && strpos($meio, '{$botoes}') === false, $meio);
ok('{$botoes} sozinho no paragrafo: sem <p> em volta da tabela', stripos($meio, '<p><table') === false, $meio);
igual('botoes uma vez so', 1, substr_count($meio, 'href="{$link_confirmacao}"'));

$lembrete = $template(TemplateEscritorio::LEMBRETE, '<p>Até amanhã {$botoes}</p>');
ok('categoria Lembrete nao tem botoes', !EmailMontagemService::temBotoes($lembrete));
igual('{$botoes} some em template sem botões', '<p>Até amanhã </p>', EmailMontagemService::comBotoes('<p>Até amanhã {$botoes}</p>', $lembrete));
igual('sem template: texto como esta', '<p>x</p>', EmailMontagemService::comBotoes('<p>x</p>', null));

$whats = $template(TemplateEscritorio::CONFIRMACAO, 'Responda SIM', ['tipo_template' => TemplateEscritorio::WHATSAPP]);
ok('confirmacao por WhatsApp nao tem botoes', !EmailMontagemService::temBotoes($whats));

$antigo = $template(TemplateEscritorio::CONFIRMACAO, '<p><a href="{$link_confirmacao}">Confirmar</a> <a href="{$link_cancelamento}">Cancelar</a></p>');
ok('template antigo com {$link_confirmacao} não ganha botões', !EmailMontagemService::temBotoes($antigo));
ok('template antigo: usaLinksAntigos', EmailMontagemService::usaLinksAntigos($antigo));
igual('template antigo: texto intacto', $antigo->template, EmailMontagemService::comBotoes($antigo->template, $antigo));
ok('so link de cancelamento tambem e antigo', EmailMontagemService::usaLinksAntigos($template(TemplateEscritorio::CONFIRMACAO, '<a href="{$link_cancelamento}">x</a>')));

// cores e texto
$b = EmailMontagemService::botoesHtml('Sim <b>já</b>', '#ffeb3b', 'Não', 'vermelho');
ok('cor invalida volta para a padrão', stripos($b, '#c62828') !== false, $b);
ok('letra preta em fundo claro', preg_match('/background(-color)?:#ffeb3b;[^"]*color:#000000/i', $b) === 1, $b);
$b2 = EmailMontagemService::botoesHtml('Sim', '#2e7d32', 'Não', '#c62828');
ok('letra branca em #2e7d32', preg_match('/background(-color)?:#2e7d32;[^"]*color:#ffffff/i', $b2) === 1, $b2);
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

fim();
