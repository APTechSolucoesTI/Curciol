<?php
// tests/apchat/ApchatVisibilidadeTest.php
// Regra de quem ve qual ticket do APChat (13/11/2026; relatorio docs/apchat-2026-11-13, secao 1.2), a mesma do APChat:
// canal e fila sao filtros independentes, lista vazia = sem restricao, admin ve tudo, excecoes (sem atendente, dele, convite,
// carteira, grupo delegado) e as configuracoes da empresa.
require_once __DIR__ . '/../bootstrap.php';

// configuracao do app1 (tenant 1) em 13/11/2026
$app1 = ['nullTickets' => 'enabled', 'showClosedForAll' => 'enabled', 'showGroupsForAll' => 'enabled', 'supervisorAdmin' => 'disabled',
         'inboundByQueueOnly' => 'disabled', 'supervisorChannelScoped' => 'disabled', 'NotViewAssignedTickets' => 'disabled', 'NotViewTicketsChatBot' => 'disabled'];
$cfg = function (array $muda = []) use ($app1) { return array_merge($app1, $muda); };
$u = function (array $muda = []) { return array_merge(['id' => 5, 'perfil' => 'user', 'configs' => [], 'ver_todos' => false, 'canais' => [113], 'filas' => [1]], $muda); };
$t = function (array $muda = []) { return array_merge(['id' => 1, 'status' => 'open', 'userId' => 7, 'queueId' => 1, 'whatsappId' => 113, 'isGroup' => false,
                                                       'shared' => false, 'userIdArray' => null, 'groupUserIdArray' => null, 'chatFlowId' => null, 'carteira' => []], $muda); };
$ve = function (array $ticket, array $usuario, array $config) { return ApchatVisibilidadeService::podeVer($ticket, $usuario, $config); };

// tabela 1.3 do relatorio
ok('canal e fila dele, do colega', $ve($t(), $u(), $cfg()));
ok('fila fora das dele e com atendente: nao', !$ve($t(['queueId' => 2]), $u(), $cfg()));
ok('fila fora das dele, sem atendente, nullTickets ligado', $ve($t(['queueId' => 2, 'userId' => null]), $u(), $cfg()));
ok('fila fora das dele, sem atendente, nullTickets desligado: nao', !$ve($t(['queueId' => 2, 'userId' => null]), $u(), $cfg(['nullTickets' => 'disabled'])));
ok('canal fora da lista dele: nao', !$ve($t(['whatsappId' => 112]), $u(), $cfg()));
ok('sem canal permitido = qualquer canal', $ve($t(['whatsappId' => 112]), $u(['canais' => []]), $cfg()));
ok('sem fila = qualquer fila', $ve($t(['queueId' => 2]), $u(['filas' => []]), $cfg()));
ok('admin ve tudo', $ve($t(['whatsappId' => 112, 'queueId' => 2]), $u(['perfil' => 'admin']), $cfg()));
ok('superadmin ve tudo', $ve($t(['whatsappId' => 112, 'queueId' => 2]), $u(['perfil' => 'superadmin']), $cfg()));
ok('ticket dele em canal nao permitido: nao (o canal vem antes)', !$ve($t(['whatsappId' => 112, 'userId' => 5]), $u(), $cfg()));

// excecoes do filtro de fila
ok('dele, fora das filas', $ve($t(['queueId' => 2, 'userId' => 5]), $u(), $cfg()));
ok('convite (shared + userIdArray)', $ve($t(['queueId' => 2, 'shared' => true, 'userIdArray' => '[5, 8]']), $u(), $cfg()));
ok('convite sem shared nao vale', !$ve($t(['queueId' => 2, 'shared' => false, 'userIdArray' => [5]]), $u(), $cfg()));
ok('carteira do contato', $ve($t(['queueId' => 2, 'carteira' => [5]]), $u(), $cfg()));
ok('ticket sem fila e sem atendente (nullTickets)', $ve($t(['queueId' => null, 'userId' => null]), $u(), $cfg()));
ok('ticket sem fila, de outro atendente: nao', !$ve($t(['queueId' => null]), $u(), $cfg()));

// perfis
ok('perfil personalizado com "ver todos"', $ve($t(['whatsappId' => 112, 'queueId' => 2]), $u(['perfil' => 'custom', 'ver_todos' => true]), $cfg()));
ok('supervisor sem "supervisor como admin": regra comum', !$ve($t(['queueId' => 2]), $u(['perfil' => 'super']), $cfg()));
ok('supervisor como admin', $ve($t(['queueId' => 2, 'whatsappId' => 112]), $u(['perfil' => 'super']), $cfg(['supervisorAdmin' => 'enabled'])));
$sup = $u(['perfil' => 'super']);
$supCfg = $cfg(['supervisorAdmin' => 'enabled', 'supervisorChannelScoped' => 'enabled']);
ok('supervisor restrito ao canal: canal dele', $ve($t(['queueId' => 2]), $sup, $supCfg));
ok('supervisor restrito ao canal: outro canal nao', !$ve($t(['queueId' => 2, 'whatsappId' => 112]), $sup, $supCfg));
$dept = $u(['perfil' => 'super', 'configs' => ['supervisorViewDept' => true]]);
$admCfg = $cfg(['supervisorAdmin' => 'enabled']);
ok('supervisor restrito ao departamento: fila dele', $ve($t(['whatsappId' => 112]), $dept, $admCfg));
ok('supervisor restrito ao departamento: outra fila nao', !$ve($t(['queueId' => 2]), $dept, $admCfg));
ok('supervisor restrito ao departamento: o dele em outra fila', $ve($t(['queueId' => 2, 'userId' => 5]), $dept, $admCfg));

// robo
$robo = $t(['userId' => null, 'queueId' => null, 'chatFlowId' => 3, 'status' => 'pending']);
ok('robo: "nao ver no chatbot" esconde ate do admin', !$ve($robo, $u(['perfil' => 'admin']), $cfg(['NotViewTicketsChatBot' => 'enabled'])));
ok('robo: opcao desligada, admin ve', $ve($robo, $u(['perfil' => 'admin']), $cfg()));

// receptivo por fila: o canal nao filtra
ok('receptivo por fila: canal fora da lista, fila dele', $ve($t(['whatsappId' => 112]), $u(), $cfg(['inboundByQueueOnly' => 'enabled'])));

// "nao visualizar ja atribuidos"
$nva = $cfg(['NotViewAssignedTickets' => 'enabled']);
ok('nao ver atribuidos: do colega na fila dele, nao', !$ve($t(), $u(), $nva));
ok('nao ver atribuidos: sem atendente', $ve($t(['userId' => null]), $u(), $nva));
ok('nao ver atribuidos: o dele', $ve($t(['userId' => 5]), $u(), $nva));
ok('nao ver atribuidos: convite', $ve($t(['shared' => true, 'userIdArray' => [5]]), $u(), $nva));
ok('nao ver atribuidos: fechado com "fechados para todos"', $ve($t(['status' => 'closed']), $u(), $nva));
ok('nao ver atribuidos: fechado sem "fechados para todos", nao', !$ve($t(['status' => 'closed']), $u(), $cfg(['NotViewAssignedTickets' => 'enabled', 'showClosedForAll' => 'disabled'])));

// grupos de WhatsApp
ok('grupo delegado a outros: nao', !$ve($t(['isGroup' => true, 'groupUserIdArray' => [9]]), $u(), $cfg()));
ok('grupo delegado a ele', $ve($t(['isGroup' => true, 'groupUserIdArray' => '[5]', 'queueId' => 2]), $u(), $cfg()));
ok('grupo, grupos para todos', $ve($t(['isGroup' => true, 'queueId' => 2]), $u(), $cfg()));
ok('grupo sem "grupos para todos", fila de outro: nao', !$ve($t(['isGroup' => true, 'queueId' => 2]), $u(), $cfg(['showGroupsForAll' => 'disabled'])));
ok('grupo sem "grupos para todos", fila dele', $ve($t(['isGroup' => true]), $u(), $cfg(['showGroupsForAll' => 'disabled'])));
ok('grupo de outro atendente com "nao ver atribuidos": nao', !$ve($t(['isGroup' => true]), $u(), $nva));

// valores como o APChat guarda
ok('ligado: enabled/true/1', ApchatVisibilidadeService::ligado('enabled') && ApchatVisibilidadeService::ligado('true') && ApchatVisibilidadeService::ligado(true) && ApchatVisibilidadeService::ligado('1'));
ok('desligado: disabled/vazio/null', !ApchatVisibilidadeService::ligado('disabled') && !ApchatVisibilidadeService::ligado('') && !ApchatVisibilidadeService::ligado(null));

fim();
