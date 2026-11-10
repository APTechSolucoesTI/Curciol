<?php
// tests/apchat/apchat-teste.php
// APChat de TESTE (apchat_homologacao, no PostgreSQL do 192.168.3.200): mesma estrutura das tabelas do APChat oficial, sem dados
// reais, com o schema curciol instalado (app/database/apchat/). Os testes so conectam aqui: qualquer outro banco e recusado.
// Os testes gravam dentro de transacao e desfazem no fim; o papel da Curciol (curciol_apchat_teste) e simulado com SET ROLE.
require_once __DIR__ . '/../bootstrap.php';

final class ApchatTeste
{
    const BANCO = 'apchat_homologacao';
    const PAPEL = 'curciol_apchat_teste';

    /** Conexao de dono (credenciais do banco da Curciol, trocando so o banco). */
    public static function pdoAdmin(string $banco = self::BANCO): PDO
    {
        if ($banco !== self::BANCO)
        {
            throw new Exception("Teste do APChat so conecta no banco " . self::BANCO . " (pedido: {$banco}).");
        }
        $i = TConnection::getDatabaseInfo('escritorio');
        $pdo = new PDO(sprintf('pgsql:host=%s;port=%s;dbname=%s;connect_timeout=5', $i['host'], $i['port'] ?? '5432', $banco), $i['user'], $i['pass'],
                       [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        if ($pdo->query('SELECT current_database()')->fetchColumn() !== self::BANCO)
        {
            throw new Exception('Conexao fora do APChat de teste.');
        }
        $pdo->exec("SET TIME ZONE 'America/Sao_Paulo'");
        return $pdo;
    }

    /** A configuracao da homologacao (app/config/apchat.php) aponta para o APChat de teste? */
    public static function configNoTeste(): bool
    {
        if (!is_file(ApchatBancoService::CONFIG)) { return false; }
        $c = include ApchatBancoService::CONFIG;
        return ($c['name'] ?? '') === self::BANCO;
    }

    /** Liga o ApchatBancoService (os testes o desligam no bootstrap) so se ele apontar para o APChat de teste. */
    public static function ligarServico(): void
    {
        if (!self::configNoTeste())
        {
            throw new Exception('app/config/apchat.php nao aponta para o APChat de teste: servico continua desligado.');
        }
        ApchatBancoService::usarFalso(null);
        ApchatBancoService::desligar(false);
    }
}
