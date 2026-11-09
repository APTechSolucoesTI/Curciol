<?php
/**
 * Montagem do e-mail (09/11/2026): botoes de confirmar e cancelar do template de confirmacao, corpo com a assinatura no fim
 * e a previa fiel usada no template, no envio, no Visualizar e no historico.
 *
 * Botoes (2a rodada, 09/11/2026): so entram onde o usuario pos {$botoes} (os dois) ou {$link_confirmacao} /
 * {$link_cancelamento} (um por um), no texto ou como destino de um link do editor. Nada entra sozinho no fim. O texto e a
 * cor de cada botao vem do template; o destino continua a variavel, trocada depois pelo TemplateEscritorio::replace.
 * Fora da confirmacao por e-mail os links continuam sendo o endereco e {$botoes} some.
 *
 * Botao "a prova de Outlook": a cor vai no bgcolor da celula e o espaco interno no mso-padding-alt (o Outlook ignora padding
 * em link); nos demais leitores o link tem o mesmo espaco e o botao inteiro e clicavel.
 */
class EmailMontagemService
{
    const VARIAVEL  = '{$botoes}';
    const CONFIRMAR = '{$link_confirmacao}';
    const CANCELAR  = '{$link_cancelamento}';

    const CONFIRMAR_TEXTO = 'Confirmar presença';
    const CONFIRMAR_COR   = '#2e7d32';
    const CANCELAR_TEXTO  = 'Cancelar agendamento';
    const CANCELAR_COR    = '#c62828';

    private const TOKENS = '\{\$(?:botoes|link_confirmacao|link_cancelamento)\}';

    /** Template de confirmacao por e-mail que usa {$botoes}, {$link_confirmacao} ou {$link_cancelamento}. */
    public static function temBotoes(?TemplateEscritorio $template): bool
    {
        return self::ehConfirmacaoPorEmail($template) && self::textoTemBotoes((string) $template->template);
    }

    /** O texto tem alguma das tres variaveis dos botoes (o formulario so mostra "Botoes do cliente" assim). */
    public static function textoTemBotoes(string $texto): bool
    {
        return strpos($texto, self::VARIAVEL) !== false || strpos($texto, self::CONFIRMAR) !== false || strpos($texto, self::CANCELAR) !== false;
    }

    /** Os dois botoes lado a lado, em tabela com estilos inline (Outlook). Links ainda como variaveis. */
    public static function botoesHtml(string $textoConfirmar, string $corConfirmar, string $textoCancelar, string $corCancelar): string
    {
        $template = new TemplateEscritorio();
        $template->botao_confirmar_texto = $textoConfirmar;
        $template->botao_confirmar_cor = $corConfirmar;
        $template->botao_cancelar_texto = $textoCancelar;
        $template->botao_cancelar_cor = $corCancelar;

        return self::tabela([self::CONFIRMAR, self::CANCELAR], $template);
    }

    /** Texto com os botoes do template onde o usuario os pos. Fora da confirmacao por e-mail, so {$botoes} some. */
    public static function comBotoes(string $texto, ?TemplateEscritorio $template): string
    {
        if (!self::ehConfirmacaoPorEmail($template))
        {
            return str_replace(self::VARIAVEL, '', $texto);
        }

        // as tabelas prontas ficam guardadas ate o fim, para os href="{$link_...}" delas nao serem trocados de novo
        $prontos = [];
        $guardar = function (array $links) use (&$prontos, $template): string {
            $prontos[] = self::tabela($links, $template);
            return "\x01" . (count($prontos) - 1) . "\x01";
        };

        // link do editor (ou de template antigo) com destino numa das variaveis: vira so a variavel
        $texto = preg_replace('#<a\b[^>]*?\bhref\s*=\s*(["\'])\s*(\{\$link_(?:confirmacao|cancelamento)\})\s*\1[^>]*>.*?</a>#is', '$2', $texto);

        // paragrafo so com as variaveis (espacos, &nbsp; e <br> em volta): uma tabela no lugar do paragrafo inteiro
        $texto = preg_replace_callback('#<p\b[^>]*>((?:\s|&nbsp;|\xc2\xa0|<br\s*/?>|' . self::TOKENS . ')*)</p>#i', function ($m) use ($guardar) {
            preg_match_all('/' . self::TOKENS . '/', $m[1], $achados);
            return $achados[0] ? $guardar(self::quais($achados[0])) : $m[0];
        }, $texto);

        // no meio do texto (nunca dentro de uma tag): cada variavel vira a sua tabela
        $partes = preg_split('/(<[^>]*>)/', $texto, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($partes as $i => $parte)
        {
            if ($parte !== '' && $parte[0] !== '<')
            {
                $partes[$i] = preg_replace_callback('/' . self::TOKENS . '/', function ($m) use ($guardar) {
                    return $guardar(self::quais([$m[0]]));
                }, $parte);
            }
        }

        return preg_replace_callback("/\x01(\\d+)\x01/", function ($m) use ($prontos) { return $prontos[(int) $m[1]]; }, implode('', $partes));
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

    private static function ehConfirmacaoPorEmail(?TemplateEscritorio $template): bool
    {
        return $template
            && $template->tipo_template === TemplateEscritorio::EMAIL
            && $template->categoria === TemplateEscritorio::CONFIRMACAO;
    }

    /** Variaveis achadas, na ordem do texto, com {$botoes} aberto nos dois links. */
    private static function quais(array $variaveis): array
    {
        $links = [];
        foreach ($variaveis as $v)
        {
            array_push($links, ...($v === self::VARIAVEL ? [self::CONFIRMAR, self::CANCELAR] : [$v]));
        }
        return $links;
    }

    /** Botoes lado a lado numa tabela (o Outlook so respeita cor e espaco em celula). */
    private static function tabela(array $links, TemplateEscritorio $template): string
    {
        $celulas = [];
        foreach ($links as $link)
        {
            $celulas[] = $link === self::CONFIRMAR
                ? self::botao($link, self::texto((string) $template->botao_confirmar_texto, self::CONFIRMAR_TEXTO), self::cor((string) $template->botao_confirmar_cor, self::CONFIRMAR_COR))
                : self::botao($link, self::texto((string) $template->botao_cancelar_texto, self::CANCELAR_TEXTO), self::cor((string) $template->botao_cancelar_cor, self::CANCELAR_COR));
        }

        return '<table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:16px 0;border-collapse:separate;"><tr>'
             . implode('<td style="width:12px;font-size:0;line-height:0;">&nbsp;</td>', $celulas)
             . '</tr></table>';
    }

    private static function botao(string $link, string $texto, string $cor): string
    {
        $letra = self::corDaLetra($cor);
        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');

        return "<td align=\"center\" valign=\"middle\" bgcolor=\"{$cor}\" style=\"border-radius:6px;background-color:{$cor};mso-padding-alt:12px 24px;\">"
             . "<a href=\"{$link}\" target=\"_blank\" style=\"display:inline-block;padding:12px 24px;font-family:Arial,Helvetica,sans-serif;font-size:15px;"
             . "line-height:18px;font-weight:bold;color:{$letra};text-decoration:none;border-radius:6px;\">"
             . "<span style=\"color:{$letra};text-decoration:none;\">{$texto}</span></a></td>";
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
