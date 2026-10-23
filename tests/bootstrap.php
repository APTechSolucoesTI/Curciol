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
