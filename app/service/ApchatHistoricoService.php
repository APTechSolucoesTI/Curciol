<?php
/**
 * Historico do WhatsApp (APChat) no cadastro do cliente (10/11/2026). Usa a transacao aberta ('escritorio').
 *
 * Numeros do cliente: o do contato principal e os dos auxiliares, enquanto nenhum outro cliente tiver aquele numero como
 * principal (decidido na hora: se depois virar principal de outro, os tickets passam a aparecer so la). Com e sem o 9.
 * So tickets fechados, do encerramento mais recente para o mais antigo.
 * 11/11/2026: atribuido = o atendente do ticket (lista); profissionais = ele e quem respondeu (tela do ticket); ticket do APChat
 * nao tem especialidade (pedido do usuario).
 * Conversa: so as mensagens de texto (sem arquivos, audios, imagens), e so de ticket do proprio cliente.
 * Ticket aberto so por um envio da Curciol (mensagem.apchat_ticket_id), sem mensagem do cliente, fica de fora: a mensagem da
 * Curciol ja aparece nos atendimentos, com a situacao de entrega.
 * Homologacao (11/11/2026): com 'historico_somente_numeros' no app/config/apchat.php, so esses numeros trazem historico.
 * 13/11/2026: so os tickets que o usuario ve no APChat (a mesma regra do APChat: ApchatVisibilidadeService), pelo atendente
 * ligado a ele; sem ligacao, nenhum. Acessos com 60 s de cache: mudou no APChat, vale para o historico inteiro.
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

    /**
     * Tickets do cliente que o usuario ve no APChat (null = o usuario logado).
     * @return array [{id, arquivado, aberto_em, fechado_em, canal, fila, principal, atendentes[], numero, contato}]
     */
    public static function tickets(int $pessoaId, ?int $usuario = null): array
    {
        return self::mapear(ApchatAcessoService::filtrar(self::linhas($pessoaId), $usuario ?? self::usuarioLogado()));
    }

    private static function usuarioLogado(): int
    {
        return (int) TSession::getValue('userid');
    }

    /** Tickets fechados do cliente, como vem do APChat (sem a regra de visibilidade). */
    private static function linhas(int $pessoaId): array
    {
        $numeros = self::numerosDoCliente($pessoaId);
        if (!$numeros) { return []; }

        $tickets = ApchatBancoService::ticketsFechadosDosNumeros($numeros);
        $daCurciol = self::abertosPelaCurciol(array_map(function ($t) { return (int) $t['id']; }, array_filter($tickets, function ($t) {
            return !self::sim($t['do_cliente'] ?? true) && !self::sim($t['arquivado'] ?? false);
        })));
        return array_values(array_filter($tickets, function ($t) use ($daCurciol) {
            if (($t['status'] ?? '') !== 'closed') { return false; }
            return !(isset($daCurciol[(int) $t['id']]) && !self::sim($t['do_cliente'] ?? true) && !self::sim($t['arquivado'] ?? false));
        }));
    }

    private static function mapear(array $tickets): array
    {
        $lista = [];
        foreach ($tickets as $t)
        {
            $atendentes = array_values(array_filter(explode('|', (string) ($t['atendentes'] ?? '')), 'strlen'));
            $atribuido = trim((string) ($t['atendente'] ?? ''));
            $atribuidoId = isset($t['userId']) && $t['userId'] !== null ? (int) $t['userId'] : null;
            $lista[] = [
                'id'         => (int) $t['id'],
                'arquivado'  => $t['arquivado'] === true || $t['arquivado'] === 't',
                'aberto_em'  => (string) $t['aberto_em'],
                'fechado_em' => (string) $t['fechado_em'],
                'canal'      => (string) ($t['canal'] ?? ''),
                'fila'       => (string) ($t['fila'] ?? ''),
                'atribuido'     => $atribuido,
                'atribuido_id'  => $atribuidoId,
                'atendentes'    => $atendentes,
                'profissionais' => array_values(array_unique(array_filter(array_merge([$atribuido], $atendentes), 'strlen'))),
                'numero'     => (string) $t['numero'],
                'contato'    => (string) ($t['contato'] ?? ''),
            ];
        }
        usort($lista, function ($a, $b) { return strcmp($b['fechado_em'], $a['fechado_em']) ?: $b['id'] <=> $a['id']; });
        return $lista;
    }

    /** @var array especialidades ja buscadas no pedido [chave => texto] */
    private static $especialidades = [];

    /** Especialidades cadastradas na pessoa do profissional (atendimento avulso da Curciol, que nao tem agendamento). */
    public static function especialidadesDoProfissional(?int $pessoaId): string
    {
        if (!$pessoaId) { return ''; }
        return self::especialidades('p' . $pessoaId, 'WHERE p.id = ?', $pessoaId);
    }

    private static function especialidades(string $chave, string $onde, int $id): string
    {
        if (!array_key_exists($chave, self::$especialidades))
        {
            $st = TTransaction::get()->prepare("SELECT string_agg(DISTINCT e.descricao, ', ' ORDER BY e.descricao)
                                                  FROM pessoa p JOIN pessoa_especialidade pe ON pe.pessoa_id = p.id
                                                  JOIN especialidade e ON e.id = pe.especialidade_id {$onde}");
            $st->execute([$id]);
            self::$especialidades[$chave] = (string) $st->fetchColumn();
        }
        return self::$especialidades[$chave];
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

    /** O ticket, se for do cliente e o usuario o ve no APChat (senao excecao). */
    public static function ticket(int $ticketId, int $pessoaId, ?int $usuario = null): array
    {
        foreach (self::linhas($pessoaId) as $t)
        {
            if ((int) $t['id'] !== $ticketId) { continue; }
            if (!ApchatAcessoService::filtrar([$t], $usuario ?? self::usuarioLogado()))
            {
                throw new Exception('Você não tem acesso a este atendimento no APChat.');
            }
            return self::mapear([$t])[0];
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
