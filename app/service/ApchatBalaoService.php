<?php
/**
 * Balao do APChat no topo da Curciol (10/11/2026). Usa a transacao aberta ('escritorio').
 *
 * Conversa a responder = ticket pendente ou aberto cuja ultima mensagem e do cliente.
 * - pendente (ninguem atendendo): quem tem acesso ao canal do ticket e, se o ticket tem fila, a fila;
 * - aberto: so o atendente do ticket.
 * Fica de fora o numero que esta aguardando o SIM/NAO de uma confirmacao da Curciol (ela mesma responde).
 * Por usuario (apchat_usuario): avisos ligados/desligados e, opcional, a lista dos tickets abertos com ele.
 * A lista de tickets do APChat fica 10 s em cache (o balao atualiza a cada 15 s em cada usuario logado). APChat fora = vazio.
 */
class ApchatBalaoService
{
    const CACHE_SEGUNDOS = 10;
    const MAXIMO = 20;

    public static function conversas(int $systemUserId): array
    {
        $vazio = ['itens' => [], 'abertos' => [], 'total' => 0];

        $ligacao = ApchatUsuarioService::doUsuario($systemUserId);
        if (!$ligacao || !$ligacao->apchat_usuario_id)
        {
            return ['situacao' => 'sem_ligacao'] + $vazio;
        }
        if ($ligacao->avisos !== 'S')
        {
            return ['situacao' => 'desligado'] + $vazio;
        }

        try
        {
            if (!ApchatBancoService::configurado() || !ApchatBancoService::disponivel())
            {
                return ['situacao' => 'erro'] + $vazio;
            }
            $tickets = self::tickets();
            $acessos = ApchatBancoService::acessos((int) $ligacao->apchat_usuario_id);
        }
        catch (Throwable $e)
        {
            return ['situacao' => 'erro'] + $vazio;
        }

        $eu = (int) $ligacao->apchat_usuario_id;
        $canais = array_flip($acessos['canais'] ?? []);
        $filas = array_flip($acessos['filas'] ?? []);
        $aguardando = array_flip(ApchatRespostaService::numerosAguardando());

        $itens = [];
        $abertos = [];
        foreach ($tickets as $t)
        {
            $meu = $t['status'] === 'open' && (int) $t['userId'] === $eu;
            if ($meu && $ligacao->mostrar_abertos === 'S')
            {
                $abertos[] = self::item($t);
            }
            if (!empty($t['ultima_nossa']) || isset($aguardando[preg_replace('/\D/', '', (string) $t['numero'])]))
            {
                continue;
            }
            $pendenteMeu = $t['status'] === 'pending' && isset($canais[(int) $t['whatsappId']]) && (empty($t['queueId']) || isset($filas[(int) $t['queueId']]));
            if ($meu || $pendenteMeu)
            {
                $itens[] = self::item($t);
            }
        }

        return ['situacao' => 'ok', 'itens' => array_slice($itens, 0, self::MAXIMO), 'abertos' => array_slice($abertos, 0, self::MAXIMO), 'total' => count($itens)];
    }

    public static function limparCache(): void
    {
        @unlink(self::arquivoCache());
    }

    private static function tickets(): array
    {
        $arquivo = self::arquivoCache();
        $cache = is_file($arquivo) ? json_decode((string) @file_get_contents($arquivo), true) : null;
        if (is_array($cache) && ($cache['em'] ?? 0) > time() - self::CACHE_SEGUNDOS)
        {
            return $cache['tickets'];
        }
        $tickets = ApchatBancoService::ticketsAbertosComUltima();
        @file_put_contents($arquivo, json_encode(['em' => time(), 'tickets' => $tickets]), LOCK_EX);
        return $tickets;
    }

    private static function arquivoCache(): string
    {
        return sys_get_temp_dir() . '/curciol-apchat-balao-' . md5(__DIR__) . '.json';
    }

    private static function item(array $t): array
    {
        $texto = trim(preg_replace('/\s+/', ' ', (string) ($t['ultima_texto'] ?? '')));
        return [
            'ticket_id' => (int) $t['id'],
            'contato'   => trim((string) ($t['contato'] ?? '')) !== '' ? (string) $t['contato'] : (string) $t['numero'],
            'numero'    => (string) $t['numero'],
            'canal'     => (string) ($t['canal'] ?? ''),
            'fila'      => $t['fila'] ?? null,
            'status'    => (string) $t['status'],
            'ultima'    => $texto !== '' ? mb_substr($texto, 0, 80) : '[arquivo]',
            'quando'    => $t['ultima_em'] ?? $t['lastMessageAt'] ?? null,
            'url'       => ApchatApiService::urlTicket((int) $t['id']),
        ];
    }
}
