<?php
/**
 * Banco do APChat (03/11/2026): a Curciol le e escreve SO no schema "curciol" do banco do APChat (usuario curciol;
 * visoes so da Curciol - tenant 21 -, espelho e funcao de transferencia; ver
 * docs/apchat-2026-11-03/apchat-schema-curciol.sql). Conexao em app/config/apchat.php (fora do git).
 *
 * - contatosPorNumeros(): o "Sincronizar todos" e o salvar da pessoa leem aqui se o numero ja existe no APChat, em vez
 *   de consultar a API numero a numero (showcontact);
 * - gravarEspelho()/desligarEspelho(): o que a Curciol mandou para cada contato. A trigger de protecao no APChat so
 *   aceita, para contato da Curciol, os valores do espelho (edicao de qualquer campo do cadastro do contato volta ao que estava);
 * - transferir(): troca de numero na Curciol - o historico do contato antigo passa para o novo e o antigo e excluido;
 * - listar()/conversa(): tela "Contatos sem vinculo".
 *
 * Sem o arquivo de configuracao (ou com o banco do APChat fora), disponivel() e false: o envio usa so a API (como
 * antes) e a troca de numero vai para a fila.
 */
class ApchatBancoService
{
    const CONFIG = 'app/config/apchat.php';

    /** @var string arquivo de conexao em uso (os testes usam o do APChat de teste: usarConfig) */
    private static $config = self::CONFIG;

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

    /** Testes: outro arquivo de conexao (null = o padrao). */
    public static function usarConfig(?string $arquivo): void
    {
        self::$config = $arquivo ?? self::CONFIG;
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
        return self::$falso !== null || (!self::$desligado && is_file(self::$config));
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

    /**
     * Homologacao (11/11/2026): numeros cujo historico do WhatsApp aparece no cadastro do cliente, pela chave
     * 'historico_somente_numeros' do app/config/apchat.php. Sem a chave (producao): null = todos os numeros.
     */
    public static function historicoSomente(): ?array
    {
        if (!is_file(self::$config)) { return null; }
        $c = include self::$config;
        $lista = is_array($c) ? ($c['historico_somente_numeros'] ?? null) : null;
        return is_array($lista) ? array_values($lista) : null;
    }

    private static function conn(): PDO
    {
        if (self::$conn) { return self::$conn; }
        if (!is_file(self::$config))
        {
            throw new Exception('Conexão com o banco do APChat não configurada (' . self::$config . ').');
        }
        $c = include self::$config;
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
                                       FROM curciol.f_contatos(NULL, ?::varchar[]) WHERE NOT "isGroup"');
        $st->execute([self::arrayPg($todos)]);
        $r = [];
        foreach ($st->fetchAll() as $l) { $r[(string) $l['number']] = $l; }
        return $r;
    }

    /** Contato do APChat pelo id (null se nao existe ou nao e da Curciol). */
    public static function contato(int $id): ?array
    {
        if (self::$falso) { return self::$falso->contato($id); }
        $st = self::conn()->prepare('SELECT id, name, number, email, "createdAt" FROM curciol.f_contatos(ARRAY[?]::int[])');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    /** O que a Curciol mandou (ou vai mandar) para o contato: a trigger de protecao aceita estes valores. */
    public static function gravarEspelho(int $id, string $nome, string $numero, ?string $email, array $corpo = []): void
    {
        if (self::$falso) { self::$falso->gravarEspelho($id, $nome, $numero, $email); return; }
        // o espelho e a transferencia ficam no banco do APChat, o mesmo da producao: homologacao so le (09/11/2026)
        AmbienteService::exigirProducaoParaEnviar();
        $campos = self::camposDoEspelho($corpo);
        self::conn()->prepare('INSERT INTO curciol.contato (contato_id, nome, numero, email, cpf, primeiro_nome, ultimo_nome, razao_social, nascimento, ligado, atualizado_em)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, true, now())
                               ON CONFLICT (contato_id) DO UPDATE SET nome = EXCLUDED.nome, numero = EXCLUDED.numero, email = EXCLUDED.email, cpf = EXCLUDED.cpf,
                                                                      primeiro_nome = EXCLUDED.primeiro_nome, ultimo_nome = EXCLUDED.ultimo_nome,
                                                                      razao_social = EXCLUDED.razao_social, nascimento = EXCLUDED.nascimento,
                                                                      ligado = true, atualizado_em = now()')
             ->execute([$id, $nome, $numero, $email !== null && $email !== '' ? $email : null, $campos['cpf'], $campos['primeiro_nome'], $campos['ultimo_nome'], $campos['razao_social'], $campos['nascimento']]);
    }

    /** Os outros campos enviados ao APChat, no formato que a protecao compara: CPF so numeros, nascimento em aaaa-mm-dd. */
    public static function camposDoEspelho(array $corpo): array
    {
        $texto = function ($campo) use ($corpo) { $v = trim((string) ($corpo[$campo] ?? '')); return $v !== '' ? $v : null; };
        $cpf = preg_replace('/\D/', '', (string) ($corpo['cpf'] ?? ''));
        $nascimento = $texto('birthdayDate');

        return ['cpf' => $cpf !== '' ? $cpf : null, 'primeiro_nome' => $texto('firstName'), 'ultimo_nome' => $texto('lastName'), 'razao_social' => $texto('businessName'),
                'nascimento' => $nascimento !== null ? substr($nascimento, 0, 10) : null];
    }

    /** O contato deixou de ser da Curciol (numero saiu de todos os cadastros): fica travado no APChat. */
    public static function desligarEspelho(int $id): void
    {
        if (self::$falso) { self::$falso->desligarEspelho($id); return; }
        AmbienteService::exigirProducaoParaEnviar();
        self::conn()->prepare('UPDATE curciol.contato SET ligado = false, atualizado_em = now() WHERE contato_id = ?')->execute([$id]);
    }

    /** Passa o historico do contato antigo para o novo e exclui o antigo (se nada mais apontar para ele). */
    public static function transferir(int $antigo, int $novo): string
    {
        if (self::$falso) { return self::$falso->transferir($antigo, $novo); }
        AmbienteService::exigirProducaoParaEnviar();
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
    /** Ordem da lista "Contatos sem vinculo" (10/11/2026): so colunas permitidas; o resto vira a ordem padrao. */
    public static function ordemSemVinculo(string $coluna, string $direcao): string
    {
        $colunas = ['name' => 'lower(c.name)', 'number' => 'c.number', 'criado_em' => 'c."createdAt"', 'ultima_mensagem' => 't."updatedAt"',
                    'atendente' => 'lower(a.name)', 'fila' => 'f.queue'];
        if (!isset($colunas[$coluna]))
        {
            return 'c."createdAt" DESC NULLS LAST, c.id DESC';
        }
        return $colunas[$coluna] . (strtolower($direcao) === 'asc' ? ' ASC' : ' DESC') . ' NULLS LAST, c.id DESC';
    }

    public static function listar(string $situacao, array $curciol, array $filtros, int $offset, int $limit, string $ordem = '', string $direcao = ''): array
    {
        if (self::$falso) { return self::$falso->listar($situacao, $curciol, $filtros, $offset, $limit, $ordem, $direcao); }

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

        // 13/11/2026: leituras por funcao (o ultimo ticket de cada contato numa passada so)
        $de = 'FROM curciol.f_contatos() c
               LEFT JOIN (SELECT DISTINCT ON (tk."contactId") tk."contactId", tk.id, tk.status, tk."lastMessage", tk."lastMessageAt", tk."updatedAt", tk."userId", tk."queueId"
                            FROM curciol.f_tickets() tk ORDER BY tk."contactId", tk."updatedAt" DESC) t ON t."contactId" = c.id
               LEFT JOIN curciol.f_atendentes() a ON a.id = t."userId"
               LEFT JOIN curciol.f_filas() f ON f.id = t."queueId"
              WHERE ' . implode(' AND ', $onde);

        $st = self::conn()->prepare('SELECT count(*) ' . $de);
        $st->execute($args);
        $total = (int) $st->fetchColumn();

        $sql = 'SELECT c.id, c.name, c.number, c.pushname, c."createdAt" AS criado_em, t.id AS ticket_id, t.status AS ticket_status,
                       t."lastMessage" AS ultima_mensagem, t."updatedAt" AS ultima_mensagem_em, a.name AS atendente, f.queue AS fila ' . $de
             . ' ORDER BY ' . self::ordemSemVinculo($ordem, $direcao) . ($limit > 0 ? ' LIMIT ' . (int) $limit . ' OFFSET ' . max(0, (int) $offset) : '');
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
        $st = self::conn()->prepare('WITH m AS (SELECT * FROM curciol.f_mensagens(NULL, ARRAY[?]::int[]) ORDER BY "createdAt" DESC LIMIT ' . (int) $limite . '),
                                          t AS (SELECT * FROM curciol.f_tickets(COALESCE((SELECT array_agg(DISTINCT "ticketId") FROM m), \'{}\')))
                                     SELECT m.id, m.body, m."mediaType", m."fileName", m."fromMe", m."isDeleted", m."ticketId", m."createdAt", m.arquivada,
                                            a.name AS atendente, t.status AS ticket_status, f.queue AS fila, m.ack, w.name AS canal
                                       FROM m
                                       LEFT JOIN t ON t.id = m."ticketId" AND t.arquivado = m.arquivada
                                       LEFT JOIN curciol.f_atendentes() a ON a.id = m."userId"
                                       LEFT JOIN curciol.f_filas() f ON f.id = t."queueId"
                                       LEFT JOIN curciol.f_canais() w ON w.id = t."whatsappId"
                                      ORDER BY m."createdAt" DESC');
        $st->execute([$contatoId]);
        return array_reverse($st->fetchAll());
    }

    /**
     * Balao do topo (10/11/2026): tickets pendentes e abertos do tenant 21 com a ultima mensagem (texto, de quem e quando).
     * @return array linhas {id, status, contactId, userId, queueId, whatsappId, contato, numero, canal, fila, atendente, ultima_nossa,
     *                       ultima_texto, ultima_em, lastMessageAt, nao_respondidas (mensagens do cliente desde a nossa ultima)}
     */
    /**
     * Balao (10/11/2026): impressao digital dos tickets pendentes e abertos (id, situacao, atendente, fila, canal, ultima mensagem e
     * hora da ultima alteracao). Muda quando pegam, transferem, encerram ou chega mensagem. Usa o indice (tenantId, status) do APChat.
     */
    public static function impressaoTickets(): string
    {
        if (self::$falso) { return self::$falso->impressaoTickets(); }
        return (string) self::conn()->query('SELECT md5(COALESCE(string_agg(id || \':\' || status || \':\' || COALESCE("userId", 0) || \':\' || COALESCE("queueId", 0)
                                                 || \':\' || COALESCE("whatsappId", 0) || \':\' || COALESCE("lastMessageAt", 0) || \':\' || "updatedAt", \',\' ORDER BY id), \'\'))
                                               FROM curciol.f_tickets(NULL, NULL, true) WHERE NOT arquivado')->fetchColumn();
    }

    public static function ticketsAbertosComUltima(int $limite = 500): array
    {
        if (self::$falso) { return self::$falso->ticketsAbertosComUltima(); }
        // 13/11/2026: por funcao - abertos/pendentes, os contatos e as mensagens SO desses tickets (indices do APChat)
        $sql = 'WITH t AS (SELECT * FROM curciol.f_tickets(NULL, NULL, true) WHERE NOT arquivado),
                     c AS (SELECT * FROM curciol.f_contatos(COALESCE((SELECT array_agg(DISTINCT "contactId") FROM t), \'{}\'))),
                     ms AS (SELECT "ticketId", "fromMe", body, "createdAt", "isDeleted" FROM curciol.f_mensagens(COALESCE((SELECT array_agg(id) FROM t), \'{}\')) WHERE NOT arquivada),
                     ult AS (SELECT DISTINCT ON ("ticketId") "ticketId", "fromMe", body, "createdAt" FROM ms
                              WHERE NOT COALESCE("isDeleted", false) ORDER BY "ticketId", "createdAt" DESC),
                     nossa AS (SELECT "ticketId", max("createdAt") AS em FROM ms WHERE "fromMe" GROUP BY "ticketId"),
                     nr AS (SELECT ms."ticketId", count(*) AS n FROM ms LEFT JOIN nossa ON nossa."ticketId" = ms."ticketId"
                             WHERE NOT COALESCE(ms."isDeleted", false) AND NOT ms."fromMe" AND ms."createdAt" > COALESCE(nossa.em, \'-infinity\')
                             GROUP BY ms."ticketId")
                SELECT t.id, t.status, t."contactId", t."userId", t."queueId", t."whatsappId",
                       to_char(to_timestamp(t."lastMessageAt" / 1000.0), \'YYYY-MM-DD HH24:MI:SS\') AS "lastMessageAt",
                       c.name AS contato, c.number AS numero, w.name AS canal, f.queue AS fila, a.name AS atendente,
                       m."fromMe" AS ultima_nossa, m.body AS ultima_texto, to_char(m."createdAt", \'YYYY-MM-DD HH24:MI:SS\') AS ultima_em,
                       COALESCE(nr.n, 0) AS nao_respondidas,
                       t."isGroup", t.shared, t."userIdArray"::text AS "userIdArray", t."groupUserIdArray"::text AS "groupUserIdArray", t."chatFlowId"
                  FROM t
                  JOIN c ON c.id = t."contactId"
                  LEFT JOIN curciol.f_canais() w ON w.id = t."whatsappId"
                  LEFT JOIN curciol.f_filas() f ON f.id = t."queueId"
                  LEFT JOIN curciol.f_atendentes() a ON a.id = t."userId"
                  LEFT JOIN ult m ON m."ticketId" = t.id
                  LEFT JOIN nr ON nr."ticketId" = t.id
                 WHERE NOT COALESCE(c."isGroup", false)
                 ORDER BY COALESCE(m."createdAt", t."updatedAt") DESC LIMIT ' . max(1, (int) $limite);
        $linhas = self::conn()->query($sql)->fetchAll();
        foreach ($linhas as &$l)
        {
            $l['ultima_nossa'] = $l['ultima_nossa'] === true || $l['ultima_nossa'] === 't' || $l['ultima_nossa'] === 1;
        }
        return $linhas;
    }

    /**
     * Entrega (10/11/2026): mensagens enviadas (fromMe) para estes numeros num intervalo, com os tiques. Vai pelo contato do
     * ticket (a copia, no canal oficial, de mensagem enviada pela API vem sem contato na mensagem).
     * @return array linhas {id, messageId, body, ack, createdAt, numero}
     */
    public static function mensagensEnviadasPara(array $numeros, string $desde, string $ate): array
    {
        if (self::$falso) { return self::$falso->mensagensEnviadasPara($numeros, $desde, $ate); }
        $st = self::conn()->prepare('WITH c AS (SELECT id, number FROM curciol.f_contatos(NULL, ?::varchar[])),
                                          t AS (SELECT id, arquivado, "contactId" FROM curciol.f_tickets(NULL, COALESCE((SELECT array_agg(id) FROM c), \'{}\')))
                                     SELECT m.id, m."messageId", m."ticketId", m.body, m.ack, m."createdAt", c.number AS numero
                                       FROM curciol.f_mensagens(COALESCE((SELECT array_agg(id) FROM t), \'{}\'), NULL, ?::timestamptz, ?::timestamptz) m
                                       JOIN t ON t.id = m."ticketId" AND t.arquivado = m.arquivada
                                       JOIN c ON c.id = t."contactId"
                                      WHERE m."fromMe" AND NOT COALESCE(m."isDeleted", false)
                                      ORDER BY m."createdAt", m.id LIMIT 200');
        $st->execute([self::arrayPg($numeros), $desde, $ate]);
        return $st->fetchAll();
    }

    /**
     * Historico no cliente (10/11/2026): tickets FECHADOS dos numeros, com canal, fila, atendente, quem encerrou e os atendentes
     * que escreveram (nomes separados por |, do primeiro ao ultimo a escrever), do encerramento mais recente para o mais antigo.
     */
    public static function ticketsFechadosDosNumeros(array $numeros): array
    {
        if (self::$falso) { return self::$falso->ticketsFechadosDosNumeros($numeros); }
        // 13/11/2026: por funcao; traz tambem o que a regra de visibilidade usa (canal, fila, convite, grupo, robo)
        $st = self::conn()->prepare('WITH c AS (SELECT id, number, name FROM curciol.f_contatos(NULL, ?::varchar[])),
                                          t AS (SELECT * FROM curciol.f_tickets(NULL, COALESCE((SELECT array_agg(id) FROM c), \'{}\')) WHERE status = \'closed\'),
                                          m AS (SELECT "ticketId", arquivada, "fromMe", "userId", "createdAt" FROM curciol.f_mensagens(COALESCE((SELECT array_agg(id) FROM t), \'{}\'))),
                                          at AS (SELECT id, name FROM curciol.f_atendentes())
                                     SELECT t.id, t.arquivado, t.status, c.number AS numero, c.name AS contato, to_char(t."createdAt", \'YYYY-MM-DD HH24:MI:SS\') AS aberto_em,
                                            to_char(COALESCE(to_timestamp(t."closedAt" / 1000.0), t."updatedAt"), \'YYYY-MM-DD HH24:MI:SS\') AS fechado_em, w.name AS canal, f.queue AS fila, t."userId", a.name AS atendente,
                                            t."closedByUserId", af.name AS fechou,
                                            (SELECT string_agg(x.name, \'|\' ORDER BY x.ultima)
                                               FROM (SELECT at.name, max(m."createdAt") AS ultima FROM m
                                                       JOIN at ON at.id = m."userId"
                                                      WHERE m."ticketId" = t.id AND m.arquivada = t.arquivado AND m."fromMe"
                                                      GROUP BY at.name) x) AS atendentes,
                                            EXISTS (SELECT 1 FROM m mc WHERE mc."ticketId" = t.id AND mc.arquivada = t.arquivado AND NOT mc."fromMe") AS do_cliente,
                                            t."contactId", t."queueId", t."whatsappId", t."isGroup", t.shared, t."userIdArray"::text AS "userIdArray",
                                            t."groupUserIdArray"::text AS "groupUserIdArray", t."chatFlowId"
                                       FROM t
                                       JOIN c ON c.id = t."contactId"
                                       LEFT JOIN curciol.f_canais() w ON w.id = t."whatsappId"
                                       LEFT JOIN curciol.f_filas() f ON f.id = t."queueId"
                                       LEFT JOIN at a ON a.id = t."userId"
                                       LEFT JOIN at af ON af.id = t."closedByUserId"
                                      ORDER BY COALESCE(to_timestamp(t."closedAt" / 1000.0), t."updatedAt") DESC, t.id DESC LIMIT 500');
        // fechado pelo isClosed do envio da API: o APChat nao preenche o closedAt -> encerramento = ultima atualizacao do ticket
        $st->execute([self::arrayPg($numeros)]);
        return $st->fetchAll();
    }

    /** Mensagens de um ticket, da mais antiga para a mais recente (mesmo formato de conversa()). */
    public static function mensagensDoTicket(int $ticketId, bool $arquivado): array
    {
        if (self::$falso) { return self::$falso->mensagensDoTicket($ticketId, $arquivado); }
        $st = self::conn()->prepare('SELECT m.id, m.body, m."mediaType", m."fileName", m."fromMe", m."isDeleted", m."ticketId", m."createdAt", m.arquivada,
                                            a.name AS atendente, f.queue AS fila, m.ack, w.name AS canal
                                       FROM curciol.f_mensagens(ARRAY[?]::int[]) m
                                       LEFT JOIN curciol.f_atendentes() a ON a.id = m."userId"
                                       LEFT JOIN curciol.f_tickets(ARRAY[?]::int[]) t ON t.id = m."ticketId" AND t.arquivado = m.arquivada
                                       LEFT JOIN curciol.f_filas() f ON f.id = t."queueId"
                                       LEFT JOIN curciol.f_canais() w ON w.id = t."whatsappId"
                                      WHERE m.arquivada = ?
                                      ORDER BY m."createdAt", m.id LIMIT 3000');
        $st->execute([$ticketId, $ticketId, $arquivado ? 'true' : 'false']);
        return $st->fetchAll();
    }

    /**
     * Acessos de um atendente do APChat: canais (ativos), filas e, desde 13/11/2026, o perfil (admin, super, user...), as
     * configuracoes do usuario (supervisorViewDept...) e o "ver todos os atendimentos" do perfil personalizado.
     * @return array {canais: int[], filas: int[], perfil: ?string, configs: array, ver_todos: bool, existe: bool}
     */
    public static function acessos(int $atendente): array
    {
        if (self::$falso) { return self::$falso->acessos($atendente); }
        $c = self::conn();
        $st = $c->prepare('SELECT "whatsappId" FROM curciol.f_canais_atendentes() WHERE "userId" = ? AND COALESCE("isActive", true)');
        $st->execute([$atendente]);
        $canais = array_map('intval', array_column($st->fetchAll(), 'whatsappId'));
        $st = $c->prepare('SELECT "queueId" FROM curciol.f_filas_atendentes() WHERE "userId" = ?');
        $st->execute([$atendente]);
        $filas = array_map('intval', array_column($st->fetchAll(), 'queueId'));
        $st = $c->prepare('SELECT profile, configs::text AS configs, ver_todos FROM curciol.f_atendentes() WHERE id = ?');
        $st->execute([$atendente]);
        $u = $st->fetch() ?: null;
        $configs = $u ? json_decode((string) $u['configs'], true) : null;
        return ['canais' => $canais, 'filas' => $filas, 'perfil' => $u ? $u['profile'] : null, 'configs' => is_array($configs) ? $configs : [],
                'ver_todos' => $u ? ($u['ver_todos'] === true || $u['ver_todos'] === 't') : false, 'existe' => $u !== null];
    }

    /** Configuracoes da empresa no APChat que mudam quem ve o que (texto como o APChat guarda: enabled/disabled). */
    public static function configuracoes(): array
    {
        if (self::$falso) { return method_exists(self::$falso, 'configuracoes') ? self::$falso->configuracoes() : []; }
        $c = json_decode((string) self::conn()->query('SELECT curciol.f_configuracoes()::text')->fetchColumn(), true);
        return is_array($c) ? $c : [];
    }

    /** Donos das carteiras dos contatos. @return array [contactId => int[] usuarios] */
    public static function carteiras(array $contatos): array
    {
        if (self::$falso) { return method_exists(self::$falso, 'carteiras') ? self::$falso->carteiras($contatos) : []; }
        $ids = self::arrayPg($contatos);
        if ($ids === '{}') { return []; }
        $st = self::conn()->prepare('SELECT "contactId", "walletId" FROM curciol.f_carteiras(?::int[])');
        $st->execute([$ids]);
        $r = [];
        foreach ($st->fetchAll() as $l) { $r[(int) $l['contactId']][] = (int) $l['walletId']; }
        return $r;
    }

    /**
     * Chat interno (13/11/2026): conversas com mensagens nao lidas de cada atendente (direta = usuario que mandou; equipe = grupo).
     * @return array linhas {usuario, tipo (usuario|equipe), conversa_id, nome, qtd, ultima, ultima_tipo, ultima_em}
     */
    public static function chatNaoLidas(array $atendentes): array
    {
        if (self::$falso) { return method_exists(self::$falso, 'chatNaoLidas') ? self::$falso->chatNaoLidas($atendentes) : []; }
        $ids = self::arrayPg($atendentes);
        if ($ids === '{}') { return []; }
        $st = self::conn()->prepare('SELECT usuario, tipo, conversa_id, nome, qtd, ultima, ultima_tipo, to_char(ultima_em, \'YYYY-MM-DD HH24:MI:SS\') AS ultima_em
                                       FROM curciol.f_chat_nao_lidas(?::int[]) ORDER BY ultima_em DESC');
        $st->execute([$ids]);
        return $st->fetchAll();
    }

    /**
     * Atendentes do APChat (tenant 21), para ligar aos usuarios da Curciol (10/11/2026).
     * @return array linhas {id, name, email, inactive}
     */
    public static function atendentes(): array
    {
        if (self::$falso) { return self::$falso->atendentes(); }
        return self::conn()->query('SELECT id, name, email, COALESCE(inactive, false) AS inactive FROM curciol.f_atendentes() ORDER BY name')->fetchAll();
    }

    /**
     * Conciliacao (10/11/2026): contatos do APChat (tenant 21) com numero e fora do espelho, os mais novos primeiro.
     * @return array linhas {id, number, name}
     */
    public static function contatosForaDoEspelho(int $limite = 200): array
    {
        if (self::$falso) { return self::$falso->contatosForaDoEspelho($limite); }
        $st = self::conn()->query('SELECT c.id, c.number, c.name FROM curciol.f_contatos() c
                                    WHERE NOT COALESCE(c."isGroup", false) AND COALESCE(c.number, \'\') <> \'\'
                                      AND NOT EXISTS (SELECT 1 FROM curciol.contato e WHERE e.contato_id = c.id)
                                    ORDER BY c.id DESC LIMIT ' . max(1, (int) $limite));
        return $st->fetchAll();
    }

    // =================================================================
    // Resposta SIM/NAO do WhatsApp de confirmacao (ApchatRespostaService)
    // =================================================================

    /**
     * Conversa do numero depois de um momento, da mais antiga para a mais recente: mensagens do cliente e as nossas. Vai
     * pelo contato do atendimento (a copia, no canal oficial, de mensagem enviada pela API vem sem contato).
     *
     * @param string[] $numeros 55 + DDD + numero (com e sem o nono digito)
     * @param string   $desde   'Y-m-d H:i:s' no horario de Sao Paulo
     * @return array linhas {id, body, mediaType, fromMe, createdAt}
     */
    public static function conversaDepois(array $numeros, string $desde, int $limite = 50): array
    {
        if (self::$falso) { return self::$falso->conversaDepois($numeros, $desde, $limite); }
        $st = self::conn()->prepare('WITH c AS (SELECT id FROM curciol.f_contatos(NULL, ?::varchar[])),
                                          t AS (SELECT id, arquivado FROM curciol.f_tickets(NULL, COALESCE((SELECT array_agg(id) FROM c), \'{}\')))
                                     SELECT m.id, m.body, m."mediaType", m."fromMe", m."createdAt"
                                       FROM curciol.f_mensagens(COALESCE((SELECT array_agg(id) FROM t), \'{}\'), NULL, ?::timestamptz) m
                                       JOIN t ON t.id = m."ticketId" AND t.arquivado = m.arquivada
                                      WHERE NOT COALESCE(m."isDeleted", false)
                                        AND m."createdAt" > ?::timestamptz
                                      ORDER BY m."createdAt", m.id LIMIT ' . (int) $limite);
        $st->execute([self::arrayPg($numeros), $desde, $desde]);
        return $st->fetchAll();
    }
}
