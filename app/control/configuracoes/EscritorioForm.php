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
        $url_apchat = new TEntry('url_apchat');
        $token_apchat = new TPassword('token_apchat');
        $button_testar_apchat = new TButton('button_testar_apchat');
        $button_enviar_teste_apchat = new TButton('button_enviar_teste_apchat');
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
        $token_apchat->enableToggleVisibility(true);

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $cep->setMask('99999-999', true);
        $cnpj->setMask('##.###.###/####-##');
        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');
        $cidade_id->setMask('{nome} - {estado->sigla}');

        $button_testar1->setAction(new TAction([$this, 'onTestarEnvioEmail']), "Testar");
        $button_testar_apchat->setAction(new TAction([$this, 'onTestarApchat'], ['static' => 1]), "Testar conexão");
        $button_enviar_teste_apchat->setAction(new TAction([$this, 'onEnviarTesteApchat'], ['static' => 1]), "Enviar teste");
        $button_buscar->setAction(new TAction([$this, 'onChangeCEP'],['static' => 1]), "Buscar");

        $button_buscar->addStyleClass('btn-default');
        $button_testar1->addStyleClass('btn-default');
        $button_testar_apchat->addStyleClass('btn-default');
        $button_enviar_teste_apchat->addStyleClass('btn-default');

        $button_buscar->setImage('fas:search #2196F3');
        $button_testar1->setImage('fas:paper-plane #4CAF50');
        $button_testar_apchat->setImage('fas:plug #4CAF50');
        $button_enviar_teste_apchat->setImage('fab:whatsapp #4CAF50');

        $id->setEditable(false);
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
        $bairro->setSize('100%');
        $numero->setSize('100%');
        $telefone->setSize('100%');
        $endereco->setSize('100%');
        $username->setSize('100%');
        $password->setSize('100%');
        $cidade_id->setSize('100%');
        $smtp_auth->setSize('100%');
        $from_name->setSize('100%');
        $from_email->setSize('100%');
        $url_sistema->setSize('100%');
        $complemento->setSize('100%');
        $email_config_id->setSize(200);
        $data_criacao->setSize('100%');
        $system_unit_id->setSize('100%');
        $logo_documento->setSize('100%');
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

        /*
            APCHAT: endereco e token da API externa (Bearer), usados pela sincronizacao de contatos
            (APChatContactService) e pelo envio de WhatsApp do agendamento (ApchatApiService). Trocar a URL
            muda o canal do APChat por onde as mensagens saem.
        */
        $url_apchat->setSize('100%');
        $token_apchat->setSize('100%');
        $url_apchat->placeholder = 'https://api.apchat.com.br/v2/api/external/código-da-integração';

        $apchat_container = new BContainer('apchat_container');
        $apchat_container->setTitle("APChat (WhatsApp)", '#25D366', '13px', 'B', '#fff');
        $apchat_container->setBorderColor('#25D366');

        $row15b = $apchat_container->addFields([new TLabel("URL da API:", null, '14px', null, '100%'), $url_apchat],[new TLabel("Token da API:", null, '14px', null, '100%'), $token_apchat]);
        $row15b->layout = [' col-sm-7',' col-sm-5'];

        $row15d = $apchat_container->addFields([$button_testar_apchat, $button_enviar_teste_apchat]);
        $row15d->layout = [' col-sm-12'];

        $row15c = $this->form->addFields([$apchat_container]);
        $row15c->layout = [' col-sm-12'];

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

    /**
     * Confere a URL e o token da tela (antes de salvar) sem enviar mensagem.
     */
    public static function onTestarApchat($param = null)
    {
        try 
        {
            ApchatApiService::testarConexao((string) ($param['url_apchat'] ?? ''), (string) ($param['token_apchat'] ?? ''));

            new TMessage('info', 'Conexão com o APChat funcionando: URL e token aceitos.');
        }
        catch (Exception $e) 
        {
            new TMessage('error', $e->getMessage());    
        }
    }

    /**
     * Envia uma mensagem de teste para o numero digitado, pela URL e token SALVOS (o token nao vai na acao do dialogo).
     */
    public static function onEnviarTesteApchat($param = null)
    {
        try 
        {
            TTransaction::open(self::$database);

            $escritorio = ApchatApiService::escritorio();
            $url = ApchatApiService::url($escritorio);
            $token = $escritorio ? trim((string) $escritorio->token_apchat) : '';

            // botao da tela: o que esta na tela tem de ser o salvo (o dialogo nao manda URL nem token)
            $urlTela = rtrim(trim((string) ($param['url_apchat'] ?? $url)), '/');
            $tokenTela = trim((string) ($param['token_apchat'] ?? $token));

            if ($urlTela !== $url || $tokenTela !== $token)
            {
                throw new Exception('A URL ou o token da tela não estão salvos. Salve antes de enviar o teste (o "Testar conexão" confere sem salvar).');
            }

            if (!empty($param['enviar']) && $param['enviar'] == 'T')
            {
                $numero = APChatContactService::normalizarTelefone($param['to'] ?? '');

                if ($numero === '')
                {
                    throw new Exception('Número inválido. Use DDD + número.');
                }

                ApchatApiService::enviar($url, $token, APChatContactService::numeroCanonico($numero),
                    'Teste de envio da Curciol pelo APChat em ' . date('d/m/Y H:i') . '. Pode ignorar esta mensagem.',
                    'curciol-teste-' . date('YmdHis'));

                TToast::show('success', 'Mensagem de teste enviada para ' . PessoaContatoService::formatarTelefone($numero));
            }
            else
            {
                ApchatApiService::testarConexao($url, $token);

                $form = new BootstrapFormBuilder('input_form');
                $form->setFieldSizes('100%');
                $to = new TEntry('to');
                $to->placeholder = '(19) 99999-9999';

                $form->addFields([new TLabel('Número que vai receber o teste', null, null, null, '100%'), $to]);
                $form->addAction('Enviar', new TAction([__CLASS__, 'onEnviarTesteApchat'], ['enviar' => 'T']), 'fas:paper-plane #4CAF50');

                new TInputDialog('Enviar teste pelo APChat', $form);
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
            $data->url_apchat = rtrim(trim((string) $data->url_apchat), '/');
            $data->token_apchat = trim((string) $data->token_apchat);

            $object->fromArray( (array) $data); // load the object with data

            $logo_documento_dir = 'app/escritorios/logo_documentos';  

            if(!$data->id){
                $object->criacao_user_id = TSession::getValue('userid');
            }else{
                $object->modificacao_user_id = TSession::getValue('userid');
            }
            $object->store(); // save the object 

            // fromArray traz o id do escritorio: o id da configuracao vem depois
            $emailConfig = new EmailConfig();
            $emailConfig->fromArray( (array) $data);
            $emailConfig->id = $data->email_config_id ?: null;
            $emailConfig->escritorio_id = $object->id;
            $emailConfig->store();

            $data->email_config_id = $emailConfig->id;

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
            Navegacao::voltarParaLista('EscritoriosList', __CLASS__);

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

                $emailConfig = EmailConfig::where('escritorio_id', '=', $id)->first();

                if ($emailConfig)
                {
                    $data = array_merge($data, $emailConfig->toArray());
                    $data['email_config_id'] = $emailConfig->id;
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

}
