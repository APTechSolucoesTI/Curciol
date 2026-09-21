<?php
/**
 * A respeito da Clonagem Dinâmica
 *
 * Clona dinamicamente os blocos ${CLONE1}..${CLONEX} no template do Word.
 *
 * O limite X é lido da tabela `Clones` (linha única, id = 1, coluna `qtd`).
 * Caso a leitura falhe ou não haja valor válido, usa fallback de 9.
 * Se um bloco CLONEX não existir no .docx, nada é exibido ao usuário — apenas é registrado no log.
 * Abre/fecha TTransaction('escritorio') somente se não houver transação aberta.
 *
 * Referente a cloneBlocosPadrao()
 * @param \PhpOffice\PhpWord\TemplateProcessor $tp   Instância do TemplateProcessor já carregada com o .docx.
 * @param int $qtd  Quantidade de repetições por bloco (ex.: número de clientes).
 *
 * @return void
 *
 *  @Exemplos
 *  $tp = new \PhpOffice\PhpWord\TemplateProcessor($arquivoDocx);
 *  self::cloneBlocosPadrao($tp, count($idsClientes));
 *
 * Efeitos colaterais:
 *  - Pode abrir/fechar transação via TTransaction::open('escritorio')/close().
 *  - Registra eventos em app/logs/doc_multi.log através de self::dbg().
 *
 * Observações:
 *  - Os blocos ausentes (ex.: CLONE5..CLONEX) são ignorados silenciosamente.
 *  - Os placeholders internos devem ser simples (ex.: ${nome_cliente}); as versões numeradas (${nome_cliente#1}, #2, …)
 *    são geradas automaticamente pela clonagem e preenchidas via setValue("campo#{$idx}", ...).
 */

class ModeloDocumentoService
{
    public static function onVerificarDadosCliente(
        $cliente,
        $modelo,
        $objeto = null,
        $qtdePagamento = 0,
        $usarMisto = false
    ) {
        try {
            // Normaliza: aceita ID ou objeto
            if (is_numeric($cliente)) { $cliente = Pessoa::find((int)$cliente); }
            if (!$cliente instanceof Pessoa) { throw new Exception('Cliente inválido ou não encontrado.'); }

            if (is_numeric($modelo)) { $modelo = ModeloDocumento::find((int)$modelo); }
            if (!$modelo) { throw new Exception('Modelo de documento inválido ou não encontrado.'); }

            $dadosVerificados = [];

            $representanteRel = PessoaRepresentantesLegais::where('pessoa_juridica_id', '=', $cliente->id)
                                ->where('principal', '=', 'S')
                                ->first();
            $rep = ($representanteRel && isset($representanteRel->representante)) ? $representanteRel->representante : null;

            // Pega linha de obrigatoriedades conforme tipo
            if ($usarMisto)
            {
                $dadosObrigatorios = ModeloDocumentoMisto::where(
                    'modelo_documento_id',
                    '=',
                    $modelo->id
                )->first();
            }
            elseif ($cliente->tipo_pessoa_id == TipoPessoa::FISICA)
            {
                $dadosObrigatorios = $rep
                    ? ModeloDocumentoPfrep::where(
                        'modelo_documento_id',
                        '=',
                        $modelo->id
                    )->first()
                    : ModeloDocumentoPf::where(
                        'modelo_documento_id',
                        '=',
                        $modelo->id
                    )->first();
            }
            else
            {
                $dadosObrigatorios = ModeloDocumentoPj::where(
                    'modelo_documento_id',
                    '=',
                    $modelo->id
                )->first();

                if (!$rep) {
                    $dadosVerificados[] = "representante";
                }
            }

            self::verificarCampoObrigatorio($dadosVerificados, $objeto, ($dadosObrigatorios ? $dadosObrigatorios->objeto : null), "objeto");

            if (($dadosObrigatorios && $dadosObrigatorios->informacoes_pagamento === 'S') && $qtdePagamento < 1) {
                $dadosVerificados[] = "informações de pagamento";
            }

            if ($usarMisto)
            {
                if ($cliente->tipo_pessoa_id == TipoPessoa::FISICA)
                {
                    $reqDocumento      = $dadosObrigatorios->pf_cpf ?? null;
                    $reqData           = $dadosObrigatorios->pf_data_nascimento ?? null;
                    $reqRg             = $dadosObrigatorios->pf_rg ?? null;
                    $reqNacionalidade  = $dadosObrigatorios->pf_nacionalidade ?? null;
                    $reqEstadoCivil    = $dadosObrigatorios->pf_estado_civil ?? null;
                    $reqProfissao      = $dadosObrigatorios->pf_profissao ?? null;
                    $reqEndereco       = $dadosObrigatorios->pf_endereco ?? null;
                }
                else
                {
                    $reqDocumento      = $dadosObrigatorios->pj_cnpj ?? null;
                    $reqData           = $dadosObrigatorios->pj_data_abertura ?? null;
                    $reqRg             = null;
                    $reqNacionalidade  = null;
                    $reqEstadoCivil    = null;
                    $reqProfissao      = null;
                    $reqEndereco       = $dadosObrigatorios->pj_endereco ?? null;
                }
            }
            else
            {
                $reqDocumento = $cliente->tipo_pessoa_id == TipoPessoa::FISICA
                    ? ($dadosObrigatorios->cpf ?? null)
                    : ($dadosObrigatorios->cnpj ?? null);

                $reqData = $cliente->tipo_pessoa_id == TipoPessoa::FISICA
                    ? ($dadosObrigatorios->data_nascimento ?? null)
                    : ($dadosObrigatorios->data_abertura ?? null);

                $reqRg            = $dadosObrigatorios->rg ?? null;
                $reqNacionalidade = $dadosObrigatorios->nacionalidade ?? null;
                $reqEstadoCivil   = $dadosObrigatorios->estado_civil ?? null;
                $reqProfissao     = $dadosObrigatorios->profissao ?? null;
                $reqEndereco      = $dadosObrigatorios->endereco ?? null;
            }

            self::verificarCampoObrigatorio(
                $dadosVerificados,
                $cliente->cpf_cnpj ?? null,
                $reqDocumento,
                $cliente->tipo_pessoa_id == TipoPessoa::FISICA
                    ? "CPF"
                    : "CNPJ"
            );

            self::verificarCampoObrigatorio(
                $dadosVerificados,
                $cliente->dt_nasci_formatada ?? null,
                $reqData,
                $cliente->tipo_pessoa_id == TipoPessoa::FISICA
                    ? "Data de nascimento"
                    : "Data de abertura"
            );

            if ($cliente->tipo_pessoa_id == TipoPessoa::FISICA)
            {
                self::verificarCampoObrigatorio(
                    $dadosVerificados,
                    $cliente->rg_ie ?? null,
                    $reqRg,
                    "rg"
                );

                self::verificarCampoObrigatorio(
                    $dadosVerificados,
                    $cliente->orgao_emissor ?? null,
                    $reqRg,
                    "órgão emissor do rg"
                );

                self::verificarCampoObrigatorio(
                    $dadosVerificados,
                    $cliente->nacionalidade ?? null,
                    $reqNacionalidade,
                    "nacionalidade"
                );

                self::verificarCampoObrigatorio(
                    $dadosVerificados,
                    $cliente->estado_civil ?? null,
                    $reqEstadoCivil,
                    "estado civil"
                );

                self::verificarCampoObrigatorio(
                    $dadosVerificados,
                    $cliente->profissao ?? null,
                    $reqProfissao,
                    "profissão"
                );
            }

           if (
                self::verificarEndereco($cliente->id) < 1 &&
                $reqEndereco === 'S'
            )
            {
                $dadosVerificados[] = "endereço principal";
            }

           if ($rep)
            {
                if ($usarMisto)
                {
                    // No Misto, o representante configurado é o representante da PJ.
                    if ($cliente->tipo_pessoa_id == TipoPessoa::JURIDICA)
                    {
                        $reqRepCpf            = $dadosObrigatorios->pj_rep_cpf ?? null;
                        $reqRepRg             = $dadosObrigatorios->pj_rep_rg ?? null;
                        $reqRepDataNascimento = $dadosObrigatorios->pj_rep_data_nascimento ?? null;
                        $reqRepNacionalidade  = $dadosObrigatorios->pj_rep_nacionalidade ?? null;
                        $reqRepEstadoCivil    = $dadosObrigatorios->pj_rep_estado_civil ?? null;
                        $reqRepProfissao      = $dadosObrigatorios->pj_rep_profissao ?? null;
                        $reqRepEndereco       = $dadosObrigatorios->pj_rep_endereco ?? null;
                    }
                    else
                    {
                        // A configuração Misto atual não possui representante da PF.
                        $reqRepCpf            = null;
                        $reqRepRg             = null;
                        $reqRepDataNascimento = null;
                        $reqRepNacionalidade  = null;
                        $reqRepEstadoCivil    = null;
                        $reqRepProfissao      = null;
                        $reqRepEndereco       = null;
                    }
                }
                else
                {
                    $reqRepCpf            = $dadosObrigatorios->cpf_rep ?? null;
                    $reqRepRg             = $dadosObrigatorios->rg_rep ?? null;
                    $reqRepDataNascimento = $dadosObrigatorios->data_nascimento_rep ?? null;
                    $reqRepNacionalidade  = $dadosObrigatorios->nacionalidade_rep ?? null;
                    $reqRepEstadoCivil    = $dadosObrigatorios->estado_civil_rep ?? null;
                    $reqRepProfissao      = $dadosObrigatorios->profissao_rep ?? null;
                    $reqRepEndereco       = $dadosObrigatorios->endereco_rep ?? null;
                }

                self::verificarCampoObrigatorio(
                    $dadosVerificados,
                    $rep->cpf_cnpj ?? null,
                    $reqRepCpf,
                    "CPF do representante"
                );

                self::verificarCampoObrigatorio(
                    $dadosVerificados,
                    $rep->dt_nasci_formatada ?? null,
                    $reqRepDataNascimento,
                    "Data de nascimento do representante"
                );

                if ($rep->tipo_pessoa_id == TipoPessoa::FISICA)
                {
                    self::verificarCampoObrigatorio(
                        $dadosVerificados,
                        $rep->rg_ie ?? null,
                        $reqRepRg,
                        "rg do representante"
                    );

                    self::verificarCampoObrigatorio(
                        $dadosVerificados,
                        $rep->orgao_emissor ?? null,
                        $reqRepRg,
                        "órgão emissor do rg do representante"
                    );

                    self::verificarCampoObrigatorio(
                        $dadosVerificados,
                        $rep->nacionalidade ?? null,
                        $reqRepNacionalidade,
                        "nacionalidade do representante"
                    );

                    self::verificarCampoObrigatorio(
                        $dadosVerificados,
                        $rep->estado_civil ?? null,
                        $reqRepEstadoCivil,
                        "estado civil do representante"
                    );

                    self::verificarCampoObrigatorio(
                        $dadosVerificados,
                        $rep->profissao ?? null,
                        $reqRepProfissao,
                        "profissão do representante"
                    );
                }

                if (
                    self::verificarEndereco($rep->id) < 1 &&
                    $reqRepEndereco === 'S'
                )
                {
                    $dadosVerificados[] = "endereço principal do representante";
                }
            }
            elseif (
                $usarMisto &&
                $cliente->tipo_pessoa_id == TipoPessoa::JURIDICA &&
                $dadosObrigatorios
            )
            {
                // Se alguma informação do representante for obrigatória,
                // mas a PJ não possuir representante.
                $exigeRepresentante = in_array('S', [
                    $dadosObrigatorios->pj_rep_cpf ?? null,
                    $dadosObrigatorios->pj_rep_rg ?? null,
                    $dadosObrigatorios->pj_rep_data_nascimento ?? null,
                    $dadosObrigatorios->pj_rep_nacionalidade ?? null,
                    $dadosObrigatorios->pj_rep_estado_civil ?? null,
                    $dadosObrigatorios->pj_rep_profissao ?? null,
                    $dadosObrigatorios->pj_rep_endereco ?? null,
                ], true);

                if ($exigeRepresentante)
                {
                    $dadosVerificados[] = "representante";
                }
            }

            return count($dadosVerificados) > 0
                ? ['cliente' => (isset($cliente->nome) ? $cliente->nome : '—'), 'dadosFaltantes' => implode(", ", $dadosVerificados)]
                : null;

        } catch (Exception $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    private static function verificarCampoObrigatorio(&$lista, $valor, $requerido, $campo) {
        if (!$valor && $requerido === "S") {
            $lista[] = $campo;
        }
    }

    private static function verificarEndereco($pessoaId) {
        return PessoaEndereco::where('pessoa_id', '=', $pessoaId)
                             ->where('principal', '=', 'S')
                             ->count();
    }

    private static function cloneBlocosPadrao(\PhpOffice\PhpWord\TemplateProcessor $tp, int $qtd): void {
        // Lê o máximo de blocos (CLONE1..CLONEX) da tabela Clone, id = 1
        $needClose = false;
        try {
            
            if (!TTransaction::get()){ 
                TTransaction::open('escritorio');
                $needClose = true; 
            }

            $cfg = Clones::find(1);

            $max = ($cfg && (int)$cfg->qtd > 0) ? (int)$cfg->qtd : 9; // fallback = 9
            $max = max(1, min(99, $max)); // limita entre 1 e 99

        } catch (\Throwable $e) {
            self::dbg('cloneBlocosPadrao: erro lendo Clone.id=1', $e->getMessage());
            $max = 9; // fallback
        } finally {
            if ($needClose && TTransaction::get()) { TTransaction::close(); }
        }

        // Clona CLONE1..CLONEX (tolerante: se faltar bloco no .docx, só loga)
        for ($n = 1; $n <= $max; $n++) {
            $blk = "CLONE{$n}";
            try {
                $tp->cloneBlock($blk, $qtd, true, true);
                self::dbg("cloneBlock {$blk} OK", ['qtde' => $qtd]);
            } catch (\Throwable $e) {
                self::dbg("Bloco {$blk} ausente (OK)");
            }
        }
    }

    private static function cloneBlocosMisto(
        \PhpOffice\PhpWord\TemplateProcessor $tp,
        array $idsClientes,
        callable $preencherCliente
    ): void
    {
        $todos = [];
        $pfs   = [];
        $pjs   = [];

        foreach ($idsClientes as $clienteId)
        {
            $cliente = Pessoa::find((int) $clienteId);

            if (!$cliente) {
                continue;
            }

            $todos[] = (int) $clienteId;

            if ($cliente->tipo_pessoa_id == TipoPessoa::FISICA)
            {
                $pfs[] = (int) $clienteId;
            }
            elseif ($cliente->tipo_pessoa_id == TipoPessoa::JURIDICA)
            {
                $pjs[] = (int) $clienteId;
            }
        }

        /*
        * Convenção do Misto:
        *
        * CLONE1      = todos
        * CLONE2_PF   = somente PF
        * CLONE3_PJ   = somente PJ
        *
        * O número NÃO possui significado.
        */

        $variaveis = $tp->getVariables();

        $blocos = [];

        foreach ($variaveis as $variavel)
        {
            if (!preg_match(
                '/^(CLONE\d+)(?:_(PF|PJ))?$/i',
                $variavel,
                $match
            )) {
                continue;
            }

            $base = strtoupper($match[1]);

            $tipo = !empty($match[2])
                ? strtoupper($match[2])
                : 'TODOS';

            $bloco = $base;

            if ($tipo === 'PF') {
                $bloco .= '_PF';
            }
            elseif ($tipo === 'PJ') {
                $bloco .= '_PJ';
            }

            // evita duplicidade
            if (isset($blocos[$bloco])) {
                continue;
            }

            $blocos[$bloco] = $tipo;
        }

        /*
        * IMPORTANTE:
        * clona UM bloco e preenche ele antes de ir para o próximo.
        *
        * Assim nome_cliente#1 de CLONE1 não conflita com
        * nome_cliente#1 de CLONE2_PF, por exemplo.
        */
        foreach ($blocos as $bloco => $tipo)
        {
            if ($tipo === 'PF')
            {
                $clientesBloco = $pfs;
            }
            elseif ($tipo === 'PJ')
            {
                $clientesBloco = $pjs;
            }
            else
            {
                $clientesBloco = $todos;
            }

            try
            {
                $tp->cloneBlock(
                    $bloco,
                    count($clientesBloco),
                    true,
                    true
                );

                self::dbg(
                    "cloneBlocosMisto {$bloco}",
                    [
                        'tipo' => $tipo,
                        'qtd'  => count($clientesBloco)
                    ]
                );

                $idx = 1;

                foreach ($clientesBloco as $clienteId)
                {
                    /*
                    * Preenche AGORA.
                    *
                    * Depois que terminar esse bloco, os #1/#2/etc
                    * dele deixam de existir e podemos clonar outro
                    * bloco usando novamente #1/#2/etc.
                    */
                    $preencherCliente($clienteId, $idx);

                    $idx++;
                }
            }
            catch (\Throwable $e)
            {
                self::dbg(
                    "Erro clone Misto {$bloco}",
                    $e->getMessage()
                );
            }
        }
    }


    // ------------------- debug/log helpers -------------------
    private static function dbg($msg, $data=null){
        try {
            $dir = 'app/logs';
            if (!is_dir($dir)) @mkdir($dir, 0777, true);
            $line = '['.date('Y-m-d H:i:s').'] '.$msg;
            if ($data !== null) $line .= ' | ' . (is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            $line .= PHP_EOL;
            @file_put_contents($dir.'/doc_multi.log', $line, FILE_APPEND);
        } catch (Exception $e) { /* ignora erro de log */ }
    }

    private static function ensureFile($path){
        if (!$path || !file_exists($path)) {
            throw new Exception("Template do modelo não encontrado: ".$path);
        }
    }

    // ------------------- principal -------------------
    public static function preencherDocumento($param){
        $debug = !empty($param['debug']);
        try{
            if ($debug) { ini_set('display_errors', 1); error_reporting(E_ALL); }
            TTransaction::open('escritorio');

            self::dbg('INICIO preencherDocumento', $param);

            // Normaliza IDs
           $idsClientes = [];
            if (!empty($param['cliente_id'])) {
                $idsClientes = self::normalizeClientesIds($param['cliente_id']);
            } elseif (!empty($param['clientes_ids'])) { // opcional, caso exista outro campo
                $idsClientes = self::normalizeClientesIds($param['clientes_ids']);
            } else {
                throw new Exception('Nenhum cliente informado (cliente_id ou clientes_ids).');
            }
            if (empty($idsClientes)) {
                throw new Exception('Lista de clientes vazia após normalização.');
            }

            $objeto          = isset($param['objeto']) ? $param['objeto'] : null;
            $modeloDocumento = ModeloDocumento::find((int)$param['modelo_documento_id']);
            if (!$modeloDocumento) throw new Exception('Modelo de documento inválido.');
            $escritorio      = Escritorio::find(1);
            $profissional    = !empty($param['profissional_id']) ? Pessoa::find((int)$param['profissional_id']) : null;

            $complemento = null;
            $tipo_complemento = null;
            if (!empty($param['atendimento_id'])) {
                $complemento = Atendimento::find((int)$param['atendimento_id']);
                $tipo_complemento = 'Atendimento';
            }
            if (!empty($param['contrato_id'])) {
                $complemento = Contrato::find((int)$param['contrato_id']);
                $tipo_complemento = 'Contrato';
            }


            // ======================= MODO MULTI (UM DOC) =======================
            if (count($idsClientes) > 1) {
                $primeiroCliente = Pessoa::find((int)$idsClientes[0]);

                
                if (!$primeiroCliente) throw new Exception('Primeiro cliente não encontrado.');
                
                $tiposClientes = self::verificarTiposClientes($idsClientes);
                $ehMisto = $tiposClientes['misto'];

                $representanteBase = PessoaRepresentantesLegais::where('pessoa_juridica_id', '=', $primeiroCliente->id)
                                                               ->where('principal', '=', 'S')
                                                               ->first();


                    // Se existir pelo menos um PF e um PJ no mesmo documento,
                    // obrigatoriamente usa a aba/modelo MISTO.
                    if ($ehMisto)
                    {
                        $documentoBase = ModeloDocumentoMisto::where(
                            'modelo_documento_id',
                            '=',
                            $modeloDocumento->id
                        )->first();

                        if (!$documentoBase || !$documentoBase->filename)
                        {
                            throw new Exception(
                                'Este contrato possui Pessoa Física e Pessoa Jurídica. ' .
                                'Configure o arquivo da aba Misto no modelo de documento.'
                            );
                        }
                    }
                    elseif (
                        $primeiroCliente->tipo_pessoa_id == TipoPessoa::FISICA &&
                        !$representanteBase
                    )
                    {
                        $documentoBase = ModeloDocumentoPf::where(
                            'modelo_documento_id',
                            '=',
                            $modeloDocumento->id
                        )->first();
                    }
                    elseif (
                        $primeiroCliente->tipo_pessoa_id == TipoPessoa::FISICA &&
                        $representanteBase
                    )
                    {
                        $documentoBase = ModeloDocumentoPfrep::where(
                            'modelo_documento_id',
                            '=',
                            $modeloDocumento->id
                        )->first();
                    }
                    else
                    {
                        $documentoBase = ModeloDocumentoPj::where(
                            'modelo_documento_id',
                            '=',
                            $modeloDocumento->id
                        )->first();
                    }

                if (!$documentoBase || !$documentoBase->filename) {
                    throw new Exception('Arquivo do modelo não configurado para MULTI.');
                }
                $nome_arquivo = $documentoBase->filename;
                self::ensureFile($nome_arquivo);
                self::dbg('Template base MULTI', $nome_arquivo);

                $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($nome_arquivo);

                $qtd = count($idsClientes);

                $primeirosNomes = [];
                $autenticadores = [];

                $templateProcessor->setValue('objeto', $objeto ?: '');

                /*
                * ÚNICO PREENCHEDOR DE CLIENTE DO MODO MULTI.
                *
                * LEGADO:
                * cloneBlocosPadrao() clona todos os CLONES pela quantidade total
                * e este callback é chamado uma vez por cliente.
                *
                * MISTO:
                * cloneBlocosMisto() decide:
                *
                * CLONE normal      = todos
                * CLONEX_PF         = somente PF
                * CLONEX_PJ         = somente PJ
                *
                * e chama este callback com o índice correto de cada bloco.
                */
                $preencherCliente = function($clienteId, $idx) use (
                    $templateProcessor,
                    $profissional,
                    $escritorio,
                    $objeto,
                    $param,
                    $complemento,
                    $tipo_complemento,
                    $ehMisto,
                    &$primeirosNomes,
                    &$autenticadores
                ) {
                    $cliente = Pessoa::find((int) $clienteId);

                    if (!$cliente)
                    {
                        self::dbg(
                            'Cliente não encontrado',
                            $clienteId
                        );

                        return;
                    }

                    // =====================================================
                    // NOME PARA PASTA DE SAÍDA
                    // =====================================================

                    $nomeRaw =
                        $cliente->nome_formatado
                        ?? $cliente->nome
                        ?? 'CLIENTE';

                    $first = trim(
                        preg_split('/\s+/u', $nomeRaw)[0]
                        ?? 'CLIENTE'
                    );

                    $first = preg_replace(
                        '/[^\pL\pN _\.-]+/u',
                        '',
                        $first
                    );

                    /*
                    * No Misto o mesmo cliente pode aparecer em:
                    *
                    * CLONE_PF/PJ
                    * e também em CLONE normal.
                    *
                    * Então usa ID como chave para não repetir nome
                    * na pasta final.
                    */
                    $primeirosNomes[$cliente->id] =
                        $first !== ''
                            ? $first
                            : 'CLIENTE';

                    // =====================================================
                    // REPRESENTANTE
                    // =====================================================

                    $representante =
                        PessoaRepresentantesLegais::where(
                            'pessoa_juridica_id',
                            '=',
                            $cliente->id
                        )
                        ->where(
                            'principal',
                            '=',
                            'S'
                        )
                        ->first();

                    // =====================================================
                    // ENDEREÇO CLIENTE
                    // =====================================================

                    $cliente_endereco =
                        PessoaEndereco::where(
                            'principal',
                            '=',
                            'S'
                        )
                        ->where(
                            'pessoa_id',
                            '=',
                            $cliente->id
                        )
                        ->first();

                    // =====================================================
                    // AUTENTICADOR
                    // =====================================================
                    //
                    // No Misto a mesma pessoa pode aparecer em vários
                    // blocos. Então gera apenas UMA VEZ por cliente.
                    // =====================================================

                    if (!isset($autenticadores[$cliente->id]))
                    {
                        $autenticador = null;
                        $guard = 0;

                        do
                        {
                            $autenticador = base64_encode(
                                rand()
                                . '-'
                                . TSession::getValue('userid')
                                . '-'
                                . TSession::getValue('unitid')
                            );

                            $existeA =
                                Documento::where(
                                    'autenticador',
                                    '=',
                                    $autenticador
                                )
                                ->count();

                            $existeB =
                                ContratoDocumento::where(
                                    'autenticador',
                                    '=',
                                    $autenticador
                                )
                                ->count();

                            $guard++;
                        }
                        while (
                            ($existeA > 0 || $existeB > 0)
                            && $guard < 5
                        );

                        $autenticadores[$cliente->id] =
                            $autenticador;
                    }
                    else
                    {
                        $autenticador =
                            $autenticadores[$cliente->id];
                    }

                    // =====================================================
                    // TIPO DA PESSOA
                    // =====================================================

                    $clienteEhPf =
                        $cliente->tipo_pessoa_id
                        == TipoPessoa::FISICA;

                    $clienteEhPj =
                        $cliente->tipo_pessoa_id
                        == TipoPessoa::JURIDICA;

                    // =====================================================
                    // DADOS PRINCIPAIS
                    // =====================================================

                    $templateProcessor->setValue(
                        "nome_cliente#{$idx}",
                        $cliente->nome_formatado
                        ?? ''
                    );

                    $templateProcessor->setValue(
                        "nome_profissional#{$idx}",
                        $profissional
                            ? ($profissional->nome_formatado ?? '')
                            : ''
                    );

                    $templateProcessor->setValue(
                        "data_nascimento#{$idx}",
                        $clienteEhPf
                            ? ($cliente->dt_nasci_formatada ?? '')
                            : ''
                    );

                    $templateProcessor->setValue(
                        "data_abertura#{$idx}",
                        $clienteEhPj
                            ? ($cliente->dt_nasci_formatada ?? '')
                            : ''
                    );

                    $templateProcessor->setValue(
                        "nome_escritorio#{$idx}",
                        $escritorio
                            ? ($escritorio->nome ?? '')
                            : ''
                    );

                    // =====================================================
                    // DADOS EXCLUSIVOS DE PF
                    // =====================================================

                    $templateProcessor->setValue(
                        "nacionalidade#{$idx}",
                        $clienteEhPf
                        && isset($cliente->nacionalidade->nome)
                            ? $cliente->nacionalidade->nome
                            : ''
                    );

                    $templateProcessor->setValue(
                        "estado_civil#{$idx}",
                        $clienteEhPf
                        && isset($cliente->estado_civil->nome)
                            ? $cliente->estado_civil->nome
                            : ''
                    );

                    $templateProcessor->setValue(
                        "profissao#{$idx}",
                        $clienteEhPf
                            ? ($cliente->profissao ?? '')
                            : ''
                    );

                    $templateProcessor->setValue(
                        "rg#{$idx}",
                        $clienteEhPf
                            ? ($cliente->rg_ie_formatado ?? '')
                            : ''
                    );

                    $templateProcessor->setValue(
                        "orgao_emissor#{$idx}",
                        $clienteEhPf
                            ? ($cliente->orgao_emissor ?? '')
                            : ''
                    );

                    $templateProcessor->setValue(
                        "cpf#{$idx}",
                        $clienteEhPf
                            ? ($cliente->cpf_cnpj_formatado ?? '')
                            : ''
                    );

                    // =====================================================
                    // DADOS EXCLUSIVOS DE PJ
                    // =====================================================

                    $templateProcessor->setValue(
                        "cnpj#{$idx}",
                        $clienteEhPj
                            ? ($cliente->cpf_cnpj_formatado ?? '')
                            : ''
                    );

                    // =====================================================
                    // CAMPOS GERAIS
                    // =====================================================

                    $templateProcessor->setValue(
                        "objeto#{$idx}",
                        $objeto ?? ''
                    );

                    $templateProcessor->setValue(
                        "informacoes_documento#{$idx}",
                        $autenticador ?? ''
                    );

                    $templateProcessor->setValue(
                        "autenticador#{$idx}",
                        $autenticador ?? ''
                    );

                    // =====================================================
                    // ENDEREÇO CLIENTE
                    // =====================================================

                    if ($cliente_endereco)
                    {
                        $templateProcessor->setValue(
                            "rua#{$idx}",
                            $cliente_endereco->rua
                            ?? ''
                        );

                        $templateProcessor->setValue(
                            "numero#{$idx}",
                            !empty($cliente_endereco->numero)
                                ? ', ' . $cliente_endereco->numero
                                : ''
                        );

                        $templateProcessor->setValue(
                            "bairro#{$idx}",
                            $cliente_endereco->bairro
                            ?? ''
                        );

                        $templateProcessor->setValue(
                            "cidade#{$idx}",
                            isset($cliente_endereco->cidade->nome)
                                ? $cliente_endereco->cidade->nome
                                : ''
                        );

                        $templateProcessor->setValue(
                            "uf#{$idx}",
                            isset(
                                $cliente_endereco
                                    ->cidade
                                    ->estado
                                    ->sigla
                            )
                                ? '/'
                                    . $cliente_endereco
                                        ->cidade
                                        ->estado
                                        ->sigla
                                : ''
                        );

                        $templateProcessor->setValue(
                            "cep#{$idx}",
                            $cliente_endereco
                                ->cep_formatado
                            ?? ''
                        );

                        $templateProcessor->setValue(
                            "complemento#{$idx}",
                            !empty(
                                $cliente_endereco
                                    ->complemento
                            )
                                ? ' - '
                                    . $cliente_endereco
                                        ->complemento
                                : ''
                        );
                    }
                    elseif ($ehMisto)
                    {
                        /*
                        * No legado não alteramos esse comportamento.
                        *
                        * No Misto limpa as tags caso o endereço
                        * seja opcional e não exista.
                        */
                        $camposEndereco = [
                            'rua',
                            'numero',
                            'bairro',
                            'cidade',
                            'uf',
                            'cep',
                            'complemento'
                        ];

                        foreach (
                            $camposEndereco
                            as $campoEndereco
                        )
                        {
                            $templateProcessor->setValue(
                                "{$campoEndereco}#{$idx}",
                                ''
                            );
                        }
                    }

                    // =====================================================
                    // REPRESENTANTE
                    // =====================================================

                    /*
                    * Legado:
                    * mantém representante como já funcionava.
                    *
                    * Misto:
                    * representante só é utilizado para PJ.
                    */
                    $usarRepresentanteNesteCliente =
                        $representante
                        && isset(
                            $representante
                                ->representante
                        )
                        && (
                            !$ehMisto
                            || $clienteEhPj
                        );

                    if ($usarRepresentanteNesteCliente)
                    {
                        $rep =
                            $representante
                                ->representante;

                        $templateProcessor->setValue(
                            "nome_representante#{$idx}",
                            $rep->nome_formatado
                            ?? ''
                        );

                        $templateProcessor->setValue(
                            "data_nascimento_representante#{$idx}",
                            $rep->dt_nasci_formatada
                            ?? ''
                        );

                        $templateProcessor->setValue(
                            "nacionalidade_representante#{$idx}",
                            isset($rep->nacionalidade->nome)
                                ? $rep->nacionalidade->nome
                                : ''
                        );

                        $templateProcessor->setValue(
                            "estado_civil_representante#{$idx}",
                            isset($rep->estado_civil->nome)
                                ? $rep->estado_civil->nome
                                : ''
                        );

                        $templateProcessor->setValue(
                            "profissao_representante#{$idx}",
                            $rep->profissao
                            ?? ''
                        );

                        $templateProcessor->setValue(
                            "rg_representante#{$idx}",
                            $rep->rg_ie_formatado
                            ?? ''
                        );

                        $templateProcessor->setValue(
                            "orgao_emissor_representante#{$idx}",
                            $rep->orgao_emissor
                            ?? ''
                        );

                        $templateProcessor->setValue(
                            "cpf_representante#{$idx}",
                            $rep->cpf_cnpj_formatado
                            ?? ''
                        );

                        // =================================================
                        // ENDEREÇO REPRESENTANTE
                        // =================================================

                        $rep_end =
                            PessoaEndereco::where(
                                'principal',
                                '=',
                                'S'
                            )
                            ->where(
                                'pessoa_id',
                                '=',
                                $rep->id
                            )
                            ->first();

                        if ($rep_end)
                        {
                            $templateProcessor->setValue(
                                "rua_representante#{$idx}",
                                $rep_end->rua
                                ?? ''
                            );

                            $templateProcessor->setValue(
                                "numero_representante#{$idx}",
                                !empty($rep_end->numero)
                                    ? ', ' . $rep_end->numero
                                    : ''
                            );

                            $templateProcessor->setValue(
                                "bairro_representante#{$idx}",
                                $rep_end->bairro
                                ?? ''
                            );

                            $templateProcessor->setValue(
                                "cidade_representante#{$idx}",
                                isset(
                                    $rep_end
                                        ->cidade
                                        ->nome
                                )
                                    ? $rep_end
                                        ->cidade
                                        ->nome
                                    : ''
                            );

                            $templateProcessor->setValue(
                                "uf_representante#{$idx}",
                                isset(
                                    $rep_end
                                        ->cidade
                                        ->estado
                                        ->sigla
                                )
                                    ? '/'
                                        . $rep_end
                                            ->cidade
                                            ->estado
                                            ->sigla
                                    : ''
                            );

                            $templateProcessor->setValue(
                                "cep_representante#{$idx}",
                                $rep_end
                                    ->cep_formatado
                                ?? ''
                            );

                            $templateProcessor->setValue(
                                "complemento_representante#{$idx}",
                                !empty(
                                    $rep_end
                                        ->complemento
                                )
                                    ? ' - '
                                        . $rep_end
                                            ->complemento
                                    : ''
                            );
                        }
                        else
                        {
                            $camposEnderecoRepresentante = [
                                'rua_representante',
                                'numero_representante',
                                'complemento_representante',
                                'bairro_representante',
                                'cidade_representante',
                                'uf_representante',
                                'cep_representante'
                            ];

                            foreach (
                                $camposEnderecoRepresentante
                                as $campoEnderecoRepresentante
                            )
                            {
                                $templateProcessor->setValue(
                                    "{$campoEnderecoRepresentante}#{$idx}",
                                    ''
                                );
                            }
                        }
                    }
                    else
                    {
                        /*
                        * Sem representante.
                        *
                        * Também é usado para PF dentro do Misto.
                        */
                        $camposRepresentante = [
                            'nome_representante',
                            'data_nascimento_representante',
                            'nacionalidade_representante',
                            'estado_civil_representante',
                            'profissao_representante',
                            'rg_representante',
                            'orgao_emissor_representante',
                            'cpf_representante',
                            'rua_representante',
                            'numero_representante',
                            'complemento_representante',
                            'bairro_representante',
                            'cidade_representante',
                            'uf_representante',
                            'cep_representante'
                        ];

                        foreach (
                            $camposRepresentante
                            as $campoRepresentante
                        )
                        {
                            $templateProcessor->setValue(
                                "{$campoRepresentante}#{$idx}",
                                ''
                            );
                        }
                    }

                    // =====================================================
                    // DATA DE VALIDADE
                    // =====================================================

                    if (isset($param['dt_validade']))
                    {
                        $templateProcessor->setValue(
                            "data_vencimento#{$idx}",
                            'Data de validade: '
                            . implode(
                                '/',
                                array_reverse(
                                    explode(
                                        '-',
                                        $param['dt_validade']
                                    )
                                )
                            )
                        );
                    }
                    else
                    {
                        $templateProcessor->setValue(
                            "data_vencimento#{$idx}",
                            ''
                        );
                    }

                    // =====================================================
                    // ATENDIMENTO
                    // =====================================================

                    if (
                        $complemento
                        && $tipo_complemento
                            == 'Atendimento'
                    )
                    {
                        $templateProcessor->setValue(
                            "data_atendimento#{$idx}",
                            $complemento
                                ->data_atendimento
                            ?? ''
                        );

                        $templateProcessor->setValue(
                            "inicio_atendimento#{$idx}",
                            $complemento
                                ->data_atendimento
                            ?? ''
                        );
                    }

                    // =====================================================
                    // ESPAÇO ENTRE CLIENTES
                    // =====================================================

                    $espaco =
                        new \PhpOffice\PhpWord\Element\TextRun();

                    $espaco->addTextBreak(2);

                    $templateProcessor->setComplexValue(
                        "espaco#{$idx}",
                        $espaco
                    );

                    self::dbg(
                        'Preenchido cliente',
                        [
                            'idx' => $idx,
                            'id' => $clienteId,
                            'nome' =>
                                $cliente->nome
                                ?? null,
                            'misto' =>
                                $ehMisto
                                    ? 'S'
                                    : 'N'
                        ]
                    );
                };

                // =========================================================
                // CLONAGEM + PREENCHIMENTO
                // =========================================================

                if ($ehMisto)
                {
                    /*
                    * MISTO
                    *
                    * ${CLONE1}
                    *      = todos
                    *
                    * ${CLONE2_PF}
                    *      = somente PF
                    *
                    * ${CLONE3_PJ}
                    *      = somente PJ
                    *
                    * O número CLONE1, CLONE2, CLONE99...
                    * NÃO possui regra de negócio.
                    */
                    self::cloneBlocosMisto(
                        $templateProcessor,
                        $idsClientes,
                        $preencherCliente
                    );
                }
                else
                {
                    /*
                    * LEGADO INTACTO.
                    *
                    * Exemplo:
                    *
                    * 5 pessoas físicas no contrato
                    *
                    * CLONE1 = 5
                    * CLONE2 = 5
                    * CLONE3 = 5
                    * CLONE4 = 5
                    * CLONE5 = 5
                    *
                    * Exatamente como já funcionava antes.
                    */
                    self::cloneBlocosPadrao(
                        $templateProcessor,
                        $qtd
                    );

                    $idx = 1;

                    foreach (
                        $idsClientes
                        as $clienteId
                    )
                    {
                        $preencherCliente(
                            $clienteId,
                            $idx
                        );

                        $idx++;
                    }
                }
                if ($complemento && $tipo_complemento == 'Contrato') {
                    $pagamentosContrato = ContratoPagamentoParcela::where('contrato_id','=',$complemento->id)
                        ->orderby('contrato_opcao_pagamento_id')
                        ->load();

                    if (!empty($pagamentosContrato)) {
                        // constrói a lista de tags auxiliares
                        $tags = '';
                        foreach ($pagamentosContrato as $j => $pagamentoContrato) {
                            $tags .= "\${informacoes_pagamento{$j}}";
                        }
                        // preenche o placeholder “principal” do contrato (fora de bloco)
                        $templateProcessor->setValue('informacoes_pagamento', $tags);

                        // agora preenche cada subtag
                        $clausula = (ContratoConfig::find(1))->clausula_pagamento;
                        $subClausula = 1;
                        foreach ($pagamentosContrato as $j => $pagamentoContrato) {
                            $tr = new \PhpOffice\PhpWord\Element\TextRun();
                            if ($subClausula > 1) $tr->addTextBreak(2);
                            $tr->addText("$clausula.$subClausula ", ['bold' => true, 'name' => 'Calibri Light', 'size' => 10]);
                            $tr->addText($pagamentoContrato->descritivo, ['name' => 'Calibri Light', 'size' => 10]);
                            $subClausula++;
                            $templateProcessor->setComplexValue("informacoes_pagamento{$j}", $tr);
                        }
                    } else {
                        $templateProcessor->setValue('informacoes_pagamento', null);
                    }
                }

                // Salva UM documento
                $destino_base = "files/documents/{$modeloDocumento->id}/";
                if (!file_exists($destino_base)) mkdir($destino_base, 0777, true);

                $label = implode(' ', array_unique($primeirosNomes));      // uniq p/ reduzir repetidos
                    $label = preg_replace('/\s+/u', ' ', trim($label));        // normaliza espaços
                    if (class_exists('Normalizer')) {                           // NFC evita treta NFD vs NFC
                        $label = Normalizer::normalize($label, Normalizer::FORM_C);
                    }
                    // limita tamanho pra não estourar path (ajuste se quiser maior)
                    if (function_exists('mb_strlen') && mb_strlen($label) > 120) {
                        $label = mb_substr($label, 0, 117) . '...';
                    }

                    $pastaGrupo = $destino_base . $label . ' ' . date('Y-m-d') . '/';
                    if (!file_exists($pastaGrupo)) mkdir($pastaGrupo, 0777, true);

                $nome_arquivo_saida = str_replace(' ','_', $modeloDocumento->nome . "_MULTI_" . date("Y-m-d"));
                $caminho_base = $pastaGrupo . $nome_arquivo_saida;

                if ($debug) { $templateProcessor->saveAs($caminho_base . ".docx"); }
                else        { @$templateProcessor->saveAs($caminho_base . ".docx"); }

                @\PhpOffice\PhpWord\Settings::setPdfRendererPath('vendor/dompdf/dompdf');
                @\PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

                $temp = @\PhpOffice\PhpWord\IOFactory::load($caminho_base . ".docx");
                $xmlWriter = @\PhpOffice\PhpWord\IOFactory::createWriter($temp , 'PDF');
                if ($debug) { $xmlWriter->save($caminho_base . ".pdf", true); }
                else        { @$xmlWriter->save($caminho_base . ".pdf", true); }

                self::dbg('FIM MULTI', $caminho_base);

                TTransaction::close();

                if ($debug) new TMessage('info', 'Documento MULTI gerado: '.$caminho_base.'.docx');

                return [
                    'multi'               => true,
                    'clientes'            => $idsClientes,
                    'novo_nome_arquivo'   => $caminho_base,
                    'modelo_documento_id' => $modeloDocumento->id
                ];
            }

            // ======================= MODO ÚNICO (LEGADO) =======================
            $cliente = Pessoa::find((int)$idsClientes[0]);
            if (!$cliente) throw new Exception('Cliente não encontrado.');

            $representante = PessoaRepresentantesLegais::where('pessoa_juridica_id', '=', $cliente->id)
                                                       ->where('principal', '=', 'S')->first();
            $cliente_endereco = PessoaEndereco::where('principal','=','S')->where('pessoa_id','=',$cliente->id)->first();

            // Autenticador
            $i=0;
            while($i<1){
                $autenticador = base64_encode(rand() . '-' . TSession::getValue('userid') .'-'. TSession::getValue('unitid'));
                $verifAutenticadoDoc = Documento::where('autenticador','=',$autenticador)->count();
                $verifAutenticadoContDoc = ContratoDocumento::where('autenticador','=',$autenticador)->count();
                if($verifAutenticadoDoc==0 && $verifAutenticadoContDoc==0){ $i++; }
            }

            if($cliente->tipo_pessoa_id == TipoPessoa::FISICA && !$representante){
                $documento = ModeloDocumentoPf::where('modelo_documento_id','=',$modeloDocumento->id)->first();
            }elseif($cliente->tipo_pessoa_id == TipoPessoa::FISICA && $representante){
                $documento = ModeloDocumentoPfrep::where('modelo_documento_id','=',$modeloDocumento->id)->first();
            }elseif($cliente->tipo_pessoa_id == TipoPessoa::JURIDICA && $representante){
                $documento = ModeloDocumentoPj::where('modelo_documento_id','=',$modeloDocumento->id)->first();
            }
            if (!$documento || !$documento->filename) throw new Exception('Arquivo do modelo não configurado.');
            $nome_arquivo = $documento->filename;
            self::ensureFile($nome_arquivo);

           $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($nome_arquivo);

            // Se o template tiver blocos do modo MULTI, clona 1 vez e troca os marcadores
            self::cloneBlocosPadrao($templateProcessor, 1);


            // helper: seta a tag simples e a versão #1 (para dentro de bloco clonado)
            $set = function($name, $val) use ($templateProcessor) {
                $v = ($val === null ? '' : $val);
                $templateProcessor->setValue($name, $v);
                $templateProcessor->setValue($name.'#1', $v);
            };

            $substituicoes = [
                'nome_cliente'         => (isset($cliente->nome_formatado) ? $cliente->nome_formatado : null),
                'nome_profissional'    => ($profissional ? $profissional->nome_formatado : null),
                'data_nascimento'      => (isset($cliente->dt_nasci_formatada) ? $cliente->dt_nasci_formatada : null),
                'data_abertura'        => (isset($cliente->dt_nasci_formatada) ? $cliente->dt_nasci_formatada : null),
                'nome_escritorio'      => ($escritorio ? $escritorio->nome : null),
                'nacionalidade'        => ($cliente && isset($cliente->nacionalidade) && isset($cliente->nacionalidade->nome)) ? $cliente->nacionalidade->nome : null,
                'estado_civil'         => ($cliente && isset($cliente->estado_civil) && isset($cliente->estado_civil->nome)) ? $cliente->estado_civil->nome : null,
                'profissao'            => (isset($cliente->profissao) ? $cliente->profissao : null),
                'rg'                   => (isset($cliente->rg_ie_formatado) ? $cliente->rg_ie_formatado : null),
                'orgao_emissor'        => (isset($cliente->orgao_emissor) ? $cliente->orgao_emissor : null),
                'cpf'                  => (isset($cliente->cpf_cnpj_formatado) ? $cliente->cpf_cnpj_formatado : null),
                'cnpj'                 => (isset($cliente->cpf_cnpj_formatado) ? $cliente->cpf_cnpj_formatado : null),
                'objeto'               => $objeto,
                'informacoes_documento'=> $autenticador,
                'autenticador'         => $autenticador
            ];

            if($cliente_endereco){
                $substituicoes["rua"]    = (isset($cliente_endereco->rua) ? $cliente_endereco->rua : null);
                $substituicoes["numero"] = (!empty($cliente_endereco->numero) ? ", ".$cliente_endereco->numero : null);
                $substituicoes["bairro"] = (isset($cliente_endereco->bairro) ? $cliente_endereco->bairro : null);
                $substituicoes["cidade"] = ($cliente_endereco && isset($cliente_endereco->cidade) && isset($cliente_endereco->cidade->nome)) ? $cliente_endereco->cidade->nome : null;
                $substituicoes["uf"]     = ($cliente_endereco && isset($cliente_endereco->cidade) && isset($cliente_endereco->cidade->estado) && isset($cliente_endereco->cidade->estado->sigla))
                                            ? "/".$cliente_endereco->cidade->estado->sigla
                                            : null;
                $substituicoes["cep"]    = (isset($cliente_endereco->cep_formatado) ? $cliente_endereco->cep_formatado : null);
                $substituicoes["complemento"] = (!empty($cliente_endereco->complemento) ? " - ".$cliente_endereco->complemento : null);
            }

            if($representante && isset($representante->representante)){
                $representante = $representante->representante;
                $representante_endereco = PessoaEndereco::where('principal','=','S')->where('pessoa_id','=',$representante->id)->first();

                $substituicoes["nome_representante"]              = (isset($representante->nome_formatado) ? $representante->nome_formatado : null);
                $substituicoes["data_nascimento_representante"]   = (isset($representante->dt_nasci_formatada) ? $representante->dt_nasci_formatada : null);
                $substituicoes["nacionalidade_representante"]     = ($representante && isset($representante->nacionalidade) && isset($representante->nacionalidade->nome)) ? $representante->nacionalidade->nome : null;
                $substituicoes["estado_civil_representante"]      = ($representante && isset($representante->estado_civil) && isset($representante->estado_civil->nome)) ? $representante->estado_civil->nome : null;
                $substituicoes["profissao_representante"]         = (isset($representante->profissao) ? $representante->profissao : null);
                $substituicoes["rg_representante"]                = (isset($representante->rg_ie_formatado) ? $representante->rg_ie_formatado : null);
                $substituicoes["orgao_emissor_representante"]     = (isset($representante->orgao_emissor) ? $representante->orgao_emissor : null);
                $substituicoes["cpf_representante"]               = (isset($representante->cpf_cnpj_formatado) ? $representante->cpf_cnpj_formatado : null);

                if($representante_endereco){
                    $substituicoes["rua_representante"] = (isset($representante_endereco->rua) ? $representante_endereco->rua : null);
                    $substituicoes["numero_representante"] = (!empty($representante_endereco->numero) ? ", ".$representante_endereco->numero : null);
                    $substituicoes["bairro_representante"] = (isset($representante_endereco->bairro) ? $representante_endereco->bairro : null);
                    $substituicoes["cidade_representante"] = ($representante_endereco && isset($representante_endereco->cidade) && isset($representante_endereco->cidade->nome)) ? $representante_endereco->cidade->nome : null;
                    $substituicoes["uf_representante"]     = ($representante_endereco && isset($representante_endereco->cidade) && isset($representante_endereco->cidade->estado) && isset($representante_endereco->cidade->estado->sigla))
                                                                ? "/".$representante_endereco->cidade->estado->sigla
                                                                : null;
                    $substituicoes["cep_representante"]    = (isset($representante_endereco->cep_formatado) ? $representante_endereco->cep_formatado : null);
                    $substituicoes["complemento_representante"] = (!empty($representante_endereco->complemento) ? " - ".$representante_endereco->complemento : null);
                }
            }

            if(isset($param['dt_validade'])){
                $substituicoes["data_vencimento"] = "Data de validade: ".implode('/', array_reverse(explode('-', $param['dt_validade'])));
            }else{
                $substituicoes["data_vencimento"] = null;
            }

            if($complemento && $tipo_complemento == 'Atendimento'){
                $substituicoes['data_atendimento'] = (isset($complemento->data_atendimento) ? $complemento->data_atendimento : null);
                $substituicoes['inicio_atendimento'] = (isset($complemento->data_atendimento) ? $complemento->data_atendimento : null);
            }

            // Pagamentos (contrato)
           if ($complemento && $tipo_complemento == 'Contrato') {
    $pagamentosContrato = ContratoPagamentoParcela::where('contrato_id','=',$complemento->id)
        ->orderby('contrato_opcao_pagamento_id')->load();

                if (!empty($pagamentosContrato)) {
                    // monta lista de subtags para as duas versões
                    $tags  = '';
                    $tags1 = '';
                    foreach ($pagamentosContrato as $i2 => $p) {
                        $tags  .= '${informacoes_pagamento'.$i2.'}';
                        $tags1 .= '${informacoes_pagamento'.$i2.'#1}';
                    }
                    $templateProcessor->setValue('informacoes_pagamento', $tags);
                    $templateProcessor->setValue('informacoes_pagamento#1', $tags1);

                    $clausula = (ContratoConfig::find(1))->clausula_pagamento;
                    $sub = 1;
                    foreach ($pagamentosContrato as $i2 => $p) {
                        $tr = new \PhpOffice\PhpWord\Element\TextRun();
                        if ($sub > 1) $tr->addTextBreak(2);
                        $tr->addText("$clausula.$sub ", ['bold'=>true, 'name'=>'Calibri Light', 'size'=>10]);
                        $tr->addText($p->descritivo,    ['name'=>'Calibri Light', 'size'=>10]);
                        $sub++;

                        // seta a subtag nas duas formas
                        $templateProcessor->setComplexValue('informacoes_pagamento'.$i2, $tr);
                        $templateProcessor->setComplexValue('informacoes_pagamento'.$i2.'#1', $tr);
                    }
                } else {
                    $templateProcessor->setValue('informacoes_pagamento', '');
                    $templateProcessor->setValue('informacoes_pagamento#1', '');
                }
            }

            foreach ($substituicoes as $k => $v) {
                $set($k, $v);
            }

            // garante também o 'objeto' nas duas formas
            $set('objeto', $objeto);

            $destino = "files/documents/{$modeloDocumento->id}/".$cliente->nome."/";
            if (!file_exists($destino)) mkdir($destino, 0777, true);
            $nome_arquivo = str_replace(' ','_', $modeloDocumento->nome."_".date("Y-m-d"));
            $docPath = $destino.$nome_arquivo.".docx";
            $pdfPath = $destino.$nome_arquivo.".pdf";

            if ($debug) { $templateProcessor->saveAs($docPath); } else { @$templateProcessor->saveAs($docPath); }

            @\PhpOffice\PhpWord\Settings::setPdfRendererPath('vendor/dompdf/dompdf');
            @\PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');

            $temp = @\PhpOffice\PhpWord\IOFactory::load($docPath);
            $xmlWriter = @\PhpOffice\PhpWord\IOFactory::createWriter($temp , 'PDF');
            if ($debug) { $xmlWriter->save($pdfPath, true); } else { @$xmlWriter->save($pdfPath, true); }

            TTransaction::close();

            if ($debug) new TMessage('info', 'Documento gerado: '.$docPath);

            return [
                'autenticador' => $autenticador,
                'complemento_id' => ($complemento ? $complemento->id : null),
                'novo_nome_arquivo' => $destino.$nome_arquivo,
                'modelo_documento_id' => $modeloDocumento->id
            ];
        
        } catch (Exception $e) {
            if (TTransaction::get()) TTransaction::rollback();
            self::dbg('EXCEPTION preencherDocumento', $e->getMessage());
            new TMessage('error', 'Erro ao gerar documento: '.$e->getMessage());
        }
    }

    private static function normalizeClientesIds($raw): array
    {
        // "1,2,3"
        if (is_string($raw)) {
            return array_values(
                array_filter(array_map('intval', array_map('trim', explode(',', $raw))))
            );
        }
        // ["1","2","3"] (sequencial)
        if (is_array($raw)) {
            $isAssoc = array_keys($raw) !== range(0, count($raw)-1);
            if ($isAssoc) {
                // {"1":"João","5":"Maria"}
                return array_values(
                    array_filter(array_map('intval', array_keys($raw)))
                );
            }
            // Pode ser [{id:1,name:"João"}, ...]
            if (!empty($raw) && (is_array($raw[0]) || is_object($raw[0]))) {
                $out = [];
                foreach ($raw as $item) {
                    if (is_array($item) && isset($item['id']))       $out[] = (int)$item['id'];
                    elseif (is_object($item) && isset($item->id))    $out[] = (int)$item->id;
                    else                                             $out[] = (int)$item; // fallback
                }
                return array_values(array_filter($out));
            }
            // sequencial simples
            return array_values(array_filter(array_map('intval', $raw)));
        }
        // single int
        if (is_numeric($raw)) return [ (int)$raw ];

        return [];
    }

    private static function verificarTiposClientes(array $idsClientes): array
    {
        $temPf = false;
        $temPj = false;

        foreach ($idsClientes as $clienteId)
        {
            $cliente = Pessoa::find((int) $clienteId);

            if (!$cliente) {
                continue;
            }

            if ($cliente->tipo_pessoa_id == TipoPessoa::FISICA)
            {
                $temPf = true;
            }
            elseif ($cliente->tipo_pessoa_id == TipoPessoa::JURIDICA)
            {
                $temPj = true;
            }

            // Já sabemos que é misto, não precisa continuar consultando
            if ($temPf && $temPj) {
                break;
            }
        }

        return [
            'pf'    => $temPf,
            'pj'    => $temPj,
            'misto' => ($temPf && $temPj)
        ];
    }

}