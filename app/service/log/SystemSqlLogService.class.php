<?php

use Adianti\Log\AdiantiLoggerInterface;

/**
 * SystemSqlLogService
 *
 * @version    1.0
 * @package    service
 * @subpackage log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemSqlLogService implements AdiantiLoggerInterface
{
    /**
     * Writes an message in the global logger
     * @param  $message Message to be written
     */
    public function write($message)
    {
        try
        {
            $dbname = TTransaction::getDatabase();
            $tipo = strtoupper(substr($message, 0, 6));
            if ($dbname === 'log' || !in_array($tipo, ['INSERT', 'UPDATE', 'DELETE'], true)) { return; }
            SystemLogWriter::inserir('system_sql_log', [
                'logdate' => date('Y-m-d H:i:s'),
                'log_year' => date('Y'), 'log_month' => date('m'), 'log_day' => date('d'),
                'login' => TSession::getValue('login'), 'database_name' => $dbname,
                'sql_command' => $message, 'statement_type' => $tipo,
                'access_ip' => $_SERVER['REMOTE_ADDR'] ?? null,
                'transaction_id' => TTransaction::getUniqId(),
                'log_trace' => (new Exception)->getTraceAsString(),
                'session_id' => session_id(), 'class_name' => $_REQUEST['class'] ?? '',
                'php_sapi' => php_sapi_name(), 'request_id' => AdiantiCoreApplication::getRequestId(),
            ]);
        }
        catch (Throwable $e)
        {
            error_log('Curciol: falha ao preparar log SQL (' . get_class($e) . ').');
        }
    }
}
