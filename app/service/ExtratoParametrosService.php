<?php
/**
 * Parametros e filtros do Extrato (janela unica ExtratoParametrosForm), usados pela listagem e pelo relatorio.
 * $p: conta_caixa_ids[], situacao ('S' compensados, 'N' nao compensados, '' todos), periodo_tipo ('compensacao',
 * 'lancamento', 'previsao'), data_de, data_ate (Y-m-d), tipo_extrato_ids[], historico, entrada_valor, saida_valor,
 * pessoa_id, processo_id (inclui os processos filhos, em qualquer nivel).
 */
class ExtratoParametrosService
{
    const SESSAO = 'ExtratoList_parametros';
    const COLUNA_DATA = ['compensacao' => 'data_compensacao', 'lancamento' => 'data_lancamento', 'previsao' => 'data_previsao_compensacao'];
    const ROTULO_DATA = ['compensacao' => 'Compensação', 'lancamento' => 'Lançamento', 'previsao' => 'Previsão de compensação'];

    public static function atuais(): array
    {
        $p = TSession::getValue(self::SESSAO);
        return is_array($p) ? $p : [];
    }

    /**
     * Parametros ativos para a faixa "Filtros aplicados" da listagem (o "x" de cada um tira so ele: remover()).
     * Campos com prefixo __param_ para o onSearch da listagem saber que e parametro e nao filtro de coluna.
     */
    public static function itens(array $p): array
    {
        if (!$p) { return []; }
        $propria = !TTransaction::get();
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $c = TTransaction::get();
            $nomes = function (string $tabela, array $ids, string $col = 'nome') use ($c) {
                if (!$ids) { return ''; }
                return implode(', ', $c->query("SELECT {$col} FROM {$tabela} WHERE id IN (" . implode(',', array_map('intval', $ids)) . ") ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN));
            };
            $dt = function ($v) { return $v ? date('d/m/Y', strtotime($v)) : '...'; };
            $i = [];
            $item = function (string $rotulo, string $valor, string $chave) use (&$i) { $i[] = ['rotulo' => $rotulo, 'valor' => $valor, 'campos' => ['__param_' . $chave]]; };
            if (!empty($p['conta_caixa_ids'])) { $item('Conta-caixa', $nomes('conta_caixa', $p['conta_caixa_ids']), 'conta_caixa_ids'); }
            if (!empty($p['situacao'])) { $item('Situação', ['S' => 'Compensados', 'N' => 'Não compensados'][$p['situacao']] ?? $p['situacao'], 'situacao'); }
            if (!empty($p['data_de']) || !empty($p['data_ate'])) {
                $item('Período (' . (self::ROTULO_DATA[$p['periodo_tipo'] ?? 'compensacao'] ?? 'Compensação') . ')', $dt($p['data_de'] ?? null) . ' até ' . $dt($p['data_ate'] ?? null), 'periodo');
            }
            if (!empty($p['tipo_extrato_ids'])) { $item('Tipo de extrato', $nomes('tipo_extrato', $p['tipo_extrato_ids']), 'tipo_extrato_ids'); }
            if (($p['historico'] ?? '') !== '') { $item('Histórico', $p['historico'], 'historico'); }
            if (isset($p['entrada_valor']) && $p['entrada_valor'] !== null) { $item('Entrada', 'R$ ' . number_format($p['entrada_valor'], 2, ',', '.'), 'entrada_valor'); }
            if (isset($p['saida_valor']) && $p['saida_valor'] !== null) { $item('Saída', 'R$ ' . number_format($p['saida_valor'], 2, ',', '.'), 'saida_valor'); }
            if (!empty($p['pessoa_id'])) { $item('Pessoa', $nomes('pessoa', [$p['pessoa_id']]), 'pessoa_id'); }
            if (!empty($p['processo_id'])) {
                $n = count(self::familia((int) $p['processo_id'])) - 1;
                $item('Processo', $nomes('processo', [$p['processo_id']], "COALESCE(numero_cnj_numero, numero_outro, id::text)") . ($n > 0 ? " (e {$n} filho(s))" : ''), 'processo_id');
            }
            return $i;
        } finally {
            if ($propria) { TTransaction::close(); }
        }
    }

    /** Tira um parametro so (o "x" da faixa); "periodo" tira as duas datas. */
    public static function remover(string $chave): void
    {
        $p = self::atuais();
        if (!$p) { return; }
        if ($chave === 'periodo') {
            $p['data_de'] = null;
            $p['data_ate'] = null;
        } elseif (in_array($chave, ['conta_caixa_ids', 'tipo_extrato_ids'], true)) {
            $p[$chave] = [];
        } elseif (in_array($chave, ['situacao', 'historico'], true)) {
            $p[$chave] = '';
        } elseif (array_key_exists($chave, $p)) {
            $p[$chave] = null;
        }
        TSession::setValue(self::SESSAO, $p);
    }

    /** Normaliza o que veio do formulario (datas dd/mm/aaaa ou aaaa-mm-dd, listas, numeros). */
    public static function normalizar($d): array
    {
        $d = (array) $d;
        $lista = function ($v) {
            $v = is_array($v) ? $v : preg_split('/[^0-9]+/', (string) $v, -1, PREG_SPLIT_NO_EMPTY);
            return array_values(array_unique(array_filter(array_map('intval', $v), function ($x) { return $x > 0; })));
        };
        $data = function ($v) {
            $v = trim((string) $v);
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) { return "{$m[3]}-{$m[2]}-{$m[1]}"; }
            return preg_match('/^\d{4}-\d{2}-\d{2}/', $v) ? substr($v, 0, 10) : null;
        };
        $valor = function ($v) {
            $v = trim(str_replace(['R$', ' '], '', (string) $v));
            if ($v === '') { return null; }
            if (strpos($v, ',') !== false) { $v = str_replace(['.', ','], ['', '.'], $v); }
            return is_numeric($v) ? round((float) $v, 2) : null;
        };
        $p = [
            'conta_caixa_ids' => $lista($d['conta_caixa_ids'] ?? ($d['conta_caixa_id'] ?? [])),
            'situacao' => in_array($d['situacao'] ?? '', ['S', 'N'], true) ? $d['situacao'] : '',
            'periodo_tipo' => isset(self::COLUNA_DATA[$d['periodo_tipo'] ?? '']) ? $d['periodo_tipo'] : 'compensacao',
            'data_de' => $data($d['data_de'] ?? ''),
            'data_ate' => $data($d['data_ate'] ?? ''),
            'tipo_extrato_ids' => $lista($d['tipo_extrato_ids'] ?? []),
            'historico' => trim((string) ($d['historico'] ?? '')),
            'entrada_valor' => $valor($d['entrada_valor'] ?? ''),
            'saida_valor' => $valor($d['saida_valor'] ?? ''),
            'pessoa_id' => ((int) ($d['pessoa_id'] ?? 0)) ?: null,
            'processo_id' => ((int) ($d['processo_id'] ?? 0)) ?: null,
        ];
        if ($p['data_de'] && $p['data_ate'] && $p['data_de'] > $p['data_ate']) { throw new Exception('A data inicial não pode ser maior que a final.'); }
        return $p;
    }

    /** Quantos parametros estao ativos (badge do botao). */
    public static function quantos(array $p): int
    {
        $n = 0;
        foreach (['conta_caixa_ids', 'tipo_extrato_ids'] as $k) { if (!empty($p[$k])) { $n++; } }
        foreach (['situacao', 'historico', 'pessoa_id', 'processo_id'] as $k) { if (!empty($p[$k])) { $n++; } }
        foreach (['entrada_valor', 'saida_valor'] as $k) { if (isset($p[$k]) && $p[$k] !== null) { $n++; } }
        if (!empty($p['data_de']) || !empty($p['data_ate'])) { $n++; }
        return $n;
    }

    /** Filtros da listagem (TFilter sobre a tabela extrato). Usa a transacao aberta. */
    public static function filtros(array $p): array
    {
        $f = [];
        if (!empty($p['conta_caixa_ids'])) { $f[] = new TFilter('conta_caixa_id', 'in', array_map('intval', $p['conta_caixa_ids'])); }
        if (($p['situacao'] ?? '') === 'S') { $f[] = new TFilter('compensado', '=', 'S'); }
        if (($p['situacao'] ?? '') === 'N') { $f[] = new TFilter("COALESCE(compensado, 'N')", '<>', 'S'); }
        $col = self::COLUNA_DATA[$p['periodo_tipo'] ?? 'compensacao'] ?? 'data_compensacao';
        if (!empty($p['data_de'])) { $f[] = new TFilter($col, '>=', $p['data_de']); }
        if (!empty($p['data_ate'])) { $f[] = new TFilter($col, '<=', $p['data_ate']); }
        if (!empty($p['tipo_extrato_ids'])) { $f[] = new TFilter('tipo_extrato_id', 'in', array_map('intval', $p['tipo_extrato_ids'])); }
        if (($p['historico'] ?? '') !== '') { $f[] = new TFilter('historico', 'ilike', '%' . $p['historico'] . '%'); }
        if (isset($p['entrada_valor']) && $p['entrada_valor'] !== null) { $f[] = new TFilter('entrada_valor', '=', $p['entrada_valor']); }
        if (isset($p['saida_valor']) && $p['saida_valor'] !== null) { $f[] = new TFilter('saida_valor', '=', $p['saida_valor']); }
        if (!empty($p['pessoa_id'])) {
            $f[] = new TFilter('lancamento_id', 'in', '(SELECT l.id FROM lancamento l JOIN conta c ON c.id = l.conta_id WHERE c.pessoa_id = ' . (int) $p['pessoa_id'] . ')');
        }
        if (!empty($p['processo_id'])) {
            $f[] = new TFilter('lancamento_id', 'in', '(' . self::sqlLancamentosDoProcesso(self::familia((int) $p['processo_id'])) . ')');
        }
        return $f;
    }

    /** O processo e todos os filhos dele (processo_vinculo: principal -> incidente), em qualquer nivel. */
    public static function familia(int $processoId): array
    {
        $st = TTransaction::get()->prepare('WITH RECURSIVE f(id) AS (SELECT ?::int UNION SELECT v.processo_incidente_id FROM processo_vinculo v JOIN f ON v.processo_principal_id = f.id)
                                            SELECT id FROM f');
        $st->execute([$processoId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    /** Parcelas das contas do processo (ou, se a conta nao tem processo, do contrato vinculado a ele). */
    public static function sqlLancamentosDoProcesso(array $processos): string
    {
        $ids = implode(',', array_map('intval', $processos ?: [0]));
        return "SELECT l.id FROM lancamento l JOIN conta c ON c.id = l.conta_id
                 WHERE c.processo_id IN ({$ids})
                    OR (c.processo_id IS NULL AND c.contrato_id IN (SELECT contrato_id FROM contrato_processo WHERE processo_id IN ({$ids})))";
    }

    /** Texto com os parametros (relatorio e listagem). Usa a transacao aberta. */
    public static function descrever(array $p): string
    {
        $c = TTransaction::get();
        $nomes = function (string $tabela, array $ids, string $col = 'nome') use ($c) {
            if (!$ids) { return ''; }
            return implode(', ', $c->query("SELECT {$col} FROM {$tabela} WHERE id IN (" . implode(',', array_map('intval', $ids)) . ") ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN));
        };
        $t = [];
        $t[] = 'Conta-caixa: ' . (!empty($p['conta_caixa_ids']) ? $nomes('conta_caixa', $p['conta_caixa_ids']) : 'todas');
        $t[] = ['S' => 'Compensados', 'N' => 'Não compensados'][$p['situacao'] ?? ''] ?? 'Compensados e não compensados';
        if (!empty($p['data_de']) || !empty($p['data_ate'])) {
            $t[] = (self::ROTULO_DATA[$p['periodo_tipo'] ?? 'compensacao'] ?? 'Compensação') . ': ' . (!empty($p['data_de']) ? date('d/m/Y', strtotime($p['data_de'])) : '...')
                . ' a ' . (!empty($p['data_ate']) ? date('d/m/Y', strtotime($p['data_ate'])) : '...');
        }
        if (!empty($p['tipo_extrato_ids'])) { $t[] = 'Tipo: ' . $nomes('tipo_extrato', $p['tipo_extrato_ids']); }
        if (($p['historico'] ?? '') !== '') { $t[] = 'Histórico contém "' . $p['historico'] . '"'; }
        if (isset($p['entrada_valor']) && $p['entrada_valor'] !== null) { $t[] = 'Entrada R$ ' . number_format($p['entrada_valor'], 2, ',', '.'); }
        if (isset($p['saida_valor']) && $p['saida_valor'] !== null) { $t[] = 'Saída R$ ' . number_format($p['saida_valor'], 2, ',', '.'); }
        if (!empty($p['pessoa_id'])) { $t[] = 'Pessoa: ' . $nomes('pessoa', [$p['pessoa_id']]); }
        if (!empty($p['processo_id'])) {
            $n = count(self::familia((int) $p['processo_id'])) - 1;
            $t[] = 'Processo: ' . $nomes('processo', [$p['processo_id']], "COALESCE(numero_cnj_numero, numero_outro, id::text)") . ($n > 0 ? " (e {$n} processo(s) filho(s))" : '');
        }
        return implode(' · ', $t);
    }
}
