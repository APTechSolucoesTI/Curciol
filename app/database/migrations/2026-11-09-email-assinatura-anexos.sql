-- Assinatura e anexos do e-mail (09/11/2026; EmailAssinaturaService, EmailAnexoService, EmailMontagemService).
-- Cada arquivo enviado vira um registro que nunca muda nem e apagado: template, mensagem e lembrete apontam para ele,
-- assim o historico mostra o que saiu mesmo depois de trocas. email_assinatura.html tem src RELATIVOS
-- (assinaturas/<hash>.png): a imagem fica hospedada na pasta publica assinaturas/ do site.
-- Botoes do template de confirmacao por e-mail: texto e cor de cada um. Pode rodar mais de uma vez.
BEGIN;

CREATE TABLE IF NOT EXISTS email_assinatura (
    id             SERIAL PRIMARY KEY,
    nome_original  VARCHAR(255) NOT NULL,
    tipo           VARCHAR(10)  NOT NULL CHECK (tipo IN ('IMAGEM', 'HTML')),
    html           TEXT         NOT NULL,
    criado_em      TIMESTAMP    NOT NULL DEFAULT now(),
    system_user_id INTEGER
);

CREATE TABLE IF NOT EXISTS email_anexo (
    id             SERIAL PRIMARY KEY,
    nome_original  VARCHAR(255) NOT NULL,
    caminho        VARCHAR(500) NOT NULL,
    tamanho        INTEGER      NOT NULL,
    criado_em      TIMESTAMP    NOT NULL DEFAULT now(),
    system_user_id INTEGER
);

CREATE TABLE IF NOT EXISTS template_escritorio_anexo (
    id                     SERIAL PRIMARY KEY,
    template_escritorio_id INTEGER NOT NULL REFERENCES template_escritorio(id) ON DELETE CASCADE,
    email_anexo_id         INTEGER NOT NULL REFERENCES email_anexo(id),
    ordem                  INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS template_escritorio_anexo_template_idx ON template_escritorio_anexo (template_escritorio_id);

CREATE TABLE IF NOT EXISTS mensagem_anexo (
    id             SERIAL PRIMARY KEY,
    mensagem_id    INTEGER NOT NULL REFERENCES mensagem(id) ON DELETE CASCADE,
    email_anexo_id INTEGER NOT NULL REFERENCES email_anexo(id)
);
CREATE INDEX IF NOT EXISTS mensagem_anexo_mensagem_idx ON mensagem_anexo (mensagem_id);

CREATE TABLE IF NOT EXISTS mensagem_agendada_anexo (
    id                   SERIAL PRIMARY KEY,
    mensagem_agendada_id INTEGER NOT NULL REFERENCES mensagem_agendada(id) ON DELETE CASCADE,
    email_anexo_id       INTEGER NOT NULL REFERENCES email_anexo(id)
);
CREATE INDEX IF NOT EXISTS mensagem_agendada_anexo_lembrete_idx ON mensagem_agendada_anexo (mensagem_agendada_id);

ALTER TABLE email_config      ADD COLUMN IF NOT EXISTS assinatura_id INTEGER REFERENCES email_assinatura(id);
ALTER TABLE mensagem          ADD COLUMN IF NOT EXISTS assinatura_id INTEGER REFERENCES email_assinatura(id);
ALTER TABLE mensagem_agendada ADD COLUMN IF NOT EXISTS assinatura_id INTEGER REFERENCES email_assinatura(id);

ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS assinatura_modo       VARCHAR(10) NOT NULL DEFAULT 'PADRAO';
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS assinatura_id         INTEGER REFERENCES email_assinatura(id);
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS botao_confirmar_texto VARCHAR(60) DEFAULT 'Confirmar presença';
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS botao_confirmar_cor   VARCHAR(7)  DEFAULT '#2e7d32';
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS botao_cancelar_texto  VARCHAR(60) DEFAULT 'Cancelar agendamento';
ALTER TABLE template_escritorio ADD COLUMN IF NOT EXISTS botao_cancelar_cor    VARCHAR(7)  DEFAULT '#c62828';

ALTER TABLE template_escritorio DROP CONSTRAINT IF EXISTS template_escritorio_assinatura_modo_check;
ALTER TABLE template_escritorio ADD CONSTRAINT template_escritorio_assinatura_modo_check
    CHECK (assinatura_modo IN ('PADRAO', 'OUTRA', 'NENHUMA'));

COMMIT;

-- Volta (so se nada foi enviado com assinatura ou anexo):
-- BEGIN;
-- ALTER TABLE template_escritorio DROP CONSTRAINT IF EXISTS template_escritorio_assinatura_modo_check;
-- ALTER TABLE template_escritorio DROP COLUMN IF EXISTS assinatura_modo, DROP COLUMN IF EXISTS assinatura_id,
--     DROP COLUMN IF EXISTS botao_confirmar_texto, DROP COLUMN IF EXISTS botao_confirmar_cor,
--     DROP COLUMN IF EXISTS botao_cancelar_texto, DROP COLUMN IF EXISTS botao_cancelar_cor;
-- ALTER TABLE mensagem_agendada DROP COLUMN IF EXISTS assinatura_id;
-- ALTER TABLE mensagem DROP COLUMN IF EXISTS assinatura_id;
-- ALTER TABLE email_config DROP COLUMN IF EXISTS assinatura_id;
-- DROP TABLE IF EXISTS mensagem_agendada_anexo, mensagem_anexo, template_escritorio_anexo, email_anexo, email_assinatura;
-- COMMIT;
