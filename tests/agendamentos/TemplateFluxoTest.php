<?php
// tests/agendamentos/TemplateFluxoTest.php
// 04/11/2026: fluxo da confirmacao na listagem e no formulario de templates: quadro com o caminho de cada resposta, coluna de
// vinculo, avisos do que falta, mensagem criada a partir do fluxo. Nao envia nada: so le e grava templates com a marca.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_FLUXO = 'TESTE FLUXO';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string { ob_start(); $f(); return ob_get_clean(); }

function limparFluxo(): void
{
    x("UPDATE template_escritorio SET template_confirmado_id = NULL, template_cancelado_id = NULL, template_lembrete_id = NULL WHERE descricao LIKE '" . MARCA_FLUXO . "%'");
    x("DELETE FROM template_escritorio WHERE descricao LIKE '" . MARCA_FLUXO . "%'");
}
limparFluxo();

$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
$escritorio = (int) q("SELECT id FROM escritorio WHERE system_unit_id = {$unidade} ORDER BY id LIMIT 1")[0]['id'];
TSession::setValue('userid', 1);
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);

$template = function (string $nome, string $categoria, array $campos = [], string $tipo = 'WHATSAPP') use ($escritorio) {
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare("INSERT INTO template_escritorio (escritorio_id, chave, descricao, habilitado, template, titulo, tipo_template, categoria) VALUES (?, ?, ?, 'T', 'Texto', 'T', ?, ?) RETURNING id");
    $st->execute([$escritorio, 'TESTE_FLUXO_' . uniqid(), MARCA_FLUXO . ' ' . $nome, $tipo, $categoria]);
    $id = (int) $st->fetchColumn();
    foreach ($campos as $c => $v) { TTransaction::get()->prepare("UPDATE template_escritorio SET {$c} = ? WHERE id = ?")->execute([$v, $id]); }
    TTransaction::close();
    return $id;
};
$comTransacao = function (callable $f) { TTransaction::open('escritorio'); $r = $f(); TTransaction::close(); return $r; };
$vinculo = function (int $id) use ($comTransacao) { return $comTransacao(function () use ($id) { return TemplateFluxoService::vinculoDaLista(TemplateEscritorio::find($id)); }); };
$painel = function () use ($comTransacao, $escritorio) { return $comTransacao(function () use ($escritorio) { return TemplateFluxoService::painel($escritorio); }); };
$salvar = function (array $campos) {
    $_POST = $campos + ['id' => '', 'escritorio_id' => '', 'chave' => '', 'fluxo_pai_id' => '', 'fluxo_ramo' => '', 'titulo' => '', 'habilitado' => 'T', 'template' => '', 'template_whatsapp' => '',
                        'respostas_confirmar' => '', 'respostas_cancelar' => '', 'template_confirmado_id' => '', 'template_cancelado_id' => '', 'template_lembrete_id' => '',
                        'template_acao_template_escritorio_id' => [''], 'template_acao_template_escritorio___row__id' => [''], 'template_acao_template_escritorio___row__data' => [''],
                        'template_acao_template_escritorio_url' => [''], 'template_acao_template_escritorio_label' => ['']];
    $s = saida(function () { $f = new TemplateEscritorioForm([]); $f->onSave(['static' => 1]); });
    $_POST = [];
    return $s;
};

try {
    // ---------- fluxo completo ----------
    $sim = $template('SIM', 'CONFIRMADO');
    $nao = $template('NAO', 'CANCELAMENTO');
    $lembrete = $template('LEMBRETE', 'LEMBRETE');
    $conf = $template('CONFIRMACAO', 'CONFIRMACAO', ['respostas_confirmar' => "Vou\nsim", 'respostas_cancelar' => "<nao>", 'template_confirmado_id' => $sim, 'template_cancelado_id' => $nao, 'template_lembrete_id' => $lembrete]);

    $h = $painel();
    $inicio = strpos($h, MARCA_FLUXO . ' CONFIRMACAO');
    ok('painel: mostra a confirmacao', $inicio !== false);
    $quadro = substr($h, $inicio);
    ok('painel: respostas do confirmar', strpos($quadro, '>Vou</span>') !== false && strpos($quadro, '>sim</span>') !== false);
    ok('painel: resposta escapada', strpos($quadro, '&lt;nao&gt;') !== false && strpos($quadro, '<nao>') === false);
    ok('painel: confirmar leva a mensagem certa', strpos($quadro, 'Fica Confirmado') < strpos($quadro, MARCA_FLUXO . ' SIM') && strpos($quadro, MARCA_FLUXO . ' SIM') < strpos($quadro, 'Fica Cancelado'));
    ok('painel: cancelar leva a mensagem certa', strpos($quadro, 'Fica Cancelado') < strpos($quadro, MARCA_FLUXO . ' NAO') && strpos($quadro, MARCA_FLUXO . ' NAO') < strpos($quadro, 'Qualquer outra resposta'));
    ok('painel: outra resposta nao muda', strpos($quadro, 'a equipe resolve no APChat') !== false);
    ok('painel: lembrete nao promete envio automatico', strpos($quadro, MARCA_FLUXO . ' LEMBRETE') !== false && strpos($quadro, 'Só sai quando alguém envia ou agenda') !== false);
    ok('painel: mensagem abre para editar', strpos($quadro, "method=onEdit&amp;key={$sim}") !== false);

    igual('lista: confirmacao mostra os dois caminhos', '<b>Vou / sim</b> → ' . MARCA_FLUXO . ' SIM<br><b>&lt;nao&gt;</b> → ' . MARCA_FLUXO . ' NAO', $vinculo($conf));
    igual('lista: resposta ao sim', 'Resposta ao <b>Vou / sim</b> de ' . MARCA_FLUXO . ' CONFIRMACAO', $vinculo($sim));
    igual('lista: resposta ao nao', 'Resposta ao <b>&lt;nao&gt;</b> de ' . MARCA_FLUXO . ' CONFIRMACAO', $vinculo($nao));
    igual('lista: lembrete', 'Lembrete de ' . MARCA_FLUXO . ' CONFIRMACAO', $vinculo($lembrete));

    $solta = $template('SOLTA', 'OUTROS');
    ok('lista: fora do fluxo', strpos($vinculo($solta), 'Sem vínculo') !== false);
    $email = $template('EMAIL', 'CONFIRMACAO', [], 'EMAIL');
    ok('lista: e-mail responde pelos links', strpos($vinculo($email), 'links do e-mail') !== false);
    ok('painel: e-mail nao vira fluxo', strpos($painel(), MARCA_FLUXO . ' EMAIL') === false);

    // ---------- o que falta aparece no quadro ----------
    x("UPDATE template_escritorio SET habilitado = 'F' WHERE id = {$sim}");
    ok('painel: mensagem desabilitada avisa', strpos($painel(), 'desabilitada: não é enviada') !== false);
    $vazia = $template('VAZIA', 'CONFIRMACAO');
    $quadro = substr($painel(), strpos($painel(), MARCA_FLUXO . ' VAZIA'));
    ok('painel: sem respostas avisa', strpos($quadro, 'Nenhuma resposta definida') !== false);
    ok('painel: sem mensagem oferece criar, em cada ponto', strpos($quadro, "fluxo_pai={$vazia}&amp;fluxo_ramo=confirmado") !== false && strpos($quadro, "fluxo_pai={$vazia}&amp;fluxo_ramo=cancelado") !== false && strpos($quadro, "fluxo_pai={$vazia}&amp;fluxo_ramo=lembrete") !== false);

    // ---------- formularios ----------
    $form = saida(function () use ($sim) { $f = new TemplateEscritorioForm(['key' => $sim]); $f->onEdit(['key' => $sim]); $f->show(); });
    ok('form da resposta: diz onde entra no fluxo', strpos($form, 'Enviada quando o cliente responde') !== false && strpos($form, 'Vou / sim') !== false);
    $form = saida(function () use ($conf) { $f = new TemplateEscritorioForm(['key' => $conf]); $f->onEdit(['key' => $conf]); $f->show(); });
    ok('form da confirmacao: cada resposta com a mensagem', strpos($form, 'Se o cliente confirmar') !== false && strpos($form, 'Se o cliente cancelar') !== false && strpos($form, 'Qualquer outra resposta') !== false);
    preg_match('~name="template_confirmado_id".*?</select>~su', $form, $m);
    ok('form: combo da mensagem so com a categoria', strpos($m[0] ?? '', MARCA_FLUXO . ' SIM') !== false && strpos($m[0] ?? '', MARCA_FLUXO . ' NAO') === false && strpos($m[0] ?? '', MARCA_FLUXO . ' SOLTA') === false);

    // a que ja esta ligada fica na lista mesmo depois de mudar de categoria (senao salvar a confirmacao perderia o vinculo)
    x("UPDATE template_escritorio SET categoria = 'OUTROS' WHERE id = {$sim}");
    $form = saida(function () use ($conf) { $f = new TemplateEscritorioForm(['key' => $conf]); $f->onEdit(['key' => $conf]); $f->show(); });
    preg_match('~name="template_confirmado_id".*?</select>~su', $form, $m);
    ok('form: ligada continua na lista', strpos($m[0] ?? '', MARCA_FLUXO . ' SIM') !== false);
    x("UPDATE template_escritorio SET categoria = 'CONFIRMADO' WHERE id = {$sim}");

    $form = saida(function () { $f = new TemplateEscritorioForm(['fluxo_novo' => 1]); $f->onShow(['fluxo_novo' => 1]); $f->show(); });
    ok('novo fluxo: ja abre com as respostas padrao', preg_match('~name="respostas_confirmar"[^>]*>sim<~', $form) === 1);
    ok('novo fluxo: abre por WhatsApp', preg_match('~value="WHATSAPP"[^>]*selected~', $form) === 1);

    $form = saida(function () use ($vazia) { $p = ['fluxo_pai' => $vazia, 'fluxo_ramo' => 'cancelado']; $f = new TemplateEscritorioForm($p); $f->onShow($p); $f->show(); });
    ok('mensagem pelo fluxo: diz de qual confirmacao e', strpos($form, 'à confirmação <b>' . MARCA_FLUXO . ' VAZIA') !== false);
    ok('mensagem pelo fluxo: guarda o vinculo no formulario', strpos($form, 'value="cancelado"') !== false);

    // ---------- salvar pela tela liga ao fluxo ----------
    $s = $salvar(['categoria' => 'OUTROS', 'tipo_template' => 'EMAIL', 'descricao' => MARCA_FLUXO . ' CRIADA', 'template_whatsapp' => 'Cancelado!', 'fluxo_pai_id' => $vazia, 'fluxo_ramo' => 'cancelado']);
    $criada = q("SELECT id, categoria, tipo_template, template FROM template_escritorio WHERE descricao = '" . MARCA_FLUXO . " CRIADA'")[0] ?? null;
    ok('salvar: mensagem criada', $criada !== null, substr($s, 0, 300));
    igual('salvar: categoria do ponto do fluxo', 'CANCELAMENTO', $criada['categoria'] ?? null);
    igual('salvar: canal WhatsApp', 'WHATSAPP', $criada['tipo_template'] ?? null);
    igual('salvar: texto', 'Cancelado!', $criada['template'] ?? null);
    igual('salvar: ligada a confirmacao', (int) ($criada['id'] ?? 0), (int) q("SELECT template_cancelado_id c FROM template_escritorio WHERE id = {$vazia}")[0]['c']);

    // ---------- so liga em confirmacao por WhatsApp do mesmo escritorio ----------
    $erro = function (int $pai) use ($solta, $comTransacao) {
        TTransaction::open('escritorio');
        try { TemplateFluxoService::ligar($pai, 'confirmado', TemplateEscritorio::find($solta)); TTransaction::close(); return null; }
        catch (Exception $e) { TTransaction::rollback(); return $e->getMessage(); }
    };
    ok('ligar: recusa template que nao e confirmacao', $erro($sim) !== null);
    ok('ligar: recusa e-mail', $erro($email) !== null);

    // ---------- tela da listagem ----------
    $lista = saida(function () { $p = new TemplateEscritorioHeaderList([]); $p->onReload([]); $p->show(); });
    ok('listagem: painel e coluna', strpos($lista, 'Fluxo da confirmação por WhatsApp') !== false && strpos($lista, 'Vínculo no fluxo') !== false && strpos($lista, 'Novo fluxo') !== false);
    igual('listagem: um painel so', 1, substr_count($lista, 'Fluxo da confirmação por WhatsApp'));
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    $_POST = [];
    limparFluxo();
}

fim();
