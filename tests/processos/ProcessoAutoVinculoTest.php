<?php
// tests/processos/ProcessoAutoVinculoTest.php
// Publicacao cujo "principal" e o proprio numero do processo (eproc: "Nº 6100122-51.2026.8.26.0019/SP")
// nao pode vincular o processo a ele mesmo como principal e incidente.
// Tudo roda numa transacao desfeita no final. So no banco de homologacao.
require __DIR__ . '/../bootstrap.php';

$A = 'APIPublicacaoController';

// ---------- 1) importacao: principal igual ao numero unico nao e principal ----------
igual('principal igual ao unico vira nulo', null, $A::principalDistinto('6100122-51.2026.8.26.0019', '6100122-51.2026.8.26.0019'));
igual('principal com espacos igual ao unico vira nulo', null, $A::principalDistinto(' 6100122-51.2026.8.26.0019 ', '6100122-51.2026.8.26.0019'));
igual('principal diferente do unico e mantido', '1000001-01.2020.8.26.0019', $A::principalDistinto('1000001-01.2020.8.26.0019', '1000001-01.2020.8.26.0019/01'));
igual('principal vazio vira nulo', null, $A::principalDistinto('  ', '1000001-01.2020.8.26.0019'));
igual('principal nulo segue nulo', null, $A::principalDistinto(null, '1000001-01.2020.8.26.0019'));

TTransaction::open('escritorio');
try {
    $c = TTransaction::get();
    $banco = $c->query('SELECT current_database()')->fetchColumn();
    if ($banco !== 'curciol_homologacao') { throw new Exception("So roda em curciol_homologacao (conectado em {$banco})."); }

    $novoProcesso = function (string $numero): int {
        $p = new Processo();
        $p->tipo_processo_id = TipoProcesso::JUDICIAL;
        $p->numero_cnj_numero = $numero;
        $p->pre_processo = 'N';
        $p->store();
        return (int) $p->id;
    };
    $novaPublicacao = function (string $unico, ?string $principal, int $processo_id): Publicacao {
        $pub = new Publicacao();
        $pub->numero_publicacao = 'TESTE AUTOVINCULO';
        $pub->numero_unico_processo = $unico;
        $pub->numero_processo_principal = $principal;
        $pub->processo_id = $processo_id;
        $pub->store();
        return $pub;
    };
    $vinculos = function (int $principal, int $incidente) use ($c): int {
        $st = $c->prepare('SELECT count(*) FROM processo_vinculo WHERE processo_principal_id = ? AND processo_incidente_id = ?');
        $st->execute([$principal, $incidente]);
        return (int) $st->fetchColumn();
    };

    $N = '9999990-01.2026.8.26.9990';
    $M = '9999991-01.2026.8.26.9990';
    $P = $novoProcesso($N);
    $I = $novoProcesso($M);
    $pubP = $novaPublicacao($N, $N, $P);  // publicacao do proprio processo, com principal = numero dele
    $novaPublicacao($M, $N, $I);          // publicacao de um incidente de verdade

    // ---------- 2) salvar processo / "Criar processo": vincula os incidentes do numero, nunca ele mesmo ----------
    PreProcessoService::vincularIncidentesDoNumero($P, $N);
    igual('salvar: processo nao vira incidente de si mesmo', 0, $vinculos($P, $P));
    igual('salvar: incidente de verdade e vinculado', 1, $vinculos($P, $I));
    PreProcessoService::vincularIncidentesDoNumero($P, $N);
    igual('salvar de novo nao duplica o vinculo', 1, $vinculos($P, $I));

    // ---------- 3) importacao: publicacao nova do mesmo processo nao cria auto-vinculo ----------
    $A::vincularProcessoPrincipal($pubP, $N);
    igual('importacao: processo nao vira incidente de si mesmo', 0, $vinculos($P, $P));
} catch (Throwable $e) {
    ok('sem excecao', false, $e->getMessage());
} finally {
    TTransaction::rollback();
}

fim();
