<?php
use Adianti\Log\AdiantiLoggerInterface;

/**
 * Log de SQL do framework (Mad Builder) — DESLIGADO em 09/11/2026: gravava cada comando (quase 10 mil por dia) e ninguem usava.
 *
 * A classe continua existindo porque os app/config/*.php do servidor (fora do git) ainda trazem 'slog' => 'SystemSqlLogService';
 * sem ela, abrir qualquer transacao quebraria. Pode sair depois que esse 'slog' for tirado dos quatro arquivos de conexao.
 */
class SystemSqlLogService implements AdiantiLoggerInterface
{
    public function write($message)
    {
    }
}
