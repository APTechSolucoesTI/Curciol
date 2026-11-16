<?php
// tests/apchat/ApchatUsuarioServidorTest.php
// Vinculo usuario <-> atendente por servidor do APChat (16/11/2026, migracao 2026-11-16-apchat-usuario-servidor.sql): o numero do
// atendente so vale no servidor (host da URL da API do Escritorio) em que foi ligado; no outro servidor o usuario fica sem atendente
// (nunca ve os tickets de outra pessoa com o mesmo numero); o mesmo numero pode ser ligado em servidores diferentes; "Ligar
// automaticamente" so respeita o que foi decidido no servidor em uso. Banco do APChat falso; grava de verdade e devolve no fim.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function qu(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function xu(string $sql, array $p = []): void { TTransaction::open('escritorio'); TTransaction::get()->prepare($sql)->execute($p); TTransaction::close(); }

$escritorios = qu('SELECT id, system_unit_id, url_apchat, token_apchat FROM escritorio ORDER BY id');
$usuarios = qu("SELECT id, name, email FROM system_users WHERE active = 'Y' ORDER BY id LIMIT 3");
[$u1, $u2, $u3] = array_map('intval', array_column($usuarios, 'id'));
$antes = qu('SELECT * FROM apchat_usuario ORDER BY id');
TSession::setValue('userunitid', (int) $escritorios[0]['system_unit_id']);
$servidor = function (string $host) { xu('UPDATE escritorio SET url_apchat = ?, token_apchat = ?', ["https://{$host}/v2/api/external/00000000-0000-4000-8000-00000000c0de", 'tk']); };
$atendente = function (int $u) { TTransaction::open('escritorio'); $l = ApchatUsuarioService::doUsuario($u); TTransaction::close(); return $l ? $l->apchat_usuario_id : null; };
$ligar = function (int $u, ?int $a) { TTransaction::open('escritorio'); ApchatUsuarioService::ligar($u, $a); TTransaction::close(); };
$falso = new class {
    public $lista = [];
    public function atendentes(): array { return $this->lista; }
};

try
{
    ApchatBancoService::usarFalso($falso);
    xu('DELETE FROM apchat_usuario');
    xu("UPDATE system_users SET email = 'servidor.um@teste.local' WHERE id = ?", [$u1]);
    xu("UPDATE system_users SET email = 'servidor.dois@teste.local' WHERE id = ?", [$u2]);

    // ---------- ligado no app1 ----------
    $servidor('api1.apchat.com.br');
    $ligar($u1, 5);
    igual('app1: ligado ao atendente 5', 5, (int) $atendente($u1));
    igual('app1: o vinculo guarda o servidor', 'api1.apchat.com.br', qu("SELECT apchat_servidor FROM apchat_usuario WHERE system_user_id = {$u1}")[0]['apchat_servidor'] ?? null);

    // ---------- trocou para o oficial: o 5 do app1 nao vale (no oficial o 5 e outra pessoa) ----------
    $servidor('api.apchat.com.br');
    igual('oficial: o usuario ligado no app1 fica sem atendente', null, $atendente($u1));
    igual('oficial: o vinculo do app1 continua gravado (nada se perde)', 5, (int) (qu("SELECT apchat_usuario_id FROM apchat_usuario WHERE system_user_id = {$u1}")[0]['apchat_usuario_id'] ?? 0));
    $ligar($u2, 5);
    igual('oficial: o mesmo numero 5 pode ser ligado a outro usuario (outro servidor)', 5, (int) $atendente($u2));
    $erro = '';
    try { $ligar($u3, 5); } catch (Throwable $e) { $erro = $e->getMessage(); if (TTransaction::get()) { TTransaction::rollback(); } }
    ok('oficial: no mesmo servidor o atendente continua de um usuario so', strpos($erro, 'já está ligado') !== false, $erro);

    // ---------- de volta ao app1: cada um ve o seu ----------
    $servidor('api1.apchat.com.br');
    igual('app1 de novo: o usuario 1 volta a ter o 5 do app1', 5, (int) $atendente($u1));
    igual('app1 de novo: o usuario 2 (ligado no oficial) fica sem atendente', null, $atendente($u2));

    // ---------- Ligar automaticamente: so o que foi decidido no servidor em uso conta ----------
    $servidor('api.apchat.com.br');
    $falso->lista = [['id' => 31, 'name' => 'Pessoa Um', 'email' => 'servidor.um@teste.local', 'inactive' => false],
                     ['id' => 5, 'name' => 'Pessoa Dois', 'email' => 'servidor.dois@teste.local', 'inactive' => false]];
    TTransaction::open('escritorio');
    ApchatUsuarioService::ligarAutomatico();
    TTransaction::close();
    igual('automatico no oficial: o usuario ligado so no app1 e ligado no oficial pelo e-mail', 31, (int) $atendente($u1));
    igual('automatico no oficial: o ligado no oficial nao muda', 5, (int) $atendente($u2));
    $servidor('api1.apchat.com.br');
    igual('automatico: o usuario 1 agora e do oficial; no app1 fica sem atendente ate ligar de novo', null, $atendente($u1));

    // ---------- desligado a mao no servidor em uso: o automatico respeita ----------
    $servidor('api.apchat.com.br');
    $ligar($u2, null);
    TTransaction::open('escritorio');
    ApchatUsuarioService::ligarAutomatico();
    TTransaction::close();
    igual('desligado a mao no oficial: o automatico nao liga de novo', null, $atendente($u2));
}
finally
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    foreach ($escritorios as $e) { $c->prepare('UPDATE escritorio SET url_apchat = ?, token_apchat = ? WHERE id = ?')->execute([$e['url_apchat'], $e['token_apchat'], $e['id']]); }
    foreach ($usuarios as $u) { $c->prepare('UPDATE system_users SET email = ? WHERE id = ?')->execute([$u['email'], $u['id']]); }
    $c->exec('DELETE FROM apchat_usuario');
    foreach ($antes as $l)
    {
        $c->prepare('INSERT INTO apchat_usuario (' . implode(', ', array_keys($l)) . ') VALUES (' . implode(', ', array_fill(0, count($l), '?')) . ')')->execute(array_values($l));
    }
    TTransaction::close();
    ApchatBancoService::usarFalso(null);
}

fim();
