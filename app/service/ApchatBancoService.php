<?php
/**
 * Banco do APChat (03/11/2026): a Curciol le e escreve SO no schema "curciol" do banco do APChat (usuario curciol;
 * visoes so da Curciol - tenant 21 -, espelho e funcao de transferencia; ver
 * docs/apchat-2026-11-03/apchat-schema-curciol.sql). Conexao em app/config/apchat.php (fora do git).
 *
 * - contatosPorNumeros(): o "Sincronizar todos" e o salvar da pessoa leem aqui se o numero ja existe no APChat, em vez
 *   de consultar a API numero a numero (showcontact);
 * - gravarEspelho()/desligarEspelho(): o que a Curciol mandou para cada contato. A trigger de protecao no APChat so
 *   aceita, para contato da Curciol, os valores do espelho (edicao pelo APChat volta ao que estava);
 * - transferir(): troca de numero na Curciol - o historico do contato antigo passa para o novo e o antigo e excluido;
 * - listar()/conversa(): tela "Contatos sem vinculo".
 *
 * Sem o arquivo de configuracao (ou com o banco do APChat fora), disponivel() e false: o envio usa so a API (como
 * antes) e a troca de numero vai para a fila.
 */
class ApchatBancoService
{
    const CONFIG = 'app/config/apchat.php';

    /** @var PDO|null */
    private static $conn = null;
    /** @var bool|null */
    private static $disponivel = null;
    /** @var string */
    private static $erro = '';
    /** @var object|null substitui o banco nos testes (mesmos metodos publicos) */
    private static $falso = null;

    /** @var bool testes: nunca usar o banco real do APChat (tests/bootstrap.php) */
    private static $desligado = false;

    public static function usarFalso(?object $falso): void
    {
        self::$falso = $falso;
        self::$conn = null;
        self::$disponivel = null;
    }

    public static function desligar(bool $desligado = true): void
    {
        self::$desligado = $desligado;
        self::$conn = null;
        self::$disponivel = null;
    }

    /** Instalacao com o banco do APChat configurado (sem ele, o envio e so pela API, como antes). */
    public static function configurado(): bool
    {
        return self::$falso !== null || (!self::$desligado && is_file(self::CONFIG));
    }

    /** O banco do APChat responde? (lembra a resposta ate o fim do pedido) */
    public static function disponivel(): bool
    {
        if (self::$falso) { return method_exists(self::$falso, 'disponivel') ? (bool) self::$falso->disponivel() : true; }
        if (self::$disponivel !== null) { return self::$disponivel; }
        try
        {
            self::conn();
            self::$disponivel = true;
        }
        catch (Throwable $e)
        {
            self::$erro = $e->getMessage();
            self::$disponivel = false;
        }
        return self::$disponivel;
    }

    public static function erro(): string
    {
        return self::$erro;
    }

    private static function conn(): PDO
    {
        if (self::$conn) { return self::$conn; }
        if (!is_file(self::CONFIG))
        {
            throw new Exception('Conexão com o banco do APChat não configurada (' . self::CONFIG . ').');
        }
        $c = include self::CONFIG;
        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s;connect_timeout=5', $c['host'], $c['port'] ?? '5432', $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec("SET TIME ZONE 'America/Sao_Paulo'");
        return self::$conn = $pdo;
    }

    /** array do PostgreSQL ({1,2,3} / {"551199...",...}) so com digitos */
    private static function arrayPg(array $valores): string
    {
        $limpos = array_values(array_unique(array_filter(array_map(function ($v) { return preg_replace('/\D/', '', (string) $v); }, $valores), 'strlen')));
        return '{' . implode(',', $limpos) . '}';
    }

    // =================================================================
    // Envio (APChatContactService)
    // =================================================================

    /**
     * Contatos do APChat com estes numeros (com e sem o nono digito).
     *
     * @param string[] $numeros 55 + DDD + numero
     * @return array [numero do APChat => linha {id, name, number, email, cpf, firstName, lastName, businessName, birthdayDate}]
     */
    public static function contatosPorNumeros(array $numeros): array
    {
        if (self::$falso) { return self::$falso->contatosPorNumeros($numeros); }
        $todos = [];
        foreach ($numeros as $n) { foreach (APChatContactService::variantes((string) $n) as $v) { $todos[] = $v; } }
        if (!$todos) { return []; }
        $st = self::conn()->prepare('SELECT id, name, number, email, cpf, "firstName", "lastName", "businessName", "birthdayDate"
                                       FROM curciol.contatos WHERE number = ANY (?::varchar[]) AND NOT "isGroup"');
        $st->execute([self::arrayPg($todos)]);
        $r = [];
        foreach ($st->fetchAll() as $l) { $r[(string) $l['number']] = $l; }
        return $r;
    }

    /** Contato do APChat pelo id (null se nao existe ou nao e da Curciol). */
    public static function contato(int $id): ?array
    {
        if (self::$falso) { return self::$falso->contato($id); }
        $st = self::conn()->prepare('SELECT id, name, number, email, "createdAt" FROM curciol.contatos WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** O que a Curciol mandou (ou vai mandar) para o contato: a trigger de protecao aceita estes valores. */
    public static function gravarEspelho(int $id, string $nome, string $numero, ?string $email): void
    {
        if (self::$falso) { self::$falso->gravarEspelho($id, $nome, $numero, $email); return; }
        self::conn()->prepare('INSERT INTO curciol.contato (contato_id, nome, numero, email, ligado, atualizado_em) VALUES (?, ?, ?, ?, true, now())
                               ON CONFLICT (contato_id) DO UPDATE SET nome = EXCLUDED.nome, numero = EXCLUDED.numero, email = EXCLUDED.email,
                                                                      ligado = true, atualizado_em = now()')
             ->execute([$id, $nome, $numero, $email !== null && $email !== '' ? $email : null]);
    }

    /** O contato deixou de ser da Curciol (numero saiu de todos os cadastros): fica travado no APChat. */
    public static function desligarEspelho(int $id): void
    {
        if (self::$falso) { self::$falso->desligarEspelho($id); return; }
        self::conn()->prepare('UPDATE curciol.contato SET ligado = false, atualizado_em = now() WHERE contato_id = ?')->execute([$id]);
    }

    /** Passa o historico do contato antigo para o novo e exclui o antigo (se nada mais apontar para ele). */
    public static function transferir(int $antigo, int $novo): string
    {
        if (self::$falso) { return self::$falso->transferir($antigo, $novo); }
        $st = self::conn()->prepare('SELECT curciol.transferir_contato(?, ?)');
        $st->execute([$antigo, $novo]);
        return (string) $st->fetchColumn();
    }

    // =================================================================
    // Tela "Contatos sem vinculo"
    // =================================================================

    /**
     * Contatos do APChat (sem grupos), do mais recente ao mais antigo, com o ultimo ticket.
     *
     * @param string $situacao SEM_VINCULO | IGNORADOS | VINCULADOS
     * @param array  $curciol  ['ids' => idapchat da Curciol, 'numeros' => numeros 55+ da Curciol (com variantes), 'ignorados' => ids]
     * @param array  $filtros  nome, numero, criado_de, criado_ate (Y-m-d), mensagem, atendente, fila
     * @return array [linhas, total]
     */
    public static function listar(string $situacao, array $curciol, array $filtros, int $offset, int $limit): array
    {
        if (self::$falso) { return self::$falso->listar($situacao, $curciol, $filtros, $offset, $limit); }

        $onde = ['NOT c."isGroup"'];
        $args = [];
        $ids = self::arrayPg($curciol['ids'] ?? []);
        $nums = self::arrayPg($curciol['numeros'] ?? []);
        $ign = self::arrayPg($curciol['ignorados'] ?? []);
        if ($situacao === 'IGNORADOS')
        {
            $onde[] = 'c.id = ANY (?::int[])';
            $args[] = $ign;
        }
        elseif ($situacao === 'VINCULADOS')
        {
            $onde[] = '(c.id = ANY (?::int[]) OR COALESCE(c.number, \'\') = ANY (?::varchar[]))';
            array_push($args, $ids, $nums);
        }
        else
        {
            $onde[] = 'NOT (c.id = ANY (?::int[])) AND NOT (COALESCE(c.number, \'\') = ANY (?::varchar[])) AND NOT (c.id = ANY (?::int[]))';
            array_push($args, $ids, $nums, $ign);
        }

        $texto = function ($v) { return '%' . mb_strtolower(trim((string) $v)) . '%'; };
        if (trim((string) ($filtros['nome'] ?? '')) !== '')
        {
            $onde[] = 'lower(unaccent(c.name)) LIKE lower(unaccent(?))';
            $args[] = $texto($filtros['nome']);
        }
        if (preg_replace('/\D/', '', (string) ($filtros['numero'] ?? '')) !== '')
        {
            $onde[] = 'c.number LIKE ?';
            $args[] = '%' . preg_replace('/\D/', '', (string) $filtros['numero']) . '%';
        }
        if (!empty($filtros['criado_de']))
        {
            $onde[] = 'c."createdAt" >= ?::date';
            $args[] = $filtros['criado_de'];
        }
        if (!empty($filtros['criado_ate']))
        {
            $onde[] = 'c."createdAt" < (?::date + 1)';
            $args[] = $filtros['criado_ate'];
        }
        if (trim((string) ($filtros['mensagem'] ?? '')) !== '')
        {
            $onde[] = 'lower(unaccent(COALESCE(t."lastMessage", \'\'))) LIKE lower(unaccent(?))';
            $args[] = $texto($filtros['mensagem']);
        }
        if (trim((string) ($filtros['atendente'] ?? '')) !== '')
        {
            $onde[] = 'lower(unaccent(COALESCE(a.name, \'\'))) LIKE lower(unaccent(?))';
            $args[] = $texto($filtros['atendente']);
        }
        if (trim((string) ($filtros['fila'] ?? '')) !== '')
        {
            $onde[] = 'lower(unaccent(COALESCE(f.queue, \'\'))) LIKE lower(unaccent(?))';
            $args[] = $texto($filtros['fila']);
        }

        $de = 'FROM curciol.contatos c
               LEFT JOIN LATERAL (SELECT tk.id, tk.status, tk."lastMessage", tk."lastMessageAt", tk."updatedAt", tk."userId", tk."queueId"
                                    FROM curciol.tickets tk WHERE tk."contactId" = c.id ORDER BY tk."updatedAt" DESC LIMIT 1) t ON true
               LEFT JOIN curciol.atendentes a ON a.id = t."userId"
               LEFT JOIN curciol.filas f ON f.id = t."queueId"
              WHERE ' . implode(' AND ', $onde);

        $st = self::conn()->prepare('SELECT count(*) ' . $de);
        $st->execute($args);
        $total = (int) $st->fetchColumn();

        $sql = 'SELECT c.id, c.name, c.number, c.pushname, c."createdAt" AS criado_em, t.id AS ticket_id, t.status AS ticket_status,
                       t."lastMessage" AS ultima_mensagem, t."updatedAt" AS ultima_mensagem_em, a.name AS atendente, f.queue AS fila ' . $de
             . ' ORDER BY c."createdAt" DESC, c.id DESC' . ($limit > 0 ? ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, (int) $offset) : '');
        $st = self::conn()->prepare($sql);
        $st->execute($args);

        return [$st->fetchAll(), $total];
    }

    /**
     * Mensagens do contato (de todos os tickets, inclusive os arquivados), da mais antiga para a mais recente.
     * @return array linhas {id, body, mediaType, fileName, fromMe, isDeleted, ticketId, createdAt, arquivada, atendente, ticket_status, fila}
     */
    public static function conversa(int $contatoId, int $limite = 3000): array
    {
        if (self::$falso) { return self::$falso->conversa($contatoId, $limite); }
        $st = self::conn()->prepare('SELECT m.id, m.body, m."mediaType", m."fileName", m."fromMe", m."isDeleted", m."ticketId", m."createdAt", m.arquivada,
                                            a.name AS atendente, t.status AS ticket_status, f.queue AS fila
                                       FROM curciol.mensagens m
                                       LEFT JOIN curciol.tickets t ON t.id = m."ticketId" AND t.arquivado = m.arquivada
                                       LEFT JOIN curciol.atendentes a ON a.id = m."userId"
                                       LEFT JOIN curciol.filas f ON f.id = t."queueId"
                                      WHERE m."contactId" = ?
                                      ORDER BY m."createdAt" DESC LIMIT ' . (int) $limite);
        $st->execute([$contatoId]);
        return array_reverse($st->fetchAll());
    }

    // =================================================================
    // Resposta SIM/NAO do WhatsApp de confirmacao (ApchatRespostaService)
    // =================================================================

    /**
     * Primeira mensagem que o cliente mandou (nao nossa) de um destes numeros depois de um momento.
     *
     * @param string[] $numeros 55 + DDD + numero (com e sem o nono digito)
     * @param string   $desde   'Y-m-d H:i:s' no horario de Sao Paulo
     * @return array|null {id, body, mediaType, createdAt}
     */
    public static function primeiraRespostaDepois(array $numeros, string $desde): ?array
    {
        if (self::$falso) { return self::$falso->primeiraRespostaDepois($numeros, $desde); }
        $st = self::conn()->prepare('SELECT m.id, m.body, m."mediaType", m."createdAt"
                                       FROM curciol.mensagens m
                                       JOIN curciol.contatos c ON c.id = m."contactId"
                                      WHERE c.number = ANY (?::varchar[])
                                        AND NOT m."fromMe" AND NOT COALESCE(m."isDeleted", false)
                                        AND m."createdAt" > ?::timestamptz
                                      ORDER BY m."createdAt", m.id LIMIT 1');
        $st->execute([self::arrayPg($numeros), $desde]);
        $r = $st->fetch();
        return $r ?: null;
    }
}
