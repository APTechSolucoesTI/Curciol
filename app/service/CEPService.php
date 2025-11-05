<?php

class CEPService
{
    const ENDPOINT = 'https://services.madbuilder.com.br/cep/api/v1/';
    
    public static function get($cep)
    {
        $ini = parse_ini_file('app/config/application.ini');
        
        $token = '/' . $ini['token'];
        
        $cep = str_replace(['-','.'], ['', ''], $cep);
        
        $dadosCep = CEPCacheService::get($cep);
        
        if($dadosCep)
        {
            return $dadosCep;
        }
        
        $result = @file_get_contents(self::ENDPOINT . $cep . $token);
        $dadosCep = json_decode($result);
        
        if(json_last_error())
        {
            throw new Exception('Houver um erro ao buscar o código');
        }
        
        $dadosCep->rua = $dadosCep->tipo_logradouro . ' ' .$dadosCep->logradouro;
        $dadosCep->cep = $cep;
        
        $dadosCep->cidade = $dadosCep->cidade;
        
        $cidade = Cidade::fromIBGE($dadosCep->cidade_cod_ibge);
        $estado = Estado::fromIBGE($dadosCep->estado_cod_ibge);
        
        if ($cidade)
        {
            $dadosCep->cidade_id = $cidade->id;
        }
        else
        {
            if (! $estado)
            {
                $estado = new Estado;
                $estado->sigla = $dadosCep->uf;
                $estado->nome = $dadosCep->estado;
                $estado->codigo_ibge = $dadosCep->estado_cod_ibge;
                $estado->criacao_user_id = TSession::getValue('userid');
                $estado->store();
            }
            
            $cidade = new Cidade;
            $cidade->nome = $dadosCep->cidade;
            $cidade->codigo_ibge = $dadosCep->cidade_cod_ibge;
            $cidade->estado_id = $estado->id;
            $cidade->criacao_user_id = TSession::getValue('userid');
            $cidade->store();
            
            $dadosCep->cidade_id = $cidade->id;
            $dadosCep->estado_id = $cidade->estado_id;
        }
        
        CEPCacheService::save($dadosCep);
        
        return $dadosCep;
    }
}