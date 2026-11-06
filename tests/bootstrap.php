<?php
// Testes de linha de comando. Nunca executar pela web.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

chdir(dirname(__DIR__));
require_once 'init.php';

// testes nunca leem nem gravam no banco real do APChat (03/11/2026); quem testa o banco usa ApchatBancoService::usarFalso
ApchatBancoService::desligar();

// Cada teste que usa HTTP deve instalar seu falso explicitamente.
// Evita que salvar um cadastro de teste sincronize contatos na integracao real.
APChatContactService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso de contatos.'); });
ApchatApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso de mensagens.'); });

$GLOBALS['__falhas'] = 0;

function ok(string $nome, bool $cond, string $extra = ''): void
{
    echo ($cond ? '  ok    ' : '  FALHA ') . $nome . ($cond || $extra === '' ? '' : "  -> {$extra}") . "\n";
    if (!$cond) { $GLOBALS['__falhas']++; }
}

function igual(string $nome, $esperado, $obtido): void
{
    $iguais = (is_float($esperado) || is_float($obtido))
        ? abs((float) $esperado - (float) $obtido) < 0.000001
        : $esperado == $obtido;
    ok($nome, $iguais, 'esperado ' . json_encode($esperado) . ', obtido ' . json_encode($obtido));
}

function lanca(string $nome, callable $fn, string $trechoMensagem): void
{
    try { $fn(); ok($nome, false, 'nao lancou excecao'); }
    catch (Throwable $e) { ok($nome, mb_stripos($e->getMessage(), $trechoMensagem) !== false, $e->getMessage()); }
}

function fim(): void
{
    $f = $GLOBALS['__falhas'];
    echo $f ? "\nFALHAS: {$f}\n" : "\nTUDO OK\n";
    exit($f ? 1 : 0);
}

/**
 * Testes do APChat (sempre com o transporte falso): sem URL ou token no escritorio (integracao desligada), poe valores
 * falsos ate o fim do teste - o servico so roda com os dois. Um token falso tambem seria recusado pelo APChat real.
 */
function tokenApchatDeTeste(): void
{
    static $isolado = false;
    if ($isolado) { return; }
    $original = TConnection::getDatabaseInfo('escritorio');
    if ($original['name'] !== 'curciol_homologacao') { throw new LogicException('Configuracao falsa exige banco de homologacao.'); }
    $conn = TConnection::openArray(array_merge($original, ['keep' => 0]));
    $schema = 'teste_apchat_' . getmypid();
    $conn->exec("CREATE SCHEMA {$schema}");
    register_shutdown_function(function () use ($conn, $schema, $original) {
        while (TTransaction::get()) { TTransaction::rollback(); }
        TConnection::setDatabaseInfo('escritorio', $original);
        $conn->exec("DROP SCHEMA {$schema} CASCADE");
    });
    $conn->exec("CREATE TABLE {$schema}.escritorio (LIKE public.escritorio INCLUDING ALL)");
    $colunas = $conn->query("SELECT column_name FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'escritorio' AND column_name NOT IN ('url_apchat', 'token_apchat') ORDER BY ordinal_position")->fetchAll(PDO::FETCH_COLUMN);
    $lista = implode(', ', array_map(fn($coluna) => '"' . str_replace('"', '""', $coluna) . '"', $colunas));
    $conn->exec("INSERT INTO {$schema}.escritorio ({$lista}) SELECT {$lista} FROM public.escritorio");
    $conn->exec("UPDATE {$schema}.escritorio SET token_apchat = 'TOKEN-FALSO-DE-TESTE', url_apchat = 'http://apchat.falso.invalid'");
    TConnection::setDatabaseInfo('escritorio', array_merge($original, ['keep' => 0, 'opts' => "options='-c search_path={$schema},public'"]));
    $isolado = true;
}
