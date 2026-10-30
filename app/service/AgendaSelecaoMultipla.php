<?php
/**
 * Varias agendas de uma vez (29/10/2026): ao ADICIONAR compromisso, bloqueio ou excecao, as agendas sao marcadas e o
 * registro e criado uma vez em cada agenda marcada. Ao EDITAR um registro que ja existe, continua a agenda unica dele
 * (combo agenda_id). 30/10/2026: o campo e o mesmo do filtro Agendas da agenda (FiltroMultiplo: busca, Todas no topo,
 * cor da agenda, marcadas empilhadas abaixo); as marcadas vao no post como agendas[].
 */
class AgendaSelecaoMultipla
{
    /** Editando um registro existente (onEdit com key, ou salvando um que tem id)? */
    public static function editando($param): bool
    {
        return !empty($param['key']) || !empty($param['id']);
    }

    /**
     * Campo "agendas" igual ao filtro Agendas da agenda. $acao: JavaScript ao fechar a lista com mudanca (opcional);
     * $param: o post (ao voltar com erro do salvar, as marcadas continuam marcadas).
     */
    public static function campo(TCriteria $criteria, string $acao = '', $param = []): TElement
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        $opcoes = [];
        $criteria->setProperty('order', 'nome');
        foreach (Agenda::getObjects($criteria) ?: [] as $a) {
            $opcoes[(int) $a->id] = ['nome' => $a->nome, 'cor' => AgendaVisual::cores($a->cor)['cor']];
        }
        if ($propria) { TTransaction::close(); }

        $div = new TElement('div');
        $div->style = 'width: 100%';
        $div->add(FiltroMultiplo::estilo());
        $marcadas = array_intersect_key($opcoes, array_flip(array_map('intval', (array) ($param['agendas'] ?? []))));
        $div->add(FiltroMultiplo::campo('agendas', 'Buscar agenda', 'Todas as agendas', $opcoes, $marcadas, $acao));
        $div->add(TScript::create(FiltroMultiplo::script(), false));
        return $div;
    }

    /** Ids das agendas marcadas (do post, $param['agendas'], ou de um objeto com ->agendas; vazio: erro). */
    public static function marcadas($fonte): array
    {
        $v = is_array($fonte) ? ($fonte['agendas'] ?? []) : ($fonte->agendas ?? []);
        $ids = array_values(array_unique(array_filter(array_map('intval', is_array($v) ? $v : [$v]))));
        if (!$ids) {
            throw new Exception('Marque pelo menos uma agenda.');
        }
        return $ids;
    }

    /** Mensagem de sucesso: quantos foram criados. */
    public static function mensagem(string $plural, int $quantidade): string
    {
        return $quantidade > 1 ? "{$quantidade} {$plural} salvos, um em cada agenda marcada" : "Registro salvo";
    }
}
