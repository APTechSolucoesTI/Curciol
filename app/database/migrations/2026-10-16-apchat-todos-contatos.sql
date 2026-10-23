-- Integracao APChat: exporta todas as pessoas com telefone valido (cliente, colaborador, parte contraria...),
-- sem olhar a autorizacao de WhatsApp. Telefone vazio ou invalido aparece como "Nao sincronizado" (IGNORADO)
-- direto pela view, sem precisar de sincronizacao. A regra do telefone e a mesma de APChatContactService::normalizarTelefone.
-- Idempotente. Depende de 2026-10-09-apchat-contatos.sql.
BEGIN;

DROP VIEW IF EXISTS apchat_contato_view;
CREATE VIEW apchat_contato_view AS
WITH tel AS (
    SELECT p.id,
           CASE WHEN length(d0) IN (12, 13) AND left(d0, 2) = '55' THEN substr(d0, 3) ELSE d0 END AS d,
           d0
      FROM (SELECT id, ltrim(regexp_replace(COALESCE(telefone, ''), '\D', '', 'g'), '0') AS d0 FROM pessoa) p
)
SELECT p.id,
       p.nome,
       p.tipo_pessoa_id,
       p.cpf_cnpj,
       p.email,
       p.telefone,
       ult.data_criacao      AS ultima_sincronizacao,
       ult.operacao          AS ultima_operacao,
       CASE WHEN NOT v.valido THEN 'IGNORADO'
            WHEN ult.situacao = 'IGNORADO' THEN NULL          -- ignorado por outro motivo (ex.: antes, sem autorizacao): ainda nao foi enviado
            ELSE ult.situacao END AS situacao,
       CASE WHEN t.d0 = '' THEN 'Telefone vazio.'
            WHEN NOT v.valido THEN 'Telefone inválido.'
            ELSE ult.mensagem END AS mensagem,
       ult.numero            AS numero_apchat,
       ult.apchat_contato_id AS apchat_contato_id,
       v.valido              AS telefone_valido
  FROM pessoa p
  JOIN tel t ON t.id = p.id
  CROSS JOIN LATERAL (
        SELECT (length(t.d) IN (10, 11) AND t.d ~ '^[1-9]{2}' AND (length(t.d) = 10 OR substr(t.d, 3, 1) = '9')) AS valido
  ) v
  LEFT JOIN LATERAL (
        SELECT l.*
          FROM apchat_contato_log l
         WHERE l.pessoa_id = p.id
         ORDER BY l.id DESC
         LIMIT 1
  ) ult ON TRUE;

DO $$
BEGIN
    IF (SELECT COUNT(*) FROM apchat_contato_view) <> (SELECT COUNT(*) FROM pessoa) THEN
        RAISE EXCEPTION 'apchat_contato_view deveria trazer todas as pessoas';
    END IF;
END $$;

COMMIT;
