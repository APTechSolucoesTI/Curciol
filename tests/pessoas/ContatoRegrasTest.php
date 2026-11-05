<?php
// tests/pessoas/ContatoRegrasTest.php
// 05/11/2026: regras dos contatos. O principal tem sempre o nome do cadastro; o auxiliar puxa o nome do principal e pode editar;
// mudou o principal, os auxiliares voltam a acompanhar; Salvar do rodape nao perde o contato digitado; excluir leva os contatos;
// telefone pesquisado em todos os contatos, com o principal primeiro. NUNCA fala com o APChat de verdade: API falsa.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_CR = 'TESTE REGRAS CONTATO';
const TEL_A = '19912340071';
const TEL_B = '19912340072';
const TEL_C = '19912340073';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string
{
    ob_start();
    try { $f(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function limparCR(): void
{
    $ids = "SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_CR . "%'";
    x("DELETE FROM apchat_contato_log WHERE pessoa_id IN ({$ids})");
    x("DELETE FROM pessoa_contato WHERE pessoa_id IN ({$ids})");
    x("DELETE FROM pessoa_grupo WHERE pessoa_id IN ({$ids})");
    x("DELETE FROM pessoa WHERE nome LIKE '" . MARCA_CR . "%'");
}
function pessoa(string $nome): int
{
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare('INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES (?, 1) RETURNING id');
    $st->execute([$nome]);
    $id = (int) $st->fetchColumn();
    TTransaction::get()->prepare('INSERT INTO pessoa_grupo (pessoa_id, grupo_id) VALUES (?, ?)')->execute([$id, Grupo::CLIENTE]);
    TTransaction::close();
    return $id;
}
/** Linha da grade como a tela manda. */
function linha(array $c): string
{
    return base64_encode(serialize((object) ($c + ['id' => null, 'nome' => null, 'descricao' => null, 'telefone' => null, 'email' => null, 'principal' => 'N', '__row__id' => 'b' . uniqid('', true), '__display__' => []])));
}
function salvar(int $id, string $nome, array $linhas, array $extra = []): string
{
    $dados = q("SELECT telefone, email FROM pessoa WHERE id = {$id}")[0];
    $p = ['id' => $id, 'nome' => $nome, 'tipo_pessoa_id' => TipoPessoa::FISICA, 'email' => (string) $dados['email'], 'cpf_cnpj' => '', 'rg_ie' => '', 'telefone' => (string) $dados['telefone'], 'dt_nascimento_abertura' => '', 'dt_falecimento' => '',
          'pessoa_contato_pessoa_list___row__data' => array_map('linha', $linhas)] + $extra;
    $_POST = $p; $_REQUEST = $p;
    return saida(function () use ($p) { (new ClienteForm($p))->onSave($p); });
}
function contatos(int $id): array { return q("SELECT id, nome, descricao, telefone, email, principal FROM pessoa_contato WHERE pessoa_id = {$id} ORDER BY id"); }
function gravados(int $id): array { return array_map(fn($c) => ['id' => (int) $c['id'], 'nome' => $c['nome'], 'descricao' => $c['descricao'], 'telefone' => $c['telefone'], 'email' => $c['email'], 'principal' => $c['principal']], contatos($id)); }

limparCR();
$GLOBALS['__salvar_sem_exit'] = true;
ApchatApiService::usarTransporte(function ($url, $corpo, $token) { return ['status' => 201, 'corpo' => '{"success":true,"contact":{"id":1}}', 'erro' => '']; });
TSession::setValue('userid', 1);
$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);

try {
    $mae = pessoa(MARCA_CR . ' MAE');
    $filha = pessoa(MARCA_CR . ' FILHA');

    // ---------- principal tem o nome do cadastro ----------
    salvar($mae, MARCA_CR . ' MAE', [['telefone' => TEL_A, 'principal' => 'S', 'nome' => 'OUTRO NOME']]);
    $c = contatos($mae);
    igual('principal: nome digitado e trocado pelo do cadastro', MARCA_CR . ' MAE', $c[0]['nome']);
    igual('principal: dados acompanham', TEL_A, q("SELECT telefone FROM pessoa WHERE id = {$mae}")[0]['telefone']);

    // ---------- auxiliar puxa o nome; pode editar ----------
    salvar($filha, MARCA_CR . ' FILHA', [], ['pessoa_contato_pessoa_telefone' => TEL_A]);
    $aux = contatos($filha)[0];
    igual('auxiliar sem nome: puxa o nome do principal', MARCA_CR . ' MAE', $aux['nome']);
    $s = salvar($filha, MARCA_CR . ' FILHA', [['id' => $aux['id'], 'telefone' => TEL_A, 'principal' => 'N', 'nome' => 'MINHA MAE', 'descricao' => 'CASA']]);
    $aux = contatos($filha)[0];
    igual('auxiliar: nome e descricao sao dele', ['MINHA MAE', 'CASA'], [$aux['nome'], $aux['descricao']]);
    igual('auxiliar: o principal nao muda', MARCA_CR . ' MAE', contatos($mae)[0]['nome']);

    // ---------- mesmo telefone em dois principais ----------
    $s = salvar($filha, MARCA_CR . ' FILHA', [['id' => $aux['id'], 'telefone' => TEL_A, 'principal' => 'S', 'nome' => 'MINHA MAE']]);
    ok('telefone so e principal de um cadastro', strpos($s, 'já é o contato principal') !== false, substr($s, 0, 300));

    // ---------- mudou o nome do cadastro: principal e auxiliares acompanham ----------
    $principal = contatos($mae)[0];
    salvar($mae, MARCA_CR . ' MAE NOVA', [['id' => $principal['id'], 'telefone' => TEL_A, 'principal' => 'S', 'nome' => $principal['nome']]]);
    igual('novo nome: principal acompanha', MARCA_CR . ' MAE NOVA', contatos($mae)[0]['nome']);
    $aux = contatos($filha)[0];
    igual('novo nome: auxiliar volta a acompanhar', MARCA_CR . ' MAE NOVA', $aux['nome']);
    igual('novo nome: descricao do auxiliar fica', 'CASA', $aux['descricao']);

    // ---------- marcar como principal quem ja tem auxiliares ----------
    $sem = pessoa(MARCA_CR . ' SEM PRINCIPAL');
    salvar($filha, MARCA_CR . ' FILHA', [['id' => $aux['id'], 'telefone' => TEL_A, 'principal' => 'N', 'nome' => $aux['nome'], 'descricao' => 'CASA'], ['telefone' => TEL_B, 'principal' => 'N', 'nome' => 'SEM DONO']]);
    salvar($sem, MARCA_CR . ' SEM PRINCIPAL', [['telefone' => TEL_B, 'principal' => 'S']]);
    $porNumero = array_column(contatos($filha), 'nome', 'telefone');
    igual('numero virou principal: auxiliar existente recebe o nome', MARCA_CR . ' SEM PRINCIPAL', $porNumero[TEL_B]);

    // ---------- aviso da troca de numero mostra quem mais tem o numero ----------
    $principal = contatos($mae)[0];
    $s = salvar($mae, MARCA_CR . ' MAE NOVA', [['id' => $principal['id'], 'telefone' => TEL_C, 'principal' => 'S', 'nome' => $principal['nome']]]);
    ok('troca do principal: pede confirmacao', strpos($s, 'TROCA_DE_NUMERO_NAO_CONFIRMADA') !== false, substr($s, 0, 300));
    ok('troca do principal: lista os outros cadastros', strpos($s, MARCA_CR . ' FILHA') !== false && strpos($s, 'serão atualizados') !== false, substr($s, 0, 600));
    igual('sem confirmar: nada muda', TEL_A, contatos($mae)[0]['telefone']);
    salvar($mae, MARCA_CR . ' MAE NOVA', [['id' => $principal['id'], 'telefone' => TEL_C, 'principal' => 'S', 'nome' => $principal['nome']]], ['apchat_troca_confirmada' => '1']);
    igual('troca confirmada: principal com o numero novo', TEL_C, contatos($mae)[0]['telefone']);
    ok('troca confirmada: auxiliar com o numero novo', in_array(TEL_C, array_column(contatos($filha), 'telefone'), true) && !in_array(TEL_A, array_column(contatos($filha), 'telefone'), true));

    // ---------- trocar o numero de um auxiliar cujo numero e de outro cadastro: so ele muda ----------
    $lista = gravados($filha);
    $copia = array_values(array_filter($lista, fn($c) => $c['telefone'] === TEL_C))[0];
    $outros = array_values(array_filter($lista, fn($c) => $c['telefone'] !== TEL_C));
    $novaLista = array_merge($outros, [['telefone' => TEL_A] + $copia]);
    $s = salvar($filha, MARCA_CR . ' FILHA', $novaLista);
    ok('troca do auxiliar: avisa que so ele muda', strpos($s, 'Só este contato muda') !== false && strpos($s, 'deixará de existir') === false, substr($s, 0, 500));
    salvar($filha, MARCA_CR . ' FILHA', $novaLista, ['apchat_troca_confirmada' => '1']);
    igual('troca do auxiliar: o principal do outro cadastro nao muda', TEL_C, contatos($mae)[0]['telefone']);

    // ---------- Salvar do rodape com contato digitado (o defeito do teste ao vivo) ----------
    $novo = pessoa(MARCA_CR . ' RODAPE');
    $s = salvar($novo, MARCA_CR . ' RODAPE', [], ['pessoa_contato_pessoa_nome' => 'DIGITADO', 'pessoa_contato_pessoa_email' => 'digitado@example.invalid']);
    $c = contatos($novo);
    igual('digitado sem Adicionar: grava', ['DIGITADO', 'digitado@example.invalid'], [$c[0]['nome'] ?? null, $c[0]['email'] ?? null]);

    $rowId = 'bteste' . uniqid();
    $s = salvar($novo, MARCA_CR . ' RODAPE', [['id' => $c[0]['id'], 'email' => 'digitado@example.invalid', 'nome' => 'DIGITADO', '__row__id' => $rowId]],
                ['pessoa_contato_pessoa_nome' => 'ALTERADO', 'pessoa_contato_pessoa_email' => 'digitado@example.invalid', 'pessoa_contato_pessoa_id' => $c[0]['id'], 'pessoa_contato_pessoa__row__id' => $rowId]);
    $c = contatos($novo);
    igual('lapis sem o Salvar da linha: grava a alteracao, sem duplicar', [1, 'ALTERADO'], [count($c), $c[0]['nome'] ?? null]);

    $s = salvar($novo, MARCA_CR . ' RODAPE', [['id' => $c[0]['id'], 'email' => 'digitado@example.invalid', 'nome' => 'ALTERADO']], ['pessoa_contato_pessoa_nome' => 'SO NOME']);
    ok('digitado incompleto: avisa em vez de dizer que salvou', strpos($s, 'Informe o telefone ou o e-mail') !== false && strpos($s, 'Registro salvo') === false, substr($s, 0, 300));
    igual('digitado incompleto: nada gravado', 1, count(contatos($novo)));

    // ---------- nome digitado diferente do contato encontrado: o usuario escolhe ----------
    $base = pessoa(MARCA_CR . ' ESCOLHA');
    $campos = ['pessoa_contato_pessoa_telefone' => TEL_C, 'pessoa_contato_pessoa_nome' => "D'AVILA"];
    $s = salvar($base, MARCA_CR . ' ESCOLHA', [], $campos);
    ok('Salvar: pergunta qual nome usar', strpos($s, 'Qual nome deseja usar') !== false && strpos($s, 'NOME_DO_CONTATO_NAO_ESCOLHIDO') !== false, substr($s, 0, 300));
    ok('pergunta: mostra o contato encontrado e os dois nomes', strpos($s, MARCA_CR . ' MAE NOVA') !== false && strpos($s, 'Usar ') !== false && strpos($s, "Manter D\\'AVILA") !== false, substr($s, -500));
    igual('Salvar: sem escolher, nada gravado', 0, count(contatos($base)));
    salvar($base, MARCA_CR . ' ESCOLHA', [], $campos + ['pessoa_contato_nome_decidido' => '1']);
    igual('Salvar: manteve o nome digitado', "D'AVILA", contatos($base)[0]['nome'] ?? null);
    x("DELETE FROM pessoa_contato WHERE pessoa_id = {$base}");

    $adicionar = function (array $p) use ($base) {
        $p = ['id' => $base, 'nome' => MARCA_CR . ' ESCOLHA'] + $p;
        $_POST = $p; $_REQUEST = $p;
        return saida(function () use ($p) { (new ClienteForm($p))->onAddDetailPessoaContatoPessoa($p); });
    };
    // a linha da grade vai em base64 na resposta
    $naLista = function (string $s) { preg_match_all("/ttable_replace_row_by_id\\('pessoa_contato_pessoa_list', '[^']+', '([^']+)'\\)/", $s, $m); return implode(' ', array_map('base64_decode', $m[1])); };
    $s = $adicionar($campos);
    ok('Adicionar: pergunta e nao coloca na lista', strpos($s, 'Qual nome deseja usar') !== false && $naLista($s) === '', substr($s, 0, 300));
    ok('Adicionar: a resposta volta para o Adicionar', strpos($s, 'depois=adicionar') !== false, substr($s, -400));
    $s = $adicionar($campos + ['pessoa_contato_nome_decidido' => '1']);
    ok('Adicionar: escolhido, entra na lista', strpos($s, 'Qual nome deseja usar') === false && strpos($naLista($s), "D'AVILA") !== false, substr($s, 0, 300));
    $s = $adicionar(['pessoa_contato_pessoa_telefone' => TEL_C, 'pessoa_contato_pessoa_nome' => MARCA_CR . ' MAE NOVA']);
    ok('Adicionar: nome igual ao encontrado nao pergunta', strpos($s, 'Qual nome deseja usar') === false, substr($s, 0, 300));
    $s = $adicionar(['pessoa_contato_pessoa_telefone' => TEL_C]);
    ok('Adicionar: nome vazio puxa o encontrado sem perguntar', strpos($s, 'Qual nome deseja usar') === false && strpos($naLista($s), MARCA_CR . ' MAE NOVA') !== false, substr($s, 0, 300));
    $s = $adicionar(['pessoa_contato_pessoa_telefone' => '19912340079', 'pessoa_contato_pessoa_nome' => 'NINGUEM TEM']);
    ok('Adicionar: numero sem principal nao pergunta', strpos($s, 'Qual nome deseja usar') === false, substr($s, 0, 300));
    $linhaGrade = 'bgrade' . uniqid();
    $emEdicao = ['pessoa_contato_pessoa__row__id' => $linhaGrade, 'pessoa_contato_pessoa_list___row__data' => [linha(['telefone' => TEL_C, 'nome' => 'NOME PROPRIO', '__row__id' => $linhaGrade])]];
    $s = $adicionar($emEdicao + ['pessoa_contato_pessoa_telefone' => TEL_C, 'pessoa_contato_pessoa_nome' => 'NOME PROPRIO']);
    ok('editando sem mudar o numero: nao pergunta, o nome e dele', strpos($s, 'Qual nome deseja usar') === false, substr($s, 0, 300));
    $emEdicaoOutro = ['pessoa_contato_pessoa__row__id' => $linhaGrade, 'pessoa_contato_pessoa_list___row__data' => [linha(['telefone' => TEL_A, 'nome' => 'NOME PROPRIO', '__row__id' => $linhaGrade])]];
    $s = $adicionar($emEdicaoOutro + ['pessoa_contato_pessoa_telefone' => TEL_C, 'pessoa_contato_pessoa_nome' => 'NOME PROPRIO']);
    ok('editando e mudando para o numero de outro: pergunta', strpos($s, 'Qual nome deseja usar') !== false, substr($s, 0, 300));

    $s = saida(function () { ClienteForm::onEscolherNomeContato(['usar' => 'encontrado', 'telefone' => TEL_C, 'depois' => 'adicionar']); });
    ok('resposta Usar: poe o nome encontrado e clica no Adicionar', strpos($s, MARCA_CR . ' MAE NOVA') !== false && strpos($s, 'pessoa_contato_nome_decidido') !== false && strpos($s, '64be92cf7046f') !== false, substr($s, 0, 400));
    $s = saida(function () { ClienteForm::onEscolherNomeContato(['usar' => 'digitado', 'telefone' => TEL_C, 'depois' => 'salvar']); });
    ok('resposta Manter: nao mexe no nome e clica no Salvar', strpos($s, MARCA_CR . ' MAE NOVA') === false && strpos($s, 'pessoa_contato_nome_decidido') !== false && strpos($s, 'method=onSave') !== false, substr($s, 0, 400));

    $s = saida(function () { ClienteForm::onTelefoneContatoSaida(['pessoa_contato_pessoa_telefone' => TEL_C, 'pessoa_contato_pessoa_nome' => 'DIGITADO']); });
    ok('sair do telefone com nome digitado: nao troca o nome', strpos($s, MARCA_CR . ' MAE NOVA') === false, substr($s, 0, 300));
    $s = saida(function () { ClienteForm::onTelefoneContatoSaida(['pessoa_contato_pessoa_telefone' => TEL_C, 'pessoa_contato_pessoa_nome' => '']); });
    ok('sair do telefone sem nome: preenche, se o telefone ainda for esse', strpos($s, MARCA_CR . ' MAE NOVA') !== false && strpos($s, TEL_C) !== false, substr($s, 0, 300));

    // ---------- trocar o contato principal: o e-mail e o nome do anterior ----------
    $TEL_P = '19912340074'; $TEL_N = '19912340075';
    $cenario = function (string $sufixo, array $principal, array $outro) {
        $id = pessoa(MARCA_CR . ' TROCA ' . $sufixo);
        salvar($id, MARCA_CR . ' TROCA ' . $sufixo, [['principal' => 'S'] + $principal, $outro]);
        $por = [];
        foreach (contatos($id) as $c) { $por[$c['principal']] = $c; }
        return [$id, $por['S'], $por['N']];
    };
    $estado = function (int $id, int $contato) { return q("SELECT nome, email, telefone, principal FROM pessoa_contato WHERE id = {$contato} AND pessoa_id = {$id}")[0] ?? null; };
    $dadosPessoa = function (int $id) { return q("SELECT telefone, email FROM pessoa WHERE id = {$id}")[0]; };

    // pelo Adicionar / Salvar da linha, a grade muda na tela
    $pela = function (int $id, string $nome, array $linhas, array $campos) {
        $p = ['id' => $id, 'nome' => $nome, 'pessoa_contato_pessoa_list___row__data' => array_map('linha', $linhas)] + $campos;
        $_POST = $p; $_REQUEST = $p;
        return saida(function () use ($p) { (new ClienteForm($p))->onAddDetailPessoaContatoPessoa($p); });
    };
    [$t1, $p1, $n1] = $cenario('A', ['telefone' => $TEL_P, 'email' => 'antigo@example.invalid'], ['telefone' => $TEL_N, 'nome' => 'AUXILIAR']);
    $grade1 = [['id' => $p1['id'], 'telefone' => $TEL_P, 'email' => 'antigo@example.invalid', 'nome' => $p1['nome'], 'principal' => 'S', '__row__id' => 'bA'], ['id' => $n1['id'], 'telefone' => $TEL_N, 'nome' => 'AUXILIAR', 'principal' => 'N', '__row__id' => 'bB']];
    $marcar = ['pessoa_contato_pessoa__row__id' => 'bB', 'pessoa_contato_pessoa_id' => $n1['id'], 'pessoa_contato_pessoa_telefone' => $TEL_N, 'pessoa_contato_pessoa_principal' => 'S'];
    $s = $pela($t1, MARCA_CR . ' TROCA A', $grade1, $marcar);
    ok('marcar principal com e-mail no anterior: pergunta', strpos($s, 'antigo@example.invalid') !== false && strpos($s, 'Levar para o novo principal') !== false && strpos($s, 'Manter no contato anterior') !== false, substr($s, -500));
    igual('marcar principal: sem responder, a grade nao muda', '', $naLista($s));
    $s = $pela($t1, MARCA_CR . ' TROCA A', $grade1, $marcar + ['pessoa_contato_email_decidido' => 'levar']);
    ok('levar, na grade: o e-mail vai para o novo e so ele tem o nome do cliente', strpos($naLista($s), 'antigo@example.invalid') !== false && substr_count($naLista($s), MARCA_CR . ' TROCA A') === 1, $naLista($s));
    $s = $pela($t1, MARCA_CR . ' TROCA A', $grade1, ['pessoa_contato_pessoa__row__id' => 'bC', 'pessoa_contato_pessoa_telefone' => '19912340076', 'pessoa_contato_pessoa_principal' => 'S', 'pessoa_contato_email_decidido' => 'manter']);
    ok('manter, na grade: o e-mail fica no anterior e so o novo tem o nome do cliente', strpos($naLista($s), 'antigo@example.invalid') !== false && substr_count($naLista($s), MARCA_CR . ' TROCA A') === 1, $naLista($s));

    // pelo Salvar do rodape: grava
    $novoNoRodape = ['pessoa_contato_pessoa_id' => $n1['id'], 'pessoa_contato_pessoa__row__id' => 'bB', 'pessoa_contato_pessoa_telefone' => $TEL_N, 'pessoa_contato_pessoa_principal' => 'S'];
    $s = salvar($t1, MARCA_CR . ' TROCA A', $grade1, $novoNoRodape);
    ok('rodape: pergunta do e-mail', strpos($s, 'EMAIL_DO_PRINCIPAL_NAO_ESCOLHIDO') !== false && strpos($s, 'Levar para o novo principal') !== false, substr($s, 0, 300));
    igual('rodape: sem responder, nada muda', 'S', $estado($t1, (int) $p1['id'])['principal']);
    salvar($t1, MARCA_CR . ' TROCA A', $grade1, $novoNoRodape + ['pessoa_contato_email_decidido' => 'levar']);
    $a = $estado($t1, (int) $p1['id']); $b = $estado($t1, (int) $n1['id']);
    igual('levar: anterior sem principal, sem nome e sem e-mail', ['N', null, null], [$a['principal'], $a['nome'], $a['email']]);
    igual('levar: novo principal com o nome do cliente e o e-mail', ['S', MARCA_CR . ' TROCA A', 'antigo@example.invalid'], [$b['principal'], $b['nome'], $b['email']]);
    igual('levar: Dados com o telefone e o e-mail do novo', [$TEL_N, 'antigo@example.invalid'], array_values($dadosPessoa($t1)));

    [$t2, $p2, $n2] = $cenario('B', ['telefone' => '19912340077', 'email' => 'fica@example.invalid'], ['telefone' => '19912340078', 'nome' => 'AUX']);
    $grade2 = [['id' => $p2['id'], 'telefone' => '19912340077', 'email' => 'fica@example.invalid', 'nome' => $p2['nome'], 'principal' => 'S', '__row__id' => 'bA'], ['id' => $n2['id'], 'telefone' => '19912340078', 'nome' => 'AUX', 'principal' => 'N', '__row__id' => 'bB']];
    salvar($t2, MARCA_CR . ' TROCA B', $grade2, ['pessoa_contato_pessoa_id' => $n2['id'], 'pessoa_contato_pessoa__row__id' => 'bB', 'pessoa_contato_pessoa_telefone' => '19912340078', 'pessoa_contato_pessoa_principal' => 'S', 'pessoa_contato_email_decidido' => 'manter']);
    $a = $estado($t2, (int) $p2['id']); $b = $estado($t2, (int) $n2['id']);
    igual('manter: anterior sem nome, com o e-mail', ['N', null, 'fica@example.invalid'], [$a['principal'], $a['nome'], $a['email']]);
    igual('manter: novo principal sem e-mail', ['S', MARCA_CR . ' TROCA B', null], [$b['principal'], $b['nome'], $b['email']]);
    igual('manter: e-mail dos Dados e o do novo (vazio)', '', (string) $dadosPessoa($t2)['email']);

    [$t3, $p3, $n3] = $cenario('C', ['email' => 'um@example.invalid'], ['email' => 'dois@example.invalid', 'nome' => 'AUX']);
    $grade3 = [['id' => $p3['id'], 'email' => 'um@example.invalid', 'nome' => $p3['nome'], 'principal' => 'S', '__row__id' => 'bA'], ['id' => $n3['id'], 'email' => 'dois@example.invalid', 'nome' => 'AUX', 'principal' => 'N', '__row__id' => 'bB']];
    $marca3 = ['pessoa_contato_pessoa_id' => $n3['id'], 'pessoa_contato_pessoa__row__id' => 'bB', 'pessoa_contato_pessoa_email' => 'dois@example.invalid', 'pessoa_contato_pessoa_principal' => 'S'];
    $s = salvar($t3, MARCA_CR . ' TROCA C', $grade3, $marca3);
    ok('os dois com e-mail: avisa que o do novo passa para o anterior', strpos($s, 'dois@example.invalid') !== false && strpos($s, 'passa para o contato anterior') !== false, substr($s, -500));
    salvar($t3, MARCA_CR . ' TROCA C', $grade3, $marca3 + ['pessoa_contato_email_decidido' => 'levar']);
    igual('os dois com e-mail, levar: troca entre eles', ['dois@example.invalid', 'um@example.invalid'], [$estado($t3, (int) $p3['id'])['email'], $estado($t3, (int) $n3['id'])['email']]);

    [$t4, $p4, $n4] = $cenario('D', ['email' => 'unico@example.invalid'], ['telefone' => '19912340079', 'nome' => 'AUX']);
    $grade4 = [['id' => $p4['id'], 'email' => 'unico@example.invalid', 'nome' => $p4['nome'], 'principal' => 'S', '__row__id' => 'bA'], ['id' => $n4['id'], 'telefone' => '19912340079', 'nome' => 'AUX', 'principal' => 'N', '__row__id' => 'bB']];
    $marca4 = ['pessoa_contato_pessoa_id' => $n4['id'], 'pessoa_contato_pessoa__row__id' => 'bB', 'pessoa_contato_pessoa_telefone' => '19912340079', 'pessoa_contato_pessoa_principal' => 'S'];
    $s = salvar($t4, MARCA_CR . ' TROCA D', $grade4, $marca4);
    ok('anterior so com e-mail: avisa que sera excluido', strpos($s, 'será excluído') !== false, substr($s, -500));
    salvar($t4, MARCA_CR . ' TROCA D', $grade4, $marca4 + ['pessoa_contato_email_decidido' => 'levar']);
    igual('anterior so com e-mail, levar: sai e fica um contato so', [1, 'S', 'unico@example.invalid'], [count(contatos($t4)), contatos($t4)[0]['principal'], contatos($t4)[0]['email']]);

    [$t5, $p5, $n5] = $cenario('E', ['telefone' => '19912340080'], ['telefone' => '19912340082', 'nome' => 'AUX']);
    $grade5 = [['id' => $p5['id'], 'telefone' => '19912340080', 'nome' => $p5['nome'], 'principal' => 'S', '__row__id' => 'bA'], ['id' => $n5['id'], 'telefone' => '19912340082', 'nome' => 'AUX', 'principal' => 'N', '__row__id' => 'bB']];
    $s = salvar($t5, MARCA_CR . ' TROCA E', $grade5, ['pessoa_contato_pessoa_id' => $n5['id'], 'pessoa_contato_pessoa__row__id' => 'bB', 'pessoa_contato_pessoa_telefone' => '19912340082', 'pessoa_contato_pessoa_principal' => 'S']);
    ok('anterior sem e-mail: nao pergunta', strpos($s, 'Levar para o novo principal') === false, substr($s, 0, 300));
    $a = $estado($t5, (int) $p5['id']);
    igual('anterior sem e-mail: troca e o nome fica vazio', ['N', null], [$a['principal'], $a['nome']]);

    $s = salvar($t5, MARCA_CR . ' TROCA E', [], ['pessoa_contato_pessoa_telefone' => TEL_C, 'pessoa_contato_pessoa_principal' => 'S']);
    ok('numero que e principal de outro cadastro nao vira principal', strpos($s, 'já é o contato principal') !== false, substr($s, 0, 300));

    // nome dado de proposito ao anterior fica; so sai o nome do cliente
    [$t6, $p6, $n6] = $cenario('F', ['telefone' => '19912340083'], ['telefone' => '19912340084', 'nome' => MARCA_CR . ' TROCA F']);
    igual('primeiro principal definido: o outro contato ja fica sem o nome do cliente', 1, count(array_filter(contatos($t6), fn($c) => $c['nome'] === MARCA_CR . ' TROCA F')));
    $grade6 = [['id' => $p6['id'], 'telefone' => '19912340083', 'nome' => 'NOME DE PROPOSITO', 'principal' => 'N', '__row__id' => 'bA'], ['id' => $n6['id'], 'telefone' => '19912340084', 'nome' => 'OUTRO', 'principal' => 'S', '__row__id' => 'bB']];
    salvar($t6, MARCA_CR . ' TROCA F', $grade6);
    igual('nome de proposito no anterior: fica', 'NOME DE PROPOSITO', $estado($t6, (int) $p6['id'])['nome']);

    $s = saida(function () { ClienteForm::onEscolherEmailPrincipal(['usar' => 'levar', 'depois' => 'salvar']); });
    ok('resposta: guarda a escolha e clica no Salvar', strpos($s, "pessoa_contato_email_decidido]').val('levar')") !== false && strpos($s, 'method=onSave') !== false, substr($s, 0, 300));
    $s = saida(function () { ClienteForm::onEscolherEmailPrincipal(['usar' => 'x', 'depois' => 'adicionar']); });
    ok('resposta: qualquer outra coisa vale manter e clica no Adicionar', strpos($s, "val('manter')") !== false && strpos($s, '64be92cf7046f') !== false, substr($s, 0, 300));

    // ---------- aberto no meio de outra tarefa: fecha ao salvar ----------
    $s = salvar($novo, MARCA_CR . ' RODAPE', [['id' => $c[0]['id'], 'email' => 'digitado@example.invalid', 'nome' => 'ALTERADO']], ['fechar_ao_salvar' => '1']);
    ok('fechar_ao_salvar: fecha e nao reabre o cadastro', strpos($s, 'Template.closeRightPanel()') !== false && strpos($s, 'class=ClienteForm&method=onEdit') === false, substr($s, 0, 300));
    $s = salvar($novo, MARCA_CR . ' RODAPE', [['id' => $c[0]['id'], 'email' => 'digitado@example.invalid', 'nome' => 'ALTERADO']]);
    ok('pela lista: continua no cadastro', strpos($s, 'class=ClienteForm') !== false && strpos($s, 'method=onEdit') !== false, substr($s, 0, 300));

    // ---------- pesquisa de telefone ----------
    $busca = function (string $classe, array $filtro) {
        TSession::setValue($classe . '_filters', null);
        TSession::setValue($classe . '_telefone_busca', null);
        TSession::setValue($classe . '_filter_data', null);
        $_POST = []; $_REQUEST = [];
        $html = saida(function () use ($classe, $filtro) { $l = new $classe([]); $l->onSearch(['__sem_formulario' => 1, '__filtros_extra' => $filtro]); $l->show(); });
        preg_match_all('~<tr[^>]*id="row_(\d+)"~', $html, $m);
        return [array_map('intval', $m[1]), $html];
    };
    x("UPDATE pessoa_contato SET telefone = '" . TEL_C . "' WHERE pessoa_id = {$filha} AND principal = 'N' AND telefone = '" . TEL_A . "'");
    [$linhas, $html] = $busca('ClienteList', ['telefone_col' => TEL_C]);
    ok('pesquisa: acha quem tem o numero em contato nao principal', in_array($filha, $linhas, true) && in_array($mae, $linhas, true), json_encode($linhas) . substr($html, 0, 200));
    igual('pesquisa: o principal vem primeiro', $mae, $linhas[0] ?? null);
    [$linhas] = $busca('ClienteList', ['telefone_col' => '(19) 91234-0073']);
    igual('pesquisa: aceita com mascara', $mae, $linhas[0] ?? null);
    [$linhas] = $busca('ClienteList', ['telefone_col' => '55' . TEL_C]);
    igual('pesquisa: aceita com 55', $mae, $linhas[0] ?? null);
    [$linhas] = $busca('ClienteList', ['telefone_col' => '19912340099']);
    ok('pesquisa: numero de ninguem nao traz nada', !in_array($mae, $linhas, true) && !in_array($filha, $linhas, true));
    [$linhas] = $busca('PessoasHeaderList', ['telefone_col' => TEL_C]);
    ok('pesquisa: lista de pessoas tambem', in_array($filha, $linhas, true) && ($linhas[0] ?? null) === $mae, json_encode($linhas));

    // ---------- excluir leva os contatos ----------
    $exc = pessoa(MARCA_CR . ' EXCLUIR');
    salvar($exc, MARCA_CR . ' EXCLUIR', [['email' => 'excluir@example.invalid', 'nome' => 'EXC']]);
    igual('excluir: tem contato', 1, count(contatos($exc)));
    $p = ['id' => $exc, 'delete' => 1];
    $s = saida(function () use ($p) { (new ClienteForm($p))->onDelete($p); });
    ok('excluir: sem erro do banco', strpos($s, 'SQLSTATE') === false, substr($s, -400));
    igual('excluir: cadastro e contatos sairam', [0, 0], [count(q("SELECT 1 FROM pessoa WHERE id = {$exc}")), count(contatos($exc))]);
    ok('excluir: fecha so uma camada', substr_count($s, 'Template.closeRightPanel()') === 1, (string) substr_count($s, 'Template.closeRightPanel()'));

    // ---------- Sair fecha a cortina nos 6 cadastros ----------
    foreach (['ClienteForm', 'ContraparteForm', 'FornecedorForm', 'ParceiroForm', 'ProfissionalForm', 'RepresentanteLegalForm'] as $form)
    {
        $codigo = file_get_contents("app/control/pessoas/{$form}.php");
        ok("{$form}: Sair fecha a cortina", strpos($codigo, 'addAction("Sair", new TAction([__CLASS__, \'onFecharCortina\']') !== false);
        ok("{$form}: excluir leva os contatos", strpos($codigo, 'PessoaContatoService::excluirContatos') !== false);
    }
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    $_POST = []; $_REQUEST = [];
    ApchatApiService::usarTransporte(null);
    limparCR();
}

fim();
