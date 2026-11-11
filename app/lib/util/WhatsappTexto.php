<?php
/**
 * Texto do WhatsApp/APChat em HTML (11/11/2026): o mesmo efeito que o APChat e o WhatsApp mostram.
 * *negrito*, _italico_, ~tachado~, ```monoespacado```, `codigo`, linhas "> citacao", links http/https clicaveis
 * (nova aba) e quebras de linha. O texto e sempre escapado antes: nada do conteudo vira HTML de verdade.
 */
class WhatsappTexto
{
    public static function html(string $texto): string
    {
        if ($texto === '') { return ''; }
        $t = htmlspecialchars(str_replace("\r\n", "\n", $texto), ENT_QUOTES, 'UTF-8');

        // blocos protegidos (nao recebem negrito/italico): ```bloco```, `codigo` e links
        $guardados = [];
        $guardar = function (string $html) use (&$guardados) {
            $guardados[] = $html;
            return "\x01" . (count($guardados) - 1) . "\x02";
        };
        $t = preg_replace_callback('/```(.+?)```/s', function ($m) use ($guardar) {
            return $guardar('<code class="apc-mono">' . str_replace("\n", '<br>', $m[1]) . '</code>');
        }, $t);
        $t = preg_replace_callback('/`([^`\n]+)`/', function ($m) use ($guardar) {
            return $guardar('<code class="apc-mono">' . $m[1] . '</code>');
        }, $t);
        $t = preg_replace_callback('#https?://(?:(?!&quot;|&\#039;|&lt;|&gt;)[^\s\x01])+#i', function ($m) use ($guardar) {
            $url = $m[0];
            $sobra = '';
            while ($url !== '' && strpos('.,;:!?)', substr($url, -1)) !== false)
            {
                $sobra = substr($url, -1) . $sobra;
                $url = substr($url, 0, -1);
            }
            return $guardar('<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>') . $sobra;
        }, $t);

        // formatacao: so dentro da linha, marcador colado no texto e fora de palavra (2 * 3 e nome_do_arquivo ficam como estao)
        foreach (['*' => 'b', '_' => 'i', '~' => 's'] as $marca => $tag)
        {
            $q = preg_quote($marca, '/');
            $t = preg_replace('/(?<![\p{L}\p{N}' . $q . '])' . $q . '([^\s' . $q . '](?:[^\n' . $q . ']*[^\s' . $q . '])?)' . $q . '(?![\p{L}\p{N}' . $q . '])/u',
                              '<' . $tag . '>$1</' . $tag . '>', $t);
        }

        // linhas: "> " vira citacao; espacos do inicio da linha saem (o APChat poe um depois da assinatura)
        $saida = '';
        $linhas = explode("\n", $t);
        foreach ($linhas as $i => $linha)
        {
            $linha = ltrim($linha, " \t");
            if (strpos($linha, '&gt; ') === 0)
            {
                $saida .= '<div class="apc-citacao">' . substr($linha, 5) . '</div>';
                continue;
            }
            $saida .= $linha . ($i < count($linhas) - 1 ? '<br>' : '');
        }

        return preg_replace_callback('/\x01(\d+)\x02/', function ($m) use ($guardados) { return $guardados[(int) $m[1]]; }, $saida);
    }
}
