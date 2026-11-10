<?php
// tests/apchat/ApchatConciliacaoTest.php
// Conciliacao (10/11/2026, cron a cada 5 min): contato do APChat fora do espelho cujo numero (ou variante com/sem 9) e da Curciol
// passa a ser ligado e trancado; variante vira segundo contato do mesmo cliente sem trocar o idapchat que ja existe; numero que
// nao e da Curciol fica como esta. Fora da producao so conta (nada vai ao APChat). APChat falso (API e banco em memoria).
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_CONC = 'TESTE CONCILIACAO APCHAT';
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }

$apchat = ['contatos' => [], 'espelho' => [], 'chamadas' => []];
APChatContactService::usarTransporte(function ($rota, $corpo, $token) use (&$apchat) {
    $apchat['chamadas'][] = [$rota, $corpo['number'] ?? ''];
    $n = (string) ($corpo['number'] ?? '');
    if ($rota === 'updateContact' && isset($apchat['contatos'][$n])) {
        $apchat['contatos'][$n] = array_merge($apchat['contatos'][$n], $corpo);
        return ['status' => 200, 'corpo' => json_encode(['success' => true, 'data' => $apchat['contatos'][$n]]), 'erro' => ''];
    }
    if ($rota === 'showcontact' && isset($apchat['contatos'][$n])) {
        return ['status' => 200, 'corpo' => json_encode(['success' => true, 'data' => $apchat['contatos'][$n]]), 'erro' => ''];
    }
    return ['status' => 404, 'corpo' => json_encode(['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND']), 'erro' => ''];
});
$banco = new class($apchat) {
    public $a;
    public function __construct(&$a) { $this->a = &$a; }
    public function disponivel() { return true; }
    public function contatosPorNumeros(array $numeros): array {
        $r = [];
        foreach ($numeros as $n) { foreach (APChatContactService::variantes((string) $n) as $v) { if (isset($this->a['contatos'][$v])) { $r[$v] = $this->a['contatos'][$v] + ['number' => $v]; } } }
        return $r;
    }
    public function contatosForaDoEspelho(int $limite = 200): array {
        $r = [];
        foreach ($this->a['contatos'] as $n => $c) { if (!isset($this->a['espelho'][$c['id']])) { $r[] = ['id' => $c['id'], 'number' => $n, 'name' => $c['name']]; } }
        return array_slice($r, 0, $limite);
    }
    public function gravarEspelho(int $id, string $nome, string $numero, ?string $email, array $corpo = []): void { $this->a['espelho'][$id] = compact('nome', 'numero', 'email'); }
    public function contato(int $id): ?array { foreach ($this->a['contatos'] as $n => $c) { if ((int) $c['id'] === $id) { return $c + ['number' => $n]; } } return null; }
};
ApchatBancoService::usarFalso($banco);
TSession::setValue('userid', 1);
tokenApchatDeTeste();

$pessoa = null;
// sobra de execucao interrompida (erro fatal nao passa pelo finally)
foreach (q("SELECT id FROM pessoa WHERE nome = '" . MARCA_CONC . "'") as $sobra)
{
    x("DELETE FROM apchat_contato_log WHERE pessoa_id = {$sobra['id']}");
    x("DELETE FROM pessoa_contato WHERE pessoa_id = {$sobra['id']}");
    x("DELETE FROM pessoa WHERE id = {$sobra['id']}");
}
try
{
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare("INSERT INTO pessoa (nome, tipo_pessoa_id, aceita_receber_mensagen_whatsapp) VALUES (?, 1, 'T') RETURNING id");
    $st->execute([MARCA_CONC]);
    $pessoa = (int) $st->fetchColumn();
    TTransaction::close();
    x("INSERT INTO pessoa_contato (pessoa_id, nome, descricao, telefone, principal, idapchat) VALUES ({$pessoa}, '" . MARCA_CONC . "', 'CELULAR', '(19) 98111-2233', 'S', 77001)");
    x("INSERT INTO pessoa_contato (pessoa_id, nome, descricao, telefone, principal) VALUES ({$pessoa}, 'RECADO', 'RECADO', '(19) 98111-4455', 'N')");

    // no APChat: o principal ja ligado (77001), a variante sem 9 do principal solta (77002), o recado solto (77003) e um estranho (77004)
    $apchat['contatos'] = [
        '5519981112233' => ['id' => 77001, 'name' => MARCA_CONC],
        '551981112233'  => ['id' => 77002, 'name' => 'Zé WhatsApp'],
        '5519981114455' => ['id' => 77003, 'name' => 'Recado WhatsApp'],
        '5511900007777' => ['id' => 77004, 'name' => 'Estranho'],
    ];
    $apchat['espelho'] = [77001 => ['nome' => MARCA_CONC, 'numero' => '5519981112233', 'email' => null]];

    // ---------- homologacao: so conta ----------
    AmbienteService::forcarProducao(false);
    $r = ApchatConciliacaoService::rodada();
    igual('homologacao: acha os dois que sao da Curciol', 2, $r['pendentes'] ?? null, json_encode($r));
    igual('homologacao: o estranho fica sem vinculo', 1, $r['sem_vinculo'] ?? null);
    igual('homologacao: nenhuma chamada ao APChat', [], $apchat['chamadas']);
    ok('homologacao: espelho nao muda', !isset($apchat['espelho'][77002]) && !isset($apchat['espelho'][77003]));

    // ---------- producao: liga e tranca ----------
    AmbienteService::forcarProducao(true);
    $r = ApchatConciliacaoService::rodada();
    igual('producao: ligados', 2, ($r['ligados'] ?? 0) + ($r['variantes'] ?? 0), json_encode($r));
    igual('producao: uma variante', 1, $r['variantes'] ?? null);
    ok('variante entra no espelho com o proprio numero e o nome da Curciol', ($apchat['espelho'][77002]['numero'] ?? '') === '551981112233' && ($apchat['espelho'][77002]['nome'] ?? '') === MARCA_CONC, json_encode($apchat['espelho']));
    igual('variante: nome atualizado no APChat', MARCA_CONC, $apchat['contatos']['551981112233']['name']);
    ok('recado ligado (espelho)', isset($apchat['espelho'][77003]));
    igual('variante nao troca o idapchat do principal', 77001, (int) q("SELECT idapchat FROM pessoa_contato WHERE pessoa_id = {$pessoa} AND principal = 'S'")[0]['idapchat']);
    igual('recado ganha o idapchat dele', 77003, (int) q("SELECT idapchat FROM pessoa_contato WHERE pessoa_id = {$pessoa} AND principal = 'N'")[0]['idapchat']);
    ok('estranho continua solto', !isset($apchat['espelho'][77004]) && $apchat['contatos']['5511900007777']['name'] === 'Estranho');
    ok('log da conciliacao', (int) q("SELECT count(*) n FROM apchat_contato_log WHERE pessoa_id = {$pessoa} AND origem = 'CONCILIACAO'")[0]['n'] >= 2);

    $apchat['chamadas'] = [];
    $r = ApchatConciliacaoService::rodada();
    igual('segunda rodada: nada a fazer', 0, ($r['ligados'] ?? 0) + ($r['variantes'] ?? 0), json_encode($r));
    igual('segunda rodada: nenhuma chamada', [], $apchat['chamadas']);

    // ---------- APChat fora ----------
    ApchatBancoService::usarFalso(new class { public function disponivel() { return false; } });
    $r = ApchatConciliacaoService::rodada();
    ok('APChat fora: nao quebra', ($r['ok'] ?? true) === false);
}
finally
{
    AmbienteService::forcarProducao(null);
    ApchatBancoService::usarFalso(null);
    if ($pessoa)
    {
        x("DELETE FROM apchat_contato_log WHERE pessoa_id = {$pessoa}");
        x("DELETE FROM pessoa_contato WHERE pessoa_id = {$pessoa}");
        x("DELETE FROM pessoa WHERE id = {$pessoa}");
    }
}

fim();
