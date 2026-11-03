<?php
// tests/portal/ApchatBancoTest.php
// Envio com o banco do APChat (03/11/2026): o envio le o banco do APChat em vez de consultar a API (igual = sem chamada,
// mudou = espelho + update, nao existe = create + espelho); troca de numero transfere o historico do contato antigo para
// o novo (so se nenhum outro cadastro usa o numero antigo); numero que saiu da Curciol fica travado; falhas vao para a
// fila e a fila refaz; acoes da tela "Contatos sem vinculo" (vincular, trocar numero, ignorar).
// NUNCA fala com o APChat de verdade: API falsa em memoria e banco do APChat falso (o mesmo estado da API falsa).
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

const MARCA_BANCO = 'TESTE APCHAT BANCO';
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

// ---------- APChat falso: API e banco com o mesmo estado ----------
$apchat = ['contatos' => [], 'proximo' => 80001, 'chamadas' => [], 'espelho' => [], 'transferencias' => [], 'desligados' => [], 'fora' => false, 'falhar_transferencia' => 0];
$responder = function (int $status, array $json) { return ['status' => $status, 'corpo' => json_encode($json), 'erro' => '']; };
APChatContactService::usarTransporte(function ($rota, $corpo, $token) use (&$apchat, $responder) {
    $apchat['chamadas'][] = $rota;
    $n = (string) ($corpo['number'] ?? '');
    if ($rota === 'showcontact') {
        return isset($apchat['contatos'][$n]) ? $responder(200, ['success' => true, 'data' => $apchat['contatos'][$n]]) : $responder(404, ['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND']);
    }
    if ($rota === 'createContact') {
        $apchat['contatos'][$n] = $corpo + ['id' => $apchat['proximo']++];
        return $responder(201, ['success' => true, 'data' => $apchat['contatos'][$n]]);
    }
    if ($rota === 'updateContact') {
        if (!isset($apchat['contatos'][$n])) { return $responder(404, ['success' => false, 'error' => 'ERR_CONTACT_NOT_FOUND']); }
        $apchat['contatos'][$n] = array_merge($apchat['contatos'][$n], $corpo);
        return $responder(200, ['success' => true, 'data' => $apchat['contatos'][$n]]);
    }
    return $responder(404, ['success' => false, 'error' => 'ROTA']);
});
$banco = new class($apchat) {
    public $a;
    public function __construct(&$a) { $this->a = &$a; }
    public function disponivel() { return !$this->a['fora']; }
    public function contatosPorNumeros(array $numeros): array {
        if ($this->a['fora']) { throw new Exception('banco do APChat fora (teste)'); }
        $r = [];
        foreach ($numeros as $n) { foreach (APChatContactService::variantes((string) $n) as $v) { if (isset($this->a['contatos'][$v])) { $r[$v] = $this->a['contatos'][$v] + ['number' => $v]; } } }
        return $r;
    }
    public function contato(int $id): ?array {
        foreach ($this->a['contatos'] as $n => $c) { if ((int) $c['id'] === $id) { return $c + ['number' => $n, 'createdAt' => date('Y-m-d H:i:s')]; } }
        return null;
    }
    public function gravarEspelho(int $id, string $nome, string $numero, ?string $email): void {
        if ($this->a['fora']) { throw new Exception('banco do APChat fora (teste)'); }
        $this->a['espelho'][$id] = ['nome' => $nome, 'numero' => $numero, 'email' => $email, 'ligado' => true];
    }
    public function desligarEspelho(int $id): void { $this->a['desligados'][] = $id; }
    public function transferir(int $antigo, int $novo): string {
        if ($this->a['falhar_transferencia'] > 0) { $this->a['falhar_transferencia']--; throw new Exception('falha na transferencia (teste)'); }
        $this->a['transferencias'][] = [$antigo, $novo];
        foreach ($this->a['contatos'] as $n => $c) { if ((int) $c['id'] === $antigo) { unset($this->a['contatos'][$n]); } }
        return 'Tickets=2 antigo excluido';
    }
    public function listar(string $situacao, array $curciol, array $filtros, int $offset, int $limit): array { return [[], 0]; }
    public function conversa(int $contatoId, int $limite = 3000): array { return []; }
};
ApchatBancoService::usarFalso($banco);

TSession::setValue('userid', 1);
tokenApchatDeTeste();
TTransaction::open('escritorio');
TSession::setValue('userunitid', (int) TTransaction::get()->query("SELECT system_unit_id FROM escritorio WHERE COALESCE(token_apchat, '') <> '' ORDER BY id LIMIT 1")->fetchColumn());
TTransaction::close();

$ids = [];
$numerosTeste = ['5511987660001', '5511987660002', '5511987660003', '5511987660004', '5511987660005', '5511987660006', '5511987660007', '5511987660008', '5511987660009', '5511987660010', '5511987660011', '5511987660012'];
$novo = function (string $nome, string $telefone) use (&$ids) {
    TTransaction::open('escritorio');
    $p = new Pessoa();
    $p->nome = MARCA_BANCO . ' ' . $nome;
    $p->tipo_pessoa_id = TipoPessoa::FISICA;
    $p->telefone = PessoaContatoService::telefoneValido($telefone);
    $p->store();
    PessoaContatoService::aplicarPrincipal($p->id, PessoaContatoService::estadoAtual(null));
    TTransaction::close();
    $ids[] = (int) $p->id;
    return (int) $p->id;
};
$idContato = function (int $pessoa) { return (int) (q("SELECT COALESCE(idapchat, 0) i FROM pessoa_contato WHERE pessoa_id = {$pessoa} ORDER BY id LIMIT 1")[0]['i'] ?? 0); };
/** troca o telefone do principal da pessoa como o salvar da tela faz (antes x depois + registrarTrocas) */
$trocar = function (int $pessoa, string $telefone) {
    TTransaction::open('escritorio');
    $antes = PessoaContatoService::estadoAtual($pessoa);
    TTransaction::get()->exec("UPDATE pessoa SET telefone = '{$telefone}' WHERE id = {$pessoa}");
    PessoaContatoService::aplicarPrincipal($pessoa, $antes);
    $m = PessoaContatoService::trocasDeNumero($antes['contatos'], PessoaContatoService::numerosDosContatos($pessoa));
    PessoaContatoService::limparIdApchat(array_column($m['trocas'], 2));
    APChatContactService::registrarTrocas($pessoa, $m['trocas'], $m['removidos']);
    TTransaction::close();
    return $m;
};

try {
    x("DELETE FROM apchat_fila WHERE numero IN ('" . implode("','", $numerosTeste) . "')");

    // ---------- envio lendo o banco: igual = sem chamada; mudou = so update; nao existe = so create ----------
    $a = $novo('A', '11987660001');
    $b = $novo('B', '11987660002');
    $c = $novo('C', '11987660003');
    $apchat['contatos']['5511987660001'] = ['id' => 80900, 'name' => MARCA_BANCO . ' A', 'firstName' => 'TESTE', 'lastName' => 'APCHAT BANCO A'];
    $apchat['contatos']['551187660002'] = ['id' => 80901, 'name' => 'NOME ANTIGO NO APCHAT'];   // sem o nono digito
    $apchat['chamadas'] = [];
    $r = APChatContactService::sincronizarLote([$a, $b, $c], ApchatContatoLog::ORIGEM_LOTE);
    igual('lote com o banco: igual / mudou / nao existe', ['SUCESSO|NENHUMA', 'SUCESSO|UPDATE', 'SUCESSO|CREATE'],
        array_map(function ($id) use ($r) { return $r[$id]->situacao . '|' . $r[$id]->operacao; }, [$a, $b, $c]));
    igual('lote com o banco: so 2 chamadas (nenhum showcontact)', ['updateContact', 'createContact'], array_values(array_diff($apchat['chamadas'], [])));
    igual('lote com o banco: idapchat gravado nos 3', [80900, 80901, $apchat['contatos']['5511987660003']['id'] ?? -1], [$idContato($a), $idContato($b), $idContato($c)]);
    ok('lote com o banco: espelho gravado para os 3 (a protecao do APChat aceita so isso)', isset($apchat['espelho'][80900], $apchat['espelho'][80901], $apchat['espelho'][$idContato($c)]));
    igual('lote com o banco: update no numero que esta no APChat (sem o nono digito)', '551187660002', $apchat['espelho'][80901]['numero'] ?? null);

    // ---------- troca de numero: o historico do antigo passa para o novo ----------
    $apchat['chamadas'] = [];
    $m = $trocar($a, '11987660004');
    igual('troca: detectada no salvar (antigo -> novo)', [['5511987660001', '5511987660004']], array_map(function ($t) { return [$t[0], $t[1]]; }, $m['trocas']));
    igual('troca: idapchat do contato limpo ate o envio', 0, $idContato($a));
    $r = APChatContactService::sincronizarPessoa($a, [], ApchatContatoLog::ORIGEM_CADASTRO);
    $novoId = (int) ($apchat['contatos']['5511987660004']['id'] ?? 0);
    igual('troca: contato novo criado e o historico do antigo transferido para ele', [[80900, $novoId]], $apchat['transferencias']);
    igual('troca: idapchat do contato = o novo', $novoId, $idContato($a));
    ok('troca: log da transferencia', (int) q("SELECT count(*) n FROM apchat_contato_log WHERE pessoa_id = {$a} AND operacao = 'TRANSFERENCIA' AND situacao = 'SUCESSO'")[0]['n'] === 1);
    ok('troca: resultado do salvar sem problema', $r->situacao === 'SUCESSO', $r->mensagem);

    // ---------- troca do principal: a copia em outro cadastro acompanha (regra 23), entao o antigo sai de todo lugar ----------
    $d = $novo('D', '11987660005');
    APChatContactService::sincronizarLote([$d], ApchatContatoLog::ORIGEM_LOTE);
    x("INSERT INTO pessoa_contato (pessoa_id, descricao, telefone, principal) VALUES ({$b}, 'MARIDO DA D', '11987660005', 'N')");
    $apchat['transferencias'] = [];
    $trocar($d, '11987660006');
    APChatContactService::sincronizarPessoa($d, [], ApchatContatoLog::ORIGEM_CADASTRO);
    igual('troca do principal: a copia no outro cadastro acompanhou o numero novo', '11987660006', q("SELECT telefone FROM pessoa_contato WHERE pessoa_id = {$b} AND descricao = 'MARIDO DA D'")[0]['telefone'] ?? null);
    igual('troca do principal: historico transferido (o numero antigo saiu de todo lugar)', 1, count($apchat['transferencias']));

    // ---------- troca de um contato NAO principal cujo numero continua em outro cadastro: nao transfere ----------
    x("INSERT INTO pessoa_contato (pessoa_id, descricao, telefone, principal) VALUES ({$d}, 'RECADO', '11987660011', 'N')");
    x("INSERT INTO pessoa_contato (pessoa_id, descricao, telefone, principal) VALUES ({$b}, 'RECADO DA D', '11987660011', 'N')");
    APChatContactService::sincronizarPessoa($d, [], ApchatContatoLog::ORIGEM_CADASTRO);
    $apchat['transferencias'] = [];
    TTransaction::open('escritorio');
    $antes = PessoaContatoService::estadoAtual($d);
    TTransaction::get()->exec("UPDATE pessoa_contato SET telefone = '11987660012' WHERE pessoa_id = {$d} AND descricao = 'RECADO'");
    $mm = PessoaContatoService::trocasDeNumero($antes['contatos'], PessoaContatoService::numerosDosContatos($d));
    APChatContactService::registrarTrocas($d, $mm['trocas'], $mm['removidos']);
    TTransaction::close();
    APChatContactService::sincronizarPessoa($d, [], ApchatContatoLog::ORIGEM_CADASTRO);
    igual('troca: numero antigo ainda em outro cadastro -> sem transferencia', [], $apchat['transferencias']);
    ok('troca: o contato do numero antigo continua no APChat (ligado ao outro cadastro)', isset($apchat['contatos']['5511987660011']));

    // ---------- numero que saiu da Curciol: fica travado no APChat ----------
    $e = $novo('E', '11987660007');
    APChatContactService::sincronizarLote([$e], ApchatContatoLog::ORIGEM_LOTE);
    $idE = $idContato($e);
    TTransaction::open('escritorio');
    $antes = PessoaContatoService::estadoAtual($e);
    TTransaction::get()->exec("DELETE FROM pessoa_contato WHERE pessoa_id = {$e}");
    TTransaction::get()->exec("UPDATE pessoa SET telefone = NULL WHERE id = {$e}");
    $mm = PessoaContatoService::trocasDeNumero($antes['contatos'], PessoaContatoService::numerosDosContatos($e));
    APChatContactService::registrarTrocas($e, $mm['trocas'], $mm['removidos']);
    TTransaction::close();
    APChatContactService::sincronizarPessoa($e, [], ApchatContatoLog::ORIGEM_CADASTRO);
    ok('numero excluido da Curciol: o contato do APChat fica travado (espelho desligado)', in_array($idE, $apchat['desligados'], true), json_encode($apchat['desligados']));

    // ---------- banco do APChat fora: erro, fila; a fila refaz quando ele volta ----------
    $f = $novo('F', '11987660008');
    $apchat['fora'] = true;
    $apchat['chamadas'] = [];
    $r = APChatContactService::sincronizarPessoa($f, [], ApchatContatoLog::ORIGEM_CADASTRO);
    ok('banco fora: erro, nenhuma chamada a API (sem o espelho a protecao desfaria)', $r->situacao === 'ERRO' && !$apchat['chamadas'], $r->mensagem);
    igual('banco fora: numero na fila', 1, (int) q("SELECT count(*) n FROM apchat_fila WHERE motivo = 'ENVIO' AND numero = '5511987660008'")[0]['n']);
    $apchat['fora'] = false;
    x("UPDATE apchat_fila SET proxima_tentativa = now() - interval '1 minute' WHERE numero IN ('" . implode("','", $numerosTeste) . "')");
    ob_start(); ApchatFilaService::processar(); ob_end_clean();
    igual('fila: refez sozinha e saiu da fila', [0, true], [(int) q("SELECT count(*) n FROM apchat_fila WHERE numero = '5511987660008'")[0]['n'], isset($apchat['contatos']['5511987660008'])]);
    ok('fila: idapchat gravado', $idContato($f) > 0);

    // ---------- transferencia que falha: fila TRANSFERENCIA e a fila refaz ----------
    $g = $novo('G', '11987660009');
    APChatContactService::sincronizarLote([$g], ApchatContatoLog::ORIGEM_LOTE);
    $idG = $idContato($g);
    $apchat['falhar_transferencia'] = 1;
    $apchat['transferencias'] = [];
    $trocar($g, '11987660010');
    $r = APChatContactService::sincronizarPessoa($g, [], ApchatContatoLog::ORIGEM_CADASTRO);
    ok('transferencia falhou: erro no resultado e na fila', $r->situacao === 'ERRO' && (int) q("SELECT count(*) n FROM apchat_fila WHERE motivo = 'TRANSFERENCIA' AND numero = '5511987660010'")[0]['n'] === 1, $r->mensagem);
    x("UPDATE apchat_fila SET proxima_tentativa = now() - interval '1 minute' WHERE numero IN ('" . implode("','", $numerosTeste) . "')");
    ob_start(); ApchatFilaService::processar(); ob_end_clean();
    igual('fila: transferencia refeita', [[$idG, (int) ($apchat['contatos']['5511987660010']['id'] ?? 0)]], $apchat['transferencias']);
    igual('fila: transferencia saiu da fila', 0, (int) q("SELECT count(*) n FROM apchat_fila WHERE numero = '5511987660010'")[0]['n']);

    // ---------- tela "Contatos sem vinculo": vincular a um cadastro existente ----------
    $apchat['contatos']['5511987669001'] = ['id' => 80950, 'name' => '~Filho da D'];
    $r = ApchatVinculoService::vincular($d, '5511987669001', null, 'filho - jose', false);
    $linha = q("SELECT nome, descricao, principal, idapchat FROM pessoa_contato WHERE pessoa_id = {$d} AND telefone = '11987669001'")[0] ?? [];
    igual('vincular: numero na aba Contatos da pessoa, nao principal, com a descricao, idapchat gravado', ['FILHO - JOSE', 'N', 80950], [$linha['descricao'] ?? null, $linha['principal'] ?? null, (int) ($linha['idapchat'] ?? 0)]);
    igual('vincular: o contato do APChat recebeu o nome da Curciol (descricao)', 'FILHO - JOSE', $apchat['contatos']['5511987669001']['name'] ?? null);
    lanca('vincular como principal um telefone que ja e principal de outro: barrado', function () use ($b) { ApchatVinculoService::vincular($b, '5511987660003', null, null, true); }, 'contato principal de');

    // ---------- tela: trocar o numero de um contato pelo do APChat (o historico do antigo vai para ele) ----------
    $apchat['contatos']['5511987669002'] = ['id' => 80951, 'name' => '5511987669002'];
    $apchat['transferencias'] = [];
    $contatoC = (int) q("SELECT id FROM pessoa_contato WHERE pessoa_id = {$c} ORDER BY id LIMIT 1")[0]['id'];
    $idAntigoC = $idContato($c);
    ApchatVinculoService::trocarNumero($contatoC, '5511987669002');
    igual('trocar numero: dados da pessoa acompanham o principal', '11987669002', q("SELECT telefone FROM pessoa WHERE id = {$c}")[0]['telefone']);
    igual('trocar numero: historico do contato antigo transferido para o do APChat', [[$idAntigoC, 80951]], $apchat['transferencias']);
    igual('trocar numero: idapchat = o contato do APChat', 80951, $idContato($c));

    // ---------- tela: ignorar ----------
    ApchatVinculoService::ignorar(80999, '5511987669999', 'PROPAGANDA');
    ok('ignorar: entra na lista de ignorados', in_array(80999, ApchatVinculoService::contextoCurciol()['ignorados'], true));
    ApchatVinculoService::desfazerIgnorar(80999);
    ok('desfazer ignorar: sai da lista', !in_array(80999, ApchatVinculoService::contextoCurciol()['ignorados'], true));
    ok('contexto da tela: numeros da Curciol com a variante do nono digito', in_array('551187660002', ApchatVinculoService::contextoCurciol()['numeros'], true));
} finally {
    APChatContactService::usarTransporte(null);
    ApchatBancoService::usarFalso(null);
    x('DELETE FROM apchat_contato_ignorado WHERE contato_id = 80999');
    x("DELETE FROM apchat_fila WHERE numero IN ('" . implode("','", $numerosTeste) . "')");
    if ($ids) {
        x('DELETE FROM apchat_contato_log WHERE pessoa_id IN (' . implode(',', $ids) . ')');
        x('DELETE FROM pessoa_contato WHERE pessoa_id IN (' . implode(',', $ids) . ')');
        x('DELETE FROM pessoa WHERE id IN (' . implode(',', $ids) . ')');
    }
}
fim();
