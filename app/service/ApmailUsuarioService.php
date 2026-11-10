<?php
/**
 * E-mail e avisos de cada usuario da Curciol no APMail (10/11/2026). Usa a transacao aberta ('escritorio').
 * Sem configuracao: o e-mail do cadastro do usuario e avisos ligados. E-mail igual ao do cadastro nao e guardado (segue o cadastro).
 */
class ApmailUsuarioService
{
    public static function doUsuario(int $systemUserId): ?ApmailUsuario
    {
        return ApmailUsuario::where('system_user_id', '=', $systemUserId)->first() ?: null;
    }

    public static function emailDoCadastro(int $systemUserId): string
    {
        $st = TTransaction::get()->prepare('SELECT email FROM system_users WHERE id = ?');
        $st->execute([$systemUserId]);
        return mb_strtolower(trim((string) $st->fetchColumn()));
    }

    /** E-mail usado no APMail: o informado na tela de usuarios das integracoes, senao o do cadastro. */
    public static function email(int $systemUserId): string
    {
        $l = self::doUsuario($systemUserId);
        $email = $l ? mb_strtolower(trim((string) $l->email)) : '';
        return $email !== '' ? $email : self::emailDoCadastro($systemUserId);
    }

    public static function avisos(int $systemUserId): bool
    {
        $l = self::doUsuario($systemUserId);
        return !$l || $l->avisos !== 'N';
    }

    public static function salvar(int $systemUserId, ?string $email, bool $avisos): void
    {
        $email = mb_strtolower(trim((string) $email));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            throw new Exception("O e-mail do APMail \"{$email}\" não é um e-mail válido.");
        }
        if ($email === self::emailDoCadastro($systemUserId))
        {
            $email = '';
        }
        $l = self::doUsuario($systemUserId) ?: new ApmailUsuario();
        $l->system_user_id = $systemUserId;
        $l->email = $email !== '' ? $email : null;
        $l->avisos = $avisos ? 'S' : 'N';
        $l->atualizado_em = date('Y-m-d H:i:s');
        $l->store();
    }
}
