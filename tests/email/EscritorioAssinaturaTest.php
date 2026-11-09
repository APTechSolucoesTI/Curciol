<?php
// tests/email/EscritorioAssinaturaTest.php
// Assinatura padrao no cadastro do escritorio (09/11/2026) e a leitura dos arquivos enviados pelos campos TFile/TMultiFile
// (so dentro de tmp/). Transacao desfeita; arquivos criados sao apagados.
require __DIR__ . '/../bootstrap.php';

const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

@mkdir('tmp', 0775, true);
$enviado = function (string $nome, string $conteudo): string { $n = uniqid() . '_' . $nome; file_put_contents("tmp/{$n}", $conteudo); return $n; };
$criadosTmp = [];
$antes = glob('assinaturas/*') ?: [];

// ---------- EmailUploadCampo ----------
$n1 = $enviado('logo_escritorio.png', base64_decode(PNG_1X1));
$criadosTmp[] = "tmp/{$n1}";
igual('TFile simples: caminho em tmp/ e nome original', [["tmp/{$n1}", 'logo_escritorio.png']], EmailUploadCampo::arquivos($n1));
$json = urlencode(json_encode(['fileName' => "tmp/{$n1}"]));
igual('TFile com file handling (JSON)', [["tmp/{$n1}", 'logo_escritorio.png']], EmailUploadCampo::arquivos($json));
igual('JSON marcado para apagar nao conta', [], EmailUploadCampo::arquivos(urlencode(json_encode(['fileName' => '', 'delFile' => "tmp/{$n1}"]))));
igual('TMultiFile: lista', 2, count(EmailUploadCampo::arquivos([$n1, $n1])));
igual('vazio', [], EmailUploadCampo::arquivos(''));
igual('null', [], EmailUploadCampo::arquivos(null));
lanca('caminho fora de tmp/ recusado', fn() => EmailUploadCampo::arquivos(urlencode(json_encode(['fileName' => 'app/config/escritorio.php']))), 'Arquivo enviado inválido');
lanca('../ no nome nao sai de tmp/', fn() => EmailUploadCampo::arquivos('../app/config/escritorio.php'), 'Arquivo enviado inválido');
lanca('arquivo que nao existe', fn() => EmailUploadCampo::arquivos('naoexiste_x.png'), 'Arquivo enviado inválido');

// ---------- salvarAssinatura ----------
TTransaction::open('escritorio');
try
{
    $config = EmailConfig::where('escritorio_id', 'is not', null)->orderBy('id')->first();
    ok('ha configuracao de e-mail', (bool) $config);
    $original = $config->assinatura_id;

    EscritorioForm::salvarAssinatura($config, ['assinatura_arquivo' => $n1]);
    ok('subir PNG grava assinatura_id', (int) $config->assinatura_id > 0);
    igual('gravado no banco', (int) $config->assinatura_id, (int) EmailConfig::find($config->id)->assinatura_id);
    $primeira = (int) $config->assinatura_id;
    igual('nome original na assinatura', 'logo_escritorio.png', EmailAssinatura::find($primeira)->nome_original);

    EscritorioForm::salvarAssinatura($config, []);
    igual('salvar sem mexer mantem a atual', $primeira, (int) EmailConfig::find($config->id)->assinatura_id);

    EscritorioForm::salvarAssinatura($config, ['assinatura_arquivo' => '']);
    igual('campo vazio mantem a atual', $primeira, (int) EmailConfig::find($config->id)->assinatura_id);

    $falso = $enviado('falso.png', 'nao sou imagem');
    $criadosTmp[] = "tmp/{$falso}";
    lanca('arquivo invalido mostra o erro', fn() => EscritorioForm::salvarAssinatura($config, ['assinatura_arquivo' => $falso]), 'não é uma imagem');
    igual('arquivo invalido nao troca a atual', $primeira, (int) EmailConfig::find($config->id)->assinatura_id);

    EscritorioForm::salvarAssinatura($config, ['assinatura_remover' => 'S', 'assinatura_arquivo' => $n1]);
    igual('marcar Remover assinatura zera (mesmo com arquivo)', null, EmailConfig::find($config->id)->assinatura_id);

    ok('previa sem assinatura', strpos(EscritorioForm::previaAssinatura(null), 'sem assinatura') !== false);
    ok('previa com assinatura relativa', strpos(EscritorioForm::previaAssinatura($primeira), 'src="assinaturas/') !== false);
}
catch (Throwable $e)
{
    ok('sem excecao inesperada', false, $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
finally
{
    TTransaction::rollback();
    foreach ($criadosTmp as $f) { @unlink($f); }
    foreach (array_diff(glob('assinaturas/*') ?: [], $antes) as $f) { @unlink($f); }
}

fim();
