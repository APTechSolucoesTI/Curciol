-- Financeiro do agendamento (30/10/2026): a aba Financeiro do agendamento e o Gerar/Desfazer financeiro so para o grupo
-- Financeiro e a Vania (usuaria 23, vania.aniele). Em vez de permissao direta (regra de 23/10: telas do financeiro so por
-- grupo), um grupo proprio "Financeiro do agendamento" so com a janela AgendamentoFinanceiroForm, com a Vania nele.
-- Pode rodar mais de uma vez.
BEGIN;

INSERT INTO system_group (id, name)
SELECT (SELECT COALESCE(max(id), 0) + 1 FROM system_group), 'Financeiro do agendamento'
 WHERE NOT EXISTS (SELECT 1 FROM system_group WHERE name = 'Financeiro do agendamento');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(max(id), 0) + 1 FROM system_group_program), g.id, p.id
  FROM system_group g, system_program p
 WHERE g.name = 'Financeiro do agendamento' AND p.controller = 'AgendamentoFinanceiroForm'
   AND NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = g.id AND x.system_program_id = p.id);

INSERT INTO system_user_group (id, system_user_id, system_group_id)
SELECT (SELECT COALESCE(max(id), 0) + 1 FROM system_user_group), u.id, g.id
  FROM system_users u, system_group g
 WHERE u.id = 23 AND u.login = 'vania.aniele' AND g.name = 'Financeiro do agendamento'
   AND NOT EXISTS (SELECT 1 FROM system_user_group x WHERE x.system_user_id = u.id AND x.system_group_id = g.id);

SELECT g.id, g.name, (SELECT string_agg(p.controller, ', ') FROM system_group_program gp JOIN system_program p ON p.id = gp.system_program_id WHERE gp.system_group_id = g.id) AS programas,
       (SELECT string_agg(u.login, ', ') FROM system_user_group ug JOIN system_users u ON u.id = ug.system_user_id WHERE ug.system_group_id = g.id) AS usuarios
  FROM system_group g WHERE g.name IN ('Financeiro', 'Financeiro do agendamento');

COMMIT;
