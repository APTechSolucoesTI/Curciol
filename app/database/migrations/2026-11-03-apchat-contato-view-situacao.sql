-- Integracao Contatos APChat (03/11/2026): situacao de cada pessoa pelo que esta de fato ligado ao APChat.
-- Desde 27/10 todo telefone valido da aba Contatos vai para o APChat (um contato por numero, id em
-- pessoa_contato.idapchat), mas a visao ainda olhava so o Telefone principal dos Dados e a ultima linha do log:
-- quem tinha telefone so na aba Contatos aparecia como "Nao sincronizado" e, depois de limpar o APChat, continuava
-- "Sincronizado" quem nao estava mais la. Agora:
--   - Nao sincronizado (IGNORADO): nenhum contato com telefone valido;
--   - Sincronizado (SUCESSO): todos os numeros validos da pessoa tem o id do APChat;
--   - Erro (ERRO): falta numero e ha envio pendente na fila (refeito sozinho); erro antigo sem fila nao conta;
--   - Nunca sincronizado (NULL): falta enviar (ou o contato do APChat foi apagado e o id zerado).
-- Mesmas colunas de antes (ApchatContatoView). Pode rodar mais de uma vez.
BEGIN;

DROP VIEW IF EXISTS apchat_contato_view;
CREATE VIEW apchat_contato_view AS
WITH contatos AS (
    SELECT c.pessoa_id, c.idapchat, c.principal, '55' || x.d AS numero
      FROM pessoa_contato c
     CROSS JOIN LATERAL (SELECT ltrim(regexp_replace(COALESCE(c.telefone, ''), '\D', '', 'g'), '0') AS d0) z
     CROSS JOIN LATERAL (SELECT CASE WHEN length(z.d0) IN (12, 13) AND left(z.d0, 2) = '55' THEN substr(z.d0, 3) ELSE z.d0 END AS d) x
     -- telefone valido = a regra do envio (APChatContactService::normalizarTelefone)
     WHERE length(x.d) IN (10, 11) AND x.d ~ '^[1-9]{2}' AND (length(x.d) = 10 OR substr(x.d, 3, 1) = '9')
), resumo AS (
    SELECT pessoa_id,
           count(DISTINCT numero) AS numeros,
           count(DISTINCT numero) FILTER (WHERE idapchat IS NOT NULL) AS com_id,
           (array_agg(numero ORDER BY (principal = 'S') DESC, numero))[1] AS numero,
           (array_agg(idapchat ORDER BY (principal = 'S') DESC, numero))[1] AS idapchat,
           array_agg(DISTINCT numero) AS lista
      FROM contatos
     GROUP BY pessoa_id
)
SELECT p.id,
       p.nome,
       p.tipo_pessoa_id,
       p.cpf_cnpj,
       p.email,
       p.telefone,
       ult.data_criacao AS ultima_sincronizacao,
       ult.operacao     AS ultima_operacao,
       CASE WHEN r.numeros IS NULL THEN 'IGNORADO'
            WHEN r.com_id = r.numeros THEN 'SUCESSO'
            WHEN fila.erro IS NOT NULL THEN 'ERRO'
            ELSE NULL END AS situacao,
       CASE WHEN r.numeros IS NULL AND NOT tt.tem_telefone THEN 'Telefone vazio.'
            WHEN r.numeros IS NULL THEN 'Telefone inválido.'
            WHEN r.com_id = r.numeros THEN COALESCE(ult.mensagem, 'Contatos ligados ao APChat.')
            WHEN fila.erro IS NOT NULL THEN 'Na fila (refeito sozinho): ' || fila.erro
            WHEN r.com_id > 0 THEN r.com_id || ' de ' || r.numeros || ' números ligados ao APChat; falta enviar o resto.'
            ELSE 'Ainda não enviado ao APChat.' END AS mensagem,
       r.numero   AS numero_apchat,
       r.idapchat AS apchat_contato_id,
       (r.numeros IS NOT NULL) AS telefone_valido
  FROM pessoa p
  LEFT JOIN resumo r ON r.pessoa_id = p.id
  -- algum telefone preenchido (Dados ou aba Contatos), para a mensagem de quem nao tem telefone valido
  CROSS JOIN LATERAL (
        SELECT regexp_replace(COALESCE(p.telefone, ''), '\D', '', 'g') <> ''
            OR EXISTS (SELECT 1 FROM pessoa_contato pc WHERE pc.pessoa_id = p.id AND regexp_replace(COALESCE(pc.telefone, ''), '\D', '', 'g') <> '') AS tem_telefone
  ) tt
  LEFT JOIN LATERAL (
        SELECT COALESCE(f.ultimo_erro, '') AS erro
          FROM apchat_fila f
         WHERE r.lista IS NOT NULL AND (f.pessoa_id = p.id OR f.numero = ANY (r.lista))
         ORDER BY f.atualizado_em DESC
         LIMIT 1
  ) fila ON TRUE
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
