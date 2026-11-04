-- Lembrete agendado do agendamento confirmado (04/11/2026; MensagemAgendadaService).
-- titulo e texto ficam ainda com as variaveis: sao trocadas na hora do envio (se o horario mudou, vai o novo).
-- situacao: PENDENTE | ENVIADA | CANCELADA | FALHOU. Pode rodar mais de uma vez.
BEGIN;

CREATE TABLE IF NOT EXISTS mensagem_agendada (
    id                     SERIAL PRIMARY KEY,
    agendamento_id         INTEGER NOT NULL REFERENCES agendamento(id) ON DELETE CASCADE,
    template_escritorio_id INTEGER REFERENCES template_escritorio(id) ON DELETE SET NULL,
    mensagem_id            INTEGER REFERENCES mensagem(id) ON DELETE SET NULL,
    system_user_id         INTEGER,
    tipo_mensagem          VARCHAR(20) NOT NULL,
    destino                VARCHAR(200) NOT NULL,
    titulo                 TEXT,
    texto                  TEXT NOT NULL,
    enviar_em              TIMESTAMP NOT NULL,
    situacao               VARCHAR(20) NOT NULL DEFAULT 'PENDENTE',
    tentativas             INTEGER NOT NULL DEFAULT 0,
    motivo                 TEXT,
    processado_em          TIMESTAMP,
    criado_em              TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS mensagem_agendada_pendente_idx ON mensagem_agendada (enviar_em) WHERE situacao = 'PENDENTE';
CREATE INDEX IF NOT EXISTS mensagem_agendada_agendamento_idx ON mensagem_agendada (agendamento_id);

COMMIT;
