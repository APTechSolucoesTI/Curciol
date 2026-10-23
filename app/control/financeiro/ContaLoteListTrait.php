<?php
/**
 * Acoes em lote nas listagens de Contas a pagar e Contas a receber:
 * coluna de selecao (com a soma na hora), "Quitar selecionadas", "Compensar selecionadas"
 * e "Desfazer ultimo lote" (so a ultima acao em lote do usuario, ate 30 minutos).
 * A classe que usa define tipoContaLote() (TipoConta::PAGAR ou TipoConta::RECEBER).
 */
trait ContaLoteListTrait
{
    /**
     * Coluna de selecao: chamar antes das outras colunas.
     * A selecao fica no servidor (sessao), valendo para todas as paginas: o "todas" do cabecalho marca todas as contas
     * do filtro atual na hora (sem percorrer linhas); desmarcar uma depois tira so ela. Mudar o filtro limpa a selecao.
     */
    protected function loteColunaSelecao(): TDataGridColumn
    {
        $classe = static::class;
        $todos = "<input type='checkbox' id='lote-todas' title='Marcar todas as contas do filtro (todas as páginas)' onclick=\"loteMarcarTodas(this.checked)\">";
        $coluna = new TDataGridColumn('id', $todos, 'center', '1%');
        // a grade escapa valores simples: o checkbox sai pelo transformer
        $coluna->setTransformer(function ($value, $object) {
            $c = new TElement('input');
            $c->type = 'checkbox';
            $c->class = 'lote-sel';
            $c->name = 'lote_sel[]';
            $c->value = (int) $object->id;
            if (!empty($object->lote_marcada)) { $c->checked = 'checked'; }
            $c->onclick = 'event.stopPropagation(); loteMarcarUma(this);';
            return $c;
        });
        TScript::create("
            window.loteMarcarUma = function (c) {
                __adianti_ajax_exec('class={$classe}&method=onLoteMarcar&id=' + encodeURIComponent(c.value) + '&marcado=' + (c.checked ? 1 : 0));
            };
            window.loteMarcarTodas = function (marcar) {
                document.querySelectorAll('input.lote-sel').forEach(function (c) { c.checked = marcar; });
                __adianti_ajax_exec('class={$classe}&method=onLoteTodas&marcado=' + (marcar ? 1 : 0));
            };
        ");
        return $this->datagrid->addColumn($coluna);
    }

    // ------------------------------------------------------------------ selecao no servidor (todas as paginas)

    private static function loteChave(): string { return static::class . '_lote_sel'; }

    /**
     * Assinatura do filtro atual: a selecao vale para um filtro; mudou o filtro, ela recomeca.
     * Vai pelo texto do filtro (valores no lugar dos parametros), nao pelo objeto: montar o SQL grava no TFilter
     * parametros com nome aleatorio, e o serialize mudava a cada clique, zerando a selecao.
     */
    private static function loteAssinaturaFiltro(): string
    {
        $partes = [];
        foreach ((array) (TSession::getValue(static::class . '_filters') ?: []) as $f) {
            $partes[] = is_object($f) && method_exists($f, 'dump') ? (clone $f)->dump(FALSE) : serialize($f);
        }
        return md5(implode("\n", $partes));
    }

    /** Selecao atual: todas (com as desmarcadas) ou so as marcadas. */
    protected static function loteSelecao(): array
    {
        $s = TSession::getValue(self::loteChave());
        if (!is_array($s) || ($s['filtro'] ?? '') !== self::loteAssinaturaFiltro()) {
            $s = ['todas' => false, 'marcadas' => [], 'desmarcadas' => [], 'filtro' => self::loteAssinaturaFiltro()];
            TSession::setValue(self::loteChave(), $s);
        }
        return $s;
    }

    public static function loteLimparSelecao(): void
    {
        TSession::setValue(self::loteChave(), null);
    }

    /** Filtros fixos da listagem (unidade do usuario e tipo da conta), os mesmos do construtor. */
    protected static function loteCriterioBase(): TCriteria
    {
        $c = new TCriteria;
        $c->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '" . SqlSeguro::id(TSession::getValue('userunitid')) . "')"));
        $c->add(new TFilter('tipo_conta_id', '=', static::tipoContaLote()));
        return $c;
    }

    /** Ids das contas selecionadas (todas do filtro atual menos as desmarcadas, ou so as marcadas). */
    protected static function loteIdsSelecionados(): array
    {
        $s = self::loteSelecao();
        if (!$s['todas']) { return array_map('intval', array_keys($s['marcadas'])); }
        $propria = !TTransaction::get();
        if ($propria) { TTransaction::open('escritorio'); }
        $criteria = self::loteCriterioBase();
        foreach ((array) (TSession::getValue(static::class . '_filters') ?: []) as $f) { $criteria->add($f); }
        $sql = new TSqlSelect;
        $sql->setEntity('conta');
        $sql->addColumn('id');
        $sql->setCriteria($criteria);
        $st = TTransaction::get()->prepare($sql->getInstruction(TRUE));
        $st->execute($criteria->getPreparedVars() ?: []);
        $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        if ($propria) { TTransaction::close(); }
        return array_values(array_diff($ids, array_map('intval', array_keys($s['desmarcadas']))));
    }

    /** Barra da selecao: quantas, quanto a quitar e quanto a compensar (somado no servidor). */
    protected static function loteScriptBarra(): void
    {
        $ids = self::loteIdsSelecionados();
        $q = 0.0; $c = 0.0;
        if ($ids) {
            foreach (LoteFinanceiroService::proximasParcelas($ids) as $p) { $q += (float) $p->valor; }
            foreach (LoteFinanceiroService::linhasACompensar($ids) as $l) { $c += (float) $l->valor; }
        }
        $s = self::loteSelecao();
        $texto = $ids ? (count($ids) . ' selecionada(s)' . ($s['todas'] ? ' (todas as páginas)' : '') . ' · a quitar ' . LoteQuitarForm::moeda($q) . ' · a compensar ' . LoteQuitarForm::moeda($c))
                      : 'Marque contas para quitar ou compensar de uma vez.';
        TScript::create('(function () { var b = document.getElementById("lote-barra"); if (b) { b.textContent = ' . json_encode($texto) . '; b.className = "lote-barra' . ($ids ? ' ativa' : '') . '"; }'
            . ' var t = document.getElementById("lote-todas"); if (t) { t.checked = ' . ($s['todas'] && !$s['desmarcadas'] ? 'true' : 'false') . '; } })();');
    }

    public static function onLoteMarcar($param = null)
    {
        $id = (int) ($param['id'] ?? 0);
        $marcar = !empty($param['marcado']);
        $s = self::loteSelecao();
        if ($id > 0) {
            if ($s['todas']) {
                if ($marcar) { unset($s['desmarcadas'][$id]); } else { $s['desmarcadas'][$id] = 1; }
            } else {
                if ($marcar) { $s['marcadas'][$id] = 1; } else { unset($s['marcadas'][$id]); }
            }
            TSession::setValue(self::loteChave(), $s);
        }
        self::loteScriptBarra();
    }

    public static function onLoteTodas($param = null)
    {
        $s = self::loteSelecao();
        $s['todas'] = !empty($param['marcado']);
        $s['marcadas'] = [];
        $s['desmarcadas'] = [];
        TSession::setValue(self::loteChave(), $s);
        self::loteScriptBarra();
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
    }

    /** Marca as linhas da pagina que estao na selecao e atualiza a barra (chamado no onReload). */
    protected function loteMarcar($objects): void
    {
        $s = self::loteSelecao();
        foreach ((array) $objects as $o) {
            $id = (int) $o->id;
            $o->lote_marcada = $s['todas'] ? !isset($s['desmarcadas'][$id]) : isset($s['marcadas'][$id]);
        }
        self::loteScriptBarra();
    }

    /** Selecao vai para a janela pela sessao (pode ter centenas de contas, nao cabe na URL). */
    private static function loteAbrir(string $janela, string $vazio): void
    {
        $ids = self::loteIdsSelecionados();
        if (!$ids) { new TMessage('info', $vazio); return; }
        $tipo = (string) static::tipoContaLote();
        TSession::setValue('lote_ids_' . $tipo, $ids);
        AdiantiCoreApplication::loadPage($janela, 'onShow', ['da_sessao' => '1', 'tipo' => $tipo]);
    }

    public static function onQuitarSelecionadas($param = null)
    {
        self::loteAbrir('LoteQuitarForm', 'Marque as contas que quer quitar.');
    }

    public static function onCompensarSelecionadas($param = null)
    {
        self::loteAbrir('LoteCompensarForm', 'Marque as contas que quer compensar.');
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
