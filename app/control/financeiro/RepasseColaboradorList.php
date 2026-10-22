<?php
/**
 * Financeiro > Repasses a colaboradores.
 * Contas a pagar geradas para os colaboradores a cada recebimento com repasse (RepasseColaboradorService).
 * Somente consulta: filtros (cortina: colaborador, processo, contrato, situacao, periodo), filtros no cabecalho das
 * colunas (padrao das listagens), totais por colaborador, origem de cada repasse e relatorio (PDF/Excel).
 * O pagamento e feito em Contas a pagar (acoes em lote).
 */
class RepasseColaboradorList extends TPage
{
    private static $formFiltros = 'formFiltros_RepasseColaborador';
    private static $formColunas = 'datagrid_form_RepasseColaborador';
    const LIMITE = 500;

    private $datagrid;
    private $datagrid_form;

    public function __construct($param = null)
    {
        parent::__construct();
        if (!empty($param['target_container'])) { $this->adianti_target_container = $param['target_container']; }

        $f = self::filtrosAtuais();
        $col = self::colunasAtuais();
        $container = new TVBox;
        $container->style = 'width:100%';
        if (empty($param['target_container'])) { $container->add(TBreadCrumb::create(['Financeiro', 'Repasses a colaboradores'])); }

        $painel = new TPanelGroup('Repasses a colaboradores');
        $painel->getBody()->style = 'overflow-x:auto';

        // botoes do topo (padrao das listagens)
        $topo = new TElement('div');
        $topo->class = 'rc-topo';
        $qtdFiltros = count(array_filter([$f['pessoa_id'], $f['processo_id'], $f['contrato_id'], $f['data_de'], $f['data_ate']], function ($v) { return $v !== null && $v !== ''; }))
            + (($f['situacao'] ?? '') !== 'aberto' ? 1 : 0);
        $topo->add(self::botao('Filtros' . ($qtdFiltros ? " <span class='badge badge-success'>{$qtdFiltros}</span>" : ''), 'fas fa-filter', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onShowCurtainFilters&static=1')"));
        $topo->add(self::botao('Limpar filtros', 'fas fa-eraser', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onClearFilters')", '#f44336'));
        $topo->add(self::botao('Atualizar', 'fas fa-sync-alt', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onShow')", '#03a9f4'));
        $topo->add(self::botao('Relatório (PDF)', 'far fa-file-pdf', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onRelatorioPdf&static=1')", '#D32F2F'));
        $topo->add(self::botao('Relatório (Excel)', 'far fa-file-excel', "__adianti_load_page('engine.php?class=RepasseColaboradorList&method=onRelatorioXls&static=1')", '#218838'));
        $painel->add($topo);

        try {
            TTransaction::open('escritorio');
            $linhas = RepasseColaboradorService::listar(array_merge($f, $col));
            // recebimento mais recente primeiro
            usort($linhas, function ($a, $b) { return [(string) $b->data_recebimento, (int) $b->id] <=> [(string) $a->data_recebimento, (int) $a->id]; });
            $descricaoFiltros = RepasseColaboradorRelatorioService::descreverFiltros(array_merge($f, $col));
            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
            $linhas = [];
            $descricaoFiltros = '';
            new TMessage('error', $e->getMessage());
        }

        $painel->add(self::resumo($linhas));
        $painel->add("<div class='rc-filtros'>Para pagar, use Financeiro › Contas › Contas a pagar (marque as contas e clique em Quitar selecionadas).</div>");

        $this->montarGrade($col);
        foreach (array_slice($linhas, 0, self::LIMITE) as $r) { $this->datagrid->addItem($r); }
        $painel->add($this->datagrid_form);
        if (!$linhas) { $painel->add("<div class='rc-vazio'>Nenhum repasse encontrado com os filtros.</div>"); }
        if (count($linhas) > self::LIMITE) {
            $painel->add("<div class='rc-filtros'>Mostrando os primeiros " . self::LIMITE . ' de ' . count($linhas) . ' repasses. Use os filtros para refinar (o relatório traz todos).</div>');
        }
        $this->datagrid->initPopoverHeaderFilters();
        // faixa "Filtros aplicados": os da cortina vao junto com os do funil das colunas (curciol-filtros.js)
        TScript::create('if (window.curciolFaixaFiltros) { curciolFaixaFiltros(' . json_encode((string) $this->datagrid->{'id'}) . ', '
            . json_encode(self::filtrosDaCortina($f)) . ', "engine.php?class=RepasseColaboradorList&method=onRemoverFiltro&static=1"); }');

        $container->add($painel);
        $container->add(self::estilo());
        parent::add($container);
    }

    public function onShow($param = null) {}

    /** Grade com os filtros no cabecalho das colunas (popover do funil, como nas outras listagens). */
    private function montarGrade(array $col): void
    {
        $this->datagrid_form = new TForm(self::$formColunas);
        $this->datagrid_form->onsubmit = 'return false';
        $grade = new TDataGrid;
        $grade->setId('RepasseColaboradorList_datagrid');
        $this->datagrid = new BootstrapDatagridWrapper($grade);
        $this->datagrid->style = 'width: 100%';

        $cColaborador = new TDataGridColumn('colaborador', 'Colaborador', 'left');
        $cOrigem = new TDataGridColumn('conta_receber_id', 'Origem', 'left');
        $cRecebido = new TDataGridColumn('data_recebimento', 'Recebido em', 'left');
        $cVencimento = new TDataGridColumn('vencimento', 'Vencimento', 'left');
        $cValor = new TDataGridColumn('valor', 'Valor', 'right');
        $cSituacao = new TDataGridColumn('pago_em', 'Situação', 'left');

        $data = function ($v) { return $v ? date('d/m/Y', strtotime($v)) : ''; };
        $cRecebido->setTransformer($data);
        $cVencimento->setTransformer($data);
        $cValor->setTransformer(function ($v) { return self::moeda($v); });
        $cOrigem->setTransformer(function ($v, $r) {
            $id = (int) $r->conta_receber_id;
            return "<a href='javascript:void(0)' class='rc-origem' title='Abrir a conta a receber de origem' onclick=\"__adianti_load_page('index.php?class=ContaFormView&method=onShow&key={$id}&id={$id}&target_container=adianti_right_panel'); return false;\">"
                . htmlspecialchars(RepasseColaboradorService::origem($r)) . '</a>';
        });
        $cSituacao->setTransformer(function ($v, $r) {
            if (!$r->pago_em) { return "<span class='rc-aberto'>A pagar</span>"; }
            return "<span class='rc-pago'>Pago em " . date('d/m/Y', strtotime($r->pago_em)) . '</span>' . ($r->compensado === 'S' ? " <span class='rc-comp'>compensado</span>" : '');
        });
        foreach ([$cOrigem, $cSituacao] as $c) { $c->disableHtmlConversion(); }

        foreach ([$cColaborador, $cOrigem, $cRecebido, $cVencimento, $cValor, $cSituacao] as $c) { $this->datagrid->addColumn($c); }

        $abrir = new TDataGridAction(['ContaFormView', 'onShow'], ['key' => '{conta_pagar_id}', 'id' => '{conta_pagar_id}', 'target_container' => 'adianti_right_panel']);
        $abrir->setField('conta_pagar_id');
        $abrir->setUseButton(false);
        $abrir->setLabel('Abrir a conta a pagar');
        $abrir->setImage('fas:search #00BCD4');
        $this->datagrid->addAction($abrir);
        $this->datagrid->createModel();

        // filtros do cabecalho
        $criteria = new TCriteria();
        $criteria->add(new TFilter('id', 'in', '(SELECT DISTINCT pessoa_id FROM repasse_colaborador)'));
        $colaborador = new TDBUniqueSearch('colaborador_col', 'escritorio', 'Pessoa', 'id', 'nome', 'nome asc', $criteria);
        $colaborador->setMinLength(0);
        $colaborador->setMask('{nome}');
        $origem = new TEntry('origem_col');
        $origem->placeholder = 'Cliente, processo ou contrato';
        $recebido = new TDate('recebido_col');
        $vencimento = new TDate('vencimento_col');
        foreach ([$recebido, $vencimento] as $d) { $d->setMask('dd/mm/yyyy'); $d->setDatabaseMask('yyyy-mm-dd'); }
        $valor = new TNumeric('valor_col', 2, ',', '.');
        $situacao = new TCombo('situacao_col');
        $situacao->addItems(['aberto' => 'A pagar', 'pago' => 'Pagos']);

        $buscar = new TAction([__CLASS__, 'onSearchColunas'], ['static' => '1']);
        $colaborador->setChangeAction($buscar);
        $situacao->setChangeAction($buscar);
        foreach ([$origem, $valor] as $e) { $e->exitOnEnter(); $e->setExitAction($buscar); }
        foreach ([$recebido, $vencimento] as $d) { $d->setExitAction($buscar); }
        foreach ([$colaborador, $origem, $recebido, $vencimento, $valor, $situacao] as $w) { $w->setSize('100%'); $this->datagrid_form->addField($w); }

        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);
        $tr->add(TElement::tag('td', ''));   // coluna da acao
        foreach ([$colaborador, $origem, $recebido, $vencimento, $valor, $situacao] as $w) { $tr->add(TElement::tag('td', $w)); }

        $this->datagrid_form->add($this->datagrid);
        $this->datagrid_form->setData((object) [
            'colaborador_col' => $col['colaborador_id'] ?? null,
            'origem_col' => $col['texto'] ?? '',
            'recebido_col' => $col['recebido_em'] ?? null,
            'vencimento_col' => $col['vencimento_em'] ?? null,
            'valor_col' => (isset($col['valor']) && $col['valor'] !== null) ? number_format((float) $col['valor'], 2, ',', '.') : '',
            'situacao_col' => $col['situacao_col'] ?? '',
        ]);
    }

    // ------------------------------------------------------------------ filtros

    private static function filtrosAtuais(): array
    {
        $f = TSession::getValue(__CLASS__ . '_filtros');
        $padrao = ['pessoa_id' => null, 'processo_id' => null, 'contrato_id' => null, 'situacao' => 'aberto',
                   'periodo_tipo' => 'recebimento', 'data_de' => null, 'data_ate' => null];
        return is_array($f) ? $f + $padrao : $padrao;
    }

    /** Filtros da cortina para a faixa "Filtros aplicados": [rotulo, valor, campos]. */
    private static function filtrosDaCortina(array $f): array
    {
        $itens = [];
        try {
            TTransaction::open('escritorio');
            if (!empty($f['pessoa_id']) && ($p = Pessoa::find((int) $f['pessoa_id']))) {
                $itens[] = ['rotulo' => 'Colaborador', 'valor' => (string) $p->nome, 'campos' => ['pessoa_id']];
            }
            if (!empty($f['processo_id']) && ($p = Processo::find((int) $f['processo_id']))) {
                $itens[] = ['rotulo' => 'Processo', 'valor' => (string) ($p->numero_cnj_numero ?: $p->numero_outro ?: $p->id), 'campos' => ['processo_id']];
            }
            if (!empty($f['contrato_id']) && ($c = Contrato::find((int) $f['contrato_id']))) {
                $itens[] = ['rotulo' => 'Contrato', 'valor' => (string) ($c->numero ?: $c->id), 'campos' => ['contrato_id']];
            }
            TTransaction::close();
        } catch (Throwable $e) {
            TTransaction::rollback();
        }
        $situacoes = ['aberto' => 'Em aberto', 'pago' => 'Pagos'];
        if (!empty($f['situacao'])) {
            $itens[] = ['rotulo' => 'Situação', 'valor' => $situacoes[$f['situacao']] ?? $f['situacao'], 'campos' => ['situacao']];
        }
        if (!empty($f['data_de']) || !empty($f['data_ate'])) {
            $data = function ($v) { return $v ? date('d/m/Y', strtotime($v)) : '...'; };
            $tipos = ['recebimento' => 'Recebimento', 'vencimento' => 'Vencimento', 'pagamento' => 'Pagamento'];
            $itens[] = ['rotulo' => 'Período (' . ($tipos[$f['periodo_tipo'] ?? 'recebimento'] ?? 'Recebimento') . ')',
                        'valor' => $data($f['data_de']) . ' até ' . $data($f['data_ate']), 'campos' => ['data_de', 'data_ate']];
        }
        return $itens;
    }

    /** "x" de um filtro da cortina na faixa: tira so ele; os outros continuam. */
    public static function onRemoverFiltro($param = null)
    {
        $f = self::filtrosAtuais();
        foreach (array_keys((array) ($param['__filtros_extra'] ?? [])) as $campo) {
            if (array_key_exists($campo, $f)) { $f[$campo] = ($campo === 'situacao') ? '' : null; }
        }
        TSession::setValue(__CLASS__ . '_filtros', $f);
        AdiantiCoreApplication::loadPage(__CLASS__, 'onShow');
    }

    /** Filtros do cabecalho das colunas, ja no formato do servico. */
    private static function colunasAtuais(): array
    {
        $c = TSession::getValue(__CLASS__ . '_colunas');
        if (!is_array($c)) { return []; }
        $r = $c;
        // colaborador do cabecalho refina o da cortina; situacao do cabecalho manda sobre a da cortina
        if (!empty($c['colaborador_id'])) { $r['pessoa_id'] = $c['colaborador_id']; }
        if (!empty($c['situacao_col'])) { $r['situacao'] = $c['situacao_col']; }
        return $r;
    }

    public static function onSearchColunas($param = null)
    {
        $data = function ($v) {
            $v = trim((string) $v);
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) { return "{$m[3]}-{$m[2]}-{$m[1]}"; }
            return preg_match('/^\d{4}-\d{2}-\d{2}/', $v) ? substr($v, 0, 10) : null;
        };
        $valor = trim(str_replace(['R$', ' '], '', (string) ($param['valor_col'] ?? '')));
        if ($valor !== '' && strpos($valor, ',') !== false) { $valor = str_replace(['.', ','], ['', '.'], $valor); }
        TSession::setValue(__CLASS__ . '_colunas', [
            'colaborador_id' => ((int) ($param['colaborador_col'] ?? 0)) ?: null,
            'texto' => trim((string) ($param['origem_col'] ?? '')),
            'recebido_em' => $data($param['recebido_col'] ?? ''),
            'vencimento_em' => $data($param['vencimento_col'] ?? ''),
            'valor' => is_numeric($valor) ? round((float) $valor, 2) : null,
            'situacao_col' => in_array($param['situacao_col'] ?? '', ['aberto', 'pago'], true) ? $param['situacao_col'] : '',
        ]);
        AdiantiCoreApplication::loadPage(__CLASS__, 'onShow');
    }

    public static function onShowCurtainFilters($param = null)
    {
        $f = self::filtrosAtuais();
        $form = new BootstrapFormBuilder(self::$formFiltros);
        $form->setFormTitle('Filtros de repasses');

        $criteria = new TCriteria();
        $criteria->add(new TFilter('id', 'in', '(SELECT DISTINCT pessoa_id FROM repasse_colaborador)'));
        $pessoa = new TDBUniqueSearch('pessoa_id', 'escritorio', 'Pessoa', 'id', 'nome', 'nome asc', $criteria);
        $pessoa->setMinLength(0);
        $pessoa->setMask('{nome}');
        // processo e contrato: escolhe na lista, completando conforme digita
        $processo = new TDBUniqueSearch('processo_id', 'escritorio', 'Processo', 'id', 'numero_cnj_numero', 'numero_cnj_numero asc');
        $processo->setFilterColumns(['numero_cnj_numero', 'numero_outro']);
        $processo->setMinLength(3);
        $processo->setMask('{numero_cnj_numero} {numero_outro}');
        $processo->setTip('Escolhendo um processo pai ou principal, entram também os repasses dos processos filhos.');
        $criteriaContrato = new TCriteria();
        $criteriaContrato->add(new TFilter('id', 'in', '(SELECT DISTINCT c.contrato_id FROM conta c JOIN repasse_colaborador r ON r.conta_receber_id = c.id WHERE c.contrato_id IS NOT NULL)'));
        $contrato = new TDBUniqueSearch('contrato_id', 'escritorio', 'Contrato', 'id', 'numero', 'numero asc', $criteriaContrato);
        $contrato->setMinLength(0);
        $contrato->setMask('{numero}');
        $situacao = new TCombo('situacao');
        $situacao->addItems(['aberto' => 'A pagar', 'pago' => 'Pagos', 'todos' => 'Todos']);
        $periodoTipo = new TCombo('periodo_tipo');
        $periodoTipo->addItems(['recebimento' => 'Data do recebimento', 'vencimento' => 'Data do vencimento', 'pagamento' => 'Data do pagamento']);
        $dataDe = new TDate('data_de');
        $dataAte = new TDate('data_ate');
        foreach ([$dataDe, $dataAte] as $d) { $d->setMask('dd/mm/yyyy'); $d->setDatabaseMask('yyyy-mm-dd'); }
        foreach ([$pessoa, $processo, $contrato, $situacao, $periodoTipo, $dataDe, $dataAte] as $w) { $w->setSize('100%'); }
        $periodoTipo->setDefaultOption(false);
        $situacao->setDefaultOption(false);

        $r = $form->addFields([new TLabel('Colaborador'), $pessoa]);
        $r->layout = ['col-sm-12'];
        $r = $form->addFields([new TLabel('Processo'), $processo], [new TLabel('Contrato'), $contrato]);
        $r->layout = ['col-sm-7', 'col-sm-5'];
        $r = $form->addFields([new TLabel('Situação'), $situacao], []);
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
            'processo_id' => $int($param['processo_id'] ?? null),
            'contrato_id' => $int($param['contrato_id'] ?? null),
            'situacao' => in_array($situacao, ['aberto', 'pago'], true) ? $situacao : '',
            'periodo_tipo' => in_array($param['periodo_tipo'] ?? '', ['recebimento', 'vencimento', 'pagamento'], true) ? $param['periodo_tipo'] : 'recebimento',
            'data_de' => $data($param['data_de'] ?? ''),
            'data_ate' => $data($param['data_ate'] ?? ''),
        ]);
        TScript::create('Template.closeRightPanel();');
        AdiantiCoreApplication::loadPage(__CLASS__, 'onShow');
    }

    /** Clique no colaborador do resumo: filtra a lista por ele (coluna Colaborador), somando aos outros filtros; de novo, tira. */
    public static function onFiltrarColaborador($param = null)
    {
        $id = (int) ($param['pessoa_id'] ?? 0);
        $colunas = TSession::getValue(__CLASS__ . '_colunas');
        $colunas = is_array($colunas) ? $colunas : [];
        $colunas['colaborador_id'] = ($id > 0 && (int) ($colunas['colaborador_id'] ?? 0) !== $id) ? $id : null;
        TSession::setValue(__CLASS__ . '_colunas', $colunas);
        AdiantiCoreApplication::loadPage(__CLASS__, 'onShow');
    }

    public function onClearFilters($param = null)
    {
        TSession::setValue(__CLASS__ . '_filtros', null);
        TSession::setValue(__CLASS__ . '_colunas', null);
        AdiantiCoreApplication::loadPage(__CLASS__, 'onShow');
    }

    // ------------------------------------------------------------------ acoes

    public static function onRelatorioPdf($param = null)
    {
        try {
            $doc = RepasseColaboradorRelatorioService::gerarPdf(array_merge(self::filtrosAtuais(), self::colunasAtuais()));
            TPage::openFile($doc['arquivo'], $doc['nome']);
        } catch (Throwable $e) {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onRelatorioXls($param = null)
    {
        try {
            $doc = RepasseColaboradorRelatorioService::gerarXls(array_merge(self::filtrosAtuais(), self::colunasAtuais()));
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
            if (!isset($p)) { $p = ['aberto' => 0.0, 'pago' => 0.0, 'qtd' => 0, 'pessoa_id' => (int) $r->pessoa_id]; }
            $p['qtd']++;
            if ($r->pago_em) { $p['pago'] += (float) $r->valor; $pago += (float) $r->valor; } else { $p['aberto'] += (float) $r->valor; $aberto += (float) $r->valor; }
            unset($p);
        }
        $h = "<div class='rc-resumo'><div class='rc-total'><span>A pagar</span><b>" . self::moeda($aberto) . "</b></div><div class='rc-total'><span>Pago</span><b>" . self::moeda($pago) . "</b></div><div class='rc-total'><span>Repasses</span><b>" . count($linhas) . '</b></div></div>';
        if ($por) {
            $h .= "<table class='rc-por'><thead><tr><th>Colaborador</th><th>Repasses</th><th class='v'>A pagar</th><th class='v'>Pago</th></tr></thead><tbody>";
            // clicar no colaborador filtra a lista por ele (somando aos outros filtros); clicar de novo tira o filtro
            $filtrado = (int) ((TSession::getValue(__CLASS__ . '_colunas') ?: [])['colaborador_id'] ?? 0);
            foreach ($por as $nome => $p) {
                $acao = htmlspecialchars("Adianti.waitMessage = false; __adianti_ajax_exec('class=RepasseColaboradorList&method=onFiltrarColaborador&pessoa_id=" . (int) $p['pessoa_id'] . "'); return false;");
                $ativo = ($filtrado === (int) $p['pessoa_id']);
                $nomeLink = "<a href='javascript:void(0)' class='rc-colab" . ($ativo ? ' rc-colab-ativo' : '') . "' title='" . ($ativo ? 'Tirar o filtro deste colaborador' : 'Filtrar por este colaborador') . "' onclick=\"{$acao}\">"
                    . ($ativo ? '<i class="fas fa-filter"></i> ' : '') . htmlspecialchars($nome) . '</a>';
                $h .= '<tr><td>' . $nomeLink . '</td><td>' . $p['qtd'] . "</td><td class='v'>" . self::moeda($p['aberto']) . "</td><td class='v'>" . self::moeda($p['pago']) . '</td></tr>';
            }
            $h .= '</tbody></table>';
        }
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
            $abrir = '<a href="javascript:void(0)" title="Abrir a conta a pagar" onclick="' . htmlspecialchars('Adianti.waitMessage = false; __adianti_load_page(' . json_encode($url) . '); return false;') . '"><i class="fas fa-external-link-alt"></i> Abrir conta</a>';
            $sit = $r->pago_em
                ? "<span style='color:#065f46'>Pago em " . date('d/m/Y', strtotime($r->pago_em)) . ($r->compensado === 'S' ? ' · compensado' : '') . '</span>'
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
            table.rc-por .v { text-align:right; white-space:nowrap; }
            table.rc-por a.rc-colab { color:#1f3a5f; text-decoration:none; border-bottom:1px dashed #94a3b8; }
            table.rc-por a.rc-colab:hover { color:#2563eb; border-bottom-color:#2563eb; }
            table.rc-por a.rc-colab-ativo { font-weight:600; color:#2563eb; border-bottom:0; }
            a.rc-origem { color:#1f3a5f; }
            .rc-aberto { color:#9a3412; font-weight:600; } .rc-pago { color:#065f46; } .rc-comp { background:#dcfce7; color:#166534; border-radius:4px; padding:0 5px; font-size:11px; }
            .rc-vazio { padding:30px; text-align:center; color:#64748b; }
        ');
        return $s;
    }
}
