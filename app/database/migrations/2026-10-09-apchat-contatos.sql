-- =====================================================================
-- Curciol - Sincronizacao de clientes com o APChat
-- Banco: escritorio | PostgreSQL
-- Data:  2026-10-09
--
-- 1. escritorio.token_apchat: token da API externa do APChat (Bearer).
--    O VALOR nao vai neste arquivo: e preenchido no cadastro do escritorio
--    (aba Configuracoes > APChat).
-- 2. apchat_contato_log: uma linha por tentativa de sincronizacao de um
--    cliente autorizado (consentimento 'T'). Guarda o resultado, o numero
--    usado e o id do contato no APChat.
-- 3. apchat_contato_view: clientes com consentimento 'T' e o resultado da
--    ultima sincronizacao. Base da tela "Integracao Contatos APChat".
-- 4. Programas ApchatContatoList (tela) e ApchatContatoFormView (lupa)
--    liberados para os grupos Admin e Configuracoes (os mesmos de
--    "Parceiros").
--
-- Script idempotente. Rollback comentado no fim.
-- =====================================================================

BEGIN;

ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS token_apchat TEXT;

CREATE TABLE IF NOT EXISTS apchat_contato_log (
    id                SERIAL PRIMARY KEY,
    pessoa_id         INTEGER NOT NULL REFERENCES pessoa(id) ON DELETE CASCADE,
    origem            VARCHAR(30) NOT NULL,
    operacao          VARCHAR(20) NOT NULL,
    situacao          VARCHAR(20) NOT NULL,
    numero            VARCHAR(20),
    numero_anterior   VARCHAR(20),
    apchat_contato_id INTEGER,
    http_status       INTEGER,
    mensagem          TEXT,
    data_criacao      TIMESTAMP NOT NULL DEFAULT now(),
    usuario_id        INTEGER
);

CREATE INDEX IF NOT EXISTS idx_apchat_contato_log_pessoa
    ON apchat_contato_log (pessoa_id, id DESC);

-- grupo_id 2 = Grupo::CLIENTE
CREATE OR REPLACE VIEW apchat_contato_view AS
SELECT p.id,
       p.nome,
       p.tipo_pessoa_id,
       p.cpf_cnpj,
       p.email,
       p.telefone,
       ult.data_criacao      AS ultima_sincronizacao,
       ult.operacao          AS ultima_operacao,
       ult.situacao          AS situacao,
       ult.mensagem          AS mensagem,
       ult.numero            AS numero_apchat,
       ult.apchat_contato_id AS apchat_contato_id
  FROM pessoa p
  LEFT JOIN LATERAL (
        SELECT l.*
          FROM apchat_contato_log l
         WHERE l.pessoa_id = p.id
         ORDER BY l.id DESC
         LIMIT 1
  ) ult ON TRUE
 WHERE UPPER(TRIM(p.aceita_receber_mensagen_whatsapp)) = 'T'
   AND EXISTS (SELECT 1 FROM pessoa_grupo g WHERE g.pessoa_id = p.id AND g.grupo_id = 2);

INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id), 0) + 1 FROM system_program), 'Integração Contatos APChat', 'ApchatContatoList'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'ApchatContatoList');

INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id), 0) + 1 FROM system_program), 'Contato APChat (visualização)', 'ApchatContatoFormView'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'ApchatContatoFormView');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_group_program) + ROW_NUMBER() OVER (ORDER BY g.id, p.id), g.id, p.id
  FROM system_group g
  JOIN system_program p ON p.controller IN ('ApchatContatoList', 'ApchatContatoFormView')
 WHERE g.name IN ('Admin', 'Configurações')
   AND NOT EXISTS (SELECT 1 FROM system_group_program gp WHERE gp.system_group_id = g.id AND gp.system_program_id = p.id);

COMMIT;

-- =====================================================================
-- ROLLBACK
-- =====================================================================
-- DELETE FROM system_group_program WHERE system_program_id IN (SELECT id FROM system_program WHERE controller IN ('ApchatContatoList', 'ApchatContatoFormView'));
-- DELETE FROM system_program WHERE controller IN ('ApchatContatoList', 'ApchatContatoFormView');
-- DROP VIEW IF EXISTS apchat_contato_view;
-- DROP TABLE IF EXISTS apchat_contato_log;
-- ALTER TABLE escritorio DROP COLUMN IF EXISTS token_apchat;
