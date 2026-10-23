-- Parceiros Alliance e Estrutura: em vez dos cadastros provisorios criados em 2026-10-13 ("ALLIANCE" e "ESTRUTURA"),
-- usar os cadastros reais (decisao do escritorio em 20/10/2026):
--   ALLIANCE  -> ALLIANCE ADMINISTRAÇÃO COMÉRCIO E ENGENHARIA LTDA
--   ESTRUTURA -> LUMES GESTAO IMOBILIARIA LTDA (provisorio; deve ser trocado ou criado outro cadastro no futuro)
-- Os cadastros reais entram no grupo Parceiro (6) e passam a ser usados nas parcerias, nas fichas, nos repasses e nas
-- contas; os provisorios sao apagados (so se nada mais apontar para eles). Idempotente.
-- Depende de 2026-10-13-levantamento-financeiro.sql e 2026-10-15-repasse-colaborador.sql.
BEGIN;

CREATE TEMP TABLE troca_parceiro ON COMMIT DROP AS
SELECT prov.id AS provisorio_id, real.id AS real_id
  FROM (VALUES ('ALLIANCE', 'ALLIANCE ADMINISTRAÇÃO COMÉRCIO E ENGENHARIA LTDA'),
               ('ESTRUTURA', 'LUMES GESTAO IMOBILIARIA LTDA')) n(provisorio, real)
  JOIN LATERAL (SELECT p.id FROM pessoa p
                 WHERE normalize(p.nome, NFC) = normalize(n.real, NFC)
                 ORDER BY p.id LIMIT 1) real ON true
  LEFT JOIN LATERAL (SELECT p.id FROM pessoa p
                      WHERE normalize(p.nome, NFC) = normalize(n.provisorio, NFC)
                        AND p.observacao = 'Parceiro de levantamento (cadastro inicial do financeiro de levantamentos).'
                      ORDER BY p.id LIMIT 1) prov ON true;

-- cadastros reais no grupo Parceiro
INSERT INTO pessoa_grupo (pessoa_id, grupo_id)
SELECT t.real_id, 6 FROM troca_parceiro t
 WHERE NOT EXISTS (SELECT 1 FROM pessoa_grupo g WHERE g.pessoa_id = t.real_id AND g.grupo_id = 6);

-- tudo que apontava para o provisorio passa para o real
UPDATE parceria_levantamento_participante x SET pessoa_id = t.real_id FROM troca_parceiro t WHERE x.pessoa_id = t.provisorio_id;
UPDATE levantamento_financeiro_repasse    x SET pessoa_id = t.real_id FROM troca_parceiro t WHERE x.pessoa_id = t.provisorio_id;
UPDATE repasse_colaborador                x SET pessoa_id = t.real_id FROM troca_parceiro t WHERE x.pessoa_id = t.provisorio_id;
UPDATE lancamento_profissional            x SET pessoa_id = t.real_id FROM troca_parceiro t WHERE x.pessoa_id = t.provisorio_id;
UPDATE conta_profissional                 x SET pessoa_id = t.real_id FROM troca_parceiro t WHERE x.pessoa_id = t.provisorio_id;
UPDATE contrato_repasse                   x SET pessoa_id = t.real_id FROM troca_parceiro t WHERE x.pessoa_id = t.provisorio_id;
UPDATE lote_pagamento                     x SET pessoa_id = t.real_id FROM troca_parceiro t WHERE x.pessoa_id = t.provisorio_id;
UPDATE conta                              x SET pessoa_id = t.real_id FROM troca_parceiro t WHERE x.pessoa_id = t.provisorio_id;
UPDATE conta                              x SET profissional_id = t.real_id FROM troca_parceiro t WHERE x.profissional_id = t.provisorio_id;

-- apaga os provisorios (se algo mais ainda apontar para eles, ficam como estao)
DO $$
DECLARE r record;
BEGIN
    FOR r IN SELECT provisorio_id FROM troca_parceiro WHERE provisorio_id IS NOT NULL LOOP
        BEGIN
            DELETE FROM pessoa_grupo WHERE pessoa_id = r.provisorio_id;
            DELETE FROM pessoa WHERE id = r.provisorio_id;
        EXCEPTION WHEN foreign_key_violation THEN
            RAISE NOTICE 'Cadastro provisorio % ainda em uso; mantido.', r.provisorio_id;
        END;
    END LOOP;
END $$;

COMMIT;

-- ROLLBACK (manual): recriar os provisorios pelo 2026-10-13 e trocar de volta os pessoa_id acima.
