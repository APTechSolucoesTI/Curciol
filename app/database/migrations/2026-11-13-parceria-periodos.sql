-- 2026-11-13 - Parceria de levantamento "Por tempo" passa a ser por periodos (decisao do usuario em 13/11/2026).
--
-- Antes (cascata, igual a planilha): o nivel 1 tirava a parte dele e o nivel 2 dividia o resto.
-- Agora: 3 periodos - antes do 1o marco, entre os marcos, depois do 2o marco. Cada coluna soma 100% e
--   percentual = a x antes + (p - a) x entre + (1 - p) x depois   (a e p: fracao do tempo antes de cada marco).
-- Nivel diz em qual marco o percentual muda: MARCO1 (entre = depois), MARCO2 (entre = antes), MARCO12 (entre livre).
--
-- Conversao (so na primeira execucao, quando a coluna percentual_entre ainda nao existe):
--   D_antes / D_depois = soma do nivel 1 nas colunas antes / depois;
--   nivel 1: antes = antes, entre = depois, depois = depois;
--   nivel 2: antes = (100 - D_antes) x antes / 100, entre = (100 - D_depois) x antes / 100, depois = (100 - D_depois) x depois / 100.
--   Ex.: SERVIDORES - Dani 50/25/25, Curciol 8/12/22,5, Jose 16,7/25,05/18,75, Kleber 12,5/18,75/18,75, Kbca/Campos 10/15/15,
--   Alliance e Lumes 1,4/2,1/0.
-- Na mesma vez, os participantes de todas as parcerias ficam na ordem que as telas mostram hoje (ordem da planilha:
-- RepasseColaboradorService::ORDEM_COLABORADORES; quem nao esta na lista vem depois, na ordem atual). A partir daqui a ordem
-- da parceria (arrastar na tela) e a ordem das telas dos financeiros gerados depois.
--
-- Ordem nos financeiros: levantamento_financeiro_repasse.ordem e conta_profissional.ordem, gravadas na geracao.
-- NULL = gerado antes desta regra: a tela continua na ordem da planilha (nada muda no que ja existe).
--
-- Idempotente. Aplicar antes do codigo.

ALTER TABLE levantamento_financeiro_repasse ADD COLUMN IF NOT EXISTS ordem INTEGER;
ALTER TABLE conta_profissional ADD COLUMN IF NOT EXISTS ordem INTEGER;

ALTER TABLE parceria_levantamento_participante DROP CONSTRAINT IF EXISTS parceria_levantamento_participante_nivel_check;
ALTER TABLE parceria_levantamento_participante ADD CONSTRAINT parceria_levantamento_participante_nivel_check
    CHECK (nivel IN ('FIXO', 'MARCO1', 'MARCO2', 'MARCO12'));

DO $$
DECLARE r RECORD;
BEGIN
  IF EXISTS (SELECT 1 FROM information_schema.columns
              WHERE table_name = 'parceria_levantamento_participante' AND column_name = 'percentual_entre') THEN
    RAISE NOTICE 'Parcerias ja convertidas para periodos: nada a fazer.';
    RETURN;
  END IF;

  ALTER TABLE parceria_levantamento_participante ADD COLUMN percentual_entre NUMERIC(12,8);

  -- cascata -> periodos
  WITH d AS (
      SELECT x.parceria_levantamento_id AS pid,
             COALESCE(SUM(x.percentual_antes)  FILTER (WHERE x.nivel = 'MARCO1'), 0) AS d_antes,
             COALESCE(SUM(x.percentual_depois) FILTER (WHERE x.nivel = 'MARCO1'), 0) AS d_depois
        FROM parceria_levantamento_participante x
        JOIN parceria_levantamento p ON p.id = x.parceria_levantamento_id
       WHERE p.tipo = 'TEMPORAL'
       GROUP BY x.parceria_levantamento_id
  ), novo AS (
      SELECT x.id, x.nivel,
             CASE WHEN x.nivel = 'MARCO1' THEN x.percentual_antes
                  ELSE round((100 - d.d_antes)  * x.percentual_antes  / 100, 8) END AS antes,
             CASE WHEN x.nivel = 'MARCO1' THEN x.percentual_depois
                  ELSE round((100 - d.d_depois) * x.percentual_antes  / 100, 8) END AS entre,
             CASE WHEN x.nivel = 'MARCO1' THEN x.percentual_depois
                  ELSE round((100 - d.d_depois) * x.percentual_depois / 100, 8) END AS depois
        FROM parceria_levantamento_participante x
        JOIN d ON d.pid = x.parceria_levantamento_id
  )
  UPDATE parceria_levantamento_participante x
     SET percentual_antes  = n.antes,
         percentual_entre  = n.entre,
         percentual_depois = n.depois,
         nivel = CASE WHEN n.nivel = 'MARCO1' THEN 'MARCO1'
                      WHEN n.antes = n.entre THEN 'MARCO2'   -- muda so no 2o marco (ou nao muda)
                      WHEN n.entre = n.depois THEN 'MARCO1'  -- muda so no 1o marco
                      ELSE 'MARCO12' END
    FROM novo n
   WHERE n.id = x.id;

  -- ordem das telas de hoje (ordem da planilha), depois a ordem atual
  WITH lista AS (
      SELECT ARRAY[normalize('CURCIOL SOCIEDADE DE ADVOGADOS', NFC), normalize('JOSÉ ALMIR CURCIOL', NFC), normalize('KLEBER CURCIOL', NFC),
                   normalize('DANIELLE DOS SANTOS MARQUES CURCIOL', NFC), normalize('IVAN PAULO FIORANI', NFC),
                   normalize('JUAN WILLIAN DE OLIVEIRA', NFC), normalize('JUAN WILLIAN DE OLIVEIRA BARROS', NFC),
                   normalize('KLEBER ANDRE FERREIRA', NFC), normalize('CARLOS ROBERTO DE CAMPOS FILHO', NFC),
                   normalize('ALLIANCE ADMINISTRAÇÃO COMÉRCIO E ENGENHARIA LTDA', NFC), normalize('LUMES GESTAO IMOBILIARIA LTDA', NFC)]::text[] AS nomes
  ), pos AS (
      SELECT x.id,
             row_number() OVER (PARTITION BY x.parceria_levantamento_id
                                ORDER BY COALESCE(array_position(l.nomes, normalize(pe.nome, NFC)::text), 999), x.ordem NULLS LAST, x.id) AS nova
        FROM parceria_levantamento_participante x
        JOIN pessoa pe ON pe.id = x.pessoa_id
       CROSS JOIN lista l
  )
  UPDATE parceria_levantamento_participante x SET ordem = pos.nova FROM pos WHERE pos.id = x.id;
END $$;

-- conferencia: toda parceria por tempo soma 100% em cada periodo; o "entre" de nivel 1/2 espelha a coluna certa
DO $$
DECLARE r RECORD;
BEGIN
  FOR r IN
    SELECT p.nome,
           round(SUM(x.percentual_antes), 6) AS antes, round(SUM(x.percentual_entre), 6) AS entre, round(SUM(x.percentual_depois), 6) AS depois
      FROM parceria_levantamento p JOIN parceria_levantamento_participante x ON x.parceria_levantamento_id = p.id
     WHERE p.tipo = 'TEMPORAL'
     GROUP BY p.id, p.nome
  LOOP
    IF r.antes <> 100 OR r.entre <> 100 OR r.depois <> 100 THEN
      RAISE EXCEPTION 'Parceria "%" nao soma 100%% por periodo: antes %, entre %, depois %.', r.nome, r.antes, r.entre, r.depois;
    END IF;
  END LOOP;
  IF EXISTS (SELECT 1 FROM parceria_levantamento_participante x JOIN parceria_levantamento p ON p.id = x.parceria_levantamento_id
              WHERE p.tipo = 'TEMPORAL'
                AND (x.nivel NOT IN ('MARCO1', 'MARCO2', 'MARCO12') OR x.percentual_entre IS NULL
                     OR (x.nivel = 'MARCO1' AND x.percentual_entre <> x.percentual_depois)
                     OR (x.nivel = 'MARCO2' AND x.percentual_entre <> x.percentual_antes))) THEN
    RAISE EXCEPTION 'Participante de parceria por tempo com nivel ou "entre" inconsistente.';
  END IF;
END $$;

-- Volta (so se o codigo antigo voltar; os percentuais da cascata NAO sao recalculados - restaurar do backup):
-- ALTER TABLE parceria_levantamento_participante DROP COLUMN IF EXISTS percentual_entre;
-- ALTER TABLE levantamento_financeiro_repasse DROP COLUMN IF EXISTS ordem;
-- ALTER TABLE conta_profissional DROP COLUMN IF EXISTS ordem;
