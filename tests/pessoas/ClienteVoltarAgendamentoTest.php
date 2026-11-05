<?php
// tests/pessoas/ClienteVoltarAgendamentoTest.php
// 05/11/2026: salvar o cadastro do cliente so volta para o agendamento quando ele foi aberto pelo proprio agendamento (nada de
// lembranca na sessao), fechando a cortina. Consulta de CEP pela ViaCEP (falsa aqui). Cliente sem telefone: nao chama o APChat.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_VA = 'TESTE VOLTAR AGENDAMENTO';
const CEP_NOVO = '99000111';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }
function saida(callable $f): string
{
    ob_start();
    try { $f(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function limparVA(): void
{
    x("DELETE FROM cep_cache WHERE cep = '" . CEP_NOVO . "'");
    x("DELETE FROM apchat_contato_log WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_VA . "%')");
    x("DELETE FROM pessoa_grupo WHERE pessoa_id IN (SELECT id FROM pessoa WHERE nome LIKE '" . MARCA_VA . "%')");
    x("DELETE FROM pessoa WHERE nome LIKE '" . MARCA_VA . "%'");
}
limparVA();

$GLOBALS['__salvar_sem_exit'] = true;
TSession::setValue('userid', 1);
$unidade = (int) q('SELECT e.system_unit_id u FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id ORDER BY a.id LIMIT 1')[0]['u'];
TSession::setValue('userunitid', $unidade);
TSession::setValue('userunitids', [$unidade]);

TTransaction::open('escritorio');
$st = TTransaction::get()->prepare("INSERT INTO pessoa (nome, tipo_pessoa_id) VALUES (?, 1) RETURNING id");
$st->execute([MARCA_VA]);
$cliente = (int) $st->fetchColumn();
TTransaction::close();

$salvar = function (array $extra) use ($cliente) {
    $p = ['id' => $cliente, 'nome' => MARCA_VA, 'tipo_pessoa_id' => TipoPessoa::FISICA, 'email' => '', 'cpf_cnpj' => '', 'rg_ie' => '', 'telefone' => '', 'dt_nascimento_abertura' => '', 'dt_falecimento' => ''] + $extra;
    $_POST = $p; $_REQUEST = $p;
    return saida(function () use ($p) { (new ClienteForm($p))->onSave($p); });
};
$abrir = function (array $extra) use ($cliente) {
    $p = ['key' => $cliente] + $extra;
    return saida(function () use ($p) { $f = new ClienteForm($p); $f->onEdit($p); $f->show(); });
};

try {
    // ---------- aberto pelo agendamento ----------
    $html = $abrir(['agendamento' => 777]);
    ok('aberto pelo agendamento: o formulario guarda de onde veio', (bool) preg_match('~name="voltar_agendamento"[^>]*value="777"|value="777"[^>]*name="voltar_agendamento"~', $html));
    ok('aberto pelo agendamento: Sair fecha a cortina', strpos($html, 'method=onFecharCortina') !== false && strpos($html, 'class=ClienteList&method=onShow') === false);
    $s = $salvar(['voltar_agendamento' => '777']);
    ok('salvar: fecha a cortina e atualiza o agendamento aberto', strpos($s, 'Template.closeRightPanel()') !== false && strpos($s, 'class=AgendamentoFormView&method=onShow&key=777') !== false, substr($s, 0, 400));
    ok('salvar: nao reabre o cadastro', strpos($s, 'class=ClienteForm&method=onEdit') === false, substr($s, 0, 300));
    ok('nada fica guardado na sessao', empty(TSession::getValue('agendamento')));

    // ---------- aberto de outro lugar: nao vai para o agendamento, nem com sessao antiga ----------
    TSession::setValue('agendamento', 777);
    TSession::setValue('atendimento', 888);
    $html = $abrir([]);
    ok('aberto pela lista: sem agendamento', (bool) preg_match('~name="voltar_agendamento"~', $html) && !preg_match('~value="777"~', $html));
    ok('aberto pela lista: Sair tambem so fecha a cortina, mantendo os filtros', strpos($html, 'method=onFecharCortina') !== false && strpos($html, 'class=ClienteList&method=onShow') === false);
    $s = $salvar([]);
    ok('salvar: nao vai para agendamento nenhum', strpos($s, 'AgendamentoFormView') === false && strpos($s, 'AtendimentoFormView') === false, substr($s, 0, 400));
    ok('salvar: continua no cadastro', strpos($s, 'class=ClienteForm') !== false && strpos($s, 'method=onEdit') !== false, substr($s, 0, 400));
    TSession::setValue('agendamento', null);
    TSession::setValue('atendimento', null);

    // ---------- fechar pelo Sair ----------
    $s = saida(function () { ClienteForm::onFecharCortina([]); });
    ok('Sair: fecha a cortina', strpos($s, 'Template.closeRightPanel()') !== false);

    // ---------- CEP ----------
    $chamadas = 0;
    $respostaApi = '{"cep":"99000-111","logradouro":"Rua Teste","bairro":"Centro","localidade":"Americana","uf":"SP","estado":"São Paulo","ibge":"3501608"}';
    CEPService::usarTransporte(function ($cep) use (&$chamadas, &$respostaApi) { $chamadas++; return $respostaApi; });
    $cep = function ($valor) { return saida(function () use ($valor) { ClienteForm::onSearchCep(['pessoa_endereco_pessoa_cep' => $valor]); }); };

    $cidade = (int) q("SELECT id FROM cidade WHERE codigo_ibge = '3501608' ORDER BY id LIMIT 1")[0]['id'];
    $s = $cep('99000-111');
    ok('CEP novo: preenche rua, bairro e cidade', strpos($s, "'pessoa_endereco_pessoa_rua', 'Rua Teste'") !== false && strpos($s, "'pessoa_endereco_pessoa_bairro', 'Centro'") !== false && strpos($s, "'pessoa_endereco_pessoa_cidade_id', '{$cidade}'") !== false, substr($s, 0, 500));
    igual('CEP novo: consultou uma vez', 1, $chamadas);
    igual('CEP novo: guardou', 1, count(q("SELECT 1 FROM cep_cache WHERE cep = '" . CEP_NOVO . "' AND cidade_id = {$cidade}")));
    $s = $cep('99000111');
    igual('CEP guardado: nao consulta de novo', 1, $chamadas);
    ok('CEP guardado: preenche', strpos($s, "'pessoa_endereco_pessoa_rua', 'Rua Teste'") !== false);
    x("DELETE FROM cep_cache WHERE cep = '" . CEP_NOVO . "'");

    $respostaApi = '{"erro":"true"}';
    ok('CEP que nao existe: avisa', strpos($cep('99000-111'), 'CEP não encontrado') !== false);
    $respostaApi = null;
    ok('servico fora do ar: avisa', strpos($cep('99000-111'), 'Não foi possível consultar o CEP') !== false);
    $respostaApi = '<html>erro</html>';
    ok('resposta estranha: avisa', strpos($cep('99000-111'), 'Não foi possível consultar o CEP') !== false);
    $antes = $chamadas;
    ok('CEP incompleto: avisa sem consultar', strpos($cep('1346'), 'CEP inválido') !== false && $chamadas === $antes);
    igual('nada guardado nos erros', 0, count(q("SELECT 1 FROM cep_cache WHERE cep = '" . CEP_NOVO . "'")));
} finally {
    while (TTransaction::get()) { TTransaction::rollback(); }
    $_POST = []; $_REQUEST = [];
    TSession::setValue('agendamento', null);
    TSession::setValue('atendimento', null);
    CEPService::usarTransporte(null);
    limparVA();
}

fim();
