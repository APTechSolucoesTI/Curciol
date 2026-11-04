-- Templates com categoria e fluxo de confirmacao; historico do agendamento com evento (04/11/2026).
-- template_escritorio.categoria: CONFIRMACAO | CONFIRMADO | CANCELAMENTO | LEMBRETE | OUTROS.
-- Fluxo, no template de confirmacao por WhatsApp: respostas validas para confirmar e para cancelar (uma por linha),
--   ignorar maiusculas e minusculas, a mensagem depois de confirmar, depois de cancelar e o lembrete depois de confirmado.
-- estado_agendamento.evento: de onde veio a linha do historico (confirmacao cancelada, horario alterado, resposta do
--   cliente pelo WhatsApp ou pelo link do e-mail).
-- O que estava fixo no codigo (sim / nao) vira o fluxo padrao, editavel pela tela. Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS categoria VARCHAR(20);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS respostas_confirmar TEXT;
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS respostas_cancelar TEXT;
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS ignorar_maiusculas CHAR(1) NOT NULL DEFAULT 'T';
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS template_confirmado_id INTEGER REFERENCES template_escritorio(id) ON DELETE SET NULL;
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS template_cancelado_id INTEGER REFERENCES template_escritorio(id) ON DELETE SET NULL;
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS template_lembrete_id INTEGER REFERENCES template_escritorio(id) ON DELETE SET NULL;

UPDATE template_escritorio
   SET categoria = CASE
           WHEN chave LIKE '%\_CONFIRMACAO\_AGENDAMENTO' THEN 'CONFIRMACAO'
           WHEN chave LIKE '%\_LEMBRETE\_CONSULTA' THEN 'LEMBRETE'
           WHEN chave = 'WHATSAPP_RESPOSTA_SIM' THEN 'CONFIRMADO'
           WHEN chave = 'WHATSAPP_RESPOSTA_NAO' THEN 'CANCELAMENTO'
           ELSE 'OUTROS' END
 WHERE categoria IS NULL;

UPDATE template_escritorio c
   SET respostas_confirmar = 'sim',
       respostas_cancelar = E'não\nnao',
       template_confirmado_id = (SELECT t.id FROM template_escritorio t WHERE t.escritorio_id = c.escritorio_id AND t.chave = 'WHATSAPP_RESPOSTA_SIM' ORDER BY t.id LIMIT 1),
       template_cancelado_id = (SELECT t.id FROM template_escritorio t WHERE t.escritorio_id = c.escritorio_id AND t.chave = 'WHATSAPP_RESPOSTA_NAO' ORDER BY t.id LIMIT 1),
       template_lembrete_id = (SELECT t.id FROM template_escritorio t WHERE t.escritorio_id = c.escritorio_id AND t.chave = 'WHATSAPP_LEMBRETE_CONSULTA' ORDER BY t.id LIMIT 1)
 WHERE c.chave = 'WHATSAPP_CONFIRMACAO_AGENDAMENTO'
   AND c.respostas_confirmar IS NULL AND c.respostas_cancelar IS NULL;

ALTER TABLE estado_agendamento ADD COLUMN IF NOT EXISTS evento VARCHAR(30);

UPDATE estado_agendamento SET evento = 'CONFIRMACAO_CANCELADA'
 WHERE evento IS NULL AND COALESCE(observacao, '') <> '';

COMMIT;
