-- 10/11/2026: conversa do ticket do WhatsApp (ApchatTicketView), aberta pela aba Atendimentos do cliente: quem abre o cadastro
-- do cliente (ClienteForm), por grupo ou por permissao direta, abre a conversa. Pode rodar de novo.
BEGIN;

INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id), 0) + 1 FROM system_program), 'Atendimento pelo WhatsApp (conversa do ticket)', 'ApchatTicketView'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'ApchatTicketView');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_group_program) + row_number() OVER (ORDER BY g.system_group_id), g.system_group_id, novo.id
  FROM (SELECT DISTINCT gp.system_group_id FROM system_group_program gp JOIN system_program p ON p.id = gp.system_program_id AND p.controller = 'ClienteForm') g
  JOIN system_program novo ON novo.controller = 'ApchatTicketView'
 WHERE NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = g.system_group_id AND x.system_program_id = novo.id);

INSERT INTO system_user_program (id, system_user_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_user_program) + row_number() OVER (ORDER BY u.system_user_id), u.system_user_id, novo.id
  FROM (SELECT DISTINCT up.system_user_id FROM system_user_program up JOIN system_program p ON p.id = up.system_program_id AND p.controller = 'ClienteForm') u
  JOIN system_program novo ON novo.controller = 'ApchatTicketView'
 WHERE NOT EXISTS (SELECT 1 FROM system_user_program x WHERE x.system_user_id = u.system_user_id AND x.system_program_id = novo.id);

COMMIT;
