-- Mensagens do agendamento pelo APChat (03/11/2026).
-- escritorio.url_apchat: endereco da API externa do APChat, editavel em Configuracoes do escritorio junto com o token
--   (trocar de canal para testar); preenchido com o endereco que estava fixo no codigo.
-- mensagem.destino: numero (55 + DDD + numero) ou e-mail para onde foi a mensagem - na homologacao e o numero digitado.
-- mensagem.retorno: resposta do APChat ao envio do WhatsApp.
-- Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS url_apchat TEXT;

UPDATE escritorio
   SET url_apchat = 'https://api.apchat.com.br/v2/api/external/baca905c-dce1-4d3f-b1bb-f2ffc981fdb4'
 WHERE COALESCE(btrim(url_apchat), '') = '';

ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS destino TEXT;
ALTER TABLE mensagem ADD COLUMN IF NOT EXISTS retorno TEXT;

COMMIT;
