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

// Beneficiarios semeados, por nome e regra (nomes comparados em NFC)
$benef = ['Cliente + Honorários Contratuais' => BeneficiarioCredito::REGRA_CLIENTE_E_HONORARIOS,
          'Sucumbência' => BeneficiarioCredito::REGRA_SOMENTE_HONORARIOS,
          'Honorários Contratuais' => BeneficiarioCredito::REGRA_SOMENTE_HONORARIOS,
          'Cliente' => BeneficiarioCredito::REGRA_SOMENTE_CLIENTE];
foreach ($benef as $nome => $regra) {
    $s = $c->prepare('select regra from beneficiario_credito where normalize(nome, NFC) = normalize(?, NFC)');
    $s->execute([$nome]);
    igual("beneficiario {$nome}", $regra, $s->fetchColumn());
}
$b = BeneficiarioCredito::where('nome', '=', 'Sucumbência')->first();
igual('model: Sucumbencia = somente honorarios', BeneficiarioCredito::REGRA_SOMENTE_HONORARIOS, $b->regra ?? null);

// Parcerias semeadas: [nome => [participantes, soma dos percentuais fixos, soma MARCO2 antes, soma MARCO2 depois]]
$parcerias = ['Curciol' => [4, 100, 0, 0], 'Custas' => [1, 100, 0, 0], 'Parceria Ivan' => [5, 100, 0, 0],
              'Parceria Juan' => [5, 100, 0, 0], 'Parceria Juan Estágio' => [5, 100, 0, 0],
              'Parceria Kleber Andre Ferreira' => [7, 0, 100, 100], 'Parceria Campos' => [7, 0, 100, 100]];
$total = 0;
$q = $c->prepare("select count(*), coalesce(sum(pp.percentual),0),
        coalesce(sum(case when pp.nivel = 'MARCO2' then pp.percentual_antes end),0),
        coalesce(sum(case when pp.nivel = 'MARCO2' then pp.percentual_depois end),0)
    from parceria_levantamento p join parceria_levantamento_participante pp on pp.parceria_levantamento_id = p.id
    where normalize(p.nome, NFC) = normalize(?, NFC)");
foreach ($parcerias as $nome => $esp) {
    $q->execute([$nome]);
    $r = $q->fetch(PDO::FETCH_NUM);
    igual("{$nome}: participantes", $esp[0], (int) $r[0]);
    igual("{$nome}: soma percentual fixo", $esp[1], (float) $r[1]);
    igual("{$nome}: soma marco2 antes", $esp[2], (float) $r[2]);
    igual("{$nome}: soma marco2 depois", $esp[3], (float) $r[3]);
    $total += (int) $r[0];
}
igual('34 participantes nos seeds', 34, $total);

// Pessoa presente em cada parceria, por nome (NFC)
$presente = $c->prepare("select count(*) from parceria_levantamento_participante pp join parceria_levantamento p on p.id = pp.parceria_levantamento_id
    join pessoa pe on pe.id = pp.pessoa_id where normalize(p.nome, NFC) = normalize(?, NFC) and normalize(pe.nome, NFC) = normalize(?, NFC)");
foreach (['Curciol', 'Parceria Ivan', 'Parceria Juan', 'Parceria Juan Estágio', 'Parceria Kleber Andre Ferreira', 'Parceria Campos'] as $p) {
    $presente->execute([$p, 'JOSÉ ALMIR CURCIOL']);
    igual("JOSE ALMIR em {$p}", 1, (int) $presente->fetchColumn());
}
$presente->execute(['Custas', 'JOSÉ ALMIR CURCIOL']);
igual('JOSE ALMIR fora de Custas', 0, (int) $presente->fetchColumn());

// So a Curciol recebe a diferenca na Parceria Juan
$dif = $c->query("select pe.nome from parceria_levantamento_participante pp join parceria_levantamento p on p.id = pp.parceria_levantamento_id join pessoa pe on pe.id = pp.pessoa_id where p.nome = 'Parceria Juan' and pp.recebe_diferenca = 'S'")->fetchAll(PDO::FETCH_COLUMN);
igual('so a Curciol recebe a diferenca na Parceria Juan', ['CURCIOL SOCIEDADE DE ADVOGADOS'], $dif);

// Parceiros criados pela carga existem no grupo 6
$g = $c->prepare('select count(*) from pessoa pe join pessoa_grupo g on g.pessoa_id = pe.id and g.grupo_id = 6 where normalize(pe.nome, NFC) = normalize(?, NFC)');
// parceiros com os cadastros reais (2026-10-20-parceiros-alliance-estrutura.sql e 2026-10-20-grupos-profissional-parceiro-fornecedor.sql)
foreach (['KLEBER ANDRE FERREIRA', 'CARLOS ROBERTO DE CAMPOS FILHO', 'ALLIANCE ADMINISTRAÇÃO COMÉRCIO E ENGENHARIA LTDA', 'LUMES GESTAO IMOBILIARIA LTDA'] as $n) {
    $g->execute([$n]);
    ok("parceiro {$n} no grupo 6", (int) $g->fetchColumn() >= 1);
}
TTransaction::rollback();
fim();
