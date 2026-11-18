<?php
// tests/apchat/EnvioBotoesTest.php
// Templates de WhatsApp da Curciol com titulo, rodape e botoes (18/11/2026): texto final (titulo em negrito, rodape em italico),
// validacao dos botoes e o envio pelo /sendButtonWABA do APChat - SO canal oficial (WABA), conferido antes de qualquer chamada
// a API (regra do usuario). Banco do APChat e API falsos; tudo numa transacao desfeita no fim.
require __DIR__ . '/../bootstrap.php';

const INTEGRACAO_B = '0f0f0f0f-1111-2222-3333-777788889999';

// ---------- texto final (sem banco) ----------
igual('titulo, corpo e rodape', "*Confirmação*\n\nOlá {{cliente}}\n\n_Curciol_", MensagemService::montarWhatsapp(' Confirmação ', "Olá {{cliente}}\n", 'Curciol'));
igual('sem titulo e sem rodape: so o corpo', 'Olá', MensagemService::montarWhatsapp('', 'Olá', null));
igual('sem corpo: nao ha mensagem (nem so o titulo)', '', MensagemService::montarWhatsapp('T', ' ', 'R'));

$tpl = new TemplateEscritorio();
$tpl->tipo_template = TemplateEscritorio::WHATSAPP;
$tpl->origem = TemplateEscritorio::ORIGEM_CURCIOL;
$tpl->categoria = TemplateEscritorio::CONFIRMACAO;
$tpl->titulo = 'Confirmação';
$tpl->template = 'Olá {$cliente}';
$tpl->rodape = 'Curciol';
$tpl->botoes = "Sim\n\n Não \nTalvez\nQuarto";
igual('textoDoTemplate: texto final e ate 2 botoes', ['*Confirmação*' . "\n\nOlá {{cliente}}\n\n_Curciol_", ['Sim', 'Não']], array_values(array_intersect_key(MensagemService::textoDoTemplate($tpl), ['texto' => 1, 'botoes' => 1])));
$email = clone $tpl; $email->tipo_template = TemplateEscritorio::EMAIL;
igual('e-mail: texto como esta, sem botoes', ['Olá {{cliente}}', []], [MensagemService::textoDoTemplate($email)['texto'], MensagemService::textoDoTemplate($email)['botoes']]);

// ---------- previa do WhatsApp ----------
$h = TemplateWhatsappPreviaService::previa(['tipo_template' => 'WHATSAPP', 'titulo' => 'Confirmação', 'template_whatsapp' => "Olá *{{cliente}}* em {{data_inicial}} e {{profissional}}\n<script>x</script>", 'rodape' => 'Curciol', 'botao1' => 'Sim', 'botao2' => 'Não', 'botao3' => '']);
ok('previa: titulo em negrito e rodape em italico (como sai pela API)', strpos($h, '<b>Confirmação</b>') !== false && strpos($h, '<i>Curciol</i>') !== false, $h);
ok('previa: texto escapado', strpos($h, '<script>') === false && strpos($h, '&lt;script&gt;') !== false);
ok('previa: variaveis destacadas e o "_" delas nao vira italico', strpos($h, '{{data_inicial}}</span>') !== false && strpos($h, '{{profissional}}</span>') !== false && substr_count($h, '<i>') === 1, $h);
ok('previa: negrito em volta da variavel', strpos($h, '<b><span') !== false);
igual('previa: os 2 botoes, com o icone de resposta', 2, substr_count($h, 'fa-reply'));
ok('previa: botoes com o texto', strpos($h, '</i>Sim</div>') !== false && strpos($h, '</i>Não</div>') !== false);
ok('previa vazia: pede a mensagem', strpos(TemplateWhatsappPreviaService::previa(['tipo_template' => 'WHATSAPP']), 'Escreva a mensagem') !== false);
$hm = TemplateWhatsappPreviaService::balao('Cabeçalho', 'Corpo', 'Rodapé', ['Sim']);
ok('previa da Meta: cabecalho, corpo e rodape cinza separados', strpos($hm, 'font-weight:700') !== false && strpos($hm, '#8696a0;font-size:13px') !== false && strpos($hm, 'Rodapé') !== false);

// ---------- validacao ----------
$v = function (array $campos) { $t = new TemplateEscritorio(); $t->tipo_template = 'WHATSAPP'; $t->origem = 'CURCIOL'; $t->template = 'Texto'; $t->categoria = 'CONFIRMACAO'; foreach ($campos as $k => $x) { $t->$k = $x; } TemplateRespostaService::validarBotoes($t); return $t; };
lanca('3 botoes: nao (so 2, usuario 18/11)', fn() => $v(['botoes' => "a\nb\nc"]), 'no máximo 2');
lanca('botao com 21 caracteres: nao', fn() => $v(['botoes' => str_repeat('x', 21)]), 'passa de 20');
lanca('rodape com 61 caracteres: nao', fn() => $v(['rodape' => str_repeat('x', 61)]), 'até 60');
lanca('botao repetido (sem maiusculas e acentos): nao', fn() => $v(['botoes' => "Não\nnao"]), 'repetido');
igual('botoes limpos (linhas vazias saem)', "Sim\nNão", $v(['botoes' => "\n Sim \n\nNão\n"])->botoes);
$e = $v(['tipo_template' => 'EMAIL', 'botoes' => 'Sim', 'rodape' => 'x']);
igual('e-mail: sem rodape nem botoes', [null, null], [$e->rodape, $e->botoes]);

// botoes so na categoria Confirma/Cancela Agendamento (usuario, 18/11/2026)
igual('nomes das categorias na tela', ['Confirma/Cancela Agendamento', 'Confirmado', 'Cancelado'],
      [TemplateEscritorio::CATEGORIAS['CONFIRMACAO'], TemplateEscritorio::CATEGORIAS['CONFIRMADO'], TemplateEscritorio::CATEGORIAS['CANCELAMENTO']]);
foreach (['CONFIRMADO', 'CANCELAMENTO', 'LEMBRETE', 'OUTROS'] as $cat)
{
    igual("{$cat}: botoes da Curciol nao valem (saem ao salvar)", null, $v(['categoria' => $cat, 'botoes' => "Sim\nNão"])->botoes);
}
lanca('Meta com botoes fora da Confirma/Cancela: nao salva', fn() => $v(['categoria' => 'LEMBRETE', 'origem' => 'META', 'botoes' => "Sim\nNão"]), 'só pode ser da categoria Confirma/Cancela Agendamento');
$outra = clone $tpl; $outra->categoria = TemplateEscritorio::LEMBRETE;
igual('fora da Confirma/Cancela: a mensagem nao leva botoes', [], MensagemService::textoDoTemplate($outra)['botoes']);

// ---------- banco do APChat e API falsos ----------
$canalWaba = ['canal_id' => 115, 'canal' => 'Curciol', 'tipo' => 'waba', 'waba_id' => '1234567890', 'token' => 'X', 'versao' => '25.0'];
$falso = new class($canalWaba) {
    public $canal; public $erro = false; public $ticket = 3161; public $pedidos = [];
    public function __construct($c) { $this->canal = $c; }
    public function integracaoDaEmpresa($id) { return true; }
    public function wabaDaIntegracao($id) { if ($this->erro) { throw new Exception('function curciol.f_waba does not exist'); } return $this->canal; }
    public function ultimoTicket($numeros, $canalId) { $this->pedidos[] = [$numeros, $canalId]; return $this->ticket; }
};
$api = [];
ApchatApiService::usarTransporte(function ($url, $corpo, $token) use (&$api) {
    $api[] = ['url' => $url, 'corpo' => $corpo];
    return ['status' => 200, 'corpo' => '{"success":true,"data":{"messaging_product":"whatsapp","messages":[{"id":"wamid.TESTE"}]}}', 'erro' => ''];
});

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$c = TTransaction::get();

try
{
    ApchatBancoService::usarFalso($falso);
    $escritorio = $c->query('SELECT id, system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $eid = (int) $escritorio['id'];
    TSession::setValue('userunitid', (int) $escritorio['system_unit_id']);
    $c->exec("UPDATE escritorio SET token_apchat = 'TOKEN-FALSO-DE-TESTE', url_apchat = 'https://api.apchat.falso/v2/api/external/" . INTEGRACAO_B . "' WHERE id = {$eid}");

    // confirmacao com botoes: cada botao tem de ser uma das respostas
    $resp = function (string $categoria) use ($eid, $c) {
        $t = new TemplateEscritorio(); $t->escritorio_id = $eid; $t->chave = 'TESTE_BTN_' . uniqid(); $t->descricao = 'TESTE BOTOES ' . $categoria;
        $t->tipo_template = 'WHATSAPP'; $t->categoria = $categoria; $t->habilitado = 'T'; $t->template = 'Resposta {{cliente}}'; $t->store(); return (int) $t->id;
    };
    $conf = new TemplateEscritorio();
    $conf->escritorio_id = $eid; $conf->tipo_template = 'WHATSAPP'; $conf->origem = 'CURCIOL'; $conf->categoria = TemplateEscritorio::CONFIRMACAO;
    $conf->template = 'Confirma?'; $conf->respostas_confirmar = "Sim\nconfirmo"; $conf->respostas_cancelar = 'Não'; $conf->ignorar_maiusculas = 'T';
    $conf->template_confirmado_id = $resp(TemplateEscritorio::CONFIRMADO); $conf->template_cancelado_id = $resp(TemplateEscritorio::CANCELAMENTO);
    $conf->botoes = "SIM\nnao";
    TemplateRespostaService::validar($conf);
    ok('confirmacao: botoes que sao respostas (sem maiusculas e acentos) passam', true);
    $conf->botoes = "Sim\nTalvez";
    lanca('confirmacao: botao que nao confirma nem cancela nao salva', fn() => TemplateRespostaService::validar($conf), 'O botão "Talvez"');

    // ---------- envio ----------
    $t = new TemplateEscritorio();
    $t->escritorio_id = $eid; $t->chave = 'TESTE_BTN_' . uniqid(); $t->descricao = 'TESTE BOTOES ENVIO'; $t->tipo_template = 'WHATSAPP'; $t->origem = 'CURCIOL';
    $t->categoria = TemplateEscritorio::CONFIRMACAO; $t->habilitado = 'T'; $t->titulo = 'Aviso'; $t->template = 'Olá {{cliente}}'; $t->rodape = 'Curciol'; $t->botoes = "Sim\nNão";
    $t->store();

    $p = new Pessoa(); $p->nome = 'TESTE BOTOES'; $p->tipo_pessoa_id = TipoPessoa::FISICA; $p->telefone = '19987650003'; $p->aceita_receber_mensagen_whatsapp = 'T'; $p->store();
    $ag = new Agendamento((int) $c->query('SELECT id FROM agendamento ORDER BY id LIMIT 1')->fetchColumn());
    $ag->cliente_id = $p->id;
    AmbienteService::forcarProducao(false);
    AmbienteService::forcarWhatsappTeste('19971045673');
    $partes = MensagemService::textoDoTemplate($t);

    // canal que nao e o oficial: barrado ANTES de qualquer chamada a API
    $falso->canal = ['canal_id' => 113, 'canal' => 'Coexistência', 'tipo' => 'whatsapp', 'waba_id' => null, 'token' => null, 'versao' => ''];
    lanca('canal nao oficial: nao envia', fn() => MensagemService::enviarWhatsapp($ag, $partes['titulo'], $partes['texto'], $t->id, ''), 'canal oficial');
    igual('canal nao oficial: nenhuma requisicao a API', 0, count($api));
    $falso->canal = null;
    lanca('sem canal: nao envia', fn() => MensagemService::enviarWhatsapp($ag, $partes['titulo'], $partes['texto'], $t->id, ''), 'não tem canal');
    $falso->canal = $canalWaba;
    $falso->erro = true;
    lanca('APChat fora (nao da para conferir): nao envia', fn() => MensagemService::enviarWhatsapp($ag, $partes['titulo'], $partes['texto'], $t->id, ''), 'não foi possível conferir');
    $falso->erro = false;
    $falso->ticket = null;
    lanca('sem conversa no canal: nao envia', fn() => MensagemService::enviarWhatsapp($ag, $partes['titulo'], $partes['texto'], $t->id, ''), 'ainda não tem conversa');
    igual('nada saiu ate aqui', 0, count($api));
    igual('nada registrado sem envio', 0, (int) $c->query("SELECT count(*) FROM mensagem WHERE template_escritorio_id = {$t->id}")->fetchColumn());

    $falso->ticket = 3161;
    $m = MensagemService::enviarWhatsapp($ag, $partes['titulo'], $partes['texto'], $t->id, '');
    igual('envio: uma chamada ao /sendButtonWABA da integracao', 'https://api.apchat.falso/v2/api/external/' . INTEGRACAO_B . '/sendButtonWABA', $api[0]['url'] ?? null);
    $corpo = $api[0]['corpo'] ?? [];
    igual('envio: numero de teste, texto final, botoes e atendimento', ['5519971045673', "*Aviso*\n\nOlá TESTE BOTOES\n\n_Curciol_", 'Sim', 'Não', 3161, true],
          [$corpo['number'] ?? null, $corpo['message'] ?? null, $corpo['button1'] ?? null, $corpo['button2'] ?? null, $corpo['ticketId'] ?? null, $corpo['isClosed'] ?? null]);
    ok('envio: sem botao 3', !isset($corpo['button3']));
    igual('envio: atendimento procurado no canal oficial, pelo numero', 115, $falso->pedidos[0][1] ?? null);
    $reg = $c->query("SELECT template, apchat_mensagem_id, apchat_ticket_id FROM mensagem WHERE id = {$m->id}")->fetch(PDO::FETCH_ASSOC);
    igual('historico: texto e botoes como o cliente ve; id da Meta e atendimento', ["*Aviso*\n\nOlá TESTE BOTOES\n\n_Curciol_\n\n[Sim]  [Não]", 'wamid.TESTE', 3161],
          [$reg['template'], $reg['apchat_mensagem_id'], (int) $reg['apchat_ticket_id']]);

    // envio manual com o texto editado: os botoes vao junto, o texto e o editado (sem repetir o titulo)
    $api = [];
    MensagemService::enviarWhatsapp($ag, 'Aviso', 'Texto editado {{cliente}}', $t->id, '');
    igual('manual editado: texto editado e os botoes', ['Texto editado TESTE BOTOES', 'Sim', 'Não'], [$api[0]['corpo']['message'] ?? null, $api[0]['corpo']['button1'] ?? null, $api[0]['corpo']['button2'] ?? null]);

    // template sem botoes continua pelo envio de texto
    $api = [];
    $t->botoes = null; $t->store();
    MensagemService::enviarWhatsapp($ag, 'Aviso', MensagemService::textoDoTemplate($t)['texto'], $t->id, '');
    ok('sem botoes: envio de texto de sempre', substr((string) ($api[0]['url'] ?? ''), -strlen(INTEGRACAO_B)) === INTEGRACAO_B && ($api[0]['corpo']['body'] ?? '') === "*Aviso*\n\nOlá TESTE BOTOES\n\n_Curciol_", json_encode($api[0] ?? null));
}
finally
{
    TTransaction::rollback();
    ApchatBancoService::usarFalso(null);
    AmbienteService::forcarProducao(null);
    AmbienteService::forcarWhatsappTeste(null);
    ApchatApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso de mensagens.'); });
}

fim();
