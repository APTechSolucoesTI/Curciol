<?php
// tests/seguranca/HomologacaoSemSaidaTest.php
// Fora da producao nada sai da Curciol (09/11/2026, homologacao com copia da producao, inclusive URL e token do
// APChat): nenhum WhatsApp, e-mail ou contato no APChat; so consultas (showcontact). Usa enderecos .invalid: mesmo com a
// trava quebrada, nada chega ao APChat real nem a um SMTP de verdade. Nao grava no banco.
require __DIR__ . '/../bootstrap.php';

$msg = AmbienteService::MSG_SEM_SAIDA;
$url = 'https://apchat.invalid/v2/api/external/teste';

// --- regra das rotas
AmbienteService::forcarProducao(false);
ok('homologacao: envio de mensagem (POST na URL) barrado', !ApchatApiService::chamadaPermitida($url));
ok('homologacao: URL com barra no fim barrada', !ApchatApiService::chamadaPermitida($url . '/'));
ok('homologacao: createContact barrado', !ApchatApiService::chamadaPermitida('createContact'));
ok('homologacao: updateContact barrado', !ApchatApiService::chamadaPermitida('updateContact'));
ok('homologacao: showcontact liberado', ApchatApiService::chamadaPermitida('showcontact'));
ok('homologacao: URL/showcontact liberado', ApchatApiService::chamadaPermitida($url . '/showcontact'));
ok('homologacao: rota parecida nao passa', !ApchatApiService::chamadaPermitida($url . '/showcontactX'));

AmbienteService::forcarProducao(true);
ok('producao: envio liberado', ApchatApiService::chamadaPermitida($url));
ok('producao: createContact liberado', ApchatApiService::chamadaPermitida('createContact'));

// --- caminho real (sem transporte falso) na homologacao
AmbienteService::forcarProducao(false);
ApchatApiService::usarTransporte(null);
APChatContactService::usarTransporte(null);

lanca('WhatsApp nao sai', fn() => ApchatApiService::enviar($url, 'TOKEN-FALSO', '5519900000000', 'oi', 'teste'), $msg);

try { ApchatApiService::testarConexao($url, 'TOKEN-FALSO'); $erro = ''; }
catch (Throwable $e) { $erro = $e->getMessage(); }
ok('testar conexao (consulta) continua saindo', strpos($erro, $msg) === false, $erro);

$podeGravar = new ReflectionMethod('APChatContactService', 'podeGravar');
$podeGravar->setAccessible(true);
ok('contatos: sem gravacao no APChat', $podeGravar->invoke(null) === false);

$novoHandle = new ReflectionMethod('APChatContactService', 'novoHandle');
$novoHandle->setAccessible(true);
lanca('contatos: createContact nao sai', fn() => $novoHandle->invoke(null, 'createContact', [], 'TOKEN-FALSO'), $msg);
lanca('contatos: updateContact nao sai', fn() => $novoHandle->invoke(null, 'updateContact', [], 'TOKEN-FALSO'), $msg);

lanca('banco do APChat: transferencia barrada', fn() => ApchatBancoService::transferir(1, 2), $msg);
lanca('banco do APChat: espelho barrado', fn() => ApchatBancoService::gravarEspelho(1, 'X', '5519900000000', null), $msg);
lanca('banco do APChat: desligar espelho barrado', fn() => ApchatBancoService::desligarEspelho(1), $msg);

$config = new EmailConfig();
$config->from_email = 'teste@curciol.invalid';
$config->from_name = 'Teste';
$config->username = 'teste';
$config->password = 'teste';
$config->host = 'smtp.curciol.invalid';
$config->port = 587;
lanca('e-mail do escritorio nao sai', fn() => (new EmailClient($config))->send('cliente@curciol.invalid', 'T', 'oi'), $msg);
lanca('e-mail de teste do escritorio nao sai', fn() => (new EmailClient($config))->testarEnvio('cliente@curciol.invalid'), $msg);
lanca('e-mail do sistema nao sai', fn() => MailService::send('usuario@curciol.invalid', 'T', 'oi'), $msg);

// com transporte falso (testes) o fluxo de contatos continua exercitado
APChatContactService::usarTransporte(function () { return ['status' => 200, 'corpo' => '{}', 'erro' => '']; });
ok('testes com transporte falso continuam gravando', $podeGravar->invoke(null) === true);

AmbienteService::forcarProducao(null);
fim();
