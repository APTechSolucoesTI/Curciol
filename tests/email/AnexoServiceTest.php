<?php
// tests/email/AnexoServiceTest.php
// Anexos do e-mail (09/11/2026): formatos aceitos, 10 MB por arquivo, 20 MB por e-mail, nome sanitizado e lista para a
// previa. Transacao desfeita; os arquivos criados em files/email/anexos sao apagados.
require __DIR__ . '/../bootstrap.php';

$tmp = sys_get_temp_dir() . '/curciol-anexo-teste-' . getmypid();
@mkdir($tmp, 0777, true);
$arquivo = function (string $nome, int $bytes) use ($tmp): string { $p = "{$tmp}/" . md5($nome); file_put_contents($p, str_repeat('a', $bytes)); return $p; };
$criados = [];

TTransaction::open('escritorio');
try
{
    $pdf = EmailAnexoService::criarDeArquivo($arquivo('contrato.pdf', 1000), 'Contrato Honorários.pdf');
    $criados[] = $pdf->caminho;
    ok('PDF aceito', (int) $pdf->id > 0);
    igual('PDF: tamanho', 1000, (int) $pdf->tamanho);
    igual('PDF: nome original preservado', 'Contrato Honorários.pdf', $pdf->nome_original);
    ok('PDF: guardado na pasta dos anexos', strpos($pdf->caminho, EmailAnexoService::PASTA . '/') === 0 && is_file($pdf->caminho), $pdf->caminho);
    ok('PDF: pasta com nome aleatorio', preg_match('#^' . preg_quote(EmailAnexoService::PASTA, '#') . '/[0-9a-f]{40}/#', $pdf->caminho) === 1, $pdf->caminho);

    foreach (['doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'zip', 'PDF'] as $ext)
    {
        $a = EmailAnexoService::criarDeArquivo($arquivo("a.{$ext}", 10), "arquivo.{$ext}");
        $criados[] = $a->caminho;
        ok(".{$ext} aceito", (int) $a->id > 0);
    }

    foreach (['exe', 'php', 'js', 'bat', 'html', 'svg', ''] as $ext)
    {
        lanca(".{$ext} recusado", fn() => EmailAnexoService::criarDeArquivo($arquivo("x.{$ext}", 10), $ext === '' ? 'sem-extensao' : "x.{$ext}"), 'Formato de anexo não aceito');
    }

    lanca('acima de 10 MB recusado', fn() => EmailAnexoService::criarDeArquivo($arquivo('grande.pdf', 10 * 1024 * 1024 + 1), 'grande.pdf'), '10 MB');

    $perigoso = EmailAnexoService::criarDeArquivo($arquivo('p.pdf', 10), '../../etc/pas<b>swd.pdf');
    $criados[] = $perigoso->caminho;
    ok('nome com ../ e barras sanitizado no caminho', strpos($perigoso->caminho, '..') === false && substr_count(substr($perigoso->caminho, strlen(EmailAnexoService::PASTA) + 1), '/') === 1, $perigoso->caminho);

    // total por e-mail
    $nove = [];
    for ($i = 0; $i < 3; $i++)
    {
        $a = EmailAnexoService::criarDeArquivo($arquivo("nove{$i}.pdf", 9 * 1024 * 1024), "nove{$i}.pdf");
        $criados[] = $a->caminho;
        $nove[] = (int) $a->id;
    }
    EmailAnexoService::validarTotal([$nove[0], $nove[1]]);
    ok('ate 20 MB passa', true);
    lanca('total acima de 20 MB recusado', fn() => EmailAnexoService::validarTotal($nove), 'Os anexos passam de 20 MB');
    EmailAnexoService::validarTotal([]);
    ok('sem anexos passa', true);

    $envio = EmailAnexoService::paraEnvio([(int) $pdf->id]);
    igual('paraEnvio devolve caminho e nome', [[realpath($pdf->caminho), 'Contrato Honorários.pdf']], $envio);
    igual('paraEnvio sem anexos', [], EmailAnexoService::paraEnvio([]));

    $lista = EmailAnexoService::listaHtml([(int) $pdf->id, (int) $perigoso->id]);
    ok('listaHtml mostra o nome', strpos($lista, 'Contrato Honorários.pdf') !== false, $lista);
    ok('listaHtml escapa o nome', strpos($lista, '<b>') === false && strpos($lista, '&lt;b&gt;') !== false, $lista);
    ok('listaHtml sem link por padrao', strpos($lista, '<a ') === false, $lista);
    $comLink = EmailAnexoService::listaHtml([(int) $pdf->id], function ($id) { return "abrir.php?id={$id}"; });
    ok('listaHtml com link', strpos($comLink, 'href="abrir.php?id=' . $pdf->id . '"') !== false, $comLink);
    igual('listaHtml vazia', '', EmailAnexoService::listaHtml([]));
}
catch (Throwable $e)
{
    ok('sem excecao inesperada', false, $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
finally
{
    TTransaction::rollback();
    foreach ($criados as $c) { @unlink($c); @rmdir(dirname($c)); }
    exec('rm -rf ' . escapeshellarg($tmp));
}

fim();
