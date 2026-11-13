-- =====================================================================================================================
-- EMERGENCIA (13/11/2026): a atualizacao do APChat (./zpro) PAROU com um destes erros citando algo do schema "curciol":
--   cannot alter type of a column used by a view or rule
--   cannot alter type of a column used in a trigger definition
--   cannot drop column ... because other objects depend on it
-- Isso so acontece se o banco ainda tiver as visoes/triggers ANTIGAS da Curciol (antes de 2026-11-13-curciol-funcoes.sql)
-- ou se alguem rodou de novo um SQL antigo. Este arquivo tira SO as visoes da Curciol e as triggers da Curciol em
-- "Contacts" (nao apaga o schema, o espelho, o log nem as funcoes). Depois: rode o ./zpro de novo e reaplique
-- curciol-funcoes.sql (que recria tudo sem prender coluna). Enquanto isso a Curciol fica sem ler o APChat.
--   psql ... -v ON_ERROR_STOP=1 -f curciol-emergencia-atualizacao.sql
-- =====================================================================================================================
BEGIN;
DO $$
DECLARE
    v record;
BEGIN
    FOR v IN SELECT c.oid::regclass AS nome FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
              WHERE n.nspname = 'curciol' AND c.relkind IN ('v', 'm') LOOP
        EXECUTE format('DROP VIEW IF EXISTS %s CASCADE', v.nome);
        RAISE NOTICE 'removida a visao %', v.nome;
    END LOOP;
    FOR v IN SELECT t.tgname, t.tgrelid::regclass AS tabela FROM pg_trigger t JOIN pg_proc p ON p.oid = t.tgfoid
              JOIN pg_namespace n ON n.oid = p.pronamespace WHERE n.nspname = 'curciol' AND NOT t.tgisinternal LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS %I ON %s', v.tgname, v.tabela);
        RAISE NOTICE 'removida a trigger % de %', v.tgname, v.tabela;
    END LOOP;
END
$$;
COMMIT;
