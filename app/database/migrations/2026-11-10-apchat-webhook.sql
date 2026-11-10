-- 10/11/2026: webhook do APChat. O APChat chama https://<site>/apchat-webhook.php?chave=<webhook_apchat_chave> a cada evento
-- (mensagens). E so a "campainha" do balao: guarda o tipo do evento, o ticket, se a mensagem e nossa e a hora (nunca o texto,
-- o contato nem os tokens que o APChat manda junto); o balao rele o banco do APChat quando chega mensagem. Pode rodar de novo.
-- A chave e gerada aqui, diferente em cada banco (homologacao e producao): cadastrar o endereco com ela na tela de webhook do APChat.
ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS webhook_apchat_chave text;
COMMENT ON COLUMN escritorio.webhook_apchat_chave IS 'Chave secreta do endereco do webhook do APChat (apchat-webhook.php?chave=...)';
UPDATE escritorio SET webhook_apchat_chave = md5(random()::text || clock_timestamp()::text || id::text) || md5(random()::text || id::text)
 WHERE coalesce(webhook_apchat_chave, '') = '';

CREATE TABLE IF NOT EXISTS apchat_webhook_evento (
    id            bigserial PRIMARY KEY,
    recebido_em   timestamp NOT NULL DEFAULT now(),
    ip            text,
    metodo        text,
    ticket_id     bigint,
    de_mim        boolean
);
COMMENT ON TABLE apchat_webhook_evento IS 'Eventos do webhook do APChat: so tipo, ticket, se a mensagem e nossa e hora (10/11/2026; 3 dias)';
-- homologacao: a primeira versao guardava o corpo inteiro
ALTER TABLE apchat_webhook_evento ADD COLUMN IF NOT EXISTS metodo text;
ALTER TABLE apchat_webhook_evento ADD COLUMN IF NOT EXISTS ticket_id bigint;
ALTER TABLE apchat_webhook_evento ADD COLUMN IF NOT EXISTS de_mim boolean;
DO $$ BEGIN
    IF EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'apchat_webhook_evento' AND column_name = 'corpo') THEN
        UPDATE apchat_webhook_evento SET metodo = corpo::jsonb->>'method', ticket_id = (corpo::jsonb#>>'{ticket,id}')::bigint
         WHERE metodo IS NULL AND corpo ~ '^\s*\{';
        ALTER TABLE apchat_webhook_evento DROP COLUMN corpo;
    END IF;
END $$;
ALTER TABLE apchat_webhook_evento DROP COLUMN IF EXISTS processado_em;
ALTER TABLE apchat_webhook_evento DROP COLUMN IF EXISTS erro;
DROP INDEX IF EXISTS ix_apchat_webhook_evento_pendente;
CREATE INDEX IF NOT EXISTS ix_apchat_webhook_evento_recebido ON apchat_webhook_evento (recebido_em);
