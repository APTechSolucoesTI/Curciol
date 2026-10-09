<?php

/**
 * Familia de processos (principal, incidentes, incidentes de incidentes) e o
 * que dela o cliente pode ver no portal.
 *
 * A arvore vem de processo_vinculo (processo_principal_id -> processo_incidente_id).
 * A base tem autovinculos (A -> A), linhas repetidas, processos com mais de
 * um pai e alguns ciclos (A -> B -> A). Por isso:
 *   - autovinculos e repeticoes sao descartados na leitura (CTE "v");
 *   - as recursoes usam UNION, e nao UNION ALL: uma linha ja vista nao volta,
 *     entao um ciclo termina sozinho em vez de girar para sempre;
 *   - com mais de um pai, o processo so e exibido se TODOS os caminhos acima
 *     dele estiverem exibidos.
 *
 * Regras (ver docs/spec-portal-processos-vinculados.md, secao 4):
 *   - exibicao liberada: o processo e todos os ancestrais com exibir_cliente = 'S';
 *   - visivel para o cliente: exibicao liberada e o cliente no contrato do processo;
 *   - topo para o cliente: visivel e sem nenhum ancestral visivel para ele. Num
 *     ciclo, em que cada um e ancestral do outro, fica o de menor id;
 *   - familia visivel: o topo e os descendentes visiveis para o cliente. Um
 *     intermediario invisivel nao esconde quem esta abaixo dele.
 *
 * Convencao de transacao igual a do PreProcessoService: nada aqui abre ou
 * fecha transacao, quem chama controla o escopo.
 */
class ProcessoFamiliaService
{
    /**
     * Arestas pai -> filho validas. Entra no WITH de todas as consultas.
     */
    private const CTE_VINCULOS = "
        v AS (
            SELECT DISTINCT
                processo_principal_id AS pai,
                processo_incidente_id AS filho
            FROM processo_vinculo
            WHERE processo_principal_id IS NOT NULL
              AND processo_incidente_id IS NOT NULL
              AND processo_principal_id <> processo_incidente_id
        )
    ";

    /**
     * Processos em que o cliente esta no contrato. Mesmo caminho de
     * processo_view e de PreProcessoService::clientesDoProcesso.
     */
    private const CTE_MEUS = "
        meus AS (
            SELECT DISTINCT cp.processo_id AS id
            FROM contrato_processo cp
            JOIN contrato_pessoa cpe ON cpe.contrato_id = cp.contrato_id
            WHERE cpe.cliente_id = :cliente_id
        )
    ";

    /**
     * Etapas que nunca aparecem para o cliente.
     */
    const ETAPAS_OCULTAS = [1, 10];

    /**
     * Etapas de abertura das duas trilhas. O filtro da trilha escolhe o par:
     * extrajudicial 8 e 2, judicial 15 e 16.
     */
    const ETAPAS_ABERTURA = [8, 2, 15, 16];

    // =================================================================
    // Hierarquia e exibicao
    // =================================================================

    /**
     * Ancestrais do processo, do mais proximo ao mais distante.
     *
     * @return int[]
     */
    public static function ancestrais($processo_id): array
    {
        $linhas = self::ancestraisComNivel($processo_id);

        return array_map('intval', array_column($linhas, 'id'));
    }

    /**
     * O ancestral oculto mais proximo, ou null se todos estiverem exibidos.
     *
     * E ele que o back-office cita para explicar por que o botao de exibicao
     * esta travado.
     */
    public static function primeiroAncestralOculto($processo_id): ?int
    {
        foreach (self::ancestraisComNivel($processo_id) as $linha)
        {
            if (!self::flagExibir($linha['exibir_cliente']))
            {
                return (int) $linha['id'];
            }
        }

        return null;
    }

    /**
     * Para um incidente que ainda vai ser criado debaixo de $pai_id: o
     * proprio pai, se estiver oculto, ou o ancestral oculto mais proximo dele.
     */
    public static function primeiroOcultoAPartirDe($pai_id): ?int
    {
        $pai_id = (int) $pai_id;

        if ($pai_id <= 0)
        {
            return null;
        }

        $pai = Processo::find($pai_id);

        if ($pai && !self::flagExibir($pai->exibir_cliente))
        {
            return $pai_id;
        }

        return self::primeiroAncestralOculto($pai_id);
    }

    /**
     * O processo acima que esta oculto e que da para reexibir: o botao dele
     * esta livre, porque todos os processos acima dele estao exibidos. E esse
     * que o aviso do back-office cita - o mais proximo pode estar travado
     * tambem (pai e avo ocultos: o botao do pai so destrava depois do avo).
     *
     * Null quando nenhum processo acima esta oculto.
     */
    public static function ancestralOcultoParaReexibir($processo_id): ?int
    {
        return self::ocultoLiberavel(self::ancestraisComNivel($processo_id));
    }

    /**
     * O mesmo, para um incidente que ainda vai ser criado debaixo de $pai_id:
     * o proprio pai entra na conta.
     */
    public static function ocultoParaReexibirAPartirDe($pai_id): ?int
    {
        $pai = Processo::find((int) $pai_id);

        if (!$pai)
        {
            return null;
        }

        $linhas = array_merge(
            [['id' => (int) $pai->id, 'nivel' => 0, 'exibir_cliente' => $pai->exibir_cliente]],
            self::ancestraisComNivel($pai->id)
        );

        return self::ocultoLiberavel($linhas);
    }

    /**
     * Como o processo e citado nos avisos: o numero; sem numero (pre-processo),
     * a descricao. O id interno nao diz nada a quem le.
     */
    public static function rotuloProcesso($processo): string
    {
        if (empty($processo))
        {
            return '';
        }

        $numero = trim((string) ($processo->numero_cnj_numero ?? ''));

        if ($numero !== '')
        {
            return $numero;
        }

        $descricao = trim((string) ($processo->descricao_pre_processo ?? ''));

        return $descricao !== '' ? "pré-processo \"{$descricao}\"" : 'sem número';
    }

    /**
     * O processo pode aparecer no portal (para quem estiver no contrato)?
     */
    public static function exibicaoLiberada($processo_id): bool
    {
        $processo = Processo::find((int) $processo_id);

        if (!$processo || !self::flagExibir($processo->exibir_cliente))
        {
            return false;
        }

        return self::primeiroAncestralOculto($processo_id) === null;
    }

    // =================================================================
    // Portal
    // =================================================================

    /**
     * De quem e a visao pedida.
     *
     * As telas do portal estao em public_classes. Com cliente logado no
     * portal, vale a sessao e o parametro da requisicao e ignorado - trocar o
     * numero na URL nao mostra o processo de outra pessoa. Sem sessao de
     * portal, o parametro so vale para usuario logado no back-office;
     * para mais ninguem.
     */
    public static function clienteDaRequisicao($cliente_param): int
    {
        $cliente_portal = (int) (TSession::getValue('portal_cliente_id') ?? 0);

        if ($cliente_portal > 0)
        {
            return $cliente_portal;
        }

        if (TSession::getValue('logged'))
        {
            return (int) $cliente_param;
        }

        return 0;
    }

    /**
     * Processos que o cliente ve como principais na lista "Meus processos".
     *
     * @return int[]
     */
    public static function toposDoCliente($cliente_id): array
    {
        $cliente_id = (int) $cliente_id;

        if ($cliente_id <= 0)
        {
            return [];
        }

        /*
            anc(id, a): "a" e ancestral de "id", para cada processo do cliente.
            liberado: processos do cliente com exibicao liberada.
            Um liberado deixa de ser topo quando tem um ancestral tambem
            liberado - exceto quando os dois estao no mesmo ciclo e o
            ancestral tem id maior; assim um ciclo fechado ainda tem um topo.
        */
        $sql = "
            WITH RECURSIVE
            " . self::CTE_VINCULOS . ",
            " . self::CTE_MEUS . ",
            anc(id, a) AS (
                SELECT m.id, v.pai
                FROM meus m
                JOIN v ON v.filho = m.id

                UNION

                SELECT anc.id, v.pai
                FROM anc
                JOIN v ON v.filho = anc.a
            ),
            liberado AS (
                SELECT m.id
                FROM meus m
                JOIN processo p ON p.id = m.id
                WHERE COALESCE(UPPER(TRIM(p.exibir_cliente)), 'N') = 'S'
                  AND NOT EXISTS (
                      SELECT 1
                      FROM anc
                      JOIN processo pa ON pa.id = anc.a
                      WHERE anc.id = m.id
                        AND COALESCE(UPPER(TRIM(pa.exibir_cliente)), 'N') <> 'S'
                  )
            )
            SELECT l.id
            FROM liberado l
            WHERE NOT EXISTS (
                SELECT 1
                FROM anc
                JOIN liberado l2 ON l2.id = anc.a
                WHERE anc.id = l.id
                  AND anc.a <> l.id
                  AND NOT (
                      anc.a > l.id
                      AND EXISTS (
                          SELECT 1 FROM anc volta
                          WHERE volta.id = anc.a
                            AND volta.a = l.id
                      )
                  )
            )
            ORDER BY l.id
        ";

        self::semJit();

        $stmt = TTransaction::get()->prepare($sql);
        $stmt->execute([':cliente_id' => $cliente_id]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * O topo e os descendentes que o cliente pode ver. Vazio quando o
     * processo nao e um topo desse cliente - e isso que barra o acesso por
     * URL a um processo alheio ou a um incidente solto.
     *
     * @param int[]|null $topos_do_cliente toposDoCliente($cliente_id), quando
     *        quem chama ja tem (a lista, que monta uma linha por topo)
     * @return int[]
     */
    public static function familiaVisivel($topo_id, $cliente_id, ?array $topos_do_cliente = null): array
    {
        $topo_id    = (int) $topo_id;
        $cliente_id = (int) $cliente_id;

        if ($topo_id <= 0 || $cliente_id <= 0)
        {
            return [];
        }

        if ($topos_do_cliente === null)
        {
            $topos_do_cliente = self::toposDoCliente($cliente_id);
        }

        if (!in_array($topo_id, array_map('intval', $topos_do_cliente), true))
        {
            return [];
        }

        return self::familiasVisiveis($cliente_id, [$topo_id])[$topo_id] ?? [];
    }

    /**
     * A familia visivel de varios topos do cliente numa consulta so - e o
     * que a lista "Meus processos" usa, em vez de uma consulta por linha.
     *
     * So recebe topos que ja sao do cliente: os de toposDoCliente(). Sem
     * $topos, usa todos os topos dele.
     *
     * @param int[]|null $topos
     * @return array<int, int[]> topo => ids da familia (o topo incluido)
     */
    public static function familiasVisiveis($cliente_id, ?array $topos = null): array
    {
        $cliente_id = (int) $cliente_id;

        if ($cliente_id <= 0)
        {
            return [];
        }

        $topos = self::idsValidos($topos ?? self::toposDoCliente($cliente_id));

        if (empty($topos))
        {
            return [];
        }

        $sql = "
            WITH RECURSIVE
            " . self::CTE_VINCULOS . ",
            " . self::CTE_MEUS . ",
            descendente(topo, id) AS (
                SELECT t, t
                FROM UNNEST(CAST(:topos AS INTEGER[])) AS t

                UNION

                SELECT d.topo, v.filho
                FROM descendente d
                JOIN v ON v.pai = d.id
            ),
            membro AS (
                SELECT DISTINCT id FROM descendente
            ),
            anc(id, a) AS (
                SELECT m.id, v.pai
                FROM membro m
                JOIN v ON v.filho = m.id

                UNION

                SELECT anc.id, v.pai
                FROM anc
                JOIN v ON v.filho = anc.a
            )
            SELECT d.topo, d.id
            FROM descendente d
            JOIN meus m ON m.id = d.id
            JOIN processo p ON p.id = d.id
            WHERE COALESCE(UPPER(TRIM(p.exibir_cliente)), 'N') = 'S'
              AND NOT EXISTS (
                  SELECT 1
                  FROM anc
                  JOIN processo pa ON pa.id = anc.a
                  WHERE anc.id = d.id
                    AND COALESCE(UPPER(TRIM(pa.exibir_cliente)), 'N') <> 'S'
              )
            ORDER BY d.topo, d.id
        ";

        self::semJit();

        $stmt = TTransaction::get()->prepare($sql);
        $stmt->execute([
            ':topos'      => self::arrayPg($topos),
            ':cliente_id' => $cliente_id,
        ]);

        $familias = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $linha)
        {
            $familias[(int) $linha['topo']][] = (int) $linha['id'];
        }

        return $familias;
    }

    // =================================================================
    // Etapas da familia
    // =================================================================

    /**
     * Trecho de SQL que restringe publicacao_etapa (alias pe) a trilha do
     * tipo de processo. Vazio para tipos sem trilha propria.
     */
    public static function filtroTrilhaSql($tipo_processo_id): string
    {
        $tipo_processo_id = (int) $tipo_processo_id;

        if ($tipo_processo_id === 1)
        {
            return " AND COALESCE(UPPER(TRIM(pe.judicial)), 'N') = 'S' ";
        }

        if ($tipo_processo_id === 2)
        {
            return " AND COALESCE(UPPER(TRIM(pe.extrajudicial)), 'N') = 'S' ";
        }

        return '';
    }

    /**
     * Etapas de abertura da trilha, na ordem do cadastro. Sempre aparecem na
     * faixa e ja contam como percorridas.
     *
     * @return int[]
     */
    public static function etapasFixas($tipo_processo_id): array
    {
        $ids = implode(',', self::ETAPAS_ABERTURA);

        $sql = "
            SELECT pe.id
            FROM publicacao_etapa pe
            WHERE pe.id IN ({$ids})
            " . self::filtroTrilhaSql($tipo_processo_id) . "
            ORDER BY pe.ordem_prioridade ASC, pe.id ASC
        ";

        return array_map('intval', TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Etapas que de fato apareceram nos processos informados: movimentacoes
     * com etapa verificada, fora das ocultas, dentro da trilha.
     *
     * @param int[] $processo_ids
     * @return object[] {id, ordem_prioridade}, da menor para a maior prioridade
     */
    public static function etapasAparecidas(array $processo_ids, $tipo_processo_id): array
    {
        $processo_ids = self::idsValidos($processo_ids);

        if (empty($processo_ids))
        {
            return [];
        }

        $ocultas = implode(',', self::ETAPAS_OCULTAS);

        $sql = "
            WITH " . self::sqlMovimentos() . "
            SELECT
                pe.id,
                pe.ordem_prioridade
            FROM mov

            INNER JOIN publicacao_etapa pe
                ON pe.id = mov.etapa_id

            WHERE pe.id NOT IN ({$ocultas})

            " . self::filtroTrilhaSql($tipo_processo_id) . "

            GROUP BY
                pe.id,
                pe.ordem_prioridade

            ORDER BY
                pe.ordem_prioridade ASC,
                pe.id ASC
        ";

        self::semJit();

        $stmt = TTransaction::get()->prepare($sql);
        $stmt->execute([':ids' => self::arrayPg($processo_ids)]);

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * CTE "mov": as movimentacoes verificadas dos processos em :ids, com a
     * etapa de cada uma. Lida primeiro, numa passada so (MATERIALIZED), e so
     * depois cruzada com as etapas.
     *
     * processo_publicacoes nao tem indice em processo_id. Com a trilha no
     * mesmo nivel, o planejador estimava 1 etapa e montava um nested loop que
     * varria a tabela inteira (137 mil linhas) uma vez por etapa: ~85 ms.
     * Separado, e uma varredura so: ~12 ms. Medido em 09/10/2026 em
     * homologacao.
     */
    private static function sqlMovimentos(): string
    {
        return "
            mov AS MATERIALIZED (
                SELECT
                    pp.processo_id,
                    COALESCE(
                        pp.publicacao_etapa_id,
                        pub.publicacao_etapa_id,
                        andam.publicacao_etapa_id
                    ) AS etapa_id
                FROM processo_publicacoes pp

                LEFT JOIN publicacao pub
                    ON pub.id = pp.publicacao_id

                LEFT JOIN andamento andam
                    ON andam.id = pp.andamento_id

                WHERE pp.processo_id = ANY(CAST(:ids AS INTEGER[]))

                AND (
                    (
                        pp.publicacao_id IS NOT NULL
                        AND pp.andamento_id IS NULL
                        AND COALESCE(UPPER(TRIM(pub.etapa_verificada)), 'N') = 'S'
                    )
                    OR
                    (
                        pp.andamento_id IS NOT NULL
                        AND pp.publicacao_id IS NULL
                        AND COALESCE(UPPER(TRIM(andam.etapa_verificada)), 'N') = 'S'
                    )
                )
            )
        ";
    }

    /**
     * A etapa atual da familia: a de maior ordem_prioridade entre as que
     * apareceram, sem contar as de abertura. Sem nenhuma, vale a ultima etapa
     * de abertura da trilha do topo - ou a primeira, se o topo for
     * pre-processo (ainda nao houve protocolo).
     *
     * E a mesma conta que a tela do processo sempre fez para um processo so;
     * agora a lista e o cabecalho usam esta funcao e mostram o mesmo valor.
     *
     * @param int[] $processo_ids familia visivel
     * @param object $topo precisa de tipo_processo_id e pre_processo
     * @return object|null {id, etapa_nome, ordem_prioridade}
     */
    public static function etapaAtual(array $processo_ids, $topo): ?object
    {
        if (empty($topo))
        {
            return null;
        }

        $tipo_processo_id = (int) ($topo->tipo_processo_id ?? 0);

        $etapa_id = self::escolherEtapaAtual(
            self::etapasAparecidas($processo_ids, $tipo_processo_id),
            self::etapasFixas($tipo_processo_id),
            $topo
        );

        if ($etapa_id === null)
        {
            return null;
        }

        return self::etapasPorId([$etapa_id])[$etapa_id] ?? null;
    }

    /**
     * etapaAtual() de varias familias de uma vez, para a lista "Meus
     * processos": uma leitura das movimentacoes de todas as familias, em vez
     * de uma por linha. A regra de escolha e a mesma (escolherEtapaAtual).
     *
     * @param array<int, int[]> $familias topo => ids, como em familiasVisiveis()
     * @return array<int, object|null> topo => {id, etapa_nome, ordem_prioridade}
     */
    public static function etapasAtuaisDasFamilias(array $familias): array
    {
        if (empty($familias))
        {
            return [];
        }

        $todos = self::idsValidos(array_merge(...array_values($familias)));
        $topos = self::idsValidos(array_keys($familias));

        $ocultas = implode(',', self::ETAPAS_OCULTAS);

        $sql = "
            WITH " . self::sqlMovimentos() . "
            SELECT DISTINCT
                mov.processo_id,
                pe.id,
                pe.ordem_prioridade,
                pe.judicial,
                pe.extrajudicial
            FROM mov

            INNER JOIN publicacao_etapa pe
                ON pe.id = mov.etapa_id

            WHERE pe.id NOT IN ({$ocultas})
        ";

        self::semJit();

        $stmt = TTransaction::get()->prepare($sql);
        $stmt->execute([':ids' => self::arrayPg($todos)]);

        $movimentos_por_processo = [];

        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $linha)
        {
            $movimentos_por_processo[(int) $linha->processo_id][] = $linha;
        }

        $stmt_topos = TTransaction::get()->prepare("
            SELECT id, tipo_processo_id, pre_processo
            FROM processo
            WHERE id = ANY(CAST(:ids AS INTEGER[]))
        ");

        $stmt_topos->execute([':ids' => self::arrayPg($topos)]);

        $dados_topos = [];

        foreach ($stmt_topos->fetchAll(PDO::FETCH_OBJ) as $linha)
        {
            $dados_topos[(int) $linha->id] = $linha;
        }

        $fixas_por_tipo = [];
        $escolhidas = [];

        foreach ($familias as $topo_id => $ids)
        {
            $topo = $dados_topos[(int) $topo_id] ?? null;

            if (!$topo)
            {
                $escolhidas[$topo_id] = null;
                continue;
            }

            $tipo = (int) $topo->tipo_processo_id;

            if (!isset($fixas_por_tipo[$tipo]))
            {
                $fixas_por_tipo[$tipo] = self::etapasFixas($tipo);
            }

            // Mesma filtragem de etapasAparecidas(): trilha do topo, sem repetir etapa.
            $aparecidas = [];

            foreach ($ids as $id)
            {
                foreach ($movimentos_por_processo[(int) $id] ?? [] as $etapa)
                {
                    if (self::etapaNaTrilha($etapa, $tipo))
                    {
                        $aparecidas[(int) $etapa->id] = $etapa;
                    }
                }
            }

            $escolhidas[$topo_id] = self::escolherEtapaAtual(array_values($aparecidas), $fixas_por_tipo[$tipo], $topo);
        }

        $etapas = self::etapasPorId(array_values($escolhidas));

        $resultado = [];

        foreach ($escolhidas as $topo_id => $etapa_id)
        {
            $resultado[$topo_id] = $etapa_id !== null ? ($etapas[$etapa_id] ?? null) : null;
        }

        return $resultado;
    }

    /**
     * A regra da etapa atual, num lugar so: a de maior ordem_prioridade entre
     * as aparecidas, fora as de abertura (desempate pela que vem primeiro na
     * lista); sem nenhuma, a ultima de abertura - ou a primeira, num
     * pre-processo.
     *
     * @param object[] $aparecidas {id, ordem_prioridade}
     * @param int[] $fixas etapasFixas() da trilha
     */
    private static function escolherEtapaAtual(array $aparecidas, array $fixas, $topo): ?int
    {
        usort($aparecidas, function ($a, $b) {
            return [(int) $a->ordem_prioridade, (int) $a->id] <=> [(int) $b->ordem_prioridade, (int) $b->id];
        });

        $etapa_id = null;
        $maior_ordem = null;

        foreach ($aparecidas as $etapa)
        {
            if (in_array((int) $etapa->id, $fixas, true))
            {
                continue;
            }

            if ($maior_ordem === null || (int) $etapa->ordem_prioridade > $maior_ordem)
            {
                $etapa_id = (int) $etapa->id;
                $maior_ordem = (int) $etapa->ordem_prioridade;
            }
        }

        if ($etapa_id === null && !empty($fixas))
        {
            $etapa_id = PreProcessoService::ehPreProcesso($topo)
                ? reset($fixas)
                : end($fixas);
        }

        return $etapa_id;
    }

    /**
     * Mesmo criterio de filtroTrilhaSql(), aplicado a uma linha ja lida.
     */
    private static function etapaNaTrilha($etapa, $tipo_processo_id): bool
    {
        if ((int) $tipo_processo_id === 1)
        {
            return self::flagExibir($etapa->judicial);
        }

        if ((int) $tipo_processo_id === 2)
        {
            return self::flagExibir($etapa->extrajudicial);
        }

        return true;
    }

    /**
     * @param array<int|null> $ids
     * @return array<int, object> id => {id, etapa_nome, ordem_prioridade}
     */
    private static function etapasPorId(array $ids): array
    {
        $ids = self::idsValidos(array_filter($ids, function ($id) {
            return $id !== null;
        }));

        if (empty($ids))
        {
            return [];
        }

        $stmt = TTransaction::get()->prepare("
            SELECT id, etapa_nome, ordem_prioridade
            FROM publicacao_etapa
            WHERE id = ANY(CAST(:ids AS INTEGER[]))
        ");

        $stmt->execute([':ids' => self::arrayPg($ids)]);

        $etapas = [];

        foreach ($stmt->fetchAll(PDO::FETCH_OBJ) as $etapa)
        {
            $etapas[(int) $etapa->id] = $etapa;
        }

        return $etapas;
    }

    // =================================================================
    // Apoio
    // =================================================================

    /**
     * @return array[] ['id', 'nivel', 'exibir_cliente'], do mais proximo ao mais distante
     */
    private static function ancestraisComNivel($processo_id): array
    {
        $processo_id = (int) $processo_id;

        if ($processo_id <= 0)
        {
            return [];
        }

        /*
            O nivel entra na linha, entao UNION sozinho nao corta um ciclo
            (o mesmo ancestral volta com nivel maior). O teto de 50 corta; a
            arvore mais funda da base tem 4 niveis.
        */
        $sql = "
            WITH RECURSIVE
            " . self::CTE_VINCULOS . ",
            anc(id, nivel) AS (
                SELECT v.pai, 1
                FROM v
                WHERE v.filho = :processo_id

                UNION

                SELECT v.pai, anc.nivel + 1
                FROM anc
                JOIN v ON v.filho = anc.id
                WHERE anc.nivel < 50
            )
            SELECT anc.id, MIN(anc.nivel) AS nivel, p.exibir_cliente
            FROM anc
            JOIN processo p ON p.id = anc.id
            WHERE anc.id <> :processo_id_proprio
            GROUP BY anc.id, p.exibir_cliente
            ORDER BY MIN(anc.nivel), anc.id
        ";

        self::semJit();

        $stmt = TTransaction::get()->prepare($sql);
        $stmt->execute([
            ':processo_id'         => $processo_id,
            ':processo_id_proprio' => $processo_id,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Desliga o JIT do PostgreSQL ate o fim da transacao corrente.
     *
     * O planejador nao sabe estimar quantas linhas uma recursao devolve e
     * chuta milhoes; com esse custo estimado ele liga o JIT e gasta ~800 ms
     * compilando uma consulta que roda em ~10 ms. Medido em 09/10/2026 em
     * homologacao: familiaVisivel caiu de ~770 ms para ~25 ms.
     */
    private static function semJit(): void
    {
        TTransaction::get()->exec('SET LOCAL jit = off');
    }

    /**
     * Entre os ocultos de $linhas (do mais proximo ao mais distante), o
     * primeiro cujo botao esta livre - nenhum oculto acima dele. Num ciclo
     * todos ficam travados entre si; ai vale o mais distante.
     *
     * @param array[] $linhas ['id', 'nivel', 'exibir_cliente']
     */
    private static function ocultoLiberavel(array $linhas): ?int
    {
        $ocultos = array_values(array_filter($linhas, function ($linha) {
            return !self::flagExibir($linha['exibir_cliente']);
        }));

        if (empty($ocultos))
        {
            return null;
        }

        foreach ($ocultos as $linha)
        {
            if (self::primeiroAncestralOculto($linha['id']) === null)
            {
                return (int) $linha['id'];
            }
        }

        return (int) end($ocultos)['id'];
    }

    private static function flagExibir($valor): bool
    {
        return strtoupper(trim((string) $valor)) === 'S';
    }

    /**
     * @return int[] ids positivos, sem repeticao
     */
    private static function idsValidos(array $ids): array
    {
        $ids = array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    /**
     * Literal de array do PostgreSQL ({1,2,3}) para passar como parametro.
     * So recebe inteiros ja validados.
     */
    private static function arrayPg(array $ids): string
    {
        return '{' . implode(',', array_map('intval', $ids)) . '}';
    }
}
