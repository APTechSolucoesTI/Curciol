<?php
// tests/levantamento/NavegadorFinal.php
// Apoio ao teste de navegador da tela Gerar: "criar" monta a fixture SEM gerar o financeiro e imprime os ids; "limpar" apaga tudo.
// Uso: php tests/levantamento/NavegadorFinal.php criar|limpar
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
$arq = sys_get_temp_dir() . '/fixture_final_levantamento.json';
$modo = $argv[1] ?? '';
if ($modo === 'criar') {
    $f = Fixtures::criar();
    file_put_contents($arq, json_encode($f));
    echo json_encode($f) . "\n";
} elseif ($modo === 'limpar') {
    Fixtures::limpar(json_decode(file_get_contents($arq), true));
    @unlink($arq);
    echo 'sobras: ' . json_encode(Fixtures::sobras()) . "\nTUDO OK\n";
}
