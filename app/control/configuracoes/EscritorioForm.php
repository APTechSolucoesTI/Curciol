<?php

class EscritorioForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'Escritorio';
    private static $primaryKey = 'id';
    private static $formName = 'form_Escritorio';

    use Adianti\Base\AdiantiFileSaveTrait;
    use BuilderMasterDetailFieldListTrait;

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
        $this->form->setFormTitle("Cadastro de escritório jurídico");

        $criteria_system_unit_id = new TCriteria();
        $criteria_cidade_id = new TCriteria();
        $criteria_escritorio_parceiro_escritorio_parceiro_id = new TCriteria();

        $id = new TEntry('id');
        $nome = new TEntry('nome');
        $system_unit_id = new TDBCombo('system_unit_id', 'escritorio', 'SystemUnit', 'id', '{name}','name asc' , $criteria_system_unit_id );
        $cnpj = new TEntry('cnpj');
        $telefone = new TEntry('telefone');
        $email = new TEntry('email');
        $logo_documento = new TFile('logo_documento');
        $url_sistema = new TEntry('url_sistema');
        $cep = new TEntry('cep');
        $button_buscar = new TButton('button_buscar');
        $cidade_id = new TDBUniqueSearch('cidade_id', 'escritorio', 'Cidade', 'id', 'nome','nome asc' , $criteria_cidade_id );
        $bairro = new TEntry('bairro');
        $endereco = new TEntry('endereco');
        $numero = new TEntry('numero');
        $complemento = new TEntry('complemento');
        $escritorio_parceiro_escritorio_id = new THidden('escritorio_parceiro_escritorio_id[]');
        $escritorio_parceiro_escritorio___row__id = new THidden('escritorio_parceiro_escritorio___row__id[]');
        $escritorio_parceiro_escritorio___row__data = new THidden('escritorio_parceiro_escritorio___row__data[]');
        $escritorio_parceiro_escritorio_parceiro_id = new TDBCombo('escritorio_parceiro_escritorio_parceiro_id[]', 'escritorio', 'Parceiro', 'id', '{nome}','nome asc' , $criteria_escritorio_parceiro_escritorio_parceiro_id );
        $this->fieldList_60f6decfd616d = new TFieldList();
        $api_key = new TPassword('api_key');
        $whatsapp_config_id = new THidden('whatsapp_config_id');
        $api_token = new TPassword('api_token');
        $phone = new TEntry('phone');
        $status = new TEntry('status');
        $device = new TEntry('device');
        $button_gerar_qrcode = new TButton('button_gerar_qrcode');
        $button_desconectar = new TButton('button_desconectar');
        $button_testar = new TButton('button_testar');
        $host = new TEntry('host');
        $email_config_id = new THidden('email_config_id');
        $smtp_auth = new TCombo('smtp_auth');
        $username = new TEntry('username');
        $password = new TPassword('password');
        $from_email = new TEntry('from_email');
        $from_name = new TEntry('from_name');
        $port = new TEntry('port');
        $button_testar1 = new TButton('button_testar1');
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $this->fieldList_60f6decfd616d->addField(null, $escritorio_parceiro_escritorio_id, []);
        $this->fieldList_60f6decfd616d->addField(null, $escritorio_parceiro_escritorio___row__id, ['uniqid' => true]);
        $this->fieldList_60f6decfd616d->addField(null, $escritorio_parceiro_escritorio___row__data, []);
        $this->fieldList_60f6decfd616d->addField(new TLabel("Parceiro", null, '14px', null), $escritorio_parceiro_escritorio_parceiro_id, ['width' => '100%']);

        $this->fieldList_60f6decfd616d->width = '100%';
        $this->fieldList_60f6decfd616d->setFieldPrefix('escritorio_parceiro_escritorio');
        $this->fieldList_60f6decfd616d->name = 'fieldList_60f6decfd616d';

        $this->criteria_fieldList_60f6decfd616d = new TCriteria();
        $this->default_item_fieldList_60f6decfd616d = new stdClass();

        $this->form->addField($escritorio_parceiro_escritorio_id);
        $this->form->addField($escritorio_parceiro_escritorio___row__id);
        $this->form->addField($escritorio_parceiro_escritorio___row__data);
        $this->form->addField($escritorio_parceiro_escritorio_parceiro_id);

        $this->fieldList_60f6decfd616d->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $nome->addValidation("nome", new TRequiredValidator()); 
        $system_unit_id->addValidation("Unidade", new TRequiredValidator()); 
        $cnpj->addValidation("CNPJ", new TRequiredValidator()); 
        $telefone->addValidation("Telefone", new TRequiredValidator()); 
        $email->addValidation("E-mail", new TRequiredValidator()); 
        $cep->addValidation("CEP", new TRequiredValidator()); 
        $cidade_id->addValidation("Cidade", new TRequiredValidator()); 
        $bairro->addValidation("Bairro", new TRequiredValidator()); 
        $endereco->addValidation("Endereço", new TRequiredValidator()); 
        $escritorio_parceiro_escritorio_parceiro_id->addValidation("Convênio", new TRequiredListValidator()); 
        $cnpj->addValidation("CNPJ", new TCNPJValidator(), []); 

        $nome->forceUpperCase();
        $email->forceLowerCase();
        $logo_documento->enableFileHandling();
        $cidade_id->setMinLength(3);
        $smtp_auth->addItems(["T"=>"Sim","N"=>"Não"]);
        $smtp_auth->enableSearch();
        $api_key->enableToggleVisibility(true);
        $api_token->enableToggleVisibility(true);

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $cep->setMask('99999-999', true);
        $cnpj->setMask('##.###.###/####-##');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $cidade_id->setMask('{nome} - {estado->sigla}');

        $button_testar1->setAction(new TAction([$this, 'onTestarEnvioEmail']), "Testar");
        $button_testar->setAction(new TAction([$this, 'onTestarEnvioWhatsapp']), "Testar");
        $button_desconectar->setAction(new TAction([$this, 'onDesconectar']), "Desconectar");
        $button_buscar->setAction(new TAction([$this, 'onChangeCEP'],['static' => 1]), "Buscar");
        $button_gerar_qrcode->setAction(new TAction([$this, 'onGenerateQrCode']), "Gerar qrcode");

        $button_buscar->addStyleClass('btn-default');
        $button_testar->addStyleClass('btn-default');
        $button_testar1->addStyleClass('btn-default');
        $button_desconectar->addStyleClass('btn-default');
        $button_gerar_qrcode->addStyleClass('btn-default');

        $button_buscar->setImage('fas:search #2196F3');
        $button_testar->setImage('fas:paper-plane #4CAF50');
        $button_gerar_qrcode->setImage('fas:qrcode #4CAF50');
        $button_testar1->setImage('fas:paper-plane #4CAF50');
        $button_desconectar->setImage('fas:power-off #F44336');

        $id->setEditable(false);
        $phone->setEditable(false);
        $status->setEditable(false);
        $device->setEditable(false);
        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(100);
        $nome->setSize('100%');
        $cnpj->setSize('100%');
        $host->setSize('100%');
        $port->setSize('100%');
        $email->setSize('100%');
        $phone->setSize('100%');
        $bairro->setSize('100%');
        $numero->setSize('100%');
        $status->setSize('100%');
        $device->setSize('100%');
        $api_key->setSize('100%');
        $telefone->setSize('100%');
        $endereco->setSize('100%');
        $username->setSize('100%');
        $password->setSize('100%');
        $cidade_id->setSize('100%');
        $api_token->setSize('100%');
        $smtp_auth->setSize('100%');
        $from_name->setSize('100%');
        $from_email->setSize('100%');
        $url_sistema->setSize('100%');
        $complemento->setSize('100%');
        $email_config_id->setSize(200);
        $data_criacao->setSize('100%');
        $system_unit_id->setSize('100%');
        $logo_documento->setSize('100%');
        $whatsapp_config_id->setSize(200);
        $data_modificacao->setSize('100%');
        $cep->setSize('calc(100% - 120px)');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $escritorio_parceiro_escritorio_parceiro_id->setSize('100%');


        $cnpj->addValidation("CNPJ", new TCNPJValidator()); 
        $email->addValidation("E-mail", new TEmailValidator()); 

        TScript::create(
            "$(document).on('keydown', 'input[name=\"telefone\"]', function (e) {
            var digit = e.key.replace(/\D/g, '');
            var value = $(this).val().replace(/\D/g, '');
            var size = value.concat(digit).length;
            $(this).mask((size <= 10) ? '(##) ####-####' : '(##) #####-####');
            });"
        );

        $this->form->appendPage("Dados cadastrais");

        $this->form->addFields([new THidden('current_tab')]);
        $this->form->setTabFunction("$('[name=current_tab]').val($(this).attr('data-current_page'));");

        $row1 = $this->form->addFields([new TLabel("Código:", null, '14px', null, '100%'),$id],[]);
        $row1->layout = ['col-sm-6','col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("Nome:", '#ff0000', '14px', null, '100%'),$nome],[new TLabel("Unidade:", '#ff0000', '14px', null, '100%'),$system_unit_id]);
        $row2->layout = [' col-sm-8',' col-sm-4'];

        $row3 = $this->form->addFields([new TLabel("CNPJ:", '#ff0000', '14px', null, '100%'),$cnpj],[new TLabel("Telefone:", '#ff0000', '14px', null, '100%'),$telefone],[new TLabel("E-mail:", '#ff0000', '14px', null, '100%'),$email]);
        $row3->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row4 = $this->form->addFields([new TLabel("Logo para documentos:", null, '14px', null, '100%'),$logo_documento],[new TLabel("URL da aplicação:", null, '14px', null),$url_sistema]);
        $row4->layout = ['col-sm-4',' col-sm-8'];

        $this->form->appendPage("Endereço");
        $row5 = $this->form->addFields([new TLabel("CEP:", '#ff0000', '14px', null, '100%'),$cep,$button_buscar],[new TLabel("Cidade:", '#ff0000', '14px', null, '100%'),$cidade_id]);
        $row5->layout = [' col-sm-4',' col-sm-8'];

        $row6 = $this->form->addFields([new TLabel("Bairro:", '#ff0000', '14px', null, '100%'),$bairro],[new TLabel("Endereço:", '#ff0000', '14px', null, '100%'),$endereco]);
        $row6->layout = [' col-sm-4',' col-sm-8'];

        $row7 = $this->form->addFields([new TLabel("Número:", null, '14px', null, '100%'),$numero],[new TLabel("Complemento:", null, '14px', null, '100%'),$complemento]);
        $row7->layout = [' col-sm-4',' col-sm-8'];

        $this->form->appendPage("Parceiros");
        $row8 = $this->form->addFields([$this->fieldList_60f6decfd616d]);
        $row8->layout = [' col-sm-12'];

        $this->form->appendPage("Configurações");

        $whatsapp_container = new BContainer('whatsapp_container');
        $this->whatsapp_container = $whatsapp_container;

        $whatsapp_container->setTitle("Whatsapp", '#4CAF50', '13px', 'B', '#fff');
        $whatsapp_container->setBorderColor('#4CAF50');

        $row9 = $whatsapp_container->addFields([new TLabel("ID:", null, '14px', null),$api_key,$whatsapp_config_id],[new TLabel("Token:", null, '14px', null),$api_token]);
        $row9->layout = ['col-sm-6','col-sm-6'];

        $row10 = $whatsapp_container->addFields([new TLabel("Número:", null, '14px', null),$phone],[new TLabel("Status:", null, '14px', null),$status],[new TLabel("Device:", null, '14px', null),$device]);
        $row10->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row11 = $whatsapp_container->addFields([new TLabel("Conexão:", null, '14px', null, '100%'),$button_gerar_qrcode,$button_desconectar,$button_testar]);
        $row11->layout = [' col-sm-12'];

        $row12 = $this->form->addFields([$whatsapp_container]);
        $row12->layout = [' col-sm-12'];

        $mail_container = new BContainer('mail_container');
        $this->mail_container = $mail_container;

        $mail_container->setTitle("E-mail", '#F44336', '13px', 'B', '#fff');
        $mail_container->setBorderColor('#F44336');

        $row13 = $mail_container->addFields([new TLabel("Host:", null, '14px', null),$host,$email_config_id],[new TLabel("Autenticar SMTP:", null, '14px', null),$smtp_auth],[new TLabel("Usuário:", null, '14px', null),$username],[new TLabel("Senha:", null, '14px', null),$password]);
        $row13->layout = [' col-sm-3',' col-sm-3',' col-sm-3',' col-sm-3'];

        $row14 = $mail_container->addFields([new TLabel("E-mail origem", null, '14px', null),$from_email],[new TLabel("Nome origem", null, '14px', null),$from_name],[new TLabel("Porta:", null, '14px', null),$port],[new TLabel(" ", null, '14px', null, '100%'),$button_testar1]);
        $row14->layout = [' col-sm-3',' col-sm-3','col-sm-3',' col-sm-3'];

        $row15 = $this->form->addFields([$mail_container]);
        $row15->layout = [' col-sm-12'];

        $this->form->appendPage("Informações de cadastro");
        $row16 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row16->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Cancelar", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Sair", new TAction(['EscritoriosList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=EscritorioForm]');
        $style->width = '70% !important';   
        $style->show(true);

    }

    public static function onChangeCEP($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);
            $dadosCEP = CEPService::get($param['cep']);
            TTransaction::close();

            $data = new stdClass;
            $data->cidade_id = $dadosCEP->cidade_id;
            $data->bairro = $dadosCEP->bairro;
            $data->endereco = $dadosCEP->rua;

            TForm::sendData(self::$formName, $data);

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onGenerateQrCode($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            if (empty($param['whatsapp_config_id']))
            {
                throw new Exception('Salve as configurações de whastapp antes');
            }

            $whatsappConfig = WhatsappConfig::find((int)$param['whatsapp_config_id']);
            $cliente = new WhatsAppClient($whatsappConfig);

            $image = $cliente->getQrCode();

            $window = TWindow::create('QR Code', 500, null);
            $window->add(TElement::tag('div', '', ['style' => "background-image: url({$image});", 'id' => 'qrcode']));
            $window->setCloseAction(new TAction([__CLASS__, 'refreshStatus'], $param));
            $window->show();

            TTransaction::close();

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onDesconectar($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            if (empty($param['whatsapp_config_id']))
            {
                throw new Exception('Salve as configurações de whastapp antes');
            }

            $whatsappConfig = WhatsappConfig::find((int)$param['whatsapp_config_id']);
            $cliente = new WhatsAppClient($whatsappConfig);

            $image = $cliente->disconnect();
            $data = (object) ['status' => 'Não conectado', 'phone' => ''];

            TForm::sendData(self::$formName, $data);

            TTransaction::close();

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onTestarEnvioWhatsapp($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            if (empty($param['whatsapp_config_id']))
            {
                throw new Exception('Salve as configurações de WhatsApp antes');
            }

            $whastappConfig = WhatsappConfig::find((int)$param['whatsapp_config_id']);

            if (! empty($param['enviar']) && $param['enviar'] == 'T' && ! empty($param['to']))
            {
                $cliente = new WhatsAppClient($whastappConfig);

                $cliente->testarEnvio($param['to']);

                TToast::show('success', 'Mensagem de teste enviada');
            }
            else
            {
                $form = new BootstrapFormBuilder('input_form');
                $form->setFieldSizes('100%');
                $to  = new TEntry('to');
                $to->placeholder = 'ex: ' . $whastappConfig->phone;

                $form->addFields( [new TLabel('Número', null, null, null, '100%'), $to]);
                $param['enviar'] = 'T';
                $form->addAction('Enviar', new TAction([__CLASS__, 'onTestarEnvioWhatsapp'], $param), 'fas:paper-plane #4CAF50');

                // show the input dialog
                new TInputDialog('Enviar teste', $form);
            }

            TTransaction::close();

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public static function onTestarEnvioEmail($param = null) 
    {
        try 
        {
            TTransaction::open(self::$database);

            if (empty($param['email_config_id']))
            {
                throw new Exception('Salve as configurações de e-mail antes');
            }

            $emailConfig = EmailConfig::find((int)$param['email_config_id']);

            if (! empty($param['enviar']) && $param['enviar'] == 'T' && ! empty($param['to']))
            {
                $cliente = new EmailClient($emailConfig);

                $cliente->testarEnvio($param['to']);

                TToast::show('success', 'E-mail de teste enviado');
            }
            else
            {
                $form = new BootstrapFormBuilder('input_form');
                $form->setFieldSizes('100%');

                $to  = new TEntry('to');
                $to->setValue($emailConfig->from_email);

                $form->addFields( [new TLabel('E-mail', null, null, null, '100%'), $to]);
                $param['enviar'] = 'T';
                $form->addAction('Enviar', new TAction([__CLASS__, 'onTestarEnvioEmail'], $param), 'fas:paper-plane #4CAF50');

                // show the input dialog
                new TInputDialog('Enviar teste', $form);
            }

            TTransaction::close();

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Escritorio(); // create an empty object 

            $data = $this->form->getData(); // get form data as array

            $data->telefone = preg_replace('/[^0-9]/', '', $data->telefone);
            $data->cnpj = preg_replace('/[^0-9]/', '', $data->cnpj);

            $object->fromArray( (array) $data); // load the object with data

            $logo_documento_dir = 'app/escritorios/logo_documentos';  

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object 

            $whatsappConfig = new WhatsappConfig();
            $whatsappConfig->id = $data->whatsapp_config_id??null;
            $whatsappConfig->escritorio_id = $object->id;
            $whatsappConfig->fromArray( (array) $data);
            $whatsappConfig->store();

            $emailConfig = new EmailConfig();
            $emailConfig->id = $data->email_config_id??null;
            $emailConfig->escritorio_id = $object->id;
            $emailConfig->fromArray( (array) $data);
            $emailConfig->store();

            $data->email_config_id = $emailConfig->id;
            $data->whatsapp_config_id = $whatsappConfig->id;

            $this->saveFile($object, $data, 'logo_documento', $logo_documento_dir);
            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            $escritorio_parceiro_escritorio_items = $this->storeItems('EscritorioParceiro', 'escritorio_id', $object, $this->fieldList_60f6decfd616d, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_60f6decfd616d); 

            if(!$data->id)
            {
                EscritorioService::criaTemplatesPadroes($object->id);
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('EscritoriosList', 'onShow', $loadPageParam); 

            TForm::sendData(self::$formName, (object)['id' => $object->id]);

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

                $object = new Escritorio($key); // instantiates the Active Record 

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->fieldList_60f6decfd616d_items = $this->loadItems('EscritorioParceiro', 'escritorio_id', $object, $this->fieldList_60f6decfd616d, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_60f6decfd616d); 
                $id = (int) $object->id;

                $data = $object->toArray();

                $whatsappConfig = WhatsappConfig::where('escritorio_id', '=', $id)->first();
                $emailConfig = EmailConfig::where('escritorio_id', '=', $id)->first();

                if ($whatsappConfig && $emailConfig)
                {
                    $data['whatsapp_config_id'] = $whatsappConfig->id;
                    $data['email_config_id'] = $emailConfig->id;

                    $whatsappConfig = WhatsappConfig::find($data['whatsapp_config_id']);

                    if(!empty($whatsappConfig->api_token) && !empty($whatsappConfig->api_key))
                    {
                        try 
                        {
                            $cliente = new WhatsAppClient($whatsappConfig);

                            $whatsappConfig->status = $cliente->getStatus();
                            $whatsappConfig->phone = $cliente->getNumber();
                            $whatsappConfig->store();    
                        } 
                        catch (Exception $e) 
                        {
                            $whatsappConfig->phone = '';
                            $whatsappConfig->status = 'Não conectado';
                            $whatsappConfig->store();
                        }
                    }

                    $data = array_merge($data, $whatsappConfig->toArray(), $emailConfig->toArray());
                }
                else
                {
                    TScript::create("$('#qrcode').closest('.tformrow').hide()");
                }

                $data['id'] = $id;

                $object = (object)  $data;

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

        $this->fieldList_60f6decfd616d->addHeader();
        $this->fieldList_60f6decfd616d->addDetail($this->default_item_fieldList_60f6decfd616d);

        $this->fieldList_60f6decfd616d->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    }

    public function onShow($param = null)
    {
        $this->fieldList_60f6decfd616d->addHeader();
        $this->fieldList_60f6decfd616d->addDetail($this->default_item_fieldList_60f6decfd616d);

        $this->fieldList_60f6decfd616d->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

    } 

    public static function getFormName()
    {
        return self::$formName;
    }

    public static function refreshStatus($param)
    {
        try
        {
            TTransaction::open(self::$database);

            $wc = WhatsappConfig::find((int)$param['whatsapp_config_id']);
            $cliente = new WhatsAppClient($wc);
            $wc->status = $cliente->getStatus();
            $wc->phone = $cliente->getNumber();
            $wc->store();

            $data = (object) ['status' => $wc->status, 'phone' => $wc->number];

            TForm::sendData(self::$formName, $data);

            TTransaction::close();
        }
        catch(Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
        finally
        {
            TWindow::closeWindow();
        }
    }

}

