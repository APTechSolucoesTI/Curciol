<?php
/**
 * Logos do APChat e do APMail para os icones do topo (10/11/2026), redesenhados em SVG a partir das imagens enviadas pelo usuario
 * (nitidos em qualquer tamanho): APChat = balao azul-marinho (#1B4A6E) com o robo branco; APMail = envelope azul (#13799C).
 */
class LogoSistemaService
{
    const COR_APCHAT = '#1B4A6E';
    const COR_APMAIL = '#13799C';

    public static function apchat(int $px = 22): string
    {
        $c = self::COR_APCHAT;
        return '<svg class="apchat-logo" width="' . $px . '" height="' . $px . '" viewBox="0 0 24 24" aria-label="APChat" role="img">'
             . '<path fill="' . $c . '" d="M5.5 1.5h13a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H10l-5.6 3.4a.6.6 0 0 1-.9-.5v-3.2a4 4 0 0 1-2-3.5V5.5a4 4 0 0 1 4-4z"/>'
             . '<circle cx="12" cy="4.3" r="1" fill="#fff"/><rect x="11.45" y="4.8" width="1.1" height="2" fill="#fff"/>'
             . '<rect x="4.9" y="9.2" width="1.4" height="3.6" rx=".6" fill="#fff"/><rect x="17.7" y="9.2" width="1.4" height="3.6" rx=".6" fill="#fff"/>'
             . '<rect x="6.6" y="6.6" width="10.8" height="9" rx="2.6" fill="#fff"/>'
             . '<path fill="' . $c . '" d="M9.4 8.7h5.2a1.3 1.3 0 0 1 1.3 1.3v2.2a1.3 1.3 0 0 1-1.3 1.3h-3.3l-1.6 1.1v-1.1h-.3a1.3 1.3 0 0 1-1.3-1.3V10a1.3 1.3 0 0 1 1.3-1.3z"/>'
             . '<circle cx="10.5" cy="11.1" r=".65" fill="#fff"/><circle cx="12" cy="11.1" r=".65" fill="#fff"/><circle cx="13.5" cy="11.1" r=".65" fill="#fff"/>'
             . '</svg>';
    }

    public static function apmail(int $px = 22): string
    {
        $c = self::COR_APMAIL;
        return '<svg class="apmail-logo" width="' . $px . '" height="' . $px . '" viewBox="0 0 24 24" aria-label="APMail" role="img" fill="none" stroke="' . $c . '"'
             . ' stroke-width="2" stroke-linejoin="round" stroke-linecap="round"><rect x="2.5" y="4.5" width="19" height="15" rx="2.2"/><path d="M3.5 6.2 12 12.8l8.5-6.6"/></svg>';
    }
}
