-- Portal do cliente: o login (pessoa.usuario) passa a ser o CPF/CNPJ so com numeros.
-- CPF/CNPJ valido (digitos verificadores) -> usuario = os numeros; vazio ou invalido -> usuario vazio (NULL).
-- Mesma regra de PessoaLoginService (cadastros). Senhas nao mudam. Idempotente.
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

DO $$
DECLARE com_cpf int; vazio int; muda int;
BEGIN
    SELECT COUNT(*) FILTER (WHERE pg_temp.cpf_cnpj_valido(cpf_cnpj)),
           COUNT(*) FILTER (WHERE NOT pg_temp.cpf_cnpj_valido(cpf_cnpj)),
           COUNT(*) FILTER (WHERE usuario IS DISTINCT FROM CASE WHEN pg_temp.cpf_cnpj_valido(cpf_cnpj) THEN regexp_replace(cpf_cnpj, '\D', '', 'g') END)
      INTO com_cpf, vazio, muda FROM pessoa;
    RAISE NOTICE 'usuario = CPF/CNPJ: % pessoas; usuario vazio (CPF/CNPJ vazio ou invalido): %; linhas alteradas: %', com_cpf, vazio, muda;
END $$;

UPDATE pessoa
   SET usuario = CASE WHEN pg_temp.cpf_cnpj_valido(cpf_cnpj) THEN regexp_replace(cpf_cnpj, '\D', '', 'g') END
 WHERE usuario IS DISTINCT FROM CASE WHEN pg_temp.cpf_cnpj_valido(cpf_cnpj) THEN regexp_replace(cpf_cnpj, '\D', '', 'g') END;

DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pessoa WHERE usuario IS NOT NULL AND usuario <> regexp_replace(COALESCE(cpf_cnpj, ''), '\D', '', 'g')) THEN
        RAISE EXCEPTION 'sobrou usuario diferente do CPF/CNPJ';
    END IF;
END $$;

COMMIT;
