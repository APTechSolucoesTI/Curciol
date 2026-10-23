-- Portal do cliente: o login (pessoa.usuario) passa a ser o CPF/CNPJ so com numeros.
-- CPF/CNPJ valido (digitos verificadores) e de acordo com o tipo de pessoa (fisica = CPF de 11 digitos, juridica = CNPJ
-- de 14; sem tipo, qualquer um) -> usuario = os numeros; vazio, invalido ou do tipo errado -> usuario vazio (NULL).
-- Mesma regra de PessoaLoginService::usuarioPadrao (cadastros). As senhas NAO mudam (decisao de 23/10/2026). Idempotente.
BEGIN;

CREATE OR REPLACE FUNCTION pg_temp.cpf_cnpj_valido(doc text) RETURNS boolean
LANGUAGE plpgsql IMMUTABLE AS $$
DECLARE
    d text := regexp_replace(COALESCE(doc, ''), '\D', '', 'g');
    s int; r int; i int;
    p1 int[] := ARRAY[5,4,3,2,9,8,7,6,5,4,3,2];
    p2 int[] := ARRAY[6,5,4,3,2,9,8,7,6,5,4,3,2];
BEGIN
    IF length(d) = 11 THEN
        IF d ~ '^(\d)\1{10}$' THEN RETURN false; END IF;
        s := 0; FOR i IN 1..9 LOOP s := s + substr(d, i, 1)::int * (11 - i); END LOOP;
        IF ((10 * s) % 11) % 10 <> substr(d, 10, 1)::int THEN RETURN false; END IF;
        s := 0; FOR i IN 1..10 LOOP s := s + substr(d, i, 1)::int * (12 - i); END LOOP;
        RETURN ((10 * s) % 11) % 10 = substr(d, 11, 1)::int;
    ELSIF length(d) = 14 THEN
        IF d ~ '^(\d)\1{13}$' THEN RETURN false; END IF;
        s := 0; FOR i IN 1..12 LOOP s := s + substr(d, i, 1)::int * p1[i]; END LOOP;
        r := s % 11;
        IF (CASE WHEN r < 2 THEN 0 ELSE 11 - r END) <> substr(d, 13, 1)::int THEN RETURN false; END IF;
        s := 0; FOR i IN 1..13 LOOP s := s + substr(d, i, 1)::int * p2[i]; END LOOP;
        r := s % 11;
        RETURN (CASE WHEN r < 2 THEN 0 ELSE 11 - r END) = substr(d, 14, 1)::int;
    END IF;
    RETURN false;
END $$;

-- login padrao: os numeros do documento quando ele e valido para o tipo de pessoa
CREATE OR REPLACE FUNCTION pg_temp.login_padrao(doc text, tipo int) RETURNS text
LANGUAGE sql IMMUTABLE AS $$
    SELECT CASE WHEN pg_temp.cpf_cnpj_valido(doc)
                 AND (tipo IS NULL OR (tipo = 1 AND length(regexp_replace(doc, '\D', '', 'g')) = 11)
                                   OR (tipo = 2 AND length(regexp_replace(doc, '\D', '', 'g')) = 14))
                THEN regexp_replace(doc, '\D', '', 'g') END
$$;

DO $$
DECLARE com_cpf int; vazio int; muda int;
BEGIN
    SELECT COUNT(*) FILTER (WHERE pg_temp.login_padrao(cpf_cnpj, tipo_pessoa_id) IS NOT NULL),
           COUNT(*) FILTER (WHERE pg_temp.login_padrao(cpf_cnpj, tipo_pessoa_id) IS NULL),
           COUNT(*) FILTER (WHERE usuario IS DISTINCT FROM pg_temp.login_padrao(cpf_cnpj, tipo_pessoa_id))
      INTO com_cpf, vazio, muda FROM pessoa;
    RAISE NOTICE 'usuario = CPF/CNPJ: % pessoas; usuario vazio (CPF/CNPJ vazio, invalido ou do tipo errado): %; linhas alteradas: %', com_cpf, vazio, muda;
END $$;

UPDATE pessoa
   SET usuario = pg_temp.login_padrao(cpf_cnpj, tipo_pessoa_id)
 WHERE usuario IS DISTINCT FROM pg_temp.login_padrao(cpf_cnpj, tipo_pessoa_id);

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pessoa WHERE usuario IS DISTINCT FROM pg_temp.login_padrao(cpf_cnpj, tipo_pessoa_id)) THEN
        RAISE EXCEPTION 'sobrou usuario diferente do CPF/CNPJ';
    END IF;
END $$;

COMMIT;
