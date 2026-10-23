<?php
// tests/levantamento/RegrasPublicacaoTest.php
// Regras da publicacao em producao (23/10/2026):
//  - login do portal = CPF/CNPJ valido para o tipo de pessoa; senha nao muda (so e gerada quando ha login e nao ha senha);
//  - parcela a receber anterior a publicacao nao gera repasse ao ser quitada, e contrato com parcela anterior em aberto
//    nao gera financeiro (nem pelo contrato nem pelo pagamento da requisicao); parcela nova gera repasse normalmente.
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/Fixtures.php';
if (!class_exists('TApplication')) { class TApplication extends AdiantiCoreApplication {} }
function q(string $sql) { TTransaction::open('escritorio'); $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_ASSOC); TTransaction::close(); return $r; }
function x(string $sql): void { TTransaction::open('escritorio'); TTransaction::get()->exec($sql); TTransaction::close(); }

$L = 'PessoaLoginService';
$F = TipoPessoa::FISICA; $J = TipoPessoa::JURIDICA;
$cpf = '52998224725'; $cnpj = '11222333000181';

// ---------- login conforme o tipo de pessoa ----------
igual('fisica com CPF valido: login = CPF', $cpf, $L::usuarioPadrao('529.982.247-25', $F));
igual('juridica com CNPJ valido: login = CNPJ', $cnpj, $L::usuarioPadrao('11.222.333/0001-81', $J));
igual('fisica com CNPJ: sem login', null, $L::usuarioPadrao($cnpj, $F));
igual('juridica com CPF: sem login', null, $L::usuarioPadrao($cpf, $J));
igual('sem tipo: vale CPF ou CNPJ', [$cpf, $cnpj], [$L::usuarioPadrao($cpf), $L::usuarioPadrao($cnpj)]);
igual('documento invalido ou vazio: sem login', [null, null], [$L::usuarioPadrao('52998224724', $F), $L::usuarioPadrao('', $F)]);

// ---------- senha: a do banco fica; gerada so com login e sem senha ----------
$p = (object) ['cpf_cnpj' => $cpf, 'tipo_pessoa_id' => $F, 'senha' => 'DIGITADA-NA-TELA'];
$L::aplicarLogin($p, 'senhaAntiga1');
igual('com login e senha gravada: fica a senha gravada (o que veio da tela nao vale)', [$cpf, 'senhaAntiga1'], [$p->usuario, $p->senha]);
$p = (object) ['cpf_cnpj' => $cpf, 'tipo_pessoa_id' => $F, 'senha' => ''];
$L::aplicarLogin($p, null);
ok('com login e sem senha: gera senha de 12 caracteres', $p->usuario === $cpf && strlen((string) $p->senha) === 12, (string) $p->senha);
$p = (object) ['cpf_cnpj' => '', 'tipo_pessoa_id' => $F, 'senha' => ''];
$L::aplicarLogin($p, null);
igual('sem login e sem senha: continua sem senha', [null, null], [$p->usuario, $p->senha]);
$p = (object) ['cpf_cnpj' => $cnpj, 'tipo_pessoa_id' => $F, 'senha' => ''];
$L::aplicarLogin($p, 'senhaAntiga2');
igual('sem login (tipo errado) e com senha gravada: a senha fica como esta', [null, 'senhaAntiga2'], [$p->usuario, $p->senha]);

// ---------- corte da publicacao: parcelas anteriores ----------
$col = q("SELECT column_default FROM information_schema.columns WHERE table_name = 'lancamento' AND column_name = 'anterior_publicacao'");
ok('coluna anterior_publicacao existe com padrao false (parcela nova nao e anterior)', $col && stripos((string) $col[0]['column_default'], 'false') !== false);

TSession::setValue('userid', 1);
TTransaction::open('escritorio');
$pid = function (string $nome) { $st = TTransaction::get()->prepare('SELECT id FROM pessoa WHERE normalize(nome, NFC) = normalize(?, NFC) ORDER BY id LIMIT 1'); $st->execute([$nome]); return (int) $st->fetchColumn(); };
$jac = $pid('JOSÉ ALMIR CURCIOL'); $juan = $pid('JUAN WILLIAN DE OLIVEIRA'); $curciol = $pid('CURCIOL SOCIEDADE DE ADVOGADOS');
$benef = BeneficiarioCredito::where('nome', '=', 'Cliente + Honorários Contratuais')->first()->id;
$parcJuan = ParceriaLevantamento::where('nome', '=', 'Parceria Juan')->first()->id;
TTransaction::close();

$f = Fixtures::criar();
$cc = (int) $f['conta_caixa_id'];
/** conta a receber do contrato da fixture, 1 parcela de 1.000 com Curciol 30%, Jac 50%, Juan 20% */
$novaConta = function (string $descricao) use ($f, $curciol, $jac, $juan) {
    TTransaction::open('escritorio');
    $ct = new Conta();
    $ct->tipo_conta_id = TipoConta::RECEBER; $ct->pessoa_id = $f['cliente_id']; $ct->escritorio_id = $f['escritorio_id']; $ct->contrato_id = $f['contrato_id'];
    $ct->categoria_conta_id = 24; $ct->tipo_documento_financeiro_id = 2; $ct->data_emissao = date('Y-m-d'); $ct->total_conta = 1000; $ct->total_parcelas = 1;
    $ct->quitada = 'N'; $ct->descricao = $descricao; $ct->proximo_vencimento_lancamento = date('Y-m-d');
    $ct->requisicao_pagamento_id = $f['requisicao_id']; // so para a limpeza das fixtures achar a conta
    $ct->store();
    $l = new Lancamento(); $l->conta_id = $ct->id; $l->parcela = 1; $l->valor = 1000; $l->valor_total = 1000; $l->saldo = 1000; $l->dt_vencimento = date('Y-m-d'); $l->tipo_pagamento_id = TipoPagamento::TRANSFERENCIA; $l->store();
    foreach ([[$curciol, 30, 300], [$jac, 50, 500], [$juan, 20, 200]] as [$p, $pct, $v]) {
        $lp = new LancamentoProfissional(); $lp->lancamento_id = $l->id; $lp->pessoa_id = $p; $lp->percentual = $pct; $lp->valor = $v; $lp->store();
    }
    TTransaction::close();
    return [(int) $ct->id, (int) $l->id];
};
$lanca = function (callable $fn) { try { $fn(); return ''; } catch (Throwable $e) { while (TTransaction::get()) { TTransaction::rollback(); } return $e->getMessage(); } };
$dadosGerar = ['contrato_id' => $f['contrato_id'], 'beneficiario_credito_id' => $benef, 'data_levantamento' => date('Y-m-d'), 'valor_levantado' => 12019.57,
    'pct_honorarios' => 20, 'pct_nf' => 16, 'parceria_levantamento_id' => $parcJuan, 'data_distribuicao' => null, 'conta_caixa_id' => $cc,
    'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'S', 'vencimento_cliente' => date('Y-m-d'),
    'categoria_conta_id' => 24, 'tipo_documento_receber_id' => 6, 'tipo_documento_pagar_id' => 8];
try {
    // parcela anterior (marcada como na publicacao) com colaboradores gravados
    [$contaAntiga, $parcelaAntiga] = $novaConta('TESTE LEVANTAMENTO parcela anterior a publicacao');
    x("UPDATE lancamento SET anterior_publicacao = true WHERE id = {$parcelaAntiga}");

    $msg = $lanca(function () use ($f) { TTransaction::open('escritorio'); ContratoFinanceiroService::exigirSemParcelaAnteriorAberta((int) $f['contrato_id']); TTransaction::close(); });
    ok('contrato com parcela anterior em aberto: Gerar financeiro bloqueado', strpos($msg, 'antes da atualização do sistema') !== false && strpos($msg, "nº {$contaAntiga}") !== false, $msg);
    $msg = $lanca(function () use ($f, $dadosGerar) { LevantamentoFinanceiroService::gerar($f['etapa3_id'], $dadosGerar); });
    ok('pagamento da requisicao do mesmo contrato: Gerar financeiro bloqueado', strpos($msg, 'antes da atualização do sistema') !== false, $msg);
    igual('nada foi gerado pelo pagamento bloqueado', 0, (int) q('SELECT count(*) n FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ' . (int) $f['etapa3_id'])[0]['n']);

    // quitar a parcela anterior nao gera repasse
    LoteFinanceiroService::quitar([$contaAntiga], (string) TipoConta::RECEBER, ['data_pagamento' => date('Y-m-d'), 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'N']);
    igual('parcela anterior quitada', true, q("SELECT dt_pagamento FROM lancamento WHERE id = {$parcelaAntiga}")[0]['dt_pagamento'] !== null);
    igual('parcela anterior quitada: nenhum repasse', 0, (int) q("SELECT count(*) n FROM repasse_colaborador WHERE conta_receber_id = {$contaAntiga}")[0]['n']);

    // sem parcela anterior em aberto: o contrato volta a poder gerar financeiro
    $msg = $lanca(function () use ($f) { TTransaction::open('escritorio'); ContratoFinanceiroService::exigirSemParcelaAnteriorAberta((int) $f['contrato_id']); TTransaction::close(); });
    igual('parcela anterior quitada: contrato liberado', '', $msg);
    $lf = LevantamentoFinanceiroService::gerar($f['etapa3_id'], $dadosGerar);
    igual('pagamento da requisicao gera financeiro com os repasses', 4, (int) q('SELECT count(*) n FROM repasse_colaborador WHERE conta_receber_id = ' . (int) $lf->conta_receber_id)[0]['n']);

    // ---------- gerar e desfazer financeiro so para o grupo Financeiro ----------
    $pag = (object) ['id' => 999999999, 'data_deposito' => date('Y-m-d'), 'valor_bruto_depositado' => 100, 'valor_mle' => 100];
    TSession::setValue('programs', ['RequisicaoPagamentoListagemHeaderList' => true, 'ContratoFormView' => true, 'LevantamentoFinanceiroView' => true]);
    ok('sem permissao: nao pode gerar (levantamento e contrato)', !LevantamentoFinanceiroForm::podeGerar() && !ContratoFormView::podeGerarFinanceiro());
    igual('sem permissao: botao Gerar financeiro some', '', LevantamentoFinanceiroForm::botaoFinanceiro($pag, [], 'janela', ''));
    ok('sem permissao: Ver financeiro continua (consulta)', strpos(LevantamentoFinanceiroForm::botaoFinanceiro($pag, [999999999 => true], 'janela', ''), 'Ver financeiro') !== false);
    ok('sem permissao: acoes do contrato escondidas', !ContratoFormView::canGerar((object) ['id' => 1, 'status_contrato_pagamento_id' => 1]) && !ContratoFormView::canDesfazer((object) ['id' => 1, 'status_contrato_pagamento_id' => 2]));
    ob_start(); LevantamentoFinanceiroView::onDesfazer(['etapa3_id' => $f['etapa3_id']]); $saida = ob_get_clean();
    while (TTransaction::get()) { TTransaction::rollback(); }
    ok('sem permissao: desfazer pela URL recusado e nada desfeito', strpos($saida, 'Somente o Financeiro') !== false
        && (int) q('SELECT count(*) n FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ' . (int) $f['etapa3_id'])[0]['n'] === 1, substr($saida, 0, 200));
    TSession::setValue('programs', ['LevantamentoFinanceiroForm' => true, 'ModalContratoGerarFinanceiro' => true]);
    ok('com permissao (Financeiro): botao Gerar financeiro aparece', strpos(LevantamentoFinanceiroForm::botaoFinanceiro($pag, [], 'janela', ''), 'Gerar financeiro') !== false);

    // parcela nova (lancada depois da publicacao) gera repasse ao quitar
    [$contaNova, $parcelaNova] = $novaConta('TESTE LEVANTAMENTO parcela nova');
    igual('parcela nova nao e anterior', 'N', q("SELECT CASE WHEN anterior_publicacao THEN 'S' ELSE 'N' END AS a FROM lancamento WHERE id = {$parcelaNova}")[0]['a']);
    LoteFinanceiroService::quitar([$contaNova], (string) TipoConta::RECEBER, ['data_pagamento' => date('Y-m-d'), 'conta_caixa_id' => $cc, 'tipo_pagamento_id' => TipoPagamento::TRANSFERENCIA, 'compensado' => 'N']);
    igual('parcela nova quitada: repasses de Jac e Juan (a Curciol nao vira conta a pagar)', [[$jac, 500.0], [$juan, 200.0]],
        array_map(function ($r) { return [(int) $r['pessoa_id'], round((float) $r['valor'], 2)]; }, q("SELECT pessoa_id, valor FROM repasse_colaborador WHERE conta_receber_id = {$contaNova} ORDER BY valor DESC")));
} finally {
    Fixtures::limpar($f);
}
fim();
