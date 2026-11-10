<?php
/**
 * Balao do APChat no topo da Curciol (10/11/2026). Usa a transacao aberta ('escritorio').
 *
 * Como as abas do APChat (10/11/2026, pedido do usuario):
 * - Abertos: todos os tickets abertos com o atendente, respondidos ou nao;
 * - Pendentes (ninguem atendendo): quem tem acesso ao canal do ticket e, se o ticket tem fila, a fila. Fica de fora o numero que
 *   esta aguardando o SIM/NAO de uma confirmacao da Curciol (ela mesma responde).
 * Ticket cuja ultima mensagem e do cliente esta "aguardando" e ganha o numero de mensagens do cliente desde a nossa ultima; o icone
 * do topo conta os TICKETS aguardando (nao as mensagens). Por usuario (apchat_usuario): avisos ligados/desligados.
 * O balao atualiza a cada 5 s em cada usuario logado. A lista de tickets do APChat fica em cache (uma leitura para todos): com o
 * webhook (campainha, ApchatWebhookService) rele quando chega mensagem nova e, sem campainha, a cada 60 s - encerrar, pegar e
 * transferir nao tocam a campainha; sem webhook nas ultimas 24 h, a cada 10 s. Acessos de cada atendente: 60 s. APChat fora = vazio.
 */
class ApchatBalaoService
{
    const CACHE_SEGUNDOS = 10;
    const CACHE_COM_WEBHOOK = 60;
    const ACESSOS_SEGUNDOS = 60;
    const ATUALIZAR_MS = 5000;
    const MAXIMO = 20;
    const FALHA_SEGUNDOS = 60;

    public static function conversas(int $systemUserId): array
    {
        $vazio = ['abertos' => [], 'pendentes' => [], 'total' => 0];

        $ligacao = ApchatUsuarioService::doUsuario($systemUserId);
        if (!$ligacao || !$ligacao->apchat_usuario_id)
        {
            return ['situacao' => 'sem_ligacao'] + $vazio;
        }
        if ($ligacao->avisos !== 'S')
        {
            return ['situacao' => 'desligado'] + $vazio;
        }

        // APChat fora: nao tenta conectar de novo por 60 s (cada tentativa espera ate 5 s, a cada 15 s, em cada usuario logado)
        $falha = self::arquivoCache() . '.falha';
        if (is_file($falha) && (int) @file_get_contents($falha) > time() - self::FALHA_SEGUNDOS)
        {
            return ['situacao' => 'erro'] + $vazio;
        }
        try
        {
            if (!ApchatBancoService::configurado() || !ApchatBancoService::disponivel())
            {
                @file_put_contents($falha, (string) time(), LOCK_EX);
                return ['situacao' => 'erro'] + $vazio;
            }
            $tickets = self::tickets();
            $acessos = self::acessos((int) $ligacao->apchat_usuario_id);
        }
        catch (Throwable $e)
        {
            @file_put_contents($falha, (string) time(), LOCK_EX);
            return ['situacao' => 'erro'] + $vazio;
        }

        $eu = (int) $ligacao->apchat_usuario_id;
        $canais = array_flip($acessos['canais'] ?? []);
        $filas = array_flip($acessos['filas'] ?? []);
        $aguardando = array_flip(ApchatRespostaService::numerosAguardando());

        $abertos = [];
        $pendentes = [];
        $total = 0;
        foreach ($tickets as $t)
        {
            if ($t['status'] === 'open' && (int) $t['userId'] === $eu)
            {
                $item = self::item($t);
                $abertos[] = $item;
            }
            elseif ($t['status'] === 'pending' && isset($canais[(int) $t['whatsappId']]) && (empty($t['queueId']) || isset($filas[(int) $t['queueId']]))
                    && !isset($aguardando[preg_replace('/\D/', '', (string) $t['numero'])]))
            {
                $item = self::item($t);
                $pendentes[] = $item;
            }
            else
            {
                continue;
            }
            $total += $item['aguardando'] ? 1 : 0;
        }

        return ['situacao' => 'ok', 'abertos' => array_slice($abertos, 0, self::MAXIMO), 'pendentes' => array_slice($pendentes, 0, self::MAXIMO), 'total' => $total];
    }

    public static function limparCache(): void
    {
        @unlink(self::arquivoCache());
        @unlink(self::arquivoCache() . '.falha');
        foreach (glob(self::arquivoCache() . '.acessos-*') ?: [] as $f) { @unlink($f); }
    }

    /** Segundos da lista de tickets em cache: 60 com o webhook ativo (a campainha avisa mensagem nova), senao 10. */
    public static function cacheSegundos(): int
    {
        return ApchatWebhookService::ativo() ? self::CACHE_COM_WEBHOOK : self::CACHE_SEGUNDOS;
    }

    /** Intervalo de atualizacao do balao no navegador (ms); desligado no application.ini continua desligado. */
    public static function intervaloMs(array $ini): string
    {
        $v = $ini['general']['verify_messages_menu'] ?? false;
        return ($v === false || $v === '' || $v === 'false' || $v === '0') ? 'false' : (string) self::ATUALIZAR_MS;
    }

    private static function acessos(int $atendente): array
    {
        $arquivo = self::arquivoCache() . '.acessos-' . $atendente;
        $cache = is_file($arquivo) ? json_decode((string) @file_get_contents($arquivo), true) : null;
        if (is_array($cache) && ($cache['em'] ?? 0) > time() - self::ACESSOS_SEGUNDOS)
        {
            return $cache['acessos'];
        }
        $acessos = ApchatBancoService::acessos($atendente);
        @file_put_contents($arquivo, json_encode(['em' => time(), 'acessos' => $acessos]), LOCK_EX);
        return $acessos;
    }

    private static function tickets(): array
    {
        $arquivo = self::arquivoCache();
        $cache = is_file($arquivo) ? json_decode((string) @file_get_contents($arquivo), true) : null;
        // campainha lida ANTES do APChat: mensagem que chegar durante a leitura faz reler na proxima atualizacao
        $evento = ApchatWebhookService::ultimoEvento();
        if (is_array($cache) && ($cache['em'] ?? 0) > time() - self::cacheSegundos() && $evento <= (int) ($cache['evento'] ?? 0))
        {
            return $cache['tickets'];
        }
        $tickets = ApchatBancoService::ticketsAbertosComUltima();
        @file_put_contents($arquivo, json_encode(['em' => time(), 'evento' => $evento, 'tickets' => $tickets]), LOCK_EX);
        return $tickets;
    }

    private static function arquivoCache(): string
    {
        return sys_get_temp_dir() . '/curciol-apchat-balao-' . md5(__DIR__) . '.json';
    }

    private static function item(array $t): array
    {
        $nossa = !empty($t['ultima_nossa']);
        $texto = (string) ($t['ultima_texto'] ?? '');
        if ($nossa)
        {
            // o APChat poe a assinatura de quem esta logado ("*Nome*:" na primeira linha); na Curciol vira "Voce:"
            $texto = preg_replace('/^\s*\*[^*\n]{1,80}\*:\s*/u', '', $texto);
        }
        $texto = trim(preg_replace('/\s+/u', ' ', $texto));
        return [
            'ticket_id' => (int) $t['id'],
            'contato'   => trim((string) ($t['contato'] ?? '')) !== '' ? (string) $t['contato'] : (string) $t['numero'],
            'numero'    => (string) $t['numero'],
            'canal'     => (string) ($t['canal'] ?? ''),
            'fila'      => $t['fila'] ?? null,
            'status'    => (string) $t['status'],
            'nossa'     => $nossa,
            // ultima mensagem do cliente: esperando resposta, com quantas mensagens dele desde a nossa ultima
            'aguardando' => $aguardando = !$nossa && ($t['ultima_em'] ?? null) !== null,
            'nao_lidas' => $aguardando ? max(1, (int) ($t['nao_respondidas'] ?? 1)) : 0,
            'ultima'    => $texto !== '' ? mb_substr($texto, 0, 80) : '[arquivo]',
            'quando'    => $t['ultima_em'] ?? $t['lastMessageAt'] ?? null,
            'url'       => ApchatApiService::urlTicket((int) $t['id']),
        ];
    }
}
