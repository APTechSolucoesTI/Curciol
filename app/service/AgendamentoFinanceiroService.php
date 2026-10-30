<?php
/**
 * Financeiro do agendamento (29/10/2026), no mesmo padrao do financeiro da requisicao de pagamento e do contrato.
 *
 * - A parceria de repasse e escolhida no agendamento (cadastro/edicao, com a divisao por colaborador na tela); o Gerar
 *   financeiro usa a dela (agendamento sem parceria: escolhida na janela e gravada no agendamento).
 * - Gerar financeiro: conta a receber do cliente com uma parcela (valor do agendamento, editavel) e a divisao da
 *   parceria de repasse gravada na parcela (lancamento_profissional, como no contrato). "Valor já quitado?":
 *   N = ainda a quitar (os repasses saem quando a parcela for quitada, RepasseColaboradorService::gerarPorRecebimento),
 *   S = ja quitado, C = ja quitado e compensado (recebimento na conta-caixa e repasses na hora). Desquitar estorna os
 *   repasses e e recusado se algum ja foi pago ao colaborador.
 * - Desfazer financeiro: so com a conta a receber sem nenhum recebimento (nem repasse), como em todo financeiro
 *   gerado: quitada, primeiro desquita; apaga a conta e a ficha.
 * - Agendamento com financeiro nao pode ser cancelado; a conta a receber gerada so muda pelo desfazer.
 * Parcerias: so as de percentual fixo (as por tempo dependem de datas de distribuicao/levantamento).
 */
class AgendamentoFinanceiroService
{
    const MSG_NAO_CANCELAR = 'Este agendamento já tem financeiro gerado (conta a receber nº %d) e não pode ser cancelado. '
        . 'Use "Desfazer financeiro" no agendamento antes (possível enquanto a conta a receber não tiver recebimento).';

    /** Valor do agendamento: soma dos procedimentos (valor total). Usa a transacao aberta. */
    public static function valorDoAgendamento(int $agendamentoId): float
    {
        $st = TTransaction::get()->prepare('SELECT COALESCE(SUM(valor_total), 0) FROM agendamento_procedimento WHERE agendamento_id = ?');
        $st->execute([$agendamentoId]);
        return round((float) $st->fetchColumn(), 2);
    }

    /** Financeiro gerado do agendamento (agendamento_financeiro), ou null. Usa a transacao aberta ou abre a propria. */
    public static function ficha(int $agendamentoId): ?object
    {
        return self::comTransacao(function (PDO $c) use ($agendamentoId) {
            $st = $c->prepare('SELECT * FROM agendamento_financeiro WHERE agendamento_id = ?');
            $st->execute([$agendamentoId]);
            return $st->fetch(PDO::FETCH_OBJ) ?: null;
        });
    }

    /** Agendamento com financeiro gerado nao pode ser cancelado. Usa a transacao aberta. */
    public static function exigirCancelavel(int $agendamentoId): void
    {
        if ($ficha = self::ficha($agendamentoId)) {
            throw new Exception(sprintf(self::MSG_NAO_CANCELAR, (int) $ficha->conta_receber_id));
        }
    }

    /** Por que nao da para gerar o financeiro do agendamento agora (null = pode). Usa a transacao aberta ou abre a propria. */
    public static function motivoNaoGerar(int $agendamentoId): ?string
    {
        return self::comTransacao(function (PDO $c) use ($agendamentoId) {
            $a = Agendamento::find($agendamentoId);
            if (!$a) { return 'Agendamento não encontrado.'; }
            if ((string) $a->estado_agenda_id === (string) EstadoAgenda::CANCELADO) { return 'Agendamento cancelado não gera financeiro.'; }
            if (self::ficha($agendamentoId)) { return 'Este agendamento já tem financeiro gerado.'; }
            return null;
        });
    }

    /**
     * Parcerias que podem ser usadas no agendamento: ativas e de percentual fixo. [id => nome]
     * Com a agenda (30/10/2026), so as parcerias de que o colaborador dono dela participa.
     */
    public static function parceriasFixas(int $agendaId = 0): array
    {
        return self::comTransacao(function (PDO $c) use ($agendaId) {
            $sql = "SELECT id, nome FROM parceria_levantamento WHERE ativo = 'S' AND tipo = ?";
            $args = [ParceriaLevantamento::TIPO_FIXA];
            if ($agendaId > 0) {
                $sql .= ' AND id IN (SELECT pp.parceria_levantamento_id FROM parceria_levantamento_participante pp JOIN agenda a ON a.profissional_id = pp.pessoa_id WHERE a.id = ?)';
                $args[] = $agendaId;
            }
            $st = $c->prepare($sql . ' ORDER BY nome');
            $st->execute($args);
            $r = [];
            foreach ($st->fetchAll(PDO::FETCH_OBJ) as $p) { $r[(int) $p->id] = $p->nome; }
            return $r;
        });
    }

    /**
     * Agenda ou parceria de agendamento com financeiro gerado nao muda (a conta a receber foi gerada com elas): desfazer
     * o financeiro antes. A parceria escolhida tem que ser uma das do colaborador da agenda. Usa a transacao aberta.
     */
    public static function exigirAgendaParceria(int $agendamentoId, int $agendaId, int $parceriaId): void
    {
        $antes = $agendamentoId > 0 ? Agendamento::find($agendamentoId) : null;
        $mudouAgenda = $antes && (int) $antes->agenda_id !== $agendaId;
        $mudouParceria = $antes && (int) $antes->parceria_levantamento_id !== $parceriaId;
        if (($mudouAgenda || $mudouParceria) && ($ficha = self::ficha($agendamentoId))) {
            throw new Exception('Este agendamento já tem financeiro gerado (conta a receber nº ' . (int) $ficha->conta_receber_id . '). '
                . 'Para alterar a ' . ($mudouAgenda ? 'agenda' : 'parceria') . ', desfaça o financeiro antes (aba Financeiro do agendamento).');
        }
        if ($parceriaId > 0 && (!$antes || $mudouAgenda || $mudouParceria) && !isset(self::parceriasFixas($agendaId)[$parceriaId])) {
            $nome = ParceriaLevantamento::find($parceriaId)->nome ?? '';
            $agenda = Agenda::find($agendaId);
            throw new Exception("A parceria {$nome} não tem o colaborador da agenda " . ($agenda->nome ?? '') . '. Escolha uma parceria de que ele participe.');
        }
    }

    /**
     * Previa da divisao da parceria para o valor (mesma regra de RepasseColaboradorService::dividirParcela: a Curciol fica
     * com a diferenca de centavos). @return array [['pessoa_id','nome','percentual','valor','escritorio'], ...]
     */
    public static function divisao(int $parceriaId, float $valor, int $escritorioId = 0): array
    {
        return self::comTransacao(function (PDO $c) use ($parceriaId, $valor, $escritorioId) {
            $parceria = ParceriaLevantamento::find($parceriaId);
            if (!$parceria || $parceria->tipo !== ParceriaLevantamento::TIPO_FIXA) { return []; }
            $per = ParceriaPercentualService::percentuais($parceria->tipo, array_map(function ($p) {
                return ['pessoa_id' => $p->pessoa_id, 'nivel' => $p->nivel, 'percentual' => $p->percentual,
                        'percentual_antes' => $p->percentual_antes, 'percentual_depois' => $p->percentual_depois, 'recebe_diferenca' => $p->recebe_diferenca];
            }, $parceria->participantes()), null, null, null, null);
            $linhas = array_values(array_filter($per['participantes'], function ($p) { return (float) $p['percentual'] > 0; }));
            if (!$linhas) { return []; }
            $base = (int) round($valor * 100);
            $centavos = array_map(function ($p) use ($base) { return (int) round($base * (float) $p['percentual']); }, $linhas);
            $receptor = RepasseColaboradorService::indiceDaDiferenca(array_column($linhas, 'pessoa_id'), count($linhas) - 1);
            if ($receptor !== null && abs(array_sum(array_column($linhas, 'percentual')) - 1) < 0.0001) {
                $centavos[$receptor] = $base - (array_sum($centavos) - $centavos[$receptor]);
            }
            $escritorioPessoa = $escritorioId > 0 ? RepasseColaboradorService::pessoaDoEscritorio($escritorioId) : 0;
            $r = [];
            foreach ($linhas as $i => $p) {
                $nome = Pessoa::find((int) $p['pessoa_id'])->nome ?? '';
                $r[] = ['pessoa_id' => (int) $p['pessoa_id'], 'nome' => $nome, 'percentual' => round((float) $p['percentual'] * 100, 4),
                        'valor' => $centavos[$i] / 100, 'escritorio' => ((int) $p['pessoa_id'] === $escritorioPessoa)];
            }
            return RepasseColaboradorService::ordenarPorColaborador($r, function ($x) { return $x['pessoa_id']; }, function ($x) { return $x['nome']; });
        });
    }

    /**
     * Participantes da parceria para a divisao na tela do agendamento, na ordem dos colaboradores:
     * [['nome', 'percentual' (0-100), 'diferenca' (fica com a diferenca de centavos)], ...]. Parceria nao fixa: [].
     */
    public static function participantes(int $parceriaId): array
    {
        return self::comTransacao(function (PDO $c) use ($parceriaId) {
            $parceria = ParceriaLevantamento::find($parceriaId);
            if (!$parceria || $parceria->tipo !== ParceriaLevantamento::TIPO_FIXA) { return []; }
            $per = ParceriaPercentualService::percentuais($parceria->tipo, array_map(function ($p) {
                return ['pessoa_id' => $p->pessoa_id, 'nivel' => $p->nivel, 'percentual' => $p->percentual,
                        'percentual_antes' => $p->percentual_antes, 'percentual_depois' => $p->percentual_depois, 'recebe_diferenca' => $p->recebe_diferenca];
            }, $parceria->participantes()), null, null, null, null);
            $linhas = array_values(array_filter($per['participantes'], function ($p) { return (float) $p['percentual'] > 0; }));
            if (!$linhas) { return []; }
            $fechaCem = abs(array_sum(array_column($linhas, 'percentual')) - 1) < 0.0001;
            $receptor = $fechaCem ? RepasseColaboradorService::indiceDaDiferenca(array_column($linhas, 'pessoa_id'), count($linhas) - 1) : null;
            $r = [];
            foreach ($linhas as $i => $p) {
                $r[] = ['pessoa_id' => (int) $p['pessoa_id'], 'nome' => Pessoa::find((int) $p['pessoa_id'])->nome ?? '',
                        'percentual' => round((float) $p['percentual'] * 100, 4), 'diferenca' => ($i === $receptor)];
            }
            $r = RepasseColaboradorService::ordenarPorColaborador($r, function ($x) { return $x['pessoa_id']; }, function ($x) { return $x['nome']; });
            return array_map(function ($x) { unset($x['pessoa_id']); return $x; }, $r);
        });
    }

    /** Escritorio da conta a receber do agendamento: o da agenda (sem ele, o da unidade logada). Usa a transacao aberta. */
    public static function escritorioDoAgendamento(int $agendamentoId): int
    {
        $st = TTransaction::get()->prepare('SELECT ag.escritorio_id FROM agendamento a JOIN agenda ag ON ag.id = a.agenda_id WHERE a.id = ?');
        $st->execute([$agendamentoId]);
        $id = (int) $st->fetchColumn();
        if ($id <= 0) {
            $esc = Escritorio::where('system_unit_id', '=', TSession::getValue('userunitid'))->first();
            $id = (int) ($esc->id ?? 0);
        }
        return $id;
    }

    /**
     * Gera o financeiro do agendamento. $d: valor, parceria_levantamento_id, conta_caixa_id, tipo_pagamento_id,
     * categoria_conta_id, tipo_documento_financeiro_id, vencimento (aaaa-mm-dd), quitacao (N = ainda a quitar,
     * S = ja quitado, C = ja quitado e compensado). Quitada: a data do recebimento e o vencimento. A parceria e a do
     * agendamento (sem parceria, a informada fica gravada nele). Abre a propria transacao.
     */
    public static function gerar(int $agendamentoId, array $d): AgendamentoFinanceiro
    {
        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            $st = $c->prepare('SELECT id FROM agendamento WHERE id = ? FOR UPDATE');
            $st->execute([$agendamentoId]);
            if (!$st->fetchColumn()) { throw new Exception('Agendamento não encontrado.'); }
            if ($motivo = self::motivoNaoGerar($agendamentoId)) { throw new Exception($motivo); }
            $agendamento = Agendamento::find($agendamentoId);

            $valor = round((float) ($d['valor'] ?? 0), 2);
            if ($valor <= 0) { throw new Exception('Informe o valor da conta a receber (maior que zero).'); }

            // a parceria e a do agendamento; sem ela, a informada na janela
            $parceriaId = (int) ($agendamento->parceria_levantamento_id ?: ($d['parceria_levantamento_id'] ?? 0));
            $parceria = ParceriaLevantamento::find($parceriaId);
            if (!$parceria || $parceria->ativo !== 'S') { throw new Exception('Escolha a parceria de repasse.'); }
            if ($parceria->tipo !== ParceriaLevantamento::TIPO_FIXA) { throw new Exception('No agendamento só valem parcerias de percentual fixo.'); }
            if (!$agendamento->parceria_levantamento_id && !isset(self::parceriasFixas((int) $agendamento->agenda_id)[$parceriaId])) {
                throw new Exception('Escolha uma parceria de que o colaborador da agenda participe.');
            }

            $ccId = (int) ($d['conta_caixa_id'] ?? 0);
            $st = $c->prepare('SELECT ativo, nome, dt_inicial FROM conta_caixa WHERE id = ? FOR UPDATE');
            $st->execute([$ccId]);
            $cc = $st->fetch(PDO::FETCH_OBJ);
            if ($ccId <= 0 || !$cc || $cc->ativo === 'N') { throw new Exception('Escolha uma conta-caixa válida.'); }

            self::exigirCadastro($c, 'tipo_pagamento', $d['tipo_pagamento_id'] ?? null, 'Escolha a forma de pagamento.');
            self::exigirCadastro($c, 'categoria_conta', $d['categoria_conta_id'] ?? null, 'Escolha a categoria da conta.');
            self::exigirCadastro($c, 'tipo_documento_financeiro', $d['tipo_documento_financeiro_id'] ?? null, 'Escolha o documento da conta a receber.');

            $vencimento = substr((string) ($d['vencimento'] ?? ''), 0, 10);
            $dv = DateTime::createFromFormat('Y-m-d', $vencimento);
            if ($vencimento === '' || !$dv || $dv->format('Y-m-d') !== $vencimento) { throw new Exception('Informe uma data de vencimento válida.'); }

            $quitacao = $d['quitacao'] ?? null;
            if (!in_array($quitacao, ['N', 'S', 'C'], true)) { throw new Exception('Informe se o valor já está quitado.'); }
            $quitar = ($quitacao !== 'N');
            $compensado = ($quitacao === 'C');
            if ($quitar) {
                if ($compensado && $cc->dt_inicial && $vencimento < substr((string) $cc->dt_inicial, 0, 10)) {
                    throw new Exception('A data do recebimento é anterior ao início da conta-caixa ' . $cc->nome . ' (' . date('d/m/Y', strtotime($cc->dt_inicial)) . ').');
                }
            }

            $agenda = Agenda::find((int) $agendamento->agenda_id);
            $escritorioId = self::escritorioDoAgendamento($agendamentoId);
            if ($escritorioId <= 0) { throw new Exception('A agenda deste agendamento não tem escritório.'); }

            // divisao da parceria (percentual fixo), base dos repasses na quitacao
            $participantes = array_map(function ($p) {
                return ['pessoa_id' => $p->pessoa_id, 'nivel' => $p->nivel, 'percentual' => $p->percentual,
                        'percentual_antes' => $p->percentual_antes, 'percentual_depois' => $p->percentual_depois, 'recebe_diferenca' => $p->recebe_diferenca];
            }, $parceria->participantes());
            $per = ParceriaPercentualService::percentuais($parceria->tipo, $participantes, null, null, null, null);
            $divisao = array_map(function ($p) { return [(int) $p['pessoa_id'], round((float) $p['percentual'] * 100, 8)]; }, $per['participantes']);

            if (empty($agendamento->parceria_levantamento_id)) {
                $c->prepare('UPDATE agendamento SET parceria_levantamento_id = ? WHERE id = ?')->execute([(int) $parceria->id, $agendamentoId]);
            }

            $userId = TSession::getValue('userid');
            $cliente = Pessoa::find((int) $agendamento->cliente_id);
            $descricao = 'Agendamento nº ' . $agendamentoId . ' - ' . trim((string) ($cliente->nome ?? ''))
                . ' - ' . date('d/m/Y H:i', strtotime((string) $agendamento->dt_inicial)) . (!empty($agenda->nome) ? ' - ' . $agenda->nome : '');

            $receber = new Conta();
            $receber->tipo_conta_id = TipoConta::RECEBER;
            $receber->pessoa_id = (int) $agendamento->cliente_id;
            $receber->profissional_id = $agenda->profissional_id ?? null;
            $receber->categoria_conta_id = (int) $d['categoria_conta_id'];
            $receber->tipo_documento_financeiro_id = (int) $d['tipo_documento_financeiro_id'];
            $receber->escritorio_id = $escritorioId;
            $receber->agendamento_id = $agendamentoId;
            $receber->conta_caixa_id = $ccId; // prevista: sugerida na quitacao
            $receber->data_emissao = date('Y-m-d');
            $receber->total_conta = $valor;
            $receber->total_parcelas = 1;
            $receber->quitada = $quitar ? 'S' : 'N';
            $receber->descricao = mb_substr($descricao, 0, 500);
            $receber->proximo_vencimento_lancamento = $vencimento;
            $receber->criacao_user_id = $userId;
            $receber->store();

            $parcela = new Lancamento();
            $parcela->conta_id = $receber->id;
            $parcela->parcela = 1;
            $parcela->valor = $valor;
            $parcela->valor_total = $valor;
            $parcela->dt_vencimento = $vencimento;
            $parcela->tipo_pagamento_id = (int) $d['tipo_pagamento_id'];
            if ($quitar) {
                $parcela->dt_pagamento = $vencimento;
                $parcela->saldo = 0;
                $parcela->acrescimo = 0;
                $parcela->desconto = 0;
            }
            $parcela->store();

            // divisao na parcela (a Curciol fica com a diferenca de centavos) e a mesma na conta
            RepasseColaboradorService::dividirParcela((int) $parcela->id, $divisao);
            foreach (LancamentoProfissional::where('lancamento_id', '=', $parcela->id)->orderBy('id')->load() as $lp) {
                $cp = new ContaProfissional();
                $cp->conta_id = $receber->id;
                $cp->pessoa_id = (int) $lp->pessoa_id;
                $cp->percentual = $lp->percentual;
                $cp->valor = $lp->valor;
                $cp->store();
            }

            if ($quitar) {
                // extrato (mesmo padrao do levantamento / ModalQuitarParcela::onPagar) e saldo da conta-caixa
                $extrato = new Extrato();
                $extrato->escritorio_id = $escritorioId;
                $extrato->conta_caixa_id = $ccId;
                $extrato->lancamento_id = $parcela->id;
                $extrato->categoria_conta_id = (int) $d['categoria_conta_id'];
                $extrato->tipo_extrato_id = TipoExtrato::RECEBER;
                $extrato->entrada_valor = $valor;
                $extrato->data_lancamento = date('Y-m-d');
                $extrato->data_previsao_compensacao = $vencimento;
                if ($compensado) {
                    $extrato->compensado = 'S';
                    $extrato->data_compensacao = $vencimento;
                    $extrato->ano = date('Y', strtotime($vencimento));
                    $extrato->mes = date('m', strtotime($vencimento));
                    $extrato->ano_mes = date('Ym', strtotime($vencimento));
                } else {
                    $extrato->compensado = 'N';
                }
                $extrato->historico = $receber->descricao;
                $extrato->criacao_user_id = $userId;
                $extrato->store();
                ContaCaixaSaldoService::recalcular($ccId);
                $parcela->extrato_id = $extrato->id;
                $parcela->store();

                // repasses aos colaboradores (a parte do escritorio fica como receita)
                RepasseColaboradorService::gerarPorRecebimento((int) $parcela->id, (int) $extrato->id, $valor, $vencimento, true);
            }

            $ficha = new AgendamentoFinanceiro();
            $ficha->agendamento_id = $agendamentoId;
            $ficha->conta_receber_id = $receber->id;
            $ficha->lancamento_id = $parcela->id;
            $ficha->parceria_levantamento_id = (int) $parceria->id;
            $ficha->valor = $valor;
            $ficha->conta_caixa_id = $ccId;
            $ficha->quitado_ao_gerar = $quitar ? 'S' : 'N';
            $ficha->data_criacao = date('Y-m-d H:i:s');
            $ficha->criacao_user_id = $userId;
            $ficha->store();

            TTransaction::close();
            return $ficha;
        } catch (Throwable $e) {
            TTransaction::rollback();
            if (stripos($e->getMessage(), 'uk_agendamento_financeiro_agendamento') !== false) {
                throw new Exception('Este agendamento já tem financeiro gerado.');
            }
            throw $e;
        }
    }

    /** Por que o financeiro do agendamento nao pode ser desfeito (null = pode). Usa a transacao aberta ou abre a propria. */
    public static function motivoBloqueioDesfazer(int $agendamentoId): ?string
    {
        return self::comTransacao(function (PDO $c) use ($agendamentoId) {
            $ficha = self::ficha($agendamentoId);
            if (!$ficha) { return 'Este agendamento não tem financeiro gerado.'; }
            $st = $c->prepare('SELECT l.id, l.dt_pagamento, l.extrato_id, l.valor, l.saldo,
                                      (SELECT COUNT(*) FROM extrato e WHERE e.lancamento_id = l.id) AS extratos,
                                      (SELECT COUNT(*) FROM repasse_colaborador r WHERE r.lancamento_receber_id = l.id) AS repasses
                                 FROM lancamento l WHERE l.conta_id = ?');
            $st->execute([(int) $ficha->conta_receber_id]);
            foreach ($st->fetchAll(PDO::FETCH_OBJ) as $l) {
                $recebida = !empty($l->dt_pagamento) || !empty($l->extrato_id) || (int) $l->extratos > 0
                    || ($l->saldo !== null && round((float) $l->saldo, 2) != round((float) $l->valor, 2));
                if ($recebida || (int) $l->repasses > 0) {
                    return 'A conta a receber nº ' . (int) $ficha->conta_receber_id . ' deste agendamento está quitada (total ou parcialmente). '
                        . 'Estorne/desquite o recebimento da conta a receber antes de desfazer o financeiro (não é possível se algum repasse ao colaborador já foi pago).';
                }
            }
            return null;
        });
    }

    /** Desfaz o financeiro do agendamento (conta a receber em aberto). Abre a propria transacao. */
    public static function desfazer(int $agendamentoId): void
    {
        TTransaction::open('escritorio');
        try {
            $c = TTransaction::get();
            $c->prepare('SELECT id FROM agendamento WHERE id = ? FOR UPDATE')->execute([$agendamentoId]);
            if ($motivo = self::motivoBloqueioDesfazer($agendamentoId)) { throw new Exception($motivo); }
            $ficha = self::ficha($agendamentoId);
            $conta = (int) $ficha->conta_receber_id;

            $c->exec("DELETE FROM lancamento_profissional_ajuste WHERE lancamento_profissional_id IN (SELECT id FROM lancamento_profissional WHERE lancamento_id IN (SELECT id FROM lancamento WHERE conta_id = {$conta}))");
            $c->exec("DELETE FROM lancamento_profissional WHERE lancamento_id IN (SELECT id FROM lancamento WHERE conta_id = {$conta})");
            $c->prepare('DELETE FROM agendamento_financeiro WHERE id = ?')->execute([(int) $ficha->id]);
            $c->exec("DELETE FROM lancamento WHERE conta_id = {$conta}");
            $c->exec("DELETE FROM conta_profissional WHERE conta_id = {$conta}");
            $c->exec("DELETE FROM conta WHERE id = {$conta}");

            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            throw $e;
        }
    }

    /**
     * Conta a receber gerada pelo agendamento: excluir/editar/cancelar parcela so pelo "Desfazer financeiro" do agendamento
     * (quitar e desquitar continuam pela conta). Null quando pode. Usa a transacao aberta ou abre a propria.
     */
    public static function motivoConta(int $contaId): ?string
    {
        if ($contaId <= 0) { return null; }
        return self::comTransacao(function (PDO $c) use ($contaId) {
            $st = $c->prepare('SELECT agendamento_id FROM agendamento_financeiro WHERE conta_receber_id = ?');
            $st->execute([$contaId]);
            $agendamento = $st->fetchColumn();
            if (!$agendamento) { return null; }
            return 'Esta conta foi gerada pelo financeiro do agendamento nº ' . (int) $agendamento . ' e não pode ser excluída nem alterada por aqui. '
                . 'Use "Desfazer financeiro" no agendamento (possível enquanto a conta não tiver recebimento).';
        });
    }

    private static function exigirCadastro(PDO $c, string $tabela, $id, string $mensagem): void
    {
        if (!is_numeric($id) || (int) $id <= 0) { throw new Exception($mensagem); }
        $st = $c->prepare("SELECT 1 FROM {$tabela} WHERE id = ?");
        $st->execute([(int) $id]);
        if (!$st->fetchColumn()) { throw new Exception($mensagem); }
    }

    /** Roda $fn com a transacao aberta pelo chamador, ou abre/fecha uma propria. */
    private static function comTransacao(callable $fn)
    {
        $propria = (TTransaction::get() === null);
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $r = $fn(TTransaction::get());
            if ($propria) { TTransaction::close(); }
            return $r;
        } catch (Throwable $e) {
            if ($propria) { TTransaction::rollback(); }
            throw $e;
        }
    }
}
