<?php
/**
 * Templates da Meta pelo canal oficial do APChat (17/11/2026).
 *
 * O APChat nao guarda os templates: a tela "Integracoes Meta > Templates" do APChat le na hora, na Meta, com a conta WhatsApp
 * Business (wabaId) e o token da Meta (bmToken) do proprio canal. A Curciol faz o mesmo: pega os dois pela funcao
 * curciol.f_waba do banco do APChat (so o canal da integracao do Escritorio, so se for oficial e desta empresa), le a lista
 * na Meta (somente leitura) e grava na Curciol os templates com ORIGEM = META. O token nunca e gravado.
 *
 * Origem META: o texto e o aprovado na Meta (nao se edita na Curciol); categoria, descricao, habilitar e as respostas sim.
 * Envio: so pelo canal oficial, pelo template aprovado (POST /templateBody do APChat), com os valores das variaveis por nome
 * ({{cliente}}, {{data_inicial}}...). Origem CURCIOL: o texto da Curciol, para qualquer canal (como sempre).
 */
class ApchatWabaService
{
    const GRAPH         = 'https://graph.facebook.com';
    const VERSAO_PADRAO = '21.0';
    const PAGINAS_MAX   = 20;

    const APROVADO = 'APPROVED';
    const REMOVIDO = 'REMOVIDO';

    const SITUACOES = [
        'APPROVED'         => 'Aprovado',
        'PENDING'          => 'Em análise',
        'REJECTED'         => 'Reprovado',
        'PAUSED'           => 'Pausado',
        'DISABLED'         => 'Desativado',
        'IN_APPEAL'        => 'Em recurso',
        'PENDING_DELETION' => 'Sendo excluído',
        'DELETED'          => 'Excluído',
        'LIMIT_EXCEEDED'   => 'Limite excedido',
        self::REMOVIDO     => 'Não existe mais na Meta',
    ];

    /** @var callable|null testes: (url, token) => ['status' => int, 'corpo' => string, 'erro' => string] */
    private static $transporte = null;

    public static function usarTransporte(?callable $transporte): void
    {
        self::$transporte = $transporte;
    }

    public static function situacao(?string $status): string
    {
        return self::SITUACOES[(string) $status] ?? (string) $status;
    }

    // =================================================================
    // Canal e lista na Meta
    // =================================================================

    /** Codigo da integracao no fim da URL do Escritorio (.../v2/api/external/<id>). */
    public static function idDaIntegracao(): string
    {
        $url = ApchatApiService::url(ApchatApiService::escritorio());
        $id = (string) substr(strrchr('/' . $url, '/'), 1);
        return preg_match('/^[0-9a-f-]{36}$/i', $id) ? strtolower($id) : '';
    }

    /**
     * Canal oficial da integracao do Escritorio, com a conta e o token da Meta. Lanca excecao com o motivo legivel.
     * @return array {canal_id, canal, tipo, waba_id, token, versao}
     */
    public static function canal(): array
    {
        if (!ApchatApiService::configurada())
        {
            throw new Exception('APChat não configurado: informe a URL e o token em Configurações do escritório.');
        }
        $config = IntegracaoAcessoService::configuracaoApchat();
        if ($config !== 'ok')
        {
            throw new Exception((string) IntegracaoAcessoService::motivoApchat($config));
        }
        try
        {
            $canal = ApchatBancoService::wabaDaIntegracao(self::idDaIntegracao());
        }
        catch (Throwable $e)
        {
            if (stripos($e->getMessage(), 'f_waba') !== false)
            {
                throw new Exception('O banco do APChat ainda não tem a leitura dos templates (curciol.f_waba). Atualize as funções da Curciol no APChat.');
            }
            throw $e;
        }
        if (!$canal)
        {
            throw new Exception('A integração configurada no Escritório não tem canal no APChat.');
        }
        if (($canal['tipo'] ?? '') !== 'waba')
        {
            throw new Exception('O canal da integração do Escritório (' . ($canal['canal'] ?? '?') . ') não é o oficial da Meta (WABA): templates da Meta só existem no canal oficial.');
        }
        if (trim((string) ($canal['waba_id'] ?? '')) === '' || trim((string) ($canal['token'] ?? '')) === '')
        {
            throw new Exception('O canal oficial ' . ($canal['canal'] ?? '') . ' não tem a conta da Meta (WABA) ligada no APChat.');
        }
        return $canal;
    }

    /** Templates da conta WhatsApp Business do canal, lidos na Meta (todas as paginas). */
    public static function templatesDaMeta(array $canal): array
    {
        $versao = preg_match('/^\d+(\.\d+)?$/', trim((string) ($canal['versao'] ?? ''))) ? trim($canal['versao']) : self::VERSAO_PADRAO;
        $url = self::GRAPH . '/v' . $versao . '/' . rawurlencode(trim((string) $canal['waba_id'])) . '/message_templates?'
             . http_build_query(['fields' => 'id,name,status,language,category,parameter_format,components', 'limit' => 100]);

        $lista = [];
        for ($pagina = 0; $url !== '' && $pagina < self::PAGINAS_MAX; $pagina++)
        {
            $r = self::get($url, trim((string) $canal['token']));
            $json = json_decode((string) $r['corpo'], true);
            if ($r['status'] !== 200 || !is_array($json) || !isset($json['data']))
            {
                $erro = is_array($json) ? trim((string) ($json['error']['message'] ?? '')) : '';
                throw new Exception('A Meta não listou os templates: ' . ($r['status'] === 0 ? 'sem resposta' . ($r['erro'] !== '' ? " ({$r['erro']})" : '') : "HTTP {$r['status']}")
                                    . ($erro !== '' ? " - {$erro}" : '') . '.');
            }
            foreach ($json['data'] as $t) { if (is_array($t) && !empty($t['name'])) { $lista[] = $t; } }
            $proxima = (string) ($json['paging']['next'] ?? '');
            $url = strpos($proxima, self::GRAPH . '/') === 0 ? $proxima : '';
        }
        return $lista;
    }

    private static function get(string $url, string $token): array
    {
        if (self::$transporte) { return call_user_func(self::$transporte, $url, $token); }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 20,
        ]);
        $texto = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = (string) curl_error($ch);
        curl_close($ch);

        return ['status' => $texto === false ? 0 : $status, 'corpo' => $texto === false ? '' : (string) $texto, 'erro' => $erro];
    }

    // =================================================================
    // Importacao
    // =================================================================

    /**
     * Importa (cria ou atualiza) os templates da Meta no escritorio. Template novo: categoria pelo nome, habilitado se aprovado e
     * com variaveis conhecidas; na confirmacao com botoes, as respostas sao os textos dos botoes. Template que ja existe: texto,
     * situacao e componentes da Meta (categoria, descricao e respostas ficam como o usuario deixou). Sumiu da Meta: desabilitado.
     * @return array {novos, atualizados, removidos, avisos: string[]}
     */
    public static function importar(int $escritorioId): array
    {
        $canal = self::canal();
        $waba = trim((string) $canal['waba_id']);
        $resumo = ['novos' => 0, 'atualizados' => 0, 'removidos' => 0, 'avisos' => []];
        $vistos = [];
        $agora = date('Y-m-d H:i:s');

        foreach (self::templatesDaMeta($canal) as $t)
        {
            $nome = (string) $t['name'];
            $idioma = (string) ($t['language'] ?? '');
            $status = strtoupper((string) ($t['status'] ?? ''));

            $obj = TemplateEscritorio::where('escritorio_id', '=', $escritorioId)->where('origem', '=', TemplateEscritorio::ORIGEM_META)
                                     ->where('meta_waba_id', '=', $waba)->where('meta_nome', '=', $nome)->where('meta_idioma', '=', $idioma)->first();
            $novo = !$obj;
            $statusAntes = $novo ? null : (string) $obj->meta_status;

            if ($novo)
            {
                $obj = new TemplateEscritorio();
                $obj->escritorio_id = $escritorioId;
                $obj->origem = TemplateEscritorio::ORIGEM_META;
                $obj->chave = 'META_' . strtoupper(uniqid());
                $obj->tipo_template = TemplateEscritorio::WHATSAPP;
                $obj->categoria = self::categoriaPeloNome($nome);
                $obj->descricao = mb_strtoupper(trim(str_replace('_', ' ', $nome)) . ($idioma !== '' && $idioma !== 'pt_BR' ? " ({$idioma})" : ''));
                $obj->ignorar_maiusculas = 'T';
            }

            $obj->meta_nome = $nome;
            $obj->meta_idioma = $idioma;
            $obj->meta_id = (string) ($t['id'] ?? '');
            $obj->meta_waba_id = $waba;
            $obj->meta_status = $status;
            $obj->meta_categoria = (string) ($t['category'] ?? '');
            $obj->meta_formato = strtoupper((string) ($t['parameter_format'] ?? '')) ?: (self::temVariavelPorPosicao($t['components'] ?? []) ? 'POSITIONAL' : 'NAMED');
            $obj->meta_componentes = json_encode(array_values((array) ($t['components'] ?? [])), JSON_UNESCAPED_UNICODE);
            $obj->meta_sincronizado_em = $agora;

            $partes = self::partes($obj);
            $obj->titulo = $partes['cabecalho'] !== '' ? $partes['cabecalho'] : $obj->descricao;
            $obj->template = $partes['texto'];

            $problema = self::problemaDoTemplate($obj);
            if ($status !== self::APROVADO || $problema !== null)
            {
                $obj->habilitado = 'F';
            }
            elseif ($novo || $statusAntes !== self::APROVADO)
            {
                $obj->habilitado = 'T'; // acabou de ser aprovado
            }

            if ($novo && $obj->categoria === TemplateEscritorio::CONFIRMACAO)
            {
                $botoes = $partes['botoes'];
                $obj->respostas_confirmar = $botoes[0] ?? 'Sim';
                $obj->respostas_cancelar = $botoes[1] ?? 'Não';
            }

            $obj->store();
            $vistos[] = (int) $obj->id;
            $resumo[$novo ? 'novos' : 'atualizados']++;
            if ($problema !== null)
            {
                $resumo['avisos'][] = "{$nome}: {$problema}";
            }
        }

        // os desta conta que nao vieram mais (excluidos na Meta): saem da Curciol; o que ja foi usado em mensagem fica so para o
        // historico (desabilitado, fora da lista de templates)
        $sumidos = TemplateEscritorio::where('escritorio_id', '=', $escritorioId)->where('origem', '=', TemplateEscritorio::ORIGEM_META)
                                     ->where('meta_waba_id', '=', $waba);
        if ($vistos) { $sumidos->where('id', 'not in', $vistos); }
        foreach ($sumidos->load() ?: [] as $obj)
        {
            if (self::usadoEmMensagem((int) $obj->id))
            {
                if ($obj->meta_status !== self::REMOVIDO)
                {
                    $obj->meta_status = self::REMOVIDO;
                    $obj->habilitado = 'F';
                    $obj->meta_sincronizado_em = $agora;
                    $obj->store();
                    $resumo['removidos']++;
                }
                continue;
            }
            TemplateAcao::where('template_escritorio_id', '=', $obj->id)->delete();
            $obj->delete();
            $resumo['removidos']++;
        }

        return $resumo;
    }

    /** Template ja usado em mensagem enviada ou agendada (o historico aponta para ele). */
    public static function usadoEmMensagem(int $id): bool
    {
        return Mensagem::where('template_escritorio_id', '=', $id)->count() > 0
            || MensagemAgendada::where('template_escritorio_id', '=', $id)->count() > 0;
    }

    /** Filtro da lista de templates: esconde os da Meta que nao existem mais la (guardados so pelo historico). */
    public static function filtroSemRemovidos(): TFilter
    {
        return new TFilter('id', 'not in', "(SELECT id FROM template_escritorio WHERE origem = '" . TemplateEscritorio::ORIGEM_META . "' AND meta_status = '" . self::REMOVIDO . "')");
    }

    /** Categoria da Curciol pelo nome do template na Meta (o usuario pode trocar). */
    public static function categoriaPeloNome(string $nome): string
    {
        $n = mb_strtolower(TratamentosService::removerAcentos($nome));
        if (strpos($n, 'cancel') !== false)                                     { return TemplateEscritorio::CANCELAMENTO; }
        if (strpos($n, 'lembrete') !== false)                                   { return TemplateEscritorio::LEMBRETE; }
        if (preg_match('/confirmad[oa]/', $n))                                  { return TemplateEscritorio::CONFIRMADO; }
        if (strpos($n, 'confirma') !== false)                                   { return TemplateEscritorio::CONFIRMACAO; }
        return TemplateEscritorio::OUTROS;
    }

    // =================================================================
    // Texto e variaveis
    // =================================================================

    /** Componentes da Meta gravados no template. */
    public static function componentes(TemplateEscritorio $t): array
    {
        $c = json_decode((string) $t->meta_componentes, true);
        return is_array($c) ? $c : [];
    }

    /**
     * Cabecalho (texto), corpo, rodape e botoes do template da Meta, com as variaveis no padrao {{nome}}.
     * texto = corpo + rodape (o que fica como "Template" na Curciol).
     */
    public static function partes(TemplateEscritorio $t): array
    {
        $r = ['cabecalho' => '', 'corpo' => '', 'rodape' => '', 'botoes' => [], 'texto' => ''];
        foreach (self::componentes($t) as $c)
        {
            $tipo = strtoupper((string) ($c['type'] ?? ''));
            if ($tipo === 'HEADER' && strtoupper((string) ($c['format'] ?? 'TEXT')) === 'TEXT') { $r['cabecalho'] = TemplateEscritorio::normalizarVariaveis((string) ($c['text'] ?? '')); }
            if ($tipo === 'BODY')   { $r['corpo'] = TemplateEscritorio::normalizarVariaveis((string) ($c['text'] ?? '')); }
            if ($tipo === 'FOOTER') { $r['rodape'] = (string) ($c['text'] ?? ''); }
            if ($tipo === 'BUTTONS')
            {
                foreach ((array) ($c['buttons'] ?? []) as $b) { if (trim((string) ($b['text'] ?? '')) !== '') { $r['botoes'][] = trim((string) $b['text']); } }
            }
        }
        $r['texto'] = trim($r['corpo'] . ($r['rodape'] !== '' ? "\n\n" . $r['rodape'] : ''));
        return $r;
    }

    /** Variaveis do template que a Curciol nao sabe preencher. */
    public static function variaveisDesconhecidas(TemplateEscritorio $t): array
    {
        $partes = self::partes($t);
        $conhecidas = array_keys(self::valoresVazios());
        return array_values(array_diff(TemplateEscritorio::variaveisDoTexto($partes['cabecalho'] . "\n" . $partes['corpo']), $conhecidas));
    }

    /** Por que o template nao pode ser enviado (alem da situacao na Meta); null = pode. */
    public static function problemaDoTemplate(TemplateEscritorio $t): ?string
    {
        if (strtoupper((string) $t->meta_formato) === 'POSITIONAL' && self::temVariavelPorPosicao(self::componentes($t)))
        {
            return 'variáveis por posição ({{1}}, {{2}}...). Recrie o template na Meta com variáveis por nome, como {{cliente}}.';
        }
        // 17/11/2026 (teste no app1): {{$cliente}} nao e variavel para a Meta (nome so com minusculas, numeros e _): ela aprova como
        // TEXTO FIXO, recusa qualquer parametro (#132000) e, sem parametros, o cliente receberia "{{$cliente}}" escrito
        $invalidas = self::variaveisInvalidasNaMeta($t);
        if ($invalidas)
        {
            return 'na Meta, ' . implode(', ', $invalidas) . ' não é variável (nome só com letras minúsculas, números e _): a Meta aprovou como texto fixo. '
                 . 'Recrie o template na Meta com ' . implode(', ', array_map(function ($v) { return TemplateEscritorio::normalizarVariaveis($v); }, $invalidas)) . '.';
        }
        $desconhecidas = self::variaveisDesconhecidas($t);
        if ($desconhecidas)
        {
            return 'variáveis que a Curciol não conhece: ' . implode(', ', array_map(function ($v) { return '{{' . $v . '}}'; }, $desconhecidas)) . '.';
        }
        return null;
    }

    /** Marcacoes {{...}} do texto da Meta que nao sao variavel valida (ex.: {{$cliente}}), como estao la. */
    public static function variaveisInvalidasNaMeta(TemplateEscritorio $t): array
    {
        $r = [];
        foreach (self::componentes($t) as $c)
        {
            if (!in_array(strtoupper((string) ($c['type'] ?? '')), ['HEADER', 'BODY'], true)) { continue; }
            preg_match_all('/\{\{\s*([^{}]*?)\s*\}\}/', (string) ($c['text'] ?? ''), $m);
            foreach ($m[1] as $i => $nome)
            {
                if (!preg_match('/^(?:[a-z][a-z0-9_]*|\d+)$/', $nome)) { $r[$m[0][$i]] = $m[0][$i]; }
            }
        }
        return array_values($r);
    }

    private static function temVariavelPorPosicao(array $componentes): bool
    {
        foreach ($componentes as $c)
        {
            if (preg_match('/\{\{\s*\d+\s*\}\}/', (string) ($c['text'] ?? ''))) { return true; }
        }
        return false;
    }

    /** Nomes das variaveis que a Curciol preenche (os mesmos do texto da Curciol). */
    private static function valoresVazios(): array
    {
        $nomes = [];
        foreach (array_keys(MensagemService::variaveis()) as $v) { $nomes[trim($v, '{}')] = ''; }
        foreach (['data_consulta', 'profissional_foto', 'link_detalhe', 'link_atendimento_online', 'usuario', 'senha'] as $v) { $nomes[$v] = ''; }
        return $nomes;
    }

    // =================================================================
    // Envio
    // =================================================================

    /** Confere antes de enviar: aprovado, variaveis certas e a integracao do Escritorio no canal da mesma conta da Meta. */
    public static function exigirEnvio(TemplateEscritorio $t): array
    {
        if ($t->meta_status !== self::APROVADO)
        {
            throw new Exception("O template \"{$t->descricao}\" ainda não está aprovado na Meta (" . self::situacao($t->meta_status) . '). Use Importar da Meta para atualizar a situação.');
        }
        $problema = self::problemaDoTemplate($t);
        if ($problema !== null)
        {
            throw new Exception("O template \"{$t->descricao}\" não pode ser enviado: {$problema}");
        }
        $canal = self::canal();
        if (trim((string) $canal['waba_id']) !== trim((string) $t->meta_waba_id))
        {
            throw new Exception("O template \"{$t->descricao}\" é de outra conta da Meta, não da do canal da integração do Escritório (" . ($canal['canal'] ?? '') . '). Importe os templates do canal atual.');
        }
        return $canal;
    }

    /**
     * Template da Meta para o agendamento: {name, language, components} com os valores de cada variavel pelo nome que esta
     * na Meta (parameter_name). Valor vazio vira "-" (a Meta recusa parametro vazio).
     */
    public static function montar(TemplateEscritorio $t, Agendamento $agendamento): array
    {
        $valores = TemplateEscritorio::valoresAgendamento($agendamento);
        $componentes = [];
        foreach (self::componentes($t) as $c)
        {
            $tipo = strtoupper((string) ($c['type'] ?? ''));
            if (!in_array($tipo, ['HEADER', 'BODY'], true) || ($tipo === 'HEADER' && strtoupper((string) ($c['format'] ?? 'TEXT')) !== 'TEXT'))
            {
                continue;
            }
            preg_match_all('/\{\{\s*([^{}]+?)\s*\}\}/', (string) ($c['text'] ?? ''), $m);
            $parametros = [];
            $usados = [];
            foreach ($m[1] as $bruto)
            {
                if (isset($usados[$bruto])) { continue; }
                $usados[$bruto] = true;
                $nome = trim(TemplateEscritorio::normalizarVariaveis('{{' . $bruto . '}}'), '{}');
                $valor = trim((string) ($valores[$nome] ?? ''));
                $parametros[] = ['type' => 'text', 'parameter_name' => $bruto, 'text' => $valor !== '' ? $valor : '-'];
            }
            if ($parametros)
            {
                $componentes[] = ['type' => strtolower($tipo), 'parameters' => $parametros];
            }
        }

        $template = ['name' => (string) $t->meta_nome, 'language' => ['code' => (string) $t->meta_idioma ?: 'pt_BR']];
        if ($componentes) { $template['components'] = $componentes; }
        return $template;
    }

    /** O que o cliente recebe, em texto (fica no historico): cabecalho, corpo, rodape e os botoes. */
    public static function textoEnviado(TemplateEscritorio $t, Agendamento $agendamento): string
    {
        $p = self::partes($t);
        $valores = TemplateEscritorio::valoresAgendamento($agendamento);
        $texto = [];
        if ($p['cabecalho'] !== '') { $texto[] = '*' . TemplateEscritorio::trocar($p['cabecalho'], $valores) . '*'; }
        $texto[] = TemplateEscritorio::trocar($p['corpo'], $valores);
        if ($p['rodape'] !== '') { $texto[] = $p['rodape']; }
        if ($p['botoes']) { $texto[] = implode('  ', array_map(function ($b) { return "[{$b}]"; }, $p['botoes'])); }
        return trim(implode("\n\n", array_filter($texto, 'strlen')));
    }
}
