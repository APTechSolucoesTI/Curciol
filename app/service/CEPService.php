<?php

/**
 * Consulta de CEP (05/11/2026: ViaCEP; a API antiga passou a responder que a licenca nao permite o uso). O que ja foi
 * consultado fica em cep_cache.
 */
class CEPService
{
    const ENDPOINT = 'https://viacep.com.br/ws/%s/json/';
    const TIMEOUT = 10;

    /** @var callable|null substitui a chamada HTTP (testes): recebe o CEP e devolve o corpo da resposta ou null */
    private static $transporte = null;

    public static function usarTransporte(?callable $transporte): void
    {
        self::$transporte = $transporte;
    }
    
    public static function get($cep)
    {
        $cep = preg_replace('/\D/', '', (string) $cep);
        
        if (strlen($cep) !== 8)
        {
            throw new Exception('CEP inválido: informe os 8 números.');
        }
        
        $dadosCep = CEPCacheService::get($cep);
        
        if($dadosCep)
        {
            return $dadosCep;
        }
        
        $resposta = json_decode((string) self::consultar($cep));

        if (!is_object($resposta))
        {
            throw new Exception('Não foi possível consultar o CEP agora. Tente de novo ou preencha o endereço à mão.');
        }
        
        if (!empty($resposta->erro))
        {
            throw new Exception('CEP não encontrado.');
        }
        
        $dadosCep = new stdClass;
        $dadosCep->cep = $cep;
        $dadosCep->rua = trim((string) ($resposta->logradouro ?? ''));
        $dadosCep->bairro = trim((string) ($resposta->bairro ?? ''));
        $dadosCep->cidade = (string) ($resposta->localidade ?? '');
        $dadosCep->uf = (string) ($resposta->uf ?? '');
        $dadosCep->cidade_cod_ibge = (string) ($resposta->ibge ?? '');
        
        $cidade = Cidade::fromIBGE($dadosCep->cidade_cod_ibge);
        
        if (!$cidade)
        {
            $estado = Estado::fromIBGE(substr($dadosCep->cidade_cod_ibge, 0, 2));

            if (! $estado)
            {
                $estado = new Estado;
                $estado->sigla = $dadosCep->uf;
                $estado->nome = (string) ($resposta->estado ?? $dadosCep->uf);
                $estado->codigo_ibge = substr($dadosCep->cidade_cod_ibge, 0, 2);
                $estado->criacao_user_id = TSession::getValue('userid');
                $estado->store();
            }
            
            $cidade = new Cidade;
            $cidade->nome = $dadosCep->cidade;
            $cidade->codigo_ibge = $dadosCep->cidade_cod_ibge;
            $cidade->estado_id = $estado->id;
            $cidade->criacao_user_id = TSession::getValue('userid');
            $cidade->store();
            
            $dadosCep->estado_id = $cidade->estado_id;
        }
        
        $dadosCep->cidade_id = $cidade->id;

        CEPCacheService::save($dadosCep);
        
        return $dadosCep;
    }

    /** Corpo da resposta da consulta; null se o servico nao respondeu. */
    private static function consultar(string $cep): ?string
    {
        if (self::$transporte)
        {
            return (self::$transporte)($cep);
        }

        $contexto = stream_context_create(['http' => ['timeout' => self::TIMEOUT, 'ignore_errors' => true]]);
        $corpo = @file_get_contents(sprintf(self::ENDPOINT, $cep), false, $contexto);

        return $corpo === false ? null : $corpo;
    }
}