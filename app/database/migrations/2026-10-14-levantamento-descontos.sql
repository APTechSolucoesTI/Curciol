-- Descontos do deposito detalhados na ficha do financeiro do levantamento (vao para a prestacao de contas ao cliente).
-- A soma (previdenciario + assistencial + IRRF) e conferida com bruto - MLE ao gerar. Idempotente.
-- Depende de 2026-10-13-levantamento-financeiro.sql.
BEGIN;

ALTER TABLE levantamento_financeiro ADD COLUMN IF NOT EXISTS desconto_previdenciario NUMERIC(14,2);
ALTER TABLE levantamento_financeiro ADD COLUMN IF NOT EXISTS desconto_assistencial NUMERIC(14,2);
ALTER TABLE levantamento_financeiro ADD COLUMN IF NOT EXISTS desconto_irrf NUMERIC(14,2);

COMMIT;

-- ROLLBACK (manual):
-- ALTER TABLE levantamento_financeiro DROP COLUMN desconto_previdenciario, DROP COLUMN desconto_assistencial, DROP COLUMN desconto_irrf;
