-- Links da Curciol para o APChat e o APMail abrindo SO no app instalado (12/11/2026).
-- 'S': o clique vira web+apchat:// / web+apmail:// (abre no app instalado; sem app, nada abre e a Curciol avisa).
-- 'N' (padrao): como antes, abre a tela numa aba do navegador.
-- So ligar quando o lado do sistema estiver pronto: APChat = snippet do nginx (curciol-apchat.conf) no servidor do APChat;
-- APMail = manifesto com protocol_handlers web+apmail (tarefa do APMail).
ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS apchat_abrir_app char(1) NOT NULL DEFAULT 'N';
ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS apmail_abrir_app char(1) NOT NULL DEFAULT 'N';
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_escritorio_apchat_abrir_app') THEN
        ALTER TABLE escritorio ADD CONSTRAINT ck_escritorio_apchat_abrir_app CHECK (apchat_abrir_app IN ('S', 'N'));
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'ck_escritorio_apmail_abrir_app') THEN
        ALTER TABLE escritorio ADD CONSTRAINT ck_escritorio_apmail_abrir_app CHECK (apmail_abrir_app IN ('S', 'N'));
    END IF;
END $$;
COMMENT ON COLUMN escritorio.apchat_abrir_app IS 'S = links da Curciol abrem o APChat so no app instalado (web+apchat)';
COMMENT ON COLUMN escritorio.apmail_abrir_app IS 'S = links da Curciol abrem o APMail so no app instalado (web+apmail)';
