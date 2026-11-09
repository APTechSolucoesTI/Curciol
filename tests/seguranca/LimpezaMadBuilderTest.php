<?php
// tests/seguranca/LimpezaMadBuilderTest.php
// Limpeza do que veio pronto do Mad Builder (09/11/2026): fica a Administracao (grupos, unidades, usuarios), os logs de acesso e
// de alteracoes, o monitor de usuarios, o sino e o portal do cliente. Sem login abre so o login, a landing (Home) e os links de
// confirmar/cancelar o agendamento. Nada aqui grava no banco.
require __DIR__ . '/../bootstrap.php';

$removidas = ['BuilderColumnDiffForm', 'BuilderConfigList', 'BuilderConfirmCommandsDiffForm', 'BuilderDatabaseDiffForm',
    'BuilderDatabaseManagerController', 'BuilderMenuUpdate', 'BuilderPageBatchUpdate', 'BuilderPermissionUpdate', 'BuilderTableDiffForm',
    'BuilderViewsDiffForm', 'SystemFrameworkUpdate', 'SystemFilesDiff', 'SystemMenuEditor', 'SystemChangeUnitForm', 'System2FAEmailForm',
    'System2FAForm', 'System2FAGoogleForm', 'TwoFactorEmailService', 'SystemRegistrationForm', 'SystemPasswordResetForm',
    'SystemRequestPasswordResetForm', 'SystemAdministrationDashboard', 'SystemDatabaseExplorer', 'SystemSQLPanel', 'SystemTableList',
    'SystemDataBrowser', 'SystemDataImportForm', 'SystemPHPInfoView', 'SystemInformationView', 'SystemModulesCheckView',
    'SystemPreferenceForm', 'SystemSupportForm', 'SystemNewChatForm', 'SystemNewChatGroupForm', 'SystemProgramList', 'SystemProgramForm',
    'SystemMessageForm', 'SystemMessageFormView', 'SystemMessageList', 'SystemDocumentCategoryFormList', 'SystemDocumentForm',
    'SystemDocumentList', 'SystemDocumentUploadForm', 'SystemSharedDocumentList', 'SystemSqlLogList', 'BuilderPageService', 'BuilderMobileService', 'BuilderCEPService', 'BuilderCustomCssService', 'SystemAddUserGroupForm',
    'SystemRequestLogList', 'SystemRequestLogView', 'SystemLogDashboard', 'SystemPHPErrorLogView', 'SystemSessionDumpView',
    'SystemAccessNotificationLogService', 'PublicForm', 'AgendaCardList', 'AgendamentoClienteCalendarFormView',
    'AgendamentoClienteCalendarForm', 'NovoClienteForm', 'ValidarDocumentoForm', 'DatabaseInstall', 'ExtensionsInstall', 'PathInstall',
    'SummaryDatabaseInstall'];
foreach ($removidas as $c)
{
    ok("saiu: {$c}", !class_exists($c));
}
ok('saiu: controlerTesteSugestao.php', !is_file('app/control/controlerTesteSugestao.php'));

$ficam = ['SystemUserList', 'SystemUserForm', 'SystemGroupList', 'SystemGroupForm', 'SystemUnitList', 'SystemUnitForm', 'SystemProfileForm',
    'SystemProfileView', 'SystemAccessLogList', 'SystemChangeLogView', 'SystemUserMonitorHeaderList', 'NotificationList', 'SystemNotificationList',
    'SystemNotificationFormView', 'LoginForm', 'Home', 'AgendamentoView', 'PreferenciaSistemaForm', 'MessageList', 'BuilderConfigForm',
    'BuilderCNPJService', 'SystemPreference', 'SystemAccessLogService', 'MeusDocumentoList'];
foreach ($ficam as $c)
{
    ok("ficou: {$c}", class_exists($c));
}

// ---------- quem abre sem login ----------
foreach (['LoginForm', 'PublicView', 'Home', 'AgendamentoView'] as $c)
{
    ok("sem login abre: {$c}", AcessoPublicoService::anonimoPode($c, false));
}
foreach (['AgendaCardList', 'ValidarDocumentoForm', 'SystemRegistrationForm', 'SystemModulesCheckView', 'DatabaseInstall', 'Teste471',
          'ProcessoPublicacoesForm', 'ParceirosHeaderList', 'ContratoDateForm', 'MeusDocumentoList', 'ProcessoViewHeaderList',
          'AdiantiUploaderService', 'AdiantiAutocompleteService', 'AdiantiMultiSearchService', 'SystemUserList', ''] as $c)
{
    ok("sem login nao abre: " . ($c ?: '(vazio)'), !AcessoPublicoService::anonimoPode($c, false));
}
foreach (['MeusDocumentoList', 'ProcessoViewHeaderList', 'ProcessosFormView', 'ProcessosFormViewInterno', 'ProcessoPublicacoesTimeLine',
          'AdiantiUploaderService', 'AdiantiAutocompleteService', 'AdiantiMultiSearchService'] as $c)
{
    ok("cliente do portal logado abre: {$c}", AcessoPublicoService::anonimoPode($c, true));
}
ok('cliente do portal nao abre tela da equipe', !AcessoPublicoService::anonimoPode('SystemUserList', true) && !AcessoPublicoService::anonimoPode('ParceirosHeaderList', true));
$engine = (string) file_get_contents('engine.php');
$index = (string) file_get_contents('index.php');
ok('engine.php usa a lista fixa', strpos($engine, 'AcessoPublicoService::anonimoPode') !== false);
ok('index.php usa a lista fixa', strpos($index, 'AcessoPublicoService::anonimoPode') !== false);

// ---------- rotas, menus, temas e configuracao ----------
$ht = (string) file_get_contents('.htaccess');
foreach (['agendas', 'agenda-', 'validar-documento', 'auth/', 'contacts'] as $r)
{
    ok("sem rota {$r}", !preg_match('#^RewriteRule \^(xhr-)?' . preg_quote($r, '#') . '#m', $ht));
}
ok('rotas de confirmar/cancelar, home e login continuam', strpos($ht, 'method=onConfirmar') !== false && strpos($ht, 'method=onCancelar') !== false
    && preg_match('#^RewriteRule \^home\$#m', $ht) && preg_match('#^RewriteRule \^login-adm\$#m', $ht));

preg_match_all('#<action>([A-Za-z0-9_]+)#', (string) file_get_contents('menu.xml'), $m);
$faltam = array_values(array_filter(array_unique($m[1]), function ($c) { return !class_exists($c); }));
igual('menu.xml so aponta para telas que existem', [], $faltam);
foreach (['SystemProgramList', 'SystemSQLPanel', 'SystemDatabaseExplorer', 'SystemMenuEditor', 'SystemLogDashboard', 'SystemSqlLogList'] as $c)
{
    ok("menu sem {$c}", strpos((string) file_get_contents('menu.xml'), $c) === false);
}
foreach (['SystemGroupList', 'SystemUnitList', 'SystemUserList', 'SystemAccessLogList', 'SystemChangeLogView'] as $c)
{
    ok("menu com {$c}", strpos((string) file_get_contents('menu.xml'), $c) !== false);
}

foreach (['app/templates/theme3', 'app/templates/theme3-adminlte3', 'app/templates/theme4', 'app/lib/include/builder/theme3',
          'app/lib/include/builder/theme3-adminlte3', 'app/lib/include/builder/theme4', 'app/control/install', 'app/control/builder/BuilderMenuUpdate.php',
          'app/routes', 'app/controller', 'rest.php', 'mobile.php', 'top_menu.xml', 'menu-dist.xml', 'menu-public-dist.xml', 'menu-mobile.xml',
          'menu-public-mobile.xml', 'app/templates/theme-builder/builder-menu.html'] as $p)
{
    ok("saiu: {$p}", !file_exists($p));
}
$sobrouBanco = array_values(array_filter(glob('app/database/*') ?: [], function ($p) { return basename($p) !== 'migrations'; }));
igual('app/database so com migrations/', [], $sobrouBanco);

$ini = (string) file_get_contents('app/config/application.ini');
ok('application.ini: do [builder] so o servidor da consulta de CNPJ', strpos($ini, 'app_url') === false && strpos($ini, 'manager_url') === false && strpos($ini, 'services_url') !== false);
// log de SQL desligado sem quebrar os app/config/*.php que ainda apontam para ele
$antesSql = class_exists('SystemSqlLogService') ? 'existe' : 'sumiu';
igual('SystemSqlLogService continua (config do servidor aponta para ele)', 'existe', $antesSql);
ok('SystemSqlLogService nao grava nada', strpos((string) file_get_contents('app/service/log/SystemSqlLogService.class.php'), 'INSERT') === false && strpos((string) file_get_contents('app/service/log/SystemSqlLogService.class.php'), 'TTransaction') === false);
ok('application.ini sem auto-cadastro', preg_match('/^user_register\s*=\s*"0"/m', $ini) === 1);
ok('application.ini sem paginas publicas de teste', strpos($ini, 'Teste471') === false && strpos($ini, '[user_public_pages]') === false);
ok('application.ini sem a pagina de entrada de exemplo do app movel', strpos($ini, 'theme_mobile') === false);

// ---------- permissoes (migracao 2026-11-09-limpeza-madbuilder-permissoes.sql) ----------
// logs e monitor de usuarios: so o grupo Admin (APTECH INFO) e, por permissao direta, os socios (3 Kleber, 4 Danielle, 5 Jose Almir)
TTransaction::open('permission');
$c = TTransaction::get();
$controllers = array_column($c->query('SELECT controller FROM system_program')->fetchAll(PDO::FETCH_ASSOC), 'controller');
igual('sem cadastro de programa das telas removidas', [], array_values(array_intersect($controllers, $removidas)));
foreach (['SystemAccessLogList', 'SystemChangeLogView', 'SystemUserMonitorHeaderList'] as $tela)
{
    $grupos = array_map('intval', array_column($c->query("SELECT gp.system_group_id FROM system_group_program gp JOIN system_program p ON p.id = gp.system_program_id WHERE p.controller = '{$tela}' ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC), 'system_group_id'));
    $usuarios = array_map('intval', array_column($c->query("SELECT up.system_user_id FROM system_user_program up JOIN system_program p ON p.id = up.system_program_id WHERE p.controller = '{$tela}' ORDER BY 1")->fetchAll(PDO::FETCH_ASSOC), 'system_user_id'));
    igual("{$tela}: so o grupo Admin", [1], $grupos);
    igual("{$tela}: permissao direta so dos socios", [3, 4, 5], $usuarios);
}
TTransaction::close();
ok('menu com o monitor de usuarios', strpos((string) file_get_contents('menu.xml'), 'SystemUserMonitorHeaderList') !== false);
// conectado = entrou nas ultimas 12 h e nao saiu (log de acesso; o Firebase do Mad Builder nunca foi configurado)
$agora = strtotime('2026-11-09 15:00:00');
$sit = UsuarioOnlineService::calcular([
    ['login' => 'ana',   'login_time' => '2026-11-09 08:00:00', 'logout_time' => null],
    ['login' => 'bia',   'login_time' => '2026-11-09 08:00:00', 'logout_time' => '2026-11-09 09:00:00'],
    ['login' => 'caio',  'login_time' => '2026-11-08 20:00:00', 'logout_time' => ''],
    ['login' => 'ana',   'login_time' => '2026-11-08 08:00:00', 'logout_time' => '2026-11-08 18:00:00'],
], $agora);
ok('online: entrou ha 7 h e nao saiu', $sit['ana']['online'] === true && $sit['ana']['ultimo'] === '2026-11-09 08:00:00', json_encode($sit));
ok('online: saiu', $sit['bia']['online'] === false);
ok('online: entrou ha mais de 12 h sem sair', $sit['caio']['online'] === false && $sit['caio']['ultimo'] === '2026-11-08 20:00:00');
ok('monitor sem Firebase e sem desconectar a forca', strpos((string) file_get_contents('app/control/admin/SystemUserMonitorHeaderList.php'), 'BuilderFirebaseService') === false
    && strpos((string) file_get_contents('app/control/admin/SystemUserMonitorHeaderList.php'), 'onDisconnect') === false);

TSession::setValue('userid', 3);
TSession::setValue('login', 'TESTE');
ob_start();
try { $f = new SystemUserMonitorHeaderList([]); $f->onReload([]); $f->show(); $erro = ''; } catch (Throwable $e) { $erro = $e->getMessage(); }
$tela = ob_get_clean();
ok('monitor de usuarios monta sem erro', $erro === '' && stripos($tela, 'fatal error') === false && stripos($tela, '__adianti_error') === false, $erro . substr($tela, 0, 300));

// ---------- login sem 2 etapas, esqueci a senha, auto-cadastro e aviso de acesso ----------
$login = (string) file_get_contents('app/control/admin/LoginForm.php');
foreach (['two_factor', 'TwoFactor', 'SystemAccessNotificationLogService', 'SystemRequestPasswordResetForm', 'SystemRegistrationForm'] as $t)
{
    ok("LoginForm sem {$t}", strpos($login, $t) === false);
}
ok('perfil sem 2 etapas', strpos((string) file_get_contents('app/resources/system_profile.html'), 'System2FA') === false);

fim();
