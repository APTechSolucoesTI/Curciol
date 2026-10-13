<?php
// tests/levantamento/EsquemaTest.php
require __DIR__ . '/../bootstrap.php';

TTransaction::open('escritorio');
$c = TTransaction::get();
$col = function ($t, $col) use ($c) {
    $s = $c->prepare("select count(*) from information_schema.columns where table_name = ? and column_name = ?");
    $s->execute([$t, $col]); return (int) $s->fetchColumn() === 1;
};
foreach (['beneficiario_credito' => 'regra', 'parceria_levantamento' => 'tipo', 'parceria_levantamento_participante' => 'percentual_antes',
          'levantamento_financeiro' => 'requisicao_pagamento_etapa3_id', 'levantamento_financeiro_repasse' => 'valor', 'conta' => 'requisicao_pagamento_id'] as $t => $cl) {
    ok("coluna {$t}.{$cl}", $col($t, $cl));
}
$unico = $c->query("select count(*) from pg_indexes where tablename = 'levantamento_financeiro' and indexdef ilike '%unique%requisicao_pagamento_etapa3_id%'")->fetchColumn();
ok('indice unico por pagamento', (int) $unico === 1);
igual('4 beneficiarios na carga', 4, (int) $c->query('select count(*) from beneficiario_credito')->fetchColumn());
igual('7 parcerias na carga', 7, (int) $c->query('select count(*) from parceria_levantamento')->fetchColumn());
igual('Parceria Juan com 5 participantes', 5, (int) $c->query("select count(*) from parceria_levantamento_participante pp join parceria_levantamento p on p.id = pp.parceria_levantamento_id where p.nome = 'Parceria Juan'")->fetchColumn());
igual('so a Curciol recebe a diferenca na Parceria Juan', 'CURCIOL SOCIEDADE DE ADVOGADOS', $c->query("select pe.nome from parceria_levantamento_participante pp join parceria_levantamento p on p.id = pp.parceria_levantamento_id join pessoa pe on pe.id = pp.pessoa_id where p.nome = 'Parceria Juan' and pp.recebe_diferenca = 'S'")->fetchColumn());
$b = BeneficiarioCredito::where('nome', '=', 'Sucumbência')->first();
igual('Sucumbencia = somente honorarios', BeneficiarioCredito::REGRA_SOMENTE_HONORARIOS, $b->regra ?? null);
TTransaction::rollback();
fim();
