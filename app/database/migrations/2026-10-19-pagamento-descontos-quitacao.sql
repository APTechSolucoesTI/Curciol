-- Descontos do deposito informados ao lancar o pagamento (o MLE passa a ser bruto - descontos) e o status anterior
-- guardado quando a requisicao-cliente vai para Quitado (para voltar a ele se a conta a receber for desquitada).
-- Idempotente. Depende de 2026-10-14-levantamento-descontos.sql.
BEGIN;

ALTER TABLE requisicao_pagamento_etapa3 ADD COLUMN IF NOT EXISTS desconto_previdenciario NUMERIC(14,2);
ALTER TABLE requisicao_pagamento_etapa3 ADD COLUMN IF NOT EXISTS desconto_assistencial NUMERIC(14,2);
ALTER TABLE requisicao_pagamento_etapa3 ADD COLUMN IF NOT EXISTS desconto_irrf NUMERIC(14,2);

ALTER TABLE requisicao_pagamento_cliente ADD COLUMN IF NOT EXISTS status_antes_quitado_id INTEGER REFERENCES status_requisicao_pagamento (id);

-- pagamentos que ja tem financeiro gerado com os descontos detalhados: os descontos passam para o pagamento
UPDATE requisicao_pagamento_etapa3 e3
   SET desconto_previdenciario = lf.desconto_previdenciario,
       desconto_assistencial   = lf.desconto_assistencial,
       desconto_irrf           = lf.desconto_irrf
  FROM levantamento_financeiro lf
 WHERE lf.requisicao_pagamento_etapa3_id = e3.id
   AND lf.desconto_previdenciario IS NOT NULL
   AND e3.desconto_previdenciario IS NULL;

COMMIT;

-- ROLLBACK (manual):
-- ALTER TABLE requisicao_pagamento_etapa3 DROP COLUMN desconto_previdenciario, DROP COLUMN desconto_assistencial, DROP COLUMN desconto_irrf;
-- ALTER TABLE requisicao_pagamento_cliente DROP COLUMN status_antes_quitado_id;
