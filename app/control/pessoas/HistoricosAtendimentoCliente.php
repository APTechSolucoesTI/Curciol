<?php

class HistoricosAtendimentoCliente extends TPage
{
    protected $form; // form
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'formView_Pessoa';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        TTransaction::open(self::$database);
        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');

        $pessoa = new Pessoa($param['key']);
        // define the form title
        $this->form->setFormTitle("Histórico de atendimentos do cliente");

        $bpagecontainer2 = new BPageContainer();

        $bpagecontainer2->setSize('100%');
        $bpagecontainer2->setAction(new TAction(['AtendimentoTimeLine', 'onShow'], ['cliente_id' => $pessoa->id]));
        $bpagecontainer2->setId('b652eaef03aef2');

        $this->form->appendPage("Listagem");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $this->atendimento_cliente_id_list = new TQuickGrid;
        $this->atendimento_cliente_id_list->style = 'width:100%';
        $this->atendimento_cliente_id_list->disableDefaultClick();

        // 11/11/2026: uma acao so (uma coluna, lupa alinhada a esquerda): onAbrir abre o atendimento da Curciol ou, se a linha for
        // um ticket do APChat, so a conversa (com "Abrir no APChat")
        $action_abrir = new TDataGridAction([__CLASS__, 'onAbrir']);
        $action_abrir->setUseButton(false);
        $action_abrir->setButtonClass('btn btn-default btn-sm');
        $action_abrir->setLabel('Visualizar');
        $action_abrir->setImage('fas:search #2196F3');
        $action_abrir->setField('id');
        $action_abrir->setParameter('key', '{id}');
        $action_abrir->setParameter('ticket_id', '{ticket_id}');
        $action_abrir->setParameter('conversa_id', '{conversa_id}');
        $action_abrir->setParameter('pessoa_id', '{pessoa_id}');
        $action_abrir->setParameter('static', '1');
        $this->atendimento_cliente_id_list->addAction($action_abrir);

        // 11/11/2026: atendimentos da Curciol e tickets do APChat com as mesmas colunas (AtendimentoTimeLine::prepararAtendimento e
        // ticketsDoWhatsapp); Profissional do ticket = atendente atribuido; campo sem valor fica vazio; ID = do atendimento ou do ticket
        $this->atendimento_cliente_id_list->addQuickColumn('Tipo', 'tipo', 'left');
        $this->atendimento_cliente_id_list->addQuickColumn('ID', 'id_texto', 'left');
        $this->atendimento_cliente_id_list->addQuickColumn('Início', 'inicio_texto', 'left');
        $this->atendimento_cliente_id_list->addQuickColumn('Fim', 'fim_texto', 'left');
        $this->atendimento_cliente_id_list->addQuickColumn('Profissional', 'profissional_texto', 'left', '25%');
        $this->atendimento_cliente_id_list->addQuickColumn('Especialidade', 'especialidade_texto', 'left', '25%');
        $this->atendimento_cliente_id_list->addQuickColumn('Fila', 'fila_texto', 'left', '20%');

        $this->atendimento_cliente_id_list->enablePopover("Histórico", " {atendimento_historico_historico_to_string} ");

        $this->atendimento_cliente_id_list->createModel();

        $criteria_atendimento_cliente_id = new TCriteria();
        $criteria_atendimento_cliente_id->add(new TFilter('cliente_id', '=', $pessoa->id));

        $criteria_atendimento_cliente_id->setProperty('order', 'dt_inicio desc');

        $atendimento_cliente_id_items = Atendimento::getObjects($criteria_atendimento_cliente_id) ?: [];
        foreach ($atendimento_cliente_id_items as $item) { AtendimentoTimeLine::prepararAtendimento($item); }

        // APChat (10/11/2026): tickets fechados do APChat na mesma lista, pela data e hora de abertura (11/11)
        // APMail (12/11/2026): conversas de e-mail do cliente na mesma lista, pelo primeiro e-mail
        foreach (AtendimentoTimeLine::conversasDoApmail((int) $pessoa->id) as $conversa)
        {
            $atendimento_cliente_id_items[] = $conversa;
        }
        foreach (AtendimentoTimeLine::ticketsDoWhatsapp((int) $pessoa->id) as $ticket)
        {
            $atendimento_cliente_id_items[] = $ticket;
        }
        usort($atendimento_cliente_id_items, function ($a, $b) { return strcmp((string) $b->dt_inicio, (string) $a->dt_inicio); });

        $this->atendimento_cliente_id_list->addItems($atendimento_cliente_id_items);

        $panel = new TElement('div');
        $panel->class = 'formView-detail';
        $panel->add(new BootstrapDatagridWrapper($this->atendimento_cliente_id_list));

        $this->form->addContent([$panel]);

        $this->form->appendPage("TimeLine");
        $row1 = $this->form->addFields([$bpagecontainer2]);
        $row1->layout = [' col-sm-12'];

        if(!empty($param['current_tab']))
        {
            $this->form->setCurrentPage($param['current_tab']);
        }

        $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoAction = new TAction(['HistoricosAtendimentoCliente', 'addAtendimentoAvulso'],['cliente_id'=>$pessoa->id]);
        $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoLabel = new TLabel("Adicionar");

        $btnHistoricosAtendimentoClienteAddAtendimentoAvulso = $this->form->addHeaderAction($btnHistoricosAtendimentoClienteAddAtendimentoAvulsoLabel, $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoAction, 'fas:plus #4CAF50'); 
        $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoLabel->setFontSize('12px'); 
        $btnHistoricosAtendimentoClienteAddAtendimentoAvulsoLabel->setFontColor('#333'); 

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Pessoas","Histórico de atendimentos do cliente"]));
        }
        $container->add($this->form);

        TTransaction::close();
        parent::add($container);

    }

    public function onShow($param = null)
    {

    }

    /** Lupa da lista (11/11/2026): ticket do APChat abre a conversa; conversa do APMail (12/11) abre os e-mails; atendimento da Curciol abre o atendimento. */
    public static function onAbrir($param = null)
    {
        $conversa = (int) ($param['conversa_id'] ?? 0);
        if ($conversa > 0)
        {
            AdiantiCoreApplication::loadPage('ApmailConversaView', 'onShow', ['key' => $conversa, 'pessoa_id' => (int) ($param['pessoa_id'] ?? 0)]);
            return;
        }
        $ticket = (int) ($param['ticket_id'] ?? 0);
        if ($ticket > 0)
        {
            AdiantiCoreApplication::loadPage('ApchatTicketView', 'onShow', ['key' => $ticket, 'pessoa_id' => (int) ($param['pessoa_id'] ?? 0)]);
            return;
        }
        AdiantiCoreApplication::loadPage('AtendimentoFormView', 'onShow', ['key' => (int) ($param['key'] ?? 0)]);
    }

    public static function addAtendimentoAvulso($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            $atendimento = new Atendimento(); 

            $login = Pessoa::where('system_users_id', '=', TSession::getValue('userid'))->first();
            if($login){
                $atendimento->profissional_id = $login->id;
            }

            if(isset($param['cliente_id'])){
                // 12/11/2026: atendimento avulso (sem agendamento) cria sem exigencia de cadastro
                $atendimento->cliente_id = $param['cliente_id'];
            }
            $atendimento->tipo_atendimento_id = TipoAtendimento::AVULSO;
            $atendimento->dt_inicio = $atendimento->dt_final = date('Y-m-d H:i:s');
            $atendimento->criacao_user_id = TSession::getValue('userid');

            $atendimento->store();

            TScript::create("$(\"[page_name='ClienteForm']\").remove()");

            $pageParam = ['key' => $atendimento->id];
            TApplication::loadPage('AtendimentoFormView', 'onShow', $pageParam);

            TTransaction::close();
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

}

