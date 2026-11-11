<?php
/**
 * Historico do WhatsApp (APChat) no cadastro do cliente (10/11/2026). Usa a transacao aberta ('escritorio').
 *
 * Numeros do cliente: o do contato principal e os dos auxiliares, enquanto nenhum outro cliente tiver aquele numero como
 * principal (decidido na hora: se depois virar principal de outro, os tickets passam a aparecer so la). Com e sem o 9.
 * So tickets fechados, do encerramento mais recente para o mais antigo. Principal = quem encerrou, senao o atendente do ticket.
 * Conversa: so as mensagens de texto (sem arquivos, audios, imagens), e so de ticket do proprio cliente.
 * Ticket aberto so por um envio da Curciol (mensagem.apchat_ticket_id), sem mensagem do cliente, fica de fora: a mensagem da
 * Curciol ja aparece nos atendimentos, com a situacao de entrega.
 * Homologacao (11/11/2026): com 'historico_somente_numeros' no app/config/apchat.php, so esses numeros trazem historico.
 */
class ApchatHistoricoService
{
    const MIDIAS = ['audio', 'ptt', 'image', 'video', 'document', 'application', 'sticker', 'location', 'vcard', 'contactmessage', 'file', 'voice'];

    public static function numerosDoCliente(int $pessoaId): array
    {
        $c = TTransaction::get();
        $st = $c->prepare("SELECT telefone, principal FROM pessoa_contato WHERE pessoa_id = ? AND COALESCE(telefone, '') <> ''");
        $st->execute([$pessoaId]);
        $numeros = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $l)
        {
            $n = APChatContactService::normalizarTelefone($l['telefone']);
            if ($n === '') { continue; }
            $n = substr($n, 0, 2) === '55' && strlen($n) >= 12 ? $n : '55' . $n;
            if ($l['principal'] !== 'S' && self::principalDeOutro($pessoaId, $n))
            {
                continue;
            }
            foreach (APChatContactService::variantes($n) as $v) { $numeros[$v] = true; }
        }
        $numeros = array_map('strval', array_keys($numeros));
        $somente = self::liberados();
        if ($somente === null) { return $numeros; }
        return array_values(array_filter($numeros, function ($v) use ($somente) { return isset($somente[APChatContactService::numeroCanonico($v)]); }));
    }

    /** Homologacao: numeros liberados (canonicos) pela chave 'historico_somente_numeros'; null = todos. */
    private static function liberados(): ?array
    {
        $lista = ApchatBancoService::historicoSomente();
        if ($lista === null) { return null; }
        $liberados = [];
        foreach ($lista as $tel)
        {
            $n = APChatContactService::normalizarTelefone($tel);
            if ($n === '') { continue; }
            $n = substr($n, 0, 2) === '55' && strlen($n) >= 12 ? $n : '55' . $n;
            $liberados[APChatContactService::numeroCanonico($n)] = true;
        }
        return $liberados;
    }

    /** [{id, arquivado, aberto_em, fechado_em, canal, fila, principal, atendentes[], numero, contato}] */
    public static function tickets(int $pessoaId): array
    {
        $numeros = self::numerosDoCliente($pessoaId);
        if (!$numeros) { return []; }

        $tickets = ApchatBancoService::ticketsFechadosDosNumeros($numeros);
        $daCurciol = self::abertosPelaCurciol(array_map(function ($t) { return (int) $t['id']; }, array_filter($tickets, function ($t) {
            return !self::sim($t['do_cliente'] ?? true) && !self::sim($t['arquivado'] ?? false);
        })));
        $lista = [];
        foreach ($tickets as $t)
        {
            if (($t['status'] ?? '') !== 'closed') { continue; }
            if (isset($daCurciol[(int) $t['id']]) && !self::sim($t['do_cliente'] ?? true) && !self::sim($t['arquivado'] ?? false)) { continue; }
            $atendentes = array_values(array_filter(explode('|', (string) ($t['atendentes'] ?? '')), 'strlen'));
            $lista[] = [
                'id'         => (int) $t['id'],
                'arquivado'  => $t['arquivado'] === true || $t['arquivado'] === 't',
                'aberto_em'  => (string) $t['aberto_em'],
                'fechado_em' => (string) $t['fechado_em'],
                'canal'      => (string) ($t['canal'] ?? ''),
                'fila'       => (string) ($t['fila'] ?? ''),
                'principal'  => trim((string) ($t['fechou'] ?? '')) !== '' ? (string) $t['fechou'] : (string) ($t['atendente'] ?? ''),
                'atendentes' => $atendentes,
                'numero'     => (string) $t['numero'],
                'contato'    => (string) ($t['contato'] ?? ''),
            ];
        }
        usort($lista, function ($a, $b) { return strcmp($b['fechado_em'], $a['fechado_em']) ?: $b['id'] <=> $a['id']; });
        return $lista;
    }

    /** Dos tickets, os que a Curciol abriu ao enviar (mensagem.apchat_ticket_id). @return array [ticket => true] */
    private static function abertosPelaCurciol(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) { return []; }
        $st = TTransaction::get()->prepare('SELECT DISTINCT apchat_ticket_id FROM mensagem WHERE apchat_ticket_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')');
        $st->execute($ids);
        return array_fill_keys(array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN)), true);
    }

    private static function sim($v): bool
    {
        return $v === true || $v === 't' || $v === 1 || $v === '1' || $v === 'true';
    }

    /** O ticket, se for do cliente (senao excecao). */
    public static function ticket(int $ticketId, int $pessoaId): array
    {
        foreach (self::tickets($pessoaId) as $t)
        {
            if ($t['id'] === $ticketId) { return $t; }
        }
        throw new Exception('Este atendimento do WhatsApp não é deste cliente.');
    }

    /** Mensagens de texto do ticket do cliente, da mais antiga para a mais recente. */
    public static function conversa(int $ticketId, int $pessoaId): array
    {
        $t = self::ticket($ticketId, $pessoaId);
        return array_values(array_filter(ApchatBancoService::mensagensDoTicket($ticketId, $t['arquivado']), function ($m) {
            $apagada = !empty($m['isDeleted']) && $m['isDeleted'] !== 'f';
            $tipo = strtolower(strtok((string) ($m['mediaType'] ?? ''), '/'));
            return !$apagada && trim((string) $m['body']) !== '' && trim((string) ($m['fileName'] ?? '')) === '' && !in_array($tipo, self::MIDIAS, true);
        }));
    }

    private static function principalDeOutro(int $pessoaId, string $numero): bool
    {
        $st = TTransaction::get()->prepare("SELECT telefone FROM pessoa_contato WHERE pessoa_id <> ? AND principal = 'S' AND regexp_replace(COALESCE(telefone, ''), '\\D', '', 'g') LIKE ?");
        $st->execute([$pessoaId, '%' . substr($numero, -8)]);
        $canonico = APChatContactService::numeroCanonico($numero);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $tel)
        {
            $n = APChatContactService::normalizarTelefone($tel);
            if ($n === '') { continue; }
            $n = substr($n, 0, 2) === '55' && strlen($n) >= 12 ? $n : '55' . $n;
            if (APChatContactService::numeroCanonico($n) === $canonico) { return true; }
        }
        return false;
    }
}
