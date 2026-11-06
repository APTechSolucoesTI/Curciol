<?php
/**
 * SystemAccessLogService
 *
 * @version    1.0
 * @package    service
 * @subpackage log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemAccessLogService
{
    /**
     * Register login
     */
    public static function registerLogin($impersonated = false, $impersonated_by = null)
    {
        SystemLogWriter::inserir('system_access_log', [
            'login' => TSession::getValue('login'), 'sessionid' => session_id(),
            'login_time' => date('Y-m-d H:i:s'),
            'login_year' => date('Y'), 'login_month' => date('m'), 'login_day' => date('d'),
            'impersonated' => $impersonated ? 'Y' : 'N', 'impersonated_by' => $impersonated_by,
            'access_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public static function registerLogout()
    {
        SystemLogWriter::executar(function (PDO $conn) {
            $conn->prepare('UPDATE system_access_log SET logout_time = ? WHERE id = (SELECT id FROM system_access_log WHERE sessionid = ? ORDER BY id DESC LIMIT 1)')
                 ->execute([date('Y-m-d H:i:s'), session_id()]);
        });
    }

    public static function getStatsByDay()
    {
        TTransaction::open('log');
        // get logs by session id
        $logs = SystemAccessLog::where('login_time', '>=', date('Y-m-01'))->where('login_time', '<=', date('Y-m-t'))->load();
        $accesses = array();
        
        if (count($logs)>0)
        {
            $accesses = array();
            foreach ($logs as $log)
            {
                $day = substr($log->login_time,8,2);
                if (isset($accesses[$day]))
                {
                    $accesses[$day] ++;
                }
                else
                {
                    $accesses[$day] = 1;
                }
            }
        }
        
        TTransaction::close();
        return $accesses;
    }
}
