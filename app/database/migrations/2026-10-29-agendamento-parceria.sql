-- Financeiro do agendamento, ajustes (29/10/2026):
-- - a parceria de repasse e escolhida no proprio agendamento (cadastro/edicao), o Gerar financeiro usa a dela;
-- - o documento da conta a receber volta a ser o "Atendimento": o tipo "Agendamento" criado na primeira versao sai
--   (as contas que o usaram passam para "Atendimento"). Em producao o tipo nunca existiu: nada muda.
-- Rodar depois de 2026-10-29-agendamento-financeiro.sql. Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE agendamento ADD COLUMN IF NOT EXISTS parceria_levantamento_id INTEGER;
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_agendamento_parceria') THEN
        ALTER TABLE agendamento ADD CONSTRAINT fk_agendamento_parceria FOREIGN KEY (parceria_levantamento_id) REFERENCES parceria_levantamento (id);
    END IF;
END $$;
COMMENT ON COLUMN agendamento.parceria_levantamento_id IS 'Parceria de repasse (so percentual fixo) usada no Gerar financeiro do agendamento';

UPDATE conta SET tipo_documento_financeiro_id = (SELECT id FROM tipo_documento_financeiro WHERE nome = 'Atendimento' ORDER BY id LIMIT 1)
 WHERE tipo_documento_financeiro_id IN (SELECT id FROM tipo_documento_financeiro WHERE nome = 'Agendamento' AND codigo = 'Agd')
   AND EXISTS (SELECT 1 FROM tipo_documento_financeiro WHERE nome = 'Atendimento');
DELETE FROM tipo_documento_financeiro t
 WHERE t.nome = 'Agendamento' AND t.codigo = 'Agd'
   AND NOT EXISTS (SELECT 1 FROM conta c WHERE c.tipo_documento_financeiro_id = t.id);

-- o agendamento que ja tem financeiro fica com a parceria usada nele
UPDATE agendamento a SET parceria_levantamento_id = f.parceria_levantamento_id
  FROM agendamento_financeiro f
 WHERE f.agendamento_id = a.id AND a.parceria_levantamento_id IS NULL;

COMMIT;
