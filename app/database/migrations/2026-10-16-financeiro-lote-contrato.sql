-- Acoes em lote em Contas a pagar/receber (quitar e compensar, com desfazer em 30 min),
-- conta-caixa prevista na conta (gerar financeiro do contrato) e permissoes das janelas novas. Idempotente.
-- Depende de 2026-10-15-repasse-colaborador.sql.
BEGIN;

-- conta-caixa prevista: sugerida na quitacao
ALTER TABLE conta ADD COLUMN IF NOT EXISTS conta_caixa_id INTEGER REFERENCES conta_caixa(id);

-- lote_pagamento vira o registro geral de acao em lote
ALTER TABLE lote_pagamento ALTER COLUMN pessoa_id DROP NOT NULL;
ALTER TABLE lote_pagamento ALTER COLUMN conta_caixa_id DROP NOT NULL;
ALTER TABLE lote_pagamento ADD COLUMN IF NOT EXISTS acao VARCHAR(10) NOT NULL DEFAULT 'quitar';
ALTER TABLE lote_pagamento ADD COLUMN IF NOT EXISTS tipo_conta_id INTEGER;
ALTER TABLE lote_pagamento ADD COLUMN IF NOT EXISTS compensado CHAR(1);
ALTER TABLE lote_pagamento ADD COLUMN IF NOT EXISTS desfeito_em TIMESTAMP;
ALTER TABLE lote_pagamento ADD COLUMN IF NOT EXISTS desfeito_user_id INTEGER;
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_lote_pagamento_acao') THEN
        ALTER TABLE lote_pagamento ADD CONSTRAINT ck_lote_pagamento_acao CHECK (acao IN ('quitar', 'compensar'));
    END IF;
END $$;
UPDATE lote_pagamento SET tipo_conta_id = 2 WHERE tipo_conta_id IS NULL;   -- lotes antigos: repasses (contas a pagar)
CREATE INDEX IF NOT EXISTS idx_lote_pagamento_usuario ON lote_pagamento (criacao_user_id, tipo_conta_id, data_criacao);

-- o que cada lote movimentou (uma linha por parcela/linha do extrato)
CREATE TABLE IF NOT EXISTS lote_pagamento_item (
    id                SERIAL PRIMARY KEY,
    lote_pagamento_id INTEGER NOT NULL REFERENCES lote_pagamento(id) ON DELETE CASCADE,
    lancamento_id     INTEGER NOT NULL,
    extrato_id        INTEGER NOT NULL,   -- sem FK: desfazer a quitacao apaga a linha do extrato
    valor             NUMERIC(14,2) NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_lote_pagamento_item_lote ON lote_pagamento_item (lote_pagamento_id);
CREATE INDEX IF NOT EXISTS idx_lote_pagamento_item_extrato ON lote_pagamento_item (extrato_id);

-- itens dos lotes de repasse ja existentes
INSERT INTO lote_pagamento_item (lote_pagamento_id, lancamento_id, extrato_id, valor)
SELECT x.lote_pagamento_id, x.lancamento_id, x.id, x.saida_valor
  FROM extrato x
 WHERE x.lote_pagamento_id IS NOT NULL
   AND NOT EXISTS (SELECT 1 FROM lote_pagamento_item i WHERE i.extrato_id = x.id);

-- permissoes das janelas novas: mesmos grupos das telas de origem
INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_program) + ROW_NUMBER() OVER (ORDER BY n.controller), n.nome, n.controller
  FROM (VALUES ('Quitar contas em lote', 'LoteQuitarForm'),
               ('Compensar contas em lote', 'LoteCompensarForm'),
               ('Parâmetros e filtros do extrato', 'ExtratoParametrosForm')) n(nome, controller)
 WHERE NOT EXISTS (SELECT 1 FROM system_program sp WHERE sp.controller = n.controller);

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_group_program) + ROW_NUMBER() OVER (ORDER BY par.system_group_id, par.program_id), par.system_group_id, par.program_id
  FROM (SELECT DISTINCT gp.system_group_id, p.id AS program_id
          FROM system_group_program gp
          JOIN system_program o ON o.id = gp.system_program_id
          JOIN system_program p ON (o.controller IN ('ContaPagarList', 'ContaReceberList') AND p.controller IN ('LoteQuitarForm', 'LoteCompensarForm'))
                                OR (o.controller = 'ExtratoList' AND p.controller = 'ExtratoParametrosForm')
         WHERE NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = gp.system_group_id AND x.system_program_id = p.id)) par;

-- a janela de pagar repasses saiu (pagamento agora e pela acao em lote de Contas a pagar)
DELETE FROM system_group_program WHERE system_program_id IN (SELECT id FROM system_program WHERE controller = 'RepasseColaboradorPagarForm');
DELETE FROM system_program WHERE controller = 'RepasseColaboradorPagarForm';

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'conta' AND column_name = 'conta_caixa_id') THEN
        RAISE EXCEPTION 'conta.conta_caixa_id nao foi criada';
    END IF;
    IF EXISTS (SELECT 1 FROM extrato x WHERE x.lote_pagamento_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM lote_pagamento_item i WHERE i.extrato_id = x.id)) THEN
        RAISE EXCEPTION 'linha de lote sem item';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM system_group_program gp JOIN system_program sp ON sp.id = gp.system_program_id WHERE sp.controller = 'LoteQuitarForm')
       OR NOT EXISTS (SELECT 1 FROM system_group_program gp JOIN system_program sp ON sp.id = gp.system_program_id WHERE sp.controller = 'ExtratoParametrosForm') THEN
        RAISE EXCEPTION 'janelas novas ficaram sem grupo';
    END IF;
END $$;

COMMIT;
