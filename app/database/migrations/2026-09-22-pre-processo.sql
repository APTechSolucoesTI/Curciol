-- =====================================================================
-- Curciol - Pre-Processo
-- Banco: escritorio (homologacao: curciol_homologacao) | PostgreSQL
-- Data:  2026-09-22
--
-- O pre-processo e o proprio processo na sua fase inicial. Nao existe
-- tabela paralela: o registro nasce em "processo" sem numero judicial e,
-- quando a publicacao correspondente chega, o MESMO id recebe os dados
-- judiciais. Por isso as mudancas abaixo sao aditivas.
--
-- Script idempotente: pode ser executado mais de uma vez.
-- O rollback esta no fim do arquivo, comentado.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. processo.numero_cnj_numero passa a aceitar NULL
--
-- Um pre-processo ainda nao foi distribuido, entao nao tem numero. A
-- ausencia e representada por NULL - nunca por '' nem por uma sequencia
-- inventada, que poluiria a busca por numero real.
-- ---------------------------------------------------------------------
ALTER TABLE processo ALTER COLUMN numero_cnj_numero DROP NOT NULL;

-- ---------------------------------------------------------------------
-- 2. Estado do registro
--
-- Tres estados, duas colunas:
--   convencional ............. pre_processo = 'N' e data_conversao IS NULL
--   pre-processo pendente .... pre_processo = 'S'
--   convertido ............... pre_processo = 'N' e data_conversao NOT NULL
--
-- descricao_pre_processo sobrevive a conversao de proposito: e o que
-- identifica a origem do registro em auditoria e continua servindo de
-- busca depois que o numero existe.
--
-- tipo_processo_id NAO participa disso. Judicial/Extrajudicial e fase
-- pre-processual sao conceitos diferentes.
-- ---------------------------------------------------------------------
ALTER TABLE processo ADD COLUMN IF NOT EXISTS pre_processo           CHARACTER(1);
ALTER TABLE processo ADD COLUMN IF NOT EXISTS descricao_pre_processo TEXT;
ALTER TABLE processo ADD COLUMN IF NOT EXISTS data_conversao         TIMESTAMP WITHOUT TIME ZONE;
ALTER TABLE processo ADD COLUMN IF NOT EXISTS conversao_user_id      INTEGER;

ALTER TABLE processo ALTER COLUMN pre_processo SET DEFAULT 'N';

-- Processos historicos sao todos convencionais.
UPDATE processo SET pre_processo = 'N' WHERE pre_processo IS NULL;

DO $do$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_processo_conversao_user_id'
    ) THEN
        ALTER TABLE processo
            ADD CONSTRAINT fk_processo_conversao_user_id
            FOREIGN KEY (conversao_user_id) REFERENCES system_users(id);
    END IF;
END
$do$;

-- ---------------------------------------------------------------------
-- 3. Duplicidade de numero
--
-- A checagem de duplicidade vivia so no PHP. Como agora varios registros
-- convivem sem numero, o indice e parcial: protege os numeros reais e
-- ignora os NULL, que sao justamente os pre-processos.
--
-- Verificado em 22/09/2026 na base de homologacao: 0 numeros duplicados
-- e 0 numeros vazios, entao o indice sobe sem conflito.
-- ---------------------------------------------------------------------
CREATE UNIQUE INDEX IF NOT EXISTS uk_processo_numero_cnj_numero
    ON processo (numero_cnj_numero)
    WHERE numero_cnj_numero IS NOT NULL AND btrim(numero_cnj_numero) <> '';

-- Listagem e janela de pesquisa filtram por pre-processo pendente.
CREATE INDEX IF NOT EXISTS idx_processo_pre_processo
    ON processo (pre_processo)
    WHERE pre_processo = 'S';

-- ---------------------------------------------------------------------
-- 4. processo_cliente - vinculo direto processo <-> cliente
--
-- Ate aqui o portal so sabia de quem era um processo atravessando
-- contrato_processo -> contrato_pessoa. Um pre-processo pode existir
-- antes do contrato, e sem essa ponte ele nao teria dono - ou ficaria
-- visivel para quem nao deveria.
--
-- Esta tabela nao substitui o caminho por contrato: soma-se a ele. A
-- view processo_view passa a unir os dois.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS processo_cliente (
    id              SERIAL PRIMARY KEY,
    processo_id     INTEGER NOT NULL,
    cliente_id      INTEGER NOT NULL,
    data_criacao    TIMESTAMP WITHOUT TIME ZONE,
    criacao_user_id INTEGER
);

DO $do$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_processo_cliente_processo_id') THEN
        ALTER TABLE processo_cliente ADD CONSTRAINT fk_processo_cliente_processo_id
            FOREIGN KEY (processo_id) REFERENCES processo(id);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_processo_cliente_cliente_id') THEN
        ALTER TABLE processo_cliente ADD CONSTRAINT fk_processo_cliente_cliente_id
            FOREIGN KEY (cliente_id) REFERENCES pessoa(id);
    END IF;

    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_processo_cliente_criacao_user_id') THEN
        ALTER TABLE processo_cliente ADD CONSTRAINT fk_processo_cliente_criacao_user_id
            FOREIGN KEY (criacao_user_id) REFERENCES system_users(id);
    END IF;
END
$do$;

-- Reexecutar a associacao nao pode duplicar o vinculo.
CREATE UNIQUE INDEX IF NOT EXISTS uk_processo_cliente
    ON processo_cliente (processo_id, cliente_id);

COMMIT;

-- ---------------------------------------------------------------------
-- 5. processo_view
--
-- Duas mudancas, ambas necessarias para o pre-processo aparecer no
-- portal sem afetar o que ja aparece hoje:
--
-- a) o vinculo com o cliente vira a uniao dos dois caminhos
--    (contrato_processo/contrato_pessoa e processo_cliente). UNION, nao
--    UNION ALL: um cliente ligado pelos dois caminhos conta uma vez.
--
-- b) area, assunto e responsavel viram LEFT JOIN. Eram INNER, e um
--    pre-processo que ainda nao tem assunto definido sumiria da lista do
--    cliente. Conferido em 22/09/2026: os 27 processos com
--    exibir_cliente = 'S' tem os tres campos preenchidos, entao nenhuma
--    linha existente muda.
--
-- As colunas antigas continuam com o mesmo nome, mesmo tipo e mesma
-- ordem; as tres novas entram no fim.
-- ---------------------------------------------------------------------
CREATE OR REPLACE VIEW processo_view AS
 SELECT p.id,
    tp.nome AS tipo_processo,
    p.numero_cnj_numero AS numero,
    pe.nome AS cliente,
    a.nome AS area,
    ass.nome AS assunto,
    rep.nome AS representante,
    pe.id AS pessoa_id,
    p.exibir_cliente,
    pp_ult.publicacao_etapa_id AS ultima_etapa_id,
    etapa_pp.etapa_nome AS ultima_etapa,
    p.pre_processo,
    p.descricao_pre_processo,
    p.data_conversao
   FROM processo p
     JOIN ( SELECT cp.processo_id,
                   cpe.cliente_id
              FROM contrato_processo cp
              JOIN contrato_pessoa cpe ON cpe.contrato_id = cp.contrato_id
            UNION
            SELECT pc.processo_id,
                   pc.cliente_id
              FROM processo_cliente pc ) vinc ON vinc.processo_id = p.id
     JOIN pessoa pe ON pe.id = vinc.cliente_id
     JOIN tipo_processo tp ON tp.id = p.tipo_processo_id
     LEFT JOIN area a ON a.id = p.area_id
     LEFT JOIN assunto ass ON ass.id = p.assunto_id
     LEFT JOIN pessoa rep ON rep.id = p.responsavel_id
     LEFT JOIN ( SELECT DISTINCT ON (mov.processo_id) mov.processo_id,
            mov.publicacao_etapa_id,
            mov.publicacao_id,
            mov.andamento_id,
            mov.id,
            mov.data_ultima_movimentacao
           FROM ( SELECT pp.processo_id,
                    pp.publicacao_etapa_id,
                    pp.publicacao_id,
                    pp.andamento_id,
                    pp.id,
                    pub.data_disponibilizacao::timestamp without time zone AS data_ultima_movimentacao
                   FROM processo_publicacoes pp
                     JOIN publicacao pub ON pub.id = pp.publicacao_id
                  WHERE (pp.publicacao_etapa_id <> ALL (ARRAY[1, 10])) AND pub.etapa_verificada = 'S'::bpchar
                UNION ALL
                 SELECT pp.processo_id,
                    pp.publicacao_etapa_id,
                    pp.publicacao_id,
                    pp.andamento_id,
                    pp.id,
                    andm.data_andamento AS data_ultima_movimentacao
                   FROM processo_publicacoes pp
                     JOIN andamento andm ON andm.id = pp.andamento_id
                  WHERE (pp.publicacao_etapa_id <> ALL (ARRAY[1, 10])) AND andm.etapa_verificada = 'S'::bpchar) mov
          ORDER BY mov.processo_id, mov.data_ultima_movimentacao DESC NULLS LAST, mov.id DESC) pp_ult ON pp_ult.processo_id = p.id
     LEFT JOIN publicacao_etapa etapa_pp ON etapa_pp.id = pp_ult.publicacao_etapa_id
  ORDER BY pp_ult.data_ultima_movimentacao DESC NULLS LAST, p.id DESC;

-- =====================================================================
-- ROLLBACK (executar de cima para baixo)
-- =====================================================================
-- Reponha antes a definicao anterior de processo_view a partir do backup
-- do schema: ela nao pode ser reconstruida depois que processo_cliente e
-- as colunas novas sairem.
--
-- DROP INDEX IF EXISTS uk_processo_cliente;
-- DROP TABLE IF EXISTS processo_cliente;
-- DROP INDEX IF EXISTS idx_processo_pre_processo;
-- DROP INDEX IF EXISTS uk_processo_numero_cnj_numero;
-- ALTER TABLE processo DROP CONSTRAINT IF EXISTS fk_processo_conversao_user_id;
-- ALTER TABLE processo DROP COLUMN IF EXISTS conversao_user_id;
-- ALTER TABLE processo DROP COLUMN IF EXISTS data_conversao;
-- ALTER TABLE processo DROP COLUMN IF EXISTS descricao_pre_processo;
-- ALTER TABLE processo DROP COLUMN IF EXISTS pre_processo;
--
-- So reponha o NOT NULL depois de tratar os pre-processos pendentes,
-- que por definicao nao tem numero:
-- ALTER TABLE processo ALTER COLUMN numero_cnj_numero SET NOT NULL;

-- ---------------------------------------------------------------------
-- 6. Permissoes das telas novas
--
-- Descoberto testando pela interface: a janela de pesquisa respondia
-- "Permissao negada". No Mad Builder toda classe de controle precisa estar
-- em system_program, e o acesso vem de system_group_program - sem isso a
-- tela existe no disco mas ninguem consegue abrir.
--
-- As duas telas novas entram nos mesmos grupos que ja tem a janela
-- equivalente de hoje (ProcessoSeekWindow, id 197): Admin e Processos.
--
-- Nenhuma das duas usa sequence: SystemProgram tem IDPOLICY 'max', entao o
-- id sai de max(id) + 1, como o proprio framework faz.
--
-- Idempotente: reexecutar nao duplica nem rouba permissao de ninguem.
-- ---------------------------------------------------------------------
INSERT INTO system_program (id, name, controller)
SELECT (SELECT max(id) FROM system_program) + 1,
       'Vincular pré-processo',
       'PreProcessoSeekWindow'
WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'PreProcessoSeekWindow');

INSERT INTO system_program (id, name, controller)
SELECT (SELECT max(id) FROM system_program) + 1,
       'Conversão de pré-processo',
       'PreProcessoConversaoForm'
WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'PreProcessoConversaoForm');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT coalesce(max(id), 0) FROM system_group_program)
           + row_number() OVER (ORDER BY g.system_group_id, p.id),
       g.system_group_id,
       p.id
FROM (SELECT DISTINCT system_group_id
      FROM system_group_program
      WHERE system_program_id = (SELECT id FROM system_program WHERE controller = 'ProcessoSeekWindow')) g
CROSS JOIN (SELECT id FROM system_program
            WHERE controller IN ('PreProcessoSeekWindow', 'PreProcessoConversaoForm')) p
WHERE NOT EXISTS (
    SELECT 1 FROM system_group_program x
    WHERE x.system_group_id = g.system_group_id
      AND x.system_program_id = p.id
);
