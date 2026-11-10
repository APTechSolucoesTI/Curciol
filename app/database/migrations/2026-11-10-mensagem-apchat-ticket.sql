-- 10/11/2026: ticket que o APChat devolve no envio do WhatsApp ({"data":{"ticketId":N}} - a resposta nao traz o id da mensagem).
-- Entrega conferida pelo ticket (mesmo texto duas vezes nao se confunde) e historico do cliente sem repetir a mensagem da Curciol
-- (ticket aberto so pelo envio, sem mensagem do cliente, nao aparece de novo). Pode rodar de novo.
ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS apchat_ticket_id bigint;
CREATE INDEX IF NOT EXISTS ix_mensagem_apchat_ticket ON mensagem (apchat_ticket_id) WHERE apchat_ticket_id IS NOT NULL;
