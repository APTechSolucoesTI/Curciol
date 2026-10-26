<?php
// tests/portal/ApchatLoteTest.php
// "Sincronizar todos" em paralelo (26/10/2026): mesma regra do cadastro (consulta, variante do nono digito, cria ou
// atualiza), telefone repetido no lote nao duplica contato, caminho curto (1 chamada) quando o numero nao mudou,
// repeticao quando o APChat pede calma (429), idapchat gravado na pessoa e chamadas realmente simultaneas.
// NUNCA fala com o APChat de verdade: APChat falso em memoria e um APChat falso por HTTP em 127.0.0.1.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_LOTE = 'TESTE APCHAT LOTE';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

// ---------- APChat falso em memoria ----------
$falso = ['contatos' => [], 'proximo' => 70001, 'chamadas' => [], 'falhar_create' => 0];
$responder = function (int $status, array $json) { return ['status' => $status, 'corpo' => json_encode($json), 'erro' => '']; };
APChatContactService::usarTransporte(function ($rota, $corpo, $token) use (&$falso, $responder) {
    $falso['chamadas'][] = $rota;
    $n = (string) ($corpo['number'] ?? '');
    if ($rota === 'showcontact') {
        return isset($falso['contatos'][$n]) ? $responder(200, ['success' => true, 'data' => $falso['contatos'][$n]]) : $responder(404, ['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND']);
    }
    if ($rota === 'createContact') {
        if ($falso['falhar_create'] > 0) { $falso['falhar_create']--; return $responder(429, ['success' => false, 'error' => 'TOO_MANY_REQUESTS']); }
        $falso['contatos'][$n] = $corpo + ['id' => $falso['proximo']++];
        return $responder(201, ['success' => true, 'data' => $falso['contatos'][$n]]);
    }
    if ($rota === 'updateContact') {
        if (!isset($falso['contatos'][$n])) { return $responder(404, ['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND']); }
        $falso['contatos'][$n] = array_merge($falso['contatos'][$n], $corpo);
        return $responder(200, ['success' => true, 'data' => $falso['contatos'][$n]]);
    }
    return $responder(404, ['success' => false, 'error' => 'ROTA']);
});

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$unidade = (int) TTransaction::get()->query("SELECT system_unit_id FROM escritorio WHERE COALESCE(token_apchat, '') <> '' ORDER BY id LIMIT 1")->fetchColumn();
TTransaction::close();
TSession::setValue('userunitid', $unidade);

$ids = [];
$novo = function (string $nome, string $telefone) use (&$ids) {
    TTransaction::open('escritorio');
    $p = new Pessoa();
    $p->nome = MARCA_LOTE . ' ' . $nome;
    $p->tipo_pessoa_id = TipoPessoa::FISICA;
    $p->telefone = $telefone;
    $p->store();
    TTransaction::close();
    $ids[] = (int) $p->id;
    return (int) $p->id;
};
$idapchat = function (int $id) { return (int) (q("SELECT COALESCE(idapchat, 0) i FROM pessoa WHERE id = {$id}")[0]['i']); };
$servidor = null;

try {
    // ---------- regra no lote (APChat falso em memoria) ----------
    $a = $novo('A', '11987650001');
    $b = $novo('B', '11987650002');
    $c = $novo('C MESMO TELEFONE DA A', '(11) 98765-0001');
    $d = $novo('D TELEFONE INVALIDO', '123');
    $falso['contatos']['551187650003'] = ['id' => 69999, 'number' => '551187650003', 'name' => 'JA EXISTIA SEM O NONO DIGITO'];
    $e = $novo('E JA EXISTIA', '11987650003');

    $falso['falhar_create'] = 1; // a primeira criacao recebe 429 e e repetida
    $r = APChatContactService::sincronizarLote([$a, $b, $c, $d, $e], ApchatContatoLog::ORIGEM_LOTE);
    igual('lote: resultados por pessoa', ['SUCESSO|CREATE', 'SUCESSO|CREATE', 'SUCESSO|UPDATE', 'IGNORADO|NENHUMA', 'SUCESSO|UPDATE'],
        array_map(function ($id) use ($r) { return $r[$id]->situacao . '|' . $r[$id]->operacao; }, [$a, $b, $c, $d, $e]));
    igual('lote: telefone repetido nao duplica (2 criados, a C atualiza o contato da A)', 2, count(array_filter($falso['chamadas'], function ($x) { return $x === 'createContact'; })) - 1);
    ok('lote: 429 foi repetido e deu certo', $falso['falhar_create'] === 0 && isset($falso['contatos']['5511987650001'], $falso['contatos']['5511987650002']));
    ok('lote: achou pela variante sem o nono digito', strpos($r[$e]->mensagem, 'sem o nono dígito') !== false, $r[$e]->mensagem);
    igual('lote: idapchat gravado (A, B, C = id da A, E = 69999; D sem)', [$falso['contatos']['5511987650001']['id'], $falso['contatos']['5511987650002']['id'], $falso['contatos']['5511987650001']['id'], 0, 69999],
        [$idapchat($a), $idapchat($b), $idapchat($c), $idapchat($d), $idapchat($e)]);
    igual('lote: um log por pessoa', 5, (int) q('SELECT count(*) n FROM apchat_contato_log WHERE pessoa_id IN (' . implode(',', [$a, $b, $c, $d, $e]) . ')')[0]['n']);

    // ---------- caminho curto: numero nao mudou -> 1 chamada ----------
    $falso['chamadas'] = [];
    $r = APChatContactService::sincronizarLote([$a, $b], ApchatContatoLog::ORIGEM_LOTE);
    igual('caminho curto: so updateContact, 1 por pessoa', ['updateContact', 'updateContact'], $falso['chamadas']);
    igual('caminho curto: atualizados', ['SUCESSO|UPDATE', 'SUCESSO|UPDATE'], [$r[$a]->situacao . '|' . $r[$a]->operacao, $r[$b]->situacao . '|' . $r[$b]->operacao]);

    // telefone mudou: volta ao fluxo completo (contato antigo atualizado, pendente, nada criado)
    x("UPDATE pessoa SET telefone = '11987650099' WHERE id = {$b}");
    $falso['chamadas'] = [];
    $r = APChatContactService::sincronizarPessoa($b, [], ApchatContatoLog::ORIGEM_CADASTRO);
    ok('telefone mudou: fluxo completo, pendente, sem criar', $r->situacao === 'PENDENTE' && !in_array('createContact', $falso['chamadas'], true), json_encode($falso['chamadas']));

    // ---------- chamadas simultaneas de verdade (APChat falso por HTTP, ~0,3 s por resposta) ----------
    APChatContactService::usarTransporte(null);
    $porta = 18000 + random_int(0, 999);
    @unlink(sys_get_temp_dir() . "/apchat-falso-{$porta}.json");
    $servidor = proc_open([PHP_BINARY, '-S', "127.0.0.1:{$porta}", __DIR__ . '/apchat-falso-servidor.php'],
        [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes, null,
        array_merge(getenv(), ['PHP_CLI_SERVER_WORKERS' => '12']));
    APChatContactService::usarUrlBase("http://127.0.0.1:{$porta}/");
    for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $porta); $i++) { usleep(100000); }

    $vinte = [];
    for ($i = 10; $i < 30; $i++) { $vinte[] = $novo("HTTP {$i}", '119876500' . $i); }
    $t = microtime(true);
    $r = APChatContactService::sincronizarLote($vinte, ApchatContatoLog::ORIGEM_LOTE);
    $segundos = microtime(true) - $t;
    $estado = json_decode((string) file_get_contents(sys_get_temp_dir() . "/apchat-falso-{$porta}.json"), true);
    igual('HTTP: 20 contatos criados', [20, 20], [count(array_filter($r, function ($x) { return $x->situacao === 'SUCESSO' && $x->operacao === 'CREATE'; })), count($estado['contatos'] ?? [])]);
    // uma chamada por vez levaria (chamadas x 0,3 s); ao mesmo tempo tem de ficar bem abaixo disso
    $umaPorVez = count($estado['chamadas'] ?? []) * 0.3;
    ok('HTTP: chamadas ao mesmo tempo (menos de 2/3 do tempo de uma por vez)', $segundos < $umaPorVez * 2 / 3, round($segundos, 2) . ' s de ' . round($umaPorVez, 1) . ' s');
    ok('HTTP: idapchat gravado nos 20', count(array_filter($vinte, function ($id) use ($idapchat) { return $idapchat($id) >= 50001; })) === 20);
    echo '    tempo dos 20 contatos em paralelo: ', round($segundos, 2), " s\n";
} finally {
    APChatContactService::usarTransporte(null);
    APChatContactService::usarUrlBase(null);
    if ($servidor) { proc_terminate($servidor); proc_close($servidor); }
    if ($ids) {
        x('DELETE FROM apchat_contato_log WHERE pessoa_id IN (' . implode(',', $ids) . ')');
        x('DELETE FROM pessoa WHERE id IN (' . implode(',', $ids) . ')');
    }
}
fim();
