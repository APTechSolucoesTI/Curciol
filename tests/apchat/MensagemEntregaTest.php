<?php
// tests/apchat/MensagemEntregaTest.php
// Entrega do WhatsApp da Curciol (10/11/2026).
// 2b: a API recusa o envio -> a tela mostra a falha, nada no historico, agendamento no mesmo estado.
// 2c: enviada = aguardando entrega; 2 tiques no APChat = entregue; 24 h sem os 2 tiques = nao entregue + aviso no sino para quem
//     enviou, o colaborador da agenda e quem criou o agendamento (sem repetir); o agendamento nao muda de estado.
// API e banco do APChat falsos.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_ENT = 'TESTE ENTREGA WHATSAPP';
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }
function avisos(): array { TTransaction::open('communication'); $r = TTransaction::get()->query("SELECT system_user_to_id FROM system_notification WHERE message LIKE '%" . MARCA_ENT . "%' ORDER BY system_user_to_id")->fetchAll(PDO::FETCH_COLUMN); TTransaction::close(); return array_map('intval', $r); }
function limparAvisos(): void { TTransaction::open('communication'); TTransaction::get()->exec("DELETE FROM system_notification WHERE message LIKE '%" . MARCA_ENT . "%'"); TTransaction::close(); }

// sobras de execucao interrompida
foreach (q("SELECT id FROM pessoa WHERE nome = '" . MARCA_ENT . "'") as $s)
{
    x("DELETE FROM mensagem WHERE agendamento_id IN (SELECT id FROM agendamento WHERE cliente_id = {$s['id']})");
    x("DELETE FROM estado_agendamento WHERE agendamento_id IN (SELECT id FROM agendamento WHERE cliente_id = {$s['id']})");
    x("DELETE FROM agendamento WHERE cliente_id = {$s['id']}");
    x("DELETE FROM pessoa_contato WHERE pessoa_id = {$s['id']}");
    x("DELETE FROM pessoa WHERE id = {$s['id']}");
}
limparAvisos();

$apchat = ['mensagens' => []];
ApchatBancoService::usarFalso(new class($apchat) {
    public $a; public function __construct(&$a) { $this->a = &$a; }
    public function disponivel() { return true; }
    public function mensagensEnviadasPara(array $numeros, string $desde, string $ate): array {
        return array_values(array_filter($this->a['mensagens'], function ($m) use ($numeros, $desde, $ate) {
            return in_array($m['numero'], $numeros, true) && $m['createdAt'] >= $desde && $m['createdAt'] <= $ate;
        }));
    }
});

$pessoa = null;
try
{
    $agenda = q('SELECT a.id, p.system_users_id AS colaborador FROM agenda a JOIN pessoa p ON p.id = a.profissional_id WHERE p.system_users_id IS NOT NULL ORDER BY a.id LIMIT 1')[0];
    $usuarios = array_map('intval', array_column(q("SELECT id FROM system_users WHERE active = 'Y' AND id <> " . (int) $agenda['colaborador'] . ' ORDER BY id LIMIT 2'), 'id'));
    [$remetente, $criador] = $usuarios;
    $colaborador = (int) $agenda['colaborador'];
    TSession::setValue('userid', $remetente);

    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $pessoa = (int) $c->query("INSERT INTO pessoa (nome, tipo_pessoa_id, aceita_receber_mensagen_whatsapp, telefone) VALUES ('" . MARCA_ENT . "', 1, 'T', '19987651111') RETURNING id")->fetchColumn();
    $c->exec("INSERT INTO pessoa_contato (pessoa_id, nome, telefone, principal) VALUES ({$pessoa}, '" . MARCA_ENT . "', '19987651111', 'S')");
    $ini = date('Y-m-d 10:00:00', strtotime('+3 days'));
    $ag = (int) $c->query("INSERT INTO agendamento (cliente_id, estado_agenda_id, agenda_id, dt_inicial, dt_final) VALUES ({$pessoa}, " . (int) EstadoAgenda::AGENDADO . ", {$agenda['id']}, '{$ini}', '" . date('Y-m-d 10:30:00', strtotime('+3 days')) . "') RETURNING id")->fetchColumn();
    $c->exec("INSERT INTO estado_agendamento (agendamento_id, estado_agenda_id, system_users_id, atribuido_em) VALUES ({$ag}, " . (int) EstadoAgenda::AGENDADO . ", {$criador}, now() - interval '2 days')");
    $tpl = (int) $c->query("SELECT id FROM template_escritorio WHERE tipo_template = 'WHATSAPP' AND categoria = 'CONFIRMACAO' AND habilitado = 'T' ORDER BY id LIMIT 1")->fetchColumn();
    $unidade = (int) $c->query('SELECT e.system_unit_id FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id WHERE a.id = ' . (int) $agenda['id'])->fetchColumn();
    TTransaction::close();
    TSession::setValue('userunitid', $unidade);
    tokenApchatDeTeste();

    // ---------- 2b: API recusa -> nada no historico, agendamento igual ----------
    AmbienteService::forcarProducao(true);
    TTransaction::open('escritorio');
    $contatos = MensagemService::contatosParaEnvio(Agendamento::find($ag), 'WHATSAPP');
    TTransaction::close();
    $param = ['agendamento_id' => $ag, 'tipo' => 'WHATSAPP', 'template' => $tpl, 'contato' => $contatos[0]['chave'] ?? ''];
    ApchatApiService::usarTransporte(function () { return ['status' => 403, 'corpo' => '{"error":"Invalid token."}', 'erro' => '']; });
    $s = saida(fn() => EnviarMensagemForm::onSend($param));
    ok('2b: a tela mostra a falha', strpos($s, 'APChat') !== false && strpos($s, '__adianti_error') !== false, substr($s, 0, 300));
    igual('2b: nada no historico', 0, (int) q("SELECT count(*) n FROM mensagem WHERE agendamento_id = {$ag}")[0]['n']);
    igual('2b: agendamento no mesmo estado', (int) EstadoAgenda::AGENDADO, (int) q("SELECT estado_agenda_id e FROM agendamento WHERE id = {$ag}")[0]['e']);

    // enviada: aguardando entrega
    ApchatApiService::usarTransporte(function () { return ['status' => 201, 'corpo' => '{"success":true}', 'erro' => '']; });
    saida(fn() => EnviarMensagemForm::onSend($param));
    $enviada = q("SELECT id, entrega FROM mensagem WHERE agendamento_id = {$ag} ORDER BY id DESC LIMIT 1")[0] ?? [];
    igual('enviada: aguardando entrega', 'A', $enviada['entrega'] ?? null);
    AmbienteService::forcarProducao(null);
    x("DELETE FROM mensagem WHERE agendamento_id = {$ag}");

    // ---------- 2c ----------
    $nova = function (string $texto, string $quando, int $quem, string $tipo = 'WHATSAPP') use ($ag, $tpl): int {
        TTransaction::open('escritorio');
        $st = TTransaction::get()->prepare("INSERT INTO mensagem (agendamento_id, template_escritorio_id, system_user_id, tipo_mensagem, destino, template, enviado_em, entrega)
                                            VALUES (?, ?, ?, ?, '5519987651111', ?, ?, ?) RETURNING id");
        $st->execute([$ag, $tpl, $quem, $tipo, $texto, $quando, $tipo === 'WHATSAPP' ? 'A' : null]);
        $id = (int) $st->fetchColumn();
        TTransaction::close();
        return $id;
    };
    $agora = date('Y-m-d H:i:s');
    $ontem = date('Y-m-d H:i:s', strtotime('-25 hours'));
    $m1 = $nova('Olá, confirma? ' . MARCA_ENT, $agora, $remetente);
    $m2 = $nova('Outra mensagem ' . MARCA_ENT, $agora, $remetente);
    $m3 = $nova('Ontem sem entrega ' . MARCA_ENT, $ontem, $remetente);
    $m4 = $nova('Entregue tarde ' . MARCA_ENT, $ontem, $remetente);
    $m5 = $nova('E-mail ' . MARCA_ENT, $ontem, $remetente, 'EMAIL');
    $apchat['mensagens'] = [
        ['id' => 'apc-1', 'messageId' => 'wamid-1', 'numero' => '5519987651111', 'body' => "Olá,  confirma?\n" . MARCA_ENT, 'ack' => 2, 'createdAt' => date('Y-m-d H:i:s', strtotime('+20 seconds'))],
        ['id' => 'apc-x', 'messageId' => 'wamid-x', 'numero' => '5519987651111', 'body' => 'Texto que nao e da Curciol', 'ack' => 3, 'createdAt' => $agora],
        ['id' => 'apc-3', 'messageId' => 'wamid-3', 'numero' => '5519987651111', 'body' => 'Ontem sem entrega ' . MARCA_ENT, 'ack' => 1, 'createdAt' => $ontem],
        ['id' => 'apc-4', 'messageId' => 'wamid-4', 'numero' => '5519987651111', 'body' => 'Entregue tarde ' . MARCA_ENT, 'ack' => 3, 'createdAt' => $ontem],
    ];

    $r = MensagemEntregaService::rodada();
    $sit = function (int $id) { return q("SELECT entrega, apchat_mensagem_id FROM mensagem WHERE id = {$id}")[0]; };
    igual('2 tiques: entregue', 'E', $sit($m1)['entrega']);
    igual('guarda o id da mensagem no APChat', 'apc-1', $sit($m1)['apchat_mensagem_id']);
    igual('outro texto nao casa: continua aguardando', 'A', $sit($m2)['entrega']);
    igual('24 h sem os 2 tiques: nao entregue', 'N', $sit($m3)['entrega']);
    igual('entregue tarde (antes da conferencia de 24 h): entregue', 'E', $sit($m4)['entrega']);
    igual('e-mail nao entra', null, $sit($m5)['entrega']);
    igual('agendamento nao muda de estado', (int) EstadoAgenda::AGENDADO, (int) q("SELECT estado_agenda_id e FROM agendamento WHERE id = {$ag}")[0]['e']);
    $esperado = array_values(array_unique([$remetente, $colaborador, $criador]));
    sort($esperado);
    igual('aviso para quem enviou, o colaborador da agenda e quem criou', $esperado, avisos());
    ok('resumo da rodada', ($r['entregues'] ?? 0) === 2 && ($r['nao_entregues'] ?? 0) === 1, json_encode($r));

    MensagemEntregaService::rodada();
    igual('segunda rodada nao repete o aviso', $esperado, avisos());

    // mesma pessoa nos tres papeis: um aviso so
    limparAvisos();
    x("UPDATE estado_agendamento SET system_users_id = {$colaborador} WHERE agendamento_id = {$ag}");
    $m6 = $nova('Mesma pessoa ' . MARCA_ENT, $ontem, $colaborador);
    MensagemEntregaService::rodada();
    igual('mesma pessoa nos tres papeis: um aviso so', [$colaborador], avisos());

    TTransaction::open('escritorio');
    ok('historico mostra a situacao', strpos((string) Mensagem::find($m3)->template_formatado, 'Não entregue') !== false
        && strpos((string) Mensagem::find($m1)->template_formatado, 'Entregue') !== false
        && strpos((string) Mensagem::find($m2)->template_formatado, 'Aguardando entrega') !== false);
    TTransaction::close();
}
finally
{
    AmbienteService::forcarProducao(null);
    ApchatBancoService::usarFalso(null);
    limparAvisos();
    if ($pessoa)
    {
        x("DELETE FROM mensagem WHERE agendamento_id IN (SELECT id FROM agendamento WHERE cliente_id = {$pessoa})");
        x("DELETE FROM estado_agendamento WHERE agendamento_id IN (SELECT id FROM agendamento WHERE cliente_id = {$pessoa})");
        x("DELETE FROM agendamento WHERE cliente_id = {$pessoa}");
        x("DELETE FROM pessoa_contato WHERE pessoa_id = {$pessoa}");
        x("DELETE FROM pessoa WHERE id = {$pessoa}");
    }
}

fim();
