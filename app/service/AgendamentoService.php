<?php

class AgendamentoService
{
    const KEY = 'Y2hhdmUgYWdlbmRhbWVudG8gYnVpbGRlciBjbGluaWNh';
    const CIPHER = 'AES-128-CBC';
    
    public static function decode($token)
    {
        $secretHash = self::KEY;
        $password = self::KEY;
        $encryptionMethod = self::CIPHER;
        $length = openssl_cipher_iv_length($encryptionMethod);
        $iv = substr(md5($password), 0, $length);
        
        return unserialize(base64_decode(openssl_decrypt(base64_decode($token), $encryptionMethod, $secretHash, null, $iv)));
    }
    
    public static function getToken($id)
    {
        $obj = new stdClass;
        $obj->agendamento_id = $id;
        
        $json = base64_encode(serialize($obj));

        $secretHash = self::KEY;
        $password = self::KEY;
        $encryptionMethod = self::CIPHER;
        $length = openssl_cipher_iv_length($encryptionMethod);
        $iv = substr(md5($password), 0, $length);
        
        return base64_encode(openssl_encrypt($json, $encryptionMethod, $secretHash, null, $iv));
    }
}
