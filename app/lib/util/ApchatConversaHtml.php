<?php
/**
 * Conversa do APChat em HTML, no padrao visual do APChat (11/11/2026): baloes do cliente a esquerda (brancos, com o nome dele)
 * e da equipe a direita (azuis, com as iniciais do atendente), separadores de ticket e de dia, hora, canal e vistos.
 * Texto com a formatacao do WhatsApp (WhatsappTexto). Mensagens especiais desenhadas em vez do JSON cru: lista do robo,
 * resposta da lista, transferencia, nota interna, transcricao de audio, cobranca e botao Pix. Tudo escapado.
 */
class ApchatConversaHtml
{
    const MIDIAS = ['audio' => 'áudio', 'ptt' => 'áudio', 'image' => 'imagem', 'video' => 'vídeo', 'document' => 'documento', 'application' => 'documento',
                    'sticker' => 'figurinha', 'location' => 'localização', 'vcard' => 'contato', 'contactmessage' => 'contato'];
    const CORES = ['#2e7d32', '#6a1b9a', '#c62828', '#ef6c00', '#00838f', '#283593', '#ad1457', '#4e342e', '#558b2f', '#0277bd'];

    /**
     * @param array $opcoes ['contato' => nome do cliente no APChat, 'tela' => true: a conversa vai ate o fim da tela (tela do ticket)]
     * As ultimas mensagens ficam embaixo e a rolagem comeca no fim (coluna invertida por fora, trilha normal por dentro).
     */
    public static function html(array $mensagens, array $opcoes = []): string
    {
        if (!$mensagens)
        {
            return self::estilo() . self::abrir($opcoes) . '<div class="apc-sep">Nenhuma mensagem com este contato.</div>' . self::fechar($opcoes);
        }
        $contato = trim((string) ($opcoes['contato'] ?? ''));
        $html = self::estilo() . self::abrir($opcoes);
        $ticket = null;
        $dia = null;
        foreach ($mensagens as $m)
        {
            $quando = strtotime((string) $m['createdAt']);
            if ((string) $m['ticketId'] !== (string) $ticket)
            {
                $ticket = $m['ticketId'];
                $html .= '<div class="apc-sep">Ticket #' . (int) $ticket . (self::sim($m['arquivada'] ?? false) ? ' · arquivado' : '') . '</div>';
                $dia = null;
            }
            if (date('Y-m-d', $quando) !== $dia)
            {
                $dia = date('Y-m-d', $quando);
                $html .= '<div class="apc-sep apc-sep-dia">' . self::rotuloDia($quando) . '</div>';
            }
            $html .= self::mensagem($m, $contato, $quando);
        }
        return $html . self::fechar($opcoes);
    }

    private static function abrir(array $opcoes): string
    {
        return '<div class="apc-conversa' . (!empty($opcoes['tela']) ? ' apc-conversa-tela' : '') . '"><div class="apc-trilha">';
    }

    /**
     * Fecha a conversa; na tela do ticket, ajusta a altura ate o fim da janela (e de novo ao redimensionar). So a conversa rola:
     * depois de ajustar, o que ainda sobrar na rolagem do painel (espacamento do formulario abaixo dela) sai da altura da conversa.
     */
    private static function fechar(array $opcoes): string
    {
        if (empty($opcoes['tela'])) { return '</div></div>'; }
        return '</div></div><script>(function(){'
             . 'var rolaveis=function(c){var r=[],p=c.parentElement;while(p){var o=getComputedStyle(p).overflowY;'
             . 'if(o==="auto"||o==="scroll"||p===document.documentElement){r.push(p);}p=p.parentElement;}return r;};'
             . 'var ajustar=function(){var l=document.querySelectorAll(".apc-conversa-tela");if(!l.length){return;}var c=l[l.length-1];'
             . 'var ps=rolaveis(c);ps.forEach(function(p){p.scrollTop=0;});'
             . 'var h=Math.max(220,window.innerHeight-c.getBoundingClientRect().top-8);c.style.height=h+"px";'
             . 'ps.forEach(function(p){var sobra=p.scrollHeight-p.clientHeight;if(sobra>0){h=Math.max(220,h-sobra);c.style.height=h+"px";}});};'
             . 'setTimeout(ajustar,30);setTimeout(ajustar,400);if(window.__apcTela){window.removeEventListener("resize",window.__apcTela);}'
             . 'window.__apcTela=ajustar;window.addEventListener("resize",ajustar);})();</script>';
    }

    /** Hoje, Ontem ou a data. */
    public static function rotuloDia(int $quando): string
    {
        $d = date('Y-m-d', $quando);
        if ($d === date('Y-m-d')) { return 'Hoje'; }
        if ($d === date('Y-m-d', strtotime('-1 day'))) { return 'Ontem'; }
        return date('d/m/Y', $quando);
    }

    /** Circulo com as iniciais (cor fixa por nome); sem nome: icone. */
    public static function avatar(string $nome, string $iconeSemNome = 'fas fa-user'): string
    {
        $nome = trim($nome);
        if ($nome === '')
        {
            return '<div class="apc-av" style="background:#90a4ae"><i class="' . $iconeSemNome . '"></i></div>';
        }
        $partes = preg_split('/\s+/u', $nome);
        $ini = mb_strtoupper(mb_substr($partes[0], 0, 1) . (count($partes) > 1 ? mb_substr(end($partes), 0, 1) : ''));
        $cor = self::CORES[abs(crc32($nome)) % count(self::CORES)];
        return '<div class="apc-av" style="background:' . $cor . '" title="' . self::e($nome) . '">' . self::e($ini) . '</div>';
    }

    private static function mensagem(array $m, string $contato, int $quando): string
    {
        $tipo = strtolower((string) ($m['mediaType'] ?? ''));
        $chave = strtok($tipo, '/');
        $texto = trim((string) ($m['body'] ?? ''));
        $enviada = self::sim($m['fromMe'] ?? false);

        if ($tipo === 'transfer')
        {
            return '<div class="apc-aviso"><i class="fas fa-exchange-alt"></i> Transferido: ' . self::e($texto) . '</div>';
        }

        $rotulo = '';
        $classe = '';
        if (self::sim($m['isDeleted'] ?? false))
        {
            $conteudo = '<i class="apc-apagada"><i class="fas fa-ban"></i> Mensagem apagada</i>';
        }
        elseif ($tipo === 'list' || $tipo === 'request_payment' || $tipo === 'pix_button' || ($texto !== '' && $texto[0] === '{' && is_array(json_decode($texto, true))))
        {
            $conteudo = self::json($tipo, (array) json_decode($texto, true), $texto);
        }
        elseif ($tipo === 'listmessage')
        {
            $rotulo = '<i class="fas fa-list-ul"></i> Resposta da lista';
            $conteudo = WhatsappTexto::html(trim(preg_replace('/^\[LIST\]\s*/', '', $texto)));
        }
        elseif ($tipo === 'transcription')
        {
            $rotulo = '<i class="fas fa-microphone"></i> Transcrição do áudio';
            $conteudo = '<i>' . WhatsappTexto::html($texto) . '</i>';
        }
        elseif ($tipo === 'notes')
        {
            $rotulo = '<i class="fas fa-sticky-note"></i> Nota interna';
            $classe = ' apc-nota';
            $conteudo = WhatsappTexto::html($texto);
        }
        elseif ($tipo === 'unsupported')
        {
            $conteudo = '<i class="apc-apagada">Mensagem não suportada pelo WhatsApp</i>';
        }
        elseif (isset(self::MIDIAS[$chave]))
        {
            $arquivo = trim((string) ($m['fileName'] ?? ''));
            $legenda = ($texto !== '' && $texto !== $arquivo) ? '<br>' . WhatsappTexto::html($texto) : '';
            $conteudo = '<i>[' . self::MIDIAS[$chave] . ($arquivo !== '' ? '] ' . self::e($arquivo) : ']') . '</i>' . $legenda;
        }
        else
        {
            $conteudo = WhatsappTexto::html($texto);
        }

        $rodape = date('H:i', $quando);
        if ($enviada)
        {
            $canal = trim((string) ($m['canal'] ?? ''));
            $rodape .= ($canal !== '' ? ' · ' . self::e($canal) : '') . self::vistos($m['ack'] ?? null);
        }
        $balao = '<div class="apc-balao">' . (!$enviada && $contato !== '' ? '<div class="apc-nome">' . self::e($contato) . '</div>' : '')
               . ($rotulo !== '' ? '<div class="apc-rotulo">' . $rotulo . '</div>' : '')
               . '<div class="apc-texto">' . $conteudo . '</div><div class="apc-rodape">' . $rodape . '</div></div>';

        if ($enviada)
        {
            $atendente = trim((string) ($m['atendente'] ?? ''));
            $primeiro = $atendente !== '' ? preg_split('/\s+/u', $atendente)[0] : 'Robô';
            $av = '<div class="apc-av-bloco">' . self::avatar($atendente, 'fas fa-robot') . '<div class="apc-av-nome">' . self::e($primeiro) . '</div></div>';
            return '<div class="apc-linha apc-enviada' . $classe . '">' . $balao . $av . '</div>';
        }
        return '<div class="apc-linha apc-recebida' . $classe . '">' . self::avatar($contato) . $balao . '</div>';
    }

    /** Lista do robo, cobranca e botao Pix (o APChat guarda em JSON). */
    private static function json(string $tipo, array $j, string $cru): string
    {
        if ($tipo === 'request_payment' || isset($j['amount']))
        {
            $valor = isset($j['amount']) ? 'R$ ' . number_format(((float) $j['amount']) / 100, 2, ',', '.') : '';
            return '<div class="apc-rotulo"><i class="fas fa-file-invoice-dollar"></i> Cobrança Pix</div>'
                 . '<b>' . self::e($j['title'] ?? '') . '</b>' . (!empty($j['text']) ? '<br>' . WhatsappTexto::html((string) $j['text']) : '')
                 . ($valor !== '' ? '<br>Valor: <b>' . $valor . '</b>' : '')
                 . (!empty($j['pixKey']) ? '<br>Chave Pix: ' . self::e($j['pixKey']) . (!empty($j['pixType']) ? ' (' . self::e($j['pixType']) . ')' : '') : '');
        }
        if ($tipo === 'pix_button' || (isset($j['pixKey']) && !isset($j['body'])))
        {
            return '<div class="apc-rotulo"><i class="fas fa-qrcode"></i> Botão Pix</div>Chave: <b>' . self::e($j['pixKey'] ?? '') . '</b>'
                 . (!empty($j['pixType']) ? ' (' . self::e($j['pixType']) . ')' : '') . (!empty($j['pixName']) ? '<br>Nome: ' . self::e($j['pixName']) : '');
        }
        if (isset($j['body']) || isset($j['sections']) || isset($j['buttons']))
        {
            $html = WhatsappTexto::html(trim((string) ($j['body'] ?? $j['text'] ?? '')));
            if (!empty($j['footer'])) { $html .= '<div class="apc-rodape-lista">' . WhatsappTexto::html((string) $j['footer']) . '</div>'; }
            $opcoes = [];
            foreach ((array) ($j['sections'] ?? []) as $secao)
            {
                if (!empty($secao['title'])) { $opcoes[] = '<li class="apc-secao">' . self::e($secao['title']) . '</li>'; }
                foreach ((array) ($secao['rows'] ?? []) as $linha)
                {
                    $opcoes[] = '<li>' . self::e($linha['title'] ?? '') . (!empty($linha['description']) ? ' <span class="apc-desc">' . self::e($linha['description']) . '</span>' : '') . '</li>';
                }
            }
            foreach ((array) ($j['buttons'] ?? []) as $botao)
            {
                $opcoes[] = '<li>' . self::e(is_array($botao) ? ($botao['title'] ?? $botao['body'] ?? $botao['text'] ?? '') : $botao) . '</li>';
            }
            if (!empty($j['button_text'])) { $html .= '<div class="apc-lista-botao"><i class="fas fa-list-ul"></i> ' . self::e($j['button_text']) . '</div>'; }
            if ($opcoes) { $html .= '<ul class="apc-lista-opcoes">' . implode('', $opcoes) . '</ul>'; }
            return $html;
        }
        return WhatsappTexto::html($cru);
    }

    /** ack do APChat: 0 aguardando, 1 enviada, 2 entregue, 3+ lida/ouvida. */
    private static function vistos($ack): string
    {
        if ($ack === null || $ack === '') { return ''; }
        $ack = (int) $ack;
        if ($ack <= 0) { return ' <i class="far fa-clock"></i>'; }
        if ($ack === 1) { return ' <i class="fas fa-check"></i>'; }
        return ' <i class="fas fa-check-double' . ($ack >= 3 ? ' apc-lida' : '') . '"></i>';
    }

    private static function sim($v): bool
    {
        return $v === true || $v === 't' || $v === 1 || $v === '1' || $v === 'true';
    }

    private static function e($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    public static function estilo(): string
    {
        return '<style>
.apc-conversa{background:#efeae2;padding:12px 14px;border-radius:6px;display:flex;flex-direction:column-reverse;max-height:65vh;overflow:auto;font-size:13px;line-height:1.35}
.apc-trilha{display:flex;flex-direction:column;gap:8px}
.apc-conversa-tela{max-height:none;height:calc(100vh - 170px);min-height:220px}
.apc-linha{display:flex;align-items:flex-end;gap:8px}
.apc-enviada{justify-content:flex-end}
.apc-av{width:30px;height:30px;border-radius:50%;color:#fff;font-size:11px;font-weight:600;display:flex;align-items:center;justify-content:center;flex:0 0 30px}
.apc-av-bloco{display:flex;flex-direction:column;align-items:center;gap:2px;width:48px;flex:0 0 48px}
.apc-av-nome{font-size:9.5px;color:#667;max-width:48px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.apc-balao{max-width:72%;padding:7px 10px 5px;border-radius:12px;box-shadow:0 1px 1px rgba(0,0,0,.12);overflow-wrap:anywhere}
.apc-recebida .apc-balao{background:#fff;color:#111;border-bottom-left-radius:4px}
.apc-enviada .apc-balao{background:#1565c0;color:#fff;border-bottom-right-radius:4px}
.apc-enviada .apc-balao a{color:#fff;text-decoration:underline}
.apc-nota .apc-balao{background:#fff3c4;color:#5d4a00}
.apc-nota .apc-balao a{color:#5d4a00}
.apc-nome{font-size:11.5px;font-weight:600;color:#1e88e5;margin-bottom:2px}
.apc-rotulo{font-size:11px;opacity:.8;margin-bottom:3px}
.apc-rodape{font-size:10.5px;text-align:right;margin-top:3px;opacity:.8;white-space:nowrap}
.apc-lida{color:#80d8ff}
.apc-sep{align-self:center;font-size:11.5px;color:#444;background:#fff;padding:2px 10px;border-radius:4px;box-shadow:0 1px 1px rgba(0,0,0,.08)}
.apc-sep-dia{border-radius:10px;color:#555}
.apc-aviso{align-self:center;font-size:11.5px;color:#555;background:#fffde7;padding:3px 12px;border-radius:10px;box-shadow:0 1px 1px rgba(0,0,0,.06)}
.apc-mono{font-family:Consolas,monospace;font-size:12px;background:rgba(0,0,0,.07);padding:0 3px;border-radius:3px}
.apc-enviada .apc-mono{background:rgba(255,255,255,.18)}
.apc-citacao{border-left:3px solid rgba(0,0,0,.25);padding-left:6px;opacity:.85;margin:2px 0}
.apc-enviada .apc-citacao{border-left-color:rgba(255,255,255,.6)}
.apc-apagada{opacity:.7}
.apc-rodape-lista{font-size:11px;opacity:.75;margin-top:3px}
.apc-lista-botao{border-top:1px solid rgba(255,255,255,.35);margin-top:6px;padding-top:5px;text-align:center;font-weight:600}
.apc-recebida .apc-lista-botao{border-top-color:rgba(0,0,0,.12);color:#1e88e5}
.apc-lista-opcoes{margin:5px 0 0;padding-left:18px;font-size:12px;opacity:.92}
.apc-lista-opcoes .apc-secao{list-style:none;margin-left:-18px;font-weight:600}
.apc-desc{opacity:.75;font-size:11px}
.apc-topo{display:flex;align-items:center;gap:10px;margin:2px 0 10px}
.apc-topo .apc-av{width:40px;height:40px;flex-basis:40px;font-size:14px}
.apc-topo-nome{font-weight:600;font-size:15px;color:#222}
.apc-topo-info{font-size:12px;color:#667}
.apc-topo-info .apchat-logo{display:inline-block !important;height:14px;width:14px;vertical-align:-2px}
</style>';
    }
}
