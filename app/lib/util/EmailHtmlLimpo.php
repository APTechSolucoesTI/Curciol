<?php
/**
 * HTML de e-mail e conteudo nao confiavel (12/11/2026, historico do APMail). Limpeza por lista de permitidos (DOM):
 * - tags perigosas saem COM o conteudo (script, iframe, form, svg...); tags desconhecidas saem e fica o texto;
 * - atributos so os de formatacao (e class); eventos (on*) nunca; style sem url()/expression/@import;
 * - 12/11/2026: links e botoes aparecem com o estilo, mas SEM endereco (nao levam a lugar nenhum); <button> vira <span>;
 *   blocos <style> (inclusive do <head>) ficam, com o CSS limpo (sem @import, url() vira none, sem expression/behavior);
 * - imagens: cid: (anexo) e externas viram texto (nao carrega nada de fora nem avisa o remetente); so data:image fica.
 * A tela ainda mostra o resultado num iframe com sandbox e CSP propria (ApmailConversaView): defesa em camadas.
 */
class EmailHtmlLimpo
{
    const PERMITIDAS = ['p', 'br', 'div', 'span', 'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'small', 'sub', 'sup', 'blockquote', 'pre', 'code',
                        'ul', 'ol', 'li', 'dl', 'dt', 'dd', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'caption', 'col', 'colgroup', 'hr',
                        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a', 'img', 'font', 'center', 'button'];
    const REMOVER = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'select', 'option', 'textarea', 'link', 'meta', 'base',
                     'svg', 'math', 'title', 'noscript', 'template', 'frame', 'frameset', 'applet', 'audio', 'video', 'canvas', 'head', 'source', 'track'];
    const ATRIBUTOS = ['style', 'align', 'valign', 'width', 'height', 'colspan', 'rowspan', 'border', 'cellpadding', 'cellspacing', 'color', 'face',
                       'size', 'dir', 'bgcolor', 'title', 'alt', 'class'];
    const VAZIAS = ['br', 'hr', 'img', 'col'];

    public static function limpar(string $html): string
    {
        if (trim($html) === '') { return ''; }
        $doc = new DOMDocument();
        $antes = libxml_use_internal_errors(true);
        $doc->loadHTML('<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $html . '</body></html>',
                       LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($antes);
        $body = $doc->getElementsByTagName('body')->item(0);
        $css = '';
        foreach ($doc->getElementsByTagName('style') as $estilo)
        {
            $css .= self::css((string) $estilo->textContent);
        }
        return ($css !== '' ? '<style>' . $css . '</style>' : '') . ($body ? self::filhos($body) : '');
    }

    /** CSS de bloco <style>: sem @import, url() vira none, sem declaracoes com expression/behavior/-moz-binding/javascript:, sem "<". */
    private static function css(string $css): string
    {
        $css = preg_replace('#/\*.*?\*/#s', '', $css);
        $css = preg_replace('/@import[^;]*;?/i', '', $css);
        $css = preg_replace('/url\s*\([^)]*\)/i', 'none', $css);
        $css = preg_replace('/[^{};]*(expression\s*\(|behavior\s*:|-moz-binding|javascript:)[^{};]*;?/i', '', $css);
        $css = str_replace(['<', '\\'], '', $css);
        return trim($css);
    }

    private static function filhos(DOMNode $no): string
    {
        $saida = '';
        foreach ($no->childNodes as $filho)
        {
            $saida .= self::no($filho);
        }
        return $saida;
    }

    private static function no(DOMNode $no): string
    {
        if ($no instanceof DOMText)
        {
            return htmlspecialchars($no->nodeValue, ENT_NOQUOTES, 'UTF-8');
        }
        if (!$no instanceof DOMElement)
        {
            return ''; // comentarios, instrucoes de processamento
        }
        $tag = strtolower($no->nodeName);
        if (in_array($tag, self::REMOVER, true))
        {
            return '';
        }
        if (!in_array($tag, self::PERMITIDAS, true))
        {
            return self::filhos($no); // html, body, tags desconhecidas: fica so o conteudo
        }
        if ($tag === 'img')
        {
            return self::imagem($no);
        }
        if ($tag === 'button')
        {
            return '<span' . self::serializar(self::atributos($no)) . '>' . self::filhos($no) . '</span>';
        }

        $atributos = self::atributos($no); // <a> nunca leva o href: aparece como link/botao, mas nao abre nada
        $abre = '<' . $tag . self::serializar($atributos) . '>';
        return in_array($tag, self::VAZIAS, true) ? $abre : $abre . self::filhos($no) . '</' . $tag . '>';
    }

    private static function imagem(DOMElement $no): string
    {
        $src = trim((string) $no->getAttribute('src'));
        if (preg_match('#^data:image/(png|jpe?g|gif|webp);base64,[a-z0-9+/=\s]+$#i', $src))
        {
            return '<img' . self::serializar(['src' => preg_replace('/\s+/', '', $src)] + self::atributos($no)) . '>';
        }
        if ($src === '') { return ''; }
        $texto = preg_match('#^https?://#i', $src) ? '[imagem externa]' : '[imagem do e-mail]';
        return '<span class="apm-img">' . $texto . '</span>';
    }

    /** Atributos permitidos, na ordem do original. */
    private static function atributos(DOMElement $no): array
    {
        $r = [];
        foreach ($no->attributes as $a)
        {
            $nome = strtolower($a->nodeName);
            if (!in_array($nome, self::ATRIBUTOS, true)) { continue; }
            $valor = trim((string) $a->nodeValue);
            if ($nome === 'style' && ($valor === '' || preg_match('/url\s*\(|expression|@import|behavior|javascript:|-moz-binding|\\\\/i', $valor))) { continue; }
            if ($nome === 'class' && !preg_match('/^[A-Za-z0-9_\- ]{1,200}$/', $valor)) { continue; }
            $r[$nome] = $valor;
        }
        return $r;
    }

    private static function serializar(array $atributos): string
    {
        $s = '';
        foreach ($atributos as $nome => $valor)
        {
            $s .= ' ' . $nome . '="' . htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') . '"';
        }
        return $s;
    }
}
