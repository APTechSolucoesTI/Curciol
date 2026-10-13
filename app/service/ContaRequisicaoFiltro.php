<?php
/**
 * Filtros por requisicao de pagamento para as listagens de Contas a receber e a pagar.
 * Campos do formulario: requisicao_pagamento_id, cliente_requisicao_id, entidade_devedora_id, processo_requisicao.
 */
class ContaRequisicaoFiltro
{
    /**
     * @param object $data dados do formulario de filtros
     * @return TFilter[]
     */
    public static function filtros($data): array
    {
        $filters = [];

        $requisicao = (int) ($data->requisicao_pagamento_id ?? 0);
        if ($requisicao > 0) {
            $filters[] = new TFilter('requisicao_pagamento_id', '=', $requisicao);
        }

        $cliente = (int) ($data->cliente_requisicao_id ?? 0);
        if ($cliente > 0) {
            $filters[] = new TFilter('requisicao_pagamento_id', 'in', "(SELECT requisicao_pagamento_id FROM requisicao_pagamento_cliente WHERE pessoa_id = {$cliente})");
        }

        $devedor = (int) ($data->entidade_devedora_id ?? 0);
        if ($devedor > 0) {
            $filters[] = new TFilter('requisicao_pagamento_id', 'in', "(SELECT requisicao_pagamento_id FROM requisicao_pagamento_cliente WHERE entidade_devedora_id = {$devedor})");
        }

        $processo = trim((string) ($data->processo_requisicao ?? ''));
        if ($processo !== '') {
            $filters[] = new TFilter('id', 'in', self::subqueryProcesso($processo));
        }

        return $filters;
    }

    /**
     * Contas da requisicao cujo processo casa com o texto OU contas cujo proprio processo_id casa
     * (o pagamento pode estar num incidente, e a conta guarda o processo do pagamento).
     * Com digitos no texto, compara so os digitos (pontuacao ignorada); sem digitos, compara o texto cru.
     */
    public static function subqueryProcesso(string $texto): string
    {
        // transacao propria (aninhada se ja houver outra) so para usar o quote da conexao
        TTransaction::open('escritorio');
        try {
            $conn = TTransaction::get();
            $digitos = preg_replace('/\D/', '', $texto);
            if ($digitos !== '') {
                $lit = $conn->quote('%' . $digitos . '%');
                $casa = "(regexp_replace(COALESCE(p.numero_cnj_numero, ''), '[^0-9]', '', 'g') LIKE {$lit}"
                      . " OR regexp_replace(COALESCE(p.numero_outro, ''), '[^0-9]', '', 'g') LIKE {$lit})";
            } else {
                $lit = $conn->quote('%' . $texto . '%');
                $casa = "(p.numero_cnj_numero ILIKE {$lit} OR p.numero_outro ILIKE {$lit})";
            }
        } finally {
            TTransaction::close();
        }

        return "(SELECT c.id FROM conta c WHERE c.requisicao_pagamento_id IN"
             . " (SELECT r.id FROM requisicao_pagamento r JOIN processo p ON p.id = r.processo_id WHERE {$casa})"
             . " OR c.processo_id IN (SELECT p.id FROM processo p WHERE {$casa}))";
    }
}
