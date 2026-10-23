<?php
/**
 * Quitar em lote (Contas a pagar / Contas a receber): quita a proxima parcela em aberto de cada conta marcada,
 * uma linha no extrato por parcela. Pode ser desfeito por 30 minutos (ultima acao em lote do usuario).
 */
class LoteQuitarForm extends TWindow
{
    protected $form;
    private static $formName = 'form_LoteQuitar';

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.7, null);
        parent::setTitle('Quitar contas selecionadas');
        parent::setProperty('class', 'window_modal');

        $tipo = self::tipo($param['tipo'] ?? '');
        // selecao das contas: pela sessao (todas as paginas, pode ser grande) ou pela URL
        $ids = self::ids(!empty($param['da_sessao']) ? (TSession::getValue('lote_ids_' . $tipo) ?: []) : ($param['ids'] ?? ''));
        $this->form = new BootstrapFormBuilder(self::$formName);

        $parcelas = [];
        try {
            TTransaction::open('escritorio');
            $parcelas = LoteFinanceiroService::proximasParcelas($ids);
            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
        $parcelas = array_filter($parcelas, function ($p) use ($tipo) { return (string) $p->tipo_conta_id === (string) $tipo; });
        $total = array_sum(array_map(function ($p) { return (float) $p->valor; }, $parcelas));
        $semParcela = count($ids) - count($parcelas);

        $resumo = "<div class='lote-resumo'>";
        if (!$parcelas) {
            $resumo .= '<b>Nenhuma das contas selecionadas tem parcela em aberto.</b>';
        } else {
            $resumo .= '<div><b>' . count($parcelas) . ' parcela(s)</b> · total <b>' . self::moeda($total) . '</b>'
                . ($semParcela > 0 ? " <span class='lote-nota'>({$semParcela} conta(s) sem parcela em aberto ficam de fora)</span>" : '') . '</div>';
            $resumo .= "<div class='lote-tabela'><table><thead><tr><th>Pessoa</th><th>Conta</th><th>Parcela</th><th>Vencimento</th><th class='v'>Valor</th></tr></thead><tbody>";
            foreach ($parcelas as $p) {
                $resumo .= '<tr><td>' . htmlspecialchars((string) $p->pessoa) . '</td><td>' . htmlspecialchars(mb_strimwidth((string) $p->descricao, 0, 70, '…')) . '</td><td>'
                    . (int) $p->parcela . '/' . (int) $p->total_parcelas . '</td><td>' . date('d/m/Y', strtotime($p->dt_vencimento)) . "</td><td class='v'>" . self::moeda($p->valor) . '</td></tr>';
            }
            $resumo .= '</tbody></table></div>';
        }
        $resumo .= '</div>' . self::estilo();

        $idsCampo = new THidden('ids');
        $idsCampo->setValue(implode(',', array_keys($parcelas)));
        $tipoCampo = new THidden('tipo');
        $tipoCampo->setValue($tipo);
        $criteriaCaixa = new TCriteria();
        $criteriaCaixa->add(new TFilter('ativo', '<>', 'N'));
        $contaCaixa = new TDBCombo('conta_caixa_id', 'escritorio', 'ContaCaixa', 'id', '{nome}', 'nome asc', $criteriaCaixa);
        $contaCaixa->enableSearch();
        $tipoPagamento = new TDBCombo('tipo_pagamento_id', 'escritorio', 'TipoPagamento', 'id', '{nome}', 'nome asc');
        $tipoPagamento->enableSearch();
        $data = new TDate('data_pagamento');
        $data->setMask('dd/mm/yyyy');
        $data->setDatabaseMask('yyyy-mm-dd');
        $compensado = new TCheckButton('compensado');
        $compensado->setUseSwitch(true, 'blue');
        $compensado->setIndexValue('S');
        $historico = new TEntry('historico');
        $historico->placeholder = 'Complemento do histórico (opcional)';
        foreach ([$contaCaixa, $tipoPagamento, $data, $historico] as $w) { $w->setSize('100%'); }

        // sugestoes: conta-caixa prevista comum a todas; tipo de pagamento comum (senao transferencia); hoje
        $caixas = array_unique(array_map(function ($p) { return (int) $p->conta_caixa_id; }, $parcelas));
        if (count($caixas) === 1 && reset($caixas) > 0) { $contaCaixa->setValue(reset($caixas)); }
        $tipos = array_unique(array_map(function ($p) { return (int) $p->tipo_pagamento_id; }, $parcelas));
        $tipoPagamento->setValue((count($tipos) === 1 && reset($tipos) > 0 && reset($tipos) !== (int) TipoPagamento::CHEQUE) ? reset($tipos) : TipoPagamento::TRANSFERENCIA);
        $data->setValue(date('d/m/Y'));

        $this->form->addContent([$resumo]);
        $this->form->addFields([$idsCampo, $tipoCampo]);
        $r = $this->form->addFields([new TLabel('Conta-caixa:', '#ff0000', '14px', null, '100%'), $contaCaixa], [new TLabel('Tipo de pagamento:', '#ff0000', '14px', null, '100%'), $tipoPagamento]);
        $r->layout = ['col-sm-6', 'col-sm-6'];
        $r = $this->form->addFields([new TLabel('Data da baixa:', '#ff0000', '14px', null, '100%'), $data], [new TLabel('Já compensado no banco:', null, '14px', null, '100%'), $compensado]);
        $r->layout = ['col-sm-6', 'col-sm-6'];
        $r = $this->form->addFields([new TLabel('Histórico:', null, '14px', null, '100%'), $historico]);
        $r->layout = ['col-sm-12'];
        $this->form->addContent(["<div class='lote-nota'>Cada parcela vira uma linha no extrato. Se errar, dá para desfazer este lote por " . LoteFinanceiroService::PRAZO_DESFAZER_MIN . ' minutos (botão "Desfazer último lote" na listagem).</div>']);

        if ($parcelas) {
            $b = $this->form->addAction('Quitar ' . self::moeda($total), new TAction([$this, 'onQuitar']), 'fas:check-circle #ffffff');
            $b->addStyleClass('btn-success');
        }
        $this->form->addAction('Cancelar', new TAction([__CLASS__, 'onFechar'], ['static' => '1']), 'fas:times #333');
        parent::add($this->form);
    }

    public function onShow($param = null) {}

    public function onQuitar($param = null)
    {
        try {
            $d = $this->form->getData();
            $tipo = self::tipo($d->tipo ?? '');
            $lote = LoteFinanceiroService::quitar(self::ids($d->ids ?? ''), $tipo, [
                'data_pagamento' => $d->data_pagamento ?? '',
                'conta_caixa_id' => $d->conta_caixa_id ?? null,
                'tipo_pagamento_id' => $d->tipo_pagamento_id ?? null,
                'compensado' => (($d->compensado ?? '') === 'S') ? 'S' : 'N',
                'historico' => $d->historico ?? '',
            ]);
            TWindow::closeWindow();
            call_user_func([self::lista($tipo), 'loteLimparSelecao']); // contas quitadas: a selecao recomeca
            TToast::show('success', 'Lote nº ' . (int) $lote->id . ': ' . (int) $lote->quantidade . ' parcela(s) quitada(s), ' . self::moeda($lote->total), 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage(self::lista($tipo), 'onReload');
        } catch (Throwable $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onFechar($param = null)
    {
        TWindow::closeWindow();
    }

    public static function lista(string $tipo): string
    {
        return ((string) $tipo === (string) TipoConta::RECEBER) ? 'ContaReceberList' : 'ContaPagarList';
    }

    public static function tipo($v): string
    {
        return ((string) $v === (string) TipoConta::RECEBER) ? (string) TipoConta::RECEBER : (string) TipoConta::PAGAR;
    }

    public static function ids($v): array
    {
        $v = is_array($v) ? $v : explode(',', (string) $v);
        return array_values(array_unique(array_filter(array_map('intval', $v), function ($x) { return $x > 0; })));
    }

    public static function moeda($v): string
    {
        return 'R$ ' . number_format((float) $v, 2, ',', '.');
    }

    public static function estilo(): string
    {
        return "<style>
            .lote-resumo { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; margin-bottom:8px; font-size:13px; }
            .lote-tabela { max-height:360px; overflow-y:auto; margin-top:8px; } /* todas as selecionadas, com rolagem */
            .lote-resumo table { width:100%; border-collapse:collapse; font-size:12px; }
            .lote-resumo th { position:sticky; top:0; background:#f8fafc; }
            .lote-resumo th, .lote-resumo td { border-bottom:1px solid #eef2f7; padding:3px 6px; text-align:left; }
            .lote-resumo .v { text-align:right; white-space:nowrap; }
            .lote-nota { color:#64748b; font-size:12px; }
        </style>";
    }
}
