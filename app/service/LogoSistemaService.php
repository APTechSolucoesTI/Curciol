<?php
/**
 * Logos do APChat e do APMail para os icones do topo (10/11/2026): APChat = icone do logo enviado pelo usuario (PNG, sem o texto);
 * APMail = envelope azul (#13799C) em SVG. COR_APCHAT = azul-marinho das abas e titulos.
 */
class LogoSistemaService
{
    const COR_APCHAT = '#1B4A6E';
    const COR_APMAIL = '#13799C';

    /** Icone do logo do APChat enviado pelo usuario (sem o texto), recortado em app/images/apchat-icone.png. */
    public static function apchat(int $px = 22): string
    {
        return '<img class="apchat-logo" src="app/images/apchat-icone.png?v=1" width="' . $px . '" height="' . $px . '" alt="APChat" style="display:block;">';
    }

    public static function apmail(int $px = 22): string
    {
        $c = self::COR_APMAIL;
        return '<svg class="apmail-logo" width="' . $px . '" height="' . $px . '" viewBox="0 0 24 24" aria-label="APMail" role="img" fill="none" stroke="' . $c . '"'
             . ' stroke-width="2" stroke-linejoin="round" stroke-linecap="round"><rect x="2.5" y="4.5" width="19" height="15" rx="2.2"/><path d="M3.5 6.2 12 12.8l8.5-6.6"/></svg>';
    }
}
