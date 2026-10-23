<?php
// tests/seguranca/PortalLoginLimiteTest.php
// Login do portal: depois de 10 senhas erradas em 15 minutos o CPF fica bloqueado; acerto limpa a contagem.
require __DIR__ . '/../bootstrap.php';

$bloqueado = function ($u) { try { PortalLoginLimite::exigirLiberado($u); return false; } catch (Exception $e) { return true; } };
$cpf = 'teste-limite-' . uniqid();

PortalLoginLimite::limpar($cpf);
for ($i = 0; $i < 9; $i++) { PortalLoginLimite::registrarFalha($cpf); }
ok('9 erros ainda liberado', !$bloqueado($cpf));
PortalLoginLimite::registrarFalha($cpf);
ok('10 erros bloqueia', $bloqueado($cpf));
ok('outro CPF nao e afetado', !$bloqueado($cpf . 'x'));
PortalLoginLimite::limpar($cpf);
ok('limpar libera', !$bloqueado($cpf));
ok('usuario vazio nunca bloqueia', !$bloqueado(''));
fim();
