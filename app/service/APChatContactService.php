<?php

/**
 * Sincronizacao das pessoas do Curciol com os contatos do APChat.
 *
 * Mao unica: Curciol -> APChat. Desde 16/10/2026 vale para toda pessoa com
 * telefone valido (cliente, colaborador, parte contraria...), sem olhar a
 * autorizacao de WhatsApp (aceita_receber_mensagen_whatsapp). Telefone vazio
 * ou invalido nao chama nenhuma rota.
 *
 * Desde 27/10/2026: um contato no APChat por NUMERO de telefone dos contatos
 * (pessoa_contato), nao so o principal. Dados do contato principal com o
 * numero (ou do mais antigo); nome = nome do contato, senao a descricao,
 * senao o numero. O id do APChat (vem na resposta da criacao ou da consulta)
 * fica em pessoa_contato.idapchat de todos os contatos com o numero.
 *
 * API (confirmada em 09/10/2026)
 * -------------------------------
 * Todas as rotas sao POST com JSON e "Authorization: Bearer <token>"; o token
 * fica em escritorio.token_apchat e o endereco em escritorio.url_apchat (vazio = URL_BASE; ApchatApiService).
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
 *   Desde 03/11/2026, telefone trocado na Curciol = contato novo no APChat e o
 *   historico do antigo passa para ele (processarTrocas; funcao do schema
 *   curciol no banco do APChat). Com o banco do APChat configurado
 *   (ApchatBancoService), o envio le la se o numero existe em vez de consultar
 *   a API, e falhas vao para a fila (ApchatFilaService).
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
    // createContact de numero que o APChat ja tem (ele trata com e sem o nono digito como o mesmo contato)
    const ERRO_JA_EXISTE = 'ERR_CONTACT_EXISTS';

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

    /** Endereco do escritorio, lido junto com o token. */
    private static $urlAtual = null;

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

            if (!Pessoa::find($pessoa_id))
            {
                TTransaction::close();

                return self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Pessoa não encontrada: nenhuma chamada ao APChat.');
            }

            // um contato no APChat por numero: os numeros validos dos contatos da pessoa (27/10/2026)
            $numeros = self::numerosDaPessoa($pessoa_id);

            if (!$numeros)
            {
                $r = self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Nenhum contato com telefone válido: nada foi enviado ao APChat.');
                self::registrar($pessoa_id, $origem, $r, null, null);
                // os numeros que sairam desta pessoa (contato excluido) deixam de ser da Curciol no APChat
                self::processarTrocas($pessoa_id, [], $origem);
                TTransaction::close();

                return $r;
            }

            $token = self::token();

            if ($token === '')
            {
                // sem token = integracao desligada (ex.: homologacao): o salvar da pessoa nao envia e nao avisa erro
                $r = self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Integração com o APChat desligada (escritório sem token): nada foi enviado.');
                self::registrar($pessoa_id, $origem, $r, $numeros[0], null);
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
            $chaves = array_map(function ($n) { return 'apchat:' . $n; }, $numeros);
            sort($chaves);
            foreach ($chaves as $chave) { self::travar($chave); }

            $principal = self::numeroPrincipal($pessoa_id);
            $noApchat = self::lerNoBanco($numeros);
            $resultados = [];
            foreach ($numeros as $numero)
            {
                $r = self::rodar(self::passosDecidir(self::montarContatoDoNumero($numero), $noApchat, $numero, self::numeroComId($numero)), $token);

                self::registrar($pessoa_id, $origem, $r, $numero, null);
                self::gravarIdApchatDoNumero($numero, $r);
                self::filaDoEnvio($numero, $pessoa_id, $r);
                $resultados[$numero] = $r;
            }

            // troca de numero (03/11/2026): o historico do contato do numero antigo passa para o do novo
            foreach (self::processarTrocas($pessoa_id, $resultados, $origem) as $numero => $r) { $resultados[$numero] = $r; }

            TTransaction::close();

            return self::resumirResultados($resultados, $principal);
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
        self::$ultimoLotePorNumero = [];

        try
        {
            TTransaction::open(self::$database);
            $token = self::token();

            foreach (($ids ? Pessoa::where('id', 'in', $ids)->load() : []) as $p) { $porId[(int) $p->id] = $p; }

            // um contato no APChat por numero: os numeros validos dos contatos de cada pessoa, cada numero uma vez so
            $numerosDe = [];
            $donoDoNumero = [];
            foreach ($ids as $id)
            {
                if (!isset($porId[$id]))
                {
                    $resultados[$id] = self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Pessoa não encontrada: nenhuma chamada ao APChat.');
                    continue;
                }

                $numerosDe[$id] = self::numerosDaPessoa($id);
                $r = null;
                if (!$numerosDe[$id])
                {
                    $r = self::resultado(ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA, 'Nenhum contato com telefone válido: nada foi enviado ao APChat.');
                }
                elseif ($token === '')
                {
                    $r = self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_NENHUMA, 'Token do APChat não configurado no cadastro do escritório.');
                }

                if ($r)
                {
                    self::registrar($id, $origem, $r, $numerosDe[$id][0] ?? null, null);
                    $resultados[$id] = $r;
                    unset($numerosDe[$id]);
                    continue;
                }
                foreach ($numerosDe[$id] as $numero) { $donoDoNumero[$numero] = $donoDoNumero[$numero] ?? $id; }
            }

            // trava os numeros do lote em ordem fixa (duas sincronizacoes ao mesmo tempo nao se travam mutuamente)
            $chaves = array_map(function ($n) { return 'apchat:' . $n; }, array_keys($donoDoNumero));
            sort($chaves);
            foreach ($chaves as $chave) { self::travar($chave); }

            // numero que e a variante (nono digito) de outro do lote espera o primeiro terminar
            $usados = [];
            $juntos = [];
            $depois = [];
            foreach (array_keys($donoDoNumero) as $numero)
            {
                $variantes = self::variantes((string) $numero);
                if (array_intersect($variantes, array_keys($usados))) { $depois[] = (string) $numero; continue; }
                foreach ($variantes as $v) { $usados[$v] = true; }
                $juntos[] = (string) $numero;
            }

            // uma leitura do banco do APChat para o lote inteiro: quem ja existe igual nao gera chamada nenhuma (03/11/2026)
            $noApchat = self::lerNoBanco(array_keys($donoDoNumero));
            $passos = function (string $numero) use ($noApchat) {
                return self::passosDecidir(self::montarContatoDoNumero($numero), $noApchat, $numero, self::numeroComId($numero));
            };

            $feitos = [];
            if ($juntos)
            {
                // rodarVarios usa as chaves do array: prefixo para o numero nao virar indice numerico
                $porChave = self::rodarVarios(array_combine(array_map(function ($n) { return 'n' . $n; }, $juntos), array_map($passos, $juntos)), $token, self::CHAMADAS_SIMULTANEAS);
                foreach ($porChave as $chave => $r) { $feitos[substr((string) $chave, 1)] = $r; }
            }
            // a variante (nono digito) de um numero do lote: le o APChat de novo - o outro acabou de ser criado agora
            foreach ($depois as $numero)
            {
                $feitos[$numero] = self::rodar(self::passosDecidir(self::montarContatoDoNumero($numero), self::lerNoBanco([$numero]), $numero, self::numeroComId($numero)), $token);
            }

            foreach ($feitos as $numero => $r)
            {
                self::gravarIdApchatDoNumero((string) $numero, $r);
                self::filaDoEnvio((string) $numero, $donoDoNumero[$numero] ?? null, $r);
            }
            self::$ultimoLotePorNumero = $feitos;

            foreach ($numerosDe as $id => $numeros)
            {
                $porNumero = [];
                foreach ($numeros as $numero)
                {
                    $porNumero[$numero] = $feitos[$numero];
                    self::registrar($id, $origem, $feitos[$numero], $numero, null);
                }
                $resultados[$id] = self::resumirResultados($porNumero, self::numeroPrincipal($id));
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

    /** @var object[] [numero => resultado] do ultimo sincronizarLote (painel de progresso do "Sincronizar todos") */
    private static $ultimoLotePorNumero = [];

    public static function resultadosDoUltimoLote(): array
    {
        return self::$ultimoLotePorNumero;
    }

    /**
     * O numero como o APChat o conta: com e sem o nono digito e o mesmo contato (fica a forma com o 9).
     */
    public static function numeroCanonico(string $numero): string
    {
        $maior = $numero;
        foreach (self::variantes($numero) as $v) { if (strlen($v) > strlen($maior)) { $maior = $v; } }
        return $maior;
    }

    /**
     * Numeros validos (canonicos) de cada pessoa com algum contato de telefone valido - contagem de contatos do APChat
     * no painel do "Sincronizar todos".
     *
     * @return array [pessoa_id => [numero canonico => true]]
     */
    public static function numerosPorPessoa(): array
    {
        TTransaction::open(self::$database);
        $linhas = TTransaction::get()->query("SELECT pessoa_id, telefone FROM pessoa_contato WHERE COALESCE(telefone, '') <> ''")->fetchAll(PDO::FETCH_ASSOC);
        TTransaction::close();

        $r = [];
        foreach ($linhas as $l)
        {
            $n = self::normalizarTelefone($l['telefone']);
            if ($n !== '') { $r[(int) $l['pessoa_id']][self::numeroCanonico($n)] = true; }
        }
        return $r;
    }

    /**
     * Ids das pessoas com algum contato de telefone valido, em ordem, para o "Sincronizar todos" (todos os contatos
     * sao exportados, nao so o principal).
     *
     * @return int[]
     */
    public static function idsAutorizados(): array
    {
        TTransaction::open(self::$database);
        $linhas = TTransaction::get()->query("SELECT pessoa_id, telefone FROM pessoa_contato WHERE COALESCE(telefone, '') <> '' ORDER BY pessoa_id")->fetchAll(PDO::FETCH_ASSOC);
        TTransaction::close();

        $ids = [];
        foreach ($linhas as $l)
        {
            if (self::normalizarTelefone($l['telefone']) !== '') { $ids[(int) $l['pessoa_id']] = true; }
        }

        return array_keys($ids);
    }

    // =================================================================
    // Decisao: consultar -> atualizar ou criar
    // =================================================================
    //
    // Cada passo e um generator: "yield [rota, corpo]" pede uma chamada a API e recebe a resposta. Assim a mesma regra
    // roda um contato por vez (cadastro: rodar) ou varios ao mesmo tempo (Sincronizar todos: rodarVarios, curl_multi).

    /**
     * O numero e a chave (03/11/2026; acabou o "numero anterior -> PENDENTE": troca de numero e contato novo, com o
     * historico transferido - ver processarTrocas).
     *
     * Com o banco do APChat ($noApchat de lerNoBanco): o contato desse numero (ou da variante do nono digito) ja existe
     * e esta igual -> nenhuma chamada; existe e mudou algo -> espelho + updateContact; nao existe -> createContact +
     * espelho. Banco configurado mas fora do ar -> erro (fila): sem o espelho a protecao do APChat desfaria a atualizacao.
     *
     * Sem o banco configurado ($noApchat null), so pela API, como antes:
     * 0. o numero ja tem idapchat -> atualiza direto (1 chamada); "nao encontrado" -> segue abaixo;
     * 1. consulta o numero (e a variante com/sem o nono digito): achou -> UPDATE nele;
     * 2. nao existe (404 ERR_CONTACT_NOT_FOUND em todas as consultas) -> CREATE. Qualquer erro de consulta encerra sem criar.
     */
    private static function passosDecidir(array $contato, ?array $noApchat, string $numero, bool $conhecido = false): Generator
    {
        if ($noApchat !== null)
        {
            if (!$noApchat['ok'])
            {
                return self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_NENHUMA,
                    'Banco do APChat indisponível: o envio ficou na fila e será refeito sozinho. ' . $noApchat['erro']);
            }

            $la = null;
            foreach (self::variantes($numero) as $v) { if (isset($noApchat['contatos'][$v])) { $la = $noApchat['contatos'][$v]; break; } }

            if ($la)
            {
                $corpo = $contato;
                $corpo['number'] = (string) $la['number'];
                // o espelho vem antes: a protecao do APChat so aceita o que estiver nele
                if ($erro = self::espelhar((int) $la['id'], $corpo))
                {
                    return self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_NENHUMA, 'Não foi possível gravar no banco do APChat: ' . $erro);
                }
                if (self::dadosIguais($contato, $la))
                {
                    $r = self::resultado(ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::OPERACAO_NENHUMA, 'Contato já estava igual no APChat.');
                    $r->apchat_contato_id = (int) $la['id'];
                    return $r;
                }
                $r = yield from self::passosAtualizar($contato, (string) $la['number']);
                $r->apchat_contato_id = $r->apchat_contato_id ?? (int) $la['id'];
                return $r;
            }

            $r = yield from self::passosCriarOuAchar($contato, $numero, true);
            if ($r->situacao === ApchatContatoLog::SITUACAO_SUCESSO && $r->operacao === ApchatContatoLog::OPERACAO_CREATE && !empty($r->apchat_contato_id) && ($erro = self::espelhar((int) $r->apchat_contato_id, $contato)))
            {
                // criado, mas ainda sem a protecao: a fila grava o espelho depois
                $r->refazer = true;
                $r->mensagem .= ' (proteção no APChat pendente: ' . $erro . ')';
            }
            return $r;
        }

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

        return yield from self::passosCriarOuAchar($contato, $numero, false);
    }

    /**
     * Cria; se o APChat responder que o contato ja existe (409 ERR_CONTACT_EXISTS - ele trata com e sem o nono digito
     * como o mesmo contato, ex.: a outra forma do numero acabou de ser criada no mesmo lote), acha pelo numero e atualiza
     * esse contato (com o espelho antes, no modo com o banco do APChat).
     */
    private static function passosCriarOuAchar(array $contato, string $numero, bool $comEspelho): Generator
    {
        $r = yield from self::passosCriar($contato);
        if (empty($r->ja_existe))
        {
            return $r;
        }

        $busca = yield from self::passosBuscar(self::variantes($numero));
        if ($busca->situacao !== 'ENCONTRADO')
        {
            return $r;
        }
        $id = (int) ($busca->contato['id'] ?? 0);
        if ($comEspelho && $id)
        {
            $corpo = $contato;
            $corpo['number'] = $busca->numero;
            if ($erro = self::espelhar($id, $corpo))
            {
                return self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_NENHUMA, 'Não foi possível gravar no banco do APChat: ' . $erro);
            }
        }
        $r = yield from self::passosAtualizar($contato, $busca->numero);
        $r->apchat_contato_id = $r->apchat_contato_id ?? ($id ?: null);
        return $r;
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
        $r->ja_existe = ($classe->tipo === 'JA_EXISTE');

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
        $ch = curl_init((self::$urlBase ?? self::$urlAtual ?? self::URL_BASE) . $rota);

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

        if ($status === 409 && $codigo === self::ERRO_JA_EXISTE)
        {
            return $saida('JA_EXISTE', 'O contato já existe no APChat (com ou sem o nono dígito).');
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
        $escritorio = ApchatApiService::escritorio();

        // o endereco vem junto do token (URL da API editavel no escritorio, 03/11/2026)
        self::$urlAtual = ApchatApiService::url($escritorio) . '/';

        return $escritorio ? trim((string) $escritorio->token_apchat) : '';
    }

    // =================================================================
    // Um contato no APChat por numero (27/10/2026)
    // =================================================================
    //
    // Todo contato da Curciol com telefone valido (pessoa_contato) e exportado. O mesmo numero em varias pessoas e um
    // contato so no APChat; o id dele fica em pessoa_contato.idapchat de todos os contatos com esse numero.

    /** filtro SQL "o telefone do contato e este numero" (com ou sem o 55); $numero = 55 + DDD + numero */
    private static function mesmoNumeroSql(string $coluna = 'telefone'): string
    {
        return "regexp_replace(COALESCE({$coluna}, ''), '\\D', '', 'g') IN (?, ?)";
    }

    private static function argsNumero(string $numero): array
    {
        return [substr($numero, 2), $numero];
    }

    /**
     * Numeros validos (55 + DDD + numero) dos contatos da pessoa, o do principal primeiro.
     *
     * @return string[]
     */
    private static function numerosDaPessoa(int $pessoa_id): array
    {
        $st = TTransaction::get()->prepare("SELECT telefone FROM pessoa_contato WHERE pessoa_id = ? ORDER BY (principal = 'S') DESC, id");
        $st->execute([$pessoa_id]);
        $numeros = [];
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $telefone)
        {
            $numero = self::normalizarTelefone($telefone);
            if ($numero !== '' && !in_array($numero, $numeros, true)) { $numeros[] = $numero; }
        }

        return $numeros;
    }

    /** numero (55 + ...) do contato principal da pessoa; '' sem principal com telefone */
    private static function numeroPrincipal(int $pessoa_id): string
    {
        $st = TTransaction::get()->prepare("SELECT telefone FROM pessoa_contato WHERE pessoa_id = ? AND principal = 'S'");
        $st->execute([$pessoa_id]);

        return self::normalizarTelefone((string) $st->fetchColumn());
    }

    /**
     * Corpo enviado ao APChat para um numero: dados do contato principal com esse numero (de qualquer pessoa) ou, sem
     * principal, do contato mais antigo. Nome = o nome do contato; sem nome, a descricao; sem os dois, o numero. O
     * principal leva tambem os dados da pessoa (CPF, nascimento, razao social), como antes.
     */
    public static function montarContatoDoNumero(string $numero): array
    {
        $st = TTransaction::get()->prepare('SELECT c.pessoa_id, c.nome, c.descricao, c.email, c.principal FROM pessoa_contato c
            WHERE ' . self::mesmoNumeroSql('c.telefone') . " ORDER BY (c.principal = 'S') DESC, c.id LIMIT 1");
        $st->execute(self::argsNumero($numero));
        $ref = $st->fetch(PDO::FETCH_ASSOC);

        $nome = trim(preg_replace('/\s+/', ' ', (string) ($ref['nome'] ?? '')));
        if ($nome === '') { $nome = trim(preg_replace('/\s+/', ' ', (string) ($ref['descricao'] ?? ''))); }

        if ($ref && $ref['principal'] === 'S' && ($pessoa = Pessoa::find((int) $ref['pessoa_id'])))
        {
            $contato = self::montarContato($pessoa);
            if ($nome !== '') { $contato['name'] = $nome; }
        }
        else
        {
            $contato = [];
            if ($nome !== '') { $contato['name'] = $nome; }
        }

        $contato['number'] = $numero;
        $contato['name'] = $contato['name'] ?? '';
        if ($contato['name'] === '') { $contato['name'] = PessoaContatoService::formatarTelefone($numero); }

        unset($contato['email']);
        $email = trim((string) ($ref['email'] ?? ''));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) { $contato['email'] = mb_strtolower($email); }

        return array_filter($contato, function ($valor) { return $valor !== null && $valor !== ''; });
    }

    /** algum contato com esse numero ja tem o id do APChat: da para tentar atualizar direto (caminho curto) */
    private static function numeroComId(string $numero): bool
    {
        $st = TTransaction::get()->prepare('SELECT 1 FROM pessoa_contato WHERE idapchat IS NOT NULL AND ' . self::mesmoNumeroSql() . ' LIMIT 1');
        $st->execute(self::argsNumero($numero));

        return (bool) $st->fetchColumn();
    }

    // =================================================================
    // Banco do APChat, espelho, fila e troca de numero (03/11/2026)
    // =================================================================

    /** @var array [pessoa_id => ['trocas' => [[antigo, novo]], 'removidos' => [numero]]] registradas pelo salvar */
    private static $trocas = [];

    /**
     * O que existe no APChat para estes numeros: null sem o banco configurado (so API); ['ok' => false] com o banco fora.
     * @return array|null ['ok' => bool, 'erro' => string, 'contatos' => [numero do APChat => linha]]
     */
    private static function lerNoBanco(array $numeros): ?array
    {
        if (!ApchatBancoService::configurado())
        {
            return null;
        }
        try
        {
            if (!ApchatBancoService::disponivel()) { throw new Exception(ApchatBancoService::erro()); }
            return ['ok' => true, 'erro' => '', 'contatos' => ApchatBancoService::contatosPorNumeros($numeros)];
        }
        catch (Throwable $e)
        {
            return ['ok' => false, 'erro' => $e->getMessage(), 'contatos' => []];
        }
    }

    /** Grava o espelho (o que a Curciol manda para o contato); devolve a mensagem de erro ou '' */
    private static function espelhar(int $id, array $corpo): string
    {
        try
        {
            ApchatBancoService::gravarEspelho($id, (string) ($corpo['name'] ?? ''), (string) $corpo['number'], $corpo['email'] ?? null);
            return '';
        }
        catch (Throwable $e)
        {
            return $e->getMessage();
        }
    }

    /** O contato do APChat ja tem tudo o que a Curciol mandaria? (campos nao enviados nao contam: o update os mantem) */
    private static function dadosIguais(array $contato, array $noApchat): bool
    {
        $data = function ($v) {
            $v = trim((string) $v);
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) { return "{$m[3]}-{$m[2]}-{$m[1]}"; }
            return substr($v, 0, 10);
        };
        foreach ($contato as $campo => $valor)
        {
            if ($campo === 'number') { continue; }
            $la = $noApchat[$campo] ?? null;
            if ($campo === 'email') { $iguais = mb_strtolower(trim((string) $valor)) === mb_strtolower(trim((string) $la)); }
            elseif ($campo === 'birthdayDate') { $iguais = $data($valor) === $data($la); }
            else { $iguais = trim((string) $valor) === trim((string) $la); }
            if (!$iguais) { return false; }
        }
        return true;
    }

    /** Envio que falhou entra na fila (reenvio sozinho); o que deu certo sai dela. */
    private static function filaDoEnvio(string $numero, ?int $pessoa_id, object $r): void
    {
        try
        {
            if ($r->situacao === ApchatContatoLog::SITUACAO_ERRO || !empty($r->refazer))
            {
                ApchatFilaService::enfileirar(ApchatFilaService::ENVIO, $numero, $pessoa_id, null, $r->mensagem);
            }
            elseif ($r->situacao === ApchatContatoLog::SITUACAO_SUCESSO)
            {
                ApchatFilaService::concluir(ApchatFilaService::ENVIO, $numero);
            }
        }
        catch (Throwable $e)
        {
            // a fila nunca impede o resto
        }
    }

    /**
     * O salvar da pessoa avisa as trocas de numero (contato que ja existia e mudou de numero) e os numeros que sairam
     * (contato excluido ou sem telefone). Usado por processarTrocas, no envio logo depois do commit.
     *
     * @param array $trocas    [[numero antigo, numero novo]] (55 + DDD + numero)
     * @param array $removidos [numero]
     */
    public static function registrarTrocas(int $pessoa_id, array $trocas, array $removidos): void
    {
        self::$trocas[$pessoa_id] = ['trocas' => $trocas, 'removidos' => $removidos];
    }

    /** Algum contato da Curciol ainda tem esse numero (com ou sem o nono digito)? */
    private static function numeroEmUso(string $numero): bool
    {
        foreach (self::variantes($numero) as $v)
        {
            $st = TTransaction::get()->prepare('SELECT 1 FROM pessoa_contato WHERE ' . self::mesmoNumeroSql() . ' LIMIT 1');
            $st->execute(self::argsNumero($v));
            if ($st->fetchColumn()) { return true; }
        }
        return false;
    }

    /**
     * Troca de numero: o contato do APChat do numero antigo passa o historico para o do numero novo e e excluido (so se
     * nenhum outro contato da Curciol ainda usa o numero antigo). Numero que saiu da Curciol: o contato do APChat fica
     * travado (ex-contato). Falha -> fila. Usa a transacao aberta.
     *
     * @param object[] $resultados [numero => resultado do envio desta pessoa]
     * @return object[] [numero => resultado com erro] para o resumo do salvar
     */
    private static function processarTrocas(int $pessoa_id, array $resultados, string $origem): array
    {
        $registro = self::$trocas[$pessoa_id] ?? null;
        unset(self::$trocas[$pessoa_id]);
        if (!$registro || (!$registro['trocas'] && !$registro['removidos']) || !ApchatBancoService::configurado())
        {
            return [];
        }

        $problemas = [];
        $antigos = array_merge(array_column($registro['trocas'], 0), $registro['removidos']);
        $noApchat = self::lerNoBanco($antigos);

        foreach ($registro['trocas'] as [$antigo, $novo])
        {
            if (self::numeroEmUso($antigo)) { continue; }   // o numero antigo continua em outro cadastro: fica como esta
            $r = self::transferirNumero($antigo, $novo, $resultados[$novo] ?? null, $noApchat);
            if ($r)
            {
                self::registrar($pessoa_id, $origem, $r, $novo, $antigo);
                if ($r->situacao === ApchatContatoLog::SITUACAO_ERRO) { $problemas[$novo] = $r; }
            }
        }

        foreach ($registro['removidos'] as $numero)
        {
            if (self::numeroEmUso($numero) || !$noApchat || !$noApchat['ok']) { continue; }
            foreach (self::variantes($numero) as $v)
            {
                if (isset($noApchat['contatos'][$v]))
                {
                    try { ApchatBancoService::desligarEspelho((int) $noApchat['contatos'][$v]['id']); } catch (Throwable $e) {}
                }
            }
        }

        return $problemas;
    }

    /**
     * Uma transferencia (antigo -> novo). $envio: resultado do envio do numero novo (traz o id do contato novo).
     * @return object|null resultado para o log (null: nada a fazer)
     */
    private static function transferirNumero(string $antigo, string $novo, ?object $envio, ?array $noApchat): ?object
    {
        $idAntigo = null;
        if ($noApchat && $noApchat['ok'])
        {
            foreach (self::variantes($antigo) as $v) { if (isset($noApchat['contatos'][$v])) { $idAntigo = (int) $noApchat['contatos'][$v]['id']; break; } }
            if (!$idAntigo) { return null; }                // o numero antigo nao tinha contato no APChat
        }
        $idNovo = (int) ($envio->apchat_contato_id ?? 0);

        if (!$noApchat || !$noApchat['ok'] || !$idNovo || ($envio->situacao ?? '') === ApchatContatoLog::SITUACAO_ERRO)
        {
            $erro = !$noApchat || !$noApchat['ok'] ? 'banco do APChat indisponível' : 'o contato do número novo ainda não foi enviado';
            ApchatFilaService::enfileirar(ApchatFilaService::TRANSFERENCIA, $novo, null, $idAntigo, $erro, $antigo);
            $r = self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_TRANSFERENCIA,
                "Troca de número: a passagem do histórico de {$antigo} para {$novo} no APChat ficou na fila ({$erro}) e será feita sozinha.");
            return $r;
        }
        if ($idAntigo === $idNovo) { return null; }         // mesmo contato (so o nono digito)

        try
        {
            $resumo = ApchatBancoService::transferir($idAntigo, $idNovo);
            ApchatFilaService::concluir(ApchatFilaService::TRANSFERENCIA, $novo, $idAntigo);
            $r = self::resultado(ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::OPERACAO_TRANSFERENCIA,
                "Troca de número: histórico do contato {$idAntigo} ({$antigo}) passado para o contato {$idNovo} ({$novo}) no APChat. {$resumo}");
            $r->apchat_contato_id = $idNovo;
            return $r;
        }
        catch (Throwable $e)
        {
            ApchatFilaService::enfileirar(ApchatFilaService::TRANSFERENCIA, $novo, null, $idAntigo, $e->getMessage(), $antigo);
            return self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_TRANSFERENCIA,
                "Troca de número: a passagem do histórico para {$novo} falhou e ficou na fila: " . $e->getMessage());
        }
    }

    /**
     * Fila: reenvia um numero (dados lidos agora) e, na transferencia, faz a passagem do historico.
     * @return object resultado (situacao SUCESSO = sai da fila)
     */
    public static function reprocessar(string $motivo, string $numero, ?int $contatoAntigo, ?string $numeroAntigo): object
    {
        TTransaction::open(self::$database);
        try
        {
            $token = self::token();
            $dono = self::donoDoNumero($numero);
            if ($token === '' || !$dono)
            {
                TTransaction::close();
                // numero que saiu da Curciol ou integracao desligada: nada mais a fazer
                return self::resultado(ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::OPERACAO_NENHUMA, $dono ? 'Integração desligada.' : 'O número não está mais na Curciol.');
            }
            self::travar('apchat:' . $numero);
            $noApchat = self::lerNoBanco(array_filter([$numero, $numeroAntigo]));
            $r = self::rodar(self::passosDecidir(self::montarContatoDoNumero($numero), $noApchat, $numero, self::numeroComId($numero)), $token);
            self::registrar($dono, ApchatContatoLog::ORIGEM_FILA, $r, $numero, null);
            self::gravarIdApchatDoNumero($numero, $r);

            if ($motivo === ApchatFilaService::TRANSFERENCIA && $r->situacao === ApchatContatoLog::SITUACAO_SUCESSO && ($contatoAntigo || $numeroAntigo))
            {
                if ($numeroAntigo && self::numeroEmUso($numeroAntigo))
                {
                    $t = null;
                }
                else
                {
                    $chave = $numeroAntigo ?: 'id' . $contatoAntigo;
                    $mapa = $noApchat ?: ['ok' => false, 'erro' => 'banco do APChat não configurado', 'contatos' => []];
                    if ($contatoAntigo)
                    {
                        // o contato antigo ja conhecido (id da fila), mesmo que o numero dele tenha mudado no APChat
                        $mapa['contatos'][$chave] = ['id' => $contatoAntigo];
                    }
                    $t = self::transferirNumero($chave, $numero, $r, $mapa);
                }
                if ($t)
                {
                    self::registrar($dono, ApchatContatoLog::ORIGEM_FILA, $t, $numero, $numeroAntigo);
                    $r = $t;
                }
            }
            TTransaction::close();
            return $r;
        }
        catch (Throwable $e)
        {
            TTransaction::rollbackAll();
            return self::resultado(ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::OPERACAO_NENHUMA, $e->getMessage());
        }
    }

    /** Pessoa dona do numero para o log (quem o tem como principal; senao o contato mais antigo) */
    private static function donoDoNumero(string $numero): ?int
    {
        $st = TTransaction::get()->prepare('SELECT pessoa_id FROM pessoa_contato WHERE ' . self::mesmoNumeroSql() . " ORDER BY (principal = 'S') DESC, id LIMIT 1");
        $st->execute(self::argsNumero($numero));
        $id = $st->fetchColumn();
        return $id ? (int) $id : null;
    }

    /** id do APChat em todos os contatos com esse numero (e em pessoa.idapchat de quem o tem como principal) */
    private static function gravarIdApchatDoNumero(string $numero, object $r): void
    {
        $id = (int) ($r->apchat_contato_id ?? 0);
        if ($id <= 0 || !in_array($r->situacao, [ApchatContatoLog::SITUACAO_SUCESSO, ApchatContatoLog::SITUACAO_PENDENTE], true))
        {
            return;
        }

        $conn = TTransaction::get();
        $conn->prepare('UPDATE pessoa_contato SET idapchat = ? WHERE ' . self::mesmoNumeroSql() . ' AND idapchat IS DISTINCT FROM ?')
             ->execute(array_merge([$id], self::argsNumero($numero), [$id]));
        $conn->prepare("UPDATE pessoa SET idapchat = ? WHERE idapchat IS DISTINCT FROM ? AND id IN (SELECT pessoa_id FROM pessoa_contato WHERE principal = 'S' AND " . self::mesmoNumeroSql() . ')')
             ->execute(array_merge([$id, $id], self::argsNumero($numero)));
    }

    /**
     * Um resultado para a pessoa a partir dos de cada numero: erro se algum deu erro, pendente se algum ficou
     * pendente; a operacao e a do numero principal.
     *
     * @param object[] $porNumero [numero => resultado]
     */
    private static function resumirResultados(array $porNumero, string $principal): object
    {
        $base = $porNumero[$principal] ?? reset($porNumero);
        $r = clone $base;
        $problemas = array_filter($porNumero, function ($x) { return $x->situacao === ApchatContatoLog::SITUACAO_ERRO || $x->situacao === ApchatContatoLog::SITUACAO_PENDENTE; });
        foreach ([ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::SITUACAO_PENDENTE] as $pior)
        {
            if ($x = array_values(array_filter($problemas, function ($y) use ($pior) { return $y->situacao === $pior; }))[0] ?? null)
            {
                $r = clone $x;
                break;
            }
        }
        if (count($porNumero) > 1)
        {
            $r->mensagem .= ' (' . count($porNumero) . ' contatos com telefone enviados' . ($problemas ? ', ' . count($problemas) . ' com problema' : '') . ')';
        }

        return $r;
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
