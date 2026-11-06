<?php
require_once __DIR__ . '/bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

/** Agendas e pessoa exclusivas do teste; nao altera agendas existentes. */
class AgendaTesteFixture
{
    public int $agenda;
    public int $outra;
    public int $cliente;
    public string $dia;

    public function __construct()
    {
        TSession::setValue('userid', 1);
        $this->dia = date('Y-m-d', strtotime('next monday +56 weeks'));
        $this->cliente = (int) $this->sql("INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES ('TESTE AGENDA ISOLADA', 1) RETURNING id")[0]['id'];
        $ids = [];
        foreach (['PRINCIPAL', 'CONVIDADA'] as $nome) {
            $ids[] = (int) $this->sql("INSERT INTO agenda (escritorio_id, profissional_id, nome, horario_inicial, horario_final, horario_inicio_intervalo, horario_fim_intervalo, duracao, dias, fl_permite_choque_horario, aceita_agendamento_online, publica)
                SELECT escritorio_id, profissional_id, ?, '09:00', '18:00', '12:00', '13:00', 30, '1,2,3,4,5', 'F', 'T', 'T' FROM agenda ORDER BY id LIMIT 1 RETURNING id", ['TESTE AGENDA ISOLADA ' . $nome])[0]['id'];
        }
        [$this->agenda, $this->outra] = $ids;
        $unidade = (int) $this->sql('SELECT e.system_unit_id FROM escritorio e JOIN agenda a ON a.escritorio_id = e.id WHERE a.id = ?', [$this->agenda])[0]['system_unit_id'];
        TSession::setValue('userunitid', $unidade);
        TSession::setValue('userunitids', [$unidade]);
        TSession::setValue('portal_cliente_id', $this->cliente);
    }

    public function sql(string $sql, array $params = []): array
    {
        TTransaction::open('escritorio');
        try {
            $st = TTransaction::get()->prepare($sql); $st->execute($params);
            $result = $st->columnCount() ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
            TTransaction::close();
            return $result;
        } catch (Throwable $e) { TTransaction::rollback(); throw $e; }
    }

    public function agendar(string $inicio, string $fim, ?int $agenda = null, $estado = EstadoAgenda::AGENDADO): int
    {
        return (int) $this->sql('INSERT INTO agendamento (cliente_id, agenda_id, dt_inicial, dt_final, estado_agenda_id) VALUES (?, ?, ?, ?, ?) RETURNING id', [$this->cliente, $agenda ?? $this->agenda, $inicio, $fim, $estado])[0]['id'];
    }

    public function limpar(): void
    {
        while (TTransaction::get()) { TTransaction::rollback(); }
        foreach (['convidado', 'estado_agendamento'] as $t) { $this->sql("DELETE FROM {$t} WHERE agendamento_id IN (SELECT id FROM agendamento WHERE cliente_id = ?)", [$this->cliente]); }
        $this->sql('DELETE FROM agendamento WHERE cliente_id = ?', [$this->cliente]);
        $this->sql('DELETE FROM convidado_compromisso WHERE agenda_id IN (?, ?) OR compromisso_id IN (SELECT id FROM compromisso WHERE agenda_id IN (?, ?))', [$this->agenda, $this->outra, $this->agenda, $this->outra]);
        foreach (['compromisso', 'bloqueio', 'agenda_excecao'] as $t) { $this->sql("DELETE FROM {$t} WHERE agenda_id IN (?, ?)", [$this->agenda, $this->outra]); }
        $this->sql('DELETE FROM agenda WHERE id IN (?, ?)', [$this->agenda, $this->outra]);
        $this->sql('DELETE FROM pessoa WHERE id = ?', [$this->cliente]);
    }
}
