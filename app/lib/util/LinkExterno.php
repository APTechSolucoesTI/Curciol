<?php
/**
 * Abrir endereco de outro sistema (APChat, APMail) numa aba com nome (11/11/2026, seguranca).
 * O endereco entra no onclick como texto JSON (aspas e apostrofos viram "/') e depois escapado para o atributo HTML:
 * htmlspecialchars sozinho nao basta, porque o navegador desfaz o &#039; antes de rodar o JavaScript. So http/https; outro esquema
 * (javascript:, data:...) nao abre nada.
 */
class LinkExterno
{
    public static function onclick(string $url, string $janela): string
    {
        $url = trim($url);
        if (!preg_match('#^https?://#i', $url))
        {
            return '';
        }
        $js = 'window.open(' . json_encode($url, JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP)
            . ', ' . json_encode($janela, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP) . '); return false;';
        return htmlspecialchars($js, ENT_QUOTES, 'UTF-8');
    }
}
