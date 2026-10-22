<?php
/**
 * Curciol (seguranca 22/10/2026): limite de senhas erradas no login do portal do cliente, por CPF/CNPJ (todos os
 * acessos de dentro do escritorio chegam pelo mesmo IP, entao o limite nao pode ser por IP). Depois de MAX_FALHAS
 * erros em JANELA segundos, aquele usuario espera JANELA segundos. Guardado em tmp/ (nao servido pela web).
 */
class PortalLoginLimite
{
    const MAX_FALHAS = 10;
    const JANELA     = 900;
    const MENSAGEM   = 'Muitas tentativas de acesso com senha incorreta. Aguarde 15 minutos e tente novamente.';

    private static function arquivo($usuario)
    {
        $dir = 'tmp/portal_login';
        if (!is_dir($dir))
        {
            @mkdir($dir, 0775, true);
        }
        return $dir . '/' . sha1('portal' . (string) $usuario) . '.json';
    }

    private static function falhasRecentes($usuario)
    {
        $f = self::arquivo($usuario);
        $falhas = is_file($f) ? (array) json_decode((string) file_get_contents($f), true) : [];
        $limite = time() - self::JANELA;
        return array_values(array_filter($falhas, function ($t) use ($limite) { return (int) $t > $limite; }));
    }

    /** Lanca excecao se o usuario esta bloqueado. */
    public static function exigirLiberado($usuario)
    {
        if ((string) $usuario !== '' AND count(self::falhasRecentes($usuario)) >= self::MAX_FALHAS)
        {
            throw new Exception(self::MENSAGEM);
        }
    }

    public static function registrarFalha($usuario)
    {
        if ((string) $usuario === '')
        {
            return;
        }
        $falhas = self::falhasRecentes($usuario);
        $falhas[] = time();
        @file_put_contents(self::arquivo($usuario), json_encode($falhas), LOCK_EX);
    }

    public static function limpar($usuario)
    {
        @unlink(self::arquivo($usuario));
    }
}
