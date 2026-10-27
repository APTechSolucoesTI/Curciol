-- Id do contato no APChat (public."Contacts".id do tenant 21) em cada contato da pessoa, 27/10/2026.
-- Ao exportar os contatos com telefone valido para o APChat, o id que o APChat gera fica aqui: e a ligacao entre o
-- contato da Curciol e o do APChat (a edicao passa a ser so pela Curciol). Vazio = contato ainda nao exportado (ou sem
-- telefone valido). O mesmo numero em contatos de pessoas diferentes e o mesmo contato no APChat (mesmo id).
-- Coluna fora do modelo PessoaContato de proposito: salvar a aba Contatos nunca a sobrescreve.
-- Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE pessoa_contato ADD COLUMN IF NOT EXISTS idapchat INTEGER;
CREATE INDEX IF NOT EXISTS idx_pessoa_contato_idapchat ON pessoa_contato (idapchat) WHERE idapchat IS NOT NULL;
COMMENT ON COLUMN pessoa_contato.idapchat IS 'Id do contato no APChat (Contacts.id, tenant 21), gravado quando o contato e exportado; vazio = nao exportado';

COMMIT;
