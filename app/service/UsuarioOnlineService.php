<?php
/**
 * Quem esta conectado, para o monitor de usuarios (09/11/2026). Vem do log de acesso: conectado = o ultimo login nas ultimas
 * 12 horas, sem saida registrada. (O monitor do Mad Builder usava o Firebase, que nunca foi configurado.)
 */
class UsuarioOnlineService
{
    const JANELA_HORAS = 12;

    /** [login => ['online' => bool, 'ultimo' => 'Y-m-d H:i:s']] a partir do log de acesso. */
    public static function situacao(): array
    {
        TTransaction::open('log');
        try
        {
            $linhas = TTransaction::get()->query("SELECT DISTINCT ON (login) login, login_time, logout_time FROM system_access_log
                                                  WHERE login_time >= now() - interval '30 days'
                                                  ORDER BY login, login_time DESC")->fetchAll(PDO::FETCH_ASSOC);
        }
        finally
        {
            TTransaction::close();
        }

        return self::calcular($linhas, time());
    }

    /** Regra pura: so o login mais recente de cada usuario conta. */
    public static function calcular(array $linhas, int $agora): array
    {
        $sit = [];
        foreach ($linhas as $l)
        {
            $login = (string) $l['login'];
            if (isset($sit[$login]) && $sit[$login]['ultimo'] >= (string) $l['login_time'])
            {
                continue;
            }
            $entrou = strtotime((string) $l['login_time']);
            $sit[$login] = [
                'online' => trim((string) ($l['logout_time'] ?? '')) === '' && $entrou !== false && $entrou >= $agora - self::JANELA_HORAS * 3600,
                'ultimo' => (string) $l['login_time'],
            ];
        }
        return $sit;
    }
}
