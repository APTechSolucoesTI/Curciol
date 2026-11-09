<?php
/**
 * Montagem do e-mail (09/11/2026): botoes de confirmar e cancelar do template de confirmacao, corpo com a assinatura no fim
 * e a previa fiel usada no template, no envio, no Visualizar e no historico.
 *
 * Botoes: o usuario so escolhe texto e cor no template (Confirmacao + E-mail); eles entram sozinhos no fim do texto ou no
 * lugar de {$botoes}, apontando para os links de sempre ({$link_confirmacao} / {$link_cancelamento}, trocados depois pelo
 * TemplateEscritorio::replace). Template antigo que ja tem esses links no texto nao ganha botoes (nao duplica).
 */
class EmailMontagemService
{
    const VARIAVEL = '{$botoes}';

    const CONFIRMAR_TEXTO = 'Confirmar presença';
    const CONFIRMAR_COR   = '#2e7d32';
    const CANCELAR_TEXTO  = 'Cancelar agendamento';
    const CANCELAR_COR    = '#c62828';

    /** Template de confirmacao por e-mail sem os links antigos no texto. */
    public static function temBotoes(?TemplateEscritorio $template): bool
    {
        return $template
            && $template->tipo_template === TemplateEscritorio::EMAIL
            && $template->categoria === TemplateEscritorio::CONFIRMACAO
            && !self::textoTemLinks((string) $template->template);
    }

    /** Confirmacao por e-mail que ainda usa {$link_confirmacao}/{$link_cancelamento} no texto (aviso do formulario). */
    public static function usaLinksAntigos(?TemplateEscritorio $template): bool
    {
        return $template
            && $template->tipo_template === TemplateEscritorio::EMAIL
            && $template->categoria === TemplateEscritorio::CONFIRMACAO
            && self::textoTemLinks((string) $template->template);
    }

    /** Os dois botoes lado a lado, em tabela com estilos inline (Outlook). Links ainda como variaveis. */
    public static function botoesHtml(string $textoConfirmar, string $corConfirmar, string $textoCancelar, string $corCancelar): string
    {
        return '<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:16px 0;"><tr>'
             . self::botao('{$link_confirmacao}', self::texto($textoConfirmar, self::CONFIRMAR_TEXTO), self::cor($corConfirmar, self::CONFIRMAR_COR))
             . '<td style="width:12px;"></td>'
             . self::botao('{$link_cancelamento}', self::texto($textoCancelar, self::CANCELAR_TEXTO), self::cor($corCancelar, self::CANCELAR_COR))
             . '</tr></table>';
    }

    /** Texto com os botoes do template: no lugar de {$botoes} ou no fim. Sem botoes, {$botoes} some. */
    public static function comBotoes(string $texto, ?TemplateEscritorio $template): string
    {
        if (!self::temBotoes($template))
        {
            return str_replace(self::VARIAVEL, '', $texto);
        }

        $botoes = self::botoesHtml((string) $template->botao_confirmar_texto, (string) $template->botao_confirmar_cor,
                                   (string) $template->botao_cancelar_texto, (string) $template->botao_cancelar_cor);

        if (strpos($texto, self::VARIAVEL) === false)
        {
            return $texto . $botoes;
        }

        // {$botoes} sozinho num paragrafo do editor: a tabela entra no lugar do paragrafo inteiro
        $texto = preg_replace('#<p[^>]*>\s*' . preg_quote(self::VARIAVEL, '#') . '\s*(<br\s*/?>)?\s*</p>#i', $botoes, $texto, 1, $trocou);

        return $trocou ? str_replace(self::VARIAVEL, '', $texto) : preg_replace('/' . preg_quote(self::VARIAVEL, '/') . '/', $botoes, $texto, 1);
    }

    /** Corpo final: texto (variaveis ja trocadas) e a assinatura no fim. $base '' = previa (relativo). */
    public static function corpo(string $texto, ?EmailAssinatura $assinatura, string $base): string
    {
        $html = EmailAssinaturaService::html($assinatura, $base);

        return $html === '' ? $texto : $texto . '<br><div class="curciol-assinatura" style="margin-top:8px;">' . $html . '</div>';
    }

    /** Previa como o cliente ve: assunto, corpo e anexos, numa folha branca. */
    public static function previa(string $assunto, string $corpo, array $anexoIds, ?callable $link = null): string
    {
        return '<div class="curciol-email-previa" style="border:1px solid #ddd;border-radius:6px;background:#fff;padding:14px 16px;">'
             . '<div style="margin-bottom:8px;color:#555;"><b>Assunto:</b> ' . htmlspecialchars($assunto, ENT_QUOTES, 'UTF-8') . '</div>'
             . '<hr style="margin:8px 0;">'
             . '<div class="curciol-email-corpo">' . $corpo . '</div>'
             . EmailAnexoService::listaHtml($anexoIds, $link)
             . '</div>';
    }

    // =================================================================
    // Apoio
    // =================================================================

    private static function textoTemLinks(string $texto): bool
    {
        return strpos($texto, '{$link_confirmacao}') !== false || strpos($texto, '{$link_cancelamento}') !== false;
    }

    private static function botao(string $link, string $texto, string $cor): string
    {
        $letra = self::corDaLetra($cor);
        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');

        return "<td style=\"border-radius:6px;background-color:{$cor};\">"
             . "<a href=\"{$link}\" target=\"_blank\" style=\"display:inline-block;padding:12px 22px;font-family:Arial,Helvetica,sans-serif;"
             . "font-size:15px;font-weight:bold;text-decoration:none;border-radius:6px;background-color:{$cor};color:{$letra};\">{$texto}</a></td>";
    }

    private static function texto(string $texto, string $padrao): string
    {
        $texto = trim($texto);

        return $texto !== '' ? mb_substr($texto, 0, 60) : $padrao;
    }

    private static function cor(string $cor, string $padrao): string
    {
        $cor = trim($cor);

        return preg_match('/^#[0-9a-fA-F]{6}$/', $cor) ? strtolower($cor) : $padrao;
    }

    /** Preta em fundo claro, branca em fundo escuro (YIQ). */
    private static function corDaLetra(string $cor): string
    {
        $r = hexdec(substr($cor, 1, 2));
        $g = hexdec(substr($cor, 3, 2));
        $b = hexdec(substr($cor, 5, 2));

        return (($r * 299 + $g * 587 + $b * 114) / 1000) >= 128 ? '#000000' : '#ffffff';
    }
}
