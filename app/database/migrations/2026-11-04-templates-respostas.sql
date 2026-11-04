-- Confirmacao por WhatsApp com exatamente duas respostas (04/11/2026): cada lista fica so com a primeira resposta; "ignorar
-- maiusculas" passa a ignorar tambem os acentos (nao e nao ficam a mesma resposta). O lembrete sai do template de confirmacao:
-- a tela de envio sugere o primeiro da categoria Lembrete. Rodar depois de 2026-11-04-templates-fluxo-historico.sql.
-- Pode rodar mais de uma vez.
BEGIN;

UPDATE template_escritorio
   SET respostas_confirmar = btrim(split_part(replace(respostas_confirmar, E'\r', ''), E'\n', 1)),
       respostas_cancelar = btrim(split_part(replace(respostas_cancelar, E'\r', ''), E'\n', 1))
 WHERE respostas_confirmar LIKE E'%\n%' OR respostas_cancelar LIKE E'%\n%';

ALTER TABLE template_escritorio DROP COLUMN IF EXISTS template_lembrete_id;

COMMIT;
