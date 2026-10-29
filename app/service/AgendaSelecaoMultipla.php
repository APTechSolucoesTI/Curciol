<?php
/**
 * Varias agendas de uma vez (29/10/2026): ao ADICIONAR compromisso, bloqueio ou excecao, as agendas sao marcadas numa
 * lista com caixa de selecao e o registro e criado uma vez em cada agenda marcada. Ao EDITAR um registro que ja existe,
 * continua a agenda unica dele (combo agenda_id).
 */
class AgendaSelecaoMultipla
{
    /** Editando um registro existente (onEdit com key, ou salvando um que tem id)? */
    public static function editando($param): bool
    {
        return !empty($param['key']) || !empty($param['id']);
    }

    /** Lista de agendas com caixa de selecao (campo "agendas"). */
    public static function campo(TCriteria $criteria): TDBCheckGroup
    {
        $campo = new TDBCheckGroup('agendas', 'escritorio', 'Agenda', 'id', '{nome}', 'nome asc', $criteria);
        $campo->setLayout('horizontal');
        $campo->setBreakItems(3);
        $campo->setSize('100%');
        return $campo;
    }

    /** Ids das agendas marcadas no formulario (vazio: erro). */
    public static function marcadas($data): array
    {
        $v = $data->agendas ?? [];
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
