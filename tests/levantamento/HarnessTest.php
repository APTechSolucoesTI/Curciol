<?php
// tests/levantamento/HarnessTest.php
require __DIR__ . '/../bootstrap.php';

ok('ok verdadeiro passa', true);
igual('igual com numeros', 2.5, 2.5);
lanca('lanca captura excecao', function () { throw new Exception('erro esperado aqui'); }, 'esperado');
ok('init carregado (TTransaction existe)', class_exists('TTransaction'));
fim();
