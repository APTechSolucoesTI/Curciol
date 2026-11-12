<?php
// tests/apmail/EmailHtmlLimpoTest.php
// HTML de e-mail e conteudo nao confiavel (12/11/2026): lista de permitidos. Sai o que executa, carrega de fora ou envia dados;
// fica a formatacao comum. Imagens: cid (anexo) e externas viram texto; data:image fica.
require __DIR__ . '/../bootstrap.php';

$l = function (string $h) { return EmailHtmlLimpo::limpar($h); };

igual('evento onclick sai', '<p>Oi</p>', $l('<p onclick="alert(1)">Oi</p>'));
igual('script sai com o conteudo', '<p>a</p><p>b</p>', $l('<p>a</p><script>alert(1)</script><p>b</p>'));
igual('link javascript: perde o endereco', '<a>clique</a>', $l('<a href="javascript:alert(1)">clique</a>'));
igual('link https ganha nova aba e noopener', '<a href="https://a.com/x?y=1&amp;z=2" target="_blank" rel="noopener noreferrer">site</a>', $l('<a href="https://a.com/x?y=1&z=2">site</a>'));
igual('mailto fica', '<a href="mailto:a@b.com" target="_blank" rel="noopener noreferrer">a@b.com</a>', $l('<a href="mailto:a@b.com">a@b.com</a>'));
igual('imagem anexa (cid) vira texto', '<p><span class="apm-img">[imagem do e-mail]</span></p>', $l('<p><img src="cid:logo"></p>'));
igual('imagem externa vira texto (nao carrega)', '<span class="apm-img">[imagem externa]</span>', $l('<img src="https://rastreio.com/pixel.png" width="1">'));
igual('imagem data: fica', '<img src="data:image/png;base64,iVBORw0KGgo=" alt="x">', $l('<img src="data:image/png;base64,iVBORw0KGgo=" alt="x" onerror="alert(1)">'));
igual('data: que nao e imagem sai', '<span class="apm-img">[imagem do e-mail]</span>', $l('<img src="data:text/html;base64,PHNjcmlwdD4=">'));
igual('style com url() sai inteiro', '<div>x</div>', $l('<div style="background:url(https://x/y.png)">x</div>'));
igual('style comum fica', '<p style="color:red;font-weight:bold">x</p>', $l('<p style="color:red;font-weight:bold">x</p>'));
igual('style com expression sai', '<p>x</p>', $l('<p style="width:expression(alert(1))">x</p>'));
igual('formulario some', '<p>a</p>', $l('<p>a</p><form action="https://x"><input name="s"><button>ok</button></form>'));
igual('iframe, object, embed e svg somem', '<p>a</p>', $l('<p>a</p><iframe src="https://x"></iframe><object></object><embed src="x"><svg><script>1</script></svg>'));
igual('tabela e negrito ficam', '<table border="0" cellpadding="2"><tbody><tr><td align="center"><b>x</b></td></tr></tbody></table>',
      $l('<table border="0" cellpadding="2"><tbody><tr><td align="center"><b>x</b></td></tr></tbody></table>'));
igual('listas e citacao ficam', '<ul><li>a</li></ul><blockquote>b</blockquote>', $l('<ul><li>a</li></ul><blockquote>b</blockquote>'));
igual('tag desconhecida: fica so o texto', '<p>texto</p>', $l('<p><custom-tag>texto</custom-tag></p>'));
igual('acentos preservados', '<p>Ação — São Paulo</p>', $l('<p>Ação — São Paulo</p>'));
igual('comentario sai', '<p>a</p>', $l('<p>a</p><!-- [if mso]>x<![endif] -->'));
igual('meta, link, base e style somem', '<p>a</p>', $l('<html><head><meta http-equiv="refresh" content="0;url=https://x"><link rel="stylesheet" href="x"><base href="https://x"><style>p{color:red}</style></head><body><p>a</p></body></html>'));
igual('vazio', '', $l(''));
igual('so texto: escapado', 'a &lt; b', $l('a < b'));

fim();
