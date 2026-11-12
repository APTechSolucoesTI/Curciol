<?php
// tests/apmail/ApmailHistoricoTest.php
// Conversas do APMail do cliente (12/11/2026): ligadas pelos e-mails do cadastro e dos contatos, na hora de mostrar; mais recente
// primeiro; conversa de outro cliente recusada; profissionais = responsavel e quem enviou, sem repetir; homologacao restringe.
require __DIR__ . '/../bootstrap.php';

function qh(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function xh(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

const MARCA_APM = 'TESTE HISTORICO APMAIL';
const PFH = '00000000-0000-4000-8000-0000000001';
$limpar = function () {
    xh("DELETE FROM apmail_conversa WHERE thread_id::text LIKE '" . PFH . "%'");
    foreach (qh("SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_APM . "%'") as $p) { xh("DELETE FROM pessoa_contato WHERE pessoa_id = {$p['id']}"); xh("DELETE FROM pessoa WHERE id = {$p['id']}"); }
};
$limpar();
$semConfig = sys_get_temp_dir() . '/apmail-sem-config-' . getmypid() . '.php';
ApmailSincronizacaoService::usarConfig($semConfig);

try
{
    TTransaction::open('escritorio');
    $c = TTransaction::get();
    $a = (int) $c->query("INSERT INTO pessoa (nome, tipo_pessoa_id, email) VALUES ('" . MARCA_APM . " A', 1, ' Cliente.A@Teste.com ') RETURNING id")->fetchColumn();
    $b = (int) $c->query("INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES ('" . MARCA_APM . " B', 1) RETURNING id")->fetchColumn();
    $c->exec("INSERT INTO pessoa_contato (pessoa_id, nome, email, principal) VALUES ({$a}, 'Financeiro', 'fin.a@teste.com', 'N'), ({$b}, 'B', 'cliente.b@teste.com', 'S')");
    $conv = function ($t, $assunto, $ini, $fim, $resp, $emails) use ($c) {
        $id = (int) $c->query("INSERT INTO apmail_conversa (thread_id, assunto, caixa_nome, inicio_em, fim_em, responsavel_nome, qtd_mensagens)
                                VALUES ('" . PFH . $t . "', '{$assunto}', 'Suporte', '{$ini}', '{$fim}', " . ($resp ? "'{$resp}'" : 'NULL') . ", 2) RETURNING id")->fetchColumn();
        foreach ($emails as $e) { $c->exec("INSERT INTO apmail_conversa_email (conversa_id, email) VALUES ({$id}, '{$e}')"); }
        return $id;
    };
    $c1 = $conv('01', 'Pedido de documento', '2026-11-01 10:00', '2026-11-01 12:00', 'Atendente Um', ['cliente.a@teste.com']);
    $c2 = $conv('02', 'Boleto', '2026-11-05 09:00', '2026-11-05 09:30', null, ['fin.a@teste.com', 'cliente.b@teste.com']);
    $c3 = $conv('03', 'Outro assunto', '2026-11-06 09:00', '2026-11-06 09:30', 'Atendente Um', ['cliente.b@teste.com']);
    $msg = function ($conversa, $id, $dir, $quando, $quem) use ($c) {
        $c->exec("INSERT INTO apmail_mensagem (apmail_id, conversa_id, direcao, assunto, de_email, mensagem_em, enviado_por_nome, corpo_texto)
                  VALUES ('" . PFH . $id . "', {$conversa}, '{$dir}', 'x', 'x@x.com', '{$quando}', " . ($quem ? "'{$quem}'" : 'NULL') . ", 'oi')");
    };
    $msg($c1, '11', 'inbound', '2026-11-01 10:00', null);
    $msg($c1, '12', 'outbound', '2026-11-01 11:00', 'Atendente Dois');
    $msg($c1, '13', 'outbound', '2026-11-01 12:00', 'Atendente Um');
    TTransaction::close();

    TTransaction::open('escritorio');
    igual('e-mails do cliente: cadastro e contatos, em minusculas', ['cliente.a@teste.com', 'fin.a@teste.com'], ApmailHistoricoService::emailsDoCliente($a));
    $lista = ApmailHistoricoService::conversas($a);
    igual('conversas do cliente A, mais recente primeiro (ligada pelo contato tambem)', [$c2, $c1], array_map('intval', array_column($lista, 'id')));
    igual('conversas do cliente B (nao ve as so do A)', [$c3, $c2], array_map('intval', array_column(ApmailHistoricoService::conversas($b), 'id')));
    igual('ultimo e-mail da conversa (Abrir no APMail)', PFH . '13', $lista[1]['ultima_mensagem_apmail_id']);

    $d = ApmailHistoricoService::conversa($c1, $a);
    igual('conversa: mensagens em ordem', [PFH . '11', PFH . '12', PFH . '13'], array_column($d['mensagens'], 'apmail_id'));
    igual('profissionais: responsavel e quem enviou, sem repetir', ['Atendente Um', 'Atendente Dois'], $d['profissionais']);
    lanca('conversa de outro cliente: recusada', fn() => ApmailHistoricoService::conversa($c3, $a), 'não é deste cliente');
    TTransaction::close();

    // homologacao: so os e-mails liberados
    $cfg = tempnam(sys_get_temp_dir(), 'apmail-cfg-');
    file_put_contents($cfg, "<?php return ['historico_somente_emails' => ['fin.a@teste.com']];");
    ApmailSincronizacaoService::usarConfig($cfg);
    TTransaction::open('escritorio');
    igual('homologacao: so o e-mail liberado conta', [$c2], array_map('intval', array_column(ApmailHistoricoService::conversas($a), 'id')));
    TTransaction::close();
    @unlink($cfg);
}
finally
{
    while (TTransaction::get()) { TTransaction::rollback(); }
    ApmailSincronizacaoService::usarConfig(null);
    $limpar();
}

fim();
