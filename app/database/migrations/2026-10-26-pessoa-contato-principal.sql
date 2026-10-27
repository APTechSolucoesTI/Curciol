-- Contato principal da pessoa (26/10/2026; regras revistas em 27/10/2026).
-- pessoa_contato.principal = 'S' marca o contato principal: o telefone e o e-mail dele sao o "Telefone principal" e o
-- e-mail dos Dados cadastrais (pessoa.telefone / pessoa.email), nos dois sentidos (PessoaContatoService).
-- No maximo um principal por pessoa e o mesmo telefone so pode ser principal de uma pessoa (indices unicos parciais).
-- nome: de quem e o contato (a propria pessoa no principal, o representante legal no contato que veio dele).
-- representante_id: o contato veio do representante legal (sai quando o representante e desvinculado).
-- So estrutura: todos comecam como 'N'; a normalizacao e feita a parte. Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE pessoa_contato ADD COLUMN IF NOT EXISTS principal CHAR(1) NOT NULL DEFAULT 'N';
ALTER TABLE pessoa_contato ADD COLUMN IF NOT EXISTS nome VARCHAR(255);
ALTER TABLE pessoa_contato ADD COLUMN IF NOT EXISTS representante_id INTEGER;
-- registros antigos ficam sem data (nao se sabe quando foram criados); os novos recebem a data do banco
ALTER TABLE pessoa_contato ADD COLUMN IF NOT EXISTS created_at TIMESTAMP;
ALTER TABLE pessoa_contato ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP;
ALTER TABLE pessoa_contato ALTER COLUMN created_at SET DEFAULT now();
ALTER TABLE pessoa_contato ALTER COLUMN updated_at SET DEFAULT now();

-- descricao opcional: o contato pode ter so telefone ou so e-mail, com ou sem descricao
ALTER TABLE pessoa_contato ALTER COLUMN descricao DROP NOT NULL;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_pessoa_contato_principal') THEN
        ALTER TABLE pessoa_contato ADD CONSTRAINT ck_pessoa_contato_principal CHECK (principal IN ('S', 'N'));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_pessoa_contato_representante') THEN
        ALTER TABLE pessoa_contato ADD CONSTRAINT fk_pessoa_contato_representante FOREIGN KEY (representante_id) REFERENCES pessoa (id) ON DELETE SET NULL;
    END IF;
END $$;

-- um principal por pessoa
CREATE UNIQUE INDEX IF NOT EXISTS uk_pessoa_contato_principal ON pessoa_contato (pessoa_id) WHERE principal = 'S';
-- o mesmo telefone so pode ser principal de uma pessoa (o principal e gravado so com DDD + numero)
CREATE UNIQUE INDEX IF NOT EXISTS uk_pessoa_contato_telefone_principal ON pessoa_contato ((regexp_replace(telefone, '\D', '', 'g')))
    WHERE principal = 'S' AND regexp_replace(COALESCE(telefone, ''), '\D', '', 'g') <> '';
CREATE INDEX IF NOT EXISTS ix_pessoa_contato_representante ON pessoa_contato (representante_id) WHERE representante_id IS NOT NULL;

COMMENT ON COLUMN pessoa_contato.principal IS 'S = contato principal (telefone e e-mail = os dos Dados cadastrais); no maximo um por pessoa; o mesmo telefone so e principal de uma pessoa';
COMMENT ON COLUMN pessoa_contato.nome IS 'De quem e o contato (a propria pessoa, o representante legal ou quem estiver na descricao)';
COMMENT ON COLUMN pessoa_contato.representante_id IS 'Contato que veio do representante legal; sai quando ele e desvinculado';

COMMIT;
