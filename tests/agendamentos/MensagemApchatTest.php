<?php
// tests/agendamentos/MensagemApchatTest.php
// Mensagens do agendamento pelo APChat (03/11/2026): trava do ambiente de teste (destino obrigatorio, nunca o numero
// ou e-mail do cliente), producao (cliente por padrao, autorizacao de WhatsApp), envio pela API com registro em
// mensagem, erros da API e teste de conexao. NUNCA fala com o APChat de verdade (transporte falso) nem envia e-mail;
// tudo numa transacao desfeita no fim.
require __DIR__ . '/../bootstrap.php';

$chamadas = [];
$resposta = ['status' => 201, 'corpo' => '{"success":true}', 'erro' => ''];
ApchatApiService::usarTransporte(function ($url, $corpo, $token) use (&$chamadas, &$resposta) {
    $chamadas[] = ['url' => $url, 'corpo' => $corpo, 'token' => $token];
    return $resposta;
});

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$c = TTransaction::get();

try {
    $escritorio = $c->query('SELECT id, system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    TSession::setValue('userunitid', (int) $escritorio['system_unit_id']);
    $c->exec("UPDATE escritorio SET token_apchat = 'TOKEN-FALSO-DE-TESTE', url_apchat = 'https://apchat.falso/v2/api/external/abc/' WHERE id = {$escritorio['id']}");

    // cliente de teste com telefone nos Dados (sem o 9 na aba Contatos) e e-mail
    $p = new Pessoa();
    $p->nome = 'TESTE MENSAGEM APCHAT';
    $p->tipo_pessoa_id = TipoPessoa::FISICA;
    $p->telefone = '19987650001';
    $p->email = 'cliente.teste@exemplo.com.br';
    $p->aceita_receber_mensagen_whatsapp = 'T';
    $p->store();
    $c->prepare("INSERT INTO pessoa_contato (pessoa_id, telefone, email, principal) VALUES (?, '1987654321', 'outro.do.cliente@exemplo.com.br', 'N')")->execute([$p->id]);

    // agendamento que ja existe (a mensagem aponta para ele), com o cliente de teste so em memoria
    $idAg = (int) $c->query('SELECT id FROM agendamento ORDER BY id LIMIT 1')->fetchColumn();
    $ag = new Agendamento($idAg);
    $ag->cliente_id = $p->id;
    igual('cliente do agendamento e o de teste', 'TESTE MENSAGEM APCHAT', $ag->cliente->nome);

    // ---------- ambiente de teste ----------
    AmbienteService::forcarProducao(false);
    lanca('teste: sem numero digitado', fn() => MensagemService::destinoWhatsapp($ag, ''), 'Ambiente de teste');
    lanca('teste: numero do cliente', fn() => MensagemService::destinoWhatsapp($ag, '(19) 98765-0001'), 'é do cliente');
    lanca('teste: numero do cliente com 55 e sem o 9', fn() => MensagemService::destinoWhatsapp($ag, '55 19 8765-0001'), 'é do cliente');
    lanca('teste: numero da aba Contatos do cliente', fn() => MensagemService::destinoWhatsapp($ag, '19 98765-4321'), 'é do cliente');
    igual('teste: outro numero passa (com o 9)', '5519912340000', MensagemService::destinoWhatsapp($ag, '(19) 91234-0000'));
    lanca('teste: numero invalido', fn() => MensagemService::destinoWhatsapp($ag, '123'), 'inválido');
    lanca('teste: sem e-mail digitado', fn() => MensagemService::destinoEmail($ag, ''), 'Ambiente de teste');
    lanca('teste: e-mail do cliente', fn() => MensagemService::destinoEmail($ag, 'Cliente.Teste@exemplo.com.br'), 'é do cliente');
    lanca('teste: e-mail da aba Contatos do cliente', fn() => MensagemService::destinoEmail($ag, 'outro.do.cliente@exemplo.com.br'), 'é do cliente');
    igual('teste: outro e-mail passa', 'eu@teste.com', MensagemService::destinoEmail($ag, ' Eu@Teste.com '));

    // envio: variaveis trocadas, registro com destino e retorno, chave externa com o id da mensagem
    $m = MensagemService::enviarWhatsapp($ag, null, 'Olá {$cliente}, agendamento {$id}', null, '19 91234-0000');
    igual('envio: uma chamada', 1, count($chamadas));
    igual('envio: endereco do escritorio (sem a barra final)', 'https://apchat.falso/v2/api/external/abc', $chamadas[0]['url']);
    igual('envio: token do escritorio', 'TOKEN-FALSO-DE-TESTE', $chamadas[0]['token']);
    igual('envio: numero', '5519912340000', $chamadas[0]['corpo']['number']);
    igual('envio: variaveis trocadas', "Olá TESTE MENSAGEM APCHAT, agendamento {$idAg}", $chamadas[0]['corpo']['body']);
    igual('envio: chave externa', 'curciol-mensagem-' . $m->id, $chamadas[0]['corpo']['externalKey']);
    $reg = $c->query("SELECT destino, retorno, tipo_mensagem, template FROM mensagem WHERE id = {$m->id}")->fetch(PDO::FETCH_ASSOC);
    igual('registro: destino', '5519912340000', $reg['destino']);
    igual('registro: retorno do APChat', '{"success":true}', $reg['retorno']);
    igual('registro: tipo', 'WHATSAPP', $reg['tipo_mensagem']);
    lanca('envio: mensagem vazia', fn() => MensagemService::enviarWhatsapp($ag, null, '   ', null, '19 91234-0000'), 'Escreva');

    // erro do APChat: excecao com o motivo
    $resposta = ['status' => 403, 'corpo' => '{"error":"Invalid token."}', 'erro' => ''];
    lanca('APChat recusa o token', fn() => MensagemService::enviarWhatsapp($ag, null, 'oi', null, '19 91234-0000'), 'token recusado');
    $resposta = ['status' => 0, 'corpo' => '', 'erro' => 'timeout'];
    lanca('APChat fora', fn() => MensagemService::enviarWhatsapp($ag, null, 'oi', null, '19 91234-0000'), 'não respondeu');

    // ---------- producao ----------
    AmbienteService::forcarProducao(true);
    igual('producao: vazio = telefone do cliente', '5519987650001', MensagemService::destinoWhatsapp($ag, ''));
    igual('producao: vazio = e-mail do cliente', 'cliente.teste@exemplo.com.br', MensagemService::destinoEmail($ag, ''));
    $ag->cliente->aceita_receber_mensagen_whatsapp = 'F';
    lanca('producao: cliente nao aceita WhatsApp', fn() => MensagemService::destinoWhatsapp($ag, ''), 'não aceita');
    igual('producao: outro numero digitado nao depende da autorizacao do cliente', '5519912340000', MensagemService::destinoWhatsapp($ag, '19 91234-0000'));
    AmbienteService::forcarProducao(null);
    ok('sem app/config/ambiente.php nesta maquina = ambiente de teste', !AmbienteService::producao() || is_file(AmbienteService::ARQUIVO));

    // ---------- template do WhatsApp: acoes vao no fim do texto ----------
    $t = new TemplateEscritorio();
    $t->escritorio_id = $escritorio['id'];
    $t->chave = 'TESTE_MENSAGEM_APCHAT';
    $t->descricao = 'Teste';
    $t->habilitado = 'T';
    $t->tipo_template = TemplateEscritorio::WHATSAPP;
    $t->template = 'Confirma?';
    $t->store();
    $a = new TemplateAcao();
    $a->template_escritorio_id = $t->id;
    $a->label = 'Confirmar';
    $a->url = '{$link_confirmacao}';
    $a->store();
    igual('template: acao no fim do texto', "Confirma?\nConfirmar: {\$link_confirmacao}", MensagemService::textoDoTemplate($t)['texto']);
    igual('templates habilitados da chave', [$t->id => 'Teste'], MensagemService::templatesDaChave('TESTE_MENSAGEM_APCHAT'));

    // ---------- teste de conexao ----------
    $resposta = ['status' => 404, 'corpo' => '{"success":false,"error":"ERR_CONTACT_NOT_FOUND"}', 'erro' => ''];
    $chamadas = [];
    ApchatApiService::testarConexao('https://apchat.falso/x/', 'tok');
    igual('conexao: consulta (showcontact), nao envia', 'https://apchat.falso/x/showcontact', $chamadas[0]['url']);
    $resposta = ['status' => 403, 'corpo' => '{"error":"Invalid token."}', 'erro' => ''];
    lanca('conexao: token errado', fn() => ApchatApiService::testarConexao('https://apchat.falso/x', 'tok'), 'token recusado');
    $resposta = ['status' => 404, 'corpo' => '<html>Cannot POST</html>', 'erro' => ''];
    lanca('conexao: endereco errado', fn() => ApchatApiService::testarConexao('https://apchat.falso/x', 'tok'), 'endereço da API');
    lanca('conexao: sem token', fn() => ApchatApiService::testarConexao('https://apchat.falso/x', ''), 'Informe');

    $e = new Escritorio();
    igual('url vazia = padrao', ApchatApiService::URL_PADRAO, ApchatApiService::url($e));
} finally {
    AmbienteService::forcarProducao(null);
    ApchatApiService::usarTransporte(null);
    TTransaction::rollback();
}

fim();
