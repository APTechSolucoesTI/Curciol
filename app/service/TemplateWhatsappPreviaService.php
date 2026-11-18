<?php
/**
 * Previa do template de WhatsApp (18/11/2026): a mensagem como chega para o cliente, no balao da conversa, com os botoes
 * embaixo (como no WhatsApp). Mesmo padrao da previa do e-mail (botao Mostrar previa do cadastro de templates).
 *   - Template da Curciol: o texto final que sai pela API - titulo em negrito na primeira linha, corpo e rodape em italico na
 *     ultima (o APChat nao envia titulo nem rodape fora do template da Meta).
 *   - Template da Meta: cabecalho, corpo e rodape cinza separados, como o template aprovado aparece.
 * Variaveis ficam como {{nome}}, destacadas. Tudo escapado: o texto do usuario nunca vira HTML.
 */
class TemplateWhatsappPreviaService
{
    const VERDE = '#00a884';

    /** HTML da previa a partir dos campos do formulario (template da Meta: os componentes gravados). */
    public static function previa(array $param): string
    {
        $gravado = !empty($param['id']) ? TemplateEscritorio::find((int) $param['id']) : null;

        if ($gravado && $gravado->ehMeta())
        {
            $p = ApchatWabaService::partes($gravado);
            return self::balao($p['cabecalho'], $p['corpo'], $p['rodape'], $p['botoes']);
        }

        $botoes = [];
        for ($i = 1; $i <= TemplateEscritorio::BOTOES_MAX; $i++)
        {
            $b = trim((string) ($param['botao' . $i] ?? ''));
            if ($b !== '') { $botoes[] = $b; }
        }
        $texto = MensagemService::montarWhatsapp(TemplateEscritorio::normalizarVariaveis((string) ($param['titulo'] ?? '')),
                                                 TemplateEscritorio::normalizarVariaveis((string) ($param['template_whatsapp'] ?? '')),
                                                 (string) ($param['rodape'] ?? ''));
        if ($texto === '' && !$botoes)
        {
            return '<div style="color:#777;font-size:13px">Escreva a mensagem para ver a prévia.</div>';
        }
        return self::balao('', $texto, '', $botoes);
    }

    /** O balao: cabecalho (so Meta), texto formatado, rodape cinza (so Meta), hora e os botoes. */
    public static function balao(string $cabecalho, string $texto, string $rodape, array $botoes): string
    {
        $h = '<div style="background:#efeae2;border-radius:8px;padding:16px 12px;font-family:-apple-system,Segoe UI,Helvetica,Arial,sans-serif">'
           . '<div style="max-width:360px;background:#fff;border-radius:8px;box-shadow:0 1px 1px rgba(0,0,0,.13);overflow:hidden">'
           . '<div style="padding:8px 10px 6px;color:#111b21;font-size:14.5px;line-height:1.4">';
        if (trim($cabecalho) !== '')
        {
            $h .= '<div style="font-weight:700;font-size:15.5px;margin-bottom:6px">' . self::formatar($cabecalho) . '</div>';
        }
        $h .= '<div>' . self::formatar($texto) . '</div>';
        if (trim($rodape) !== '')
        {
            $h .= '<div style="color:#8696a0;font-size:13px;margin-top:6px">' . self::formatar($rodape) . '</div>';
        }
        $h .= '<div style="text-align:right;color:#8696a0;font-size:11px;margin-top:2px">12:00</div></div>';
        foreach ($botoes as $b)
        {
            $h .= '<div style="border-top:1px solid #e9edef;padding:9px 10px;text-align:center;color:' . self::VERDE . ';font-size:14.5px;font-weight:500">'
                . '<i class="fas fa-reply" style="margin-right:6px"></i>' . htmlspecialchars((string) $b, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        return $h . '</div></div>';
    }

    /**
     * Texto do WhatsApp em HTML: escapado, *negrito*, _italico_, ~riscado~, quebras de linha e as variaveis {{nome}} destacadas.
     * As variaveis saem do texto antes da formatacao (o "_" de {{data_inicial}} nao vira italico).
     */
    public static function formatar(string $texto): string
    {
        $vars = [];
        $t = preg_replace_callback('/\{\{[a-z0-9_]+\}\}/i', function ($m) use (&$vars) { $vars[] = $m[0]; return "\x01" . (count($vars) - 1) . "\x02"; }, $texto);
        $t = htmlspecialchars((string) $t, ENT_QUOTES, 'UTF-8');
        $t = preg_replace('/\*([^*\n]+)\*/u', '<b>$1</b>', $t);
        $t = preg_replace('/(?<![\p{L}\p{N}])_([^_\n]+)_(?![\p{L}\p{N}])/u', '<i>$1</i>', $t);
        $t = preg_replace('/~([^~\n]+)~/u', '<s>$1</s>', $t);
        // so <br>, sem a quebra de linha original (a previa pode estar dentro de um white-space:pre-wrap)
        $t = str_replace(["\r\n", "\r", "\n"], '<br>', $t);
        return preg_replace_callback('/\x01(\d+)\x02/', function ($m) use ($vars) {
            return '<span style="background:#fff3cd;border-radius:3px;padding:0 2px;">' . htmlspecialchars($vars[(int) $m[1]], ENT_QUOTES, 'UTF-8') . '</span>';
        }, $t);
    }
}
