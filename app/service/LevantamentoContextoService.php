<?php
// app/service/LevantamentoContextoService.php
/**
 * Contexto de um pagamento (Etapa 3 da requisicao de pagamento) para gerar o financeiro do levantamento:
 * cliente, devedor, valores do deposito, contratos elegiveis com % de honorarios e repasse,
 * e a sugestao de contrato / % / parceria.
 *
 * Usa a transacao aberta pelo chamador; se nao houver, abre e fecha a propria.
 */
class LevantamentoContextoService
{
    const OPCAO_PAGAMENTO_PERCENTUAL = 3; // contrato_pagamento_parcela.contrato_opcao_pagamento_id

    public static function carregar(int $etapa3Id): object
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $ctx = self::montar(TTransaction::get(), $etapa3Id);
            if ($propria) { TTransaction::close(); }
            return $ctx;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /**
     * Id da parceria FIXA ativa cujos participantes (pessoa_id => percentual) batem exatamente
     * (tolerancia 0,01) com o repasse do contrato. Null se nenhuma bater.
     *
     * @param array $repasse pessoa_id => percentual
     */
    public static function parceriaPorRepasse(array $repasse): ?int
    {
        if (empty($repasse)) { return null; }

        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $r = self::parceriaPorRepasseNaConexao(TTransaction::get(), $repasse);
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    // ---------------------------------------------------------------

    private static function montar(PDO $c, int $etapa3Id): object
    {
        $st = $c->prepare("
            SELECT e3.id, e3.requisicao_pagamento_cliente_id, e3.processo_filho_id, e3.numero_ciclo, e3.data_deposito,
                   e3.valor_bruto_depositado, e3.valor_mle, e3.data_deferimento_mle, e3.saldo_bruto,
                   rpc.pessoa_id AS cliente_id, rpc.entidade_devedora_id AS devedor_id,
                   rp.id AS requisicao_id, rp.processo_id AS processo_requisicao_id,
                   tr.nome AS tipo_requisicao,
                   cli.nome AS cliente_nome, cli.cpf_cnpj AS cliente_documento, dev.nome AS devedor_nome,
                   COALESCE(pf.numero_cnj_numero, pf.numero_outro, pf.id::text) AS processo_numero
            FROM requisicao_pagamento_etapa3 e3
            JOIN requisicao_pagamento_cliente rpc ON rpc.id = e3.requisicao_pagamento_cliente_id
            JOIN requisicao_pagamento rp ON rp.id = rpc.requisicao_pagamento_id
            LEFT JOIN tipos_requisicao_pagamento tr ON tr.id = rp.tipos_requisicao_pagamento_id
            LEFT JOIN pessoa cli ON cli.id = rpc.pessoa_id
            LEFT JOIN pessoa dev ON dev.id = rpc.entidade_devedora_id
            LEFT JOIN processo pf ON pf.id = e3.processo_filho_id
            WHERE e3.id = ?
        ");
        $st->execute([$etapa3Id]);
        $e = $st->fetch(PDO::FETCH_OBJ);
        if (!$e) { throw new Exception('Pagamento não encontrado.'); }

        $bruto = (float) $e->valor_bruto_depositado;
        $mle   = (float) $e->valor_mle;
        if (empty($e->data_deposito) || $bruto <= 0 || $mle <= 0) { throw new Exception('Pagamento sem depósito lançado.'); }

        // numero do pagamento dentro da requisicao-cliente (1o, 2o...)
        $st = $c->prepare('
            SELECT COUNT(*) FROM requisicao_pagamento_etapa3
            WHERE requisicao_pagamento_cliente_id = ?
              AND (COALESCE(numero_ciclo, 1), id) <= (?, ?)');
        $st->execute([(int) $e->requisicao_pagamento_cliente_id, (int) ($e->numero_ciclo ?? 1), (int) $e->id]);
        $numeroPagamento = (int) $st->fetchColumn();

        // raiz da familia de processos e sua data de distribuicao
        $processoId = (int) $e->processo_filho_id;
        $ancestrais = ProcessoFamiliaService::ancestrais($processoId);
        $raizId = $ancestrais ? (int) end($ancestrais) : $processoId;
        $st = $c->prepare('SELECT data_distribuicao_protocolo, COALESCE(numero_cnj_numero, numero_outro, id::text) AS numero FROM processo WHERE id = ?');
        $st->execute([$raizId]);
        $raiz = $st->fetch(PDO::FETCH_OBJ);
        $dist = $raiz ? $raiz->data_distribuicao_protocolo : null;

        $processoRequisicao = (int) $e->processo_requisicao_id;
        $ancestraisRequisicao = $processoRequisicao ? ProcessoFamiliaService::ancestrais($processoRequisicao) : [];
        $contratos = self::contratosElegiveis($c, (int) $e->cliente_id,
            array_unique(array_merge([$processoId, $processoRequisicao], $ancestrais, $ancestraisRequisicao)));

        $sugerido = (count($contratos) === 1) ? $contratos[0] : null;

        $st = $c->prepare('SELECT COUNT(*) FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ?');
        $st->execute([$etapa3Id]);

        $escritorioId = null;
        if ($sugerido) {
            $st2 = $c->prepare('SELECT escritorio_id FROM contrato WHERE id = ?');
            $st2->execute([$sugerido['id']]);
            $escritorioId = $st2->fetchColumn() ?: null;
        }

        return (object) [
            'etapa3_id'              => (int) $e->id,
            'numero_pagamento'       => $numeroPagamento,
            'requisicao_id'          => (int) $e->requisicao_id,
            'tipo_requisicao'        => $e->tipo_requisicao,
            'rpc_id'                 => (int) $e->requisicao_pagamento_cliente_id,
            'cliente_id'             => (int) $e->cliente_id,
            'cliente_nome'           => $e->cliente_nome,
            'cliente_documento'      => $e->cliente_documento,
            'devedor_id'             => (int) $e->devedor_id,
            'devedor_nome'           => $e->devedor_nome,
            'processo_id'            => $processoId,
            'processo_numero'        => $e->processo_numero,
            'processo_raiz_id'       => $raizId,
            'processo_raiz_numero'   => $raiz ? $raiz->numero : null,
            'data_distribuicao'      => $dist ? substr($dist, 0, 10) : null,
            'data_deposito'          => $e->data_deposito,
            'bruto'                  => $bruto,
            'mle'                    => $mle,
            'descontos'              => $bruto - $mle,
            'saldo_bruto'            => ($e->saldo_bruto === null) ? null : (float) $e->saldo_bruto,
            'data_deferimento_mle'   => $e->data_deferimento_mle,
            'contratos'              => $contratos,
            'contrato_sugerido_id'   => $sugerido ? $sugerido['id'] : null,
            'pct_honorarios_sugerido'=> ($sugerido && $sugerido['pct_honorarios'] !== null) ? (float) $sugerido['pct_honorarios'] : null,
            'parceria_sugerida_id'   => $sugerido ? self::parceriaPorRepasseNaConexao($c, $sugerido['repasse']) : null,
            'escritorio_id'          => $escritorioId ? (int) $escritorioId : null,
            'ja_gerado'              => ((int) $st->fetchColumn() > 0),
        ];
    }

    /** @return array[] ['id','numero','pct_honorarios','repasse'] */
    private static function contratosElegiveis(PDO $c, int $clienteId, array $processos): array
    {
        $processos = array_values(array_unique(array_filter(array_map('intval', $processos))));
        if (!$processos) { return []; }

        $st = $c->prepare('
            SELECT DISTINCT c.id, c.numero
            FROM contrato c
            JOIN contrato_processo cp ON cp.contrato_id = c.id
            JOIN contrato_pessoa cpe ON cpe.contrato_id = c.id
            WHERE cpe.cliente_id = ? AND cp.processo_id IN (' . implode(',', $processos) . ')
            ORDER BY c.id');
        $st->execute([$clienteId]);
        $contratos = [];
        foreach ($st->fetchAll(PDO::FETCH_OBJ) as $l) {
            $contratos[(int) $l->id] = ['id' => (int) $l->id, 'numero' => $l->numero, 'pct_honorarios' => null, 'repasse' => []];
        }
        if (!$contratos) { return []; }
        $ids = implode(',', array_keys($contratos));

        // % de honorarios: so unidade '%' (multiplicador 'x' fica nulo: o usuario informa no formulario)
        $st = $c->query("
            SELECT p.contrato_id, p.complemento_indexador
            FROM contrato_pagamento_parcela p
            JOIN unidade_indexador u ON u.id = p.unidade_indexador_id
            WHERE p.contrato_id IN ({$ids}) AND p.contrato_opcao_pagamento_id = " . self::OPCAO_PAGAMENTO_PERCENTUAL . " AND u.simbolo = '%'
            ORDER BY p.id");
        $pcts = [];
        foreach ($st->fetchAll(PDO::FETCH_OBJ) as $l) {
            $v = self::interpretarPercentual($l->complemento_indexador);
            if ($v !== null) { $pcts[(int) $l->contrato_id][(string) $v] = $v; }
        }
        foreach ($pcts as $contratoId => $valores) {
            if (count($valores) === 1) { $contratos[$contratoId]['pct_honorarios'] = (string) reset($valores); }
        }

        $st = $c->query("SELECT contrato_id, pessoa_id, percentual FROM contrato_repasse WHERE contrato_id IN ({$ids}) ORDER BY id");
        foreach ($st->fetchAll(PDO::FETCH_OBJ) as $l) {
            $contratos[(int) $l->contrato_id]['repasse'][(int) $l->pessoa_id] = (float) $l->percentual;
        }

        return array_values($contratos);
    }

    /** '20', '17,5', '17.5%' => float; fora de 0..100 ou nao numerico => null. */
    private static function interpretarPercentual($texto): ?float
    {
        $t = str_replace(',', '.', trim(str_replace(['%', ' '], '', (string) $texto)));
        if ($t === '' || !is_numeric($t)) { return null; }
        $v = (float) $t;
        return ($v >= 0 && $v <= 100) ? $v : null;
    }

    private static function parceriaPorRepasseNaConexao(PDO $c, array $repasse): ?int
    {
        if (empty($repasse)) { return null; }

        $st = $c->prepare("
            SELECT p.id, pp.pessoa_id, pp.percentual
            FROM parceria_levantamento p
            JOIN parceria_levantamento_participante pp ON pp.parceria_levantamento_id = p.id
            WHERE p.tipo = ? AND p.ativo = 'S'
            ORDER BY p.id, pp.id");
        $st->execute([ParceriaLevantamento::TIPO_FIXA]);
        $parcerias = [];
        foreach ($st->fetchAll(PDO::FETCH_OBJ) as $l) {
            $parcerias[(int) $l->id][(int) $l->pessoa_id] = (float) $l->percentual;
        }

        foreach ($parcerias as $id => $mapa) {
            if (count($mapa) !== count($repasse)) { continue; }
            $bate = true;
            foreach ($mapa as $pessoaId => $pct) {
                if (!isset($repasse[$pessoaId]) || abs((float) $repasse[$pessoaId] - $pct) > 0.01) { $bate = false; break; }
            }
            if ($bate) { return $id; }
        }
        return null;
    }
}
