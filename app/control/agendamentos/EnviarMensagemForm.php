<?php
/**
 * Envio de WhatsApp (APChat) e e-mail do agendamento (03/11/2026).
 *
 * Com "chave" (botoes de confirmacao e lembrete): escolhe entre os templates daquela chave e mostra a mensagem pronta,
 * com as variaveis trocadas, sem edicao. Sem chave (envio manual): texto escrito na hora, com a lista de variaveis
 * para clicar; as variaveis sao trocadas no envio.
 *
 * Fora da producao o "Enviar para" e obrigatorio e nao aceita numero/e-mail do cliente (MensagemService).
 */
class EnviarMensagemForm extends TPage
{
    protected $form;
    private static $database = 'escritorio';
    private static $formName = 'form_EnviarMensagemForm';

    private $preview;

    public function __construct($param = null)
    {
        parent::__construct();

        if (!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $tipo = ($param['tipo'] ?? '') === TemplateEscritorio::EMAIL ? TemplateEscritorio::EMAIL : TemplateEscritorio::WHATSAPP;
        $chave = trim((string) ($param['chave'] ?? ''));
        $ehEmail = $tipo === TemplateEscritorio::EMAIL;

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle(($ehEmail ? 'Enviar e-mail' : 'Enviar WhatsApp') . ($chave === '' ? ' manual' : ''));

        $hTipo = new THidden('tipo');
        $hChave = new THidden('chave');
        $hAgendamento = new THidden('agendamento_id');
        $hTipo->setValue($tipo);
        $hChave->setValue($chave);
        $hAgendamento->setValue($param['agendamento_id'] ?? null);

        $destino = new TEntry('destino');
        $destino->setSize('100%');
        $destino->placeholder = $ehEmail ? 'e-mail' : '(19) 99999-9999';

        $row = $this->form->addFields([new TLabel($ehEmail ? 'Enviar para (e-mail):' : 'Enviar para (WhatsApp):', '#ff0000', '14px', null, '100%'), $destino, $hTipo, $hChave, $hAgendamento]);
        $row->layout = ['col-sm-6'];

        if (!AmbienteService::producao())
        {
            $aviso = new TElement('div');
            $aviso->class = 'alert alert-warning';
            $aviso->style = 'margin:0;padding:8px 12px;';
            $aviso->add('<i class="fas fa-flask"></i> <b>Ambiente de teste:</b> digite ' . ($ehEmail ? 'o e-mail' : 'o número')
                . ' que vai receber a mensagem. ' . ($ehEmail ? 'O e-mail' : 'O número') . ' do cliente é bloqueado.');
            $this->form->addContent([$aviso]);
        }

        if ($chave !== '')
        {
            $template = new TCombo('template');
            $template->setSize('100%');
            $template->setDefaultOption(false);
            $template->setChangeAction(new TAction([$this, 'onChangeTemplate'], ['static' => 1]));

            $row = $this->form->addFields([new TLabel('Template:', null, '14px', null, '100%'), $template]);
            $row->layout = ['col-sm-6'];

            $this->preview = new TElement('div');
            $this->preview->id = 'mensagem_preview';
            $this->preview->style = 'border:1px solid #ddd;border-radius:6px;padding:12px;background:#f7f7f7;min-height:80px;'
                                  . ($ehEmail ? '' : 'white-space:pre-wrap;');

            $row = $this->form->addFields([new TLabel('Mensagem que será enviada:', null, '14px', null, '100%'), $this->preview]);
            $row->layout = ['col-sm-12'];
        }
        else
        {
            if ($ehEmail)
            {
                $titulo = new TEntry('titulo');
                $titulo->setSize('100%');
                $row = $this->form->addFields([new TLabel('Título:', '#ff0000', '14px', null, '100%'), $titulo]);
                $row->layout = ['col-sm-12'];

                $texto = new THtmlEditor('content_email');
                $texto->setSize('100%', 220);
            }
            else
            {
                $texto = new TText('content_whatsapp');
                $texto->setSize('100%', 160);
            }

            $row = $this->form->addFields([new TLabel('Mensagem:', '#ff0000', '14px', null, '100%'), $texto]);
            $row->layout = ['col-sm-12'];

            $variaveis = new BContainer('bcontainer_variaveis');
            $variaveis->setTitle('Variáveis (clique para inserir na mensagem)', '#333', '13px', '', '#fff');
            $variaveis->setBorderColor('#c0c0c0');

            $colunas = [[], [], []];
            $i = 0;
            foreach (MensagemService::variaveis() as $variavel => $descricao)
            {
                $colunas[$i++ % 3][] = new TLabel("<a href=\"#\" class=\"curciol-variavel\" data-variavel=\"{$variavel}\"><b>{$variavel}</b></a> {$descricao}", null, '12px', null, '100%');
            }
            $row = $variaveis->addFields($colunas[0], $colunas[1], $colunas[2]);
            $row->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

            $this->form->addFields([$variaveis])->layout = ['col-sm-12'];

            // insere no ponto do cursor (texto do WhatsApp ou editor do e-mail)
            TScript::create("
                var \$e = $('[name=content_email]');
                if (\$e.length) { \$e.on('summernote.blur', function () { \$e.summernote('editor.saveRange'); }); }
                $(document).off('click.curciolVariavel').on('click.curciolVariavel', '.curciol-variavel', function (ev) {
                    ev.preventDefault();
                    var v = $(this).data('variavel');
                    var t = $('[name=content_whatsapp]')[0];
                    if (t) {
                        var a = t.selectionStart || 0, b = t.selectionEnd || 0;
                        t.value = t.value.slice(0, a) + v + t.value.slice(b);
                        t.focus(); t.selectionStart = t.selectionEnd = a + v.length;
                        return;
                    }
                    var e = $('[name=content_email]');
                    if (e.length) { e.summernote('editor.restoreRange'); e.summernote('editor.focus'); e.summernote('editor.insertText', v); }
                });
            ");
        }

        $btn = $this->form->addAction('Enviar', new TAction([$this, 'onSend'], ['static' => 1]), 'fas:paper-plane #ffffff');
        $btn->addStyleClass('btn-primary');

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

    public function onShow($param = null)
    {
        try
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find((int) ($param['agendamento_id'] ?? 0));

            if (!$agendamento)
            {
                throw new Exception('Agendamento não encontrado.');
            }

            $ehEmail = ($param['tipo'] ?? '') === TemplateEscritorio::EMAIL;
            $data = new stdClass;

            // producao: ja vem o do cliente (pode trocar); teste: vazio, o usuario digita
            if (AmbienteService::producao())
            {
                $data->destino = $ehEmail ? (string) $agendamento->cliente->email
                                          : PessoaContatoService::formatarTelefone($agendamento->cliente->telefone);
            }

            $chave = trim((string) ($param['chave'] ?? ''));

            if ($chave !== '')
            {
                $templates = MensagemService::templatesDaChave($chave);

                if (!$templates)
                {
                    throw new Exception('Nenhum template habilitado para esta mensagem. Veja Configurações › Template.');
                }

                $this->form->getField('template')->addItems($templates);
                $data->template = array_key_first($templates);

                $this->preview->add(self::previewHtml($agendamento, TemplateEscritorio::find($data->template)));
            }

            $this->form->setData($data);

            TTransaction::close();
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onChangeTemplate($param = null)
    {
        try
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find((int) ($param['agendamento_id'] ?? 0));
            $template = self::templateDoEnvio($param);
            $html = $agendamento && $template ? self::previewHtml($agendamento, $template) : '';

            TTransaction::close();

            TScript::create("$('#mensagem_preview').html(" . json_encode($html) . ");");
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onSend($param = null)
    {
        try
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find((int) ($param['agendamento_id'] ?? 0));

            if (!$agendamento)
            {
                throw new Exception('Agendamento é obrigatório');
            }

            $ehEmail = ($param['tipo'] ?? '') === TemplateEscritorio::EMAIL;
            $templateId = null;

            if (trim((string) ($param['chave'] ?? '')) !== '')
            {
                $template = self::templateDoEnvio($param);

                if (!$template)
                {
                    throw new Exception('Escolha o template.');
                }

                $partes = MensagemService::textoDoTemplate($template);
                $titulo = $partes['titulo'];
                $texto = $partes['texto'];
                $templateId = $template->id;
            }
            else
            {
                $titulo = $ehEmail ? (string) ($param['titulo'] ?? '') : null;
                $texto = (string) ($ehEmail ? ($param['content_email'] ?? '') : ($param['content_whatsapp'] ?? ''));
            }

            $mensagem = $ehEmail
                ? MensagemService::enviarEmail($agendamento, $titulo, $texto, $templateId, $param['destino'] ?? '')
                : MensagemService::enviarWhatsapp($agendamento, $titulo, $texto, $templateId, $param['destino'] ?? '');

            TTransaction::close();

            $para = $ehEmail ? $mensagem->destino : PessoaContatoService::formatarTelefone($mensagem->destino);

            TScript::create("Template.closeRightPanel();");
            TToast::show('success', "Mensagem enviada para {$para}", 'topRight', 'fas:check');
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /** Template escolhido, conferido contra a chave do botao (so os habilitados do escritorio). */
    private static function templateDoEnvio(array $param): ?TemplateEscritorio
    {
        $id = (int) ($param['template'] ?? $param['key'] ?? 0);
        $permitidos = MensagemService::templatesDaChave((string) ($param['chave'] ?? ''));

        return isset($permitidos[$id]) ? TemplateEscritorio::find($id) : null;
    }

    /** A mensagem como sai: variaveis trocadas; WhatsApp em texto, e-mail com o assunto em cima. */
    private static function previewHtml(Agendamento $agendamento, TemplateEscritorio $template): string
    {
        $partes = MensagemService::textoDoTemplate($template);
        $texto = TemplateEscritorio::replace($agendamento, $partes['texto']);

        if ($template->tipo_template == TemplateEscritorio::EMAIL)
        {
            $titulo = htmlspecialchars(TemplateEscritorio::replace($agendamento, $partes['titulo']));
            return "<div style=\"margin-bottom:8px\"><b>Assunto:</b> {$titulo}</div><hr style=\"margin:8px 0\">{$texto}";
        }

        return htmlspecialchars(trim($texto));
    }

    public static function getFormName()
    {
        return self::$formName;
    }
}
