-- =====================================================================
-- Curciol - Pre-Processo, ajustes
-- Banco: escritorio (homologacao: curciol_homologacao) | PostgreSQL
-- Data:  2026-09-23
--
-- Complementa 2026-09-22-pre-processo.sql. Aplicar depois dele.
--
-- Duas mudancas:
--   1. processo_cliente sai. Um processo - pre ou definitivo - sempre tem
--      contrato, entao o dono continua sendo descoberto por
--      contrato_processo -> contrato_pessoa, como sempre foi.
--   2. A etapa inicial do pre-processo passa a ser escolhida no cadastro de
--      etapas, por tipo de processo, em vez de localizada pelo nome.
--
-- Script idempotente. Rollback comentado no fim.
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 1. Etapa padrao do pre-processo
--
-- Ate aqui o servico procurava a etapa por nome ("organiza...documental").
-- Funcionava, mas amarrava a regra a um texto do cadastro: renomear a etapa
-- quebraria a criacao automatica do andamento, em silencio.
--
-- Agora a escolha e explicita e fica com quem cadastra as etapas. Uma etapa
-- por trilha: no maximo uma marcada para Judicial e no maximo uma para
-- Extrajudicial. Uma etapa que sirva as duas trilhas pode ocupar os dois
-- lugares ao mesmo tempo.
-- ---------------------------------------------------------------------
ALTER TABLE publicacao_etapa
    ADD COLUMN IF NOT EXISTS padrao_pre_processo CHARACTER(1);

ALTER TABLE publicacao_etapa
    ALTER COLUMN padrao_pre_processo SET DEFAULT 'N';

UPDATE publicacao_etapa SET padrao_pre_processo = 'N' WHERE padrao_pre_processo IS NULL;

/*
    Preserva o comportamento atual: as duas etapas de "Organização Documental"
    que o codigo achava pelo nome passam a ocupar o lugar explicitamente.
    Feito por nome + trilha, e nao por id fixo, para o script valer em
    qualquer base. Se ja houver uma etapa marcada para a trilha, nada muda.
*/
UPDATE publicacao_etapa pe
   SET padrao_pre_processo = 'S'
 WHERE pe.etapa_nome ILIKE '%organiza%documental%'
   AND COALESCE(UPPER(TRIM(pe.judicial)), 'N') = 'S'
   AND NOT EXISTS (
       SELECT 1 FROM publicacao_etapa x
        WHERE x.padrao_pre_processo = 'S'
          AND COALESCE(UPPER(TRIM(x.judicial)), 'N') = 'S'
   )
   AND pe.id = (
       SELECT MIN(y.id) FROM publicacao_etapa y
        WHERE y.etapa_nome ILIKE '%organiza%documental%'
          AND COALESCE(UPPER(TRIM(y.judicial)), 'N') = 'S'
   );

UPDATE publicacao_etapa pe
   SET padrao_pre_processo = 'S'
 WHERE pe.etapa_nome ILIKE '%organiza%documental%'
   AND COALESCE(UPPER(TRIM(pe.extrajudicial)), 'N') = 'S'
   AND NOT EXISTS (
       SELECT 1 FROM publicacao_etapa x
        WHERE x.padrao_pre_processo = 'S'
          AND COALESCE(UPPER(TRIM(x.extrajudicial)), 'N') = 'S'
   )
   AND pe.id = (
       SELECT MIN(y.id) FROM publicacao_etapa y
        WHERE y.etapa_nome ILIKE '%organiza%documental%'
          AND COALESCE(UPPER(TRIM(y.extrajudicial)), 'N') = 'S'
   );

/*
    "Somente uma por tipo" no banco, e nao so na tela.

    O indice e parcial e recai sobre uma coluna que, dentro do predicado, so
    pode valer 'S' - entao ele admite no maximo uma linha por trilha. A tela
    desmarca a anterior antes de gravar; este indice e a rede embaixo, para o
    caso de duas pessoas salvarem ao mesmo tempo ou de uma carga direta.
*/
CREATE UNIQUE INDEX IF NOT EXISTS uk_publicacao_etapa_padrao_judicial
    ON publicacao_etapa (padrao_pre_processo)
    WHERE padrao_pre_processo = 'S'
      AND COALESCE(UPPER(TRIM(judicial)), 'N') = 'S';

CREATE UNIQUE INDEX IF NOT EXISTS uk_publicacao_etapa_padrao_extrajudicial
    ON publicacao_etapa (padrao_pre_processo)
    WHERE padrao_pre_processo = 'S'
      AND COALESCE(UPPER(TRIM(extrajudicial)), 'N') = 'S';

COMMIT;

-- ---------------------------------------------------------------------
-- 2. processo_view volta a descobrir o cliente so pelo contrato
--
-- A uniao com processo_cliente sai. O restante da definicao continua como
-- ficou em 22/09: area, assunto e responsavel seguem em LEFT JOIN, para um
-- pre-processo sem esses campos ainda aparecer para o cliente, e as tres
-- colunas novas continuam no fim.
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
     JOIN contrato_processo cp ON cp.processo_id = p.id
     JOIN contrato_pessoa cpe ON cpe.contrato_id = cp.contrato_id
     JOIN pessoa pe ON pe.id = cpe.cliente_id
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

-- ---------------------------------------------------------------------
-- 3. processo_cliente sai
--
-- Ela existia para o caso de um pre-processo nascer antes do contrato. Esse
-- caso nao existe: todo processo tem contrato. Nada de historico se perde -
-- a tabela e desta mesma entrega e nenhum registro de producao passou por
-- ela.
--
-- Roda depois da view, que e quem ainda a referenciava.
-- ---------------------------------------------------------------------
DROP INDEX IF EXISTS uk_processo_cliente;
DROP TABLE IF EXISTS processo_cliente;

-- =====================================================================
-- ROLLBACK
-- =====================================================================
-- Para reverter, aplique de novo a secao 4 (processo_cliente) e a secao 5
-- (processo_view) de 2026-09-22-pre-processo.sql, e depois:
--
-- DROP INDEX IF EXISTS uk_publicacao_etapa_padrao_extrajudicial;
-- DROP INDEX IF EXISTS uk_publicacao_etapa_padrao_judicial;
-- ALTER TABLE publicacao_etapa DROP COLUMN IF EXISTS padrao_pre_processo;
