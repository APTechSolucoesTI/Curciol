-- CPF/CNPJ das pessoas gravado so com numeros (pedido do escritorio, 22/10/2026).
-- Daqui em diante o modelo Pessoa ja grava so os numeros (Pessoa::onBeforeStore); esta atualizacao acerta os cadastros
-- antigos gravados com pontos, tracos e barras. As listas e os documentos ja formatam o CPF/CNPJ a partir dos numeros.
-- Pode rodar mais de uma vez: so mexe em quem ainda tem algum caractere que nao e numero.

UPDATE pessoa
   SET cpf_cnpj = regexp_replace(cpf_cnpj, '\D', '', 'g')
 WHERE cpf_cnpj ~ '\D';
