-- Repasses a colaboradores: conta a pagar por colaborador a cada recebimento de conta a receber com repasse,
-- pagamento em lote e configuracao (vencimento, categoria e documento padrao). Idempotente.
-- Depende de 2026-10-13-levantamento-financeiro.sql e 2026-10-14-financeiro-configuracao.sql.
BEGIN;

CREATE TABLE IF NOT EXISTS lote_pagamento (
    id                SERIAL PRIMARY KEY,
    pessoa_id         INTEGER NOT NULL REFERENCES pessoa(id),
    data_pagamento    DATE NOT NULL,
    conta_caixa_id    INTEGER NOT NULL REFERENCES conta_caixa(id),
    tipo_pagamento_id INTEGER REFERENCES tipo_pagamento(id),
    quantidade        INTEGER NOT NULL,
    total             NUMERIC(14,2) NOT NULL,
    historico         TEXT,
    data_criacao      TIMESTAMP NOT NULL DEFAULT now(),
    criacao_user_id   INTEGER
);

CREATE TABLE IF NOT EXISTS repasse_colaborador (
    id                         SERIAL PRIMARY KEY,
    pessoa_id                  INTEGER NOT NULL REFERENCES pessoa(id),
    conta_pagar_id             INTEGER NOT NULL REFERENCES conta(id),
    lancamento_pagar_id        INTEGER NOT NULL REFERENCES lancamento(id),
    conta_receber_id           INTEGER NOT NULL REFERENCES conta(id),
    lancamento_receber_id      INTEGER NOT NULL REFERENCES lancamento(id),
    extrato_recebimento_id     INTEGER,            -- registro do extrato do recebimento que gerou (sem FK: o desquitar apaga o extrato depois de apagar o repasse)
    levantamento_financeiro_id INTEGER REFERENCES levantamento_financeiro(id),
    percentual                 NUMERIC(12,8),
    valor_recebido             NUMERIC(14,2) NOT NULL,
    valor                      NUMERIC(14,2) NOT NULL CHECK (valor > 0),
    data_recebimento           DATE NOT NULL,
    data_criacao               TIMESTAMP NOT NULL DEFAULT now(),
    criacao_user_id            INTEGER
);
CREATE UNIQUE INDEX IF NOT EXISTS uk_repasse_colaborador_conta_pagar ON repasse_colaborador (conta_pagar_id);
CREATE INDEX IF NOT EXISTS idx_repasse_colaborador_pessoa ON repasse_colaborador (pessoa_id);
CREATE INDEX IF NOT EXISTS idx_repasse_colaborador_lanc_receber ON repasse_colaborador (lancamento_receber_id);
CREATE INDEX IF NOT EXISTS idx_repasse_colaborador_extrato ON repasse_colaborador (extrato_recebimento_id);

ALTER TABLE extrato ADD COLUMN IF NOT EXISTS lote_pagamento_id INTEGER REFERENCES lote_pagamento(id);
CREATE INDEX IF NOT EXISTS idx_extrato_lote_pagamento ON extrato (lote_pagamento_id);

-- configuracao
ALTER TABLE financeiro_configuracao ADD COLUMN IF NOT EXISTS repasse_dias_vencimento INTEGER NOT NULL DEFAULT 0;
ALTER TABLE financeiro_configuracao ADD COLUMN IF NOT EXISTS repasse_categoria_conta_id INTEGER REFERENCES categoria_conta(id);
ALTER TABLE financeiro_configuracao ADD COLUMN IF NOT EXISTS repasse_tipo_documento_id INTEGER REFERENCES tipo_documento_financeiro(id);

-- documento novo "Repasse Colaborador" (conta a pagar), no molde do "Repasse Cliente"
INSERT INTO tipo_documento_financeiro (nome, tipo_conta_id, codigo, gera_codigo, padrao_id, data_criacao)
SELECT 'Repasse Colaborador', 2, 'RCol', 'S', 1, now()
 WHERE NOT EXISTS (SELECT 1 FROM tipo_documento_financeiro WHERE nome = 'Repasse Colaborador');

UPDATE financeiro_configuracao
   SET repasse_categoria_conta_id = (SELECT id FROM categoria_conta WHERE upper(nome) = 'DESPESAS COM PESSOAL - ADVOGADOS ASSOCIADOS' ORDER BY id LIMIT 1)
 WHERE id = 1 AND repasse_categoria_conta_id IS NULL;
UPDATE financeiro_configuracao
   SET repasse_tipo_documento_id = (SELECT id FROM tipo_documento_financeiro WHERE nome = 'Repasse Colaborador' ORDER BY id LIMIT 1)
 WHERE id = 1 AND repasse_tipo_documento_id IS NULL;

-- permissoes: grupos de Contas a pagar
INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_program) + ROW_NUMBER() OVER (ORDER BY n.controller), n.nome, n.controller
  FROM (VALUES ('Repasses a colaboradores', 'RepasseColaboradorList'), ('Pagar repasses selecionados', 'RepasseColaboradorPagarForm')) n(nome, controller)
 WHERE NOT EXISTS (SELECT 1 FROM system_program sp WHERE sp.controller = n.controller);

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_group_program) + ROW_NUMBER() OVER (ORDER BY par.system_group_id, par.program_id), par.system_group_id, par.program_id
  FROM (SELECT DISTINCT gp.system_group_id, p.id AS program_id
          FROM system_group_program gp
          JOIN system_program o ON o.id = gp.system_program_id AND o.controller = 'ContaPagarList'
          JOIN system_program p ON p.controller IN ('RepasseColaboradorList', 'RepasseColaboradorPagarForm')
         WHERE NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = gp.system_group_id AND x.system_program_id = p.id)) par;

DO $$
BEGIN
    IF (SELECT repasse_categoria_conta_id IS NULL OR repasse_tipo_documento_id IS NULL FROM financeiro_configuracao WHERE id = 1) THEN
        RAISE EXCEPTION 'Configuracao do financeiro sem categoria/documento padrao do repasse';
    END IF;
    IF NOT EXISTS (SELECT 1 FROM system_group_program gp JOIN system_program sp ON sp.id = gp.system_program_id WHERE sp.controller = 'RepasseColaboradorList') THEN
        RAISE EXCEPTION 'RepasseColaboradorList ficou sem grupo';
    END IF;
END $$;

COMMIT;

-- ROLLBACK (manual, so sem repasses gerados):
-- DELETE FROM system_group_program WHERE system_program_id IN (SELECT id FROM system_program WHERE controller IN ('RepasseColaboradorList','RepasseColaboradorPagarForm'));
-- DELETE FROM system_program WHERE controller IN ('RepasseColaboradorList','RepasseColaboradorPagarForm');
-- ALTER TABLE extrato DROP COLUMN lote_pagamento_id; DROP TABLE repasse_colaborador; DROP TABLE lote_pagamento;
-- ALTER TABLE financeiro_configuracao DROP COLUMN repasse_dias_vencimento, DROP COLUMN repasse_categoria_conta_id, DROP COLUMN repasse_tipo_documento_id;
