<?php
// tests/email/TemplateEmailFormTest.php
// Template de e-mail (09/11/2026): assinatura (padrao do escritorio, outra ou nenhuma), anexos, botoes da confirmacao e a
// previa do formulario. Transacao desfeita; arquivos criados sao apagados.
require __DIR__ . '/../bootstrap.php';

const PNG_1X1 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

@mkdir('tmp', 0775, true);
$tmpCriados = [];
$enviado = function (string $nome, string $conteudo) use (&$tmpCriados): string { $n = uniqid() . '_' . $nome; file_put_contents("tmp/{$n}", $conteudo); $tmpCriados[] = "tmp/{$n}"; return $n; };
$antesAss = glob('assinaturas/*') ?: [];
$anexosCriados = [];

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
try
{
    $c = TTransaction::get();
    $esc = $c->query('SELECT id, system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    TSession::setValue('userunitid', (int) $esc['system_unit_id']);

    $novoTemplate = function (string $tipo, string $categoria, string $texto) use ($esc) {
        $t = new TemplateEscritorio();
        $t->escritorio_id = (int) $esc['id'];
        $t->chave = 'TESTE_' . strtoupper(uniqid());
        $t->descricao = 'TESTE TEMPLATE EMAIL';
        $t->habilitado = 'T';
        $t->titulo = 'TESTE';
        $t->template = $texto;
        $t->tipo_template = $tipo;
        $t->categoria = $categoria;
        $t->store();
        return $t;
    };
    $anexosDe = fn(int $id) => array_map('intval', array_column($c->query("SELECT email_anexo_id FROM template_escritorio_anexo WHERE template_escritorio_id = {$id} ORDER BY ordem, id")->fetchAll(PDO::FETCH_ASSOC), 'email_anexo_id'));

    // ---------- assinatura ----------
    $t = $novoTemplate('EMAIL', 'CONFIRMACAO', '<p>Olá {$cliente}</p>');
    TemplateEmailService::salvarExtras($t, ['assinatura_modo' => 'OUTRA', 'assinatura_arquivo' => $enviado('outra.png', base64_decode(PNG_1X1))]);
    ok('salvar com OUTRA + arquivo grava assinatura_id', (int) TemplateEscritorio::find($t->id)->assinatura_id > 0);
    igual('modo OUTRA gravado', 'OUTRA', TemplateEscritorio::find($t->id)->assinatura_modo);
    $outra = (int) TemplateEscritorio::find($t->id)->assinatura_id;

    TemplateEmailService::salvarExtras($t, ['assinatura_modo' => 'OUTRA', 'assinatura_arquivo' => '']);
    igual('OUTRA sem arquivo novo mantem a anterior', $outra, (int) TemplateEscritorio::find($t->id)->assinatura_id);

    // como no onSave: objeto montado so com os campos do formulario (sem assinatura_id)
    $doForm = new TemplateEscritorio();
    $doForm->fromArray(['id' => $t->id, 'escritorio_id' => $t->escritorio_id, 'chave' => $t->chave, 'descricao' => $t->descricao, 'habilitado' => 'T',
                        'titulo' => 'TESTE', 'template' => $t->template, 'tipo_template' => 'EMAIL', 'categoria' => 'CONFIRMACAO', 'assinatura_modo' => 'OUTRA']);
    TemplateEmailService::salvarExtras($doForm, ['assinatura_modo' => 'OUTRA', 'assinatura_arquivo' => '']);
    igual('objeto do formulario: OUTRA sem arquivo novo mantem a gravada', $outra, (int) TemplateEscritorio::find($t->id)->assinatura_id);

    $t2 = $novoTemplate('EMAIL', 'LEMBRETE', '<p>x</p>');
    lanca('OUTRA sem arquivo e sem assinatura anterior: erro', fn() => TemplateEmailService::salvarExtras($t2, ['assinatura_modo' => 'OUTRA']), 'Escolha o arquivo da assinatura');

    TemplateEmailService::salvarExtras($t, ['assinatura_modo' => 'NENHUMA']);
    igual('NENHUMA zera assinatura_id', null, TemplateEscritorio::find($t->id)->assinatura_id);
    igual('NENHUMA gravado', 'NENHUMA', TemplateEscritorio::find($t->id)->assinatura_modo);
    TemplateEmailService::salvarExtras($t, ['assinatura_modo' => 'PADRAO']);
    igual('PADRAO gravado', 'PADRAO', TemplateEscritorio::find($t->id)->assinatura_modo);
    TemplateEmailService::salvarExtras($t, ['assinatura_modo' => 'X']);
    igual('modo invalido vira PADRAO', 'PADRAO', TemplateEscritorio::find($t->id)->assinatura_modo);

    // ---------- anexos ----------
    TemplateEmailService::salvarExtras($t, ['anexos_novos' => [$enviado('a.pdf', '%PDF a'), $enviado('b.pdf', '%PDF b')]]);
    $dois = $anexosDe((int) $t->id);
    igual('anexos novos ligados ao template', 2, count($dois));
    foreach ($dois as $id) { $anexosCriados[] = EmailAnexo::find($id)->caminho; }
    TemplateEmailService::salvarExtras($t, ['anexos_manter' => [$dois[1]], 'anexos_novos' => [$enviado('c.pdf', '%PDF c')]]);
    $agora = $anexosDe((int) $t->id);
    ok('anexo desmarcado desligado e novo ligado', count($agora) === 2 && !in_array($dois[0], $agora, true) && in_array($dois[1], $agora, true), json_encode($agora));
    foreach ($agora as $id) { $anexosCriados[] = EmailAnexo::find($id)->caminho; }
    ok('o email_anexo desligado continua (historico)', (bool) EmailAnexo::find($dois[0]));
    TemplateEmailService::salvarExtras($t, ['anexos_manter' => []]);
    igual('desmarcar todos desliga todos', [], $anexosDe((int) $t->id));

    $grandes = [];
    foreach ([1, 2] as $i) { $grandes[] = (int) $c->query("INSERT INTO email_anexo (nome_original, caminho, tamanho) VALUES ('g{$i}.pdf', 'x', " . (11 * 1024 * 1024) . ') RETURNING id')->fetchColumn(); }
    foreach ($grandes as $i => $g) { $c->exec("INSERT INTO template_escritorio_anexo (template_escritorio_id, email_anexo_id, ordem) VALUES ({$t->id}, {$g}, {$i})"); }
    lanca('anexos do template acima de 20 MB: erro', fn() => TemplateEmailService::salvarExtras($t, ['anexos_manter' => $grandes]), '20 MB');
    $c->exec("DELETE FROM template_escritorio_anexo WHERE template_escritorio_id = {$t->id}");

    // ---------- botoes ----------
    TemplateEmailService::salvarExtras($t, ['botao_confirmar_texto' => '  Vou sim ', 'botao_confirmar_cor' => '#ABCDEF', 'botao_cancelar_texto' => '', 'botao_cancelar_cor' => 'azul']);
    $g = TemplateEscritorio::find($t->id);
    igual('texto do botao gravado sem espacos', 'Vou sim', $g->botao_confirmar_texto);
    igual('cor gravada em minusculas', '#abcdef', $g->botao_confirmar_cor);
    igual('texto vazio volta ao padrao', 'Cancelar agendamento', $g->botao_cancelar_texto);
    igual('cor invalida volta ao padrao', '#c62828', $g->botao_cancelar_cor);

    // ---------- WhatsApp nao guarda nada disso ----------
    $w = $novoTemplate('WHATSAPP', 'LEMBRETE', 'Oi');
    TemplateEmailService::salvarExtras($w, ['assinatura_modo' => 'OUTRA', 'assinatura_arquivo' => $enviado('w.png', base64_decode(PNG_1X1)), 'anexos_novos' => [$enviado('w.pdf', '%PDF')]]);
    $gw = TemplateEscritorio::find($w->id);
    ok('template WhatsApp nao guarda assinatura, anexos nem botoes', $gw->assinatura_id === null && $gw->assinatura_modo === 'PADRAO' && $anexosDe((int) $w->id) === [] && $gw->botao_confirmar_texto === null);

    // ---------- previa ----------
    $base = ['tipo_template' => 'EMAIL', 'categoria' => 'CONFIRMACAO', 'titulo' => 'Confirme {$cliente}', 'template' => '<p>Olá {$cliente}</p><p>{$botoes}</p>',
             'botao_confirmar_texto' => 'Vou sim', 'botao_confirmar_cor' => '#2e7d32', 'botao_cancelar_texto' => 'Não vou', 'botao_cancelar_cor' => '#c62828',
             'assinatura_modo' => 'NENHUMA', 'id' => $t->id];
    $p = TemplateEmailService::previa($base);
    ok('previa: variavel destacada', preg_match('#<span[^>]*>\{\$cliente\}</span>#', $p) === 1, $p);
    ok('previa: botoes', strpos($p, 'Vou sim') !== false && strpos($p, 'Não vou') !== false);
    ok('previa: botoes sem link de verdade', strpos($p, 'href="#"') !== false && strpos($p, '{$link_') === false, $p);
    ok('previa: variavel nao destacada dentro de atributo', strpos($p, 'href="<span') === false);
    ok('previa: assunto', strpos($p, 'Confirme') !== false);

    // assinatura do escritorio na previa (PADRAO)
    $cfg = EmailConfig::where('escritorio_id', '=', (int) $esc['id'])->first();
    $ass = EmailAssinaturaService::criarDeArquivo('tmp/' . $enviado('esc.png', base64_decode(PNG_1X1)), 'esc.png');
    if ($cfg) { $cfg->assinatura_id = $ass->id; $cfg->store(); }
    preg_match('#assinaturas/[0-9a-f]{40}\.png#', $ass->html, $mEsc);
    $pp = TemplateEmailService::previa(['assinatura_modo' => 'PADRAO'] + $base);
    ok('previa PADRAO mostra a assinatura do escritorio', isset($mEsc[0]) && strpos($pp, $mEsc[0]) !== false, $pp);
    $po = TemplateEmailService::previa(['assinatura_modo' => 'OUTRA'] + $base);
    ok('previa OUTRA sem arquivo salvo avisa', strpos($po, 'Escolha o arquivo da assinatura') !== false || strpos($po, 'assinaturas/') === false);
    $pn = TemplateEmailService::previa(['assinatura_modo' => 'OUTRA', 'assinatura_arquivo' => $enviado('nova.png', base64_decode(PNG_1X1))] + $base);
    ok('previa OUTRA com arquivo novo: aviso de que aparece depois de salvar', strpos($pn, 'depois de salvar') !== false, $pn);
    if ($cfg) { $cfg->assinatura_id = null; $cfg->store(); }
    $ps = TemplateEmailService::previa(['assinatura_modo' => 'PADRAO'] + $base);
    ok('previa PADRAO sem assinatura no escritorio: aviso', strpos($ps, 'O escritório ainda não tem assinatura') !== false, $ps);

    // anexos na previa: mantidos e novos
    $an = EmailAnexoService::criarDeArquivo('tmp/' . $enviado('proc.pdf', '%PDF'), 'Procuração.pdf');
    $anexosCriados[] = $an->caminho;
    $pa = TemplateEmailService::previa(['anexos_manter' => [(int) $an->id], 'anexos_novos' => [$enviado('novo.pdf', '%PDF')]] + $base);
    ok('previa lista anexo mantido', strpos($pa, 'Procuração.pdf') !== false);
    ok('previa lista anexo novo', strpos($pa, 'novo.pdf') !== false, $pa);

    // link feito no editor (ou template antigo): vira o botao estilizado, sem aviso
    $antigo = ['template' => '<p><a href="{$link_confirmacao}">Confirmar</a></p>'] + $base;
    $pl = TemplateEmailService::previa($antigo);
    ok('previa: link do editor vira o botao de confirmar', strpos($pl, 'Vou sim') !== false && strpos($pl, 'Não vou') === false, $pl);
    ok('previa: sem o aviso antigo dos links no texto', strpos($pl, 'links no texto') === false, $pl);

    // um por um, sem destaque amarelo em volta das variaveis dos botoes
    $um = TemplateEmailService::previa(['template' => '<p>Cancelar: {$link_cancelamento}</p>'] + $base);
    ok('previa: {$link_cancelamento} sozinho vira o botao de cancelar', strpos($um, 'Não vou') !== false && strpos($um, 'Vou sim') === false, $um);
    ok('previa: variavel do botao nao fica destacada', strpos($um, '>{$link_cancelamento}</span>') === false, $um);

    // confirmacao por e-mail sem nenhum botao: avisa (o cliente nao teria como responder)
    $sem = TemplateEmailService::previa(['template' => '<p>Olá {$cliente}</p>'] + $base);
    ok('previa: confirmacao sem botoes avisa', strpos($sem, 'não tem os botões') !== false, $sem);
    ok('previa: confirmacao sem botoes nao inventa botao', strpos($sem, 'Vou sim') === false, $sem);
    $lemb = TemplateEmailService::previa(['categoria' => 'LEMBRETE', 'template' => '<p>Olá</p>'] + $base);
    ok('previa: lembrete sem botoes nao avisa', strpos($lemb, 'não tem os botões') === false, $lemb);

    $pw = TemplateEmailService::previa(['tipo_template' => 'WHATSAPP'] + $base);
    igual('previa de WhatsApp vazia', '', $pw);
}
catch (Throwable $e)
{
    ok('sem excecao inesperada', false, $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
}
finally
{
    TTransaction::rollback();
    foreach ($tmpCriados as $f) { @unlink($f); }
    foreach ($anexosCriados as $f) { @unlink($f); @rmdir(dirname($f)); }
    foreach (array_diff(glob('assinaturas/*') ?: [], $antesAss) as $f) { @unlink($f); }
}

fim();
