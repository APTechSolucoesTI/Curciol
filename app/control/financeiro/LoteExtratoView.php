<?php
/**
 * Cortina "Ver lote" do Extrato (23/10/2026): dados do lote no topo, desfazer (a ultima acao em lote do proprio usuario,
 * ate 30 minutos, mesma regra da tela de contas) e a lista das contas do lote, cada uma podendo ser aberta e, se ainda
 * nao compensada, compensada. No Extrato o lote aparece numa linha so (ExtratoLoteService).
 * Parametro: lote_id.
 */
class LoteExtratoView extends TPage
{
    private static $database = 'escritorio';

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');
        $loteId = (int) ($param['lote_id'] ?? 0);

        $form = new BootstrapFormBuilder('form_LoteExtratoView');
        $form->setFormTitle("Lote nº {$loteId}");

        try {
            TTransaction::open(self::$database);
            $lote = LotePagamento::find($loteId);
            if (!$lote) { throw new Exception('Lote não encontrado.'); }
            $itens = self::itens($loteId);
            $compensacoes = self::lotesDeCompensacao(array_filter(array_map(function ($i) { return (int) $i->extrato_id; }, $itens)));
            $caixa = $lote->conta_caixa_id ? ContaCaixa::find($lote->conta_caixa_id) : null;
            $tipoPagamento = $lote->tipo_pagamento_id ? TipoPagamento::find($lote->tipo_pagamento_id) : null;
            TTransaction::close();

            $usuario = '';
            if ($lote->criacao_user_id) {
                TTransaction::open('permission');
                $u = SystemUsers::find((int) $lote->criacao_user_id);
                $usuario = $u ? $u->name : '';
                TTransaction::close();
            }

            $form->addContent([self::cabecalho($lote, $itens, $compensacoes, $caixa, $tipoPagamento, $usuario)]);
            $form->addContent([self::lista($itens)]);

            $ultimo = LoteFinanceiroService::ultimoDesfazivel((int) TSession::getValue('userid'), (string) $lote->tipo_conta_id);
            if ($ultimo && (int) $ultimo->id === $loteId) {
                $ate = date('H:i', strtotime($lote->data_criacao . ' +' . LoteFinanceiroService::PRAZO_DESFAZER_MIN . ' minutes'));
                $b = $form->addHeaderAction("Desfazer lote (até {$ate})", new TAction([__CLASS__, 'onConfirmarDesfazer'], ['static' => '1', 'lote_id' => $loteId]), 'fas:undo #f44336');
                $b->class = 'btn btn-sm btn-default';
            }
        } catch (Throwable $e) {
            while (TTransaction::get()) { TTransaction::rollback(); }
            $erro = new TElement('div');
            $erro->class = 'alert alert-danger';
            $erro->add(htmlspecialchars($e->getMessage()));
            $form->addContent([$erro]);
        }

        $fechar = new TButton('closeCurtain');
        $fechar->class = 'btn btn-sm btn-default';
        $fechar->style = 'margin-right:10px;';
        $fechar->onClick = 'Template.closeRightPanel();';
        $fechar->setLabel('Fechar');
        $fechar->setImage('fas:times');
        $form->addHeaderWidget($fechar);

        parent::add($form);
        $estilo = new TStyle('right-panel > .container-part[page-name=LoteExtratoView]');
        $estilo->width = '60% !important';
        $estilo->show(true);
    }

    public function onShow($param = null) {}

    /** Contas do lote (uma por parcela quitada), com a situacao da linha no extrato. */
    private static function itens(int $loteId): array
    {
        $st = TTransaction::get()->prepare(
            "SELECT i.extrato_id, i.valor, l.parcela, c.id AS conta_id, c.descricao, c.total_parcelas, c.numero_documento,
                    p.nome AS pessoa, x.id AS x_id, x.compensado, x.data_compensacao
               FROM lote_pagamento_item i
               JOIN lancamento l ON l.id = i.lancamento_id
               JOIN conta c ON c.id = l.conta_id
               LEFT JOIN pessoa p ON p.id = c.pessoa_id
               LEFT JOIN extrato x ON x.id = i.extrato_id
              WHERE i.lote_pagamento_id = ?
              ORDER BY p.nome, c.id, l.parcela");
        $st->execute([$loteId]);
        return $st->fetchAll(PDO::FETCH_OBJ);
    }

    /** Lotes de compensacao que mexeram nas linhas deste lote. */
    private static function lotesDeCompensacao(array $extratoIds): array
    {
        if (!$extratoIds) { return []; }
        $st = TTransaction::get()->query(
            "SELECT DISTINCT lp.id, lp.data_criacao, lp.desfeito_em FROM lote_pagamento_item i
               JOIN lote_pagamento lp ON lp.id = i.lote_pagamento_id AND lp.acao = 'compensar'
              WHERE i.extrato_id IN (" . implode(',', array_map('intval', $extratoIds)) . ") ORDER BY lp.id");
        return $st->fetchAll(PDO::FETCH_OBJ);
    }

    private static function cabecalho($lote, array $itens, array $compensacoes, $caixa, $tipoPagamento, string $usuario): TElement
    {
        $h = function ($v) { return htmlspecialchars((string) $v); };
        $ativos = array_filter($itens, function ($i) { return !empty($i->x_id); });
        $comp = count(array_filter($ativos, function ($i) { return ($i->compensado ?? 'N') === 'S'; }));
        $situacao = !$ativos ? 'Quitação desfeita' : ($comp === 0 ? 'Não compensado' : ($comp === count($ativos) ? 'Compensado' : "Compensado em parte ({$comp} de " . count($ativos) . ')'));
        $linhas = [
            'Ação' => ($lote->acao === 'compensar' ? 'Compensação' : 'Quitação') . ((string) $lote->tipo_conta_id === (string) TipoConta::RECEBER ? ' · contas a receber' : ' · contas a pagar'),
            'Data' => $lote->data_criacao ? date('d/m/Y H:i', strtotime($lote->data_criacao)) : '',
            'Usuário' => $usuario,
            'Data do pagamento' => $lote->data_pagamento ? date('d/m/Y', strtotime($lote->data_pagamento)) : '',
            'Conta-caixa' => $caixa ? $caixa->nome : '',
            'Tipo de pagamento' => $tipoPagamento ? $tipoPagamento->nome : '',
            'Histórico' => trim((string) $lote->historico) !== '' ? $lote->historico : '—',
            'Contas' => (int) $lote->quantidade,
            'Total' => LoteQuitarForm::moeda($lote->total),
            'Compensação' => $situacao,
        ];
        if ($compensacoes) {
            $linhas['Lotes de compensação'] = implode(', ', array_map(function ($c) {
                return 'nº ' . (int) $c->id . ' (' . date('d/m/Y H:i', strtotime($c->data_criacao)) . ($c->desfeito_em ? ', desfeito' : '') . ')';
            }, $compensacoes));
        }
        if ($lote->desfeito_em) { $linhas['Desfeito em'] = date('d/m/Y H:i', strtotime($lote->desfeito_em)); }

        $html = "<style>
            .lote-cab { display:grid; grid-template-columns:repeat(auto-fill, minmax(210px, 1fr)); gap:8px 16px; margin-bottom:14px; }
            .lote-cab div span { display:block; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:.03em; }
            .lote-cab div b { font-size:13px; color:#1e293b; }
            .lote-contas { width:100%; border-collapse:collapse; font-size:13px; }
            .lote-contas th { background:#0d4069; color:#fff; padding:6px 8px; text-align:left; font-weight:600; }
            .lote-contas td { border:1px solid #dbe3ec; padding:5px 8px; vertical-align:middle; }
            .lote-contas tr:nth-child(even) td { background:#f2f5f9; }
            .lote-contas .num { text-align:right; white-space:nowrap; }
            .lote-contas .acoes { white-space:nowrap; text-align:center; }
            .lote-contas .acoes a { margin:0 4px; }
        </style><div class='lote-cab'>";
        foreach ($linhas as $rotulo => $valor) { $html .= '<div><span>' . $h($rotulo) . '</span><b>' . $h($valor) . '</b></div>'; }
        $html .= '</div>';
        $div = new TElement('div');
        $div->add($html);
        return $div;
    }

    private static function lista(array $itens): TElement
    {
        $h = function ($v) { return htmlspecialchars((string) $v); };
        $html = "<table class='lote-contas'><thead><tr><th>Pessoa</th><th>Conta</th><th>Parcela</th><th class='num'>Valor</th><th>Compensado</th><th>Ações</th></tr></thead><tbody>";
        foreach ($itens as $i) {
            $conta = (int) $i->conta_id;
            $abrir = "<a generator='adianti' href='index.php?class=ContaFormView&method=onShow&key={$conta}&id={$conta}' title='Abrir a conta'><i class='fas fa-search' style='color:#00BCD4'></i> Abrir</a>";
            if (empty($i->x_id)) {
                $situacao = '<span class="label label-default">Quitação desfeita</span>';
                $compensar = '';
            } elseif (($i->compensado ?? 'N') === 'S') {
                $situacao = '<span class="label label-success">Sim</span> ' . ($i->data_compensacao ? date('d/m/Y', strtotime($i->data_compensacao)) : '');
                $compensar = '';
            } else {
                $situacao = '<span class="label label-danger">Não</span>';
                $compensar = "<a href='#' onclick=\"__adianti_ajax_exec('class=LoteExtratoView&method=onCompensarConta&extrato_id=" . (int) $i->x_id . "'); return false;\" title='Compensar esta conta'><i class='fas fa-dollar-sign' style='color:#4CAF50'></i> Compensar</a>";
            }
            $html .= '<tr><td>' . $h($i->pessoa) . '</td><td>' . $h(trim(($i->descricao ?: '') . ($i->numero_documento ? ' · ' . $i->numero_documento : '')) ?: "Conta {$conta}") . '</td>'
                . '<td>' . (int) $i->parcela . ($i->total_parcelas ? '/' . (int) $i->total_parcelas : '') . '</td>'
                . "<td class='num'>" . $h(LoteQuitarForm::moeda($i->valor)) . '</td><td>' . $situacao . "</td><td class='acoes'>{$abrir}{$compensar}</td></tr>";
        }
        $html .= '</tbody></table>';
        $div = new TElement('div');
        $div->style = 'overflow-x:auto';
        $div->add($html);
        return $div;
    }

    /** Compensar uma conta do lote: mesma janela de compensacao do Extrato. */
    public static function onCompensarConta($param = null)
    {
        $id = (int) ($param['extrato_id'] ?? 0);
        if ($id <= 0) { return; }
        TSession::setValue('paramSelecionados', [$id]);
        TApplication::loadPage('ModalCompensarExtrato', 'onShow');
    }

    public static function onConfirmarDesfazer($param = null)
    {
        $loteId = (int) ($param['lote_id'] ?? 0);
        new TQuestion("Desfazer o lote nº {$loteId}? As parcelas voltam a ficar em aberto e as linhas saem do extrato.",
            new TAction([__CLASS__, 'onDesfazer'], ['static' => '1', 'lote_id' => $loteId]));
    }

    public static function onDesfazer($param = null)
    {
        try {
            LoteFinanceiroService::desfazer((int) ($param['lote_id'] ?? 0), (int) TSession::getValue('userid'));
            TToast::show('success', 'Lote desfeito', 'topRight', 'far:check-circle');
            Navegacao::voltarParaLista('ExtratoList', __CLASS__);
        } catch (Throwable $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
