<?php
// Libera o repasse das parcelas anteriores a publicacao (RepasseParcelasAnterioresService). So pela linha de comando.
//   php app/database/migrations/2026-11-06-repasse-parcelas-anteriores.php            simula e desfaz tudo
//   php app/database/migrations/2026-11-06-repasse-parcelas-anteriores.php --aplicar  grava
// Depende de 2026-11-06-lancamento-recebido-sem-repasse.sql. Rodar de novo nao repete: as parcelas tratadas perdem a marca.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__, 3) . '/tests/bootstrap.php'; // API do APChat bloqueada: este script nao envia nada

TSession::setValue('userid', 1);
$r = RepasseParcelasAnterioresService::executar(in_array('--aplicar', $argv, true));
echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n";
echo $r['aplicado'] ? "GRAVADO\n" : "SIMULACAO: nada foi gravado\n";
