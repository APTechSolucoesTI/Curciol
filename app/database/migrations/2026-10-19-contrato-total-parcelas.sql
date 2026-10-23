-- Contas a receber geradas pelo contrato: o Gerar financeiro nao atualizava o total de parcelas
-- (ficava 1 com 2 ou mais parcelas e o historico saia "PARCELA 2/1"). Reconta pelas parcelas nao canceladas.
-- So mexe em contas de contrato (documento "Contrato") com o total diferente. Idempotente.
BEGIN;

DO $$
DECLARE n int;
BEGIN
    SELECT COUNT(*) INTO n
      FROM conta c
     WHERE c.contrato_id IS NOT NULL AND c.tipo_conta_id = 1
       AND c.tipo_documento_financeiro_id IN (SELECT id FROM tipo_documento_financeiro WHERE nome = 'Contrato')
       AND COALESCE(c.total_parcelas, 0) <> (SELECT COUNT(*) FROM lancamento l WHERE l.conta_id = c.id AND COALESCE(l.cancelado, 'N') <> 'S');
    RAISE NOTICE 'contas de contrato com total de parcelas errado: %', n;
END $$;

UPDATE conta c
   SET total_parcelas = (SELECT COUNT(*) FROM lancamento l WHERE l.conta_id = c.id AND COALESCE(l.cancelado, 'N') <> 'S')
 WHERE c.contrato_id IS NOT NULL AND c.tipo_conta_id = 1
   AND c.tipo_documento_financeiro_id IN (SELECT id FROM tipo_documento_financeiro WHERE nome = 'Contrato')
   AND COALESCE(c.total_parcelas, 0) <> (SELECT COUNT(*) FROM lancamento l WHERE l.conta_id = c.id AND COALESCE(l.cancelado, 'N') <> 'S')
   AND EXISTS (SELECT 1 FROM lancamento l WHERE l.conta_id = c.id);

COMMIT;
