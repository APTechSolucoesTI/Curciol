-- Parcela anterior a publicacao, recebida em parte antes da liberacao do repasse (06/11/2026): guarda quanto entrou sem repasse.
-- Ao quitar, o repasse sai so sobre a proporcao do restante. Vazio nas demais parcelas. Pode rodar mais de uma vez.
BEGIN;
ALTER TABLE lancamento ADD COLUMN IF NOT EXISTS recebido_sem_repasse NUMERIC(14, 2);
COMMIT;
