<?php
/**
 * Horario livre na agenda (30/10/2026): ao gravar um agendamento (novo, de excecao, repeticoes ou mudando agenda/horario)
 * nao pode haver no mesmo horario da mesma agenda outro agendamento (inclusive de excecao e os em que a agenda e
 * convidada), bloqueio ou compromisso (inclusive os em que a agenda e convidada). Encostar (um termina quando o outro
 * comeca) pode. Agenda que permite choque de horario aceita agendamento sobre agendamento, mas nunca sobre bloqueio
 * ou compromisso. Agendamentos cancelados e de quem nao compareceu nao ocupam o horario. Usa a transacao aberta.
 *
 * Horario de atendimento (revisto em 05/11/2026): agendamento e compromisso, a mesma regra: so em horario normal da agenda (dia
 * que atende, dentro do horario, fora do intervalo) ou numa excecao, e sem outro agendamento ou compromisso no mesmo horario.
 *
 * Excecao: pode ficar sobre bloqueio, intervalo e nao atende; pode pegar horario normal, desde que pelo menos uma parte nao seja.
 * Durante ela, o bloqueio, o intervalo e o nao atende daquele trecho nao contam; a sobreposicao entre marcados continua barrada.
 *
 * Bloqueio: o contrario. Precisa pegar pelo menos uma parte de horario normal e nao entra sobre agendamento, compromisso ou
 * excecao, seja qual for o estado (tem que cancelar ou reagendar antes). Sobre cancelado pode, mas o cancelado que fica sob o
 * bloqueio nao muda mais de estado (ver exigirPodeSairDeCancelado).
 *
 * Bloqueio sobre bloqueio e excecao sobre excecao: se so repete o que ja existe, avisa e nao cria; se passa do que existe,
 * estende o existente em vez de criar outro (ver planoDoPeriodo).
 */
class AgendaConflitoService
{
    public static function exigirLivre(int $agendaId, string $inicio, string $fim, int $ignorarAgendamentoId = 0): void
    {
        if ($agendaId <= 0 || trim($inicio) === '' || trim($fim) === '') { return; }
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);
        if ($fim <= $inicio) { throw new Exception('O fim do horário tem que ser depois do início.'); }

        $c = TTransaction::get();
        $agenda = Agenda::find($agendaId);
        $nomeAgenda = $agenda ? ' na agenda ' . $agenda->nome : '';

        // o que cai numa excecao da agenda nao confere atendimento nem bloqueio
        $foraDaExcecao = self::foraDeExcecoes($agendaId, $inicio, $fim);
        foreach ($foraDaExcecao as [$de, $ate]) {
            self::exigirAtendimento($agendaId, $de, $ate, ' Para liberar este horário, crie uma exceção em Adicionar > Exceção.');
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

        foreach ($foraDaExcecao as [$de, $ate]) {
            self::exigirSemBloqueio($agendaId, $de, $ate, $nomeAgenda);
        }

        $st = $c->prepare("SELECT c.dt_inicio AS dt_inicial, c.dt_final, t.nome AS detalhe FROM compromisso c LEFT JOIN tipo_compromisso t ON t.id = c.tipo_compromisso_id
                            WHERE (c.agenda_id = :agenda OR c.id IN (SELECT compromisso_id FROM convidado_compromisso WHERE agenda_id = :agenda2))
                              AND c.dt_inicio < :fim AND c.dt_final > :inicio ORDER BY c.dt_inicio LIMIT 1");
        $st->execute([':agenda' => $agendaId, ':agenda2' => $agendaId, ':fim' => $fim, ':inicio' => $inicio]);
        if ($r = $st->fetch(PDO::FETCH_OBJ)) { throw new Exception(self::mensagem('um compromisso', $nomeAgenda, $r)); }
    }

    /** Compromisso: a mesma regra do agendamento. So em horario normal ou em excecao, sem agendamento nem outro compromisso. */
    public static function exigirLivreCompromisso(int $agendaId, string $inicio, string $fim, int $ignorarCompromissoId = 0): void
    {
        if ($agendaId <= 0 || trim($inicio) === '' || trim($fim) === '') { return; }
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);
        if ($fim <= $inicio) { throw new Exception('O fim do horário tem que ser depois do início.'); }
        $agenda = Agenda::find($agendaId);
        $nomeAgenda = $agenda ? ' na agenda ' . $agenda->nome : '';
        foreach (self::foraDeExcecoes($agendaId, $inicio, $fim) as [$de, $ate])
        {
            self::exigirSemBloqueio($agendaId, $de, $ate, $nomeAgenda);
            self::exigirAtendimento($agendaId, $de, $ate, ' Para liberar este horário, crie uma exceção em Adicionar > Exceção.');
        }

        foreach (self::ocupacoes($agendaId, $inicio, $fim, $ignorarCompromissoId) as $o)
        {
            throw new Exception(self::mensagem($o['tipo'] === 'agendamento' ? 'um agendamento' : 'um compromisso', $nomeAgenda, (object) $o));
        }
    }

    /**
     * Bloqueio gravado pegando tambem o intervalo ou o nao atende: o aviso de cada periodo, sem repetir.
     * $periodos: [[agenda, inicio, fim]]. Usa a transacao aberta.
     */
    public static function avisosAtendimento(array $periodos): array
    {
        $avisos = [];
        foreach ($periodos as [$agenda, $inicio, $fim])
        {
            foreach (self::foraDeExcecoes((int) $agenda, (string) $inicio, (string) $fim) as [$de, $ate])
            {
                try
                {
                    self::exigirAtendimento((int) $agenda, $de, $ate);
                }
                catch (Exception $e)
                {
                    $avisos[$e->getMessage()] = true;
                }
            }
        }

        return array_keys($avisos);
    }

    /** Periodos das excecoes da agenda que pegam o horario: [[inicio, fim]] */
    private static function excecoesNoHorario(int $agendaId, string $inicio, string $fim): array
    {
        return self::periodosNoHorario('agenda_excecao', $agendaId, $inicio, $fim);
    }

    private static function periodosNoHorario(string $tabela, int $agendaId, string $inicio, string $fim): array
    {
        $st = TTransaction::get()->prepare("SELECT dt_inicio, dt_final FROM {$tabela} WHERE agenda_id = ? AND dt_inicio < ? AND dt_final > ? ORDER BY dt_inicio");
        $st->execute([$agendaId, self::minuto($fim), self::minuto($inicio)]);

        return array_map(function ($r) { return [self::minuto((string) $r['dt_inicio']), self::minuto((string) $r['dt_final'])]; }, $st->fetchAll(PDO::FETCH_ASSOC));
    }

    /** $trechos sem os periodos $tirar. Horarios no formato de minuto() (a ordem de texto e a ordem de tempo). */
    private static function subtrair(array $trechos, array $tirar): array
    {
        foreach ($tirar as [$a, $b])
        {
            $novos = [];
            foreach ($trechos as [$i, $f])
            {
                if ($b <= $i || $a >= $f) { $novos[] = [$i, $f]; continue; }
                if ($a > $i) { $novos[] = [$i, $a]; }
                if ($b < $f) { $novos[] = [$b, $f]; }
            }
            $trechos = $novos;
        }

        return $trechos;
    }

    /** O que sobra do horario fora das excecoes da agenda: [[inicio, fim]] (vazio: esta todo numa excecao). */
    public static function foraDeExcecoes(int $agendaId, string $inicio, string $fim): array
    {
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);

        return self::subtrair([[$inicio, $fim]], self::excecoesNoHorario($agendaId, $inicio, $fim));
    }

    /** Trechos do horario em que a agenda atende normalmente: dia que atende, dentro do horario e fora do intervalo. */
    private static function trechosDeAtendimento(Agenda $agenda, string $inicio, string $fim): array
    {
        $dias = trim((string) $agenda->dias) === '' ? null : array_map('intval', explode(',', (string) $agenda->dias));
        $hIni = substr((string) $agenda->horario_inicial, 0, 5);
        $hFim = substr((string) $agenda->horario_final, 0, 5);
        $iIni = substr((string) $agenda->horario_inicio_intervalo, 0, 5);
        $iFim = substr((string) $agenda->horario_fim_intervalo, 0, 5);
        $trechos = [];

        for ($dia = substr($inicio, 0, 10); $dia . ' 00:00' < $fim; $dia = date('Y-m-d', strtotime($dia . ' +1 day')))
        {
            if ($dias !== null && !in_array((int) date('w', strtotime($dia)), $dias, true)) { continue; }
            $de = max($inicio, $dia . ' ' . ($hIni !== '' ? $hIni : '00:00'));
            $ate = min($fim, $hFim !== '' ? $dia . ' ' . $hFim : date('Y-m-d', strtotime($dia . ' +1 day')) . ' 00:00');
            if ($ate <= $de) { continue; }
            $doDia = [[$de, $ate]];
            if ($iIni !== '' && $iFim !== '')
            {
                $doDia = self::subtrair($doDia, [[$dia . ' ' . $iIni, $dia . ' ' . $iFim]]);
            }
            $trechos = array_merge($trechos, $doDia);
        }

        return $trechos;
    }

    /**
     * Excecao nova: pelo menos uma parte do periodo tem que estar em bloqueio, intervalo ou nao atende. Todo em horario normal, nao
     * ha o que liberar.
     */
    public static function exigirPeriodoDeExcecao(int $agendaId, string $inicio, string $fim): void
    {
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);
        if ($fim <= $inicio) { throw new Exception('O fim do horário tem que ser depois do início.'); }

        $agenda = Agenda::find($agendaId);
        if (!$agenda) { throw new Exception('Agenda não encontrada.'); }

        $normais = self::subtrair(self::trechosDeAtendimento($agenda, $inicio, $fim), self::periodosNoHorario('bloqueio', $agendaId, $inicio, $fim));
        $emHorarioNormal = 0;
        foreach ($normais as [$de, $ate])
        {
            $emHorarioNormal += strtotime($ate) - strtotime($de);
        }
        if ($emHorarioNormal >= strtotime($fim) - strtotime($inicio))
        {
            throw new Exception("A exceção precisa pegar pelo menos uma parte de bloqueio, intervalo ou não atende. Nesse período, a agenda {$agenda->nome} atende normalmente.");
        }
    }

    /**
     * O periodo pega horario normal da agenda? A folga de 1 minuto e para o intervalo cadastrado como 12:01 a 12:59: o bloqueio de
     * 12:00 a 13:00 esta so no intervalo.
     */
    public static function temHorarioNormal(int $agendaId, string $inicio, string $fim): bool
    {
        $agenda = Agenda::find($agendaId);
        if (!$agenda)
        {
            return false;
        }
        foreach (self::trechosDeAtendimento($agenda, self::minuto($inicio), self::minuto($fim)) as [$de, $ate])
        {
            if (strtotime($ate) - strtotime($de) > 60)
            {
                return true;
            }
        }

        return false;
    }

    /** "em 04/01/2027 das 10:00 às 10:30" ou "de 04/01/2027 10:00 a 05/01/2027 17:00" */
    private static function quando(string $inicio, string $fim): string
    {
        $i = strtotime($inicio);
        $f = strtotime($fim);

        return date('d/m/Y', $i) === date('d/m/Y', $f)
            ? 'em ' . date('d/m/Y', $i) . ' das ' . date('H:i', $i) . ' às ' . date('H:i', $f)
            : 'de ' . date('d/m/Y H:i', $i) . ' a ' . date('d/m/Y H:i', $f);
    }

    /**
     * Periodo novo de bloqueio ou excecao contra os que ja existem na agenda. Encostar nao conta como sobrepor.
     *
     * @return array acao: 'criar', 'ja_existe' (nao passa do que existe: nada a fazer) ou 'estender' (passa: o existente cresce);
     *               inicio/fim: o periodo final; novos: os trechos que ainda nao existiam; existentes: as linhas sobrepostas; mensagem
     */
    private static function planoDoPeriodo(string $tabela, int $agendaId, string $inicio, string $fim, int $ignorarId = 0): array
    {
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);
        if ($fim <= $inicio) { throw new Exception('O fim do horário tem que ser depois do início.'); }

        $textos = $tabela === 'bloqueio' ? ['um bloqueio', 'O bloqueio', 'estendido', 'Nenhum bloqueio novo foi criado.'] : ['uma exceção', 'A exceção', 'estendida', 'Nenhuma exceção nova foi criada.'];
        $agenda = Agenda::find($agendaId);
        $nomeAgenda = $agenda ? ' na agenda ' . $agenda->nome : '';

        $st = TTransaction::get()->prepare("SELECT id, dt_inicio, dt_final, observacao FROM {$tabela} WHERE agenda_id = ? AND id <> ? AND dt_inicio < ? AND dt_final > ? ORDER BY dt_inicio");
        $st->execute([$agendaId, $ignorarId, $fim, $inicio]);
        $existentes = $st->fetchAll(PDO::FETCH_ASSOC);

        if (!$existentes)
        {
            return ['acao' => 'criar', 'inicio' => $inicio, 'fim' => $fim, 'novos' => [[$inicio, $fim]], 'existentes' => [], 'mensagem' => ''];
        }

        $periodos = array_map(function ($r) { return [self::minuto((string) $r['dt_inicio']), self::minuto((string) $r['dt_final'])]; }, $existentes);
        $novos = self::subtrair([[$inicio, $fim]], $periodos);
        $quandos = implode(' e ', array_map(function ($p) { return self::quando($p[0], $p[1]); }, $periodos));

        if (!$novos)
        {
            return ['acao' => 'ja_existe', 'inicio' => $inicio, 'fim' => $fim, 'novos' => [], 'existentes' => $existentes,
                    'mensagem' => "Já existe {$textos[0]}{$nomeAgenda} {$quandos}. {$textos[3]}"];
        }

        $de = min(array_merge([$inicio], array_column($periodos, 0)));
        $ate = max(array_merge([$fim], array_column($periodos, 1)));

        return ['acao' => 'estender', 'inicio' => $de, 'fim' => $ate, 'novos' => $novos, 'existentes' => $existentes,
                'mensagem' => "{$textos[1]} que já existia{$nomeAgenda} ({$quandos}) foi {$textos[2]} e agora fica " . self::quando($de, $ate) . '.'];
    }

    /**
     * Bloqueio novo (ou mudando agenda/horario): o que fazer com os bloqueios que ja existem, ja validado. Precisa pegar horario
     * normal; o trecho novo nao entra sobre excecao, agendamento nem compromisso, seja qual for o estado (cancelado e de quem nao
     * compareceu nao ocupam).
     */
    public static function planejarBloqueio(int $agendaId, string $inicio, string $fim, int $ignorarBloqueioId = 0): array
    {
        $plano = self::planoDoPeriodo('bloqueio', $agendaId, $inicio, $fim, $ignorarBloqueioId);
        if ($plano['acao'] === 'ja_existe')
        {
            return $plano;
        }

        $agenda = Agenda::find($agendaId);
        $nomeAgenda = $agenda ? ' na agenda ' . $agenda->nome : '';
        if (!self::temHorarioNormal($agendaId, $plano['inicio'], $plano['fim']))
        {
            throw new Exception("O bloqueio precisa pegar pelo menos uma parte do horário normal de atendimento{$nomeAgenda}. Esse período está todo em intervalo ou não atende.");
        }

        $linhas = [];
        foreach ($plano['novos'] as [$de, $ate])
        {
            self::exigirSemExcecao($agendaId, $de, $ate);
            $marcados = self::sobreposicoesDoBloqueio([[$agendaId, $de, $ate]]);
            $linhas = array_merge($linhas, $marcados['impedem'], $marcados['avisam']);
        }
        if ($linhas)
        {
            throw new Exception("Não é possível bloquear este horário{$nomeAgenda}. Cancele ou reagende antes:<br><br>" . self::listaParaAviso(array_values(array_unique($linhas))));
        }

        return $plano;
    }

    /** Excecao nova: o que fazer com as excecoes que ja existem, ja validado (pelo menos uma parte fora do horario normal). */
    public static function planejarExcecao(int $agendaId, string $inicio, string $fim): array
    {
        $plano = self::planoDoPeriodo('agenda_excecao', $agendaId, $inicio, $fim);
        if ($plano['acao'] !== 'ja_existe')
        {
            self::exigirPeriodoDeExcecao($agendaId, $plano['inicio'], $plano['fim']);
        }

        return $plano;
    }

    /**
     * Aplica o plano 'estender': $alvo (ou o primeiro existente) fica com o periodo final e os outros sobrepostos saem, para nao
     * sobrar dois. As observacoes diferentes sao mantidas juntas. Usa a transacao aberta.
     */
    public static function fundirPeriodos(string $classe, array $plano, string $observacao, $alvo = null)
    {
        $existentes = $plano['existentes'];
        if (!$alvo)
        {
            $alvo = new $classe((int) array_shift($existentes)['id']);
        }

        $textos = [];
        foreach (array_merge([(string) $alvo->observacao], array_column($existentes, 'observacao'), [$observacao]) as $texto)
        {
            $texto = trim((string) $texto);
            if ($texto !== '') { $textos[mb_strtoupper($texto)] = $texto; }
        }

        foreach ($existentes as $outro)
        {
            (new $classe((int) $outro['id']))->delete();
        }

        $alvo->dt_inicio = $plano['inicio'];
        $alvo->dt_final = $plano['fim'];
        $alvo->observacao = implode(' / ', $textos);
        $alvo->modificacao_user_id = TSession::getValue('userid');
        $alvo->store();

        return $alvo;
    }

    /**
     * Agendamento cancelado (ou de quem nao compareceu) sob um bloqueio criado depois nao volta a ocupar o horario: nao muda de
     * estado. Numa excecao, o bloqueio daquele trecho nao conta.
     */
    public static function exigirPodeSairDeCancelado(int $agendaId, string $inicio, string $fim): void
    {
        foreach (self::foraDeExcecoes($agendaId, $inicio, $fim) as [$de, $ate])
        {
            if (self::periodosNoHorario('bloqueio', $agendaId, $de, $ate))
            {
                throw new Exception('Este agendamento não pode mudar de situação porque há um bloqueio nesse horário. Para atender esse cliente, crie um novo agendamento em outro horário.');
            }
        }
    }

    /** Bloqueio novo nao entra em cima de uma excecao que ja existe. */
    public static function exigirSemExcecao(int $agendaId, string $inicio, string $fim): void
    {
        $inicio = self::minuto($inicio);
        $fim = self::minuto($fim);
        $st = TTransaction::get()->prepare("SELECT dt_inicio AS dt_inicial, dt_final, observacao AS detalhe FROM agenda_excecao
                                             WHERE agenda_id = ? AND dt_inicio < ? AND dt_final > ? ORDER BY dt_inicio LIMIT 1");
        $st->execute([$agendaId, $fim, $inicio]);
        if ($r = $st->fetch(PDO::FETCH_OBJ))
        {
            $agenda = Agenda::find($agendaId);
            throw new Exception(self::mensagem('uma exceção', $agenda ? ' na agenda ' . $agenda->nome : '', $r));
        }
    }

    /** Agendas convidadas de um agendamento: as mesmas regras da principal. */
    public static function exigirLivreConvidados(array $agendas, string $inicio, string $fim, int $ignorarAgendamentoId = 0): void
    {
        foreach ($agendas as $agenda)
        {
            self::exigirLivre((int) $agenda, $inicio, $fim, $ignorarAgendamentoId);
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

    /** "2026-10-30T14:30:00" -> "2026-10-30 14:30" */
    private static function minuto(string $v): string
    {
        return substr(str_replace('T', ' ', trim($v)), 0, 16);
    }

    private static function mensagem(string $tipo, string $nomeAgenda, $r): string
    {
        $quando = self::quando((string) $r->dt_inicial, (string) $r->dt_final);
        $detalhe = trim((string) $r->detalhe) !== '' ? ' (' . htmlspecialchars(mb_strimwidth(trim((string) $r->detalhe), 0, 60, '…')) . ')' : '';
        return "Já existe {$tipo}{$nomeAgenda} {$quando}{$detalhe}. Escolha outro horário.";
    }
}
