<?php

/** Logs operacionais nao participam da pilha nem da transacao do usuario. */
class SystemLogWriter
{
    public static function executar(callable $gravar): void
    {
        try
        {
            $info = TConnection::getDatabaseInfo('log');
            if (!$info) { throw new Exception('Conexao de log indisponivel'); }
            // Mesmo quando log e escritorio apontam ao mesmo banco, o PDO e exclusivo.
            $info['keep'] = 0;
            $conn = TConnection::openArray($info);
            $gravar($conn);
        }
        catch (Throwable $e)
        {
            // Nao expor SQL, dados pessoais ou credenciais em tela nem no log de erro.
            error_log('Curciol: falha ao registrar log operacional (' . get_class($e) . ').');
        }
    }

    public static function inserir(string $tabela, array $dados): void
    {
        self::executar(function (PDO $conn) use ($tabela, $dados) {
            if (!in_array($tabela, ['system_sql_log', 'system_access_log'], true))
            {
                throw new InvalidArgumentException('Tabela de log invalida');
            }
            $colunas = array_keys($dados);
            foreach ($colunas as $coluna)
            {
                if (!preg_match('/^[a-z_]+$/', $coluna)) { throw new InvalidArgumentException('Coluna invalida'); }
            }
            $sql = 'INSERT INTO ' . $tabela . ' (' . implode(', ', $colunas) . ') VALUES (' . implode(', ', array_fill(0, count($dados), '?')) . ')';
            $conn->prepare($sql)->execute(array_values($dados));
        });
    }
}
