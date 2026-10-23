<?php

class AgendamentoService
{
    const KEY = 'Y2hhdmUgYWdlbmRhbWVudG8gYnVpbGRlciBjbGluaWNh';
    const CIPHER = 'AES-128-CBC';
    const PREFIXO = 'v2-';

    /**
     * Curciol (seguranca 22/10/2026): o token antigo era cifrado com chave fixa no codigo (forjavel) e passava por
     * unserialize. O novo e "v2-<id>-<assinatura>", assinado com o seed do sistema. Links antigos ja enviados aos
     * clientes continuam abrindo enquanto o agendamento nao passou e se ele ja existia quando o formato mudou.
     */
    public static function decode($token)
    {
        $token = (string) $token;
        if (strpos($token, self::PREFIXO) === 0)
        {
            $partes = explode('-', substr($token, strlen(self::PREFIXO)));
            if (count($partes) == 2 AND ctype_digit($partes[0]) AND hash_equals(self::assinatura($partes[0]), $partes[1]))
            {
                $obj = new stdClass;
                $obj->agendamento_id = (int) $partes[0];
                return $obj;
            }
            return null;
        }

        $secretHash = self::KEY;
        $password = self::KEY;
        $encryptionMethod = self::CIPHER;
        $length = openssl_cipher_iv_length($encryptionMethod);
        $iv = substr(md5($password), 0, $length);

        $obj = @unserialize(base64_decode((string) openssl_decrypt(base64_decode($token), $encryptionMethod, $secretHash, null, $iv)), ['allowed_classes' => ['stdClass']]);
        if (!$obj instanceof stdClass OR empty($obj->agendamento_id) OR !self::legadoValido((int) $obj->agendamento_id))
        {
            return null;
        }
        return $obj;
    }

    public static function getToken($id)
    {
        return self::PREFIXO . (int) $id . '-' . self::assinatura((int) $id);
    }

    private static function assinatura($id)
    {
        $ini = AdiantiApplicationConfig::get();
        $chave = APPLICATION_NAME . ($ini['general']['seed'] ?? '') . 'agendamento';
        return substr(hash_hmac('sha256', 'agendamento:' . (int) $id, $chave), 0, 32);
    }

    /** Token antigo: so de agendamento que ja existia na troca do formato e que ainda nao passou. */
    private static function legadoValido($id)
    {
        TTransaction::open('permission');
        $preferencia = SystemPreference::find('agendamento_token_legado_ate');
        TTransaction::close();

        TTransaction::open('escritorio');
        if (!$preferencia)
        {
            $preferencia = new stdClass;
            $preferencia->preference = (int) Agendamento::where('id', '>', 0)->maxBy('id');
            TTransaction::open('permission');
            $nova = new SystemPreference;
            $nova->id = 'agendamento_token_legado_ate';
            $nova->preference = (string) $preferencia->preference;
            $nova->store();
            TTransaction::close();
        }
        $agendamento = Agendamento::find($id);
        TTransaction::close();

        return $agendamento
            AND $id <= (int) $preferencia->preference
            AND strtotime((string) $agendamento->dt_inicial) >= strtotime('-1 day');
    }
}
