-- 16/11/2026: o vinculo usuario da Curciol <-> atendente do APChat passa a dizer DE QUAL APChat (servidor da URL da API do
-- Escritorio, ex. api.apchat.com.br = oficial, api1.apchat.com.br = app1). O banco do APChat e escolhido pela URL; o numero do
-- atendente so vale no servidor em que foi ligado (no outro, o mesmo numero e outra pessoa). Vinculo de outro servidor = sem
-- vinculo (APChat Indisponivel para o usuario) ate ligar de novo. Pode rodar de novo.
BEGIN;

ALTER TABLE apchat_usuario ADD COLUMN IF NOT EXISTS apchat_servidor varchar(255);
COMMENT ON COLUMN apchat_usuario.apchat_servidor IS 'Servidor do APChat (host da URL da API) do atendente ligado ou do desligamento manual; NULL = antigo (vale para o atual)';

-- os vinculos e desligamentos que ja existem sao do servidor da URL de hoje (producao: sempre o oficial)
UPDATE apchat_usuario
   SET apchat_servidor = (SELECT lower(substring(btrim(e.url_apchat) FROM '^[A-Za-z][A-Za-z0-9+.-]*://([^/:?#]+)'))
                            FROM escritorio e WHERE COALESCE(btrim(e.url_apchat), '') <> ''   -- a URL basta: a producao esta sem token desde 03/11
                           ORDER BY e.id LIMIT 1)
 WHERE apchat_servidor IS NULL;

-- o mesmo numero de atendente pode existir em servidores diferentes; no mesmo servidor, um usuario da Curciol so
ALTER TABLE apchat_usuario DROP CONSTRAINT IF EXISTS apchat_usuario_apchat_usuario_id_key;
CREATE UNIQUE INDEX IF NOT EXISTS apchat_usuario_servidor_atendente ON apchat_usuario (COALESCE(apchat_servidor, ''), apchat_usuario_id)
 WHERE apchat_usuario_id IS NOT NULL;

COMMIT;
