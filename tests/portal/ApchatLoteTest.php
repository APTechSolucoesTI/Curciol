<?php
// tests/portal/ApchatLoteTest.php
// "Sincronizar todos" em paralelo (26/10/2026; um contato no APChat por numero dos contatos desde 27/10): mesma regra do cadastro (consulta, variante do nono digito, cria ou
// atualiza), telefone repetido no lote nao duplica contato, caminho curto (1 chamada) quando o numero nao mudou,
// repeticao quando o APChat pede calma (429), idapchat gravado na pessoa e chamadas realmente simultaneas.
// NUNCA fala com o APChat de verdade: APChat falso em memoria e um APChat falso por HTTP em 127.0.0.1.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_LOTE = 'TESTE APCHAT LOTE';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

// ---------- APChat falso em memoria ----------
$falso = ['contatos' => [], 'proximo' => 70001, 'chamadas' => [], 'falhar_create' => 0, 'tempo_update' => 0, 'tempo_create' => 0];
$responder = function (int $status, array $json) { return ['status' => $status, 'corpo' => json_encode($json), 'erro' => '']; };
APChatContactService::usarTransporte(function ($rota, $corpo, $token) use (&$falso, $responder) {
    $falso['chamadas'][] = $rota;
    $n = (string) ($corpo['number'] ?? '');
    if ($rota === 'showcontact') {
        return isset($falso['contatos'][$n]) ? $responder(200, ['success' => true, 'data' => $falso['contatos'][$n]]) : $responder(404, ['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND']);
    }
    $semResposta = ['status' => 0, 'corpo' => '', 'erro' => 'Operation timed out after 15000 milliseconds'];
    if ($rota === 'updateContact' && $falso['tempo_update'] > 0) { $falso['tempo_update']--; return $semResposta; }
    if ($rota === 'createContact' && $falso['tempo_create'] > 0) { $falso['tempo_create']--; return $semResposta; }
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
tokenApchatDeTeste();
TTransaction::open('escritorio');
$unidade = (int) TTransaction::get()->query("SELECT system_unit_id FROM escritorio WHERE COALESCE(token_apchat, '') <> '' ORDER BY id LIMIT 1")->fetchColumn();
TTransaction::close();
TSession::setValue('userunitid', $unidade);

$ids = [];
// pessoa com o telefone dos Dados (vira o contato principal, como pela tela); telefone invalido fica sem contato
$novo = function (string $nome, string $telefone) use (&$ids) {
    TTransaction::open('escritorio');
    $p = new Pessoa();
    $p->nome = MARCA_LOTE . ' ' . $nome;
    $p->tipo_pessoa_id = TipoPessoa::FISICA;
    $p->telefone = PessoaContatoService::telefoneValido($telefone) ?? $telefone;
    $p->store();
    if (PessoaContatoService::telefoneValido($telefone)) { PessoaContatoService::aplicarPrincipal($p->id, PessoaContatoService::estadoAtual(null)); }
    TTransaction::close();
    $ids[] = (int) $p->id;
    return (int) $p->id;
};
// pessoa so com um contato nao principal (sem nada nos Dados)
$soContato = function (string $nome, string $telefone, ?string $descricao, ?string $nomeContato = null) use (&$ids) {
    TTransaction::open('escritorio');
    $p = new Pessoa();
    $p->nome = MARCA_LOTE . ' ' . $nome;
    $p->tipo_pessoa_id = TipoPessoa::FISICA;
    $p->store();
    TTransaction::get()->prepare("INSERT INTO pessoa_contato (pessoa_id, nome, descricao, telefone, principal) VALUES (?, ?, ?, ?, 'N')")->execute([$p->id, $nomeContato, $descricao, $telefone]);
    TTransaction::close();
    $ids[] = (int) $p->id;
    return (int) $p->id;
};
$idapchat = function (int $id) { return (int) (q("SELECT COALESCE(idapchat, 0) i FROM pessoa WHERE id = {$id}")[0]['i']); };
$idContatos = function (int $id) { return array_map('intval', array_column(q("SELECT COALESCE(idapchat, 0) i FROM pessoa_contato WHERE pessoa_id = {$id} ORDER BY id"), 'i')); };
$servidor = null;

try {
    // ---------- regra no lote (APChat falso em memoria): um contato no APChat por numero ----------
    $a = $novo('A', '11987650001');
    $b = $novo('B', '11987650002');
    $c = $novo('C MESMO TELEFONE DA A', '(11) 98765-0001'); // vira contato nao principal da C (o numero e principal da A)
    $d = $novo('D TELEFONE INVALIDO', '123');
    $falso['contatos']['551187650003'] = ['id' => 69999, 'number' => '551187650003', 'name' => 'JA EXISTIA SEM O NONO DIGITO'];
    $e = $novo('E JA EXISTIA', '11987650003');

    $falso['falhar_create'] = 1; // a primeira criacao recebe 429 e e repetida
    $r = APChatContactService::sincronizarLote([$a, $b, $c, $d, $e], ApchatContatoLog::ORIGEM_LOTE);
    igual('lote: resultados por pessoa (a C usa o mesmo contato da A)', ['SUCESSO|CREATE', 'SUCESSO|CREATE', 'SUCESSO|CREATE', 'IGNORADO|NENHUMA', 'SUCESSO|UPDATE'],
        array_map(function ($id) use ($r) { return $r[$id]->situacao . '|' . $r[$id]->operacao; }, [$a, $b, $c, $d, $e]));
    igual('lote: telefone repetido nao duplica (2 criados, o numero da A e da C uma vez so)', 2, count(array_filter($falso['chamadas'], function ($x) { return $x === 'createContact'; })) - 1);
    ok('lote: 429 foi repetido e deu certo', $falso['falhar_create'] === 0 && isset($falso['contatos']['5511987650001'], $falso['contatos']['5511987650002']));
    ok('lote: achou pela variante sem o nono digito', strpos($r[$e]->mensagem, 'sem o nono dígito') !== false, $r[$e]->mensagem);
    igual('lote: idapchat nos contatos (A e C com o mesmo id; E = 69999; D sem contato)',
        [[$falso['contatos']['5511987650001']['id']], [$falso['contatos']['5511987650002']['id']], [$falso['contatos']['5511987650001']['id']], [], [69999]],
        [$idContatos($a), $idContatos($b), $idContatos($c), $idContatos($d), $idContatos($e)]);
    igual('lote: idapchat da pessoa = o do principal (C sem principal)', [$falso['contatos']['5511987650001']['id'], 0, 69999], [$idapchat($a), $idapchat($c), $idapchat($e)]);
    igual('lote: nome no APChat = o do contato principal', MARCA_LOTE . ' A', $falso['contatos']['5511987650001']['name'] ?? null);
    igual('lote: um log por pessoa e numero', 5, (int) q('SELECT count(*) n FROM apchat_contato_log WHERE pessoa_id IN (' . implode(',', [$a, $b, $c, $d, $e]) . ')')[0]['n']);

    // contato sem principal: nome = o nome do contato; sem nome, a descricao; sem os dois, o numero
    $g = $soContato('G', '11987650005', 'RECADO SITIO');
    $h = $soContato('H', '11987650006', null);
    $k = $soContato('K', '11987650007', 'FILHA', 'MARIA DE TESTE');
    APChatContactService::sincronizarLote([$g, $h, $k], ApchatContatoLog::ORIGEM_LOTE);
    igual('nome enviado: descricao / numero / nome do contato', ['RECADO SITIO', '(11) 98765-0006', 'MARIA DE TESTE'],
        [$falso['contatos']['5511987650005']['name'] ?? null, $falso['contatos']['5511987650006']['name'] ?? null, $falso['contatos']['5511987650007']['name'] ?? null]);
    ok('contatos so de outras pessoas tambem sao exportados (idapchat gravado)', $idContatos($g)[0] > 0 && $idContatos($h)[0] > 0 && $idContatos($k)[0] > 0);
    ok('Sincronizar todos inclui quem so tem contato (sem telefone nos Dados)', in_array($g, APChatContactService::idsAutorizados(), true));

    // ---------- caminho curto: numero ja tem id do APChat -> 1 chamada ----------
    $falso['chamadas'] = [];
    $r = APChatContactService::sincronizarLote([$a, $b], ApchatContatoLog::ORIGEM_LOTE);
    igual('caminho curto: so updateContact, 1 por numero', ['updateContact', 'updateContact'], $falso['chamadas']);
    igual('caminho curto: atualizados', ['SUCESSO|UPDATE', 'SUCESSO|UPDATE'], [$r[$a]->situacao . '|' . $r[$a]->operacao, $r[$b]->situacao . '|' . $r[$b]->operacao]);

    // tempo esgotado: atualizacao e repetida (da o mesmo resultado); criacao nao (repetir poderia duplicar)
    $falso['chamadas'] = [];
    $falso['tempo_update'] = 1;
    $r = APChatContactService::sincronizarLote([$a], ApchatContatoLog::ORIGEM_LOTE);
    ok('tempo esgotado na atualizacao: repetida e deu certo', $r[$a]->situacao === 'SUCESSO' && $falso['chamadas'] === ['updateContact', 'updateContact'], json_encode($falso['chamadas']));
    $f = $novo('F CRIACAO SEM RESPOSTA', '11987650004');
    $falso['chamadas'] = [];
    $falso['tempo_create'] = 1;
    $r = APChatContactService::sincronizarLote([$f], ApchatContatoLog::ORIGEM_LOTE);
    ok('tempo esgotado na criacao: nao repete (fica erro, sem duplicar)', $r[$f]->situacao === 'ERRO' && count(array_keys($falso['chamadas'], 'createContact')) === 1, json_encode($falso['chamadas']));

    // telefone do principal mudou na Curciol: a copia na outra pessoa acompanha; no APChat o contato antigo e atualizado
    // e fica pendente (a API nao troca numero), nada e criado
    $copia = $soContato('B2 COPIA DA B', '11987650002', 'IRMAO');
    TTransaction::open('escritorio');
    $antes = PessoaContatoService::estadoAtual($b);
    TTransaction::get()->exec("UPDATE pessoa SET telefone = '11987650099' WHERE id = {$b}");
    PessoaContatoService::aplicarPrincipal($b, $antes);
    TTransaction::close();
    igual('telefone do principal mudou: a copia em outra pessoa acompanha (descricao fica)', [['11987650099', 'IRMAO']],
        array_map('array_values', q("SELECT telefone, descricao FROM pessoa_contato WHERE pessoa_id = {$copia}")));
    $falso['chamadas'] = [];
    $r = APChatContactService::sincronizarPessoa($b, [], ApchatContatoLog::ORIGEM_CADASTRO);
    ok('telefone mudou: fluxo completo, pendente, sem criar', $r->situacao === 'PENDENTE' && !in_array('createContact', $falso['chamadas'], true), json_encode($falso['chamadas']) . ' ' . $r->situacao);

    // ---------- sincronizacao interrompida: oferece continuar de onde parou (so a pergunta; nada e enviado) ----------
    TSession::setValue('ApchatContatoList_parou', 120);
    ob_start(); ApchatContatoList::onSincronizarTodos([]); $pergunta = ob_get_clean();
    ok('interrompida: pergunta continuar (offset 120) ou recomecar (offset 0)', strpos($pergunta, 'Continuar de onde parou') !== false
        && strpos($pergunta, 'offset=120') !== false && strpos($pergunta, 'offset=0') !== false, substr(strip_tags($pergunta), 0, 200));
    TSession::setValue('ApchatContatoList_parou', null);
    ob_start(); ApchatContatoList::onSincronizarTodos([]); $pergunta = ob_get_clean();
    ok('sem interrupcao: pergunta normal, comecando do inicio', strpos($pergunta, 'Continuar de onde parou') === false && strpos($pergunta, 'offset=0') !== false);

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
        x('DELETE FROM pessoa_contato WHERE pessoa_id IN (' . implode(',', $ids) . ')');
        x('DELETE FROM pessoa WHERE id IN (' . implode(',', $ids) . ')');
    }
}
fim();
