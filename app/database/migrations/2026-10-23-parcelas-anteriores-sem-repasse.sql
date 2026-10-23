-- Regra da publicacao em producao (23/10/2026): o que ja estava no sistema continua igual.
-- Toda parcela de conta a receber que ja existe quando esta atualizacao roda fica marcada como anterior:
-- quitar uma parcela anterior nunca gera repasse aos colaboradores (mesmo com colaboradores gravados nela),
-- e o "Gerar financeiro" do contrato ou do pagamento da requisicao fica bloqueado enquanto o contrato tiver parcela
-- anterior em aberto. Parcelas criadas depois (Gerar financeiro ou lancadas a mao) geram repasse normalmente.
-- Marca so na primeira vez (quando cria a coluna): rodar de novo nao marca as parcelas novas.
BEGIN;

DO $$
DECLARE n int;
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'lancamento' AND column_name = 'anterior_publicacao') THEN
        ALTER TABLE lancamento ADD COLUMN anterior_publicacao BOOLEAN NOT NULL DEFAULT false;
        UPDATE lancamento l SET anterior_publicacao = true
          FROM conta c WHERE c.id = l.conta_id AND c.tipo_conta_id IN (1, 3);
        GET DIAGNOSTICS n = ROW_COUNT;
        RAISE NOTICE 'parcelas a receber marcadas como anteriores a publicacao: %', n;
    ELSE
        RAISE NOTICE 'coluna lancamento.anterior_publicacao ja existe: nada marcado de novo';
    END IF;
END $$;

COMMIT;
