<?php
    require_once 'init.php';
    new TSession;

   if (!isset($_GET['file'])) {
        exit('Arquivo ausente no servidor.');
    }

    if (!(TSession::getValue('logged') || TSession::getValue('portal_cliente_id'))) {
        exit('Sessão expirada ou acesso não permitido.');
    }

    // Monta caminho base real
    $requested = urldecode($_GET['file']);
    $basePath  = realpath(__DIR__);

    $rawPath = $basePath . '/' . $requested;

    // tenta NFC e NFD ANTES do realpath
    $normalized = normalize_and_check($rawPath);

    if (!$normalized) {
        exit('Arquivo não encontrado.');
    }

    $fullPath = realpath($normalized);

    // impede quebrar por url invalida
    if (!$fullPath || strpos($fullPath, $basePath) !== 0) {
        exit('Acesso não permitido.');
    }

    // Função auxiliar para tentar NFC e NFD
    function normalize_and_check($path)
    {
        if (!class_exists('Normalizer')) {
            return file_exists($path) ? $path : false;
        }

        // Tenta NFC
        $nfc = Normalizer::normalize($path, Normalizer::FORM_C);
        if (file_exists($nfc)) {
            return $nfc;
        }

        // Tenta NFD
        $nfd = Normalizer::normalize($path, Normalizer::FORM_D);
        if (file_exists($nfd)) {
            return $nfd;
        }

        return false;
    }

    $file = normalize_and_check($fullPath);
    if (!$file) {
        exit('Arquivo não encontrado.');
    }

    // Curciol (seguranca 22/10/2026): pastas do sistema nunca sao servidas; o cliente do portal (sem login da
    // equipe) so baixa o que foi gerado para ele em tmp/ (Meus documentos).
    $relativo = ltrim(str_replace('\\', '/', substr(realpath($file), strlen($basePath))), '/');
    if (preg_match('#^(\.git|\.claude|app/config|app/database|app/control|app/model|app/service|lib|vendor|tests|docs)(/|$)#i', $relativo)) {
        exit('Acesso não permitido.');
    }
    if (!TSession::getValue('logged') && strpos($relativo, 'tmp/') !== 0) {
        exit('Acesso não permitido.');
    }

    $info = pathinfo($file);
    $extension = strtolower($info['extension'] ?? '');

    $content_type_list = [
        'txt'  => 'text/plain',
        'html' => 'text/html',
        'csv'  => 'text/csv',
        'pdf'  => 'application/pdf',
        'rtf'  => 'application/rtf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'svg'  => 'image/svg+xml',
        'xml'  => 'application/xml',
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
        'bz'   => 'application/x-bzip',
        'bz2'  => 'application/x-bzip2',
        'tar'  => 'application/x-tar',
        'mp4'  => 'video/mp4'
    ];

        if (!isset($content_type_list[$extension])) {
            exit('Tipo de arquivo não permitido.');
        }

    $basename = !empty($_GET['basename'])
        ? basename($_GET['basename'])
        : basename($file);

    $filesize = filesize($file);

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Content-type: " . $content_type_list[$extension]);
    header("Content-Length: {$filesize}");
    // html, svg e xml baixam como anexo (abertos no navegador rodariam script na origem do sistema)
    $disposicao = in_array($extension, ['html', 'svg', 'xml']) ? 'attachment' : 'inline';
    header("X-Content-Type-Options: nosniff");
    if ($disposicao == 'attachment') {
        header("Content-Security-Policy: sandbox");
    }
    header("Content-Disposition: {$disposicao}; filename=\"{$basename}\"");
    header("Content-Transfer-Encoding: binary");

    readfile($file);
    exit;
