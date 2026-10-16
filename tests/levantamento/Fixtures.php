<?php
// tests/levantamento/Fixtures.php
// Dados fictícios (requisição, pagamento, contrato, conta-caixa) para os testes do financeiro do levantamento.
// Tudo que é criado tem "TESTE LEVANTAMENTO" no nome. limpar() apaga SOMENTE pelos ids devolvidos por criar().
// Só roda no banco de homologação.
class Fixtures
{
    const MARCA = 'TESTE LEVANTAMENTO';

    /**
     * Opções: clientes (1), contratos (1), pct_contrato ('20'; array = uma condição % por item), unidade_indexador_id (1 = %, 2 = x),
     * repasse (pessoa_nome => %; padrão = participantes da "Parceria Juan"), bruto (13195.26), mle (11479.87),
     * deposito ('2026-01-10'), deferimento (null), distribuicao (null: data de distribuição do processo raiz).
     *
     * Cenário: processo raiz (a requisição) + processo incidente (processo_filho_id do pagamento, vinculado ao raiz);
     * os contratos do cliente estão no raiz. Com clientes > 1, cada cliente extra tem uma requisição-cliente
     * (sem Etapa 3) e um contrato próprio no mesmo processo.
     *
     * @return array etapa3_id, requisicao_id, rpc_id, cliente_id, devedor_id, processo_id (do pagamento = incidente),
     *               contrato_id (o primeiro), conta_caixa_id, mais: processo_raiz_id, contratos_ids, rpc_ids, cliente_ids,
     *               processos_ids, repasse (pessoa_id => %), pessoas_ids
     */
    public static function criar(array $opcoes = []): array
    {
        $o = $opcoes + [
            'clientes' => 1, 'contratos' => 1, 'pct_contrato' => '20', 'unidade_indexador_id' => 1, 'repasse' => null,
            'bruto' => 13195.26, 'mle' => 11479.87, 'deposito' => '2026-01-10', 'deferimento' => null, 'distribuicao' => null,
        ];
        $o['clientes']  = max(1, (int) $o['clientes']);
        $o['contratos'] = max(1, (int) $o['contratos']);

        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            self::exigirHomologacao($c);
            $m = self::MARCA;

            $escritorio = (int) $c->query('SELECT MIN(id) FROM escritorio')->fetchColumn();
            if ($escritorio <= 0) { throw new Exception('Fixtures: nenhum escritório cadastrado.'); }

            $f0 = ['escritorio_id' => $escritorio];
            $repasse = self::resolverRepasse($c, $o['repasse']);

            $f = $f0 + ['repasse' => $repasse, 'pessoas_ids' => [], 'cliente_ids' => [], 'rpc_ids' => [], 'contratos_ids' => [], 'processos_ids' => []];

            $f['conta_caixa_id'] = self::inserir($c, 'conta_caixa',
                ['nome' => $m, 'tipo_conta_caixa_id' => 1, 'dt_inicial' => '2020-01-01', 'saldo_inicial' => 0,
                 'saldo_instantaneo' => 0, 'saldo_nao_compensado' => 0, 'ativo' => 'S']);

            $f['devedor_id'] = self::inserir($c, 'pessoa', ['nome' => "{$m} DEVEDOR", 'tipo_pessoa_id' => 2]);
            $f['pessoas_ids'][] = $f['devedor_id'];

            for ($i = 1; $i <= $o['clientes']; $i++) {
                $id = self::inserir($c, 'pessoa', ['nome' => "{$m} CLIENTE {$i}", 'tipo_pessoa_id' => 1, 'cpf_cnpj' => '00000000000']);
                $f['cliente_ids'][] = $id;
                $f['pessoas_ids'][] = $id;
            }
            $f['cliente_id'] = $f['cliente_ids'][0];

            $f['processo_raiz_id'] = self::inserir($c, 'processo',
                ['tipo_processo_id' => 1, 'numero_outro' => "{$m} RAIZ", 'data_distribuicao_protocolo' => $o['distribuicao']]);
            $f['processo_id'] = self::inserir($c, 'processo', ['tipo_processo_id' => 1, 'numero_outro' => "{$m} INCIDENTE"]);
            $f['processos_ids'] = [$f['processo_raiz_id'], $f['processo_id']];
            self::inserir($c, 'processo_vinculo', ['processo_principal_id' => $f['processo_raiz_id'], 'processo_incidente_id' => $f['processo_id']]);

            // Requisição (tipo 2 = RPV), requisição-cliente (status 5 = Depositado) e Etapa 3 só do 1º cliente
            $f['requisicao_id'] = self::inserir($c, 'requisicao_pagamento', ['processo_id' => $f['processo_raiz_id'], 'tipos_requisicao_pagamento_id' => 2]);
            foreach ($f['cliente_ids'] as $clienteId) {
                $f['rpc_ids'][] = self::inserir($c, 'requisicao_pagamento_cliente',
                    ['pessoa_id' => $clienteId, 'entidade_devedora_id' => $f['devedor_id'], 'requisicao_pagamento_id' => $f['requisicao_id'],
                     'status_requisicao_pagamento_id' => 5, 'valor' => $o['bruto'], 'data_base' => '2020-01-01']);
            }
            $f['rpc_id'] = $f['rpc_ids'][0];
            $f['etapa3_id'] = self::inserir($c, 'requisicao_pagamento_etapa3',
                ['requisicao_pagamento_cliente_id' => $f['rpc_id'], 'processo_filho_id' => $f['processo_id'], 'numero_ciclo' => 1,
                 'data_deposito' => $o['deposito'], 'valor_bruto_depositado' => $o['bruto'], 'valor_mle' => $o['mle'],
                 'data_deferimento_mle' => $o['deferimento'], 'possui_saldo' => 'N']);

            // Contratos: $o['contratos'] para o 1º cliente e 1 para cada cliente extra, todos no processo raiz
            $donos = array_fill(0, $o['contratos'], $f['cliente_id']);
            foreach (array_slice($f['cliente_ids'], 1) as $extra) { $donos[] = $extra; }
            foreach ($donos as $n => $dono) {
                $contratoId = self::inserir($c, 'contrato',
                    ['escritorio_id' => $escritorio, 'numero' => "{$m} " . ($n + 1), 'objeto' => $m]);
                $f['contratos_ids'][] = $contratoId;
                self::inserir($c, 'contrato_pessoa', ['contrato_id' => $contratoId, 'cliente_id' => $dono]);
                self::inserir($c, 'contrato_processo', ['contrato_id' => $contratoId, 'processo_id' => $f['processo_raiz_id']]);
                foreach ((array) $o['pct_contrato'] as $complemento) {
                    self::inserir($c, 'contrato_pagamento_parcela',
                        ['contrato_id' => $contratoId, 'contrato_opcao_pagamento_id' => 3,
                         'unidade_indexador_id' => (int) $o['unidade_indexador_id'], 'complemento_indexador' => (string) $complemento]);
                }
                foreach ($repasse as $pessoaId => $pct) {
                    self::inserir($c, 'contrato_repasse', ['contrato_id' => $contratoId, 'pessoa_id' => $pessoaId, 'percentual' => $pct]);
                }
            }
            $f['contrato_id'] = $f['contratos_ids'][0];

            TTransaction::close();
            return $f;
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    /** Apaga o que criar() criou (pelos ids de $f) e tudo que o financeiro gerou nas contas da requisição. */
    public static function limpar(array $f): void
    {
        if (empty($f['requisicao_id']) && empty($f['conta_caixa_id'])) { return; }

        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            self::exigirHomologacao($c);
            $req = (int) ($f['requisicao_id'] ?? 0);
            $cc  = (int) ($f['conta_caixa_id'] ?? 0);
            $contratosIds = array_map('intval', $f['contratos_ids'] ?? []);
            $contas = $req ? self::coluna($c, 'SELECT id FROM conta WHERE requisicao_pagamento_id = ?', [$req]) : [];
            if ($contratosIds) { // contas geradas pelo contrato (e os repasses delas, que copiam o contrato)
                $contas = array_values(array_unique(array_merge($contas, self::coluna($c, 'SELECT id FROM conta WHERE contrato_id IN (' . implode(',', $contratosIds) . ')'))));
            }
            $levs   = !empty($f['etapa3_id']) ? self::coluna($c, 'SELECT id FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ?', [(int) $f['etapa3_id']]) : [];

            // repasses a colaboradores (e lotes de pagamento deles) antes das contas e da ficha
            if ($contas) {
                $lotes = self::coluna($c, 'SELECT DISTINCT x.lote_pagamento_id FROM extrato x JOIN lancamento l ON l.id = x.lancamento_id WHERE x.lote_pagamento_id IS NOT NULL AND l.conta_id IN (' . implode(',', $contas) . ')
                                           UNION SELECT DISTINCT i.lote_pagamento_id FROM lote_pagamento_item i JOIN lancamento l ON l.id = i.lancamento_id WHERE l.conta_id IN (' . implode(',', $contas) . ')'
                                           . ($cc ? ' UNION SELECT id FROM lote_pagamento WHERE conta_caixa_id = ' . $cc : ''));
                $c->exec('DELETE FROM repasse_colaborador WHERE conta_receber_id IN (' . implode(',', $contas) . ') OR conta_pagar_id IN (' . implode(',', $contas) . ')');
                if ($lotes) {
                    $c->exec('UPDATE lancamento SET extrato_id = NULL WHERE extrato_id IN (SELECT id FROM extrato WHERE lote_pagamento_id IN (' . implode(',', $lotes) . '))');
                    $c->exec('DELETE FROM extrato WHERE lote_pagamento_id IN (' . implode(',', $lotes) . ')');
                    self::apagar($c, 'lote_pagamento', 'id', $lotes);
                }
            }
            self::apagar($c, 'levantamento_financeiro_repasse', 'levantamento_financeiro_id', $levs);
            self::apagar($c, 'levantamento_financeiro', 'id', $levs);

            // lancamento <-> extrato se apontam um para o outro: soltar antes de apagar
            if ($contas) {
                $c->exec('UPDATE conta SET conta_origem_id = NULL WHERE id IN (' . implode(',', $contas) . ')');
                $c->exec('UPDATE lancamento SET extrato_id = NULL WHERE conta_id IN (' . implode(',', $contas) . ')');
            }
            if ($cc) { $c->exec("UPDATE extrato SET lancamento_id = NULL WHERE conta_caixa_id = {$cc}"); }
            if ($contas) {
                $lancs = self::coluna($c, 'SELECT id FROM lancamento WHERE conta_id IN (' . implode(',', $contas) . ')');
                self::apagar($c, 'lancamento_profissional', 'lancamento_id', $lancs);
                self::apagar($c, 'lancamento', 'id', $lancs);
                self::apagar($c, 'conta_profissional', 'conta_id', $contas);
                self::apagar($c, 'conta', 'id', $contas);
            }
            if ($cc) { self::apagar($c, 'extrato', 'conta_caixa_id', [$cc]); }

            self::apagar($c, 'requisicao_pagamento_etapa3', 'requisicao_pagamento_cliente_id', $f['rpc_ids'] ?? []);
            self::apagar($c, 'requisicao_pagamento_cliente', 'id', $f['rpc_ids'] ?? []);
            self::apagar($c, 'requisicao_pagamento', 'id', $req ? [$req] : []);

            $contratos = $f['contratos_ids'] ?? [];
            foreach (['contrato_repasse', 'contrato_pagamento_parcela', 'contrato_processo', 'contrato_pessoa'] as $t) {
                self::apagar($c, $t, 'contrato_id', $contratos);
            }
            self::apagar($c, 'contrato', 'id', $contratos);

            $procs = $f['processos_ids'] ?? [];
            self::apagar($c, 'processo_vinculo', 'processo_principal_id', $procs);
            self::apagar($c, 'processo_vinculo', 'processo_incidente_id', $procs);
            self::apagar($c, 'processo', 'id', $procs);
            self::apagar($c, 'pessoa', 'id', $f['pessoas_ids'] ?? []);
            self::apagar($c, 'conta_caixa', 'id', $cc ? [$cc] : []);

            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    /** Linhas com a marca que ainda existem (para o smoke de limpeza). tabela => quantidade, só o que for > 0. */
    public static function sobras(): array
    {
        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            self::exigirHomologacao($c);
            $q = ['pessoa' => 'nome', 'processo' => 'numero_outro', 'contrato' => 'numero', 'conta_caixa' => 'nome'];
            $r = [];
            foreach ($q as $tabela => $col) {
                $st = $c->prepare("SELECT COUNT(*) FROM {$tabela} WHERE {$col} LIKE ?");
                $st->execute([self::MARCA . '%']);
                $n = (int) $st->fetchColumn();
                if ($n > 0) { $r[$tabela] = $n; }
            }
            TTransaction::close();
            return $r;
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    // ---------------------------------------------------------------

    private static function exigirHomologacao(PDO $c): void
    {
        $banco = $c->query('SELECT current_database()')->fetchColumn();
        if ($banco !== 'curciol_homologacao') { throw new Exception("Fixtures só rodam em curciol_homologacao (conectado em {$banco})."); }
    }

    /** pessoa_id => percentual. $nomes null = participantes da "Parceria Juan" lidos do banco. */
    private static function resolverRepasse(PDO $c, ?array $nomes): array
    {
        $r = [];
        if ($nomes === null) {
            $st = $c->prepare("SELECT pp.pessoa_id, pp.percentual FROM parceria_levantamento p
                JOIN parceria_levantamento_participante pp ON pp.parceria_levantamento_id = p.id
                WHERE p.nome = 'Parceria Juan' ORDER BY pp.ordem, pp.id");
            $st->execute();
            foreach ($st->fetchAll(PDO::FETCH_NUM) as $l) { $r[(int) $l[0]] = (float) $l[1]; }
            if (!$r) { throw new Exception('Fixtures: "Parceria Juan" não encontrada ou sem participantes.'); }
            return $r;
        }

        foreach ($nomes as $nome => $pct) {
            // nomes gravados em NFD no banco: comparar normalizado
            $st = $c->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?::text, NFC) ORDER BY id');
            $st->execute([(string) $nome]);
            $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            if (count($ids) > 1) {
                $st = $c->prepare('SELECT DISTINCT pessoa_id FROM parceria_levantamento_participante WHERE pessoa_id IN (' . implode(',', $ids) . ')');
                $st->execute();
                $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
            }
            if (count($ids) !== 1) {
                throw new Exception("Fixtures: pessoa \"{$nome}\" " . (count($ids) ? 'ambígua' : 'não encontrada') . '.');
            }
            $r[$ids[0]] = (float) $pct;
        }
        return $r;
    }

    private static function inserir(PDO $c, string $tabela, array $dados): int
    {
        $cols = array_keys($dados);
        $sql = "INSERT INTO {$tabela} (" . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ') RETURNING id';
        $st = $c->prepare($sql);
        $st->execute(array_values($dados));
        return (int) $st->fetchColumn();
    }

    private static function coluna(PDO $c, string $sql, array $params = []): array
    {
        $st = $c->prepare($sql);
        $st->execute($params);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    private static function apagar(PDO $c, string $tabela, string $coluna, array $ids): void
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) { return; }
        $c->exec("DELETE FROM {$tabela} WHERE {$coluna} IN (" . implode(',', $ids) . ')');
    }
}
