<?php
// tests/levantamento/ContratoProcessoVinculoTest.php
// Aba Processos do contrato: lista os processos ja vinculados, vincula pela busca (sem duplicar) e desvincula com confirmacao.
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }

function saida(callable $fn): string
{
    ob_start();
    try { $fn(); } catch (Throwable $e) { echo 'EXCECAO: ' . $e->getMessage(); }
    $s = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    return $s;
}
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }

TSession::setValue('userid', 1);
$f = Fixtures::criar();
$ct = (int) $f['contrato_id'];
$raiz = (int) $f['processo_raiz_id'];
$incidente = (int) $f['processo_id'];
$vinculados = function () use ($ct) { return array_map(function ($r) { return (int) $r['processo_id']; }, q("SELECT processo_id FROM contrato_processo WHERE contrato_id = {$ct} ORDER BY processo_id")); };
try {
    // tela: o processo ja vinculado aparece, com a busca para vincular outro
    $s = saida(function () use ($ct) { $p = new ContratoFormView(['key' => $ct, 'id' => $ct]); $p->show(); });
    ok('aba Processos aparece', strpos($s, 'Processos') !== false && strpos($s, 'Vincular processo:') !== false, substr($s, 0, 500));
    ok('processo ja vinculado listado com link', strpos($s, 'TESTE LEVANTAMENTO RAIZ') !== false || strpos($s, 'key=' . $raiz . '&amp;register_state') !== false || strpos($s, 'key=' . $raiz . '&register_state') !== false, '');
    ok('acao Desvincular na linha', strpos($s, 'onDesvincularProcesso') !== false);

    $s = saida(function () use ($ct) { ContratoFormView::onVincularProcesso(['contrato_id' => $ct, 'vincular_processo_id' => '']); });
    ok('vincular sem escolher: mensagem', strpos($s, 'Escolha o processo a vincular') !== false, $s);

    $s = saida(function () use ($ct, $incidente) { ContratoFormView::onVincularProcesso(['contrato_id' => $ct, 'vincular_processo_id' => $incidente]); });
    ok('vincular o incidente: sem erro', strpos($s, '__adianti_error') === false && strpos($s, 'EXCECAO') === false, $s);
    igual('contrato com os dois processos', [$raiz, $incidente], $vinculados());
    igual('vinculo guarda quem criou', 1, (int) q("SELECT criacao_user_id u FROM contrato_processo WHERE contrato_id = {$ct} AND processo_id = {$incidente}")[0]['u']);

    $s = saida(function () use ($ct, $incidente) { ContratoFormView::onVincularProcesso(['contrato_id' => $ct, 'vincular_processo_id' => $incidente]); });
    ok('vincular de novo: ja vinculado', strpos($s, 'já está vinculado') !== false, $s);
    igual('sem vinculo duplicado', [$raiz, $incidente], $vinculados());

    $s = saida(function () use ($ct, $incidente) { ContratoFormView::onDesvincularProcesso(['contrato_id' => $ct, 'processo_id' => $incidente]); });
    ok('desvincular pede confirmacao', strpos($s, 'Desvincular o processo') !== false && strpos($s, 'INCIDENTE') !== false, $s);
    igual('sem confirmar nada muda', [$raiz, $incidente], $vinculados());

    $s = saida(function () use ($ct, $incidente) { ContratoFormView::onDesvincularProcesso(['contrato_id' => $ct, 'processo_id' => $incidente, 'confirmado' => 1]); });
    ok('desvincular confirmado: sem erro', strpos($s, '__adianti_error') === false && strpos($s, 'EXCECAO') === false, $s);
    igual('so o raiz continua vinculado', [$raiz], $vinculados());
    igual('o processo continua existindo', 1, (int) q("SELECT count(*) n FROM processo WHERE id = {$incidente}")[0]['n']);
} finally {
    Fixtures::limpar($f);
}
fim();
