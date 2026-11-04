-- Sem link de reuniao (04/11/2026): o sistema nao gera nem envia mais link de atendimento online (Jitsi). Continuam a marca
-- online do agendamento (agendamento.online) e a opcao da agenda (agenda.aceita_agendamento_online). Rodar depois do codigo.
-- Pode rodar mais de uma vez.
ALTER TABLE agendamento DROP COLUMN IF EXISTS link_atendimento_online;
