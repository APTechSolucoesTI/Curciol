<?php
// tests/apchat/ApchatUsuarioTest.php
// Usuarios da Curciol ligados aos atendentes do APChat (10/11/2026): automatico pelo e-mail, senao pelo nome completo ou primeiro
// nome de UM unico usuario ativo livre; um atendente para um usuario; alteravel; tela de configuracao e campo no usuario.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }

// ---------- regra pura ----------
$atendentes = [
    ['id' => 1, 'name' => 'Kleber', 'email' => 'Kleber@Curciol.com.br', 'inactive' => false],
    ['id' => 2, 'name' => 'Danielle Curciol', 'email' => 'dani@curciol.com.br', 'inactive' => false],
    ['id' => 3, 'name' => 'Juliana', 'email' => null, 'inactive' => false],
    ['id' => 4, 'name' => 'Ana', 'email' => null, 'inactive' => false],
    ['id' => 5, 'name' => 'Kadichari', 'email' => 'kadi@curciol.com.br', 'inactive' => false],
    ['id' => 6, 'name' => 'Vânia', 'email' => null, 'inactive' => false],
    ['id' => 7, 'name' => 'Desligado', 'email' => 'paulo@curciol.com.br', 'inactive' => true],
];
$usuarios = [
    ['id' => 10, 'name' => 'KLEBER CURCIOL', 'email' => 'kleber@curciol.com.br', 'active' => 'Y'],
    ['id' => 11, 'name' => 'DANIELLE DOS SANTOS', 'email' => 'dani@curciol.com.br', 'active' => 'Y'],
    ['id' => 12, 'name' => 'JULIANA SOUZA', 'email' => 'ju@outro.com', 'active' => 'Y'],
    ['id' => 13, 'name' => 'ANA LIMA', 'email' => null, 'active' => 'Y'],
    ['id' => 14, 'name' => 'ANA PAULA', 'email' => null, 'active' => 'Y'],
    ['id' => 15, 'name' => 'INATIVO', 'email' => 'kadi@curciol.com.br', 'active' => 'N'],
    ['id' => 16, 'name' => 'VANIA MOREIRA', 'email' => null, 'active' => 'Y'],
    ['id' => 17, 'name' => 'PAULO', 'email' => 'paulo@curciol.com.br', 'active' => 'Y'],
];
$casados = ApchatUsuarioService::casar($atendentes, $usuarios, []);
igual('por e-mail, sem diferenca de maiusculas', 1, $casados[10] ?? null);
igual('por e-mail', 2, $casados[11] ?? null);
igual('pelo primeiro nome de um unico usuario', 3, $casados[12] ?? null);
ok('empate de primeiro nome: ninguem', !isset($casados[13]) && !isset($casados[14]) && !in_array(4, $casados, true));
ok('usuario inativo nao liga', !isset($casados[15]));
igual('nome sem acento (Vânia = VANIA)', 6, $casados[16] ?? null);
ok('atendente inativo no APChat nao liga', !isset($casados[17]));
$comLigados = ApchatUsuarioService::casar($atendentes, $usuarios, [12 => 9, 99 => 1]);
ok('ja ligados nao mudam (usuario 12 e atendente 1 ocupados)', !isset($comLigados[12]) && !in_array(1, $comLigados, true) && !isset($comLigados[10]), json_encode($comLigados));

// ---------- banco ----------
ApchatBancoService::usarFalso(new class($atendentes) {
    public $a; public function __construct($a) { $this->a = $a; }
    public function disponivel() { return true; }
    public function atendentes(): array { return $this->a; }
});
TSession::setValue('userid', 1);
TTransaction::open('escritorio');
try
{
    $c = TTransaction::get();
    $ids = array_map('intval', array_column($c->query("SELECT id FROM system_users WHERE active = 'Y' ORDER BY id LIMIT 2")->fetchAll(PDO::FETCH_ASSOC), 'id'));
    [$u1, $u2] = $ids;
    $c->exec('DELETE FROM apchat_usuario');

    ApchatUsuarioService::ligar($u1, 3);
    igual('ligar grava', 3, (int) ApchatUsuarioService::doUsuario($u1)->apchat_usuario_id);
    igual('avisos ligados por padrao', 'S', ApchatUsuarioService::doUsuario($u1)->avisos);
    igual('tickets abertos desligados por padrao', 'N', ApchatUsuarioService::doUsuario($u1)->mostrar_abertos);
    lanca('atendente ja ligado a outro usuario: recusa', fn() => ApchatUsuarioService::ligar($u2, 3), 'já está ligado');
    ApchatUsuarioService::ligar($u1, null);
    igual('desligar', null, ApchatUsuarioService::doUsuario($u1)->apchat_usuario_id);
    ApchatUsuarioService::ligar($u2, 3);
    igual('livre de novo: outro usuario pode', 3, (int) ApchatUsuarioService::doUsuario($u2)->apchat_usuario_id);
    ApchatUsuarioService::preferencias($u2, false, true);
    ok('preferencias', ApchatUsuarioService::doUsuario($u2)->avisos === 'N' && ApchatUsuarioService::doUsuario($u2)->mostrar_abertos === 'S');

    // desligado a mao (registro sem atendente) nao volta a ser ligado pelo automatico
    $kleber = (int) $c->query("SELECT id FROM system_users WHERE active = 'Y' AND id NOT IN ({$u1}, {$u2}) ORDER BY id LIMIT 1")->fetchColumn();
    $c->prepare('UPDATE system_users SET email = ? WHERE id = ?')->execute(['Kleber@Curciol.com.br', $kleber]);
    ApchatUsuarioService::ligar($kleber, null);
    ApchatUsuarioService::ligarAutomatico();
    igual('desligado a mao continua desligado', null, ApchatUsuarioService::doUsuario($kleber)->apchat_usuario_id);
    $c->exec("DELETE FROM apchat_usuario WHERE system_user_id = {$kleber}");
    ApchatUsuarioService::ligarAutomatico();
    igual('nunca configurado: automatico liga pelo e-mail', 1, (int) (ApchatUsuarioService::doUsuario($kleber)->apchat_usuario_id ?? 0));

    $n = ApchatUsuarioService::ligarAutomatico();
    ok('automatico nao desfaz ligacao feita a mao', (int) ApchatUsuarioService::doUsuario($u2)->apchat_usuario_id === 3);
    ok('automatico devolve quantos ligou', is_int($n));

    $tela = saida(function () { $t = new ApchatUsuarioList([]); $t->onReload([]); $t->show(); });
    ok('tela de usuarios do APChat monta', strpos($tela, 'form_ApchatUsuarioList') !== false && stripos($tela, 'fatal error') === false, substr($tela, 0, 300));
    ok('tela lista os atendentes', strpos($tela, 'Juliana') !== false);
    $form = saida(function () use ($u2) { $f = new SystemUserForm([]); $f->onEdit(['key' => $u2]); $f->show(); });
    ok('cadastro de usuario: campo do atendente do APChat com o ligado', preg_match('/name="apchat_usuario_id"/', $form) === 1 && strpos($form, 'Juliana') !== false, substr($form, 0, 200));
}
finally
{
    TTransaction::rollback();
    ApchatBancoService::usarFalso(null);
}

fim();
