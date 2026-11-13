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
 * Carga (10/11/2026): o navegador pede o balao a cada 5 s, so com a aba a vista. O estado do APChat e UM so para todos os usuarios
 * (arquivo de cache): ate 5 s depois da ultima conferencia, nenhum pedido vai ao APChat (nem conecta). Depois, UM processo (trava;
 * os outros usam o que ja tem) confere a impressao digital dos abertos/pendentes (~1,5 ms) e a campainha do webhook; so se algo
 * mudou (pegar, transferir, encerrar, mensagem) rele a lista. Releitura completa a cada 60 s, de seguranca. Cache gravado de forma
 * atomica (arquivo novo + troca). Acessos de cada atendente: 60 s. APChat fora = vazio, nova tentativa depois de 60 s.
 * 13/11/2026: Pendentes pela regra do APChat (ApchatVisibilidadeService: lista vazia de canais/filas = sem restricao, admin ve
 * todos, sem atendente conforme a empresa, convite, carteira). Chat interno: a cada conferencia, UMA leitura das nao lidas de
 * todos os atendentes ligados (com avisos); falha do chat nao derruba os tickets ("chat interno indisponivel").
 */
class ApchatBalaoService
{
    const CONFERIR_SEGUNDOS = 5;
    const RELER_SEGUNDOS = 60;
    const ACESSOS_SEGUNDOS = 60;
    const ATUALIZAR_MS = 5000;
    const MAXIMO = 20;
    const FALHA_SEGUNDOS = 60;

    /** @var int|null testes: relogio fixo */
    private static $relogio = null;

    public static function usarRelogio(?int $agora): void
    {
        self::$relogio = $agora;
        ApchatAcessoService::usarRelogio($agora);
    }

    private static function agora(): int
    {
        return self::$relogio ?? time();
    }

    public static function conversas(int $systemUserId): array
    {
        $vazio = ['abertos' => [], 'pendentes' => [], 'chat' => [], 'chat_situacao' => 'ok', 'total' => 0];

        $ligacao = ApchatUsuarioService::doUsuario($systemUserId);
        if (!$ligacao || !$ligacao->apchat_usuario_id)
        {
            return ['situacao' => 'sem_ligacao'] + $vazio;
        }
        if ($ligacao->avisos !== 'S')
        {
            return ['situacao' => 'desligado'] + $vazio;
        }

        // APChat fora: nao tenta conectar de novo por 60 s (cada tentativa espera ate 5 s)
        $falha = self::arquivoCache() . '.falha';
        if (is_file($falha) && (int) @file_get_contents($falha) > self::agora() - self::FALHA_SEGUNDOS)
        {
            return ['situacao' => 'erro'] + $vazio;
        }
        try
        {
            if (!ApchatBancoService::configurado())
            {
                throw new Exception('APChat nao configurado');
            }
            $estado = self::estado();
            $tickets = $estado['tickets'];
            $acessos = ApchatAcessoService::acessos((int) $ligacao->apchat_usuario_id);
            $config = ApchatAcessoService::configuracoes();
        }
        catch (Throwable $e)
        {
            self::gravar($falha, (string) self::agora());
            return ['situacao' => 'erro'] + $vazio;
        }

        $eu = (int) $ligacao->apchat_usuario_id;
        $carteiras = $estado['carteiras'] ?? [];
        $aguardando = array_flip($estado['aguardando'] ?? []);

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
            elseif ($t['status'] === 'pending' && !isset($aguardando[preg_replace('/\D/', '', (string) $t['numero'])])
                    && ApchatVisibilidadeService::podeVer($t + ['carteira' => $carteiras[(int) ($t['contactId'] ?? 0)] ?? []], $acessos, $config))
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

        // chat interno: as conversas com nao lidas deste atendente; o icone soma as mensagens nao lidas
        $chat = [];
        if (($estado['chat'] ?? null) === null)
        {
            $chatSituacao = 'indisponivel';
        }
        else
        {
            $chatSituacao = 'ok';
            foreach ($estado['chat'] as $c)
            {
                if ((int) $c['usuario'] !== $eu) { continue; }
                $chat[] = self::itemChat($c);
                $total += (int) $c['qtd'];
            }
        }

        return ['situacao' => 'ok', 'abertos' => array_slice($abertos, 0, self::MAXIMO), 'pendentes' => array_slice($pendentes, 0, self::MAXIMO),
                'chat' => array_slice($chat, 0, self::MAXIMO), 'chat_situacao' => $chatSituacao, 'total' => $total];
    }

    private static function itemChat(array $c): array
    {
        $tipo = $c['tipo'] === 'equipe' ? 'equipe' : 'usuario';
        $texto = trim(preg_replace('/\s+/u', ' ', (string) ($c['ultima'] ?? '')));
        return [
            'tipo'        => $tipo,
            'conversa_id' => (int) $c['conversa_id'],
            'nome'        => trim((string) ($c['nome'] ?? '')) !== '' ? (string) $c['nome'] : ($tipo === 'equipe' ? 'Equipe' : 'Usuário'),
            'qtd'         => (int) $c['qtd'],
            'ultima'      => $texto !== '' ? mb_substr($texto, 0, 80) : '[arquivo]',
            'quando'      => $c['ultima_em'] ?? null,
            'url'         => ApchatApiService::urlChat($tipo, (int) $c['conversa_id']),
        ];
    }

    /** Atendentes do APChat ligados a usuarios da Curciol com avisos ligados (o chat e lido de uma vez para todos). */
    private static function atendentesComAvisos(): array
    {
        $c = TTransaction::get();
        if (!$c) { return []; }
        return array_map('intval', $c->query("SELECT DISTINCT apchat_usuario_id FROM apchat_usuario WHERE apchat_usuario_id IS NOT NULL AND avisos = 'S'")->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function limparCache(): void
    {
        @unlink(self::arquivoCache());
        @unlink(self::arquivoCache() . '.falha');
        ApchatAcessoService::limpar();
    }

    /** Intervalo de atualizacao do balao no navegador (ms); desligado no application.ini continua desligado. */
    public static function intervaloMs(array $ini): string
    {
        $v = $ini['general']['verify_messages_menu'] ?? false;
        return ($v === false || $v === '' || $v === 'false' || $v === '0') ? 'false' : (string) self::ATUALIZAR_MS;
    }

    /**
     * Estado do APChat, o mesmo para todos: {em, checado_em, evento, impressao, aguardando, tickets}. Ver o cabecalho.
     */
    private static function estado(): array
    {
        $arquivo = self::arquivoCache();
        $cache = self::ler($arquivo);
        if ($cache && ($cache['checado_em'] ?? 0) > self::agora() - self::CONFERIR_SEGUNDOS)
        {
            return $cache;
        }

        $trava = @fopen(self::arquivoTrava(), 'c');
        if ($trava && !flock($trava, LOCK_EX | LOCK_NB))
        {
            if ($cache)
            {
                fclose($trava);
                return $cache; // outro processo esta conferindo: usa o que ja tem
            }
            flock($trava, LOCK_EX); // primeira carga: espera o outro terminar
        }
        try
        {
            $cache = self::ler($arquivo) ?: $cache;
            $agora = self::agora();
            if ($cache && ($cache['checado_em'] ?? 0) > $agora - self::CONFERIR_SEGUNDOS)
            {
                return $cache;
            }
            if (!ApchatBancoService::disponivel())
            {
                throw new Exception('APChat fora: ' . ApchatBancoService::erro());
            }
            // campainha lida ANTES do APChat: mensagem que chegar durante a leitura faz reler na proxima conferencia
            $evento = ApchatWebhookService::ultimoEvento();
            $impressao = ApchatBancoService::impressaoTickets();
            $mudou = !$cache || $impressao !== ($cache['impressao'] ?? null) || $evento > (int) ($cache['evento'] ?? 0)
                  || ($cache['em'] ?? 0) <= $agora - self::RELER_SEGUNDOS;
            $tickets = $mudou ? ApchatBancoService::ticketsAbertosComUltima() : $cache['tickets'];
            try
            {
                $chat = ApchatBancoService::chatNaoLidas(self::atendentesComAvisos());
            }
            catch (Throwable $e)
            {
                $chat = null; // chat interno indisponivel (ex.: APChat sem a funcao): os tickets continuam
            }
            $estado = [
                'em'         => $mudou ? $agora : $cache['em'],
                'checado_em' => $agora,
                'evento'     => $evento,
                'impressao'  => $impressao,
                'aguardando' => ApchatRespostaService::numerosAguardando(),
                'tickets'    => $tickets,
                'carteiras'  => $mudou ? self::carteirasDe($tickets) : ($cache['carteiras'] ?? []),
                'chat'       => $chat,
            ];
            self::gravar($arquivo, json_encode($estado));
            return $estado;
        }
        finally
        {
            if ($trava)
            {
                flock($trava, LOCK_UN);
                fclose($trava);
            }
        }
    }

    public static function arquivoTrava(): string
    {
        return self::arquivoCache() . '.trava';
    }

    private static function ler(string $arquivo): ?array
    {
        $dados = is_file($arquivo) ? json_decode((string) @file_get_contents($arquivo), true) : null;
        return is_array($dados) ? $dados : null;
    }

    /** Grava num arquivo novo e troca: quem le ao mesmo tempo nunca pega o arquivo pela metade. */
    private static function gravar(string $arquivo, string $conteudo): void
    {
        $tmp = $arquivo . '.tmp' . getmypid() . '-' . mt_rand();
        if (@file_put_contents($tmp, $conteudo) !== false && !@rename($tmp, $arquivo))
        {
            @unlink($tmp);
        }
    }

    /** Donos das carteiras dos contatos dos tickets abertos (excecao da regra de fila). Falha = sem carteiras. */
    private static function carteirasDe(array $tickets): array
    {
        try
        {
            $ids = array_values(array_unique(array_filter(array_map(function ($t) { return (int) ($t['contactId'] ?? 0); }, $tickets))));
            return $ids ? ApchatBancoService::carteiras($ids) : [];
        }
        catch (Throwable $e)
        {
            return [];
        }
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
            'atendente' => $t['atendente'] ?? null,
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
