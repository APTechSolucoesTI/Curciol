<?php
// tests/levantamento/GruposPessoaTest.php
// Regra do escritorio: todo Profissional e tambem Parceiro, e todo Parceiro e tambem Fornecedor (PessoaGrupo::onAfterStore).
require __DIR__ . '/../bootstrap.php';

function grupos(int $pessoaId): array
{
    TTransaction::open('escritorio');
    $g = array_map('intval', PessoaGrupo::where('pessoa_id', '=', $pessoaId)->getIndexedArray('id', 'grupo_id'));
    TTransaction::close();
    sort($g);
    return array_values($g);
}

TTransaction::open('escritorio');
$c = TTransaction::get();
if ($c->query('select current_database()')->fetchColumn() !== 'curciol_homologacao') { echo "ABORTADO: so em homologacao\n"; exit(1); }
$ids = [];
foreach (['PROFISSIONAL', 'PARCEIRO', 'CLIENTE'] as $n) {
    $st = $c->prepare("INSERT INTO pessoa (tipo_pessoa_id, nome, nome_busca, observacao, data_criacao) VALUES (1, ?, ?, 'teste de grupos', now()) RETURNING id");
    $st->execute(["TESTE LEVANTAMENTO GRUPO {$n}", "TESTE LEVANTAMENTO GRUPO {$n}"]);
    $ids[$n] = (int) $st->fetchColumn();
}
TTransaction::close();

try {
    $gravar = function (int $pessoaId, $grupoId) {
        TTransaction::open('escritorio');
        $g = new PessoaGrupo();
        $g->pessoa_id = $pessoaId;
        $g->grupo_id = $grupoId;
        $g->store();
        TTransaction::close();
    };
    $gravar($ids['PROFISSIONAL'], Grupo::PROFISSIONAL);
    igual('profissional vira parceiro e fornecedor', [1, 3, 6], grupos($ids['PROFISSIONAL']));
    $gravar($ids['PARCEIRO'], Grupo::PARCEIRO);
    igual('parceiro vira fornecedor', [3, 6], grupos($ids['PARCEIRO']));
    $gravar($ids['CLIENTE'], Grupo::CLIENTE);
    igual('cliente continua so cliente', [2], grupos($ids['CLIENTE']));
    $gravar($ids['PROFISSIONAL'], Grupo::PROFISSIONAL);
    igual('sem vinculo repetido', [1, 1, 3, 6], grupos($ids['PROFISSIONAL']));

    TTransaction::open('escritorio');
    $semRegra = (int) TTransaction::get()->query('SELECT COUNT(*) FROM pessoa_grupo g WHERE (g.grupo_id = 1 AND NOT EXISTS (SELECT 1 FROM pessoa_grupo x WHERE x.pessoa_id = g.pessoa_id AND x.grupo_id = 6))
                                                   OR (g.grupo_id = 6 AND NOT EXISTS (SELECT 1 FROM pessoa_grupo x WHERE x.pessoa_id = g.pessoa_id AND x.grupo_id = 3))')->fetchColumn();
    $provisorios = (int) TTransaction::get()->query("SELECT COUNT(*) FROM pessoa WHERE observacao = 'Parceiro de levantamento (cadastro inicial do financeiro de levantamentos).'")->fetchColumn();
    TTransaction::close();
    igual('cadastros existentes seguem a regra', 0, $semRegra);
    igual('sem parceiros provisorios (KBÇA, CAMPOS, ALLIANCE, ESTRUTURA)', 0, $provisorios);
} finally {
    TTransaction::open('escritorio');
    $lista = implode(',', $ids);
    TTransaction::get()->exec("DELETE FROM pessoa_grupo WHERE pessoa_id IN ({$lista})");
    TTransaction::get()->exec("DELETE FROM pessoa WHERE id IN ({$lista})");
    TTransaction::close();
}
fim();
