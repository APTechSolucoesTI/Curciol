<?php
/**
 * Resposta do cliente ao WhatsApp de confirmacao do agendamento (03/11/2026).
 *
 * Tarefa agendada a cada minuto (cmd.php "class=ApchatRespostaService&method=vigiar"): fica rodando e olha a cada 5 segundos.
 * Para cada confirmacao enviada que ainda espera resposta, le no banco do APChat a conversa do numero depois do envio.
 * As respostas sao do template de confirmacao (Configuracoes > Template, 04/11/2026): a que confirma e a que cancela,
 * ignorando maiusculas, minusculas e acentos ou nao, e a mensagem de cada caso. Contam ate 3 mensagens SEGUIDAS do cliente e todas
 * tem de ser a mesma decisao; espaco, pontuacao e emoji em volta da resposta nao importam.
 *   - resposta que confirma: agendamento Confirmado (com o atendimento, como o Confirmar da equipe) e a mensagem do fluxo;
 *   - resposta que cancela: agendamento Cancelado e a mensagem do fluxo;
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
    const SIM = 'SIM';
    const NAO = 'NAO';
    const OUTRA = 'OUTRA';
    const SUBSTITUIDA = 'SUBSTITUIDA';   // outra confirmacao foi enviada depois para o mesmo numero
    const ATENDENTE = 'ATENDENTE';       // atendente escreveu na conversa antes de completar as respostas
    const RESPOSTAS_SEGUIDAS = 3;        // so as 3 mensagens seguidas do cliente contam
    const ENCERRADA = 'ENCERRADA';       // agendamento mudou de estado ou a data passou sem resposta

    const VIGIA_SEGUNDOS = 55;           // o cron chama a cada minuto; a proxima chamada assume
    const VIGIA_INTERVALO = 5;           // a resposta e lida em ate 5 segundos

    private static $database = 'escritorio';

    /**
     * SIM (confirma), NAO (cancela) ou OUTRA, pelas respostas do fluxo. Vale a resposta inteira; a assinatura "*Nome*:" no
     * comeco (cliente que responde por um sistema de atendimento) e espaco, pontuacao e emoji em volta nao contam.
     */
    public static function classificar(?string $texto, string $confirmar = 'sim', string $cancelar = "não\nnao", bool $ignorarMaiusculas = true): string
    {
        $t = self::respostaComparavel($texto, $ignorarMaiusculas);

        if ($t === '') { return self::OUTRA; }
        if (in_array($t, self::respostasDaLista($confirmar, $ignorarMaiusculas), true)) { return self::SIM; }
        if (in_array($t, self::respostasDaLista($cancelar, $ignorarMaiusculas), true)) { return self::NAO; }
        return self::OUTRA;
    }

    /** As respostas de uma lista (uma por linha), prontas para comparar. */
    public static function respostasDaLista(?string $lista, bool $ignorarMaiusculas): array
    {
        $respostas = array_map(function ($r) use ($ignorarMaiusculas) { return self::respostaComparavel($r, $ignorarMaiusculas); }, preg_split('/\R/u', (string) $lista));

        return array_values(array_unique(array_filter($respostas, 'strlen')));
    }

    /** A resposta pronta para comparar. O ignorar vale para maiusculas, minusculas e acentos. */
    public static function respostaComparavel(?string $texto, bool $ignorarMaiusculas): string
    {
        $t = preg_replace('/^\s*\*[^*\n]{1,80}\*:\s*/u', '', (string) $texto);
        $t = preg_replace('/^[^\p{L}\p{N}]+|[^\p{L}\p{N}]+$/u', '', $t);

        return $ignorarMaiusculas ? mb_strtolower(TratamentosService::removerAcentos($t)) : $t;
    }

    /** A resposta para mostrar: sem a assinatura "*Nome*:" de quem responde por um sistema de atendimento. */
    public static function semAssinatura(?string $texto): string
    {
        return trim(preg_replace('/\*[^*\n]{1,80}\*:\s*/u', '', (string) $texto));
    }

    /** Uma rodada. */
    public static function processar($param = null): void
    {
        self::comTrava(function () { self::rodada(); });
    }

    /** Cron a cada minuto: fica olhando as respostas ate a proxima chamada. */
    public static function vigiar($param = null): void
    {
        // lembretes agendados: uma vez por minuto, sem depender do banco do APChat
        MensagemAgendadaService::processar();

        self::comTrava(function () {
            $fim = time() + self::VIGIA_SEGUNDOS;
            do
            {
                self::rodada();
                if (time() + self::VIGIA_INTERVALO >= $fim) { break; }
                sleep(self::VIGIA_INTERVALO);
            }
            while (true);
        });
    }

    /** Arquivo da trava (os testes seguram a trava para o cron nao pegar o que eles criam). */
    public static function arquivoDaTrava(): string
    {
        return sys_get_temp_dir() . '/curciol-apchat-resposta-' . md5(getcwd()) . '.lock';
    }

    /** Uma por vez: a anterior ainda rodando = pula esta. */
    private static function comTrava(callable $rodar): void
    {
        if (!ApchatBancoService::configurado() || !ApchatBancoService::disponivel())
        {
            echo date('Y-m-d H:i:s') . " banco do APChat indisponivel: " . ApchatBancoService::erro() . "\n";
            return;
        }

        $trava = fopen(self::arquivoDaTrava(), 'c');
        if (!$trava || !flock($trava, LOCK_EX | LOCK_NB))
        {
            return;
        }

        try
        {
            $rodar();
        }
        finally
        {
            flock($trava, LOCK_UN);
            fclose($trava);
        }
    }

    public static function rodada(): void
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

    /**
     * Confirmacoes esperando resposta, a mais recente de cada numero; as anteriores do mesmo numero ficam substituidas.
     */
    private static function pendentes(): array
    {
        $c = TTransaction::get();
        $linhas = $c->query("SELECT m.id, m.agendamento_id, m.destino, m.enviado_em, m.system_user_id,
                                    t.respostas_confirmar, t.respostas_cancelar, t.ignorar_maiusculas, t.template_confirmado_id, t.template_cancelado_id
                               FROM mensagem m
                               JOIN template_escritorio t ON t.id = m.template_escritorio_id
                              WHERE t.categoria = '" . TemplateEscritorio::CONFIRMACAO . "'
                                AND (COALESCE(btrim(t.respostas_confirmar), '') <> '' OR COALESCE(btrim(t.respostas_cancelar), '') <> '')
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
            $tipos = array_unique(array_map(function ($r) use ($p) {
                return self::classificar($r['body'] ?? '', (string) $p['respostas_confirmar'], (string) $p['respostas_cancelar'], $p['ignorar_maiusculas'] !== 'F');
            }, $respostas));
            $tipo = count($tipos) === 1 ? reset($tipos) : self::OUTRA;
            $mensagem->resposta = $tipo;
            $mensagem->resposta_texto = $todas;
            $mensagem->respondido_em = date('Y-m-d H:i:s', strtotime((string) end($respostas)['createdAt']));

            $quem = PessoaContatoService::formatarTelefone($p['destino']) . ' respondeu "' . self::semAssinatura($todas) . '"';

            if ($tipo === self::SIM)
            {
                AgendamentoService::respostaDoCliente($agendamento, (int) EstadoAgenda::CONFIRMADO, EstadoAgendamento::EVENTO_CONFIRMADO_WHATSAPP, $quem);
                $mensagem->resposta_obs = 'Agendamento confirmado pelo cliente (WhatsApp).';
                self::responder($agendamento, $mensagem, (int) $p['template_confirmado_id']);
            }
            elseif ($tipo === self::NAO)
            {
                if (AgendamentoFinanceiroService::ficha((int) $agendamento->id))
                {
                    $mensagem->resposta_obs = 'Cliente respondeu que cancela, mas o agendamento tem financeiro gerado: não foi cancelado.';
                }
                else
                {
                    AgendamentoService::respostaDoCliente($agendamento, (int) EstadoAgenda::CANCELADO, EstadoAgendamento::EVENTO_CANCELADO_WHATSAPP, $quem);
                    $mensagem->resposta_obs = 'Agendamento cancelado pelo cliente (WhatsApp).';
                    self::responder($agendamento, $mensagem, (int) $p['template_cancelado_id']);
                }
            }
            else
            {
                $mensagem->resposta_obs = 'Resposta fora das respostas válidas: nada automático, agendamento em aberto.';
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

    /** Mensagem automatica do fluxo (sem template, ou desabilitado, so muda o agendamento). */
    private static function responder(Agendamento $agendamento, Mensagem $confirmacao, int $templateId): void
    {
        $template = $templateId ? TemplateEscritorio::find($templateId) : null;

        if (!$template || $template->habilitado != 'T')
        {
            return;
        }

        MensagemService::enviarWhatsapp($agendamento, $template->titulo, (string) $template->template, $template->id, (string) $confirmacao->destino);
    }
}
