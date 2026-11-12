<?php
// tests/apmail/EmailHtmlLimpoTest.php
// HTML de e-mail e conteudo nao confiavel (12/11/2026): lista de permitidos. Sai o que executa, carrega de fora ou envia dados;
// fica a formatacao comum. Imagens: cid (anexo) e externas viram texto; data:image fica.
require __DIR__ . '/../bootstrap.php';

$l = function (string $h) { return EmailHtmlLimpo::limpar($h); };

igual('evento onclick sai', '<p>Oi</p>', $l('<p onclick="alert(1)">Oi</p>'));
igual('script sai com o conteudo', '<p>a</p><p>b</p>', $l('<p>a</p><script>alert(1)</script><p>b</p>'));
igual('link javascript: perde o endereco', '<a>clique</a>', $l('<a href="javascript:alert(1)">clique</a>'));
igual('link aparece mas sem endereco (nao leva a lugar nenhum)', '<a>site</a>', $l('<a href="https://a.com/x?y=1&z=2" target="_top">site</a>'));
igual('botao feito de link: estilo mantido, sem endereco', '<a style="background-color:#4CAF50;color:#fff;padding:8px 16px;border-radius:4px">Confirmar</a>',
      $l('<a href="https://curciol.com/confirmar?t=1" style="background-color:#4CAF50;color:#fff;padding:8px 16px;border-radius:4px">Confirmar</a>'));
igual('<button> vira texto com o mesmo estilo', '<span style="background:#e53935;color:#fff">Cancelar</span>', $l('<button type="submit" onclick="x()" style="background:#e53935;color:#fff">Cancelar</button>'));
igual('bloco <style> e classes mantidos, sem url(), @import e expression', '<style>.btn{color:red;background:none}.x{}</style><a class="btn primario">Ok</a>',
      $l('<style>@import url(https://x/a.css);.btn{color:red;background:url(https://x/y.png)}.x{width:expression(alert(1))}</style><a class="btn primario" href="https://x">Ok</a>'));
igual('classe com caracteres estranhos sai', '<p>a</p>', $l('<p class="a&quot; onmouseover=&quot;x">a</p>'));
igual('mailto tambem sem endereco', '<a>a@b.com</a>', $l('<a href="mailto:a@b.com">a@b.com</a>'));
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
igual('meta, link e base somem; style vira so o CSS limpo', '<style>p{color:red}</style><p>a</p>', $l('<html><head><meta http-equiv="refresh" content="0;url=https://x"><link rel="stylesheet" href="x"><base href="https://x"><style>p{color:red}</style></head><body><p>a</p></body></html>'));
igual('vazio', '', $l(''));
igual('so texto: escapado', 'a &lt; b', $l('a < b'));

fim();
