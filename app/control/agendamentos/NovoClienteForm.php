<?php

class NovoClienteForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Pessoa';
    private static $primaryKey = 'id';
    private static $formName = 'form_NovoClienteForm';

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
        $this->form->setFormTitle("Cliente");

        $criteria_tipo_pessoa_id = new TCriteria();

        $tipo_pessoa_id = new TDBCombo('tipo_pessoa_id', 'escritorio', 'TipoPessoa', 'id', '{nome}','nome asc' , $criteria_tipo_pessoa_id );
        $nome = new TEntry('nome');
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $aceita_receber_mensagen_whatsapp = new TCheckButton('aceita_receber_mensagen_whatsapp');

        $nome->addValidation("Nome", new TRequiredValidator()); 
        $telefone->addValidation("Telefone", new TRequiredValidator()); 
        $email->addValidation("E-mail", new TRequiredValidator()); 
        $email->addValidation("E-mail", new TEmailValidator(), []); 

        $tipo_pessoa_id->enableSearch();
        $nome->forceUpperCase();
        $email->forceLowerCase();
        $aceita_receber_mensagen_whatsapp->setUseSwitch(true, 'green');
        $aceita_receber_mensagen_whatsapp->setIndexValue("T");
        $nome->setMaxLength(255);
        $email->setMaxLength(255);
        $telefone->setMaxLength(20);

        $nome->setSize('100%');
        $email->setSize('100%');
        $telefone->setSize('100%');
        $tipo_pessoa_id->setSize('100%');

        TScript::create(
            "$(document).on('keydown', 'input[name=\"telefone\"]', function (e) {
            var digit = e.key.replace(/\D/g, '');
            var value = $(this).val().replace(/\D/g, '');
            var size = value.concat(digit).length;
            $(this).mask((size <= 10) ? '(##) ####-####' : '(##) #####-####');
            });"
        );
        $row1 = $this->form->addFields([new TLabel("Tipo de pessoa:", '#FF0000', '12px', null, '100%'),$tipo_pessoa_id],[new TLabel("Nome:", '#ff0000', '12px', null, '100%'),$nome]);
        $row1->layout = ['col-sm-4',' col-sm-8'];

        $row2 = $this->form->addFields([new TLabel("Telefone:", '#FF0000', '12px', null, '100%'),$telefone],[new TLabel("E-mail:", '#FF0000', '12px', null, '100%'),$email]);
        $row2->layout = [' col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Aceita receber informações sobre atualizações do seus agendamentos por whatsapp", null, '12px', null, '100%'),$aceita_receber_mensagen_whatsapp,new TLabel(new TImage('fas:info-circle #03A9F4')."Alguns exemplos de interação são lembrete de consulta, confirmação de agendamento", '#607D8B', '8px', 'I', '100%')]);
        $row3->layout = [' col-sm-12'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Cadastrar e continuar agendamento", new TAction([$this, 'onSave']), 'fas:arrow-right #ffffff');
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

        $style = new TStyle('right-panel > .container-part[page-name=NovoClienteForm]');
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

            $object = new Pessoa(); // create an empty object 

            $data = $this->form->getData(); // get form data as array

            $data->telefone = preg_replace('/[^0-9]/', '', $data->telefone);

            $object->fromArray( (array) $data); // load the object with data

            $username = str_replace(' ', '_', $object->nome);
            $username = TextService::slug($username);
            $username = str_replace('_', '.', $username);
            $object->usuario = $username;

            if($object->aceita_receber_mensagen_whatsapp || $object->aceita_receber_mensagen_whatsapp=="T"){
                $object->aceita_receber_mensagen_whatsapp = "T";
            }else{
                $object->aceita_receber_mensagen_whatsapp = "F";
            }

            $consulta = Pessoa::where('usuario', '=', $username)->first();
            $count = 1;

            // gerando um nome de usuario unico
            while($consulta)
            {
                $object->usuario = $username.$count;
                $consulta = Pessoa::where('usuario', '=', $object->usuario)->first();
                $count++;
            }

            $bytes = openssl_random_pseudo_bytes(3);
            $pwd = bin2hex($bytes);

            $object->senha = $pwd;

            $object->criacao_user_id = TSession::getValue('userid');

            $object->store(); // save the object 

            //if (Pessoa::where('documento', '=', $data->documento)->count() > 1)
            //{
            //    throw new Exception('CPF já utilizado');
            //}

            $pessoaGrupo = new PessoaGrupo();
            $pessoaGrupo->pessoa_id = $object->id;
            $pessoaGrupo->grupo_id = Grupo::CLIENTE;
            $pessoaGrupo->store();

            $agenda = Agenda::find(TSession::getValue('portal_agenda_id'));

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data

            //MensagemService::enviarEmailCredenciais($object, $agenda->escritorio);

            TTransaction::close(); // close the transaction

            TToast::show('success', "Sucesso! você recebera um e-mail com o seu usuário e sua senha para acessar seus agendamentos e .documentos.", 'topRight', 'far:check-circle'); 

            TSession::setValue('portal_cliente_id', $object->id);

            $param = ['date' => TSession::getValue('portal_date'), 'agenda_id' => TSession::getValue('portal_agenda_id')];

            TApplication::loadPage('AgendamentoClienteCalendarForm', 'onStartEdit', $param);
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

                $object = new Pessoa($key); // instantiates the Active Record 

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

        $data = new stdClass;
        $data->aceita_receber_mensagen_whatsapp = 'T';
        $this->form->setData($data);

        TSession::setValue('portal_date', $param['date']);
        TSession::setValue('portal_agenda_id', $param['agenda_id']);
    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

