<?php
// tests/seguranca/TokenTest.php
// Login por token (API) so aceita token emitido para a API, de usuario existente e ativo; dados vem do banco.
require __DIR__ . '/../bootstrap.php';

use Firebase\JWT\JWT;

$ini  = AdiantiApplicationConfig::get();
$seed = APPLICATION_NAME . $ini['general']['seed'];
$aceita = function (array $token) use ($seed) {
    TSession::setValue('logged', FALSE);
    try { ApplicationAuthenticationService::fromToken(JWT::encode($token, $seed, 'HS256')); return TSession::getValue('logged') === TRUE; }
    catch (Exception $e) { return false; }
};

TTransaction::open('permission');
$admin   = SystemUsers::find(1);
$inativo = SystemUsers::where('active', '=', 'N')->first();
TTransaction::close();

$base = ['user' => $admin->login, 'userid' => $admin->id, 'username' => 'x', 'usermail' => 'x', 'expires' => strtotime('+1 hour')];
ok('token de API aceito', $aceita($base + ['purpose' => 'rest']));
igual('nome vem do banco', $admin->name, TSession::getValue('username'));
ok('token sem proposito recusado', !$aceita($base));
ok('token de redefinir senha recusado', !$aceita(['user' => $admin->login, 'purpose' => 'reset', 'expires' => strtotime('+1 hour')]));
ok('login diferente do id recusado', !$aceita(['user' => 'outro'] + $base + ['purpose' => 'rest']));
ok('token vencido recusado', !$aceita(['expires' => strtotime('-1 minute')] + $base + ['purpose' => 'rest']));
if ($inativo)
{
    ok('usuario inativo recusado', !$aceita(['user' => $inativo->login, 'userid' => $inativo->id] + $base + ['purpose' => 'rest']));
}
fim();
