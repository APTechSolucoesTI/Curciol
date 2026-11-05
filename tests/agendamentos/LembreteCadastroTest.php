<?php
// tests/agendamentos/LembreteCadastroTest.php
// 04/11/2026: lembrete do agendamento confirmado (so templates de Lembrete, na hora ou agendado; MensagemAgendadaService),
// cadastro incompleto (confirma, mas nao inicia; aviso na tela e na agenda), bloqueio sem agenda no filtro (icone no topo)
// e filtro inicial das telas de contas. NUNCA fala com o APChat de verdade nem manda e-mail: API falsa.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_LEMBRETE = 'TESTE LEMBRETE CADASTRO';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string { ob_start(); $f(); return ob_get_clean(); }

function limparLembrete(): void
{
    $ags = array_column(q("SELECT a.id FROM agendamento a JOIN pessoa p ON p.id = a.cliente_id WHERE p.nome LIKE '" . MARCA_LEMBRETE . "%'"), 'id');
    $lista = $ags ? implode(',', array_map('intval', $ags)) : '0';
    x("DELETE FROM mensagem_agendada WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM mensagem WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM estado_agendamento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM atendimento_historico WHERE atendimento_id IN (SELECT id FROM atendimento WHERE agendamento_id IN ({$lista}))");
    x("DELETE FROM atendimento_procedimento WHERE atendimento_id IN (SELECT id FROM atendimento WHERE agendamento_id IN ({$lista}))");
    x("DELETE FROM atendimento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM agendamento WHERE id IN ({$lista})");
    x("DELETE FROM bloqueio WHERE observacao LIKE '" . MARCA_LEMBRETE . "%'");
    x("DELETE FROM compromisso WHERE observacao LIKE '" . MARCA_LEMBRETE . "%'");
    x("DELETE FROM pessoa_representantes_legais WHERE pessoa_juridica_id IN (SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_LEMBRETE . "%')");
    x("DELETE FROM pessoa_endereco WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_LEMBRETE . "%')");
    x("DELETE FROM pessoa_contato WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_LEMBRETE . "%')");
    x("DELETE FROM pessoa WHERE nome LIKE '" . MARCA_LEMBRETE . "%'");
}
// segura a trava durante o teste: o cron real da homologacao nao pode pegar (e enviar de verdade) o que o teste cria
$travaCron = fopen(MensagemAgendadaService::arquivoDaTrava(), 'c');
flock($travaCron, LOCK_EX);
limparLembrete();

$enviadas = [];
$falharEnvio = false;
ApchatApiService::usarTransporte(function ($url, $corpo, $token) use (&$enviadas, &$falharEnvio) {
    if ($falharEnvio) { return ['status' => 0, 'corpo' => '', 'erro' => 'timeout']; }
    $enviadas[] = $corpo;
    return ['status' => 201, 'corpo' => '{"success":true}', 'erro' => ''];
});
AmbienteService::forcarProducao(false);
TSession::setValue('userid', 1);

$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);
$agendaId = (int) q("SELECT a.id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id WHERE e.system_unit_id = {$unidade} ORDER BY a.id LIMIT 1")[0]['id'];

$cliente = function (string $nome, array $campos = [], int $tipo = 1) {
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare("INSERT INTO pessoa (nome, tipo_pessoa_id, aceita_receber_mensagen_whatsapp) VALUES (?, ?, 'T') RETURNING id");
    $st->execute([MARCA_LEMBRETE . ' ' . $nome, $tipo]);
    $id = (int) $st->fetchColumn();
    foreach ($campos as $c => $v) { $u = TTransaction::get()->prepare("UPDATE pessoa SET {$c} = ? WHERE id = ?"); $u->execute([$v, $id]); }
    TTransaction::close();
    return $id;
};
$agendamento = function (int $pessoa, int $estado, string $quando = '+3 days') use ($agendaId) {
    TTransaction::open('escritorio');
    $ini = date('Y-m-d H:i:00', strtotime($quando));
    $st = TTransaction::get()->prepare('INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES (?, ?, ?, ?, ?) RETURNING id');
    $st->execute([$pessoa, $estado, $agendaId, $ini, date('Y-m-d H:i:00', strtotime($quando . ' +30 minutes'))]);
    $id = (int) $st->fetchColumn();
    TTransaction::close();
    return $id;
};
$situacao = fn(int $id) => q("SELECT situacao, tentativas, motivo, mensagem_id FROM mensagem_agendada WHERE id = {$id}")[0];
// data futura no formato da tela
$tela = fn(string $quando) => date('d/m/Y H:i', strtotime($quando));
$agendar = function (int $ag, string $quando, string $texto = 'Lembrete: {$cliente}', string $destino = '5519912345001', string $tipo = 'WHATSAPP', ?string $titulo = null) {
    TTransaction::open('escritorio');
    try {
        $l = MensagemAgendadaService::agendar(Agendamento::find($ag), $tipo, $titulo, $texto, null, $destino, $quando);
        TTransaction::close();
        return (int) $l->id;
    } catch (Exception $e) {
        TTransaction::rollback();
        return $e->getMessage();
    }
};
$vencer = function (int $id) { x("UPDATE mensagem_agendada SET enviar_em = '" . date('Y-m-d H:i:s', strtotime('-1 minute')) . "' WHERE id = {$id}"); };
$rodar = function () { ob_start(); MensagemAgendadaService::rodada(); return ob_get_clean(); };
$campoCompletos = ['telefone' => '19912345000', 'cpf_cnpj' => '11144477735', 'rg_ie' => '123', 'orgao_emissor' => 'SSP', 'dt_nascimento_abertura' => '1980-01-01'];
$bloqueioAgenda = null;

try {
    // ---------- pendencias do cadastro: os mesmos campos da regra antiga ----------
    $pfVazia = $cliente('PF VAZIA');
    TTransaction::open('escritorio');
    igual('PF vazia: tudo falta', ['telefone', 'endereço principal', 'CPF', 'RG', 'órgão emissor do RG', 'data de nascimento'], AgendamentoService::pendenciasCadastro($pfVazia));
    TTransaction::close();

    $pjVazia = $cliente('PJ VAZIA', [], 2);
    TTransaction::open('escritorio');
    igual('PJ vazia: os campos de PJ', ['telefone', 'endereço principal', 'CNPJ', 'data de abertura', 'representante legal'], AgendamentoService::pendenciasCadastro($pjVazia));
    TTransaction::close();

    // sexo, nacionalidade e estado civil nao contam
    $pfCompleta = $cliente('PF COMPLETA', $campoCompletos);
    TTransaction::open('escritorio');
    TTransaction::get()->prepare('INSERT INTO pessoa_endereco (pessoa_id, principal, cidade_id, rua, numero, bairro, cep) VALUES (?, ?, (SELECT id FROM cidade ORDER BY id LIMIT 1), ?, ?, ?, ?)')->execute([$pfCompleta, 'S', 'Rua', '1', 'Centro', '13000000']);
    igual('PF completa: nada falta', [], AgendamentoService::pendenciasCadastro($pfCompleta));
    // telefone: basta um numero, nos Dados ou em qualquer contato, principal ou nao
    $semTelefone = ['endereço principal', 'CPF', 'RG', 'órgão emissor do RG', 'data de nascimento'];
    $contato = function (int $pessoa, ?string $telefone, ?string $email, string $principal = 'N') {
        TTransaction::get()->prepare('INSERT INTO pessoa_contato (pessoa_id, telefone, email, principal) VALUES (?, ?, ?, ?)')->execute([$pessoa, $telefone, $email, $principal]);
    };
    $pfContato = $cliente('PF CONTATO');
    $contato($pfContato, null, 'teste@example.invalid');
    igual('telefone: contato so com e-mail nao conta', array_merge(['telefone'], $semTelefone), AgendamentoService::pendenciasCadastro($pfContato));
    $contato($pfContato, '0', null);
    igual('telefone: numero zerado nao conta', array_merge(['telefone'], $semTelefone), AgendamentoService::pendenciasCadastro($pfContato));
    $contato($pfContato, '19912345002', null);
    igual('telefone: contato nao principal conta', $semTelefone, AgendamentoService::pendenciasCadastro($pfContato));
    $pfPrincipal = $cliente('PF PRINCIPAL');
    $contato($pfPrincipal, '19912345003', null, 'S');
    igual('telefone: contato principal conta', $semTelefone, AgendamentoService::pendenciasCadastro($pfPrincipal));
    $pfDados = $cliente('PF DADOS', ['telefone' => '19912345004']);
    igual('telefone: nos Dados conta, sem contato', $semTelefone, AgendamentoService::pendenciasCadastro($pfDados));

    igual('por extenso: um campo', 'CPF', AgendamentoService::camposPorExtenso(['CPF']));
    igual('por extenso: dois campos', 'CPF e RG', AgendamentoService::camposPorExtenso(['CPF', 'RG']));
    igual('por extenso: virgula e "e" no ultimo', 'telefone, CPF e RG', AgendamentoService::camposPorExtenso(['telefone', 'CPF', 'RG']));
    igual('varios: so quem tem pendencia', [$pfVazia, $pjVazia], array_keys(AgendamentoService::pendenciasCadastroDe([$pfVazia, $pjVazia, $pfCompleta])));
    TTransaction::close();

    // ---------- confirma com cadastro incompleto; nao inicia ----------
    $agIncompleto = $agendamento($pfVazia, (int) EstadoAgenda::AGENDADO);
    saida(function () use ($agIncompleto) { AgendamentoFormView::onConfirmar(['key' => $agIncompleto]); });
    igual('Confirmar manual com cadastro incompleto: confirma', (int) EstadoAgenda::CONFIRMADO, (int) q("SELECT estado_agenda_id e FROM agendamento WHERE id = {$agIncompleto}")[0]['e']);
    igual('confirmou: atendimento criado', 1, count(q("SELECT 1 FROM atendimento WHERE agendamento_id = {$agIncompleto}")));

    $s = saida(function () use ($agIncompleto) { AgendamentoFormView::onIniciar(['key' => $agIncompleto]); });
    ok('Iniciar bloqueado: diz o que falta, sem a palavra falta', strpos($s, 'Cadastro do cliente incompleto: telefone') !== false && strpos($s, 'órgão emissor do RG e data de nascimento') !== false && strpos($s, 'falta') === false, substr($s, 0, 200));
    igual('Iniciar bloqueado: continua Confirmado', (int) EstadoAgenda::CONFIRMADO, (int) q("SELECT estado_agenda_id e FROM agendamento WHERE id = {$agIncompleto}")[0]['e']);
    ok('Iniciar bloqueado: atendimento nao comeca', q("SELECT dt_inicio FROM atendimento WHERE agendamento_id = {$agIncompleto}")[0]['dt_inicio'] === null);

    $html = saida(function () use ($agIncompleto) { (new AgendamentoFormView(['key' => $agIncompleto]))->show(); });
    ok('tela: mostra o cadastro incompleto e os campos', strpos($html, 'Cadastro incompleto:</b> telefone, endereço principal, CPF, RG, órgão emissor do RG e data de nascimento') !== false);
    ok('tela: botao de completar o cadastro', strpos($html, 'Completar cadastro') !== false && strpos($html, 'onVisualizarCliente') !== false);
    ok('tela: Iniciar desabilitado', preg_match('/onIniciar[^>]*opacity:\.5|opacity:\.5[^>]*onIniciar/', $html) === 1);

    $agOk = $agendamento($pfCompleta, (int) EstadoAgenda::AGENDADO, '+4 days');
    saida(function () use ($agOk) { AgendamentoFormView::onConfirmar(['key' => $agOk]); });
    $html = saida(function () use ($agOk) { (new AgendamentoFormView(['key' => $agOk]))->show(); });
    ok('cadastro completo: sem aviso e Iniciar normal', strpos($html, 'Cadastro incompleto:') === false && strpos($html, 'Completar cadastro') === false && strpos($html, 'opacity:.5') === false);

    // ---------- tela de envio: confirmado so oferece Lembrete; agendado oferece os demais ----------
    TTransaction::open('escritorio');
    $doConfirmado = MensagemService::templatesDoAgendamento(Agendamento::find($agOk), 'WHATSAPP');
    $doConfirmadoEmail = MensagemService::templatesDoAgendamento(Agendamento::find($agOk), 'EMAIL');
    TTransaction::close();
    ok('confirmado: ha lembrete de WhatsApp e de e-mail', count($doConfirmado) >= 1 && count($doConfirmadoEmail) >= 1);
    $so = true;
    foreach ($doConfirmado + $doConfirmadoEmail as $rotulo) { $so = $so && strpos($rotulo, 'Lembrete - ') === 0; }
    ok('confirmado: so templates de Lembrete', $so, json_encode($doConfirmado));

    $agAberto = $agendamento($pfCompleta, (int) EstadoAgenda::AGENDADO, '+5 days');
    $htmlAberto = saida(function () use ($agAberto) { $p = ['agendamento_id' => $agAberto, 'tipo' => 'WHATSAPP']; $pg = new EnviarMensagemForm($p); $pg->onShow($p); $pg->show(); });
    ok('agendado: sem opcao de agendar', strpos($htmlAberto, 'name="agendar"') === false && strpos($htmlAberto, 'Confirmação - ') !== false);
    $htmlLembrete = saida(function () use ($agOk) { $p = ['agendamento_id' => $agOk, 'tipo' => 'WHATSAPP']; $pg = new EnviarMensagemForm($p); $pg->onShow($p); $pg->show(); });
    ok('confirmado: com a opcao de agendar e so Lembrete no combo', strpos($htmlLembrete, 'name="agendar"') !== false && strpos($htmlLembrete, 'name="enviar_em"') !== false && strpos($htmlLembrete, 'Confirmação - ') === false && strpos($htmlLembrete, 'Lembrete - ') !== false);
    ok('sem comentario de linha no script da tela', strpos($htmlLembrete, 'curciolAgendar') !== false);

    // ---------- agendar: validacoes ----------
    ok('agendar: so confirmado', strpos((string) $agendar($agAberto, $tela('+1 day')), 'confirmado') !== false);
    ok('agendar: data obrigatoria', strpos((string) $agendar($agOk, ''), 'data e a hora') !== false);
    ok('agendar: data invalida', strpos((string) $agendar($agOk, '31/02/2030 10:00'), 'data e a hora') !== false);
    ok('agendar: nao no passado', strpos((string) $agendar($agOk, $tela('-1 hour')), 'no futuro') !== false);
    ok('agendar: antes do agendamento', strpos((string) $agendar($agOk, $tela('+10 days')), 'antes do horário') !== false);
    ok('agendar: em teste, numero obrigatorio', strpos((string) $agendar($agOk, $tela('+1 day'), 'x', ''), 'Ambiente de teste') !== false);
    ok('agendar: em teste, nunca o numero do cliente', strpos((string) $agendar($agOk, $tela('+1 day'), 'x', '19912345000'), 'Ambiente de teste') !== false);
    ok('agendar: e-mail pede titulo', strpos((string) $agendar($agOk, $tela('+1 day'), 'x', 'teste@exemplo.com', 'EMAIL'), 'Título') !== false);

    // ---------- envio pelo cron ----------
    $l1 = $agendar($agOk, $tela('+1 day'));
    ok('agendou', is_int($l1), (string) $l1);
    $rodar();
    igual('antes da hora: nao envia', 0, count($enviadas));
    igual('antes da hora: continua pendente', 'PENDENTE', $situacao($l1)['situacao']);

    $html = saida(function () use ($agOk) { (new AgendamentoFormView(['key' => $agOk]))->show(); });
    ok('tela: lembrete agendado com botao de cancelar', strpos($html, 'agendado para') !== false && strpos($html, 'onCancelarLembrete') !== false);

    $vencer($l1);
    x("UPDATE pessoa SET nome = '" . MARCA_LEMBRETE . " PF NOVO NOME' WHERE id = {$pfCompleta}");
    ob_start(); MensagemAgendadaService::processar(); ob_end_clean();
    igual('outro rodando (a trava): pula', 0, count($enviadas));
    $rodar();
    igual('na hora: enviou uma', 1, count($enviadas));
    ok('as variaveis sao trocadas no envio (nome novo)', strpos($enviadas[0]['body'] ?? '', 'PF NOVO NOME') !== false, json_encode($enviadas[0] ?? null));
    igual('foi para o numero de teste', '5519912345001', $enviadas[0]['number'] ?? '');
    $r = $situacao($l1);
    igual('enviada', 'ENVIADA', $r['situacao']);
    igual('ficou em mensagem, com o usuario que agendou', 1, count(q("SELECT 1 FROM mensagem WHERE id = " . (int) $r['mensagem_id'] . " AND system_user_id = 1")));
    $rodar();
    igual('nao envia de novo', 1, count($enviadas));
    $html = saida(function () use ($agOk) { (new AgendamentoFormView(['key' => $agOk]))->show(); });
    ok('tela: lembrete enviado', strpos($html, 'enviado em') !== false);

    // falha: tenta de novo e, no limite, fica como nao enviado
    $l2 = $agendar($agOk, $tela('+1 day'));
    $vencer($l2);
    $falharEnvio = true;
    $rodar();
    igual('falhou: continua pendente', 'PENDENTE', $situacao($l2)['situacao']);
    igual('falhou: conta a tentativa', 1, (int) $situacao($l2)['tentativas']);
    igual('falhou: desfaz o registro da mensagem', 1, count(q("SELECT 1 FROM mensagem WHERE agendamento_id = {$agOk}")));
    for ($i = 1; $i < MensagemAgendadaService::TENTATIVAS; $i++) { $rodar(); }
    igual('no limite: FALHOU', 'FALHOU', $situacao($l2)['situacao']);
    $falharEnvio = false;
    $html = saida(function () use ($agOk) { (new AgendamentoFormView(['key' => $agOk]))->show(); });
    ok('tela: mostra que nao foi enviado', strpos($html, 'não foi enviado') !== false);

    // cancelar pelo botao
    $l3 = $agendar($agOk, $tela('+1 day'));
    saida(function () use ($agOk, $l3) { AgendamentoFormView::onConfirmarCancelarLembrete(['key' => $agOk, 'lembrete' => $l3]); });
    igual('cancelar: cancelado', 'CANCELADA', $situacao($l3)['situacao']);
    $s = saida(function () use ($agOk, $l3) { AgendamentoFormView::onConfirmarCancelarLembrete(['key' => $agOk, 'lembrete' => $l3]); });
    ok('cancelar de novo: avisa', strpos($s, 'não está mais aguardando') !== false);

    // agendamento que deixou de estar confirmado ou que ja passou: nao sai
    $l4 = $agendar($agOk, $tela('+1 day'));
    x("UPDATE agendamento SET estado_agenda_id = " . (int) EstadoAgenda::CANCELADO . " WHERE id = {$agOk}");
    $vencer($l4);
    $antes = count($enviadas);
    $rodar();
    igual('nao confirmado: nao envia', $antes, count($enviadas));
    igual('nao confirmado: cancela', 'CANCELADA', $situacao($l4)['situacao']);
    x("UPDATE agendamento SET estado_agenda_id = " . (int) EstadoAgenda::CONFIRMADO . " WHERE id = {$agOk}");

    $l5 = $agendar($agOk, $tela('+1 day'));
    $vencer($l5);
    x("UPDATE agendamento SET dt_inicial = '" . date('Y-m-d H:i:s', strtotime('-1 hour')) . "', dt_final = '" . date('Y-m-d H:i:s', strtotime('-30 minutes')) . "' WHERE id = {$agOk}");
    $rodar();
    igual('agendamento ja passou: nao envia', $antes, count($enviadas));
    igual('agendamento ja passou: cancela', 'CANCELADA', $situacao($l5)['situacao']);
    x("UPDATE agendamento SET dt_inicial = '" . date('Y-m-d H:i:00', strtotime('+4 days')) . "', dt_final = '" . date('Y-m-d H:i:00', strtotime('+4 days 30 minutes')) . "' WHERE id = {$agOk}");

    // cancelar a confirmacao leva os lembretes pendentes; reconfirmar nao os traz de volta
    $l6 = $agendar($agOk, $tela('+1 day'));
    TTransaction::open('escritorio');
    AgendamentoService::cancelarConfirmacao(Agendamento::find($agOk), 'teste');
    TTransaction::close();
    $r6 = $situacao($l6);
    igual('cancelar confirmacao: cancela o lembrete', 'CANCELADA', $r6['situacao']);
    igual('cancelar confirmacao: motivo', 'Confirmação cancelada', $r6['motivo']);

    // ---------- agenda: cartao e resumo do confirmado com cadastro incompleto ----------
    $dia = date('Y-m-d', strtotime('+3 days'));
    TSession::setValue('agendamentos_filter_agenda_id', [$agendaId]);
    TSession::setValue('agendamentos_filter_data', (object) ['agenda_id' => [$agendaId]]);
    $eventos = function () use ($dia) {
        $s = saida(function () use ($dia) { AgendamentoCalendarFormView::getEvents(['start' => "{$dia}T00:00:00", 'end' => date('Y-m-d', strtotime($dia . ' +1 day')) . 'T00:00:00']); });
        return json_decode($s, true) ?: [];
    };
    $doAg = function (array $evs, int $id) { foreach ($evs as $e) { if ((string) ($e['id'] ?? '') === (string) $id) { return $e; } } return null; };
    $e = $doAg($eventos(), $agIncompleto);
    ok('agenda: cartao com a classe da borda vermelha', in_array('agd-incompleto', $e['classNames'] ?? [], true), json_encode($e['classNames'] ?? null));
    preg_match("/data-resumo='([^']+)'/", (string) ($e['title'] ?? ''), $m);
    $resumo = base64_decode($m[1] ?? '');
    ok('agenda: resumo comeca pelo cadastro incompleto, em vermelho', strpos($resumo, '>Cadastro Incompleto</div>') !== false && strpos($resumo, 'Cadastro Incompleto') < strpos($resumo, 'Horário') && strpos($resumo, '#d32f2f') !== false);
    ok('agenda: resumo nao lista os campos', strpos($resumo, 'CPF') === false && strpos($resumo, 'falta') === false);

    // agendado (ainda nao confirmado): a marca aparece na agenda, mas os campos so na tela do confirmado
    $agSemConfirmar = $agendamento($pfVazia, (int) EstadoAgenda::AGENDADO, '+3 days 2 hours');
    $e2 = $doAg($eventos(), $agSemConfirmar);
    ok('agenda: agendado tambem recebe a marca', $e2 !== null && in_array('agd-incompleto', $e2['classNames'] ?? [], true));
    $html = saida(function () use ($agSemConfirmar) { (new AgendamentoFormView(['key' => $agSemConfirmar]))->show(); });
    ok('tela do agendado: nao mostra os campos', strpos($html, 'Cadastro incompleto:') === false && strpos($html, 'Completar cadastro') === false);
    $eOk = $doAg($eventos(), $agOk);

    // cancelado futuro: sem a borda; o resumo continua avisando e a tela nao lista os campos
    $agCancelado = $agendamento($pfVazia, (int) EstadoAgenda::CANCELADO, "{$dia} 09:30:00");
    $eCancelado = $doAg($eventos(), $agCancelado);
    ok('agenda: cancelado sem a borda', $eCancelado !== null && !in_array('agd-incompleto', $eCancelado['classNames'] ?? [], true));
    preg_match("/data-resumo='([^']+)'/", (string) ($eCancelado['title'] ?? ''), $mCancelado);
    ok('agenda: cancelado mantem o aviso no resumo', strpos(base64_decode($mCancelado[1] ?? ''), 'Cadastro Incompleto') !== false);
    $html = saida(function () use ($agCancelado) { (new AgendamentoFormView(['key' => $agCancelado]))->show(); });
    ok('tela do cancelado: nao lista os campos', strpos($html, 'Cadastro incompleto:') === false);

    // o mesmo cliente em duas agendas: completar o cadastro uma vez tira o aviso de todos os agendamentos
    $agenda2 = (int) (q("SELECT a.id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id WHERE e.system_unit_id = {$unidade} AND a.id <> {$agendaId} ORDER BY a.id LIMIT 1")[0]['id'] ?? 0);
    ok('existe uma segunda agenda para o teste', $agenda2 > 0);
    $pfDuas = $cliente('PF DUAS AGENDAS');
    $agDuas1 = $agendamento($pfDuas, (int) EstadoAgenda::CONFIRMADO, "{$dia} 10:00:00");
    $agDuas2 = $agendamento($pfDuas, (int) EstadoAgenda::CONFIRMADO, "{$dia} 10:30:00");
    x("UPDATE agendamento SET agenda_id = {$agenda2} WHERE id = {$agDuas2}");
    $filtroAntes = TSession::getValue('agendamentos_filter_agenda_id');
    TSession::setValue('agendamentos_filter_agenda_id', [$agendaId, $agenda2]);
    foreach ([$agDuas1, $agDuas2] as $agDuas) {
        ok("duas agendas: {$agDuas} com a borda antes de completar", in_array('agd-incompleto', $doAg($eventos(), $agDuas)['classNames'] ?? [], true));
    }
    x("UPDATE pessoa SET telefone = '19912345005', cpf_cnpj = '11144477735', rg_ie = '123', orgao_emissor = 'SSP', dt_nascimento_abertura = '1980-01-01' WHERE id = {$pfDuas}");
    x("INSERT INTO pessoa_endereco (pessoa_id, principal, cidade_id, rua, numero, bairro, cep) VALUES ({$pfDuas}, 'S', (SELECT id FROM cidade ORDER BY id LIMIT 1), 'Rua', '1', 'Centro', '13000000')");
    foreach ([$agDuas1, $agDuas2] as $agDuas) {
        $eDuas = $doAg($eventos(), $agDuas);
        ok("duas agendas: {$agDuas} sem a borda depois de completar", $eDuas !== null && !in_array('agd-incompleto', $eDuas['classNames'] ?? [], true));
        preg_match("/data-resumo='([^']+)'/", (string) ($eDuas['title'] ?? ''), $mDuas);
        ok("duas agendas: {$agDuas} sem o aviso no resumo", strpos(base64_decode($mDuas[1] ?? ''), 'Cadastro Incompleto') === false);
        $html = saida(function () use ($agDuas) { (new AgendamentoFormView(['key' => $agDuas]))->show(); });
        ok("duas agendas: {$agDuas} sem o aviso na tela", strpos($html, 'Cadastro incompleto:') === false && strpos($html, 'Completar cadastro') === false);
    }
    TSession::setValue('agendamentos_filter_agenda_id', $filtroAntes);
    ok('agenda: no mes, cadeado na mesma altura do numero do dia', strpos(AgendaVisual::estilo(), '.fc-daygrid-day-top { align-items: center; }') !== false);
    ok('agenda: cadeado do topo sem a interrogacao no mouse', strpos(AgendaVisual::estilo(), 'cursor: help') === false && strpos(AgendaVisual::estilo(), '.agd-bloq-topo { color: #b45309; font-size: 11px; margin-left: 6px; cursor: default; }') !== false);
    foreach (['agd-ev-l1', 'agd-ev-hora', 'agd-ev-l2'] as $linhaCartao) {
        ok("agenda: {$linhaCartao} em 10px", strpos(AgendaVisual::estilo(), ".{$linhaCartao} { display: block; font-size: 10px;") !== false);
    }
    // agendamento que ja terminou nao recebe a marca, em nenhum estado; o que ainda nao terminou recebe, mesmo ja iniciado
    foreach ([EstadoAgenda::ATENDIDO => 'Atendido', EstadoAgenda::CANCELADO => 'Cancelado', EstadoAgenda::CONFIRMADO => 'Confirmado'] as $estadoPassado => $nomeEstado) {
        $agPassado = $agendamento($pfVazia, (int) $estadoPassado, '-2 days');
        $diaPassado = date('Y-m-d', strtotime('-2 days'));
        $evs = json_decode(saida(function () use ($diaPassado) { AgendamentoCalendarFormView::getEvents(['start' => "{$diaPassado}T00:00:00", 'end' => date('Y-m-d', strtotime($diaPassado . ' +1 day')) . 'T00:00:00']); }), true) ?: [];
        $ePassado = $doAg($evs, $agPassado);
        ok("agenda: {$nomeEstado} que ja passou aparece sem a marca", $ePassado !== null && !in_array('agd-incompleto', $ePassado['classNames'] ?? [], true));
        preg_match("/data-resumo='([^']+)'/", (string) ($ePassado['title'] ?? ''), $mPassado);
        ok("agenda: {$nomeEstado} que ja passou sem o aviso no resumo", strpos(base64_decode($mPassado[1] ?? ''), 'Cadastro Incompleto') === false);
    }
    igual('marca: termina no futuro marca', ['CPF'], AgendaVisual::faltamNoCartao([7 => ['CPF']], 7, date('Y-m-d H:i:s', strtotime('+1 minute'))));
    igual('marca: ja terminou nao marca', [], AgendaVisual::faltamNoCartao([7 => ['CPF']], 7, date('Y-m-d H:i:s', strtotime('-1 minute'))));
    ok('agenda: cadastro completo sem marca', $eOk === null || !in_array('agd-incompleto', $eOk['classNames'] ?? [], true));

    // ---------- agenda sem filtro: bloqueio so no topo; intervalo e nao atende fora ----------
    TTransaction::open('escritorio');
    $st = TTransaction::get()->prepare('INSERT INTO bloqueio (agenda_id, dt_inicio, dt_final, observacao) VALUES (?, ?, ?, ?) RETURNING id');
    $st->execute([$agendaId, "{$dia} 22:30:00", "{$dia} 23:00:00", MARCA_LEMBRETE . ' <b>x</b>']);
    $bloqueioAgenda = (int) $st->fetchColumn();
    TTransaction::close();

    $comAgenda = $eventos();
    $achou = array_values(array_filter($comAgenda, fn($v) => ($v['id'] ?? '') === "bloqueio-{$bloqueioAgenda}"));
    ok('com agenda: bloqueio na grade', count($achou) === 1);
    ok('com agenda: intervalo ou fundo da agenda aparece', count(array_filter($comAgenda, fn($v) => !empty($v['display']) && $v['display'] === 'background')) > 0);

    TSession::setValue('agendamentos_filter_agenda_id', null);
    TSession::setValue('agendamentos_filter_data', null);
    $semAgenda = $eventos();
    $marcas = array_values(array_filter($semAgenda, fn($v) => ($v['id'] ?? '') === "bloqueio-topo-{$bloqueioAgenda}"));
    ok('sem agenda: o bloqueio vira marca invisivel', count($marcas) === 1 && ($marcas[0]['display'] ?? '') === 'none' && !empty($marcas[0]['bloqueio_topo']));
    ok('sem agenda: resumo do bloqueio escapado', strpos($marcas[0]['resumo_topo'] ?? '', '&lt;b&gt;x') !== false);
    ok('sem agenda: nenhum bloqueio na grade', count(array_filter($semAgenda, fn($v) => preg_match('/^bloqueio-\d+$/', (string) ($v['id'] ?? '')))) === 0);
    ok('sem agenda: sem intervalo, nao atende e fundo', count(array_filter($semAgenda, fn($v) => ($v['display'] ?? '') === 'background')) === 0);
    ok('sem agenda: agendamento continua', $doAg($semAgenda, $agIncompleto) !== null);

    // as regras de conflito nao dependem do filtro
    TTransaction::open('escritorio');
    $erro = '';
    try { AgendaConflitoService::exigirLivreCompromisso($agendaId, "{$dia} 22:30", "{$dia} 23:00"); } catch (Exception $ex) { $erro = $ex->getMessage(); }
    TTransaction::close();
    ok('sem agenda no filtro: conflito com o bloqueio continua barrando', strpos($erro, 'bloqueio') !== false, $erro);

    // ---------- contas: filtro inicial ----------
    foreach (['ContaPagarList', 'ContaReceberList'] as $lista) {
        FiltroLista::limparSessao($lista);
        $s = saida(function () use ($lista) { $p = new $lista([]); $p->onShow([]); });
        $f = TSession::getValue($lista . '_filter_data');
        igual("{$lista}: primeira vez em aberto", 'N', $f->quitada ?? null);
        igual("{$lista}: um filtro", 1, count(TSession::getValue($lista . '_filters') ?: []));

        // ordem: vencimento exibido, do mais recente para o mais antigo
        $s = saida(function () use ($lista, &$linhas) { $p = new $lista([]); $linhas = $p->onReload([]); });
        $datas = [];
        TTransaction::open('escritorio');
        foreach ($linhas ?: [] as $c) { $datas[] = ContaListaColunas::vencimento($c); }
        TTransaction::close();
        $ordenadas = array_values(array_filter($datas));
        $esperado = $ordenadas;
        rsort($esperado);
        igual("{$lista}: vencimento decrescente", $esperado, $ordenadas);

        // voltar para a tela (onShow de novo, sem abrir aba nova): mantem o que estava
        TSession::setValue($lista . '_filter_data', (object) ['descricao' => 'XYZ']);
        saida(function () use ($lista) { $p = new $lista([]); $p->onShow([]); });
        igual("{$lista}: voltar mantem os filtros", 'XYZ', TSession::getValue($lista . '_filter_data')->descricao ?? null);

        // limpar zera, e voltar continua zerado
        saida(function () use ($lista) { $p = new $lista([]); $p->onClearFilters([]); });
        saida(function () use ($lista) { $p = new $lista([]); $p->onShow([]); });
        ok("{$lista}: limpar zera e voltar continua zerado", empty(TSession::getValue($lista . '_filter_data')) && empty(TSession::getValue($lista . '_filters')));

        // aba nova pelo menu: volta ao padrao
        FiltroLista::limparSessao($lista);
        saida(function () use ($lista) { $p = new $lista([]); $p->onShow([]); });
        igual("{$lista}: aba nova volta para em aberto", 'N', TSession::getValue($lista . '_filter_data')->quitada ?? null);
        FiltroLista::limparSessao($lista);
    }
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    limparLembrete();
    ApchatApiService::usarTransporte(null);
    AmbienteService::forcarProducao(null);
    TSession::setValue('agendamentos_filter_agenda_id', null);
    TSession::setValue('agendamentos_filter_data', null);
    flock($travaCron, LOCK_UN);
    fclose($travaCron);
}

fim();
