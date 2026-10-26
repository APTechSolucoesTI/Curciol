-- Contato principal da pessoa (26/10/2026).
-- pessoa_contato.principal = 'S' marca o contato principal: o telefone dele e o telefone dos Dados cadastrais
-- (pessoa.telefone) sao sempre o mesmo (PessoaContatoService). No maximo um principal por pessoa (indice unico parcial).
-- So estrutura: todos comecam como 'N'; quem vira principal e decidido pela normalizacao (aprovada a parte) ou ao
-- salvar o cadastro. Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE pessoa_contato ADD COLUMN IF NOT EXISTS principal CHAR(1) NOT NULL DEFAULT 'N';

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_pessoa_contato_principal') THEN
        ALTER TABLE pessoa_contato ADD CONSTRAINT ck_pessoa_contato_principal CHECK (principal IN ('S', 'N'));
    END IF;
END $$;

CREATE UNIQUE INDEX IF NOT EXISTS uk_pessoa_contato_principal ON pessoa_contato (pessoa_id) WHERE principal = 'S';
COMMENT ON COLUMN pessoa_contato.principal IS 'S = contato principal (mesmo telefone de pessoa.telefone); no maximo um por pessoa';

COMMIT;
