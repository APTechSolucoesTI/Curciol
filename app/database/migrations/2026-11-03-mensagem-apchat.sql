-- Mensagens do agendamento pelo APChat (03/11/2026).
-- escritorio.url_apchat: endereco da API externa do APChat, editavel em Configuracoes do escritorio junto com o token
--   (trocar de canal para testar). Uma integracao so para mensagens e contatos; o escritorio informa URL e token.
-- mensagem.destino: numero (55 + DDD + numero) ou e-mail para onde foi a mensagem - na homologacao e o numero digitado.
-- mensagem.retorno: resposta do APChat ao envio do WhatsApp.
-- Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS url_apchat TEXT;

ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS destino TEXT;
ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS retorno TEXT;

COMMIT;
