<?php
/**
 * Resposta do cliente ao WhatsApp de confirmacao do agendamento (03/11/2026).
 *
 * Tarefa agendada a cada minuto (cmd.php "class=ApchatRespostaService&method=processar"): para cada confirmacao enviada
 * que ainda espera resposta, le no banco do APChat a conversa do numero depois do envio. Regra do usuario (04/11/2026):
 * contam ate 3 mensagens SEGUIDAS do cliente e todas tem de ser so a mesma palavra.
 *   - SIM (so a palavra: sim/Sim/SIM/sIm, com espaco, pontuacao ou emoji em volta): agendamento Confirmado (com o
 *     atendimento, como o Confirmar da equipe) e a mensagem "Resposta SIM" (template WHATSAPP_RESPOSTA_SIM);
 *   - NAO (nao/não/NÃO...): agendamento Cancelado e a mensagem de agradecimento (WHATSAPP_RESPOSTA_NAO);
 *   - frase, saudacao (ate automatica) ou mistura, mesmo com SIM/NAO depois: nada automatico, o agendamento fica em
 *     aberto, a equipe resolve no APChat e o que vier depois nao conta;
 *   - atendente escreveu na conversa antes de completar as 3: tambem nada automatico. E atendente toda mensagem nossa
 *     que nao seja um texto enviado pela Curciol (a API e a copia no canal oficial trazem esse texto).
 * So vale enquanto o agendamento esta Agendado e a data nao passou. Com mais de uma confirmacao para o mesmo numero,
 * vale a ultima enviada (as anteriores ficam "substituidas"). A resposta automatica vai para o mesmo numero da
 * confirmacao (na homologacao, o numero de teste digitado).
 */
class ApchatRespostaService
{
    const CHAVE_CONFIRMACAO = 'WHATSAPP_CONFIRMACAO_AGENDAMENTO';
    const CHAVE_RESPOSTA_SIM = 'WHATSAPP_RESPOSTA_SIM';
    const CHAVE_RESPOSTA_NAO = 'WHATSAPP_RESPOSTA_NAO';

    const SIM = 'SIM';
    const NAO = 'NAO';
    const OUTRA = 'OUTRA';
    const SUBSTITUIDA = 'SUBSTITUIDA';   // outra confirmacao foi enviada depois para o mesmo numero
    const ATENDENTE = 'ATENDENTE';       // atendente escreveu na conversa antes de completar as respostas
    const RESPOSTAS_SEGUIDAS = 3;        // so as 3 mensagens seguidas do cliente contam
    const ENCERRADA = 'ENCERRADA';       // agendamento mudou de estado ou a data passou sem resposta

    private static $database = 'escritorio';

    /**
     * SIM, NAO ou OUTRA: so a palavra conta (maiusculas, acento, espaco, pontuacao e emoji nao importam). A assinatura
     * "*Nome*:" no comeco (cliente que responde por um sistema de atendimento, como o proprio APChat) nao conta.
     */
    public static function classificar(?string $texto): string
    {
        $t = preg_replace('/^\s*\*[^*\n]{1,80}\*:\s*/u', '', (string) $texto);
        $t = mb_strtolower(trim($t));
        $t = strtr($t, ['ã' => 'a', 'á' => 'a', 'à' => 'a', 'â' => 'a', 'í' => 'i', 'î' => 'i', 'õ' => 'o', 'ó' => 'o', 'ô' => 'o']);
        $t = preg_replace('/[^a-z0-9]/', '', $t);

        if ($t === 'sim') { return self::SIM; }
        if ($t === 'nao') { return self::NAO; }
        return self::OUTRA;
    }

    /** Cron. */
    public static function processar($param = null): void
    {
        if (!ApchatBancoService::configurado() || !ApchatBancoService::disponivel())
        {
            echo date('Y-m-d H:i:s') . " banco do APChat indisponivel: " . ApchatBancoService::erro() . "\n";
            return;
        }

        // uma rodada por vez (a anterior ainda rodando = pula esta)
        $trava = fopen(sys_get_temp_dir() . '/curciol-apchat-resposta-' . md5(getcwd()) . '.lock', 'c');
        if (!$trava || !flock($trava, LOCK_EX | LOCK_NB))
        {
            return;
        }

        try
        {
            TTransaction::open(self::$database);
            $pendentes = self::pendentes();
            TTransaction::close();

            foreach ($pendentes as $p)
            {
                $r = self::processarUma($p);
                if ($r !== null) { echo date('Y-m-d H:i:s') . " mensagem {$p['id']} agendamento {$p['agendamento_id']}: {$r}\n"; }
            }
        }
        finally
        {
            flock($trava, LOCK_UN);
            fclose($trava);
        }
    }

    /**
     * Confirmacoes esperando resposta, a mais recente de cada numero; as anteriores do mesmo numero ficam substituidas.
     */
    private static function pendentes(): array
    {
        $c = TTransaction::get();
        $linhas = $c->query("SELECT m.id, m.agendamento_id, m.destino, m.enviado_em, m.system_user_id
                               FROM mensagem m
                               JOIN template_escritorio t ON t.id = m.template_escritorio_id
                              WHERE t.chave = '" . self::CHAVE_CONFIRMACAO . "'
                                AND m.tipo_mensagem = 'WHATSAPP' AND m.resposta IS NULL AND COALESCE(m.destino, '') <> ''
                              ORDER BY m.enviado_em DESC, m.id DESC")->fetchAll(PDO::FETCH_ASSOC);

        $porNumero = [];
        $substituidas = [];
        foreach ($linhas as $l)
        {
            $numero = APChatContactService::numeroCanonico((string) $l['destino']);
            if (isset($porNumero[$numero])) { $substituidas[] = (int) $l['id']; continue; }
            $porNumero[$numero] = $l;
        }

        if ($substituidas)
        {
            $c->exec("UPDATE mensagem SET resposta = '" . self::SUBSTITUIDA . "', respondido_em = now() WHERE id IN (" . implode(',', $substituidas) . ')');
        }

        return array_values($porNumero);
    }

    /** Uma confirmacao: o que aconteceu (null = ainda sem resposta). Erro desfaz tudo e tenta de novo na proxima rodada. */
    private static function processarUma(array $p): ?string
    {
        try
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find((int) $p['agendamento_id']);
            $mensagem = Mensagem::find((int) $p['id']);

            if (!$agendamento || $agendamento->estado_agenda_id != EstadoAgenda::AGENDADO || strtotime((string) $agendamento->dt_inicial) < time())
            {
                $mensagem->resposta = self::ENCERRADA;
                $mensagem->respondido_em = date('Y-m-d H:i:s');
                $mensagem->resposta_obs = $agendamento ? 'Agendamento já não estava Agendado ou a data passou.' : 'Agendamento não existe mais.';
                $mensagem->store();
                TTransaction::close();
                return self::ENCERRADA;
            }

            $conversa = ApchatBancoService::conversaDepois(APChatContactService::variantes((string) $p['destino']), (string) $p['enviado_em']);
            $daCurciol = self::textosDaCurciol((string) $p['destino'], (string) $p['enviado_em']);

            $respostas = [];
            $atendente = false;
            foreach ($conversa as $m)
            {
                if ($m['fromMe'] === true || $m['fromMe'] === 't')
                {
                    if (!in_array(self::textoComparavel($m['body'] ?? ''), $daCurciol, true)) { $atendente = true; break; }
                    continue;
                }
                $respostas[] = $m;
                if (count($respostas) === self::RESPOSTAS_SEGUIDAS) { break; }
            }

            if (!$respostas && !$atendente)
            {
                TTransaction::close();
                return null;
            }

            $todas = mb_substr(implode(' / ', array_map([self::class, 'textoDaResposta'], $respostas)), 0, 2000);

            if ($atendente)
            {
                $mensagem->resposta = self::ATENDENTE;
                $mensagem->resposta_texto = $todas !== '' ? $todas : null;
                $mensagem->respondido_em = date('Y-m-d H:i:s');
                $mensagem->resposta_obs = 'Um atendente escreveu na conversa antes da resposta: nada automático, agendamento em aberto.';
                $mensagem->store();
                TTransaction::close();
                return self::ATENDENTE;
            }

            // a resposta automatica e o historico ficam no nome de quem enviou a confirmacao
            TSession::setValue('userid', (int) $p['system_user_id']);
            TSession::setValue('userunitid', (int) $agendamento->agenda->escritorio->system_unit_id);

            // todas as mensagens seguidas tem de ser so a mesma palavra
            $tipos = array_unique(array_map(function ($r) { return self::classificar($r['body'] ?? ''); }, $respostas));
            $tipo = count($tipos) === 1 ? reset($tipos) : self::OUTRA;
            $mensagem->resposta = $tipo;
            $mensagem->resposta_texto = $todas;
            $mensagem->respondido_em = date('Y-m-d H:i:s', strtotime((string) end($respostas)['createdAt']));

            if ($tipo === self::SIM)
            {
                self::mudarEstado($agendamento, EstadoAgenda::CONFIRMADO);
                if (!Atendimento::where('agendamento_id', '=', $agendamento->id)->first())
                {
                    AgendamentoService::criarAtendimento($agendamento);
                }
                $mensagem->resposta_obs = 'Agendamento confirmado pelo cliente (WhatsApp).';
                self::responder($agendamento, $mensagem, self::CHAVE_RESPOSTA_SIM);
            }
            elseif ($tipo === self::NAO)
            {
                if (AgendamentoFinanceiroService::ficha((int) $agendamento->id))
                {
                    $mensagem->resposta_obs = 'Cliente respondeu NÃO, mas o agendamento tem financeiro gerado: não foi cancelado.';
                }
                else
                {
                    self::mudarEstado($agendamento, EstadoAgenda::CANCELADO);
                    $mensagem->resposta_obs = 'Agendamento cancelado pelo cliente (WhatsApp).';
                    self::responder($agendamento, $mensagem, self::CHAVE_RESPOSTA_NAO);
                }
            }
            else
            {
                $mensagem->resposta_obs = 'Resposta com frase ou diferente de SIM/NÃO: nada automático, agendamento em aberto.';
            }

            $mensagem->store();
            TTransaction::close();

            return $tipo;
        }
        catch (Throwable $e)
        {
            TTransaction::rollback();
            return 'ERRO (tenta de novo): ' . $e->getMessage();
        }
    }

    /** Textos que a Curciol mandou para este numero desde o envio (a API e a copia no canal oficial trazem o mesmo texto). */
    private static function textosDaCurciol(string $destino, string $desde): array
    {
        $st = TTransaction::get()->prepare("SELECT template FROM mensagem WHERE destino = ? AND tipo_mensagem = 'WHATSAPP' AND enviado_em >= ?");
        $st->execute([$destino, $desde]);

        return array_map([self::class, 'textoComparavel'], $st->fetchAll(PDO::FETCH_COLUMN));
    }

    private static function textoComparavel(?string $texto): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $texto));
    }

    /** Texto da mensagem do cliente; audio, imagem etc. pelo tipo. */
    private static function textoDaResposta(array $resposta): string
    {
        return trim((string) ($resposta['body'] ?? '')) ?: ('(' . ($resposta['mediaType'] ?? 'sem texto') . ')');
    }

    private static function mudarEstado(Agendamento $agendamento, int $estado): void
    {
        $agendamento->estado_agenda_id = $estado;
        $agendamento->store();

        $historico = new EstadoAgendamento();
        $historico->agendamento_id = $agendamento->id;
        $historico->estado_agenda_id = $estado;
        $historico->atribuido_em = date('Y-m-d H:i:s');
        $historico->store();
    }

    /** Mensagem automatica de resposta (sem template habilitado, so muda o agendamento). */
    private static function responder(Agendamento $agendamento, Mensagem $confirmacao, string $chave): void
    {
        $template = TemplateEscritorio::where('chave', '=', $chave)
                                      ->where('escritorio_id', '=', (int) $agendamento->agenda->escritorio_id)
                                      ->where('habilitado', '=', 'T')
                                      ->first();
        if (!$template)
        {
            return;
        }

        MensagemService::enviarWhatsapp($agendamento, $template->titulo, (string) $template->template, $template->id, (string) $confirmacao->destino);
    }
}
