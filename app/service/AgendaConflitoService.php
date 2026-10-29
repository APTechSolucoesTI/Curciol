<?php
/**
 * Horario livre na agenda (30/10/2026): ao gravar um agendamento (novo, de excecao, repeticoes ou mudando agenda/horario)
 * nao pode haver no mesmo horario da mesma agenda outro agendamento (inclusive de excecao e os em que a agenda e
 * convidada), bloqueio ou compromisso (inclusive os em que a agenda e convidada). Encostar (um termina quando o outro
 * comeca) pode. Agenda que permite choque de horario aceita agendamento sobre agendamento, mas nunca sobre bloqueio
 * ou compromisso. Agendamentos cancelados e de quem nao compareceu nao ocupam o horario. Usa a transacao aberta.
 */
class AgendaConflitoService
{
    public static function exigirLivre(int $agendaId, string $inicio, string $fim, int $ignorarAgendamentoId = 0): void
    {
        if ($agendaId <= 0 || trim($inicio) === '' || trim($fim) === '') { return; }
        $inicio = substr(str_replace('T', ' ', trim($inicio)), 0, 16);
        $fim = substr(str_replace('T', ' ', trim($fim)), 0, 16);
        if ($fim <= $inicio) { throw new Exception('O fim do horário tem que ser depois do início.'); }

        $c = TTransaction::get();
        $agenda = Agenda::find($agendaId);
        $nomeAgenda = $agenda ? ' na agenda ' . $agenda->nome : '';

        if (!$agenda || $agenda->fl_permite_choque_horario != 'T') {
            $st = $c->prepare("SELECT a.dt_inicial, a.dt_final, p.nome AS detalhe FROM agendamento a LEFT JOIN pessoa p ON p.id = a.cliente_id
                                WHERE (a.agenda_id = :agenda OR a.id IN (SELECT agendamento_id FROM convidado WHERE agenda_id = :agenda2))
                                  AND a.id <> :ignorar AND COALESCE(a.ativo, 'T') = 'T' AND a.estado_agenda_id NOT IN (:cancelado, :faltou)
                                  AND a.dt_inicial < :fim AND a.dt_final > :inicio
                                ORDER BY a.dt_inicial LIMIT 1");
            $st->execute([':agenda' => $agendaId, ':agenda2' => $agendaId, ':ignorar' => $ignorarAgendamentoId, ':cancelado' => (int) EstadoAgenda::CANCELADO,
                          ':faltou' => (int) EstadoAgenda::NAO_COMPARECEU, ':fim' => $fim, ':inicio' => $inicio]);
            if ($r = $st->fetch(PDO::FETCH_OBJ)) { throw new Exception(self::mensagem('um agendamento', $nomeAgenda, $r)); }
        }

        $st = $c->prepare("SELECT dt_inicio AS dt_inicial, dt_final, observacao AS detalhe FROM bloqueio
                            WHERE agenda_id = :agenda AND dt_inicio < :fim AND dt_final > :inicio ORDER BY dt_inicio LIMIT 1");
        $st->execute([':agenda' => $agendaId, ':fim' => $fim, ':inicio' => $inicio]);
        if ($r = $st->fetch(PDO::FETCH_OBJ)) { throw new Exception(self::mensagem('um bloqueio', $nomeAgenda, $r)); }

        $st = $c->prepare("SELECT c.dt_inicio AS dt_inicial, c.dt_final, t.nome AS detalhe FROM compromisso c LEFT JOIN tipo_compromisso t ON t.id = c.tipo_compromisso_id
                            WHERE (c.agenda_id = :agenda OR c.id IN (SELECT compromisso_id FROM convidado_compromisso WHERE agenda_id = :agenda2))
                              AND c.dt_inicio < :fim AND c.dt_final > :inicio ORDER BY c.dt_inicio LIMIT 1");
        $st->execute([':agenda' => $agendaId, ':agenda2' => $agendaId, ':fim' => $fim, ':inicio' => $inicio]);
        if ($r = $st->fetch(PDO::FETCH_OBJ)) { throw new Exception(self::mensagem('um compromisso', $nomeAgenda, $r)); }
    }

    private static function mensagem(string $tipo, string $nomeAgenda, $r): string
    {
        $ini = strtotime((string) $r->dt_inicial);
        $fim = strtotime((string) $r->dt_final);
        $quando = date('d/m/Y', $ini) === date('d/m/Y', $fim)
            ? 'em ' . date('d/m/Y', $ini) . ' das ' . date('H:i', $ini) . ' às ' . date('H:i', $fim)
            : 'de ' . date('d/m/Y H:i', $ini) . ' a ' . date('d/m/Y H:i', $fim);
        $detalhe = trim((string) $r->detalhe) !== '' ? ' (' . htmlspecialchars(mb_strimwidth(trim((string) $r->detalhe), 0, 60, '…')) . ')' : '';
        return "Já existe {$tipo}{$nomeAgenda} {$quando}{$detalhe}. Escolha outro horário.";
    }
}
