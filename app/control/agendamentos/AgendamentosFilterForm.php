<?php

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

        $cliente_id = new TDBUniqueSearch('cliente_id', 'escritorio', 'Pessoa', 'id', 'nome','nome asc' , $criteria_cliente_id );
        $agenda_id = new TDBMultiSearch('agenda_id', 'escritorio', 'Agenda', 'id', 'nome','nome asc' , $criteria_agenda_id );
        $dt_busca = new TDate('dt_busca');
        $button_filtrar = new TButton('button_filtrar');
        $calendario = new BPageContainer();


        $dt_busca->setDatabaseMask('yyyy-mm-dd');
        $button_filtrar->addStyleClass('btn-primary');
        $button_filtrar->setImage('fas:search #FFFFFF');
        $calendario->setId('pageAgendaFormView');
        $agenda_id->setMinLength(3);
        $cliente_id->setMinLength(3);

        $button_filtrar->setAction(new TAction([$this, 'onFilter']), "Filtrar");
        $calendario->setAction(new TAction(['AgendamentoCalendarFormView', 'onShow'], $param));

        $agenda_id->setMask('{nome}');
        $dt_busca->setMask('dd/mm/yyyy');
        $cliente_id->setMask('{nome_formatado}');

        $dt_busca->setSize(150);
        $cliente_id->setSize('100%');
        $calendario->setSize('100%');
        $agenda_id->setSize('100%', 40);

        $loadingContainer = new TElement('div');
        $loadingContainer->style = 'text-align:center; padding:50px';

        $icon = new TElement('i');
        $icon->class = ' fa-spin fa-3x';

        $loadingContainer->add($icon);
        $loadingContainer->add('<br>Carregando');

        $calendario->add($loadingContainer);

        $this->calendario = $calendario;

        $row1 = $this->form->addFields([new TLabel("Cliente:", null, '14px', null),$cliente_id],[new TLabel("Agenda:", null, '14px', null),$agenda_id],[new TLabel("Data:", null, '14px', null, '100%'),$dt_busca,$button_filtrar]);
        $row1->layout = ['col-sm-3',' col-sm-6','col-sm-3'];

        $row2 = $this->form->addFields([$calendario]);
        $row2->layout = [' col-sm-12'];

        // create the form actions

        $btn_onshow = $this->form->addHeaderAction("Visualizar como listagem", new TAction(['AgendaAgendamentoList', 'onShow']), 'fas:list-ul #009688');
        $this->btn_onshow = $btn_onshow;

        $btn_onshow = $this->form->addHeaderAction("Adicionar compromisso", new TAction(['CompromissoForm', 'onShow']), 'fas:calendar-times #000000');
        $this->btn_onshow = $btn_onshow;

        $btn_onshow = $this->form->addHeaderAction("Adicionar bloqueio", new TAction(['BloqueioForm', 'onShow']), 'fas:ban #607D8B');
        $this->btn_onshow = $btn_onshow;

        $btn_onshow = $this->form->addHeaderAction("Adicionar Exceção", new TAction(['AgendamentoExcecao', 'onShow']), 'fas:calendar-plus #2196F3');
        $this->btn_onshow = $btn_onshow;

        $btnCliente = $this->form->addHeaderAction("Cliente", new TAction(['ClienteForm', 'onShow']), 'fas:user-plus #4CAF50');
        $this->btnCliente = $btnCliente;

        $btn_onshow = $this->form->addHeaderAction("Novo Agendamento", new TAction(['AgendamentoFormBtn', 'onShow']), 'fas:calendar-plus #9C27B0');
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
        $this->form->setData($data);

        parent::add($container);

    }

    public  function onFilter($param = null) 
    {
        try 
        {
            $data = $this->form->getData();
            $this->form->setData($data);

            TSession::setValue('agendamentos_filter_agenda_id', $data->agenda_id);
            TSession::setValue('agendamentos_filter_data', $data);

         $this->onAtualiza();

        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
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
        $profissional = Pessoa::where('system_users_id','=',TSession::getValue('userid'))->first();
        if($profissional){
            $grupo = PessoaGrupo::where('pessoa_id','=',$profissional->id)->where('grupo_id','=',Grupo::PROFISSIONAL)->count();
            if($grupo>0){
                $agenda = Agenda::where('profissional_id','=',$profissional->id)->first();
                if($agenda){

                    $object->agenda_id[] = $agenda->id;
                }
            }

            $object->cliente_id = '';
            $object->dt_busca = '';

            TForm::sendData(self::$formName, $object);
            TSession::setValue('agendamentos_filter_data', $object);
            TSession::setValue('agendamentos_filter_agenda_id', $object->agenda_id);
        }
        TTransaction::close();

    } 

}

