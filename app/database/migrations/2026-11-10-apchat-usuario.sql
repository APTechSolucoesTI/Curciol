-- 10/11/2026: usuarios da Curciol ligados aos atendentes do APChat (balao de mensagens no topo). Pode rodar de novo.
BEGIN;

CREATE TABLE IF NOT EXISTS apchat_usuario (
    id                serial PRIMARY KEY,
    system_user_id    integer NOT NULL UNIQUE,
    apchat_usuario_id integer UNIQUE,
    avisos            char(1) NOT NULL DEFAULT 'S' CHECK (avisos IN ('S', 'N')),
    mostrar_abertos   char(1) NOT NULL DEFAULT 'N' CHECK (mostrar_abertos IN ('S', 'N')),
    atualizado_em     timestamp NOT NULL DEFAULT now()
);
COMMENT ON TABLE apchat_usuario IS 'Usuario da Curciol <-> atendente do APChat (tenant 21), avisos do balao e lista de tickets abertos (10/11/2026)';

-- tela Configuracoes > APChat > Usuarios: mesmos grupos da tela "Contatos sem vinculo"
INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id), 0) + 1 FROM system_program), 'Usuários do APChat', 'ApchatUsuarioList'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'ApchatUsuarioList');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_group_program) + row_number() OVER (ORDER BY g.system_group_id), g.system_group_id, novo.id
  FROM system_group_program g
  JOIN system_program sv ON sv.id = g.system_program_id AND sv.controller = 'ApchatContatoSemVinculoList'
  JOIN system_program novo ON novo.controller = 'ApchatUsuarioList'
 WHERE NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = g.system_group_id AND x.system_program_id = novo.id);

COMMIT;
