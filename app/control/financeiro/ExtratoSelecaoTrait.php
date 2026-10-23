<?php
/**
 * Selecao de linhas do Extrato para compensar/descompensar em lote (23/10/2026), no padrao de Contas a pagar/receber:
 * a selecao fica na sessao e vale para todas as paginas ("todas" do cabecalho marca as linhas do filtro atual na hora,
 * desmarcar uma depois tira so ela), a barra soma entradas e saidas, mudar o filtro ou "Limpar filtros" desmarca tudo.
 * Usado pela listagem do Extrato e pela cortina do lote (LoteExtratoView).
 *
 * A classe que usa define:
 *  - selCriterio(): TCriteria com os filtros atuais (sem ordem nem paginacao) das linhas que podem ser marcadas;
 *  - selRecarregar(): recarrega a tela depois de compensar/descompensar.
 */
trait ExtratoSelecaoTrait
{
    /** Checkbox do cabecalho ("todas as paginas"). */
    protected static function selCabecalho(): string
    {
        $c = static::class;
        return "<input type='checkbox' id='sel-todas-{$c}' title='Marcar todas as linhas do filtro (todas as páginas)'"
            . " onclick=\"__adianti_ajax_exec('class={$c}&method=onSelTodas&marcado=' + (this.checked ? 1 : 0)); document.querySelectorAll('input.sel-{$c}').forEach(function (x) { x.checked = this.checked; }, this);\">";
    }

    /** Checkbox da linha (chamar depois de selMarcar, que diz se ela esta marcada). */
    protected static function selCheckbox($object): TElement
    {
        $c = static::class;
        $e = new TElement('input');
        $e->type = 'checkbox';
        $e->class = "sel-{$c}";
        $e->value = (int) $object->id;
        if (!empty($object->sel_marcada)) { $e->checked = 'checked'; }
        $e->onclick = "event.stopPropagation(); __adianti_ajax_exec('class={$c}&method=onSelMarcar&id=' + encodeURIComponent(this.value) + '&marcado=' + (this.checked ? 1 : 0));";
        return $e;
    }

    /** Barra com a quantidade e as somas (vai abaixo dos botoes). */
    protected static function selBarra(): TElement
    {
        $barra = new TElement('div');
        $barra->id = 'sel-barra-' . static::class;
        $barra->class = 'sel-barra';
        $barra->add('Marque linhas para compensar ou descompensar de uma vez.');
        $estilo = new TElement('style');
        $estilo->add('.sel-barra { margin:6px 0; padding:6px 10px; border-radius:6px; background:#f1f5f9; font-size:13px; color:#334155; }
                      .sel-barra.ativa { background:#ecfdf5; color:#065f46; font-weight:600; }');
        $div = new TElement('div');
        $div->add($estilo);
        $div->add($barra);
        return $div;
    }

    // ------------------------------------------------------------------ selecao na sessao

    private static function selChave(): string { return static::class . '_sel'; }

    /**
     * Assinatura do filtro atual: mudou o filtro, a selecao recomeca. Vai pelo texto do criterio (valores no lugar dos
     * parametros), nao pelo objeto: montar o SQL grava parametros com nome aleatorio no TFilter.
     */
    private static function selAssinatura(): string
    {
        $propria = !TTransaction::get();
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            return md5((clone static::selCriterio())->dump(FALSE));
        } finally {
            if ($propria) { TTransaction::close(); }
        }
    }

    protected static function selSelecao(): array
    {
        $s = TSession::getValue(self::selChave());
        $assinatura = self::selAssinatura();
        if (!is_array($s) || ($s['filtro'] ?? '') !== $assinatura) {
            $s = ['todas' => false, 'marcadas' => [], 'desmarcadas' => [], 'filtro' => $assinatura];
            TSession::setValue(self::selChave(), $s);
        }
        return $s;
    }

    public static function selLimpar(): void
    {
        TSession::setValue(self::selChave(), null);
    }

    /** Ids marcados (todas do filtro menos as desmarcadas, ou so as marcadas que ainda passam pelo filtro). */
    protected static function selIds(): array
    {
        $s = self::selSelecao();
        if (!$s['todas'] && !$s['marcadas']) { return []; }
        $propria = !TTransaction::get();
        if ($propria) { TTransaction::open('escritorio'); }
        try {
            $criteria = clone static::selCriterio();
            if (!$s['todas']) { $criteria->add(new TFilter('id', 'in', array_map('intval', array_keys($s['marcadas'])))); }
            $sql = new TSqlSelect;
            $sql->setEntity('extrato');
            $sql->addColumn('id');
            $sql->setCriteria($criteria);
            $st = TTransaction::get()->prepare($sql->getInstruction(TRUE));
            $st->execute($criteria->getPreparedVars() ?: []);
            $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        } finally {
            if ($propria) { TTransaction::close(); }
        }
        return $s['todas'] ? array_values(array_diff($ids, array_map('intval', array_keys($s['desmarcadas'])))) : $ids;
    }

    /** Quantidade e somas das linhas: [quantidade, entradas, saidas]. Usa a transacao aberta. */
    protected static function selSomas(array $ids, string $situacao = ''): array
    {
        if (!$ids) { return [0, 0.0, 0.0]; }
        $where = 'id IN (' . implode(',', array_map('intval', $ids)) . ')';
        if ($situacao === 'S') { $where .= " AND compensado = 'S'"; }
        if ($situacao === 'N') { $where .= " AND COALESCE(compensado, 'N') <> 'S'"; }
        $r = TTransaction::get()->query("SELECT COUNT(*), COALESCE(SUM(entrada_valor), 0), COALESCE(SUM(saida_valor), 0) FROM extrato WHERE {$where}")->fetch(PDO::FETCH_NUM);
        return [(int) $r[0], round((float) $r[1], 2), round((float) $r[2], 2)];
    }

    /** Ids das linhas marcadas numa situacao (S = compensadas, N = nao compensadas). */
    private static function selIdsNaSituacao(array $ids, string $situacao): array
    {
        if (!$ids) { return []; }
        $cond = $situacao === 'S' ? "compensado = 'S'" : "COALESCE(compensado, 'N') <> 'S'";
        return array_map('intval', TTransaction::get()->query('SELECT id FROM extrato WHERE id IN (' . implode(',', array_map('intval', $ids)) . ") AND {$cond} ORDER BY id")->fetchAll(PDO::FETCH_COLUMN));
    }

    protected static function selMoeda($v): string
    {
        return 'R$ ' . number_format((float) $v, 2, ',', '.');
    }

    /** Atualiza a barra e o checkbox do cabecalho. */
    protected static function selScriptBarra(): void
    {
        $ids = self::selIds();
        $s = self::selSelecao();
        $texto = 'Marque linhas para compensar ou descompensar de uma vez.';
        if ($ids) {
            $propria = !TTransaction::get();
            if ($propria) { TTransaction::open('escritorio'); }
            [$n, $entradas, $saidas] = self::selSomas($ids);
            [$nComp] = self::selSomas($ids, 'S');
            if ($propria) { TTransaction::close(); }
            $texto = $n . ' selecionada(s)' . ($s['todas'] ? ' (todas as páginas)' : '') . ' · entradas ' . self::selMoeda($entradas)
                . ' · saídas ' . self::selMoeda($saidas) . ' · ' . ($n - $nComp) . ' a compensar · ' . $nComp . ' compensada(s)';
        }
        $c = static::class;
        TScript::create('(function () { var b = document.getElementById(' . json_encode("sel-barra-{$c}") . '); if (b) { b.textContent = ' . json_encode($texto)
            . '; b.className = "sel-barra' . ($ids ? ' ativa' : '') . '"; } var t = document.getElementById(' . json_encode("sel-todas-{$c}") . '); if (t) { t.checked = '
            . ($s['todas'] && !$s['desmarcadas'] ? 'true' : 'false') . '; } })();');
    }

    /** Marca as linhas da pagina que estao na selecao e atualiza a barra (chamar no onReload). */
    protected static function selMarcar($objects): void
    {
        $s = self::selSelecao();
        foreach ((array) $objects as $o) {
            $id = (int) $o->id;
            $o->sel_marcada = $s['todas'] ? !isset($s['desmarcadas'][$id]) : isset($s['marcadas'][$id]);
        }
        self::selScriptBarra();
    }

    public static function onSelMarcar($param = null)
    {
        $id = (int) ($param['id'] ?? 0);
        $marcar = !empty($param['marcado']);
        $s = self::selSelecao();
        if ($id > 0) {
            if ($s['todas']) {
                if ($marcar) { unset($s['desmarcadas'][$id]); } else { $s['desmarcadas'][$id] = 1; }
            } else {
                if ($marcar) { $s['marcadas'][$id] = 1; } else { unset($s['marcadas'][$id]); }
            }
            TSession::setValue(self::selChave(), $s);
        }
        self::selScriptBarra();
    }

    public static function onSelTodas($param = null)
    {
        $s = self::selSelecao();
        $s['todas'] = !empty($param['marcado']);
        $s['marcadas'] = [];
        $s['desmarcadas'] = [];
        TSession::setValue(self::selChave(), $s);
        self::selScriptBarra();
    }

    // ------------------------------------------------------------------ acoes

    /** Compensar: so as marcadas ainda nao compensadas vao para a janela (a data da compensacao e pedida la). */
    public static function onCompensarSelecionadas($param = null)
    {
        try {
            $ids = self::selIds();
            TTransaction::open('escritorio');
            $pendentes = self::selIdsNaSituacao($ids, 'N');
            TTransaction::close();
            if (!$ids) { new TMessage('info', 'Marque as linhas que quer compensar.'); return; }
            if (!$pendentes) { new TMessage('info', 'Todas as linhas marcadas já estão compensadas.'); return; }
            TSession::setValue('paramSelecionados', $pendentes);
            TSession::setValue('paramSelecionados_origem', static::class);
            TApplication::loadPage('ModalCompensarExtrato', 'onShow');
        } catch (Throwable $e) {
            while (TTransaction::get()) { TTransaction::rollback(); }
            new TMessage('error', $e->getMessage());
        }
    }

    /** Descompensar: confirma com a quantidade e as somas das marcadas que estao compensadas. */
    public static function onDescompensarSelecionadas($param = null)
    {
        try {
            $ids = self::selIds();
            if (!$ids) { new TMessage('info', 'Marque as linhas que quer descompensar.'); return; }
            TTransaction::open('escritorio');
            [$n, $entradas, $saidas] = self::selSomas($ids, 'S');
            TTransaction::close();
            if ($n === 0) { new TMessage('info', 'Nenhuma das linhas marcadas está compensada.'); return; }
            new TQuestion("Descompensar {$n} linha(s)? Entradas " . self::selMoeda($entradas) . ' · saídas ' . self::selMoeda($saidas)
                . '. As linhas marcadas que não estão compensadas ficam como estão.',
                new TAction([static::class, 'onDescompensarConfirmado'], ['static' => '1']));
        } catch (Throwable $e) {
            while (TTransaction::get()) { TTransaction::rollback(); }
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onDescompensarConfirmado($param = null)
    {
        try {
            $ids = self::selIds();
            TTransaction::open('escritorio');
            $contasAfetadas = [];
            foreach (self::selIdsNaSituacao($ids, 'S') as $id) {
                $aux = Extrato::find($id);
                $aux->modificacao_user_id = TSession::getValue('userid');
                $aux->compensado = 'N';
                $aux->data_compensacao = null;
                $aux->ano = null;
                $aux->mes = null;
                $aux->ano_mes = null;
                $aux->store();
                $contasAfetadas[] = $aux->conta_caixa_id;
            }
            ContaCaixaSaldoService::recalcular($contasAfetadas);
            TTransaction::close();
            self::selLimpar();
            TToast::show('success', 'Descompensação concluída', 'topRight', 'far:check-circle');
            static::selRecarregar();
        } catch (Throwable $e) {
            while (TTransaction::get()) { TTransaction::rollback(); }
            new TMessage('error', $e->getMessage());
        }
    }
}
