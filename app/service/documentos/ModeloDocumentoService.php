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
    public static function onVerificarDadosCliente($cliente, $modelo, $objeto = null, $qtdePagamento = 0) {
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
            if ($cliente->tipo_pessoa_id == TipoPessoa::FISICA) {
                $dadosObrigatorios = $rep
                    ? ModeloDocumentoPfr::where('modelo_documento_id', '=', $modelo->id)->first()
                    : ModeloDocumentoPf::where('modelo_documento_id', '=', $modelo->id)->first();
            } else {
                $dadosObrigatorios = ModeloDocumentoPj::where('modelo_documento_id', '=', $modelo->id)->first();
                if (!$rep) $dadosVerificados[] = "representante";
            }

            self::verificarCampoObrigatorio($dadosVerificados, $objeto, ($dadosObrigatorios ? $dadosObrigatorios->objeto : null), "objeto");

            if (($dadosObrigatorios && $dadosObrigatorios->informacoes_pagamento === 'S') && $qtdePagamento < 1) {
                $dadosVerificados[] = "informações de pagamento";
            }

            self::verificarCampoObrigatorio(
                $dadosVerificados,
                (isset($cliente->cpf_cnpj) ? $cliente->cpf_cnpj : null),
                ($cliente->tipo_pessoa_id == TipoPessoa::FISICA)
                    ? ($dadosObrigatorios ? $dadosObrigatorios->cpf  : null)
                    : ($dadosObrigatorios ? $dadosObrigatorios->cnpj : null),
                ($cliente->tipo_pessoa_id == TipoPessoa::FISICA) ? "CPF" : "CNPJ"
            );

            self::verificarCampoObrigatorio(
                $dadosVerificados,
                (isset($cliente->dt_nasci_formatada) ? $cliente->dt_nasci_formatada : null),
                ($cliente->tipo_pessoa_id == TipoPessoa::FISICA)
                    ? ($dadosObrigatorios ? $dadosObrigatorios->data_nascimento : null)
                    : ($dadosObrigatorios ? $dadosObrigatorios->data_abertura   : null),
                ($cliente->tipo_pessoa_id == TipoPessoa::FISICA) ? "Data de nascimento" : "Data de abertura"
            );

            if ($cliente->tipo_pessoa_id == TipoPessoa::FISICA) {
                self::verificarCampoObrigatorio($dadosVerificados, (isset($cliente->rg_ie) ? $cliente->rg_ie : null), ($dadosObrigatorios ? $dadosObrigatorios->rg : null), "rg");
                self::verificarCampoObrigatorio($dadosVerificados, (isset($cliente->orgao_emissor) ? $cliente->orgao_emissor : null), ($dadosObrigatorios ? $dadosObrigatorios->rg : null), "órgão emissor do rg");
                self::verificarCampoObrigatorio($dadosVerificados, (isset($cliente->nacionalidade) ? $cliente->nacionalidade : null), ($dadosObrigatorios ? $dadosObrigatorios->nacionalidade : null), "nacionalidade");
                self::verificarCampoObrigatorio($dadosVerificados, (isset($cliente->estado_civil) ? $cliente->estado_civil : null),     ($dadosObrigatorios ? $dadosObrigatorios->estado_civil  : null), "estado civil");
                self::verificarCampoObrigatorio($dadosVerificados, (isset($cliente->profissao) ? $cliente->profissao : null),           ($dadosObrigatorios ? $dadosObrigatorios->profissao     : null), "profissão");
            }

            if ((self::verificarEndereco($cliente->id) < 1) && ($dadosObrigatorios && $dadosObrigatorios->endereco === "S")) {
                $dadosVerificados[] = "endereço principal";
            }

            if ($rep) {
                self::verificarCampoObrigatorio($dadosVerificados, (isset($rep->cpf_cnpj) ? $rep->cpf_cnpj : null),               ($dadosObrigatorios ? $dadosObrigatorios->cpf_rep           : null), "CPF do representante");
                self::verificarCampoObrigatorio($dadosVerificados, (isset($rep->dt_nasci_formatada) ? $rep->dt_nasci_formatada : null), ($dadosObrigatorios ? $dadosObrigatorios->data_nascimento    : null), "Data de nascimento do representante");

                if ($rep->tipo_pessoa_id == TipoPessoa::FISICA) {
                    self::verificarCampoObrigatorio($dadosVerificados, (isset($rep->rg_ie) ? $rep->rg_ie : null),                 ($dadosObrigatorios ? $dadosObrigatorios->rg_rep             : null), "rg do representante");
                    self::verificarCampoObrigatorio($dadosVerificados, (isset($rep->orgao_emissor) ? $rep->orgao_emissor : null), ($dadosObrigatorios ? $dadosObrigatorios->rg_rep             : null), "órgão emissor do rg do representante");
                    self::verificarCampoObrigatorio($dadosVerificados, (isset($rep->nacionalidade) ? $rep->nacionalidade : null), ($dadosObrigatorios ? $dadosObrigatorios->nacionalidade_rep  : null), "nacionalidade do representante");
                    self::verificarCampoObrigatorio($dadosVerificados, (isset($rep->estado_civil) ? $rep->estado_civil : null),   ($dadosObrigatorios ? $dadosObrigatorios->estado_civil_rep   : null), "estado civil do representante");
                    self::verificarCampoObrigatorio($dadosVerificados, (isset($rep->profissao) ? $rep->profissao : null),         ($dadosObrigatorios ? $dadosObrigatorios->profissao_rep      : null), "profissão do representante");
                }

                if ((self::verificarEndereco($rep->id) < 1) && ($dadosObrigatorios && $dadosObrigatorios->endereco_rep === "S")) {
                    $dadosVerificados[] = "endereço principal do representante";
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
                $representanteBase = PessoaRepresentantesLegais::where('pessoa_juridica_id', '=', $primeiroCliente->id)
                                                               ->where('principal', '=', 'S')
                                                               ->first();

                // Decide template base
                if ($primeiroCliente->tipo_pessoa_id == TipoPessoa::FISICA && !$representanteBase) {
                    $documentoBase = ModeloDocumentoPf::where('modelo_documento_id','=',$modeloDocumento->id)->first();
                } elseif ($primeiroCliente->tipo_pessoa_id == TipoPessoa::FISICA && $representanteBase) {
                    $documentoBase = ModeloDocumentoPfrep::where('modelo_documento_id','=',$modeloDocumento->id)->first();
                } else {
                    $documentoBase = ModeloDocumentoPj::where('modelo_documento_id','=',$modeloDocumento->id)->first();
                }
                if (!$documentoBase || !$documentoBase->filename) {
                    throw new Exception('Arquivo do modelo não configurado para MULTI.');
                }
                $nome_arquivo = $documentoBase->filename;
                self::ensureFile($nome_arquivo);
                self::dbg('Template base MULTI', $nome_arquivo);

                $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($nome_arquivo);

                $qtd = count($idsClientes);

               //PADRÃO NOVO: CLONE1, CLONE2, CLONE3....CLONE9 (tolerante)
               self::cloneBlocosPadrao($templateProcessor, $qtd);

                $primeirosNomes = [];
                
                $templateProcessor->setValue('objeto', $objeto ?: '');

                // Preenche cada índice
                $idx = 1;
                foreach ($idsClientes as $clienteId) {
                    $cliente = Pessoa::find((int)$clienteId);
                    if (!$cliente) { self::dbg('Cliente não encontrado', $clienteId); $idx++; continue; }

                    $nomeRaw = $cliente->nome_formatado ?? $cliente->nome ?? 'CLIENTE';
                    $first = trim(preg_split('/\s+/u', $nomeRaw)[0] ?? 'CLIENTE');
                    $first = preg_replace('/[^\pL\pN _\.-]+/u', '', $first); // mantém letras, números, espaço, _.-
                    // evita vazio
                    $primeirosNomes[] = $first !== '' ? $first : 'CLIENTE';

                    $representante = PessoaRepresentantesLegais::where('pessoa_juridica_id', '=', $cliente->id)
                                                               ->where('principal', '=', 'S')
                                                               ->first();
                    $cliente_endereco = PessoaEndereco::where('principal','=','S')
                                                      ->where('pessoa_id','=',$cliente->id)
                                                      ->first();



                    // Autenticador por cliente (tentativas)
                    $autenticador = null;
                    $guard = 0;
                    do {
                        $autenticador = base64_encode(rand() . '-' . TSession::getValue('userid') . '-' . TSession::getValue('unitid'));
                        $existeA = Documento::where('autenticador','=',$autenticador)->count();
                        $existeB = ContratoDocumento::where('autenticador','=',$autenticador)->count();
                        $guard++;
                    } while (($existeA>0 || $existeB>0) && $guard < 5);

                    // Campos cliente
                    $templateProcessor->setValue("nome_cliente#{$idx}",          (isset($cliente->nome_formatado) ? $cliente->nome_formatado : null));
                    $templateProcessor->setValue("nome_profissional#{$idx}",     ($profissional ? $profissional->nome_formatado : null));
                    $templateProcessor->setValue("data_nascimento#{$idx}",       (isset($cliente->dt_nasci_formatada) ? $cliente->dt_nasci_formatada : null));
                    $templateProcessor->setValue("data_abertura#{$idx}",         (isset($cliente->dt_nasci_formatada) ? $cliente->dt_nasci_formatada : null));
                    $templateProcessor->setValue("nome_escritorio#{$idx}",       ($escritorio ? $escritorio->nome : null));
                    $templateProcessor->setValue("nacionalidade#{$idx}",         ($cliente && isset($cliente->nacionalidade) && isset($cliente->nacionalidade->nome)) ? $cliente->nacionalidade->nome : null);
                    $templateProcessor->setValue("estado_civil#{$idx}",          ($cliente && isset($cliente->estado_civil) && isset($cliente->estado_civil->nome)) ? $cliente->estado_civil->nome : null);
                    $templateProcessor->setValue("profissao#{$idx}",             (isset($cliente->profissao) ? $cliente->profissao : null));
                    $templateProcessor->setValue("rg#{$idx}",                    (isset($cliente->rg_ie_formatado) ? $cliente->rg_ie_formatado : null));
                    $templateProcessor->setValue("orgao_emissor#{$idx}",         (isset($cliente->orgao_emissor) ? $cliente->orgao_emissor : null));
                    $templateProcessor->setValue("cpf#{$idx}",                   (isset($cliente->cpf_cnpj_formatado) ? $cliente->cpf_cnpj_formatado : null));
                    $templateProcessor->setValue("cnpj#{$idx}",                  (isset($cliente->cpf_cnpj_formatado) ? $cliente->cpf_cnpj_formatado : null));
                    $templateProcessor->setValue("objeto#{$idx}",                $objeto);
                    $templateProcessor->setValue("informacoes_documento#{$idx}", $autenticador);
                    $templateProcessor->setValue("autenticador#{$idx}",          $autenticador);

                    if ($cliente_endereco) {
                        $templateProcessor->setValue("rua#{$idx}",     (isset($cliente_endereco->rua) ? $cliente_endereco->rua : null));
                        $templateProcessor->setValue("numero#{$idx}",  (!empty($cliente_endereco->numero) ? ", ".$cliente_endereco->numero : null));
                        $templateProcessor->setValue("bairro#{$idx}",  (isset($cliente_endereco->bairro) ? $cliente_endereco->bairro : null));
                        $templateProcessor->setValue(
                            "cidade#{$idx}",
                            ($cliente_endereco && isset($cliente_endereco->cidade) && isset($cliente_endereco->cidade->nome)) ? $cliente_endereco->cidade->nome : null
                        );
                        $templateProcessor->setValue(
                            "uf#{$idx}",
                            ($cliente_endereco && isset($cliente_endereco->cidade) && isset($cliente_endereco->cidade->estado) && isset($cliente_endereco->cidade->estado->sigla))
                                ? "/".$cliente_endereco->cidade->estado->sigla
                                : null
                        );
                        $templateProcessor->setValue("cep#{$idx}",     (isset($cliente_endereco->cep_formatado) ? $cliente_endereco->cep_formatado : null));
                        $templateProcessor->setValue("complemento#{$idx}", (!empty($cliente_endereco->complemento) ? " - ".$cliente_endereco->complemento : null));
                    }

                    // Representante
                    if ($representante && isset($representante->representante)) {
                        $rep = $representante->representante;

                        $templateProcessor->setValue("nome_representante#{$idx}",            (isset($rep->nome_formatado) ? $rep->nome_formatado : null));
                        $templateProcessor->setValue("data_nascimento_representante#{$idx}", (isset($rep->dt_nasci_formatada) ? $rep->dt_nasci_formatada : null));
                        $templateProcessor->setValue("nacionalidade_representante#{$idx}",   ($rep && isset($rep->nacionalidade) && isset($rep->nacionalidade->nome)) ? $rep->nacionalidade->nome : null);
                        $templateProcessor->setValue("estado_civil_representante#{$idx}",    ($rep && isset($rep->estado_civil) && isset($rep->estado_civil->nome)) ? $rep->estado_civil->nome : null);
                        $templateProcessor->setValue("profissao_representante#{$idx}",       (isset($rep->profissao) ? $rep->profissao : null));
                        $templateProcessor->setValue("rg_representante#{$idx}",              (isset($rep->rg_ie_formatado) ? $rep->rg_ie_formatado : null));
                        $templateProcessor->setValue("orgao_emissor_representante#{$idx}",   (isset($rep->orgao_emissor) ? $rep->orgao_emissor : null));
                        $templateProcessor->setValue("cpf_representante#{$idx}",             (isset($rep->cpf_cnpj_formatado) ? $rep->cpf_cnpj_formatado : null));

                        $rep_end = PessoaEndereco::where('principal','=','S')
                                                 ->where('pessoa_id','=',$rep->id)
                                                 ->first();
                        if ($rep_end) {
                            $templateProcessor->setValue("rua_representante#{$idx}",     (isset($rep_end->rua) ? $rep_end->rua : null));
                            $templateProcessor->setValue("numero_representante#{$idx}",  (!empty($rep_end->numero) ? ", ".$rep_end->numero : null));
                            $templateProcessor->setValue("bairro_representante#{$idx}",  (isset($rep_end->bairro) ? $rep_end->bairro : null));
                            $templateProcessor->setValue(
                                "cidade_representante#{$idx}",
                                ($rep_end && isset($rep_end->cidade) && isset($rep_end->cidade->nome)) ? $rep_end->cidade->nome : null
                            );
                            $templateProcessor->setValue(
                                "uf_representante#{$idx}",
                                ($rep_end && isset($rep_end->cidade) && isset($rep_end->cidade->estado) && isset($rep_end->cidade->estado->sigla))
                                    ? "/".$rep_end->cidade->estado->sigla
                                    : null
                            );
                            $templateProcessor->setValue("cep_representante#{$idx}",     (isset($rep_end->cep_formatado) ? $rep_end->cep_formatado : null));
                            $templateProcessor->setValue("complemento_representante#{$idx}", (!empty($rep_end->complemento) ? " - ".$rep_end->complemento : null));
                        }
                    }

                    // Validade (opcional)
                    if (isset($param['dt_validade'])) {
                        $templateProcessor->setValue("data_vencimento#{$idx}", "Data de validade: ".implode('/', array_reverse(explode('-', $param['dt_validade']))));
                    } else {
                        $templateProcessor->setValue("data_vencimento#{$idx}", null);
                    }

                    // Complementos
                    if ($complemento && $tipo_complemento == 'Atendimento') {
                        $templateProcessor->setValue("data_atendimento#{$idx}",   (isset($complemento->data_atendimento) ? $complemento->data_atendimento : null));
                        $templateProcessor->setValue("inicio_atendimento#{$idx}", (isset($complemento->data_atendimento) ? $complemento->data_atendimento : null));
                    }

                    

                    // “respiro” entre clientes (precisa existir ${espaco} no bloco)
                    $espaco = new \PhpOffice\PhpWord\Element\TextRun();
                    $espaco->addTextBreak(2);
                    $templateProcessor->setComplexValue("espaco#{$idx}", $espaco);

                    self::dbg('Preenchido cliente', ['idx'=>$idx, 'id'=>$clienteId, 'nome'=> (isset($cliente->nome)?$cliente->nome:null)]);
                    $idx++;
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

}