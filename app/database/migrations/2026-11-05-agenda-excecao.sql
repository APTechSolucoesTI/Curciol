-- Excecao da agenda (05/11/2026): um periodo liberado dentro de bloqueio, intervalo ou nao atende, em que se pode marcar
-- agendamentos e compromissos. Substitui a tela "Agendamento de Excecao" (AgendamentoExcecao): os agendamentos criados por ela
-- continuam como agendamentos comuns. Quem podia usar a tela antiga passa a poder usar a nova (mesmo programa, novo controller);
-- quem ja esta logado precisa entrar de novo. Pode rodar mais de uma vez.
BEGIN;

CREATE TABLE IF NOT EXISTS agenda_excecao (
    id SERIAL PRIMARY KEY,
    agenda_id integer NOT NULL REFERENCES agenda (id) ON DELETE CASCADE,
    dt_inicio timestamp NOT NULL,
    dt_final timestamp NOT NULL,
    observacao text NOT NULL,
    data_criacao timestamp,
    criacao_user_id integer,
    data_modificacao timestamp,
    modificacao_user_id integer,
    CONSTRAINT ck_agenda_excecao_periodo CHECK (dt_final > dt_inicio)
);
CREATE INDEX IF NOT EXISTS idx_agenda_excecao_periodo ON agenda_excecao (agenda_id, dt_inicio, dt_final);

UPDATE system_program SET controller = 'AgendaExcecaoForm', name = 'Exceção da agenda'
 WHERE controller = 'AgendamentoExcecao'
   AND NOT EXISTS (SELECT 1 FROM system_program WHERE controller = 'AgendaExcecaoForm');

COMMIT;

-- Para desfazer:
-- UPDATE system_program SET controller = 'AgendamentoExcecao', name = 'Agendamento de Exceção' WHERE controller = 'AgendaExcecaoForm';
-- DROP TABLE agenda_excecao;
