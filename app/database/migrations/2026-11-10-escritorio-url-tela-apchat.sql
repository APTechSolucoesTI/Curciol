-- 10/11/2026: endereco da tela do APChat (botoes "Abrir no APChat" e balao do topo). Vazio = https://app.apchat.com.br. Pode rodar de novo.
ALTER TABLE escritorio ADD COLUMN IF NOT EXISTS url_tela_apchat text;
COMMENT ON COLUMN escritorio.url_tela_apchat IS 'Endereco da tela do APChat (vazio = https://app.apchat.com.br)';
