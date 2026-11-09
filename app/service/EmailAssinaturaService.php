<?php
/**
 * Assinatura do e-mail hospedada pela propria Curciol (09/11/2026).
 *
 * O usuario so sobe o arquivo (PNG, JPG, GIF, HTML ou o ZIP da assinatura do Outlook: o .htm e a pasta de imagens ao lado).
 * As imagens vao para a pasta publica assinaturas/ do site, com nome aleatorio de 40 hex que nunca e sobrescrito nem
 * apagado (e-mails ja enviados continuam apontando para ele). O registro (EmailAssinatura) guarda o HTML pronto da
 * assinatura com src RELATIVO (assinaturas/<hash>.png); html() poe o endereco do sistema na frente na hora do envio.
 *
 * HTML: so HTML de e-mail - sem script, formulario, iframe, eventos on*, javascript: e comentarios (some o VML do Outlook;
 * o <img> de reserva fica). Imagem embutida em base64 e hospedada; https:// de terceiros fica como esta; caminho de arquivo
 * do computador so funciona dentro do ZIP - sozinho, recusa com a lista do que falta.
 */
class EmailAssinaturaService
{
    const PASTA = 'assinaturas';

    const MAX_ARQUIVO = 2 * 1024 * 1024;
    const MAX_ZIP     = 5 * 1024 * 1024;

    const IMAGENS = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_GIF => 'gif'];

    // elementos que nao sao de e-mail (o conteudo vai junto)
    const PROIBIDOS = ['script', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'form', 'input', 'button',
                       'textarea', 'select', 'link', 'meta', 'base', 'title', 'noscript'];

    /**
     * Cria a assinatura a partir de um arquivo enviado. Usa a transacao aberta.
     *
     * @throws Exception com mensagem para o usuario
     */
    public static function criarDeArquivo(string $caminho, string $nomeOriginal): EmailAssinatura
    {
        if (!is_file($caminho))
        {
            throw new Exception('Arquivo da assinatura não encontrado. Envie de novo.');
        }

        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if (in_array($ext, ['png', 'jpg', 'jpeg', 'gif'], true))
        {
            self::exigirTamanho($caminho, self::MAX_ARQUIVO, 'A assinatura passa de 2 MB.');
            $tipo = EmailAssinatura::IMAGEM;
            $html = self::tagDaImagem(self::hospedarImagem($caminho, $nomeOriginal));
        }
        elseif (in_array($ext, ['html', 'htm'], true))
        {
            self::exigirTamanho($caminho, self::MAX_ARQUIVO, 'A assinatura passa de 2 MB.');
            $tipo = EmailAssinatura::HTML;
            $html = self::limparHtml((string) file_get_contents($caminho), function () { return null; });
        }
        elseif ($ext === 'zip')
        {
            self::exigirTamanho($caminho, self::MAX_ZIP, 'O ZIP da assinatura passa de 5 MB.');
            $tipo = EmailAssinatura::HTML;
            $html = self::htmlDoZip($caminho);
        }
        else
        {
            throw new Exception('Formato não aceito para a assinatura: use PNG, JPG, GIF, HTML ou o ZIP da assinatura do Outlook.');
        }

        $assinatura = new EmailAssinatura();
        $assinatura->nome_original = mb_substr($nomeOriginal, 0, 255);
        $assinatura->tipo = $tipo;
        $assinatura->html = $html;
        $assinatura->system_user_id = TSession::getValue('userid') ?: null;
        $assinatura->store();

        return $assinatura;
    }

    /** HTML da assinatura; $base '' = relativo (previa dentro da Curciol), senao o endereco do sistema (e-mail). */
    public static function html(?EmailAssinatura $assinatura, string $base): string
    {
        if (!$assinatura)
        {
            return '';
        }

        $html = (string) $assinatura->html;
        $base = rtrim(trim($base), '/');

        if ($base === '')
        {
            return $html;
        }

        return preg_replace('/(\ssrc=)(["\'])' . preg_quote(self::PASTA, '/') . '\//i', '$1$2' . $base . '/' . self::PASTA . '/', $html);
    }

    /** Assinatura que vale: PADRAO = a do escritorio, OUTRA = a do template, NENHUMA = sem. */
    public static function resolver(string $modo, ?int $assinaturaDoTemplate, ?int $assinaturaDoEscritorio): ?int
    {
        if ($modo === TemplateEscritorio::ASSINATURA_NENHUMA)
        {
            return null;
        }
        if ($modo === TemplateEscritorio::ASSINATURA_OUTRA)
        {
            return $assinaturaDoTemplate ?: null;
        }

        return $assinaturaDoEscritorio ?: null;
    }

    /** Assinatura padrao do escritorio (email_config). Usa a transacao aberta. */
    public static function doEscritorio(int $escritorioId): ?int
    {
        $config = EmailConfig::where('escritorio_id', '=', $escritorioId)->first();

        return $config && $config->assinatura_id ? (int) $config->assinatura_id : null;
    }

    // =================================================================
    // Apoio
    // =================================================================

    private static function exigirTamanho(string $caminho, int $maximo, string $mensagem): void
    {
        if ((int) filesize($caminho) > $maximo)
        {
            throw new Exception($mensagem);
        }
    }

    /** Copia a imagem para a pasta publica. @return array [src relativo, largura, altura] */
    private static function hospedarImagem(string $caminho, string $nome): array
    {
        $info = @getimagesize($caminho);

        if (!$info || !isset(self::IMAGENS[$info[2]]))
        {
            throw new Exception("O arquivo \"{$nome}\" não é uma imagem PNG, JPG ou GIF válida.");
        }

        if (!is_dir(self::PASTA) && !@mkdir(self::PASTA, 0775, true))
        {
            throw new Exception('Não foi possível criar a pasta das assinaturas.');
        }

        do
        {
            $destino = self::PASTA . '/' . bin2hex(random_bytes(20)) . '.' . self::IMAGENS[$info[2]];
        }
        while (file_exists($destino));

        if (!@copy($caminho, $destino))
        {
            throw new Exception('Não foi possível guardar a imagem da assinatura.');
        }
        @chmod($destino, 0664);

        return [$destino, (int) $info[0], (int) $info[1]];
    }

    private static function tagDaImagem(array $imagem): string
    {
        [$src, $largura, $altura] = $imagem;

        return "<img src=\"{$src}\" width=\"{$largura}\" height=\"{$altura}\" alt=\"Assinatura\" style=\"display:block;border:0;max-width:100%;height:auto;\">";
    }

    /**
     * Limpa o HTML e hospeda as imagens. $resolver(src) devolve o caminho local de uma imagem relativa (ZIP) ou null.
     */
    private static function limparHtml(string $html, callable $resolver): string
    {
        if (!mb_check_encoding($html, 'UTF-8'))
        {
            // assinatura do Outlook costuma vir em windows-1252
            $html = mb_convert_encoding($html, 'UTF-8', 'Windows-1252');
        }
        $html = preg_replace('/<meta[^>]*charset[^>]*>/i', '', $html);

        $doc = new DOMDocument();
        $anterior = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="curciol-raiz">' . self::semCabecalho($html) . '</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $xpath = new DOMXPath($doc);

        foreach (iterator_to_array($xpath->query('//comment()')) as $no)
        {
            $no->parentNode->removeChild($no);
        }
        foreach (self::PROIBIDOS as $tag)
        {
            foreach (iterator_to_array($doc->getElementsByTagName($tag)) as $no)
            {
                $no->parentNode->removeChild($no);
            }
        }
        foreach (iterator_to_array($xpath->query('//@*')) as $atributo)
        {
            $nome = strtolower($atributo->nodeName);
            $valor = strtolower(preg_replace('/\s+/', '', (string) $atributo->nodeValue));

            // qualquer atributo (xlink:href do SVG, data-*, ...) com javascript:/vbscript: sai
            if (strpos($nome, 'on') === 0
                || preg_match('/^(javascript|vbscript):/', $valor)
                || (substr($nome, -4) === 'href' && strpos($valor, 'data:') === 0))
            {
                $atributo->ownerElement->removeAttributeNode($atributo);
            }
        }

        $faltando = [];
        foreach (iterator_to_array($doc->getElementsByTagName('img')) as $img)
        {
            $src = trim((string) $img->getAttribute('src'));

            if (preg_match('#^data:image/(png|jpe?g|gif);base64,(.+)$#is', $src, $m))
            {
                $temporario = tempnam(sys_get_temp_dir(), 'curciol-assinatura-');
                file_put_contents($temporario, base64_decode(preg_replace('/\s+/', '', $m[2])));
                try { $img->setAttribute('src', self::hospedarImagem($temporario, 'imagem embutida no HTML')[0]); }
                finally { @unlink($temporario); }
            }
            elseif (preg_match('#^https?://#i', $src))
            {
                continue;
            }
            elseif ($src !== '' && ($local = $resolver($src)) !== null)
            {
                $img->setAttribute('src', self::hospedarImagem($local, basename($local))[0]);
            }
            elseif ($src !== '')
            {
                $faltando[] = $src;
            }
        }

        if ($faltando)
        {
            throw new Exception('A assinatura em HTML aponta para imagens que não vieram junto: ' . implode(', ', array_unique($faltando))
                . '. Envie o ZIP com o arquivo HTML e a pasta das imagens (no Outlook, a pasta Assinaturas) ou a assinatura como imagem.');
        }

        $raiz = $doc->getElementById('curciol-raiz');
        $saida = '';
        foreach ($raiz ? iterator_to_array($raiz->childNodes) : [] as $filho)
        {
            $saida .= $doc->saveHTML($filho);
        }

        return trim($saida);
    }

    /** Os <style> do cabecalho e o conteudo do <body> (sem html/head/body). */
    private static function semCabecalho(string $html): string
    {
        $estilos = '';
        if (preg_match_all('#<style\b[^>]*>.*?</style>#is', $html, $m))
        {
            $estilos = implode('', $m[0]);
        }
        if (preg_match('#<body\b[^>]*>(.*)</body>#is', $html, $m))
        {
            return $estilos . $m[1];
        }

        return preg_replace('#<head\b[^>]*>.*?</head>#is', $estilos, preg_replace('#</?(html|body)\b[^>]*>#i', '', $html));
    }

    /** ZIP do Outlook: o primeiro .htm/.html e as imagens que ele usa, na pasta ao lado. Entradas com .. sao ignoradas. */
    private static function htmlDoZip(string $caminho): string
    {
        $zip = new ZipArchive();
        if ($zip->open($caminho) !== true)
        {
            throw new Exception('Não foi possível abrir o ZIP da assinatura.');
        }

        $pasta = sys_get_temp_dir() . '/curciol-assinatura-zip-' . bin2hex(random_bytes(8));
        mkdir($pasta, 0700, true);

        try
        {
            $htmlDoZip = null;
            for ($i = 0; $i < $zip->numFiles; $i++)
            {
                $nome = str_replace('\\', '/', (string) $zip->getNameIndex($i));

                if ($nome === '' || substr($nome, -1) === '/' || $nome[0] === '/' || strpos($nome, ':') !== false
                    || in_array('..', explode('/', $nome), true) || strpos($nome, '__MACOSX/') === 0)
                {
                    continue;
                }

                $destino = "{$pasta}/{$nome}";
                @mkdir(dirname($destino), 0700, true);
                file_put_contents($destino, (string) $zip->getFromIndex($i));

                if ($htmlDoZip === null && preg_match('/\.html?$/i', $nome))
                {
                    $htmlDoZip = $destino;
                }
            }
            $zip->close();

            if ($htmlDoZip === null)
            {
                throw new Exception('O ZIP da assinatura não tem um arquivo HTML.');
            }

            $dirHtml = dirname($htmlDoZip);
            $raiz = realpath($pasta);

            return self::limparHtml((string) file_get_contents($htmlDoZip), function (string $src) use ($dirHtml, $raiz) {
                $relativo = rawurldecode(preg_replace('/[?#].*$/', '', str_replace('\\', '/', $src)));
                $local = realpath($dirHtml . '/' . $relativo);

                return $local && strpos($local, $raiz . '/') === 0 && is_file($local) ? $local : null;
            });
        }
        finally
        {
            self::apagarPasta($pasta);
        }
    }

    /** PHP-FPM do servidor bloqueia exec: apaga a pasta temporaria do ZIP em PHP. */
    private static function apagarPasta(string $pasta): void
    {
        if (!is_dir($pasta))
        {
            return;
        }
        $itens = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($itens as $item)
        {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($pasta);
    }
}
