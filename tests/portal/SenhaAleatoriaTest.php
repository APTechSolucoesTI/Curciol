<?php
// tests/portal/SenhaAleatoriaTest.php
// Senha automatica do portal: 12 caracteres, com minuscula, maiuscula, numero e especial, sem caracteres ambiguos
// nem os que formatam texto no WhatsApp ou quebram HTML.
require __DIR__ . '/../bootstrap.php';

$vistas = [];
$falhas = [];
for ($i = 0; $i < 2000; $i++) {
    $s = PessoaLoginService::senhaAleatoria();
    $vistas[$s] = true;
    if (strlen($s) !== 12) { $falhas[] = "tamanho: $s"; }
    if (!preg_match('/[a-z]/', $s)) { $falhas[] = "sem minuscula: $s"; }
    if (!preg_match('/[A-Z]/', $s)) { $falhas[] = "sem maiuscula: $s"; }
    if (!preg_match('/[0-9]/', $s)) { $falhas[] = "sem numero: $s"; }
    if (!preg_match('/[!@#%+=?\-]/', $s)) { $falhas[] = "sem especial: $s"; }
    if (preg_match('/[0O1lIo*_~`<>&"\'\s]/', $s)) { $falhas[] = "caractere proibido: $s"; }
}
igual('2000 senhas seguem a regra', [], array_slice($falhas, 0, 5));
ok('senhas nao se repetem', count($vistas) === 2000, (string) count($vistas));
fim();
