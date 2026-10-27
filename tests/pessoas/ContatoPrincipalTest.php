<?php
// tests/pessoas/ContatoPrincipalTest.php
// Contato principal (26/10/2026; regras de 27/10/2026): telefone e e-mail dos Dados cadastrais = os do contato principal,
// nos dois sentidos; principal nao obrigatorio; no maximo um por pessoa; o mesmo telefone so e principal de uma pessoa
// (tambem no banco); contato com telefone e/ou e-mail; nome preenchido sozinho; contatos dos representantes legais.
// Regra direto no PessoaContatoService e pelo salvar das telas (Cliente, Fornecedor, Profissional) e pela aba Contatos.
// NUNCA fala com o APChat de verdade: o salvar das telas usa um APChat falso em memoria.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA = 'TESTE CONTATO PRINCIPAL';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function saida(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function erro(string $s): string
{
    if (preg_match('/EXCECAO: (.*)/', $s, $m)) { return $m[1]; }
    if (preg_match("/__adianti_error\\(['\"][^'\"]*['\"],\\s*['\"]([^'\"]+)/", $s, $m)) { return $m[1]; }
    return '';
}
function post(array $dados): array { $_POST = $dados; $_REQUEST = $dados; return $dados; }
/** linha da grade de contatos como a tela posta */
function linha(?int $id, string $descricao, string $telefone, string $principal, string $email = '', string $nome = ''): array
{
    $rowId = 'b' . uniqid();
    $item = (object) ['id' => $id, 'nome' => $nome, 'descricao' => $descricao, 'telefone' => $telefone, 'email' => $email, 'principal' => $principal,
        '__row__id' => $rowId, '__display__' => (object) []];
    return ['data' => base64_encode(serialize($item)), 'principal' => $principal, 'row' => $rowId];
}
/** linhas da grade que a tela trocou na tela: [row id => html] */
function linhasTrocadas(string $saida): array
{
    preg_match_all("/ttable_replace_row_by_id\\('pessoa_contato_pessoa_list', '([^']+)', '([^']+)'\\)/", $saida, $m, PREG_SET_ORDER);
    $r = [];
    foreach ($m as $x) { $r[$x[1]] = base64_decode($x[2]); }
    return $r;
}
function grade(array $linhas): array
{
    return ['pessoa_contato_pessoa_list___row__data' => array_column($linhas, 'data'),
            'pessoa_contato_pessoa_list_principal' => array_column($linhas, 'principal')];
}
/** campos da aba Contatos para o "Adicionar" */
function aba(array $campos): array
{
    return $campos + ['pessoa_contato_pessoa_nome' => '', 'pessoa_contato_pessoa_descricao' => '', 'pessoa_contato_pessoa_telefone' => '', 'pessoa_contato_pessoa_email' => '',
        'pessoa_contato_pessoa_principal' => 'N', 'pessoa_contato_pessoa_id' => '', 'pessoa_contato_pessoa__row__id' => ''];
}

// ---------- APChat falso (o salvar das telas sincroniza depois do commit) ----------
$chamadasApchat = 0;
APChatContactService::usarTransporte(function ($rota, $corpo, $token) use (&$chamadasApchat) {
    $chamadasApchat++;
    if ($rota === 'showcontact') { return ['status' => 404, 'corpo' => json_encode(['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND']), 'erro' => '']; }
    return ['status' => 201, 'corpo' => json_encode(['success' => true, 'data' => $corpo + ['id' => 90000 + random_int(1, 9999)]]), 'erro' => ''];
});
TSession::setValue('userid', 1);

// ---------- cenarios direto no servico: tudo dentro de uma transacao desfeita no fim ----------
/** pessoa + contatos [descricao, telefone, principal, email]; devolve [pessoa_id, ids dos contatos] */
function cenario(?string $telefone, array $contatos = [], ?string $email = null, string $nome = MARCA): array
{
    $c = TTransaction::get();
    $st = $c->prepare('INSERT INTO pessoa (nome, tipo_pessoa_id, telefone, email) VALUES (?, ?, ?, ?) RETURNING id');
    $st->execute([$nome, TipoPessoa::FISICA, $telefone, $email]);
    $id = (int) $st->fetchColumn();
    $ids = [];
    foreach ($contatos as $ct)
    {
        [$descricao, $tel, $principal] = $ct;
        $st = $c->prepare('INSERT INTO pessoa_contato (pessoa_id, descricao, telefone, principal, email) VALUES (?, ?, ?, ?, ?) RETURNING id');
        $st->execute([$id, $descricao, $tel, $principal, $ct[3] ?? null]);
        $ids[] = (int) $st->fetchColumn();
    }
    return [$id, $ids];
}
function estado(int $id): array
{
    $c = TTransaction::get();
    $p = $c->query("SELECT telefone, email FROM pessoa WHERE id = {$id}")->fetch(PDO::FETCH_ASSOC);
    $cs = $c->query("SELECT id, nome, descricao, telefone, email, principal, representante_id FROM pessoa_contato WHERE pessoa_id = {$id} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    return ['telefone' => $p['telefone'], 'email' => $p['email'], 'contatos' => $cs, 'principal' => array_values(array_filter($cs, function ($x) { return $x['principal'] === 'S'; }))];
}
function sql(string $s): void { TTransaction::get()->exec($s); }
/** roda $fn numa transacao desfeita no fim (savepoint por cenario) */
function isolado(callable $fn): void
{
    TTransaction::get()->exec('SAVEPOINT cenario');
    try { $fn(); } finally { TTransaction::get()->exec('ROLLBACK TO SAVEPOINT cenario'); }
}
$resumo = function (array $cs) { return array_map(function ($x) { return [$x['telefone'], $x['email'], $x['principal']]; }, $cs); };

igual('valido: celular com mascara', '19998765432', PessoaContatoService::telefoneValido('(19) 99876-5432'));
igual('valido: com 55 na frente', '19998765432', PessoaContatoService::telefoneValido('5519998765432'));
igual('valido: fixo de 10 digitos', '1932345678', PessoaContatoService::telefoneValido('1932345678'));
igual('invalido: 11 digitos sem o 9', null, PessoaContatoService::telefoneValido('19876543210'));
igual('invalido: curto', null, PessoaContatoService::telefoneValido('123'));

TTransaction::open('escritorio');
try {
    isolado(function () use ($resumo) {
        [$p] = cenario('19998760001', [], 'Teste@Exemplo.com', MARCA . ' FULANO');
        PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null));
        $e = estado($p);
        igual('sem contato: cria o principal com o telefone e o e-mail dos Dados', [['19998760001', 'Teste@Exemplo.com', 'S']], $resumo($e['contatos']));
        igual('sem contato: o principal criado leva o nome da pessoa', MARCA . ' FULANO', $e['contatos'][0]['nome']);
    });
    isolado(function () use ($resumo) {
        [$p, [$c1]] = cenario('19998760002', [['CELULAR', '(19) 99876-0002', 'N']], 'a@b.com');
        PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual($p));
        $e = estado($p);
        igual('contato com o telefone e sem e-mail: vira o principal e ganha o e-mail (nada criado, descricao mantida)', [[['19998760002', 'a@b.com', 'S']], 'CELULAR'], [$resumo($e['contatos']), $e['contatos'][0]['descricao']]);
    });
    isolado(function () use ($resumo) {
        [$p, [$c1]] = cenario('19998760003', [['FILHA', '19998760003', 'N', 'filha@b.com']], 'mae@b.com');
        PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual($p));
        igual('contato com o mesmo telefone e outro e-mail: continua nao principal e e criado um principal novo', [['19998760003', 'filha@b.com', 'N'], ['19998760003', 'mae@b.com', 'S']], $resumo(estado($p)['contatos']));
    });
    isolado(function () use ($resumo) {
        [$p, [$c1]] = cenario('19998760004', [['', null, 'N', 'x@b.com']], 'x@b.com');
        PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual($p));
        igual('contato so com o e-mail dos Dados: vira o principal e ganha o telefone', [['19998760004', 'x@b.com', 'S']], $resumo(estado($p)['contatos']));
    });
    isolado(function () {
        [$p, [$c1]] = cenario('19998760005', [['', '19998760005', 'S', 'a@b.com']], 'a@b.com');
        $antes = PessoaContatoService::estadoAtual($p);
        sql("UPDATE pessoa SET telefone = '19998760055', email = 'novo@b.com' WHERE id = {$p}");
        PessoaContatoService::aplicarPrincipal($p, $antes);
        $e = estado($p)['principal'][0];
        igual('mudou o telefone e o e-mail dos Dados: o contato principal acompanha', ['19998760055', 'novo@b.com'], [$e['telefone'], $e['email']]);
    });
    isolado(function () {
        [$p, [$c1, $c2]] = cenario('19998760006', [['', '19998760006', 'S', 'a@b.com'], ['TRABALHO', '1932340006', 'N', 'trab@b.com']], 'a@b.com');
        $antes = PessoaContatoService::estadoAtual($p);
        sql("UPDATE pessoa_contato SET principal = 'N' WHERE id = {$c1}");
        sql("UPDATE pessoa_contato SET principal = 'S' WHERE id = {$c2}");
        PessoaContatoService::aplicarPrincipal($p, $antes);
        $e = estado($p);
        igual('marcou outro principal na aba: telefone e e-mail dos Dados passam a ser os dele', ['1932340006', 'trab@b.com'], [$e['telefone'], $e['email']]);
    });
    isolado(function () use ($resumo) {
        [$p, [$c1]] = cenario('19998760007', [['', '19998760007', 'S', 'a@b.com']], 'a@b.com');
        $antes = PessoaContatoService::estadoAtual($p);
        sql("UPDATE pessoa SET telefone = NULL, email = NULL WHERE id = {$p}");
        PessoaContatoService::aplicarPrincipal($p, $antes);
        igual('apagou telefone e e-mail dos Dados: o contato fica, mas deixa de ser principal', [['19998760007', 'a@b.com', 'N']], $resumo(estado($p)['contatos']));
    });
    isolado(function () {
        [$p, [$c1]] = cenario('19998760008', [['', '19998760008', 'S', 'a@b.com'], ['RECADO', '1932340008', 'N']], 'a@b.com');
        $antes = PessoaContatoService::estadoAtual($p);
        sql("DELETE FROM pessoa_contato WHERE id = {$c1}");
        PessoaContatoService::aplicarPrincipal($p, $antes);
        $e = estado($p);
        igual('excluiu o principal na aba: telefone e e-mail dos Dados ficam vazios (principal nao e obrigatorio)', [null, null, []], [$e['telefone'], $e['email'], $e['principal']]);
    });
    isolado(function () {
        [$p] = cenario(null, [['RECADO', '19998760010', 'N']]);
        PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null));
        igual('sem telefone nos Dados e contato valido nao principal: salva sem principal', [], estado($p)['principal']);
    });
    isolado(function () use ($resumo) {
        [$p] = cenario(null, [], 'so@email.com');
        PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null));
        igual('so e-mail nos Dados: cria o principal so com o e-mail', [[null, 'so@email.com', 'S']], $resumo(estado($p)['contatos']));
    });
    isolado(function () use ($resumo) {
        [$outra] = cenario('19998760011', [['', '19998760011', 'S']], null, MARCA . ' DONO');
        [$p] = cenario('19998760011', [], 'b@b.com');
        $aviso = PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null));
        $e = estado($p);
        igual('telefone dos Dados ja e principal de outra pessoa: fica como contato nao principal e o Telefone principal fica vazio',
            [[['19998760011', 'b@b.com', 'N']], null], [$resumo($e['contatos']), $e['telefone']]);
        ok('telefone de outra pessoa: aviso com o nome do dono', strpos((string) $aviso, MARCA . ' DONO') !== false, (string) $aviso);
        igual('nome sugerido pelo telefone: o do dono do principal', MARCA . ' DONO', PessoaContatoService::nomeDoTelefone('(19) 99876-0011'));
    });
    isolado(function () {
        [$p] = cenario('199876000');
        lanca('telefone dos Dados invalido: bloqueia', function () use ($p) { PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null)); }, 'Telefone inválido');
    });
    isolado(function () {
        [$p] = cenario(null, [['', '123', 'S']]);
        lanca('principal com telefone invalido: bloqueia', function () use ($p) { PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null)); }, 'Telefone inválido');
    });
    // representantes legais
    isolado(function () {
        [$rep] = cenario('19998760020', [['', '19998760020', 'S', 'rep@b.com']], 'rep@b.com', MARCA . ' REPRESENTANTE');
        [$repSem] = cenario(null, [], null, MARCA . ' REP SEM CONTATO');
        [$emp, [$antigo]] = cenario('1932340020', [['', '1932340020', 'S'], ['SOCIO', '19998760020', 'N']]);
        sql("INSERT INTO pessoa_representantes_legais (pessoa_juridica_id, representante_id, descricao, principal) VALUES ({$emp}, {$rep}, 'SÓCIO', 'S'), ({$emp}, {$repSem}, 'GERENTE', 'N')");
        PessoaContatoService::sincronizarRepresentantes($emp);
        $do = array_values(array_filter(estado($emp)['contatos'], function ($x) { return $x['representante_id'] !== null; }));
        igual('representante: o contato com o telefone dele e aproveitado (nome dele, nao principal, e-mail completado); sem duplicar',
            [[MARCA . ' REPRESENTANTE', 'SOCIO', '19998760020', 'rep@b.com', 'N']], array_map(function ($x) { return [$x['nome'], $x['descricao'], $x['telefone'], $x['email'], $x['principal']]; }, $do));
        igual('representante sem contato salvo: nao cria contato', 2, count(estado($emp)['contatos']));
        sql("DELETE FROM pessoa_representantes_legais WHERE pessoa_juridica_id = {$emp} AND representante_id = {$rep}");
        PessoaContatoService::sincronizarRepresentantes($emp);
        igual('representante desvinculado: o contato que veio dele sai', [['1932340020', null, 'S']], array_map(function ($x) { return [$x['telefone'], $x['email'], $x['principal']]; }, estado($emp)['contatos']));
    });
    isolado(function () {
        [$rep] = cenario('19998760021', [['', '19998760021', 'S']], null, MARCA . ' REP NOVO');
        [$emp] = cenario(null);
        sql("INSERT INTO pessoa_representantes_legais (pessoa_juridica_id, representante_id, descricao, principal) VALUES ({$emp}, {$rep}, 'PROCURADOR', 'S')");
        PessoaContatoService::sincronizarRepresentantes($emp);
        $c = estado($emp)['contatos'];
        igual('representante sem contato na pessoa: cria com o nome dele e a descricao do vinculo', [[MARCA . ' REP NOVO', 'PROCURADOR', '19998760021', 'N', $rep]],
            array_map(function ($x) { return [$x['nome'], $x['descricao'], $x['telefone'], $x['principal'], (int) $x['representante_id']]; }, $c));
    });
    // banco
    isolado(function () {
        lanca('banco: dois principais na mesma pessoa e recusado (indice unico)', function () { cenario(null, [['A', '19998760031', 'S'], ['B', '19998760032', 'S']]); }, 'uk_pessoa_contato_principal');
    });
    isolado(function () {
        cenario(null, [['A', '19998760033', 'S']]);
        lanca('banco: o mesmo telefone principal em duas pessoas e recusado (indice unico)', function () { cenario(null, [['B', '19998760033', 'S']]); }, 'uk_pessoa_contato_telefone_principal');
    });
    isolado(function () {
        cenario(null, [['A', '19998760034', 'S']]);
        cenario(null, [['B', '19998760034', 'N']]);
        ok('banco: o mesmo telefone como nao principal em outra pessoa e aceito', true);
    });
    isolado(function () {
        lanca('banco: principal so aceita S ou N', function () { cenario(null, [['A', '19998760035', 'X']]); }, 'ck_pessoa_contato_principal');
    });
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
}

// ---------- pelas telas (gravam de verdade; apagados no fim) ----------
$criados = function () { return array_map('intval', array_column(q("SELECT id FROM pessoa WHERE nome LIKE '" . MARCA . "%'"), 'id')); };
try {
    $base = ['id' => '', 'tipo_pessoa_id' => TipoPessoa::FISICA, 'email' => '', 'cpf_cnpj' => '', 'rg_ie' => ''];

    // Fornecedor novo: telefone e e-mail viram o contato principal, com o nome da pessoa
    $s = saida(function () use ($base) { $p = post(['nome' => MARCA . ' FORNECEDOR', 'telefone' => '(19) 99876-0101', 'email' => 'forn@teste.com'] + $base); (new FornecedorForm($p))->onSave($p); });
    $f = q("SELECT id FROM pessoa WHERE nome = '" . MARCA . " FORNECEDOR'");
    $fid = (int) ($f[0]['id'] ?? 0);
    igual('Fornecedor novo: salvou e criou o principal com telefone, e-mail e nome', [1, ['19998760101', 'forn@teste.com', MARCA . ' FORNECEDOR']],
        [count($f), array_values(q("SELECT telefone, email, nome FROM pessoa_contato WHERE pessoa_id = {$fid} AND principal = 'S'")[0] ?? [erro($s)])]);
    ok('Fornecedor novo: APChat falso chamado (nunca o real)', $chamadasApchat > 0, erro($s));
    ok('Fornecedor novo: depois de salvar a cortina continua no registro (reabre no onEdit, nao fecha)',
        strpos($s, "class=FornecedorForm&method=onEdit&key={$fid}") !== false && strpos($s, 'closeRightPanel') === false, substr($s, -400));

    // Fornecedor com telefone invalido: bloqueia e nao grava nada
    $s = saida(function () use ($base) { $p = post(['nome' => MARCA . ' INVALIDO', 'telefone' => '(19) 8765-432'] + $base); (new FornecedorForm($p))->onSave($p); });
    ok('Fornecedor com telefone invalido: aviso e nada gravado', strpos(erro($s), 'Telefone inválido') !== false && !q("SELECT id FROM pessoa WHERE nome = '" . MARCA . " INVALIDO'"), erro($s));

    // outra pessoa com o mesmo telefone do Fornecedor: salva, mas o numero fica nao principal e o Telefone principal vazio
    $s = saida(function () use ($base) { $p = post(['nome' => MARCA . ' MESMO TELEFONE', 'telefone' => '(19) 99876-0101'] + $base); (new FornecedorForm($p))->onSave($p); });
    $m = q("SELECT p.telefone, c.telefone AS contato, c.principal FROM pessoa p JOIN pessoa_contato c ON c.pessoa_id = p.id WHERE p.nome = '" . MARCA . " MESMO TELEFONE'");
    igual('telefone que ja e principal de outra pessoa: contato nao principal e Telefone principal vazio', [[null, '19998760101', 'N']], array_map('array_values', $m));
    ok('telefone que ja e principal de outra pessoa: aviso na tela', strpos($s, MARCA . ' FORNECEDOR') !== false, erro($s) ?: substr($s, 0, 300));

    // Cliente: Dados sem telefone, aba com 2 contatos e o segundo marcado como principal -> Dados recebem telefone e e-mail dele
    $p = ['nome' => MARCA . ' CLIENTE', 'telefone' => ''] + $base + grade([linha(null, 'CASA', '1932340102', 'N'), linha(null, 'CELULAR', '19998760102', 'S', 'cel@teste.com')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    $c = q("SELECT id, telefone, email FROM pessoa WHERE nome = '" . MARCA . " CLIENTE'");
    $cid = (int) ($c[0]['id'] ?? 0);
    igual('Cliente: principal escolhido na aba vai para os Dados (telefone e e-mail)', ['19998760102', 'cel@teste.com'], [$c[0]['telefone'] ?? erro($s), $c[0]['email'] ?? null]);
    $contatos = q("SELECT id, descricao, principal, nome FROM pessoa_contato WHERE pessoa_id = {$cid} ORDER BY id");
    igual('Cliente: os 2 contatos gravados, so o CELULAR principal (e com o nome da pessoa)', [['CASA', 'N', null], ['CELULAR', 'S', MARCA . ' CLIENTE']], array_map(function ($x) { return [$x['descricao'], $x['principal'], $x['nome']]; }, $contatos));

    // Cliente editado: trocar o principal na aba (do CELULAR para a CASA) nao bate no indice unico e troca os Dados
    [$casa, $cel] = array_map('intval', array_column($contatos, 'id'));
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '19998760102', 'email' => 'cel@teste.com'] + $base + grade([linha($casa, 'CASA', '1932340102', 'S'), linha($cel, 'CELULAR', '19998760102', 'N', 'cel@teste.com')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    igual('Cliente editado: trocou o principal na aba e os Dados acompanharam (sem e-mail no novo principal)', ['1932340102', null, $casa],
        [q("SELECT telefone FROM pessoa WHERE id = {$cid}")[0]['telefone'], q("SELECT email FROM pessoa WHERE id = {$cid}")[0]['email'], (int) (q("SELECT id FROM pessoa_contato WHERE pessoa_id = {$cid} AND principal = 'S'")[0]['id'] ?? 0)]);

    // Cliente editado: mudar so o telefone e o e-mail dos Dados muda o contato principal
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '(19) 3234-0199', 'email' => 'casa@teste.com'] + $base + grade([linha($casa, 'CASA', '1932340102', 'S'), linha($cel, 'CELULAR', '19998760102', 'N', 'cel@teste.com')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    igual('Cliente editado: mudou telefone e e-mail dos Dados e o principal acompanhou', ['1932340199', 'casa@teste.com'], array_values(q("SELECT telefone, email FROM pessoa_contato WHERE id = {$casa}")[0] ?? [erro($s)]));

    // dois principais marcados na aba: bloqueia
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199'] + $base + grade([linha($casa, 'CASA', '1932340199', 'S'), linha($cel, 'CELULAR', '19998760102', 'S')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    ok('Cliente: dois principais na aba bloqueia', strpos(erro($s), 'apenas um contato como principal') !== false, erro($s));

    // Cliente com representante legal: o contato dele entra na aba como nao principal; desvinculado, sai
    $s = saida(function () use ($base) { $p = post(['nome' => MARCA . ' REPRESENTANTE', 'telefone' => '(19) 99876-0150', 'email' => 'rep@teste.com'] + $base); (new FornecedorForm($p))->onSave($p); });
    $rid = (int) (q("SELECT id FROM pessoa WHERE nome = '" . MARCA . " REPRESENTANTE'")[0]['id'] ?? 0);
    $rep = (object) ['id' => null, 'descricao' => 'SÓCIO', 'representante_id' => $rid, 'principal' => 'S', '__row__id' => 'brep', '__display__' => (object) []];
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199', 'email' => 'casa@teste.com'] + $base + grade([linha($casa, 'CASA', '1932340199', 'S', 'casa@teste.com'), linha($cel, 'CELULAR', '19998760102', 'N', 'cel@teste.com')])
        + ['pessoa_representantes_legais_pessoa_juridica_list___row__data' => [base64_encode(serialize($rep))], 'pessoa_representantes_legais_pessoa_juridica__row__id' => ['brep'], 'pessoa_representantes_legais_pessoa_juridica_principal' => ['S']];
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    igual('Cliente com representante: contato dele criado (nome, descricao do vinculo, nao principal)', [[MARCA . ' REPRESENTANTE', 'SÓCIO', '19998760150', 'rep@teste.com', 'N']],
        array_map('array_values', q("SELECT nome, descricao, telefone, email, principal FROM pessoa_contato WHERE pessoa_id = {$cid} AND representante_id = {$rid}")) ?: [erro($s)]);
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199', 'email' => 'casa@teste.com'] + $base + grade([linha($casa, 'CASA', '1932340199', 'S', 'casa@teste.com'), linha($cel, 'CELULAR', '19998760102', 'N', 'cel@teste.com')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    igual('Cliente sem o representante: o contato que veio dele sai', 0, count(q("SELECT 1 FROM pessoa_contato WHERE pessoa_id = {$cid} AND representante_id = {$rid}")));

    // Profissional: salvar pela tela acrescenta o grupo Profissional e nao tira os outros (o Cliente acima continua Cliente)
    $tipoProfissional = (int) (q('SELECT id FROM tipo_profissional ORDER BY id LIMIT 1')[0]['id'] ?? 0);
    TTransaction::open('permission');
    $usados = array_map('intval', array_column(q('SELECT DISTINCT system_users_id FROM pessoa WHERE system_users_id IS NOT NULL'), 'system_users_id'));
    $livres = TTransaction::get()->query('SELECT id FROM system_users ORDER BY id DESC')->fetchAll(PDO::FETCH_COLUMN);
    TTransaction::close();
    $usuarioLivre = (int) (array_values(array_diff(array_map('intval', $livres), $usados))[0] ?? 0);
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199', 'email' => 'casa@teste.com', 'tipo_profissional_id' => $tipoProfissional, 'system_users_id' => $usuarioLivre,
        'registro_profissional' => '123456', 'orgao_registro_profissional' => 'OAB/SP']
        + $base + grade([linha($casa, 'CASA', '1932340199', 'S', 'casa@teste.com'), linha($cel, 'CELULAR', '19998760102', 'N')]);
    $s = saida(function () use ($p) { post($p); (new ProfissionalForm($p))->onSave($p); });
    $grupos = array_map('intval', array_column(q("SELECT grupo_id FROM pessoa_grupo WHERE pessoa_id = {$cid} ORDER BY grupo_id"), 'grupo_id'));
    $esperado = [Grupo::CLIENTE, Grupo::PROFISSIONAL, Grupo::PARCEIRO, Grupo::FORNECEDOR]; sort($esperado);
    igual('Profissional: salvar mantem o grupo Cliente e acrescenta o Profissional (com Parceiro e Fornecedor)', $esperado, $grupos);
    ok('Profissional: salvou sem erro', erro($s) === '', erro($s));

    // na aba, antes de salvar: marcar um contato como principal tira a marca do antigo e troca telefone e e-mail dos Dados
    $antigo = linha(501, 'CASA', '1932340301', 'S');
    $outro = linha(502, 'RECADO', '1932340302', 'N');
    $p = post(aba(['nome' => MARCA . ' ABA', 'pessoa_contato_pessoa_descricao' => 'CELULAR', 'pessoa_contato_pessoa_telefone' => '(19) 99876-0300', 'pessoa_contato_pessoa_email' => 'aba@teste.com',
        'pessoa_contato_pessoa_principal' => 'S']) + grade([$antigo, $outro]));
    $s = saida(function () use ($p) { (new ClienteForm([]))->onAddDetailPessoaContatoPessoa($p); });
    $trocadas = linhasTrocadas($s);
    ok('aba: o antigo principal volta como Nao na grade', isset($trocadas[$antigo['row']]) && strpos($trocadas[$antigo['row']], 'Não') !== false && strpos($trocadas[$antigo['row']], 'Sim') === false, erro($s) ?: implode(',', array_keys($trocadas)));
    ok('aba: o contato que nao era principal nao e mexido', !isset($trocadas[$outro['row']]));
    ok('aba: o contato novo entra como principal, com o nome da pessoa', (bool) array_filter($trocadas, function ($html, $id) use ($antigo) { return $id !== $antigo['row'] && strpos($html, 'CELULAR') !== false && strpos($html, 'Sim') !== false && strpos($html, MARCA . ' ABA') !== false; }, ARRAY_FILTER_USE_BOTH));
    ok('aba: telefone e e-mail dos Dados passam a ser os do novo principal', preg_match("/telefone.{0,10}\\(19\\) 99876-0300/", $s) === 1 && strpos($s, 'aba@teste.com') !== false, substr($s, 0, 300));

    // contato so com e-mail, sem descricao: aceito; sem telefone e sem e-mail: recusado
    $s = saida(function () { $p = post(aba(['pessoa_contato_pessoa_email' => 'so@email.com'])); (new ClienteForm([]))->onAddDetailPessoaContatoPessoa($p); });
    ok('aba: contato so com e-mail e sem descricao e aceito', erro($s) === '' && count(linhasTrocadas($s)) === 1, erro($s));
    $s = saida(function () { $p = post(aba(['pessoa_contato_pessoa_descricao' => 'NADA'])); (new ClienteForm([]))->onAddDetailPessoaContatoPessoa($p); });
    ok('aba: contato sem telefone e sem e-mail e recusado', strpos(erro($s), 'telefone ou o e-mail') !== false && !linhasTrocadas($s), erro($s));

    // telefone que ja e principal de outra pessoa: nao pode ser marcado como principal; como nao principal pega o nome do dono
    $s = saida(function () use ($cid) { $p = post(aba(['id' => $cid, 'pessoa_contato_pessoa_telefone' => '19998760101', 'pessoa_contato_pessoa_principal' => 'S'])); (new ClienteForm([]))->onAddDetailPessoaContatoPessoa($p); });
    ok('aba: principal com telefone que e principal de outra pessoa e recusado', strpos(erro($s), MARCA . ' FORNECEDOR') !== false && !linhasTrocadas($s), erro($s));
    $s = saida(function () use ($cid) { $p = post(aba(['id' => $cid, 'pessoa_contato_pessoa_telefone' => '19998760101'])); (new ClienteForm([]))->onAddDetailPessoaContatoPessoa($p); });
    ok('aba: o mesmo telefone como nao principal entra com o nome do dono', erro($s) === '' && strpos(implode('', linhasTrocadas($s)), MARCA . ' FORNECEDOR') !== false, erro($s));
    $s = saida(function () { TSession::setValue('x', 1); (new ClienteForm([])); ClienteForm::onTelefoneContatoSaida(['pessoa_contato_pessoa_telefone' => '(19) 99876-0101', 'pessoa_contato_pessoa_nome' => '']); });
    ok('aba: ao sair do telefone sugere o nome do dono do principal', strpos($s, MARCA . ' FORNECEDOR') !== false, erro($s) ?: substr($s, 0, 200));

    // grade salva com um principal cujo telefone e principal de outra pessoa (aba antiga aberta): mensagem clara, nada gravado
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199', 'email' => 'casa@teste.com'] + $base + grade([linha($casa, 'CASA', '1932340199', 'N', 'casa@teste.com'), linha(null, 'OUTRO', '19998760101', 'S')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    ok('Cliente: salvar com principal de telefone de outra pessoa mostra mensagem clara', strpos(erro($s), 'já é o contato principal de ' . MARCA . ' FORNECEDOR') !== false, erro($s));

    // Cliente salvo na cortina: continua no registro e na mesma aba
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199', 'email' => 'casa@teste.com', 'current_tab' => '2'] + $base + grade([linha($casa, 'CASA', '1932340199', 'S', 'casa@teste.com'), linha($cel, 'CELULAR', '19998760102', 'N')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    ok('Cliente salvo: cortina continua no registro, na aba em que estava', strpos($s, "class=ClienteForm&method=onEdit&key={$cid}") !== false && strpos($s, 'current_tab=2') !== false, erro($s) ?: substr($s, -400));

    // a aba Contatos com Nome e a chave Principal aparece nos 6 cadastros, e o rotulo Telefone principal nos Dados
    foreach (['ClienteForm', 'ContraparteForm', 'RepresentanteLegalForm', 'FornecedorForm', 'ParceiroForm', 'ProfissionalForm'] as $tela)
    {
        $html = saida(function () use ($tela) { (new $tela([]))->show(); });
        ok("{$tela}: aba Contatos com Nome e Principal; Telefone principal nos Dados", strpos($html, 'pessoa_contato_pessoa_principal') !== false && strpos($html, 'pessoa_contato_pessoa_nome') !== false
            && strpos($html, '>Contatos<') !== false && strpos($html, 'Telefone principal:') !== false, erro($html) ?: substr(strip_tags($html), 0, 120));
    }
} finally {
    APChatContactService::usarTransporte(null);
    if ($ids = $criados())
    {
        $lista = implode(',', $ids);
        TTransaction::open('escritorio');
        $c = TTransaction::get();
        $c->exec("DELETE FROM pessoa_representantes_legais WHERE pessoa_juridica_id IN ({$lista}) OR representante_id IN ({$lista})");
        foreach (['apchat_contato_log', 'pessoa_contato', 'pessoa_endereco', 'pessoa_grupo', 'pessoa_especialidade', 'classificacoes_cliente'] as $tabela)
        {
            $c->exec("DELETE FROM {$tabela} WHERE pessoa_id IN ({$lista})");
        }
        $c->exec("DELETE FROM pessoa WHERE id IN ({$lista})");
        TTransaction::close();
    }
}
fim();
