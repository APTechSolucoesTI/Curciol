-- =====================================================================
-- Curciol - Pre-Processo: vinculo dentro da consulta de publicacao
-- Banco: escritorio (homologacao: curciol_homologacao) | PostgreSQL
-- Data:  2026-09-23
--
-- Aplicar depois de 2026-09-23-pre-processo-ajustes.sql.
--
-- O fluxo de vincular pre-processo deixou de abrir janelas sobre a consulta
-- de publicacao e passou a acontecer dentro dela. Com isso as duas telas
-- deixaram de ser TWindow e foram renomeadas, porque um nome terminado em
-- "Window" numa classe que nao e janela engana quem ler depois:
--
--   PreProcessoSeekWindow     -> PreProcessoVincularList
--   PreProcessoConversaoForm  -> PreProcessoVincularConfirmacao
--
-- O UPDATE preserva o id de cada programa, e com ele todas as concessoes
-- em system_group_program e system_user_program. Apagar e recriar faria os
-- grupos perderem o acesso em silencio.
--
-- Script idempotente. Rollback no fim, comentado.
-- =====================================================================

BEGIN;

UPDATE system_program
   SET controller = 'PreProcessoVincularList',
       name       = 'Vincular pré-processo'
 WHERE controller = 'PreProcessoSeekWindow';

UPDATE system_program
   SET controller = 'PreProcessoVincularConfirmacao',
       name       = 'Confirmação do vínculo de pré-processo'
 WHERE controller = 'PreProcessoConversaoForm';

/*
    Se por algum motivo os programas nao existirem (base que nunca recebeu a
    entrega anterior), cria-os com as mesmas concessoes da janela de busca de
    processo, que e a tela equivalente.
*/
INSERT INTO system_program (id, name, controller)
SELECT (SELECT max(id) FROM system_program) + 1,
       'Vincular pré-processo',
       'PreProcessoVincularList'
WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'PreProcessoVincularList');

INSERT INTO system_program (id, name, controller)
SELECT (SELECT max(id) FROM system_program) + 1,
       'Confirmação do vínculo de pré-processo',
       'PreProcessoVincularConfirmacao'
WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'PreProcessoVincularConfirmacao');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT coalesce(max(id), 0) FROM system_group_program)
           + row_number() OVER (ORDER BY g.system_group_id, p.id),
       g.system_group_id,
       p.id
FROM (SELECT DISTINCT system_group_id
      FROM system_group_program
      WHERE system_program_id = (SELECT id FROM system_program WHERE controller = 'ProcessoSeekWindow')) g
CROSS JOIN (SELECT id FROM system_program
            WHERE controller IN ('PreProcessoVincularList', 'PreProcessoVincularConfirmacao')) p
WHERE NOT EXISTS (
    SELECT 1 FROM system_group_program x
    WHERE x.system_group_id = g.system_group_id
      AND x.system_program_id = p.id
);

COMMIT;

-- =====================================================================
-- ROLLBACK
-- =====================================================================
-- UPDATE system_program SET controller = 'PreProcessoSeekWindow',
--        name = 'Vincular pré-processo'
--  WHERE controller = 'PreProcessoVincularList';
--
-- UPDATE system_program SET controller = 'PreProcessoConversaoForm',
--        name = 'Conversão de pré-processo'
--  WHERE controller = 'PreProcessoVincularConfirmacao';
