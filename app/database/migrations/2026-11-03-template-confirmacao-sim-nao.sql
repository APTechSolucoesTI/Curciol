-- WhatsApp de confirmacao do agendamento sem links (03/11/2026): o cliente responde SIM ou NAO na conversa.
-- As acoes (botoes de link da Z-API) nao vao mais na mensagem. Troca so o texto padrao de fabrica do template
-- (terminava em "Deseja:", completado pelos links); texto ja editado pelo escritorio fica como esta.
-- Pode rodar mais de uma vez.
BEGIN;

UPDATE template_escritorio
   SET template = E'Olá {$cliente}. Temos agendado para {$data_inicial}, com {$profissional}.\n\nResponda *SIM* para confirmar ou *NÃO* para cancelar.',
       data_modificacao = now()
 WHERE chave = 'WHATSAPP_CONFIRMACAO_AGENDAMENTO'
   AND btrim(template) IN ('Olá {$cliente}. Temos agendado para {$data_inicial}, com {$profissional}. Deseja:',
                           'Olá {$cliente}. Temos uma agendamento marcado para {$data_inicial}, com {$profissional}. Deseja:');

COMMIT;
