<?php
/**
 * Financeiro > Repasses a colaboradores.
 * Contas a pagar geradas para os colaboradores a cada recebimento com repasse (RepasseColaboradorService).
 * Filtros (cortina), totais por colaborador, selecao com soma, pagamento unico (lote), estorno do lote e relatorio.
 */
class RepasseColaboradorList extends TPage
{
    private static $formFiltros = 'formFiltros_RepasseColaborador';
    const LIMITE = 500;

    public function __construct($param = null)
    {
        parent::__construct();
        if (!empty($param['target_container'])) { $this->adianti_target_container = $param['target_container']; }

        $f = self::filtrosAtuais();
        $container = new TVBox;
        $container->style = 'width:100%';
        if (empty($param['target_container'])) { $container->add(TBreadCrumb::create(['Financeiro', 'Repasses a colaboradores'])); }

        $painel = new TPanelGroup('Repasses a colaboradores');
        $painel->getBody()->style = 'overflow-x:auto';

        // botoes do topo (padrao das listagens)
        $topo = new TElement('div');
        $topo->class = 'rc-topo';
        $qtdFiltros = count(array_filter([$f['pessoa_id'], $f['processo'], $f['requisicao_id'], $f['contrato'], $f['lote_id'], $f['data_de'], $f['data_ate']], function ($v) { return $v !== null && $v !== ''; }))
            + (($f['situacao'] ?? '') !== 'aberto' ? 1 : 0);
        $topo->add(self::botao('Filtros' . ($qtdFiltros ? " <span class='badge badge-success'>{$qtdFiltros}</span>" : ''), 'fas fa-filter', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onShowCurtainFilters&static=1')"));
        $topo->add(self::botao('Limpar filtros', 'fas fa-eraser', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onClearFilters')", '#f44336'));
        $topo->add(self::botao('Atualizar', 'fas fa-sync-alt', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onShow')", '#03a9f4'));
        $topo->add(self::botao('Relatório (PDF)', 'far fa-file-pdf', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onRelatorioPdf&static=1')", '#D32F2F'));
        $topo->add(self::botao('Relatório (Excel)', 'far fa-file-excel', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onRelatorioXls&static=1')", '#218838'));
        $painel->add($topo);

        try {
            TTransaction::open('escritorio');
            $linhas = RepasseColaboradorService::listar($f);
            $descricaoFiltros = RepasseColaboradorRelatorioService::descreverFiltros($f);
            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            $linhas = [];
            $descricaoFiltros = '';
            new TMessage('error', $e->getMessage());
        }

        $painel->add("<div class='rc-filtros'>Filtros: " . htmlspecialchars($descricaoFiltros) . '</div>');
        $painel->add(self::resumo($linhas));
        $painel->add("<div class='rc-selecao' id='rc-selecao'><span id='rc-sel-texto'>Marque as contas a pagar de um colaborador para pagar de uma vez.</span>"
            . "<button type='button' class='btn btn-sm btn-primary' id='rc-btn-pagar' disabled onclick='rcPagar()'><i class='fas fa-money-check-alt'></i> Pagar selecionadas</button></div>");
        $painel->add(self::tabela(array_slice($linhas, 0, self::LIMITE)));
        if (count($linhas) > self::LIMITE) {
            $painel->add("<div class='rc-filtros'>Mostrando os primeiros " . self::LIMITE . ' de ' . count($linhas) . ' repasses. Use os filtros para refinar (o relatório traz todos).</div>');
        }

        $container->add($painel);
        $container->add(self::estilo());
        parent::add($container);
    }

    public function onShow($param = null) {}

    // ------------------------------------------------------------------ filtros

    private static function filtrosAtuais(): array
    {
        $f = TSession::getValue(__CLASS__ . '_filtros');
        $padrao = ['pessoa_id' => null, 'processo' => '', 'requisicao_id' => null, 'contrato' => '', 'situacao' => 'aberto',
                   'periodo_tipo' => 'recebimento', 'data_de' => null, 'data_ate' => null, 'lote_id' => null];
        return is_array($f) ? $f + $padrao : $padrao;
    }

    public static function onShowCurtainFilters($param = null)
    {
        $f = self::filtrosAtuais();
        $form = new BootstrapFormBuilder(self::$formFiltros);
        $form->setFormTitle('Filtros de repasses');

        $criteria = new TCriteria();
        $criteria->add(new TFilter('id', 'in', '(SELECT DISTINCT pessoa_id FROM lancamento_profissional)'));
        $pessoa = new TDBUniqueSearch('pessoa_id', 'escritorio', 'Pessoa', 'id', 'nome', 'nome asc', $criteria);
        $pessoa->setMinLength(0);
        $pessoa->setMask('{nome}');
        $processo = new TEntry('processo');
        $requisicao = new TEntry('requisicao_id');
        $contrato = new TEntry('contrato');
        $lote = new TEntry('lote_id');
        $situacao = new TCombo('situacao');
        $situacao->addItems(['aberto' => 'A pagar', 'pago' => 'Pagos', 'todos' => 'Todos']);
        $periodoTipo = new TCombo('periodo_tipo');
        $periodoTipo->addItems(['recebimento' => 'Data do recebimento', 'vencimento' => 'Data do vencimento', 'pagamento' => 'Data do pagamento']);
        $dataDe = new TDate('data_de');
        $dataAte = new TDate('data_ate');
        foreach ([$dataDe, $dataAte] as $d) { $d->setMask('dd/mm/yyyy'); $d->setDatabaseMask('yyyy-mm-dd'); }
        foreach ([$pessoa, $processo, $requisicao, $contrato, $lote, $situacao, $periodoTipo, $dataDe, $dataAte] as $w) { $w->setSize('100%'); }
        $periodoTipo->setDefaultOption(false);
        $situacao->setDefaultOption(false);

        $r = $form->addFields([new TLabel('Colaborador'), $pessoa]);
        $r->layout = ['col-sm-12'];
        $r = $form->addFields([new TLabel('Processo'), $processo], [new TLabel('Requisição nº'), $requisicao], [new TLabel('Contrato'), $contrato]);
        $r->layout = ['col-sm-6', 'col-sm-3', 'col-sm-3'];
        $r = $form->addFields([new TLabel('Situação'), $situacao], [new TLabel('Lote nº'), $lote]);
        $r->layout = ['col-sm-6', 'col-sm-6'];
        $r = $form->addFields([new TLabel('Período por'), $periodoTipo], [new TLabel('De'), $dataDe], [new TLabel('Até'), $dataAte]);
        $r->layout = ['col-sm-6', 'col-sm-3', 'col-sm-3'];

        $dados = (object) $f;
        $dados->situacao = $f['situacao'] ?: 'todos';
        $form->setData($dados);

        $b = $form->addAction('Buscar', new TAction([__CLASS__, 'onSearch']), 'fas:search #ffffff');
        $b->addStyleClass('btn-primary');
        $fechar = new TButton('closeCurtain');
        $fechar->class = 'btn btn-sm btn-default';
        $fechar->style = 'margin-right:10px;';
        $fechar->onClick = 'Template.closeRightPanel();';
        $fechar->setLabel('Fechar');
        $fechar->setImage('fas:times');
        $form->addHeaderWidget($fechar);

        $page = new TPage();
        $page->setTargetContainer('adianti_right_panel');
        $page->setProperty('page-name', 'RepasseColaboradorFiltros');
        $page->setProperty('page_name', 'RepasseColaboradorFiltros');
        $page->adianti_target_container = 'adianti_right_panel';
        $page->target_container = 'adianti_right_panel';
        $page->add($form);
        $page->setIsWrapped(true);
        $page->show();
    }

    public function onSearch($param = null)
    {
        $int = function ($v) { $v = preg_replace('/\D/', '', (string) $v); return $v === '' ? null : (int) $v; };
        $data = function ($v) {
            $v = trim((string) $v);
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) { return "{$m[3]}-{$m[2]}-{$m[1]}"; }
            return preg_match('/^\d{4}-\d{2}-\d{2}/', $v) ? substr($v, 0, 10) : null;
        };
        $situacao = $param['situacao'] ?? 'aberto';
        TSession::setValue(__CLASS__ . '_filtros', [
            'pessoa_id' => $int($param['pessoa_id'] ?? null),
            'processo' => trim((string) ($param['processo'] ?? '')),
            'requisicao_id' => $int($param['requisicao_id'] ?? null),
            'contrato' => trim((string) ($param['contrato'] ?? '')),
            'lote_id' => $int($param['lote_id'] ?? null),
            'situacao' => in_array($situacao, ['aberto', 'pago'], true) ? $situacao : '',
            'periodo_tipo' => in_array($param['periodo_tipo'] ?? '', ['recebimento', 'vencimento', 'pagamento'], true) ? $param['periodo_tipo'] : 'recebimento',
            'data_de' => $data($param['data_de'] ?? ''),
            'data_ate' => $data($param['data_ate'] ?? ''),
        ]);
        TScript::create('Template.closeRightPanel();');
        AdiantiCoreApplication::loadPage(__CLASS__, 'onShow');
    }

    public function onClearFilters($param = null)
    {
        TSession::setValue(__CLASS__ . '_filtros', null);
        AdiantiCoreApplication::loadPage(__CLASS__, 'onShow');
    }

    // ------------------------------------------------------------------ acoes

    public static function onConfirmarEstornoLote($param)
    {
        $lote = (int) ($param['lote_id'] ?? 0);
        $acao = new TAction([__CLASS__, 'onEstornarLote'], ['lote_id' => $lote, 'static' => '1']);
        new TQuestion("Estornar o lote de pagamento nº {$lote}? As contas do lote voltam a ficar a pagar e as linhas do pagamento saem do extrato (o saldo da conta-caixa é recalculado).", $acao);
    }

    public static function onEstornarLote($param)
    {
        try {
            RepasseColaboradorService::estornarLote((int) ($param['lote_id'] ?? 0));
            TToast::show('success', 'Lote estornado', 'topRight', 'far:check-circle');
            AdiantiCoreApplication::loadPage(__CLASS__, 'onShow');
        } catch (Throwable $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onRelatorioPdf($param = null)
    {
        try {
            $doc = RepasseColaboradorRelatorioService::gerarPdf(self::filtrosAtuais());
            TPage::openFile($doc['arquivo'], $doc['nome']);
        } catch (Throwable $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onRelatorioXls($param = null)
    {
        try {
            $doc = RepasseColaboradorRelatorioService::gerarXls(self::filtrosAtuais());
            TPage::openFile($doc['arquivo'], $doc['nome']);
        } catch (Throwable $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    // ------------------------------------------------------------------ montagem

    private static function resumo(array $linhas): string
    {
        $por = [];
        $aberto = 0.0; $pago = 0.0;
        foreach ($linhas as $r) {
            $p = &$por[$r->colaborador];
            if (!isset($p)) { $p = ['aberto' => 0.0, 'pago' => 0.0, 'qtd' => 0]; }
            $p['qtd']++;
            if ($r->pago_em) { $p['pago'] += (float) $r->valor; $pago += (float) $r->valor; } else { $p['aberto'] += (float) $r->valor; $aberto += (float) $r->valor; }
            unset($p);
        }
        $h = "<div class='rc-resumo'><div class='rc-total'><span>A pagar</span><b>" . self::moeda($aberto) . "</b></div><div class='rc-total'><span>Pago</span><b>" . self::moeda($pago) . "</b></div><div class='rc-total'><span>Repasses</span><b>" . count($linhas) . '</b></div></div>';
        if (count($por) > 1 || $por) {
            $h .= "<table class='rc-por'><thead><tr><th>Colaborador</th><th>Repasses</th><th class='v'>A pagar</th><th class='v'>Pago</th></tr></thead><tbody>";
            foreach ($por as $nome => $p) {
                $h .= '<tr><td>' . htmlspecialchars($nome) . '</td><td>' . $p['qtd'] . "</td><td class='v'>" . self::moeda($p['aberto']) . "</td><td class='v'>" . self::moeda($p['pago']) . '</td></tr>';
            }
            $h .= '</tbody></table>';
        }
        return $h;
    }

    private static function tabela(array $linhas): string
    {
        if (!$linhas) { return "<div class='rc-vazio'>Nenhum repasse encontrado com os filtros.</div>"; }
        $h = "<table class='rc-tabela'><thead><tr><th style='width:28px'><input type='checkbox' id='rc-todos' title='Marcar os a pagar visíveis' onclick='rcMarcarTodos(this)'></th>"
            . "<th>Colaborador</th><th>Origem</th><th>Recebido em</th><th>Vencimento</th><th class='v'>Valor</th><th>Situação</th><th></th></tr></thead><tbody>";
        foreach ($linhas as $r) {
            $id = (int) $r->conta_pagar_id;
            $sel = $r->pago_em ? '' : "<input type='checkbox' class='rc-sel' value='{$id}' data-valor='" . number_format((float) $r->valor, 2, '.', '') . "' data-pessoa='" . (int) $r->pessoa_id . "' data-nome='" . htmlspecialchars($r->colaborador, ENT_QUOTES) . "' onclick='rcAtualizar()'>";
            if ($r->pago_em) {
                $sit = "<span class='rc-pago'>Pago em " . date('d/m/Y', strtotime($r->pago_em)) . ($r->lote_id ? ' · lote nº ' . (int) $r->lote_id : '') . '</span>'
                    . ($r->compensado === 'S' ? " <span class='rc-comp'>compensado</span>" : '');
            } else {
                $sit = "<span class='rc-aberto'>A pagar</span>";
            }
            $abrir = "<a href='javascript:void(0)' title='Abrir a conta a pagar' onclick=\"__adianti_load_page('index.php?class=ContaFormView&method=onShow&key={$id}&id={$id}&target_container=adianti_right_panel'); return false;\"><i class='fas fa-search'></i></a>";
            $origem = "<a href='javascript:void(0)' title='Abrir a conta a receber de origem' onclick=\"__adianti_load_page('index.php?class=ContaFormView&method=onShow&key=" . (int) $r->conta_receber_id . '&id=' . (int) $r->conta_receber_id . "&target_container=adianti_right_panel'); return false;\">" . htmlspecialchars(RepasseColaboradorService::origem($r)) . '</a>';
            $estornar = ($r->lote_id) ? " <a href='javascript:void(0)' title='Estornar o lote nº " . (int) $r->lote_id . "' onclick=\"__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onConfirmarEstornoLote&static=1&lote_id=" . (int) $r->lote_id . "'); return false;\"><i class='fas fa-undo' style='color:#d32f2f'></i></a>" : '';
            $h .= "<tr><td>{$sel}</td><td>" . htmlspecialchars($r->colaborador) . "</td><td class='rc-origem'>{$origem}</td><td>" . date('d/m/Y', strtotime($r->data_recebimento))
                . '</td><td>' . ($r->vencimento ? date('d/m/Y', strtotime($r->vencimento)) : '') . "</td><td class='v'>" . self::moeda($r->valor) . "</td><td>{$sit}</td><td class='rc-acoes'>{$abrir}{$estornar}</td></tr>";
        }
        $h .= '</tbody></table>';
        $h .= "<script>
            function rcSelecionados() { return Array.prototype.slice.call(document.querySelectorAll('.rc-sel:checked')); }
            function rcAtualizar() {
                var sel = rcSelecionados(), total = 0, pessoas = {};
                sel.forEach(function (c) { total += parseFloat(c.dataset.valor); pessoas[c.dataset.pessoa] = c.dataset.nome; });
                var nomes = Object.keys(pessoas).map(function (k) { return pessoas[k]; });
                var txt = document.getElementById('rc-sel-texto'), btn = document.getElementById('rc-btn-pagar'), box = document.getElementById('rc-selecao');
                if (!sel.length) { txt.textContent = 'Marque as contas a pagar de um colaborador para pagar de uma vez.'; btn.disabled = true; box.className = 'rc-selecao'; return; }
                var valor = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                if (nomes.length > 1) {
                    txt.textContent = sel.length + ' selecionadas - ' + valor + ' - de ' + nomes.length + ' colaboradores: cada pagamento é para um colaborador só.';
                    btn.disabled = true; box.className = 'rc-selecao rc-selecao-erro';
                } else {
                    txt.textContent = sel.length + ' selecionada(s) de ' + nomes[0] + ' - total ' + valor;
                    btn.disabled = false; box.className = 'rc-selecao rc-selecao-ok';
                }
            }
            function rcMarcarTodos(c) { document.querySelectorAll('.rc-sel').forEach(function (x) { x.checked = c.checked; }); rcAtualizar(); }
            function rcPagar() {
                var ids = rcSelecionados().map(function (c) { return c.value; });
                if (ids.length) { __adianti_load_page('engine.php?class=RepasseColaboradorPagarForm&method=onShow&ids=' + ids.join(',')); }
            }
        </script>";
        return $h;
    }

    /** Quadro "Repasses aos colaboradores" (detalhe da conta a receber e Ver financeiro do levantamento). */
    public static function secaoHtml(array $linhas): string
    {
        $td = 'padding:4px 8px;border-bottom:1px solid #eef2f7;font-size:12px;text-align:left';
        $h = "<table style='width:100%;border-collapse:collapse'><thead><tr>"
            . "<th style='{$td};background:#f8fafc'>Colaborador</th><th style='{$td};background:#f8fafc'>Recebido em</th><th style='{$td};background:#f8fafc'>Vencimento</th>"
            . "<th style='{$td};background:#f8fafc;text-align:right'>Valor</th><th style='{$td};background:#f8fafc'>Situação</th><th style='{$td};background:#f8fafc'></th></tr></thead><tbody>";
        foreach ($linhas as $r) {
            $id = (int) $r->conta_pagar_id;
            $url = "index.php?class=ContaFormView&method=onShow&key={$id}&id={$id}&target_container=adianti_right_panel";
            $abrir = '<a href="javascript:void(0)" title="Abrir a conta a pagar" onclick="' . htmlspecialchars('Adianti.waitMessage = false; __adianti_load_page(' . json_encode($url) . '); return false;') . '"><i class="fas fa-external-link-alt"></i> conta #' . $id . '</a>';
            $sit = $r->pago_em
                ? "<span style='color:#065f46'>Pago em " . date('d/m/Y', strtotime($r->pago_em)) . ($r->lote_id ? ' · lote nº ' . (int) $r->lote_id : '') . ($r->compensado === 'S' ? ' · compensado' : '') . '</span>'
                : "<span style='color:#9a3412;font-weight:600'>A pagar</span>";
            $h .= "<tr><td style='{$td}'>" . htmlspecialchars($r->colaborador) . "</td><td style='{$td}'>" . date('d/m/Y', strtotime($r->data_recebimento))
                . "</td><td style='{$td}'>" . ($r->vencimento ? date('d/m/Y', strtotime($r->vencimento)) : '') . "</td><td style='{$td};text-align:right;white-space:nowrap'>" . self::moeda($r->valor)
                . "</td><td style='{$td}'>{$sit}</td><td style='{$td}'>{$abrir}</td></tr>";
        }
        return $h . '</tbody></table>';
    }

    private static function botao(string $rotulo, string $icone, string $js, string $cor = '#333'): string
    {
        return "<button type='button' class='btn btn-default btn-sm' onclick=\"{$js}\"><i class='{$icone}' style='color:{$cor}'></i> {$rotulo}</button>";
    }

    public static function moeda($v): string
    {
        return 'R$ ' . number_format((float) $v, 2, ',', '.');
    }

    private static function estilo(): TElement
    {
        $s = new TElement('style');
        $s->add('
            .rc-topo { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:10px; }
            .rc-topo .badge { margin-left:2px; }
            .rc-filtros { color:#64748b; font-size:12px; margin:4px 0 10px 0; }
            .rc-resumo { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:10px; }
            .rc-total { border:1px solid #e2e8f0; border-radius:8px; padding:8px 14px; min-width:150px; }
            .rc-total span { display:block; color:#64748b; font-size:12px; } .rc-total b { font-size:16px; }
            table.rc-por { border-collapse:collapse; margin-bottom:12px; min-width:420px; }
            table.rc-por th, table.rc-por td { padding:4px 10px; border-bottom:1px solid #eef2f7; text-align:left; font-size:12px; }
            .rc-selecao { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; background:#f1f5f9; margin-bottom:10px; font-size:13px; position:sticky; top:0; z-index:5; }
            .rc-selecao-ok { background:#ecfdf5; color:#065f46; } .rc-selecao-erro { background:#fef2f2; color:#991b1b; }
            table.rc-tabela { width:100%; border-collapse:collapse; font-size:12.5px; }
            table.rc-tabela th { background:#f8fafc; border-bottom:1px solid #cbd5e1; padding:6px 8px; text-align:left; white-space:nowrap; }
            table.rc-tabela td { border-bottom:1px solid #eef2f7; padding:6px 8px; vertical-align:top; }
            table.rc-tabela .v, table.rc-por .v { text-align:right; white-space:nowrap; }
            .rc-origem a { color:#1f3a5f; } .rc-acoes a { margin-right:8px; }
            .rc-aberto { color:#9a3412; font-weight:600; } .rc-pago { color:#065f46; } .rc-comp { background:#dcfce7; color:#166534; border-radius:4px; padding:0 5px; font-size:11px; }
            .rc-vazio { padding:30px; text-align:center; color:#64748b; }
        ');
        return $s;
    }
}
