<?php

/**
 * Escolha do pre-processo que a publicacao vai completar.
 *
 * Renderiza dentro da propria "Consulta de publicacao", numa area que a tela
 * reserva para este fluxo. Nao e janela: abrir um modal sobre o modal da
 * publicacao empilhava duas camadas e, ao terminar, as duas fechavam e a
 * consulta reabria - tres transicoes para uma acao so.
 *
 * Aqui a publicacao nunca sai da tela. Ela continua visivel acima enquanto a
 * lista, a confirmacao e o resultado se sucedem no mesmo lugar.
 *
 * A publicacao de origem viaja como parametro (publicacao_id e nivel), nunca
 * em sessao: com duas abas abertas, sessao cruza os registros.
 */
class PreProcessoVincularList extends TPage
{
    private $form;
    private $datagrid;
    private $pageNavigation;
    private $loaded;
    private $publicacao_id;
    private $nivel;
    private $container;
    private static $database = 'escritorio';
    private static $activeRecord = 'Processo';
    private static $primaryKey = 'id';
    private static $formName = 'form_PreProcessoVincularList';
    private $showMethods = ['onReload', 'onSearch', 'onClearFilters'];
    private $limit = 10;

    /** Id do elemento, dentro da consulta de publicacao, que hospeda o fluxo. */
    const CONTAINER = 'publicacao_vincular_pre_processo';

    public function __construct($param = null)
    {
        parent::__construct();

        $this->publicacao_id = (int) ($param['publicacao_id'] ?? 0);
        $this->nivel = (($param['nivel'] ?? '') === PreProcessoService::NIVEL_PRINCIPAL)
            ? PreProcessoService::NIVEL_PRINCIPAL
            : PreProcessoService::NIVEL_PROCESSO;

        $this->container = $param['target_container'] ?? self::CONTAINER;
        $this->adianti_target_container = $this->container;

        /* Viaja em toda acao desta tela, para tudo voltar ao mesmo lugar. */
        $contexto = [
            'publicacao_id'    => $this->publicacao_id,
            'nivel'            => $this->nivel,
            'target_container' => $this->container,
        ];

        $this->form = new BootstrapFormBuilder(self::$formName);

        /*
            O titulo vai como conteudo, e nao por setFormTitle: dentro da
            janela da publicacao o cabecalho do BootstrapFormBuilder nao e
            renderizado, e o passo ficaria sem nome nenhum.
        */
        $titulo = new TElement('div');
        $titulo->class = 'curciol-passo-vinculo-titulo';
        $titulo->add(
            $this->nivel === PreProcessoService::NIVEL_PRINCIPAL
                ? 'Escolha o pré-processo que será o processo principal'
                : 'Escolha o pré-processo que esta publicação completa'
        );

        $linha_titulo = $this->form->addContent([$titulo]);
        $linha_titulo->layout = [' col-sm-12'];

        $criteria_cliente = new TCriteria();
        $filterVar = Grupo::CLIENTE;
        $criteria_cliente->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')"));

        $descricao  = new TEntry('descricao_pre_processo');
        $cliente_id = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome', 'nome asc', $criteria_cliente);
        $area_id    = new TDBCombo('area_id', 'escritorio', 'Area', 'id', '{nome}', 'nome asc');

        $descricao->setSize('100%');
        $cliente_id->setSize('100%');
        $area_id->setSize('100%');
        $cliente_id->setMinLength(2);
        $cliente_id->setMask('{nome}');
        $cliente_id->setFilterColumns(['nome', 'cpf_cnpj']);
        $area_id->enableSearch();
        $descricao->placeholder = 'Parte da descrição';

        $linha = $this->form->addFields(
            [new TLabel('Descrição:', null, '13px', null, '100%'), $descricao],
            [new TLabel('Cliente:', null, '13px', null, '100%'), $cliente_id],
            [new TLabel('Área:', null, '13px', null, '100%'), $area_id]
        );
        $linha->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        $this->form->setData(TSession::getValue(__CLASS__ . '_filter_data'));

        $btn_buscar = $this->form->addAction('Buscar', new TAction([$this, 'onSearch'], $contexto), 'fas:search #ffffff');
        $btn_buscar->addStyleClass('btn-primary');

        $this->form->addAction('Limpar filtros', new TAction([$this, 'onClearFilters'], $contexto), 'fas:eraser #dd5a43');

        /*
            Sair do fluxo e apenas esvaziar esta area: a publicacao ja esta
            aberta atras e nao precisa ser recarregada.
        */
        $this->form->addAction('Cancelar', new TAction([__CLASS__, 'onFechar'], ['target_container' => $this->container, 'static' => 1]), 'fas:times #666666');

        $this->datagrid = new TDataGrid;
        $this->datagrid->setId(__CLASS__ . '_datagrid');
        $this->datagrid = new BootstrapDatagridWrapper($this->datagrid);
        $this->datagrid->style = 'width: 100%';

        $col_id        = new TDataGridColumn('id', 'Id', 'left', '60px');
        $col_descricao = new TDataGridColumn('descricao_pre_processo', 'Descrição do pré-processo', 'left');
        $col_clientes  = new TDataGridColumn('id', 'Cliente(s)', 'left');
        $col_contrato  = new TDataGridColumn('id', 'Contrato', 'left', '120px');
        $col_area      = new TDataGridColumn('area->nome', 'Área', 'left');
        $col_assunto   = new TDataGridColumn('assunto->nome', 'Assunto', 'left');
        $col_resp      = new TDataGridColumn('responsavel->nome', 'Responsável', 'left');
        $col_criacao   = new TDataGridColumn('data_criacao', 'Criado em', 'left', '110px');

        $col_descricao->setTransformer(function ($value) {
            $value = trim((string) $value);

            return $value === '' ? '<i>sem descrição</i>' : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        });

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

        foreach ([$col_id, $col_descricao, $col_clientes, $col_contrato, $col_area, $col_assunto, $col_resp, $col_criacao] as $coluna)
        {
            $this->datagrid->addColumn($coluna);
        }

        $action_selecionar = new TDataGridAction(['PreProcessoVincularConfirmacao', 'onShow']);
        $action_selecionar->setUseButton(true);
        $action_selecionar->setButtonClass('btn btn-default btn-sm');
        $action_selecionar->setLabel('Selecionar');
        $action_selecionar->setImage('far:hand-pointer #44bd32');
        $action_selecionar->setField(self::$primaryKey);
        $action_selecionar->setParameter('processo_id', '{id}');
        $action_selecionar->setParameter('publicacao_id', $this->publicacao_id);
        $action_selecionar->setParameter('nivel', $this->nivel);
        $action_selecionar->setParameter('target_container', $this->container);
        $this->datagrid->addAction($action_selecionar);

        $this->datagrid->createModel();

        $this->pageNavigation = new TPageNavigation;
        $this->pageNavigation->enableCounters();
        $this->pageNavigation->setAction(new TAction([$this, 'onReload'], $contexto));
        $this->pageNavigation->setWidth($this->datagrid->getWidth());

        /*
            Grid e paginacao entram no mesmo formulario, e nao num TPanelGroup
            separado: dois cartoes empilhados dentro da publicacao produziam
            duas faixas cinza escuras, uma do rodape de cada um.
        */
        $envolucro = new TElement('div');
        $envolucro->class = 'table-responsive';
        $envolucro->add($this->datagrid);

        $linha_grid = $this->form->addContent([$envolucro]);
        $linha_grid->layout = [' col-sm-12'];

        $linha_paginacao = $this->form->addContent([$this->pageNavigation]);
        $linha_paginacao->layout = [' col-sm-12'];

        /*
            A area do fluxo precisa se ler como um passo inserido na tela, e
            nao como mais um campo da publicacao. A aparencia esta em
            app/lib/include/css/curciol-portal.css, secao 11.
        */
        $caixa = new TElement('div');
        $caixa->class = 'curciol-passo-vinculo';
        $caixa->add($this->form);

        parent::add($caixa);
    }

    /**
     * Esvazia a area do fluxo. A publicacao continua como estava.
     */
    public static function onFechar($param = null)
    {
        $container = $param['target_container'] ?? self::CONTAINER;

        TScript::create("$('#" . addslashes($container) . "').html('');");
    }

    /**
     * Somente pre-processos pendentes: registros ja convertidos nao estao
     * disponiveis para receber outro numero.
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
            'offset'           => 0,
            'first_page'       => 1,
            'publicacao_id'    => $param['publicacao_id'] ?? $this->publicacao_id,
            'nivel'            => $param['nivel'] ?? $this->nivel,
            'target_container' => $param['target_container'] ?? $this->container,
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
                O TPageNavigation deste tema desenha dez botoes de pagina
                mesmo quando tudo cabe numa. Numa listagem inteira isso passa;
                dentro deste cartao, dez numeros para duas linhas dominam a
                area. Cabendo em uma pagina, sobra so a contagem.
            */
            if ($count <= $this->limit)
            {
                $area = addslashes($this->container);

                TScript::create("$('#{$area} .tpagenavigation ul, #{$area} .tpagenavigation .pagination').hide();");
            }

            /*
                Lista vazia nao pode virar beco sem saida: o caminho e
                cadastrar o pre-processo antes, e o usuario precisa saber.
            */
            if ($count == 0)
            {
                TToast::show(
                    'info',
                    'Nenhum pré-processo disponível. Cadastre-o em Processos, marcando "É um pré-processo?", e volte a esta publicação.',
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
