-- 10/11/2026: APMail na Curciol (envelope do topo) e tela unica "Usuarios das integracoes" (APChat + APMail). Pode rodar de novo.
--  1) escritorio: endereco e token da API do APMail (o token so no banco, nunca no codigo);
--  2) apmail_usuario: e-mail do usuario no APMail (vazio = o do cadastro do usuario) e avisos S/N;
--  3) a tela ApchatUsuarioList vira "Usuarios das integracoes" (menu Administracao): so o grupo Admin, sem permissao direta (11/11/2026).
BEGIN;

ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS url_apmail text;
ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS token_apmail text;
COMMENT ON COLUMN escritorio.url_apmail IS 'Endereco do APMail (vazio = https://app.apmail.com.br)';
COMMENT ON COLUMN escritorio.token_apmail IS 'Token da API de notificacoes do APMail (Painel Administrativo > Integracoes > Tokens de API)';

CREATE TABLE IF NOT EXISTS apmail_usuario (
    id             serial PRIMARY KEY,
    system_user_id integer NOT NULL UNIQUE,
    email          text,
    avisos         char(1) NOT NULL DEFAULT 'S' CHECK (avisos IN ('S', 'N')),
    atualizado_em  timestamp NOT NULL DEFAULT now()
);
COMMENT ON TABLE apmail_usuario IS 'Usuario da Curciol no APMail: e-mail (vazio = o do cadastro) e avisos do envelope (10/11/2026)';

UPDATE system_program SET name = 'Usuários das integrações' WHERE controller = 'ApchatUsuarioList';
DELETE FROM system_group_program
 WHERE system_program_id IN (SELECT id FROM system_program WHERE controller = 'ApchatUsuarioList') AND system_group_id <> 1;
INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT coalesce(max(id), 0) + 1 FROM system_group_program), 1, p.id
  FROM system_program p
 WHERE p.controller = 'ApchatUsuarioList'
   AND NOT EXISTS (SELECT 1 FROM system_group_program g WHERE g.system_group_id = 1 AND g.system_program_id = p.id);
DELETE FROM system_user_program
 WHERE system_program_id IN (SELECT id FROM system_program WHERE controller = 'ApchatUsuarioList');

COMMIT;
