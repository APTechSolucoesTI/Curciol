<?php
/**
 * Topo em um pedido so (11/11/2026): o BuilderTemplate.js pede a cada 10 s (so com a aba a vista; ao voltar para a aba, na hora e
 * com o APMail fresco) o balao do APChat (MessageList), o envelope do APMail (MailList) e o sino de notificacoes (NotificationList).
 * Antes eram 3 pedidos (22 por minuto por aba); cada pedido custa ~75 ms so para o sistema iniciar. Cada parte vai com a assinatura
 * do conteudo (md5): o navegador so redesenha a parte que mudou. A sessao e liberada no comeco, para nao segurar os cliques do usuario.
 *   engine.php?class=TopoNotificacoes&method=onCarregar&static=1[&fresco=1]  ->  {mensagens|mail|notificacoes: {html, assinatura}}
 */
class TopoNotificacoes
{
    const PARTES = ['mensagens' => 'MessageList', 'mail' => 'MailList', 'notificacoes' => 'NotificationList'];

    public static function onCarregar($param = null)
    {
        // os componentes so leem a sessao: libera a trava da sessao para os outros pedidos do usuario nao esperarem
        if (session_status() === PHP_SESSION_ACTIVE)
        {
            session_write_close();
        }
        $fresco = !empty($param['fresco']);

        $partes = [];
        foreach (self::PARTES as $chave => $classe)
        {
            ob_start();
            try
            {
                $componente = new $classe(['theme' => 'theme-builder', 'fresco' => $fresco ? 1 : 0]);
                $componente->show();
            }
            catch (Throwable $e)
            {
                // uma parte que falha fica vazia e nao derruba as outras
            }
            $html = (string) ob_get_clean();
            $partes[$chave] = ['html' => $html, 'assinatura' => md5($html)];
        }

        if (!headers_sent())
        {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }
        echo json_encode($partes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
