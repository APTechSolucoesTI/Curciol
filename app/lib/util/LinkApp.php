<?php
/**
 * Links da Curciol para o APChat e o APMail (12/11/2026). Regra do usuario (17/11/2026): abrem SO no app instalado, nunca em
 * aba ou janela do navegador. O link e web+apchat:// ou web+apmail:// e o sistema operacional entrega ao app (manifesto com
 * protocol_handlers); sem o app instalado o navegador (Chrome/Brave) nao faz nada. Sem integracao ou sem vinculo do usuario o
 * item nem chega a ter link (IntegracaoAcessoService). A chave do escritorio apchat_abrir_app/apmail_abrir_app nao e mais usada.
 *
 * Contratos: APChat (snippet do nginx /etc/nginx/snippets/curciol-apchat.conf no servidor do APChat):
 *   web+apchat://ticket/<numero>, web+apchat://chat/usuario/<numero>, web+apchat://chat/equipe/<numero> (13/11/2026),
 *   web+apchat://inicio (qualquer outro vai para a tela inicial).
 * APMail (docs da tarefa do APMail): web+apmail://abrir?caminho=<mailboxes[].url da API>, web+apmail://mensagem/<uuid>.
 */
class LinkApp
{
    const NOMES = ['apchat' => 'APChat', 'apmail' => 'APMail'];

    /** onclick do ticket do APChat. */
    public static function apchatTicket(int $ticketId): string
    {
        if ($ticketId <= 0) { return ''; }
        return self::onclick('web+apchat://ticket/' . $ticketId, 'apchat');
    }

    /** onclick de uma conversa do chat interno do APChat (13/11/2026): $tipo usuario|equipe. */
    public static function apchatChat(string $tipo, int $id): string
    {
        if ($id <= 0) { return ''; }
        $tipo = $tipo === 'equipe' ? 'equipe' : 'usuario';
        return self::onclick('web+apchat://chat/' . $tipo . '/' . $id, 'apchat');
    }

    /** onclick da tela inicial do APChat. */
    public static function apchatInicio(): string
    {
        return self::onclick('web+apchat://inicio', 'apchat');
    }

    /** onclick da tela inicial do APMail. */
    public static function apmailInicio(): string
    {
        return self::apmailCaixa('/');
    }

    /** onclick da caixa do APMail: $caminho = mailboxes[].url da API (relativo); so caminho do proprio APMail, senao a tela inicial. */
    public static function apmailCaixa(string $caminho): string
    {
        return self::onclick(self::apmailCaixaLink($caminho), 'apmail');
    }

    /** Link do app do APMail para a caixa: so caminho do proprio APMail, senao a tela inicial. */
    public static function apmailCaixaLink(string $caminho): string
    {
        $caminho = trim($caminho);
        if ($caminho === '' || $caminho[0] !== '/' || strpos($caminho, '//') === 0 || preg_match('#[\s\\\\]#', $caminho)) { $caminho = '/'; }
        return 'web+apmail://abrir?caminho=' . rawurlencode($caminho);
    }

    /**
     * onclick de um item do balao do topo (13/11/2026): ticket, conversa do chat interno ou caixa do APMail. O endereco nao vai na
     * tela: o clique pergunta a Curciol (TopoAbrir), que confere NA HORA se o item ainda e do usuario e so entao devolve o
     * link do app. Item velho (ja deveria ter sumido do balao) nao abre: aviso e o balao atualiza.
     */
    public static function notificacao(string $tipo, string $id): string
    {
        $f = JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP;
        return htmlspecialchars('CurciolAbrirNotificacao(this, ' . json_encode($tipo, $f) . ', ' . json_encode($id, $f) . '); return false;', ENT_QUOTES, 'UTF-8');
    }

    /** onclick de um e-mail do APMail pelo UUID interno (data.id da API de mensagens). */
    public static function apmailMensagem(string $uuid): string
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) { return ''; }
        return self::onclick('web+apmail://mensagem/' . strtolower($uuid), 'apmail');
    }

    /** Mesmo cuidado do LinkExterno: texto em JSON (aspas viram ") e depois escapado para o atributo HTML. */
    private static function onclick(string $link, string $sistema): string
    {
        $f = JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP;
        return htmlspecialchars('CurciolAbrirApp(' . json_encode($link, $f) . ', ' . json_encode(self::NOMES[$sistema], $f) . '); return false;', ENT_QUOTES, 'UTF-8');
    }
}
