-- =====================================================================================================================
-- Curciol no banco do APChat (tenant 21) - 03/11/2026. RODAR NO BANCO DO APCHAT (db postgres) como superusuario.
-- Pode rodar mais de uma vez. Senha do usuario: psql -v senha_curciol='...' (nunca escrever a senha neste arquivo).
--
-- O que faz (nada dentro do schema public e alterado, alem de trocar a trigger da Curciol em "Contacts"):
--   1. remove a trigger antiga trg_curciol_sincroniza_contato (dblink para o IP interno da Curciol: travava ~2 min cada
--      edicao de nome/numero/e-mail do tenant 21);
--   2. schema curciol com visoes SO do tenant 21 (o usuario da Curciol nao enxerga as tabelas nem outros tenants);
--   3. curciol.contato: espelho do que a Curciol mandou para cada contato do APChat (ligado = contato atual da Curciol;
--      ligado = false: ja foi da Curciol e ficou desligado -> travado);
--   4. trigger de protecao (antes de gravar nome/numero/e-mail de contato do tenant 21): contato ligado so aceita os
--      valores da Curciol, ex-contato fica travado, contato fora do espelho e livre. Nunca impede o APChat de gravar;
--   5. curciol.transferir_contato(antigo, novo): passa tudo do contato antigo para o novo (todas as tabelas com FK para
--      "Contacts", lidas do proprio banco, e o historico arquivado TicketsAntigo/MessagesAntigo) e so exclui o antigo
--      quando nada mais aponta para ele;
--   6. usuario curciol: le as visoes, grava o espelho e executa a transferencia. Nada mais.
-- =====================================================================================================================

-- 1) trigger antiga
DROP TRIGGER IF EXISTS trg_curciol_sincroniza_contato ON public."Contacts";
DROP FUNCTION IF EXISTS public.curciol_sincroniza_contato();

-- 2) schema e visoes do tenant 21
CREATE SCHEMA IF NOT EXISTS curciol;
REVOKE ALL ON SCHEMA curciol FROM PUBLIC;

CREATE OR REPLACE VIEW curciol.contatos AS
    SELECT id, name, number, email, cpf, "firstName", "lastName", "businessName", "birthdayDate", pushname, lid, "isLid",
           "isGroup", "createdAt", "updatedAt"
      FROM public."Contacts" WHERE "tenantId" = 21;

CREATE OR REPLACE VIEW curciol.tickets AS
    SELECT id, status, "lastMessage", "contactId", "userId", "queueId", "createdAt", "updatedAt", "lastMessageAt", "closedAt",
           false AS arquivado
      FROM public."Tickets" WHERE "tenantId" = 21
    UNION ALL
    SELECT id, status, "lastMessage", "contactId", "userId", "queueId", "createdAt", "updatedAt", "lastMessageAt", "closedAt",
           true
      FROM public."TicketsAntigo" WHERE "tenantId" = 21;

CREATE OR REPLACE VIEW curciol.mensagens AS
    SELECT id, body, "mediaType", "fileName", "fromMe", "isDeleted", "ticketId", "contactId", "userId", "createdAt", false AS arquivada
      FROM public."Messages" WHERE "tenantId" = 21
    UNION ALL
    SELECT id, body, "mediaType", NULL::text, "fromMe", "isDeleted", "ticketId", "contactId", "userId", "createdAt", true
      FROM public."MessagesAntigo" WHERE "tenantId" = 21;

CREATE OR REPLACE VIEW curciol.filas AS
    SELECT id, queue, "isActive" FROM public."Queues" WHERE "tenantId" = 21;

-- so id e nome (Users tem senha, tokens etc.)
CREATE OR REPLACE VIEW curciol.atendentes AS
    SELECT id, name FROM public."Users" WHERE "tenantId" = 21;

-- 3) espelho e log
CREATE TABLE IF NOT EXISTS curciol.contato (
    contato_id    integer PRIMARY KEY,                 -- "Contacts".id
    nome          varchar(255),
    numero        varchar(30),
    email         varchar(255),
    ligado        boolean NOT NULL DEFAULT true,       -- false: ex-contato da Curciol (travado)
    atualizado_em timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS curciol.log (
    id              bigserial PRIMARY KEY,
    quando          timestamptz NOT NULL DEFAULT now(),
    acao            varchar(30) NOT NULL,              -- EDICAO_BLOQUEADA, TRANSFERENCIA, TRANSFERENCIA_PARCIAL
    contato_id      integer,
    contato_novo_id integer,
    detalhe         text
);
CREATE INDEX IF NOT EXISTS ix_log_contato ON curciol.log (contato_id);

-- 04/11/2026: todos os campos que a Curciol manda (a trava de 4 compara com eles)
ALTER TABLE curciol.contato ADD COLUMN IF NOT EXISTS cpf text;
ALTER TABLE curciol.contato ADD COLUMN IF NOT EXISTS primeiro_nome text;
ALTER TABLE curciol.contato ADD COLUMN IF NOT EXISTS ultimo_nome text;
ALTER TABLE curciol.contato ADD COLUMN IF NOT EXISTS razao_social text;
ALTER TABLE curciol.contato ADD COLUMN IF NOT EXISTS nascimento text;

-- 4) protecao
-- 04/11/2026 (usuario): TODOS os campos do cadastro do contato protegidos, nao so nome, numero e e-mail
CREATE OR REPLACE FUNCTION curciol.proteger_contato()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = curciol, public, pg_temp
AS $fn$
DECLARE
    e         curciol.contato%ROWTYPE;
    v_campos  text[] := '{}';
BEGIN
    SELECT * INTO e FROM curciol.contato WHERE contato_id = OLD.id;
    IF NOT FOUND THEN
        RETURN NEW;                                    -- nunca passou pela Curciol: livre
    END IF;

    IF NEW.name IS DISTINCT FROM OLD.name
       AND (NOT e.ligado OR e.nome IS NULL OR btrim(NEW.name) IS DISTINCT FROM btrim(e.nome)) THEN
        NEW.name := OLD.name; v_campos := v_campos || 'nome'::text;
    END IF;
    IF NEW.number IS DISTINCT FROM OLD.number
       AND (NOT e.ligado OR e.numero IS NULL OR NEW.number IS DISTINCT FROM e.numero) THEN
        NEW.number := OLD.number; v_campos := v_campos || 'numero'::text;
    END IF;
    IF NEW.email IS DISTINCT FROM OLD.email
       AND (NOT e.ligado OR e.email IS NULL OR lower(btrim(NEW.email)) IS DISTINCT FROM lower(btrim(e.email))) THEN
        NEW.email := OLD.email; v_campos := v_campos || 'email'::text;
    END IF;
    IF NEW.cpf IS DISTINCT FROM OLD.cpf
       AND (NOT e.ligado OR e.cpf IS NULL OR regexp_replace(NEW.cpf, '\D', '', 'g') IS DISTINCT FROM e.cpf) THEN
        NEW.cpf := OLD.cpf; v_campos := v_campos || 'cpf'::text;
    END IF;
    IF NEW."firstName" IS DISTINCT FROM OLD."firstName"
       AND (NOT e.ligado OR e.primeiro_nome IS NULL OR btrim(NEW."firstName") IS DISTINCT FROM e.primeiro_nome) THEN
        NEW."firstName" := OLD."firstName"; v_campos := v_campos || 'primeiro_nome'::text;
    END IF;
    IF NEW."lastName" IS DISTINCT FROM OLD."lastName"
       AND (NOT e.ligado OR e.ultimo_nome IS NULL OR btrim(NEW."lastName") IS DISTINCT FROM e.ultimo_nome) THEN
        NEW."lastName" := OLD."lastName"; v_campos := v_campos || 'ultimo_nome'::text;
    END IF;
    IF NEW."businessName" IS DISTINCT FROM OLD."businessName"
       AND (NOT e.ligado OR e.razao_social IS NULL OR btrim(NEW."businessName") IS DISTINCT FROM e.razao_social) THEN
        NEW."businessName" := OLD."businessName"; v_campos := v_campos || 'razao_social'::text;
    END IF;
    IF NEW."birthdayDate" IS DISTINCT FROM OLD."birthdayDate"
       AND (NOT e.ligado OR e.nascimento IS NULL OR left(btrim(NEW."birthdayDate"), 10) IS DISTINCT FROM e.nascimento) THEN
        NEW."birthdayDate" := OLD."birthdayDate"; v_campos := v_campos || 'nascimento'::text;
    END IF;
    IF NEW.cidade IS DISTINCT FROM OLD.cidade THEN NEW.cidade := OLD.cidade; v_campos := v_campos || 'cidade'::text; END IF;
    IF NEW.estado IS DISTINCT FROM OLD.estado THEN NEW.estado := OLD.estado; v_campos := v_campos || 'estado'::text; END IF;
    IF NEW.cep IS DISTINCT FROM OLD.cep THEN NEW.cep := OLD.cep; v_campos := v_campos || 'cep'::text; END IF;

    IF array_length(v_campos, 1) > 0 THEN
        INSERT INTO curciol.log (acao, contato_id, detalhe) VALUES ('EDICAO_BLOQUEADA', OLD.id, 'campos: ' || array_to_string(v_campos, ', '));
    END IF;

    RETURN NEW;

EXCEPTION WHEN OTHERS THEN
    RAISE WARNING 'curciol.proteger_contato(%): %', OLD.id, SQLERRM;
    RETURN NEW;
END;
$fn$;

DROP TRIGGER IF EXISTS trg_curciol_proteger_contato ON public."Contacts";
CREATE TRIGGER trg_curciol_proteger_contato
BEFORE UPDATE OF "name", "number", "email", "cpf", "firstName", "lastName", "businessName", "birthdayDate", "cidade", "estado", "cep" ON public."Contacts"
FOR EACH ROW
WHEN (NEW."tenantId" = 21
      AND (OLD."name", OLD."number", OLD."email", OLD."cpf", OLD."firstName", OLD."lastName", OLD."businessName", OLD."birthdayDate", OLD."cidade", OLD."estado", OLD."cep")
          IS DISTINCT FROM
          (NEW."name", NEW."number", NEW."email", NEW."cpf", NEW."firstName", NEW."lastName", NEW."businessName", NEW."birthdayDate", NEW."cidade", NEW."estado", NEW."cep"))
EXECUTE FUNCTION curciol.proteger_contato();

-- 5) transferencia
CREATE OR REPLACE FUNCTION curciol.transferir_contato(p_antigo integer, p_novo integer)
RETURNS text
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $fn$
DECLARE
    v_ref    record;
    v_n      bigint;
    v_resta  bigint := 0;
    v_resumo text := '';
    v_onde   text := '';
BEGIN
    IF p_antigo IS NULL OR p_novo IS NULL OR p_antigo = p_novo THEN
        RAISE EXCEPTION 'transferir_contato: contatos invalidos (% -> %)', p_antigo, p_novo;
    END IF;
    PERFORM 1 FROM "Contacts" WHERE id = p_antigo AND "tenantId" = 21 FOR UPDATE;
    IF NOT FOUND THEN RAISE EXCEPTION 'transferir_contato: contato antigo % nao existe no tenant 21', p_antigo; END IF;
    PERFORM 1 FROM "Contacts" WHERE id = p_novo AND "tenantId" = 21 FOR UPDATE;
    IF NOT FOUND THEN RAISE EXCEPTION 'transferir_contato: contato novo % nao existe no tenant 21', p_novo; END IF;

    -- ligacoes que nao podem repetir: o que o novo ja tem fica so uma vez
    DELETE FROM "ContactTags" a WHERE a."contactId" = p_antigo
       AND EXISTS (SELECT 1 FROM "ContactTags" b WHERE b."contactId" = p_novo AND b."tagId" = a."tagId" AND b."tenantId" = a."tenantId");
    DELETE FROM "ContactWallets" a WHERE a."contactId" = p_antigo
       AND EXISTS (SELECT 1 FROM "ContactWallets" b WHERE b."contactId" = p_novo AND b."walletId" = a."walletId" AND b."tenantId" = a."tenantId");
    DELETE FROM "ContactCustomFields" a WHERE a."contactId" = p_antigo
       AND EXISTS (SELECT 1 FROM "ContactCustomFields" b WHERE b."contactId" = p_novo AND b.name = a.name);

    -- tudo que aponta para "Contacts" (lido do banco: tabela nova do APChat entra sozinha) + historico arquivado
    FOR v_ref IN
        SELECT c.conrelid::regclass::text AS tabela, a.attname AS coluna
          FROM pg_constraint c
          JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
         WHERE c.contype = 'f' AND c.confrelid = 'public."Contacts"'::regclass
        UNION
        SELECT 'public."TicketsAntigo"', 'contactId'
        UNION
        SELECT 'public."MessagesAntigo"', 'contactId'
    LOOP
        EXECUTE format('UPDATE %s SET %I = $1 WHERE %I = $2', v_ref.tabela, v_ref.coluna, v_ref.coluna) USING p_novo, p_antigo;
        GET DIAGNOSTICS v_n = ROW_COUNT;
        IF v_n > 0 THEN v_resumo := v_resumo || format('%s=%s ', v_ref.tabela, v_n); END IF;
    END LOOP;

    -- confere: nada pode continuar apontando para o antigo (a exclusao apaga em cascata)
    FOR v_ref IN
        SELECT c.conrelid::regclass::text AS tabela, a.attname AS coluna
          FROM pg_constraint c
          JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey)
         WHERE c.contype = 'f' AND c.confrelid = 'public."Contacts"'::regclass
        UNION
        SELECT 'public."TicketsAntigo"', 'contactId'
        UNION
        SELECT 'public."MessagesAntigo"', 'contactId'
    LOOP
        EXECUTE format('SELECT count(*) FROM %s WHERE %I = $1', v_ref.tabela, v_ref.coluna) INTO v_n USING p_antigo;
        IF v_n > 0 THEN v_resta := v_resta + v_n; v_onde := v_onde || format('%s=%s ', v_ref.tabela, v_n); END IF;
    END LOOP;

    IF v_resta = 0 THEN
        -- sai do espelho ANTES: a protecao de exclusao (trg_curciol_proteger_exclusao) barra contato que esta nele
        DELETE FROM curciol.contato WHERE contato_id = p_antigo;
        DELETE FROM "Contacts" WHERE id = p_antigo;
        v_resumo := btrim(v_resumo || 'antigo excluido');
        INSERT INTO curciol.log (acao, contato_id, contato_novo_id, detalhe) VALUES ('TRANSFERENCIA', p_antigo, p_novo, v_resumo);
    ELSE
        INSERT INTO curciol.contato AS c (contato_id, ligado) VALUES (p_antigo, false)
            ON CONFLICT (contato_id) DO UPDATE SET ligado = false, atualizado_em = now();
        v_resumo := btrim(v_resumo || 'antigo NAO excluido, ainda referenciado em: ' || v_onde);
        INSERT INTO curciol.log (acao, contato_id, contato_novo_id, detalhe) VALUES ('TRANSFERENCIA_PARCIAL', p_antigo, p_novo, v_resumo);
    END IF;

    RETURN v_resumo;
END
$fn$;

-- 4b) protecao contra exclusao (03/11/2026, pedido do usuario): contato que esta no espelho (da Curciol, ligado ou
--     ex-contato travado) nao e excluido pelo APChat - o DELETE nao acontece (nada sai em cascata: tickets, mensagens)
--     e fica no curciol.log. Quem precisa excluir (limpeza do tenant, transferencia) tira do espelho antes.
CREATE OR REPLACE FUNCTION curciol.proteger_exclusao()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = curciol, public, pg_temp
AS $fn$
BEGIN
    IF EXISTS (SELECT 1 FROM curciol.contato WHERE contato_id = OLD.id) THEN
        BEGIN
            INSERT INTO curciol.log (acao, contato_id, detalhe) VALUES ('EXCLUSAO_BLOQUEADA', OLD.id, concat_ws(' | ', OLD.name, OLD.number, OLD.email));
        EXCEPTION WHEN OTHERS THEN
            RAISE WARNING 'curciol.proteger_exclusao(%): log nao gravado: %', OLD.id, SQLERRM;
        END;
        RETURN NULL;                                   -- contato da Curciol: a exclusao nao acontece
    END IF;
    RETURN OLD;
END
$fn$;

DROP TRIGGER IF EXISTS trg_curciol_proteger_exclusao ON public."Contacts";
CREATE TRIGGER trg_curciol_proteger_exclusao
BEFORE DELETE ON public."Contacts"
FOR EACH ROW
WHEN (OLD."tenantId" = 21)
EXECUTE FUNCTION curciol.proteger_exclusao();

REVOKE ALL ON FUNCTION curciol.proteger_exclusao() FROM PUBLIC;
REVOKE ALL ON FUNCTION curciol.proteger_contato() FROM PUBLIC;
REVOKE ALL ON FUNCTION curciol.transferir_contato(integer, integer) FROM PUBLIC;

-- 6) usuario da Curciol
SELECT format('CREATE ROLE curciol LOGIN PASSWORD %L CONNECTION LIMIT 20', :'senha_curciol')
 WHERE NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = 'curciol') \gexec
SELECT format('ALTER ROLE curciol PASSWORD %L', :'senha_curciol') \gexec
ALTER ROLE curciol SET statement_timeout = '30s';
ALTER ROLE curciol SET search_path = curciol;

GRANT USAGE ON SCHEMA curciol TO curciol;
GRANT SELECT ON curciol.contatos, curciol.tickets, curciol.mensagens, curciol.filas, curciol.atendentes TO curciol;
GRANT SELECT, INSERT, UPDATE, DELETE ON curciol.contato TO curciol;
GRANT SELECT ON curciol.log TO curciol;
GRANT EXECUTE ON FUNCTION curciol.transferir_contato(integer, integer) TO curciol;
