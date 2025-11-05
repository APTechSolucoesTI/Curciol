<?php

class TemplateEscritorioForm extends TPage
{
    protected BootstrapFormBuilder $form;
    private $formFields = [];
    private static $database = 'escritorio';
    private static $activeRecord = 'TemplateEscritorio';
    private static $primaryKey = 'id';
    private static $formName = 'form_TemplateClinicaForm';

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
        $chave = new TEntry('chave');
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

        $chave->addValidation("Chave", new TRequiredValidator()); 
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

        $chave->forceUpperCase();
        $titulo->forceUpperCase();
        $descricao->forceUpperCase();

        $data_criacao->setEditable(false);
        $data_modificacao->setEditable(false);
        $criacao_user_name->setEditable(false);
        $modificacao_user_name->setEditable(false);

        $id->setSize(200);
        $chave->setSize('100%');
        $titulo->setSize('100%');
        $descricao->setSize('100%');
        $escritorio_id->setSize(200);
        $habilitado->setSize('100%');
        $data_criacao->setSize('100%');
        $tipo_template->setSize('100%');
        $template->setSize('100%', 210);
        $data_modificacao->setSize('100%');
        $criacao_user_name->setSize('100%');
        $modificacao_user_name->setSize('100%');
        $template_whatsapp->setSize('100%', 210);
        $template_acao_template_escritorio_url->setSize('100%');
        $template_acao_template_escritorio_label->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Chave:", '#ff0000', '14px', null, '100%'),$escritorio_id,$id,$chave],[new TLabel("Descrição:", '#ff0000', '14px', null, '100%'),$descricao],[new TLabel("Tipo:", '#FF0000', '14px', null),$tipo_template]);
        $row1->layout = [' col-sm-3','col-sm-6',' col-sm-3'];

        $bcontainer_62e034c1eb8f6 = new BContainer('bcontainer_62e034c1eb8f6');
        $this->bcontainer_62e034c1eb8f6 = $bcontainer_62e034c1eb8f6;

        $bcontainer_62e034c1eb8f6->setTitle("Variávies", '#333', '13px', '', '#fff');
        $bcontainer_62e034c1eb8f6->setBorderColor('#c0c0c0');

        $row2 = $bcontainer_62e034c1eb8f6->addFields([new TLabel("<b>{\$id}</b> Código do agendamento", null, '14px', null, '100%'),new TLabel("<b>{\$endereco_escritorio}</b> Endereço do escritório", null, '14px', null, '100%'),new TLabel("<b>{\$escritorio}</b> Nome do escritório", null, '14px', null, '100%'),new TLabel("<b>{\$estado}</b> Estado atual do agendamento", null, '14px', null, '100%')],[new TLabel("<b>{\$profissional}</b> Nome do profissional", null, '14px', null, '100%'),new TLabel("<b>{\$data_inicial}</b> Data do agendamento", null, '14px', null, '100%'),new TLabel("<b>{\$cliente}</b> Nome do cliente", null, '14px', null, '100%'),new TLabel("<b>{\$profissional_foto}</b> Foto do profissional", null, '14px', null, '100%')],[new TLabel("<b>{\$link_detalhe}</b> Link de detalhe do agendamento ", null, '14px', null),new TLabel("<b>{\$link_cancelamento}</b> Link para cancelar agendamento ", null, '14px', null, '100%'),new TLabel("<b>{\$link_confirmacao}</b> Link para confirmar agendamento ", null, '14px', null, '100%'),new TLabel("<b>{\$link_atendimento_online}</b> Link do atendimento online", null, '14px', null, '100%')]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row3 = $bcontainer_62e034c1eb8f6->addFields([new TLabel("<b>{\$telefone_escritorio}</b> Telefone do escritório", null, '14px', null, '100%'),new TLabel("<b>{\$email_escritorio}</b> E-mail do escritório", null, '14px', null, '100%')],[new TLabel("<b>{\$observacao}</b> Observação", null, '14px', null, '100%')],[]);
        $row3->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row4 = $this->form->addFields([$bcontainer_62e034c1eb8f6]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addFields([new TLabel("Título:", null, '14px', null, '100%'),$titulo],[new TLabel("Habilitar:", '#ff0000', '14px', null, '100%'),$habilitado]);
        $row5->layout = ['col-sm-9','col-sm-3'];

        $row6 = $this->form->addFields([new TLabel("Template:", null, '14px', null, '100%'),$template]);
        $row6->layout = ['col-sm-12'];

        $row7 = $this->form->addFields([new TLabel("Template:", null, '14px', null, '100%'),$template_whatsapp]);
        $row7->layout = ['col-sm-12'];

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
                TScript::create("$('[name=template_whatsapp]').closest('.tformrow').show();");
                TScript::create("$('[name=fieldList_acoes]').closest('.tformrow').show().prev().show();");

                TScript::create("$('[name=template]').closest('.tformrow').hide();");
            }

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
            $object->escritorio_id = TSession::getValue('userunitid');

            if ($object->tipo_template == TemplateEscritorio::WHATSAPP)
            {
                $object->template = $data->template_whatsapp;
            }

            if ($object->tipo_template == TemplateEscritorio::EMAIL && empty($object->titulo))
            {
                throw new Exception("Título é obrigátorio para envio de e-mail");
            }

            $object->store(); // save the object 

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
            TApplication::loadPage('TemplateEscritorioHeaderList', 'onShow', $loadPageParam); 

                        TScript::create("Template.closeRightPanel();");
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
                    TEntry::disableField(self::$formName, 'descricao');
                    TEntry::disableField(self::$formName, 'chave');
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
                }

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

    public static function getFormName()
    {
        return self::$formName;
    }

}

