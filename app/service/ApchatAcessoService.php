<?php
/**
 * Acessos do APChat de cada usuario da Curciol (13/11/2026), para a regra de visibilidade (ApchatVisibilidadeService):
 * - usuario da Curciol sem atendente do APChat ligado ("Usuarios das integracoes") nao ve ticket nenhum (como no APMail);
 * - acessos do atendente (canais, filas, perfil, configuracoes) e as configuracoes da empresa sao lidos do banco do APChat
 *   e ficam 60 s em cache (como as caixas do APMail): mudou no APChat, a Curciol acompanha sozinha, para todos os tickets
 *   desde o primeiro (nada e copiado; o historico e lido na hora);
 * - APChat fora: vale o ultimo acesso conhecido; se nunca houve leitura, a excecao sobe (quem chama nao mostra nada).
 * Usa a transacao aberta ('escritorio') para achar a ligacao do usuario.
 */
class ApchatAcessoService
{
    const SEGUNDOS = 60;

    /** @var int|null testes: relogio fixo */
    private static $relogio = null;

    public static function usarRelogio(?int $agora): void
    {
        self::$relogio = $agora;
    }

    private static function agora(): int
    {
        return self::$relogio ?? time();
    }

    /** Atendente do APChat ligado ao usuario da Curciol (null = sem ligacao). */
    public static function atendenteDo(int $systemUserId): ?int
    {
        if ($systemUserId <= 0) { return null; }
        $l = ApchatUsuarioService::doUsuario($systemUserId);
        return $l && $l->apchat_usuario_id ? (int) $l->apchat_usuario_id : null;
    }

    /** Acessos do atendente com o id dele: {id, canais, filas, perfil, configs, ver_todos, existe}. */
    public static function acessos(int $atendente): array
    {
        return ['id' => $atendente] + self::cache('acessos-' . $atendente, function () use ($atendente) { return ApchatBancoService::acessos($atendente); });
    }

    public static function configuracoes(): array
    {
        return self::cache('config', function () { return ApchatBancoService::configuracoes(); });
    }

    /**
     * Dos tickets (linhas do ApchatBancoService, com contactId, userId, queueId, whatsappId...), so os que o usuario da Curciol
     * ve no APChat. Sem ligacao, ou atendente que nao existe mais no APChat: nenhum.
     */
    public static function filtrar(array $tickets, int $systemUserId): array
    {
        if (!$tickets) { return []; }
        $atendente = self::atendenteDo($systemUserId);
        if (!$atendente) { return []; }
        $u = self::acessos($atendente);
        if (array_key_exists('existe', $u) && !$u['existe']) { return []; }
        $config = self::configuracoes();
        $contatos = array_values(array_unique(array_filter(array_map(function ($t) { return (int) ($t['contactId'] ?? 0); }, $tickets))));
        $carteiras = $contatos ? ApchatBancoService::carteiras($contatos) : [];
        return array_values(array_filter($tickets, function ($t) use ($u, $config, $carteiras) {
            return ApchatVisibilidadeService::podeVer($t + ['carteira' => $carteiras[(int) ($t['contactId'] ?? 0)] ?? []], $u, $config);
        }));
    }

    public static function limpar(): void
    {
        foreach (glob(self::arquivo('*')) ?: [] as $f) { @unlink($f); }
    }

    private static function cache(string $chave, callable $ler): array
    {
        $arquivo = self::arquivo($chave);
        $c = is_file($arquivo) ? json_decode((string) @file_get_contents($arquivo), true) : null;
        if (is_array($c) && ($c['em'] ?? 0) > self::agora() - self::SEGUNDOS && is_array($c['v'] ?? null))
        {
            return $c['v'];
        }
        try
        {
            $v = $ler();
        }
        catch (Throwable $e)
        {
            if (is_array($c) && is_array($c['v'] ?? null)) { return $c['v']; }   // APChat fora: o ultimo conhecido
            throw $e;
        }
        $tmp = $arquivo . '.' . getmypid() . '.tmp';
        if (@file_put_contents($tmp, json_encode(['em' => self::agora(), 'v' => $v])) !== false) { @rename($tmp, $arquivo); }
        return $v;
    }

    private static function arquivo(string $chave): string
    {
        return sys_get_temp_dir() . '/curciol-apchat-acesso-' . md5(__DIR__) . '-' . $chave . '.json';
    }
}
