<?php
/**
 * Horario livre na agenda (30/10/2026): ao gravar um agendamento (novo, de excecao, repeticoes ou mudando agenda/horario)
 * nao pode haver no mesmo horario da mesma agenda outro agendamento (inclusive de excecao e os em que a agenda e
 * convidada), bloqueio ou compromisso (inclusive os em que a agenda e convidada). Encostar (um termina quando o outro
 * comeca) pode. Agenda que permite choque de horario aceita agendamento sobre agendamento, mas nunca sobre bloqueio
 * ou compromisso. Agendamentos cancelados e de quem nao compareceu nao ocupam o horario. Usa a transacao aberta.
 *
 * Horario de atendimento (30/10/2026): agendamento novo (ou mudando agenda/horario) so dentro do atendimento da agenda:
 * em dia que ela atende, dentro do horario e fora do intervalo. A excecao existe para marcar agendamento no intervalo ou
 * quando a agenda nao atende (nunca sobre bloqueio). Compromisso e bloqueio podem sobrepor o intervalo e o nao atende, e a
 * tela avisa ao gravar (05/11/2026, ver avisosAtendimento).
 *
 * Compromisso (05/11/2026): tambem nao pode cair sobre agendamento nem sobre outro compromisso, nem em agenda que permite
 * choque de horario. Agendas convidadas (05/11/2026): valem as mesmas regras da agenda principal. Bloqueio novo sobre o que
 * ja esta marcado: confirmado ou em atendimento impede; o resto pede confirmacao (ver ocupacoes).
 */
class AgendaConflitoService
{
    /** $excecao: agendamento de excecao (pode cair no intervalo e quando a agenda nao atende). */
    public static function exigirLivre(int $agendaId, string $inicio, string $fim, int $ignorarAgendamentoId = 0, bool $excecao = false): void
    {
        if ($agendaId <= 0 || trim($inicio) === '' || trim($fim) === '') { return; }
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);
        if ($fim <= $inicio) { throw new Exception('O fim do horário tem que ser depois do início.'); }

        $c = TTransaction::get();
        $agenda = Agenda::find($agendaId);
        $nomeAgenda = $agenda ? ' na agenda ' . $agenda->nome : '';

        if (!$excecao) {
            self::exigirAtendimento($agendaId, $inicio, $fim, ' Para marcar mesmo assim, use Adicionar > Exceção.');
        }

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

        self::exigirSemBloqueio($agendaId, $inicio, $fim, $nomeAgenda);

        $st = $c->prepare("SELECT c.dt_inicio AS dt_inicial, c.dt_final, t.nome AS detalhe FROM compromisso c LEFT JOIN tipo_compromisso t ON t.id = c.tipo_compromisso_id
                            WHERE (c.agenda_id = :agenda OR c.id IN (SELECT compromisso_id FROM convidado_compromisso WHERE agenda_id = :agenda2))
                              AND c.dt_inicio < :fim AND c.dt_final > :inicio ORDER BY c.dt_inicio LIMIT 1");
        $st->execute([':agenda' => $agendaId, ':agenda2' => $agendaId, ':fim' => $fim, ':inicio' => $inicio]);
        if ($r = $st->fetch(PDO::FETCH_OBJ)) { throw new Exception(self::mensagem('um compromisso', $nomeAgenda, $r)); }
    }

    /** Compromisso: fora de bloqueio, de agendamento e de outro compromisso; pode sobrepor o intervalo e o nao atende. */
    public static function exigirLivreCompromisso(int $agendaId, string $inicio, string $fim, int $ignorarCompromissoId = 0): void
    {
        if ($agendaId <= 0 || trim($inicio) === '' || trim($fim) === '') { return; }
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);
        if ($fim <= $inicio) { throw new Exception('O fim do horário tem que ser depois do início.'); }
        $agenda = Agenda::find($agendaId);
        $nomeAgenda = $agenda ? ' na agenda ' . $agenda->nome : '';
        self::exigirSemBloqueio($agendaId, $inicio, $fim, $nomeAgenda);

        foreach (self::ocupacoes($agendaId, $inicio, $fim, $ignorarCompromissoId) as $o)
        {
            throw new Exception(self::mensagem($o['tipo'] === 'agendamento' ? 'um agendamento' : 'um compromisso', $nomeAgenda, (object) $o));
        }
    }

    /**
     * Compromisso ou bloqueio gravado sobre o intervalo ou o nao atende: o aviso de cada periodo, sem repetir.
     * $periodos: [[agenda, inicio, fim]]. Usa a transacao aberta.
     */
    public static function avisosAtendimento(array $periodos): array
    {
        $avisos = [];
        foreach ($periodos as [$agenda, $inicio, $fim])
        {
            try
            {
                self::exigirAtendimento((int) $agenda, (string) $inicio, (string) $fim);
            }
            catch (Exception $e)
            {
                $avisos[$e->getMessage()] = true;
            }
        }

        return array_keys($avisos);
    }

    /** Agendas convidadas de um agendamento: as mesmas regras da principal. */
    public static function exigirLivreConvidados(array $agendas, string $inicio, string $fim, int $ignorarAgendamentoId = 0, bool $excecao = false): void
    {
        foreach ($agendas as $agenda)
        {
            self::exigirLivre((int) $agenda, $inicio, $fim, $ignorarAgendamentoId, $excecao);
        }
    }

    /** Agendas convidadas de um compromisso: as mesmas regras da principal. */
    public static function exigirLivreConvidadosCompromisso(array $agendas, string $inicio, string $fim, int $ignorarCompromissoId = 0): void
    {
        foreach ($agendas as $agenda)
        {
            self::exigirLivreCompromisso((int) $agenda, $inicio, $fim, $ignorarCompromissoId);
        }
    }

    /** Agendas convidadas que vieram da tela, sem repetir e sem a principal. */
    public static function convidadosPostados(array $param, string $campo, int $agendaPrincipal = 0): array
    {
        $agendas = array_unique(array_filter(array_map('intval', (array) ($param[$campo] ?? []))));

        return array_values(array_diff($agendas, [$agendaPrincipal]));
    }

    /**
     * O que ja esta marcado no horario da agenda (ela sendo a principal ou convidada), do mais cedo ao mais tarde.
     * Agendamento cancelado e de quem nao compareceu nao ocupa.
     *
     * @return array [['tipo' => 'agendamento'|'compromisso', 'id', 'estado', 'dt_inicial', 'dt_final', 'detalhe']]
     */
    public static function ocupacoes(int $agendaId, string $inicio, string $fim, int $ignorarCompromissoId = 0): array
    {
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);
        $c = TTransaction::get();

        $st = $c->prepare("SELECT 'agendamento' AS tipo, a.id, a.estado_agenda_id AS estado, a.dt_inicial, a.dt_final, p.nome AS detalhe
                             FROM agendamento a LEFT JOIN pessoa p ON p.id = a.cliente_id
                            WHERE (a.agenda_id = :agenda OR a.id IN (SELECT agendamento_id FROM convidado WHERE agenda_id = :agenda2))
                              AND COALESCE(a.ativo, 'T') = 'T' AND a.estado_agenda_id NOT IN (:cancelado, :faltou)
                              AND a.dt_inicial < :fim AND a.dt_final > :inicio ORDER BY a.dt_inicial");
        $st->execute([':agenda' => $agendaId, ':agenda2' => $agendaId, ':cancelado' => (int) EstadoAgenda::CANCELADO, ':faltou' => (int) EstadoAgenda::NAO_COMPARECEU, ':fim' => $fim, ':inicio' => $inicio]);
        $lista = $st->fetchAll(PDO::FETCH_ASSOC);

        $st = $c->prepare("SELECT 'compromisso' AS tipo, c.id, 0 AS estado, c.dt_inicio AS dt_inicial, c.dt_final, t.nome AS detalhe
                             FROM compromisso c LEFT JOIN tipo_compromisso t ON t.id = c.tipo_compromisso_id
                            WHERE (c.agenda_id = :agenda OR c.id IN (SELECT compromisso_id FROM convidado_compromisso WHERE agenda_id = :agenda2))
                              AND c.id <> :ignorar AND c.dt_inicio < :fim AND c.dt_final > :inicio ORDER BY c.dt_inicio");
        $st->execute([':agenda' => $agendaId, ':agenda2' => $agendaId, ':ignorar' => $ignorarCompromissoId, ':fim' => $fim, ':inicio' => $inicio]);

        return array_merge($lista, $st->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Bloqueio novo (ou mudando agenda/horario) sobre o que ja esta marcado. $periodos: [[agenda, inicio, fim]].
     * Agendamento confirmado ou em atendimento impede o bloqueio; o resto pede confirmacao.
     *
     * @return array ['impedem' => [linha de texto], 'avisam' => [linha de texto]]
     */
    public static function sobreposicoesDoBloqueio(array $periodos): array
    {
        $vistos = [];
        $res = ['impedem' => [], 'avisam' => []];
        foreach ($periodos as [$agenda, $inicio, $fim])
        {
            foreach (self::ocupacoes((int) $agenda, (string) $inicio, (string) $fim) as $o)
            {
                $chave = $o['tipo'] . $o['id'];
                if (isset($vistos[$chave])) { continue; }
                $vistos[$chave] = true;
                $quando = date('d/m/Y H:i', strtotime((string) $o['dt_inicial'])) . ' às ' . date('H:i', strtotime((string) $o['dt_final']));
                $nome = trim((string) $o['detalhe']) !== '' ? ' (' . htmlspecialchars(mb_strimwidth(trim((string) $o['detalhe']), 0, 60, '…')) . ')' : '';
                $linha = ($o['tipo'] === 'agendamento' ? 'Agendamento' : 'Compromisso') . " em {$quando}{$nome}";
                $impede = $o['tipo'] === 'agendamento' && in_array((string) $o['estado'], [EstadoAgenda::CONFIRMADO, EstadoAgenda::EM_ATENDIMENTO], true);
                $res[$impede ? 'impedem' : 'avisam'][] = $linha;
            }
        }

        return $res;
    }

    /** Lista curta para aviso: ate 10 linhas e quantas ficaram de fora. */
    public static function listaParaAviso(array $linhas): string
    {
        $mostrar = array_slice($linhas, 0, 10);
        $resto = count($linhas) - count($mostrar);

        return implode('<br>', $mostrar) . ($resto > 0 ? "<br>e mais {$resto}." : '');
    }

    /**
     * Dia a dia do periodo: a agenda tem que atender no dia da semana (agenda sem dias marcados atende todos), o trecho
     * tem que caber no horario dela e nao pode encostar no intervalo.
     */
    public static function exigirAtendimento(int $agendaId, string $inicio, string $fim, string $dica = ''): void
    {
        $agenda = Agenda::find($agendaId);
        if (!$agenda) { return; }
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);
        $dias = trim((string) $agenda->dias) === '' ? null : array_map('intval', explode(',', (string) $agenda->dias));
        $hIni = substr((string) $agenda->horario_inicial, 0, 5);
        $hFim = substr((string) $agenda->horario_final, 0, 5);
        $iIni = substr((string) $agenda->horario_inicio_intervalo, 0, 5);
        $iFim = substr((string) $agenda->horario_fim_intervalo, 0, 5);
        $semana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];

        for ($dia = substr($inicio, 0, 10); $dia . ' 00:00' < $fim; $dia = date('Y-m-d', strtotime($dia . ' +1 day'))) {
            $de = max($inicio, $dia . ' 00:00');
            $amanha = date('Y-m-d', strtotime($dia . ' +1 day')) . ' 00:00';
            $ate = min($fim, $amanha);
            if ($ate <= $de) { continue; }
            $deH = substr($de, 11, 5);
            $ateH = $ate === $amanha ? '24:00' : substr($ate, 11, 5);
            $data = date('d/m/Y', strtotime($dia));
            $w = (int) date('w', strtotime($dia));

            if ($dias !== null && !in_array($w, $dias, true)) {
                throw new Exception("A agenda {$agenda->nome} não atende em {$data} ({$semana[$w]}).{$dica}");
            }
            if (($hIni !== '' && $deH < $hIni) || ($hFim !== '' && $ateH > $hFim)) {
                throw new Exception("O horário fica fora do atendimento da agenda {$agenda->nome} em {$data} (das {$hIni} às {$hFim}).{$dica}");
            }
            if ($iIni !== '' && $iFim !== '' && $deH < $iFim && $ateH > $iIni) {
                throw new Exception("O horário cai no intervalo da agenda {$agenda->nome} em {$data} (das {$iIni} às {$iFim}).{$dica}");
            }
        }
    }

    private static function exigirSemBloqueio(int $agendaId, string $inicio, string $fim, string $nomeAgenda): void
    {
        $st = TTransaction::get()->prepare("SELECT dt_inicio AS dt_inicial, dt_final, observacao AS detalhe FROM bloqueio
                                             WHERE agenda_id = :agenda AND dt_inicio < :fim AND dt_final > :inicio ORDER BY dt_inicio LIMIT 1");
        $st->execute([':agenda' => $agendaId, ':fim' => $fim, ':inicio' => $inicio]);
        if ($r = $st->fetch(PDO::FETCH_OBJ)) { throw new Exception(self::mensagem('um bloqueio', $nomeAgenda, $r)); }
    }

    /** De varios dias ou com 8 horas ou mais (mesmo criterio de AgendaVisual::diaInteiro). */
    public static function longo(string $inicio, string $fim): bool
    {
        $i = strtotime(self::minuto($inicio));
        $f = strtotime(self::minuto($fim));
        return date('Y-m-d', $i) !== date('Y-m-d', $f - 1) || ($f - $i) >= 8 * 3600;
    }

    /** "2026-10-30T14:30:00" -> "2026-10-30 14:30" */
    private static function minuto(string $v): string
    {
        return substr(str_replace('T', ' ', trim($v)), 0, 16);
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
