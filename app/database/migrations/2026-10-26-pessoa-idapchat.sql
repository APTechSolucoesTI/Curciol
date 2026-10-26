-- Id do contato no APChat (public."Contacts".id do tenant 21), 26/10/2026.
-- Ligacao fixa entre a pessoa da Curciol e o contato do APChat: a trigger do banco do APChat
-- (trg_curciol_sincroniza_contato) atualiza a pessoa com o mesmo idapchat quando o contato muda la.
-- Coluna fora do modelo Pessoa de proposito: salvar uma pessoa pelo sistema nunca a sobrescreve.
-- Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE pessoa ADD COLUMN IF NOT EXISTS idapchat INTEGER;
CREATE INDEX IF NOT EXISTS idx_pessoa_idapchat ON pessoa (idapchat) WHERE idapchat IS NOT NULL;
COMMENT ON COLUMN pessoa.idapchat IS 'Id do contato no APChat (Contacts.id, tenant 21); usado pela trigger de sincronizacao APChat -> Curciol';

COMMIT;
