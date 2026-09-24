-- =====================================================================
-- Curciol - Detalhamento da etapa
-- Banco: escritorio | PostgreSQL
-- Data:  2026-09-24
--
-- A coluna publicacao_etapa.detalhamento entrou pelo cadastro de etapas
-- (campo "Detalhamento", exibido na timeline e no balao da aba Andamentos)
-- e foi criada na homologacao direto pelo Mad Builder, sem script. Este
-- arquivo registra a mudanca para os demais ambientes.
--
-- Pode ser aplicado antes ou depois das migrations de pre-processo.
-- Script idempotente. Rollback comentado no fim.
-- =====================================================================

BEGIN;

ALTER TABLE publicacao_etapa ADD COLUMN IF NOT EXISTS detalhamento TEXT;

COMMIT;

-- =====================================================================
-- ROLLBACK
-- =====================================================================
-- ALTER TABLE publicacao_etapa DROP COLUMN IF EXISTS detalhamento;
