<?php
/**
 * Envio de WhatsApp (APChat) e e-mail do agendamento (botoes da tela do agendamento; 04/11/2026).
 *
 * Em cima, o contato do cliente que recebe: "Nome | Telefone | Descricao", o principal primeiro e com o selo verde.
 * Abaixo, o template e a opcao "Manual": sem ela, a mensagem pronta com as variaveis trocadas, sem edicao; com ela, o
 * texto do template fica editavel (ou tudo do zero, com "Nenhum"), com a lista de variaveis para clicar e o botao
 * Visualizar, que mostra como a mensagem sai para este agendamento.
 *
 * Fora da producao o contato so aparece: a mensagem vai SO para o numero/e-mail digitado (obrigatorio e nunca do cliente;
 * MensagemService).
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
        $ehEmail = $tipo === TemplateEscritorio::EMAIL;
        $producao = AmbienteService::producao();

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle($ehEmail ? 'Enviar e-mail' : 'Enviar WhatsApp');

        $hTipo = new THidden('tipo');
        $hAgendamento = new THidden('agendamento_id');
        $hTipo->setValue($tipo);
        $hAgendamento->setValue($param['agendamento_id'] ?? null);

        // ---------- quem recebe ----------
        $contato = new TCombo('contato');
        $contato->setSize('100%');
        $contato->setDefaultOption(false);

        $rotulo = $producao ? 'Enviar para (contato do cliente):' : 'Contato do cliente (em teste, não recebe a mensagem):';
        $row = $this->form->addFields([new TLabel($rotulo, $producao ? '#ff0000' : null, '14px', null, '100%'), $contato, $hTipo, $hAgendamento]);
        $row->layout = ['col-sm-12'];

        if (!$producao)
        {
            $destino = new TEntry('destino');
            $destino->setSize('100%');
            $destino->placeholder = $ehEmail ? 'e-mail' : '(19) 99999-9999';

            $aviso = new TElement('div');
            $aviso->class = 'alert alert-warning';
            $aviso->style = 'margin:0;padding:8px 12px;';
            $aviso->add('<i class="fas fa-flask"></i> <b>Ambiente de teste:</b> a mensagem vai só para ' . ($ehEmail ? 'o e-mail digitado' : 'o número digitado')
                . '. ' . ($ehEmail ? 'O e-mail' : 'O número') . ' do cliente é bloqueado.');

            $row = $this->form->addFields([new TLabel($ehEmail ? 'Enviar para (e-mail de teste):' : 'Enviar para (número de teste):', '#ff0000', '14px', null, '100%'), $destino], [$aviso]);
            $row->layout = ['col-sm-4', 'col-sm-8'];
        }

        // ---------- template e manual ----------
        $template = new TCombo('template');
        $template->setSize('100%');
        $template->setDefaultOption('Nenhum (escrever do zero)');
        $template->setChangeAction(new TAction([$this, 'onChangeTemplate'], ['static' => 1]));

        $manual = new TCheckButton('manual');
        $manual->setIndexValue('S');
        $manual->setUseSwitch(true, 'green');

        $row = $this->form->addFields([new TLabel('Template:', null, '14px', null, '100%'), $template], [new TLabel('Manual:', null, '14px', null, '100%'), $manual]);
        $row->layout = ['col-sm-10', 'col-sm-2'];

        // ---------- mensagem pronta (sem Manual) ----------
        $this->preview = new TElement('div');
        $this->preview->id = 'mensagem_preview';
        $this->preview->style = 'border:1px solid #ddd;border-radius:6px;padding:12px;background:#f7f7f7;min-height:80px;'
                              . ($ehEmail ? '' : 'white-space:pre-wrap;');

        $row = $this->form->addFields([new TLabel('Mensagem que será enviada:', null, '14px', null, '100%'), $this->preview]);
        $row->layout = ['col-sm-12'];

        // ---------- texto editavel (com Manual) ----------
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

        $btnVisualizar = new TButton('btn_visualizar');
        $btnVisualizar->setAction(new TAction([$this, 'onVisualizar'], ['static' => 1]), 'Visualizar');
        $btnVisualizar->setImage('fas:eye #2196F3');
        $btnVisualizar->addStyleClass('btn-default');

        $visualizacao = new TElement('div');
        $visualizacao->id = 'mensagem_visualizacao';
        $visualizacao->style = 'margin-top:8px;' . ($ehEmail ? '' : 'white-space:pre-wrap;');

        $row = $this->form->addFields([$btnVisualizar, $visualizacao]);
        $row->layout = ['col-sm-12'];

        $colunas = [[], [], []];
        $i = 0;
        foreach (MensagemService::variaveis() as $variavel => $descricao)
        {
            $colunas[$i++ % 3][] = new TLabel("<a href=\"#\" class=\"curciol-variavel\" data-variavel=\"{$variavel}\"><b>{$variavel}</b></a> {$descricao}", null, '12px', null, '100%');
        }
        $rowVar = $variaveis->addFields($colunas[0], $colunas[1], $colunas[2]);
        $rowVar->layout = ['col-sm-4', 'col-sm-4', 'col-sm-4'];

        $caixaVariaveis = new TElement('div');
        $caixaVariaveis->id = 'caixa_variaveis';
        $caixaVariaveis->add($variaveis);

        $row = $this->form->addFields([$caixaVariaveis]);
        $row->layout = ['col-sm-12'];

        // Manual mostra o texto editavel no lugar da mensagem pronta; variavel entra no ponto do cursor; contato principal
        // com o quadradinho verde. Sem comentario dentro do script: o TScript junta tudo em uma linha
        TScript::create("
            window.curciolManual = function () {
                var m = $('[name=manual]').is(':checked');
                $('#mensagem_preview').closest('.tformrow').toggle(!m);
                $('[name=content_whatsapp], [name=content_email], [name=titulo], [name=btn_visualizar]').closest('.tformrow').toggle(m);
                $('#caixa_variaveis').toggle(m);
            };
            $(document).off('change.curciolManual').on('change.curciolManual', '[name=manual]', window.curciolManual);
            $(function () {
                window.curciolManual();
                if ($.fn.select2) {
                    var contato = function (o) {
                        if (!o.id) { return o.text; }
                        return $('<div>').text(o.text).html().replace('" . MensagemService::MARCA_PRINCIPAL . "',
                            '<span style=\"background:#4CAF50;color:#fff;border-radius:3px;padding:1px 6px;font-size:11px;margin-left:4px;\">principal</span>');
                    };
                    $('[name=contato]').select2({ width: '100%', templateResult: contato, templateSelection: contato, escapeMarkup: function (m) { return m; } });
                }
                var \$e = $('[name=content_email]');
                if (\$e.length) { \$e.on('summernote.blur', function () { \$e.summernote('editor.saveRange'); }); }
            });
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

            $tipo = ($param['tipo'] ?? '') === TemplateEscritorio::EMAIL ? TemplateEscritorio::EMAIL : TemplateEscritorio::WHATSAPP;
            $data = new stdClass;

            $contatos = MensagemService::contatosParaEnvio($agendamento, $tipo);
            $this->form->getField('contato')->addItems(array_column($contatos, 'rotulo', 'chave'));
            $data->contato = $contatos ? $contatos[0]['chave'] : null;

            $templates = MensagemService::templatesDoEnvio($tipo);
            $this->form->getField('template')->addItems($templates);
            $padrao = MensagemService::templatePadrao($agendamento, $tipo, $templates);

            if ($padrao)
            {
                $data->template = $padrao->id;
                $partes = MensagemService::textoDoTemplate($padrao);
                $data->titulo = $partes['titulo'];
                $data->content_whatsapp = $partes['texto'];
                $data->content_email = $partes['texto'];
                $this->preview->add(self::previewHtml($agendamento, $padrao));
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

    /** Troca a mensagem pronta e o texto editavel; "Nenhum" limpa e marca Manual. */
    public static function onChangeTemplate($param = null)
    {
        try
        {
            TTransaction::open(self::$database);

            $agendamento = Agendamento::find((int) ($param['agendamento_id'] ?? 0));
            $template = self::templateDoEnvio($param);
            $html = $agendamento && $template ? self::previewHtml($agendamento, $template) : '';
            $partes = $template ? MensagemService::textoDoTemplate($template) : ['titulo' => '', 'texto' => ''];

            TTransaction::close();

            $texto = json_encode($partes['texto']);
            TScript::create("
                $('#mensagem_preview').html(" . json_encode($html) . ");
                $('#mensagem_visualizacao').empty();
                $('[name=titulo]').val(" . json_encode($partes['titulo']) . ");
                $('[name=content_whatsapp]').val({$texto});
                if ($('[name=content_email]').length) { $('[name=content_email]').summernote('code', {$texto}); }
                " . ($template ? '' : "$('[name=manual]').prop('checked', true); window.curciolManual();"));
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /** Visualizar (Manual): a mensagem como sai para este agendamento, com as variaveis trocadas. */
    public static function onVisualizar($param = null)
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
            $texto = (string) ($ehEmail ? ($param['content_email'] ?? '') : ($param['content_whatsapp'] ?? ''));
            $html = self::mensagemHtml($agendamento, $ehEmail, (string) ($param['titulo'] ?? ''), $texto);

            TTransaction::close();

            TScript::create("$('#mensagem_visualizacao').html(" . json_encode('<div style="border:1px solid #ddd;border-radius:6px;padding:12px;background:#f7f7f7;">' . $html . '</div>') . ");");
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

            $tipo = ($param['tipo'] ?? '') === TemplateEscritorio::EMAIL ? TemplateEscritorio::EMAIL : TemplateEscritorio::WHATSAPP;
            $ehEmail = $tipo === TemplateEscritorio::EMAIL;
            $template = self::templateDoEnvio($param);
            $manual = !empty($param['manual']);

            if ($manual)
            {
                $titulo = $ehEmail ? (string) ($param['titulo'] ?? '') : ($template ? (string) $template->titulo : null);
                $texto = (string) ($ehEmail ? ($param['content_email'] ?? '') : ($param['content_whatsapp'] ?? ''));
            }
            elseif ($template)
            {
                $partes = MensagemService::textoDoTemplate($template);
                $titulo = $partes['titulo'];
                $texto = $partes['texto'];
            }
            else
            {
                throw new Exception('Escolha o template ou marque Manual.');
            }

            // fora da producao, so o digitado; na producao, o contato escolhido
            $destino = AmbienteService::producao()
                ? MensagemService::destinoDoContato($agendamento, $tipo, (string) ($param['contato'] ?? ''))
                : (string) ($param['destino'] ?? '');

            $templateId = MensagemService::templateRegistrado($template, $manual, $texto);

            $mensagem = $ehEmail
                ? MensagemService::enviarEmail($agendamento, $titulo, $texto, $templateId, $destino)
                : MensagemService::enviarWhatsapp($agendamento, $titulo, $texto, $templateId, $destino);

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

    /** Template escolhido, so entre os que podem ser enviados neste canal (null = nenhum). */
    private static function templateDoEnvio(array $param): ?TemplateEscritorio
    {
        $id = (int) ($param['template'] ?? $param['key'] ?? 0);
        $tipo = ($param['tipo'] ?? '') === TemplateEscritorio::EMAIL ? TemplateEscritorio::EMAIL : TemplateEscritorio::WHATSAPP;
        $permitidos = MensagemService::templatesDoEnvio($tipo);

        return isset($permitidos[$id]) ? TemplateEscritorio::find($id) : null;
    }

    /** A mensagem como sai: variaveis trocadas; WhatsApp em texto, e-mail com o assunto em cima. */
    private static function previewHtml(Agendamento $agendamento, TemplateEscritorio $template): string
    {
        $partes = MensagemService::textoDoTemplate($template);

        return self::mensagemHtml($agendamento, $template->tipo_template == TemplateEscritorio::EMAIL, $partes['titulo'], $partes['texto']);
    }

    private static function mensagemHtml(Agendamento $agendamento, bool $ehEmail, string $titulo, string $texto): string
    {
        $texto = TemplateEscritorio::replace($agendamento, $texto);

        if ($ehEmail)
        {
            $titulo = htmlspecialchars(TemplateEscritorio::replace($agendamento, $titulo));
            return "<div style=\"margin-bottom:8px\"><b>Assunto:</b> {$titulo}</div><hr style=\"margin:8px 0\">{$texto}";
        }

        return htmlspecialchars(trim($texto));
    }

    public static function getFormName()
    {
        return self::$formName;
    }
}
