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

// Parcerias: a homologacao e copia da producao desde 09/11/2026 (nomes trocados pelo escritorio, ex. SERVIDORES (CAMPOS)).
// Confere a regra, nao os nomes da carga inicial: fixa soma 100%; por tempo (por periodos desde 13/11/2026) soma 100% em
// cada periodo, so com niveis MARCO1/MARCO2/MARCO12, e o "entre" de nivel 1/2 espelha a coluna certa.
$parcerias = $c->query("select p.id, p.nome, p.tipo, count(*) n, coalesce(sum(pp.percentual),0) fixo,
        coalesce(sum(pp.percentual_antes),0) antes, coalesce(sum(pp.percentual_entre),0) entre, coalesce(sum(pp.percentual_depois),0) depois,
        count(*) filter (where p.tipo = 'TEMPORAL' and (pp.nivel not in ('MARCO1','MARCO2','MARCO12')
            or (pp.nivel = 'MARCO1' and pp.percentual_entre is distinct from pp.percentual_depois)
            or (pp.nivel = 'MARCO2' and pp.percentual_entre is distinct from pp.percentual_antes))) inconsistentes,
        count(*) filter (where pp.recebe_diferenca = 'S') dif
    from parceria_levantamento p join parceria_levantamento_participante pp on pp.parceria_levantamento_id = p.id
    where p.ativo = 'S' group by p.id, p.nome, p.tipo order by p.id")->fetchAll(PDO::FETCH_OBJ);
ok('ha parcerias ativas', count($parcerias) > 0);
foreach ($parcerias as $p) {
    if ($p->tipo === 'FIXA') {
        igual("{$p->nome}: soma 100%", 100.0, (float) $p->fixo);
    } else {
        igual("{$p->nome}: 100% em cada periodo", [100.0, 100.0, 100.0], [(float) $p->antes, (float) $p->entre, (float) $p->depois]);
        igual("{$p->nome}: niveis e entre consistentes", 0, (int) $p->inconsistentes);
    }
    igual("{$p->nome}: um so recebe a diferenca", 1, (int) $p->dif);
}
$temporais = array_filter($parcerias, function ($p) { return $p->tipo === 'TEMPORAL'; });
ok('ha parceria por tempo (SERVIDORES)', count($temporais) >= 2);
$curciolDif = $c->query("select count(*) from parceria_levantamento_participante pp join pessoa pe on pe.id = pp.pessoa_id
    where pp.recebe_diferenca = 'S' and pe.nome = 'CURCIOL SOCIEDADE DE ADVOGADOS'")->fetchColumn();
ok('a Curciol recebe a diferenca nas parcerias por tempo', (int) $curciolDif >= count($temporais));

// Parceiros criados pela carga existem no grupo 6
$g = $c->prepare('select count(*) from pessoa pe join pessoa_grupo g on g.pessoa_id = pe.id and g.grupo_id = 6 where normalize(pe.nome, NFC) = normalize(?, NFC)');
// parceiros com os cadastros reais (2026-10-20-parceiros-alliance-estrutura.sql e 2026-10-20-grupos-profissional-parceiro-fornecedor.sql)
foreach (['KLEBER ANDRE FERREIRA', 'CARLOS ROBERTO DE CAMPOS FILHO', 'ALLIANCE ADMINISTRAÇÃO COMÉRCIO E ENGENHARIA LTDA', 'LUMES GESTAO IMOBILIARIA LTDA'] as $n) {
    $g->execute([$n]);
    ok("parceiro {$n} no grupo 6", (int) $g->fetchColumn() >= 1);
}
TTransaction::rollback();
fim();
