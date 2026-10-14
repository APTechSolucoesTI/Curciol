<?php
// tests/levantamento/PrestacaoContasTest.php
// Prestacao de contas ao cliente (modelo da aba "Prestacao de Contas" da planilha), com os valores do ID 14
// da planilha Valores Pagos RPV-Precatorio (o exemplo que a propria aba mostra).
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
$S = 'LevantamentoFinanceiroService';

$dados = function ($f, array $extra = []) {
    TTransaction::open('escritorio');
    $b = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
    $p = ParceriaLevantamento::where('nome', '=', 'Parceria Ivan')->first()->id;
    TTransaction::close();
    return $extra + ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $b, 'data_levantamento' => '2026-08-03', 'valor_levantado' => 16842.59,
        'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $p, 'data_distribuicao' => null, 'conta_caixa_id' => $f['conta_caixa_id'],
        'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S', 'vencimento_cliente' => '2026-08-10',
        'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8,
        'desconto_previdenciario' => 1101.25, 'desconto_assistencial' => 207.87, 'desconto_irrf' => 0];
};
$arquivos = [];

$f = Fixtures::criar(['bruto' => 17908.21, 'mle' => 16599.09, 'deposito' => '2026-05-29']);
try {
    TTransaction::open('escritorio');
    TTransaction::get()->exec('UPDATE requisicao_pagamento_cliente SET valor = 15140.76 WHERE id = ' . (int) $f['rpc_id']);
    TTransaction::close();

    lanca('descontos que nao somam bruto - MLE', function () use ($S, $f, $dados) { $S::gerar($f['etapa3_id'], $dados($f, ['desconto_irrf' => 10])); }, 'não batem');
    lanca('desconto faltando', function () use ($S, $f, $dados) { $d = $dados($f); unset($d['desconto_irrf']); $S::gerar($f['etapa3_id'], $d); }, 'IRRF');

    $S::gerar($f['etapa3_id'], $dados($f));
    TTransaction::open('escritorio');
    $ficha = LevantamentoFinanceiro::where('requisicao_pagamento_etapa3_id', '=', $f['etapa3_id'])->first();
    igual('ficha guarda os descontos', [1101.25, 207.87, 0.0], [(float) $ficha->desconto_previdenciario, (float) $ficha->desconto_assistencial, (float) $ficha->desconto_irrf]);
    ok('ha prestacao de contas (tem valor ao cliente)', LevantamentoPrestacaoContasService::disponivel($ficha));
    $d = LevantamentoPrestacaoContasService::dados((int) $f['etapa3_id']);
    TTransaction::close();

    $linhas = [];
    foreach ($d['linhas'] as [$rotulo, $valor]) { if ($rotulo !== 'separador') { $linhas[$rotulo] = round((float) $valor, 2); } }
    // valores da aba "Prestacao de Contas" para o ID 14 (arredondados a centavos)
    igual('Calculos Homologados (bruto) = total requisitado', 15140.76, $linhas['Cálculos Homologados (bruto)'] ?? null);
    igual('Valor Atualizado para Deposito (bruto)', 17908.21, $linhas['Valor Atualizado para Depósito (bruto)'] ?? null);
    igual('Valor Depositado', 17908.21, $linhas['Valor Depositado (atualizado)'] ?? null);
    igual('Desconto Previdenciario', 1101.25, $linhas['Desconto Previdenciário (-)'] ?? null);
    igual('Desconto Assistencial', 207.87, $linhas['Desconto Assistencial (-)'] ?? null);
    igual('Desconto IRRF', 0.0, $linhas['Desconto IRRF (-)'] ?? null);
    igual('Desconto de Honorarios Contratuais', 3581.64, $linhas['Desconto de Honorários Contratuais (-)'] ?? null);
    igual('Liquido Cliente na data do Deposito', 13017.45, $linhas['Líquido Cliente na data do Depósito'] ?? null);
    igual('Liquido Honorarios Levantado', 3634.18, $linhas['Líquido Honorários Levantado (atualizado)'] ?? null);
    igual('Liquido Cliente Levantado', 13208.41, $linhas['Líquido Cliente Levantado (atualizado)'] ?? null);
    ok('cliente e CPF', $d['cliente'] !== '' && strpos($d['documento'], 'CPF') === 0, $d['documento']);
    ok('processo do pagamento com o principal entre parenteses', strpos($d['processo'], '(') !== false, $d['processo']);
    ok('rodape com endereco do escritorio', count($d['escritorio']['rodape']) >= 1, json_encode($d['escritorio']));

    $pdf = LevantamentoPrestacaoContasService::gerarPdf((int) $f['etapa3_id']);
    $arquivos[] = $pdf['arquivo'];
    ok('PDF gerado', is_file($pdf['arquivo']) && filesize($pdf['arquivo']) > 5000 && file_get_contents($pdf['arquivo'], false, null, 0, 4) === '%PDF', $pdf['arquivo']);
    ok('nome do PDF sem acento', (bool) preg_match('/^Prestacao_de_contas_[A-Za-z0-9_]+\.pdf$/', $pdf['nome']), $pdf['nome']);

    $doc = LevantamentoPrestacaoContasService::gerarWord((int) $f['etapa3_id']);
    $arquivos[] = $doc['arquivo'];
    $zip = new ZipArchive();
    $xml = ($zip->open($doc['arquivo']) === true) ? $zip->getFromName('word/document.xml') : '';
    ok('Word gerado', $xml !== '' && $xml !== false);
    ok('Word com o valor ao cliente', strpos((string) $xml, 'R$ 13.208,41') !== false);
    ok('Word com o nome do cliente', strpos((string) $xml, htmlspecialchars($d['cliente'], ENT_XML1)) !== false);
    if ($zip->status === ZipArchive::ER_OK) { $zip->close(); }

    $S::desfazer($f['etapa3_id']);
} finally {
    foreach ($arquivos as $a) { @unlink($a); }
    Fixtures::limpar($f);
}

// Sucumbencia: nao ha valor ao cliente, nao ha prestacao de contas
$f2 = Fixtures::criar(['bruto' => 2013.04, 'mle' => 2013.04, 'deposito' => '2026-05-29']);
try {
    TTransaction::open('escritorio');
    $suc = BeneficiarioCredito::where('nome', '=', 'Sucumbência')->first()->id;
    TTransaction::close();
    $S::gerar($f2['etapa3_id'], $dados($f2, ['beneficiario_credito_id' => $suc, 'pct_honorarios' => null, 'valor_levantado' => 2066.08, 'vencimento_cliente' => null,
        'desconto_previdenciario' => 0, 'desconto_assistencial' => 0, 'desconto_irrf' => 0]));
    TTransaction::open('escritorio');
    ok('sucumbencia: sem prestacao de contas', !LevantamentoPrestacaoContasService::disponivel(LevantamentoFinanceiro::where('requisicao_pagamento_etapa3_id', '=', $f2['etapa3_id'])->first()));
    TTransaction::close();
    lanca('sucumbencia: gerar PDF recusa', function () use ($f2) { LevantamentoPrestacaoContasService::gerarPdf((int) $f2['etapa3_id']); }, 'não há prestação');
    $S::desfazer($f2['etapa3_id']);
} finally {
    Fixtures::limpar($f2);
}
echo 'sobras: ' . json_encode(Fixtures::sobras()) . "\n";
fim();
