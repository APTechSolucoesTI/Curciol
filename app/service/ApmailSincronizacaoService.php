<?php
/**
 * Sincronizacao das conversas do APMail para o historico do cliente (12/11/2026). Cron a cada 5 minutos:
 *   cmd.php "class=ApmailSincronizacaoService&method=processar"
 *
 * - pagina por pagina da listagem por cursor (recebidos e enviados, direction=all), junta as conversas (threadId) que mudaram;
 * - para cada conversa, le a conversa inteira (/threads, com corpo) e grava: apmail_conversa, apmail_mensagem (upsert por id do
 *   APMail, sem duplicar) e apmail_conversa_email (participantes, sem os e-mails das caixas);
 * - o cursor so e salvo DEPOIS de gravar a pagina inteira: se cair no meio, a proxima rodada refaz o trecho sem duplicar;
 * - homologacao: com 'historico_somente_emails' em app/config/apmail.php, so grava as conversas desses e-mails (as outras sao
 *   ignoradas e o cursor avanca igual); sem a chave (producao), todas;
 * - erro do APMail (fora do ar, 429, 401...): registra em apmail_sincronizacao.ultimo_erro e para; nada e apagado;
 * - trava de arquivo: duas rodadas nunca correm juntas. Anexos: so a quantidade.
 */
class ApmailSincronizacaoService
{
    const CONFIG = 'app/config/apmail.php';
    const MAX_PAGINAS = 20;
    const FUSO = 'America/Sao_Paulo';

    private static $config = self::CONFIG;
    private static $trava = null;
    private static $credenciais = null;

    /** Testes: outro arquivo de configuracao (null = app/config/apmail.php). */
    public static function usarConfig(?string $arquivo): void
    {
        self::$config = $arquivo ?? self::CONFIG;
    }

    /** Testes: outro arquivo de trava. */
    public static function usarTrava(?string $arquivo): void
    {
        self::$trava = $arquivo;
    }

    /** Testes: ['url', 'token', 'email'] no lugar do escritorio (o cursor real nunca e tocado). */
    public static function usarCredenciais(?array $credenciais): void
    {
        self::$credenciais = $credenciais;
    }

    /** Homologacao: os e-mails liberados (minusculas) da chave 'historico_somente_emails'; null = todos. */
    public static function somenteEmails(): ?array
    {
        if (!is_file(self::$config)) { return null; }
        $c = include self::$config;
        $lista = is_array($c) ? ($c['historico_somente_emails'] ?? null) : null;
        if (!is_array($lista)) { return null; }
        return array_values(array_unique(array_filter(array_map(function ($e) { return mb_strtolower(trim((string) $e)); }, $lista), 'strlen')));
    }

    /** Usuario de integracao: o do escritorio; vazio = o e-mail APMail do primeiro usuario do grupo Admin. Usa a transacao aberta. */
    public static function usuarioIntegracao(?Escritorio $escritorio): string
    {
        $email = mb_strtolower(trim((string) ($escritorio->apmail_email_integracao ?? '')));
        if ($email !== '') { return $email; }
        $ids = TTransaction::get()->query("SELECT u.id FROM system_users u JOIN system_user_group g ON g.system_user_id = u.id AND g.system_group_id = 1
                                            WHERE u.active = 'Y' ORDER BY u.id")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($ids as $id)
        {
            $e = ApmailUsuarioService::email((int) $id);
            if ($e !== '') { return mb_strtolower($e); }
        }
        return '';
    }

    /** @return array {conversas, mensagens, ignoradas, erro} */
    public static function processar($param = null): array
    {
        $r = ['conversas' => 0, 'mensagens' => 0, 'ignoradas' => 0, 'erro' => null];
        $h = @fopen(self::$trava ?? sys_get_temp_dir() . '/curciol-apmail-sinc.lock', 'c');
        if (!$h || !flock($h, LOCK_EX | LOCK_NB))
        {
            if ($h) { fclose($h); }
            $r['erro'] = 'ocupado';
            return $r;
        }
        $chave = null;
        try
        {
            [$url, $token, $email] = self::credenciais();
            if ($token === '' || $email === '')
            {
                $r['erro'] = 'APMail sem token ou sem usuário de integração no escritório.';
                return $r;
            }
            $chave = sha1($token . '|' . $email . '|all');
            $somente = self::somenteEmails();
            $cursor = self::cursor($chave);
            $vistas = [];
            for ($pagina = 0; $pagina < self::MAX_PAGINAS; $pagina++)
            {
                $p = ApmailApiService::mensagens($url, $token, $email, $cursor);
                foreach ($p['itens'] as $item)
                {
                    $thread = (string) ($item['threadId'] ?? '');
                    if ($thread === '' || isset($vistas[$thread])) { continue; }
                    $vistas[$thread] = true;
                    $mensagens = ApmailApiService::conversa($url, $token, $email, $thread);
                    $n = self::gravar($thread, $mensagens, $somente);
                    if ($n === null) { $r['ignoradas']++; } else { $r['conversas']++; $r['mensagens'] += $n; }
                }
                if ($p['proximo'] !== null) { $cursor = $p['proximo']; }
                self::salvar($chave, $cursor, null);
                if (!$p['mais']) { break; }
            }
        }
        catch (Throwable $e)
        {
            $r['erro'] = $e->getMessage() !== '' ? $e->getMessage() : get_class($e);
            if ($chave !== null) { self::salvar($chave, null, $r['erro']); }
        }
        finally
        {
            flock($h, LOCK_UN);
            fclose($h);
        }
        return $r;
    }

    /** [url, token, email do usuario de integracao]. */
    private static function credenciais(): array
    {
        if (self::$credenciais !== null)
        {
            return [(string) self::$credenciais['url'], (string) self::$credenciais['token'], mb_strtolower((string) self::$credenciais['email'])];
        }
        TTransaction::open('escritorio');
        try
        {
            $e = ApmailApiService::escritorio();
            return [ApmailApiService::url($e), trim((string) ($e->token_apmail ?? '')), self::usuarioIntegracao($e)];
        }
        finally
        {
            TTransaction::close();
        }
    }

    private static function cursor(string $chave): ?string
    {
        TTransaction::open('escritorio');
        $st = TTransaction::get()->prepare('SELECT cursor FROM apmail_sincronizacao WHERE chave = ?');
        $st->execute([$chave]);
        $c = $st->fetchColumn();
        TTransaction::close();
        return $c !== false && $c !== null && $c !== '' ? (string) $c : null;
    }

    /** Grava o cursor (null = mantem) e o erro da rodada (null = limpa). */
    private static function salvar(string $chave, ?string $cursor, ?string $erro): void
    {
        TTransaction::open('escritorio');
        $st = TTransaction::get()->prepare('INSERT INTO apmail_sincronizacao (chave, cursor, atualizado_em, ultima_rodada_em, ultimo_erro) VALUES (?, ?, now(), now(), ?)
                                            ON CONFLICT (chave) DO UPDATE SET cursor = COALESCE(EXCLUDED.cursor, apmail_sincronizacao.cursor),
                                                   atualizado_em = now(), ultima_rodada_em = now(), ultimo_erro = EXCLUDED.ultimo_erro');
        $st->execute([$chave, $cursor, $erro !== null ? mb_substr($erro, 0, 500) : null]);
        TTransaction::close();
    }

    /** Grava a conversa inteira. @return int|null mensagens gravadas; null = ignorada (nenhum participante liberado). */
    private static function gravar(string $thread, array $mensagens, ?array $somente): ?int
    {
        if (!$mensagens) { return 0; }
        usort($mensagens, function ($a, $b) { return strcmp((string) ($a['messageAt'] ?? ''), (string) ($b['messageAt'] ?? '')) ?: strcmp((string) $a['id'], (string) $b['id']); });
        $participantes = self::participantes($mensagens);
        if ($somente !== null && !array_intersect($participantes, $somente)) { return null; }

        $primeira = $mensagens[0];
        $ultima = end($mensagens);
        TTransaction::open('escritorio');
        try
        {
            $c = TTransaction::get();
            $st = $c->prepare('INSERT INTO apmail_conversa (thread_id, assunto, caixa_id, caixa_nome, caixa_email, inicio_em, fim_em, responsavel_nome, responsavel_email,
                                                            situacao, qtd_mensagens, atualizado_em)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, now())
                               ON CONFLICT (thread_id) DO UPDATE SET assunto = EXCLUDED.assunto, caixa_id = EXCLUDED.caixa_id, caixa_nome = EXCLUDED.caixa_nome,
                                      caixa_email = EXCLUDED.caixa_email, inicio_em = EXCLUDED.inicio_em, fim_em = EXCLUDED.fim_em, responsavel_nome = EXCLUDED.responsavel_nome,
                                      responsavel_email = EXCLUDED.responsavel_email, situacao = EXCLUDED.situacao, qtd_mensagens = EXCLUDED.qtd_mensagens, atualizado_em = now()
                               RETURNING id');
            $st->execute([strtolower($thread), self::texto($primeira['subject'] ?? $ultima['subject'] ?? null), self::uuid($ultima['mailbox']['id'] ?? null),
                          self::texto($ultima['mailbox']['name'] ?? null), self::email($ultima['mailbox']['email'] ?? null),
                          self::data($primeira['messageAt'] ?? null), self::data($ultima['messageAt'] ?? null),
                          self::texto($ultima['assignedToUser']['name'] ?? null), self::email($ultima['assignedToUser']['email'] ?? null),
                          self::texto($ultima['status'] ?? null), count($mensagens)]);
            $conversaId = (int) $st->fetchColumn();

            $m = $c->prepare('INSERT INTO apmail_mensagem (apmail_id, conversa_id, direcao, assunto, de_nome, de_email, para, cc, mensagem_em, enviado_por_nome,
                                                           enviado_por_email, corpo_html, corpo_texto, qtd_anexos, qtd_imagens_corpo, tem_convite, importado_em)
                              VALUES (?, ?, ?, ?, ?, ?, ?::jsonb, ?::jsonb, ?, ?, ?, ?, ?, ?, ?, ?, now())
                              ON CONFLICT (apmail_id) DO UPDATE SET conversa_id = EXCLUDED.conversa_id, direcao = EXCLUDED.direcao, assunto = EXCLUDED.assunto,
                                     de_nome = EXCLUDED.de_nome, de_email = EXCLUDED.de_email, para = EXCLUDED.para, cc = EXCLUDED.cc, mensagem_em = EXCLUDED.mensagem_em,
                                     enviado_por_nome = EXCLUDED.enviado_por_nome, enviado_por_email = EXCLUDED.enviado_por_email,
                                     corpo_html = COALESCE(EXCLUDED.corpo_html, apmail_mensagem.corpo_html), corpo_texto = COALESCE(EXCLUDED.corpo_texto, apmail_mensagem.corpo_texto),
                                     qtd_anexos = EXCLUDED.qtd_anexos, qtd_imagens_corpo = EXCLUDED.qtd_imagens_corpo, tem_convite = EXCLUDED.tem_convite');
            foreach ($mensagens as $x)
            {
                $id = self::uuid($x['id'] ?? null);
                if ($id === null) { continue; }
                $corpo = is_array($x['body'] ?? null) ? $x['body'] : [];
                $arq = self::arquivos($x);
                $m->execute([$id, $conversaId, ($x['direction'] ?? '') === 'outbound' ? 'outbound' : 'inbound', self::texto($x['subject'] ?? null),
                             self::texto($x['from']['name'] ?? null), self::email($x['from']['email'] ?? null), self::lista($x['to'] ?? []), self::lista($x['cc'] ?? []),
                             self::data($x['messageAt'] ?? null), self::texto($x['sentByUser']['name'] ?? null), self::email($x['sentByUser']['email'] ?? null),
                             isset($corpo['sanitizedHtml']) ? (string) $corpo['sanitizedHtml'] : (isset($corpo['html']) ? (string) $corpo['html'] : null),
                             isset($corpo['text']) ? (string) $corpo['text'] : null, $arq['anexos'], $arq['imagens_corpo'], $arq['convite'] ? 'true' : 'false']);
            }

            $c->prepare('DELETE FROM apmail_conversa_email WHERE conversa_id = ?')->execute([$conversaId]);
            $e = $c->prepare('INSERT INTO apmail_conversa_email (conversa_id, email) VALUES (?, ?) ON CONFLICT DO NOTHING');
            foreach ($participantes as $p) { $e->execute([$conversaId, $p]); }
            TTransaction::close();
            return count($mensagens);
        }
        catch (Throwable $erro)
        {
            TTransaction::rollback();
            throw $erro;
        }
    }

    /**
     * Separa os arquivos do e-mail (12/11/2026): imagem do corpo (assinatura/logo: inline ou com contentId) e convite de agenda
     * (.ics/text/calendar, conta uma vez) nao sao anexo; confirmacao de leitura (message/disposition-notification) e tecnica e nao conta.
     * Sem a lista de arquivos (corpo grande demais no APMail): usa attachmentCount.
     *
     * @return array {anexos: int, imagens_corpo: int, convite: bool}
     */
    private static function arquivos(array $x): array
    {
        if (!isset($x['attachments']) || !is_array($x['attachments']))
        {
            return ['anexos' => (int) ($x['attachmentCount'] ?? 0), 'imagens_corpo' => 0, 'convite' => false];
        }
        $r = ['anexos' => 0, 'imagens_corpo' => 0, 'convite' => false];
        foreach ($x['attachments'] as $a)
        {
            $tipo = strtolower(trim((string) ($a['contentType'] ?? '')));
            $nome = strtolower((string) ($a['filename'] ?? ''));
            if (strpos($tipo, 'image/') === 0 && (!empty($a['inline']) || trim((string) ($a['contentId'] ?? '')) !== ''))
            {
                $r['imagens_corpo']++;
            }
            elseif ($tipo === 'text/calendar' || $tipo === 'application/ics' || substr($nome, -4) === '.ics')
            {
                $r['convite'] = true;
            }
            elseif ($tipo === 'message/disposition-notification' || $tipo === 'message/delivery-status')
            {
                continue;
            }
            else
            {
                $r['anexos']++;
            }
        }
        return $r;
    }

    /** E-mails de remetente, destinatarios e Cc (minusculas), sem os das caixas do APMail. */
    private static function participantes(array $mensagens): array
    {
        $caixas = [];
        $todos = [];
        foreach ($mensagens as $x)
        {
            if ($cx = self::email($x['mailbox']['email'] ?? null)) { $caixas[$cx] = true; }
            foreach (array_merge([$x['from'] ?? []], (array) ($x['to'] ?? []), (array) ($x['cc'] ?? [])) as $p)
            {
                if ($e = self::email(is_array($p) ? ($p['email'] ?? null) : null)) { $todos[$e] = true; }
            }
        }
        return array_values(array_diff(array_keys($todos), array_keys($caixas)));
    }

    private static function lista($enderecos): string
    {
        $r = [];
        foreach ((array) $enderecos as $p)
        {
            if (!is_array($p)) { continue; }
            $e = self::email($p['email'] ?? null);
            if ($e) { $r[] = ['nome' => trim((string) ($p['name'] ?? '')), 'email' => $e]; }
        }
        return json_encode($r, JSON_UNESCAPED_UNICODE);
    }

    private static function email($v): ?string
    {
        $e = mb_strtolower(trim((string) $v));
        return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : null;
    }

    private static function uuid($v): ?string
    {
        $u = strtolower(trim((string) $v));
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $u) ? $u : null;
    }

    private static function texto($v): ?string
    {
        $t = trim((string) $v);
        return $t !== '' ? mb_substr($t, 0, 1000) : null;
    }

    /** ISO 8601 (UTC) -> horario de Brasilia (Y-m-d H:i:s). */
    private static function data($v): ?string
    {
        if (trim((string) $v) === '') { return null; }
        try
        {
            return (new DateTime((string) $v))->setTimezone(new DateTimeZone(self::FUSO))->format('Y-m-d H:i:s');
        }
        catch (Throwable $e)
        {
            return null;
        }
    }
}
