<?php

class AgendamentoClienteCalendarForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Agendamento';
    private static $primaryKey = 'id';
    private static $formName = 'form_AgendamentoClienteCalendarForm';
    private static $startDateField = 'dt_inicial';
    private static $endDateField = 'dt_final';

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

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Agendamento cliente");

        $view = new THidden('view');

        $criteria_cliente_id = new TCriteria();

        $filterVar = TSession::getValue('portal_cliente_id');
        $criteria_cliente_id->add(new TFilter('id', '=', $filterVar)); 
        $filterVar = Grupo::CLIENTE;
        $criteria_cliente_id->add(new TFilter('id', 'in', "(SELECT pessoa_id FROM pessoa_grupo WHERE grupo_id = '{$filterVar}')")); 

        $id = new TEntry('id');
        $agenda_id = new THidden('agenda_id');
        $cliente_id = new TDBCombo('cliente_id', 'escritorio', 'Pessoa', 'id', '{nome}','nome asc' , $criteria_cliente_id );
        $dt_inicial = new TDateTime('dt_inicial');
        $observacao = new TEntry('observacao');
        $online = new TRadioGroup('online');

        $dt_inicial->addValidation("Data", new TRequiredValidator()); 

        $dt_inicial->setMask('dd/mm/yyyy hh:ii');
        $dt_inicial->setDatabaseMask('yyyy-mm-dd hh:ii');
        $online->addItems(["T"=>"Online","F"=>"Presencial"]);
        $online->setLayout('horizontal');
        $online->setValue('F');
        $online->setUseButton();
        $id->setEditable(false);
        $cliente_id->setEditable(false);
        $dt_inicial->setEditable(false);

        $id->setSize('100%');
        $agenda_id->setSize(200);
        $online->setSize('100%');
        $cliente_id->setSize('100%');
        $dt_inicial->setSize('100%');
        $observacao->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id,$agenda_id],[new TLabel("Cliente:", null, '14px', null),$cliente_id],[new TLabel("Data:", null, '14px', null, '100%'),$dt_inicial]);
        $row1->layout = [' col-sm-2',' col-sm-6','col-sm-4'];

        $row2 = $this->form->addFields([new TLabel("Observação:", null, '14px', null, '100%'),$observacao]);
        $row2->layout = [' col-sm-12'];

        $row3 = $this->form->addFields([new TLabel("Tipo de atendimento:", null, '14px', null, '100%'),$online]);
        $row3->layout = ['col-sm-6'];

        $this->form->addFields([$view]);

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=AgendamentoClienteCalendarForm]');
        $style->width = '50% !important';   
        $style->show(true);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Agendamento(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->dt_final = date('Y-m-d H:i:s', strtotime($object->dt_inicial . " + {$object->agenda->duracao} minutes"));
            $object->estado_agenda_id = EstadoAgenda::AGUARDANDO_VALIDACAO_ESCRITORIO;
            $object->cliente_id = TSession::getValue('portal_cliente_id');

            if (! $object->validate())
            {
                throw new Exception('Verifique o horário. Existem bloqueios ou horarios de intervalo sobrepostos');
            }

            if ($object->aceita_agendamento_online == 'F')
            {
                $object->online = 'F';
            }

            if ($object->online == 'T')
            {
                $object->link_atendimento_online = implode('-', [bin2hex(random_bytes(4)),bin2hex(random_bytes(2)),bin2hex(chr((ord(random_bytes(1)) & 0x0F) | 0x40)) . bin2hex(random_bytes(1)),bin2hex(chr((ord(random_bytes(1)) & 0x3F) | 0x80)) . bin2hex(random_bytes(1)),bin2hex(random_bytes(6))]);
            }

            $object->store(); // save the object 

            $estadoAgendamento = new EstadoAgendamento();
            $estadoAgendamento->agendamento_id = $object->id;
            $estadoAgendamento->estado_agenda_id = $object->estado_agenda_id;
            $estadoAgendamento->atribuido_em = date('Y-m-d H:i:s');
            $estadoAgendamento->system_users_id = null;
            $estadoAgendamento->store();

            $messageAction = new TAction(['AgendamentoClienteCalendarFormView', 'onReload']);
            $messageAction->setParameter('view', $data->view);
            $messageAction->setParameter('date', explode(' ', $data->dt_inicial)[0]);

            $messageAction->setParameter('agenda_id', $object->agenda_id);
            $messageAction->setParameter('key', $object->agenda_id);

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            new TMessage('info', "Agendamento reservado! Aguarde o contato da clínica para mais detalhes.", $messageAction); 

                        TScript::create("Template.closeRightPanel();"); 

        }
        catch (Exception $e) // in case of exception
        {

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Agendamento($key); // instantiates the Active Record 

                                $object->view = !empty($param['view']) ? $param['view'] : 'agendaWeek'; 

                $this->form->setData($object); // fill the form 

                TTransaction::close(); // close the transaction 
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(true);

    }

    public function onShow($param = null)
    {

    } 

    public function onStartEdit($param)
    {

        $this->form->clear(true);

        $data = new stdClass;
        $data->view = $param['view'] ?? 'agendaWeek'; // calendar view
        $data->agenda = new stdClass();
        $data->agenda->cor = '#3a87ad';

        if (!empty($param['date']))
        {
            if(strlen($param['date']) == '10')
                $param['date'].= ' 09:00';

            $data->dt_inicial = str_replace('T', ' ', $param['date']);

            $dt_final = new DateTime($data->dt_inicial);
            $dt_final->add(new DateInterval('PT1H'));
            $data->dt_final = $dt_final->format('Y-m-d H:i:s');

            TTransaction::open(self::$database);
            $agenda = Agenda::find($param['agenda_id']);
            $data->agenda_id = $agenda->id;
            $data->cliente_id = TSession::getValue('portal_cliente_id');

            if ($agenda->aceita_agendamento_online == 'F')
            {
                TScript::create("$('[name=online]').closest('.tformrow').hide()");
            }

            TTransaction::close();

        }

        $this->form->setData( $data );
    }

    public static function onUpdateEvent($param)
    {
        try
        {
            if (isset($param['id']))
            {
                TTransaction::open(self::$database);

                $class = self::$activeRecord;
                $object = new $class($param['id']);

                $object->dt_inicial = str_replace('T', ' ', $param['start_time']);
                $object->dt_final   = str_replace('T', ' ', $param['end_time']);

                $object->store();

                // close the transaction
                TTransaction::close();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', '<b>Error</b> ' . $e->getMessage());
            TTransaction::rollback();
        }
    }

    public static function getFormName()
    {
        return self::$formName;
    }

}

