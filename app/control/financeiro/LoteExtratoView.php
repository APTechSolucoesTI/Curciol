<?php
/**
 * Cortina "Ver lote" do Extrato (23/10/2026): dados do lote no topo, desfazer (a ultima acao em lote do proprio usuario,
 * ate 30 minutos, mesma regra da tela de contas) e as linhas do lote no padrao da listagem do Extrato: selecao que vale
 * para todas as paginas com a soma de entradas e saidas, filtros no cabecalho das colunas (somam entre si), Atualizar,
 * Limpar filtros, Compensar e Descompensar. Cada linha abre a conta. No Extrato o lote aparece numa linha so
 * (ExtratoLoteService) e so e compensado por aqui.
 * Parametro: lote_id (guardado na sessao para paginar, filtrar e recarregar).
 */
class LoteExtratoView extends TPage
{
    use ExtratoSelecaoTrait;

    private static $database = 'escritorio';
    private $form;
    private $datagrid;
    private $datagrid_form;
    private $pageNavigation;
    private $loaded = false;
    private $limit = 20;
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters'];

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setTargetContainer('adianti_right_panel');

        // outro lote: comeca sem filtros e sem selecao
        $loteId = (int) ($param['lote_id'] ?? 0);
        if ($loteId > 0 && $loteId !== self::loteAtual()) {
            TSession::setValue(__CLASS__ . '_lote_id', $loteId);
            TSession::setValue(__CLASS__ . '_filter_data', null);
            TSession::setValue(__CLASS__ . '_filters', null);
            self::selLimpar();
        }
        $loteId = self::loteAtual();

        $this->form = new BootstrapFormBuilder('form_LoteExtratoView');
        $this->form->setFormTitle("Lote nº {$loteId}");

        try {
            TTransaction::open(self::$database);
            $lote = LotePagamento::find($loteId);
            if (!$lote) { throw new Exception('Lote não encontrado.'); }
            $caixa = $lote->conta_caixa_id ? ContaCaixa::find($lote->conta_caixa_id) : null;
            $tipoPagamento = $lote->tipo_pagamento_id ? TipoPagamento::find($lote->tipo_pagamento_id) : null;
            $resumo = self::resumo($loteId);
            TTransaction::close();

            $usuario = '';
            if ($lote->criacao_user_id) {
                TTransaction::open('permission');
                $u = SystemUsers::find((int) $lote->criacao_user_id);
                $usuario = $u ? $u->name : '';
                TTransaction::close();
            }

            $this->form->addContent([self::cabecalho($lote, $resumo, $caixa, $tipoPagamento, $usuario)]);
            $this->form->addContent([$this->listagem()]);

            $ultimo = LoteFinanceiroService::ultimoDesfazivel((int) TSession::getValue('userid'), (string) $lote->tipo_conta_id);
            if ($ultimo && (int) $ultimo->id === $loteId) {
                $ate = date('H:i', strtotime($lote->data_criacao . ' +' . LoteFinanceiroService::PRAZO_DESFAZER_MIN . ' minutes'));
                $b = $this->form->addHeaderAction("Desfazer lote (até {$ate})", new TAction([__CLASS__, 'onConfirmarDesfazer'], ['static' => '1', 'lote_id' => $loteId]), 'fas:undo #f44336');
                $b->class = 'btn btn-sm btn-default';
            }
        } catch (Throwable $e) {
            while (TTransaction::get()) { TTransaction::rollback(); }
            $erro = new TElement('div');
            $erro->class = 'alert alert-danger';
            $erro->add(htmlspecialchars($e->getMessage()));
            $this->form->addContent([$erro]);
        }

        $fechar = new TButton('closeCurtain');
        $fechar->class = 'btn btn-sm btn-default';
        $fechar->style = 'margin-right:10px;';
        $fechar->onClick = 'Template.closeRightPanel();';
        $fechar->setLabel('Fechar');
        $fechar->setImage('fas:times');
        $this->form->addHeaderWidget($fechar);

        parent::add($this->form);
        $estilo = new TStyle('right-panel > .container-part[page-name=LoteExtratoView]');
        $estilo->width = '70% !important';
        $estilo->show(true);
    }

    private static function loteAtual(): int
    {
        return (int) TSession::getValue(__CLASS__ . '_lote_id');
    }

    /** Linhas do lote no extrato e quantas estao compensadas (quitacao desfeita apaga as linhas). */
    private static function resumo(int $loteId): object
    {
        $r = TTransaction::get()->query("SELECT COUNT(*) n, COUNT(*) FILTER (WHERE compensado = 'S') comp FROM extrato WHERE lote_pagamento_id = " . $loteId)->fetch(PDO::FETCH_OBJ);
        return (object) ['linhas' => (int) $r->n, 'compensadas' => (int) $r->comp];
    }

    /** Lotes de compensacao que mexeram nas linhas deste lote. */
    private static function lotesDeCompensacao(int $loteId): array
    {
        return TTransaction::get()->query(
            "SELECT DISTINCT lp.id, lp.data_criacao, lp.desfeito_em FROM lote_pagamento_item i
               JOIN lote_pagamento lp ON lp.id = i.lote_pagamento_id AND lp.acao = 'compensar'
              WHERE i.extrato_id IN (SELECT id FROM extrato WHERE lote_pagamento_id = {$loteId}) ORDER BY lp.id")->fetchAll(PDO::FETCH_OBJ);
    }

    private static function cabecalho($lote, $resumo, $caixa, $tipoPagamento, string $usuario): TElement
    {
        $h = function ($v) { return htmlspecialchars((string) $v); };
        $n = $resumo->linhas;
        $comp = $resumo->compensadas;
        $situacao = $n === 0 ? 'Quitação desfeita' : ($comp === 0 ? 'Não compensado' : ($comp === $n ? 'Compensado' : "Compensado em parte ({$comp} de {$n})"));
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
        $compensacoes = self::lotesDeCompensacao((int) $lote->id);
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
        </style><div class='lote-cab'>";
        foreach ($linhas as $rotulo => $valor) { $html .= '<div><span>' . $h($rotulo) . '</span><b>' . $h($valor) . '</b></div>'; }
        $html .= '</div>';
        $div = new TElement('div');
        $div->add($html);
        return $div;
    }

    /** Listagem das linhas do lote: botoes, barra da selecao, filtros no cabecalho e paginacao. */
    private function listagem(): TElement
    {
        $compensado_col = new TCombo('lote_compensado_col');
        $compensado_col->addItems(['S' => 'Sim', 'N' => 'Não']);
        $data_compensacao_col = new TDate('lote_data_compensacao_col');
        $data_compensacao_col->setMask('dd/mm/yyyy');
        $data_compensacao_col->setDatabaseMask('yyyy-mm-dd');
        $pessoa_col = new TEntry('lote_pessoa_col');
        $conta_col = new TEntry('lote_conta_col');
        $entrada_col = new TNumeric('lote_entrada_col', '2', ',', '.');
        $saida_col = new TNumeric('lote_saida_col', '2', ',', '.');
        $buscar = new TAction([__CLASS__, 'onSearch'], ['static' => '1']);
        $compensado_col->setChangeAction($buscar);
        $data_compensacao_col->setExitAction($buscar);
        foreach ([$pessoa_col, $conta_col, $entrada_col, $saida_col] as $campo) {
            $campo->exitOnEnter();
            $campo->setExitAction($buscar);
        }
        foreach ([$compensado_col, $data_compensacao_col, $pessoa_col, $conta_col, $entrada_col, $saida_col] as $campo) { $campo->setSize('100%'); }

        $this->datagrid = new BootstrapDatagridWrapper(new TDataGrid);
        $this->datagrid->setId(__CLASS__ . '_datagrid');
        $this->datagrid->style = 'width: 100%';
        $this->datagrid_form = new TForm('datagrid_form_LoteExtratoView');
        $this->datagrid_form->onsubmit = 'return false';

        $sel = new TDataGridColumn('id', self::selCabecalho(), 'center', '1%');
        $sel->setTransformer(function ($value, $object) { return self::selCheckbox($object); });
        $compensado = new TDataGridColumn('compensado', 'Compensado', 'center', '10%');
        $compensado->setTransformer(function ($value) {
            $l = new TElement('span');
            $sim = in_array($value, ['S', 'T'], true);
            $l->class = 'label label-' . ($sim ? 'success' : 'danger');
            $l->add($sim ? 'Sim' : 'Não');
            return $l;
        });
        $dataComp = new TDataGridColumn('data_compensacao', 'Data da compensação', 'center', '11%');
        $dataComp->setTransformer(function ($value) { return $value ? date('d/m/Y', strtotime($value)) : ''; });
        $pessoa = new TDataGridColumn('pessoa_nome', 'Pessoa', 'left');
        $conta = new TDataGridColumn('conta_descricao', 'Conta', 'left');
        $parcela = new TDataGridColumn('parcela_texto', 'Parcela', 'center', '7%');
        $moeda = function ($value) { return 'R$ ' . number_format((float) $value, 2, ',', '.'); };
        $entrada = new TDataGridColumn('entrada_valor', 'Entrada', 'right', '11%');
        $saida = new TDataGridColumn('saida_valor', 'Saída', 'right', '11%');
        foreach ([$entrada, $saida] as $col) {
            $col->setTransformer($moeda);
            $col->setTotalFunction(function ($values) { return array_sum((array) $values); });
        }
        foreach ([$sel, $compensado, $dataComp, $pessoa, $conta, $parcela, $entrada, $saida] as $col) { $this->datagrid->addColumn($col); }

        $abrir = new TDataGridAction(['ContaFormView', 'onShow'], ['register_state' => 'false']);
        $abrir->setUseButton(false);
        $abrir->setLabel('Abrir a conta');
        $abrir->setImage('fas:search #00BCD4');
        $abrir->setField('conta_id');
        $abrir->setParameter('key', '{conta_id}');
        $abrir->setParameter('id', '{conta_id}');
        $this->datagrid->addAction($abrir);

        $this->datagrid->createModel();

        // filtros no cabecalho, na ordem das colunas (acao, selecao, colunas)
        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);
        $tr->add(TElement::tag('td', ''));
        $tr->add(TElement::tag('td', ''));
        foreach ([$compensado_col, $data_compensacao_col, $pessoa_col, $conta_col, null, $entrada_col, $saida_col] as $campo) {
            $tr->add(TElement::tag('td', $campo ?? ''));
            if ($campo) { $this->datagrid_form->addField($campo); }
        }
        $this->datagrid_form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        $compensar = new TButton('button_lote_ext_compensar');
        $compensar->setAction(new TAction([__CLASS__, 'onCompensarSelecionadas'], ['static' => '1']), 'Compensar');
        $compensar->addStyleClass('btn-default');
        $compensar->setImage('fas:dollar-sign #4CAF50');
        $descompensar = new TButton('button_lote_ext_descompensar');
        $descompensar->setAction(new TAction([__CLASS__, 'onDescompensarSelecionadas'], ['static' => '1']), 'Descompensar');
        $descompensar->addStyleClass('btn-default');
        $descompensar->setImage('fas:ban #F44336');
        $limpar = new TButton('button_lote_ext_limpar');
        $limpar->setAction(new TAction([__CLASS__, 'onClearFilters']), 'Limpar filtros');
        $limpar->addStyleClass('btn-default');
        $limpar->setImage('fas:eraser #f44336');
        $atualizar = new TButton('button_lote_ext_atualizar');
        $atualizar->setAction(new TAction([__CLASS__, 'onRefresh']), 'Atualizar');
        $atualizar->addStyleClass('btn-default');
        $atualizar->setImage('fas:sync-alt #03a9f4');
        foreach ([$compensar, $descompensar, $limpar, $atualizar] as $b) { $this->datagrid_form->addField($b); }

        $acoes = new TElement('div');
        $acoes->class = 'datagrid-header-actions';
        $acoes->style = 'justify-content: space-between;';
        $esquerda = new TElement('div');
        $esquerda->class = 'datagrid-header-actions-left-actions';
        $esquerda->add($compensar);
        $esquerda->add($descompensar);
        $direita = new TElement('div');
        $direita->class = 'datagrid-header-actions-left-actions';
        $direita->add($limpar);
        $direita->add($atualizar);
        $acoes->add($esquerda);
        $acoes->add($direita);

        $this->datagrid_form->add($acoes);
        $this->datagrid_form->add(self::selBarra());
        $this->datagrid_form->add($this->datagrid);

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));

        $div = new TElement('div');
        $div->class = 'table-responsive';
        $div->add($this->datagrid_form);
        $div->add($this->pageNavigation);
        return $div;
    }

    /** Linhas do lote que podem ser marcadas: as que passam pelos filtros do cabecalho. */
    protected static function selCriterio(): TCriteria
    {
        $c = new TCriteria;
        $c->add(new TFilter('lote_pagamento_id', '=', self::loteAtual()));
        foreach ((array) (TSession::getValue(__CLASS__ . '_filters') ?: []) as $f) { $c->add($f); }
        return $c;
    }

    protected static function selRecarregar(): void
    {
        Navegacao::atualizarListaAoFechar();
        AdiantiCoreApplication::loadPage(__CLASS__, 'onReload');
    }

    public function onReload($param = null)
    {
        if (!$this->datagrid) { return; } // lote nao encontrado: so a mensagem
        try {
            TTransaction::open(self::$database);
            $criteria = self::selCriterio();
            $param['order'] = $param['order'] ?? 'id';
            $param['direction'] = $param['direction'] ?? 'asc';
            $criteria->setProperties($param);
            $criteria->setProperty('limit', $this->limit);
            $objects = (new TRepository('Extrato'))->load($criteria, false) ?: [];

            // pessoa, conta e parcela de cada linha (uma consulta para a pagina)
            $info = [];
            $lancs = array_filter(array_map(function ($o) { return (int) $o->lancamento_id; }, $objects));
            if ($lancs) {
                $st = TTransaction::get()->query(
                    "SELECT l.id, l.parcela, c.id AS conta_id, c.descricao, c.numero_documento, c.total_parcelas, p.nome
                       FROM lancamento l JOIN conta c ON c.id = l.conta_id LEFT JOIN pessoa p ON p.id = c.pessoa_id
                      WHERE l.id IN (" . implode(',', $lancs) . ')');
                foreach ($st->fetchAll(PDO::FETCH_OBJ) as $r) { $info[(int) $r->id] = $r; }
            }

            $this->datagrid->clear();
            self::selMarcar($objects);
            foreach ($objects as $o) {
                $r = $info[(int) $o->lancamento_id] ?? null;
                $o->conta_id = $r ? (int) $r->conta_id : 0;
                $o->pessoa_nome = $r ? (string) $r->nome : '';
                $o->conta_descricao = $r ? (trim(($r->descricao ?: '') . ($r->numero_documento ? ' · ' . $r->numero_documento : '')) ?: 'Conta ' . (int) $r->conta_id) : (string) $o->historico;
                $o->parcela_texto = $r ? (int) $r->parcela . ($r->total_parcelas ? '/' . (int) $r->total_parcelas : '') : '';
                $row = $this->datagrid->addItem($o);
                $row->id = "row_{$o->id}";
            }

            $criteria->resetProperties();
            $this->pageNavigation->setCount((new TRepository('Extrato'))->count($criteria));
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($this->limit);
            TTransaction::close();

            $this->datagrid->initPopoverHeaderFilters();

            // faixa "Filtros aplicados" (o funil do cabecalho a tela le sozinha)
            FiltroLista::faixa(__CLASS__, null, $this->datagrid_form, $this->datagrid);
            $this->loaded = true;
        } catch (Throwable $e) {
            while (TTransaction::get()) { TTransaction::rollback(); }
            new TMessage('error', $e->getMessage());
        }
    }

    /** Filtros do cabecalho (somam entre si): grava na sessao e recarrega. */
    public function onSearch($param = null)
    {
        $data = FiltroLista::acumular(__CLASS__, $this->datagrid_form ? $this->datagrid_form->getData() : [], (array) $param);
        $filters = [];
        if (($data->lote_compensado_col ?? '') === 'S') { $filters[] = new TFilter('compensado', '=', 'S'); }
        if (($data->lote_compensado_col ?? '') === 'N') { $filters[] = new TFilter("COALESCE(compensado, 'N')", '<>', 'S'); }
        if (!empty($data->lote_data_compensacao_col) && ($d = SqlSeguro::data($data->lote_data_compensacao_col))) { $filters[] = new TFilter('data_compensacao', '=', $d); }
        $texto = function ($v) { return '%' . TratamentosService::removerAcentos(trim((string) $v)) . '%'; };
        if (trim((string) ($data->lote_pessoa_col ?? '')) !== '') {
            $filters[] = new TFilter('lancamento_id', 'in', "(SELECT l.id FROM lancamento l JOIN conta c ON c.id = l.conta_id JOIN pessoa p ON p.id = c.pessoa_id WHERE unaccent(p.nome) ilike ?)", null, [$texto($data->lote_pessoa_col)]);
        }
        if (trim((string) ($data->lote_conta_col ?? '')) !== '') {
            $filters[] = new TFilter('lancamento_id', 'in', "(SELECT l.id FROM lancamento l JOIN conta c ON c.id = l.conta_id WHERE unaccent(COALESCE(c.descricao, '') || ' ' || COALESCE(c.numero_documento, '')) ilike ?)", null, [$texto($data->lote_conta_col)]);
        }
        foreach (['lote_entrada_col' => 'entrada_valor', 'lote_saida_col' => 'saida_valor'] as $campo => $coluna) {
            if (isset($data->$campo) && is_scalar($data->$campo) && trim((string) $data->$campo) !== '' && is_numeric($data->$campo)) {
                $filters[] = new TFilter($coluna, '=', round((float) $data->$campo, 2));
            }
        }
        TSession::setValue(__CLASS__ . '_filter_data', $data);
        TSession::setValue(__CLASS__ . '_filters', $filters);
        AdiantiCoreApplication::loadPage(__CLASS__, 'onReload', ['offset' => 0, 'first_page' => 1]);
    }

    public function onClearFilters($param = null)
    {
        TSession::setValue(__CLASS__ . '_filter_data', null);
        TSession::setValue(__CLASS__ . '_filters', null);
        self::selLimpar();
        if ($this->datagrid_form) { $this->datagrid_form->clear(); }
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    public function onRefresh($param = null)
    {
        $this->onReload([]);
    }

    public function onShow($param = null) {}

    public function show()
    {
        if (!$this->loaded && (!isset($_GET['method']) || !in_array($_GET['method'], $this->showMethods))) {
            $this->onReload(func_num_args() > 0 ? func_get_arg(0) : []);
        }
        parent::show();
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
