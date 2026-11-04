<?php
// tests/pessoas/PessoaDocumentoTest.php
// 04/11/2026: formato do RG nos formularios de pessoa e na edicao direto na lista; CPF e CNPJ na edicao direto na lista. O cadastro
// completo (AgendamentoService::pendenciasCadastro) so olha se esta preenchido. Nao fala com o APChat.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_DOC = 'TESTE DOCUMENTO';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string { ob_start(); $f(); return ob_get_clean(); }
function limparDoc(): void { x("DELETE FROM pessoa WHERE nome LIKE '" . MARCA_DOC . "%'"); }
limparDoc();
TSession::setValue('userid', 1);

$pessoa = function (string $nome, int $tipo) {
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare('INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES (?, ?) RETURNING id');
    $st->execute([MARCA_DOC . ' ' . $nome, $tipo]);
    $id = (int) $st->fetchColumn();
    TTransaction::close();
    return $id;
};
$inline = function (int $id, string $coluna, string $valor) {
    return saida(function () use ($id, $coluna, $valor) { PessoaEditHeaderList::onSaveInline(['_field_name' => "{$id}_{$coluna}", '_field_value' => $valor, 'column' => $coluna]); });
};
$campo = fn(int $id, string $coluna) => q("SELECT {$coluna} c FROM pessoa WHERE id = {$id}")[0]['c'];

try {
    // ---------- formato do RG: os desenhos que existem no cadastro ----------
    foreach (['123456789', '12345678X', '12.345.678-9', '12.345.678-X', '1.234.567', '12345678 9', '123456', '12345', '111.444.777-35', '12345678901234'] as $rg) {
        ok("RG valido: {$rg}", PessoaDocumentoService::rgValido($rg));
    }
    foreach (['abc', 'SSP123456', '1234', '0000000', '00.000.000-0', '12345A678', '123456789012345', 'XX', '12345678XX'] as $rg) {
        ok("RG invalido: {$rg}", !PessoaDocumentoService::rgValido($rg));
    }

    $lanca = function ($rg, $tipo) { try { PessoaDocumentoService::exigirRgValido($rg, $tipo); return null; } catch (Exception $e) { return $e->getMessage(); } };
    igual('RG vazio pode', null, $lanca('', TipoPessoa::FISICA));
    igual('RG so com espaco pode', null, $lanca('   ', TipoPessoa::FISICA));
    igual('pessoa fisica: RG errado barra', 'RG inválido: confira os números digitados.', $lanca('abc', TipoPessoa::FISICA));
    igual('pessoa juridica: inscricao estadual nao passa por aqui', null, $lanca('ISENTO', TipoPessoa::JURIDICA));

    // ---------- os 6 formularios validam antes de gravar ----------
    foreach (['Cliente', 'Contraparte', 'Fornecedor', 'Parceiro', 'Profissional', 'RepresentanteLegal'] as $f) {
        $codigo = preg_replace('~^\s*//.*$~m', '', file_get_contents("app/control/pessoas/{$f}Form.php"));
        ok("{$f}Form valida RG", substr_count($codigo, 'PessoaDocumentoService::exigirRgValido($data->rg_ie') === 1);
        ok("{$f}Form valida CPF e CNPJ", strpos($codigo, 'new TCPFValidator') !== false && strpos($codigo, 'new TCNPJValidator') !== false);
        ok("{$f}Form valida telefone dos Dados", strpos($codigo, 'PessoaContatoService::exigirTelefoneValido($data->telefone)') !== false);
    }

    // ---------- edicao direto na lista ----------
    $pf = $pessoa('PF', (int) TipoPessoa::FISICA);
    $s = $inline($pf, 'rg_ie', 'abc');
    ok('lista: RG errado barra', strpos($s, 'RG inválido') !== false && $campo($pf, 'rg_ie') === null, substr($s, 0, 200));
    $inline($pf, 'rg_ie', '12.345.678-X');
    igual('lista: RG certo grava', '12.345.678-X', $campo($pf, 'rg_ie'));
    $s = $inline($pf, 'cpf_cnpj', '111.444.777-00');
    ok('lista: CPF errado barra', strpos($s, 'CPF inválido') !== false && $campo($pf, 'cpf_cnpj') === null, substr($s, 0, 200));
    $inline($pf, 'cpf_cnpj', '111.444.777-35');
    igual('lista: CPF certo grava so os numeros', '11144477735', $campo($pf, 'cpf_cnpj'));
    $s = $inline($pf, 'cpf_cnpj', '123');
    igual('lista: tamanho errado nao troca', '11144477735', $campo($pf, 'cpf_cnpj'));
    $pj = $pessoa('PJ', (int) TipoPessoa::JURIDICA);
    $inline($pj, 'rg_ie', 'ISENTO');
    igual('lista: inscricao estadual livre', 'ISENTO', $campo($pj, 'rg_ie'));
    $s = $inline($pj, 'cpf_cnpj', '11.222.333/0001-00');
    ok('lista: CNPJ errado barra', strpos($s, 'CNPJ inválido') !== false && $campo($pj, 'cpf_cnpj') === null, substr($s, 0, 200));
    $s = $inline($pf, 'telefone', '123');
    ok('lista: telefone errado continua barrando', $campo($pf, 'telefone') === null);

    // ---------- cadastro completo: so preenchido ----------
    x("UPDATE pessoa SET rg_ie = 'qualquer coisa', cpf_cnpj = '123' WHERE id = {$pf}");
    TTransaction::open('escritorio');
    $faltam = AgendamentoService::pendenciasCadastro($pf);
    TTransaction::close();
    ok('cadastro completo nao julga o formato', !in_array('RG', $faltam, true) && !in_array('CPF', $faltam, true), json_encode($faltam, JSON_UNESCAPED_UNICODE));
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    limparDoc();
}

fim();
