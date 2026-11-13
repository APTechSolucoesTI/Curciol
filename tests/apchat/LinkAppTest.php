<?php
// tests/apchat/LinkAppTest.php
// Links da Curciol para o APChat e o APMail abrindo SO no app instalado (12/11/2026): com a chave do escritorio ligada
// (apchat_abrir_app / apmail_abrir_app = 'S'), o link vira web+apchat:// ou web+apmail:// (o sistema operacional entrega
// ao app instalado; sem app, nao abre nada e a Curciol avisa). Desligada: como antes (aba com nome, so http/https).
require __DIR__ . '/../bootstrap.php';

$atr = function ($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };
$url = 'https://app1.apchat.com.br/atendimento?ticketId=9';

try
{
    // desligado: igual ao LinkExterno
    LinkApp::forcar('apchat', false);
    LinkApp::forcar('apmail', false);
    igual('apchat desligado: abre a tela do APChat numa aba (como antes)', LinkExterno::onclick($url, 'apchat'), LinkApp::apchatTicket(9, $url));
    igual('apchat desligado: chat interno na aba do APChat', LinkExterno::onclick('https://x/chat-privado?contactId=5', 'apchat'), LinkApp::apchatChat('usuario', 5, 'https://x/chat-privado?contactId=5'));
    igual('apmail desligado: abre a caixa numa aba (como antes)', LinkExterno::onclick('https://app.apmail.com.br/mail/a2', 'apmail'),
          LinkApp::apmailCaixa('https://app.apmail.com.br/mail/a2', '/mail/a2'));

    // ligado: endereco proprio do app
    LinkApp::forcar('apchat', true);
    LinkApp::forcar('apmail', true);
    igual('apchat ligado: ticket pelo app', $atr('CurciolAbrirApp("web+apchat://ticket/9", "APChat"); return false;'), LinkApp::apchatTicket(9, $url));
    igual('apchat ligado: chat com usuario pelo app (13/11/2026)', $atr('CurciolAbrirApp("web+apchat://chat/usuario/5", "APChat"); return false;'), LinkApp::apchatChat('usuario', 5, 'https://x'));
    igual('apchat ligado: chat da equipe pelo app', $atr('CurciolAbrirApp("web+apchat://chat/equipe/7", "APChat"); return false;'), LinkApp::apchatChat('equipe', 7, 'https://x'));
    igual('apchat ligado: tipo desconhecido vira usuario', $atr('CurciolAbrirApp("web+apchat://chat/usuario/7", "APChat"); return false;'), LinkApp::apchatChat('x"y', 7, 'https://x'));
    igual('chat sem id nao abre nada', '', LinkApp::apchatChat('usuario', 0, 'https://x'));
    igual('apchat ligado: tela inicial pelo app', $atr('CurciolAbrirApp("web+apchat://inicio", "APChat"); return false;'), LinkApp::apchatInicio('https://app1.apchat.com.br'));
    igual('apmail ligado: caixa pelo app (caminho da API codificado)', $atr('CurciolAbrirApp("web+apmail://abrir?caminho=%2Fmail%2Fa2%3Fassigned%3Dany", "APMail"); return false;'),
          LinkApp::apmailCaixa('https://app.apmail.com.br/mail/a2?assigned=any', '/mail/a2?assigned=any'));
    igual('apmail ligado: caminho que nao e do APMail vira a tela inicial', $atr('CurciolAbrirApp("web+apmail://abrir?caminho=%2F", "APMail"); return false;'),
          LinkApp::apmailCaixa('https://app.apmail.com.br/', '//site.com/x'));
    igual('apmail ligado: mensagem pelo UUID', $atr('CurciolAbrirApp("web+apmail://mensagem/60862da5-9a2f-411a-89a3-d54147793789", "APMail"); return false;'),
          LinkApp::apmailMensagem('60862da5-9a2f-411a-89a3-d54147793789', 'https://app.apmail.com.br/'));
    igual('apmail ligado: UUID invalido nao abre nada', '', LinkApp::apmailMensagem("x\");alert(1);//", 'https://app.apmail.com.br/'));
    igual('ticket invalido (zero) nao abre nada', '', LinkApp::apchatTicket(0, $url));
    ok('ligado: sem window.open', strpos(LinkApp::apchatTicket(9, $url), 'window.open') === false);

    // chave do escritorio (sem forcar): le escritorio.apchat_abrir_app / apmail_abrir_app
    LinkApp::forcar('apchat', null);
    LinkApp::forcar('apmail', null);
    TTransaction::open('escritorio');
    $e = ApchatApiService::escritorio();
    $esperado = $e && $e->apchat_abrir_app === 'S';
    TTransaction::close();
    igual('apchat: segue a chave do escritorio', $esperado, LinkApp::ligado('apchat'));
    igual('sistema desconhecido: desligado', false, LinkApp::ligado('outro'));
    $col = (function () { TTransaction::open('escritorio'); $r = TTransaction::get()->query("SELECT column_name, column_default, is_nullable FROM information_schema.columns
             WHERE table_name = 'escritorio' AND column_name IN ('apchat_abrir_app', 'apmail_abrir_app') ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; })();
    ok('colunas no escritorio, padrao N, obrigatorias', count($col) === 2 && strpos((string) $col[0]['column_default'], "'N'") !== false && $col[0]['is_nullable'] === 'NO'
       && strpos((string) $col[1]['column_default'], "'N'") !== false, json_encode($col));

    // cadastro do escritorio: Admin liga/desliga; quem nao e Admin nao muda
    $atual = (object) ['url_apchat' => 'u', 'token_apchat' => 't', 'url_tela_apchat' => null, 'url_apmail' => null, 'token_apmail' => null, 'apchat_abrir_app' => 'S', 'apmail_abrir_app' => 'N'];
    $d = EscritorioForm::segredosAoSalvar((object) ['url_apchat' => 'u', 'token_apchat' => '', 'apchat_abrir_app' => 'S'], $atual, true);
    ok('admin: marcado = S, desmarcado = N', $d->apchat_abrir_app === 'S' && $d->apmail_abrir_app === 'N', json_encode($d));
    $d = EscritorioForm::segredosAoSalvar((object) ['apchat_abrir_app' => '', 'apmail_abrir_app' => 'S'], $atual, false);
    ok('nao admin: mantem o que estava', $d->apchat_abrir_app === 'S' && $d->apmail_abrir_app === 'N', json_encode($d));

    $js = (string) file_get_contents('app/lib/include/builder/theme-builder/BuilderTemplate.js');
    ok('JS: abertura do app não infere falha pela perda de foco', strpos($js, 'window.CurciolAbrirApp') !== false && strpos($js, 'CurciolAvisoApp') === false);
}
finally
{
    LinkApp::forcar('apchat', null);
    LinkApp::forcar('apmail', null);
}

fim();
