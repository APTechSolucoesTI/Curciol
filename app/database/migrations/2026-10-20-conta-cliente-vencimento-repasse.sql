-- Conta a pagar ao cliente no mesmo padrao dos repasses aos colaboradores (20/10/2026):
--   - nasce do recebimento da conta a receber do levantamento (proporcional no recebimento parcial, o restante na
--     quitacao), some quando o recebimento e desquitado e volta ao quitar de novo, com o vencimento original;
--   - cada conta do cliente fica registrada em levantamento_conta_cliente, com o recebimento (extrato) que a gerou.
-- Vencimento das contas de repasse: padrao passa a ser o ultimo dia do mes do recebimento (ou N dias, configuravel).
-- Idempotente. Depende de 2026-10-13-levantamento-financeiro.sql e 2026-10-15-repasse-colaborador.sql.
BEGIN;

-- dados da conta do cliente informados no Gerar financeiro (usados para recria-la)
ALTER TABLE levantamento_financeiro ADD COLUMN IF NOT EXISTS cliente_vencimento DATE;
ALTER TABLE levantamento_financeiro ADD COLUMN IF NOT EXISTS cliente_categoria_conta_id INTEGER;
ALTER TABLE levantamento_financeiro ADD COLUMN IF NOT EXISTS cliente_tipo_documento_id INTEGER;
ALTER TABLE levantamento_financeiro ADD COLUMN IF NOT EXISTS cliente_tipo_pagamento_id INTEGER;

CREATE TABLE IF NOT EXISTS levantamento_conta_cliente (
    id                          SERIAL PRIMARY KEY,
    levantamento_financeiro_id  INTEGER NOT NULL REFERENCES levantamento_financeiro (id) ON DELETE CASCADE,
    conta_pagar_id              INTEGER NOT NULL REFERENCES conta (id) ON DELETE CASCADE,
    lancamento_pagar_id         INTEGER REFERENCES lancamento (id) ON DELETE SET NULL,
    conta_receber_id            INTEGER NOT NULL REFERENCES conta (id) ON DELETE CASCADE,
    lancamento_receber_id       INTEGER REFERENCES lancamento (id) ON DELETE SET NULL,
    extrato_recebimento_id      INTEGER,
    valor                       NUMERIC(14,2) NOT NULL,
    valor_recebido              NUMERIC(14,2),
    data_recebimento            DATE,
    data_criacao                TIMESTAMP NOT NULL DEFAULT now(),
    criacao_user_id             INTEGER
);
CREATE UNIQUE INDEX IF NOT EXISTS uk_levantamento_conta_cliente_pagar ON levantamento_conta_cliente (conta_pagar_id);
CREATE INDEX IF NOT EXISTS ix_levantamento_conta_cliente_extrato ON levantamento_conta_cliente (extrato_recebimento_id);
CREATE INDEX IF NOT EXISTS ix_levantamento_conta_cliente_ficha ON levantamento_conta_cliente (levantamento_financeiro_id);

-- financeiros ja gerados: guarda os dados da conta do cliente e registra a conta existente
UPDATE levantamento_financeiro lf
   SET cliente_vencimento = l.dt_vencimento,
       cliente_categoria_conta_id = c.categoria_conta_id,
       cliente_tipo_documento_id = c.tipo_documento_financeiro_id,
       cliente_tipo_pagamento_id = l.tipo_pagamento_id
  FROM conta c
  JOIN LATERAL (SELECT * FROM lancamento WHERE conta_id = c.id ORDER BY parcela, id LIMIT 1) l ON true
 WHERE c.id = lf.conta_pagar_id
   AND lf.cliente_vencimento IS NULL;

INSERT INTO levantamento_conta_cliente (levantamento_financeiro_id, conta_pagar_id, lancamento_pagar_id, conta_receber_id,
                                        lancamento_receber_id, extrato_recebimento_id, valor, valor_recebido, data_recebimento, criacao_user_id)
SELECT lf.id, lf.conta_pagar_id, lp.id, lf.conta_receber_id, lr.id, lr.extrato_id, c.total_conta, lf.valor_levantado, lf.data_levantamento, lf.criacao_user_id
  FROM levantamento_financeiro lf
  JOIN conta c ON c.id = lf.conta_pagar_id
  LEFT JOIN LATERAL (SELECT id FROM lancamento WHERE conta_id = lf.conta_pagar_id ORDER BY parcela, id LIMIT 1) lp ON true
  LEFT JOIN LATERAL (SELECT id, extrato_id FROM lancamento WHERE conta_id = lf.conta_receber_id ORDER BY parcela, id LIMIT 1) lr ON true
 WHERE lf.conta_receber_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM levantamento_conta_cliente x WHERE x.conta_pagar_id = lf.conta_pagar_id);

-- vencimento das contas de repasse: ULTIMO_DIA_MES (padrao) ou DIAS (repasse_dias_vencimento depois do recebimento)
ALTER TABLE financeiro_configuracao ADD COLUMN IF NOT EXISTS repasse_vencimento_tipo VARCHAR(20) NOT NULL DEFAULT 'ULTIMO_DIA_MES';

COMMIT;

-- ROLLBACK (manual):
-- DROP TABLE levantamento_conta_cliente;
-- ALTER TABLE levantamento_financeiro DROP COLUMN cliente_vencimento, DROP COLUMN cliente_categoria_conta_id,
--   DROP COLUMN cliente_tipo_documento_id, DROP COLUMN cliente_tipo_pagamento_id;
-- ALTER TABLE financeiro_configuracao DROP COLUMN repasse_vencimento_tipo;
