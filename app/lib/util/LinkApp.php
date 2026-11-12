<?php
/**
 * Links da Curciol para o APChat e o APMail (12/11/2026). Com a chave do escritorio ligada (apchat_abrir_app /
 * apmail_abrir_app = 'S'), o clique abre SO no app instalado: o link vira web+apchat:// ou web+apmail://, o sistema
 * operacional entrega ao app (manifesto do app com protocol_handlers) e, sem app, nada abre e a Curciol avisa
 * (window.CurciolAbrirApp no BuilderTemplate.js). Desligada: como antes (LinkExterno, aba com nome, so http/https).
 *
 * Contratos: APChat (snippet do nginx /etc/nginx/snippets/curciol-apchat.conf no servidor do APChat):
 *   web+apchat://ticket/<numero>, web+apchat://inicio (qualquer outro vai para a tela inicial).
 * APMail (docs da tarefa do APMail): web+apmail://abrir?caminho=<mailboxes[].url da API>, web+apmail://mensagem/<uuid>.
 */
class LinkApp
{
    const NOMES = ['apchat' => 'APChat', 'apmail' => 'APMail'];

    /** @var array testes: [sistema => bool] */
    private static $forcado = [];
    /** @var array chave do escritorio ja lida no pedido [sistema => bool] */
    private static $lido = [];

    /** Testes: liga/desliga sem ler o escritorio; null volta a ler. */
    public static function forcar(string $sistema, ?bool $ligado): void
    {
        if ($ligado === null) { unset(self::$forcado[$sistema]); } else { self::$forcado[$sistema] = $ligado; }
        unset(self::$lido[$sistema]);
    }

    /** O escritorio quer os links deste sistema so pelo app instalado? */
    public static function ligado(string $sistema): bool
    {
        if (!isset(self::NOMES[$sistema])) { return false; }
        if (array_key_exists($sistema, self::$forcado)) { return self::$forcado[$sistema]; }
        if (!array_key_exists($sistema, self::$lido))
        {
            $abriu = !TTransaction::get();
            try
            {
                if ($abriu) { TTransaction::open('escritorio'); }
                $e = $sistema === 'apchat' ? ApchatApiService::escritorio() : ApmailApiService::escritorio();
                self::$lido[$sistema] = $e !== null && (string) $e->{$sistema . '_abrir_app'} === 'S';
            }
            catch (Throwable $erro)
            {
                self::$lido[$sistema] = false;
            }
            finally
            {
                if ($abriu && TTransaction::get()) { TTransaction::close(); }
            }
        }
        return self::$lido[$sistema];
    }

    /** onclick do ticket do APChat. $urlWeb: a tela do ticket (usada com a chave desligada). */
    public static function apchatTicket(int $ticketId, string $urlWeb): string
    {
        if ($ticketId <= 0) { return ''; }
        return self::ligado('apchat') ? self::onclick('web+apchat://ticket/' . $ticketId, 'apchat') : LinkExterno::onclick($urlWeb, 'apchat');
    }

    /** onclick da tela inicial do APChat. */
    public static function apchatInicio(string $urlWeb): string
    {
        return self::ligado('apchat') ? self::onclick('web+apchat://inicio', 'apchat') : LinkExterno::onclick($urlWeb, 'apchat');
    }

    /** onclick da caixa do APMail: $caminho = mailboxes[].url da API (relativo); so caminho do proprio APMail, senao a tela inicial. */
    public static function apmailCaixa(string $urlWeb, string $caminho): string
    {
        if (!self::ligado('apmail')) { return LinkExterno::onclick($urlWeb, 'apmail'); }
        $caminho = trim($caminho);
        if ($caminho === '' || $caminho[0] !== '/' || strpos($caminho, '//') === 0 || preg_match('#[\s\\\\]#', $caminho)) { $caminho = '/'; }
        return self::onclick('web+apmail://abrir?caminho=' . rawurlencode($caminho), 'apmail');
    }

    /** onclick de um e-mail do APMail pelo UUID interno (data.id da API de mensagens). */
    public static function apmailMensagem(string $uuid, string $urlWebInicio): string
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid)) { return ''; }
        return self::ligado('apmail') ? self::onclick('web+apmail://mensagem/' . strtolower($uuid), 'apmail') : LinkExterno::onclick($urlWebInicio, 'apmail');
    }

    /** Mesmo cuidado do LinkExterno: texto em JSON (aspas viram ") e depois escapado para o atributo HTML. */
    private static function onclick(string $link, string $sistema): string
    {
        $f = JSON_UNESCAPED_SLASHES | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP;
        return htmlspecialchars('CurciolAbrirApp(' . json_encode($link, $f) . ', ' . json_encode(self::NOMES[$sistema], $f) . '); return false;', ENT_QUOTES, 'UTF-8');
    }
}
