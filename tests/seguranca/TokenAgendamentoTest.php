<?php
// tests/seguranca/TokenAgendamentoTest.php
// Link do agendamento: token novo assinado; token antigo so para agendamento existente que ainda nao passou.
require __DIR__ . '/../bootstrap.php';

$legado = function ($id) {
    $obj = new stdClass;
    $obj->agendamento_id = $id;
    $iv = substr(md5(AgendamentoService::KEY), 0, openssl_cipher_iv_length(AgendamentoService::CIPHER));
    return base64_encode(openssl_encrypt(base64_encode(serialize($obj)), AgendamentoService::CIPHER, AgendamentoService::KEY, null, $iv));
};

$token = AgendamentoService::getToken(1834);
igual('token novo abre o agendamento', 1834, AgendamentoService::decode($token)->agendamento_id ?? null);
ok('token novo so com caracteres de URL', (bool) preg_match('/^[A-Za-z0-9-]+$/', $token));
ok('token de outro id recusado', AgendamentoService::decode(str_replace('v2-1834-', 'v2-1835-', $token)) === null);
ok('assinatura alterada recusada', AgendamentoService::decode(substr($token, 0, -1) . (substr($token, -1) == 'a' ? 'b' : 'a')) === null);
ok('lixo recusado', AgendamentoService::decode('abc') === null);

TTransaction::open('escritorio');
// link antigo so vale para agendamento que ja existia quando a regra entrou (id ate a preferencia agendamento_token_legado_ate)
TTransaction::open('permission');
$corte = (int) (SystemPreference::find('agendamento_token_legado_ate')->preference ?? 0);
TTransaction::close();
$futuro  = Agendamento::where('dt_inicial', '>=', date('Y-m-d H:i:s'))->where('id', '<=', $corte)->orderBy('id', 'desc')->first();
$passado = Agendamento::where('dt_inicial', '<', date('Y-m-d', strtotime('-10 days')))->orderBy('id', 'desc')->first();
TTransaction::close();
if ($futuro)
{
    igual('link antigo de agendamento futuro continua abrindo', (int) $futuro->id, (int) (AgendamentoService::decode($legado($futuro->id))->agendamento_id ?? 0));
}
if ($passado)
{
    ok('link antigo de agendamento ja passado recusado', AgendamentoService::decode($legado($passado->id)) === null);
}
ok('link antigo de agendamento inexistente recusado', AgendamentoService::decode($legado(999999999)) === null);
fim();
