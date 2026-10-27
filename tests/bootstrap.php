<?php
// Testes de linha de comando. Nunca executar pela web.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

chdir(dirname(__DIR__));
require_once 'init.php';

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
 * Testes do APChat (sempre com o transporte falso): sem token no escritorio (envio desligado, ex.: homologacao), poe um
 * token falso ate o fim do teste - o servico so roda com token. Um token falso tambem seria recusado pelo APChat real.
 */
function tokenApchatDeTeste(): void
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    if (!(int) $c->query("SELECT count(*) FROM escritorio WHERE COALESCE(token_apchat, '') <> ''")->fetchColumn())
    {
        $c->exec("UPDATE escritorio SET token_apchat = 'TOKEN-FALSO-DE-TESTE' WHERE id = (SELECT min(id) FROM escritorio)");
        register_shutdown_function(function () {
            while (TTransaction::get()) { TTransaction::rollback(); }
            TTransaction::open('escritorio');
            TTransaction::get()->exec("UPDATE escritorio SET token_apchat = NULL WHERE token_apchat = 'TOKEN-FALSO-DE-TESTE'");
            TTransaction::close();
        });
    }
    TTransaction::close();
}
