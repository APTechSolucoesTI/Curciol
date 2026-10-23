-- Regra do escritorio (20/10/2026): todo Profissional (1) e tambem Parceiro (6), e todo Parceiro e tambem Fornecedor (3).
-- Os novos cadastros seguem a regra pelo PessoaGrupo::onAfterStore; aqui os vinculos que ja existem.
-- E os parceiros provisorios "KBÇA" e "CAMPOS" (criados em 2026-10-13) passam para os cadastros reais:
--   KBÇA   -> KLEBER ANDRE FERREIRA
--   CAMPOS -> CARLOS ROBERTO DE CAMPOS FILHO (CPF 095.857.238-08)
-- Idempotente. Depende de 2026-10-13-levantamento-financeiro.sql e 2026-10-20-parceiros-alliance-estrutura.sql.
BEGIN;

CREATE TEMP TABLE troca_parceiro ON COMMIT DROP AS
SELECT prov.id AS provisorio_id, real.id AS real_id
  FROM (VALUES ('KBÇA', 'KLEBER ANDRE FERREIRA', NULL),
               ('CAMPOS', 'CARLOS ROBERTO DE CAMPOS FILHO', '09585723808')) n(provisorio, real, documento)
  JOIN LATERAL (SELECT p.id FROM pessoa p
                 WHERE normalize(p.nome, NFC) = normalize(n.real, NFC)
                   AND (n.documento IS NULL OR regexp_replace(COALESCE(p.cpf_cnpj, ''), '[^0-9]', '', 'g') = n.documento)
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

-- todo Profissional tambem Parceiro; todo Parceiro tambem Fornecedor
INSERT INTO pessoa_grupo (pessoa_id, grupo_id)
SELECT DISTINCT g.pessoa_id, 6 FROM pessoa_grupo g
 WHERE g.grupo_id = 1
   AND NOT EXISTS (SELECT 1 FROM pessoa_grupo x WHERE x.pessoa_id = g.pessoa_id AND x.grupo_id = 6);
INSERT INTO pessoa_grupo (pessoa_id, grupo_id)
SELECT DISTINCT g.pessoa_id, 3 FROM pessoa_grupo g
 WHERE g.grupo_id = 6
   AND NOT EXISTS (SELECT 1 FROM pessoa_grupo x WHERE x.pessoa_id = g.pessoa_id AND x.grupo_id = 3);

COMMIT;

-- ROLLBACK (manual): os vinculos criados aqui nao sao distinguiveis dos anteriores; recriar os provisorios pelo
-- 2026-10-13 e trocar de volta os pessoa_id acima se for preciso.
