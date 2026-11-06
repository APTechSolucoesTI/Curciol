<?php
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
$GLOBALS['__salvar_sem_exit'] = true;
APChatContactService::usarTransporte(fn() => ['status' => 201, 'corpo' => '{"success":true,"data":{"id":1}}', 'erro' => '']);
TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$unidade = (int) TTransaction::get()->query('SELECT system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetchColumn();
TTransaction::close();
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);
foreach (['ContraparteForm' => Grupo::CONTRAPARTE, 'ClienteForm' => Grupo::CLIENTE, 'FornecedorForm' => Grupo::FORNECEDOR, 'RepresentanteLegalForm' => Grupo::REPRESENTANTE_LEGAL, 'ParceiroForm' => Grupo::PARCEIRO] as $classe => $grupo) {
$marca = 'TESTE ROLLBACK CADASTRO ' . getmypid() . ' ' . $classe;
echo $classe . PHP_EOL;
$param = ['id' => '', 'nome' => $marca, 'tipo_pessoa_id' => TipoPessoa::FISICA,
    'cpf_cnpj' => '', 'rg_ie' => '', 'telefone' => '', 'email' => '',
    'pessoa_contato_pessoa_nome' => 'AUX TESTE', 'pessoa_contato_pessoa_telefone' => '19912340981',
    'pessoa_endereco_pessoa_list___row__data' => ['invalido'],
    'pessoa_endereco_pessoa_list_principal' => ['N']];
$salvar = function (array $p) use ($classe): string {
    $_POST = $_REQUEST = $p;
    ob_start();
    try { (new $classe($p))->onSave($p); return ob_get_clean(); }
    catch (Throwable $e) { ob_end_clean(); throw $e; }
};
try {
    $html = $salvar($param);
    ok('erro posterior aos contatos e reproduzido', strpos($html, 'Selecione algum endereço') !== false);
    ok('rollback: ID sem commit nao vai ao formulario', !preg_match("/tform_send_data\('[^']+', 'id', '[0-9]+',/", $html));
    igual('rollback: nao fica transacao aberta', null, TTransaction::get());
    // Simula o navegador aplicando o ID que recebeu antes do erro e repetindo Salvar.
    if (preg_match("/tform_send_data\('[^']+', 'id', '([0-9]+)',/", $html, $m)) { $param['id'] = $m[1]; }
    unset($param['pessoa_endereco_pessoa_list___row__data'], $param['pessoa_endereco_pessoa_list_principal']);
    $salvar($param);
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $st = $c->prepare('SELECT id FROM pessoa WHERE nome = ?'); $st->execute([$marca]);
    $ids = $st->fetchAll(PDO::FETCH_COLUMN);
    igual('repetir salvar: uma pessoa', 1, count($ids));
    $id = (int) ($ids[0] ?? 0);
    igual('repetir salvar: grupo do cadastro presente', 1, (int) $c->query('SELECT count(*) FROM pessoa_grupo WHERE pessoa_id = ' . $id . ' AND grupo_id = ' . (int) $grupo)->fetchColumn());
    igual('repetir salvar: um contato auxiliar', 1, (int) $c->query("SELECT count(*) FROM pessoa_contato WHERE pessoa_id = {$id} AND telefone = '19912340981'")->fetchColumn());
    TTransaction::close();
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    foreach (['apchat_contato_log', 'pessoa_contato', 'pessoa_grupo'] as $t) {
        $c->prepare("DELETE FROM {$t} WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome = ?)")->execute([$marca]);
    }
    $c->prepare('DELETE FROM pessoa WHERE nome = ?')->execute([$marca]);
    TTransaction::close();
}
}
APChatContactService::usarTransporte(null);
fim();
