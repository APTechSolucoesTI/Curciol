<?php
/**
 * Ligacao dos usuarios da Curciol aos atendentes do APChat (10/11/2026). Usa a transacao aberta ('escritorio').
 *
 * Automatico: pelo e-mail (sem diferenca de maiusculas); senao pelo nome completo ou pelo primeiro nome, sem acento, de UM unico
 * usuario ativo ainda livre; empate ou nada achado = sem ligacao. Atendente inativo no APChat nao liga. Um atendente so pode estar
 * ligado a um usuario. Ligacao feita a mao nao e desfeita pelo automatico.
 */
class ApchatUsuarioService
{
    /**
     * Regra pura.
     * @param array $atendentes [{id, name, email, inactive}]
     * @param array $usuarios   [{id, name, email, active}]
     * @param array $jaLigados  [system_user_id => apchat_usuario_id] (nao mudam)
     * @return array [system_user_id => apchat_usuario_id] so dos novos
     */
    public static function casar(array $atendentes, array $usuarios, array $jaLigados): array
    {
        $ocupados = array_flip(array_map('intval', array_values($jaLigados)));
        $livres = array_values(array_filter($usuarios, function ($u) use ($jaLigados) {
            return ($u['active'] ?? 'Y') === 'Y' && !isset($jaLigados[(int) $u['id']]);
        }));

        $novos = [];
        foreach ($atendentes as $a)
        {
            $aid = (int) $a['id'];
            if (!empty($a['inactive']) || isset($ocupados[$aid]))
            {
                continue;
            }
            $email = mb_strtolower(trim((string) ($a['email'] ?? '')));
            $nome = self::comparavel((string) ($a['name'] ?? ''));
            $achados = [];
            if ($email !== '')
            {
                $achados = array_filter($livres, function ($u) use ($email, $novos) { return !isset($novos[(int) $u['id']]) && mb_strtolower(trim((string) ($u['email'] ?? ''))) === $email; });
            }
            if (!$achados && $nome !== '')
            {
                $achados = array_filter($livres, function ($u) use ($nome, $novos) { return !isset($novos[(int) $u['id']]) && self::comparavel((string) $u['name']) === $nome; });
                if (!$achados && strpos($nome, ' ') === false)
                {
                    $achados = array_filter($livres, function ($u) use ($nome, $novos) { return !isset($novos[(int) $u['id']]) && explode(' ', self::comparavel((string) $u['name']))[0] === $nome; });
                }
            }
            if (count($achados) === 1)
            {
                $novos[(int) reset($achados)['id']] = $aid;
                $ocupados[$aid] = true;
            }
        }
        return $novos;
    }

    /** Liga pelo automatico os que ainda nao tem atendente. Devolve quantos ligou. */
    public static function ligarAutomatico(): int
    {
        $atendentes = ApchatBancoService::atendentes();
        $usuarios = TTransaction::get()->query("SELECT id, name, email, active FROM system_users ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        // quem ja tem registro (ligado ou desligado a mao) nao muda; so os nunca configurados entram no automatico
        // 16/11/2026: so conta o que foi decidido NESTE servidor do APChat (vinculo de outro servidor = nunca configurado aqui)
        $jaLigados = [];
        foreach (ApchatUsuario::where('id', '>', 0)->load() ?: [] as $l)
        {
            if (self::doServidorAtual($l)) { $jaLigados[(int) $l->system_user_id] = (int) $l->apchat_usuario_id; }
        }
        $novos = self::casar($atendentes, $usuarios, $jaLigados);
        foreach ($novos as $usuario => $atendente)
        {
            self::ligar($usuario, $atendente);
        }
        return count($novos);
    }

    /** Liga (ou desliga, com null) o atendente do usuario. Recusa atendente ja ligado a outro usuario. */
    public static function ligar(int $systemUserId, ?int $apchatUsuarioId): void
    {
        if ($apchatUsuarioId)
        {
            $outro = null;
            foreach (ApchatUsuario::where('apchat_usuario_id', '=', $apchatUsuarioId)->where('system_user_id', '<>', $systemUserId)->load() ?: [] as $o)
            {
                if (self::doServidorAtual($o)) { $outro = $o; break; }
            }
            if ($outro)
            {
                $dono = SystemUsers::find((int) $outro->system_user_id);
                throw new Exception('Este atendente do APChat já está ligado a ' . ($dono ? $dono->name : 'outro usuário') . '.');
            }
        }
        $l = self::registro($systemUserId);
        $l->apchat_usuario_id = $apchatUsuarioId ?: null;
        $l->apchat_servidor = self::servidor() ?: null;
        $l->atualizado_em = date('Y-m-d H:i:s');
        $l->store();
    }

    /** Servidor do APChat em uso (host da URL da API do Escritorio; 16/11/2026). */
    public static function servidor(): string
    {
        return ApchatBancoService::servidorDaIntegracao();
    }

    /** O vinculo e do servidor em uso? (NULL = vinculo antigo, vale para o atual) */
    private static function doServidorAtual(ApchatUsuario $l): bool
    {
        $s = trim((string) $l->apchat_servidor);
        return $s === '' || strtolower($s) === self::servidor();
    }

    public static function preferencias(int $systemUserId, bool $avisos, bool $mostrarAbertos): void
    {
        $l = self::registro($systemUserId);
        $l->avisos = $avisos ? 'S' : 'N';
        $l->mostrar_abertos = $mostrarAbertos ? 'S' : 'N';
        $l->atualizado_em = date('Y-m-d H:i:s');
        $l->store();
    }

    /**
     * Vinculo do usuario PARA LER (balao, cliques, historico, telas): atendente de outro servidor do APChat vem sem atendente
     * (16/11/2026: no outro servidor o mesmo numero e outra pessoa). Copia: nunca gravar o que esta funcao devolve.
     */
    public static function doUsuario(int $systemUserId): ?ApchatUsuario
    {
        $l = self::registroGravado($systemUserId);
        if ($l && !self::doServidorAtual($l))
        {
            $l = clone $l;
            $l->apchat_usuario_id = null;
        }
        return $l;
    }

    private static function registroGravado(int $systemUserId): ?ApchatUsuario
    {
        return ApchatUsuario::where('system_user_id', '=', $systemUserId)->first() ?: null;
    }

    private static function registro(int $systemUserId): ApchatUsuario
    {
        $l = self::registroGravado($systemUserId);
        if (!$l)
        {
            $l = new ApchatUsuario();
            $l->system_user_id = $systemUserId;
            $l->avisos = 'S';
            $l->mostrar_abertos = 'N';
        }
        return $l;
    }

    private static function comparavel(string $nome): string
    {
        return trim(preg_replace('/\s+/', ' ', mb_strtoupper(TratamentosService::removerAcentos($nome))));
    }
}
