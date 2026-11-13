<?php
/**
 * Quem ve qual ticket do APChat (13/11/2026): a MESMA regra do APChat (decisao do usuario: "manter a regra do apchat mesmo"),
 * tirada do espelho comentado do backend no frontend do APChat (src/lib/can-user-see-ticket.ts, ZPRO 4.0.6.7; relatorio em
 * docs/apchat-2026-11-13, secao 1.2). Na ordem:
 *   1. robo: com "Nao visualizar tickets no ChatBot", some o ticket so do robo (sem atendente, sem fila, com fluxo) - para todos;
 *   2. admin/superadmin, supervisor com "supervisor como admin" e perfil personalizado com "ver todos" veem tudo
 *      (supervisor restrito ao canal: so os canais dele; restrito ao departamento: as filas dele + o que e dele/convite/grupo);
 *   3. canal: com algum canal permitido, o canal do ticket precisa estar na lista (lista vazia = sem restricao; nao vale com
 *      "receptivo por fila");
 *   4. grupos de WhatsApp: delegado = so os delegados; "nao ver atribuidos" esconde o grupo de outro; sem "grupos para todos",
 *      so o grupo das filas dele ou atribuido a ele;
 *   5. fila: com alguma fila, ticket fora delas so se for dele, convite, carteira, grupo delegado ou sem atendente com
 *      "ver sem atendente" (nullTickets) - lista vazia = sem restricao;
 *   6. "nao visualizar ja atribuidos": so sem atendente, dele, convite, grupo delegado (e fechados com "fechados para todos");
 *   7. senao, ve o que passou pelos filtros (inclusive o do colega numa fila dele).
 * Canal e fila sao filtros INDEPENDENTES (no APChat a fila nao pertence a canal).
 */
class ApchatVisibilidadeService
{
    /**
     * @param array $t       ticket {status, userId, queueId, whatsappId, isGroup, shared, userIdArray, groupUserIdArray, chatFlowId,
     *                       carteira: int[] donos da carteira do contato}
     * @param array $u       atendente {id, perfil, configs: array, ver_todos: bool, canais: int[], filas: int[]}
     * @param array $config  configuracoes da empresa como o APChat guarda (ApchatBancoService::configuracoes)
     */
    public static function podeVer(array $t, array $u, array $config): bool
    {
        $eu = (int) ($u['id'] ?? 0);
        if ($eu <= 0) { return false; }
        $c = function (string $chave) use ($config) { return self::ligado($config[$chave] ?? null); };

        $atendente = isset($t['userId']) && $t['userId'] !== null && $t['userId'] !== '' ? (int) $t['userId'] : null;
        $fila = isset($t['queueId']) && $t['queueId'] !== null && $t['queueId'] !== '' ? (int) $t['queueId'] : null;
        $canal = isset($t['whatsappId']) && $t['whatsappId'] !== null && $t['whatsappId'] !== '' ? (int) $t['whatsappId'] : null;
        $canais = array_map('intval', $u['canais'] ?? []);
        $filas = array_map('intval', $u['filas'] ?? []);
        $naFila = $fila !== null && in_array($fila, $filas, true);
        $semAtendente = $atendente === null;
        $dele = $atendente === $eu;
        $convite = self::ligado($t['shared'] ?? false) && in_array($eu, self::ids($t['userIdArray'] ?? null), true);
        $carteira = in_array($eu, array_map('intval', $t['carteira'] ?? []), true);
        $grupo = self::ligado($t['isGroup'] ?? false);
        $delegados = $grupo ? self::ids($t['groupUserIdArray'] ?? null) : [];
        $delegado = in_array($eu, $delegados, true);

        // 1. robo
        if ($c('NotViewTicketsChatBot') && $semAtendente && $fila === null && !empty($t['chatFlowId'])) { return false; }

        // 2. quem ve tudo
        $perfil = strtolower((string) ($u['perfil'] ?? ''));
        $super = $perfil === 'super';
        if (in_array($perfil, ['admin', 'superadmin'], true) || ($super && $c('supervisorAdmin')) || !empty($u['ver_todos']))
        {
            if ($super && $c('supervisorChannelScoped') && $canais && !in_array((int) $canal, $canais, true)) { return false; }
            if ($super && self::ligado(($u['configs'] ?? [])['supervisorViewDept'] ?? false))
            {
                return $naFila || $dele || $convite || $delegado;
            }
            return true;
        }

        // 3. canal
        if ($canais && !$c('inboundByQueueOnly') && !in_array((int) $canal, $canais, true)) { return false; }

        // 4. grupos de WhatsApp
        if ($grupo)
        {
            if ($delegados) { return $delegado; }
            if ($c('NotViewAssignedTickets') && !$semAtendente && !$dele && !$convite) { return false; }
            if (!$c('showGroupsForAll')) { return $naFila || $dele; }
            return true;
        }

        // 5. fila
        if ($filas && !$naFila && !($dele || $convite || $carteira || ($semAtendente && $c('nullTickets')))) { return false; }

        // 6. "nao visualizar ja atribuidos a outros"
        if ($c('NotViewAssignedTickets'))
        {
            return $semAtendente || $dele || $convite || (($t['status'] ?? '') === 'closed' && $c('showClosedForAll'));
        }

        // 7. regra padrao
        return true;
    }

    /** Opcao ligada, como o APChat guarda (enabled/disabled, true/false, 1/0). */
    public static function ligado($v): bool
    {
        if (is_bool($v)) { return $v; }
        return in_array(strtolower(trim((string) $v)), ['enabled', 'true', 't', '1', 'yes', 'sim', 'on'], true);
    }

    /** Lista de ids (JSON do APChat "[5, 8]", array ou nada). @return int[] */
    public static function ids($v): array
    {
        if (is_string($v)) { $v = json_decode($v, true); }
        if (!is_array($v)) { return []; }
        $r = [];
        foreach ($v as $x)
        {
            if (is_array($x)) { $x = $x['id'] ?? $x['userId'] ?? null; }
            if (is_numeric($x)) { $r[] = (int) $x; }
        }
        return $r;
    }
}
