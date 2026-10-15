-- =====================================================================
-- Curciol - Processo vinculado a ele mesmo (pai e filho com o mesmo numero)
-- Banco: escritorio (homologacao: curciol_homologacao) | PostgreSQL
-- Data:  2026-10-15
--
-- Publicacoes do eproc trazem "Nº <numero>/SP" no texto. A importacao lia a
-- barra como incidente e gravava o proprio numero como numero do processo
-- principal. Ao criar o processo, ele era vinculado a si mesmo em
-- processo_vinculo (principal = incidente).
--
-- O codigo deixou de gravar e de vincular assim. Este script limpa o que
-- ficou:
--   1. apaga os vinculos de um processo com ele mesmo;
--   2. limpa numero_processo_principal quando e igual ao numero do proprio
--      processo da publicacao.
--
-- Antes de mudar, guarda as linhas afetadas em duas tabelas de backup.
-- Script idempotente. Rollback comentado no fim.
-- =====================================================================

BEGIN;

CREATE TABLE IF NOT EXISTS bkp_20261015_processo_autovinculo AS
    SELECT * FROM processo_vinculo WHERE false;

INSERT INTO bkp_20261015_processo_autovinculo
SELECT v.* FROM processo_vinculo v
 WHERE v.processo_principal_id = v.processo_incidente_id
   AND NOT EXISTS (SELECT 1 FROM bkp_20261015_processo_autovinculo b WHERE b.id = v.id);

DELETE FROM processo_vinculo
 WHERE processo_principal_id = processo_incidente_id;

CREATE TABLE IF NOT EXISTS bkp_20261015_publicacao_principal AS
    SELECT id, numero_processo_principal FROM publicacao WHERE false;

INSERT INTO bkp_20261015_publicacao_principal
SELECT p.id, p.numero_processo_principal FROM publicacao p
 WHERE trim(p.numero_processo_principal) = trim(p.numero_unico_processo)
   AND NOT EXISTS (SELECT 1 FROM bkp_20261015_publicacao_principal b WHERE b.id = p.id);

UPDATE publicacao
   SET numero_processo_principal = NULL
 WHERE trim(numero_processo_principal) = trim(numero_unico_processo);

COMMIT;

-- ---------------------------------------------------------------------
-- Rollback
-- ---------------------------------------------------------------------
-- BEGIN;
-- INSERT INTO processo_vinculo SELECT b.* FROM bkp_20261015_processo_autovinculo b
--  WHERE NOT EXISTS (SELECT 1 FROM processo_vinculo v WHERE v.id = b.id);
-- UPDATE publicacao p SET numero_processo_principal = b.numero_processo_principal
--   FROM bkp_20261015_publicacao_principal b WHERE b.id = p.id;
-- COMMIT;
