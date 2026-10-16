<?php
/**
 * Acoes em lote em Contas a pagar / Contas a receber.
 * - quitar: quita a proxima parcela em aberto de cada conta selecionada (uma linha no extrato por parcela);
 *   conta a receber com repasse gera as contas a pagar dos colaboradores, como na quitacao individual.
 * - compensar: compensa as linhas ainda nao compensadas do extrato das contas selecionadas.
 * - desfazer: como um Ctrl+Z, a ultima acao em lote ainda nao desfeita do proprio usuario naquela tela,
 *   ate 30 minutos depois dela, e so se nada do lote foi mexido depois.
 * O saldo da conta-caixa e recalculado pelo extrato (ContaCaixaSaldoService).
 */
class LoteFinanceiroService
{
    const PRAZO_DESFAZER_MIN = 30;

    // ------------------------------------------------------------------ consulta

    /**
     * Proxima parcela em aberto de cada conta (nao quitada, nao cancelada), na ordem das parcelas.
     * @return object[] conta_id => (lancamento_id, parcela, total_parcelas, dt_vencimento, valor, acrescimo, desconto, tipo_pagamento_id,
     *                  conta_caixa_id, pessoa, descricao, tipo_conta_id)
     */
    public static function proximasParcelas(array $contaIds): array
    {
        $ids = self::ids($contaIds);
        if (!$ids) { return []; }
        $sql = "SELECT DISTINCT ON (l.conta_id) l.conta_id, l.id AS lancamento_id, l.parcela, c.total_parcelas, l.dt_vencimento,
                       CASE WHEN COALESCE(l.saldo, 0) > 0 THEN l.saldo ELSE COALESCE(l.valor_total, l.valor) END AS valor,
                       COALESCE(l.acrescimo, 0) AS acrescimo, COALESCE(l.desconto, 0) AS desconto, l.tipo_pagamento_id,
                       c.conta_caixa_id, p.nome AS pessoa, c.descricao, c.tipo_conta_id
                  FROM lancamento l
                  JOIN conta c ON c.id = l.conta_id
             LEFT JOIN pessoa p ON p.id = c.pessoa_id
                 WHERE l.conta_id IN (" . implode(',', $ids) . ")
                   AND l.dt_pagamento IS NULL AND COALESCE(l.cancelado, 'N') <> 'S'
              ORDER BY l.conta_id, l.parcela, l.id";
        $r = [];
        foreach (self::consultar($sql) as $o) { $r[(int) $o->conta_id] = $o; }
        return $r;
    }

    /**
     * Linhas do extrato ainda nao compensadas das contas.
     * @return object[] (extrato_id, conta_id, lancamento_id, parcela, valor, conta_caixa_id, conta_caixa, dt_pagamento, pessoa, descricao)
     */
    public static function linhasACompensar(array $contaIds): array
    {
        $ids = self::ids($contaIds);
        if (!$ids) { return []; }
        $sql = "SELECT x.id AS extrato_id, l.conta_id, l.id AS lancamento_id, l.parcela, COALESCE(x.entrada_valor, 0) + COALESCE(x.saida_valor, 0) AS valor,
                       x.conta_caixa_id, cc.nome AS conta_caixa, l.dt_pagamento, p.nome AS pessoa, c.descricao
                  FROM extrato x
                  JOIN lancamento l ON l.id = x.lancamento_id
                  JOIN conta c ON c.id = l.conta_id
             LEFT JOIN conta_caixa cc ON cc.id = x.conta_caixa_id
             LEFT JOIN pessoa p ON p.id = c.pessoa_id
                 WHERE l.conta_id IN (" . implode(',', $ids) . ") AND COALESCE(x.compensado, 'N') <> 'S'
              ORDER BY p.nome, l.conta_id, l.parcela, x.id";
        return self::consultar($sql);
    }

    // ------------------------------------------------------------------ quitar

    /**
     * Quita a proxima parcela em aberto de cada conta. $d: data_pagamento (Y-m-d), conta_caixa_id, tipo_pagamento_id,
     * compensado ('S'/'N'), historico (complemento opcional).
     */
    public static function quitar(array $contaIds, string $tipoConta, array $d): LotePagamento
    {
        $propria = self::abrir();
        try {
            $c = TTransaction::get();
            $parcelas = self::proximasParcelas($contaIds);
            if (!$parcelas) { throw new Exception('Nenhuma das contas selecionadas tem parcela em aberto.'); }
            foreach ($parcelas as $p) {
                if ((string) $p->tipo_conta_id !== (string) $tipoConta) { throw new Exception('Selecione contas desta tela.'); }
                if ((float) $p->acrescimo != 0.0 || (float) $p->desconto != 0.0) {
                    throw new Exception('A parcela ' . $p->parcela . ' de "' . $p->descricao . '" tem acréscimo ou desconto: quite essa conta individualmente.');
                }
            }

            $data = self::data($d['data_pagamento'] ?? '', 'Informe a data do pagamento.');
            if ($data > date('Y-m-d')) { throw new Exception('A data do pagamento não pode ser futura.'); }
            $ccId = (int) ($d['conta_caixa_id'] ?? 0);
            $cc = self::contaCaixa($c, $ccId);
            $compensado = (($d['compensado'] ?? 'N') === 'S');
            if ($compensado && $cc->dt_inicial && $data <= substr((string) $cc->dt_inicial, 0, 10)) {
                throw new Exception('A data deve ser mais recente que o início da conta-caixa ' . $cc->nome . '.');
            }
            $tipoPagamento = (int) ($d['tipo_pagamento_id'] ?? 0);
            if ($tipoPagamento <= 0) { throw new Exception('Escolha o tipo de pagamento.'); }
            if ($tipoPagamento === (int) TipoPagamento::CHEQUE) { throw new Exception('Pagamento em cheque precisa do número de cada cheque: quite essas contas individualmente.'); }
            $complemento = trim((string) ($d['historico'] ?? ''));

            // trava as parcelas e confere que continuam em aberto
            $lancIds = array_map(function ($p) { return (int) $p->lancamento_id; }, $parcelas);
            $abertas = $c->query('SELECT id FROM lancamento WHERE id IN (' . implode(',', $lancIds) . ') AND dt_pagamento IS NULL FOR UPDATE')->fetchAll(PDO::FETCH_COLUMN);
            if (count($abertas) !== count($lancIds)) { throw new Exception('Alguma parcela foi quitada enquanto isso. Atualize a lista.'); }

            $userId = TSession::getValue('userid');
            $lote = new LotePagamento();
            $lote->acao = 'quitar';
            $lote->tipo_conta_id = (int) $tipoConta;
            $lote->data_pagamento = $data;
            $lote->conta_caixa_id = $ccId;
            $lote->tipo_pagamento_id = $tipoPagamento;
            $lote->compensado = $compensado ? 'S' : 'N';
            $lote->quantidade = count($parcelas);
            $lote->total = round(array_sum(array_map(function ($p) { return (float) $p->valor; }, $parcelas)), 2);
            $lote->historico = $complemento ?: null;
            $pessoas = array_unique(array_map(function ($p) { return $p->pessoa; }, $parcelas));
            $lote->pessoa_id = (count($pessoas) === 1) ? (int) $c->query('SELECT pessoa_id FROM conta WHERE id = ' . (int) reset($parcelas)->conta_id)->fetchColumn() : null;
            $lote->data_criacao = date('Y-m-d H:i:s');
            $lote->criacao_user_id = $userId;
            $lote->store();

            foreach ($parcelas as $p) {
                $lancamento = Lancamento::find((int) $p->lancamento_id);
                $conta = Conta::find((int) $p->conta_id);
                $valor = round((float) $p->valor, 2);

                $lancamento->dt_pagamento = $data;
                $lancamento->tipo_pagamento_id = $tipoPagamento;
                $lancamento->acrescimo = 0;
                $lancamento->desconto = 0;
                $lancamento->cheque_numero = null;
                $lancamento->cheque_banco_id = null;
                $lancamento->saldo = 0;
                $lancamento->store();

                $x = new Extrato();
                $x->escritorio_id = (int) $conta->escritorio_id;
                $x->conta_caixa_id = $ccId;
                $x->lancamento_id = $lancamento->id;
                $x->categoria_conta_id = (int) $conta->categoria_conta_id;
                if ((string) $tipoConta === (string) TipoConta::RECEBER) {
                    $x->tipo_extrato_id = TipoExtrato::RECEBER;
                    $x->entrada_valor = $valor;
                    $x->saida_valor = 0;
                } else {
                    $x->tipo_extrato_id = TipoExtrato::PAGAR;
                    $x->entrada_valor = 0;
                    $x->saida_valor = $valor;
                }
                $x->data_lancamento = date('Y-m-d');
                $x->data_previsao_compensacao = $lancamento->dt_vencimento;
                if ($compensado) {
                    $x->compensado = 'S';
                    $x->data_compensacao = $data;
                    $x->ano = date('Y', strtotime($data));
                    $x->mes = date('m', strtotime($data));
                    $x->ano_mes = date('Ym', strtotime($data));
                } else {
                    $x->compensado = 'N';
                }
                $x->historico = self::historico($conta, $lancamento, $tipoConta) . ' LOTE Nº ' . $lote->id . ($complemento ? ' - ' . $complemento : '');
                $x->lote_pagamento_id = $lote->id;
                $x->criacao_user_id = $userId;
                $x->store();

                $lancamento->extrato_id = $x->id;
                $lancamento->store();

                self::reavaliarConta($c, $conta);

                $item = new LotePagamentoItem();
                $item->lote_pagamento_id = $lote->id;
                $item->lancamento_id = $lancamento->id;
                $item->extrato_id = $x->id;
                $item->valor = $valor;
                $item->store();

                if ((string) $tipoConta === (string) TipoConta::RECEBER) {
                    RepasseColaboradorService::gerarPorRecebimento((int) $lancamento->id, (int) $x->id, $valor, $data, true);
                }
            }
            ContaCaixaSaldoService::recalcular($ccId);

            if ($propria) { TTransaction::close(); }
            return $lote;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    // ------------------------------------------------------------------ compensar

    /** Compensa as linhas ainda nao compensadas do extrato das contas, na data $dataCompensacao (Y-m-d). */
    public static function compensar(array $contaIds, string $tipoConta, string $dataCompensacao): LotePagamento
    {
        $propria = self::abrir();
        try {
            $c = TTransaction::get();
            $data = self::data($dataCompensacao, 'Informe a data da compensação.');
            if ($data > date('Y-m-d')) { throw new Exception('A data da compensação não pode ser superior à data de hoje.'); }

            $linhas = self::linhasACompensar($contaIds);
            if (!$linhas) { throw new Exception('Nenhuma das contas selecionadas tem pagamento a compensar.'); }
            $tipos = $c->query('SELECT DISTINCT tipo_conta_id FROM conta WHERE id IN (' . implode(',', self::ids($contaIds)) . ')')->fetchAll(PDO::FETCH_COLUMN);
            if (count($tipos) !== 1 || (string) $tipos[0] !== (string) $tipoConta) { throw new Exception('Selecione contas desta tela.'); }

            $extratoIds = array_map(function ($l) { return (int) $l->extrato_id; }, $linhas);
            $travadas = $c->query("SELECT id FROM extrato WHERE id IN (" . implode(',', $extratoIds) . ") AND COALESCE(compensado, 'N') <> 'S' FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
            if (count($travadas) !== count($extratoIds)) { throw new Exception('Alguma linha foi compensada enquanto isso. Atualize a lista.'); }

            $userId = TSession::getValue('userid');
            $lote = new LotePagamento();
            $lote->acao = 'compensar';
            $lote->tipo_conta_id = (int) $tipoConta;
            $lote->data_pagamento = $data;
            $lote->quantidade = count($linhas);
            $lote->total = round(array_sum(array_map(function ($l) { return (float) $l->valor; }, $linhas)), 2);
            $lote->data_criacao = date('Y-m-d H:i:s');
            $lote->criacao_user_id = $userId;
            $lote->store();

            $contasCaixa = [];
            foreach ($linhas as $l) {
                $x = Extrato::find((int) $l->extrato_id);
                $cc = self::contaCaixa($c, (int) $x->conta_caixa_id, false);
                if ($cc->dt_inicial && $data <= substr((string) $cc->dt_inicial, 0, 10)) {
                    throw new Exception('A data da compensação deve ser mais recente que o início da conta-caixa ' . $cc->nome . '.');
                }
                if ($l->dt_pagamento && $data < substr((string) $l->dt_pagamento, 0, 10)) {
                    throw new Exception('A data da compensação deve ser igual ou posterior à baixa (' . date('d/m/Y', strtotime($l->dt_pagamento)) . ') de "' . $l->descricao . '".');
                }
                $x->compensado = 'S';
                $x->data_compensacao = $data;
                $x->ano = date('Y', strtotime($data));
                $x->mes = date('m', strtotime($data));
                $x->ano_mes = date('Ym', strtotime($data));
                $x->modificacao_user_id = $userId;
                $x->store();
                $contasCaixa[] = (int) $x->conta_caixa_id;

                $item = new LotePagamentoItem();
                $item->lote_pagamento_id = $lote->id;
                $item->lancamento_id = (int) $l->lancamento_id;
                $item->extrato_id = (int) $x->id;
                $item->valor = round((float) $l->valor, 2);
                $item->store();
            }
            ContaCaixaSaldoService::recalcular($contasCaixa);

            if ($propria) { TTransaction::close(); }
            return $lote;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    // ------------------------------------------------------------------ desfazer

    /**
     * Ultima acao em lote ainda nao desfeita do usuario naquela tela, se ainda esta no prazo (30 min).
     * @return object|null (id, acao, quantidade, total, data_criacao, minutos_restantes)
     */
    public static function ultimoDesfazivel(int $userId, string $tipoConta): ?object
    {
        if ($userId <= 0) { return null; }
        $propria = self::abrir();
        try {
            // data_criacao e gravada pelo PHP (fuso local): o prazo tambem e contado no PHP
            $st = TTransaction::get()->prepare("SELECT id, acao, quantidade, total, data_criacao
                                                  FROM lote_pagamento WHERE criacao_user_id = ? AND tipo_conta_id = ? AND desfeito_em IS NULL
                                              ORDER BY data_criacao DESC, id DESC LIMIT 1");
            $st->execute([$userId, (int) $tipoConta]);
            $l = $st->fetch(PDO::FETCH_OBJ);
            if ($propria) { TTransaction::close(); }
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
        if (!$l) { return null; }
        $l->minutos_restantes = (int) ceil((strtotime($l->data_criacao) + self::PRAZO_DESFAZER_MIN * 60 - time()) / 60);
        return ($l->minutos_restantes > 0) ? $l : null;
    }

    /** Desfaz o lote (so o ultimo do usuario naquela tela, dentro do prazo, sem nada mexido depois). */
    public static function desfazer(int $loteId, int $userId): void
    {
        $propria = self::abrir();
        try {
            $c = TTransaction::get();
            $st = $c->prepare('SELECT * FROM lote_pagamento WHERE id = ? FOR UPDATE');
            $st->execute([$loteId]);
            $lote = $st->fetch(PDO::FETCH_OBJ);
            if (!$lote) { throw new Exception('Lote não encontrado.'); }
            $ultimo = self::ultimoDesfazivel($userId, (string) $lote->tipo_conta_id);
            if (!$ultimo || (int) $ultimo->id !== (int) $lote->id) {
                throw new Exception('Só dá para desfazer a sua última ação em lote nesta tela, até ' . self::PRAZO_DESFAZER_MIN . ' minutos depois dela.');
            }
            $itens = $c->query('SELECT * FROM lote_pagamento_item WHERE lote_pagamento_id = ' . (int) $loteId . ' ORDER BY id')->fetchAll(PDO::FETCH_OBJ);
            $contasCaixa = [];

            if ($lote->acao === 'quitar') {
                foreach ($itens as $i) {
                    $st = $c->prepare('SELECT l.id, l.extrato_id, l.dt_pagamento, l.conta_id, COALESCE(l.valor_total, l.valor) AS base, x.id AS x_id, x.compensado, x.conta_caixa_id
                                         FROM lancamento l LEFT JOIN extrato x ON x.id = ? WHERE l.id = ? FOR UPDATE OF l');
                    $st->execute([(int) $i->extrato_id, (int) $i->lancamento_id]);
                    $r = $st->fetch(PDO::FETCH_OBJ);
                    if (!$r || !$r->x_id || (int) $r->extrato_id !== (int) $i->extrato_id || empty($r->dt_pagamento)) {
                        throw new Exception('Uma das contas do lote foi desquitada ou alterada depois. O lote não pode ser desfeito.');
                    }
                    if ((string) $r->compensado !== (string) ($lote->compensado ?: 'N')) {
                        throw new Exception('Um pagamento do lote foi compensado depois. Descompense-o antes de desfazer o lote.');
                    }
                }
                foreach ($itens as $i) {
                    if ((string) $lote->tipo_conta_id === (string) TipoConta::RECEBER) {
                        RepasseColaboradorService::estornarPorRecebimento((int) $i->extrato_id); // recusa se algum repasse ja foi pago
                    }
                    $lancamento = Lancamento::find((int) $i->lancamento_id);
                    $x = Extrato::find((int) $i->extrato_id);
                    $contasCaixa[] = (int) $x->conta_caixa_id;
                    $base = round((float) ($lancamento->valor_total !== null && $lancamento->valor_total !== '' ? $lancamento->valor_total : $lancamento->valor), 2);
                    $lancamento->dt_pagamento = null;
                    $lancamento->ano_pagamento = null;
                    $lancamento->mes_pagamento = null;
                    $lancamento->ano_mes_pagamento = null;
                    $lancamento->extrato_id = null;
                    // havia pagamento parcial antes: a parcela volta a dever so o que o lote quitou
                    $lancamento->saldo = (round((float) $i->valor, 2) < $base) ? round((float) $i->valor, 2) : null;
                    $lancamento->store();
                    $c->exec('DELETE FROM extrato WHERE id = ' . (int) $i->extrato_id);
                    self::reavaliarConta($c, Conta::find((int) $lancamento->conta_id));
                }
            } else {
                foreach ($itens as $i) {
                    $x = Extrato::find((int) $i->extrato_id);
                    if (!$x || $x->compensado !== 'S' || substr((string) $x->data_compensacao, 0, 10) !== substr((string) $lote->data_pagamento, 0, 10)) {
                        throw new Exception('Uma das linhas do lote foi descompensada ou alterada depois. O lote não pode ser desfeito.');
                    }
                }
                foreach ($itens as $i) {
                    $x = Extrato::find((int) $i->extrato_id);
                    $x->compensado = 'N';
                    $x->data_compensacao = null;
                    $x->ano = null;
                    $x->mes = null;
                    $x->ano_mes = null;
                    $x->modificacao_user_id = $userId;
                    $x->store();
                    $contasCaixa[] = (int) $x->conta_caixa_id;
                }
            }

            $c->prepare('UPDATE lote_pagamento SET desfeito_em = ?, desfeito_user_id = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $userId, $loteId]);
            ContaCaixaSaldoService::recalcular($contasCaixa);

            if ($propria) { TTransaction::close(); }
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    // ------------------------------------------------------------------ auxiliares

    /** Quitada se nao sobrou parcela aberta; proximo vencimento = primeira parcela aberta. */
    private static function reavaliarConta(PDO $c, ?Conta $conta): void
    {
        if (!$conta) { return; }
        $st = $c->prepare("SELECT MIN(dt_vencimento) FROM lancamento WHERE conta_id = ? AND dt_pagamento IS NULL AND COALESCE(cancelado, 'N') <> 'S'");
        $st->execute([(int) $conta->id]);
        $proximo = $st->fetchColumn();
        $conta->quitada = $proximo ? 'N' : 'S';
        if ($proximo) { $conta->proximo_vencimento_lancamento = $proximo; }
        $conta->store();
    }

    private static function historico(Conta $conta, Lancamento $l, string $tipoConta): string
    {
        $categoria = $conta->categoria_conta->nome ?? '';
        $pessoa = mb_strtoupper($conta->pessoa->nome ?? '');
        $documento = mb_strtoupper($conta->tipo_documento_financeiro->nome ?? '');
        $prefixo = ((string) $tipoConta === (string) TipoConta::RECEBER) ? 'RECEBIMENTO' : 'PAGAMENTO';
        return "{$prefixo} - {$categoria} - {$pessoa} - REF. {$documento} #{$conta->numero_documento} - PARCELA {$l->parcela}/{$conta->total_parcelas}.";
    }

    private static function contaCaixa(PDO $c, int $id, bool $exigirAtiva = true): object
    {
        $st = $c->prepare('SELECT id, nome, ativo, dt_inicial FROM conta_caixa WHERE id = ?');
        $st->execute([$id]);
        $cc = $st->fetch(PDO::FETCH_OBJ);
        if (!$cc || ($exigirAtiva && $cc->ativo === 'N')) { throw new Exception('Escolha a conta-caixa.'); }
        return $cc;
    }

    private static function data($v, string $mensagem): string
    {
        $v = trim((string) $v);
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) { $v = "{$m[3]}-{$m[2]}-{$m[1]}"; }
        $v = substr($v, 0, 10);
        $dt = DateTime::createFromFormat('Y-m-d', $v);
        if (!$dt || $dt->format('Y-m-d') !== $v) { throw new Exception($mensagem); }
        return $v;
    }

    private static function ids(array $v): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $v), function ($x) { return $x > 0; })));
    }

    /** SELECT na transacao aberta (ou numa propria). */
    private static function consultar(string $sql): array
    {
        $propria = self::abrir();
        try {
            $r = TTransaction::get()->query($sql)->fetchAll(PDO::FETCH_OBJ);
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }

    private static function abrir(): bool
    {
        if (TTransaction::get() === null) { TTransaction::open('escritorio'); return true; }
        return false;
    }
}
