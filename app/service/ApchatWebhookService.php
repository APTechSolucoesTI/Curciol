<?php
/**
 * Webhook do APChat (10/11/2026). Usa a transacao aberta ('escritorio').
 *
 * O APChat chama apchat-webhook.php?chave=<escritorio.webhook_apchat_chave> com um POST JSON a cada evento. E so a "campainha"
 * do balao: o webhook avisa mensagens (do cliente e do atendente), mas nao avisa pegar, transferir nem encerrar ticket - a
 * situacao dos tickets vem sempre do banco do APChat. Por isso guarda so o tipo do evento, o ticket, se a mensagem e nossa e a
 * hora (nunca o texto, o contato nem os tokens dos canais que vem junto), por 3 dias. Devolve o codigo HTTP da resposta.
 */
class ApchatWebhookService
{
    const MAXIMO_BYTES = 1048576;
    const DIAS = 3;
    // eventos de mensagem: tocam a campainha do balao
    const MENSAGEM = 'message';
    const ENVIO_PREFIXO = 'message_send';

    public static function receber(string $metodo, ?string $chave, string $corpo, string $ip): int
    {
        if (strtoupper($metodo) !== 'POST')
        {
            return 405;
        }
        if (!self::chaveValida((string) $chave))
        {
            return 403;
        }
        if (strlen($corpo) > self::MAXIMO_BYTES)
        {
            return 413;
        }
        $dados = json_decode($corpo, true);
        if (!is_array($dados))
        {
            return 400;
        }

        $evento = mb_substr(trim((string) ($dados['method'] ?? '')), 0, 60);
        $ticket = $dados['ticket']['id'] ?? null;
        $ticket = is_numeric($ticket) ? (int) $ticket : null;
        $deMim = null;
        if ($evento === self::MENSAGEM)
        {
            $deMim = !empty($dados['msg']['fromMe']);
        }
        elseif (strpos($evento, self::ENVIO_PREFIXO) === 0)
        {
            // resposta do atendente pela tela do APChat (message_send_uazapi, ...): o APChat nao manda fromMe
            $deMim = true;
        }

        $st = TTransaction::get()->prepare('INSERT INTO apchat_webhook_evento (ip, metodo, ticket_id, de_mim) VALUES (?, ?, ?, ?)');
        $st->bindValue(1, $ip);
        $st->bindValue(2, $evento !== '' ? $evento : null);
        $st->bindValue(3, $ticket, $ticket === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $st->bindValue(4, $deMim, $deMim === null ? PDO::PARAM_NULL : PDO::PARAM_BOOL);
        $st->execute();

        return 200;
    }

    /** id do ultimo evento de mensagem (0 = nenhum): mudou = o balao rele o APChat. */
    public static function ultimoEvento(): int
    {
        return (int) TTransaction::get()->query('SELECT coalesce(max(id), 0) FROM apchat_webhook_evento WHERE ticket_id IS NOT NULL')->fetchColumn();
    }

    /** Chegou evento de mensagem nas ultimas 24 h? (sem webhook, o balao rele o APChat mais vezes) */
    public static function ativo(): bool
    {
        return (bool) TTransaction::get()->query("SELECT EXISTS (SELECT 1 FROM apchat_webhook_evento WHERE ticket_id IS NOT NULL AND recebido_em > now() - interval '24 hours')")->fetchColumn();
    }

    /** Apaga os eventos com mais de 3 dias; devolve quantos. */
    public static function limparAntigos(): int
    {
        return TTransaction::get()->exec("DELETE FROM apchat_webhook_evento WHERE recebido_em < now() - interval '" . self::DIAS . " days'");
    }

    private static function chaveValida(string $chave): bool
    {
        if (strlen($chave) < 32)
        {
            return false;
        }
        $chaves = TTransaction::get()->query("SELECT webhook_apchat_chave FROM escritorio WHERE coalesce(webhook_apchat_chave, '') <> ''")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($chaves as $certa)
        {
            if (hash_equals((string) $certa, $chave))
            {
                return true;
            }
        }
        return false;
    }
}
