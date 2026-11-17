<?php
// tests/apchat/ApchatAvisoFilaTest.php
// Aviso ao salvar pessoa quando o contato nao foi atualizado no APChat (17/11/2026): texto simples, sem detalhe tecnico, com o
// motivo e "ficou na fila"; banco do APChat que nao respondeu nao e tentado de novo por 1 minuto (o salvar nao espera 5 s a cada
// vez). Nenhum banco real: endereco que nao responde.
require __DIR__ . '/../bootstrap.php';

$r = function (string $situacao, string $mensagem) { return (object) ['situacao' => $situacao, 'operacao' => 'NENHUMA', 'mensagem' => $mensagem]; };
$erro = ApchatContatoLog::SITUACAO_ERRO;

// ---------- aviso ----------
$banco = APChatContactService::avisoDaTela($r($erro, 'Banco do APChat indisponível: o envio ficou na fila e será refeito sozinho. SQLSTATE[08006] [7] connection to server at "api.apchat.com.br" (46.202.144.41), port 5432 failed: timeout expired'), 'Cliente salvo');
ok('banco fora: sem detalhe tecnico', strpos($banco, 'SQLSTATE') === false && strpos($banco, '46.202') === false && strpos($banco, '5432') === false, $banco);
ok('banco fora: diz o que houve e que ficou na fila', strpos($banco, 'Cliente salvo, mas o contato ainda não foi atualizado no APChat: sem conexão com o APChat no momento.') === 0
    && strpos($banco, 'ficou na fila e será feita sozinha') !== false, $banco);
$token = APChatContactService::avisoDaTela($r($erro, 'Sem conexão com o APChat: token recusado (confira o token em Configurações do escritório).'), 'Registro salvo');
ok('token recusado: aponta o Escritorio', strpos($token, 'Registro salvo, mas') === 0 && strpos($token, 'recusou a integração') !== false && strpos($token, 'Escritório › Configurações › APChat') !== false, $token);
$empresa = APChatContactService::avisoDaTela($r($erro, 'Banco do APChat indisponível: o envio ficou na fila e será refeito sozinho. ' . IntegracaoAcessoService::APCHAT['outra_empresa']), 'Cliente salvo');
ok('integracao de outra empresa: diz isso', strpos($empresa, 'não é da empresa do banco do APChat') !== false, $empresa);
igual('deu certo: nada a avisar', null, APChatContactService::avisoDaTela($r(ApchatContatoLog::SITUACAO_SUCESSO, 'Contato atualizado.')));
igual('ignorado: nada a avisar', null, APChatContactService::avisoDaTela($r(ApchatContatoLog::SITUACAO_IGNORADO, 'Integração desligada.')));

// ---------- banco que nao respondeu: 1 minuto sem tentar ----------
$cfg = sys_get_temp_dir() . '/apchat-aviso-teste-' . getmypid() . '.php';
file_put_contents($cfg, "<?php return ['host' => '10.255.255.1', 'port' => '5432', 'name' => 'x', 'user' => 'x', 'pass' => 'x'];");
try
{
    ApchatBancoService::usarFalso(null);
    ApchatBancoService::desligar(false);
    ApchatBancoService::lembrarFalhaSempre(true);
    ApchatBancoService::usarConfig($cfg);
    ApchatBancoService::esquecerFalha();
    $t = microtime(true);
    $um = ApchatBancoService::disponivel();
    $primeira = microtime(true) - $t;
    ApchatBancoService::usarConfig($cfg); // outro pedido (sem a resposta guardada no processo)
    $t = microtime(true);
    $dois = ApchatBancoService::disponivel();
    $segunda = microtime(true) - $t;
    ok('banco fora: a primeira tentativa espera o limite e falha', !$um && $primeira >= 3, sprintf('%.1f s', $primeira));
    ok('banco fora: a segunda (outro pedido, menos de 1 min) nem tenta', !$dois && $segunda < 0.5, sprintf('%.2f s', $segunda));
    ok('banco fora: o motivo continua disponivel', ApchatBancoService::erro() !== '');
    ApchatBancoService::esquecerFalha();
    ApchatBancoService::usarConfig($cfg);
    $t = microtime(true);
    ApchatBancoService::disponivel();
    ok('depois de esquecer a falha: tenta de novo', microtime(true) - $t >= 3);
}
finally
{
    ApchatBancoService::esquecerFalha();
    ApchatBancoService::lembrarFalhaSempre(false);
    ApchatBancoService::usarConfig(null);
    ApchatBancoService::desligar();
    @unlink($cfg);
}

fim();
