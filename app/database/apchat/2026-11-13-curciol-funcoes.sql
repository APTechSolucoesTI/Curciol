-- =====================================================================================================================
-- Curciol no banco do APChat - 13/11/2026: LEITURAS POR FUNCAO (nada da Curciol trava atualizacao do APChat).
--
--   psql -v tenant=21 -v papel=curciol -v travas=1 -v ON_ERROR_STOP=1 --single-transaction -f 2026-11-13-curciol-funcoes.sql
--     tenant : tenant da Curciol (oficial e APChat de teste = 21; app1 = 1)
--     papel  : usuario do banco que a Curciol usa (oficial = curciol; app1 = curciol_homolog; teste = curciol_apchat_teste)
--     travas : 1 = reinstala as travas do contato (protecao, exclusao, contato novo) sem lista de colunas (oficial e teste;
--              exige 2026-11-03 e 2026-11-10 antes); 0 = nao mexe em trigger (app1, que so le)
--
-- Por que (testado em 13/11/2026): uma VISAO sobre tabela do APChat, ou uma trigger com "UPDATE OF coluna"/"WHEN (...)",
-- prende a coluna no Postgres. Quando a atualizacao do APChat altera ou remove a coluna (changeColumn, removeColumn,
-- dropTable), a migracao do APChat PARA com erro ("cannot alter type of a column used by a view or rule"). Corpo de
-- funcao plpgsql nao prende nada: a migracao sempre passa e, se uma coluna sumir, so a leitura da Curciol falha (coluna
-- lida vira NULL e curciol.conferir() acusa).
--
-- O que faz (nada no schema public muda; as triggers da Curciol em "Contacts" so perdem a lista de colunas):
--   1. curciol.tenant(), curciol._defs()/_sel(): tenant e colunas lidas (coluna que nao existe vira NULL);
--   2. funcoes de leitura f_* (SECURITY DEFINER, so o tenant da Curciol, com filtro por parametro para usar os indices);
--   3. as visoes de antes (mesmos nomes e colunas) passam a ler das funcoes: o codigo antigo continua funcionando;
--   4. curciol.dependencias() e curciol.conferir(): a conferencia depois de cada atualizacao do APChat;
--   5. (travas=1) protecao do contato, exclusao e contato novo sem "UPDATE OF"/"WHEN";
--   6. o papel da Curciol: EXECUTE nas funcoes e SELECT nas visoes. Funcoes fechadas para PUBLIC.
-- Pode rodar mais de uma vez. Desfazer: rodar de novo os arquivos de 03/11 e 10/11 (visoes antigas) - nao recomendado.
-- =====================================================================================================================
\if :{?tenant}
\else
    \echo 'informe -v tenant=N -v papel=NOME -v travas=0|1'
    \quit
\endif

CREATE SCHEMA IF NOT EXISTS curciol;
REVOKE ALL ON SCHEMA curciol FROM PUBLIC;

-- 1) tenant e colunas ------------------------------------------------------------------------------------------------
SELECT format('CREATE OR REPLACE FUNCTION curciol.tenant() RETURNS integer LANGUAGE sql IMMUTABLE AS %L', 'SELECT ' || (:tenant)::integer) \gexec

-- colunas que a Curciol le de cada tabela do APChat ("coluna tipo"), na ordem em que as funcoes devolvem
CREATE OR REPLACE FUNCTION curciol._defs(p_tab text) RETURNS text[]
    LANGUAGE sql IMMUTABLE
AS $$
    SELECT CASE p_tab
        WHEN 'Contacts'   THEN ARRAY['id integer', 'name varchar', 'number varchar', 'email varchar', 'cpf varchar', 'firstName varchar',
                                     'lastName varchar', 'businessName varchar', 'birthdayDate varchar', 'pushname varchar', 'lid varchar',
                                     'isLid boolean', 'isGroup boolean', 'createdAt timestamptz', 'updatedAt timestamptz']
        WHEN 'Tickets'    THEN ARRAY['id integer', 'status varchar', 'lastMessage text', 'contactId integer', 'userId integer', 'queueId integer',
                                     'createdAt timestamptz', 'updatedAt timestamptz', 'lastMessageAt bigint', 'closedAt bigint']
        WHEN 'Tickets+'   THEN ARRAY['whatsappId integer', 'closedByUserId integer', 'isGroup boolean', 'shared boolean', 'userIdArray jsonb',
                                     'groupUserIdArray jsonb', 'chatFlowId integer']
        WHEN 'Messages'   THEN ARRAY['id varchar', 'body text', 'mediaType varchar', 'fileName text', 'fromMe boolean', 'isDeleted boolean',
                                     'ticketId integer', 'contactId integer', 'userId integer', 'createdAt timestamptz']
        WHEN 'Messages+'  THEN ARRAY['ack integer', 'status varchar', 'messageId varchar', 'queueId integer']
        WHEN 'Users'      THEN ARRAY['id integer', 'name varchar', 'email varchar', 'inactive boolean', 'profile varchar', 'configs jsonb', 'customProfileId integer']
        WHEN 'Queues'     THEN ARRAY['id integer', 'queue varchar', 'isActive boolean']
        WHEN 'Whatsapps'  THEN ARRAY['id integer', 'name varchar', 'type varchar', 'isDeleted boolean']
        WHEN 'UsersQueues'   THEN ARRAY['userId integer', 'queueId integer']
        WHEN 'UserWhatsapps' THEN ARRAY['userId integer', 'whatsappId integer', 'isActive boolean']
        WHEN 'ContactWallets' THEN ARRAY['contactId integer', 'walletId integer']
    END
$$;

-- lista do SELECT ("x.coluna::tipo" ou "NULL::tipo" quando a coluna nao existe na tabela)
CREATE OR REPLACE FUNCTION curciol._sel(p_tab text, p_defs text[]) RETURNS text
    LANGUAGE sql STABLE SET search_path = pg_catalog, pg_temp
AS $$
    SELECT string_agg(CASE WHEN a.attname IS NOT NULL THEN format('x.%I::%s AS %I', d.col, d.tipo, d.col) ELSE format('NULL::%s AS %I', d.tipo, d.col) END, ', ' ORDER BY d.ord)
      FROM unnest(p_defs) WITH ORDINALITY u(def, ord)
      CROSS JOIN LATERAL (SELECT split_part(u.def, ' ', 1) AS col, substr(u.def, length(split_part(u.def, ' ', 1)) + 2) AS tipo, u.ord) d
      LEFT JOIN pg_attribute a ON a.attrelid = to_regclass(format('public.%I', p_tab)) AND a.attname = d.col AND a.attnum > 0 AND NOT a.attisdropped
$$;

-- 2) leituras ---------------------------------------------------------------------------------------------------------
-- contatos: por ids, por numeros ou (sem filtro) todos os do tenant
CREATE OR REPLACE FUNCTION curciol.f_contatos(p_ids integer[] DEFAULT NULL, p_numeros varchar[] DEFAULT NULL)
RETURNS TABLE (id integer, name varchar, number varchar, email varchar, cpf varchar, "firstName" varchar, "lastName" varchar,
               "businessName" varchar, "birthdayDate" varchar, pushname varchar, lid varchar, "isLid" boolean, "isGroup" boolean,
               "createdAt" timestamptz, "updatedAt" timestamptz)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 200
AS $$
DECLARE
    v_onde text := '';
BEGIN
    IF p_ids IS NOT NULL THEN v_onde := v_onde || ' AND x.id = ANY ($1)'; END IF;
    IF p_numeros IS NOT NULL THEN v_onde := v_onde || ' AND x.number = ANY ($2)'; END IF;
    RETURN QUERY EXECUTE format('SELECT %s FROM public."Contacts" x WHERE x."tenantId" = %s%s',
                                curciol._sel('Contacts', curciol._defs('Contacts')), curciol.tenant(), v_onde)
                 USING p_ids, p_numeros;
END
$$;

-- tickets (+ arquivados em TicketsAntigo, se existir): por ids, por contatos, so os abertos/pendentes, ou todos
CREATE OR REPLACE FUNCTION curciol.f_tickets(p_ids integer[] DEFAULT NULL, p_contatos integer[] DEFAULT NULL, p_abertos boolean DEFAULT false)
RETURNS TABLE (id integer, status varchar, "lastMessage" text, "contactId" integer, "userId" integer, "queueId" integer,
               "createdAt" timestamptz, "updatedAt" timestamptz, "lastMessageAt" bigint, "closedAt" bigint, arquivado boolean,
               "whatsappId" integer, "closedByUserId" integer, "isGroup" boolean, shared boolean, "userIdArray" jsonb,
               "groupUserIdArray" jsonb, "chatFlowId" integer)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 200
AS $$
DECLARE
    v_onde text := '';
    v_tab  text;
BEGIN
    IF p_ids IS NOT NULL THEN v_onde := v_onde || ' AND x.id = ANY ($1)'; END IF;
    IF p_contatos IS NOT NULL THEN v_onde := v_onde || ' AND x."contactId" = ANY ($2)'; END IF;
    IF coalesce(p_abertos, false) THEN v_onde := v_onde || ' AND x.status IN (''pending'', ''open'')'; END IF;
    FOREACH v_tab IN ARRAY ARRAY['Tickets', 'TicketsAntigo'] LOOP
        CONTINUE WHEN to_regclass(format('public.%I', v_tab)) IS NULL OR (v_tab = 'TicketsAntigo' AND coalesce(p_abertos, false));
        RETURN QUERY EXECUTE format('SELECT %s, %L::boolean, %s FROM public.%I x WHERE x."tenantId" = %s%s',
                                    curciol._sel(v_tab, curciol._defs('Tickets')), v_tab = 'TicketsAntigo',
                                    curciol._sel(v_tab, curciol._defs('Tickets+')), v_tab, curciol.tenant(), v_onde)
                     USING p_ids, p_contatos;
    END LOOP;
END
$$;

-- mensagens (+ MessagesAntigo): por tickets e/ou contatos, com intervalo opcional. Sem filtro nenhum = todas (so a visao antiga usa)
CREATE OR REPLACE FUNCTION curciol.f_mensagens(p_tickets integer[] DEFAULT NULL, p_contatos integer[] DEFAULT NULL,
                                               p_desde timestamptz DEFAULT NULL, p_ate timestamptz DEFAULT NULL)
RETURNS TABLE (id varchar, body text, "mediaType" varchar, "fileName" text, "fromMe" boolean, "isDeleted" boolean, "ticketId" integer,
               "contactId" integer, "userId" integer, "createdAt" timestamptz, arquivada boolean, ack integer, status varchar,
               "messageId" varchar, "queueId" integer)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 1000
AS $$
DECLARE
    v_onde text := '';
    v_tab  text;
BEGIN
    IF p_tickets IS NOT NULL THEN v_onde := v_onde || ' AND x."ticketId" = ANY ($1)'; END IF;
    IF p_contatos IS NOT NULL THEN v_onde := v_onde || ' AND x."contactId" = ANY ($2)'; END IF;
    IF p_desde IS NOT NULL THEN v_onde := v_onde || ' AND x."createdAt" >= $3'; END IF;
    IF p_ate IS NOT NULL THEN v_onde := v_onde || ' AND x."createdAt" <= $4'; END IF;
    FOREACH v_tab IN ARRAY ARRAY['Messages', 'MessagesAntigo'] LOOP
        CONTINUE WHEN to_regclass(format('public.%I', v_tab)) IS NULL;
        RETURN QUERY EXECUTE format('SELECT %s, %L::boolean, %s FROM public.%I x WHERE x."tenantId" = %s%s',
                                    curciol._sel(v_tab, curciol._defs('Messages')), v_tab = 'MessagesAntigo',
                                    curciol._sel(v_tab, curciol._defs('Messages+')), v_tab, curciol.tenant(), v_onde)
                     USING p_tickets, p_contatos, p_desde, p_ate;
    END LOOP;
END
$$;

CREATE OR REPLACE FUNCTION curciol.f_filas()
RETURNS TABLE (id integer, queue varchar, "isActive" boolean)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 50
AS $$
BEGIN
    RETURN QUERY EXECUTE format('SELECT %s FROM public."Queues" x WHERE x."tenantId" = %s', curciol._sel('Queues', curciol._defs('Queues')), curciol.tenant());
END
$$;

CREATE OR REPLACE FUNCTION curciol.f_canais()
RETURNS TABLE (id integer, name varchar, type varchar, "isDeleted" boolean)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 20
AS $$
BEGIN
    RETURN QUERY EXECUTE format('SELECT %s FROM public."Whatsapps" x WHERE x."tenantId" = %s', curciol._sel('Whatsapps', curciol._defs('Whatsapps')), curciol.tenant());
END
$$;

-- atendentes (so id, nome, e-mail, inativo, perfil e configuracoes: Users tem senha e tokens) + "ver todos os atendimentos"
-- do perfil personalizado (CustomProfiles.customPermissions: lista de permissoes ou objeto {permissao: true})
CREATE OR REPLACE FUNCTION curciol.f_atendentes()
RETURNS TABLE (id integer, name varchar, email varchar, inactive boolean, profile varchar, configs jsonb, "customProfileId" integer, ver_todos boolean)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 50
AS $$
DECLARE
    v_perm text := 'false';
BEGIN
    IF to_regclass('public."CustomProfiles"') IS NOT NULL
       AND EXISTS (SELECT 1 FROM pg_attribute WHERE attrelid = 'public."CustomProfiles"'::regclass AND attname = 'customPermissions' AND NOT attisdropped) THEN
        v_perm := 'coalesce((SELECT CASE jsonb_typeof(cp."customPermissions"::jsonb)
                                      WHEN ''array''  THEN cp."customPermissions"::jsonb ? ''tickets_view_all''
                                      WHEN ''object'' THEN coalesce(cp."customPermissions"::jsonb ->> ''tickets_view_all'', ''false'') IN (''true'', ''1'', ''enabled'')
                                      ELSE false END
                               FROM public."CustomProfiles" cp WHERE cp.id = u."customProfileId"), false)';
    END IF;
    RETURN QUERY EXECUTE format('SELECT u.*, %s FROM (SELECT %s FROM public."Users" x WHERE x."tenantId" = %s) u',
                                v_perm, curciol._sel('Users', curciol._defs('Users')), curciol.tenant());
END
$$;

CREATE OR REPLACE FUNCTION curciol.f_filas_atendentes()
RETURNS TABLE ("userId" integer, "queueId" integer)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 100
AS $$
BEGIN
    RETURN QUERY EXECUTE format('SELECT %s FROM public."UsersQueues" x JOIN public."Users" u ON u.id = x."userId" AND u."tenantId" = %s',
                                curciol._sel('UsersQueues', curciol._defs('UsersQueues')), curciol.tenant());
END
$$;

CREATE OR REPLACE FUNCTION curciol.f_canais_atendentes()
RETURNS TABLE ("userId" integer, "whatsappId" integer, "isActive" boolean)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 100
AS $$
BEGIN
    RETURN QUERY EXECUTE format('SELECT %s FROM public."UserWhatsapps" x JOIN public."Users" u ON u.id = x."userId" AND u."tenantId" = %s',
                                curciol._sel('UserWhatsapps', curciol._defs('UserWhatsapps')), curciol.tenant());
END
$$;

-- carteiras dos contatos (walletId = usuario dono da carteira)
CREATE OR REPLACE FUNCTION curciol.f_carteiras(p_contatos integer[])
RETURNS TABLE ("contactId" integer, "walletId" integer)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 50
AS $$
BEGIN
    IF to_regclass('public."ContactWallets"') IS NULL OR p_contatos IS NULL THEN RETURN; END IF;
    RETURN QUERY EXECUTE format('SELECT %s FROM public."ContactWallets" x WHERE x."tenantId" = %s AND x."contactId" = ANY ($1)',
                                curciol._sel('ContactWallets', curciol._defs('ContactWallets')), curciol.tenant())
                 USING p_contatos;
END
$$;

-- configuracoes da empresa que mudam quem ve o que: colunas do Tenants e chaves do Settings (texto como o APChat guarda)
CREATE OR REPLACE FUNCTION curciol.f_configuracoes() RETURNS jsonb
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp
AS $$
DECLARE
    v_t jsonb := '{}';
    v_s jsonb := '{}';
BEGIN
    IF to_regclass('public."Tenants"') IS NOT NULL THEN
        EXECUTE format('SELECT to_jsonb(x) FROM public."Tenants" x WHERE x.id = %s', curciol.tenant()) INTO v_t;
        SELECT coalesce(jsonb_object_agg(k, v_t -> k), '{}') INTO v_t
          FROM unnest(ARRAY['nullTickets', 'showClosedForAll', 'showGroupsForAll', 'supervisorAdmin', 'inboundByQueueOnly', 'supervisorChannelScoped']) k
         WHERE v_t ? k;
    END IF;
    IF to_regclass('public."Settings"') IS NOT NULL THEN
        EXECUTE format('SELECT coalesce(jsonb_object_agg(x.key, x.value), ''{}'') FROM public."Settings" x WHERE x."tenantId" = %s
                         AND x.key IN (''NotViewAssignedTickets'', ''NotViewTicketsChatBot'', ''DirectTicketsToWallets'')', curciol.tenant()) INTO v_s;
    END IF;
    RETURN coalesce(v_t, '{}') || coalesce(v_s, '{}');
END
$$;

-- chat interno: conversas com mensagem nao lida para cada usuario (direta: read = false para o destinatario; equipe: sem
-- linha em ReadPrivateMessageGroups para a participacao do usuario). Sem as tabelas (versao sem chat) = nada.
CREATE OR REPLACE FUNCTION curciol.f_chat_nao_lidas(p_usuarios integer[])
RETURNS TABLE (usuario integer, tipo text, conversa_id integer, nome text, qtd bigint, ultima text, ultima_tipo text, ultima_em timestamptz)
    LANGUAGE plpgsql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp ROWS 20
AS $$
BEGIN
    IF p_usuarios IS NULL OR to_regclass('public."PrivateMessage"') IS NULL THEN RETURN; END IF;
    RETURN QUERY EXECUTE format($q$
        SELECT d.usuario, 'usuario'::text, d.de, u.name::text, d.qtd, d.ultima, d.ultima_tipo, d.ultima_em
          FROM (SELECT pm."receiverId"::integer AS usuario, pm."senderId"::integer AS de, count(*) AS qtd,
                       (array_agg(pm.text::text ORDER BY pm."createdAt" DESC, pm.id DESC))[1] AS ultima,
                       (array_agg(pm."mediaType"::text ORDER BY pm."createdAt" DESC, pm.id DESC))[1] AS ultima_tipo,
                       max(pm."createdAt")::timestamptz AS ultima_em
                  FROM public."PrivateMessage" pm
                 WHERE pm."tenantId" = %1$s AND pm."receiverId" = ANY ($1) AND pm."groupId" IS NULL AND NOT coalesce(pm.read, false)
                 GROUP BY 1, 2) d
          LEFT JOIN public."Users" u ON u.id = d.de AND u."tenantId" = %1$s
    $q$, curciol.tenant()) USING p_usuarios;

    IF to_regclass('public."UsersPrivateGroups"') IS NULL OR to_regclass('public."GroupMessages"') IS NULL
       OR to_regclass('public."ReadPrivateMessageGroups"') IS NULL THEN
        RETURN;
    END IF;
    RETURN QUERY EXECUTE format($q$
        SELECT d.usuario, 'equipe'::text, d.grupo, g."group"::text, d.qtd, d.ultima, d.ultima_tipo, d.ultima_em
          FROM (SELECT ug."userId"::integer AS usuario, ug."groupId"::integer AS grupo, count(*) AS qtd,
                       (array_agg(pm.text::text ORDER BY pm."createdAt" DESC, pm.id DESC))[1] AS ultima,
                       (array_agg(pm."mediaType"::text ORDER BY pm."createdAt" DESC, pm.id DESC))[1] AS ultima_tipo,
                       max(pm."createdAt")::timestamptz AS ultima_em
                  FROM public."UsersPrivateGroups" ug
                  JOIN public."PrivateMessage" pm ON pm."groupId" = ug."groupId" AND pm."tenantId" = %1$s
                       AND pm."senderId" IS DISTINCT FROM ug."userId"
                 WHERE ug."userId" = ANY ($1)
                   AND NOT EXISTS (SELECT 1 FROM public."ReadPrivateMessageGroups" r WHERE r."userGroupId" = ug.id AND r."internalMessageId" = pm.id)
                 GROUP BY 1, 2) d
          JOIN public."GroupMessages" g ON g.id = d.grupo AND g."tenantId" = %1$s AND coalesce(g."isActive", true)
    $q$, curciol.tenant()) USING p_usuarios;
END
$$;

-- 3) visoes de antes, agora lendo das funcoes (mesmos nomes e colunas; nada prende coluna do APChat) ---------------------
DROP VIEW IF EXISTS curciol.contatos, curciol.tickets, curciol.mensagens, curciol.filas, curciol.atendentes, curciol.canais,
                    curciol.filas_atendentes, curciol.canais_atendentes;
CREATE VIEW curciol.contatos AS SELECT * FROM curciol.f_contatos();
CREATE VIEW curciol.tickets AS
    SELECT id, status, "lastMessage", "contactId", "userId", "queueId", "createdAt", "updatedAt", "lastMessageAt", "closedAt",
           arquivado, "whatsappId", "closedByUserId"
      FROM curciol.f_tickets();
CREATE VIEW curciol.mensagens AS SELECT * FROM curciol.f_mensagens();
CREATE VIEW curciol.filas AS SELECT * FROM curciol.f_filas();
CREATE VIEW curciol.atendentes AS SELECT id, name, email, inactive FROM curciol.f_atendentes();
CREATE VIEW curciol.canais AS SELECT * FROM curciol.f_canais();
CREATE VIEW curciol.filas_atendentes AS SELECT * FROM curciol.f_filas_atendentes();
CREATE VIEW curciol.canais_atendentes AS SELECT * FROM curciol.f_canais_atendentes();

-- 4) conferencia ------------------------------------------------------------------------------------------------------
-- objetos da Curciol que prendem coluna/tabela do APChat (devem ser zero: senao a atualizacao do APChat pode parar)
CREATE OR REPLACE FUNCTION curciol.dependencias()
RETURNS TABLE (objeto text, prende text)
    LANGUAGE sql STABLE SECURITY DEFINER SET search_path = pg_catalog, pg_temp
AS $$
    SELECT DISTINCT pg_describe_object(d.classid, d.objid, d.objsubid), pg_describe_object(d.refclassid, d.refobjid, d.refobjsubid)
      FROM pg_depend d
      JOIN pg_class rc ON d.refclassid = 'pg_class'::regclass AND rc.oid = d.refobjid
      JOIN pg_namespace rn ON rn.oid = rc.relnamespace
     WHERE rn.nspname NOT IN ('curciol', 'pg_catalog', 'information_schema') AND rn.nspname NOT LIKE 'pg_toast%'
       AND (   (d.classid = 'pg_rewrite'::regclass
                AND EXISTS (SELECT 1 FROM pg_rewrite r JOIN pg_class v ON v.oid = r.ev_class JOIN pg_namespace vn ON vn.oid = v.relnamespace
                             WHERE r.oid = d.objid AND vn.nspname = 'curciol'))
            OR (d.classid = 'pg_trigger'::regclass AND d.refobjsubid > 0
                AND EXISTS (SELECT 1 FROM pg_trigger t JOIN pg_proc p ON p.oid = t.tgfoid JOIN pg_namespace pn ON pn.oid = p.pronamespace
                             WHERE t.oid = d.objid AND pn.nspname = 'curciol'))
            OR (d.classid = 'pg_proc'::regclass
                AND EXISTS (SELECT 1 FROM pg_proc p JOIN pg_namespace pn ON pn.oid = p.pronamespace WHERE p.oid = d.objid AND pn.nspname = 'curciol')))
     ORDER BY 1, 2
$$;

-- tudo o que a Curciol le, testado agora: vazio = OK; cada linha = o que falhou
CREATE OR REPLACE FUNCTION curciol.conferir()
RETURNS TABLE (item text, erro text)
    LANGUAGE plpgsql VOLATILE SECURITY DEFINER SET search_path = pg_catalog, pg_temp
AS $$
DECLARE
    v_tab  text;
    v_def  text;
    v_sql  text;
    v_n    bigint;
BEGIN
    -- colunas que a Curciol le e o APChat nao tem mais
    FOREACH v_tab IN ARRAY ARRAY['Contacts', 'Tickets', 'Tickets+', 'Messages', 'Messages+', 'Users', 'Queues', 'Whatsapps', 'UsersQueues', 'UserWhatsapps', 'ContactWallets'] LOOP
        IF to_regclass(format('public.%I', replace(v_tab, '+', ''))) IS NULL THEN
            item := 'tabela'; erro := format('public."%s" nao existe', replace(v_tab, '+', '')); RETURN NEXT; CONTINUE;
        END IF;
        FOREACH v_def IN ARRAY curciol._defs(v_tab) LOOP
            IF NOT EXISTS (SELECT 1 FROM pg_attribute WHERE attrelid = to_regclass(format('public.%I', replace(v_tab, '+', '')))
                              AND attname = split_part(v_def, ' ', 1) AND attnum > 0 AND NOT attisdropped) THEN
                item := 'coluna'; erro := format('public."%s"."%s" nao existe', replace(v_tab, '+', ''), split_part(v_def, ' ', 1)); RETURN NEXT;
            END IF;
        END LOOP;
    END LOOP;
    FOREACH v_tab IN ARRAY ARRAY['Settings', 'Tenants', 'PrivateMessage', 'GroupMessages', 'UsersPrivateGroups', 'ReadPrivateMessageGroups'] LOOP
        IF to_regclass(format('public.%I', v_tab)) IS NULL THEN
            item := 'tabela'; erro := format('public."%s" nao existe', v_tab); RETURN NEXT;
        END IF;
    END LOOP;

    -- cada leitura roda de verdade (com valores que nao existem: so testa a consulta)
    FOREACH v_sql IN ARRAY ARRAY[
        'SELECT count(*) FROM curciol.f_contatos(ARRAY[0], ARRAY[''0''])',
        'SELECT count(*) FROM curciol.f_tickets(ARRAY[0], ARRAY[0])',
        'SELECT count(*) FROM curciol.f_tickets(NULL, NULL, true)',
        'SELECT count(*) FROM curciol.f_mensagens(ARRAY[0], ARRAY[0], now(), now())',
        'SELECT count(*) FROM curciol.f_filas()',
        'SELECT count(*) FROM curciol.f_canais()',
        'SELECT count(*) FROM curciol.f_atendentes()',
        'SELECT count(*) FROM curciol.f_filas_atendentes()',
        'SELECT count(*) FROM curciol.f_canais_atendentes()',
        'SELECT count(*) FROM curciol.f_carteiras(ARRAY[0])',
        'SELECT count(*) FROM (SELECT curciol.f_configuracoes()) x',
        'SELECT count(*) FROM curciol.f_chat_nao_lidas(ARRAY[0])'] LOOP
        BEGIN
            EXECUTE v_sql INTO v_n;
        EXCEPTION WHEN OTHERS THEN
            item := substring(v_sql FROM 'curciol\.(f_[a-z_]+)'); erro := SQLERRM; RETURN NEXT;
        END;
    END LOOP;

    FOR item, erro IN SELECT 'dependencia', d.objeto || ' prende ' || d.prende FROM curciol.dependencias() d LOOP
        RETURN NEXT;
    END LOOP;
END
$$;

-- 5) travas do contato sem lista de colunas (o tenant e conferido dentro da funcao) -------------------------------------
\if :travas
SELECT to_regprocedure('curciol.espelho_do_numero(text)') IS NULL AS sem_espelho \gset
\if :sem_espelho
    \echo 'travas=1 exige 2026-11-03-schema-curciol.sql e 2026-11-10-curciol-contato-novo-e-visoes.sql antes'
    \quit
\endif

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
    IF NEW."tenantId" IS DISTINCT FROM curciol.tenant() THEN
        RETURN NEW;                                    -- outro tenant: nada a fazer
    END IF;
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

CREATE OR REPLACE FUNCTION curciol.proteger_exclusao()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = curciol, public, pg_temp
AS $fn$
BEGIN
    IF OLD."tenantId" IS DISTINCT FROM curciol.tenant() THEN
        RETURN OLD;
    END IF;
    IF EXISTS (SELECT 1 FROM curciol.contato WHERE contato_id = OLD.id) THEN
        BEGIN
            INSERT INTO curciol.log (acao, contato_id, detalhe) VALUES ('EXCLUSAO_BLOQUEADA', OLD.id, concat_ws(' | ', OLD.name, OLD.number, OLD.email));
        EXCEPTION WHEN OTHERS THEN
            RAISE WARNING 'curciol.proteger_exclusao(%): log nao gravado: %', OLD.id, SQLERRM;
        END;
        RETURN NULL;                                   -- contato da Curciol: a exclusao nao acontece
    END IF;
    RETURN OLD;
EXCEPTION WHEN OTHERS THEN
    RAISE WARNING 'curciol.proteger_exclusao(%): %', OLD.id, SQLERRM;
    RETURN OLD;                                        -- erro da Curciol nunca impede o APChat
END
$fn$;

CREATE OR REPLACE FUNCTION curciol.contato_novo() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER SET search_path = curciol, public, pg_temp
AS $$
DECLARE
    e record;
BEGIN
    IF NEW."tenantId" IS DISTINCT FROM curciol.tenant() OR coalesce(NEW.number, '') = '' THEN
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

CREATE OR REPLACE FUNCTION curciol.contato_novo_espelho() RETURNS trigger
    LANGUAGE plpgsql SECURITY DEFINER SET search_path = curciol, public, pg_temp
AS $$
DECLARE
    e record;
BEGIN
    IF NEW."tenantId" IS DISTINCT FROM curciol.tenant() OR coalesce(NEW.number, '') = '' THEN
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

DROP TRIGGER IF EXISTS trg_curciol_proteger_contato ON public."Contacts";
CREATE TRIGGER trg_curciol_proteger_contato BEFORE UPDATE ON public."Contacts" FOR EACH ROW EXECUTE FUNCTION curciol.proteger_contato();
DROP TRIGGER IF EXISTS trg_curciol_proteger_exclusao ON public."Contacts";
CREATE TRIGGER trg_curciol_proteger_exclusao BEFORE DELETE ON public."Contacts" FOR EACH ROW EXECUTE FUNCTION curciol.proteger_exclusao();
DROP TRIGGER IF EXISTS trg_curciol_contato_novo ON public."Contacts";
CREATE TRIGGER trg_curciol_contato_novo BEFORE INSERT ON public."Contacts" FOR EACH ROW EXECUTE FUNCTION curciol.contato_novo();
DROP TRIGGER IF EXISTS trg_curciol_contato_novo_espelho ON public."Contacts";
CREATE TRIGGER trg_curciol_contato_novo_espelho AFTER INSERT ON public."Contacts" FOR EACH ROW EXECUTE FUNCTION curciol.contato_novo_espelho();
REVOKE ALL ON FUNCTION curciol.proteger_contato(), curciol.proteger_exclusao(), curciol.contato_novo(), curciol.contato_novo_espelho() FROM PUBLIC;
\endif

-- 6) permissoes -------------------------------------------------------------------------------------------------------
REVOKE ALL ON FUNCTION curciol.tenant(), curciol._defs(text), curciol._sel(text, text[]),
    curciol.f_contatos(integer[], varchar[]), curciol.f_tickets(integer[], integer[], boolean),
    curciol.f_mensagens(integer[], integer[], timestamptz, timestamptz), curciol.f_filas(), curciol.f_canais(), curciol.f_atendentes(),
    curciol.f_filas_atendentes(), curciol.f_canais_atendentes(), curciol.f_carteiras(integer[]), curciol.f_configuracoes(),
    curciol.f_chat_nao_lidas(integer[]), curciol.dependencias(), curciol.conferir() FROM PUBLIC;
-- de 10/11: espelho_do_numero le o espelho (nomes, numeros, CPF) como dono; so as travas usam
SELECT format('REVOKE ALL ON FUNCTION %s FROM PUBLIC', f) FROM unnest(ARRAY['curciol.espelho_do_numero(text)', 'curciol.variantes_numero(text)']) f
 WHERE to_regprocedure(f) IS NOT NULL \gexec
SELECT format('GRANT USAGE ON SCHEMA curciol TO %I', :'papel') \gexec
SELECT format('GRANT EXECUTE ON FUNCTION curciol.tenant(), curciol._defs(text), curciol._sel(text, text[]),
    curciol.f_contatos(integer[], varchar[]), curciol.f_tickets(integer[], integer[], boolean),
    curciol.f_mensagens(integer[], integer[], timestamptz, timestamptz), curciol.f_filas(), curciol.f_canais(), curciol.f_atendentes(),
    curciol.f_filas_atendentes(), curciol.f_canais_atendentes(), curciol.f_carteiras(integer[]), curciol.f_configuracoes(),
    curciol.f_chat_nao_lidas(integer[]), curciol.dependencias(), curciol.conferir() TO %I', :'papel') \gexec
SELECT format('GRANT SELECT ON curciol.contatos, curciol.tickets, curciol.mensagens, curciol.filas, curciol.atendentes, curciol.canais,
    curciol.filas_atendentes, curciol.canais_atendentes TO %I', :'papel') \gexec
