-- Conversas do APMail no historico do cliente (12/11/2026). Pode rodar de novo.
-- A sincronizacao (ApmailSincronizacaoService, cron a cada 5 min) grava aqui; a aba Atendimentos do cliente so le daqui.
-- Sem anexos: so a quantidade (para ver, "Abrir no APMail").
BEGIN;

CREATE TABLE IF NOT EXISTS apmail_conversa (
    id                serial PRIMARY KEY,
    thread_id         uuid NOT NULL UNIQUE,
    assunto           text,
    caixa_id          uuid,
    caixa_nome        text,
    caixa_email       text,
    inicio_em         timestamp,
    fim_em            timestamp,
    responsavel_nome  text,
    responsavel_email text,
    situacao          varchar(30),
    qtd_mensagens     integer NOT NULL DEFAULT 0,
    atualizado_em     timestamp NOT NULL DEFAULT now()
);
COMMENT ON TABLE apmail_conversa IS 'Conversa do APMail (threadId) importada para o historico do cliente (12/11/2026). Datas em America/Sao_Paulo';

CREATE TABLE IF NOT EXISTS apmail_mensagem (
    id                serial PRIMARY KEY,
    apmail_id         uuid NOT NULL UNIQUE,
    conversa_id       integer NOT NULL REFERENCES apmail_conversa (id) ON DELETE CASCADE,
    direcao           varchar(10) NOT NULL,
    assunto           text,
    de_nome           text,
    de_email          text,
    para              jsonb NOT NULL DEFAULT '[]',
    cc                jsonb NOT NULL DEFAULT '[]',
    mensagem_em       timestamp,
    enviado_por_nome  text,
    enviado_por_email text,
    corpo_html        text,
    corpo_texto       text,
    qtd_anexos        integer NOT NULL DEFAULT 0,
    importado_em      timestamp NOT NULL DEFAULT now()
);
CREATE INDEX IF NOT EXISTS ix_apmail_mensagem_conversa ON apmail_mensagem (conversa_id, mensagem_em);
-- anexo x imagem do corpo (assinatura/logo: inline ou com contentId) x convite de agenda (.ics/text/calendar): so arquivos de
-- verdade contam como anexo (12/11/2026)
ALTER TABLE apmail_mensagem ADD COLUMN IF NOT EXISTS qtd_imagens_corpo integer NOT NULL DEFAULT 0;
ALTER TABLE apmail_mensagem ADD COLUMN IF NOT EXISTS tem_convite boolean NOT NULL DEFAULT false;
COMMENT ON TABLE apmail_mensagem IS 'E-mail (recebido ou enviado) de uma conversa do APMail (12/11/2026). corpo_html = sanitizedHtml do APMail; a tela limpa de novo';

CREATE TABLE IF NOT EXISTS apmail_conversa_email (
    conversa_id integer NOT NULL REFERENCES apmail_conversa (id) ON DELETE CASCADE,
    email       text NOT NULL,
    UNIQUE (conversa_id, email)
);
CREATE INDEX IF NOT EXISTS ix_apmail_conversa_email_email ON apmail_conversa_email (email);
COMMENT ON TABLE apmail_conversa_email IS 'Enderecos (remetente, destinatarios, Cc; sem os das caixas) de cada conversa: liga a conversa ao cliente pelos e-mails dele';

CREATE TABLE IF NOT EXISTS apmail_sincronizacao (
    id               serial PRIMARY KEY,
    chave            varchar(64) NOT NULL UNIQUE,
    cursor           text,
    atualizado_em    timestamp NOT NULL DEFAULT now(),
    ultima_rodada_em timestamp,
    ultimo_erro      text
);
COMMENT ON TABLE apmail_sincronizacao IS 'Cursor da sincronizacao por token + usuario de integracao + direcao (12/11/2026)';
-- reconciliacao diaria (listagem do zero): caixa liberada depois no token nao aparece no cursor (regra do APMail)
ALTER TABLE apmail_sincronizacao ADD COLUMN IF NOT EXISTS concluida_em timestamp;

ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS apmail_email_integracao varchar(255);
COMMENT ON COLUMN escritorio.apmail_email_integracao IS 'Usuario do APMail que enxerga todas as caixas (historico dos clientes). Vazio = o do primeiro Admin ligado';

-- Usuarios das integracoes: situacao da ligacao com o APMail (S = reconhecido, N = nao reconhecido, nulo = nao conferido)
ALTER TABLE apmail_usuario ADD COLUMN IF NOT EXISTS situacao char(1);
ALTER TABLE apmail_usuario ADD COLUMN IF NOT EXISTS conferido_em timestamp;
-- acesso ao historico (12/11/2026): caixas que o usuario acessa no APMail (notificacoes), guardadas 60 s; APMail fora = ultimo conhecido
ALTER TABLE apmail_usuario ADD COLUMN IF NOT EXISTS caixas jsonb;
ALTER TABLE apmail_usuario ADD COLUMN IF NOT EXISTS caixas_em timestamp;

-- tela da conversa (ApmailConversaView): quem abre o cadastro do cliente (ClienteForm), por grupo ou permissao direta
INSERT INTO system_program (id, name, controller)
SELECT (SELECT COALESCE(MAX(id), 0) + 1 FROM system_program), 'Conversa de e-mail do APMail (historico do cliente)', 'ApmailConversaView'
 WHERE NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'ApmailConversaView');

INSERT INTO system_group_program (id, system_group_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_group_program) + row_number() OVER (ORDER BY g.system_group_id), g.system_group_id, novo.id
  FROM (SELECT DISTINCT gp.system_group_id FROM system_group_program gp JOIN system_program p ON p.id = gp.system_program_id AND p.controller = 'ClienteForm') g
  JOIN system_program novo ON novo.controller = 'ApmailConversaView'
 WHERE NOT EXISTS (SELECT 1 FROM system_group_program x WHERE x.system_group_id = g.system_group_id AND x.system_program_id = novo.id);

INSERT INTO system_user_program (id, system_user_id, system_program_id)
SELECT (SELECT COALESCE(MAX(id), 0) FROM system_user_program) + row_number() OVER (ORDER BY u.system_user_id), u.system_user_id, novo.id
  FROM (SELECT DISTINCT up.system_user_id FROM system_user_program up JOIN system_program p ON p.id = up.system_program_id AND p.controller = 'ClienteForm') u
  JOIN system_program novo ON novo.controller = 'ApmailConversaView'
 WHERE NOT EXISTS (SELECT 1 FROM system_user_program x WHERE x.system_user_id = u.system_user_id AND x.system_program_id = novo.id);

COMMIT;
