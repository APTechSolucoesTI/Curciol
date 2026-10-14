-- Configuracao do financeiro: % de custo de NF padrao do levantamento judicial.
-- Tambem libera os cadastros do levantamento (agora em Configuracoes > Financeiro) para os grupos que ja acessam "Contas Caixa".
-- Idempotente. Depende de 2026-10-13-levantamento-financeiro.sql.
BEGIN;

CREATE TABLE IF NOT EXISTS financeiro_configuracao (
    id                  SERIAL PRIMARY KEY,
    pct_nf_padrao       NUMERIC(12,8) NOT NULL DEFAULT 16 CHECK (pct_nf_padrao >= 0 AND pct_nf_padrao <= 100),
    data_modificacao    TIMESTAMP,
    modificacao_user_id INTEGER
);

INSERT INTO financeiro_configuracao (id, pct_nf_padrao)
SELECT 1, 16
 WHERE NOT EXISTS (SELECT 1 FROM financeiro_configuracao WHERE id = 1);
SELECT setval(pg_get_serial_sequence('financeiro_configuracao', 'id'), GREATEST((SELECT MAX(id) FROM financeiro_configuracao), 1));

INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_program) + 1, 'Configuração do financeiro', 'FinanceiroConfiguracaoForm'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'FinanceiroConfiguracaoForm');

-- grupos de "Contas Caixa" (ContaCaixaList) recebem a configuracao e os cadastros do levantamento
INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_group_program) + ROW_NUMBER() OVER (ORDER BY par.system_group_id, par.program_id), par.system_group_id, par.program_id
  FROM (SELECT DISTINCT gp.system_group_id, p.id AS program_id
          FROM system_group_program gp
          JOIN system_program o ON o.id = gp.system_program_id AND o.controller = 'ContaCaixaList'
          JOIN system_program p ON p.controller IN ('FinanceiroConfiguracaoForm','BeneficiarioCreditoList','BeneficiarioCreditoForm','ParceriaLevantamentoList','ParceriaLevantamentoForm')
         WHERE NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = gp.system_group_id AND x.system_program_id = p.id)) par;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM system_group_program gp JOIN system_program sp ON sp.id = gp.system_program_id WHERE sp.controller = 'FinanceiroConfiguracaoForm') THEN
        RAISE EXCEPTION 'FinanceiroConfiguracaoForm ficou sem nenhum grupo (ContaCaixaList sem grupos?)';
    END IF;
END $$;

COMMIT;

-- ROLLBACK (manual):
-- DELETE FROM system_group_program WHERE system_program_id = (SELECT id FROM system_program WHERE controller = 'FinanceiroConfiguracaoForm');
-- DELETE FROM system_program WHERE controller = 'FinanceiroConfiguracaoForm';
-- DROP TABLE financeiro_configuracao;
