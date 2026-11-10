-- =====================================================================================================================
-- Curciol no banco do APChat (tenant 21) - 10/11/2026. Depois de 2026-11-03-schema-curciol.sql. Pode rodar mais de uma vez.
-- RODAR PRIMEIRO NO APCHAT DE TESTE (apchat_homologacao). No APChat oficial so com autorizacao, depois de ensaio numa transacao
-- desfeita (BEGIN; \i este arquivo; testes; ROLLBACK;). Nao altera nem exclui nada do APChat: so cria/troca objetos do schema
-- curciol e duas triggers em "Contacts".
--
--  1. visoes ampliadas (so colunas a mais no fim; nada sai): tickets (+ canal, quem fechou), mensagens (+ tiques, situacao, id,
--     fila), atendentes (+ e-mail, inativo) e as novas canais, filas_atendentes e canais_atendentes;
--  2. contato novo (BEFORE/AFTER INSERT em "Contacts", so tenant 21): se o numero - ou o mesmo numero com/sem o 9 - for de um
--     contato ligado da Curciol (espelho), o contato novo ja nasce com os dados da Curciol, entra no espelho (trancado pelas
--     travas que ja existem) e fica no log como NOVO_LIGADO; senao nasce livre e fica no log como NOVO_SEM_VINCULO.
--     Qualquer erro vira WARNING: o APChat grava do mesmo jeito.
-- =====================================================================================================================

-- 1) visoes -----------------------------------------------------------------------------------------------------------
CREATE OR REPLACE VIEW curciol.tickets AS
    SELECT id, status, "lastMessage", "contactId", "userId", "queueId", "createdAt", "updatedAt", "lastMessageAt", "closedAt",
           false AS arquivado, "whatsappId", "closedByUserId"
      FROM public."Tickets" WHERE "tenantId" = 21
    UNION ALL
    SELECT id, status, "lastMessage", "contactId", "userId", "queueId", "createdAt", "updatedAt", "lastMessageAt", "closedAt",
           true, "whatsappId", NULL::integer
      FROM public."TicketsAntigo" WHERE "tenantId" = 21;

CREATE OR REPLACE VIEW curciol.mensagens AS
    SELECT id, body, "mediaType", "fileName", "fromMe", "isDeleted", "ticketId", "contactId", "userId", "createdAt", false AS arquivada,
           ack, status, "messageId", "queueId"
      FROM public."Messages" WHERE "tenantId" = 21
    UNION ALL
    SELECT id, body, "mediaType", NULL::text, "fromMe", "isDeleted", "ticketId", "contactId", "userId", "createdAt", true,
           ack, status, "messageId", "queueId"
      FROM public."MessagesAntigo" WHERE "tenantId" = 21;

-- so id, nome, e-mail e inativo (Users tem senha, tokens etc.)
CREATE OR REPLACE VIEW curciol.atendentes AS
    SELECT id, name, email, inactive FROM public."Users" WHERE "tenantId" = 21;

CREATE OR REPLACE VIEW curciol.canais AS
    SELECT id, name, type, "isDeleted" FROM public."Whatsapps" WHERE "tenantId" = 21;

CREATE OR REPLACE VIEW curciol.filas_atendentes AS
    SELECT uq."userId", uq."queueId"
      FROM public."UsersQueues" uq JOIN public."Users" u ON u.id = uq."userId" AND u."tenantId" = 21;

CREATE OR REPLACE VIEW curciol.canais_atendentes AS
    SELECT uw."userId", uw."whatsappId", uw."isActive"
      FROM public."UserWhatsapps" uw JOIN public."Users" u ON u.id = uw."userId" AND u."tenantId" = 21;

-- 2) contato novo -----------------------------------------------------------------------------------------------------
-- o mesmo numero com e sem o nono digito (Brasil: 55 + DDD + 8 ou 9 digitos)
CREATE OR REPLACE FUNCTION curciol.variantes_numero(p_numero text) RETURNS text[]
    LANGUAGE sql IMMUTABLE
AS $$
    SELECT CASE
             WHEN n ~ '^55[0-9]{2}9[0-9]{8}$' THEN ARRAY[n, substr(n, 1, 4) || substr(n, 6)]
             WHEN n ~ '^55[0-9]{2}[0-9]{8}$'  THEN ARRAY[n, substr(n, 1, 4) || '9' || substr(n, 5)]
             ELSE ARRAY[n]
           END
      FROM (SELECT regexp_replace(coalesce(p_numero, ''), '\D', '', 'g') AS n) x
$$;

-- contato ligado do espelho com o numero (o igual antes da variante)
CREATE OR REPLACE FUNCTION curciol.espelho_do_numero(p_numero text) RETURNS curciol.contato
    LANGUAGE sql STABLE SECURITY DEFINER SET search_path = curciol, public, pg_temp
AS $$
    SELECT e.* FROM curciol.contato e
     WHERE e.ligado AND e.numero = ANY (curciol.variantes_numero(p_numero))
     ORDER BY (e.numero = regexp_replace(coalesce(p_numero, ''), '\D', '', 'g')) DESC, e.atualizado_em DESC
     LIMIT 1
$$;

CREATE OR REPLACE FUNCTION curciol.contato_novo() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER SET search_path = curciol, public, pg_temp
AS $$
DECLARE
    e record;
BEGIN
    IF coalesce(NEW.number, '') = '' THEN
        RETURN NEW;
    END IF;
    SELECT * INTO e FROM curciol.espelho_do_numero(NEW.number);
    IF FOUND AND e.contato_id IS NOT NULL THEN
        NEW.name           := coalesce(e.nome, NEW.name);
        NEW.email          := coalesce(e.email, NEW.email);
        NEW.cpf            := coalesce(e.cpf, NEW.cpf);
        NEW."firstName"    := coalesce(e.primeiro_nome, NEW."firstName");
        NEW."lastName"     := coalesce(e.ultimo_nome, NEW."lastName");
        NEW."businessName" := coalesce(e.razao_social, NEW."businessName");
        NEW."birthdayDate" := coalesce(e.nascimento, NEW."birthdayDate");
    END IF;
    RETURN NEW;
EXCEPTION WHEN OTHERS THEN
    RAISE WARNING 'curciol.contato_novo(%): %', NEW.number, SQLERRM;
    RETURN NEW;
END;
$$;

-- depois de gravar (o id so existe agora): entra no espelho com o proprio numero, trancado pelas travas de sempre
CREATE OR REPLACE FUNCTION curciol.contato_novo_espelho() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER SET search_path = curciol, public, pg_temp
AS $$
DECLARE
    e record;
BEGIN
    IF coalesce(NEW.number, '') = '' THEN
        RETURN NULL;
    END IF;
    SELECT * INTO e FROM curciol.espelho_do_numero(NEW.number);
    IF FOUND AND e.contato_id IS NOT NULL AND e.contato_id <> NEW.id THEN
        INSERT INTO curciol.contato (contato_id, nome, numero, email, ligado, cpf, primeiro_nome, ultimo_nome, razao_social, nascimento)
        VALUES (NEW.id, NEW.name, NEW.number, NEW.email, true, e.cpf, e.primeiro_nome, e.ultimo_nome, e.razao_social, e.nascimento)
        ON CONFLICT (contato_id) DO NOTHING;
        INSERT INTO curciol.log (acao, contato_id, contato_novo_id, detalhe)
        VALUES ('NOVO_LIGADO', NEW.id, e.contato_id, concat_ws(' | ', NEW.number, e.numero));
    ELSE
        INSERT INTO curciol.log (acao, contato_id, detalhe) VALUES ('NOVO_SEM_VINCULO', NEW.id, concat_ws(' | ', NEW.number, NEW.name));
    END IF;
    RETURN NULL;
EXCEPTION WHEN OTHERS THEN
    RAISE WARNING 'curciol.contato_novo_espelho(%): %', NEW.id, SQLERRM;
    RETURN NULL;
END;
$$;

DROP TRIGGER IF EXISTS trg_curciol_contato_novo ON public."Contacts";
CREATE TRIGGER trg_curciol_contato_novo
    BEFORE INSERT ON public."Contacts"
    FOR EACH ROW WHEN (NEW."tenantId" = 21)
    EXECUTE FUNCTION curciol.contato_novo();

DROP TRIGGER IF EXISTS trg_curciol_contato_novo_espelho ON public."Contacts";
CREATE TRIGGER trg_curciol_contato_novo_espelho
    AFTER INSERT ON public."Contacts"
    FOR EACH ROW WHEN (NEW."tenantId" = 21)
    EXECUTE FUNCTION curciol.contato_novo_espelho();

-- 3) usuario da Curciol: so le as visoes novas ---------------------------------------------------------------------------
GRANT SELECT ON curciol.tickets, curciol.mensagens, curciol.atendentes, curciol.canais, curciol.filas_atendentes, curciol.canais_atendentes TO curciol;
