<?php
// tests/apchat/TemplateMetaTest.php
// Templates da Meta (17/11/2026): variaveis no padrao {{nome}} (com as formas antigas aceitas), importacao da lista da Meta pelo
// canal oficial da integracao (curciol.f_waba, banco falso), origem, situacao, categoria pelo nome, respostas pelos botoes,
// template que sumiu, e o envio pelo template aprovado (/templateBody com os valores por nome). NUNCA fala com a Meta nem com o
// APChat de verdade (transportes falsos); tudo numa transacao desfeita no fim.
require __DIR__ . '/../bootstrap.php';

const INTEGRACAO = '0f0f0f0f-1111-2222-3333-444455556666';

// ---------- variaveis (sem banco) ----------
igual('antiga {$nome} vira {{nome}}', 'Olá {{cliente}} em {{data_inicial}}', TemplateEscritorio::normalizarVariaveis('Olá {$cliente} em {$data_inicial}'));
igual('{{$nome}} (copiado para a Meta) vira {{nome}}', 'Olá {{cliente}}', TemplateEscritorio::normalizarVariaveis('Olá {{$cliente}}'));
igual('{{ nome }} com espacos vira {{nome}}', 'Olá {{cliente}}', TemplateEscritorio::normalizarVariaveis('Olá {{ cliente }}'));
igual('link do editor codificado vira {{nome}}', 'href="{{link_confirmacao}}" e {{link_cancelamento}}', TemplateEscritorio::normalizarVariaveis('href="%7B%24link_confirmacao%7D" e %7B%7Blink_cancelamento%7D%7D'));
igual('texto sem variavel fica igual', 'Olá {cliente} $x', TemplateEscritorio::normalizarVariaveis('Olá {cliente} $x'));
igual('variaveis do texto, na ordem e sem repetir', ['cliente', 'data_inicial'], TemplateEscritorio::variaveisDoTexto('{$cliente} {{data_inicial}} {{cliente}}'));
igual('troca: um passo so (valor com variavel nao e trocado de novo)', 'Oi {{senha}}', TemplateEscritorio::trocar('Oi {{cliente}}', ['cliente' => '{{senha}}', 'senha' => 'X']));
ok('lista de variaveis no padrao novo', isset(MensagemService::variaveis()['{{cliente}}']) && !isset(MensagemService::variaveis()['{$cliente}']));
igual('categoria pelo nome: confirmacao', TemplateEscritorio::CONFIRMACAO, ApchatWabaService::categoriaPeloNome('confirmacao_agendamento'));
igual('categoria pelo nome: confirmado', TemplateEscritorio::CONFIRMADO, ApchatWabaService::categoriaPeloNome('agendamento_confirmado'));
igual('categoria pelo nome: cancelado', TemplateEscritorio::CANCELAMENTO, ApchatWabaService::categoriaPeloNome('agendamento_cancelado'));
igual('categoria pelo nome: lembrete', TemplateEscritorio::LEMBRETE, ApchatWabaService::categoriaPeloNome('lembrete_agendamento'));
igual('categoria pelo nome: outro', TemplateEscritorio::OUTROS, ApchatWabaService::categoriaPeloNome('boas_vindas'));

// ---------- banco do APChat e Meta falsos ----------
$canal = ['canal_id' => 115, 'canal' => 'Curciol', 'tipo' => 'waba', 'waba_id' => '1234567890', 'token' => 'TOKEN-META-FALSO', 'versao' => '25.0'];
$falso = new class($canal) {
    public $canal; public $ids = [];
    public function __construct($c) { $this->canal = $c; }
    public function integracaoDaEmpresa($id) { return true; }
    public function wabaDaIntegracao($id) { $this->ids[] = $id; return $this->canal; }
};

$componentesConfirmacao = [
    ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Confirmação de Agendamento'],
    ['type' => 'BODY', 'text' => "Olá {{cliente}}!\n\nTemos agendado para a data {{data_inicial}}, com {{profissional}}.\n\nResponda *SIM* ou *NÃO*."],
    ['type' => 'FOOTER', 'text' => 'Curciol Sociedade de Advogados.'],
    ['type' => 'BUTTONS', 'buttons' => [['type' => 'QUICK_REPLY', 'text' => 'Sim'], ['type' => 'QUICK_REPLY', 'text' => 'Não']]],
];
$meta = [
    ['id' => '901', 'name' => 'confirmacao_agendamento', 'status' => 'APPROVED', 'language' => 'pt_BR', 'category' => 'UTILITY', 'parameter_format' => 'NAMED', 'components' => $componentesConfirmacao],
    ['id' => '902', 'name' => 'lembrete_agendamento', 'status' => 'PENDING', 'language' => 'pt_BR', 'category' => 'UTILITY', 'parameter_format' => 'NAMED',
     'components' => [['type' => 'BODY', 'text' => 'Lembramos do dia {{data_inicial}}.']]],
    ['id' => '903', 'name' => 'posicional', 'status' => 'APPROVED', 'language' => 'pt_BR', 'category' => 'UTILITY', 'parameter_format' => 'POSITIONAL',
     'components' => [['type' => 'BODY', 'text' => 'Olá {{1}}']]],
    ['id' => '904', 'name' => 'desconhecida', 'status' => 'APPROVED', 'language' => 'pt_BR', 'category' => 'UTILITY', 'parameter_format' => 'NAMED',
     'components' => [['type' => 'BODY', 'text' => 'Olá {{apelido}}']]],
    // {{$cliente}}: a Meta aprova como texto fixo (teste real no app1, 17/11/2026: #132000 ao mandar os parametros)
    ['id' => '905', 'name' => 'com_cifrao', 'status' => 'APPROVED', 'language' => 'pt_BR', 'category' => 'UTILITY', 'parameter_format' => 'NAMED',
     'components' => [['type' => 'BODY', 'text' => 'Olá {{$cliente}}, dia {{$data_inicial}}']]],
];
$metaChamadas = [];
ApchatWabaService::usarTransporte(function ($url, $token) use (&$meta, &$metaChamadas) {
    $metaChamadas[] = ['url' => $url, 'token' => $token];
    return ['status' => 200, 'corpo' => json_encode(['data' => $meta]), 'erro' => ''];
});
$api = [];
ApchatApiService::usarTransporte(function ($url, $corpo, $token) use (&$api) {
    $api[] = ['url' => $url, 'corpo' => $corpo, 'token' => $token];
    return ['status' => 200, 'corpo' => '{"data":{"ticketId":77}}', 'erro' => ''];
});

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$c = TTransaction::get();

try
{
    ApchatBancoService::usarFalso($falso);
    $escritorio = $c->query('SELECT id, system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $eid = (int) $escritorio['id'];
    TSession::setValue('userunitid', (int) $escritorio['system_unit_id']);
    $c->exec("UPDATE escritorio SET token_apchat = 'TOKEN-FALSO-DE-TESTE', url_apchat = 'https://api.apchat.falso/v2/api/external/" . INTEGRACAO . "' WHERE id = {$eid}");
    $c->exec("DELETE FROM template_escritorio WHERE escritorio_id = {$eid} AND origem = 'META'");

    // ---------- importacao ----------
    $r = ApchatWabaService::importar($eid);
    igual('importar: 5 novos', [5, 0, 0], [$r['novos'], $r['atualizados'], $r['removidos']]);
    igual('importar: integracao da URL do Escritorio', [INTEGRACAO], array_unique($falso->ids));
    ok('importar: le na Meta a conta do canal, com o token do canal', strpos($metaChamadas[0]['url'], 'https://graph.facebook.com/v25.0/1234567890/message_templates?') === 0 && $metaChamadas[0]['token'] === 'TOKEN-META-FALSO', json_encode($metaChamadas[0]));
    igual('importar: avisos do posicional, da variavel desconhecida e do {{$nome}}', 3, count($r['avisos']));

    $t = TemplateEscritorio::where('escritorio_id', '=', $eid)->where('meta_nome', '=', 'confirmacao_agendamento')->first();
    igual('confirmacao: origem Meta, WhatsApp, aprovada, habilitada', ['META', 'WHATSAPP', 'APPROVED', 'T'], [$t->origem, $t->tipo_template, $t->meta_status, $t->habilitado]);
    igual('confirmacao: categoria pelo nome', TemplateEscritorio::CONFIRMACAO, $t->categoria);
    igual('confirmacao: titulo = cabecalho', 'Confirmação de Agendamento', $t->titulo);
    ok('confirmacao: texto com as variaveis no padrao {{nome}} e o rodape', strpos($t->template, 'Olá {{cliente}}!') === 0 && strpos($t->template, '{{profissional}}') !== false && substr($t->template, -31) === 'Curciol Sociedade de Advogados.', $t->template);
    igual('confirmacao: respostas = textos dos botoes', ['Sim', 'Não'], [$t->respostas_confirmar, $t->respostas_cancelar]);
    $l = TemplateEscritorio::where('escritorio_id', '=', $eid)->where('meta_nome', '=', 'lembrete_agendamento')->first();
    igual('lembrete em analise: desabilitado', ['PENDING', 'F', TemplateEscritorio::LEMBRETE], [$l->meta_status, $l->habilitado, $l->categoria]);
    $pos = TemplateEscritorio::where('escritorio_id', '=', $eid)->where('meta_nome', '=', 'posicional')->first();
    ok('posicional: desabilitado, com o motivo', $pos->habilitado === 'F' && strpos((string) ApchatWabaService::problemaDoTemplate($pos), 'posição') !== false);
    $desc = TemplateEscritorio::where('escritorio_id', '=', $eid)->where('meta_nome', '=', 'desconhecida')->first();
    $cif = TemplateEscritorio::where('escritorio_id', '=', $eid)->where('meta_nome', '=', 'com_cifrao')->first();
    $probCif = (string) ApchatWabaService::problemaDoTemplate($cif);
    ok('{{$cliente}} na Meta (texto fixo): desabilitado, pedindo {{cliente}}', $cif->habilitado === 'F' && strpos($probCif, 'texto fixo') !== false
        && strpos($probCif, 'Recrie o template na Meta com {{cliente}}, {{data_inicial}}') !== false, $probCif);
    lanca('{{$cliente}} na Meta: nao envia', fn() => ApchatWabaService::exigirEnvio($cif), 'texto fixo');
    ok('variavel desconhecida: desabilitado, com o nome', $desc->habilitado === 'F' && strpos((string) ApchatWabaService::problemaDoTemplate($desc), '{{apelido}}') !== false);

    // reimportar: o que o usuario mudou fica; a situacao vem da Meta; aprovado agora = habilitado; sumiu sem uso = excluido
    $t->descricao = 'MINHA CONFIRMACAO';
    $t->respostas_confirmar = 'Confirmo';
    $t->store();
    $meta[1]['status'] = 'APPROVED';
    unset($meta[3]);
    $meta = array_values($meta);
    $r = ApchatWabaService::importar($eid);
    igual('reimportar: 4 atualizados, 1 que sumiu', [0, 4, 1], [$r['novos'], $r['atualizados'], $r['removidos']]);
    $t = new TemplateEscritorio($t->id);
    igual('reimportar: descricao e resposta do usuario ficam', ['MINHA CONFIRMACAO', 'Confirmo'], [$t->descricao, $t->respostas_confirmar]);
    $l = new TemplateEscritorio($l->id);
    igual('reimportar: aprovado agora = habilitado', ['APPROVED', 'T'], [$l->meta_status, $l->habilitado]);
    igual('reimportar: excluido na Meta e sem uso = sai da Curciol', 0, (int) $c->query("SELECT count(*) FROM template_escritorio WHERE id = " . (int) $desc->id)->fetchColumn());
    igual('reimportar: um registro por template', 4, (int) $c->query("SELECT count(*) FROM template_escritorio WHERE escritorio_id = {$eid} AND origem = 'META'")->fetchColumn());

    // canal que nao e oficial / Meta recusa
    $falso->canal = ['canal_id' => 113, 'canal' => 'Coexistência', 'tipo' => 'whatsapp', 'waba_id' => null, 'token' => null, 'versao' => ''];
    lanca('canal nao oficial: nao importa', fn() => ApchatWabaService::importar($eid), 'não é o oficial');
    $falso->canal = $canal;
    ApchatWabaService::usarTransporte(function () { return ['status' => 400, 'corpo' => '{"error":{"message":"Invalid OAuth access token"}}', 'erro' => '']; });
    lanca('Meta recusa: mensagem da Meta', fn() => ApchatWabaService::importar($eid), 'Invalid OAuth access token');

    // ---------- envio pelo template aprovado ----------
    $p = new Pessoa();
    $p->nome = 'TESTE TEMPLATE META';
    $p->tipo_pessoa_id = TipoPessoa::FISICA;
    $p->telefone = '19987650002';
    $p->aceita_receber_mensagen_whatsapp = 'T';
    $p->store();
    $idAg = (int) $c->query('SELECT id FROM agendamento ORDER BY id LIMIT 1')->fetchColumn();
    $ag = new Agendamento($idAg);
    $ag->cliente_id = $p->id;
    $valores = TemplateEscritorio::valoresAgendamento($ag);

    AmbienteService::forcarProducao(false);
    AmbienteService::forcarWhatsappTeste('19971045673');
    $m = MensagemService::enviarWhatsapp($ag, null, 'texto que nao vale', $t->id, '');
    igual('envio: uma chamada ao /templateBody da integracao', 'https://api.apchat.falso/v2/api/external/' . INTEGRACAO . '/templateBody', $api[0]['url'] ?? null);
    $td = $api[0]['corpo']['templateData'] ?? [];
    igual('envio: so para o numero de teste', ['5519971045673', '5519971045673', true], [$api[0]['corpo']['number'], $td['to'] ?? null, $api[0]['corpo']['isClosed']]);
    igual('envio: nome e idioma do template', ['template', 'confirmacao_agendamento', 'pt_BR'], [$td['type'] ?? null, $td['template']['name'] ?? null, $td['template']['language']['code'] ?? null]);
    $params = $td['template']['components'][0]['parameters'] ?? [];
    igual('envio: corpo com os valores pelo nome da Meta', [['type' => 'text', 'parameter_name' => 'cliente', 'text' => 'TESTE TEMPLATE META'],
                                                           ['type' => 'text', 'parameter_name' => 'data_inicial', 'text' => $valores['data_inicial']],
                                                           ['type' => 'text', 'parameter_name' => 'profissional', 'text' => (string) $valores['profissional']]], $params);
    igual('envio: so o corpo tem variaveis', ['body'], array_column($td['template']['components'], 'type'));
    $reg = $c->query("SELECT template, titulo, template_escritorio_id, apchat_ticket_id FROM mensagem WHERE id = {$m->id}")->fetch(PDO::FETCH_ASSOC);
    ok('historico: o texto que o cliente recebe (cabecalho, corpo, rodape, botoes)', strpos($reg['template'], '*Confirmação de Agendamento*') === 0 && strpos($reg['template'], 'Olá TESTE TEMPLATE META!') !== false
        && strpos($reg['template'], '[Sim]  [Não]') !== false && strpos($reg['template'], 'texto que nao vale') === false, $reg['template']);
    igual('historico: template e ticket', [(int) $t->id, 77], [(int) $reg['template_escritorio_id'], (int) $reg['apchat_ticket_id']]);

    // travas do envio
    lanca('Manual com template da Meta: nao', fn() => MensagemService::exigirEnvioDoAgendamento((function () use ($ag) { $a = clone $ag; $a->estado_agenda_id = EstadoAgenda::AGENDADO; return $a; })(), $t, true, 'Sim'), 'não pode ser alterado');
    $pend = clone $t; $pend->meta_status = 'PENDING';
    lanca('em analise: nao envia', fn() => ApchatWabaService::exigirEnvio($pend), 'ainda não está aprovado');
    $outra = clone $t; $outra->meta_waba_id = '999';
    lanca('outra conta da Meta: nao envia', fn() => ApchatWabaService::exigirEnvio($outra), 'outra conta da Meta');
    lanca('posicional: nao envia', fn() => ApchatWabaService::exigirEnvio(new TemplateEscritorio($pos->id)), 'posição');
    $api = [];

    // template da Curciol continua com o texto da Curciol
    MensagemService::enviarWhatsapp($ag, null, 'Olá {$cliente} / {{cliente}}', null, '');
    igual('template da Curciol: texto, formas antiga e nova trocadas', 'Olá TESTE TEMPLATE META / TESTE TEMPLATE META', $api[0]['corpo']['body'] ?? null);

    // excluido na Meta depois de usado: fica so para o historico (desabilitado, fora da lista); os outros seguem
    $meta = array_values(array_filter($meta, fn($x) => $x['name'] !== 'confirmacao_agendamento'));
    ApchatWabaService::usarTransporte(function ($url, $token) use (&$meta) { return ['status' => 200, 'corpo' => json_encode(['data' => $meta]), 'erro' => '']; });
    $r = ApchatWabaService::importar($eid);
    igual('excluido na Meta e ja usado: conta como retirado', [0, 3, 1], [$r['novos'], $r['atualizados'], $r['removidos']]);
    $t = new TemplateEscritorio($t->id);
    igual('excluido na Meta e ja usado: guardado desabilitado', [ApchatWabaService::REMOVIDO, 'F'], [$t->meta_status, $t->habilitado]);
    $crit = new TCriteria; $crit->add(new TFilter('escritorio_id', '=', $eid)); $crit->add(new TFilter('origem', '=', 'META')); $crit->add(ApchatWabaService::filtroSemRemovidos());
    $naLista = array_map(fn($o) => $o->meta_nome, TemplateEscritorio::getObjects($crit) ?: []);
    sort($naLista);
    igual('lista de templates: so os que existem na Meta', ['com_cifrao', 'lembrete_agendamento', 'posicional'], $naLista);
    igual('historico: a mensagem continua com o template', (int) $t->id, (int) $c->query("SELECT template_escritorio_id FROM mensagem WHERE id = {$m->id}")->fetchColumn());
}
finally
{
    TTransaction::rollback();
    ApchatBancoService::usarFalso(null);
    ApchatWabaService::usarTransporte(null);
    AmbienteService::forcarProducao(null);
    AmbienteService::forcarWhatsappTeste(null);
    ApchatApiService::usarTransporte(function () { throw new LogicException('Teste sem transporte falso de mensagens.'); });
}

fim();
