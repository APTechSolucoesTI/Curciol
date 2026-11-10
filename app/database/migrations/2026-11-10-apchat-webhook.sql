-- 10/11/2026: webhook do APChat. O APChat chama https://<site>/apchat-webhook.php?chave=<webhook_apchat_chave> a cada evento
-- (mensagens); a Curciol guarda o evento como chegou em apchat_webhook_evento e processa depois. Pode rodar de novo.
-- A chave e gerada aqui, diferente em cada banco (homologacao e producao): cadastrar o endereco com ela na tela de webhook do APChat.
ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS webhook_apchat_chave text;
COMMENT ON COLUMN escritorio.webhook_apchat_chave IS 'Chave secreta do endereco do webhook do APChat (apchat-webhook.php?chave=...)';
UPDATE escritorio SET webhook_apchat_chave = md5(random()::text || clock_timestamp()::text || id::text) || md5(random()::text || id::text)
 WHERE coalesce(webhook_apchat_chave, '') = '';

CREATE TABLE IF NOT EXISTS apchat_webhook_evento (
    id            bigserial PRIMARY KEY,
    recebido_em   timestamp NOT NULL DEFAULT now(),
    ip            text,
    corpo         text NOT NULL,
    processado_em timestamp,
    erro          text
);
COMMENT ON TABLE apchat_webhook_evento IS 'Eventos recebidos do webhook do APChat, como chegaram (10/11/2026)';
CREATE INDEX IF NOT EXISTS ix_apchat_webhook_evento_pendente ON apchat_webhook_evento (id) WHERE processado_em IS NULL;
