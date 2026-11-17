<?php
// tests/apchat/LinkAppTest.php
// Links da Curciol para o APChat e o APMail abrindo SO no app instalado (12/11/2026; regra do usuario de 17/11/2026: sempre,
// nunca em aba ou janela do navegador). O link e web+apchat:// ou web+apmail:// (o sistema operacional entrega ao app instalado;
// sem app, o navegador nao faz nada). A chave do escritorio (apchat_abrir_app / apmail_abrir_app) nao e mais lida.
require __DIR__ . '/../bootstrap.php';

$atr = function ($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); };

igual('ticket pelo app', $atr('CurciolAbrirApp("web+apchat://ticket/9", "APChat"); return false;'), LinkApp::apchatTicket(9));
igual('chat com usuario pelo app (13/11/2026)', $atr('CurciolAbrirApp("web+apchat://chat/usuario/5", "APChat"); return false;'), LinkApp::apchatChat('usuario', 5));
igual('chat da equipe pelo app', $atr('CurciolAbrirApp("web+apchat://chat/equipe/7", "APChat"); return false;'), LinkApp::apchatChat('equipe', 7));
igual('tipo desconhecido vira usuario', $atr('CurciolAbrirApp("web+apchat://chat/usuario/7", "APChat"); return false;'), LinkApp::apchatChat('x"y', 7));
igual('chat sem id nao abre nada', '', LinkApp::apchatChat('usuario', 0));
igual('tela inicial do APChat pelo app', $atr('CurciolAbrirApp("web+apchat://inicio", "APChat"); return false;'), LinkApp::apchatInicio());
igual('tela inicial do APMail pelo app', $atr('CurciolAbrirApp("web+apmail://abrir?caminho=%2F", "APMail"); return false;'), LinkApp::apmailInicio());
igual('caixa do APMail pelo app (caminho da API codificado)', $atr('CurciolAbrirApp("web+apmail://abrir?caminho=%2Fmail%2Fa2%3Fassigned%3Dany", "APMail"); return false;'),
      LinkApp::apmailCaixa('/mail/a2?assigned=any'));
igual('caminho que nao e do APMail vira a tela inicial', $atr('CurciolAbrirApp("web+apmail://abrir?caminho=%2F", "APMail"); return false;'), LinkApp::apmailCaixa('//site.com/x'));
igual('mensagem do APMail pelo UUID', $atr('CurciolAbrirApp("web+apmail://mensagem/60862da5-9a2f-411a-89a3-d54147793789", "APMail"); return false;'),
      LinkApp::apmailMensagem('60862da5-9a2f-411a-89a3-d54147793789'));
igual('UUID invalido nao abre nada', '', LinkApp::apmailMensagem("x\");alert(1);//"));
igual('ticket invalido (zero) nao abre nada', '', LinkApp::apchatTicket(0));
foreach ([LinkApp::apchatTicket(9), LinkApp::apchatChat('equipe', 7), LinkApp::apchatInicio(), LinkApp::apmailInicio(), LinkApp::apmailCaixa('/mail/a2'),
          LinkApp::apmailMensagem('60862da5-9a2f-411a-89a3-d54147793789')] as $i => $l)
{
    ok("nunca aba ({$i}): sem http, sem window.open, sem LinkExterno", strpos($l, 'http') === false && strpos($l, 'window.open') === false && strpos($l, 'LinkExterno') === false, $l);
}

// cadastro do escritorio: a chave nao aparece mais e fica sempre S
$atual = (object) ['url_apchat' => 'u', 'token_apchat' => 't', 'url_tela_apchat' => null, 'url_apmail' => null, 'token_apmail' => null, 'apchat_abrir_app' => 'N', 'apmail_abrir_app' => 'N'];
$d = EscritorioForm::segredosAoSalvar((object) ['url_apchat' => 'u', 'token_apchat' => ''], $atual, true);
ok('admin salva: chave sempre S (nao e mais lida)', $d->apchat_abrir_app === 'S' && $d->apmail_abrir_app === 'S', json_encode($d));
$form = (string) file_get_contents('app/control/configuracoes/EscritorioForm.php');
ok('cadastro do escritorio sem o botao "Abrir só pelo app instalado"', strpos($form, "new TCheckButton('apchat_abrir_app')") === false && strpos($form, "new TCheckButton('apmail_abrir_app')") === false);

$topo = (string) file_get_contents('app/control/TopoAbrir.php');
ok('clique do topo: nunca devolve endereco de aba', strpos($topo, "'janela'") === false && strpos($topo, 'function aba(') === false);
$js = (string) file_get_contents('app/lib/include/builder/theme-builder/BuilderTemplate.js');
ok('JS: so o app, sem window.open no clique das notificacoes', strpos($js, 'window.CurciolAbrirApp') !== false && strpos($js, 'window.open(r.url') === false && strpos($js, 'CurciolAvisoApp') === false);

fim();
