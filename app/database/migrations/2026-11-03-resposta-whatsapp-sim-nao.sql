-- Resposta SIM/NAO ao WhatsApp de confirmacao do agendamento (03/11/2026; ApchatRespostaService, cron a cada minuto).
-- mensagem.resposta: SIM | NAO | OUTRA | SUBSTITUIDA (outra confirmacao enviada depois ao mesmo numero) | ENCERRADA
--   (agendamento mudou de estado ou a data passou); NULL = esperando resposta.
-- Templates novos por escritorio (editaveis em Configuracoes > Template): a mensagem automatica depois do SIM
--   (confirmacao recebida) e depois do NAO (agradecimento).
-- Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS resposta VARCHAR(20);
ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS resposta_texto TEXT;
ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS respondido_em TIMESTAMP;
ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS resposta_obs TEXT;

CREATE INDEX IF NOT EXISTS mensagem_esperando_resposta_idx ON mensagem (enviado_em) WHERE resposta IS NULL;

INSERT INTO template_escritorio (escritorio_id, chave, descricao, habilitado, template, titulo, tipo_template, readonly, data_criacao)
SELECT e.id, 'WHATSAPP_RESPOSTA_SIM', 'Resposta SIM: confirmação recebida', 'T',
       E'Obrigado, {$cliente}! Seu agendamento para {$data_inicial}, com {$profissional}, está confirmado.',
       'Confirmação recebida', 'WHATSAPP', 'T', now()
  FROM escritorio e
 WHERE NOT EXISTS (SELECT 1 FROM template_escritorio t WHERE t.escritorio_id = e.id AND t.chave = 'WHATSAPP_RESPOSTA_SIM');

INSERT INTO template_escritorio (escritorio_id, chave, descricao, habilitado, template, titulo, tipo_template, readonly, data_criacao)
SELECT e.id, 'WHATSAPP_RESPOSTA_NAO', 'Resposta NÃO: agradecimento', 'T',
       E'Obrigado por avisar, {$cliente}. Seu agendamento de {$data_inicial} foi cancelado.',
       'Agendamento cancelado', 'WHATSAPP', 'T', now()
  FROM escritorio e
 WHERE NOT EXISTS (SELECT 1 FROM template_escritorio t WHERE t.escritorio_id = e.id AND t.chave = 'WHATSAPP_RESPOSTA_NAO');

COMMIT;
