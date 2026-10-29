-- Gerar financeiro do agendamento (29/10/2026).
-- O botao "Gerar financeiro" do agendamento cria a conta a receber (uma parcela) com a divisao da parceria de repasse
-- na parcela (lancamento_profissional): quitada na hora gera os repasses; em aberto, gera quando for quitada (mesmo
-- fluxo das outras contas). agendamento_financeiro guarda o que foi gerado (um por agendamento).
-- conta.agendamento_id: a conta veio do agendamento (conta a receber e os repasses dela) - coluna Origem das contas.
-- Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE conta ADD COLUMN IF NOT EXISTS agendamento_id INTEGER;
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_conta_agendamento') THEN
        ALTER TABLE conta ADD CONSTRAINT fk_conta_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamento (id);
    END IF;
END $$;
CREATE INDEX IF NOT EXISTS ix_conta_agendamento ON conta (agendamento_id) WHERE agendamento_id IS NOT NULL;
COMMENT ON COLUMN conta.agendamento_id IS 'Conta gerada pelo financeiro do agendamento (a receber e os repasses dela)';

CREATE TABLE IF NOT EXISTS agendamento_financeiro (
    id                        SERIAL PRIMARY KEY,
    agendamento_id            INTEGER NOT NULL REFERENCES agendamento (id),
    conta_receber_id          INTEGER NOT NULL REFERENCES conta (id),
    lancamento_id             INTEGER NOT NULL REFERENCES lancamento (id),
    parceria_levantamento_id  INTEGER REFERENCES parceria_levantamento (id),
    valor                     NUMERIC(15,2) NOT NULL,
    conta_caixa_id            INTEGER REFERENCES conta_caixa (id),
    quitado_ao_gerar          CHAR(1) NOT NULL DEFAULT 'N',
    data_criacao              TIMESTAMP NOT NULL DEFAULT now(),
    criacao_user_id           INTEGER
);
CREATE UNIQUE INDEX IF NOT EXISTS uk_agendamento_financeiro_agendamento ON agendamento_financeiro (agendamento_id);
COMMENT ON TABLE agendamento_financeiro IS 'Financeiro gerado pelo agendamento (Gerar financeiro); sai com o Desfazer financeiro';

-- categoria padrao da conta a receber do agendamento (o documento padrao e o "Atendimento", que ja existe)
INSERT INTO categoria_conta (tipo_conta_id, nome, data_criacao)
SELECT 1, 'Recebimentos de agendamentos', now()
 WHERE NOT EXISTS (SELECT 1 FROM categoria_conta WHERE lower(nome) = 'recebimentos de agendamentos');

-- gerar/desfazer o financeiro do agendamento: so o grupo Financeiro (regra de 23/10/2026)
INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_program) + 1, 'Gerar financeiro do agendamento', 'AgendamentoFinanceiroForm'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'AgendamentoFinanceiroForm');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_group_program) + 1, g.id, p.id
  FROM system_group g JOIN system_program p ON p.controller = 'AgendamentoFinanceiroForm'
 WHERE g.name = 'Financeiro'
   AND NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = g.id AND x.system_program_id = p.id);

COMMIT;
