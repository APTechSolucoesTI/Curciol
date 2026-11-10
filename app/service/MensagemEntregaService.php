<?php
/**
 * Entrega do WhatsApp enviado pela Curciol (10/11/2026), no cron da fila (a cada 5 min).
 *
 * Toda mensagem de WhatsApp enviada fica "aguardando entrega" (A). Quando a mesma mensagem no APChat ganha os 2 tiques (ack >= 2)
 * vira "entregue" (E). Se passar 24 h sem os 2 tiques vira "nao entregue" (N) e avisa no sino quem enviou, o colaborador da agenda
 * e quem criou o agendamento (sem repetir). O agendamento nao muda de estado. Celular desligado fica com 1 tique por horas: por
 * isso so 24 h contam como nao entregue.
 *
 * Casamento com a mensagem do APChat: pelo id que a API devolveu no envio; senao, pelo ticket que ela devolveu (a primeira
 * mensagem nossa daquele ticket - a resposta do APChat traz so o ticket); senao pelo numero (com e sem o 9), mensagem nossa
 * (fromMe), mesmo texto (espacos ignorados) e horario entre 2 min antes e 30 min depois do envio. APChat fora: nada muda (nem
 * vira "nao entregue" sem conferir).
 */
class MensagemEntregaService
{
    const HORAS = 24;
    const POR_RODADA = 300;

    /** id da mensagem na resposta da API de envio (quando vem). */
    public static function idDaResposta(string $corpo): ?string
    {
        $json = json_decode($corpo, true);
        if (!is_array($json)) { return null; }
        foreach ([$json['data']['id'] ?? null, $json['data']['messageId'] ?? null, $json['messageId'] ?? null, $json['message']['id'] ?? null] as $v)
        {
            if (is_scalar($v) && trim((string) $v) !== '') { return mb_substr(trim((string) $v), 0, 255); }
        }
        return null;
    }

    /** ticket na resposta da API de envio ({"data":{"ticketId":N}}). */
    public static function ticketDaResposta(string $corpo): ?int
    {
        $json = json_decode($corpo, true);
        if (!is_array($json)) { return null; }
        foreach ([$json['data']['ticketId'] ?? null, $json['ticketId'] ?? null] as $v)
        {
            if (is_numeric($v) && (int) $v > 0) { return (int) $v; }
        }
        return null;
    }

    public static function rodada(): array
    {
        $r = ['ok' => true, 'entregues' => 0, 'nao_entregues' => 0, 'aguardando' => 0];

        TTransaction::open('escritorio');
        $ids = TTransaction::get()->query("SELECT id FROM mensagem WHERE tipo_mensagem = 'WHATSAPP' AND entrega = 'A' ORDER BY enviado_em, id LIMIT " . self::POR_RODADA)->fetchAll(PDO::FETCH_COLUMN);
        TTransaction::close();
        if (!$ids) { return $r; }

        try
        {
            $apchat = ApchatBancoService::configurado() && ApchatBancoService::disponivel();
        }
        catch (Throwable $e)
        {
            $apchat = false;
        }
        if (!$apchat)
        {
            return ['ok' => false, 'aguardando' => count($ids)] + $r;
        }

        foreach ($ids as $id)
        {
            try
            {
                TTransaction::open('escritorio');
                $m = Mensagem::find((int) $id);
                [$idApchat, $ack] = self::conferir($m);
                if ($idApchat !== null && $idApchat !== (string) $m->apchat_mensagem_id)
                {
                    $m->apchat_mensagem_id = $idApchat;
                }
                if ($ack !== null && $ack >= 2)
                {
                    $m->entrega = 'E';
                    $m->entrega_em = date('Y-m-d H:i:s');
                    $r['entregues']++;
                }
                elseif (strtotime((string) $m->enviado_em) < time() - self::HORAS * 3600)
                {
                    $m->entrega = 'N';
                    $m->entrega_em = date('Y-m-d H:i:s');
                    $r['nao_entregues']++;
                }
                else
                {
                    $r['aguardando']++;
                }
                $m->store();
                $naoEntregue = $m->entrega === 'N' ? $m : null;
                TTransaction::close();
                if ($naoEntregue) { self::avisar($naoEntregue); }
            }
            catch (Throwable $e)
            {
                TTransaction::rollback();
            }
        }

        return $r;
    }

    /** Quem e avisado: quem enviou, o colaborador da agenda e quem criou o agendamento (primeiro estado), sem repetir. Transacao aberta. */
    public static function destinatarios(Agendamento $agendamento, ?int $remetente): array
    {
        $colaborador = $agendamento->agenda && $agendamento->agenda->profissional ? (int) $agendamento->agenda->profissional->system_users_id : 0;
        $st = TTransaction::get()->prepare('SELECT system_users_id FROM estado_agendamento WHERE agendamento_id = ? ORDER BY atribuido_em, id LIMIT 1');
        $st->execute([(int) $agendamento->id]);
        $criador = (int) $st->fetchColumn();
        return array_values(array_unique(array_filter([(int) $remetente, $colaborador, $criador])));
    }

    /** [id no APChat, tiques] da mensagem, ou [null, null] sem casamento. Transacao aberta. */
    private static function conferir(Mensagem $m): array
    {
        $destino = preg_replace('/\D/', '', (string) $m->destino);
        $destino = strlen($destino) <= 11 ? '55' . $destino : $destino;
        $enviado = strtotime((string) $m->enviado_em);
        $lista = ApchatBancoService::mensagensEnviadasPara(APChatContactService::variantes($destino), date('Y-m-d H:i:s', $enviado - 120), date('Y-m-d H:i:s', $enviado + 1800));

        $texto = self::comparavel((string) $m->template);
        foreach ($lista as $l)
        {
            if ($m->apchat_mensagem_id)
            {
                $casa = in_array((string) $m->apchat_mensagem_id, [(string) $l['id'], (string) ($l['messageId'] ?? '')], true);
            }
            elseif ($m->apchat_ticket_id)
            {
                $casa = (int) ($l['ticketId'] ?? 0) === (int) $m->apchat_ticket_id;
            }
            else
            {
                $casa = self::comparavel((string) $l['body']) === $texto;
            }
            if ($casa)
            {
                return [(string) $l['id'], isset($l['ack']) ? (int) $l['ack'] : null];
            }
        }
        return [null, null];
    }

    private static function avisar(Mensagem $m): void
    {
        TTransaction::open('escritorio');
        $agendamento = Agendamento::find((int) $m->agendamento_id);
        $para = $agendamento ? self::destinatarios($agendamento, $m->system_user_id ? (int) $m->system_user_id : null) : [];
        $cliente = $agendamento && $agendamento->cliente ? (string) $agendamento->cliente->nome : '';
        TTransaction::close();

        $trecho = mb_substr(trim(preg_replace('/\s+/', ' ', (string) $m->template)), 0, 120);
        foreach ($para as $usuario)
        {
            SystemNotification::register($usuario, 'WhatsApp não entregue',
                "A mensagem de WhatsApp para {$cliente} (agendamento #{$m->agendamento_id}) não foi entregue em 24 horas: \"{$trecho}\"",
                new TAction(['AgendamentoFormView', 'onShow'], ['key' => (int) $m->agendamento_id]), 'Ver agendamento', 'fab fa-whatsapp');
        }
    }

    private static function comparavel(string $texto): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $texto)));
    }
}
