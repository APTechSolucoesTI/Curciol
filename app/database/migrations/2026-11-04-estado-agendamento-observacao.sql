-- "Cancelar confirmacao" do agendamento (04/11/2026): o agendamento confirmado volta a Agendado e a observacao de quem
-- cancelou fica no historico de estados.
-- Pode rodar mais de uma vez.
BEGIN;

ALTER TABLE estado_agendamento ADD COLUMN IF NOT EXISTS observacao TEXT;

COMMIT;
