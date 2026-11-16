<?php
// tests/agendamentos/WhatsappNumeroTesteTest.php
// Homologacao com numero de WhatsApp de teste (16/11/2026; AmbienteService::whatsappTeste): todo WhatsApp do agendamento vai
// so para o numero de teste, qualquer outro numero nao sai, contatos continuam sem sincronizar, a producao nao muda.
// NUNCA fala com o APChat de verdade: o envio permitido vai para uma porta local fechada.
require __DIR__ . '/../bootstrap.php';

const TESTE_WPP = '19971045673';
const URL_FECHADA = 'http://127.0.0.1:9/v2/api/external/teste';

$erroDe = function (callable $f) { try { $f(); return ''; } catch (Throwable $e) { return $e->getMessage(); } };

try {
    // ---------- numero de teste ----------
    AmbienteService::forcarProducao(false);
    AmbienteService::forcarWhatsappTeste(TESTE_WPP);
    igual('numero de teste canonico', '5519971045673', AmbienteService::whatsappTeste());
    ok('o mesmo numero sem o 9 e com mascara', AmbienteService::ehWhatsappTeste('(19) 7104-5673') && AmbienteService::ehWhatsappTeste('551971045673'));
    ok('outro numero nao e o de teste', !AmbienteService::ehWhatsappTeste('5519912340001') && !AmbienteService::ehWhatsappTeste('') && !AmbienteService::ehWhatsappTeste(null));

    // ---------- destino: sempre o numero de teste, mesmo digitando outro ou vazio ----------
    TTransaction::open('escritorio');
    $agendamento = Agendamento::where('cliente_id', 'is not', null)->orderBy('id', 'desc')->first();
    igual('destino: digitado outro numero vai para o de teste', '5519971045673', MensagemService::destinoWhatsapp($agendamento, '19912340001'));
    igual('destino: vazio vai para o de teste', '5519971045673', MensagemService::destinoWhatsapp($agendamento, ''));
    $doCliente = MensagemService::numerosDoCliente((int) $agendamento->cliente_id);
    if ($doCliente) { igual('destino: numero do cliente vai para o de teste', '5519971045673', MensagemService::destinoWhatsapp($agendamento, $doCliente[0])); }
    TTransaction::close();

    // ---------- saida: so o envio para o numero de teste passa da trava ----------
    // sem o transporte falso do bootstrap para chegar na trava; o que passa dela vai so para a porta local fechada
    ApchatApiService::usarTransporte(null);
    $outro = $erroDe(function () { ApchatApiService::enviar(URL_FECHADA, 'token-falso', '5519912340001', 'oi', 'teste-1'); });
    igual('saida: outro numero barrado', sprintf(AmbienteService::MSG_SO_NUMERO_TESTE, PessoaContatoService::formatarTelefone('5519971045673')), $outro);
    $teste = $erroDe(function () { ApchatApiService::enviar(URL_FECHADA, 'token-falso', '5519971045673', 'oi', 'teste-2'); });
    ok('saida: numero de teste passa da trava (a porta fechada nao responde)', strpos($teste, 'não respondeu') !== false, $teste);
    $semNove = $erroDe(function () { ApchatApiService::enviar(URL_FECHADA, 'token-falso', '551971045673', 'oi', 'teste-3'); });
    ok('saida: numero de teste sem o 9 tambem passa', strpos($semNove, 'não respondeu') !== false, $semNove);
    ok('contatos: criar e alterar continuam barrados', !ApchatApiService::chamadaPermitida('createContact') && !ApchatApiService::chamadaPermitida('updateContact'));

    // ---------- sem numero de teste: nada sai (como antes) ----------
    AmbienteService::forcarWhatsappTeste(null);
    igual('sem numero de teste: vazio', '', AmbienteService::whatsappTeste());
    igual('sem numero de teste: envio barrado', AmbienteService::MSG_SEM_SAIDA, $erroDe(function () { ApchatApiService::enviar(URL_FECHADA, 'token-falso', '5519971045673', 'oi', 'teste-4'); }));
    TTransaction::open('escritorio');
    igual('sem numero de teste: destino exige o digitado', sprintf(MensagemService::MSG_TESTE_SEM_DESTINO, 'o número'), $erroDe(function () use ($agendamento) { MensagemService::destinoWhatsapp($agendamento, ''); }));
    TTransaction::close();

    // ---------- producao: o numero de teste nao vale ----------
    AmbienteService::forcarProducao(true);
    AmbienteService::forcarWhatsappTeste(TESTE_WPP);
    igual('producao: sem numero de teste', '', AmbienteService::whatsappTeste());
    ok('producao: nenhum numero e o de teste', !AmbienteService::ehWhatsappTeste('5519971045673'));
} finally {
    if (TTransaction::get()) { TTransaction::rollback(); }
    AmbienteService::forcarWhatsappTeste(null);
    AmbienteService::forcarProducao(null);
    ApchatApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso de mensagens.'); });
}

fim();
