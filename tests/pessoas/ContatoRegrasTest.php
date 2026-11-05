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
