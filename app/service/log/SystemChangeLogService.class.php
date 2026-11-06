<?php
/**
 * SystemChangeLogService
 *
 * @version    1.0
 * @package    service
 * @subpackage log
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class SystemChangeLogService
{
    /**
     * Register a change log
     */
    public static function register($activeRecord, $lastState, $currentState)
    {
        try
        {
            $lastState = (array) $lastState;
            $currentState = (array) $currentState;
            $ignorar = [$activeRecord->getCreatedAtColumn(), $activeRecord->getUpdatedAtColumn(), $activeRecord->getDeletedAtColumn()];
            $alteracoes = [];
            foreach ($lastState as $coluna => $valor)
            {
                if (!array_key_exists($coluna, $currentState) && !in_array($coluna, $ignorar) && (string) $valor !== '')
                {
                    $alteracoes[] = ['deleted', $coluna, (string) $valor, ''];
                }
            }
            foreach ($currentState as $coluna => $valor)
            {
                if (in_array($coluna, $ignorar)) { continue; }
                $texto = is_scalar($valor) || is_null($valor) ? $valor : serialize($valor);
                if (array_key_exists($coluna, $lastState))
                {
                    if ($valor != $lastState[$coluna]) { $alteracoes[] = ['changed', $coluna, (string) $lastState[$coluna], $texto]; }
                }
                elseif (!empty($valor)) { $alteracoes[] = ['created', $coluna, '', $texto]; }
            }
            if (!$alteracoes) { return; }
            $pk = $activeRecord->getPrimaryKey();
            $dados = [$activeRecord->getEntity(), date('Y-m-d H:i:s'), date('Y'), date('m'), date('d'),
                TSession::getValue('login'), $pk, $activeRecord->$pk,
                $_SERVER['REMOTE_ADDR'] ?? null, TTransaction::getUniqId(), (new Exception)->getTraceAsString(),
                session_id(), $_REQUEST['class'] ?? '', php_sapi_name()];
            SystemLogWriter::executar(function (PDO $conn) use ($dados, $alteracoes) {
                $conn->beginTransaction();
                try
                {
                    $st = $conn->prepare('INSERT INTO system_change_log (tablename, logdate, log_year, log_month, log_day, login, primarykey, pkvalue, access_ip, transaction_id, log_trace, session_id, class_name, php_sapi, operation, columnname, oldvalue, newvalue) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    foreach ($alteracoes as $alteracao) { $st->execute(array_merge($dados, $alteracao)); }
                    $conn->commit();
                }
                finally { if ($conn->inTransaction()) { $conn->rollBack(); } }
            });
        }
        catch (Throwable $e)
        {
            error_log('Curciol: falha ao preparar auditoria (' . get_class($e) . ').');
        }
    }
}
