-- 09/11/2026 (2a rodada do e-mail): sem a pagina de detalhe do agendamento e botoes so onde o usuario os poe.
-- Rodar uma vez em cada banco (homologacao e producao), depois do codigo publicado. Pode rodar de novo sem efeito.

BEGIN;

-- 1) {$link_detalhe} excluido: as acoes "Ver agendamento" e o link nos templates (o link inteiro, para nao ficar um botao
--    sem destino). O replace do sistema tambem tira o que sobrar.
DELETE FROM template_acao WHERE url = '{$link_detalhe}';

UPDATE template_escritorio
   SET template = replace(regexp_replace(template, '<a\s[^>]*href="\{\$link_detalhe\}"[^>]*>[^<]*</a>', '', 'gi'), '{$link_detalhe}', '')
 WHERE template LIKE '%{$link_detalhe}%';

UPDATE template_escritorio
   SET titulo = replace(titulo, '{$link_detalhe}', '')
 WHERE titulo LIKE '%{$link_detalhe}%';

-- 2) confirmacao por e-mail com os links feitos no editor (<a href="{$link_confirmacao}">...</a>): o texto e a cor do link
--    passam para os "Botoes do cliente" e o link vira so a variavel (o botao sai igual, agora tambem no Outlook)
UPDATE template_escritorio t
   SET botao_confirmar_texto = COALESCE(NULLIF(left(btrim(replace(replace(c.texto, '&nbsp;', ' '), '&amp;', '&')), 60), ''), t.botao_confirmar_texto),
       botao_confirmar_cor   = COALESCE(lower(c.cor), t.botao_confirmar_cor)
  FROM (SELECT id,
               (regexp_match(template, '<a\s[^>]*href="\{\$link_confirmacao\}"[^>]*>([^<]*)</a>', 'i'))[1] AS texto,
               COALESCE((regexp_match(template, '<a\s[^>]*background-color:\s*(#[0-9a-fA-F]{6})[^>]*href="\{\$link_confirmacao\}"', 'i'))[1],
                        (regexp_match(template, '<a\s[^>]*href="\{\$link_confirmacao\}"[^>]*background-color:\s*(#[0-9a-fA-F]{6})', 'i'))[1]) AS cor
          FROM template_escritorio) c
 WHERE c.id = t.id
   AND t.tipo_template = 'EMAIL' AND t.categoria = 'CONFIRMACAO'
   AND t.template ~* '<a\s[^>]*href="\{\$link_confirmacao\}"[^>]*>[^<]*</a>';

UPDATE template_escritorio t
   SET botao_cancelar_texto = COALESCE(NULLIF(left(btrim(replace(replace(c.texto, '&nbsp;', ' '), '&amp;', '&')), 60), ''), t.botao_cancelar_texto),
       botao_cancelar_cor   = COALESCE(lower(c.cor), t.botao_cancelar_cor)
  FROM (SELECT id,
               (regexp_match(template, '<a\s[^>]*href="\{\$link_cancelamento\}"[^>]*>([^<]*)</a>', 'i'))[1] AS texto,
               COALESCE((regexp_match(template, '<a\s[^>]*background-color:\s*(#[0-9a-fA-F]{6})[^>]*href="\{\$link_cancelamento\}"', 'i'))[1],
                        (regexp_match(template, '<a\s[^>]*href="\{\$link_cancelamento\}"[^>]*background-color:\s*(#[0-9a-fA-F]{6})', 'i'))[1]) AS cor
          FROM template_escritorio) c
 WHERE c.id = t.id
   AND t.tipo_template = 'EMAIL' AND t.categoria = 'CONFIRMACAO'
   AND t.template ~* '<a\s[^>]*href="\{\$link_cancelamento\}"[^>]*>[^<]*</a>';

UPDATE template_escritorio
   SET template = regexp_replace(template, '<a\s[^>]*href="(\{\$link_(confirmacao|cancelamento)\})"[^>]*>[^<]*</a>', '\1', 'gi')
 WHERE tipo_template = 'EMAIL' AND categoria = 'CONFIRMACAO'
   AND template ~* '<a\s[^>]*href="\{\$link_(confirmacao|cancelamento)\}"[^>]*>[^<]*</a>';

COMMIT;
