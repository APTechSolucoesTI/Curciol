-- =====================================================================================================================
-- Homologacao lendo OUTRO APChat (10/11/2026; teste no api1.apchat.com.br, tenancia 1). SO LEITURA. Nunca no APChat oficial.
--   psql -v tenant=1 -v papel=curciol_homolog -v senha='...' -v ON_ERROR_STOP=1 -f leitura-homologacao.sql
-- Cria so o schema curciol: as mesmas visoes da Curciol filtradas na tenancia informada, o espelho e o log VAZIOS (a homologacao
-- nunca grava no APChat) e um papel que SO le. Nada no schema public muda: nenhuma trigger, nenhuma funcao, nenhum dado.
-- Pode rodar de novo. Para desfazer: DROP SCHEMA curciol CASCADE; DROP ROLE <papel>;
-- =====================================================================================================================
\if :{?tenant}
\else
    \echo 'informe -v tenant=N'
    \quit
\endif
SELECT to_regclass('public."TicketsAntigo"') IS NOT NULL AND to_regclass('public."MessagesAntigo"') IS NOT NULL AS tem_antigo \gset

BEGIN;
CREATE SCHEMA IF NOT EXISTS curciol;
REVOKE ALL ON SCHEMA curciol FROM PUBLIC;

CREATE OR REPLACE VIEW curciol.contatos AS
    SELECT id, name, number, email, cpf, "firstName", "lastName", "businessName", "birthdayDate", pushname, lid, "isLid",
           "isGroup", "createdAt", "updatedAt"
      FROM public."Contacts" WHERE "tenantId" = :tenant;

\if :tem_antigo
CREATE OR REPLACE VIEW curciol.tickets AS
    SELECT id, status, "lastMessage", "contactId", "userId", "queueId", "createdAt", "updatedAt", "lastMessageAt", "closedAt",
           false AS arquivado, "whatsappId", "closedByUserId"
      FROM public."Tickets" WHERE "tenantId" = :tenant
    UNION ALL
    SELECT id, status, "lastMessage", "contactId", "userId", "queueId", "createdAt", "updatedAt", "lastMessageAt", "closedAt",
           true, "whatsappId", NULL::integer
      FROM public."TicketsAntigo" WHERE "tenantId" = :tenant;

CREATE OR REPLACE VIEW curciol.mensagens AS
    SELECT id, body, "mediaType", "fileName", "fromMe", "isDeleted", "ticketId", "contactId", "userId", "createdAt", false AS arquivada,
           ack, status, "messageId", "queueId"
      FROM public."Messages" WHERE "tenantId" = :tenant
    UNION ALL
    SELECT id, body, "mediaType", NULL::text, "fromMe", "isDeleted", "ticketId", "contactId", "userId", "createdAt", true,
           ack, status, "messageId", "queueId"
      FROM public."MessagesAntigo" WHERE "tenantId" = :tenant;
\else
CREATE OR REPLACE VIEW curciol.tickets AS
    SELECT id, status, "lastMessage", "contactId", "userId", "queueId", "createdAt", "updatedAt", "lastMessageAt", "closedAt",
           false AS arquivado, "whatsappId", "closedByUserId"
      FROM public."Tickets" WHERE "tenantId" = :tenant;

CREATE OR REPLACE VIEW curciol.mensagens AS
    SELECT id, body, "mediaType", "fileName", "fromMe", "isDeleted", "ticketId", "contactId", "userId", "createdAt", false AS arquivada,
           ack, status, "messageId", "queueId"
      FROM public."Messages" WHERE "tenantId" = :tenant;
\endif

CREATE OR REPLACE VIEW curciol.filas AS
    SELECT id, queue, "isActive" FROM public."Queues" WHERE "tenantId" = :tenant;

-- so id, nome, e-mail e inativo (Users tem senha, tokens etc.)
CREATE OR REPLACE VIEW curciol.atendentes AS
    SELECT id, name, email, inactive FROM public."Users" WHERE "tenantId" = :tenant;

-- sem tokenAPI nem nada alem do nome e do tipo
CREATE OR REPLACE VIEW curciol.canais AS
    SELECT id, name, type, "isDeleted" FROM public."Whatsapps" WHERE "tenantId" = :tenant;

CREATE OR REPLACE VIEW curciol.filas_atendentes AS
    SELECT uq."userId", uq."queueId"
      FROM public."UsersQueues" uq JOIN public."Users" u ON u.id = uq."userId" AND u."tenantId" = :tenant;

CREATE OR REPLACE VIEW curciol.canais_atendentes AS
    SELECT uw."userId", uw."whatsappId", uw."isActive"
      FROM public."UserWhatsapps" uw JOIN public."Users" u ON u.id = uw."userId" AND u."tenantId" = :tenant;

-- espelho e log: so para as leituras da Curciol funcionarem; ficam vazios
CREATE TABLE IF NOT EXISTS curciol.contato (
    contato_id    integer PRIMARY KEY,
    nome          varchar(255),
    numero        varchar(30),
    email         varchar(255),
    ligado        boolean NOT NULL DEFAULT true,
    atualizado_em timestamptz NOT NULL DEFAULT now(),
    cpf           text,
    primeiro_nome text,
    ultimo_nome   text,
    razao_social  text,
    nascimento    text
);
CREATE TABLE IF NOT EXISTS curciol.log (
    id              bigserial PRIMARY KEY,
    quando          timestamptz NOT NULL DEFAULT now(),
    acao            varchar(30) NOT NULL,
    contato_id      integer,
    contato_novo_id integer,
    detalhe         text
);

-- papel so de leitura (a senha so no app/config/apchat.php da homologacao)
SELECT NOT EXISTS (SELECT 1 FROM pg_roles WHERE rolname = :'papel') AS papel_novo \gset
\if :papel_novo
SELECT format('CREATE ROLE %I LOGIN PASSWORD %L CONNECTION LIMIT 10', :'papel', :'senha') \gexec
\else
SELECT format('ALTER ROLE %I LOGIN PASSWORD %L CONNECTION LIMIT 10', :'papel', :'senha') \gexec
\endif
SELECT format('ALTER ROLE %I SET default_transaction_read_only = on', :'papel') \gexec
SELECT format('ALTER ROLE %I SET statement_timeout = %L', :'papel', '30s') \gexec
SELECT format('GRANT CONNECT ON DATABASE %I TO %I', current_database(), :'papel') \gexec
SELECT format('GRANT USAGE ON SCHEMA curciol TO %I', :'papel') \gexec
SELECT format('GRANT SELECT ON ALL TABLES IN SCHEMA curciol TO %I', :'papel') \gexec
COMMIT;
