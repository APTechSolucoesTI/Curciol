<?php

class Teste471 extends TPage
{
    public function atualizaNumeroUnicoProcesso($param = null)
    {

        // Padrao da expressao regular para encontrar o formato (9999/9999999-9)
        $padrao = "/\(\d{4}\/\d{7}-\d\)/";
        
        TTransaction::open('escritorio');
        
        $jornal_id = (Jornal::where('nome','=','STJ')->first())->id;
        
        $publicacoes = Publicacao::where("processo_id","is",null)
                    ->where('numero_unico_processo','is',null)
                    ->where('data_disponibilizacao','>=','2016-08-01')
                    ->where('data_disponibilizacao','<=','2024-02-16')
                    ->where('jornal_id','=',$jornal_id)
                    ->load();
        
        foreach($publicacoes as $publicacao){
            
            // Faz a correspondência usando preg_match
            if (preg_match($padrao, $publicacao->texto, $matches)) {
                
                $publicacao->numero_unico_processo = str_replace("(","",str_replace(")","",$matches[0]));
                $publicacao->store();
                APIPublicacaoController::vincularProcesso($publicacao);
                
            }
        }
        TTransaction::close();
    }
    
    public function searchPublicacaoDuplicada(){
        TTransaction::open('escritorio');
            
        $conn = TTransaction::get();
        $result = $conn->query('SELECT id, numero_arquivo, numero_publicacao, numero_unico_processo FROM public.publicacao WHERE id in
                                (SELECT id FROM publicacao
                                WHERE numero_unico_processo is not null AND id IN (
                                  SELECT id
                                  FROM (
                                    SELECT id, ROW_NUMBER() OVER(PARTITION BY numero_arquivo, numero_publicacao, processo_id ORDER BY id) AS linha
                                    FROM publicacao
                                  ) publicacao
                                  WHERE linha > 1
                                )) ORDER BY numero_arquivo, numero_publicacao, numero_unico_processo;');
        
        $objects = $result->fetchAll(PDO::FETCH_CLASS, "stdClass");
        if($objects)
        {
            foreach($objects as $object) 
            { 
                $ids[] = $object->id;
            }    
        }
        
        
        return $publicacoes_duplicadas = Publicacao::where('id','in',$ids)->load();
    }
    
    public function onShow(){
        try{
            $publicacoes_duplicadas = Teste471::searchPublicacaoDuplicada();
            TTransaction::open('escritorio');
            foreach($publicacoes_duplicadas as $publicacao){
                $pub_profissionais = PublicacaoProfissional::where('publicacao_id','=',$publicacao->id)->load();
                foreach($pub_profissionais as $pub_profissional){
                    $publicacao_duplicada = Publicacao::where('id','=',$pub_profissional->publicacao_id)->first();
                    $primeiraPublicacao = Publicacao::where('numero_arquivo','=',$publicacao_duplicada->numero_arquivo)
                                                    ->where('numero_publicacao','=',$publicacao_duplicada->numero_publicacao)
                                                    ->where('numero_unico_processo','=',$publicacao_duplicada->numero_unico_processo)
                                                    ->where('id','<>',$publicacao_duplicada->id)
                                                    ->orderby('data_disponibilizacao')
                                                    ->first();
                    $pub_profissional->publicacao_id = $primeiraPublicacao->id;
                    $pub_profissional->store();
                }
            }
            TTransaction::close();
        }catch (Exception $e){
            new TMessage('error', $e->getMessage());    
        }
    }
}
