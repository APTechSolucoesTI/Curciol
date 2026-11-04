<?php
// tests/agendamentos/TemplateRespostaTest.php
// 04/11/2026: template de confirmacao por WhatsApp com duas respostas e um template obrigatorio para cada uma, da categoria
// certa; criar o template de resposta sem sair do formulario; o e-mail fica sem nada disso. Nao envia nada.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_RESP = 'TESTE RESPOSTA';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string { ob_start(); $f(); return ob_get_clean(); }

function limparResp(): void
{
    x("UPDATE template_escritorio SET template_confirmado_id = NULL, template_cancelado_id = NULL WHERE descricao LIKE '" . MARCA_RESP . "%'");
    x("DELETE FROM template_escritorio WHERE descricao LIKE '" . MARCA_RESP . "%'");
}
limparResp();

$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
$escritorio = (int) q("SELECT id FROM escritorio WHERE system_unit_id = {$unidade} ORDER BY id LIMIT 1")[0]['id'];
TSession::setValue('userid', 1);
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);

$template = function (string $nome, string $categoria, string $tipo = 'WHATSAPP', string $habilitado = 'T') use ($escritorio) {
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare("INSERT INTO template_escritorio (escritorio_id, chave, descricao, habilitado, template, titulo, tipo_template, categoria) VALUES (?, ?, ?, ?, 'Texto', 'T', ?, ?) RETURNING id");
    $st->execute([$escritorio, 'TESTE_RESP_' . uniqid(), MARCA_RESP . ' ' . $nome, $habilitado, $tipo, $categoria]);
    $id = (int) $st->fetchColumn();
    TTransaction::close();
    return $id;
};
$salvar = function (array $campos) {
    $_POST = $campos + ['id' => '', 'escritorio_id' => '', 'chave' => '', 'titulo' => '', 'habilitado' => 'T', 'template' => '', 'template_whatsapp' => 'Texto', 'ignorar_maiusculas' => 'T',
                        'respostas_confirmar' => '', 'respostas_cancelar' => '', 'template_confirmado_id' => '', 'template_cancelado_id' => '',
                        'template_acao_template_escritorio_id' => [''], 'template_acao_template_escritorio___row__id' => [''], 'template_acao_template_escritorio___row__data' => [''],
                        'template_acao_template_escritorio_url' => [''], 'template_acao_template_escritorio_label' => ['']];
    $s = saida(function () { $f = new TemplateEscritorioForm([]); $f->onSave(['static' => 1]); });
    $_POST = [];
    return $s;
};
$gravado = fn(string $nome) => q("SELECT * FROM template_escritorio WHERE descricao = '" . MARCA_RESP . " {$nome}'")[0] ?? null;
$comboDe = function (string $html, string $campo) { preg_match('~name="' . $campo . '".*?</select>~su', $html, $m); return $m[0] ?? ''; };

try {
    // ---------- identificar a resposta: maiusculas, minusculas e acentos ----------
    foreach (['Não', 'NÃO', 'nao', 'NAO', 'não!'] as $t) { igual("ignorando: '{$t}' cancela", 'NAO', ApchatRespostaService::classificar($t, 'Sim', 'Não', true)); }
    foreach (['sim', 'SÍM', 'Sim.'] as $t) { igual("ignorando: '{$t}' confirma", 'SIM', ApchatRespostaService::classificar($t, 'Sim', 'Não', true)); }
    igual('sem ignorar: acento faz diferenca', 'OUTRA', ApchatRespostaService::classificar('Nao', 'Sim', 'Não', false));
    igual('sem ignorar: maiuscula faz diferenca', 'OUTRA', ApchatRespostaService::classificar('NÃO', 'Sim', 'Não', false));
    igual('sem ignorar: igual vale', 'NAO', ApchatRespostaService::classificar('Não', 'Sim', 'Não', false));
    igual('resposta personalizada', 'SIM', ApchatRespostaService::classificar('1', '1', '2', true));
    igual('frase nao vale', 'OUTRA', ApchatRespostaService::classificar('nao posso', 'Sim', 'Não', true));

    // ---------- so as categorias que existem ----------
    igual('categorias fixas', ['CONFIRMACAO', 'CONFIRMADO', 'CANCELAMENTO', 'LEMBRETE', 'OUTROS'], array_keys(TemplateEscritorio::CATEGORIAS));

    // ---------- combos ----------
    $sim = $template('SIM', 'CONFIRMADO');
    $nao = $template('NAO', 'CANCELAMENTO');
    $desabilitado = $template('DESABILITADO', 'CONFIRMADO', 'WHATSAPP', 'F');
    $email = $template('EMAIL CONFIRMADO', 'CONFIRMADO', 'EMAIL');
    $outros = $template('OUTROS', 'OUTROS');
    $form = saida(function () { $f = new TemplateEscritorioForm([]); $f->onShow([]); $f->show(); });
    $combo = $comboDe($form, 'template_confirmado_id');
    ok('combo do confirmar: categoria certa', strpos($combo, MARCA_RESP . ' SIM') !== false);
    ok('combo do confirmar: nada de outra categoria, e-mail ou desabilitado', strpos($combo, MARCA_RESP . ' NAO') === false && strpos($combo, MARCA_RESP . ' EMAIL') === false && strpos($combo, MARCA_RESP . ' DESABILITADO') === false && strpos($combo, MARCA_RESP . ' OUTROS') === false);
    $combo = $comboDe($form, 'template_cancelado_id');
    ok('combo do cancelar: categoria certa', strpos($combo, MARCA_RESP . ' NAO') !== false && strpos($combo, MARCA_RESP . ' SIM') === false);
    ok('form: uma resposta por campo, sem lista', preg_match('~<input[^>]*name="respostas_confirmar"~', $form) === 1 && preg_match('~<input[^>]*name="respostas_cancelar"~', $form) === 1);
    ok('form: botao para ignorar maiusculas, minusculas e acentos', strpos($form, 'name="ignorar_maiusculas"') !== false && strpos($form, 'acentos') !== false);
    igual('form: criar template nas duas respostas', 2, substr_count($form, 'onNovaResposta'));
    ok('form: a secao olha os campos do proprio form, nao os filtros da listagem atras', strpos($form, "$('form[name=form_TemplateClinicaForm] [name=categoria]').val()") !== false && strpos($form, "$('[name=categoria]')") === false && strpos($form, "$('[name=tipo_template]')") === false);
    ok('form: sem lembrete', strpos($form, 'template_lembrete_id') === false);

    // ---------- salvar a confirmacao ----------
    $base = ['categoria' => 'CONFIRMACAO', 'tipo_template' => 'WHATSAPP', 'descricao' => MARCA_RESP . ' CONF', 'respostas_confirmar' => ' 1 ', 'respostas_cancelar' => ' 2 ', 'template_confirmado_id' => $sim, 'template_cancelado_id' => $nao];
    $s = $salvar(['respostas_confirmar' => ''] + $base);
    ok('sem resposta: nao salva', $gravado('CONF') === null && strpos($s, 'Defina as duas respostas') !== false, substr($s, 0, 200));
    $s = $salvar(['respostas_cancelar' => '!!!'] + $base);
    ok('resposta sem letra nem numero: nao salva', $gravado('CONF') === null && strpos($s, 'Defina as duas respostas') !== false);
    $s = $salvar(['respostas_confirmar' => 'Não', 'respostas_cancelar' => 'NAO'] + $base);
    ok('respostas iguais ignorando acento: nao salva', $gravado('CONF') === null && strpos($s, 'As duas respostas são iguais') !== false, substr($s, 0, 200));
    $s = $salvar(['template_confirmado_id' => ''] + $base);
    ok('sem template do confirmar: nao salva', $gravado('CONF') === null && strpos($s, 'depois da resposta') !== false);
    $s = $salvar(['template_cancelado_id' => ''] + $base);
    ok('sem template do cancelar: nao salva', $gravado('CONF') === null && strpos($s, 'depois da resposta') !== false);
    $s = $salvar(['template_confirmado_id' => $nao] + $base);
    ok('template de outra categoria: nao salva', $gravado('CONF') === null && strpos($s, 'tem de ser de WhatsApp') !== false, substr($s, 0, 200));
    $s = $salvar(['template_confirmado_id' => $email] + $base);
    ok('template de e-mail: nao salva', $gravado('CONF') === null && strpos($s, 'tem de ser de WhatsApp') !== false);
    $s = $salvar(['template_confirmado_id' => $desabilitado] + $base);
    ok('template desabilitado: nao salva', $gravado('CONF') === null && strpos($s, 'tem de ser de WhatsApp') !== false);

    $s = $salvar($base);
    $conf = $gravado('CONF');
    ok('completo: salva', $conf !== null, substr($s, 0, 300));
    igual('completo: respostas sem espaco', ['1', '2'], [$conf['respostas_confirmar'] ?? null, $conf['respostas_cancelar'] ?? null]);
    igual('completo: templates', [$sim, $nao], [(int) ($conf['template_confirmado_id'] ?? 0), (int) ($conf['template_cancelado_id'] ?? 0)]);
    ok('sem a coluna do lembrete (migracao)', !array_key_exists('template_lembrete_id', $conf));

    // ---------- sem ignorar, acento e maiuscula diferenciam ----------
    $s = $salvar(['descricao' => MARCA_RESP . ' SENSIVEL', 'respostas_confirmar' => 'Não', 'respostas_cancelar' => 'NAO', 'ignorar_maiusculas' => ''] + $base);
    igual('sem ignorar: respostas diferentes valem', 'F', $gravado('SENSIVEL')['ignorar_maiusculas'] ?? null);

    // ---------- e-mail: sem respostas, nem que venham no formulario ----------
    $s = $salvar(['tipo_template' => 'EMAIL', 'titulo' => 'T', 'descricao' => MARCA_RESP . ' CONF EMAIL', 'template' => 'Texto'] + $base);
    $confEmail = $gravado('CONF EMAIL');
    ok('e-mail: salva', $confEmail !== null, substr($s, 0, 300));
    igual('e-mail: sem respostas nem templates', [null, null, null, null], [$confEmail['respostas_confirmar'], $confEmail['respostas_cancelar'], $confEmail['template_confirmado_id'], $confEmail['template_cancelado_id']]);
    // outra categoria tambem nao guarda
    $s = $salvar(['categoria' => 'LEMBRETE', 'descricao' => MARCA_RESP . ' LEMBRETE'] + $base);
    igual('outra categoria: sem respostas', null, $gravado('LEMBRETE')['respostas_confirmar']);

    // ---------- template em uso fica protegido ----------
    $editar = ['id' => $sim, 'descricao' => MARCA_RESP . ' SIM', 'tipo_template' => 'WHATSAPP', 'categoria' => 'CONFIRMADO'];
    $s = $salvar(['categoria' => 'OUTROS'] + $editar);
    ok('em uso: nao muda de categoria', strpos($s, 'resposta automática') !== false && $gravado('SIM')['categoria'] === 'CONFIRMADO', substr($s, 0, 200));
    $s = $salvar(['tipo_template' => 'EMAIL', 'titulo' => 'T', 'template' => 'x'] + $editar);
    ok('em uso: nao vira e-mail', strpos($s, 'resposta automática') !== false && $gravado('SIM')['tipo_template'] === 'WHATSAPP');
    $s = $salvar(['habilitado' => 'F'] + $editar);
    ok('em uso: nao desabilita', strpos($s, 'resposta automática') !== false && $gravado('SIM')['habilitado'] === 'T');
    $s = $salvar(['template_whatsapp' => 'Texto novo'] + $editar);
    igual('em uso: o texto pode mudar', 'Texto novo', $gravado('SIM')['template'] ?? null);
    $s = saida(function () use ($sim) { $l = new TemplateEscritorioHeaderList([]); $l->onDelete(['key' => $sim, 'delete' => 1]); });
    ok('em uso: nao exclui', strpos($s, 'resposta automática') !== false && $gravado('SIM') !== null, substr($s, 0, 200));

    // ---------- criar o template de resposta sem sair do formulario ----------
    $janela = saida(function () { TemplateEscritorioForm::onNovaResposta(['ramo' => 'cancelado']); });
    ok('janela: diz a categoria', strpos($janela, 'Cancelamento') !== false && strpos($janela, 'name="nova_descricao"') !== false && strpos($janela, 'name="nova_texto"') !== false);
    $s = saida(function () { TemplateEscritorioForm::onSalvarNovaResposta(['ramo' => 'cancelado', 'nova_descricao' => '', 'nova_texto' => 'Texto']); });
    ok('janela: descricao obrigatoria', strpos($s, 'Informe a descrição') !== false);
    $s = saida(function () { TemplateEscritorioForm::onSalvarNovaResposta(['ramo' => 'cancelado', 'nova_descricao' => MARCA_RESP . ' JANELA', 'nova_texto' => '  ']); });
    ok('janela: texto obrigatorio', strpos($s, 'Escreva a mensagem') !== false && $gravado('JANELA') === null);
    $s = saida(function () { TemplateEscritorioForm::onSalvarNovaResposta(['ramo' => 'cancelado', 'nova_descricao' => MARCA_RESP . ' janela', 'nova_texto' => 'Cancelado, {$cliente}']); });
    $criada = $gravado('JANELA');
    ok('janela: cria', $criada !== null, substr($s, 0, 300));
    igual('janela: WhatsApp, Cancelamento, habilitado', ['WHATSAPP', 'CANCELAMENTO', 'T'], [$criada['tipo_template'] ?? null, $criada['categoria'] ?? null, $criada['habilitado'] ?? null]);
    igual('janela: escritorio do usuario', $escritorio, (int) ($criada['escritorio_id'] ?? 0));
    ok('janela: entra no combo certo e ja fica escolhido', strpos($s, "tcombo_add_option('form_TemplateClinicaForm', 'template_cancelado_id', '{$criada['id']}'") !== false && strpos($s, 'tform_send_data') !== false, substr($s, 0, 400));
    $s = saida(function () { TemplateEscritorioForm::onSalvarNovaResposta(['ramo' => 'lembrete', 'nova_descricao' => MARCA_RESP . ' RUIM', 'nova_texto' => 'x']); });
    ok('janela: so as duas respostas', $gravado('RUIM') === null);

    // ---------- listagem ----------
    $lista = saida(function () { $p = new TemplateEscritorioHeaderList([]); $p->onReload([]); $p->show(); });
    ok('lista: sem o painel', strpos($lista, 'tf-painel') === false && strpos($lista, 'Novo fluxo') === false);
    TTransaction::open('escritorio');
    igual('lista: confirmacao mostra as respostas', '<b>1</b> → ' . MARCA_RESP . ' SIM<br><b>2</b> → ' . MARCA_RESP . ' NAO', TemplateRespostaService::colunaDaLista(TemplateEscritorio::find((int) $conf['id'])));
    ok('lista: resposta mostra de quem e', strpos(TemplateRespostaService::colunaDaLista(TemplateEscritorio::find($nao)), 'Resposta ao <b>2</b> de ' . MARCA_RESP . ' CONF') !== false);
    igual('lista: e-mail sem vinculo', '', TemplateRespostaService::colunaDaLista(TemplateEscritorio::find((int) $confEmail['id'])));
    TTransaction::close();

    // ---------- tela de envio: o lembrete vem pela categoria ----------
    ok('template padrao continua sem o campo do lembrete', strpos(file_get_contents('app/service/MensagemService.php'), 'template_lembrete_id') === false);
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    $_POST = [];
    limparResp();
}

fim();
