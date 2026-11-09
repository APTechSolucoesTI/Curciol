<?php
// tests/email/AssinaturaServiceTest.php
// Assinatura do e-mail hospedada pela Curciol (09/11/2026): imagem, HTML e ZIP do Outlook viram registro com src relativo
// para a pasta publica assinaturas/. Tudo numa transacao desfeita; os arquivos criados em assinaturas/ sao apagados.
require __DIR__ . '/../bootstrap.php';

const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
const GIF_1X1 = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

$tmp = sys_get_temp_dir() . '/curciol-assinatura-teste-' . getmypid();
@mkdir($tmp, 0777, true);
$arquivo = function (string $nome, string $conteudo) use ($tmp): string { $p = "{$tmp}/{$nome}"; @mkdir(dirname($p), 0777, true); file_put_contents($p, $conteudo); return $p; };
$antes = glob('assinaturas/*') ?: [];
$novos = function () use ($antes) { return array_values(array_diff(glob('assinaturas/*') ?: [], $antes)); };
$srcs = function (string $html) { preg_match_all('/src="([^"]*)"/', $html, $m); return $m[1]; };

TTransaction::open('escritorio');
try
{
    // ---------- imagem ----------
    $png = $arquivo('logo.png', base64_decode(PNG_1X1));
    $a = EmailAssinaturaService::criarDeArquivo($png, 'Logo Escritório.png');
    igual('PNG: tipo IMAGEM', EmailAssinatura::IMAGEM, $a->tipo);
    igual('PNG: nome original guardado', 'Logo Escritório.png', $a->nome_original);
    ok('PNG: registro gravado', (int) $a->id > 0);
    $s = $srcs($a->html);
    ok('PNG vira <img> hospedado', count($s) === 1 && preg_match('#^assinaturas/[0-9a-f]{40}\.png$#', $s[0]) === 1, $a->html);
    ok('PNG: arquivo hospedado identico ao original', isset($s[0]) && is_file($s[0]) && md5_file($s[0]) === md5_file($png));

    $gif = EmailAssinaturaService::criarDeArquivo($arquivo('logo.gif', base64_decode(GIF_1X1)), 'logo.gif');
    ok('GIF aceito', preg_match('#src="assinaturas/[0-9a-f]{40}\.gif"#', $gif->html) === 1, $gif->html);

    if (function_exists('imagejpeg'))
    {
        $im = imagecreatetruecolor(4, 2);
        imagejpeg($im, "{$tmp}/logo.jpg");
        $jpg = EmailAssinaturaService::criarDeArquivo("{$tmp}/logo.jpg", 'logo.JPEG');
        ok('JPG aceito', preg_match('#src="assinaturas/[0-9a-f]{40}\.jpg"#', $jpg->html) === 1, $jpg->html);
        ok('JPG: largura e altura no <img>', strpos($jpg->html, 'width="4"') !== false && strpos($jpg->html, 'height="2"') !== false, $jpg->html);
    }
    else
    {
        ok('GD com JPEG no servidor de testes', false);
    }

    $qtd = count($novos());
    lanca('texto com extensao .png recusado', fn() => EmailAssinaturaService::criarDeArquivo($arquivo('falso.png', '<?php echo 1;'), 'falso.png'), 'não é uma imagem');
    lanca('.php recusado', fn() => EmailAssinaturaService::criarDeArquivo($arquivo('x.php', '<?php echo 1;'), 'x.php'), 'Formato não aceito');
    lanca('.svg recusado', fn() => EmailAssinaturaService::criarDeArquivo($arquivo('x.svg', '<svg></svg>'), 'x.svg'), 'Formato não aceito');
    lanca('.pdf recusado', fn() => EmailAssinaturaService::criarDeArquivo($arquivo('x.pdf', '%PDF-1.4'), 'x.pdf'), 'Formato não aceito');
    lanca('acima de 2 MB recusado', fn() => EmailAssinaturaService::criarDeArquivo($arquivo('grande.png', base64_decode(PNG_1X1) . str_repeat("\0", 2 * 1024 * 1024)), 'grande.png'), '2 MB');
    igual('recusados nao deixam arquivo em assinaturas/', $qtd, count($novos()));

    // ---------- HTML ----------
    $html = '<html><head><meta charset="utf-8"><title>x</title><style>p{color:#333}</style><script>alert(1)</script></head><body>'
          . '<!-- comentario --><p onclick="roubar()">Dr. José <b>Curciol</b></p>'
          . '<img src="data:image/png;base64,' . PNG_1X1 . '" alt="logo">'
          . '<img src="https://exemplo.com/logo.png">'
          . '<a href="javascript:alert(1)">x</a><iframe src="https://mal.com"></iframe><form><input name="a"></form>'
          . '<svg><a xlink:href="javascript:alert(2)"><text>y</text></a></svg><div data-url=" JaVaScRiPt:alert(3)">z</div>'
          . '</body></html>';
    $h = EmailAssinaturaService::criarDeArquivo($arquivo('assinatura.html', $html), 'assinatura.html');
    igual('HTML: tipo HTML', EmailAssinatura::HTML, $h->tipo);
    $s = $srcs($h->html);
    ok('HTML: data:image base64 hospedado', isset($s[0]) && preg_match('#^assinaturas/[0-9a-f]{40}\.png$#', $s[0]) === 1 && strpos($h->html, 'data:') === false, $h->html);
    ok('HTML: https mantido', in_array('https://exemplo.com/logo.png', $s, true));
    ok('HTML: texto com acento mantido', strpos($h->html, 'Dr. José') !== false && strpos($h->html, '<b>Curciol</b>') !== false, $h->html);
    ok('HTML: estilo mantido', strpos($h->html, 'p{color:#333}') !== false);
    foreach (['<script', 'onclick', 'javascript:', '<iframe', '<form', '<input', '<!--', '<title', '<meta'] as $proibido)
    {
        ok("HTML: sem {$proibido}", stripos($h->html, $proibido) === false, $h->html);
    }

    // HTML do Outlook sozinho: imagem numa pasta ao lado
    $outlook = '<html><body><p>Equipe</p><img src="assinatura_arquivos/image001.png"></body></html>';
    $qtd = count($novos());
    try { EmailAssinaturaService::criarDeArquivo($arquivo('assinatura.htm', $outlook), 'assinatura.htm'); $erro = ''; }
    catch (Exception $e) { $erro = $e->getMessage(); }
    ok('HTML do Outlook sozinho recusado com a imagem que falta', strpos($erro, 'assinatura_arquivos/image001.png') !== false, $erro);
    ok('HTML do Outlook sozinho: mensagem sugere o ZIP', stripos($erro, 'ZIP') !== false, $erro);
    igual('HTML recusado nao deixa arquivo', $qtd, count($novos()));

    // ---------- ZIP do Outlook ----------
    $fora = sys_get_temp_dir() . '/curciol-zipslip-' . getmypid() . '.txt';
    $zip = new ZipArchive();
    $zip->open("{$tmp}/assinatura.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('Assinaturas/Equipe.htm', '<html><body><p>Equipe</p><img src="Equipe_arquivos/image%20001.png"></body></html>');
    $zip->addFromString('Assinaturas/Equipe_arquivos/image 001.png', base64_decode(PNG_1X1));
    $zip->addFromString('../curciol-zipslip-' . getmypid() . '.txt', 'nao pode sair');
    $zip->close();
    $z = EmailAssinaturaService::criarDeArquivo("{$tmp}/assinatura.zip", 'assinatura.zip');
    $s = $srcs($z->html);
    ok('ZIP do Outlook: imagem da pasta hospedada', isset($s[0]) && preg_match('#^assinaturas/[0-9a-f]{40}\.png$#', $s[0]) === 1, $z->html);
    ok('ZIP do Outlook: texto do HTML', strpos($z->html, 'Equipe') !== false);
    ok('ZIP: caminho com ../ nao sai da pasta', !file_exists($fora));

    $zip = new ZipArchive();
    $zip->open("{$tmp}/sem-html.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('image001.png', base64_decode(PNG_1X1));
    $zip->close();
    lanca('ZIP sem HTML recusado', fn() => EmailAssinaturaService::criarDeArquivo("{$tmp}/sem-html.zip", 'sem-html.zip'), 'não tem um arquivo HTML');

    // ---------- html(): relativo na previa, absoluto no e-mail ----------
    ok('html() relativo', strpos(EmailAssinaturaService::html($a, ''), 'src="assinaturas/') !== false);
    ok('html() absoluto', strpos(EmailAssinaturaService::html($a, 'https://x.com.br:90/'), 'src="https://x.com.br:90/assinaturas/') !== false);
    ok('html() absoluto nao mexe em https de terceiros', strpos(EmailAssinaturaService::html($h, 'https://x.com.br:90'), 'src="https://exemplo.com/logo.png"') !== false);
    igual('html() sem assinatura', '', EmailAssinaturaService::html(null, 'https://x.com.br'));

    // ---------- resolver ----------
    igual('resolver PADRAO usa a do escritorio', 7, EmailAssinaturaService::resolver(TemplateEscritorio::ASSINATURA_PADRAO, 5, 7));
    igual('resolver OUTRA usa a do template', 5, EmailAssinaturaService::resolver(TemplateEscritorio::ASSINATURA_OUTRA, 5, 7));
    igual('resolver NENHUMA', null, EmailAssinaturaService::resolver(TemplateEscritorio::ASSINATURA_NENHUMA, 5, 7));
    igual('resolver PADRAO sem assinatura no escritorio', null, EmailAssinaturaService::resolver(TemplateEscritorio::ASSINATURA_PADRAO, 5, null));

    // ---------- doEscritorio ----------
    $cfg = EmailConfig::where('assinatura_id', 'is', null)->first();
    if ($cfg)
    {
        $cfg->assinatura_id = $a->id;
        $cfg->store();
        igual('doEscritorio le email_config.assinatura_id', (int) $a->id, EmailAssinaturaService::doEscritorio((int) $cfg->escritorio_id));
    }
    igual('doEscritorio sem configuracao', null, EmailAssinaturaService::doEscritorio(-1));
}
catch (Throwable $e)
{
    ok('sem excecao inesperada', false, $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
finally
{
    TTransaction::rollback();
    foreach ($novos() as $f) { @unlink($f); }
    exec('rm -rf ' . escapeshellarg($tmp));
    @unlink($fora ?? '');
}

fim();
