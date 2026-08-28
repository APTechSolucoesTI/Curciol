<?php

class APIPublicacaoController extends TPage
{
    private static $database = 'escritorio';

    public static function enviarAppChat($mensagem){
        
        $token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ0ZW5hbnRJZCI6MSwicHJvZmlsZSI6ImFkbWluIiwic2Vzc2lvbklkIjo0NCwiaWF0IjoxNzQ1NDIwMjA2LCJleHAiOjE4MDg0OTIyMDZ9.P5D2aFKD_OZdPm0-0FTqltyGhyHYPAyY9th2T3yHwhc';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api1.apchat.com.br/v2/api/external/066de006-74e0-4815-8339-03b62bf2987b",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode([
                "body" => $mensagem,
                "number" => "5519988790174",
                "externalKey" => "unique_id",
                "isClosed" => false
            ]),
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer $token",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
    }
    
    public static function sincronizarSeteDias(){
        try
        {
            self::zerarSystemSqlLog();
            
            $objeto = new stdClass();
            $objeto->de  = date('Y-m-d', strtotime('-7 days'));
            $objeto->ate = date('Y-m-d');

            APIPublicacaoController::buscarPublicacoes($objeto);

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function sincronizarHoje(){
        try
        {
            self::zerarSystemSqlLog();
            
            $objeto = new stdClass();
            $objeto->de  = date('Y-m-d');
            $objeto->ate = date('Y-m-d');

            APIPublicacaoController::buscarPublicacoes($objeto);

        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function zerarSystemSqlLog(){
        TTransaction::open('log');
        SystemSqlLog::where('id','>=',0)->delete();
        TTransaction::close();
    }
    
    public static function buscarPublicacoes($objeto){
        try {

            self::zerarSystemSqlLog();
            
            TTransaction::open(self::$database);

            $messageAction = null;

            $chaves = [];

            $profissionais = PessoaGrupo::where('grupo_id','=',Grupo::PROFISSIONAL)->load();
            foreach($profissionais as $profissional){
                $pessoa = Pessoa::find($profissional->pessoa_id);
                if($pessoa->chave_aasp!=null){
                    $chaves[$pessoa->id] = $pessoa->chave_aasp;
                }
            }
            TTransaction::close();
            
            $countNew = 0;
            $countAtt = 0;
            
            foreach($chaves as $profissional_id=>$chave){
                
                $start = new \DateTime($objeto->de);
                $end = new \DateTime($objeto->ate.' +1 day');
                $periodArr = new \DatePeriod($start , new \DateInterval('P1D') , $end);
                
                foreach($periodArr as $period) {
                    
                   // $url = "https://sherlyn-uncreditable-tabitha.ngrok-free.dev/proxy.php/api/Associado/intimacao/json?chave=$chave&data=".$period->format('d/m/Y');
                   $url = "https://intimacaoapi.aasp.org.br/api/Associado/intimacao/json?chave=$chave&data=".$period->format('d/m/Y');
                 
                    $json = file_get_contents($url) ?? null;

                    
                    if($json != null){

                        $jo = json_decode($json) ?? null;
                        
                        if($jo != null){
                            
                            if($jo->intimacoes){
            
                                foreach ($jo->intimacoes as $value) {

                                    self::zerarSystemSqlLog();

                                    TTransaction::open(self::$database);
                                    
                                    //JORNAL DE PUBLICAÇÃO
                                    $jornal = Jornal::where('nome','=',$value->jornal->nomeJornal)->first();
                
                                    if(!$jornal){
                                        $jornal = new Jornal();
                                        $jornal->nome = $value->jornal->nomeJornal;
                                        $jornal->store();
                                    }
                                    
                                    TTransaction::close();
                                    
                                    $processoPrincipal = null;
                                    $processoUnico = null;
                                    
                                    $buscaBarra = $value->numeroUnicoProcesso."/";
                                    $posicaoIniBarra = strpos($value->textoPublicacao,$buscaBarra);
                                    
                                    $buscaParentese = "(processo principal ";
                                    $posicaoIniParentese = strpos($value->textoPublicacao,$buscaParentese);
                                    
                                    $buscaNOrigem = "Nº origem: ";
                                    $posicaoIniNOrigem = strpos($value->textoPublicacao,$buscaNOrigem);

                                    $buscaOrigem = "Processo de origem: ";
                                    $posicaoIniOrigem = strpos($value->textoPublicacao, $buscaOrigem);
                                    
                                    if(substr($value->numeroUnicoProcesso, -5) == '.0500'){
                                        TScript::create("console.log(".substr($value->numeroUnicoProcesso, -5).");");
                                    }
                    
                                    if($posicaoIniBarra !== false){
                                        if($value->numeroUnicoProcesso){
                                            if(substr($value->textoPublicacao, $posicaoIniBarra, strlen($buscaBarra)+5) == $value->numeroUnicoProcesso."/50000"){
                                                $processoUnico = $value->numeroUnicoProcesso;
                                            }else{
                                                // Encontra a posição do primeiro espaço após a barra
                                                $posicaoEspaco = strpos($value->textoPublicacao, ' ', $posicaoIniBarra + strlen($buscaBarra));
                                                if ($posicaoEspaco !== false) {
                                                    // Se encontrou espaço após a barra, extrai a substring até esse espaço
                                                    $processoUnico = substr($value->textoPublicacao, $posicaoIniBarra, $posicaoEspaco - $posicaoIniBarra);
                                                } else {
                                                    // Se não encontrou espaço após a barra, extrai até o final da string
                                                    $processoUnico = substr($value->textoPublicacao, $posicaoIniBarra, strlen($buscaBarra)+2);
                                                }
                                                $processoPrincipal = $value->numeroUnicoProcesso;
                                            }
                                        }
                                    }elseif($posicaoIniParentese !== false){
                                        $posicaoFinParentese = strpos($value->textoPublicacao,")",$posicaoIniParentese);
                                        $processoUnico = $value->numeroUnicoProcesso;
                                        $processoPrincipal = str_replace($buscaParentese, "", substr($value->textoPublicacao, $posicaoIniParentese, $posicaoFinParentese-$posicaoIniParentese));
                                    }elseif($posicaoIniNOrigem !== false){
                                        $posicaoFinNOrigem = strpos($value->textoPublicacao,";",$posicaoIniNOrigem);
                                        $processoUnico = $value->numeroUnicoProcesso;
                                        $processoPrincipal = str_replace($buscaNOrigem, "", substr($value->textoPublicacao, $posicaoIniNOrigem, $posicaoFinNOrigem-$posicaoIniNOrigem));
                                    }elseif(substr($value->numeroUnicoProcesso, -5) == '.0500'){
                                        $processoUnico = $value->numeroUnicoProcesso;
                                        // Aqui extraímos o número do processo da origem e formatamos a parte após a barra
                                        if ($posicaoIniOrigem !== false) {
                                            $posicaoIniNumero = $posicaoIniOrigem + strlen($buscaOrigem);
                                            $parteTexto = substr($value->textoPublicacao, $posicaoIniNumero);
                                            $posicaoBarra = strpos($parteTexto, '/');

                                            if ($posicaoBarra !== false) {
                                                $antesDaBarra = substr($parteTexto, 0, $posicaoBarra);
                                                $depoisDaBarra = substr($parteTexto, $posicaoBarra + 1);
                                                $depoisDaBarraLimpo = (int) $depoisDaBarra;
                                                $depoisDaBarraFormatado = ($depoisDaBarraLimpo>99) ? $depoisDaBarraLimpo : str_pad($depoisDaBarraLimpo, 2, '0', STR_PAD_LEFT);
                                                $processoPrincipal = $antesDaBarra . '/' . $depoisDaBarraFormatado;
                                            }
                                        }
                                    }else{
                                        $processoUnico = $value->numeroUnicoProcesso;
                                    }
                                    
                                    $processoUnico = self::normalizarProcessoUnicoAposBarra($processoUnico);

                                    
                                    if($processoUnico == "" || $processoUnico==null){
                                        
                                        $padrao = "/\(\d{4}\/\d{7}-\d\)/";
                                        
                                        // Padrao da expressao regular para encontrar o formato (9999/9999999-9)
                                        if (preg_match($padrao, $value->textoPublicacao, $matches)) {
                                            $processoUnico = str_replace("(","",str_replace(")","",$matches[0]));
                                        }
                                    }
                                    
                                    $processoUnico = self::normalizarProcessoUnicoAposBarra($processoUnico);

                                
                                    //ADICIONA MASCARA NO NUMERO DO PROCESSO QUANDO O NUMERO TEM 20 CARACTERES
                                    if(strlen($processoUnico)==20){
                                        $processoUnico= substr($processoUnico,0,7)."-".
                                                        substr($processoUnico,7,2).".".
                                                        substr($processoUnico,9,4).".".
                                                        substr($processoUnico,13,1).".".
                                                        substr($processoUnico,14,2).".".
                                                        substr($processoUnico,16,4);
                                    }
                                    
                                    TTransaction::open(self::$database);
                                    
                                    $criteria  = new TCriteria();
                                    $criteria->add(new TFilter('numero_publicacao','=',$value->numeroPublicacao));
                                    $criteria->add(new TFilter('numero_arquivo','=',$value->numeroArquivo));
                                    
                                    $criteria1 = new TCriteria();
                                    $criteria1->add(new TFilter('numero_unico_processo','=',$processoUnico), TExpression::OR_OPERATOR);
                                    $criteria1->add(new TFilter('numero_unico_processo','is',null), TExpression::OR_OPERATOR);
                                    
                                    $criteria->add($criteria1);
                                    
                                    $repository = new TRepository('Publicacao');
                                    $criteria->setProperty('limit', 1);
                                    $publicacao = $repository->load($criteria, FALSE);
                                    
                                    
                                    //SE NÃO TIVER, CRIA UMA
                                    if($publicacao == null){
                
                                        $publicacao = new Publicacao();
                                        
                                        $textoPublicacao = $value->textoPublicacao;
                                        $textoPublicacao = str_replace('a target="_blank" href="','',$textoPublicacao);
                                        
                                        $pos = strrpos($textoPublicacao, '"https://');
                                        if ($pos !== false) {
                                            $textoPublicacao = substr($textoPublicacao, 0, $pos);
                                        }
                                        
                                        $publicacao->texto = $textoPublicacao;
                                        $publicacao->titulo = $value->titulo;
                                        $publicacao->numero_publicacao = $value->numeroPublicacao;
                                        $publicacao->numero_arquivo = $value->numeroArquivo;
                                        $publicacao->cabecalho = $value->cabecalho;
                                        $publicacao->rodape = $value->rodape;
                
                                        $publicacao->numero_unico_processo = $processoUnico;
                                        $publicacao->numero_processo_principal = $processoPrincipal ?? null;
                
                                        $publicacao->jornal_id = $jornal->id;
                                        $publicacao->data_tratamento = $value->jornal->dataTratamento;
                                        $publicacao->data_disponibilizacao = $value->jornal->dataDisponibilizacao_Publicacao;
                                        $publicacao->termo_ref_data = $value->jornal->termoReferenciaData;
                                        $publicacao->etapa_verificada = 'N';
                                        $publicacao->store();
                                        
                                        $countNew++;
                                        
                                        $publicacao = Publicacao::find($publicacao->id);
                                        
                                        APIPublicacaoController::adicionarMovimentacao($publicacao->id, "Publicação sincronizada.", null, null);
                
                                    }else{
                                        $publicacao = ($publicacao)[0];
                                    }                                    
                                    
                                    APIPublicacaoController::buscarPrazo($publicacao);
                                    
                                    APIPublicacaoController::vincularProcesso($publicacao);
                                    
                                    if(isset($processoPrincipal) && $processoPrincipal!=null && !empty($processoPrincipal) && 
                                        isset($processoUnico) && $processoUnico!=null && !empty($processoUnico))
                                        APIPublicacaoController::vincularProcessoPrincipal($publicacao, $processoPrincipal);
                                    
                                    APIPublicacaoController::vincularProfissionais($publicacao, $value, $profissional_id);
                                    
                                    TTransaction::close();
                                    
                                    $countAtt++;
                                }
                            }
                        }
                    }
                }
            }
            $erro = error_get_last();
            if ($erro && ($countNew+$countAtt) == 0) {
                throw new Exception("Erro ao acessar a URL: " . $erro['message']);
            }

            LogCrontab::registrarLog("Buscar Publicações", __METHOD__, 0, 'Publicações sincronizadas. '.$countNew.' novas e '.($countAtt-$countNew).' atualizadas.', "Arquivo: APIPublicacaoController<br/>Linha: " . __LINE__.".", 1);
        }
        catch (Exception $e) 
        {
            LogCrontab::registrarLog("Buscar Publicações", __METHOD__, 1, "Exception: ".$e->getMessage(), "Arquivo: " . $e->getFile() . "<br/>Linha: " . $e->getLine().".", 1);
        }
    }
    
    public static function vincularProcesso($publicacao){
        
        //VERIFICA SE JA TEM UM PROCESSO COM O NÚMERO TRAZIDO DA PUBLICAÇÃO
        $processo = Processo::where('numero_cnj_numero','=',$publicacao->numero_unico_processo)->first();
        
        /*
        if(!$processo){
            if($publicacao->numero_unico_processo){
                $processo = new Processo();
                $processo->tipo_processo_id = TipoProcesso::JUDICIAL;
                $processo->numero_cnj_numero = $publicacao->numero_unico_processo;
                $processo->store();
                
                APIPublicacaoController::adicionarMovimentacao($publicacao->id, "Processo criado.", null, $processo->id);
            }
        }
        
        */
        
        //SE TIVER, VINCULA NA PUBLICAÇÃO
        if($processo){
            $publicacao->processo_id = $processo->id;
            $publicacao->store();
        
        
            //ATUALIZAR PUBLICAÇÕES COM O NUMERO DO PROCESSO PRINCIPAL
            $publicacoes = Publicacao::where('numero_unico_processo','=',$publicacao->numero_unico_processo)->load();
            foreach($publicacoes as $publicacao){
                if(!$publicacao->processo_id){
                    $publicacao->processo_id = $processo->id;
                    $publicacao->store();
                    APIPublicacaoController::adicionarMovimentacao($publicacao->id, "Processo vinculado.", null, $processo->id);
                }
            }
        }
    }
    
    public static function vincularProcessoPrincipal($publicacao, $numero_processo_principal){
        //VERIFICA SE JA TEM UM PROCESSO COM O NÚMERO PRINCIPAL TRAZIDO DA PUBLICAÇÃO SE NÃO ESTIVER VINCULADO
        if($publicacao->processo_id){
            if($publicacao->numero_processo_principal){
                $principal = Processo::where('numero_cnj_numero','=',$numero_processo_principal)->first();
                
                /*
                if(!$principal){
                    $principal = new Processo();
                    $principal->tipo_processo_id = TipoProcesso::JUDICIAL;
                    $principal->numero_cnj_numero = $numero_processo_principal;
                    $principal->store();
                }
                */
                
                if($principal){
                    $vinculo = ProcessoVinculo::where('processo_principal_id','=',$principal->id)
                                                ->where('processo_incidente_id','=',$publicacao->processo_id)
                                                ->count();
                    if($vinculo<1){                      
                        $vinculo = new ProcessoVinculo();
                        $vinculo->processo_principal_id = $principal->id;
                        $vinculo->processo_incidente_id = $publicacao->processo_id;
                        $vinculo->store();
                    }
                }
            }
            
            //ATUALIZAR PUBLICAÇÕES COM O NUMERO DO PROCESSO PRINCIPAL
            $publicacoesExistentes = Publicacao::where('processo_id','=',$publicacao->processo_id)->load();
            foreach($publicacoesExistentes as $publicacaoExistente){
                if($publicacaoExistente->numero_processo_principal == null || !$publicacaoExistente->numero_processo_principal || empty($publicacaoExistente->numero_processo_principal)){
                    $publicacaoExistente->numero_processo_principal = $publicacao->numero_processo_principal;
                    $publicacaoExistente->store();
                }
            }
        }
    }
    
    public static function vincularProfissionais($publicacao, $value, $profissional_id){
        //VERIFICA SE O PROFISSIONAL JA ESTA VINCULADO A PUBLICAÇÃO
        $publicacaoProfissional = PublicacaoProfissional::where('publicacao_id','=',$publicacao->id)
                                                    ->where('profissional_id','=',$profissional_id)
                                                    ->where('codigo_relacionamento','=',$value->codigoRelacionamento)
                                                    ->first();
                                                    
        //VINCULA A PUBLICAÇÃO AO PROFISSIONAL SE NÃO ESTIVER                          
        if(!$publicacaoProfissional && Pessoa::find($profissional_id)){
            $publicacaoProfissional = new PublicacaoProfissional();
            $publicacaoProfissional->publicacao_id = (int) $publicacao->id;
            $publicacaoProfissional->profissional_id = $profissional_id;
            $publicacaoProfissional->codigo_relacionamento = $value->codigoRelacionamento;
            $publicacaoProfissional->store();
        }
    }
    
    public static function adicionarMovimentacao($publicacao_id, $descricao, $tarefa_id, $processo_id){
        $movimentacao = new PublicacaoMovimentacao();
        $movimentacao->publicacao_id = $publicacao_id;
        $movimentacao->descricao = $descricao;
        $movimentacao->tarefa_id = $tarefa_id ?? null;
        $movimentacao->processo_id = $processo_id ?? null;
        $movimentacao->store();
    }
    
    public static function buscarPrazo($publicacao){
        
        PublicacaoSugestaoPrazo::where('publicacao_id','=',$publicacao->id)->delete();
        
        $repository = new TRepository('ConfigBuscaPrazo');
        $criteria = new TCriteria;
        $param['order'] = 'pont';
        $param['direction'] = 'desc';
        $criteria->setProperties($param);
        $configuracoes = $repository->load($criteria);
        
        foreach($configuracoes as $configuracao){
            foreach(ConfigBuscaPrazoTexto::where('config_busca_prazo_id','=',$configuracao->id)->load() as $configuracaoTexto){
                if(stripos($publicacao->texto, $configuracaoTexto->texto) !== false){
                    $sugestao = new PublicacaoSugestaoPrazo();
                    $sugestao->config_busca_prazo_id = $configuracaoTexto->config_busca_prazo_id;
                    $sugestao->publicacao_id = $publicacao->id;
                    $sugestao->resultado_busca = str_ireplace($configuracaoTexto->texto, "<span style='background-color:#fff000;'>".$configuracaoTexto->texto."</span>", $publicacao->texto);
                    $sugestao->store();
                }
            }
        }
    }

    private static function normalizarProcessoUnicoAposBarra(?string $processoUnico): ?string
    {
        if ($processoUnico === null) {
            return null;
        }

        $processoUnico = trim($processoUnico);
        if ($processoUnico === '') {
            return $processoUnico;
        }

        // Se tiver barra, valida o sufixo
        if (strpos($processoUnico, '/') !== false) {
            $partes = explode('/', $processoUnico, 2);
            $antes = trim($partes[0]);
            $depois = trim($partes[1] ?? '');

            // Se depois da barra NÃO for só dígitos, corta
            if ($depois === '' || !ctype_digit($depois)) {
                return $antes;
            }

            // Se for só números, mantém como está (com /)
            return $antes . '/' . $depois;
        }

        return $processoUnico;
    }

   public static function onVerificaPublicacaoEtapa (){
    try {
        TTransaction::open(self::$database);
        $contador = 0;
        $hoje = date('Y-m-d H:i:s');
        $updates = [];
        $inserts = [];

        $conn = TTransaction::get();

        // BUSCA AS PALAVRAS E JA PEGA SE A ETAPA SERVE PRA JUDICIAL/EXTRAJUDICIAL
        $sqlPal = "
            SELECT 
                epc.id,
                epc.publicacao_etapa_id,
                epc.palavra_chave,
                pe.ordem_prioridade,
                pe.judicial,
                pe.extrajudicial
            FROM etapa_palavras_chaves epc
            LEFT JOIN publicacao_etapa pe ON pe.id = epc.publicacao_etapa_id
            ORDER BY pe.ordem_prioridade DESC
        ";

        $resultPal = $conn->query($sqlPal);
        $palavras = $resultPal->fetchAll(PDO::FETCH_OBJ);

        $mapaJudicial = [];
        $mapaExtrajudicial = [];
        $regexJudicial = [];
        $regexExtrajudicial = [];

        if (!empty($palavras)) {
            foreach ($palavras as $palavra) {
                if (empty($palavra->palavra_chave)) {
                    continue;
                }

                $p = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $palavra->palavra_chave) ?: $palavra->palavra_chave;
                $p = preg_replace('/[^a-zA-Z0-9\s_-]/', '', $p);
                $p = strtolower(trim($p));

                if (empty($p)) {
                    continue;
                }

                // ETAPA DISPONIVEL PRA PROCESSO JUDICIAL
                if ($palavra->judicial == 'S') {
                    $regexJudicial[] = preg_quote($p, '/');

                    if (!isset($mapaJudicial[$p])) {
                        $mapaJudicial[$p] = $palavra->publicacao_etapa_id;
                    }
                }

                // ETAPA DISPONIVEL PRA PROCESSO EXTRAJUDICIAL
                if ($palavra->extrajudicial == 'S') {
                    $regexExtrajudicial[] = preg_quote($p, '/');

                    if (!isset($mapaExtrajudicial[$p])) {
                        $mapaExtrajudicial[$p] = $palavra->publicacao_etapa_id;
                    }
                }
            }
        }

        $regexJudicial = !empty($regexJudicial) ? '/(' . implode('|', $regexJudicial) . ')/i' : null;
        $regexExtrajudicial = !empty($regexExtrajudicial) ? '/(' . implode('|', $regexExtrajudicial) . ')/i' : null;

        // JA TRAZ JUNTO O TIPO DO PROCESSO
        $sqlPub = "
            SELECT
                pub.id, 
                pub.texto,
                pub.processo_id,
                proc.tipo_processo_id
            FROM publicacao pub
            INNER JOIN processo proc ON proc.id = pub.processo_id
            WHERE pub.publicacao_etapa_id IS NULL
            AND pub.processo_id IS NOT NULL
        ";

        $resultPub = $conn->query($sqlPub);

        $sqlUpdate = "UPDATE publicacao 
                      SET publicacao_etapa_id = :etapa_id 
                      WHERE id = :id";

        $stmt = $conn->prepare($sqlUpdate);

        $sqlInsert = "INSERT INTO processo_publicacoes
                      (processo_id, publicacao_id, publicacao_etapa_id, date_log)
                      VALUES (:processo_id, :publicacao_id, :etapa_id, :date_log)";

        $stmtInsert = $conn->prepare($sqlInsert);

        $lote = 0;

        while ($pub = $resultPub->fetch(PDO::FETCH_OBJ)) {

            $lote++;
            $etapa_id = 1;
            $processo_id = $pub->processo_id;

            if ($lote >= 1000) {
                foreach ($updates as $u) {
                    $stmt->execute($u);
                }

                foreach ($inserts as $i) {
                    $stmtInsert->execute($i);
                }

                $updates = [];
                $inserts = [];
                $lote = 0;
            }

            if (empty($pub->texto)) {
                $updates[] = [
                    ':etapa_id' => $etapa_id,
                    ':id' => $pub->id
                ];

                $inserts[] = [
                    ':processo_id' => $processo_id,
                    ':publicacao_id' => $pub->id,
                    ':etapa_id' => $etapa_id,
                    ':date_log' => $hoje
                ];

                continue;
            }

            $t = strip_tags($pub->texto);
            $t = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $t) ?: $t;
            $t = preg_replace('/[^a-zA-Z0-9\s_-]/', '', $t);
            $t = strtolower($t);
            $t = preg_replace('/\s+/', ' ', $t);

            // JUDICIAL
            if ($pub->tipo_processo_id == 1) {
                if ($regexJudicial && preg_match($regexJudicial, $t, $match)) {
                    $palavraEncontrada = strtolower($match[1]);
                    $etapa_id = $mapaJudicial[$palavraEncontrada] ?? 1;
                    $contador++;
                }
            }

            // EXTRAJUDICIAL
            elseif ($pub->tipo_processo_id == 2) {
                if ($regexExtrajudicial && preg_match($regexExtrajudicial, $t, $match)) {
                    $palavraEncontrada = strtolower($match[1]);
                    $etapa_id = $mapaExtrajudicial[$palavraEncontrada] ?? 1;
                    $contador++;
                }
            }

            $updates[] = [
                ':etapa_id' => $etapa_id,
                ':id' => $pub->id
            ];

            $inserts[] = [
                ':processo_id' => $processo_id,
                ':publicacao_id' => $pub->id,
                ':etapa_id' => $etapa_id,
                ':date_log' => $hoje
            ];
        }

        foreach ($updates as $u) {
            $stmt->execute($u);
        }

        foreach ($inserts as $i) {
            $stmtInsert->execute($i);
        }

        TToast::show('success', "{$contador} registros atualizados", 'topRight', 'far:check-circle');
        TTransaction::close(); 

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onExcluirVerificacaoPublicacaoEtapa (){
        try {
            TTransaction::open(self::$database);            
            $conn = TTransaction::get();              

            $sqlUpdate = "UPDATE publicacao 
                            SET publicacao_etapa_id = null
                            WHERE publicacao_etapa_id IS NOT NULL";

            $stmt = $conn->prepare($sqlUpdate);
            if (!$stmt->execute()) {
                throw new Exception('Erro ao desvincular etapa de publicações!');
            }

            $result = $conn->exec("DELETE FROM processo_publicacoes");

            if ($result == false) {
                throw new Exception('Erro ao deletar processo_publicacoes!');
            }

            TToast::show('success', "sincronização excluída", 'topRight', 'far:check-circle');
            TTransaction::close(); 

        } catch (Exception $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}