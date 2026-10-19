<?php
/**
 * Status "Quitado" da requisicao de pagamento (por cliente da requisicao).
 *
 * So pode ficar Quitado quando: ha pagamento lancado (com deposito), o ultimo nao deixou saldo remanescente,
 * todos tem o financeiro gerado e todas as contas a receber desses financeiros estao quitadas (as contas a pagar
 * nao contam). Quando isso acontece o status vai sozinho para Quitado; o status anterior fica guardado e volta
 * se a condicao deixar de valer (conta desquitada, financeiro desfeito, novo pagamento com saldo...).
 * Requisicoes que ja estavam Quitado antes desta regra (sem status anterior guardado) nao sao mexidas.
 *
 * Todos os metodos usam a transacao aberta.
 */
class RequisicaoQuitacaoService
{
    /** id do status "Quitado" (pelo nome); null se nao existir. */
    public static function statusQuitadoId(): ?int
    {
        $id = TTransaction::get()->query("SELECT id FROM status_requisicao_pagamento WHERE UPPER(TRIM(nome)) IN ('QUITADO', 'QUITADA') ORDER BY id LIMIT 1")->fetchColumn();
        return $id ? (int) $id : null;
    }

    /** Status guardado como "anterior" ao ir para Quitado: o atual ou, sem status, o inicial (Cadastrado). */
    private static function anteriorOuInicial(?int $atual): ?int
    {
        if ($atual !== null) {
            return $atual;
        }
        $id = TTransaction::get()->query("SELECT id FROM status_requisicao_pagamento ORDER BY CASE WHEN UPPER(nome) IN ('CADASTRADO', 'CADASTRADA') THEN 1 ELSE 2 END, id LIMIT 1")->fetchColumn();
        return $id ? (int) $id : null;
    }

    /** Por que o cliente da requisicao ainda nao pode ficar Quitado; null quando pode. */
    public static function motivo(int $rpcId): ?string
    {
        $st = TTransaction::get()->prepare("
            SELECT e3.id, e3.possui_saldo, lf.id AS ficha_id, cr.quitada
              FROM requisicao_pagamento_etapa3 e3
              LEFT JOIN levantamento_financeiro lf ON lf.requisicao_pagamento_etapa3_id = e3.id
              LEFT JOIN conta cr ON cr.id = lf.conta_receber_id
             WHERE e3.requisicao_pagamento_cliente_id = ?
               AND e3.data_deposito IS NOT NULL
             ORDER BY COALESCE(e3.numero_ciclo, 1), e3.id");
        $st->execute([$rpcId]);
        $pagamentos = $st->fetchAll(PDO::FETCH_OBJ);

        if (!$pagamentos) {
            return 'Nenhum pagamento lançado.';
        }
        if (strtoupper(trim((string) end($pagamentos)->possui_saldo)) === 'S') {
            return 'Ainda há saldo remanescente a receber.';
        }
        foreach ($pagamentos as $p) {
            if (empty($p->ficha_id)) {
                return 'Gere o financeiro de todos os pagamentos.';
            }
        }
        foreach ($pagamentos as $p) {
            if ($p->quitada !== 'S') {
                return 'Quite a conta a receber de todos os pagamentos.';
            }
        }
        return null;
    }

    /** @return array rpcId => motivo (null quando pode ficar Quitado) */
    public static function motivos(array $rpcIds): array
    {
        $r = [];
        foreach ($rpcIds as $id) {
            $r[(int) $id] = self::motivo((int) $id);
        }
        return $r;
    }

    /**
     * Status escolhido no formulario da requisicao (depois de gravar os pagamentos do mesmo envio).
     * Recusa Quitado sem a condicao; guarda/limpa o status anterior; sem troca de status, aplica a regra automatica.
     */
    public static function aplicarEscolha(int $rpcId, ?int $statusAnterior, ?int $statusEscolhido): void
    {
        $quitado = self::statusQuitadoId();
        if ($quitado === null) {
            return;
        }
        $c = TTransaction::get();
        if ($statusEscolhido === $statusAnterior) {
            self::atualizar($rpcId);
            return;
        }
        if ($statusEscolhido === $quitado) {
            $motivo = self::motivo($rpcId);
            if ($motivo !== null) {
                throw new Exception('O status Quitado ainda não pode ser escolhido: ' . $motivo);
            }
            $c->prepare('UPDATE requisicao_pagamento_cliente SET status_antes_quitado_id = ? WHERE id = ?')->execute([self::anteriorOuInicial($statusAnterior), $rpcId]);
        } elseif ($statusAnterior === $quitado) {
            $c->prepare('UPDATE requisicao_pagamento_cliente SET status_antes_quitado_id = NULL WHERE id = ?')->execute([$rpcId]);
        }
    }

    /** Regra automatica: vai para Quitado quando a condicao vale; volta ao status anterior quando deixa de valer. */
    public static function atualizar(int $rpcId): void
    {
        $quitado = self::statusQuitadoId();
        if ($quitado === null || $rpcId <= 0) {
            return;
        }
        $c = TTransaction::get();
        $st = $c->prepare('SELECT status_requisicao_pagamento_id, status_antes_quitado_id FROM requisicao_pagamento_cliente WHERE id = ? FOR UPDATE');
        $st->execute([$rpcId]);
        $rpc = $st->fetch(PDO::FETCH_OBJ);
        if (!$rpc) {
            return;
        }
        $atual = $rpc->status_requisicao_pagamento_id !== null ? (int) $rpc->status_requisicao_pagamento_id : null;
        $motivo = self::motivo($rpcId);
        $userId = TSession::getValue('userid');

        if ($motivo === null && $atual !== $quitado) {
            $c->prepare('UPDATE requisicao_pagamento_cliente
                            SET status_antes_quitado_id = ?, status_requisicao_pagamento_id = ?, data_modificacao = CURRENT_TIMESTAMP, modificacao_user_id = ?
                          WHERE id = ?')
              ->execute([self::anteriorOuInicial($atual), $quitado, $userId, $rpcId]);
        } elseif ($motivo !== null && $atual === $quitado && $rpc->status_antes_quitado_id !== null) {
            $c->prepare('UPDATE requisicao_pagamento_cliente
                            SET status_requisicao_pagamento_id = status_antes_quitado_id, status_antes_quitado_id = NULL, data_modificacao = CURRENT_TIMESTAMP, modificacao_user_id = ?
                          WHERE id = ?')
              ->execute([$userId, $rpcId]);
        }
    }

    /** Conta a receber gravada (quitada/desquitada): reavalia o cliente da requisicao do financeiro dela, se houver. */
    public static function atualizarPorConta(int $contaId): void
    {
        $st = TTransaction::get()->prepare('
            SELECT e3.requisicao_pagamento_cliente_id
              FROM levantamento_financeiro lf
              JOIN requisicao_pagamento_etapa3 e3 ON e3.id = lf.requisicao_pagamento_etapa3_id
             WHERE lf.conta_receber_id = ?');
        $st->execute([$contaId]);
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $rpcId) {
            self::atualizar((int) $rpcId);
        }
    }
}
