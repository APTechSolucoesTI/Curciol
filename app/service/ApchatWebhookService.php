<?php
/**
 * Webhook do APChat (10/11/2026). Usa a transacao aberta ('escritorio').
 *
 * O APChat chama apchat-webhook.php?chave=<escritorio.webhook_apchat_chave> com um POST JSON a cada evento. Aqui so confere a
 * chave e guarda o evento como chegou, menos os SEGREDOS (apchat_webhook_evento); o processamento fica para depois, para responder na hora.
 * Devolve o codigo HTTP da resposta.
 */
class ApchatWebhookService
{
    const MAXIMO_BYTES = 1048576;

    // campos que o APChat manda junto e que nunca ficam na Curciol: tokens dos canais e chaves de IA/integracoes
    const SEGREDOS = ['tokenAPI', 'bmToken', 'wavoipToken', 'chatgptApiKey', 'chatgptOrganizationId', 'difyKey', 'apiConfig', 'n8nUrl'];

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
        if (trim($corpo) === '')
        {
            return 400;
        }

        $st = TTransaction::get()->prepare('INSERT INTO apchat_webhook_evento (ip, corpo) VALUES (?, ?)');
        $st->execute([$ip, self::semSegredos($corpo)]);

        return 200;
    }

    /** Tira os SEGREDOS de qualquer nivel do JSON; corpo que nao e JSON fica como chegou. */
    public static function semSegredos(string $corpo): string
    {
        $dados = json_decode($corpo, true);
        if (!is_array($dados))
        {
            return $corpo;
        }
        $tirar = function (array $d) use (&$tirar): array {
            foreach ($d as $k => $v)
            {
                if (in_array($k, self::SEGREDOS, true))
                {
                    unset($d[$k]);
                }
                elseif (is_array($v))
                {
                    $d[$k] = $tirar($v);
                }
            }
            return $d;
        };
        return json_encode($tirar($dados), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
