-- Executar no banco da conexao log, antes de publicar o codigo que usa IDs serial.
-- Nao executar no banco do escritorio. Nao instala funcoes nem triggers.
BEGIN;
LOCK TABLE system_sql_log, system_access_log IN ACCESS EXCLUSIVE MODE;

CREATE SEQUENCE IF NOT EXISTS system_sql_log_id_seq;
ALTER SEQUENCE system_sql_log_id_seq OWNED BY system_sql_log.id;
SELECT setval('system_sql_log_id_seq', GREATEST(
    COALESCE((SELECT max(id) FROM system_sql_log), 0),
    (SELECT last_value FROM system_sql_log_id_seq), 1));
ALTER TABLE system_sql_log ALTER COLUMN id SET DEFAULT nextval('system_sql_log_id_seq');

CREATE SEQUENCE IF NOT EXISTS system_access_log_id_seq;
ALTER SEQUENCE system_access_log_id_seq OWNED BY system_access_log.id;
SELECT setval('system_access_log_id_seq', GREATEST(
    COALESCE((SELECT max(id) FROM system_access_log), 0),
    (SELECT last_value FROM system_access_log_id_seq), 1));
ALTER TABLE system_access_log ALTER COLUMN id SET DEFAULT nextval('system_access_log_id_seq');
COMMIT;
