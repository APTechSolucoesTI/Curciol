-- 18/11/2026: templates de WhatsApp com titulo, corpo, rodape e botoes (Meta e Curciol no mesmo padrao).
--   descricao continua sendo o NOME do template (lista, combos, envios); observacao e a Descricao livre (descritivo).
--   rodape e botoes: no template da Curciol, o rodape vai em italico no fim do texto e os botoes saem pelo /sendButtonWABA do
--   APChat (so canal oficial); no da Meta, copia dos componentes aprovados (so leitura).
--   categoria ja aceita vazio: template da Meta importado sem botoes fica sem categoria ate o usuario escolher.
-- Pode rodar de novo.
BEGIN;

ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS observacao text;
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS rodape varchar(60);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS botoes text;

COMMENT ON COLUMN template_escritorio.descricao IS 'Nome do template (lista, combos e envios)';
COMMENT ON COLUMN template_escritorio.observacao IS 'Descricao livre do template (descritivo)';
COMMENT ON COLUMN template_escritorio.rodape IS 'WhatsApp: rodape da mensagem (Curciol: em italico no fim do texto; Meta: o FOOTER aprovado)';
COMMENT ON COLUMN template_escritorio.botoes IS 'WhatsApp: botoes de resposta, um por linha, ate 3 com ate 20 caracteres (Curciol: /sendButtonWABA, so canal oficial)';

ALTER TABLE template_escritorio ALTER COLUMN categoria DROP NOT NULL;

-- templates da Meta ja importados: titulo (cabecalho), corpo, rodape e botoes separados, a partir dos componentes (o
-- proximo Importar da Meta grava igual)
UPDATE template_escritorio t
   SET rodape = left(f.rodape, 60), botoes = f.botoes, titulo = f.cabecalho, template = COALESCE(f.corpo, t.template)
  FROM (SELECT te.id,
               (SELECT c->>'text' FROM jsonb_array_elements(te.meta_componentes::jsonb) c
                 WHERE upper(c->>'type') = 'HEADER' AND upper(COALESCE(c->>'format', 'TEXT')) = 'TEXT' LIMIT 1) AS cabecalho,
               (SELECT c->>'text' FROM jsonb_array_elements(te.meta_componentes::jsonb) c WHERE upper(c->>'type') = 'BODY' LIMIT 1) AS corpo,
               (SELECT c->>'text' FROM jsonb_array_elements(te.meta_componentes::jsonb) c WHERE upper(c->>'type') = 'FOOTER' LIMIT 1) AS rodape,
               (SELECT string_agg(b->>'text', E'\n') FROM jsonb_array_elements(te.meta_componentes::jsonb) c,
                       jsonb_array_elements(COALESCE(c->'buttons', '[]'::jsonb)) b WHERE upper(c->>'type') = 'BUTTONS') AS botoes
          FROM template_escritorio te
         WHERE te.origem = 'META' AND te.meta_componentes IS NOT NULL AND te.meta_componentes LIKE '[%') f
 WHERE t.id = f.id;

COMMIT;
