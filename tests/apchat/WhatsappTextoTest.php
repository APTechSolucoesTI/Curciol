<?php
// tests/apchat/WhatsappTextoTest.php
// Formatacao do WhatsApp/APChat no historico do cliente (11/11/2026): *negrito*, _italico_, ~tachado~, ```monoespacado```,
// `codigo`, > citacao, links clicaveis e quebras de linha - sempre com o HTML do texto escapado.
require __DIR__ . '/../bootstrap.php';

$h = function (string $t) { return WhatsappTexto::html($t); };

igual('negrito', '<b>Gabriel Teixeira</b>:<br>oi', $h("*Gabriel Teixeira*:\noi"));
igual('italico', 'um <i>teste</i> aqui', $h('um _teste_ aqui'));
igual('tachado', '<s>errado</s> certo', $h('~errado~ certo'));
igual('monoespacado (bloco)', '<code class="apc-mono">linha 1<br>linha 2</code>', $h("```linha 1\nlinha 2```"));
igual('codigo em linha', 'use <code class="apc-mono">git pull</code>', $h('use `git pull`'));
igual('combinado', '<b><i>importante</i></b>', $h('*_importante_*'));
igual('asterisco solto nao vira negrito', '2 * 3 = 6 e 4 * 5', $h('2 * 3 = 6 e 4 * 5'));
igual('palavra_com_sublinhado nao vira italico', 'nome_do_arquivo.txt', $h('nome_do_arquivo.txt'));
igual('negrito nao atravessa linha', "*a<br>b*", $h("*a\nb*"));
igual('citacao', '<div class="apc-citacao">texto citado</div>resposta', $h("> texto citado\nresposta"));
igual('HTML escapado', '&lt;script&gt;alert(1)&lt;/script&gt; <b>&lt;b&gt;</b>', $h('<script>alert(1)</script> *<b>*'));
igual('link clicavel, sem formatar o _ do link', 'veja <a href="https://www.youtube.com/watch?v=_ZumaeaYdYc" target="_blank" rel="noopener noreferrer">https://www.youtube.com/watch?v=_ZumaeaYdYc</a>.',
      $h('veja https://www.youtube.com/watch?v=_ZumaeaYdYc.'));
igual('link com & escapado uma vez so', '<a href="https://a.com/?x=1&amp;y=2" target="_blank" rel="noopener noreferrer">https://a.com/?x=1&amp;y=2</a>', $h('https://a.com/?x=1&y=2'));
igual('so http e https viram link', 'javascript:alert(1) ftp://x', $h('javascript:alert(1) ftp://x'));
igual('aspas no texto nao quebram o link', '<a href="https://a.com" target="_blank" rel="noopener noreferrer">https://a.com</a>&quot;x', $h('https://a.com"x'));
ok('espacos do inicio da linha somem (assinatura do APChat)', strpos($h("*Leandro Zago*:\n https://x.com"), '<b>Leandro Zago</b>:<br><a href="https://x.com"') === 0, $h("*Leandro Zago*:\n https://x.com"));
igual('vazio', '', $h(''));

fim();
