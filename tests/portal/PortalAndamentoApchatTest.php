<?php
// tests/portal/PortalAndamentoApchatTest.php
// Portal do cliente: login pelo CPF/CNPJ (com ou sem pontuacao); cards da timeline (seta, Detalhamento, Informacoes adicionais);
// consulta de andamento (botoes lado a lado, complemento abaixo) e lista com Data antes do Titulo;
// APChat: todas as pessoas, telefone vazio/invalido = "Nao sincronizado" sem chamar a API.
// NAO sincroniza telefone valido: homologacao usa o token do APChat de producao.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

function saida(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function erro(string $s): string
{
    if (preg_match('/EXCECAO: (.*)/', $s, $m)) { return $m[1]; }
    if (preg_match("/__adianti_error\\(['\"][^'\"]*['\"],\\s*['\"]([^'\"]+)/", $s, $m)) { return $m[1]; }
    return '';
}
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }

const MARCA = 'TESTE LEVANTAMENTO PORTAL';
TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$c = TTransaction::get();
if ($c->query('SELECT current_database()')->fetchColumn() !== 'curciol_homologacao') { echo "ABORTADO\n"; exit(1); }
$novo = function (string $nome, array $campos) use ($c) {
    $p = new Pessoa();
    $p->nome = MARCA . ' ' . $nome;
    $p->tipo_pessoa_id = 1;
    foreach ($campos as $k => $v) { $p->$k = $v; }
    $p->store();
    return (int) $p->id;
};
$cpf = '52998224725';   // CPF valido de teste
$comSenha = $novo('COM SENHA', ['cpf_cnpj' => $cpf, 'senha' => 'senha-teste-1', 'telefone' => '']);
$semSenha = $novo('SEM SENHA', ['cpf_cnpj' => '11144477735', 'senha' => '', 'telefone' => '123']);
$foneOk = $novo('FONE OK', ['telefone' => '(19) 99876-5432']);
TTransaction::close();
$ids = [$comSenha, $semSenha, $foneOk];
try {
    // ---------- portal: login pelo CPF ----------
    $entra = function (string $login, string $senha) { return saida(function () use ($login, $senha) { Home::onDocumentos(['usuario' => $login, 'senha' => $senha]); }); };
    $s = $entra('529.982.247-25', 'senha-teste-1');
    ok('login com CPF pontuado entra', erro($s) === '' && strpos($s, 'ProcessosFormView') !== false, erro($s));
    ok('login com CPF so numeros entra', strpos($entra($cpf, 'senha-teste-1'), 'ProcessosFormView') !== false);
    igual('portal guarda a pessoa', $comSenha, (int) TSession::getValue('portal_cliente_id'));
    igual('senha errada', 'CPF ou senha incorretos.', erro($entra($cpf, 'errada')));
    ok('CPF sem senha cadastrada', strpos(erro($entra('111.444.777-35', 'x')), 'ainda não é um cliente registrado') !== false, erro($entra('111.444.777-35', 'x')));
    igual('campo vazio', 'Informe seu CPF e senha para acessar seus processos.', erro($entra('...', 'x')));
    $html = file_get_contents('app/resources/home.html');
    ok('tela de login: "Login (CPF)" e formatacao so na tela', strpos($html, '<label>Login (CPF)</label>') !== false && strpos($html, 'curciolFormatarDocumento') !== false);

    // ---------- portal: cards da timeline ----------
    $proc = q("SELECT a.processo_id FROM andamento a JOIN processo_publicacoes pp ON pp.andamento_id = a.id JOIN publicacao_etapa e ON e.id = a.publicacao_etapa_id JOIN processo pr ON pr.id = a.processo_id AND pr.exibir_cliente = 'S'
                WHERE COALESCE(e.detalhamento, '') <> '' AND COALESCE(pp.complemento, '') <> '' ORDER BY a.id DESC LIMIT 1");
    if ($proc) {
        $t = saida(function () use ($proc) { (new ProcessoPublicacoesTimeLine(['processo_id' => $proc[0]['processo_id']]))->show(); });
        ok('timeline: seta para baixo, sem onclick inline', erro($t) === '' && strpos($t, 'fa-chevron-down curciol-tl-seta') !== false && strpos($t, "curciol-timeline-toggle'\n") === false && strpos($t, 'icon.innerHTML') === false, erro($t));
        ok('timeline: "Detalhamento" e "Informações Adicionais" em destaque', strpos($t, '<b>Detalhamento</b>') !== false && strpos($t, '<b>Andamento</b>') === false && strpos($t, 'curciol-info-adicional') !== false);
    } else {
        ok('timeline: processo com detalhamento e complemento para testar', false, 'nenhum encontrado');
    }
    $js = file_get_contents('app/lib/include/js/curciol-portal.js');
    $css = file_get_contents('app/lib/include/css/curciol-portal.css');
    ok('portal: clique no card (js) e cor #009688 (css)', strpos($js, 'function alternarCard') !== false && strpos($css, '#009688') !== false);

    // ---------- consulta de andamento e lista ----------
    $and = q("SELECT a.id, a.processo_id FROM andamento a JOIN processo_publicacoes pp ON pp.andamento_id = a.id WHERE a.publicacao_etapa_id IS NOT NULL ORDER BY a.id DESC LIMIT 1");
    $v = saida(function () use ($and) { (new AndamentoFormView(['key' => $and[0]['id'], 'id' => $and[0]['id']]))->show(); });
    $pEtapa = strpos($v, 'tbuttonalteretapa'); $pComp = strpos($v, 'tbuttonaddcomplemento'); $pRotulo = strpos($v, 'Complemento:'); $pTitulo = strpos($v, 'Titulo:');
    ok('andamento: Alterar Etapa e complemento lado a lado, complemento abaixo dos botoes e antes das informacoes',
        erro($v) === '' && $pEtapa !== false && $pComp !== false && $pEtapa < $pComp && $pComp < $pRotulo && $pRotulo < $pTitulo, erro($v) ?: json_encode([$pEtapa, $pComp, $pRotulo, $pTitulo]));
    $l = saida(function () use ($and) { (new ViewAndamentosPublicacoesProcesso(['key' => $and[0]['processo_id']]))->show(); });
    $pData = strpos($l, '>Data<'); $pTit = strpos($l, '>Titulo<');
    ok('lista de andamentos: Data antes de Titulo', $pData !== false && $pTit !== false && $pData < $pTit, json_encode([$pData, $pTit]));

    // ---------- APChat: todas as pessoas; telefone vazio/invalido = Nao sincronizado ----------
    $view = q('SELECT id, situacao, mensagem, telefone_valido FROM apchat_contato_view WHERE id IN (' . implode(',', $ids) . ') ORDER BY id');
    igual('view traz qualquer pessoa (nao so cliente autorizado)', 3, count($view));
    igual('telefone vazio', ['IGNORADO', 'Telefone vazio.', false], [$view[0]['situacao'], $view[0]['mensagem'], (bool) $view[0]['telefone_valido']]);
    igual('telefone invalido', ['IGNORADO', 'Telefone inválido.', false], [$view[1]['situacao'], $view[1]['mensagem'], (bool) $view[1]['telefone_valido']]);
    igual('telefone valido, sem consentimento: entra para sincronizar', [null, true], [$view[2]['situacao'], (bool) $view[2]['telefone_valido']]);
    $todos = APChatContactService::idsAutorizados();
    ok('"Sincronizar todos" pega so telefone valido', in_array($foneOk, $todos, true) && !in_array($semSenha, $todos, true) && !in_array($comSenha, $todos, true));
    $r = APChatContactService::sincronizarPessoa($semSenha);
    igual('telefone invalido nao chama a API', [ApchatContatoLog::SITUACAO_IGNORADO, ApchatContatoLog::OPERACAO_NENHUMA], [$r->situacao, $r->operacao]);
    $selo = ApchatContatoList::seloSituacao('IGNORADO');
    ok('selo "Não sincronizado" em vermelho', strpos($selo, 'Não sincronizado') !== false && strpos($selo, '#c62828') !== false, $selo);
} finally {
    TTransaction::open('escritorio');
    TTransaction::get()->exec('DELETE FROM apchat_contato_log WHERE pessoa_id IN (' . implode(',', $ids) . ')');
    TTransaction::get()->exec('DELETE FROM pessoa WHERE id IN (' . implode(',', $ids) . ')');
    TTransaction::close();
    TSession::setValue('portal_cliente_id', null);
}
fim();
