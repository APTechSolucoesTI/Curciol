<?php
// tests/seguranca/ContaAdminTest.php
// Conta do grupo Admin so e alterada por Admin; os demais usuarios continuam sendo gerenciados normalmente.
require __DIR__ . '/../bootstrap.php';

$recusa = function (callable $f) { try { $f(); return false; } catch (Exception $e) { return $e->getMessage() == ContaAdmin::MENSAGEM; } };

TTransaction::open('permission');
$outro = SystemUserGroup::where('system_group_id', '<>', ContaAdmin::GRUPO_ADMIN)->first()->system_user_id;
TTransaction::close();

ok('admin (id 1) e Admin', ContaAdmin::usuarioEhAdmin(1));
ok('usuario comum nao e Admin', !ContaAdmin::usuarioEhAdmin($outro));

TSession::setValue('usergroupids', [2, 3]);
ok('nao Admin: recusa alterar admin', $recusa(fn() => ContaAdmin::exigirPermissao(1)));
ok('nao Admin: recusa personificar admin', $recusa(fn() => ContaAdmin::exigirPermissaoLogin('admin')));
ok('nao Admin: recusa dar grupo Admin', $recusa(fn() => ContaAdmin::exigirPermissaoGrupos(['1', '2'])));
ok('nao Admin: altera usuario comum', !$recusa(fn() => ContaAdmin::exigirPermissao($outro)));
ok('nao Admin: novo usuario sem grupo Admin', !$recusa(fn() => ContaAdmin::exigirPermissaoGrupos(['2', '3'])));

TSession::setValue('usergroupids', [1]);
ok('Admin: altera admin', !$recusa(fn() => ContaAdmin::exigirPermissao(1)));
ok('Admin: da grupo Admin', !$recusa(fn() => ContaAdmin::exigirPermissaoGrupos([1])));
fim();
