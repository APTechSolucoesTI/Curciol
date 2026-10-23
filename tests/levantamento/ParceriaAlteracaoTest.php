<?php
// tests/levantamento/ParceriaAlteracaoTest.php
// Alterar os participantes de uma parceria vale so para os financeiros gerados depois:
// o financeiro ja gerado guarda os percentuais e valores da epoca (ficha e repasses da parcela).
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
$S = 'LevantamentoFinanceiroService';

$nome = 'TESTE PARCERIA ALTERACAO ' . uniqid();
$parceriaId = null;
$f = Fixtures::criar();
$f2 = null;
try {
    TTransaction::open('escritorio');
    $benef   = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
    $curciol = (int) Pessoa::where('nome', '=', 'CURCIOL SOCIEDADE DE ADVOGADOS')->first()->id;
    $kleber  = (int) Pessoa::where('nome', '=', 'KLEBER CURCIOL')->first()->id;
    $p = new ParceriaLevantamento; $p->nome = $nome; $p->tipo = ParceriaLevantamento::TIPO_FIXA; $p->ativo = 'S'; $p->store();
    $parceriaId = (int) $p->id;
    $participantes = function (array $lista) use ($parceriaId) {
        // mesmo caminho do formulario: apaga e regrava os participantes
        ParceriaLevantamentoParticipante::where('parceria_levantamento_id', '=', $parceriaId)->delete();
        $ordem = 1;
        foreach ($lista as [$pessoa, $pct, $dif]) {
            $q = new ParceriaLevantamentoParticipante;
            $q->parceria_levantamento_id = $parceriaId; $q->pessoa_id = $pessoa; $q->nivel = 'FIXO';
            $q->percentual = $pct; $q->recebe_diferenca = $dif; $q->ordem = $ordem++;
            $q->store();
        }
    };
    $participantes([[$curciol, 50, 'S'], [$kleber, 50, 'N']]);
    TTransaction::close();

    $d = function ($fx) use ($benef, $parceriaId) {
        return ['contrato_id' => $fx['contrato_id'], 'beneficiario_credito_id' => $benef, 'data_levantamento' => '2026-02-10', 'valor_levantado' => 12019.57,
                'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $parceriaId, 'data_distribuicao' => null,
                'conta_caixa_id' => $fx['conta_caixa_id'], 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S',
                'vencimento_cliente' => '2026-02-15', 'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8];
    };
    $valores = function ($lista) { $r = []; foreach ($lista as $x) { $r[(int) $x->pessoa_id] = round((float) $x->valor, 2); } ksort($r); return $r; };

    $lf = $S::gerar($f['etapa3_id'], $d($f));
    TTransaction::open('escritorio');
    $parcela = Lancamento::where('conta_id', '=', $lf->conta_receber_id)->first();
    $antesFicha   = $valores((new LevantamentoFinanceiro($lf->id))->repasses());
    $antesParcela = $valores(LancamentoProfissional::where('lancamento_id', '=', $parcela->id)->load());
    igual('gerado com 50/50', [$curciol => 1160.51, $kleber => 1160.51], $antesFicha);

    // altera a parceria: 70/30
    $participantes([[$curciol, 70, 'S'], [$kleber, 30, 'N']]);
    TTransaction::close();

    TTransaction::open('escritorio');
    igual('ficha ja gerada continua 50/50', $antesFicha, $valores((new LevantamentoFinanceiro($lf->id))->repasses()));
    igual('repasses da parcela continuam 50/50', $antesParcela, $valores(LancamentoProfissional::where('lancamento_id', '=', $parcela->id)->load()));
    TTransaction::close();

    // financeiro gerado depois usa a parceria nova
    $f2 = Fixtures::criar();
    $sim = $S::simular($f2['etapa3_id'], $d($f2));
    $novos = []; foreach ($sim['resultado']['repasses'] as $r) { $novos[(int) $r['pessoa_id']] = $r['valor']; } ksort($novos);
    igual('financeiro novo sai 70/30', [$curciol => 1624.71, $kleber => 696.31], $novos);
} finally {
    Fixtures::limpar($f);
    if ($f2) { Fixtures::limpar($f2); }
    if ($parceriaId) {
        TTransaction::open('escritorio');
        ParceriaLevantamentoParticipante::where('parceria_levantamento_id', '=', $parceriaId)->delete();
        ParceriaLevantamento::where('id', '=', $parceriaId)->delete();
        TTransaction::close();
    }
}
echo 'sobras: ' . json_encode(Fixtures::sobras()) . "\n";
fim();
