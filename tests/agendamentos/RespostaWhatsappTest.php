<?php
// tests/agendamentos/RespostaWhatsappTest.php
// Resposta SIM/NAO ao WhatsApp de confirmacao (03/11/2026; ApchatRespostaService): SIM confirma (com atendimento) e
// manda a "confirmacao recebida", NAO cancela e agradece, frase ou saudacao antes deixa em aberto, atendente interrompe, so a ultima confirmacao do numero
// vale, agendamento que ja mudou encerra, erro no envio da resposta desfaz e tenta de novo.
// NUNCA fala com o APChat de verdade: banco do APChat falso e API falsa.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_RESP = 'TESTE RESPOSTA WHATSAPP';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

function limparResposta(): void
{
    $ags = array_column(q("SELECT a.id FROM agendamento a JOIN pessoa p ON p.id = a.cliente_id WHERE p.nome LIKE '" . MARCA_RESP . "%'"), 'id');
    $lista = $ags ? implode(',', array_map('intval', $ags)) : '0';
    x("DELETE FROM mensagem_acao WHERE mensagem_id IN (SELECT id FROM mensagem WHERE agendamento_id IN ({$lista}))");
    x("DELETE FROM mensagem WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM estado_agendamento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM atendimento_historico WHERE atendimento_id IN (SELECT id FROM atendimento WHERE agendamento_id IN ({$lista}))");
    x("DELETE FROM atendimento_procedimento WHERE atendimento_id IN (SELECT id FROM atendimento WHERE agendamento_id IN ({$lista}))");
    x("DELETE FROM atendimento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM agendamento WHERE id IN ({$lista})");
    x("DELETE FROM pessoa_contato WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_RESP . "%')");
    x("DELETE FROM pessoa WHERE nome LIKE '" . MARCA_RESP . "%'");
}
limparResposta();

// ---------- banco do APChat falso: a conversa de cada numero ----------
class ApchatBancoRespostasFalso
{
    public $respostas = [];
    public function disponivel() { return true; }
    public function conversaDepois(array $numeros, string $desde, int $limite = 50)
    {
        foreach ($numeros as $n) { if (isset($this->respostas[$n])) { return array_slice($this->respostas[$n], 0, $limite); } }
        return [];
    }
}
$banco = new ApchatBancoRespostasFalso();
ApchatBancoService::usarFalso($banco);
$responde = function (string $numero, string $texto) use ($banco) {
    $banco->respostas[$numero][] = ['id' => 1, 'body' => $texto, 'mediaType' => 'chat', 'fromMe' => false, 'createdAt' => date('Y-m-d H:i:s')];
};
// mensagem nossa na conversa: atendente pelo APChat ou a copia, no canal oficial, do texto enviado pela Curciol
$nossa = function (string $numero, string $texto) use ($banco) {
    $banco->respostas[$numero][] = ['id' => 2, 'body' => $texto, 'mediaType' => 'chat', 'fromMe' => true, 'createdAt' => date('Y-m-d H:i:s')];
};

$enviadas = [];
$falharEnvio = false;
ApchatApiService::usarTransporte(function ($url, $corpo, $token) use (&$enviadas, &$falharEnvio) {
    if ($falharEnvio) { return ['status' => 0, 'corpo' => '', 'erro' => 'timeout']; }
    $enviadas[] = $corpo;
    return ['status' => 201, 'corpo' => '{"success":true}', 'erro' => ''];
});
AmbienteService::forcarProducao(false);
TSession::setValue('userid', 1);

$agendaId = (int) q('SELECT id FROM agenda ORDER BY id LIMIT 1')[0]['id'];
$confirmacaoId = (int) (q("SELECT t.id FROM template_escritorio t JOIN agenda a ON a.escritorio_id = t.escritorio_id WHERE a.id = {$agendaId} AND t.chave = 'WHATSAPP_CONFIRMACAO_AGENDAMENTO' ORDER BY t.id LIMIT 1")[0]['id'] ?? 0);

// agendamento Agendado para amanha e a confirmacao ja enviada ao numero de teste
$novo = function (string $nome, string $destino, string $enviadaHa = '1 minute') use ($agendaId, $confirmacaoId) {
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $st = $c->prepare('INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES (?, 1) RETURNING id');
    $st->execute([MARCA_RESP . ' ' . $nome]);
    $pessoa = (int) $st->fetchColumn();
    $st = $c->prepare("INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES (?, ?, ?, now() + interval '1 day', now() + interval '1 day 30 minutes') RETURNING id");
    $st->execute([$pessoa, (int) EstadoAgenda::AGENDADO, $agendaId]);
    $ag = (int) $st->fetchColumn();
    $st = $c->prepare("INSERT INTO mensagem (agendamento_id, template_escritorio_id, system_user_id, titulo, template, tipo_mensagem, destino, enviado_em)
                       VALUES (?, ?, 1, 'Confirmação', 'Responda SIM ou NÃO', 'WHATSAPP', ?, now() - ?::interval) RETURNING id");
    $st->execute([$ag, $confirmacaoId, $destino, $enviadaHa]);
    $m = (int) $st->fetchColumn();
    TTransaction::close();
    return [$ag, $m];
};
$estado = fn(int $ag) => (int) q("SELECT estado_agenda_id e FROM agendamento WHERE id = {$ag}")[0]['e'];
$resposta = fn(int $m) => q("SELECT resposta, resposta_texto FROM mensagem WHERE id = {$m}")[0];
$rodar = function () { ob_start(); ApchatRespostaService::processar(); return ob_get_clean(); };

try {
    // ---------- classificar: so a palavra ----------
    foreach (['sim', 'Sim', 'SIM', 'sIm', ' sim ', 'Sim!', 'sim!', 'sim.', '*SIM*', 'Sim 👍'] as $t) { igual("SIM: '{$t}'", 'SIM', ApchatRespostaService::classificar($t)); }
    foreach (['não', 'nao', 'NÃO', 'Nao.', 'NAO!'] as $t) { igual("NAO: '{$t}'", 'NAO', ApchatRespostaService::classificar($t)); }
    foreach (['Sim, mas posso mudar o horário?', 'simm', 'sim sim', 's', 'ok', '', 'não posso'] as $t) { igual("OUTRA: '{$t}'", 'OUTRA', ApchatRespostaService::classificar($t)); }
    // assinatura de quem responde por um sistema de atendimento (caso real do teste de 03/11/2026)
    igual('assinatura: "*Henrique Rufino*:\n sim"', 'SIM', ApchatRespostaService::classificar("*Henrique Rufino*:\n sim"));
    igual('assinatura: "*Ana*: Não!"', 'NAO', ApchatRespostaService::classificar('*Ana*: Não!'));
    igual('assinatura com outra coisa', 'OUTRA', ApchatRespostaService::classificar("*Ana*:\n sim, mas depois"));

    ok('templates de resposta existem (migracao)', $confirmacaoId > 0 && count(q("SELECT 1 FROM template_escritorio WHERE chave IN ('WHATSAPP_RESPOSTA_SIM', 'WHATSAPP_RESPOSTA_NAO')")) >= 2);

    [$agSim, $mSim] = $novo('SIM', '5519912340001');
    [$agNao, $mNao] = $novo('NAO', '5519912340002');

    // ---------- sem resposta ainda ----------
    $rodar();
    ok('sem resposta: continua esperando', $resposta($mSim)['resposta'] === null && $estado($agSim) == EstadoAgenda::AGENDADO);

    // ---------- SIM (resposta chegou sem o nono digito: o numero e o mesmo) ----------
    $responde('551912340001', 'Sim!');
    $enviadas = [];
    $rodar();
    igual('SIM: agendamento confirmado', EstadoAgenda::CONFIRMADO, $estado($agSim));
    igual('SIM: resposta registrada', ['resposta' => 'SIM', 'resposta_texto' => 'Sim!'], $resposta($mSim));
    igual('SIM: atendimento criado', 1, count(q("SELECT 1 FROM atendimento WHERE agendamento_id = {$agSim}")));
    igual('SIM: historico do estado', 1, count(q("SELECT 1 FROM estado_agendamento WHERE agendamento_id = {$agSim} AND estado_agenda_id = " . EstadoAgenda::CONFIRMADO)));
    igual('SIM: uma mensagem automatica', 1, count($enviadas));
    igual('SIM: para o numero da confirmacao', '5519912340001', $enviadas[0]['number'] ?? '');
    ok('SIM: texto da confirmacao recebida com o nome', strpos($enviadas[0]['body'] ?? '', MARCA_RESP . ' SIM') !== false && strpos($enviadas[0]['body'], 'confirmado') !== false, $enviadas[0]['body'] ?? '');
    igual('SIM: resposta automatica registrada', 1, count(q("SELECT 1 FROM mensagem m JOIN template_escritorio t ON t.id = m.template_escritorio_id WHERE m.agendamento_id = {$agSim} AND t.chave = 'WHATSAPP_RESPOSTA_SIM'")));
    $enviadas = [];
    $rodar();
    igual('SIM: rodar de novo nao repete nada', 0, count($enviadas));

    // ---------- NAO ----------
    $responde('5519912340002', 'NÃO.');
    $rodar();
    igual('NAO: agendamento cancelado', EstadoAgenda::CANCELADO, $estado($agNao));
    igual('NAO: resposta registrada', 'NAO', $resposta($mNao)['resposta']);
    igual('NAO: mensagem de agradecimento', 1, count($enviadas));
    ok('NAO: texto do agradecimento', strpos($enviadas[0]['body'] ?? '', 'Obrigado por avisar') !== false, $enviadas[0]['body'] ?? '');

    // ---------- frase, saudacao ou mistura, mesmo com SIM/NAO depois: nao confirma nem cancela (usuario, 04/11/2026) ----------
    $semDecisao = [
        'frase' => ['Sim, mas posso mudar o horário?'],
        'saudacao automatica e Sim' => ['Olá! Agradecemos o contato.', 'Sim'],
        'frase, saudacao e sIm!' => ['Quero saber o horário', 'Olá! Agradecemos o contato.', 'sIm!'],
        'Bom dia e NAO' => ['Bom dia', 'NÃO'],
        'sim e nao' => ['sim', 'não'],
        'Sim e frase' => ['Sim', 'mas prefiro à tarde'],
    ];
    $final = 1000;
    foreach ($semDecisao as $caso => $textos) {
        $numero = '551991234' . ($final++);
        [$ag, $m] = $novo('ABERTO ' . $final, $numero);
        foreach ($textos as $t) { $responde($numero, $t); }
        $enviadas = [];
        $rodar();
        igual("{$caso}: agendamento continua Agendado", EstadoAgenda::AGENDADO, $estado($ag));
        igual("{$caso}: registrado sem decisao", ['resposta' => 'OUTRA', 'resposta_texto' => implode(' / ', $textos)], $resposta($m));
        igual("{$caso}: nenhuma mensagem automatica", 0, count($enviadas));
        $responde($numero, 'sim');
        $rodar();
        igual("{$caso}: SIM depois nao conta", EstadoAgenda::AGENDADO, $estado($ag));
        igual("{$caso}: nada enviado depois", 0, count($enviadas));
    }

    // ---------- ate 3 seguidas, so a mesma palavra ----------
    [$agTres, $mTres] = $novo('TRES', '5519912340007');
    foreach (['sim', 'Sim!', 'SIM'] as $t) { $responde('5519912340007', $t); }
    $enviadas = [];
    $rodar();
    igual('3 vezes SIM: confirma', EstadoAgenda::CONFIRMADO, $estado($agTres));
    igual('3 vezes SIM: uma mensagem automatica', 1, count($enviadas));

    // ---------- a copia do texto da Curciol no canal oficial nao e atendente ----------
    [$agCopia, $mCopia] = $novo('COPIA', '5519912340003');
    $nossa('5519912340003', "  Responda  SIM\nou NÃO ");
    $responde('5519912340003', 'sim');
    $rodar();
    igual('copia do texto enviado: SIM confirma', EstadoAgenda::CONFIRMADO, $estado($agCopia));

    // ---------- atendente escreveu: para a contagem ----------
    [$agAtend, $mAtend] = $novo('ATENDENTE', '5519912340009');
    $nossa('5519912340009', '*Kleber*: pode confirmar?');
    $enviadas = [];
    $rodar();
    igual('atendente antes da resposta: para de contar', 'ATENDENTE', $resposta($mAtend)['resposta']);
    $responde('5519912340009', 'sim');
    $rodar();
    igual('atendente antes da resposta: SIM depois nao confirma', EstadoAgenda::AGENDADO, $estado($agAtend));
    igual('atendente antes da resposta: nada enviado', 0, count($enviadas));

    [$agMeio, $mMeio] = $novo('MEIO', '5519912340010');
    $responde('5519912340010', 'sim');
    $nossa('5519912340010', '*Kleber*: tudo certo?');
    $rodar();
    igual('atendente antes de a tarefa ler o SIM: nao confirma', EstadoAgenda::AGENDADO, $estado($agMeio));
    igual('atendente antes de a tarefa ler o SIM: registrado', ['resposta' => 'ATENDENTE', 'resposta_texto' => 'sim'], $resposta($mMeio));

    [$agDepois, $mDepois] = $novo('DEPOIS', '5519912340011');
    foreach (['não', 'NAO', 'Não!'] as $t) { $responde('5519912340011', $t); }
    $nossa('5519912340011', '*Kleber*: certo');
    $rodar();
    igual('atendente depois das 3 mensagens: vale o que o cliente respondeu', EstadoAgenda::CANCELADO, $estado($agDepois));

    // telas: uma com a resposta registrada e outra esperando
    [$agTela, $mTela] = $novo('TELA', '5519912340012');
    $responde('5519912340012', 'Sim, mas prefiro à tarde');
    $rodar();
    [$agEspera, $mEspera] = $novo('ESPERA', '5519912340008');

    // ---------- duas confirmacoes para o mesmo numero: vale a ultima ----------
    [$agVelho, $mVelho] = $novo('VELHO', '5519912340004', '10 minutes');
    [$agNovo, $mNovo] = $novo('NOVO', '5519912340004', '1 minute');
    $responde('5519912340004', 'sim');
    $rodar();
    igual('mesmo numero: a anterior fica substituida', 'SUBSTITUIDA', $resposta($mVelho)['resposta']);
    igual('mesmo numero: a anterior nao muda', EstadoAgenda::AGENDADO, $estado($agVelho));
    igual('mesmo numero: a ultima e confirmada', EstadoAgenda::CONFIRMADO, $estado($agNovo));

    // ---------- agendamento ja confirmado pela equipe: encerra sem mexer ----------
    [$agEquipe, $mEquipe] = $novo('EQUIPE', '5519912340005');
    x('UPDATE agendamento SET estado_agenda_id = ' . EstadoAgenda::CONFIRMADO . " WHERE id = {$agEquipe}");
    $responde('5519912340005', 'não');
    $enviadas = [];
    $rodar();
    igual('ja confirmado: encerrada', 'ENCERRADA', $resposta($mEquipe)['resposta']);
    igual('ja confirmado: continua confirmado', EstadoAgenda::CONFIRMADO, $estado($agEquipe));
    igual('ja confirmado: nada enviado', 0, count($enviadas));

    // ---------- erro no envio da resposta automatica: desfaz e tenta de novo ----------
    [$agErro, $mErro] = $novo('ERRO', '5519912340006');
    $responde('5519912340006', 'SIM');
    $falharEnvio = true;
    $saida = $rodar();
    ok('erro: avisa no log da tarefa', strpos($saida, 'ERRO') !== false, $saida);
    igual('erro: agendamento continua Agendado', EstadoAgenda::AGENDADO, $estado($agErro));
    ok('erro: continua esperando', $resposta($mErro)['resposta'] === null);
    igual('erro: nada de atendimento', 0, count(q("SELECT 1 FROM atendimento WHERE agendamento_id = {$agErro}")));
    $falharEnvio = false;
    $rodar();
    igual('erro: na rodada seguinte confirma', EstadoAgenda::CONFIRMADO, $estado($agErro));

    // ---------- tela do agendamento mostra a resposta ----------
    TSession::setValue('userunitid', (int) q("SELECT e.system_unit_id u FROM escritorio e JOIN agenda a ON a.escritorio_id = e.id WHERE a.id = {$agendaId}")[0]['u']);
    ob_start();
    (new AgendamentoFormView(['key' => $agTela]))->show();
    $html = ob_get_clean();
    ok('tela: mostra as respostas do cliente', strpos($html, 'respondeu') !== false && strpos($html, 'prefiro à tarde') !== false);
    ob_start();
    (new AgendamentoFormView(['key' => $agEspera]))->show();
    $html = ob_get_clean();
    ok('tela: aguardando resposta', strpos($html, 'aguardando resposta') !== false);

    // ---------- linha do confirmado pelo cliente, e so 2 botoes de envio ----------
    ob_start();
    (new AgendamentoFormView(['key' => $agSim]))->show();
    $html = ob_get_clean();
    ok('tela: "Agendamento confirmado pelo cliente - numero respondeu ... em data"', (bool) preg_match('~Agendamento confirmado pelo cliente</b> - \(19\) 91234-0001 respondeu &quot;Sim!&quot; em \d\d/\d\d/\d{4} \d\d:\d\d~', $html));
    ok('tela: botoes unicos de WhatsApp e e-mail', strpos($html, 'Enviar WhatsApp') !== false && strpos($html, 'Enviar e-mail') !== false);
    ok('tela: sem os botoes por template', strpos($html, 'WHATSAPP_CONFIRMACAO_AGENDAMENTO') === false && strpos($html, 'lembrete de consulta') === false);
    ok('tela: Cancelar confirmacao aparece no confirmado', (bool) preg_match('~<a[^>]*(?<!display: none;)[^>]*>[^<]*(<i[^>]*></i>)?[^<]*Cancelar confirmação~u', $html));
    ob_start();
    (new AgendamentoFormView(['key' => $agEspera]))->show();
    $html = ob_get_clean();
    ok('tela: Cancelar confirmacao escondido no Agendado', (bool) preg_match('~style="display: none;"[^>]*>[^<]*(<i[^>]*></i>)?[^<]*Cancelar confirmação~u', $html));

    // ---------- Cancelar confirmacao ----------
    ob_start();
    AgendamentoFormView::onCancelarConfirmacao(['key' => $agSim]);
    $html = ob_get_clean();
    ok('cancelar confirmacao: janela pede a observacao', strpos($html, 'name="observacao"') !== false && strpos($html, 'volta para') !== false);
    ob_start();
    AgendamentoFormView::onSalvarCancelarConfirmacao(['key' => $agSim, 'observacao' => '   ']);
    $html = ob_get_clean();
    igual('cancelar confirmacao: sem observacao nao cancela', EstadoAgenda::CONFIRMADO, $estado($agSim));
    ok('cancelar confirmacao: avisa da observacao', strpos($html, 'Escreva a observação') !== false);
    ob_start();
    AgendamentoFormView::onSalvarCancelarConfirmacao(['key' => $agSim, 'observacao' => 'Cliente pediu para remarcar']);
    ob_end_clean();
    igual('cancelar confirmacao: volta para Agendado', EstadoAgenda::AGENDADO, $estado($agSim));
    igual('cancelar confirmacao: atendimento sem nada ligado sai', 0, count(q("SELECT 1 FROM atendimento WHERE agendamento_id = {$agSim}")));
    igual('cancelar confirmacao: observacao no historico', 1, count(q("SELECT 1 FROM estado_agendamento WHERE agendamento_id = {$agSim} AND estado_agenda_id = " . EstadoAgenda::AGENDADO . " AND observacao = 'Cliente pediu para remarcar'")));
    ob_start();
    (new AgendamentoFormView(['key' => $agSim]))->show();
    $html = ob_get_clean();
    ok('cancelar confirmacao: tela mostra a observacao', strpos($html, 'Confirmação cancelada por') !== false && strpos($html, 'Cliente pediu para remarcar') !== false);
    ok('cancelar confirmacao: some a resposta do cliente da tela', strpos($html, 'respondeu &quot;Sim!&quot;') === false || strpos($html, 'Automático') !== false);
    ok('historico: coluna Observacao e Automatico para a resposta do cliente', strpos($html, 'Observação') !== false && strpos($html, 'Automático') !== false && strpos($html, 'respondeu') !== false);
    ok('cancelar confirmacao: nao diz mais que esta confirmado', strpos($html, 'Agendamento confirmado pelo cliente') === false);
    ob_start();
    AgendamentoFormView::onSalvarCancelarConfirmacao(['key' => $agSim, 'observacao' => 'de novo']);
    $html = ob_get_clean();
    ok('cancelar confirmacao: so de confirmado', strpos($html, 'Só é possível') !== false);

    // reconfirmar: atendimento com algo ligado fica e nao duplica
    TTransaction::open('escritorio');
    $a = Agendamento::find($agSim);
    $a->estado_agenda_id = EstadoAgenda::CONFIRMADO;
    $a->store();
    $at = AgendamentoService::criarAtendimento($a);
    TTransaction::get()->prepare('INSERT INTO atendimento_historico (atendimento_id, historico) VALUES (?, ?)')->execute([$at->id, 'teste']);
    AgendamentoService::cancelarConfirmacao($a, 'remarcar');
    TTransaction::close();
    igual('atendimento com historico ligado fica', 1, count(q("SELECT 1 FROM atendimento WHERE agendamento_id = {$agSim}")));
    TTransaction::open('escritorio');
    $a = Agendamento::find($agSim);
    igual('reconfirmar reaproveita o atendimento', (int) $at->id, (int) AgendamentoService::criarAtendimento($a)->id);
    TTransaction::close();
    igual('reconfirmar nao duplica', 1, count(q("SELECT 1 FROM atendimento WHERE agendamento_id = {$agSim}")));

    // confirmou de novo: o cancelamento sai da tela e fica no historico
    TTransaction::open('escritorio');
    $a = Agendamento::find($agSim);
    AgendamentoService::respostaDoCliente($a, (int) EstadoAgenda::CONFIRMADO, EstadoAgendamento::EVENTO_CONFIRMADO_WHATSAPP, 'teste');
    TTransaction::close();
    ob_start();
    (new AgendamentoFormView(['key' => $agSim]))->show();
    $html = ob_get_clean();
    ok('confirmou de novo: tira "Confirmacao cancelada" da tela', strpos($html, 'Confirmação cancelada por') === false);
    ok('confirmou de novo: a observacao fica no historico', strpos($html, 'remarcar') !== false);

    // ---------- alteracao de horario vai para o historico ----------
    TTransaction::open('escritorio');
    $a = Agendamento::find($agSim);
    $a->observacao = 'nao mexe no horario';
    $a->store();
    TTransaction::close();
    igual('horario: salvar sem mudar nao registra', 0, count(q("SELECT 1 FROM estado_agendamento WHERE agendamento_id = {$agSim} AND evento = 'HORARIO'")));
    TTransaction::open('escritorio');
    $a = Agendamento::find($agSim);
    $a->dt_inicial = '2031-05-10 14:00:00';
    $a->dt_final = '2031-05-10 14:30:00';
    $a->store();
    TTransaction::close();
    $linhaHorario = q("SELECT observacao FROM estado_agendamento WHERE agendamento_id = {$agSim} AND evento = 'HORARIO'");
    igual('horario: uma linha no historico', 1, count($linhaHorario));
    ok('horario: de quanto para quanto', strpos($linhaHorario[0]['observacao'] ?? '', 'para 10/05/2031 14:00 às 14:30') !== false, $linhaHorario[0]['observacao'] ?? '');

    // ---------- link do e-mail: abrir so pergunta; o clique confirma ou cancela ----------
    [$agLink, $mLink] = $novo('LINK', '5519912340030');
    $token = AgendamentoService::getToken($agLink);
    $pg = function (string $metodo, array $extra = []) use ($agLink, $token) {
        $p = ['key' => $agLink, 'token' => $token] + $extra;
        ob_start();
        $v = new AgendamentoView($p);
        $v->$metodo($p);
        return ob_get_clean();
    };
    $s = $pg('onConfirmar');
    igual('link: abrir nao confirma (antivirus de e-mail abre os links)', EstadoAgenda::AGENDADO, $estado($agLink));
    ok('link: pergunta antes', strpos($s, 'Confirma este agendamento') !== false);
    $pg('onConfirmar', ['respondeu' => 1]);
    igual('link: o clique confirma', EstadoAgenda::CONFIRMADO, $estado($agLink));
    igual('link: cria o atendimento', 1, count(q("SELECT 1 FROM atendimento WHERE agendamento_id = {$agLink}")));
    igual('link: historico automatico', 1, count(q("SELECT 1 FROM estado_agendamento WHERE agendamento_id = {$agLink} AND evento = 'CONFIRMADO_EMAIL' AND system_users_id IS NULL")));
    ob_start();
    (new AgendamentoFormView(['key' => $agLink]))->show();
    $html = ob_get_clean();
    ok('link: tela diz que foi pelo e-mail', strpos($html, 'pelo link do e-mail') !== false && strpos($html, 'fa-envelope') !== false);
    [$agLink2, $mLink2] = $novo('LINK2', '5519912340031');
    $p2 = ['key' => $agLink2, 'token' => AgendamentoService::getToken($agLink2)];
    ob_start(); $v = new AgendamentoView($p2); $v->onCancelar($p2); ob_end_clean();
    igual('link: abrir nao cancela', EstadoAgenda::AGENDADO, $estado($agLink2));
    ob_start(); $v = new AgendamentoView($p2); $v->onCancelar($p2 + ['respondeu' => 1]); ob_end_clean();
    igual('link: o clique cancela', EstadoAgenda::CANCELADO, $estado($agLink2));

    // ---------- fluxo personalizado: respostas do usuario ----------
    igual('fluxo: resposta do usuario', 'SIM', ApchatRespostaService::classificar('Confirmo!', "sim\nconfirmo\npode confirmar", 'cancelar'));
    igual('fluxo: resposta com mais de uma palavra', 'SIM', ApchatRespostaService::classificar('pode confirmar', "sim\nconfirmo\npode confirmar", 'cancelar'));
    igual('fluxo: cancelar', 'NAO', ApchatRespostaService::classificar('CANCELAR', 'sim', 'cancelar'));
    igual('fluxo: fora da lista', 'OUTRA', ApchatRespostaService::classificar('sim', 'confirmo', 'cancelar'));
    igual('fluxo: frase com a palavra no meio nao vale', 'OUTRA', ApchatRespostaService::classificar('eu confirmo sim', "sim\nconfirmo", 'cancelar'));
    igual('fluxo: ignorando maiusculas', 'SIM', ApchatRespostaService::classificar('sIm', 'sim', 'nao', true));
    igual('fluxo: sem ignorar, so igual', 'OUTRA', ApchatRespostaService::classificar('SIM', 'sim', 'nao', false));
    igual('fluxo: sem ignorar, igual vale', 'SIM', ApchatRespostaService::classificar('sim!', 'sim', 'nao', false));

    // o proprio fluxo do template decide e manda a mensagem escolhida
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $esc = (int) $c->query("SELECT escritorio_id FROM template_escritorio WHERE id = {$confirmacaoId}")->fetchColumn();
    $st = $c->prepare("INSERT INTO template_escritorio (escritorio_id, chave, descricao, habilitado, template, titulo, tipo_template, categoria) VALUES (?, ?, ?, 'T', ?, 'T', 'WHATSAPP', 'CONFIRMADO') RETURNING id");
    $st->execute([$esc, 'TESTE_FLUXO_OK', 'TESTE FLUXO OK', 'Fechado, {$cliente}!']);
    $tOk = (int) $st->fetchColumn();
    $st = $c->prepare("INSERT INTO template_escritorio (escritorio_id, chave, descricao, habilitado, template, titulo, tipo_template, categoria, respostas_confirmar, respostas_cancelar, ignorar_maiusculas, template_confirmado_id) VALUES (?, ?, ?, 'T', 'Vem?', 'T', 'WHATSAPP', 'CONFIRMACAO', ?, ?, 'F', ?) RETURNING id");
    $st->execute([$esc, 'TESTE_FLUXO', 'TESTE FLUXO', "Vou\nConfirmo", 'Desisto', $tOk]);
    $tFluxo = (int) $st->fetchColumn();
    TTransaction::close();
    try {
        [$agFluxo, $mFluxo] = $novo('FLUXO', '5519912340040');
        x("UPDATE mensagem SET template_escritorio_id = {$tFluxo} WHERE id = {$mFluxo}");
        $responde('5519912340040', 'Confirmo');
        $enviadas = [];
        $rodar();
        igual('fluxo: confirma pela resposta do usuario', EstadoAgenda::CONFIRMADO, $estado($agFluxo));
        ok('fluxo: manda a mensagem escolhida', strpos($enviadas[0]['body'] ?? '', 'Fechado, ' . MARCA_RESP) === 0, $enviadas[0]['body'] ?? '');
        [$agFluxo2, $mFluxo2] = $novo('FLUXO2', '5519912340041');
        x("UPDATE mensagem SET template_escritorio_id = {$tFluxo} WHERE id = {$mFluxo2}");
        $responde('5519912340041', 'confirmo');
        $rodar();
        igual('fluxo: sem ignorar maiusculas, minuscula nao confirma', EstadoAgenda::AGENDADO, $estado($agFluxo2));
    } finally {
        x("DELETE FROM mensagem WHERE template_escritorio_id IN ({$tOk}, {$tFluxo})");
        x("DELETE FROM template_escritorio WHERE id IN ({$tFluxo}, {$tOk})");
    }

    // tela de cadastro: categoria e fluxo, sem o campo chave
    ob_start();
    (new TemplateEscritorioForm([]))->show();
    $html = ob_get_clean();
    ok('cadastro: categoria e respostas', strpos($html, 'name="categoria"') !== false && strpos($html, 'name="respostas_confirmar"') !== false && strpos($html, 'name="respostas_cancelar"') !== false && strpos($html, 'name="ignorar_maiusculas"') !== false);
    ok('cadastro: a chave nao aparece', (bool) preg_match('~<input[^>]*name="chave"[^>]*type="hidden"|<input[^>]*type="hidden"[^>]*name="chave"~', $html));

    // ---------- tela de envio: ambiente de teste x producao ----------
    [$agEnvio, $mEnvio] = $novo('ENVIO', '5519912340020');
    $clienteEnvio = (int) q("SELECT cliente_id c FROM agendamento WHERE id = {$agEnvio}")[0]['c'];
    x("UPDATE pessoa SET aceita_receber_mensagen_whatsapp = 'T', telefone = '19987650021' WHERE id = {$clienteEnvio}");
    x("INSERT INTO pessoa_contato (pessoa_id, nome, descricao, telefone, principal) VALUES ({$clienteEnvio}, 'TITULAR', 'CELULAR', '19987650021', 'S')");
    x("INSERT INTO pessoa_contato (pessoa_id, nome, descricao, telefone, principal) VALUES ({$clienteEnvio}, 'FILHA', 'RECADO', '19987650022', 'N')");
    $filha = (int) q("SELECT id FROM pessoa_contato WHERE pessoa_id = {$clienteEnvio} AND nome = 'FILHA'")[0]['id'];
    $tela = function (array $p) { ob_start(); $pg = new EnviarMensagemForm($p); $pg->onShow($p); $pg->show(); return ob_get_clean(); };
    $param = ['tipo' => 'WHATSAPP', 'agendamento_id' => $agEnvio];
    $templateConfirmacao = $confirmacaoId;
    $enviar = function (array $extra) use ($param, $templateConfirmacao) {
        ob_start();
        EnviarMensagemForm::onSend($param + $extra + ['template' => $templateConfirmacao]);
        return ob_get_clean();
    };

    AmbienteService::forcarProducao(false);
    $html = $tela($param);
    ok('teste: mostra os contatos, nome | telefone | descricao, com a marca so no principal', strpos($html, 'TITULAR | (19) 98765-0021 | CELULAR [principal]') !== false && strpos($html, 'FILHA | (19) 98765-0022 | RECADO<') !== false);
    preg_match_all('~<script[^>]*>(.*?)</script>~s', $html, $scripts);
    ok('tela: sem comentario de linha nos scripts (o TScript junta tudo em uma linha)', !preg_grep('~(?<![:'"])//~', $scripts[1]));
    ok('tela: selo verde do principal', strpos($html, '#4CAF50') !== false && strpos($html, 'select2') !== false);
    ok('tela: botao Visualizar', strpos($html, 'name="btn_visualizar"') !== false && strpos($html, 'id="mensagem_visualizacao"') !== false);
    ob_start();
    EnviarMensagemForm::onVisualizar($param + ['content_whatsapp' => 'Oi {$cliente}, dia {$data_inicial}']);
    $s = ob_get_clean();
    ok('Visualizar: troca as variaveis pelo caso real', strpos($s, 'Oi ' . MARCA_RESP . ' ENVIO, dia ') !== false && strpos($s, '$cliente') === false, substr($s, 0, 200));
    ok('teste: pede o numero digitado', strpos($html, 'name="destino"') !== false && strpos($html, 'Ambiente de teste') !== false);
    ok('tela: Manual esconde a mensagem pronta e mostra o texto pelas linhas do formulario', strpos($html, 'id="mensagem_preview"') !== false && strpos($html, 'id="caixa_variaveis"') !== false && strpos($html, "closest('.tformrow')") !== false);
    ok('tela: Manual ao lado do template', strpos($html, 'name="manual"') !== false && strpos($html, 'name="template"') !== false);
    ok('tela: template padrao do Agendado e a confirmacao', strpos($html, 'Responda SIM ou NÃO') !== false || strpos($html, 'Responda *SIM*') !== false);
    $enviadas = [];
    $s = $enviar(['contato' => (string) $filha, 'destino' => '']);
    igual('teste: sem digitar nao envia, mesmo com contato escolhido', 0, count($enviadas));
    ok('teste: avisa', strpos($s, 'Ambiente de teste') !== false);
    $s = $enviar(['contato' => (string) $filha, 'destino' => '19987650022']);
    igual('teste: numero do cliente digitado nao envia', 0, count($enviadas));
    $enviar(['contato' => (string) $filha, 'destino' => '19912340099']);
    igual('teste: vai so para o digitado', ['5519912340099'], array_column($enviadas, 'number'));

    AmbienteService::forcarProducao(true);
    $html = $tela($param);
    ok('producao: sem o campo de digitar', strpos($html, 'name="destino"') === false && strpos($html, 'Ambiente de teste') === false);
    ok('producao: contatos com o principal', strpos($html, 'TITULAR | (19) 98765-0021 | CELULAR [principal]') !== false);
    $enviadas = [];
    $enviar(['contato' => (string) $filha]);
    igual('producao: vai para o contato escolhido', ['5519987650022'], array_column($enviadas, 'number'));
    $enviadas = [];
    $enviar(['contato' => '999999999']);
    igual('producao: contato de outra pessoa nao envia', 0, count($enviadas));

    // Manual: texto editado, variaveis trocadas e sem pedir SIM nao espera resposta
    $enviadas = [];
    $enviar(['contato' => (string) $filha, 'manual' => 'S', 'content_whatsapp' => 'Bom dia {$cliente}, segue o endereço']);
    igual('manual: texto editado com variaveis trocadas', 'Bom dia ' . MARCA_RESP . ' ENVIO, segue o endereço', $enviadas[0]['body'] ?? '');
    igual('manual sem pedir SIM: nao fica esperando resposta', [''], array_column(q("SELECT COALESCE(template_escritorio_id::text, '') t FROM mensagem WHERE agendamento_id = {$agEnvio} ORDER BY id DESC LIMIT 1"), 't'));
    $enviadas = [];
    $enviar(['contato' => (string) $filha, 'template' => '', 'manual' => 'S', 'content_whatsapp' => 'Do zero']);
    igual('manual sem template: escreve do zero', 'Do zero', $enviadas[0]['body'] ?? '');
    $enviadas = [];
    $s = $enviar(['contato' => (string) $filha, 'template' => '']);
    igual('sem template e sem Manual: nao envia', 0, count($enviadas));
    AmbienteService::forcarProducao(false);
} finally {
    ApchatBancoService::usarFalso(null);
    ApchatApiService::usarTransporte(null);
    AmbienteService::forcarProducao(null);
    limparResposta();
}

fim();
