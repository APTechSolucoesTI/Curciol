<?php

/** Horarios do modal: expediente e excecoes, sempre validados tambem nas convidadas. */
class AgendaHorarioDisponivelService
{
    public static function listar(int $agendaId, string $dia, array $convidadas = [], bool $apenasPrimeiro = false): array
    {
        $inicioDia = strtotime($dia . ' 00:00:00');
        $fimDia = strtotime($dia . ' +1 day');
        $agendas = array_unique(array_merge([$agendaId], array_filter(array_map('intval', $convidadas))));
        $faixasPorAgenda = [];
        $faixasCandidatas = [];
        $duracao = 0;
        foreach ($agendas as $id)
        {
            $agenda = Agenda::find($id);
            if (!$agenda) { throw new Exception('Agenda não encontrada.'); }
            if ($id === $agendaId) { $duracao = max(5, (int) $agenda->duracao) * 60; }
            $faixas = [];
            $dias = trim((string) $agenda->dias);
            if ($dias === '' || in_array((int) date('w', $inicioDia), array_map('intval', explode(',', $dias)), true))
            {
                $faixas[] = [strtotime($dia . ' ' . ($agenda->horario_inicial ?: '00:00')), $agenda->horario_final ? strtotime($dia . ' ' . $agenda->horario_final) : $fimDia];
            }
            $st = TTransaction::get()->prepare('SELECT dt_inicio, dt_final FROM agenda_excecao WHERE agenda_id = ? AND dt_inicio < ? AND dt_final > ?');
            $st->execute([$id, date('Y-m-d H:i:s', $fimDia), date('Y-m-d H:i:s', $inicioDia)]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r)
            {
                $faixas[] = [max($inicioDia, strtotime($r['dt_inicio'])), min($fimDia, strtotime($r['dt_final']))];
            }
            if (!$faixas) { return []; }
            $faixasCandidatas[] = $faixas;
            usort($faixas, fn($a, $b) => $a[0] <=> $b[0]);
            $cobertura = [];
            foreach ($faixas as [$inicio, $fim])
            {
                $ultimo = count($cobertura) - 1;
                if ($ultimo >= 0 && $inicio <= $cobertura[$ultimo][1]) { $cobertura[$ultimo][1] = max($cobertura[$ultimo][1], $fim); }
                else { $cobertura[] = [$inicio, $fim]; }
            }
            $faixasPorAgenda[] = $cobertura;
        }
        // Toda faixa pode introduzir um inicio valido, inclusive a excecao de uma convidada.
        $candidatos = [];
        foreach ($faixasCandidatas as $faixas)
        {
            foreach ($faixas as [$inicio, $fim])
            {
                for ($de = $inicio; $de + $duracao <= $fim; $de += $duracao) { $candidatos[$de] = true; }
            }
        }
        ksort($candidatos);
        $horarios = [];
        foreach ($candidatos as $de => $_)
        {
            foreach ($faixasPorAgenda as $faixas)
            {
                $coberto = false;
                foreach ($faixas as [$inicio, $fim]) { if ($de >= $inicio && $de + $duracao <= $fim) { $coberto = true; break; } }
                if (!$coberto) { continue 2; }
            }
            try
            {
                foreach ($agendas as $id) { AgendaConflitoService::exigirLivre($id, date('Y-m-d H:i:s', $de), date('Y-m-d H:i:s', $de + $duracao)); }
                $hora = date('H:i', $de); $horarios[$hora] = $hora;
                if ($apenasPrimeiro) { return $horarios; }
            }
            catch (Exception $e) { }
        }
        return $horarios;
    }
}
