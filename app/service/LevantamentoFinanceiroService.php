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
    /**
     * Cliente da requisicao (requisicao_pagamento_cliente) a que a conta se refere, para abrir a requisicao na aba
     * certa: pela ficha do levantamento da conta, pela do recebimento que gerou o repasse, ou pela pessoa da conta.
     */
    public static function clienteDaRequisicao(int $contaId): ?int
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $st = TTransaction::get()->prepare('
                SELECT COALESCE(
                    (SELECT lf.requisicao_pagamento_cliente_id FROM levantamento_financeiro lf
                      WHERE :c IN (lf.conta_receber_id, lf.conta_pagar_id)
                         OR lf.id IN (SELECT x.levantamento_financeiro_id FROM levantamento_conta_cliente x WHERE x.conta_pagar_id = :c)
                      ORDER BY lf.id DESC LIMIT 1),
                    (SELECT lf.requisicao_pagamento_cliente_id FROM repasse_colaborador r
                       JOIN levantamento_financeiro lf ON lf.id = r.levantamento_financeiro_id OR lf.conta_receber_id = r.conta_receber_id
                      WHERE r.conta_pagar_id = :c ORDER BY lf.id DESC LIMIT 1),
                    (SELECT rpc.id FROM conta c JOIN requisicao_pagamento_cliente rpc
                         ON rpc.requisicao_pagamento_id = c.requisicao_pagamento_id AND rpc.pessoa_id = c.pessoa_id
                      WHERE c.id = :c ORDER BY rpc.id LIMIT 1))');
            $st->execute(['c' => $contaId]);
            $id = $st->fetchColumn();
            if ($propria) { TTransaction::close(); }
            return $id ? (int) $id : null;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    public static function contaDoLevantamento(int $contaId): ?array
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $st = TTransaction::get()->prepare('SELECT id, conta_receber_id FROM levantamento_financeiro WHERE conta_receber_id = ? OR conta_pagar_id = ?
                OR id IN (SELECT levantamento_financeiro_id FROM levantamento_conta_cliente WHERE conta_pagar_id = ?) ORDER BY id LIMIT 1');
            $st->execute([$contaId, $contaId, $contaId]);
            $l = $st->fetch(PDO::FETCH_OBJ);
            if ($propria) { TTransaction::close(); }
            if (!$l) { return null; }
            return ['ficha_id' => (int) $l->id, 'papel' => ((int) $l->conta_receber_id === $contaId) ? 'RECEBER' : 'PAGAR'];
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /**
     * Pagamento com financeiro gerado: deposito, bruto, MLE, descontos e processo nao podem mudar (so valem os campos presentes em $novos).
     * Chamar dentro da transacao do salvamento, antes do UPDATE.
     */
    public static function garantirPagamentoEditavel(int $etapa3Id, array $novos): void
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $c = TTransaction::get();
            if (self::fichaId($c, $etapa3Id) !== null) {
                $st = $c->prepare('SELECT data_deposito, valor_bruto_depositado, valor_mle, processo_filho_id, desconto_previdenciario, desconto_assistencial, desconto_irrf FROM requisicao_pagamento_etapa3 WHERE id = ?');
                $st->execute([$etapa3Id]);
                $atual = $st->fetch(PDO::FETCH_ASSOC);
                if ($atual) {
                    $dinheiro = function ($v) { return ($v === null || $v === '') ? null : round((float) $v, 2); };
                    $inteiro  = function ($v) { return ($v === null || $v === '') ? null : (int) $v; };
                    $mudou = false;
                    if (array_key_exists('data_deposito', $novos)) {
                        $mudou = $mudou || (substr((string) $atual['data_deposito'], 0, 10) !== substr((string) $novos['data_deposito'], 0, 10));
                    }
                    if (array_key_exists('valor_bruto_depositado', $novos)) {
                        $mudou = $mudou || ($dinheiro($atual['valor_bruto_depositado']) !== $dinheiro($novos['valor_bruto_depositado']));
                    }
                    if (array_key_exists('valor_mle', $novos)) {
                        $mudou = $mudou || ($dinheiro($atual['valor_mle']) !== $dinheiro($novos['valor_mle']));
                    }
                    foreach (['desconto_previdenciario', 'desconto_assistencial', 'desconto_irrf'] as $campo) {
                        if (array_key_exists($campo, $novos)) {
                            $mudou = $mudou || ($dinheiro($atual[$campo]) !== $dinheiro($novos[$campo]));
                        }
                    }
                    if (array_key_exists('processo_filho_id', $novos)) {
                        $mudou = $mudou || ($inteiro($atual['processo_filho_id']) !== $inteiro($novos['processo_filho_id']));
                    }
                    if ($mudou) {
                        throw new Exception('Este pagamento já tem o financeiro gerado: data do depósito, valor bruto, descontos, valor do MLE e processo não podem ser alterados. Desfaça o financeiro antes de alterar.');
                    }
                }
            }
            if ($propria) { TTransaction::close(); }
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    /** O cliente da requisicao (rpc) tem pagamento com financeiro gerado? Entao nao pode ser removido. */
    public static function garantirClienteRemovivel(int $rpcId): void
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $st = TTransaction::get()->prepare('SELECT COUNT(*) FROM levantamento_financeiro lf JOIN requisicao_pagamento_etapa3 e ON e.id = lf.requisicao_pagamento_etapa3_id WHERE e.requisicao_pagamento_cliente_id = ?');
            $st->execute([$rpcId]);
            $tem = ((int) $st->fetchColumn() > 0);
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
        if ($propria) { TTransaction::close(); }
        if ($tem) { throw new Exception('Este cliente tem pagamento com financeiro gerado e não pode ser removido da requisição. Desfaça o financeiro do pagamento antes.'); }
    }

    /**
     * Conta gerada pelo financeiro do levantamento: bloqueia a acao fora de "Desfazer financeiro".
     * $bloqueiaPagar = false deixa passar a conta a pagar ao cliente (quitar/desquitar sao o pagamento real).
     */
    /**
     * Desquitar a conta a receber do levantamento: permitido se a conta a pagar ao cliente nao tiver pagamento
     * (os repasses aos colaboradores sao conferidos e apagados pelo estorno do recebimento). Usa a transacao aberta.
     */
    public static function exigirDesquitavel(int $contaId): void
    {
        $c = TTransaction::get();
        $st = $c->prepare('SELECT id FROM levantamento_financeiro WHERE conta_receber_id = ? ORDER BY id DESC LIMIT 1');
        $st->execute([$contaId]);
        $fichaId = (int) $st->fetchColumn();
        foreach ($fichaId ? self::contasDoCliente($c, $fichaId) : [] as $pagar) {
            if (self::contaComPagamento($c, $pagar)) {
                throw new Exception('A conta a pagar ao cliente deste levantamento já foi paga (total ou parcialmente). Desquite o pagamento ao cliente antes de desquitar o recebimento.');
            }
        }
    }

    /** O recebimento desquitado sai do extrato: a ficha deixa de apontar para ele (a nova quitacao grava o novo). */
    public static function desvincularExtrato(int $extratoId): void
    {
        TTransaction::get()->prepare('UPDATE levantamento_financeiro SET extrato_id = NULL WHERE extrato_id = ?')->execute([$extratoId]);
    }

    /** Conta com pagamento: parcela paga (dt_pagamento/extrato_id) ou parcial (saldo menor que o valor, ou extrato da parcela). */
    private static function contaComPagamento(PDO $c, int $contaId): bool
    {
        $st = $c->prepare('SELECT COUNT(*) FROM lancamento l WHERE l.conta_id = ?
            AND (l.dt_pagamento IS NOT NULL OR l.extrato_id IS NOT NULL
                 OR (l.saldo IS NOT NULL AND l.saldo <> l.valor)
                 OR EXISTS (SELECT 1 FROM extrato x WHERE x.lancamento_id = l.id))');
        $st->execute([$contaId]);
        return (int) $st->fetchColumn() > 0;
    }

    /**
     * Conta a pagar ao cliente gerada por um recebimento da conta a receber do levantamento (mesmo padrao dos repasses):
     * recebimento parcial gera a parte proporcional (valor ao cliente x recebido / valor levantado) e a quitacao gera o
     * restante. Vencimento, categoria, documento e forma de pagamento sao os informados no Gerar financeiro.
     * Usa a transacao aberta. @return int|null id da conta criada
     */
    public static function gerarContaClientePorRecebimento(int $fichaId, Conta $receber, Lancamento $parcela, int $extratoId, float $valorRecebido, string $data, bool $quitacao): ?int
    {
        $c = TTransaction::get();
        $st = $c->prepare('SELECT lf.*, rpc.pessoa_id AS cliente_id FROM levantamento_financeiro lf
                             LEFT JOIN requisicao_pagamento_cliente rpc ON rpc.id = lf.requisicao_pagamento_cliente_id WHERE lf.id = ?');
        $st->execute([$fichaId]);
        $f = $st->fetch(PDO::FETCH_OBJ);
        if (!$f || (int) $f->conta_receber_id !== (int) $receber->id || empty($f->cliente_id)) {
            return null;
        }
        $z = round((float) $f->z, 2);
        $st = $c->prepare('SELECT COALESCE(SUM(valor), 0) FROM levantamento_conta_cliente WHERE levantamento_financeiro_id = ?');
        $st->execute([$fichaId]);
        $restante = round($z - (float) $st->fetchColumn(), 2);
        $y = round((float) $f->valor_levantado, 2);
        $valor = $quitacao ? $restante : min($restante, ($y > 0) ? round($z * $valorRecebido / $y, 2) : 0.0);
        if ($valor <= 0) {
            return null;
        }

        $data = substr($data, 0, 10);
        $vencimento = !empty($f->cliente_vencimento) ? substr((string) $f->cliente_vencimento, 0, 10) : $data;

        $pagar = new Conta();
        $pagar->tipo_conta_id = TipoConta::PAGAR;
        $pagar->pessoa_id = (int) $f->cliente_id;
        $pagar->categoria_conta_id = $f->cliente_categoria_conta_id ?: $receber->categoria_conta_id;
        $pagar->tipo_documento_financeiro_id = $f->cliente_tipo_documento_id ?: null;
        $pagar->escritorio_id = (int) $receber->escritorio_id;
        $pagar->contrato_id = $f->contrato_id ?: null;
        $pagar->processo_id = $f->processo_id ?: null;
        $pagar->requisicao_pagamento_id = $f->requisicao_pagamento_id ?: null;
        $pagar->data_emissao = $data;
        $pagar->total_conta = $valor;
        $pagar->total_parcelas = 1;
        $pagar->quitada = 'N';
        $pagar->descricao = $receber->descricao;
        $pagar->conta_origem_id = $receber->id;
        $pagar->criacao_user_id = TSession::getValue('userid');
        $pagar->proximo_vencimento_lancamento = $vencimento;
        $pagar->store();

        $lanc = new Lancamento();
        $lanc->conta_id = $pagar->id;
        $lanc->parcela = 1;
        $lanc->valor = $valor;
        $lanc->valor_total = $valor;
        $lanc->dt_vencimento = $vencimento;
        $lanc->tipo_pagamento_id = $f->cliente_tipo_pagamento_id ?: TipoPagamento::TRANSFERENCIA;
        $lanc->store();

        self::registrarContaCliente($c, $fichaId, (int) $pagar->id, (int) $lanc->id, (int) $receber->id, (int) $parcela->id, $extratoId ?: null, $valor, $valorRecebido, $data);
        return (int) $pagar->id;
    }

    /** Liga a conta do cliente ao recebimento que a gerou; a ficha aponta para a mais recente. */
    private static function registrarContaCliente(PDO $c, int $fichaId, int $pagarId, ?int $lancPagarId, int $receberId, ?int $lancReceberId, ?int $extratoId, float $valor, float $valorRecebido, string $data): void
    {
        $c->prepare('INSERT INTO levantamento_conta_cliente (levantamento_financeiro_id, conta_pagar_id, lancamento_pagar_id, conta_receber_id,
                         lancamento_receber_id, extrato_recebimento_id, valor, valor_recebido, data_recebimento, criacao_user_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
          ->execute([$fichaId, $pagarId, $lancPagarId, $receberId, $lancReceberId, $extratoId, round($valor, 2), round($valorRecebido, 2), $data, TSession::getValue('userid')]);
        $c->prepare('UPDATE levantamento_financeiro SET conta_pagar_id = ? WHERE id = ?')->execute([$pagarId, $fichaId]);
    }

    /**
     * Recebimento $extratoId desfeito (desquitar): apaga as contas do cliente geradas por ele; recusa se alguma tiver
     * pagamento. Usa a transacao aberta.
     */
    public static function estornarClientePorRecebimento(int $extratoId): void
    {
        if ($extratoId <= 0) {
            return;
        }
        $c = TTransaction::get();
        $st = $c->prepare('SELECT lancamento_id FROM extrato WHERE id = ?');
        $st->execute([$extratoId]);
        $lancReceber = (int) $st->fetchColumn();
        // registros sem extrato: contas do cliente anteriores a esta regra, ligadas a parcela recebida
        $st = $c->prepare('SELECT id, conta_pagar_id, levantamento_financeiro_id FROM levantamento_conta_cliente
                            WHERE extrato_recebimento_id = ? OR (extrato_recebimento_id IS NULL AND lancamento_receber_id = ?) ORDER BY id');
        $st->execute([$extratoId, $lancReceber]);
        $linhas = $st->fetchAll(PDO::FETCH_OBJ);
        foreach ($linhas as $l) {
            if (self::contaComPagamento($c, (int) $l->conta_pagar_id)) {
                throw new Exception('A conta a pagar ao cliente gerada por este recebimento já foi paga (total ou parcialmente). Desquite o pagamento ao cliente antes.');
            }
        }
        foreach ($linhas as $l) {
            $c->prepare('DELETE FROM levantamento_conta_cliente WHERE id = ?')->execute([(int) $l->id]);
            // a ficha aponta para a conta do cliente mais recente que sobrar
            $c->prepare('UPDATE levantamento_financeiro SET conta_pagar_id = (SELECT conta_pagar_id FROM levantamento_conta_cliente
                             WHERE levantamento_financeiro_id = ? ORDER BY id DESC LIMIT 1) WHERE id = ?')
              ->execute([(int) $l->levantamento_financeiro_id, (int) $l->levantamento_financeiro_id]);
            $c->prepare('DELETE FROM lancamento WHERE conta_id = ?')->execute([(int) $l->conta_pagar_id]);
            $c->prepare('DELETE FROM conta WHERE id = ?')->execute([(int) $l->conta_pagar_id]);
        }
    }

    /** Contas a pagar ao cliente do financeiro (todas as geradas pelos recebimentos, mais a da ficha). */
    public static function contasDoCliente(PDO $c, int $fichaId): array
    {
        $st = $c->prepare('SELECT conta_pagar_id FROM levantamento_conta_cliente WHERE levantamento_financeiro_id = ?
                           UNION SELECT conta_pagar_id FROM levantamento_financeiro WHERE id = ? AND conta_pagar_id IS NOT NULL');
        $st->execute([$fichaId, $fichaId]);
        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function exigirContaNaoGerada(int $contaId, bool $bloqueiaPagar = true): void
    {
        $g = self::contaDoLevantamento($contaId);
        if ($g && ($g['papel'] === 'RECEBER' || $bloqueiaPagar)) {
            throw new Exception('Conta gerada pelo financeiro do levantamento. Para corrigir, use "Desfazer financeiro" no pagamento da requisição.');
        }
        // conta a pagar de repasse a colaborador: so muda pelo estorno do recebimento que a gerou
        if ($bloqueiaPagar) {
            RepasseColaboradorService::exigirNaoRepasse($contaId);
            // conta gerada pelo financeiro do contrato: so pelo "Desfazer financeiro" do contrato
            if ($motivo = ContratoFinanceiroService::motivoConta($contaId)) {
                throw new Exception($motivo);
            }
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
            $st = $c->prepare('SELECT ativo, nome, dt_inicial FROM conta_caixa WHERE id = ? FOR UPDATE');
            $st->execute([$ccId]);
            $cc = $st->fetch(PDO::FETCH_OBJ);
            if ($ccId <= 0 || !$cc || $cc->ativo === 'N') { throw new Exception('Conta-caixa inválida.'); }

            // demais dados do formulario
            self::exigirCadastro($c, 'tipo_pagamento', $d['tipo_pagamento_id'] ?? null, 'Informe um tipo de pagamento válido.');
            self::exigirCadastro($c, 'categoria_conta', $d['categoria_conta_id'] ?? null, 'Informe uma categoria da conta válida.');
            self::exigirCadastro($c, 'tipo_documento_financeiro', $d['tipo_documento_receber_id'] ?? null, 'Informe um tipo de documento válido para a conta a receber.');
            if (!in_array($d['compensado'] ?? null, ['S', 'N'], true)) { throw new Exception('Informe se o valor já está compensado.'); }
            $compensado = ($d['compensado'] === 'S');
            if ($compensado && $cc->dt_inicial && $x < substr((string) $cc->dt_inicial, 0, 10)) {
                throw new Exception('A data do levantamento é anterior ao início da conta-caixa ' . $cc->nome . ' (' . date('d/m/Y', strtotime($cc->dt_inicial)) . ').');
            }
            $vencimento = substr((string) ($d['vencimento_cliente'] ?? ''), 0, 10);
            if ($z > 0) {
                $dv = DateTime::createFromFormat('Y-m-d', $vencimento);
                if ($vencimento === '' || !$dv || $dv->format('Y-m-d') !== $vencimento) { throw new Exception('Informe uma data de vencimento válida para o pagamento ao cliente.'); }
                self::exigirCadastro($c, 'tipo_documento_financeiro', $d['tipo_documento_pagar_id'] ?? null, 'Informe um tipo de documento válido para a conta a pagar ao cliente.');
            }

            // descontos do deposito detalhados (prestacao de contas): a soma tem que ser bruto - MLE;
            // lancados no pagamento, valem os do pagamento
            $descontos = self::descontosDetalhados($ctx->descontos_pagamento ? array_merge($d, $ctx->descontos_pagamento) : $d, round($ctx->bruto - $ctx->mle, 2));

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
            $parcela->acrescimo = 0;
            $parcela->desconto = 0;
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
            ContaCaixaSaldoService::recalcular($ccId);
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
            if ($descontos) {
                [$ficha->desconto_previdenciario, $ficha->desconto_assistencial, $ficha->desconto_irrf] = $descontos;
            }
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
            // dados da conta do cliente: ela e recriada com eles se o recebimento for desquitado e quitado de novo
            $ficha->cliente_vencimento = ($z > 0) ? $vencimento : null;
            $ficha->cliente_categoria_conta_id = (int) $d['categoria_conta_id'];
            $ficha->cliente_tipo_documento_id = !empty($d['tipo_documento_pagar_id']) ? (int) $d['tipo_documento_pagar_id'] : null;
            $ficha->cliente_tipo_pagamento_id = !empty($d['tipo_pagamento_id']) ? (int) $d['tipo_pagamento_id'] : null;
            $ficha->conta_caixa_id = $ccId;
            $ficha->data_criacao = date('Y-m-d H:i:s');
            $ficha->criacao_user_id = $userId;
            $ficha->store();
            if ($pagarId) {
                self::registrarContaCliente($c, (int) $ficha->id, (int) $pagarId, (int) $parcelaPagar->id, (int) $receber->id, (int) $parcela->id, (int) $extrato->id, $z, $y, $x);
            }

            foreach ($res['repasses'] as $r) {
                $fr = new LevantamentoFinanceiroRepasse();
                $fr->levantamento_financeiro_id = $ficha->id;
                $fr->pessoa_id = $r['pessoa_id'];
                $fr->percentual = round((float) $r['percentual'] * 100, 8);
                $fr->valor = $r['valor'];
                $fr->ajuste_centavos = $r['ajuste_centavos'];
                $fr->store();
            }

            // contas a pagar dos colaboradores (a parte do escritorio fica como receita)
            RepasseColaboradorService::gerarPorRecebimento((int) $parcela->id, (int) $extrato->id, $y, $x, true, (int) $ficha->id);
            // conta a receber nasce quitada: a requisicao pode ir para Quitado
            RequisicaoQuitacaoService::atualizar((int) $ctx->rpc_id);
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

            // conta a receber e todas as contas a pagar ao cliente (uma por recebimento)
            $contas = array_values(array_unique(array_filter(array_merge([(int) $ficha->conta_receber_id], self::contasDoCliente($c, (int) $ficha->id)))));
            $lista  = $contas ? implode(',', $contas) : '0';
            $parcelas = self::ids($c, 'SELECT id FROM lancamento WHERE conta_id = ' . (int) $ficha->conta_receber_id);
            $lancs    = $parcelas ? implode(',', $parcelas) : '0';
            $todas    = self::ids($c, "SELECT id FROM lancamento WHERE conta_id IN ({$lista})");
            $todasLista = $todas ? implode(',', $todas) : '0';

            // contas-caixa dos extratos que vao sair: o saldo delas e recalculado depois de apagar
            $contasCaixa = self::ids($c, "SELECT DISTINCT conta_caixa_id FROM extrato WHERE lancamento_id IN ({$lancs})");

            // repasses aos colaboradores gerados pelos recebimentos desta conta (recusa se algum ja foi pago)
            foreach (self::ids($c, "SELECT id FROM extrato WHERE lancamento_id IN ({$lancs})") as $extratoRecebimento) {
                RepasseColaboradorService::estornarPorRecebimento((int) $extratoRecebimento);
            }
            $c->exec("UPDATE lancamento SET extrato_id = NULL WHERE conta_id IN ({$lista})");
            $c->exec("DELETE FROM extrato WHERE lancamento_id IN ({$lancs})");
            ContaCaixaSaldoService::recalcular($contasCaixa);
            $c->exec("DELETE FROM lancamento_profissional_ajuste WHERE lancamento_profissional_id IN (SELECT id FROM lancamento_profissional WHERE lancamento_id IN ({$todasLista}))");
            $c->exec("DELETE FROM lancamento_profissional WHERE lancamento_id IN ({$todasLista})");
            $c->exec("DELETE FROM lancamento WHERE conta_id IN ({$lista})");
            $c->exec("DELETE FROM conta_profissional WHERE conta_id IN ({$lista})");

            $c->prepare('DELETE FROM levantamento_financeiro WHERE id = ?')->execute([(int) $ficha->id]);
            // a conta a pagar aponta para a conta a receber (conta_origem_id): pagar antes de receber
            foreach (array_reverse($contas) as $contaId) { $c->exec('DELETE FROM conta WHERE id = ' . $contaId); }

            // sem o financeiro, a requisicao que estava Quitado volta ao status anterior
            $st = $c->prepare('SELECT requisicao_pagamento_cliente_id FROM requisicao_pagamento_etapa3 WHERE id = ?');
            $st->execute([$etapa3Id]);
            RequisicaoQuitacaoService::atualizar((int) $st->fetchColumn());

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
        // contrato com parcela a receber anterior a publicacao em aberto continua pelo modo antigo
        ContratoFinanceiroService::exigirSemParcelaAnteriorAberta($contratoId);

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
            'pessoas_curciol'   => RepasseColaboradorService::pessoasCurciol(), // ficam com a diferenca de centavos
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
        $st = $c->prepare('SELECT id, conta_pagar_id FROM levantamento_financeiro WHERE requisicao_pagamento_etapa3_id = ?');
        $st->execute([$etapa3Id]);
        $ficha = $st->fetch(PDO::FETCH_OBJ);
        if (!$ficha) { return 'Este pagamento não tem financeiro gerado.'; }

        foreach (self::contasDoCliente($c, (int) $ficha->id) as $pagar) {
            if (self::contaComPagamento($c, $pagar)) {
                return 'A conta a pagar ao cliente já foi paga (total ou parcialmente). Estorne o pagamento ao cliente antes de desfazer.';
            }
        }
        $st = $c->prepare('SELECT DISTINCT r.extrato_recebimento_id FROM repasse_colaborador r JOIN levantamento_financeiro lf ON lf.id = r.levantamento_financeiro_id WHERE lf.requisicao_pagamento_etapa3_id = ?');
        $st->execute([$etapa3Id]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $extratoRecebimento) {
            $m = RepasseColaboradorService::motivoBloqueioRecebimento((int) $extratoRecebimento);
            if ($m !== null) { return $m; }
        }
        return null;
    }

    /**
     * Descontos do deposito detalhados: [previdenciario, assistencial, IRRF] ou null se nenhum foi informado.
     * Quando informados, os tres sao obrigatorios, nao negativos e a soma tem que ser o total (bruto - MLE).
     */
    public static function descontosDetalhados(array $d, float $total): ?array
    {
        $chaves = ['desconto_previdenciario' => 'previdenciário', 'desconto_assistencial' => 'assistencial', 'desconto_irrf' => 'IRRF'];
        $informados = array_filter(array_keys($chaves), function ($k) use ($d) { return isset($d[$k]) && $d[$k] !== ''; });
        if (!$informados) { return null; }
        $valores = [];
        foreach ($chaves as $k => $nome) {
            if (!isset($d[$k]) || $d[$k] === '' || !is_numeric($d[$k])) { throw new Exception("Informe o desconto {$nome} (use 0 se não houve)."); }
            if ((float) $d[$k] < 0) { throw new Exception("O desconto {$nome} não pode ser negativo."); }
            $valores[] = round((float) $d[$k], 2);
        }
        $soma = round(array_sum($valores), 2);
        if (abs($soma - round($total, 2)) > 0.004) {
            throw new Exception('Os descontos informados (previdenciário + assistencial + IRRF = R$ ' . number_format($soma, 2, ',', '.')
                . ') não batem com o total de descontos do pagamento (bruto − MLE = R$ ' . number_format($total, 2, ',', '.') . ').');
        }
        return $valores;
    }

    private static function exigirCadastro(PDO $c, string $tabela, $id, string $mensagem): void
    {
        if (!is_numeric($id) || (int) $id <= 0) { throw new Exception($mensagem); }
        $st = $c->prepare("SELECT 1 FROM {$tabela} WHERE id = ?");
        $st->execute([(int) $id]);
        if (!$st->fetchColumn()) { throw new Exception($mensagem); }
    }

    private static function ids(PDO $c, string $sql): array
    {
        return array_map('intval', $c->query($sql)->fetchAll(PDO::FETCH_COLUMN));
    }
}
