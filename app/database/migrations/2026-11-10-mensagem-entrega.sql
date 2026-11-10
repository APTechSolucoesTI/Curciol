-- 10/11/2026: situacao de entrega do WhatsApp enviado pela Curciol. A = aguardando entrega, E = entregue (2 tiques no APChat),
-- N = nao entregue (24 h sem os 2 tiques; avisa no sino). Nulo = e-mail ou mensagem antiga. Pode rodar de novo.
ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS entrega char(1) CHECK (entrega IN ('A', 'E', 'N'));
ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS entrega_em timestamp;
ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS apchat_mensagem_id text;
CREATE INDEX IF NOT EXISTS ix_mensagem_entrega_aguardando ON mensagem (enviado_em) WHERE entrega = 'A';
