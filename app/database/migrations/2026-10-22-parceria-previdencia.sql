-- Parceria "Previdência" (planilha Valores Pagos RPV-Precatório, 22/10/2026): percentuais fixos
-- CURCIOL SOCIEDADE DE ADVOGADOS 50% (recebe a diferença de centavos), KLEBER CURCIOL 25%, DANIELLE 25%.
-- Pode rodar mais de uma vez: não duplica a parceria nem os participantes.

INSERT INTO parceria_levantamento (nome, tipo, marco1, marco2, data_criacao) VALUES
 ('Previdência', 'FIXA', NULL, NULL, now())
ON CONFLICT (nome) DO NOTHING;

WITH carga (parceria, pessoa, nivel, pct, antes, depois, dif, ordem) AS (VALUES
 ('Previdência','CURCIOL SOCIEDADE DE ADVOGADOS','FIXO',50,NULL,NULL,'S',1),
 ('Previdência','KLEBER CURCIOL','FIXO',25,NULL,NULL,'N',2),
 ('Previdência','DANIELLE DOS SANTOS MARQUES CURCIOL','FIXO',25,NULL,NULL,'N',3)
)
INSERT INTO parceria_levantamento_participante (parceria_levantamento_id, pessoa_id, nivel, percentual, percentual_antes, percentual_depois, recebe_diferenca, ordem)
SELECT p.id, pe.id, c.nivel, c.pct, c.antes::numeric, c.depois::numeric, c.dif, c.ordem
  FROM carga c
  JOIN parceria_levantamento p ON p.nome = c.parceria
  -- pessoa por nome (cadastros antigos têm acento decomposto: compara em NFC)
  JOIN LATERAL (SELECT pp.id FROM pessoa pp
                 WHERE normalize(pp.nome, NFC) = normalize(c.pessoa, NFC)
                 ORDER BY pp.id LIMIT 1) pe ON TRUE
ON CONFLICT (parceria_levantamento_id, pessoa_id) DO NOTHING;
