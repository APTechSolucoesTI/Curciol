<?php
/**
 * Contatos do APChat sem vinculo com a Curciol (03/11/2026).
 *
 * Numeros que mandaram mensagem no APChat (o APChat cria o contato sozinho) e que ainda nao estao em nenhum cadastro da
 * Curciol, do mais recente ao mais antigo, uma linha por contato com o ultimo ticket (ultima mensagem, atendente,
 * fila). A lupa abre a cortina com a conversa (so leitura) e as acoes: criar cadastro, vincular a um cadastro existente,
 * trocar o numero de um contato, ignorar. Le o banco do APChat pelo usuario "curciol" (ApchatBancoService).
 *
 * Estrutura igual as demais listagens (RepresentantesLegaisList): botoes no topo, filtro no topo das colunas (somam,
 * FiltroLista), busca e exportacao.
 */
class ApchatContatoSemVinculoList extends TPage
{
    private $form;
    private $datagrid;
    private $datagrid_form;
    private $pageNavigation;
    private $loaded;
    private static $formName = 'formList_ApchatContatoSemVinculo';
    private $showMethods = ['onReload', 'onSearch', 'onRefresh', 'onClearFilters', 'onGlobalSearch'];
    private $limit = 20;

    const SITUACOES = ['SEM_VINCULO' => 'Sem vínculo', 'VINCULADOS' => 'Já vinculados', 'IGNORADOS' => 'Ignorados'];

    public function __construct($param = null)
    {
        parent::__construct();

        if (!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $nome_col = new TEntry('nome_col');
        $numero_col = new TEntry('numero_col');
        $criado_de_col = new TDate('criado_de_col');
        $criado_ate_col = new TDate('criado_ate_col');
        $mensagem_col = new TEntry('mensagem_col');
        $atendente_col = new TEntry('atendente_col');
        $fila_col = new TEntry('fila_col');
        $situacao = new TCombo('situacao');
        $globalfilt = new TEntry('globalfilt');

        $situacao->addItems(self::SITUACOES);
        $situacao->setDefaultOption(false);
        $situacao->setValue('SEM_VINCULO');
        $situacao->setSize(170);
        $situacao->setChangeAction(new TAction([$this, 'onSearch'], ['static' => '1']));

        foreach ([$nome_col, $numero_col, $mensagem_col, $atendente_col, $fila_col] as $campo)
        {
            $campo->exitOnEnter();
            $campo->setExitAction(new TAction([$this, 'onSearch'], ['static' => '1']));
            $campo->setSize('100%');
        }
        foreach ([$criado_de_col, $criado_ate_col] as $campo)
        {
            $campo->setMask('dd/mm/yyyy');
            $campo->setDatabaseMask('yyyy-mm-dd');
            $campo->setExitAction(new TAction([$this, 'onSearch'], ['static' => '1']));
            $campo->setSize('100%');
        }
        $criado_de_col->placeholder = 'de';
        $criado_ate_col->placeholder = 'até';

        $globalfilt->setEnterAction(new TAction([$this, 'onGlobalSearch']));
        $globalfilt->setInnerIcon(new TImage('fas:search #9E9E9E'), 'left');
        $globalfilt->placeholder = 'Nome ou número';
        $globalfilt->setSize(200);

        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__ . '_datagrid');
        $this->datagrid_form = new TForm('datagrid_' . self::$formName);
        $this->datagrid_form->onsubmit = 'return false';
        $this->datagrid_form->addField($globalfilt);
        $this->datagrid_form->addField($situacao);
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(400);

        $column_nome = new TDataGridColumn('name', "Nome no APChat", 'left');
        $column_numero = new TDataGridColumn('number', "Número", 'left', '140px');
        $column_criado = new TDataGridColumn('criado_em', "Criado em", 'center', '125px');
        $column_mensagem = new TDataGridColumn('ultima_mensagem', "Última mensagem", 'left');
        $column_atendente = new TDataGridColumn('atendente', "Último atendente", 'left', '150px');
        $column_fila = new TDataGridColumn('fila', "Fila", 'left', '120px');

        $column_numero->setTransformer(function ($value) { return self::formatarNumero($value); });
        $column_criado->setTransformer(function ($value) { return $value ? date('d/m/Y H:i', strtotime($value)) : ''; });
        $column_mensagem->setTransformer(function ($value, $object) {
            if (!$object->ticket_id) { return '<span style="color:#999">sem conversa</span>'; }
            $texto = trim(preg_replace('/\s+/', ' ', (string) $value));
            $texto = mb_strlen($texto) > 70 ? mb_substr($texto, 0, 70) . '…' : $texto;
            $quando = $object->ultima_mensagem_em ? date('d/m/Y H:i', strtotime($object->ultima_mensagem_em)) : '';
            return '<span style="color:#888">' . $quando . '</span> ' . htmlspecialchars($texto);
        });
        $column_atendente->setTransformer(function ($value) { return htmlspecialchars((string) $value) ?: '<span style="color:#999">—</span>'; });
        $column_fila->setTransformer(function ($value) { return htmlspecialchars((string) $value) ?: '<span style="color:#999">—</span>'; });
        $column_nome->setTransformer(function ($value) { return htmlspecialchars((string) $value); });

        $this->datagrid->addColumn($column_nome);
        $this->datagrid->addColumn($column_numero);
        $this->datagrid->addColumn($column_criado);
        $this->datagrid->addColumn($column_mensagem);
        $this->datagrid->addColumn($column_atendente);
        $this->datagrid->addColumn($column_fila);

        $action_ver = new TDataGridAction(['ApchatContatoConversaView', 'onShow']);
        $action_ver->setUseButton(false);
        $action_ver->setButtonClass('btn btn-default btn-sm');
        $action_ver->setLabel("Ver conversa e vincular");
        $action_ver->setImage('fas:search #478fca');
        $action_ver->setField('id');
        $this->datagrid->addAction($action_ver);

        $this->datagrid->createModel();

        // filtro no topo das colunas (mesmo padrao das outras listagens)
        $tr = new TElement('tr');
        $tr->id = 'datagrid-header-filter-row';
        $this->datagrid->prependRow($tr);
        $tr->add(TElement::tag('td', ''));
        $tr->add(TElement::tag('td', $nome_col));
        $tr->add(TElement::tag('td', $numero_col));
        $datas = new TElement('div');
        $datas->style = 'display:flex; gap:4px';
        $datas->add($criado_de_col);
        $datas->add($criado_ate_col);
        $tr->add(TElement::tag('td', $datas));
        $tr->add(TElement::tag('td', $mensagem_col));
        $tr->add(TElement::tag('td', $atendente_col));
        $tr->add(TElement::tag('td', $fila_col));
        foreach ([$nome_col, $numero_col, $criado_de_col, $criado_ate_col, $mensagem_col, $atendente_col, $fila_col] as $campo)
        {
            $this->datagrid_form->addField($campo);
        }
        $this->datagrid_form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload']));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup("Contatos do APChat sem vínculo com a Curciol");
        $panel->datagrid = 'datagrid-container';
        $panel->add($this->datagrid_form);
        $panel->getBody()->class .= ' table-responsive';
        $panel->addFooter($this->pageNavigation);

        $headerActions = new TElement('div');
        $headerActions->class = ' datagrid-header-actions ';
        $headerActions->style = 'justify-content: space-between;';
        $head_left_actions = new TElement('div');
        $head_left_actions->class = ' datagrid-header-actions-left-actions ';
        $head_right_actions = new TElement('div');
        $head_right_actions->class = ' datagrid-header-actions-left-actions ';
        $headerActions->add($head_left_actions);
        $headerActions->add($head_right_actions);
        $this->datagrid_form->add($headerActions);

        $button_atualizar = new TButton('button_atualizar');
        $button_atualizar->setAction(new TAction([__CLASS__, 'onRefresh']), "Atualizar");
        $button_atualizar->addStyleClass('btn-default');
        $button_atualizar->setImage('fas:sync-alt #03a9f4');
        $this->datagrid_form->addField($button_atualizar);

        $button_limpar = new TButton('button_limpar_filtros');
        $button_limpar->setAction(new TAction([__CLASS__, 'onClearFilters']), "Limpar filtros");
        $button_limpar->addStyleClass('btn-default');
        $button_limpar->setImage('fas:eraser #f44336');
        $this->datagrid_form->addField($button_limpar);

        $dropdown_exportar = new TDropDown("Exportar", 'fas:file-export #2d3436');
        $dropdown_exportar->setPullSide('right');
        $dropdown_exportar->setButtonClass('btn btn-default waves-effect dropdown-toggle');
        $dropdown_exportar->addPostAction("CSV", new TAction([__CLASS__, 'onExportCsv'], ['static' => 1]), 'datagrid_' . self::$formName, 'fas:table #00b894');

        $button_buscar = new TButton('button_buscar');
        $button_buscar->setAction(new TAction([__CLASS__, 'onGlobalSearch']), "Buscar");
        $button_buscar->addStyleClass('btn-default');
        $button_buscar->setImage('fas:search #9E9E9E');
        $this->datagrid_form->addField($button_buscar);

        $rotulo = new TElement('span');
        $rotulo->style = 'margin: 0 6px 0 4px; align-self:center; color:#555';
        $rotulo->add('Situação:');

        $head_left_actions->add($button_atualizar);
        $head_left_actions->add($button_limpar);
        $head_left_actions->add($dropdown_exportar);
        $head_left_actions->add($rotulo);
        $head_left_actions->add($situacao);
        $head_right_actions->add($globalfilt);
        $head_right_actions->add($button_buscar);

        $this->datagrid_form->add($this->datagrid);

        $container = new TVBox;
        $container->style = 'width: 100%';
        if (empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Configurações", "Contatos do APChat sem vínculo"]));
        }
        $container->add($panel);
        parent::add($container);
    }

    public static function formatarNumero($numero): string
    {
        $d = preg_replace('/\D/', '', (string) $numero);
        if (strlen($d) >= 12 && substr($d, 0, 2) === '55') { return PessoaContatoService::formatarTelefone(substr($d, 2)); }
        return $d !== '' ? $d : 'sem número';
    }

    public function onGlobalSearch($param = null)
    {
        $param['globalSearch'] = true;
        $this->onSearch($param);
    }

    public function onRefresh($param = null)
    {
        $this->onReload([]);
    }

    public function onClearFilters($param = null)
    {
        FiltroLista::limparSessao(__CLASS__);
        $this->datagrid_form->clear();
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    /** Filtros que se somam (cabecalho, busca, situacao) */
    public function onSearch($param = null)
    {
        $data = FiltroLista::acumular(__CLASS__, $this->datagrid_form->getData(), (array) $param);
        TSession::setValue(__CLASS__ . '_filter_data', $data);
        $this->datagrid_form->setData($data);
        $this->onReload(['offset' => 0, 'first_page' => 1]);
    }

    /** filtros da sessao no formato do ApchatBancoService::listar */
    private static function filtros(): array
    {
        $d = (array) (TSession::getValue(__CLASS__ . '_filter_data') ?: []);
        $data = function ($v) {
            $v = trim((string) $v);
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m)) { return "{$m[3]}-{$m[2]}-{$m[1]}"; }
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
        };
        $f = [
            'nome'       => $d['nome_col'] ?? '',
            'numero'     => $d['numero_col'] ?? '',
            'criado_de'  => $data($d['criado_de_col'] ?? ''),
            'criado_ate' => $data($d['criado_ate_col'] ?? ''),
            'mensagem'   => $d['mensagem_col'] ?? '',
            'atendente'  => $d['atendente_col'] ?? '',
            'fila'       => $d['fila_col'] ?? '',
        ];
        // busca global: nome ou numero
        $g = trim((string) ($d['globalfilt'] ?? ''));
        if ($g !== '')
        {
            if (preg_replace('/\D/', '', $g) !== '' && !preg_match('/[[:alpha:]]/u', $g)) { $f['numero'] = $g; } else { $f['nome'] = $g; }
        }
        return $f;
    }

    private static function situacao(): string
    {
        $dados = (array) (TSession::getValue(__CLASS__ . '_filter_data') ?: []);
        $s = (string) ($dados['situacao'] ?? '');
        return isset(self::SITUACOES[$s]) ? $s : 'SEM_VINCULO';
    }

    public function onReload($param = null)
    {
        try
        {
            if (!ApchatBancoService::configurado() || !ApchatBancoService::disponivel())
            {
                throw new Exception('Não foi possível ler o APChat agora. ' . ApchatBancoService::erro());
            }

            $limit = $this->limit;
            $offset = isset($param['offset']) ? (int) $param['offset'] : 0;
            [$linhas, $total] = ApchatBancoService::listar(self::situacao(), ApchatVinculoService::contextoCurciol(), self::filtros(), $offset, $limit);

            $this->datagrid->clear();
            foreach ($linhas as $l)
            {
                $this->datagrid->addItem((object) $l);
            }

            $this->pageNavigation->setCount($total);
            $this->pageNavigation->setProperties($param ?: []);
            $this->pageNavigation->setLimit($limit);
            $this->loaded = true;

            return $linhas;
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            $this->loaded = true;
        }
    }

    public function onExportCsv($param = null)
    {
        try
        {
            [$linhas] = ApchatBancoService::listar(self::situacao(), ApchatVinculoService::contextoCurciol(), self::filtros(), 0, 0);
            $output = 'app/output/' . uniqid() . '.csv';
            $h = fopen($output, 'w');
            fputcsv($h, ['Nome no APChat', 'Número', 'Criado em', 'Última mensagem', 'Data da última mensagem', 'Último atendente', 'Fila']);
            foreach ($linhas as $l)
            {
                fputcsv($h, [$l['name'], self::formatarNumero($l['number']), $l['criado_em'] ? date('d/m/Y H:i', strtotime($l['criado_em'])) : '',
                             trim(preg_replace('/\s+/', ' ', (string) $l['ultima_mensagem'])), $l['ultima_mensagem_em'] ? date('d/m/Y H:i', strtotime($l['ultima_mensagem_em'])) : '',
                             $l['atendente'], $l['fila']]);
            }
            fclose($h);
            TPage::openFile($output);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {
    }

    public function show()
    {
        if (!$this->loaded && (!isset($_GET['method']) || !(in_array($_GET['method'], $this->showMethods))))
        {
            if (func_num_args() > 0)
            {
                $this->onReload(func_get_arg(0));
            }
            else
            {
                $this->onReload();
            }
        }
        parent::show();
    }
}
