<?php
// tests/apchat/ApchatSemVinculoListaTest.php
// "Contatos sem vinculo" no padrao das listagens (10/11/2026): ordenacao clicando no titulo de cada coluna (so colunas permitidas),
// filtros em popover no cabecalho (funil) que somam com a busca, faixa "Filtros aplicados". APChat falso.
require __DIR__ . '/../bootstrap.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function saida(callable $f): string { ob_start(); try { $f(); } finally { $s = ob_get_clean(); } return $s; }

igual('ordem padrao: criados mais recentes', 'c."createdAt" DESC NULLS LAST, c.id DESC', ApchatBancoService::ordemSemVinculo('', ''));
igual('ordem pelo nome', 'lower(c.name) ASC NULLS LAST, c.id DESC', ApchatBancoService::ordemSemVinculo('name', 'asc'));
igual('ordem pela ultima mensagem', 't."updatedAt" DESC NULLS LAST, c.id DESC', ApchatBancoService::ordemSemVinculo('ultima_mensagem', 'desc'));
igual('coluna desconhecida: padrao (sem SQL do usuario)', 'c."createdAt" DESC NULLS LAST, c.id DESC', ApchatBancoService::ordemSemVinculo('1; DROP TABLE x', 'asc'));
igual('direcao estranha vira DESC', 'f.queue DESC NULLS LAST, c.id DESC', ApchatBancoService::ordemSemVinculo('fila', 'asc; --'));

$falso = new class {
    public $pedidos = [];
    public function disponivel() { return true; }
    public function listar(string $situacao, array $curciol, array $filtros, int $offset, int $limit, string $ordem = '', string $direcao = ''): array {
        $this->pedidos[] = compact('situacao', 'filtros', 'ordem', 'direcao');
        return [[['id' => 1, 'name' => 'Fulano', 'number' => '5511900007777', 'pushname' => null, 'criado_em' => '2026-11-06 16:52:00', 'ticket_id' => null,
                  'ticket_status' => null, 'ultima_mensagem' => null, 'ultima_mensagem_em' => null, 'atendente' => null, 'fila' => null]], 1];
    }
};
ApchatBancoService::usarFalso($falso);
TSession::setValue('userid', 1);
TTransaction::open('escritorio');
TSession::setValue('userunitid', (int) TTransaction::get()->query('SELECT system_unit_id FROM escritorio ORDER BY id LIMIT 1')->fetchColumn());
TTransaction::close();
FiltroLista::limparSessao('ApchatContatoSemVinculoList');

$tela = saida(function () { $l = new ApchatContatoSemVinculoList([]); $l->onReload(['order' => 'name', 'direction' => 'asc']); $l->show(); });
ok('lista monta', strpos($tela, 'Fulano') !== false && stripos($tela, 'fatal error') === false, substr($tela, 0, 300));
ok('filtros em popover no cabecalho (funil)', strpos($tela, 'tdatagrid_init_header_popover_filters') !== false);
ok('titulos das colunas ordenam', substr_count($tela, 'order=') >= 6 || substr_count($tela, '&amp;order=') >= 6, '');
igual('ordem pedida chega ao banco', ['name', 'asc'], [end($falso->pedidos)['ordem'], end($falso->pedidos)['direcao']]);

// filtro do cabecalho soma com a busca e entra na faixa
$_POST = ['nome_col' => 'Ful'];
saida(function () { $l = new ApchatContatoSemVinculoList([]); $l->onSearch(['nome_col' => 'Ful', 'static' => '1']); });
$_POST = ['nome_col' => 'Ful', 'globalfilt' => '7777']; // no navegador o formulario vai inteiro, com o filtro do cabecalho ja preenchido
saida(function () { $l = new ApchatContatoSemVinculoList([]); $l->onGlobalSearch(['globalfilt' => '7777']); });
$_POST = [];
$tela2 = saida(function () { $l = new ApchatContatoSemVinculoList([]); $l->onReload([]); $l->show(); });
$filtros = end($falso->pedidos)['filtros'];
ok('filtros somam (cabecalho + busca)', ($filtros['nome'] ?? '') === 'Ful' && strpos(json_encode($filtros), '7777') !== false, json_encode($filtros));
ok('faixa "Filtros aplicados" com o filtro do cabecalho', strpos($tela2, 'curciolFaixaFiltros') !== false && strpos($tela2, 'Ful') !== false, substr($tela2, 0, 300));

FiltroLista::limparSessao('ApchatContatoSemVinculoList');
ApchatBancoService::usarFalso(null);
fim();
