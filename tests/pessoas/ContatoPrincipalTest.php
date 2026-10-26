<?php
// tests/pessoas/ContatoPrincipalTest.php
// Contato principal (26/10/2026): telefone dos Dados cadastrais = telefone do contato principal, nos dois sentidos;
// telefone invalido bloqueia; no maximo um principal (tambem no banco); obrigatorio quando ha contato com telefone valido.
// Regra direto no PessoaContatoService e pelo salvar das telas (Cliente, Fornecedor) e do cadastro pelo agendamento.
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
function linha(?int $id, string $descricao, string $telefone, string $principal): array
{
    $rowId = 'b' . uniqid();
    $item = (object) ['id' => $id, 'descricao' => $descricao, 'telefone' => $telefone, 'email' => '', 'principal' => $principal,
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

// ---------- APChat falso (o salvar das telas sincroniza depois do commit) ----------
$chamadasApchat = 0;
APChatContactService::usarTransporte(function ($rota, $corpo, $token) use (&$chamadasApchat) {
    $chamadasApchat++;
    if ($rota === 'showcontact') { return ['status' => 404, 'corpo' => json_encode(['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND']), 'erro' => '']; }
    return ['status' => 201, 'corpo' => json_encode(['success' => true, 'data' => $corpo + ['id' => 90000 + random_int(1, 9999)]]), 'erro' => ''];
});
TSession::setValue('userid', 1);

// ---------- cenarios direto no servico: tudo dentro de uma transacao desfeita no fim ----------
/** pessoa + contatos [descricao, telefone, principal]; devolve [pessoa_id, ids dos contatos] */
function cenario(?string $telefone, array $contatos = []): array
{
    $c = TTransaction::get();
    $st = $c->prepare('INSERT INTO pessoa (nome, tipo_pessoa_id, telefone) VALUES (?, ?, ?) RETURNING id');
    $st->execute([MARCA, TipoPessoa::FISICA, $telefone]);
    $id = (int) $st->fetchColumn();
    $ids = [];
    foreach ($contatos as [$descricao, $tel, $principal])
    {
        $st = $c->prepare('INSERT INTO pessoa_contato (pessoa_id, descricao, telefone, principal) VALUES (?, ?, ?, ?) RETURNING id');
        $st->execute([$id, $descricao, $tel, $principal]);
        $ids[] = (int) $st->fetchColumn();
    }
    return [$id, $ids];
}
function estado(int $id): array
{
    $c = TTransaction::get();
    $tel = $c->query("SELECT telefone FROM pessoa WHERE id = {$id}")->fetchColumn();
    $cs = $c->query("SELECT id, descricao, telefone, principal FROM pessoa_contato WHERE pessoa_id = {$id} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    return ['telefone' => $tel, 'contatos' => $cs, 'principal' => array_values(array_filter($cs, function ($x) { return $x['principal'] === 'S'; }))];
}
function sql(string $s): void { TTransaction::get()->exec($s); }
/** roda $fn numa transacao desfeita no fim (savepoint por cenario) */
function isolado(callable $fn): void
{
    TTransaction::get()->exec('SAVEPOINT cenario');
    try { $fn(); } finally { TTransaction::get()->exec('ROLLBACK TO SAVEPOINT cenario'); }
}

igual('valido: celular com mascara', '19998765432', PessoaContatoService::telefoneValido('(19) 99876-5432'));
igual('valido: com 55 na frente', '19998765432', PessoaContatoService::telefoneValido('5519998765432'));
igual('valido: fixo de 10 digitos', '1932345678', PessoaContatoService::telefoneValido('1932345678'));
igual('invalido: 11 digitos sem o 9', null, PessoaContatoService::telefoneValido('19876543210'));
igual('invalido: curto', null, PessoaContatoService::telefoneValido('123'));

TTransaction::open('escritorio');
try {
    isolado(function () {
        [$p] = cenario('19998760001');
        PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null));
        $e = estado($p);
        igual('sem contato: cria o principal com o telefone dos Dados e descricao vazia', [['19998760001', '', 'S']],
            array_map(function ($x) { return [$x['telefone'], $x['descricao'], $x['principal']]; }, $e['contatos']));
    });
    isolado(function () {
        [$p, [$casa, $semDesc]] = cenario('19998760002', [['CASA', '5519998760002', 'N'], ['', '(19) 99876-0002', 'N']]);
        $antes = PessoaContatoService::estadoAtual($p);
        PessoaContatoService::aplicarPrincipal($p, $antes);
        $e = estado($p);
        igual('mesmo numero em 2 contatos: vira principal o sem descricao, nada criado', [$semDesc, 2], [(int) ($e['principal'][0]['id'] ?? 0), count($e['contatos'])]);
    });
    isolado(function () {
        [$p, [$c1]] = cenario('19998760003', [['', '19998760003', 'S']]);
        $antes = PessoaContatoService::estadoAtual($p);
        sql("UPDATE pessoa SET telefone = '19998760033' WHERE id = {$p}");
        PessoaContatoService::aplicarPrincipal($p, $antes);
        igual('mudou o telefone dos Dados: o contato principal acompanha', '19998760033', estado($p)['principal'][0]['telefone']);
    });
    isolado(function () {
        [$p, [$c1, $c2]] = cenario('19998760004', [['', '19998760004', 'S'], ['TRABALHO', '1932340004', 'N']]);
        $antes = PessoaContatoService::estadoAtual($p);
        sql("UPDATE pessoa_contato SET principal = 'N' WHERE id = {$c1}");
        sql("UPDATE pessoa_contato SET principal = 'S' WHERE id = {$c2}");
        PessoaContatoService::aplicarPrincipal($p, $antes);
        igual('marcou outro principal na aba: o telefone dos Dados passa a ser o dele', '1932340004', estado($p)['telefone']);
    });
    isolado(function () {
        [$p, [$c1, $c2]] = cenario('19998760005', [['', '19998760005', 'S'], ['TRABALHO', '1932340005', 'N']]);
        $antes = PessoaContatoService::estadoAtual($p);
        sql("UPDATE pessoa SET telefone = '19998769999' WHERE id = {$p}");
        sql("UPDATE pessoa_contato SET principal = 'N' WHERE id = {$c1}");
        sql("UPDATE pessoa_contato SET principal = 'S' WHERE id = {$c2}");
        PessoaContatoService::aplicarPrincipal($p, $antes);
        $e = estado($p);
        igual('mudaram os dois: vale a aba (Dados = principal novo; contato antigo intacto)', ['1932340005', '19998760005'], [$e['telefone'], $e['contatos'][0]['telefone']]);
    });
    isolado(function () {
        [$p, [$c1]] = cenario('19998760006', [['', '19998760006', 'S']]);
        $antes = PessoaContatoService::estadoAtual($p);
        sql("UPDATE pessoa SET telefone = NULL WHERE id = {$p}");
        lanca('apagou o telefone dos Dados com principal: bloqueia', function () use ($p, $antes) { PessoaContatoService::aplicarPrincipal($p, $antes); }, 'exclua o contato principal');
    });
    isolado(function () {
        [$p, [$c1]] = cenario('19998760007', [['', '19998760007', 'S'], ['RECADO', '(19) 3234-0007', 'N']]);
        $antes = PessoaContatoService::estadoAtual($p);
        sql("DELETE FROM pessoa_contato WHERE id = {$c1}");
        lanca('excluiu o principal e sobrou contato valido: pede outro principal', function () use ($p, $antes) { PessoaContatoService::aplicarPrincipal($p, $antes); }, 'Selecione um contato como principal');
    });
    isolado(function () {
        [$p, [$c1]] = cenario('19998760008', [['', '19998760008', 'S'], ['RECADO', '123', 'N']]);
        $antes = PessoaContatoService::estadoAtual($p);
        sql("DELETE FROM pessoa_contato WHERE id = {$c1}");
        PessoaContatoService::aplicarPrincipal($p, $antes);
        igual('excluiu o principal e nao sobrou contato valido: telefone dos Dados fica vazio', null, estado($p)['telefone']);
    });
    isolado(function () {
        [$p] = cenario('199876000');
        lanca('telefone dos Dados invalido: bloqueia', function () use ($p) { PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null)); }, 'Telefone inválido');
    });
    isolado(function () {
        [$p] = cenario(null, [['', '123', 'S']]);
        lanca('principal com telefone invalido: bloqueia', function () use ($p) { PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null)); }, 'precisa ter um telefone válido');
    });
    isolado(function () {
        [$p] = cenario(null, [['RECADO', '19998760010', 'N']]);
        lanca('sem telefone nos Dados e contato valido sem principal: bloqueia', function () use ($p) { PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null)); }, 'Selecione um contato como principal');
    });
    isolado(function () {
        [$p] = cenario(null, [['ANTIGO', '123', 'N']]);
        PessoaContatoService::aplicarPrincipal($p, PessoaContatoService::estadoAtual(null));
        igual('sem telefone e sem contato valido: salva sem principal', [], estado($p)['principal']);
    });
    isolado(function () {
        lanca('banco: dois principais na mesma pessoa e recusado (indice unico)', function () { cenario(null, [['A', '19998760011', 'S'], ['B', '19998760012', 'S']]); }, 'uk_pessoa_contato_principal');
    });
    isolado(function () {
        lanca('banco: principal so aceita S ou N', function () { cenario(null, [['A', '19998760013', 'X']]); }, 'ck_pessoa_contato_principal');
    });
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
}

// ---------- pelas telas (gravam de verdade; apagados no fim) ----------
$criados = function () { return array_map('intval', array_column(q("SELECT id FROM pessoa WHERE nome LIKE '" . MARCA . "%'"), 'id')); };
try {
    $base = ['id' => '', 'tipo_pessoa_id' => TipoPessoa::FISICA, 'email' => '', 'cpf_cnpj' => '', 'rg_ie' => ''];

    // Fornecedor (aba Contatos nova): telefone novo vira o contato principal
    $s = saida(function () use ($base) { $p = post($base + ['nome' => MARCA . ' FORNECEDOR', 'telefone' => '(19) 99876-0101']); (new FornecedorForm($p))->onSave($p); });
    $f = q("SELECT id FROM pessoa WHERE nome = '" . MARCA . " FORNECEDOR'");
    $fid = (int) ($f[0]['id'] ?? 0);
    igual('Fornecedor novo: salvou e criou o contato principal', [1, '19998760101'], [count($f), q("SELECT telefone FROM pessoa_contato WHERE pessoa_id = {$fid} AND principal = 'S'")[0]['telefone'] ?? null]);
    ok('Fornecedor novo: APChat falso chamado (nunca o real)', $chamadasApchat > 0, erro($s));
    ok('Fornecedor novo: depois de salvar a cortina continua no registro (reabre no onEdit, nao fecha)',
        strpos($s, "class=FornecedorForm&method=onEdit&key={$fid}") !== false && strpos($s, 'closeRightPanel') === false, substr($s, -400));

    // Fornecedor com telefone invalido: bloqueia e nao grava nada
    $s = saida(function () use ($base) { $p = post($base + ['nome' => MARCA . ' INVALIDO', 'telefone' => '(19) 8765-432']); (new FornecedorForm($p))->onSave($p); });
    ok('Fornecedor com telefone invalido: aviso e nada gravado', strpos(erro($s), 'Telefone inválido') !== false && !q("SELECT id FROM pessoa WHERE nome = '" . MARCA . " INVALIDO'"), erro($s));

    // Cliente: Dados sem telefone, aba com 2 contatos e o segundo marcado como principal -> Dados recebem o telefone dele
    $p = $base + ['nome' => MARCA . ' CLIENTE', 'telefone' => ''] + grade([linha(null, 'CASA', '1932340102', 'N'), linha(null, 'CELULAR', '19998760102', 'S')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    $c = q("SELECT id, telefone FROM pessoa WHERE nome = '" . MARCA . " CLIENTE'");
    $cid = (int) ($c[0]['id'] ?? 0);
    igual('Cliente: principal escolhido na aba vai para os Dados cadastrais', '19998760102', $c[0]['telefone'] ?? erro($s));
    $contatos = q("SELECT id, descricao, principal FROM pessoa_contato WHERE pessoa_id = {$cid} ORDER BY id");
    igual('Cliente: os 2 contatos gravados, so o CELULAR principal', [['CASA', 'N'], ['CELULAR', 'S']], array_map(function ($x) { return [$x['descricao'], $x['principal']]; }, $contatos));

    // Cliente editado: trocar o principal na aba (do CELULAR para a CASA) nao bate no indice unico e troca os Dados
    [$casa, $cel] = array_map('intval', array_column($contatos, 'id'));
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '19998760102'] + $base + grade([linha($casa, 'CASA', '1932340102', 'S'), linha($cel, 'CELULAR', '19998760102', 'N')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    igual('Cliente editado: trocou o principal na aba e os Dados acompanharam', ['1932340102', $casa], [q("SELECT telefone FROM pessoa WHERE id = {$cid}")[0]['telefone'], (int) (q("SELECT id FROM pessoa_contato WHERE pessoa_id = {$cid} AND principal = 'S'")[0]['id'] ?? 0)]);

    // Cliente editado: mudar so o telefone dos Dados muda o contato principal
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '(19) 3234-0199'] + $base + grade([linha($casa, 'CASA', '1932340102', 'S'), linha($cel, 'CELULAR', '19998760102', 'N')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    igual('Cliente editado: mudou o telefone dos Dados e o principal acompanhou', '1932340199', q("SELECT telefone FROM pessoa_contato WHERE id = {$casa}")[0]['telefone'] ?? erro($s));

    // dois principais marcados na aba: bloqueia
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199'] + $base + grade([linha($casa, 'CASA', '1932340199', 'S'), linha($cel, 'CELULAR', '19998760102', 'S')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    ok('Cliente: dois principais na aba bloqueia', strpos(erro($s), 'apenas um contato como principal') !== false, erro($s));

    // Profissional: salvar pela tela acrescenta o grupo Profissional e nao tira os outros (o Cliente acima continua Cliente)
    $tipoProfissional = (int) (q('SELECT id FROM tipo_profissional ORDER BY id LIMIT 1')[0]['id'] ?? 0);
    TTransaction::open('permission');
    $usados = array_map('intval', array_column(q('SELECT DISTINCT system_users_id FROM pessoa WHERE system_users_id IS NOT NULL'), 'system_users_id'));
    $livres = TTransaction::get()->query('SELECT id FROM system_users ORDER BY id DESC')->fetchAll(PDO::FETCH_COLUMN);
    TTransaction::close();
    $usuarioLivre = (int) (array_values(array_diff(array_map('intval', $livres), $usados))[0] ?? 0);
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199', 'tipo_profissional_id' => $tipoProfissional, 'system_users_id' => $usuarioLivre,
        'registro_profissional' => '123456', 'orgao_registro_profissional' => 'OAB/SP']
        + $base + grade([linha($casa, 'CASA', '1932340199', 'S'), linha($cel, 'CELULAR', '19998760102', 'N')]);
    $s = saida(function () use ($p) { post($p); (new ProfissionalForm($p))->onSave($p); });
    $grupos = array_map('intval', array_column(q("SELECT grupo_id FROM pessoa_grupo WHERE pessoa_id = {$cid} ORDER BY grupo_id"), 'grupo_id'));
    // Profissional traz junto Parceiro e Fornecedor (PessoaGrupo::GRUPOS_IMPLICITOS)
    $esperado = [Grupo::CLIENTE, Grupo::PROFISSIONAL, Grupo::PARCEIRO, Grupo::FORNECEDOR]; sort($esperado);
    igual('Profissional: salvar mantem o grupo Cliente e acrescenta o Profissional (com Parceiro e Fornecedor)', $esperado, $grupos);
    ok('Profissional: salvou sem erro', erro($s) === '', erro($s));
    $s = saida(function () use ($p) { post($p); (new ProfissionalForm($p))->onSave($p); });
    igual('Profissional: salvar de novo nao duplica grupo', 4, count(q("SELECT 1 FROM pessoa_grupo WHERE pessoa_id = {$cid}")));

    // na aba, antes de salvar: marcar um contato como principal tira a marca do antigo e troca o telefone dos Dados
    $antigo = linha(501, 'CASA', '1932340301', 'S');
    $outro = linha(502, 'RECADO', '1932340302', 'N');
    $p = post(['pessoa_contato_pessoa_descricao' => 'CELULAR', 'pessoa_contato_pessoa_telefone' => '(19) 99876-0300', 'pessoa_contato_pessoa_email' => '',
        'pessoa_contato_pessoa_principal' => 'S', 'pessoa_contato_pessoa_id' => '', 'pessoa_contato_pessoa__row__id' => ''] + grade([$antigo, $outro]));
    $s = saida(function () use ($p) { (new ClienteForm([]))->onAddDetailPessoaContatoPessoa($p); });
    $trocadas = linhasTrocadas($s);
    ok('aba: o antigo principal volta como Nao na grade', isset($trocadas[$antigo['row']]) && strpos($trocadas[$antigo['row']], 'Não') !== false && strpos($trocadas[$antigo['row']], 'Sim') === false, erro($s) ?: implode(',', array_keys($trocadas)));
    ok('aba: o contato que nao era principal nao e mexido', !isset($trocadas[$outro['row']]));
    ok('aba: o contato novo entra como principal', (bool) array_filter($trocadas, function ($html, $id) use ($antigo) { return $id !== $antigo['row'] && strpos($html, 'CELULAR') !== false && strpos($html, 'Sim') !== false; }, ARRAY_FILTER_USE_BOTH));
    ok('aba: telefone dos Dados cadastrais passa a ser o do novo principal', preg_match("/telefone.{0,10}\\(19\\) 99876-0300/", $s) === 1, substr($s, 0, 300));

    // contato que nao e principal: nada muda nas outras linhas nem nos Dados
    $p = post(['pessoa_contato_pessoa_descricao' => 'TRABALHO', 'pessoa_contato_pessoa_telefone' => '1932340303', 'pessoa_contato_pessoa_email' => '',
        'pessoa_contato_pessoa_principal' => 'N', 'pessoa_contato_pessoa_id' => '', 'pessoa_contato_pessoa__row__id' => ''] + grade([$antigo, $outro]));
    $s = saida(function () use ($p) { (new ClienteForm([]))->onAddDetailPessoaContatoPessoa($p); });
    ok('aba: contato nao principal nao mexe no principal nem nos Dados', count(linhasTrocadas($s)) === 1 && !isset(linhasTrocadas($s)[$antigo['row']]) && strpos($s, '3234-0303') === false, erro($s));

    // descricao: obrigatoria so para quem nao e o principal
    $p = post(['pessoa_contato_pessoa_descricao' => '', 'pessoa_contato_pessoa_telefone' => '19998760304', 'pessoa_contato_pessoa_email' => '',
        'pessoa_contato_pessoa_principal' => 'S', 'pessoa_contato_pessoa_id' => '', 'pessoa_contato_pessoa__row__id' => '']);
    $s = saida(function () use ($p) { (new ClienteForm([]))->onAddDetailPessoaContatoPessoa($p); });
    ok('aba: principal sem descricao e aceito', erro($s) === '' && count(linhasTrocadas($s)) === 1, erro($s));
    $p = post(['pessoa_contato_pessoa_principal' => 'N'] + $p);
    $s = saida(function () use ($p) { (new ClienteForm([]))->onAddDetailPessoaContatoPessoa($p); });
    ok('aba: contato que nao e principal sem descricao e recusado', strpos(erro($s), 'Descrição') !== false && !linhasTrocadas($s), erro($s));

    // principal sem descricao salvo pela tela (era erro de banco: descricao NULL na coluna NOT NULL)
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199'] + $base + grade([linha($casa, '', '1932340199', 'S'), linha($cel, 'CELULAR', '19998760102', 'N')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    ok('Cliente: salva com o principal sem descricao', erro($s) === '' && in_array(q("SELECT descricao FROM pessoa_contato WHERE id = {$casa}")[0]['descricao'] ?? 'x', [null, ''], true), erro($s));
    // como a tela do usuario mandou (descricao NULL vinda do banco): o UPDATE com descricao = NULL agora passa
    $item = (object) ['id' => $casa, 'pessoa_id' => $cid, 'descricao' => null, 'telefone' => '1932340199', 'email' => null, 'principal' => 'S', '__row__id' => 'bnulo', '__display__' => (object) []];
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199'] + $base + ['pessoa_contato_pessoa_list___row__data' => [base64_encode(serialize($item)), linha($cel, 'CELULAR', '19998760102', 'N')['data']], 'pessoa_contato_pessoa_list_principal' => ['S', 'N']];
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    ok('Cliente: salva com o principal de descricao NULL (o caso do erro de banco)', erro($s) === '' && q("SELECT descricao FROM pessoa_contato WHERE id = {$casa}")[0]['descricao'] === null, erro($s));

    // Cliente salvo na cortina: continua no registro e na mesma aba
    $p = ['id' => $cid, 'nome' => MARCA . ' CLIENTE', 'telefone' => '1932340199', 'current_tab' => '2'] + $base + grade([linha($casa, 'CASA', '1932340199', 'S'), linha($cel, 'CELULAR', '19998760102', 'N')]);
    $s = saida(function () use ($p) { post($p); (new ClienteForm($p))->onSave($p); });
    ok('Cliente salvo: cortina continua no registro, na aba em que estava', strpos($s, "class=ClienteForm&method=onEdit&key={$cid}") !== false && strpos($s, 'current_tab=2') !== false, erro($s) ?: substr($s, -400));

    // a aba Contatos com a chave Principal aparece nos 6 cadastros
    foreach (['ClienteForm', 'ContraparteForm', 'RepresentanteLegalForm', 'FornecedorForm', 'ParceiroForm', 'ProfissionalForm'] as $tela)
    {
        $html = saida(function () use ($tela) { (new $tela([]))->show(); });
        ok("{$tela}: aba Contatos com a chave Principal", strpos($html, 'pessoa_contato_pessoa_principal') !== false && strpos($html, '>Contatos<') !== false, erro($html) ?: substr(strip_tags($html), 0, 120));
    }
} finally {
    APChatContactService::usarTransporte(null);
    if ($ids = $criados())
    {
        $lista = implode(',', $ids);
        TTransaction::open('escritorio');
        $c = TTransaction::get();
        foreach (['apchat_contato_log', 'pessoa_contato', 'pessoa_endereco', 'pessoa_grupo', 'pessoa_especialidade', 'classificacoes_cliente'] as $tabela)
        {
            $c->exec("DELETE FROM {$tabela} WHERE pessoa_id IN ({$lista})");
        }
        $c->exec("DELETE FROM pessoa WHERE id IN ({$lista})");
        TTransaction::close();
    }
}
fim();
