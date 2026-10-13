-- =====================================================================
-- Curciol - Financeiro do levantamento judicial
-- Banco: escritorio | PostgreSQL | Data: 2026-10-13
-- Idempotente enquanto os cadastros semeados nao forem renomeados. Rollback comentado no fim.
-- =====================================================================
BEGIN;

CREATE TABLE IF NOT EXISTS beneficiario_credito (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(120) NOT NULL UNIQUE,
    regra VARCHAR(30) NOT NULL CHECK (regra IN ('CLIENTE_E_HONORARIOS','SOMENTE_HONORARIOS','SOMENTE_CLIENTE')),
    ativo CHAR(1) NOT NULL DEFAULT 'S',
    data_criacao TIMESTAMP, criacao_user_id INTEGER, data_modificacao TIMESTAMP, modificacao_user_id INTEGER
);

CREATE TABLE IF NOT EXISTS parceria_levantamento (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(120) NOT NULL UNIQUE,
    tipo VARCHAR(10) NOT NULL CHECK (tipo IN ('FIXA','TEMPORAL')),
    marco1 DATE, marco2 DATE,
    ativo CHAR(1) NOT NULL DEFAULT 'S',
    data_criacao TIMESTAMP, criacao_user_id INTEGER, data_modificacao TIMESTAMP, modificacao_user_id INTEGER
);

CREATE TABLE IF NOT EXISTS parceria_levantamento_participante (
    id SERIAL PRIMARY KEY,
    parceria_levantamento_id INTEGER NOT NULL REFERENCES parceria_levantamento(id) ON DELETE CASCADE,
    pessoa_id INTEGER NOT NULL REFERENCES pessoa(id),
    nivel VARCHAR(10) NOT NULL CHECK (nivel IN ('FIXO','MARCO1','MARCO2')),
    percentual NUMERIC(12,8), percentual_antes NUMERIC(12,8), percentual_depois NUMERIC(12,8),
    recebe_diferenca CHAR(1) NOT NULL DEFAULT 'N',
    ordem INTEGER NOT NULL DEFAULT 0,
    UNIQUE (parceria_levantamento_id, pessoa_id)
);

CREATE TABLE IF NOT EXISTS levantamento_financeiro (
    id SERIAL PRIMARY KEY,
    requisicao_pagamento_etapa3_id INTEGER NOT NULL REFERENCES requisicao_pagamento_etapa3(id),
    requisicao_pagamento_id INTEGER NOT NULL REFERENCES requisicao_pagamento(id),
    requisicao_pagamento_cliente_id INTEGER NOT NULL REFERENCES requisicao_pagamento_cliente(id),
    contrato_id INTEGER REFERENCES contrato(id),
    processo_id INTEGER REFERENCES processo(id),
    beneficiario_credito_id INTEGER NOT NULL REFERENCES beneficiario_credito(id),
    parceria_levantamento_id INTEGER REFERENCES parceria_levantamento(id),
    regra VARCHAR(30) NOT NULL,
    bruto_depositado NUMERIC(14,2) NOT NULL, valor_mle NUMERIC(14,2) NOT NULL, descontos NUMERIC(14,2) NOT NULL,
    pct_honorarios NUMERIC(12,8), pct_nf NUMERIC(12,8) NOT NULL,
    data_levantamento DATE NOT NULL, valor_levantado NUMERIC(14,2) NOT NULL, data_distribuicao DATE,
    fracao_a NUMERIC(12,8), fracao_p NUMERIC(12,8), fator_d NUMERIC(12,8),
    u NUMERIC(14,4), v NUMERIC(14,4), w NUMERIC(14,4),
    z NUMERIC(14,2) NOT NULL, aa NUMERIC(14,2) NOT NULL, ac NUMERIC(14,2) NOT NULL, ad NUMERIC(14,2) NOT NULL,
    conta_receber_id INTEGER REFERENCES conta(id), conta_pagar_id INTEGER REFERENCES conta(id),
    extrato_id INTEGER, conta_caixa_id INTEGER REFERENCES conta_caixa(id),
    data_criacao TIMESTAMP NOT NULL DEFAULT now(), criacao_user_id INTEGER
);
CREATE UNIQUE INDEX IF NOT EXISTS uk_levantamento_financeiro_etapa3 ON levantamento_financeiro (requisicao_pagamento_etapa3_id);

CREATE TABLE IF NOT EXISTS levantamento_financeiro_repasse (
    id SERIAL PRIMARY KEY,
    levantamento_financeiro_id INTEGER NOT NULL REFERENCES levantamento_financeiro(id) ON DELETE CASCADE,
    pessoa_id INTEGER NOT NULL REFERENCES pessoa(id),
    percentual NUMERIC(12,8) NOT NULL, valor NUMERIC(14,2) NOT NULL, ajuste_centavos NUMERIC(14,2) NOT NULL DEFAULT 0
);

ALTER TABLE conta ADD COLUMN IF NOT EXISTS requisicao_pagamento_id INTEGER REFERENCES requisicao_pagamento(id);
CREATE INDEX IF NOT EXISTS idx_conta_requisicao_pagamento ON conta (requisicao_pagamento_id);

-- Carga inicial: beneficiarios
INSERT INTO beneficiario_credito (nome, regra, data_criacao) VALUES
 ('Cliente + Honorários Contratuais', 'CLIENTE_E_HONORARIOS', now()),
 ('Sucumbência', 'SOMENTE_HONORARIOS', now()),
 ('Honorários Contratuais', 'SOMENTE_HONORARIOS', now()),
 ('Cliente', 'SOMENTE_CLIENTE', now())
ON CONFLICT (nome) DO NOTHING;

-- Carga inicial: parcerias (participantes por NOME de pessoa; pessoa inexistente fica de fora)
INSERT INTO parceria_levantamento (nome, tipo, marco1, marco2, data_criacao) VALUES
 ('Curciol', 'FIXA', NULL, NULL, now()), ('Custas', 'FIXA', NULL, NULL, now()),
 ('Parceria Ivan', 'FIXA', NULL, NULL, now()), ('Parceria Juan', 'FIXA', NULL, NULL, now()),
 ('Parceria Juan Estágio', 'FIXA', NULL, NULL, now()),
 ('Parceria Kleber Andre Ferreira', 'TEMPORAL', '2016-11-01', '2018-06-01', now()),
 ('Parceria Campos', 'TEMPORAL', '2016-11-01', '2018-06-01', now())
ON CONFLICT (nome) DO NOTHING;

-- Parceiros que ainda nao existem (decisao do usuario em 13/10/2026): pessoas novas no grupo Parceiro (6).
-- Nomes provisórios = apelidos da planilha; o escritorio pode completar o cadastro em Pessoas.
INSERT INTO pessoa (tipo_pessoa_id, nome, nome_busca, aceita_receber_mensagen_whatsapp, observacao, data_criacao)
SELECT n.tipo, n.nome, n.busca, 'F', 'Parceiro de levantamento (cadastro inicial do financeiro de levantamentos).', now()
  FROM (VALUES (1, 'KBÇA', 'KBCA'), (1, 'CAMPOS', 'CAMPOS'), (2, 'ALLIANCE', 'ALLIANCE'), (2, 'ESTRUTURA', 'ESTRUTURA')) n(tipo, nome, busca)
 WHERE NOT EXISTS (SELECT 1 FROM pessoa p JOIN pessoa_grupo g ON g.pessoa_id = p.id AND g.grupo_id = 6 WHERE normalize(p.nome, NFC) = normalize(n.nome, NFC));

-- nome_busca no padrao do cadastro (maiusculo, sem acento); corrige quem ja rodou a versao anterior (minusculo)
UPDATE pessoa p SET nome_busca = n.busca
  FROM (VALUES ('KBÇA', 'KBCA'), ('CAMPOS', 'CAMPOS'), ('ALLIANCE', 'ALLIANCE'), ('ESTRUTURA', 'ESTRUTURA')) n(nome, busca)
 WHERE normalize(p.nome, NFC) = normalize(n.nome, NFC)
   AND p.observacao = 'Parceiro de levantamento (cadastro inicial do financeiro de levantamentos).'
   AND p.nome_busca IS DISTINCT FROM n.busca;

INSERT INTO pessoa_grupo (pessoa_id, grupo_id)
SELECT p.id, 6 FROM pessoa p
 WHERE normalize(p.nome, NFC) IN (normalize('KBÇA', NFC), 'CAMPOS', 'ALLIANCE', 'ESTRUTURA')
   AND p.observacao = 'Parceiro de levantamento (cadastro inicial do financeiro de levantamentos).'
   AND NOT EXISTS (SELECT 1 FROM pessoa_grupo g WHERE g.pessoa_id = p.id AND g.grupo_id = 6);

WITH carga (parceria, pessoa, nivel, pct, antes, depois, dif, ordem) AS (VALUES
 ('Curciol','CURCIOL SOCIEDADE DE ADVOGADOS','FIXO',25,NULL,NULL,'S',1), ('Curciol','JOSÉ ALMIR CURCIOL','FIXO',25,NULL,NULL,'N',2),
 ('Curciol','KLEBER CURCIOL','FIXO',25,NULL,NULL,'N',3), ('Curciol','DANIELLE DOS SANTOS MARQUES CURCIOL','FIXO',25,NULL,NULL,'N',4),
 ('Custas','CURCIOL SOCIEDADE DE ADVOGADOS','FIXO',100,NULL,NULL,'S',1),
 ('Parceria Ivan','CURCIOL SOCIEDADE DE ADVOGADOS','FIXO',20,NULL,NULL,'S',1), ('Parceria Ivan','JOSÉ ALMIR CURCIOL','FIXO',20,NULL,NULL,'N',2),
 ('Parceria Ivan','KLEBER CURCIOL','FIXO',20,NULL,NULL,'N',3), ('Parceria Ivan','DANIELLE DOS SANTOS MARQUES CURCIOL','FIXO',20,NULL,NULL,'N',4),
 ('Parceria Ivan','IVAN PAULO FIORANI','FIXO',20,NULL,NULL,'N',5),
 ('Parceria Juan','CURCIOL SOCIEDADE DE ADVOGADOS','FIXO',20,NULL,NULL,'S',1), ('Parceria Juan','JOSÉ ALMIR CURCIOL','FIXO',20,NULL,NULL,'N',2),
 ('Parceria Juan','KLEBER CURCIOL','FIXO',20,NULL,NULL,'N',3), ('Parceria Juan','DANIELLE DOS SANTOS MARQUES CURCIOL','FIXO',20,NULL,NULL,'N',4),
 ('Parceria Juan','JUAN WILLIAN DE OLIVEIRA','FIXO',20,NULL,NULL,'N',5),
 ('Parceria Juan Estágio','CURCIOL SOCIEDADE DE ADVOGADOS','FIXO',24.5,NULL,NULL,'S',1), ('Parceria Juan Estágio','JOSÉ ALMIR CURCIOL','FIXO',24.5,NULL,NULL,'N',2),
 ('Parceria Juan Estágio','KLEBER CURCIOL','FIXO',24.5,NULL,NULL,'N',3), ('Parceria Juan Estágio','DANIELLE DOS SANTOS MARQUES CURCIOL','FIXO',24.5,NULL,NULL,'N',4),
 ('Parceria Juan Estágio','JUAN WILLIAN DE OLIVEIRA','FIXO',2,NULL,NULL,'N',5),
 ('Parceria Kleber Andre Ferreira','DANIELLE DOS SANTOS MARQUES CURCIOL','MARCO1',NULL,50,25,'N',1),
 ('Parceria Kleber Andre Ferreira','CURCIOL SOCIEDADE DE ADVOGADOS','MARCO2',NULL,16,30,'S',2),
 ('Parceria Kleber Andre Ferreira','JOSÉ ALMIR CURCIOL','MARCO2',NULL,33.4,25,'N',3),
 ('Parceria Kleber Andre Ferreira','KLEBER CURCIOL','MARCO2',NULL,25,25,'N',4),
 ('Parceria Campos','DANIELLE DOS SANTOS MARQUES CURCIOL','MARCO1',NULL,50,25,'N',1),
 ('Parceria Campos','CURCIOL SOCIEDADE DE ADVOGADOS','MARCO2',NULL,16,30,'S',2),
 ('Parceria Campos','JOSÉ ALMIR CURCIOL','MARCO2',NULL,33.4,25,'N',3),
 ('Parceria Campos','KLEBER CURCIOL','MARCO2',NULL,25,25,'N',4),
 ('Parceria Kleber Andre Ferreira','KBÇA','MARCO2',NULL,20,20,'N',5),
 ('Parceria Kleber Andre Ferreira','ALLIANCE','MARCO2',NULL,2.8,0,'N',6),
 ('Parceria Kleber Andre Ferreira','ESTRUTURA','MARCO2',NULL,2.8,0,'N',7),
 ('Parceria Campos','CAMPOS','MARCO2',NULL,20,20,'N',5),
 ('Parceria Campos','ALLIANCE','MARCO2',NULL,2.8,0,'N',6),
 ('Parceria Campos','ESTRUTURA','MARCO2',NULL,2.8,0,'N',7)
)
INSERT INTO parceria_levantamento_participante (parceria_levantamento_id, pessoa_id, nivel, percentual, percentual_antes, percentual_depois, recebe_diferenca, ordem)
SELECT p.id, pe.id, c.nivel, c.pct, c.antes, c.depois, c.dif, c.ordem
  FROM carga c
  JOIN parceria_levantamento p ON p.nome = c.parceria
  -- pessoa por nome; para os parceiros novos, so a do grupo Parceiro (evita homonimos de clientes)
  JOIN LATERAL (SELECT pp.id FROM pessoa pp
                 WHERE normalize(pp.nome, NFC) = normalize(c.pessoa, NFC)  -- cadastros antigos tem acento decomposto (NFD)
                   AND (normalize(c.pessoa, NFC) NOT IN (normalize('KBÇA', NFC),'CAMPOS','ALLIANCE','ESTRUTURA')
                        OR EXISTS (SELECT 1 FROM pessoa_grupo g WHERE g.pessoa_id = pp.id AND g.grupo_id = 6))
                 ORDER BY pp.id LIMIT 1) pe ON TRUE
ON CONFLICT (parceria_levantamento_id, pessoa_id) DO NOTHING;

-- Permissoes: cadastros = grupos de "Parceiros"; telas do pagamento = grupos de RequisicaoPagamentoListagemHeaderList UNIAO os de ContaFormView (Financeiro)
INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_program) + ROW_NUMBER() OVER (ORDER BY n.controller), n.nome, n.controller
  FROM (VALUES ('Beneficiários do crédito','BeneficiarioCreditoList'), ('Beneficiário do crédito','BeneficiarioCreditoForm'),
               ('Parcerias de levantamento','ParceriaLevantamentoList'), ('Parceria de levantamento','ParceriaLevantamentoForm'),
               ('Gerar financeiro do levantamento','LevantamentoFinanceiroForm'), ('Financeiro do levantamento','LevantamentoFinanceiroView')) n(nome, controller)
 WHERE NOT EXISTS (SELECT 1 FROM system_program sp WHERE sp.controller = n.controller);

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id),0) FROM system_group_program) + ROW_NUMBER() OVER (ORDER BY par.system_group_id, par.program_id), par.system_group_id, par.program_id
  FROM (SELECT DISTINCT g.system_group_id, p.id AS program_id   -- DISTINCT: grupo com as duas telas de origem entra uma vez so
          FROM system_program p
          JOIN (SELECT gp.system_group_id, sp.controller AS origem
                  FROM system_group_program gp JOIN system_program sp ON sp.id = gp.system_program_id
                 WHERE sp.controller IN ('RequisicaoPagamentoListagemHeaderList', 'ContaFormView', 'ParceirosHeaderList')) g
            ON (p.controller LIKE 'Levantamento%' AND g.origem IN ('RequisicaoPagamentoListagemHeaderList', 'ContaFormView'))
            OR (p.controller NOT LIKE 'Levantamento%' AND g.origem = 'ParceirosHeaderList')
         WHERE p.controller IN ('BeneficiarioCreditoList','BeneficiarioCreditoForm','ParceriaLevantamentoList','ParceriaLevantamentoForm','LevantamentoFinanceiroForm','LevantamentoFinanceiroView')
           AND NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = g.system_group_id AND x.system_program_id = p.id)) par;

-- Validacao da carga: pessoa que nao casou e descartada em silencio pelo INSERT acima; aqui falha alto.
DO $$
DECLARE r RECORD; total INTEGER;
BEGIN
  FOR r IN
    SELECT e.nome, e.n, e.soma, e.m2_antes, e.m2_depois,
           (SELECT count(*) FROM parceria_levantamento_participante x WHERE x.parceria_levantamento_id = p.id) AS n_real,
           (SELECT COALESCE(sum(x.percentual), 0) FROM parceria_levantamento_participante x WHERE x.parceria_levantamento_id = p.id) AS soma_real,
           (SELECT COALESCE(sum(x.percentual_antes), 0) FROM parceria_levantamento_participante x WHERE x.parceria_levantamento_id = p.id AND x.nivel = 'MARCO2') AS m2_antes_real,
           (SELECT COALESCE(sum(x.percentual_depois), 0) FROM parceria_levantamento_participante x WHERE x.parceria_levantamento_id = p.id AND x.nivel = 'MARCO2') AS m2_depois_real
      FROM (VALUES ('Curciol',4,100,0,0), ('Custas',1,100,0,0), ('Parceria Ivan',5,100,0,0), ('Parceria Juan',5,100,0,0),
                   ('Parceria Juan Estágio',5,100,0,0), ('Parceria Kleber Andre Ferreira',7,0,100,100), ('Parceria Campos',7,0,100,100))
           e(nome, n, soma, m2_antes, m2_depois)
      LEFT JOIN parceria_levantamento p ON normalize(p.nome, NFC) = normalize(e.nome, NFC)
  LOOP
    IF r.n_real IS NULL OR r.n_real <> r.n OR r.soma_real <> r.soma OR r.m2_antes_real <> r.m2_antes OR r.m2_depois_real <> r.m2_depois THEN
      RAISE EXCEPTION 'Parceria "%" incompleta: % participantes (esperado %), soma % (esperado %), marco2 antes/depois %/% (esperado %/%). Alguma pessoa nao foi encontrada pelo nome.',
        r.nome, r.n_real, r.n, r.soma_real, r.soma, r.m2_antes_real, r.m2_depois_real, r.m2_antes, r.m2_depois;
    END IF;
  END LOOP;
  SELECT count(*) INTO total FROM parceria_levantamento_participante pp JOIN parceria_levantamento p ON p.id = pp.parceria_levantamento_id
   WHERE normalize(p.nome, NFC) IN ('Curciol','Custas','Parceria Ivan','Parceria Juan',normalize('Parceria Juan Estágio', NFC),'Parceria Kleber Andre Ferreira','Parceria Campos');
  IF total <> 34 THEN RAISE EXCEPTION 'Carga de participantes incompleta: % linhas (esperado 34).', total; END IF;
  -- cada programa novo precisa de pelo menos um grupo (senao ninguem acessa a tela)
  FOR r IN
    SELECT sp.controller FROM system_program sp
     WHERE sp.controller IN ('BeneficiarioCreditoList','BeneficiarioCreditoForm','ParceriaLevantamentoList','ParceriaLevantamentoForm','LevantamentoFinanceiroForm','LevantamentoFinanceiroView')
       AND NOT EXISTS (SELECT 1 FROM system_group_program gp WHERE gp.system_program_id = sp.id)
  LOOP
    RAISE EXCEPTION 'Programa "%" sem nenhum grupo de acesso.', r.controller;
  END LOOP;
  IF (SELECT count(*) FROM system_program WHERE controller IN ('BeneficiarioCreditoList','BeneficiarioCreditoForm','ParceriaLevantamentoList','ParceriaLevantamentoForm','LevantamentoFinanceiroForm','LevantamentoFinanceiroView')) <> 6 THEN
    RAISE EXCEPTION 'Os 6 programas novos nao foram todos cadastrados.';
  END IF;
END $$;

COMMIT;

-- ROLLBACK (de cima para baixo)
-- DELETE FROM system_group_program WHERE system_program_id IN (SELECT id FROM system_program WHERE controller IN ('BeneficiarioCreditoList','BeneficiarioCreditoForm','ParceriaLevantamentoList','ParceriaLevantamentoForm','LevantamentoFinanceiroForm','LevantamentoFinanceiroView'));
-- DELETE FROM system_program WHERE controller IN ('BeneficiarioCreditoList','BeneficiarioCreditoForm','ParceriaLevantamentoList','ParceriaLevantamentoForm','LevantamentoFinanceiroForm','LevantamentoFinanceiroView');
-- DROP INDEX IF EXISTS idx_conta_requisicao_pagamento; ALTER TABLE conta DROP COLUMN IF EXISTS requisicao_pagamento_id;
-- DROP TABLE IF EXISTS levantamento_financeiro_repasse, levantamento_financeiro, parceria_levantamento_participante, parceria_levantamento, beneficiario_credito;
-- DELETE FROM pessoa_grupo WHERE grupo_id = 6 AND pessoa_id IN (SELECT id FROM pessoa WHERE nome IN ('KBÇA','CAMPOS','ALLIANCE','ESTRUTURA') AND observacao = 'Parceiro de levantamento (cadastro inicial do financeiro de levantamentos).');
-- DELETE FROM pessoa WHERE nome IN ('KBÇA','CAMPOS','ALLIANCE','ESTRUTURA') AND observacao = 'Parceiro de levantamento (cadastro inicial do financeiro de levantamentos).';  -- so se nenhuma tabela referenciar
