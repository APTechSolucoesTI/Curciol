<?php

class EnviarMensagemForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = '';
    private static $activeRecord = '';
    private static $primaryKey = '';
    private static $formName = 'form_EnviarMensagemForm';

    use BuilderMasterDetailFieldListTrait;

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
        $this->form->setFormTitle("Enviar mensagem");

        $criteria_template = new TCriteria();

        if(!empty($param['tipo']))
        {
            TSession::setValue(__CLASS__.'load_filter_tipo_template', $param['tipo']);
        }
        $filterVar = TSession::getValue(__CLASS__.'load_filter_tipo_template');
        $criteria_template->add(new TFilter('tipo_template', '=', $filterVar)); 
        $filterVar = TSession::getValue("userunitid");
        $criteria_template->add(new TFilter('escritorio_id', 'in', "(SELECT id FROM escritorio WHERE system_unit_id = '{$filterVar}')")); 

        $template = new TDBCombo('template', 'escritorio', 'TemplateEscritorio', 'id', '{descricao}','descricao asc' , $criteria_template );
        $tipo = new THidden('tipo');
        $agendamento_id = new THidden('agendamento_id');
        $titulo = new TEntry('titulo');
        $content_whatsapp = new TText('content_whatsapp');
        $content_email = new THtmlEditor('content_email');
        $mensagem_acao_mensagem_id = new THidden('mensagem_acao_mensagem_id[]');
        $mensagem_acao_mensagem___row__id = new THidden('mensagem_acao_mensagem___row__id[]');
        $mensagem_acao_mensagem___row__data = new THidden('mensagem_acao_mensagem___row__data[]');
        $mensagem_acao_mensagem_url = new TEntry('mensagem_acao_mensagem_url[]');
        $mensagem_acao_mensagem_label = new TEntry('mensagem_acao_mensagem_label[]');
        $this->fieldList_acoes = new TFieldList();

        $this->fieldList_acoes->addField(null, $mensagem_acao_mensagem_id, []);
        $this->fieldList_acoes->addField(null, $mensagem_acao_mensagem___row__id, ['uniqid' => true]);
        $this->fieldList_acoes->addField(null, $mensagem_acao_mensagem___row__data, []);
        $this->fieldList_acoes->addField(new TLabel("Url", null, '14px', null), $mensagem_acao_mensagem_url, ['width' => '50%']);
        $this->fieldList_acoes->addField(new TLabel("Label", null, '14px', null), $mensagem_acao_mensagem_label, ['width' => '50%']);

        $this->fieldList_acoes->width = '100%';
        $this->fieldList_acoes->setFieldPrefix('mensagem_acao_mensagem');
        $this->fieldList_acoes->name = 'fieldList_acoes';

        $this->criteria_fieldList_acoes = new TCriteria();
        $this->default_item_fieldList_acoes = new stdClass();

        $this->form->addField($mensagem_acao_mensagem_id);
        $this->form->addField($mensagem_acao_mensagem___row__id);
        $this->form->addField($mensagem_acao_mensagem___row__data);
        $this->form->addField($mensagem_acao_mensagem_url);
        $this->form->addField($mensagem_acao_mensagem_label);

        $this->fieldList_acoes->setRemoveAction(null, 'fas:times #dd5a43', "Excluír");

        $template->setChangeAction(new TAction([$this,'onChangeTemplate']));

        $template->enableSearch();
        $titulo->forceUpperCase();
        $tipo->setValue($param['tipo']??null);
        $agendamento_id->setValue($param['agendamento_id']??null);

        $tipo->setSize(200);
        $titulo->setSize('100%');
        $template->setSize('100%');
        $agendamento_id->setSize(200);
        $content_email->setSize('100%', 180);
        $content_whatsapp->setSize('100%', 100);
        $mensagem_acao_mensagem_url->setSize('100%');
        $mensagem_acao_mensagem_label->setSize('100%');


        $row1 = $this->form->addFields([new TLabel("Template:", null, '14px', null, '100%'),$template,$tipo,$agendamento_id]);
        $row1->layout = [' col-sm-12'];

        $bcontainer_62e141a0065a9 = new BContainer('bcontainer_62e141a0065a9');
        $this->bcontainer_62e141a0065a9 = $bcontainer_62e141a0065a9;

        $bcontainer_62e141a0065a9->setTitle("Variáveis", '#333', '13px', '', '#fff');
        $bcontainer_62e141a0065a9->setBorderColor('#c0c0c0');

        $row2 = $bcontainer_62e141a0065a9->addFields([new TLabel("<b>{\$id}</b> Código do agendamento", null, '12px', null, '100%'),new TLabel("<b>{\$endereco_escritorio}</b> Endereço do escritório", null, '12px', null, '100%'),new TLabel("<b>{\$escritorio}</b> Nome do escritório", null, '12px', null, '100%'),new TLabel("<b>{\$estado}</b> Estado atual do agendamento", null, '12px', null, '100%')],[new TLabel("<b>{\$profissional}</b> Nome do profissional", null, '12px', null, '100%'),new TLabel("<b>{\$data_inicial}</b> Data do agendamento", null, '12px', null, '100%'),new TLabel("<b>{\$cliente}</b> Nome do cliente", null, '12px', null, '100%'),new TLabel("<b>{\$profissional_foto}</b> Foto do profissional", null, '12px', null, '100%')],[new TLabel("<b>{\$link_detalhe}</b> Link de detalhe do agendamento", null, '12px', null, '100%'),new TLabel("<b>{\$link_cancelamento}</b> Link para cancelar agendamento", null, '12px', null, '100%'),new TLabel("<b>{\$link_confirmacao}</b> Link para confirmar agendamento", null, '12px', null, '100%'),new TLabel("<b>{\$link_atendimento_online}</b> Link do atendimento online", null, '12px', null)]);
        $row2->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row3 = $bcontainer_62e141a0065a9->addFields([new TLabel("<b>{\$telefone_escritorio}</b> Telefone do escritório", null, '12px', null, '100%'),new TLabel("<b>{\$email_escritorio}</b> E-mail do escritório", null, '12px', null, '100%')],[new TLabel("<b>{\$observacao}</b> Observação", null, '12px', null, '100%')],[]);
        $row3->layout = [' col-sm-4',' col-sm-4',' col-sm-4'];

        $row4 = $this->form->addFields([$bcontainer_62e141a0065a9]);
        $row4->layout = [' col-sm-12'];

        $row5 = $this->form->addFields([new TLabel("Título:", null, '14px', null, '100%'),$titulo]);
        $row5->layout = [' col-sm-12'];

        $row6 = $this->form->addFields([new TLabel("Template:", null, '14px', null),$content_whatsapp]);
        $row6->layout = [' col-sm-12'];

        $row7 = $this->form->addFields([new TLabel("Template:", null, '14px', null),$content_email]);
        $row7->layout = [' col-sm-12'];

        $row8 = $this->form->addFields([new TLabel("Ações", null, '14px', null)]);
        $row8->layout = [' col-sm-12'];

        $row9 = $this->form->addFields([$this->fieldList_acoes]);
        $row9->layout = [' col-sm-12'];

        // create the form actions
        $btn_onsend = $this->form->addAction("Enviar", new TAction([$this, 'onSend']), 'fas:paper-plane #ffffff');
        $this->btn_onsend = $btn_onsend;
        $btn_onsend->addStyleClass('btn-primary'); 

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');

        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=EnviarMensagemForm]');
        $style->width = '75% !important';   
        $style->show(true);

    }

    public static function onChangeTemplate($param = null) 
    {
        try 
        {
            TTransaction::open('escritorio');

            $data = new stdClass;

            if (! empty($param['key']))
            {
                $template = TemplateEscritorio::find($param['key']);

                $data->content_whatsapp = $template->template;
                $data->content_email = $template->template;
                $data->titulo = $template->titulo;

                $acoes = $template->getTemplateAcaos();

                $labels = [];
                $urls = [];
                if ($acoes)
                {
                    foreach ($acoes as $acao)
                    {
                        $labels[] = $acao->label;
                        $urls[] = $acao->url;
                    }
                }

                $data->mensagem_acao_mensagem_label = $labels;
                $data->mensagem_acao_mensagem_url = $urls;

                TFieldList::clear('fieldList_acoes');
                TFieldList::addRows('fieldList_acoes', count($labels) - 1);
            }
            else
            {
                $data->content_whatsapp = '';
                $data->content_email = '';
                $data->titulo = '';

                TFieldList::clear('fieldList_acoes');
            }

            TForm::sendData(self::$formName, $data, false, false, 1000);

            TTransaction::close();

        }
        catch (Exception $e) 
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());    
        }
    }

    public function onSend($param = null) 
    {
        try
        {
            TTransaction::open('escritorio');
            $data = $this->form->getData();

            $titulo = $data->titulo;
            $template_id = $data->template;
            $template = $data->tipo == TemplateEscritorio::EMAIL ? $data->content_email : $data->content_whatsapp;
            $list_acoes = $this->fieldList_acoes->getPostData();
            $acoes = [];

            if ($list_acoes)
            {
                foreach($list_acoes as $acao)
                {
                    if (empty($acao->url) || empty($acao->label))
                    {
                        continue;
                    }

                    $acoes[] = (object) ['label' => $acao->label, 'url' => $acao->url];
                }
            }

            if (empty($data->agendamento_id))
            {
                throw new Exception('Agendamento é obrigatório');
            }

            $agendamento = Agendamento::find($data->agendamento_id);

            if (! in_array($data->tipo, [TemplateEscritorio::EMAIL, TemplateEscritorio::WHATSAPP]))
            {
                throw new Exception('Tipo inválido');
            }

            if (empty($titulo) && $data->tipo == TemplateEscritorio::EMAIL)
            {
                throw new Exception("Título é obrigátorio para envio de e-mail");
            }

            if (empty($template))
            {
                throw new Exception("Template é obrigátorio");
            }

            if ($data->tipo == TemplateEscritorio::EMAIL)
            {
                MensagemService::enviarEmail($agendamento, $titulo, $template, $template_id);
            }
            else
            {
                MensagemService::enviarWhatsapp($agendamento, $titulo, $template, $acoes, $template_id);
            }

            TTransaction::close();

            TScript::create("Template.closeRightPanel();");
            TToast::show("success", 'Envio realizado com sucesso!', "topRight", "fas:check");
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public function onShow($param = null)
    {               
        $this->fieldList_acoes->addHeader();
        $this->fieldList_acoes->addDetail($this->default_item_fieldList_acoes);

        $this->fieldList_acoes->addCloneAction(null, 'fas:plus #69aa46', "Clonar");

        if (! empty($param['tipo']) && $param['tipo'] == TemplateEscritorio::EMAIL)
        {
            TScript::create("$('[name=content_whatsapp]').closest('.tformrow').hide();");
            TScript::create("$('[name=fieldList_acoes]').closest('.tformrow').hide().prev().hide();");
        }
        else
        {
            TScript::create("$('[name=content_email]').closest('.tformrow').hide();");
        }
    } 

}

