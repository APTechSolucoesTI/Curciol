-- Contatos sem vinculo do APChat (06/11/2026): lista, conversa, CSV e vincular so para quem tem acesso ao Financeiro: grupos
-- Admin e Financeiro e os usuarios do grupo "Financeiro do agendamento" (a Vania). Ela recebe por usuario, sem mudar o grupo, que
-- tem de continuar so com a janela do financeiro. Rodar DEPOIS de 2026-11-03-apchat-fila-sem-vinculo.sql, que dava a tela a quase
-- todos os grupos; se aquela rodar de novo, rode esta outra vez. Quem esta logado precisa entrar de novo. Pode rodar mais de uma vez.
BEGIN;

DELETE FROM system_group_program
 WHERE system_program_id IN (SELECT id FROM system_program WHERE controller IN ('ApchatContatoSemVinculoList', 'ApchatContatoConversaView', 'ApchatVincularForm'))
   AND system_group_id NOT IN (SELECT id FROM system_group WHERE name IN ('Admin', 'Financeiro'));

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_group_program) + ROW_NUMBER() OVER (ORDER BY g.id, p.id), g.id, p.id
  FROM system_group g
  JOIN system_program p ON p.controller IN ('ApchatContatoSemVinculoList', 'ApchatContatoConversaView', 'ApchatVincularForm')
 WHERE g.name IN ('Admin', 'Financeiro')
   AND NOT EXISTS (SELECT 1 FROM system_group_program gp WHERE gp.system_group_id = g.id AND gp.system_program_id = p.id);

INSERT INTO system_user_program (id, system_user_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_user_program) + ROW_NUMBER() OVER (ORDER BY ug.system_user_id, p.id), ug.system_user_id, p.id
  FROM system_user_group ug
  JOIN system_group g ON g.id = ug.system_group_id AND g.name = 'Financeiro do agendamento'
  JOIN system_program p ON p.controller IN ('ApchatContatoSemVinculoList', 'ApchatContatoConversaView', 'ApchatVincularForm')
 WHERE NOT EXISTS (SELECT 1 FROM system_user_program up WHERE up.system_user_id = ug.system_user_id AND up.system_program_id = p.id);

COMMIT;
