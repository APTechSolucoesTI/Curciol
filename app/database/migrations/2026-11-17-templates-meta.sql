-- 17/11/2026: templates da Meta e variaveis no padrao da Meta.
--   1. template_escritorio ganha a ORIGEM: CURCIOL (criado e editado na Curciol, vale para qualquer canal) ou META (importado
--      da conta WhatsApp Business do canal oficial do APChat; o texto e o aprovado na Meta, so pelo canal oficial) e o vinculo
--      com a Meta (nome, idioma, situacao, categoria, formato das variaveis, componentes, conta e ultima importacao).
--   2. Variaveis no padrao da Meta, {{nome}}, em todos os textos gravados: {$nome} (antigo), {{$nome}}, {{ nome }} e a forma
--      codificada no link do editor (%7B%24nome%7D, %7B%7Bnome%7D%7D). A Curciol continua aceitando as formas antigas.
-- Pode rodar de novo.
BEGIN;

ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS origem varchar(10) NOT NULL DEFAULT 'CURCIOL';
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS meta_nome varchar(512);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS meta_idioma varchar(20);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS meta_id varchar(64);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS meta_waba_id varchar(64);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS meta_status varchar(30);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS meta_categoria varchar(30);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS meta_formato varchar(20);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS meta_componentes text;
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS meta_sincronizado_em timestamp;

COMMENT ON COLUMN template_escritorio.origem IS 'CURCIOL = criado na Curciol (qualquer canal); META = importado da Meta (texto aprovado, so canal oficial)';
COMMENT ON COLUMN template_escritorio.meta_nome IS 'Nome do template na Meta (envio por /templateBody)';
COMMENT ON COLUMN template_escritorio.meta_status IS 'Situacao na Meta na ultima importacao: APPROVED, PENDING, REJECTED, PAUSED, DISABLED... ou REMOVIDO (sumiu da Meta)';
COMMENT ON COLUMN template_escritorio.meta_formato IS 'NAMED (variaveis por nome) ou POSITIONAL ({{1}}, {{2}}...)';
COMMENT ON COLUMN template_escritorio.meta_componentes IS 'Componentes do template na Meta (JSON: HEADER, BODY, FOOTER, BUTTONS)';
COMMENT ON COLUMN template_escritorio.meta_waba_id IS 'Conta WhatsApp Business (WABA) de onde o template veio';

ALTER TABLE template_escritorio DROP CONSTRAINT IF EXISTS template_escritorio_origem_chk;
ALTER TABLE template_escritorio ADD CONSTRAINT template_escritorio_origem_chk CHECK (origem IN ('CURCIOL', 'META'));

-- um template da Meta (conta + nome + idioma) uma vez so por escritorio: importar de novo atualiza
CREATE UNIQUE INDEX IF NOT EXISTS template_escritorio_meta_unico ON template_escritorio (escritorio_id, meta_waba_id, meta_nome, meta_idioma)
 WHERE origem = 'META';

-- variaveis no padrao da Meta ------------------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION pg_temp.curciol_variaveis(t text) RETURNS text LANGUAGE sql IMMUTABLE AS $$
    SELECT regexp_replace(regexp_replace(regexp_replace(regexp_replace($1,
           '%7B%7B(?:%20)*(?:%24)?([A-Za-z0-9_]+)(?:%20)*%7D%7D', '{{\1}}', 'gi'),
           '%7B%24([A-Za-z0-9_]+)%7D', '{{\1}}', 'gi'),
           '\{\{\s*\$?([A-Za-z0-9_]+)\s*\}\}', '{{\1}}', 'g'),
           '\{\$([A-Za-z0-9_]+)\}', '{{\1}}', 'g')
$$;

UPDATE template_escritorio
   SET template = pg_temp.curciol_variaveis(template), titulo = pg_temp.curciol_variaveis(titulo)
 WHERE template IS DISTINCT FROM pg_temp.curciol_variaveis(template) OR titulo IS DISTINCT FROM pg_temp.curciol_variaveis(titulo);

UPDATE template_acao SET url = pg_temp.curciol_variaveis(url) WHERE url IS DISTINCT FROM pg_temp.curciol_variaveis(url);

-- lembretes que ainda vao sair (o texto e gravado com as variaveis e trocado no envio)
UPDATE mensagem_agendada
   SET texto = pg_temp.curciol_variaveis(texto), titulo = pg_temp.curciol_variaveis(titulo)
 WHERE situacao = 'PENDENTE'
   AND (texto IS DISTINCT FROM pg_temp.curciol_variaveis(texto) OR titulo IS DISTINCT FROM pg_temp.curciol_variaveis(titulo));

COMMIT;
