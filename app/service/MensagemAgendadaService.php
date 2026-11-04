<?php
/**
 * Lembrete agendado do agendamento confirmado (04/11/2026): a tela de envio grava a mensagem com a data e a hora; o cron
 * (ApchatRespostaService::vigiar, a cada minuto) envia o que venceu pelo MensagemService, com as mesmas travas do envio
 * na hora. As variaveis sao trocadas no envio. So sai com o agendamento ainda Confirmado e antes do horario dele.
 */
class MensagemAgendadaService
{
    private static $database = 'escritorio';

    /** Falha no envio tenta de novo a cada minuto ate este numero de vezes. */
    const TENTATIVAS = 5;

    /** Grava o lembrete. $quando no formato da tela (dd/mm/aaaa hh:mm). Usa a transacao aberta. */
    public static function agendar(Agendamento $agendamento, string $tipo, ?string $titulo, string $texto, ?int $templateId, string $destino, string $quando): MensagemAgendada
    {
        if ((int) $agendamento->estado_agenda_id !== (int) EstadoAgenda::CONFIRMADO)
        {
            throw new Exception('Só é possível agendar o lembrete de um agendamento confirmado.');
        }

        $data = DateTime::createFromFormat('d/m/Y H:i', trim($quando));
        $erros = DateTime::getLastErrors();

        if (!$data || !empty($erros['warning_count']) || !empty($erros['error_count']))
        {
            throw new Exception('Informe a data e a hora do envio.');
        }

        $enviarEm = $data->format('Y-m-d H:i:00');

        if ($enviarEm <= date('Y-m-d H:i:s'))
        {
            throw new Exception('A data do envio tem que ser no futuro.');
        }
        if ($enviarEm >= (string) $agendamento->dt_inicial)
        {
            throw new Exception('A data do envio tem que ser antes do horário do agendamento (' . date('d/m/Y H:i', strtotime($agendamento->dt_inicial)) . ').');
        }

        $ehEmail = $tipo === TemplateEscritorio::EMAIL;

        // as mesmas travas do envio na hora (em teste, nunca o numero ou e-mail do cliente)
        $destino = $ehEmail ? MensagemService::destinoEmail($agendamento, $destino) : MensagemService::destinoWhatsapp($agendamento, $destino);

        if (trim(strip_tags($texto)) === '')
        {
            throw new Exception('Escreva a mensagem.');
        }
        if ($ehEmail && trim((string) $titulo) === '')
        {
            throw new Exception('Título é obrigatório para envio de e-mail.');
        }

        $lembrete = new MensagemAgendada();
        $lembrete->agendamento_id = $agendamento->id;
        $lembrete->template_escritorio_id = $templateId ?: null;
        $lembrete->system_user_id = TSession::getValue('userid');
        $lembrete->tipo_mensagem = $ehEmail ? TemplateEscritorio::EMAIL : TemplateEscritorio::WHATSAPP;
        $lembrete->destino = $destino;
        $lembrete->titulo = $titulo;
        $lembrete->texto = $texto;
        $lembrete->enviar_em = $enviarEm;
        $lembrete->situacao = MensagemAgendada::PENDENTE;
        $lembrete->tentativas = 0;
        $lembrete->store();

        return $lembrete;
    }

    /** Lembretes para mostrar na tela do agendamento (os cancelados nao aparecem). */
    public static function doAgendamento(int $agendamentoId): array
    {
        return MensagemAgendada::where('agendamento_id', '=', $agendamentoId)
                               ->where('situacao', '<>', MensagemAgendada::CANCELADA)
                               ->orderBy('enviar_em')
                               ->load() ?: [];
    }

    /** Cancela um lembrete que ainda nao saiu. */
    public static function cancelar(int $agendamentoId, int $lembreteId): void
    {
        $lembrete = MensagemAgendada::find($lembreteId);

        if (!$lembrete || (int) $lembrete->agendamento_id !== $agendamentoId || $lembrete->situacao !== MensagemAgendada::PENDENTE)
        {
            throw new Exception('Este lembrete não está mais aguardando o envio.');
        }

        self::encerrar($lembrete, MensagemAgendada::CANCELADA, 'Cancelado pelo usuário');
    }

    /** O agendamento deixou de estar confirmado: nada do que estava agendado sai, nem se confirmar de novo. */
    public static function cancelarPendentes(int $agendamentoId, string $motivo): void
    {
        foreach (MensagemAgendada::where('agendamento_id', '=', $agendamentoId)->where('situacao', '=', MensagemAgendada::PENDENTE)->load() ?: [] as $lembrete)
        {
            self::encerrar($lembrete, MensagemAgendada::CANCELADA, $motivo);
        }
    }

    /** Arquivo da trava do envio (os testes seguram a trava para o cron nao pegar o que eles criam). */
    public static function arquivoDaTrava(): string
    {
        return sys_get_temp_dir() . '/curciol-mensagem-agendada-' . md5(getcwd()) . '.lock';
    }

    /** Uma por vez: a anterior ainda enviando = pula esta. */
    public static function processar($param = null): void
    {
        $trava = fopen(self::arquivoDaTrava(), 'c');
        if (!$trava || !flock($trava, LOCK_EX | LOCK_NB))
        {
            return;
        }

        try
        {
            self::rodada();
        }
        finally
        {
            flock($trava, LOCK_UN);
            fclose($trava);
        }
    }

    /** Envia o que venceu. */
    public static function rodada(): void
    {
        TTransaction::open(self::$database);
        $st = TTransaction::get()->prepare("SELECT id FROM mensagem_agendada WHERE situacao = ? AND enviar_em <= ? ORDER BY enviar_em, id LIMIT 50");
        $st->execute([MensagemAgendada::PENDENTE, date('Y-m-d H:i:s')]);
        $ids = $st->fetchAll(PDO::FETCH_COLUMN);
        TTransaction::close();

        foreach ($ids as $id)
        {
            echo date('Y-m-d H:i:s') . " lembrete {$id}: " . self::enviarUm((int) $id) . "\n";
        }
    }

    /** Um lembrete: o que aconteceu. Erro desfaz o envio e fica para a proxima rodada, ate o limite de tentativas. */
    private static function enviarUm(int $id): string
    {
        try
        {
            TTransaction::open(self::$database);

            $lembrete = MensagemAgendada::find($id);

            if (!$lembrete || $lembrete->situacao !== MensagemAgendada::PENDENTE)
            {
                TTransaction::close();
                return 'ja processado';
            }

            $agendamento = Agendamento::find((int) $lembrete->agendamento_id);
            $motivo = self::motivoParaNaoEnviar($agendamento);

            if ($motivo !== null)
            {
                self::encerrar($lembrete, MensagemAgendada::CANCELADA, $motivo);
                TTransaction::close();
                return "cancelado: {$motivo}";
            }

            $mensagem = $lembrete->tipo_mensagem === TemplateEscritorio::EMAIL
                ? MensagemService::enviarEmail($agendamento, $lembrete->titulo, (string) $lembrete->texto, $lembrete->template_escritorio_id, $lembrete->destino)
                : MensagemService::enviarWhatsapp($agendamento, $lembrete->titulo, (string) $lembrete->texto, $lembrete->template_escritorio_id, $lembrete->destino);

            $mensagem->system_user_id = $lembrete->system_user_id;
            $mensagem->store();

            $lembrete->mensagem_id = $mensagem->id;
            self::encerrar($lembrete, MensagemAgendada::ENVIADA, null);

            TTransaction::close();
            return 'enviado';
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            return self::registrarFalha($id, $e->getMessage());
        }
    }

    private static function motivoParaNaoEnviar(?Agendamento $agendamento): ?string
    {
        if (!$agendamento)
        {
            return 'Agendamento não encontrado';
        }
        if ((int) $agendamento->estado_agenda_id !== (int) EstadoAgenda::CONFIRMADO)
        {
            return 'O agendamento não está mais confirmado';
        }
        if ((string) $agendamento->dt_inicial <= date('Y-m-d H:i:s'))
        {
            return 'O horário do agendamento já passou';
        }
        return null;
    }

    private static function registrarFalha(int $id, string $erro): string
    {
        TTransaction::open(self::$database);

        $lembrete = MensagemAgendada::find($id);
        $lembrete->tentativas = (int) $lembrete->tentativas + 1;

        if ((int) $lembrete->tentativas >= self::TENTATIVAS)
        {
            self::encerrar($lembrete, MensagemAgendada::FALHOU, $erro);
        }
        else
        {
            $lembrete->motivo = $erro;
            $lembrete->store();
        }

        TTransaction::close();

        return "falha {$lembrete->tentativas}: {$erro}";
    }

    private static function encerrar(MensagemAgendada $lembrete, string $situacao, ?string $motivo): void
    {
        $lembrete->situacao = $situacao;
        $lembrete->motivo = $motivo;
        $lembrete->processado_em = date('Y-m-d H:i:s');
        $lembrete->store();
    }
}
