<?php
/**
 * Curciol (seguranca 22/10/2026): contas do grupo Admin so podem ser alteradas por quem tambem e Admin
 * (personificar, editar, excluir, ativar/desativar, clonar ou dar o grupo Admin a alguem). Quem tem acesso as
 * telas de usuarios sem ser Admin continua gerenciando os demais usuarios normalmente.
 */
class ContaAdmin
{
    const GRUPO_ADMIN = 1;
    const MENSAGEM = 'Somente um administrador pode alterar uma conta de administrador';

    public static function sessaoEhAdmin(): bool
    {
        return in_array(self::GRUPO_ADMIN, array_map('intval', (array) TSession::getValue('usergroupids')), TRUE);
    }

    public static function usuarioEhAdmin($user_id): bool
    {
        TTransaction::open('permission');
        $admin = SystemUserGroup::where('system_user_id', '=', (int) $user_id)
                                ->where('system_group_id', '=', self::GRUPO_ADMIN)
                                ->count() > 0;
        TTransaction::close();
        return $admin;
    }

    /** Lanca excecao se quem esta logado nao e Admin e o usuario alvo (id) e. */
    public static function exigirPermissao($user_id)
    {
        if (!self::sessaoEhAdmin() AND !empty($user_id) AND self::usuarioEhAdmin($user_id))
        {
            throw new Exception(self::MENSAGEM);
        }
    }

    public static function exigirPermissaoLogin($login)
    {
        TTransaction::open('permission');
        $user = SystemUsers::newFromLogin((string) $login);
        TTransaction::close();
        if ($user instanceof SystemUsers)
        {
            self::exigirPermissao($user->id);
        }
    }

    /** So Admin coloca alguem no grupo Admin. */
    public static function exigirPermissaoGrupos($group_ids)
    {
        if (!self::sessaoEhAdmin() AND in_array(self::GRUPO_ADMIN, array_map('intval', (array) $group_ids), TRUE))
        {
            throw new Exception(self::MENSAGEM);
        }
    }
}
