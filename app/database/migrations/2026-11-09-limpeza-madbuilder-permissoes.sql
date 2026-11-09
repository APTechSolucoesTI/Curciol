-- 09/11/2026: limpeza do Mad Builder (commit 02f9aac) — permissões. Rodar no banco das permissões (curciol / curciol_homologacao).
-- Pode rodar de novo sem efeito. Não apaga usuário, grupo nem log: só o cadastro das telas que saíram do sistema e as ligações delas.
--  1) telas removidas: saem de system_program e das ligações de grupo e de usuário (página inicial que apontasse para elas fica vazia);
--  2) logs de acesso e de alterações e o monitor de usuários on-line: só o grupo Admin (APTECH INFO) e, por permissão direta,
--     os sócios (3 Kleber, 4 Danielle, 5 José Almir — mesmos ids na produção). Decisão do usuário em 09/11/2026.

BEGIN;

CREATE TEMP TABLE telas_removidas (controller text PRIMARY KEY) ON COMMIT DROP;
INSERT INTO telas_removidas VALUES
 ('BuilderColumnDiffForm'), ('BuilderConfigList'), ('BuilderConfirmCommandsDiffForm'), ('BuilderDatabaseDiffForm'),
 ('BuilderDatabaseManagerController'), ('BuilderMenuUpdate'), ('BuilderPageBatchUpdate'), ('BuilderPermissionUpdate'), ('BuilderTableDiffForm'),
 ('BuilderViewsDiffForm'), ('SystemFrameworkUpdate'), ('SystemFilesDiff'), ('SystemMenuEditor'), ('SystemChangeUnitForm'), ('System2FAEmailForm'),
 ('System2FAForm'), ('System2FAGoogleForm'), ('SystemRegistrationForm'), ('SystemPasswordResetForm'), ('SystemRequestPasswordResetForm'),
 ('SystemAdministrationDashboard'), ('SystemDatabaseExplorer'), ('SystemSQLPanel'), ('SystemTableList'), ('SystemDataBrowser'),
 ('SystemDataImportForm'), ('SystemPHPInfoView'), ('SystemInformationView'), ('SystemModulesCheckView'), ('SystemPreferenceForm'),
 ('SystemSupportForm'), ('SystemNewChatForm'), ('SystemNewChatGroupForm'), ('SystemAddUserGroupForm'), ('SystemProgramList'), ('SystemProgramForm'),
 ('SystemMessageForm'), ('SystemMessageFormView'), ('SystemMessageList'), ('SystemDocumentCategoryFormList'), ('SystemDocumentForm'),
 ('SystemDocumentList'), ('SystemDocumentUploadForm'), ('SystemSharedDocumentList'), ('SystemSqlLogList'), ('SystemRequestLogList'),
 ('SystemRequestLogView'), ('SystemLogDashboard'), ('SystemPHPErrorLogView'), ('SystemSessionDumpView'), ('PublicForm'), ('AgendaCardList'),
 ('AgendamentoClienteCalendarFormView'), ('AgendamentoClienteCalendarForm'), ('NovoClienteForm'), ('ValidarDocumentoForm'),
 ('controlerTesteSugestao'), ('Teste471'), ('BuilderPageService'), ('BuilderCustomCssService');

UPDATE system_users SET frontpage_id = NULL
 WHERE frontpage_id IN (SELECT p.id FROM system_program p JOIN telas_removidas r ON r.controller = p.controller);
DELETE FROM system_group_program WHERE system_program_id IN (SELECT p.id FROM system_program p JOIN telas_removidas r ON r.controller = p.controller);
DELETE FROM system_user_program  WHERE system_program_id IN (SELECT p.id FROM system_program p JOIN telas_removidas r ON r.controller = p.controller);
DELETE FROM system_program       WHERE controller IN (SELECT controller FROM telas_removidas);

-- monitor de usuários on-line: passa a ter cadastro (antes só o admin do framework abria)
INSERT INTO system_program (id, name, controller)
SELECT (SELECT coalesce(max(id), 0) + 1 FROM system_program), 'Usuários on-line', 'SystemUserMonitorHeaderList'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'SystemUserMonitorHeaderList');

-- logs e monitor: grupo Admin + sócios por permissão direta, ninguém mais
CREATE TEMP TABLE telas_admin (controller text PRIMARY KEY) ON COMMIT DROP;
INSERT INTO telas_admin VALUES ('SystemAccessLogList'), ('SystemChangeLogView'), ('SystemUserMonitorHeaderList');

DELETE FROM system_group_program
 WHERE system_program_id IN (SELECT p.id FROM system_program p JOIN telas_admin t ON t.controller = p.controller) AND system_group_id <> 1;
INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT coalesce(max(id), 0) FROM system_group_program) + row_number() OVER (ORDER BY p.id), 1, p.id
  FROM system_program p JOIN telas_admin t ON t.controller = p.controller
 WHERE NOT EXISTS (SELECT 1 FROM system_group_program g WHERE g.system_group_id = 1 AND g.system_program_id = p.id);

DELETE FROM system_user_program
 WHERE system_program_id IN (SELECT p.id FROM system_program p JOIN telas_admin t ON t.controller = p.controller) AND system_user_id NOT IN (3, 4, 5);
INSERT INTO system_user_program (id, system_user_id, system_program_id)
SELECT (SELECT coalesce(max(id), 0) FROM system_user_program) + row_number() OVER (ORDER BY u.id, p.id), u.id, p.id
  FROM system_program p JOIN telas_admin t ON t.controller = p.controller
 CROSS JOIN (SELECT id FROM system_users WHERE id IN (3, 4, 5)) u
 WHERE NOT EXISTS (SELECT 1 FROM system_user_program x WHERE x.system_user_id = u.id AND x.system_program_id = p.id);

COMMIT;
