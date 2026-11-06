<?php

/** Horarios do modal: expediente e excecoes, sempre validados tambem nas convidadas. */
class AgendaHorarioDisponivelService
{
    public static function listar(int $agendaId, string $dia, array $convidadas = []): array
    {
        $agenda = Agenda::find($agendaId);
        if (!$agenda) { throw new Exception('Agenda não encontrada.'); }
        $inicioDia = $dia . ' 00:00:00';
        $fimDia = date('Y-m-d', strtotime($dia . ' +1 day')) . ' 00:00:00';
        $faixas = [[$dia . ' ' . ($agenda->horario_inicial ?: '00:00'), $agenda->horario_final ? $dia . ' ' . $agenda->horario_final : $fimDia]];
        $st = TTransaction::get()->prepare('SELECT dt_inicio, dt_final FROM agenda_excecao WHERE agenda_id = ? AND dt_inicio < ? AND dt_final > ?');
        $st->execute([$agendaId, $fimDia, $inicioDia]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r)
        {
            $faixas[] = [max($inicioDia, $r['dt_inicio']), min($fimDia, $r['dt_final'])];
        }
        $duracao = max(5, (int) $agenda->duracao) * 60;
        $agendas = array_unique(array_merge([$agendaId], array_filter(array_map('intval', $convidadas))));
        $horarios = [];
        foreach ($faixas as [$inicio, $fim])
        {
            for ($de = strtotime($inicio), $ate = strtotime($fim); $de + $duracao <= $ate; $de += $duracao)
            {
                try
                {
                    foreach ($agendas as $id)
                    {
                        AgendaConflitoService::exigirLivre($id, date('Y-m-d H:i:s', $de), date('Y-m-d H:i:s', $de + $duracao));
                    }
                    $hora = date('H:i', $de);
                    $horarios[$hora] = $hora;
                }
                catch (Exception $e) { }
            }
        }
        ksort($horarios);
        return $horarios;
    }
}
