<?php

/**
 * Janela de pesquisa de pre-processos, aberta a partir de uma publicacao.
 *
 * Existe porque a publicacao deixou de criar processo: agora o escritorio
 * cadastra o pre-processo antes e, quando a publicacao chega, escolhe qual
 * deles ela completa.
 *
 * Enquanto nao ha numero, quem identifica o registro e a descricao, o
 * cliente, o contrato e o id interno - por isso todos aparecem na lista.
 *
 * A publicacao de origem viaja em todas as acoes como parametro
 * (publicacao_id e nivel), nunca em sessao. O caminho antigo, em
 * ProcessoSeekWindow, guarda nivel_processo e publicacao_id em
 * TSession: com duas abas abertas a segunda sobrescreve a primeira e a
 * associacao sai errada. Aqui isso nao acontece.
 */
class PreProcessoSeekWindow extends TWindow
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $loaded;
    private $publicacao_id;
    private $nivel;
    private static $database = 'escritorio';
    private static $activeRecord = 'Processo';
    private static $primaryKey = 'id';
    private static $formName = 'form_PreProcessoSeekWindow';
    private $showMethods = ['onReload', 'onSearch'];
    private $limit = 20;

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.9, null);
        parent::setTitle('Vincular pré-processo');
        parent::setProperty('class', 'window_modal');

        $this->publicacao_id = (int) ($param['publicacao_id'] ?? 0);
        $this->nivel = (($param['nivel'] ?? '') === PreProcessoService::NIVEL_PRINCIPAL)
            ? PreProcessoService::NIVEL_PRINCIPAL
            : PreProcessoService::NIVEL_PROCESSO;

        $contexto = [
            'publicacao_id' => $this->publicacao_id,
            'nivel'         => $this->nivel,
        ];

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle(
            $this->nivel === PreProcessoService::NIVEL_PRINCIPAL
                ? 'Escolha o pré-processo que será o processo principal'
                : 'Escolha o pré-processo que esta publicação completa'
        );

        $criteria_cliente = new TCriteria();
        $filterVar = Grupo::CLIENTE;
        $criteria_cliente->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')"));

        $descricao   = new TEntry('descricao_pre_processo');
        $cliente_id  = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome', 'nome asc', $criteria_cliente);
        $area_id     = new TDBCombo('area_id', 'escritorio', 'Area', 'id', '{nome}', 'nome asc');

        $descricao->setSize('100%');
        $cliente_id->setSize('100%');
        $area_id->setSize('100%');
        $cliente_id->setMinLength(2);
        $cliente_id->setMask('{nome}');
        $cliente_id->setFilterColumns(['nome', 'cpf_cnpj']);
        $area_id->enableSearch();

        $row1 = $this->form->addFields(
            [new TLabel('Descrição:', null, '14px', null, '100%'), $descricao],
            [new TLabel('Cliente:', null, '14px', null, '100%'), $cliente_id]
        );
        $row1->layout = ['col-sm-6', 'col-sm-6'];

        $row2 = $this->form->addFields([new TLabel('Área:', null, '14px', null, '100%'), $area_id]);
        $row2->layout = ['col-sm-6'];

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        $btn_onsearch = $this->form->addAction('Buscar', new TAction([$this, 'onSearch'], $contexto), 'fas:search #ffffff');
        $btn_onsearch->addStyleClass('btn-primary');

        $btn_limpar = $this->form->addAction('Limpar filtros', new TAction([$this, 'onClearFilters'], $contexto), 'fas:eraser #dd5a43');

        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__ . '_datagrid');
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->datagrid->style = 'width: 100%';
        $this->datagrid->setHeight(320);

        $col_id        = new TDataGridColumn('id', 'Id', 'left', '60px');
        $col_descricao = new TDataGridColumn('descricao_pre_processo', 'Descrição do pré-processo', 'left');
        $col_clientes  = new TDataGridColumn('id', 'Cliente(s)', 'left');
        $col_contrato  = new TDataGridColumn('id', 'Contrato', 'left', '120px');
        $col_area      = new TDataGridColumn('area->nome', 'Área', 'left');
        $col_assunto   = new TDataGridColumn('assunto->nome', 'Assunto', 'left');
        $col_resp      = new TDataGridColumn('responsavel->nome', 'Responsável', 'left');
        $col_criacao   = new TDataGridColumn('data_criacao', 'Criado em', 'left', '120px');
        $col_estado    = new TDataGridColumn('pre_processo', 'Estado', 'left', '170px');

        $col_descricao->setTransformer(function ($value) {
            $value = trim((string) $value);

            return $value === '' ? '<i>sem descrição</i>' : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        });

        /*
            Clientes e contrato saem das duas pontes possiveis. Rodam dentro
            da transacao ja aberta pelo onReload, e a pagina tem 20 linhas.
        */
        $col_clientes->setTransformer(function ($value) {
            $clientes = PreProcessoService::clientesDoProcesso($value);

            return empty($clientes)
                ? '<i>sem cliente vinculado</i>'
                : htmlspecialchars(implode(', ', $clientes), ENT_QUOTES, 'UTF-8');
        });

        $col_contrato->setTransformer(function ($value) {
            $numeros = [];

            foreach (ContratoProcesso::where('processo_id', '=', (int) $value)->load() as $vinculo)
            {
                $contrato = Contrato::find($vinculo->contrato_id);

                if ($contrato)
                {
                    $numeros[] = $contrato->numero;
                }
            }

            return empty($numeros) ? '-' : htmlspecialchars(implode(', ', $numeros), ENT_QUOTES, 'UTF-8');
        });

        $col_criacao->setTransformer(function ($value) {
            return empty($value) ? '-' : date('d/m/Y', strtotime($value));
        });

        $col_estado->setTransformer(function ($value, $object) {
            return PreProcessoService::estado($object);
        });

        $this->datagrid->addColumn($col_id);
        $this->datagrid->addColumn($col_descricao);
        $this->datagrid->addColumn($col_clientes);
        $this->datagrid->addColumn($col_contrato);
        $this->datagrid->addColumn($col_area);
        $this->datagrid->addColumn($col_assunto);
        $this->datagrid->addColumn($col_resp);
        $this->datagrid->addColumn($col_criacao);
        $this->datagrid->addColumn($col_estado);

        $action_selecionar = new TDataGridAction(['PreProcessoConversaoForm', 'onShow']);
        $action_selecionar->setUseButton(true);
        $action_selecionar->setButtonClass('btn btn-default btn-sm');
        $action_selecionar->setLabel('Selecionar');
        $action_selecionar->setImage('far:hand-pointer #44bd32');
        $action_selecionar->setField(self::$primaryKey);
        $action_selecionar->setParameter('processo_id', '{id}');
        $action_selecionar->setParameter('publicacao_id', $this->publicacao_id);
        $action_selecionar->setParameter('nivel', $this->nivel);
        $this->datagrid->addAction($action_selecionar);

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload'], $contexto));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        $panel = new TPanelGroup();
        $panel->datagrid = 'datagrid-container';
        $panel->add($this->datagrid);
        $panel->getBody()->class .= ' table-responsive';
        $panel->addFooter($this->pageNavigation);

        parent::add($this->form);
        parent::add($panel);
    }

    /**
     * Somente pre-processos pendentes.
     *
     * Registros ja convertidos nao aparecem: eles nao estao disponiveis para
     * receber outro numero.
     */
    private function criteriaBase(): TCriteria
    {
        $criteria = new TCriteria();
        $criteria->add(new TFilter('pre_processo', '=', PreProcessoService::SIM));

        return $criteria;
    }

    public function onSearch($param = null)
    {
        $data = $this->form->getData();
        $filters = [];

        if (!empty($data->descricao_pre_processo))
        {
            $filters[] = new TFilter('descricao_pre_processo', 'ilike', "%{$data->descricao_pre_processo}%");
        }

        if (!empty($data->cliente_id))
        {
            $cliente_id = (int) $data->cliente_id;

            /*
                Procura o cliente pelo contrato, que e o mesmo caminho que o
                portal usa para decidir de quem e o processo.
            */
            $filters[] = new TFilter('id', 'in', "(
                SELECT cp.processo_id
                FROM contrato_processo cp
                JOIN contrato_pessoa cpe ON cpe.contrato_id = cp.contrato_id
                WHERE cpe.cliente_id = {$cliente_id}
            )");
        }

        if (!empty($data->area_id))
        {
            $filters[] = new TFilter('area_id', '=', $data->area_id);
        }

        $this->form->setData($data);

        TSession::setValue(__CLASS__ . '_filter_data', $data);
        TSession::setValue(__CLASS__ . '_filters', $filters);

        $this->onReload([
            'offset'        => 0,
            'first_page'    => 1,
            'publicacao_id' => $param['publicacao_id'] ?? $this->publicacao_id,
            'nivel'         => $param['nivel'] ?? $this->nivel,
        ]);
    }

    public function onClearFilters($param = null)
    {
        TSession::setValue(__CLASS__ . '_filter_data', null);
        TSession::setValue(__CLASS__ . '_filters', null);

        $this->form->clear(true);
        $this->onReload(['offset' => 0, 'first_page' => 1] + (array) $param);
    }

    public function onReload($param = null)
    {
        try
        {
            TTransaction::open(self::$database);

            $repository = new TRepository(self::$activeRecord);
            $criteria = $this->criteriaBase();

            if (empty($param['order']))
            {
                $param['order'] = 'id';
            }

            if (empty($param['direction']))
            {
                $param['direction'] = 'desc';
            }

            $criteria->setProperties($param);
            $criteria->setProperty('limit', $this->limit);

            if ($filters = TSession::getValue(__CLASS__ . '_filters'))
            {
                foreach ($filters as $filter)
                {
                    $criteria->add($filter);
                }
            }

            $objects = $repository->load($criteria, false);

            $this->datagrid->clear();

            if ($objects)
            {
                foreach ($objects as $object)
                {
                    $this->datagrid->addItem($object);
                }
            }

            $criteria->resetProperties();
            $count = $repository->count($criteria);

            $this->pageNavigation->setCount($count);
            $this->pageNavigation->setProperties($param);
            $this->pageNavigation->setLimit($this->limit);

            TTransaction::close();
            $this->loaded = true;

            /*
                Lista vazia nao pode virar beco sem saida: o usuario precisa
                saber que o caminho e cadastrar o pre-processo antes.
            */
            if ($count == 0)
            {
                TToast::show(
                    'info',
                    'Nenhum pré-processo disponível para vincular. Cadastre o pré-processo em Processos, marcando "É um pré-processo?", e volte a esta publicação.',
                    'topRight',
                    'fas:info-circle'
                );
            }
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
            TTransaction::rollback();
        }
    }

    public function onShow($param = null)
    {
    }

    public function show()
    {
        if (!$this->loaded and (!isset($_GET['method']) or !(in_array($_GET['method'], $this->showMethods))))
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
