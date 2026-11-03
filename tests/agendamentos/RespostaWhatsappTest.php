<?php
// tests/agendamentos/RespostaWhatsappTest.php
// Resposta SIM/NAO ao WhatsApp de confirmacao (03/11/2026; ApchatRespostaService): SIM confirma (com atendimento) e
// manda a "confirmacao recebida", NAO cancela e agradece, outra resposta nao faz nada, so a ultima confirmacao do numero
// vale, agendamento que ja mudou encerra, erro no envio da resposta desfaz e tenta de novo.
// NUNCA fala com o APChat de verdade: banco do APChat falso e API falsa.
require __DIR__ . '/../bootstrap.php';

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
    x("DELETE FROM atendimento_procedimento WHERE atendimento_id IN (SELECT id FROM atendimento WHERE agendamento_id IN ({$lista}))");
    x("DELETE FROM atendimento WHERE agendamento_id IN ({$lista})");
    x("DELETE FROM agendamento WHERE id IN ({$lista})");
    x("DELETE FROM pessoa WHERE nome LIKE '" . MARCA_RESP . "%'");
}
limparResposta();

// ---------- banco do APChat falso: a primeira resposta de cada numero ----------
class ApchatBancoRespostasFalso
{
    public $respostas = [];
    public function disponivel() { return true; }
    public function primeiraRespostaDepois(array $numeros, string $desde)
    {
        foreach ($numeros as $n) { if (isset($this->respostas[$n])) { return $this->respostas[$n]; } }
        return null;
    }
}
$banco = new ApchatBancoRespostasFalso();
ApchatBancoService::usarFalso($banco);
$responde = function (string $numero, string $texto) use ($banco) {
    $banco->respostas[$numero] = ['id' => 1, 'body' => $texto, 'mediaType' => 'chat', 'createdAt' => date('Y-m-d H:i:s')];
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
    foreach (['sim', 'Sim', 'SIM', ' sim ', 'Sim!', 'sim.', '*SIM*', 'Sim 👍'] as $t) { igual("SIM: '{$t}'", 'SIM', ApchatRespostaService::classificar($t)); }
    foreach (['não', 'nao', 'NÃO', 'Nao.', 'NAO!'] as $t) { igual("NAO: '{$t}'", 'NAO', ApchatRespostaService::classificar($t)); }
    foreach (['Sim, mas posso mudar o horário?', 'simm', 'sim sim', 's', 'ok', '', 'não posso'] as $t) { igual("OUTRA: '{$t}'", 'OUTRA', ApchatRespostaService::classificar($t)); }
    // assinatura de quem responde por um sistema de atendimento (caso real do teste de 03/11/2026)
    igual('assinatura: "*Henrique Rufino*:\n sim"', 'SIM', ApchatRespostaService::classificar("*Henrique Rufino*:\n sim"));
    igual('assinatura: "*Ana*: Não!"', 'NAO', ApchatRespostaService::classificar('*Ana*: Não!'));
    igual('assinatura com outra coisa', 'OUTRA', ApchatRespostaService::classificar("*Ana*:\n sim, mas depois"));

    ok('templates de resposta existem (migracao)', $confirmacaoId > 0 && count(q("SELECT 1 FROM template_escritorio WHERE chave IN ('WHATSAPP_RESPOSTA_SIM', 'WHATSAPP_RESPOSTA_NAO')")) >= 2);

    [$agSim, $mSim] = $novo('SIM', '5519912340001');
    [$agNao, $mNao] = $novo('NAO', '5519912340002');
    [$agOutra, $mOutra] = $novo('OUTRA', '5519912340003');

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

    // ---------- outra resposta ----------
    $responde('5519912340003', 'Sim, mas posso mudar o horário?');
    $enviadas = [];
    $rodar();
    igual('OUTRA: agendamento continua Agendado', EstadoAgenda::AGENDADO, $estado($agOutra));
    igual('OUTRA: registrada', ['resposta' => 'OUTRA', 'resposta_texto' => 'Sim, mas posso mudar o horário?'], $resposta($mOutra));
    igual('OUTRA: nenhuma mensagem automatica', 0, count($enviadas));

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
    (new AgendamentoFormView(['key' => $agOutra]))->show();
    $html = ob_get_clean();
    ok('tela: mostra a resposta do cliente', strpos($html, 'Confirmação por WhatsApp enviada') !== false && strpos($html, 'posso mudar o horário') !== false);
} finally {
    ApchatBancoService::usarFalso(null);
    ApchatApiService::usarTransporte(null);
    AmbienteService::forcarProducao(null);
    limparResposta();
}

fim();
