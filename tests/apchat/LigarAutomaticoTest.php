<?php
// tests/apchat/LigarAutomaticoTest.php
// "Ligar automaticamente" (17/11/2026): APChat e APMail independentes - com so uma integracao funcionando, liga os usuarios dela e
// so informa a outra, sem bloquear com "Configure o token do APMail...". Banco do APChat e API do APMail falsos; grava de verdade
// e devolve tudo no fim.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function ql(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function xl(string $sql, array $p = []): void { TTransaction::open('escritorio'); TTransaction::get()->prepare($sql)->execute($p); TTransaction::close(); }
// o aviso vai em base64 (__adianti_show_toast64): devolve "tipo|mensagem" decodificada, mais o resto da saida
$ligar = function () { ob_start(); ApchatUsuarioList::onLigarAutomatico([]); $s = ob_get_clean();
    return preg_match("/__adianti_show_toast64\('(\w+)', '([^']+)'/", $s, $m) ? "'{$m[1]}'|" . base64_decode($m[2]) . "
" . $s : $s; };

$escritorios = ql('SELECT id, system_unit_id, url_apchat, token_apchat, url_apmail, token_apmail FROM escritorio ORDER BY id');
$usuarios = ql("SELECT id, email FROM system_users WHERE active = 'Y' ORDER BY id LIMIT 2");
[$u1, $u2] = array_map('intval', array_column($usuarios, 'id'));
$apchatAntes = ql('SELECT * FROM apchat_usuario ORDER BY id');
$apmailAntes = ql('SELECT * FROM apmail_usuario ORDER BY id');
TSession::setValue('userunitid', (int) $escritorios[0]['system_unit_id']);
TSession::setValue('userid', $u1);
$falso = new class {
    public $empresa = true;
    public function disponivel() { return true; }
    public function integracaoDaEmpresa(string $id) { return $this->empresa; }
    public function atendentes(): array { return [['id' => 771, 'name' => 'Pessoa Ligar Um', 'email' => 'ligar.um@teste.local', 'inactive' => false]]; }
};
$apmailChamadas = 0;
ApmailApiService::usarTransporte(function (string $url) use (&$apmailChamadas) {
    $apmailChamadas++;
    parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
    if (($q['email'] ?? '') === 'ligar.dois@teste.local') {
        return ['status' => 200, 'retry_after' => null, 'erro' => '', 'corpo' => json_encode(['success' => true, 'data' => ['unread' => 0, 'pending' => 0, 'mailboxes' => []]])];
    }
    return ['status' => 404, 'corpo' => '{"error":{"code":"notification_scope_unavailable","message":"x"}}', 'erro' => '', 'retry_after' => null];
});

try
{
    ApchatBancoService::usarFalso($falso);
    xl('DELETE FROM apchat_usuario'); xl('DELETE FROM apmail_usuario');
    xl("UPDATE system_users SET email = 'ligar.um@teste.local' WHERE id = ?", [$u1]);
    xl("UPDATE system_users SET email = 'ligar.dois@teste.local' WHERE id = ?", [$u2]);

    // ---------- so o APChat funcionando (APMail sem token) ----------
    xl("UPDATE escritorio SET url_apchat = 'https://apchat.teste.invalid/v2/api/external/00000000-0000-4000-8000-00000000c0de', token_apchat = 'tk', token_apmail = NULL");
    $saida = $ligar();
    ok('so APChat: liga os usuarios do APChat', (int) (ql("SELECT apchat_usuario_id FROM apchat_usuario WHERE system_user_id = {$u1}")[0]['apchat_usuario_id'] ?? 0) === 771, $saida);
    ok('so APChat: nao bloqueia com a mensagem do token do APMail', strpos($saida, 'Configure o token do APMail') === false && strpos($saida, 'TMessage') === false && strpos($saida, "'error'") === false, substr(strip_tags($saida), 0, 300));
    ok('so APChat: informa que o APMail nao foi ligado e o motivo', strpos($saida, 'APMail:</b> não ligado') !== false && strpos($saida, 'APMail não está configurada') !== false, substr($saida, 0, 400));
    igual('so APChat: nao chama o APMail', 0, $apmailChamadas);

    // ---------- so o APMail funcionando (APChat de outra empresa) ----------
    xl('DELETE FROM apchat_usuario');
    $falso->empresa = false;
    xl("UPDATE escritorio SET url_apmail = 'https://apmail.teste.invalid', token_apmail = 'tk-apmail'");
    $saida = $ligar();
    igual('so APMail: o usuario reconhecido e ligado no APMail', 'S', ql("SELECT situacao FROM apmail_usuario WHERE system_user_id = {$u2}")[0]['situacao'] ?? null);
    igual('so APMail: nada ligado no APChat', 0, count(ql('SELECT 1 FROM apchat_usuario WHERE apchat_usuario_id IS NOT NULL')));
    ok('so APMail: informa o motivo do APChat', strpos($saida, 'APChat:</b> não ligado') !== false && strpos($saida, 'mesma empresa') !== false, substr($saida, 0, 400));

    // ---------- nenhuma funcionando: aviso, sem erro ----------
    xl('UPDATE escritorio SET token_apmail = NULL');
    $saida = $ligar();
    ok('nenhuma: aviso (nao erro) com os dois motivos', strpos($saida, "'warning'") !== false && substr_count($saida, 'não ligado') === 2, substr($saida, 0, 400));
}
finally
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    foreach ($escritorios as $e) { $c->prepare('UPDATE escritorio SET url_apchat = ?, token_apchat = ?, url_apmail = ?, token_apmail = ? WHERE id = ?')->execute([$e['url_apchat'], $e['token_apchat'], $e['url_apmail'], $e['token_apmail'], $e['id']]); }
    foreach ($usuarios as $u) { $c->prepare('UPDATE system_users SET email = ? WHERE id = ?')->execute([$u['email'], $u['id']]); }
    $c->exec('DELETE FROM apchat_usuario'); $c->exec('DELETE FROM apmail_usuario');
    foreach (['apchat_usuario' => $apchatAntes, 'apmail_usuario' => $apmailAntes] as $tab => $linhas) {
        foreach ($linhas as $l) { $c->prepare("INSERT INTO {$tab} (" . implode(', ', array_keys($l)) . ') VALUES (' . implode(', ', array_fill(0, count($l), '?')) . ')')->execute(array_values($l)); }
    }
    TTransaction::close();
    ApchatBancoService::usarFalso(null);
    ApmailApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso do APMail.'); });
    ApmailEnvelopeService::limparCache();
}

fim();
