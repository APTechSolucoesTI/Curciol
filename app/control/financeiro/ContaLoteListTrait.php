<?php
/**
 * Acoes em lote nas listagens de Contas a pagar e Contas a receber:
 * coluna de selecao (com a soma na hora), "Quitar selecionadas", "Compensar selecionadas"
 * e "Desfazer ultimo lote" (so a ultima acao em lote do usuario, ate 30 minutos).
 * A classe que usa define tipoContaLote() (TipoConta::PAGAR ou TipoConta::RECEBER).
 */
trait ContaLoteListTrait
{
    /** Coluna de selecao: chamar antes das outras colunas. */
    protected function loteColunaSelecao(): TDataGridColumn
    {
        $todos = "<input type='checkbox' title='Marcar todas desta página' onclick=\"document.querySelectorAll('input.lote-sel').forEach(function (c) { c.checked = this.checked; }, this); loteAtualizar();\">";
        return $this->datagrid->addColumn(new TDataGridColumn('lote_sel', $todos, 'center', '1%'));
    }

    /** Botoes e barra da selecao, na esquerda do cabecalho. */
    protected function loteBotoes($headLeft): void
    {
        $quitar = new TButton('button_lote_quitar');
        $quitar->setAction(new TAction([static::class, 'onQuitarSelecionadas'], ['static' => '1']), 'Quitar selecionadas');
        $quitar->addStyleClass('btn-default');
        $quitar->setImage('fas:check-double #4CAF50');
        $this->datagrid_form->addField($quitar);

        $compensar = new TButton('button_lote_compensar');
        $compensar->setAction(new TAction([static::class, 'onCompensarSelecionadas'], ['static' => '1']), 'Compensar selecionadas');
        $compensar->addStyleClass('btn-default');
        $compensar->setImage('fas:dollar-sign #4CAF50');
        $this->datagrid_form->addField($compensar);

        $headLeft->add($quitar);
        $headLeft->add($compensar);

        $ultimo = null;
        try {
            $ultimo = LoteFinanceiroService::ultimoDesfazivel((int) TSession::getValue('userid'), (string) static::tipoContaLote());
        } catch (Throwable $e) {
            $ultimo = null;
        }
        if ($ultimo) {
            $desfazer = new TButton('button_lote_desfazer');
            $desfazer->setAction(new TAction([static::class, 'onConfirmarDesfazerLote'], ['static' => '1', 'lote_id' => (int) $ultimo->id]),
                'Desfazer último lote (' . ($ultimo->acao === 'quitar' ? 'quitação' : 'compensação') . ' · ' . (int) $ultimo->quantidade . ' · ' . LoteQuitarForm::moeda($ultimo->total)
                . ' · até ' . date('H:i', strtotime($ultimo->data_criacao . ' +' . LoteFinanceiroService::PRAZO_DESFAZER_MIN . ' minutes')) . ')');
            $desfazer->addStyleClass('btn-default');
            $desfazer->setImage('fas:undo #f44336');
            $this->datagrid_form->addField($desfazer);
            $headLeft->add($desfazer);
        }

        $barra = new TElement('div');
        $barra->id = 'lote-barra';
        $barra->class = 'lote-barra';
        $barra->add('Marque contas para quitar ou compensar de uma vez.');
        $this->datagrid_form->add($barra);
        $estilo = new TElement('style');
        $estilo->add('.lote-barra { margin:6px 0; padding:6px 10px; border-radius:6px; background:#f1f5f9; font-size:13px; color:#334155; }
                      .lote-barra.ativa { background:#ecfdf5; color:#065f46; font-weight:600; }');
        $this->datagrid_form->add($estilo);
        TScript::create("
            window.loteAtualizar = function () {
                var sel = document.querySelectorAll('input.lote-sel:checked'), q = 0, c = 0;
                sel.forEach(function (x) { q += parseFloat(x.getAttribute('data-quitar') || 0); c += parseFloat(x.getAttribute('data-compensar') || 0); });
                var b = document.getElementById('lote-barra');
                if (!b) { return; }
                var m = function (v) { return v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); };
                if (!sel.length) { b.textContent = 'Marque contas para quitar ou compensar de uma vez.'; b.className = 'lote-barra'; return; }
                b.textContent = sel.length + ' selecionada(s) · a quitar ' + m(q) + ' · a compensar ' + m(c);
                b.className = 'lote-barra ativa';
            };
        ");
    }

    /** Marca cada linha com o valor a quitar (proxima parcela) e a compensar. */
    protected function loteMarcar($objects): void
    {
        $ids = [];
        foreach ((array) $objects as $o) { $ids[] = (int) $o->id; }
        $quitar = [];
        $compensar = [];
        if ($ids) {
            foreach (LoteFinanceiroService::proximasParcelas($ids) as $contaId => $p) { $quitar[$contaId] = (float) $p->valor; }
            foreach (LoteFinanceiroService::linhasACompensar($ids) as $l) { $compensar[(int) $l->conta_id] = ($compensar[(int) $l->conta_id] ?? 0) + (float) $l->valor; }
        }
        foreach ((array) $objects as $o) {
            $id = (int) $o->id;
            $o->lote_sel = "<input type='checkbox' class='lote-sel' name='lote_sel[]' value='{$id}' data-quitar='" . number_format($quitar[$id] ?? 0, 2, '.', '')
                . "' data-compensar='" . number_format($compensar[$id] ?? 0, 2, '.', '') . "' onclick='event.stopPropagation(); loteAtualizar();'>";
        }
    }

    public static function onQuitarSelecionadas($param = null)
    {
        $ids = LoteQuitarForm::ids($param['lote_sel'] ?? []);
        if (!$ids) { new TMessage('info', 'Marque as contas que quer quitar.'); return; }
        AdiantiCoreApplication::loadPage('LoteQuitarForm', 'onShow', ['ids' => implode(',', $ids), 'tipo' => (string) static::tipoContaLote()]);
    }

    public static function onCompensarSelecionadas($param = null)
    {
        $ids = LoteQuitarForm::ids($param['lote_sel'] ?? []);
        if (!$ids) { new TMessage('info', 'Marque as contas que quer compensar.'); return; }
        AdiantiCoreApplication::loadPage('LoteCompensarForm', 'onShow', ['ids' => implode(',', $ids), 'tipo' => (string) static::tipoContaLote()]);
    }

    public static function onConfirmarDesfazerLote($param = null)
    {
        $loteId = (int) ($param['lote_id'] ?? 0);
        try {
            TTransaction::open('escritorio');
            $lote = LotePagamento::find($loteId);
            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            $lote = null;
        }
        if (!$lote) { new TMessage('error', 'Lote não encontrado.'); return; }
        $texto = ($lote->acao === 'quitar')
            ? 'Desfazer a quitação do lote nº ' . $loteId . '? As ' . (int) $lote->quantidade . ' parcela(s) voltam a ficar em aberto e as linhas saem do extrato.'
              . (((string) $lote->tipo_conta_id === (string) TipoConta::RECEBER) ? ' As contas a pagar de repasse geradas por esses recebimentos também são apagadas.' : '')
            : 'Desfazer a compensação do lote nº ' . $loteId . '? As ' . (int) $lote->quantidade . ' linha(s) do extrato voltam a ficar não compensadas.';
        new TQuestion($texto, new TAction([static::class, 'onDesfazerLote'], ['static' => '1', 'lote_id' => $loteId]));
    }

    public static function onDesfazerLote($param = null)
    {
        try {
            LoteFinanceiroService::desfazer((int) ($param['lote_id'] ?? 0), (int) TSession::getValue('userid'));
            TToast::show('success', 'Lote desfeito', 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage(static::class, 'onReload');
        } catch (Throwable $e) {
            new TMessage('error', $e->getMessage());
        }
    }
}
