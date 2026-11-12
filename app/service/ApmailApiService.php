<?php
/**
 * API de notificacoes do APMail (10/11/2026). So le: nao marca nada como lido, por isso funciona tambem na homologacao.
 *   GET {url}/api/integrations/notifications?email=...   Authorization: Bearer {token}
 * Resposta: data.unread / data.pending (contagens independentes, nao somar) e data.mailboxes (caixas autorizadas, inclusive as
 * zeradas; url relativa). Erros: 400, 401 (token), 403 (rota nao autorizada), 404 (usuario fora do escopo), 429 (Retry-After), 5xx.
 * Endereco e token no escritorio (Escritorio > Configuracoes > APMail). Limite do APMail: 1.200 consultas por minuto por token.
 */
class ApmailApiService
{
    const URL_PADRAO = 'https://app.apmail.com.br';
    const ROTA = '/api/integrations/notifications';
    const ROTA_MENSAGENS = '/api/integrations/messages';
    const ROTA_CONVERSAS = '/api/integrations/threads/';
    const TIMEOUT_CONEXAO = 5;
    const TIMEOUT_TOTAL = 8;

    /**
     * Substitui a chamada HTTP (testes). Recebe (url, token) e devolve ['status' => int, 'corpo' => string, 'erro' => string,
     * 'retry_after' => int|null].
     *
     * @var callable|null
     */
    private static $transporte = null;

    public static function usarTransporte(?callable $transporte): void
    {
        self::$transporte = $transporte;
    }

    /** Escritorio da unidade logada; sem token nele, o primeiro escritorio com token do APMail. Usa a transacao aberta. */
    public static function escritorio(): ?Escritorio
    {
        $unidade = (int) (TSession::getValue('userunitid') ?? 0);
        $escritorio = $unidade > 0 ? Escritorio::where('system_unit_id', '=', $unidade)->first() : null;
        if (!$escritorio || trim((string) $escritorio->token_apmail) === '')
        {
            $escritorio = Escritorio::where('token_apmail', 'is not', null)->where('token_apmail', '<>', '')->orderBy('id')->first();
        }
        return $escritorio ?: null;
    }

    public static function url(?Escritorio $escritorio): string
    {
        $url = rtrim(trim((string) ($escritorio->url_apmail ?? '')), '/');
        return $url !== '' ? $url : self::URL_PADRAO;
    }

    /**
     * Endereco completo de uma caixa: a API devolve o caminho relativo; so ele e aceito (seguranca 11/11/2026: endereco absoluto ou
     * //outro-site vindo da resposta nao vira link - fica a base do APMail).
     */
    public static function urlCaixa(string $base, string $caminho): string
    {
        $caminho = trim($caminho);
        if ($caminho === '' || $caminho[0] !== '/' || strpos($caminho, '//') === 0 || preg_match('#[\s\\\\]#', $caminho))
        {
            $caminho = '/';
        }
        return rtrim($base, '/') . $caminho;
    }

    /**
     * Notificacoes do usuario do APMail pelo e-mail.
     *
     * @return array {pendentes, nao_lidas, caixas: [{nome, email, pendentes, nao_lidas, url}]} (url completa)
     * @throws ApmailErro com o codigo HTTP (0 = sem resposta) e o Retry-After, quando vier
     */
    public static function notificacoes(string $url, string $token, string $email): array
    {
        $url = rtrim(trim($url), '/') ?: self::URL_PADRAO;
        $r = self::chamar($url . self::ROTA . '?email=' . rawurlencode(trim($email)), trim($token));
        $json = json_decode((string) $r['corpo'], true);
        if ($r['status'] !== 200 || !is_array($json) || empty($json['success']) || !is_array($json['data'] ?? null))
        {
            throw new ApmailErro(self::motivo($r, is_array($json) ? $json : []), (int) $r['status'], $r['retry_after'] ?? null);
        }
        $d = $json['data'];
        $caixas = [];
        foreach ((array) ($d['mailboxes'] ?? []) as $m)
        {
            $caixas[] = [
                'nome'      => (string) ($m['name'] ?? $m['email'] ?? 'Caixa'),
                'email'     => (string) ($m['email'] ?? ''),
                'pendentes' => (int) ($m['pending'] ?? 0),
                'nao_lidas' => (int) ($m['unread'] ?? 0),
                'url'       => self::urlCaixa($url, (string) ($m['url'] ?? '/')),
                'caminho'   => substr(self::urlCaixa($url, (string) ($m['url'] ?? '/')), strlen(rtrim($url, '/'))) ?: '/',
            ];
        }
        usort($caixas, function ($a, $b) { return [$b['pendentes'], $b['nao_lidas']] <=> [$a['pendentes'], $a['nao_lidas']] ?: strcmp($a['nome'], $b['nome']); });
        return [
            'pendentes' => (int) ($d['totals']['pending'] ?? $d['pending'] ?? 0),
            'nao_lidas' => (int) ($d['totals']['unread'] ?? $d['unread'] ?? 0),
            'caixas'    => $caixas,
        ];
    }

    /** Botao "Testar" do escritorio: consulta com o endereco e o token da tela. Lanca excecao com o motivo. */
    public static function testar(string $url, string $token, string $email): array
    {
        if (trim($token) === '') { throw new Exception('Informe o token da API do APMail.'); }
        if (trim($email) === '') { throw new Exception('Seu usuário não tem e-mail para consultar o APMail.'); }
        return self::notificacoes($url, $token, $email);
    }

    /**
     * Historico (12/11/2026): uma pagina da sincronizacao por cursor (recebidos e enviados), sem corpo.
     *
     * @return array {itens: array, proximo: ?string (nextCursor), mais: bool (hasMore)}
     * @throws ApmailErro
     */
    public static function mensagens(string $url, string $token, string $email, ?string $cursor, int $limite = 100): array
    {
        $url = rtrim(trim($url), '/') ?: self::URL_PADRAO;
        $q = 'email=' . rawurlencode(trim($email)) . '&direction=all&limit=' . max(1, min(100, $limite))
           . ($cursor !== null && $cursor !== '' ? '&cursor=' . rawurlencode($cursor) : '');
        $d = self::dados(self::chamar($url . self::ROTA_MENSAGENS . '?' . $q, trim($token)), 'messages:read');
        return [
            'itens'   => array_values((array) ($d['items'] ?? [])),
            'proximo' => isset($d['nextCursor']) && $d['nextCursor'] !== '' ? (string) $d['nextCursor'] : null,
            'mais'    => !empty($d['hasMore']),
        ];
    }

    /**
     * Historico (12/11/2026): a conversa inteira (recebidos e enviados, com corpo), juntando as paginas, em ordem.
     *
     * @throws ApmailErro
     */
    public static function conversa(string $url, string $token, string $email, string $threadId): array
    {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $threadId))
        {
            throw new ApmailErro('Conversa do APMail inválida.', 400);
        }
        $url = rtrim(trim($url), '/') ?: self::URL_PADRAO;
        $itens = [];
        $cursor = null;
        for ($pagina = 0; $pagina < 40; $pagina++)
        {
            $q = 'email=' . rawurlencode(trim($email)) . '&limit=50' . ($cursor !== null ? '&cursor=' . rawurlencode($cursor) : '');
            $d = self::dados(self::chamar($url . self::ROTA_CONVERSAS . strtolower($threadId) . '?' . $q, trim($token)), 'messages:read');
            foreach ((array) ($d['items'] ?? []) as $m) { $itens[] = $m; }
            $cursor = isset($d['nextCursor']) && $d['nextCursor'] !== '' ? (string) $d['nextCursor'] : null;
            if (empty($d['hasMore']) || $cursor === null) { break; }
        }
        return $itens;
    }

    /** data da resposta de sucesso; senao ApmailErro com o motivo. */
    private static function dados(array $r, string $permissao): array
    {
        $json = json_decode((string) $r['corpo'], true);
        if ($r['status'] !== 200 || !is_array($json) || empty($json['success']) || !is_array($json['data'] ?? null))
        {
            throw new ApmailErro(self::motivo($r, is_array($json) ? $json : [], $permissao), (int) $r['status'], $r['retry_after'] ?? null);
        }
        return $json['data'];
    }

    private static function motivo(array $r, array $json, string $permissao = 'notifications:read'): string
    {
        $msg = trim((string) ($json['error']['message'] ?? ''));
        switch ((int) $r['status'])
        {
            case 0:   return 'O APMail não respondeu' . ($r['erro'] ? ' (' . $r['erro'] . ')' : '') . '.';
            case 400: return 'O APMail recusou o e-mail informado (formato inválido).';
            case 401: return 'Token do APMail ausente, inválido, expirado ou revogado.';
            case 403: return 'A rota não está autorizada para este token no APMail (permissão ' . $permissao . ').';
            case 404: return 'O e-mail não está liberado para esta integração no APMail.';
            case 429: return 'Limite de consultas do APMail atingido; tente de novo em instantes.';
        }
        return 'Falha no APMail (HTTP ' . (int) $r['status'] . ')' . ($msg !== '' ? ': ' . $msg : '.');
    }

    private static function chamar(string $url, string $token): array
    {
        if (self::$transporte)
        {
            return call_user_func(self::$transporte, $url, $token);
        }
        $retry = null;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPGET        => true,
            // so http/https (seguranca 11/11/2026)
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER     => ['Accept: application/json', 'Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_CONEXAO,
            CURLOPT_TIMEOUT        => self::TIMEOUT_TOTAL,
            CURLOPT_HEADERFUNCTION => function ($ch, $linha) use (&$retry) {
                if (stripos($linha, 'Retry-After:') === 0) { $retry = (int) trim(substr($linha, 12)); }
                return strlen($linha);
            },
        ]);
        $texto = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = (string) curl_error($ch);
        curl_close($ch);
        return ['status' => $texto === false ? 0 : $status, 'corpo' => $texto === false ? '' : (string) $texto, 'erro' => $erro, 'retry_after' => $retry];
    }
}
