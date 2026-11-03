-- APChat (03/11/2026): fila de reenvio e tela "Contatos sem vinculo".
-- apchat_fila: numero que nao foi para o APChat (APChat fora, erro) ou transferencia de historico que falhou; uma tarefa
--   agendada (cmd.php "class=ApchatFilaService&method=processar") reenvia sozinha, lendo os dados na hora do reenvio.
-- apchat_contato_ignorado: contato do APChat sem vinculo marcado como "Ignorar" (propaganda, engano) na tela nova.
-- Telas novas liberadas para TODOS os grupos (decisao do usuario: por enquanto todo mundo tem acesso).
-- Pode rodar mais de uma vez.
BEGIN;

CREATE TABLE IF NOT EXISTS apchat_fila (
    id                SERIAL PRIMARY KEY,
    motivo            VARCHAR(20) NOT NULL,          -- ENVIO | TRANSFERENCIA
    numero            VARCHAR(20) NOT NULL,          -- 55 + DDD + numero (o numero novo, na transferencia)
    pessoa_id         INTEGER REFERENCES pessoa(id) ON DELETE SET NULL,
    contato_antigo_id INTEGER,                       -- TRANSFERENCIA: contato do APChat do numero antigo
    numero_antigo     VARCHAR(20),                   -- TRANSFERENCIA: o numero antigo
    tentativas        INTEGER NOT NULL DEFAULT 0,
    proxima_tentativa TIMESTAMP NOT NULL DEFAULT now(),
    ultimo_erro       TEXT,
    criado_em         TIMESTAMP NOT NULL DEFAULT now(),
    atualizado_em     TIMESTAMP NOT NULL DEFAULT now()
);
-- um pendente por numero e motivo (de novo na fila = so atualiza)
CREATE UNIQUE INDEX IF NOT EXISTS uk_apchat_fila_pendente ON apchat_fila (motivo, numero, COALESCE(contato_antigo_id, 0));
CREATE INDEX IF NOT EXISTS ix_apchat_fila_proxima ON apchat_fila (proxima_tentativa);

CREATE TABLE IF NOT EXISTS apchat_contato_ignorado (
    contato_id  INTEGER PRIMARY KEY,                 -- "Contacts".id no APChat
    numero      VARCHAR(20),
    nome        VARCHAR(255),
    usuario_id  INTEGER,
    criado_em   TIMESTAMP NOT NULL DEFAULT now()
);

INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id), 0) + 1 FROM system_program), 'Contatos do APChat sem vínculo', 'ApchatContatoSemVinculoList'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'ApchatContatoSemVinculoList');
INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id), 0) + 1 FROM system_program), 'Conversa do contato no APChat', 'ApchatContatoConversaView'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'ApchatContatoConversaView');
INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id), 0) + 1 FROM system_program), 'Vincular contato do APChat', 'ApchatVincularForm'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'ApchatVincularForm');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_group_program) + ROW_NUMBER() OVER (ORDER BY g.id, p.id), g.id, p.id
  FROM system_group g
  JOIN system_program p ON p.controller IN ('ApchatContatoSemVinculoList', 'ApchatContatoConversaView', 'ApchatVincularForm')
 WHERE g.name <> 'Financeiro do agendamento'   -- grupo so com a janela do financeiro (30/10); a Vania tem a tela pelos outros grupos
   AND NOT EXISTS (SELECT 1 FROM system_group_program gp WHERE gp.system_group_id = g.id AND gp.system_program_id = p.id);
DELETE FROM system_group_program
 WHERE system_group_id IN (SELECT id FROM system_group WHERE name = 'Financeiro do agendamento')
   AND system_program_id IN (SELECT id FROM system_program WHERE controller IN ('ApchatContatoSemVinculoList', 'ApchatContatoConversaView', 'ApchatVincularForm'));

COMMIT;

-- =====================================================================
-- ROLLBACK
-- =====================================================================
-- DELETE FROM system_group_program WHERE system_program_id IN (SELECT id FROM system_program WHERE controller IN ('ApchatContatoSemVinculoList', 'ApchatContatoConversaView', 'ApchatVincularForm'));
-- DELETE FROM system_program WHERE controller IN ('ApchatContatoSemVinculoList', 'ApchatContatoConversaView', 'ApchatVincularForm');
-- DROP TABLE IF EXISTS apchat_contato_ignorado;
-- DROP TABLE IF EXISTS apchat_fila;
