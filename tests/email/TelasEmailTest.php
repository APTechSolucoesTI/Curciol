<?php
// tests/email/TelasEmailTest.php
// Telas do e-mail (09/11/2026, 2a rodada): assunto com esse nome, sem {$link_detalhe}, texto com altura automatica, previa do
// template e Visualizar do envio que abrem e fecham (com Atualizar), "Botoes do cliente" so com os links no texto.
// So monta as telas (nada e gravado).
require __DIR__ . '/../bootstrap.php';

function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }
function tag(string $html, string $nome, string $tag = 'textarea'): string { return preg_match('/<' . $tag . '\b(?=[^>]*\bname="' . preg_quote($nome, '/') . '")[^>]*>/', $html, $m) ? $m[0] : ''; }

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$esc = TTransaction::get()->query('SELECT id, system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
$agendamento = (int) TTransaction::get()->query('SELECT id FROM agendamento ORDER BY id DESC LIMIT 1')->fetchColumn();
TTransaction::close();
TSession::setValue('userunitid', (int) $esc['system_unit_id']);

// ---------- script da altura automatica ----------
ok('script da altura automatica existe', is_file('app/lib/include/curciol-autoaltura.js'));
ok('script carregado pelo tema', strpos((string) file_get_contents('app/templates/theme-builder/libraries_theme.html'), 'app/lib/include/curciol-autoaltura.js') !== false);

// ---------- formulario do template ----------
$t = saida(function () { $f = new TemplateEscritorioForm([]); $f->onShow([]); $f->show(); });
ok('template: monta', strpos($t, 'form_TemplateClinicaForm') !== false, substr($t, 0, 300));
ok('template: sem {$link_detalhe}', strpos($t, 'link_detalhe') === false);
ok('template: campo chamado Assunto do e-mail', strpos($t, 'Assunto do e-mail') !== false);
// 18/11/2026: Categoria, Nome, Tipo; embaixo a Descricao livre; WhatsApp com rodape e ate 3 botoes
ok('template: campo Nome e a Descricao livre', strpos($t, '>Nome:') !== false && strpos($t, 'name="observacao"') !== false && strpos($t, 'nome do template na lista') === false);
ok('template: rodape e 3 botoes do WhatsApp', strpos($t, 'name="rodape"') !== false && strpos($t, 'name="botao1"') !== false && strpos($t, 'name="botao3"') !== false && strpos($t, 'name="botao4"') === false);
ok('template: botoes com ate 20 caracteres e rodape com ate 60', (bool) preg_match('/name="botao1"[^>]*maxlength="20"|maxlength="20"[^>]*name="botao1"/', $t) && (bool) preg_match('/name="rodape"[^>]*maxlength="60"|maxlength="60"[^>]*name="rodape"/', $t));
ok('template: previa vale para o WhatsApp tambem', strpos($t, 'Prévia do WhatsApp') !== false);
$titulo = tag($t, 'titulo', 'input');
ok('template: assunto sem maiusculas forcadas (variaveis como {$cliente} continuam valendo)', $titulo !== '' && stripos($titulo, 'uppercase') === false, $titulo);
ok('template: texto do e-mail com altura automatica', strpos(tag($t, 'template'), 'data-autoaltura') !== false, tag($t, 'template'));
ok('template: texto do WhatsApp com altura automatica', strpos(tag($t, 'template_whatsapp'), 'data-autoaltura') !== false, tag($t, 'template_whatsapp'));
ok('template: previa comeca fechada', preg_match('/id="template_previa"[^>]*display:\s*none/', $t) === 1 || preg_match('/style="[^"]*display:\s*none[^"]*"[^>]*id="template_previa"/', $t) === 1);
ok('template: botao Mostrar previa', strpos($t, 'Mostrar prévia') !== false && strpos($t, 'curciolPreviaAlternar') !== false);
ok('template: botao Atualizar previa', strpos($t, 'Atualizar prévia') !== false);
ok('template: Botoes do cliente dependem dos links no texto', strpos($t, 'link_confirmacao') !== false && strpos($t, 'curciolTemBotoes') !== false);

// ---------- envio pelo agendamento ----------
$e = saida(function () use ($agendamento) { $f = new EnviarMensagemForm(['tipo' => 'EMAIL', 'agendamento_id' => $agendamento]); $f->show(); });
ok('envio: monta', strpos($e, 'form_EnviarMensagemForm') !== false, substr($e, 0, 300));
ok('envio: campo chamado Assunto', strpos($e, 'Assunto:') !== false && strpos($e, 'Título:') === false);
ok('envio: sem {$link_detalhe}', strpos($e, 'link_detalhe') === false);
ok('envio: texto do e-mail com altura automatica', strpos(tag($e, 'content_email'), 'data-autoaltura') !== false, tag($e, 'content_email'));
ok('envio: Visualizar abre e fecha', strpos($e, 'curciolVisualizarAlternar') !== false);
ok('envio: Atualizar a visualizacao', strpos($e, 'curciolVisualizarAtualizar') !== false && strpos($e, 'Atualizar') !== false);
ok('envio: visualizacao comeca fechada', preg_match('/id="mensagem_visualizacao"[^>]*display:\s*none/', $e) === 1 || preg_match('/style="[^"]*display:\s*none[^"]*"[^>]*id="mensagem_visualizacao"/', $e) === 1);

$w = saida(function () use ($agendamento) { $f = new EnviarMensagemForm(['tipo' => 'WHATSAPP', 'agendamento_id' => $agendamento]); $f->show(); });
ok('envio WhatsApp: texto com altura automatica', strpos(tag($w, 'content_whatsapp'), 'data-autoaltura') !== false, tag($w, 'content_whatsapp'));

fim();
