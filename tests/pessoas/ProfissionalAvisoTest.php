<?php
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
tokenApchatDeTeste();
APChatContactService::usarTransporte(fn() => ['status' => 201, 'corpo' => '{"success":true,"data":{"id":1}}', 'erro' => '']);
$GLOBALS['__salvar_sem_exit'] = true;
TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$c = TTransaction::get();
$unidade = (int) $c->query('SELECT system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetchColumn();
$tipo = (int) $c->query('SELECT id FROM tipo_profissional ORDER BY id LIMIT 1')->fetchColumn();
$usados = $c->query('SELECT system_users_id FROM pessoa WHERE system_users_id IS NOT NULL')->fetchAll(PDO::FETCH_COLUMN);
TTransaction::close();
TTransaction::open('permission');
$usuarios = TTransaction::get()->query('SELECT id FROM system_users ORDER BY id DESC')->fetchAll(PDO::FETCH_COLUMN);
TTransaction::close();
$usuario = (int) (array_values(array_diff($usuarios, $usados))[0] ?? 0);
TSession::setValue('userunitid', $unidade); TSession::setValue('userunitids', [$unidade]);
$marca = 'TESTE AVISO PROFISSIONAL ' . getmypid();
try {
    ok('existe usuario livre para a fixture', $usuario > 0);
    $_POST = $_REQUEST = $p = ['id' => '', 'nome' => $marca, 'tipo_pessoa_id' => TipoPessoa::FISICA,
        'tipo_profissional_id' => $tipo, 'system_users_id' => $usuario, 'telefone' => '19912340982',
        'email' => '', 'cpf_cnpj' => '', 'rg_ie' => (string) (60000000 + getmypid() % 1000000), 'registro_profissional' => 'TESTE', 'orgao_registro_profissional' => 'OAB'];
    ob_start(); (new ProfissionalForm($p))->onSave($p); $html = ob_get_clean();
    TTransaction::open('escritorio');
    $st=TTransaction::get()->prepare('SELECT id FROM pessoa WHERE nome=?');$st->execute([$marca]);$id=(int)$st->fetchColumn();TTransaction::close();
    ok('profissional novo foi salvo', $id > 0);
    // Reproduz o contrato de fireEvents do TForm: o browser ainda pode estar sem ID.
    preg_match_all("/tform_send_data\('[^']+', 'telefone', '([^']*)', (true|false),/", $html, $m, PREG_SET_ORDER);
    ob_start();
    foreach ($m as $envio) { if ($envio[2] === 'true') { ProfissionalForm::onTelefoneDadosSaida(['id'=>'', 'telefone'=>stripslashes($envio[1])]); } }
    $aviso=ob_get_clean(); preg_match_all("/__adianti_show_toast64\('warning', '([^']+)'/", $aviso, $toasts); $aviso=implode(' ',array_map('base64_decode',$toasts[1]));
    ok('sincronizar dados salvos nao avisa que o telefone e do proprio cadastro', strpos($aviso, 'já é o contato principal') === false);
    ob_start();ProfissionalForm::onTelefoneDadosSaida(['id'=>'', 'telefone'=>'19912340982']);$aviso=ob_get_clean(); preg_match_all("/__adianti_show_toast64\('warning', '([^']+)'/", $aviso, $toasts); $aviso=implode(' ',array_map('base64_decode',$toasts[1]));
    ok('digitacao em outro cadastro continua avisando numero ocupado', strpos($aviso, 'já é o contato principal') !== false);
} finally {
    while(TTransaction::get()){TTransaction::rollback();}
    TTransaction::open('escritorio');$c=TTransaction::get();
    foreach(['pessoa_contato','pessoa_grupo'] as $t){$c->prepare("DELETE FROM {$t} WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome=?)")->execute([$marca]);}
    $c->prepare('DELETE FROM pessoa WHERE nome=?')->execute([$marca]);TTransaction::close();
}
fim();
