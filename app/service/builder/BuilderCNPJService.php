<?php

/**
 * Adaptador de CNPJ para a BrasilAPI. Mantém os métodos usados pelo código legado.
 */
class BuilderCNPJService
{
    const ENDPOINT = 'https://brasilapi.com.br/api/cnpj/v1/';
    const TIMEOUT = 10;

    /** @var callable|null Recebe a URL e retorna {status, corpo}, para testes sem HTTP real. */
    private static $transporte = null;

    public static function usarTransporte(?callable $transporte): void
    {
        self::$transporte = $transporte;
    }

    public static function getUrl($cnpj): string
    {
        if (!is_string($cnpj) || preg_match('/[^0-9.\/\s-]/', $cnpj))
        {
            throw new Exception('CNPJ inválido. Confira os números digitados.');
        }
        $cnpj = PessoaLoginService::digitos($cnpj);
        if (strlen($cnpj) !== 14 || !PessoaLoginService::documentoValido($cnpj))
        {
            throw new Exception('CNPJ inválido. Confira os 14 números digitados.');
        }
        return self::ENDPOINT . $cnpj;
    }

    public static function get($cnpj): stdClass
    {
        $url = self::getUrl($cnpj);
        $r = self::consultar($url);
        $status = (int) ($r['status'] ?? 0);
        if ($status === 400) { throw new Exception('CNPJ inválido. Confira os números digitados.'); }
        if ($status === 404) { throw new Exception('CNPJ não encontrado na BrasilAPI. Confira os números digitados.'); }
        if ($status === 429) { throw new Exception('Limite de consultas atingido. Aguarde um pouco e tente novamente.'); }
        if ($status !== 200)
        {
            throw new Exception('Consulta de CNPJ indisponível no momento. Tente novamente ou preencha o cadastro manualmente.');
        }
        $dados = json_decode((string) ($r['corpo'] ?? ''));
        if (!$dados instanceof stdClass || !is_string($dados->razao_social ?? null) || trim($dados->razao_social) === ''
            || !is_string($dados->cnpj ?? null) || $dados->cnpj !== substr($url, strlen(self::ENDPOINT)))
        {
            throw new Exception('A consulta de CNPJ retornou uma resposta inválida. Tente novamente em instantes.');
        }
        return $dados;
    }

    /** Compatibilidade com o formato completo anterior. A BrasilAPI não fornece inscrições estaduais. */
    public static function getFull($cnpj): stdClass
    {
        $dados = self::get($cnpj);
        $dados->estabelecimento = (object) [
            'email' => $dados->email ?? null,
            'data_inicio_atividade' => $dados->data_inicio_atividade ?? null,
            'inscricoes_estaduais' => [],
        ];
        return $dados;
    }

    private static function consultar(string $url): array
    {
        if (self::$transporte) { return (self::$transporte)($url); }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        $corpo = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $corpo === false ? 0 : $status, 'corpo' => $corpo === false ? '' : $corpo];
    }
}