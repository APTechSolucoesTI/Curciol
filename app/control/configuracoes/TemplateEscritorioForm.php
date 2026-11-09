<?php

class TemplateEscritorioForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'TemplateEscritorio';
    private static $primaryKey = 'id';
    private static $formName = 'form_TemplateClinicaForm';
    private $usadoEm;
    private $assinaturaAtual;

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
        $this->form->setFormTitle("Cadastro de templates");


        $escritorio_id = new THidden('escritorio_id');
        $id = new THidden('id');
        $chave = new THidden('chave');
        $categoria = new TCombo('categoria');
        $descricao = new TEntry('descricao');
        $tipo_template = new TCombo('tipo_template');
        $titulo = new TEntry('titulo');
        $habilitado = new TRadioGroup('habilitado');
        $template = new THtmlEditor('template');
        $template_whatsapp = new TText('template_whatsapp');
        $template_acao_template_escritorio_id = new THidden('template_acao_template_escritorio_id[]');
        $template_acao_template_escritorio___row__id = new THidden('template_acao_template_escritorio___row__id[]');
        $template_acao_template_escritorio___row__data = new THidden('template_acao_template_escritorio___row__data[]');
        $template_acao_template_escritorio_url = new TEntry('template_acao_template_escritorio_url[]');
        $template_acao_template_escritorio_label = new TEntry('template_acao_template_escritorio_label[]');
        $this->fieldList_acoes = new TFieldList();
        $data_criacao = new TDateTime('data_criacao');
        $criacao_user_name = new TEntry('criacao_user_name');
        $data_modificacao = new TDateTime('data_modificacao');
        $modificacao_user_name = new TEntry('modificacao_user_name');

        $this->fieldList_acoes->addField(null, $template_acao_template_escritorio_id, []);
        $this->fieldList_acoes->addField(null, $template_acao_template_escritorio___row__id, ['uniqid' => true]);
        $this->fieldList_acoes->addField(null, $template_acao_template_escritorio___row__data, []);
        $this->fieldList_acoes->addField(new TLabel("Url", null, '14px', null), $template_acao_template_escritorio_url, ['width' => '50%']);
        $this->fieldList_acoes->addField(new TLabel("Label", null, '14px', null), $template_acao_template_escritorio_label, ['width' => '50%']);

        $this->fieldList_acoes->width = '100%';
        $this->fieldList_acoes->setFieldPrefix('template_acao_template_escritorio');
        $this->fieldList_acoes->name = 'fieldList_acoes';

        $this->criteria_fieldList_acoes = new TCriteria();
        $this->default_item_fieldList_acoes = new stdClass();

        $this->form->addField($template_acao_template_escritorio_id);
        $this->form->addField($template_acao_template_escritorio___row__id);
        $this->form->addField($template_acao_template_escritorio___row__data);
        $this->form->addField($template_acao_template_escritorio_url);
        $this->form->addField($template_acao_template_escritorio_label);

        $this->fieldList_acoes->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $tipo_template->setChangeAction(new TAction([$this,'onChangeTipo']));

        $categoria->addValidation("Categoria", new TRequiredValidator());
        $descricao->addValidation("Descrição", new TRequiredValidator()); 
        $tipo_template->addValidation("Tipo", new TRequiredValidator()); 
        $habilitado->addValidation("Habilitar", new TRequiredValidator()); 

        $tipo_template->setDefaultOption(false);
        $habilitado->setLayout('horizontal');
        $habilitado->setUseButton();
        $habilitado->addItems(["T"=>"Sim","F"=>"Não"]);
        $tipo_template->addItems(["EMAIL"=>"E-mail","WHATSAPP"=>"WhatsApp"]);

        $habilitado->setValue('T');
        $tipo_template->setValue('EMAIL');

        $data_criacao->setMask('dd/mm/yyyy hh:ii');
        $data_modificacao->setMask('dd/mm/yyyy hh:ii');

        $data_criacao->setDatabaseMask('yyyy-mm-dd hh:ii');
        $data_modificacao->setDatabaseMask('yyyy-mm-dd hh:ii');

        $categoria->addItems(TemplateEscritorio::CATEGORIAS);
        $categoria->setDefaultOption(false);
        $categoria->setValue(TemplateEscritorio::CONFIRMACAO);
        $categoria->setSize('100%');
        // o titulo e o assunto do e-mail (09/11/2026): sem maiusculas forcadas, que estragavam as variaveis ({$cliente})
        $descricao->forceUpperCase();

        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(200);
        $titulo->setSize('100%');
        $descricao->setSize('100%');
        $escritorio_id->setSize(200);
        $habilitado->setSize('100%');
        $data_criacao->setSize('100%');
        $tipo_template->setSize('100%');
        $template->setSize('100%', 210);
        $template->setProperty('data-autoaltura', '1');
        $template_whatsapp->setProperty('data-autoaltura', '1');
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $template_whatsapp->setSize('100%', 210);
        $template_acao_template_escritorio_url->setSize('100%');
        $template_acao_template_escritorio_label->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Categoria:", '#ff0000', '14px', null, '100%'),$escritorio_id,$id,$chave,$categoria],[new TLabel("Descrição (nome do template na lista):", '#ff0000', '14px', null, '100%'),$descricao],[new TLabel("Tipo:", '#FF0000', '14px', null),$tipo_template]);
        $row1->layout = [' col-sm-3','col-sm-6',' col-sm-3'];

        // de que confirmacao este template e a resposta automatica (preenchido no onEdit)
        $this->usadoEm = new TElement('div');
        $this->form->addFields([$this->usadoEm])->layout = ['col-sm-12'];

        $bcontainer_62e034c1eb8f6 = new BContainer('bcontainer_62e034c1eb8f6');
        $this->bcontainer_62e034c1eb8f6 = $bcontainer_62e034c1eb8f6;

        $bcontainer_62e034c1eb8f6->setTitle("Variávies", '#333', '13px', '', '#fff');
        $bcontainer_62e034c1eb8f6->setBorderColor('#c0c0c0');

        $row2 = $bcontainer_62e034c1eb8f6->addFields([new TLabel("<b>{\$id}</b> Código do agendamento", null, '14px', null, '100%'),new TLabel("<b>{\$endereco_escritorio}</b> Endereço do escritório", null, '14px', null, '100%'),new TLabel("<b>{\$escritorio}</b> Nome do escritório", null, '14px', null, '100%'),new TLabel("<b>{\$estado}</b> Estado atual do agendamento", null, '14px', null, '100%')],[new TLabel("<b>{\$profissional}</b> Nome do profissional", null, '14px', null, '100%'),new TLabel("<b>{\$data_inicial}</b> Data do agendamento", null, '14px', null, '100%'),new TLabel("<b>{\$cliente}</b> Nome do cliente", null, '14px', null, '100%'),new TLabel("<b>{\$profissional_foto}</b> Foto do profissional", null, '14px', null, '100%')],[new TLabel("<b>{\$link_confirmacao}</b> Botão de confirmar (na confirmação por e-mail; nos outros, o link)", null, '14px', null, '100%'),new TLabel("<b>{\$link_cancelamento}</b> Botão de cancelar (na confirmação por e-mail; nos outros, o link)", null, '14px', null, '100%')]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row3 = $bcontainer_62e034c1eb8f6->addFields([new TLabel("<b>{\$telefone_escritorio}</b> Telefone do escritório", null, '14px', null, '100%'),new TLabel("<b>{\$email_escritorio}</b> E-mail do escritório", null, '14px', null, '100%')],[new TLabel("<b>{\$observacao}</b> Observação", null, '14px', null, '100%')],[new TLabel("<b>{\$botoes}</b> Botões de confirmar e cancelar (confirmação por e-mail)", null, '14px', null, '100%')]);
        $row3->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row4 = $this->form->addFields([$bcontainer_62e034c1eb8f6]);
        $row4->layout = [' col-sm-12'];

        // e-mail: e o assunto que o cliente ve (na lista e no envio aparece a Descricao); WhatsApp: so o titulo do historico
        $row5 = $this->form->addFields([new TLabel("<span class='curciol-rotulo-titulo'>Assunto do e-mail:</span>", null, '14px', null, '100%'),$titulo],[new TLabel("Habilitar:", '#ff0000', '14px', null, '100%'),$habilitado]);
        $row5->layout = ['col-sm-9','col-sm-3'];

        $row6 = $this->form->addFields([new TLabel("Template:", null, '14px', null, '100%'),$template]);
        $row6->layout = ['col-sm-12'];

        $row7 = $this->form->addFields([new TLabel("Template:", null, '14px', null, '100%'),$template_whatsapp]);
        $row7->layout = ['col-sm-12'];

        $this->montarExtrasDoEmail();

        // confirmacao por WhatsApp: as duas respostas e o template de cada uma (o e-mail continua so com os links da mensagem)
        $respostas_confirmar = new TEntry('respostas_confirmar');
        $respostas_cancelar = new TEntry('respostas_cancelar');
        $ignorar_maiusculas = new TCheckButton('ignorar_maiusculas');
        $template_confirmado_id = new TDBCombo('template_confirmado_id', 'escritorio', 'TemplateEscritorio', 'id', '{descricao}', 'descricao asc', TemplateRespostaService::criteriaDoRamo('confirmado'));
        $template_cancelado_id = new TDBCombo('template_cancelado_id', 'escritorio', 'TemplateEscritorio', 'id', '{descricao}', 'descricao asc', TemplateRespostaService::criteriaDoRamo('cancelado'));

        $respostas_confirmar->setSize('100%');
        $respostas_cancelar->setSize('100%');
        $respostas_confirmar->setValue('Sim');
        $respostas_cancelar->setValue('Não');
        $ignorar_maiusculas->setIndexValue('T');
        $ignorar_maiusculas->setUseSwitch(true, 'green');
        $ignorar_maiusculas->setValue('T');
        $template_confirmado_id->setSize('100%');
        $template_cancelado_id->setSize('100%');

        $tituloRespostas = new TElement('div');
        $tituloRespostas->id = 'respostas_titulo';
        $tituloRespostas->style = 'border-top:1px solid #ddd;padding-top:10px;';
        $tituloRespostas->add('<b style="font-size:15px">Respostas do cliente</b><br>Vale a resposta inteira. Qualquer outra resposta não muda o agendamento e nada é enviado.');

        $criarTemplate = function (string $ramo) {
            return new TActionLink(' Criar novo template', new TAction([__CLASS__, 'onNovaResposta'], ['ramo' => $ramo, 'static' => 1]), '#1a73e8', '13px', '', 'fas:plus #1a73e8');
        };

        $this->form->addFields([$tituloRespostas])->layout = ['col-sm-12'];
        $rowRespostas = $this->form->addFields([new TLabel("Resposta que confirma o agendamento:", '#ff0000', '14px', null, '100%'), $respostas_confirmar], [new TLabel("Resposta que cancela o agendamento:", '#ff0000', '14px', null, '100%'), $respostas_cancelar]);
        $rowRespostas->layout = ['col-sm-6', 'col-sm-6'];
        $this->form->addFields([new TLabel("Ignorar maiúsculas, minúsculas e acentos (sim serve para SIM e Sim; não serve para nao e NÃO):", null, '14px', null, '100%'), $ignorar_maiusculas])->layout = ['col-sm-12'];
        $rowRespostas = $this->form->addFields(
            [new TLabel("Template enviado depois de confirmar (categoria Agendamento confirmado):", '#ff0000', '14px', null, '100%'), $template_confirmado_id, $criarTemplate('confirmado')],
            [new TLabel("Template enviado depois de cancelar (categoria Cancelamento):", '#ff0000', '14px', null, '100%'), $template_cancelado_id, $criarTemplate('cancelado')]);
        $rowRespostas->layout = ['col-sm-6', 'col-sm-6'];

        // sempre pelo form: os filtros da listagem, atras da cortina, tem os mesmos nomes de campo
        $form = 'form[name=' . self::$formName . ']';
        TScript::create("
            window.curciolRespostasTemplate = function () {
                var mostra = $('{$form} [name=categoria]').val() === '" . TemplateEscritorio::CONFIRMACAO . "' && $('{$form} [name=tipo_template]').val() === '" . TemplateEscritorio::WHATSAPP . "';
                $('{$form} #respostas_titulo, {$form} [name=respostas_confirmar], {$form} [name=ignorar_maiusculas], {$form} [name=template_confirmado_id]').closest('.tformrow').toggle(mostra);
            };
            $(document).off('change.curciolRespostas').on('change.curciolRespostas', '{$form} [name=categoria], {$form} [name=tipo_template]', window.curciolRespostasTemplate);
            $(function () { window.curciolRespostasTemplate(); });
        ");

        $row8 = $this->form->addFields([new TLabel("Ações:", null, '14px', null, '100%')]);
        $row8->layout = [' col-sm-12'];

        $row9 = $this->form->addFields([$this->fieldList_acoes]);
        $row9->layout = ['col-sm-12'];

        $row10 = $this->form->addContent([new TFormSeparator("", '#333', '18', '#797979')]);
        $row11 = $this->form->addFields([new TLabel("Criado em:", null, '14px', null, '100%'),$data_criacao],[new TLabel("Criado por:", null, '14px', null, '100%'),$criacao_user_name],[new TLabel("Atualizado em:", null, '14px', null, '100%'),$data_modificacao],[new TLabel("Atualizado por:", null, '14px', null, '100%'),$modificacao_user_name]);
        $row11->layout = ['col-sm-3','col-sm-3',' col-sm-3',' col-sm-3'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave'],['static' => 1]), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onshow = $this->form->addAction("Sair", new TAction(['TemplateEscritorioHeaderList', 'onShow']), 'fas:arrow-left #000000');
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

        $style = new TStyle('right-panel > .container-part[page-name=TemplateEscritorioForm]');
        $style->width = '85% !important';   
        $style->show(true);

    }

    public static function onChangeTipo($param = null) 
    {
        try 
        {
            if (! empty($param['tipo_template']) && $param['tipo_template'] == 'EMAIL')
            {
                TScript::create("$('[name=template_whatsapp]').closest('.tformrow').hide();");
                TScript::create("$('[name=fieldList_acoes]').closest('.tformrow').hide().prev().hide();");

                TScript::create("$('[name=template]').closest('.tformrow').show();");
            }
            else
            {
                // acoes (links) nao vao mais na mensagem: o cliente responde SIM ou NAO (03/11/2026)
                TScript::create("$('[name=template_whatsapp]').closest('.tformrow').show();");
                TScript::create("$('[name=fieldList_acoes]').closest('.tformrow').hide().prev().hide();");

                TScript::create("$('[name=template]').closest('.tformrow').hide();");
            }

            // o campo que apareceu ganha a altura do texto (escondido nao tem altura para medir)
            TScript::create("if (window.curciolAutoAltura) { window.curciolAutoAltura(); }");
        }
        catch (Exception $e) 
        {
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

            $object = new TemplateEscritorio(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data
            $object->escritorio_id = Escritorio::findByUnitId(TSession::getValue('userunitid'))->id;

            // template do sistema nao muda de canal; template novo ganha a chave sozinho (quem escolhe e a categoria)
            $gravado = !empty($data->id) ? TemplateEscritorio::find($data->id) : null;
            if ($gravado && $gravado->readonly == 'T')
            {
                $object->tipo_template = $gravado->tipo_template;
            }
            if (empty($object->chave))
            {
                $object->chave = $gravado && $gravado->chave ? $gravado->chave : 'TEMPLATE_' . strtoupper(uniqid());
            }

            $object->ignorar_maiusculas = !empty($data->ignorar_maiusculas) ? 'T' : 'F';
            $object->template_confirmado_id = $data->template_confirmado_id ?: null;
            $object->template_cancelado_id = $data->template_cancelado_id ?: null;

            // assinatura (09/11/2026): sem escolha vale a padrao do escritorio; a assinatura "Outra" gravada continua
            $object->assinatura_modo = $object->assinatura_modo ?: TemplateEscritorio::ASSINATURA_PADRAO;
            $object->assinatura_id = $gravado ? $gravado->assinatura_id : null;

            if ($object->tipo_template == TemplateEscritorio::WHATSAPP)
            {
                $object->template = $data->template_whatsapp;
            }

            TemplateRespostaService::validar($object);

            if ($gravado)
            {
                TemplateRespostaService::exigirLivreParaMudar($gravado, $object);
            }

            if ($object->tipo_template == TemplateEscritorio::EMAIL && empty($object->titulo))
            {
                throw new Exception("O assunto do e-mail é obrigatório.");
            }

            $object->store(); // save the object

            // e-mail (09/11/2026): assinatura, anexos e botoes; TCheckGroup todo desmarcado nao posta nada
            $extras = (array) $param;
            if (!empty($extras['anexos_manter_enviado']) && !isset($extras['anexos_manter']))
            {
                $extras['anexos_manter'] = [];
            }
            TemplateEmailService::salvarExtras($object, $extras);
            $data->assinatura_arquivo = '';
            $data->anexos_novos = [];

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            $actions = $this->fieldList_acoes->getPostData();
            if (count($actions) == 1 && empty($actions[0]->url))
            {
                TemplateAcao::where('template_escritorio_id', '=', $object->id)->delete();
            }
            else
            {
            $template_acao_template_escritorio_items = $this->storeItems('TemplateAcao', 'template_escritorio_id', $object, $this->fieldList_acoes, function($masterObject, $detailObject){ 

                //code here

            }, $this->criteria_fieldList_acoes); 
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            Navegacao::voltarParaLista('TemplateEscritorioHeaderList', __CLASS__);

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

                $object = new TemplateEscritorio($key); // instantiates the Active Record 

                if ($object->readonly == 'T')
                {
                    TCombo::disableField(self::$formName, 'tipo_template');
                }

                if ($object->tipo_template == 'EMAIL')
                {
                    TScript::create("$('[name=template_whatsapp]').closest('.tformrow').hide();");
                    TScript::create("$('[name=fieldList_acoes]').closest('.tformrow').hide().prev().hide();");
                }
                else
                {
                    $object->template_whatsapp = $object->template;
                    TScript::create("$('[name=template]').closest('.tformrow').hide();");
                    TScript::create("$('[name=fieldList_acoes]').closest('.tformrow').hide().prev().hide();");
                }

                $this->mostrarUsadoEm(TemplateRespostaService::usadoEm($object));
                $this->carregarExtrasDoEmail($object);

                                $object->criacao_user_name = $object->criacao_user->name;
                $object->modificacao_user_name = $object->modificacao_user->name;

                $this->fieldList_acoes_items = $this->loadItems('TemplateAcao', 'template_escritorio_id', $object, $this->fieldList_acoes, function($masterObject, $detailObject, $objectItems){ 

                    //code here

                }, $this->criteria_fieldList_acoes); 

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

        $this->fieldList_acoes->addHeader();
        $this->fieldList_acoes->addDetail($this->default_item_fieldList_acoes);

        $this->fieldList_acoes->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        TScript::create("$('[name=template_whatsapp]').closest('.tformrow').hide();");
        TScript::create("$('[name=fieldList_acoes]').closest('.tformrow').hide().prev().hide();");

    }

    public function onShow($param = null)
    {
        $this->fieldList_acoes->addHeader();
        $this->fieldList_acoes->addDetail($this->default_item_fieldList_acoes);

        $this->fieldList_acoes->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        TScript::create("$('[name=template_whatsapp]').closest('.tformrow').hide();");
        TScript::create("$('[name=fieldList_acoes]').closest('.tformrow').hide().prev().hide();");
    }

    /**
     * E-mail (09/11/2026): botoes do cliente (so Confirmacao), assinatura (padrao do escritorio, outra ou nenhuma), anexos e
     * a previa que se atualiza enquanto o usuario edita.
     */
    private function montarExtrasDoEmail(): void
    {
        // ---------- botoes do cliente (confirmacao por e-mail) ----------
        $tituloBotoes = new TElement('div');
        $tituloBotoes->id = 'botoes_titulo';
        $tituloBotoes->style = 'border-top:1px solid #ddd;padding-top:10px;';
        $tituloBotoes->add('<b style="font-size:15px">Botões do cliente</b><br>Aparecem onde estiver {$botoes} (os dois juntos) ou {$link_confirmacao} e {$link_cancelamento} (um por um) '
                         . 'e abrem as páginas de sempre para confirmar ou cancelar. O texto e a cor abaixo valem para os dois jeitos.');
        $this->form->addFields([$tituloBotoes])->layout = ['col-sm-12'];

        $confirmarTexto = new TEntry('botao_confirmar_texto');
        $confirmarCor = new TColor('botao_confirmar_cor');
        $cancelarTexto = new TEntry('botao_cancelar_texto');
        $cancelarCor = new TColor('botao_cancelar_cor');
        foreach ([$confirmarTexto, $confirmarCor, $cancelarTexto, $cancelarCor] as $campo) { $campo->setSize('100%'); }
        $confirmarTexto->setMaxLength(60);
        $cancelarTexto->setMaxLength(60);
        $confirmarTexto->setValue(EmailMontagemService::CONFIRMAR_TEXTO);
        $confirmarCor->setValue(EmailMontagemService::CONFIRMAR_COR);
        $cancelarTexto->setValue(EmailMontagemService::CANCELAR_TEXTO);
        $cancelarCor->setValue(EmailMontagemService::CANCELAR_COR);

        $row = $this->form->addFields([new TLabel('Texto do botão que confirma:', null, '14px', null, '100%'), $confirmarTexto], [new TLabel('Cor:', null, '14px', null, '100%'), $confirmarCor],
                                      [new TLabel('Texto do botão que cancela:', null, '14px', null, '100%'), $cancelarTexto], [new TLabel('Cor:', null, '14px', null, '100%'), $cancelarCor]);
        $row->layout = ['col-sm-4', 'col-sm-2', 'col-sm-4', 'col-sm-2'];

        // ---------- assinatura ----------
        $modo = new TRadioGroup('assinatura_modo');
        $modo->addItems([TemplateEscritorio::ASSINATURA_PADRAO => 'Padrão do escritório', TemplateEscritorio::ASSINATURA_OUTRA => 'Outra', TemplateEscritorio::ASSINATURA_NENHUMA => 'Sem assinatura']);
        $modo->setLayout('horizontal');
        $modo->setUseButton();
        $modo->setValue(TemplateEscritorio::ASSINATURA_PADRAO);

        $arquivo = new TFile('assinatura_arquivo');
        $arquivo->setAllowedExtensions(['png', 'jpg', 'jpeg', 'gif', 'html', 'htm', 'zip']);
        $arquivo->setSize('100%');
        $this->assinaturaAtual = new TElement('div');
        $this->assinaturaAtual->id = 'assinatura_atual';
        $this->assinaturaAtual->style = 'font-size:12px;color:#555;margin-top:4px;';

        $row = $this->form->addFields([new TLabel('Assinatura:', null, '14px', null, '100%'), $modo],
                                      [new TLabel('Arquivo da assinatura (PNG, JPG, GIF, HTML ou o ZIP da assinatura do Outlook):', null, '14px', null, '100%'), $arquivo, $this->assinaturaAtual]);
        // mesmas colunas dos anexos: o arquivo da assinatura fica alinhado com "Acrescentar anexos"
        $row->layout = ['col-sm-6', 'col-sm-6'];

        // ---------- anexos ----------
        $manter = new TCheckGroup('anexos_manter');
        $manter->setLayout('vertical');
        $enviado = new THidden('anexos_manter_enviado');
        $enviado->setValue('1');
        $novos = new TMultiFile('anexos_novos');
        $novos->setAllowedExtensions(EmailAnexoService::EXTENSOES);
        $novos->setSize('100%');

        $row = $this->form->addFields([new TLabel('Anexos do template (desmarque para tirar):', null, '14px', null, '100%'), $manter, $enviado],
                                      [new TLabel('Acrescentar anexos (PDF, Word, Excel, imagem, TXT ou ZIP; até 10 MB cada, 20 MB no total):', null, '14px', null, '100%'), $novos]);
        $row->layout = ['col-sm-6', 'col-sm-6'];

        // ---------- previa: abre e fecha (abrir atualiza); Atualizar so com ela aberta ----------
        $botoesPrevia = new TElement('div');
        $botoesPrevia->add('<button type="button" class="btn btn-sm btn-default" id="btn_previa_alternar" onclick="window.curciolPreviaAlternar(); return false;">'
                         . '<i class="fas fa-eye" style="color:#2196F3"></i> <span class="curciol-previa-rotulo">Mostrar prévia</span></button> '
                         . '<button type="button" class="btn btn-sm btn-default" id="btn_previa_atualizar" style="display:none" onclick="window.curciolPreviaTemplate(); return false;">'
                         . '<i class="fas fa-sync-alt" style="color:#2196F3"></i> Atualizar prévia</button>');

        $previa = new TElement('div');
        $previa->id = 'template_previa';
        $previa->style = 'display:none;margin-top:8px;background:#f4f4f4;border-radius:6px;padding:10px;';

        $this->form->addFields([new TLabel('Prévia do e-mail (como o cliente vê):', null, '14px', null, '100%'), $botoesPrevia, $previa])->layout = ['col-sm-12'];

        // sempre pelo form: os filtros da listagem, atras da cortina, tem os mesmos nomes de campo
        // "Botoes do cliente" so com {$botoes}, {$link_confirmacao} ou {$link_cancelamento} no texto (mesma regra do
        // EmailMontagemService::textoTemBotoes). Previa: abrir atualiza; aberta, acompanha o que e digitado
        $form = 'form[name=' . self::$formName . ']';
        $regexBotoes = '/\{\$(?:botoes|link_confirmacao|link_cancelamento)\}/';
        TScript::create("
            window.curciolTextoTemplate = function (f) {
                var t = f.find('textarea[name=template]');
                return t.next('.note-editor').length ? t.summernote('code') : (t.val() || '');
            };
            window.curciolTemBotoes = function (f) { return {$regexBotoes}.test(window.curciolTextoTemplate(f)); };
            window.curciolExtrasEmail = function () {
                var f = $('{$form}');
                var email = f.find('[name=tipo_template]').val() === '" . TemplateEscritorio::EMAIL . "';
                var confirmacao = email && f.find('[name=categoria]').val() === '" . TemplateEscritorio::CONFIRMACAO . "';
                f.find('#botoes_titulo, [name=botao_confirmar_texto]').closest('.tformrow').toggle(confirmacao && window.curciolTemBotoes(f));
                f.find('[name=assinatura_modo], [name^=anexos_novos], #template_previa').closest('.tformrow').toggle(email);
                var outra = f.find('[name=assinatura_modo]:checked').val() === '" . TemplateEscritorio::ASSINATURA_OUTRA . "';
                f.find('[name=assinatura_arquivo]').closest('div[class*=col-sm]').toggle(email && outra);
                f.find('.curciol-rotulo-titulo').text(email ? 'Assunto do e-mail:' : 'Título:');
            };
            window.curciolPreviaTemplate = function () {
                var f = $('{$form}');
                var t = f.find('textarea[name=template]');
                if (t.length && t.next('.note-editor').length) { t.val(t.summernote('code')); }
                if (f.find('[name=tipo_template]').val() !== '" . TemplateEscritorio::EMAIL . "') { f.find('#template_previa').empty(); return; }
                $.post('engine.php?class=TemplateEscritorioForm&method=onPrevia&static=1', f.serialize(), function (r) { f.find('#template_previa').html(r); });
            };
            window.curciolPreviaAlternar = function () {
                var f = $('{$form}');
                var p = f.find('#template_previa');
                var abrir = !p.is(':visible');
                p.toggle(abrir);
                f.find('#btn_previa_atualizar').toggle(abrir);
                f.find('#btn_previa_alternar .curciol-previa-rotulo').text(abrir ? 'Ocultar prévia' : 'Mostrar prévia');
                if (abrir) { window.curciolPreviaTemplate(); } else { p.empty(); }
            };
            window.curciolPreviaDepois = function () {
                clearTimeout(window.curciolPreviaTempo);
                if (!$('{$form} #template_previa').is(':visible')) { return; }
                window.curciolPreviaTempo = setTimeout(window.curciolPreviaTemplate, 800);
            };
            window.curciolTextoMudou = function () { window.curciolExtrasEmail(); window.curciolPreviaDepois(); };
            $(document).off('.curciolExtras')
                .on('change.curciolExtras', '{$form} [name=categoria], {$form} [name=tipo_template], {$form} [name=assinatura_modo]', window.curciolExtrasEmail)
                .on('change.curciolExtras input.curciolExtras', '{$form} input, {$form} select, {$form} textarea', window.curciolPreviaDepois)
                .on('summernote.change.curciolExtras summernote.init.curciolExtras', '{$form} textarea[name=template]', window.curciolTextoMudou);
            $(function () { window.curciolExtrasEmail(); });
        ");
    }

    /** onEdit: anexos do template (marcados) e a assinatura "Outra" atual. */
    private function carregarExtrasDoEmail(TemplateEscritorio $template): void
    {
        $itens = [];
        foreach (TemplateEmailService::anexosDoTemplate((int) $template->id) as $id)
        {
            $anexo = EmailAnexo::find($id);
            if ($anexo)
            {
                $itens[$id] = htmlspecialchars((string) $anexo->nome_original, ENT_QUOTES, 'UTF-8') . ' (' . EmailAnexoService::tamanhoLegivel((int) $anexo->tamanho) . ')';
            }
        }
        $this->form->getField('anexos_manter')->addItems($itens);
        $template->anexos_manter = array_keys($itens);

        if ($template->assinatura_modo === TemplateEscritorio::ASSINATURA_OUTRA && $template->assinatura_id && ($assinatura = EmailAssinatura::find((int) $template->assinatura_id)))
        {
            $this->assinaturaAtual->add('Atual: <b>' . htmlspecialchars((string) $assinatura->nome_original, ENT_QUOTES, 'UTF-8') . '</b>. Escolha outro arquivo só se quiser trocar.');
        }
    }

    /** Previa do e-mail com os dados do formulario (chamada pelo script da tela; devolve so o HTML). */
    public static function onPrevia($param = null)
    {
        try
        {
            TTransaction::open(self::$database);
            echo TemplateEmailService::previa((array) $param);
            TTransaction::close();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            echo '<div class="alert alert-danger" style="margin:0">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }

    /** Linhas dizendo de que confirmacao este template e a resposta (HTML ja escapado). */
    private function mostrarUsadoEm(array $usos): void
    {
        if ($usos)
        {
            $this->usadoEm->style = 'background:#eef5fb;border-left:3px solid #1a73e8;padding:8px 10px;font-size:13px;';
            $this->usadoEm->add(implode('<br>', $usos));
        }
    }

    /** Janela do "Criar novo template" de uma resposta: nao sai do formulario, o que ja foi digitado nele continua la. */
    public static function onNovaResposta($param)
    {
        $ramo = (string) ($param['ramo'] ?? '');

        if (!isset(TemplateRespostaService::RAMOS[$ramo]))
        {
            return;
        }

        $form = new BootstrapFormBuilder('form_template_resposta');
        $form->setFieldSizes('100%');

        $descricao = new TEntry('nova_descricao');
        $descricao->setSize('100%');
        $descricao->forceUpperCase();
        $texto = new TText('nova_texto');
        $texto->setSize('100%', 150);

        $variaveis = '';
        foreach (MensagemService::variaveis() as $variavel => $significado)
        {
            $variaveis .= "<a href=\"#\" class=\"curciol-variavel-resposta\" data-variavel=\"{$variavel}\" title=\"{$significado}\" style=\"margin-right:10px;white-space:nowrap;\">{$variavel}</a> ";
        }

        $form->addContent(['<div style="margin-bottom:8px">Categoria <b>' . TemplateEscritorio::CATEGORIAS[TemplateRespostaService::RAMOS[$ramo]['categoria']] . '</b>, tipo <b>WhatsApp</b>.</div>']);
        $row = $form->addFields([new TLabel('Descrição:', '#ff0000', '14px', null, '100%'), $descricao]);
        $row->layout = ['col-sm-12'];
        $row = $form->addFields([new TLabel('Mensagem:', '#ff0000', '14px', null, '100%'), $texto]);
        $row->layout = ['col-sm-12'];
        $form->addContent(['<div style="font-size:12px;margin-top:4px"><b>Variáveis (clique para inserir):</b><br>' . $variaveis . '</div>']);

        $btn = $form->addAction('Salvar', new TAction([__CLASS__, 'onSalvarNovaResposta'], ['ramo' => $ramo, 'static' => 1]), 'fas:save #ffffff');
        $btn->addStyleClass('btn-primary');

        $window = TWindow::create('Novo template de resposta', 0.6, null);
        $window->setProperty('class', 'window_modal');
        $window->add($form);
        $window->show();

        TScript::create("
            $(document).off('click.curciolVariavelResposta').on('click.curciolVariavelResposta', '.curciol-variavel-resposta', function (ev) {
                ev.preventDefault();
                var v = $(this).data('variavel');
                var t = $('[name=nova_texto]')[0];
                var a = t.selectionStart || 0, b = t.selectionEnd || 0;
                t.value = t.value.slice(0, a) + v + t.value.slice(b);
                t.focus();
                t.selectionStart = t.selectionEnd = a + v.length;
            });
        ");
    }

    /** Grava o template da janela e ja o deixa escolhido na resposta, no formulario que continua aberto. */
    public static function onSalvarNovaResposta($param)
    {
        try
        {
            $ramo = (string) ($param['ramo'] ?? '');

            if (!isset(TemplateRespostaService::RAMOS[$ramo]))
            {
                throw new Exception('Resposta inválida.');
            }

            $descricao = mb_strtoupper(trim((string) ($param['nova_descricao'] ?? '')));
            $texto = trim((string) ($param['nova_texto'] ?? ''));

            if ($descricao === '')
            {
                throw new Exception('Informe a descrição.');
            }
            if ($texto === '')
            {
                throw new Exception('Escreva a mensagem.');
            }

            TTransaction::open(self::$database);

            $template = new TemplateEscritorio();
            $template->escritorio_id = Escritorio::findByUnitId(TSession::getValue('userunitid'))->id;
            $template->chave = 'TEMPLATE_' . strtoupper(uniqid());
            $template->categoria = TemplateRespostaService::RAMOS[$ramo]['categoria'];
            $template->tipo_template = TemplateEscritorio::WHATSAPP;
            $template->descricao = $descricao;
            $template->titulo = $descricao;
            $template->template = $texto;
            $template->habilitado = 'T';
            $template->store();

            $itens = TemplateRespostaService::itensDoRamo($ramo);

            TTransaction::close();

            TWindow::closeWindow();

            $coluna = TemplateRespostaService::RAMOS[$ramo]['coluna'];
            TCombo::reload(self::$formName, $coluna, $itens, true, false);
            TForm::sendData(self::$formName, (object) [$coluna => $template->id], false, false);

            TToast::show('success', 'Template criado e escolhido para esta resposta.', 'topRight', 'far:check-circle');
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    } 

    public static function getFormName()
    {
        return self::$formName;
    }

}

