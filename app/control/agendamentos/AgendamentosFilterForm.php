<?php
/**
 * Tela Agendamentos > Agendamentos (agenda). Curciol (29/10/2026): visual repaginado no padrao do Adianti:
 * - cabecalho: Visualizar como listagem, Adicionar (compromisso, bloqueio e excecao), Cliente, Novo agendamento,
 *   Limpar filtros, Atualizar;
 * - filtros numa linha so: clientes, agendas e periodo juntos a esquerda, a legenda no canto direito;
 * - filtros: clientes e agendas (FiltroMultiplo: campo fino, lista que so fecha ao clicar fora, "Todas" no topo,
 *   marcados empilhados abaixo, agendas com a cor), periodo (o calendario abre so pelo icone; vem com hoje e hoje,
 *   que nao filtra: a agenda abre na semana) e a Legenda, que abre/fecha ao lado;
 * - o calendario (AgendamentoCalendarFormView) le os filtros da sessao (agendamentos_filter_data /
 *   agendamentos_filter_agenda_id) e rola junto com a pagina.
 */
class AgendamentosFilterForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_AgendamentosFilterForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param = null)
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // tela aberta pelo menu: o profissional ja ve a agenda dele
        if (($param['method'] ?? '') === 'onShow')
        {
            self::filtroInicial();
        }
        $filtro = self::filtroDaSessao();

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Agenda");

        TTransaction::open(self::$database);
        $userId = (int) TSession::getValue('userid');
        $unidade = (int) TSession::getValue('userunitid');
        // agendas da unidade em que o usuario e profissional (relacionado ou responsavel)
        $agendas = [];
        $st = TTransaction::get()->query("SELECT a.id, a.nome, a.cor FROM agenda a JOIN escritorio e ON e.id = a.escritorio_id
                                          WHERE e.system_unit_id = {$unidade}
                                            AND (a.id IN (SELECT ap.agenda_id FROM agenda_profissional ap JOIN pessoa p ON p.id = ap.profissional_id WHERE p.system_users_id = {$userId})
                                              OR a.profissional_id IN (SELECT p.id FROM pessoa p WHERE p.system_users_id = {$userId}))
                                          ORDER BY a.nome");
        foreach ($st->fetchAll(PDO::FETCH_OBJ) as $a) { $agendas[(int) $a->id] = ['nome' => $a->nome, 'cor' => AgendaVisual::cores($a->cor)['cor']]; }
        $agendasMarcadas = [];
        foreach ($filtro->agenda_id as $id) {
            $a = $agendas[$id] ?? (($x = Agenda::find($id)) ? ['nome' => $x->nome, 'cor' => AgendaVisual::cores($x->cor)['cor']] : null);
            if ($a) { $agendasMarcadas[$id] = $a; }
        }
        $clientesMarcados = [];
        foreach ($filtro->cliente_id as $id) {
            if ($p = Pessoa::find($id)) { $clientesMarcados[$id] = ['nome' => $p->nome]; }
        }
        $estados = EstadoAgenda::where('id', '>', 0)->orderBy('id')->load();
        TTransaction::close();

        $aplicar = "__adianti_post_data('" . self::$formName . "', 'class=" . __CLASS__ . "&method=onFilter&static=1');";
        $clientes = FiltroMultiplo::campo('cliente_id', 'Buscar cliente', 'Todos da lista', [], $clientesMarcados, $aplicar,
                                          'engine.php?class=' . __CLASS__ . '&method=onBuscarClientes&static=1', 3);
        $agendasCampo = FiltroMultiplo::campo('agenda_id', 'Buscar agenda', 'Todas as agendas', $agendas, $agendasMarcadas, $aplicar);

        $periodo = new BDateRange('dt_busca', 'dt_busca_fim');
        $periodo->setMask('dd/mm/yyyy');
        $periodo->setDatabaseMask('yyyy-mm-dd');
        $periodo->setExitAction(new TAction([__CLASS__, 'onFilter'], ['static' => '1']));
        $periodo->setSize('100%');
        // sem periodo filtrado, mostra hoje e hoje (nao filtra: a agenda abre na semana)
        $periodo->setValue($filtro->dt_busca ?: date('Y-m-d'));
        $periodo->setEndValue($filtro->dt_busca_fim ?: ($filtro->dt_busca ?: date('Y-m-d')));

        $calendario = new BPageContainer();
        $calendario->setId('pageAgendaFormView');
        $calendario->setAction(new TAction(['AgendamentoCalendarFormView', 'onShow'], $param));
        $calendario->setSize('100%');

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';
        $icon = new TElement('i');
        $icon->class = ' fa-spin fa-3x';
        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');
        $calendario->add($loadingContainer);
        $this->calendario = $calendario;

        // uma linha so: clientes, agendas e periodo juntos a esquerda; a legenda no canto direito
        $this->form->addField($periodo);
        $bloco = function (string $classe, string $rotulo, $campo) {
            $div = new TElement('div');
            $div->class = 'agd-filtro ' . $classe;
            $div->add(new TLabel($rotulo, null, '14px', null));
            $div->add($campo);
            return $div;
        };
        $corExemplo = $agendasMarcadas ? reset($agendasMarcadas)['cor'] : ($agendas ? reset($agendas)['cor'] : null);
        $legenda = new TElement('div');
        $legenda->class = 'agd-filtro agd-filtro-legenda';
        $legenda->add(AgendaVisual::botaoLegenda());
        $legenda->add(AgendaVisual::legenda($estados ?: [], $corExemplo));

        $linha = new TElement('div');
        $linha->class = 'agd-filtros';
        $linha->add($bloco('agd-filtro-largo', 'Clientes:', $clientes));
        $linha->add($bloco('agd-filtro-largo', 'Agendas:', $agendasCampo));
        $linha->add($bloco('agd-filtro-periodo', 'Período:', $periodo));
        $linha->add($legenda);

        $row1 = $this->form->addFields([$linha]);
        $row1->layout = ['col-sm-12'];

        $row3 = $this->form->addFields([$calendario]);
        $row3->layout = [' col-sm-12'];

        // create the form actions
        $btn_onshow = $this->form->addHeaderAction("Visualizar como listagem", new TAction(['AgendaAgendamentoList', 'onShow']), 'fas:list-ul #009688');
        $this->btn_onshow = $btn_onshow;

        // compromisso, bloqueio e excecao juntos num menu
        $adicionar = new TDropDown('Adicionar', 'fas:calendar-plus #2196F3');
        $adicionar->setButtonClass('btn btn-default btn-sm dropdown-toggle');
        $adicionar->addAction('Compromisso', new TAction(['CompromissoForm', 'onShow']), 'fas:briefcase #000000');
        $adicionar->addAction('Bloqueio', new TAction(['BloqueioForm', 'onShow']), 'fas:ban #607D8B');
        $adicionar->addAction('Exceção', new TAction(['AgendamentoExcecao', 'onShow']), 'fas:calendar-plus #2196F3');
        $this->form->addHeaderWidget($adicionar);

        $btnCliente = $this->form->addHeaderAction("Cliente", new TAction(['ClienteForm', 'onShow']), 'fas:user-plus #4CAF50');
        $this->btnCliente = $btnCliente;

        $btn_onshow = $this->form->addHeaderAction("Novo agendamento", new TAction(['AgendamentoFormBtn', 'onShow']), 'fas:calendar-plus #9C27B0');
        $this->btn_onshow = $btn_onshow;

        $this->form->addHeaderAction("Limpar filtros", new TAction([__CLASS__, 'onLimparFiltros'], ['static' => '1']), 'fas:eraser #dd5a43');

        $btnAtualiza = $this->form->addHeaderAction("Atualizar", new TAction([$this, 'onAtualiza']), 'fas:sync-alt #000000');
        $this->btnAtualiza = $btnAtualiza;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Agendamentos","Agendamentos"]));
        }
        $container->add($this->form);

        $btnCliente->getAction()->setParameter('origin', 'calendar');

        parent::add($container);
        parent::add(FiltroMultiplo::estilo());
        parent::add(AgendaVisual::estilo());
        parent::add(TScript::create(FiltroMultiplo::script(), false));
        parent::add(TScript::create(AgendaVisual::script(), false));
        parent::add(TScript::create(self::scriptPeriodo(), false));
    }

    /** O calendario do periodo abre so pelo icone (clicar no campo deixa digitar a data). */
    private static function scriptPeriodo(): string
    {
        $form = self::$formName;
        return <<<JS
setTimeout(function () {
    var caixa = $('form[name="{$form}"] [name="dt_busca"]').closest('.bdaterange-container');
    var picker = caixa.data('picker');
    if (!picker || caixa.data('so-icone')) { return; }
    caixa.data('so-icone', true);
    caixa.find('input').each(function () {
        this.addEventListener('click', function (e) { e.stopImmediatePropagation(); }, true);
        this.addEventListener('focus', function (e) { e.stopImmediatePropagation(); }, true);
    });
    caixa.find('.bdaterange-icon').css('cursor', 'pointer').off('click.agd').on('click.agd', function (e) {
        e.preventDefault(); e.stopPropagation();
        if (picker.isShown()) { picker.hide(); } else { picker.show(); }
    });
}, 400);
JS;
    }

    /** Filtro guardado na sessao, normalizado (formato antigo tinha um cliente so). */
    private static function filtroDaSessao(): stdClass
    {
        $s = TSession::getValue('agendamentos_filter_data') ?: new stdClass;
        $ids = function ($v) { return array_values(array_unique(array_filter(array_map('intval', is_array($v) ? $v : ($v === null || $v === '' ? [] : [$v]))))); };
        $f = new stdClass;
        $f->cliente_id = $ids($s->cliente_id ?? null);
        $f->agenda_id = $ids($s->agenda_id ?? TSession::getValue('agendamentos_filter_agenda_id'));
        $f->dt_busca = SqlSeguro::data($s->dt_busca ?? '') ?: '';
        $f->dt_busca_fim = SqlSeguro::data($s->dt_busca_fim ?? '') ?: '';
        return $f;
    }

    private static function gravarFiltro(stdClass $f): void
    {
        TSession::setValue('agendamentos_filter_agenda_id', $f->agenda_id);
        TSession::setValue('agendamentos_filter_data', $f);
    }

    /** Ao abrir pelo menu: sem filtros, e o profissional com a agenda dele marcada. */
    private static function filtroInicial(): void
    {
        $f = (object) ['cliente_id' => [], 'agenda_id' => [], 'dt_busca' => '', 'dt_busca_fim' => ''];
        TTransaction::open(self::$database);
        $profissional = Pessoa::where('system_users_id', '=', TSession::getValue('userid'))->first();
        if ($profissional && PessoaGrupo::where('pessoa_id', '=', $profissional->id)->where('grupo_id', '=', Grupo::PROFISSIONAL)->count() > 0) {
            $agenda = Agenda::where('profissional_id', '=', $profissional->id)->first();
            if ($agenda) { $f->agenda_id[] = (int) $agenda->id; }
        }
        TTransaction::close();
        self::gravarFiltro($f);
    }

    /** Aplica os filtros (clientes e agendas ao fechar a lista, periodo ao escolher) e recarrega o calendario. */
    public static function onFilter($param = null)
    {
        try 
        {
            $ids = function ($v) { return array_values(array_unique(array_filter(array_map('intval', is_array($v) ? $v : ($v === null || $v === '' ? [] : [$v]))))); };
            $f = new stdClass;
            $f->cliente_id = $ids($param['cliente_id'] ?? null);
            $f->agenda_id = $ids($param['agenda_id'] ?? null);
            $f->dt_busca = SqlSeguro::data($param['dt_busca'] ?? '') ?: '';
            $f->dt_busca_fim = SqlSeguro::data($param['dt_busca_fim'] ?? '') ?: '';
            // hoje a hoje e o padrao do campo: nao filtra (a agenda continua na semana)
            $hoje = date('Y-m-d');
            if ($f->dt_busca === $hoje && in_array($f->dt_busca_fim, ['', $hoje], true)) {
                $f->dt_busca = '';
                $f->dt_busca_fim = '';
            }
            self::gravarFiltro($f);
            self::recarregarCalendario();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    /** Busca de clientes para o filtro (a partir de 3 letras): [{id, nome}]. */
    public static function onBuscarClientes($param = null)
    {
        $texto = trim((string) ($param['q'] ?? ''));
        $lista = [];
        if (mb_strlen($texto) >= 3) {
            TTransaction::open(self::$database);
            $st = TTransaction::get()->prepare('SELECT id, nome FROM pessoa WHERE id IN (SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = ?)
                                                AND unaccent(nome) ILIKE unaccent(?) ORDER BY nome LIMIT 50');
            $st->execute([Grupo::CLIENTE, '%' . $texto . '%']);
            foreach ($st->fetchAll(PDO::FETCH_OBJ) as $p) { $lista[] = ['id' => (int) $p->id, 'nome' => $p->nome]; }
            TTransaction::close();
        }
        echo json_encode($lista);
    }

    public static function onLimparFiltros($param = null)
    {
        self::gravarFiltro((object) ['cliente_id' => [], 'agenda_id' => [], 'dt_busca' => '', 'dt_busca_fim' => '']);
        TApplication::loadPage(__CLASS__, 'onRecarregar');
    }

    /** Recarrega a tela com os filtros da sessao (depois de limpar). */
    public function onRecarregar($param = null)
    {
    }

    /** O calendario le os filtros e as agendas ao montar (horarios): recarrega o quadro dele. */
    private static function recarregarCalendario(): void
    {
        TApplication::loadPage('AgendamentoCalendarFormView', 'onShow', ['target_container' => 'pageAgendaFormView', 'register_state' => 'false', 'show_loading' => 'false']);
    }

    public function onAtualiza($param = null) 
    {
        try 
        {
            TScript::create("$('#agendamentos').data('fullcalendar').refetchEvents()");

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onShow($param = null)
    {               
        // o filtro inicial (agenda do profissional) e montado no construtor, antes de desenhar os campos
    } 

}
