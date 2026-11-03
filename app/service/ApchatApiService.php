<?php
/**
 * API externa do APChat: endereco e token do escritorio, envio de mensagem de WhatsApp e teste de conexao.
 *
 * O endereco (url_apchat) e o token (token_apchat) ficam no cadastro do escritorio (Configuracoes) e podem ser trocados
 * pela tela para testar outro canal; a sincronizacao de contatos (APChatContactService) usa os mesmos.
 *
 * Envio (03/11/2026): POST no proprio endereco, JSON {number, body, externalKey, isClosed}, "Authorization: Bearer".
 * A integracao deve ser do canal "Curciol - Coexistencia" (113): o oficial (112, WABA) recusa texto livre fora da
 * janela de 24h (erro 131047) e o modo hibrido do APChat (oficial ligado ao 113) so desvia os envios feitos pela tela.
 * Teste de conexao: consulta de um numero que nao existe (showcontact) - nao envia nada nem altera o APChat:
 *   404 ERR_CONTACT_NOT_FOUND ou 200 = endereco e token certos; 403 = token recusado; 404 sem JSON = endereco errado.
 */
class ApchatApiService
{
    const URL_PADRAO = 'https://api.apchat.com.br/v2/api/external/baca905c-dce1-4d3f-b1bb-f2ffc981fdb4';

    const TIMEOUT_CONEXAO = 5;
    const TIMEOUT_TOTAL   = 20;

    // numero que nao existe: o teste de conexao so consulta
    const NUMERO_TESTE_CONEXAO = '5500000000000';

    /**
     * Substitui a chamada HTTP (testes). Recebe (url, corpo, token) e devolve ['status' => int, 'corpo' => string,
     * 'erro' => string].
     *
     * @var callable|null
     */
    private static $transporte = null;

    public static function usarTransporte(?callable $transporte): void
    {
        self::$transporte = $transporte;
    }

    /**
     * Escritorio da unidade logada; sem token nele, o primeiro escritorio com token (mesma regra dos contatos).
     */
    public static function escritorio(): ?Escritorio
    {
        $unidade = (int) (TSession::getValue('userunitid') ?? 0);

        $escritorio = $unidade > 0 ? Escritorio::where('system_unit_id', '=', $unidade)->first() : null;

        if (!$escritorio || trim((string) $escritorio->token_apchat) === '')
        {
            $escritorio = Escritorio::where('token_apchat', 'is not', null)
                                    ->where('token_apchat', '<>', '')
                                    ->orderBy('id')
                                    ->first();
        }

        return $escritorio ?: null;
    }

    /** Endereco da API sem a barra final; vazio = o padrao. */
    public static function url(?Escritorio $escritorio): string
    {
        $url = rtrim(trim((string) ($escritorio->url_apchat ?? '')), '/');

        return $url !== '' ? $url : self::URL_PADRAO;
    }

    /**
     * Envia uma mensagem de WhatsApp pelo APChat com o endereco e o token do escritorio.
     *
     * @return string resposta do APChat (fica registrada na mensagem)
     */
    public static function enviarMensagem(string $numero, string $texto, string $chave): string
    {
        $escritorio = self::escritorio();
        $token = $escritorio ? trim((string) $escritorio->token_apchat) : '';

        if ($token === '')
        {
            throw new Exception('APChat não configurado: informe a URL e o token em Configurações do escritório.');
        }

        return self::enviar(self::url($escritorio), $token, $numero, $texto, $chave);
    }

    /**
     * Envio com endereco e token informados (o "Enviar teste" usa os da tela, antes de salvar).
     */
    public static function enviar(string $url, string $token, string $numero, string $texto, string $chave): string
    {
        // isClosed: o atendimento que o envio abre no canal da integracao ja nasce fechado. Com a integracao no canal de
        // coexistencia (nao oficial), o oficial recebe a copia da mensagem e a conversa (e a resposta) segue no
        // atendimento do oficial; sem isso ficava um atendimento a toa aberto no nao oficial (teste de 03/11/2026).
        $resposta = self::chamar(rtrim(trim($url), '/'), [
            'number'      => $numero,
            'body'        => $texto,
            'externalKey' => $chave,
            'isClosed'    => true,
        ], trim($token));

        if ($resposta['status'] < 200 || $resposta['status'] >= 300)
        {
            throw new Exception('O APChat não enviou a mensagem: ' . self::motivo($resposta));
        }

        return $resposta['corpo'];
    }

    /**
     * Confere endereco e token sem enviar nada. Lanca excecao com o motivo quando nao funciona.
     */
    public static function testarConexao(string $url, string $token): void
    {
        $url = rtrim(trim($url), '/');
        $token = trim($token);

        if ($url === '' || $token === '')
        {
            throw new Exception('Informe a URL e o token da API do APChat.');
        }

        $resposta = self::chamar($url . '/showcontact', ['number' => self::NUMERO_TESTE_CONEXAO], $token);
        $json = json_decode($resposta['corpo'], true);

        if ($resposta['status'] === 200 || ($resposta['status'] === 404 && is_array($json)
            && ($json['error'] ?? '') === APChatContactService::ERRO_NAO_ENCONTRADO))
        {
            return;
        }

        throw new Exception('Sem conexão com o APChat: ' . self::motivo($resposta));
    }

    /** Motivo legivel de uma resposta de erro. */
    public static function motivo(array $resposta): string
    {
        $status = (int) $resposta['status'];
        $json = json_decode((string) $resposta['corpo'], true);
        $erro = is_array($json) ? (string) ($json['error'] ?? $json['message'] ?? '') : '';

        if ($status === 0)
        {
            return 'o APChat não respondeu' . ($resposta['erro'] !== '' ? " ({$resposta['erro']})" : '') . '.';
        }
        if ($status === 401 || $status === 403)
        {
            return 'token recusado (confira o token em Configurações do escritório).';
        }
        if ($status === 404 && !is_array($json))
        {
            return 'endereço da API não encontrado (confira a URL em Configurações do escritório).';
        }

        return "HTTP {$status}" . ($erro !== '' ? " - {$erro}" : '') . '.';
    }

    private static function chamar(string $url, array $corpo, string $token): array
    {
        if (self::$transporte)
        {
            return call_user_func(self::$transporte, $url, $corpo, $token);
        }

        $ch = curl_init($url);

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

        $texto = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = (string) curl_error($ch);
        curl_close($ch);

        return [
            'status' => $texto === false ? 0 : $status,
            'corpo'  => $texto === false ? '' : (string) $texto,
            'erro'   => $erro,
        ];
    }
}
