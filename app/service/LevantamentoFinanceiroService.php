<?php
// app/service/LevantamentoFinanceiroService.php
/**
 * Gera e desfaz o financeiro de um pagamento de requisicao (levantamento judicial):
 *  - conta a receber do devedor, ja quitada, com extrato na conta-caixa e repasses aos parceiros;
 *  - conta a pagar ao cliente, em aberto (so quando ha valor ao cliente);
 *  - ficha (levantamento_financeiro) com as entradas e o resultado do calculo.
 *
 * Dinheiro e saldo bancario: tudo numa unica transacao, com a Etapa 3 travada (FOR UPDATE).
 * O saldo da conta-caixa e alterado por UPDATE atomico no banco (nunca leitura + store).
 */
class LevantamentoFinanceiroService
{
    /** Calcula sem gravar nada. @return array contexto, entrada, resultado, percentuais */
    public static function simular(int $etapa3Id, array $d): array
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $r = self::montarSimulacao($etapa3Id, $d);
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /** Id da ficha do pagamento, ou null se o financeiro ainda nao foi gerado. */
    public static function existePara(int $etapa3Id): ?int
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $id = self::fichaId(TTransaction::get(), $etapa3Id);
            if ($propria) { TTransaction::close(); }
            return $id;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /** A conta pertence a uma ficha de levantamento? ['ficha_id'=>int,'papel'=>'RECEBER'|'PAGAR'] ou null. */
    public static function contaDoLevantamento(int $contaId): ?array
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $st = TTransaction::get()->prepare('SELECT id, conta_receber_id FROM levantamento_financeiro WHERE conta_receber_id = ? OR conta_pagar_id = ? ORDER BY id LIMIT 1');
            $st->execute([$contaId, $contaId]);
            $l = $st->fetch(PDO::FETCH_OBJ);
            if ($propria) { TTransaction::close(); }
            if (!$l) { return null; }
            return ['ficha_id' => (int) $l->id, 'papel' => ((int) $l->conta_receber_id === $contaId) ? 'RECEBER' : 'PAGAR'];
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    public static function gerar(int $etapa3Id, array $d): LevantamentoFinanceiro
    {
        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            self::travarPagamento($c, $etapa3Id);
            if (self::fichaId($c, $etapa3Id) !== null) { throw new Exception('Já existe financeiro para este pagamento.'); }

            $sim = self::montarSimulacao($etapa3Id, $d);
            $ctx = $sim['contexto'];
            $e   = $sim['entrada'];
            $res = $sim['resultado'];
            $per = $sim['percentuais'];

            $x = $e['data_levantamento'];
            $y = round((float) $e['valor_levantado'], 2);
            $z = round((float) $res['z'], 2);

            // conta-caixa
            $ccId = (int) ($d['conta_caixa_id'] ?? 0);
            $st = $c->prepare('SELECT ativo FROM conta_caixa WHERE id = ? FOR UPDATE');
            $st->execute([$ccId]);
            $cc = $st->fetch(PDO::FETCH_OBJ);
            if ($ccId <= 0 || !$cc || $cc->ativo === 'N') { throw new Exception('Conta-caixa inválida.'); }

            // demais dados do formulario
            if (empty($d['tipo_pagamento_id'])) { throw new Exception('Informe o tipo de pagamento.'); }
            if (empty($d['categoria_conta_id'])) { throw new Exception('Informe a categoria da conta.'); }
            if (empty($d['tipo_documento_receber_id'])) { throw new Exception('Informe o tipo de documento da conta a receber.'); }
            if (!in_array($d['compensado'] ?? null, ['S', 'N'], true)) { throw new Exception('Informe se o valor já está compensado.'); }
            $compensado = ($d['compensado'] === 'S');
            $vencimento = substr((string) ($d['vencimento_cliente'] ?? ''), 0, 10);
            if ($z > 0) {
                if ($vencimento === '') { throw new Exception('Informe o vencimento do pagamento ao cliente.'); }
                if (empty($d['tipo_documento_pagar_id'])) { throw new Exception('Informe o tipo de documento da conta a pagar ao cliente.'); }
            }

            $st = $c->prepare('SELECT escritorio_id FROM contrato WHERE id = ?');
            $st->execute([(int) $e['contrato_id']]);
            $escritorioId = $st->fetchColumn();
            if (!$escritorioId) { throw new Exception('O contrato escolhido não tem escritório.'); }

            $userId    = TSession::getValue('userid');
            $descricao = "Levantamento judicial - {$ctx->cliente_nome} - {$ctx->processo_numero} - {$ctx->numero_pagamento}º pagamento";

            // conta a receber (ja quitada)
            $receber = new Conta();
            $receber->tipo_conta_id = TipoConta::RECEBER;
            $receber->pessoa_id = $ctx->devedor_id;
            $receber->categoria_conta_id = (int) $d['categoria_conta_id'];
            $receber->tipo_documento_financeiro_id = (int) $d['tipo_documento_receber_id'];
            $receber->escritorio_id = (int) $escritorioId;
            $receber->contrato_id = (int) $e['contrato_id'];
            $receber->processo_id = $ctx->processo_id;
            $receber->requisicao_pagamento_id = $ctx->requisicao_id;
            $receber->data_emissao = $x;
            $receber->total_conta = $y;
            $receber->total_parcelas = 1;
            $receber->quitada = 'S';
            $receber->descricao = $descricao;
            $receber->criacao_user_id = $userId;
            $receber->proximo_vencimento_lancamento = $x;
            $receber->store();

            $parcela = new Lancamento();
            $parcela->conta_id = $receber->id;
            $parcela->parcela = 1;
            $parcela->valor = $y;
            $parcela->valor_total = $y;
            $parcela->saldo = 0;
            $parcela->dt_vencimento = $x;
            $parcela->dt_pagamento = $x;
            $parcela->tipo_pagamento_id = (int) $d['tipo_pagamento_id'];
            $parcela->store();

            // extrato (mesmo padrao de ModalQuitarParcela::onPagar) e saldo da conta-caixa
            $extrato = new Extrato();
            $extrato->escritorio_id = (int) $escritorioId;
            $extrato->conta_caixa_id = $ccId;
            $extrato->lancamento_id = $parcela->id;
            $extrato->categoria_conta_id = (int) $d['categoria_conta_id'];
            $extrato->tipo_extrato_id = TipoExtrato::RECEBER;
            $extrato->entrada_valor = $y;
            $extrato->data_lancamento = date('Y-m-d');
            $extrato->data_previsao_compensacao = $x;
            if ($compensado) {
                $extrato->compensado = 'S';
                $extrato->data_compensacao = $x;
                $extrato->ano = date('Y', strtotime($x));
                $extrato->mes = date('m', strtotime($x));
                $extrato->ano_mes = date('Ym', strtotime($x));
            } else {
                $extrato->compensado = 'N';
            }
            $extrato->historico = $descricao;
            $extrato->criacao_user_id = $userId;
            $extrato->store();
            self::ajustarSaldo($c, $ccId, $compensado ? 'saldo_instantaneo' : 'saldo_nao_compensado', $y, $userId);

            $parcela->extrato_id = $extrato->id;
            $parcela->store();

            // repasses aos parceiros (conta e parcela)
            foreach ($res['repasses'] as $r) {
                $pct = round((float) $r['percentual'] * 100, 8);
                $cp = new ContaProfissional();
                $cp->conta_id = $receber->id;
                $cp->pessoa_id = $r['pessoa_id'];
                $cp->percentual = $pct;
                $cp->valor = $r['valor'];
                $cp->store();

                $lp = new LancamentoProfissional();
                $lp->lancamento_id = $parcela->id;
                $lp->pessoa_id = $r['pessoa_id'];
                $lp->percentual = $pct;
                $lp->valor = $r['valor'];
                $lp->store();
            }

            // conta a pagar ao cliente (em aberto)
            $pagarId = null;
            if ($z > 0) {
                $pagar = new Conta();
                $pagar->tipo_conta_id = TipoConta::PAGAR;
                $pagar->pessoa_id = $ctx->cliente_id;
                $pagar->categoria_conta_id = (int) $d['categoria_conta_id'];
                $pagar->tipo_documento_financeiro_id = (int) $d['tipo_documento_pagar_id'];
                $pagar->escritorio_id = (int) $escritorioId;
                $pagar->contrato_id = (int) $e['contrato_id'];
                $pagar->processo_id = $ctx->processo_id;
                $pagar->requisicao_pagamento_id = $ctx->requisicao_id;
                $pagar->data_emissao = $x;
                $pagar->total_conta = $z;
                $pagar->total_parcelas = 1;
                $pagar->quitada = 'N';
                $pagar->descricao = $descricao;
                $pagar->conta_origem_id = $receber->id;
                $pagar->criacao_user_id = $userId;
                $pagar->proximo_vencimento_lancamento = $vencimento;
                $pagar->store();
                $pagarId = $pagar->id;

                $parcelaPagar = new Lancamento();
                $parcelaPagar->conta_id = $pagar->id;
                $parcelaPagar->parcela = 1;
                $parcelaPagar->valor = $z;
                $parcelaPagar->valor_total = $z;
                $parcelaPagar->dt_vencimento = $vencimento;
                $parcelaPagar->tipo_pagamento_id = (int) $d['tipo_pagamento_id'];
                $parcelaPagar->store();
            }

            // ficha com o calculo
            $ficha = new LevantamentoFinanceiro();
            $ficha->requisicao_pagamento_etapa3_id = $etapa3Id;
            $ficha->requisicao_pagamento_id = $ctx->requisicao_id;
            $ficha->requisicao_pagamento_cliente_id = $ctx->rpc_id;
            $ficha->contrato_id = (int) $e['contrato_id'];
            $ficha->processo_id = $ctx->processo_id;
            $ficha->beneficiario_credito_id = (int) $e['beneficiario_credito_id'];
            $ficha->parceria_levantamento_id = $e['parceria_levantamento_id'];
            $ficha->regra = $e['regra'];
            $ficha->bruto_depositado = $ctx->bruto;
            $ficha->valor_mle = $ctx->mle;
            $ficha->descontos = round($ctx->bruto - $ctx->mle, 2);
            $ficha->pct_honorarios = ($e['regra'] === BeneficiarioCredito::REGRA_CLIENTE_E_HONORARIOS) ? (float) $e['pct_honorarios'] : null;
            $ficha->pct_nf = (float) $e['pct_nf'];
            $ficha->data_levantamento = $x;
            $ficha->valor_levantado = $y;
            $ficha->data_distribuicao = $e['data_distribuicao_usada'];
            $ficha->fracao_a = $per['a'] ?? null;
            $ficha->fracao_p = $per['p'] ?? null;
            $ficha->fator_d = $per['d'] ?? null;
            $ficha->u = $res['u'];
            $ficha->v = $res['v'];
            $ficha->w = $res['w'];
            $ficha->z = $z;
            $ficha->aa = $res['aa'];
            $ficha->ac = $res['ac'];
            $ficha->ad = $res['ad'];
            $ficha->conta_receber_id = $receber->id;
            $ficha->conta_pagar_id = $pagarId;
            $ficha->extrato_id = $extrato->id;
            $ficha->conta_caixa_id = $ccId;
            $ficha->data_criacao = date('Y-m-d H:i:s');
            $ficha->criacao_user_id = $userId;
            $ficha->store();

            foreach ($res['repasses'] as $r) {
                $fr = new LevantamentoFinanceiroRepasse();
                $fr->levantamento_financeiro_id = $ficha->id;
                $fr->pessoa_id = $r['pessoa_id'];
                $fr->percentual = round((float) $r['percentual'] * 100, 8);
                $fr->valor = $r['valor'];
                $fr->ajuste_centavos = $r['ajuste_centavos'];
                $fr->store();
            }

            TTransaction::close();
            return $ficha;
        } catch (Throwable $ex) {
            TTransaction::rollback();
            // corrida que escapou da trava: o indice unico da ficha barrou
            if (stripos($ex->getMessage(), 'uk_levantamento_financeiro_etapa3') !== false) {
                throw new Exception('Já existe financeiro para este pagamento.');
            }
            throw $ex;
        }
    }

    /** null = pode desfazer; texto = motivo do bloqueio. */
    public static function podeDesfazer(int $etapa3Id): ?string
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $r = self::motivoBloqueio(TTransaction::get(), $etapa3Id);
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /** Apaga o financeiro gerado e devolve o saldo da(s) conta(s)-caixa conforme o estado atual de cada extrato. */
    public static function desfazer(int $etapa3Id): void
    {
        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            self::travarPagamento($c, $etapa3Id);

            $motivo = self::motivoBloqueio($c, $etapa3Id);
            if ($motivo !== null) { throw new Exception($motivo); }

            $st = $c->prepare('SELECT id, conta_receber_id, conta_pagar_id FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ?');
            $st->execute([$etapa3Id]);
            $ficha = $st->fetch(PDO::FETCH_OBJ);
            $userId = TSession::getValue('userid');

            $contas = array_values(array_filter([(int) $ficha->conta_receber_id, (int) $ficha->conta_pagar_id]));
            $lista  = $contas ? implode(',', $contas) : '0';
            $parcelas = self::ids($c, "SELECT id FROM lancamento WHERE conta_id IN ({$lista})");
            $lancs    = $parcelas ? implode(',', $parcelas) : '0';

            // devolve o saldo conforme o estado ATUAL de cada extrato das parcelas a receber
            $st = $c->query("SELECT id, conta_caixa_id, compensado, COALESCE(entrada_valor, 0) - COALESCE(saida_valor, 0) AS liquido
                             FROM extrato WHERE lancamento_id IN ({$lancs}) ORDER BY id FOR UPDATE");
            $extratos = $st->fetchAll(PDO::FETCH_OBJ);
            foreach ($extratos as $x) {
                self::ajustarSaldo($c, (int) $x->conta_caixa_id, ($x->compensado === 'S') ? 'saldo_instantaneo' : 'saldo_nao_compensado', -1 * (float) $x->liquido, $userId);
            }

            $c->exec("UPDATE lancamento SET extrato_id = NULL WHERE conta_id IN ({$lista})");
            $c->exec("DELETE FROM extrato WHERE lancamento_id IN ({$lancs})");
            $c->exec("DELETE FROM lancamento_profissional_ajuste WHERE lancamento_profissional_id IN (SELECT id FROM lancamento_profissional WHERE lancamento_id IN ({$lancs}))");
            $c->exec("DELETE FROM lancamento_profissional WHERE lancamento_id IN ({$lancs})");
            $c->exec("DELETE FROM lancamento WHERE conta_id IN ({$lista})");
            $c->exec("DELETE FROM conta_profissional WHERE conta_id IN ({$lista})");

            // a conta a pagar aponta para a conta a receber (conta_origem_id): apagar primeiro
            $c->exec("UPDATE conta SET conta_origem_id = NULL WHERE id IN ({$lista})");
            $c->prepare('DELETE FROM levantamento_financeiro WHERE id = ?')->execute([(int) $ficha->id]);
            $c->exec("DELETE FROM conta WHERE id IN ({$lista})");

            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    // ---------------------------------------------------------------

    private static function montarSimulacao(int $etapa3Id, array $d): array
    {
        $ctx = LevantamentoContextoService::carregar($etapa3Id);

        $contratoId = (int) ($d['contrato_id'] ?? 0);
        $contratoOk = false;
        foreach ($ctx->contratos as $ct) {
            if ((int) $ct['id'] === $contratoId) { $contratoOk = true; break; }
        }
        if (!$contratoOk) { throw new Exception('Contrato inválido para este pagamento.'); }

        $benef = BeneficiarioCredito::find((int) ($d['beneficiario_credito_id'] ?? 0));
        if (!$benef || $benef->ativo !== 'S') { throw new Exception('Beneficiário do crédito inválido.'); }

        $x = substr((string) ($d['data_levantamento'] ?? ''), 0, 10);

        // parceria: so quando ha honorarios para repartir
        $parceriaId = null;
        $per = ['a' => null, 'p' => null, 'd' => null, 'participantes' => []];
        $distribuicaoUsada = null;
        if ($benef->regra !== BeneficiarioCredito::REGRA_SOMENTE_CLIENTE && !empty($d['parceria_levantamento_id'])) {
            $parceria = ParceriaLevantamento::find((int) $d['parceria_levantamento_id']);
            if (!$parceria || $parceria->ativo !== 'S') { throw new Exception('Parceria inválida.'); }
            $parceriaId = (int) $parceria->id;
            $participantes = array_map(function ($p) {
                return ['pessoa_id' => $p->pessoa_id, 'nivel' => $p->nivel, 'percentual' => $p->percentual,
                        'percentual_antes' => $p->percentual_antes, 'percentual_depois' => $p->percentual_depois,
                        'recebe_diferenca' => $p->recebe_diferenca];
            }, $parceria->participantes());
            $g = !empty($d['data_distribuicao']) ? substr((string) $d['data_distribuicao'], 0, 10) : $ctx->data_distribuicao;
            $per = ParceriaPercentualService::percentuais($parceria->tipo, $participantes, $parceria->marco1, $parceria->marco2, $g, $x);
            if ($parceria->tipo === ParceriaLevantamento::TIPO_TEMPORAL) { $distribuicaoUsada = $g; }
        }

        $entrada = [
            'bruto'             => $ctx->bruto,
            'mle'               => $ctx->mle,
            'valor_levantado'   => $d['valor_levantado'] ?? 0,
            'regra'             => $benef->regra,
            'data_deposito'     => $ctx->data_deposito,
            'data_levantamento' => $x,
            'pct_honorarios'    => $d['pct_honorarios'] ?? null,
            'pct_nf'            => $d['pct_nf'] ?? null,
            'participantes'     => $per['participantes'],
        ];
        $resultado = LevantamentoCalculoService::calcular($entrada);

        // dados que o gerar() usa depois (nao fazem parte do calculo)
        $entrada['contrato_id'] = $contratoId;
        $entrada['beneficiario_credito_id'] = (int) $benef->id;
        $entrada['parceria_levantamento_id'] = $parceriaId;
        $entrada['data_distribuicao_usada'] = $distribuicaoUsada;

        return ['contexto' => $ctx, 'entrada' => $entrada, 'resultado' => $resultado, 'percentuais' => $per];
    }

    private static function travarPagamento(PDO $c, int $etapa3Id): void
    {
        $st = $c->prepare('SELECT id FROM requisicao_pagamento_etapa3 WHERE id = ? FOR UPDATE');
        $st->execute([$etapa3Id]);
        if (!$st->fetchColumn()) { throw new Exception('Pagamento não encontrado.'); }
    }

    private static function fichaId(PDO $c, int $etapa3Id): ?int
    {
        $st = $c->prepare('SELECT id FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ?');
        $st->execute([$etapa3Id]);
        $id = $st->fetchColumn();
        return $id ? (int) $id : null;
    }

    private static function motivoBloqueio(PDO $c, int $etapa3Id): ?string
    {
        $st = $c->prepare('SELECT conta_pagar_id FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ?');
        $st->execute([$etapa3Id]);
        $ficha = $st->fetch(PDO::FETCH_OBJ);
        if (!$ficha) { return 'Este pagamento não tem financeiro gerado.'; }

        if ($ficha->conta_pagar_id) {
            $st = $c->prepare('SELECT COUNT(*) FROM lancamento WHERE conta_id = ? AND (dt_pagamento IS NOT NULL OR extrato_id IS NOT NULL)');
            $st->execute([(int) $ficha->conta_pagar_id]);
            if ((int) $st->fetchColumn() > 0) {
                return 'A conta a pagar ao cliente já foi paga. Estorne o pagamento ao cliente antes de desfazer.';
            }
        }
        return null;
    }

    /** UPDATE atomico do saldo (soma $delta, que pode ser negativo). $coluna e sempre um dos dois nomes fixos. */
    private static function ajustarSaldo(PDO $c, int $contaCaixaId, string $coluna, float $delta, $userId): void
    {
        if (!in_array($coluna, ['saldo_instantaneo', 'saldo_nao_compensado'], true)) { throw new Exception('Coluna de saldo inválida.'); }
        $st = $c->prepare("UPDATE conta_caixa SET {$coluna} = COALESCE({$coluna}, 0) + CAST(:valor AS numeric), modificacao_user_id = :usuario WHERE id = :id");
        $st->execute([':valor' => sprintf('%.2f', $delta), ':usuario' => $userId, ':id' => $contaCaixaId]);
        if ($st->rowCount() !== 1) { throw new Exception('Conta-caixa inválida.'); }
    }

    private static function ids(PDO $c, string $sql): array
    {
        return array_map('intval', $c->query($sql)->fetchAll(PDO::FETCH_COLUMN));
    }
}
