<?php
/**
 * Conversas do APMail no historico do cliente (12/11/2026). So le o banco da Curciol (a sincronizacao grava): a tela do cliente
 * nunca le e-mails no APMail. Usa a transacao aberta ('escritorio').
 *
 * - e-mails do cliente: o do cadastro e os dos contatos (minusculas); homologacao: so os de 'historico_somente_emails';
 * - conversas: as que tem algum desses e-mails entre os participantes, decidido na hora (cadastrar um e-mail traz as antigas);
 * - ACESSO (12/11/2026, mesmas regras do APMail): cada usuario so ve as conversas das caixas que ele acessa no APMail. As caixas vem
 *   das notificacoes do APMail para o e-mail do usuario (Usuarios das integracoes), guardadas 60 s em apmail_usuario.caixas: mudou o
 *   acesso no APMail, muda aqui em ate 1 minuto. 404 (usuario fora do APMail) = nenhuma; APMail fora do ar = ultimo acesso conhecido;
 * - mensagens da conversa: da mais recente para a mais antiga;
 * - profissionais (tela da conversa): o responsavel atual e quem enviou, sem repetir (mesma regra do APChat: a lista mostra so o
 *   responsavel).
 */
class ApmailHistoricoService
{
    const ACESSO_SEGUNDOS = 60;

    /** @var array|null testes: caixas fixas (null = as do usuario logado) */
    private static $caixasForcadas = null;

    /** Testes: caixas que o "usuario" acessa (null = ler do APMail pelo usuario logado). */
    public static function usarCaixas(?array $caixas): void
    {
        self::$caixasForcadas = $caixas;
    }

    public static function emailsDoCliente(int $pessoaId): array
    {
        $st = TTransaction::get()->prepare("SELECT email FROM pessoa WHERE id = ? UNION ALL SELECT email FROM pessoa_contato WHERE pessoa_id = ?");
        $st->execute([$pessoaId, $pessoaId]);
        $emails = [];
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $e)
        {
            $e = mb_strtolower(trim((string) $e));
            if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) { $emails[$e] = true; }
        }
        $emails = array_keys($emails);
        $somente = ApmailSincronizacaoService::somenteEmails();
        if ($somente !== null) { $emails = array_values(array_intersect($emails, $somente)); }
        sort($emails);
        return $emails;
    }

    /**
     * Ids das caixas que o usuario da Curciol acessa no APMail (notificacoes do e-mail dele). Guardado 60 s; 404 = nenhuma;
     * APMail fora do ar = o ultimo conhecido (nunca conhecido = nenhuma).
     */
    public static function caixasDoUsuario(int $systemUserId): array
    {
        if ($systemUserId <= 0) { return []; }
        $c = TTransaction::get();
        $st = $c->prepare("SELECT caixas, caixas_em > now() - (? || ' seconds')::interval AS recente FROM apmail_usuario WHERE system_user_id = ?");
        $st->execute([self::ACESSO_SEGUNDOS, $systemUserId]);
        $l = $st->fetch(PDO::FETCH_ASSOC);
        $guardadas = $l && $l['caixas'] !== null ? (array) json_decode((string) $l['caixas'], true) : null;
        if ($guardadas !== null && in_array($l['recente'], [true, 't', 1, '1'], true)) { return $guardadas; }

        $escritorio = ApmailApiService::escritorio();
        $token = $escritorio ? trim((string) $escritorio->token_apmail) : '';
        $email = ApmailUsuarioService::email($systemUserId);
        if ($token === '' || $email === '') { return $guardadas ?? []; }
        try
        {
            $n = ApmailApiService::notificacoes(ApmailApiService::url($escritorio), $token, $email);
            $ids = array_values(array_unique(array_filter(array_column($n['caixas'], 'id'), function ($id) { return self::uuid($id); })));
        }
        catch (ApmailErro $e)
        {
            if (!in_array((int) $e->getCode(), [400, 404], true)) { return $guardadas ?? []; }
            $ids = []; // usuario fora do APMail ou nao liberado na integracao
        }
        catch (Throwable $e)
        {
            return $guardadas ?? [];
        }
        $c->prepare("INSERT INTO apmail_usuario (system_user_id, avisos, caixas, caixas_em, atualizado_em) VALUES (?, 'S', ?::jsonb, now(), now())
                     ON CONFLICT (system_user_id) DO UPDATE SET caixas = EXCLUDED.caixas, caixas_em = now()")
          ->execute([$systemUserId, json_encode($ids)]);
        return $ids;
    }

    /** Conversas do cliente nas caixas que o usuario logado acessa no APMail, mais recente primeiro. */
    public static function conversas(int $pessoaId): array
    {
        return self::consultar($pessoaId, self::caixasPermitidas());
    }

    /** {conversa, mensagens (mais recente primeiro), profissionais}. So conversa do proprio cliente e de caixa que o usuario acessa. */
    public static function conversa(int $conversaId, int $pessoaId): array
    {
        $da = null;
        foreach (self::conversas($pessoaId) as $c)
        {
            if ((int) $c['id'] === $conversaId) { $da = $c; break; }
        }
        if (!$da)
        {
            foreach (self::consultar($pessoaId, null) as $c)
            {
                if ((int) $c['id'] === $conversaId)
                {
                    throw new Exception('Você não tem acesso, no APMail, à caixa desta conversa de e-mail.');
                }
            }
            throw new Exception('Esta conversa de e-mail não é deste cliente.');
        }
        $conversa = TTransaction::get()->query('SELECT * FROM apmail_conversa WHERE id = ' . (int) $conversaId)->fetch(PDO::FETCH_ASSOC);
        $conversa['ultima_mensagem_apmail_id'] = $da['ultima_mensagem_apmail_id'];
        $st = TTransaction::get()->prepare('SELECT * FROM apmail_mensagem WHERE conversa_id = ? ORDER BY mensagem_em DESC, id DESC');
        $st->execute([$conversaId]);
        $mensagens = $st->fetchAll(PDO::FETCH_ASSOC);
        $profissionais = [];
        foreach (array_merge([$conversa['responsavel_nome'] ?? null], array_column($mensagens, 'enviado_por_nome')) as $nome)
        {
            $nome = trim((string) $nome);
            if ($nome !== '') { $profissionais[$nome] = true; }
        }
        return ['conversa' => $conversa, 'mensagens' => $mensagens, 'profissionais' => array_keys($profissionais)];
    }

    private static function caixasPermitidas(): array
    {
        if (self::$caixasForcadas !== null) { return self::$caixasForcadas; }
        return self::caixasDoUsuario((int) TSession::getValue('userid'));
    }

    /** @param array|null $caixas null = sem filtro de acesso (so para dizer "sem acesso" em vez de "nao e deste cliente") */
    private static function consultar(int $pessoaId, ?array $caixas): array
    {
        $emails = self::emailsDoCliente($pessoaId);
        if (!$emails) { return []; }
        $filtro = '';
        $params = [self::arrayPg($emails)];
        if ($caixas !== null)
        {
            $caixas = array_values(array_filter(array_map('strtolower', $caixas), function ($id) { return self::uuid($id); }));
            if (!$caixas) { return []; }
            $filtro = ' AND c.caixa_id = ANY (?::uuid[])';
            $params[] = '{' . implode(',', $caixas) . '}';
        }
        $st = TTransaction::get()->prepare('SELECT c.id, c.thread_id, c.assunto, c.caixa_id, c.caixa_nome, c.inicio_em, c.fim_em, c.responsavel_nome, c.qtd_mensagens,
                                                   (SELECT m.apmail_id FROM apmail_mensagem m WHERE m.conversa_id = c.id ORDER BY m.mensagem_em DESC, m.id DESC LIMIT 1) AS ultima_mensagem_apmail_id
                                              FROM apmail_conversa c
                                             WHERE EXISTS (SELECT 1 FROM apmail_conversa_email e WHERE e.conversa_id = c.id AND e.email = ANY (?::text[]))' . $filtro . '
                                             ORDER BY c.inicio_em DESC NULLS LAST, c.id DESC LIMIT 500');
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function arrayPg(array $textos): string
    {
        return '{' . implode(',', array_map(function ($e) { return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $e) . '"'; }, $textos)) . '}';
    }

    private static function uuid($id): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', (string) $id);
    }
}
