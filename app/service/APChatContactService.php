<?php

/**
 * Sincronizacao das pessoas do Curciol com os contatos do APChat.
 *
 * Mao unica: Curciol -> APChat. Desde 16/10/2026 vale para toda pessoa com
 * telefone valido (cliente, colaborador, parte contraria...), sem olhar a
 * autorizacao de WhatsApp (aceita_receber_mensagen_whatsapp). Telefone vazio
 * ou invalido nao chama nenhuma rota.
 *
 * API (confirmada em 09/10/2026)
 * -------------------------------
 * Todas as rotas sao POST com JSON e "Authorization: Bearer <token>"; o token
 * fica em escritorio.token_apchat.
 *
 *   showcontact    {number}   200 {success:true, data:{id,...}}         encontrado
 *                             404 {success:false, error:ERR_CONTACT_NOT_FOUND}  nao existe
 *   createContact  {...}      201 {success:true, data:{id,...}}
 *   updateContact  {number,...}  200 {success:true, data:{...}}
 *                             404 ERR_CONTACT_NOT_FOUND se o numero nao existe
 *                                 (nao cria nada)
 *   token ausente/invalido    403 {error:"Token was not provided."|"Invalid token."}
 *   corpo incompleto          500 {error:"Internal server error"}
 *   rota/metodo errado        404 com HTML
 *
 * - O update localiza o contato pelo number e preserva os campos que nao
 *   forem enviados. Por isso campo vazio no Curciol nao e enviado.
 * - O update NAO troca o numero: id, contactId e newNumber sao ignorados.
 *   Telefone alterado no Curciol, com contato no numero antigo, vira
 *   PENDENTE (o contato antigo e atualizado, nada e criado) - ver decidir().
 * - So um 404 com ERR_CONTACT_NOT_FOUND prova que o contato nao existe e
 *   libera o create. Qualquer outro erro encerra sem criar nada.
 *
 * Falhas aqui nunca desfazem nem impedem a gravacao do cliente: o ponto de
 * entrada (sincronizarPessoa) nao lanca excecao e e chamado depois do
 * commit.
 */
class APChatContactService
{
    const URL_BASE = 'https://api.apchat.com.br/v2/api/external/baca905c-dce1-4d3f-b1bb-f2ffc981fdb4/';

    const TIMEOUT_CONEXAO = 5;
    const TIMEOUT_TOTAL   = 15;

    const ERRO_NAO_ENCONTRADO = 'ERR_CONTACT_NOT_FOUND';

    /**
     * Sincronizar todos: chamadas abertas ao mesmo tempo, repeticoes e pausa quando o APChat pede calma.
     * Medido em 26/10/2026: o APChat atende ~7 chamadas por segundo com 5, 10 ou 20 simultaneas (uma por vez, ~5,7);
     * 5 ja chega no teto sem sobrecarregar o APChat.
     */
    const CHAMADAS_SIMULTANEAS = 5;
    const TENTATIVAS           = 2;
    const PAUSA_REPETICAO_MS   = 1500;

    private static $database = 'escritorio';

    /**
     * Substitui a chamada HTTP (testes). Recebe (rota, corpo, token) e
     * devolve ['status' => int, 'corpo' => string, 'erro' => string].
     *
     * @var callable|null
     */
    private static $transporte = null;

    public static function usarTransporte(?callable $transporte): void
    {
        self::$transporte = $transporte;
    }

    /** Endereco da API (testes: um APChat falso local); null volta ao de verdade. */
    private static $urlBase = null;

    public static function usarUrlBase(?string $url): void
    {
        self::$urlBase = $url;
    }

    // =================================================================
    // Ponto de entrada
    // =================================================================

    /**
     * Sincroniza um cliente. Nunca lanca excecao.
     *
     * @param int      $pessoa_id
     * @param string[] $telefones_anteriores telefone gravado antes da edicao
     *                 (ClienteForm); o ultimo numero sincronizado com sucesso
     *                 entra sozinho.
     * @param string   $origem ApchatContatoLog::ORIGEM_*
     * @return object {situacao, operacao, mensagem}; situacao IGNORADO sem
     *                consentimento (nada e registrado nem chamado)
     */
    public static function sincronizarPessoa($pessoa_id, array $telefones_anteriores = [], $origem = ApchatContatoLog::ORIGEM_CADASTRO): object
    {
        $pessoa_id = (int) $pessoa_id;
        $aberta = false;

        try
        {
            TTransaction::open(self::$database);
            $aberta = true;

            $pessoa = Pessoa::find($pessoa_id);

            if (!$pessoa)
            {
                TTransaction::close();

                return self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Pessoa não encontrada: nenhuma chamada ao APChat.');
            }

            $contato = self::montarContato($pessoa);

            if (empty($contato['number']))
            {
                $r = self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Telefone ausente ou inválido: nada foi enviado ao APChat.');
                self::registrar($pessoa_id, $origem, $r, null, null);
                TTransaction::close();

                return $r;
            }

            if (empty($contato['name']))
            {
                $r = self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Cliente sem nome: nada foi enviado ao APChat.');
                self::registrar($pessoa_id, $origem, $r, $contato['number'], null);
                TTransaction::close();

                return $r;
            }

            $token = self::token();

            if ($token === '')
            {
                $r = self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_NENHUMA, 'Token do APChat não configurado no cadastro do escritório.');
                self::registrar($pessoa_id, $origem, $r, $contato['number'], null);
                TTransaction::close();

                return $r;
            }

            /*
                Dois salvamentos simultaneos do mesmo numero (clique repetido,
                "Sincronizar todos" junto com uma edicao) consultariam juntos,
                os dois veriam "nao existe" e os dois criariam. A trava segura
                o numero ate o fim desta transacao; o segundo espera e, ao
                consultar, ja encontra o contato criado pelo primeiro.
            */
            self::travar('apchat:' . $contato['number']);

            $anteriores = self::numerosAnteriores($pessoa_id, $telefones_anteriores, $contato['number']);

            $r = self::rodar(self::passosDecidir($contato, $anteriores, self::numeroConhecido($pessoa_id, $contato['number'])), $token);

            self::registrar($pessoa_id, $origem, $r, $contato['number'], $r->numero_anterior ?? null);
            self::gravarIdApchat($pessoa_id, $r);

            TTransaction::close();

            return $r;
        }
        catch (Throwable $e)
        {
            // so a transacao aberta aqui; a de quem chamou fica intacta
            if ($aberta && TTransaction::get())
            {
                try { TTransaction::rollback(); } catch (Throwable $ignorado) {}
            }

            $r = self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_NENHUMA, 'Falha interna na sincronização: ' . $e->getMessage());

            try
            {
                TTransaction::open(self::$database);
                self::registrar($pessoa_id, $origem, $r, null, null);
                TTransaction::close();
            }
            catch (Throwable $ignorado)
            {
                try { TTransaction::rollback(); } catch (Throwable $x) {}
            }

            return $r;
        }
    }

    /**
     * "Sincronizar todos": o mesmo que sincronizarPessoa para varios cadastros, com as chamadas de ate
     * CHAMADAS_SIMULTANEAS contatos ao mesmo tempo. Quem divide o numero (ou a variante do nono digito) com outro do
     * lote vai depois, um por vez, e acha o contato que o primeiro criou: nada de contato duplicado. Os numeros do lote
     * ficam travados ate o fim (mesma trava do cadastro). Nunca lanca excecao.
     *
     * @param int[] $ids
     * @return object[] [pessoa_id => {situacao, operacao, mensagem}]
     */
    public static function sincronizarLote(array $ids, string $origem = ApchatContatoLog::ORIGEM_LOTE): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $resultados = [];
        $porId = [];

        try
        {
            TTransaction::open(self::$database);
            $token = self::token();

            foreach (($ids ? Pessoa::where('id', 'in', $ids)->load() : []) as $p) { $porId[(int) $p->id] = $p; }

            $contatos = [];
            foreach ($ids as $id)
            {
                if (!isset($porId[$id]))
                {
                    $resultados[$id] = self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Pessoa não encontrada: nenhuma chamada ao APChat.');
                    continue;
                }

                $contato = self::montarContato($porId[$id]);
                $r = null;
                if (empty($contato['number']))
                {
                    $r = self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Telefone ausente ou inválido: nada foi enviado ao APChat.');
                }
                elseif (empty($contato['name']))
                {
                    $r = self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Cliente sem nome: nada foi enviado ao APChat.');
                }
                elseif ($token === '')
                {
                    $r = self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_NENHUMA, 'Token do APChat não configurado no cadastro do escritório.');
                }

                if ($r)
                {
                    self::registrar($id, $origem, $r, ($contato['number'] ?? '') ?: null, null);
                    $resultados[$id] = $r;
                    continue;
                }
                $contatos[$id] = $contato;
            }

            // trava os numeros do lote em ordem fixa (duas sincronizacoes ao mesmo tempo nao se travam mutuamente)
            $chaves = array_values(array_unique(array_map(function ($c) { return 'apchat:' . $c['number']; }, $contatos)));
            sort($chaves);
            foreach ($chaves as $chave) { self::travar($chave); }

            // quem divide numero com outro do lote espera o primeiro terminar
            $usados = [];
            $juntos = [];
            $depois = [];
            foreach ($contatos as $id => $contato)
            {
                $variantes = self::variantes($contato['number']);
                if (array_intersect($variantes, array_keys($usados))) { $depois[] = $id; continue; }
                foreach ($variantes as $v) { $usados[$v] = true; }
                $juntos[] = $id;
            }

            $passos = function (int $id) use ($contatos) {
                return self::passosDecidir($contatos[$id], self::numerosAnteriores($id, [], $contatos[$id]['number']),
                                           self::numeroConhecido($id, $contatos[$id]['number']));
            };

            $feitos = [];
            if ($juntos)
            {
                $feitos = self::rodarVarios(array_combine($juntos, array_map($passos, $juntos)), $token, self::CHAMADAS_SIMULTANEAS);
            }
            foreach ($depois as $id) { $feitos[$id] = self::rodar($passos($id), $token); }

            foreach ($feitos as $id => $r)
            {
                self::registrar($id, $origem, $r, $contatos[$id]['number'], $r->numero_anterior ?? null);
                self::gravarIdApchat($id, $r);
                $resultados[$id] = $r;
            }

            TTransaction::close();
        }
        catch (Throwable $e)
        {
            try { if (TTransaction::get()) { TTransaction::rollback(); } } catch (Throwable $ignorado) {}

            $r = self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_NENHUMA, 'Falha interna na sincronização: ' . $e->getMessage());
            $resultados = [];

            try
            {
                TTransaction::open(self::$database);
                foreach ($ids as $id)
                {
                    $resultados[$id] = $r;
                    if (isset($porId[$id])) { self::registrar($id, $origem, $r, null, null); }
                }
                TTransaction::close();
            }
            catch (Throwable $ignorado)
            {
                try { TTransaction::rollback(); } catch (Throwable $x) {}
            }
        }

        return $resultados;
    }

    /**
     * Consulta o contato no APChat, sem gravar nada (tela de visualizacao).
     * Qualquer pessoa com telefone valido.
     *
     * @return object {situacao: ENCONTRADO|NAO_ENCONTRADO|ERRO|IGNORADO, contato?, numero?, mensagem}
     */
    public static function consultarPessoa($pessoa_id): object
    {
        try
        {
            TTransaction::open(self::$database);
            $pessoa = Pessoa::find((int) $pessoa_id);
            $existe = (bool) $pessoa;
            $contato = $pessoa ? self::montarContato($pessoa) : [];
            $token = self::token();
            TTransaction::close();
        }
        catch (Throwable $e)
        {
            try { TTransaction::rollback(); } catch (Throwable $x) {}

            return (object) ['situacao' => 'ERRO', 'mensagem' => $e->getMessage()];
        }

        if (!$existe)
        {
            return (object) ['situacao' => 'IGNORADO', 'mensagem' => 'Pessoa não encontrada.'];
        }

        if (empty($contato['number']))
        {
            return (object) ['situacao' => 'IGNORADO', 'mensagem' => 'Telefone ausente ou inválido.'];
        }

        if ($token === '')
        {
            return (object) ['situacao' => 'ERRO', 'mensagem' => 'Token do APChat não configurado no cadastro do escritório.'];
        }

        return self::rodar(self::passosBuscar(self::variantes($contato['number'])), $token);
    }

    /**
     * Telefone gravado da pessoa antes de uma edicao ('' se nova). Usa a transacao aberta do formulario.
     */
    public static function telefoneGravado($pessoa_id): string
    {
        $pessoa_id = (int) $pessoa_id;
        if ($pessoa_id <= 0 || !TTransaction::get())
        {
            return '';
        }

        $st = TTransaction::get()->prepare('SELECT telefone FROM pessoa WHERE id = ?');
        $st->execute([$pessoa_id]);

        return (string) $st->fetchColumn();
    }

    /**
     * Ids das pessoas com telefone valido, em ordem, para o "Sincronizar todos".
     *
     * @return int[]
     */
    public static function idsAutorizados(): array
    {
        TTransaction::open(self::$database);
        $ids = TTransaction::get()->query('SELECT id FROM apchat_contato_view WHERE telefone_valido ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        TTransaction::close();

        return array_map('intval', $ids);
    }

    // =================================================================
    // Decisao: consultar -> atualizar ou criar
    // =================================================================
    //
    // Cada passo e um generator: "yield [rota, corpo]" pede uma chamada a API e recebe a resposta. Assim a mesma regra
    // roda um contato por vez (cadastro: rodar) ou varios ao mesmo tempo (Sincronizar todos: rodarVarios, curl_multi).

    /**
     * 0. Caminho curto: o ultimo envio deu certo com este mesmo numero -> atualiza direto (1 chamada). Se o APChat
     *    responder "nao encontrado" (o numero mudou la), segue o fluxo completo abaixo.
     * 1. Consulta o numero atual (e a variante com/sem o nono digito).
     *    Achou: UPDATE nele.
     * 2. Nao achou: consulta os numeros anteriores. Achou: UPDATE no contato
     *    antigo e PENDENTE (a API nao troca numero) - nao cria outro.
     * 3. Nenhum existe, com 404 ERR_CONTACT_NOT_FOUND em todas as
     *    consultas: CREATE.
     * Qualquer erro de consulta encerra sem criar.
     */
    private static function passosDecidir(array $contato, array $anteriores, bool $conhecido = false): Generator
    {
        if ($conhecido)
        {
            $resposta = yield ['updateContact', $contato];
            $classe = self::classificar($resposta);

            if ($classe->tipo === 'OK')
            {
                $r = self::resultado(ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::OPERACAO_UPDATE, 'Contato atualizado no APChat.');
                $r->http_status = $resposta['status'];
                $r->apchat_contato_id = $classe->dados['id'] ?? null;

                return $r;
            }

            if ($classe->tipo !== 'NAO_ENCONTRADO')
            {
                $r = self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_UPDATE, 'Atualização no APChat falhou; nenhum contato foi criado. ' . $classe->mensagem);
                $r->http_status = $resposta['status'];

                return $r;
            }
            // nao achou pelo numero de antes: segue o fluxo completo
        }

        $atual = yield from self::passosBuscar(self::variantes($contato['number']));

        if ($atual->situacao === 'ERRO')
        {
            return self::erroDeConsulta($atual);
        }

        if ($atual->situacao === 'ENCONTRADO')
        {
            $r = yield from self::passosAtualizar($contato, $atual->numero);
            $r->apchat_contato_id = $r->apchat_contato_id ?? ($atual->contato['id'] ?? null);

            if ($r->situacao === ApchatContatoLog::SITUACAO_SUCESSO && $atual->numero !== $contato['number'])
            {
                $r->mensagem = "Contato atualizado. No APChat ele está com o número {$atual->numero} (sem o nono dígito); a API não permite trocar o número.";
            }

            return $r;
        }

        if (!empty($anteriores))
        {
            $antigo = yield from self::passosBuscar($anteriores);

            if ($antigo->situacao === 'ERRO')
            {
                return self::erroDeConsulta($antigo);
            }

            if ($antigo->situacao === 'ENCONTRADO')
            {
                $r = yield from self::passosAtualizar($contato, $antigo->numero);
                $r->apchat_contato_id = $r->apchat_contato_id ?? ($antigo->contato['id'] ?? null);
                $r->numero_anterior = $antigo->numero;

                if ($r->situacao === ApchatContatoLog::SITUACAO_SUCESSO)
                {
                    $r->situacao = ApchatContatoLog::SITUACAO_PENDENTE;
                    $r->mensagem = "Telefone alterado de {$antigo->numero} para {$contato['number']}. A API do APChat não permite trocar o número: "
                                 . "o contato existente foi atualizado com os demais dados e mantém o número antigo. Troque o número manualmente no APChat. Nenhum contato novo foi criado.";
                }

                return $r;
            }
        }

        return yield from self::passosCriar($contato);
    }

    private static function erroDeConsulta(object $busca): object
    {
        $r = self::resultado(
            ApchatContatoLog::SITUACAO_ERRO,
            ApchatContatoLog::OPERACAO_NENHUMA,
            'Consulta ao APChat falhou; nada foi criado nem atualizado. ' . $busca->mensagem
        );
        $r->http_status = $busca->http_status ?? null;

        return $r;
    }

    /**
     * Consulta cada numero ate achar. So devolve NAO_ENCONTRADO se TODOS
     * responderem ERR_CONTACT_NOT_FOUND.
     *
     * @param string[] $numeros
     * @return Generator que devolve {situacao: ENCONTRADO|NAO_ENCONTRADO|ERRO, numero?, contato?, mensagem, http_status?}
     */
    private static function passosBuscar(array $numeros): Generator
    {
        foreach ($numeros as $numero)
        {
            $resposta = yield ['showcontact', ['number' => $numero]];
            $classe = self::classificar($resposta);

            if ($classe->tipo === 'OK')
            {
                return (object) [
                    'situacao'    => 'ENCONTRADO',
                    'numero'      => (string) ($classe->dados['number'] ?? $numero),
                    'contato'     => $classe->dados,
                    'mensagem'    => 'Contato encontrado.',
                    'http_status' => $resposta['status'],
                ];
            }

            if ($classe->tipo !== 'NAO_ENCONTRADO')
            {
                return (object) [
                    'situacao'    => 'ERRO',
                    'mensagem'    => $classe->mensagem,
                    'http_status' => $resposta['status'],
                ];
            }
        }

        return (object) ['situacao' => 'NAO_ENCONTRADO', 'mensagem' => 'Contato não existe no APChat.'];
    }

    private static function passosAtualizar(array $contato, string $numero_no_apchat): Generator
    {
        $corpo = $contato;
        $corpo['number'] = $numero_no_apchat;

        $resposta = yield ['updateContact', $corpo];
        $classe = self::classificar($resposta);

        if ($classe->tipo === 'OK')
        {
            $r = self::resultado(ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::OPERACAO_UPDATE, 'Contato atualizado no APChat.');
        }
        else
        {
            // Inclusive se a resposta for "nao encontrado": nunca vira CREATE aqui.
            $r = self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_UPDATE, 'Atualização no APChat falhou; nenhum contato foi criado. ' . $classe->mensagem);
        }

        $r->http_status = $resposta['status'];
        $r->apchat_contato_id = $classe->dados['id'] ?? null;

        return $r;
    }

    private static function passosCriar(array $contato): Generator
    {
        $resposta = yield ['createContact', $contato];
        $classe = self::classificar($resposta);

        if ($classe->tipo === 'OK')
        {
            $r = self::resultado(ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::OPERACAO_CREATE, 'Contato criado no APChat.');
        }
        else
        {
            $r = self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_CREATE, 'Criação no APChat falhou. ' . $classe->mensagem);
        }

        $r->http_status = $resposta['status'];
        $r->apchat_contato_id = $classe->dados['id'] ?? null;

        return $r;
    }

    // =================================================================
    // HTTP
    // =================================================================

    /**
     * @return array ['status' => int (0 sem resposta), 'corpo' => string, 'erro' => string]
     */
    private static function chamar(string $rota, array $corpo, string $token): array
    {
        if (self::$transporte)
        {
            return call_user_func(self::$transporte, $rota, $corpo, $token);
        }

        $ch = self::novoHandle($rota, $corpo, $token);
        $texto = curl_exec($ch);
        $resposta = self::respostaDoHandle($ch, $texto);
        curl_close($ch);

        return $resposta;
    }

    private static function novoHandle(string $rota, array $corpo, string $token)
    {
        $ch = curl_init((self::$urlBase ?? self::URL_BASE) . $rota);

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($corpo, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_CONEXAO,
            CURLOPT_TIMEOUT        => self::TIMEOUT_TOTAL,
        ]);

        return $ch;
    }

    private static function respostaDoHandle($ch, $texto): array
    {
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return [
            'status' => ($texto === false || $texto === null) ? 0 : $status,
            'corpo'  => ($texto === false || $texto === null) ? '' : (string) $texto,
            'erro'   => (string) curl_error($ch),
        ];
    }

    /**
     * O APChat pediu calma (429) ou ficou instavel (502/503/504): a chamada e repetida depois de uma pausa.
     * Sem resposta (tempo esgotado) so consulta e atualizacao se repetem: repetir da o mesmo resultado. A criacao nao:
     * se o APChat criou e so a resposta se perdeu, repetir duplicaria o contato (a proxima sincronizacao o acha).
     */
    private static function repetir(array $resposta, int $tentativa, string $rota): bool
    {
        if ($tentativa >= self::TENTATIVAS) { return false; }
        $status = (int) $resposta['status'];
        return in_array($status, [429, 502, 503, 504], true) || ($status === 0 && $rota !== 'createContact');
    }

    /** Chamada com as repeticoes de repetir(). */
    private static function chamarComRepeticao(string $rota, array $corpo, string $token): array
    {
        for ($tentativa = 0; ; $tentativa++)
        {
            $resposta = self::chamar($rota, $corpo, $token);
            if (!self::repetir($resposta, $tentativa, $rota)) { return $resposta; }
            usleep(self::PAUSA_REPETICAO_MS * 1000 * ($tentativa + 1));
        }
    }

    /** Roda os passos de um contato, uma chamada por vez. */
    private static function rodar(Generator $passos, string $token): object
    {
        while ($passos->valid())
        {
            [$rota, $corpo] = $passos->current();
            $passos->send(self::chamarComRepeticao($rota, $corpo, $token));
        }

        return $passos->getReturn();
    }

    /**
     * Roda os passos de varios contatos ao mesmo tempo: ate $maximo chamadas abertas juntas (curl_multi). A resposta de
     * cada chamada destrava o proximo passo daquele contato. Devolve [chave => resultado], na mesma chave de $passos.
     *
     * @param Generator[] $passos
     */
    private static function rodarVarios(array $passos, string $token, int $maximo): array
    {
        $resultados = [];

        // testes: sem rede, os contatos andam em rodizio (mesma ordem de eventos de chamadas simultaneas)
        if (self::$transporte)
        {
            $ativos = $passos;
            while ($ativos)
            {
                foreach ($ativos as $chave => $g)
                {
                    if (!$g->valid()) { $resultados[$chave] = $g->getReturn(); unset($ativos[$chave]); continue; }
                    [$rota, $corpo] = $g->current();
                    $g->send(self::chamarComRepeticao($rota, $corpo, $token));
                }
            }
            return $resultados;
        }

        $multi = curl_multi_init();
        $fila = $passos;
        $abertos = []; // id do handle => [chave, handle, rota, corpo, tentativa]

        $abrir = function ($chave, Generator $g, int $tentativa = 0, ?array $pedido = null) use (&$abertos, &$resultados, $multi, $token) {
            if ($pedido === null)
            {
                if (!$g->valid()) { $resultados[$chave] = $g->getReturn(); return; }
                $pedido = $g->current();
            }
            [$rota, $corpo] = $pedido;
            $ch = self::novoHandle($rota, $corpo, $token);
            curl_multi_add_handle($multi, $ch);
            $abertos[(int) $ch] = [$chave, $ch, $rota, $corpo, $tentativa];
        };

        while (count($abertos) < $maximo && $fila)
        {
            $chave = array_key_first($fila);
            $abrir($chave, $fila[$chave]);
            unset($fila[$chave]);
        }

        while ($abertos)
        {
            do { $estado = curl_multi_exec($multi, $rodando); } while ($estado === CURLM_CALL_MULTI_PERFORM);
            if (curl_multi_select($multi, 1.0) === -1) { usleep(1000); }

            while ($info = curl_multi_info_read($multi))
            {
                $ch = $info['handle'];
                [$chave, , $rota, $corpo, $tentativa] = $abertos[(int) $ch];
                unset($abertos[(int) $ch]);
                $resposta = self::respostaDoHandle($ch, curl_multi_getcontent($ch));
                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);

                $g = $passos[$chave];
                if (self::repetir($resposta, $tentativa, $rota))
                {
                    usleep(self::PAUSA_REPETICAO_MS * 1000 * ($tentativa + 1));
                    $abrir($chave, $g, $tentativa + 1, [$rota, $corpo]);
                    continue;
                }

                $g->send($resposta);
                $abrir($chave, $g);

                while (count($abertos) < $maximo && $fila)
                {
                    $proxima = array_key_first($fila);
                    $abrir($proxima, $fila[$proxima]);
                    unset($fila[$proxima]);
                }
            }
        }

        curl_multi_close($multi);

        return $resultados;
    }

    /**
     * @return object {tipo: OK|NAO_ENCONTRADO|AUTENTICACAO|VALIDACAO|INDISPONIVEL|CONEXAO|INESPERADO, dados: array, mensagem: string}
     */
    private static function classificar(array $resposta): object
    {
        $status = (int) $resposta['status'];
        $json = json_decode((string) $resposta['corpo'], true);
        $json = is_array($json) ? $json : null;
        $codigo = is_array($json) ? (string) ($json['error'] ?? '') : '';

        $saida = function ($tipo, $mensagem, $dados = []) {
            return (object) ['tipo' => $tipo, 'mensagem' => $mensagem, 'dados' => $dados];
        };

        if ($status === 0)
        {
            return $saida('CONEXAO', 'Sem resposta do APChat (conexão ou tempo esgotado): ' . $resposta['erro']);
        }

        if (($status === 200 || $status === 201) && $json && ($json['success'] ?? false) === true)
        {
            return $saida('OK', 'OK', is_array($json['data'] ?? null) ? $json['data'] : []);
        }

        if ($status === 404 && $codigo === self::ERRO_NAO_ENCONTRADO)
        {
            return $saida('NAO_ENCONTRADO', 'Contato não encontrado no APChat.');
        }

        $detalhe = $codigo !== '' ? $codigo : ('resposta ' . ($json ? 'sem código de erro' : 'não é JSON'));

        if ($status === 401 || $status === 403)
        {
            return $saida('AUTENTICACAO', "Falha de autenticação no APChat (HTTP {$status}: {$detalhe}). Confira o token no cadastro do escritório.");
        }

        if ($status === 400 || $status === 422)
        {
            return $saida('VALIDACAO', "APChat recusou os dados (HTTP {$status}: {$detalhe}).");
        }

        if ($status >= 500)
        {
            return $saida('INDISPONIVEL', "APChat com erro ou indisponível (HTTP {$status}: {$detalhe}).");
        }

        return $saida('INESPERADO', "Resposta inesperada do APChat (HTTP {$status}: {$detalhe}).");
    }

    // =================================================================
    // Dados do cliente
    // =================================================================

    /**
     * Corpo enviado ao APChat. Campos sem valor ficam de fora: no update a
     * API mantem o que ja existe la.
     */
    public static function montarContato($pessoa): array
    {
        $nome = trim(preg_replace('/\s+/', ' ', (string) $pessoa->nome));
        $fisica = ((string) $pessoa->tipo_pessoa_id === TipoPessoa::FISICA);

        $contato = [
            'name'   => $nome,
            'number' => self::normalizarTelefone($pessoa->telefone),
        ];

        $email = trim((string) $pessoa->email);

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            $contato['email'] = mb_strtolower($email);
        }

        if ($fisica)
        {
            $cpf = preg_replace('/\D/', '', (string) $pessoa->cpf_cnpj);

            if (strlen($cpf) === 11)
            {
                $contato['cpf'] = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
            }

            if ($nome !== '')
            {
                $partes = explode(' ', $nome, 2);
                $contato['firstName'] = $partes[0];

                if (!empty($partes[1]))
                {
                    $contato['lastName'] = $partes[1];
                }
            }

            $nascimento = self::dataBr($pessoa->dt_nascimento_abertura);

            if ($nascimento)
            {
                $contato['birthdayDate'] = $nascimento;
            }
        }
        else
        {
            // Pessoa juridica: o nome e a razao social. CNPJ e data de abertura
            // nao cabem nos campos cpf/birthdayDate da API.
            if ($nome !== '')
            {
                $contato['businessName'] = $nome;
            }
        }

        return array_filter($contato, function ($valor) {
            return $valor !== null && $valor !== '';
        });
    }

    /**
     * Telefone no padrao 55 + DDD + numero, so digitos; '' se invalido.
     * Aceita com ou sem o 55, com mascara e com zeros a esquerda.
     */
    public static function normalizarTelefone($telefone): string
    {
        $digitos = ltrim(preg_replace('/\D/', '', (string) $telefone), '0');

        if ((strlen($digitos) === 12 || strlen($digitos) === 13) && substr($digitos, 0, 2) === '55')
        {
            $digitos = substr($digitos, 2);
        }

        if (strlen($digitos) !== 10 && strlen($digitos) !== 11)
        {
            return '';
        }

        // DDD: dois digitos de 1 a 9
        if (!preg_match('/^[1-9]{2}/', $digitos))
        {
            return '';
        }

        // Com 11 digitos e celular: comeca com 9 depois do DDD
        if (strlen($digitos) === 11 && $digitos[2] !== '9')
        {
            return '';
        }

        return '55' . $digitos;
    }

    /**
     * O numero e a variante com/sem o nono digito de celular - contatos que
     * chegaram pelo proprio WhatsApp costumam estar gravados sem ele.
     *
     * @return string[]
     */
    public static function variantes(string $numero): array
    {
        $lista = [$numero];

        if (strlen($numero) === 13 && $numero[4] === '9')
        {
            $lista[] = substr($numero, 0, 4) . substr($numero, 5);
        }
        elseif (strlen($numero) === 12 && strpos('6789', $numero[4]) !== false)
        {
            $lista[] = substr($numero, 0, 4) . '9' . substr($numero, 4);
        }

        return $lista;
    }

    private static function dataBr($data): ?string
    {
        $data = trim((string) $data);

        if ($data === '' || !preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $data, $m))
        {
            return null;
        }

        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1]) || "{$m[1]}-{$m[2]}-{$m[3]}" > date('Y-m-d'))
        {
            return null;
        }

        return "{$m[3]}/{$m[2]}/{$m[1]}";
    }

    // =================================================================
    // Apoio
    // =================================================================

    /**
     * Token do escritorio da unidade logada; sem ele, o do primeiro
     * escritorio que tiver token.
     */
    private static function token(): string
    {
        $unidade = (int) (TSession::getValue('userunitid') ?? 0);

        $escritorio = null;

        if ($unidade > 0)
        {
            $escritorio = Escritorio::where('system_unit_id', '=', $unidade)->first();
        }

        if (!$escritorio || trim((string) $escritorio->token_apchat) === '')
        {
            $escritorio = Escritorio::where('token_apchat', 'is not', null)
                                    ->where('token_apchat', '<>', '')
                                    ->orderBy('id')
                                    ->first();
        }

        return $escritorio ? trim((string) $escritorio->token_apchat) : '';
    }

    /**
     * Numeros em que o contato pode estar no APChat alem do atual: o
     * telefone gravado antes da edicao e o ultimo numero sincronizado com
     * sucesso. Cada um com a variante do nono digito; sem os do numero atual.
     *
     * @return string[]
     */
    private static function numerosAnteriores(int $pessoa_id, array $telefones, string $atual): array
    {
        $ultimo = ApchatContatoLog::where('pessoa_id', '=', $pessoa_id)
                                  ->where('situacao', 'in', [ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::SITUACAO_PENDENTE])
                                  ->orderBy('id', 'desc')
                                  ->first();

        if ($ultimo)
        {
            $telefones[] = $ultimo->numero_anterior ?: $ultimo->numero;
            $telefones[] = $ultimo->numero;
        }

        $proibidos = self::variantes($atual);
        $lista = [];

        foreach ($telefones as $telefone)
        {
            $numero = self::normalizarTelefone($telefone);

            if ($numero === '')
            {
                continue;
            }

            foreach (self::variantes($numero) as $variante)
            {
                if (!in_array($variante, $proibidos, true) && !in_array($variante, $lista, true))
                {
                    $lista[] = $variante;
                }
            }
        }

        return $lista;
    }

    /**
     * Caminho curto: o ultimo envio com sucesso foi com este mesmo numero (o telefone nao mudou desde entao), entao o
     * contato esta no APChat com ele e da para atualizar direto. Usa a transacao aberta.
     */
    private static function numeroConhecido(int $pessoa_id, string $atual): bool
    {
        $ultimo = ApchatContatoLog::where('pessoa_id', '=', $pessoa_id)
                                  ->where('situacao', 'in', [ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::SITUACAO_PENDENTE])
                                  ->orderBy('id', 'desc')
                                  ->first();

        return $ultimo && $ultimo->situacao === ApchatContatoLog::SITUACAO_SUCESSO && (string) $ultimo->numero === $atual && empty($ultimo->numero_anterior);
    }

    /**
     * Guarda na pessoa o id do contato no APChat (pessoa.idapchat; base da trigger APChat -> Curciol). Mexe so nessa
     * coluna e so quando muda. Usa a transacao aberta.
     */
    private static function gravarIdApchat(int $pessoa_id, object $r): void
    {
        $id = (int) ($r->apchat_contato_id ?? 0);
        if ($id <= 0 || !in_array($r->situacao, [ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::SITUACAO_PENDENTE], true))
        {
            return;
        }

        TTransaction::get()->prepare('UPDATE pessoa SET idapchat = ? WHERE id = ? AND idapchat IS DISTINCT FROM ?')->execute([$id, $pessoa_id, $id]);
    }

    private static function travar(string $chave): void
    {
        $conn = TTransaction::get();
        $conn->query('SELECT pg_advisory_xact_lock(hashtext(' . $conn->quote($chave) . '))');
    }

    private static function registrar(int $pessoa_id, string $origem, object $r, ?string $numero, ?string $numero_anterior): void
    {
        $log = new ApchatContatoLog;
        $log->pessoa_id = $pessoa_id;
        $log->origem = $origem;
        $log->operacao = $r->operacao;
        $log->situacao = $r->situacao;
        $log->numero = $numero;
        $log->numero_anterior = $numero_anterior;
        $log->apchat_contato_id = $r->apchat_contato_id ?? null;
        $log->http_status = $r->http_status ?? null;
        $log->mensagem = mb_substr((string) $r->mensagem, 0, 2000);
        $log->data_criacao = date('Y-m-d H:i:s');
        $log->usuario_id = TSession::getValue('userid') ?: null;
        $log->store();
    }

    private static function resultado(string $situacao, string $operacao, string $mensagem): object
    {
        return (object) ['situacao' => $situacao, 'operacao' => $operacao, 'mensagem' => $mensagem];
    }
}
