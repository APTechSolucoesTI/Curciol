<?php
/**
 * Colunas e filtros de cabecalho compartilhados por Contas a pagar e Contas a receber (22/10/2026):
 * - Origem: requisicao de pagamento e/ou contrato que gerou a conta (com link), ou "Manual".
 *   Os formularios manuais nao deixam escolher requisicao nem contrato; esses campos so sao preenchidos pelas
 *   geracoes automaticas (levantamento, financeiro do contrato, repasse ao colaborador e conta a pagar ao cliente).
 * - Vencimento: proxima parcela em aberto; conta quitada (ou sem proximo vencimento) mostra o da ultima parcela.
 */
class ContaListaColunas
{
    /** Data de vencimento exibida, no mesmo criterio de exibicao. */
    const VENCIMENTO_SQL = "CAST(CASE WHEN c.quitada IN ('S','T') OR c.proximo_vencimento_lancamento IS NULL
                                 THEN (SELECT max(l.dt_vencimento) FROM lancamento l WHERE l.conta_id = c.id)
                                 ELSE c.proximo_vencimento_lancamento END AS DATE)";

    /** Campos do cabecalho das colunas (abrem no funil). $lista e a listagem (onSearch estatico). */
    public static function camposCabecalho($lista): array
    {
        $buscar = new TAction([$lista, 'onSearch'], ['static' => '1']);

        $numero = new TEntry('numero_documento_col');
        $numero->exitOnEnter();
        $numero->setExitAction($buscar);
        $numero->setSize('100%');
        $numero->placeholder = 'Número do documento';

        $origem = new TCombo('origem_col');
        $origem->addItems(['manual' => 'Manual', 'requisicao' => 'Requisição', 'contrato' => 'Contrato']);
        $origem->setChangeAction($buscar);
        $origem->setSize('100%');
        $origemNumero = new TEntry('origem_numero_col');
        $origemNumero->exitOnEnter();
        $origemNumero->setExitAction($buscar);
        $origemNumero->setSize('100%');
        $origemNumero->placeholder = 'Nº da requisição ou do contrato';

        $totalDe = new TNumeric('total_col_ini', 2, ',', '.', true);
        $totalDe->setExitAction($buscar);
        $totalDe->setSize('100%');
        $totalDe->placeholder = 'Total de';
        $totalAte = new TNumeric('total_col_fim', 2, ',', '.', true);
        $totalAte->setSize('100%');
        $totalAte->placeholder = 'até';

        $vencimento = new BDateRange('vencimento_col', 'vencimento_col_fim');
        $vencimento->setMask('dd/mm/yyyy');
        $vencimento->setDatabaseMask('yyyy-mm-dd');
        $vencimento->setExitAction($buscar);
        $vencimento->setSize(220);

        return [
            'numero'       => [$numero],
            'origem'       => [$origem, $origemNumero],
            'total'        => [$totalDe, $totalAte],
            'vencimento'   => [$vencimento],
        ];
    }

    /** Celula do cabecalho com os campos de uma coluna (varios campos vao juntos no mesmo funil). */
    public static function celula(array $campos): TElement
    {
        $div = new TElement('div');
        $div->style = 'display: flex; flex-direction: column; gap: 6px; width: 100%;';
        foreach ($campos as $campo)
        {
            $div->add($campo);
        }
        return TElement::tag('td', $div);
    }

    public static function origem($conta): string
    {
        $partes = [];
        if (!empty($conta->requisicao_pagamento_id))
        {
            $id = (int) $conta->requisicao_pagamento_id;
            // abre na aba do cliente a que a conta se refere (a requisicao pode ter varios clientes)
            $rpc = !empty($conta->id) ? LevantamentoFinanceiroService::clienteDaRequisicao((int) $conta->id) : null;
            $aba = $rpc ? "&requisicao_pagamento_cliente_id={$rpc}" : '';
            $partes[] = "<a generator='adianti' href='index.php?class=RequisicaoPagamentoVisualizacao&method=onShow&key={$id}{$aba}&register_state=false'>Requisição nº {$id}</a>";
        }
        if (!empty($conta->contrato_id))
        {
            $id = (int) $conta->contrato_id;
            $propria = !TTransaction::get();
            if ($propria)
            {
                TTransaction::open('escritorio');
            }
            $contrato = Contrato::find($id);
            if ($propria)
            {
                TTransaction::close();
            }
            $numero = $contrato && trim((string) $contrato->numero) !== '' ? htmlspecialchars((string) $contrato->numero) : "#{$id}";
            $partes[] = "<a generator='adianti' href='index.php?class=ContratoFormView&method=onShow&key={$id}&register_state=false'>Contrato {$numero}</a>";
        }
        return $partes ? implode('<br>', $partes) : '<span style="color:#888">Manual</span>';
    }

    /** Vencimento exibido (aaaa-mm-dd) ou null. */
    public static function vencimento($conta): ?string
    {
        $quitada = in_array($conta->quitada, ['S', 'T'], true);
        if (!$quitada AND !empty($conta->proximo_vencimento_lancamento))
        {
            return substr((string) $conta->proximo_vencimento_lancamento, 0, 10);
        }
        if (empty($conta->id))
        {
            return null;
        }
        $propria = !TTransaction::get();
        if ($propria)
        {
            TTransaction::open('escritorio');
        }
        $st = TTransaction::get()->prepare('SELECT max(dt_vencimento) FROM lancamento WHERE conta_id = ?');
        $st->execute([(int) $conta->id]);
        $data = $st->fetchColumn();
        if ($propria)
        {
            TTransaction::close();
        }
        return $data ? substr((string) $data, 0, 10) : null;
    }

    public static function vencimentoFormatado($conta): string
    {
        $data = self::vencimento($conta);
        return $data ? date('d/m/Y', strtotime($data)) : '';
    }

    /** Filtros dos campos de cabecalho novos (numero, origem, total, vencimento). */
    public static function filtros($data): array
    {
        $filtros = [];
        $valor = function ($campo) use ($data) {
            $v = $data->$campo ?? null;
            return is_scalar($v) ? trim((string) $v) : '';
        };

        if ($valor('numero_documento_col') !== '')
        {
            $filtros[] = new TFilter('unaccent(CAST(numero_documento AS TEXT))', 'ilike', '%' . TratamentosService::removerAcentos($valor('numero_documento_col')) . '%');
        }

        $tipo = $valor('origem_col');
        if ($tipo == 'manual')
        {
            $filtros[] = new TFilter('requisicao_pagamento_id', 'IS', NULL);
            $filtros[] = new TFilter('contrato_id', 'IS', NULL);
        }
        elseif ($tipo == 'requisicao')
        {
            $filtros[] = new TFilter('requisicao_pagamento_id', 'IS NOT', NULL);
        }
        elseif ($tipo == 'contrato')
        {
            $filtros[] = new TFilter('contrato_id', 'IS NOT', NULL);
        }

        $numero = $valor('origem_numero_col');
        if ($numero !== '' AND $tipo != 'manual')
        {
            $condicoes = [];
            if ($tipo != 'contrato' AND ctype_digit($numero))
            {
                $condicoes[] = 'c.requisicao_pagamento_id = ' . (int) $numero;
            }
            if ($tipo != 'requisicao')
            {
                $condicoes[] = "c.contrato_id IN (SELECT ct.id FROM contrato ct WHERE CAST(ct.numero AS TEXT) ILIKE '%" . SqlSeguro::texto($numero) . "%')";
            }
            $filtros[] = new TFilter('id', 'in', '(SELECT c.id FROM conta c WHERE ' . ($condicoes ? implode(' OR ', $condicoes) : 'false') . ')');
        }

        $totalDe = $valor('total_col_ini');
        if ($totalDe !== '' AND is_numeric($totalDe))
        {
            $filtros[] = new TFilter('total_conta', '>=', (float) $totalDe);
        }
        $totalAte = $valor('total_col_fim');
        if ($totalAte !== '' AND is_numeric($totalAte))
        {
            $filtros[] = new TFilter('total_conta', '<=', (float) $totalAte);
        }

        $vencDe = SqlSeguro::data($valor('vencimento_col'));
        $vencAte = SqlSeguro::data($valor('vencimento_col_fim'));
        if ($vencDe OR $vencAte)
        {
            $sql = '(SELECT c.id FROM conta c WHERE ' . self::VENCIMENTO_SQL
                 . ($vencDe ? " >= '{$vencDe}'" : " IS NOT NULL")
                 . ($vencAte ? ' AND ' . self::VENCIMENTO_SQL . " <= '{$vencAte}'" : '') . ')';
            $filtros[] = new TFilter('id', 'in', $sql);
        }

        return $filtros;
    }
}
