<?php
/**
 * Pagamento unico (lote) das contas de repasse selecionadas em Repasses a colaboradores.
 * Uma linha no extrato por conta, agrupadas pelo lote; o lote e compensado depois no Extrato (ou ja aqui).
 */
class RepasseColaboradorPagarForm extends TWindow
{
    protected $form;
    private static $formName = 'form_RepasseColaboradorPagar';

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.55, null);
        parent::setTitle('Pagar repasses selecionados');
        parent::setProperty('class', 'window_modal');

        $ids = self::ids($param['ids'] ?? '');
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('Pagar repasses selecionados');

        try {
            TTransaction::open('escritorio');
            $linhas = $ids ? array_values(array_filter(RepasseColaboradorService::listar(['situacao' => 'aberto']), function ($r) use ($ids) {
                return in_array((int) $r->conta_pagar_id, $ids, true);
            })) : [];
            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            $linhas = [];
            new TMessage('error', $e->getMessage());
        }

        $total = array_sum(array_map(function ($r) { return (float) $r->valor; }, $linhas));
        $nomes = array_unique(array_map(function ($r) { return $r->colaborador; }, $linhas));
        $resumo = "<div class='rcp-resumo'>";
        if (!$linhas) {
            $resumo .= '<b>Nenhuma conta a pagar em aberto entre as selecionadas.</b> Atualize a lista de repasses.';
        } else {
            $resumo .= '<div><b>' . htmlspecialchars(implode(', ', $nomes)) . '</b> · ' . count($linhas) . ' conta(s) · total <b>' . RepasseColaboradorList::moeda($total) . '</b></div>';
            $resumo .= "<table class='rcp-tab'><thead><tr><th>Origem</th><th>Vencimento</th><th class='v'>Valor</th></tr></thead><tbody>";
            foreach ($linhas as $r) {
                $resumo .= '<tr><td>' . htmlspecialchars(RepasseColaboradorService::origem($r)) . '</td><td>' . ($r->vencimento ? date('d/m/Y', strtotime($r->vencimento)) : '') . "</td><td class='v'>" . RepasseColaboradorList::moeda($r->valor) . '</td></tr>';
            }
            $resumo .= '</tbody></table>';
        }
        $resumo .= "</div><style>
            .rcp-resumo { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; margin-bottom:6px; font-size:13px; }
            .rcp-resumo table { width:100%; border-collapse:collapse; margin-top:8px; font-size:12px; }
            .rcp-resumo th, .rcp-resumo td { border-bottom:1px solid #eef2f7; padding:3px 6px; text-align:left; }
            .rcp-resumo .v { text-align:right; white-space:nowrap; }
        </style>";

        $idsCampo = new THidden('ids');
        $idsCampo->setValue(implode(',', array_map(function ($r) { return (int) $r->conta_pagar_id; }, $linhas)));
        $criteriaCaixa = new TCriteria();
        $criteriaCaixa->add(new TFilter('ativo', '<>', 'N'));
        $contaCaixa = new TDBCombo('conta_caixa_id', 'escritorio', 'ContaCaixa', 'id', '{nome}', 'nome asc', $criteriaCaixa);
        $contaCaixa->enableSearch();
        $tipoPagamento = new TDBCombo('tipo_pagamento_id', 'escritorio', 'TipoPagamento', 'id', '{nome}', 'nome asc');
        $tipoPagamento->enableSearch();
        $tipoPagamento->setDefaultOption(false);
        $tipoPagamento->setValue(TipoPagamento::TRANSFERENCIA);
        $data = new TDate('data_pagamento');
        $data->setMask('dd/mm/yyyy');
        $data->setDatabaseMask('yyyy-mm-dd');
        $data->setValue(date('d/m/Y'));
        $compensado = new TCheckButton('compensado');
        $compensado->setUseSwitch(true, 'blue');
        $compensado->setIndexValue('S');
        $historico = new TEntry('historico');
        $historico->placeholder = 'Ex.: PIX de 15/10';
        foreach ([$contaCaixa, $tipoPagamento, $data, $historico] as $w) { $w->setSize('100%'); }

        $this->form->addContent([$resumo]);
        $r = $this->form->addFields([$idsCampo]);
        $r = $this->form->addFields([new TLabel('Conta-caixa:', '#ff0000', '14px', null, '100%'), $contaCaixa], [new TLabel('Tipo de pagamento:', '#ff0000', '14px', null, '100%'), $tipoPagamento]);
        $r->layout = ['col-sm-6', 'col-sm-6'];
        $r = $this->form->addFields([new TLabel('Data do pagamento:', '#ff0000', '14px', null, '100%'), $data], [new TLabel('Já compensado no banco:', null, '14px', null, '100%'), $compensado]);
        $r->layout = ['col-sm-6', 'col-sm-6'];
        $r = $this->form->addFields([new TLabel('Histórico:', null, '14px', null, '100%'), $historico]);
        $r->layout = ['col-sm-12'];
        $this->form->addContent(["<div style='color:#64748b;font-size:12px'>Cada conta vira uma linha no extrato, agrupadas no mesmo lote. Se ainda não caiu no banco, deixe sem compensar e compense o lote depois no Extrato (filtro Lote).</div>"]);

        if ($linhas) {
            $b = $this->form->addAction('Pagar ' . RepasseColaboradorList::moeda($total), new TAction([$this, 'onPagar']), 'fas:check-circle #ffffff');
            $b->addStyleClass('btn-success');
        }
        $this->form->addAction('Cancelar', new TAction([__CLASS__, 'onFechar']), 'fas:times #333');

        parent::add($this->form);
    }

    public function onShow($param = null) {}

    public function onPagar($param = null)
    {
        try {
            $d = $this->form->getData();
            $ids = self::ids($d->ids ?? '');
            $lote = RepasseColaboradorService::pagarLote($ids, [
                'data_pagamento' => self::dataBanco($d->data_pagamento ?? ''),
                'conta_caixa_id' => $d->conta_caixa_id ?? null,
                'tipo_pagamento_id' => $d->tipo_pagamento_id ?? null,
                'compensado' => (($d->compensado ?? '') === 'S') ? 'S' : 'N',
                'historico' => $d->historico ?? '',
            ]);
            TWindow::closeWindow();
            TToast::show('success', 'Pago no lote nº ' . (int) $lote->id . ' (' . RepasseColaboradorList::moeda($lote->total) . ')', 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage('RepasseColaboradorList', 'onShow');
        } catch (Throwable $e) {
            $this->form->setData($this->form->getData());
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onFechar($param = null)
    {
        TWindow::closeWindow();
    }

    private static function ids($v): array
    {
        return array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $v)))));
    }

    private static function dataBanco($v): string
    {
        $v = trim((string) $v);
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) { return "{$m[3]}-{$m[2]}-{$m[1]}"; }
        return substr($v, 0, 10);
    }
}
