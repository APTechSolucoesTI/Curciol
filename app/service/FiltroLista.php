<?php
/**
 * Filtros das listagens que se somam (20/10/2026).
 *
 * As listagens tem ate tres lugares de filtro: o formulario da cortina (botao Filtros), o cabecalho das colunas e os
 * botoes rapidos (ex.: Vencidas, A vencer, Em aberto). Antes cada busca lia so o formulario que a disparou e apagava
 * os outros filtros. Agora a busca parte dos filtros ja aplicados (guardados na sessao em <Classe>_filter_data) e so
 * troca os campos do formulario que disparou a busca: o resto continua valendo.
 */
class FiltroLista
{
    /**
     * Filtros aplicados + campos do formulario que disparou a busca (inclusive os esvaziados, que deixam de filtrar)
     * + $param['__filtros_extra'] (botoes rapidos).
     */
    public static function acumular(string $classe, $dadosFormulario, array $param = []): stdClass
    {
        $dados = (array) (TSession::getValue($classe . '_filter_data') ?: []);
        foreach ((array) $dadosFormulario as $campo => $valor) {
            $dados[$campo] = $valor;
        }
        foreach ((array) ($param['__filtros_extra'] ?? []) as $campo => $valor) {
            $dados[$campo] = $valor;
        }
        return (object) $dados;
    }

    /**
     * Botao rapido liga/desliga: aplica os valores somando aos filtros atuais; se ja estavam aplicados, tira.
     * @return array valores para __filtros_extra
     */
    public static function alternar(string $classe, array $valores): array
    {
        $atual = (array) (TSession::getValue($classe . '_filter_data') ?: []);
        $ligado = true;
        foreach ($valores as $campo => $valor) {
            if ((string) ($atual[$campo] ?? '') !== (string) $valor) {
                $ligado = false;
            }
        }
        if ($ligado) {
            foreach ($valores as $campo => $valor) {
                $valores[$campo] = '';
            }
        }
        return $valores;
    }

    /**
     * Pessoas de um grupo, para o autocomplete dos filtros de nome (TDBUniqueSearch com chave = nome: o filtro continua
     * sendo pelo nome, so que escolhido numa lista).
     */
    public static function criterioGrupo(string $grupoId): TCriteria
    {
        $c = new TCriteria();
        $c->add(new TFilter('id', 'in', '(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = ' . (int) $grupoId . ')'));
        return $c;
    }

    /** Pessoas que aparecem na listagem (ex.: clientes de requisicao), para o autocomplete so sugerir quem tem registro. */
    public static function criterioPessoasDe(string $tabela, string $coluna): TCriteria
    {
        $tabela = preg_replace('/[^a-z_]/', '', $tabela);
        $coluna = preg_replace('/[^a-z_]/', '', $coluna);
        $c = new TCriteria();
        $c->add(new TFilter('id', 'in', "(SELECT DISTINCT {$coluna} FROM {$tabela} WHERE {$coluna} IS NOT NULL)"));
        return $c;
    }

    /** Aba fechada e aberta de novo: a tela comeca sem filtros (chamado pelo engine.php com curciol_limpar_filtros=1). */
    public static function limparSessao(string $classe): void
    {
        $classe = preg_replace('/[^A-Za-z0-9_]/', '', $classe);
        foreach (['_filter_data', '_filters', '_filtros', '_colunas', '_parametros'] as $sufixo) {
            TSession::setValue($classe . $sufixo, null);
        }
    }

    /**
     * Faixa "Filtros aplicados" (22/10/2026): manda para a tela os filtros ativos da cortina e dos botoes rapidos
     * (o funil do cabecalho a tela le sozinha, curciol-filtros.js). Chamar no fim do onReload da listagem.
     * $extras: campos que nao estao em formulario nenhum (botoes rapidos): campo => [rotulo, [valor => texto]].
     * $prontos: itens ja montados [rotulo, valor, campos] (ex.: parametros da janela do Extrato).
     */
    public static function faixa(string $classe, $form, $datagridForm, $datagrid, array $extras = [], array $prontos = []): void
    {
        $classe = preg_replace('/[^A-Za-z0-9_]/', '', $classe);
        $dados = (array) (TSession::getValue($classe . '_filter_data') ?: []);
        $doCabecalho = ($datagridForm && method_exists($datagridForm, 'getFields')) ? array_keys($datagridForm->getFields()) : [];

        $itens = [];
        $usados = [];
        foreach (self::camposPorRotulo($form) as $grupo) {
            $partes = [];
            $campos = [];
            foreach ($grupo['campos'] as [$nome, $campo, $antes]) {
                if (in_array($nome, $doCabecalho, true) || !array_key_exists($nome, $dados)) { continue; }
                $texto = self::textoDoValor($campo, $dados[$nome]);
                $campos[] = $nome;
                $usados[] = $nome;
                if ($texto === '') { continue; }
                $partes[] = (count($partes) && $antes !== '') ? "{$antes} {$texto}" : $texto;
            }
            if ($partes) {
                $itens[] = ['rotulo' => $grupo['rotulo'], 'valor' => implode(' ', $partes), 'campos' => $campos];
            }
        }
        foreach ($extras as $nome => [$rotulo, $textos]) {
            $valor = $dados[$nome] ?? '';
            if (is_scalar($valor) && (string) $valor !== '' && !in_array($nome, $usados, true)) {
                $itens[] = ['rotulo' => $rotulo, 'valor' => $textos[(string) $valor] ?? (string) $valor, 'campos' => [$nome]];
            }
        }

        foreach ($prontos as $item) { $itens[] = $item; }

        $id = $datagrid ? (string) $datagrid->{'id'} : '';
        if ($id === '') { return; }
        $url = "engine.php?class={$classe}&method=onSearch&static=1&__sem_formulario=1";
        TScript::create('if (window.curciolFaixaFiltros) { curciolFaixaFiltros(' . json_encode($id) . ', ' . json_encode($itens) . ', ' . json_encode($url) . '); }');
    }

    /** Campos do formulario agrupados pelo rotulo da linha em que estao (ex.: "Data de emissao: [de] ate [fim]"). */
    private static function camposPorRotulo($form): array
    {
        if (!$form) { return []; }
        $grupos = [];
        try {
            $prop = new ReflectionProperty(get_class($form), 'tabcontent');
            $prop->setAccessible(true);
            $abas = (array) $prop->getValue($form);
        } catch (Throwable $e) {
            return [];
        }
        foreach ($abas as $linhas) {
            foreach ((array) $linhas as $linha) {
                if (!is_object($linha) || ($linha->type ?? '') !== 'fields') { continue; }
                foreach ((array) $linha->content as $slot) {
                    $rotulo = null; $antes = ''; $campos = [];
                    foreach ((array) $slot as $item) {
                        if ($item instanceof TLabel) {
                            $texto = self::textoRotulo($item);
                            if ($rotulo === null) { $rotulo = $texto; } else { $antes = $texto; }
                            continue;
                        }
                        if ($item instanceof AdiantiWidgetInterface && !($item instanceof THidden) && !($item instanceof TButton) && method_exists($item, 'getName')) {
                            $campos[] = [$item->getName(), $item, $antes];
                            $antes = '';
                        }
                    }
                    if ($campos) {
                        $grupos[] = ['rotulo' => $rotulo ?: ucfirst(str_replace('_', ' ', $campos[0][0])), 'campos' => $campos];
                    }
                }
            }
        }
        return $grupos;
    }

    private static function textoRotulo(TLabel $rotulo): string
    {
        // le o valor sem desenhar: o show() do TLabel acrescenta o texto de novo a cada chamada e o rotulo saia
        // dobrado na tela e na faixa ("ateate", 30/10/2026)
        $html = (string) $rotulo->getValue();
        return trim(rtrim(trim(html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8')), ':'));
    }

    /** Texto do valor como aparece na tela (opcao do combo, nome da pessoa, data dd/mm/aaaa). */
    private static function textoDoValor($campo, $valor): string
    {
        if (is_array($valor)) {
            $textos = array_filter(array_map(function ($v) use ($campo) { return self::textoDoValor($campo, $v); }, $valor), 'strlen');
            return implode(', ', $textos);
        }
        $valor = trim((string) $valor);
        if ($valor === '') { return ''; }
        try {
            if (method_exists($campo, 'getItems') && ($itens = $campo->getItems()) && isset($itens[$valor])) {
                return trim(strip_tags((string) $itens[$valor]));
            }
            $ler =function ($obj, $p) { $r = new ReflectionProperty(get_class($obj), $p); $r->setAccessible(true); return $r->getValue($obj); };
            if (property_exists($campo, 'model') && property_exists($campo, 'key') && property_exists($campo, 'database')) {
                $model = $ler($campo, 'model'); $key = $ler($campo, 'key'); $db = $ler($campo, 'database');
                $coluna = property_exists($campo, 'column') ? $ler($campo, 'column') : null;
                if ($model && $key && $db && $key !== $coluna) {
                    $propria = !TTransaction::get();
                    if ($propria) { TTransaction::open($db); }
                    $obj = $model::where($key, '=', $valor)->first();
                    if ($propria) { TTransaction::close(); }
                    if ($obj) {
                        $texto = $coluna && !preg_match('/[{}]/', $coluna) ? $obj->$coluna : ($obj->nome ?? null);
                        if ($texto !== null && $texto !== '') { return (string) $texto; }
                    }
                }
            }
        } catch (Throwable $e) {
            // sem descricao: mostra o valor como veio
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $valor, $m)) { return "{$m[3]}/{$m[2]}/{$m[1]}"; }
        return $valor;
    }

    /** O valor esta aplicado agora? (para destacar o botao rapido ativo) */
    public static function aplicado(string $classe, string $campo, string $valor): bool
    {
        $atual = (array) (TSession::getValue($classe . '_filter_data') ?: []);
        return (string) ($atual[$campo] ?? '') === $valor;
    }
}
