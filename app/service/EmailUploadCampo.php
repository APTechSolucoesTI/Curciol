<?php
/**
 * Arquivos enviados pelos campos TFile/TMultiFile das telas de e-mail (09/11/2026): assinatura e anexos.
 *
 * O Adianti sobe o arquivo para tmp/ como "<uniqid>_<nome>" e o campo posta esse nome (ou, com enableFileHandling, um JSON
 * com fileName). O valor vem do navegador: so vale arquivo que existe DENTRO de tmp/.
 */
class EmailUploadCampo
{
    const PASTA = 'tmp';

    /**
     * @param string|array|null $valor valor postado pelo campo
     * @return array [[caminho em tmp/, nome original], ...]
     */
    public static function arquivos($valor): array
    {
        $lista = [];
        foreach (is_array($valor) ? $valor : [$valor] as $item)
        {
            $item = trim((string) $item);
            if ($item === '')
            {
                continue;
            }

            if (strpos($item, '%7B') === 0 || strpos($item, '{') === 0)
            {
                $json = json_decode(urldecode($item));
                if (!is_object($json) || !empty($json->delFile) || trim((string) ($json->fileName ?? '')) === '')
                {
                    continue;
                }
                $caminho = (string) $json->fileName;
            }
            else
            {
                $caminho = self::PASTA . '/' . $item;
            }

            $real = realpath($caminho);
            $pasta = realpath(self::PASTA);

            if (!$real || !$pasta || strpos($real, $pasta . DIRECTORY_SEPARATOR) !== 0 || !is_file($real))
            {
                throw new Exception('Arquivo enviado inválido. Envie de novo.');
            }

            $lista[] = [self::PASTA . '/' . basename($real), preg_replace('/^[0-9a-f]{13}_/', '', basename($real))];
        }

        return $lista;
    }

    /** O primeiro arquivo do campo, ou null. */
    public static function arquivo($valor): ?array
    {
        return self::arquivos($valor)[0] ?? null;
    }
}
