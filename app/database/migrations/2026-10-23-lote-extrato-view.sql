-- Cortina "Ver lote" do Extrato (LoteExtratoView, 23/10/2026): quem acessa o Extrato acessa a cortina.
-- Mesmos grupos e mesmos usuarios (permissao direta) da tela ExtratoList. Pode rodar mais de uma vez.
BEGIN;

INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_program) + 1, 'Lote do extrato', 'LoteExtratoView'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'LoteExtratoView');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_group_program) + ROW_NUMBER() OVER (ORDER BY par.system_group_id), par.system_group_id, par.program_id
  FROM (SELECT DISTINCT gp.system_group_id, p.id AS program_id
          FROM system_group_program gp
          JOIN system_program o ON o.id = gp.system_program_id AND o.controller = 'ExtratoList'
          JOIN system_program p ON p.controller = 'LoteExtratoView'
         WHERE NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = gp.system_group_id AND x.system_program_id = p.id)) par;

INSERT INTO system_user_program (id, system_user_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_user_program) + ROW_NUMBER() OVER (ORDER BY par.system_user_id), par.system_user_id, par.program_id
  FROM (SELECT DISTINCT up.system_user_id, p.id AS program_id
          FROM system_user_program up
          JOIN system_program o ON o.id = up.system_program_id AND o.controller = 'ExtratoList'
          JOIN system_program p ON p.controller = 'LoteExtratoView'
         WHERE NOT EXISTS (SELECT 1 FROM system_user_program x WHERE x.system_user_id = up.system_user_id AND x.system_program_id = p.id)) par;

COMMIT;
