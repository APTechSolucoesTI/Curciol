<?php
// tests/portal/ApchatEspelhoTest.php
// 04/11/2026: o espelho guarda todos os campos que a Curciol manda, no formato que o APChat grava (a protecao compara com eles).
require __DIR__ . '/../bootstrap.php';

igual('pessoa fisica', ['cpf' => '11144477735', 'primeiro_nome' => 'ANA', 'ultimo_nome' => 'DA SILVA', 'razao_social' => null, 'nascimento' => '1980-02-03'],
    ApchatBancoService::camposDoEspelho(['cpf' => '111.444.777-35', 'firstName' => ' ANA ', 'lastName' => 'DA SILVA', 'birthdayDate' => '1980-02-03']));
igual('pessoa juridica', ['cpf' => null, 'primeiro_nome' => null, 'ultimo_nome' => null, 'razao_social' => 'EMPRESA', 'nascimento' => null],
    ApchatBancoService::camposDoEspelho(['businessName' => 'EMPRESA']));
igual('contato nao principal: so nome, numero e e-mail', ['cpf' => null, 'primeiro_nome' => null, 'ultimo_nome' => null, 'razao_social' => null, 'nascimento' => null],
    ApchatBancoService::camposDoEspelho(['name' => 'FILHA', 'number' => '5519900000000']));
// nascimento vai em aaaa-mm-dd: com dd/mm/aaaa o APChat inverte dia e mes
TTransaction::open('escritorio');
$p = new Pessoa();
$p->nome = 'ANA DA SILVA';
$p->tipo_pessoa_id = TipoPessoa::FISICA;
$p->dt_nascimento_abertura = '1980-02-03';
igual('nascimento enviado ao APChat', '1980-02-03', APChatContactService::montarContato($p)['birthdayDate'] ?? null);
TTransaction::rollback();

fim();
