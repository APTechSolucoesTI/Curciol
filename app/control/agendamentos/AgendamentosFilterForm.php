<?php
/**
 * Tela Agendamentos > Agendamentos (agenda). Curciol (29/10/2026): visual repaginado no padrao do Adianti:
 * - cabecalho: Visualizar como listagem, Adicionar (compromisso, bloqueio e excecao), Cliente, Novo agendamento, Atualizar;
 * - filtros que se aplicam ao mudar: varios clientes e varias agendas (lista com caixa de selecao) e o periodo
 *   (inicio e fim); Limpar filtros;
 * - o calendario (AgendamentoCalendarFormView) traz a faixa das agendas e o "Calendario e legenda".
 * Os filtros ficam na sessao (agendamentos_filter_data / agendamentos_filter_agenda_id), lidos pelo calendario.
 */
class AgendamentosFilterForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
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

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Agenda");

        $criteria_cliente_id = new TCriteria();
        $criteria_agenda_id = new TCriteria();

        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_agenda_id->add(new TFilter('escritorio_id', '=', $filterVar)); 

        $filterVar = TSession::getValue('userid');

        $criteriaAcesso = new TCriteria;
        // está como um profissional relacionado com a agenda
        $criteriaAcesso->add(new TFilter('id', 'in', "(SELECT agenda.id FROM agenda, agenda_profissional, pessoa WHERE pessoa.id = agenda_profissional.profissional_id AND agenda.id = agenda_profissional.agenda_id AND system_users_id = '{$filterVar}')"));
        // oyu é o profissional responsável da agenda
        $criteriaAcesso->add(new TFilter('id', 'in', "(SELECT agenda.id FROM agenda, pessoa WHERE pessoa.id = agenda.profissional_id AND system_users_id = '{$filterVar}')"), TExpression::OR_OPERATOR); 

        $criteria_agenda_id->add($criteriaAcesso);

        $filtrar = new TAction([__CLASS__, 'onFilter'], ['static' => '1']);

        $cliente_id = new TDBMultiSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );
        $agenda_id = new TDBMultiSearch('agenda_id', 'escritorio', 'Agenda', 'id', 'nome','nome asc' , $criteria_agenda_id );
        $periodo = new BDateRange('dt_busca', 'dt_busca_fim');
        $calendario = new BPageContainer();

        $cliente_id->setMinLength(3);
        $cliente_id->setMask('{nome_formatado}');
        $cliente_id->setChangeAction($filtrar);
        $agenda_id->setMinLength(0);
        $agenda_id->setMask('{nome}');
        $agenda_id->setChangeAction($filtrar);
        $periodo->setMask('dd/mm/yyyy');
        $periodo->setDatabaseMask('yyyy-mm-dd');
        $periodo->setExitAction($filtrar);

        $limpar = new TButton('btn_limpar_filtros');
        $limpar->setAction(new TAction([__CLASS__, 'onLimparFiltros'], ['static' => '1']), 'Limpar filtros');
        $limpar->addStyleClass('btn-default');
        $limpar->setImage('fas:eraser #dd5a43');

        $calendario->setId('pageAgendaFormView');
        $calendario->setAction(new TAction(['AgendamentoCalendarFormView', 'onShow'], $param));

        $cliente_id->setSize('100%');
        $agenda_id->setSize('100%');
        $periodo->setSize('100%');
        $calendario->setSize('100%');

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = ' fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $calendario->add($loadingContainer);

        $this->calendario = $calendario;

        $row1 = $this->form->addFields([new TLabel("Clientes:", null, '14px', null, '100%'),$cliente_id],[new TLabel("Agendas:", null, '14px', null, '100%'),$agenda_id],[new TLabel("Período:", null, '14px', null, '100%'),$periodo],[new TLabel("&nbsp;", null, '14px', null, '100%'),$limpar]);
        $row1->layout = ['col-sm-4',' col-sm-4','col-sm-2','col-sm-2'];

        $row2 = $this->form->addFields([$calendario]);
        $row2->layout = [' col-sm-12'];

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

        $data = TSession::getValue('agendamentos_filter_data');
        if ($data) {
            // sessao do formato antigo (um cliente so)
            foreach (['cliente_id', 'agenda_id'] as $campo) {
                $v = $data->$campo ?? [];
                $data->$campo = array_values(array_filter(array_map('intval', is_array($v) ? $v : ($v === '' || $v === null ? [] : [$v]))));
            }
            $this->form->setData($data);
        }

        parent::add($container);
        parent::add(self::estiloCaixas());
    }

    /** Clientes e agendas: lista com caixa de selecao (fica aberta para marcar varios). */
    private static function estiloCaixas()
    {
        $css = '<style>
            .select2-dropdown.agd-check .select2-results__option { position: relative; padding-left: 28px; }
            .select2-dropdown.agd-check .select2-results__option::before { content: ""; position: absolute; left: 8px; top: 50%; width: 13px; height: 13px; margin-top: -7px;
                border: 1px solid #8694b0; border-radius: 3px; background: #fff; }
            .select2-dropdown.agd-check .select2-results__option[aria-selected=true]::before { background: #528bb7; border-color: #528bb7;
                box-shadow: inset 0 0 0 2px #fff; }
            .select2-dropdown.agd-check .select2-results__option[aria-selected=true] { background: #edf3f9; color: #122f4e; }
            .select2-dropdown.agd-check .select2-results__option.select2-results__message::before { display: none; }
        </style>';
        $js = "setTimeout(function () {
            var campos = $('form[name=" . self::$formName . "]').find('select[name=\"cliente_id[]\"], select[name=\"agenda_id[]\"]');
            campos.each(function () { var s2 = $(this).data('select2'); if (s2 && s2.options) { s2.options.set('closeOnSelect', false); } });
            campos.off('select2:open.agd').on('select2:open.agd', function () { setTimeout(function () { $('.select2-container--open .select2-dropdown').addClass('agd-check'); }, 0); });
        }, 300);";
        $div = new TElement('div');
        $div->add($css);
        $div->add(TScript::create($js, false));
        return $div;
    }

    /** Aplica os filtros (ao mudar clientes, agendas ou periodo) e recarrega o calendario. */
    public static function onFilter($param = null)
    {
        try 
        {
            $ids = function ($v) { return array_values(array_filter(array_map('intval', is_array($v) ? $v : ($v === null || $v === '' ? [] : [$v])))); };
            $data = new stdClass;
            $data->cliente_id = $ids($param['cliente_id'] ?? null);
            $data->agenda_id = $ids($param['agenda_id'] ?? null);
            $data->dt_busca = SqlSeguro::data($param['dt_busca'] ?? '') ?: '';
            $data->dt_busca_fim = SqlSeguro::data($param['dt_busca_fim'] ?? '') ?: '';

            TSession::setValue('agendamentos_filter_agenda_id', $data->agenda_id);
            TSession::setValue('agendamentos_filter_data', $data);

            self::recarregarCalendario();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onLimparFiltros($param = null)
    {
        $data = new stdClass;
        $data->cliente_id = [];
        $data->agenda_id = [];
        $data->dt_busca = '';
        $data->dt_busca_fim = '';
        TSession::setValue('agendamentos_filter_agenda_id', []);
        TSession::setValue('agendamentos_filter_data', $data);
        TForm::sendData(self::$formName, (object) ['dt_busca' => '', 'dt_busca_fim' => '']);
        TScript::create("$('form[name=" . self::$formName . "]').find('select[name=\"cliente_id[]\"], select[name=\"agenda_id[]\"]').val(null).trigger('change.select2');");
        self::recarregarCalendario();
    }

    /** O calendario le os filtros e as agendas ao montar (horarios, faixa das agendas): recarrega o quadro dele. */
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

        TTransaction::open('escritorio');

        $object = new stdClass();
        $object->agenda_id = [];
        $object->cliente_id = [];
        $object->dt_busca = '';
        $object->dt_busca_fim = '';
        $profissional = Pessoa::where('system_users_id','=',TSession::getValue('userid'))->first();
        if($profissional){
            $grupo = PessoaGrupo::where('pessoa_id','=',$profissional->id)->where('grupo_id','=',Grupo::PROFISSIONAL)->count();
            if($grupo>0){
                $agenda = Agenda::where('profissional_id','=',$profissional->id)->first();
                if($agenda){

                    $object->agenda_id[] = $agenda->id;
                }
            }

            TForm::sendData(self::$formName, $object);
            TSession::setValue('agendamentos_filter_data', $object);
            TSession::setValue('agendamentos_filter_agenda_id', $object->agenda_id);
        }
        TTransaction::close();

    } 

}
