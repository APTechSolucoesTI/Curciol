<?php
/**
 * Envio de WhatsApp (APChat) e e-mail do agendamento (botoes da tela do agendamento; 04/11/2026).
 *
 * Em cima, o contato do cliente que recebe: "Nome | Telefone | Descricao", o principal primeiro e com o selo verde.
 * Abaixo, o template e a opcao "Manual": sem ela, a mensagem pronta com as variaveis trocadas, sem edicao; com ela, o
 * texto do template fica editavel, com a lista de variaveis para clicar e o botao Visualizar, que mostra como a mensagem
 * sai para este agendamento.
 *
 * Agendado: so templates de Confirmacao. Confirmado: so de Lembrete, na hora ou agendado (MensagemAgendadaService). Nos outros
 * estados nao envia (MensagemService::exigirEnvioDoAgendamento, tambem no servidor).
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
        $template->setDefaultOption(false);
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
            $row = $this->form->addFields([new TLabel('Assunto:', '#ff0000', '14px', null, '100%'), $titulo]);
            $row->layout = ['col-sm-12'];

            $texto = new THtmlEditor('content_email');
            $texto->setSize('100%', 220);
        }
        else
        {
            $texto = new TText('content_whatsapp');
            $texto->setSize('100%', 160);
        }
        // cresce com o texto e ja abre mostrando a mensagem inteira (curciol-autoaltura.js)
        $texto->setProperty('data-autoaltura', '1');

        $row = $this->form->addFields([new TLabel('Mensagem:', '#ff0000', '14px', null, '100%'), $texto]);
        $row->layout = ['col-sm-12'];

        $variaveis = new BContainer('bcontainer_variaveis');
        $variaveis->setTitle('Variáveis (clique para inserir na mensagem)', '#333', '13px', '', '#fff');
        $variaveis->setBorderColor('#c0c0c0');

        // Visualizar abre e fecha (abrir atualiza); Atualizar so com ela aberta
        $btnVisualizar = new TElement('div');
        $btnVisualizar->id = 'mensagem_visualizar_botoes';
        $btnVisualizar->add('<button type="button" class="btn btn-default" id="btn_visualizar" onclick="window.curciolVisualizarAlternar(); return false;">'
                          . '<i class="fas fa-eye" style="color:#2196F3"></i> <span class="curciol-visualizar-rotulo">Visualizar</span></button> '
                          . '<button type="button" class="btn btn-default" id="btn_visualizar_atualizar" style="display:none" onclick="window.curciolVisualizarAtualizar(); return false;">'
                          . '<i class="fas fa-sync-alt" style="color:#2196F3"></i> Atualizar</button>');

        $visualizacao = new TElement('div');
        $visualizacao->id = 'mensagem_visualizacao';
        $visualizacao->style = 'display:none;margin-top:8px;' . ($ehEmail ? '' : 'white-space:pre-wrap;');

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

        // e-mail com Manual (09/11/2026): assinatura e anexos so deste envio (o template nao muda)
        if ($ehEmail)
        {
            $assinaturaModo = new TCombo('assinatura_modo');
            $assinaturaModo->setSize('100%');
            $assinaturaModo->setDefaultOption(false);
            $assinaturaModo->addItems(EnvioEmailService::opcoesAssinatura(null));
            $assinaturaArquivo = new TFile('assinatura_arquivo');
            $assinaturaArquivo->setAllowedExtensions(['png', 'jpg', 'jpeg', 'gif', 'html', 'htm', 'zip']);
            $assinaturaArquivo->setSize('100%');

            $row = $this->form->addFields([new TLabel('Assinatura:', null, '14px', null, '100%'), $assinaturaModo],
                                          [new TLabel('Arquivo da assinatura (PNG, JPG, GIF, HTML ou o ZIP do Outlook):', null, '14px', null, '100%'), $assinaturaArquivo]);
            $row->layout = ['col-sm-5', 'col-sm-7'];

            $anexosTemplate = new TCheckGroup('anexos_template');
            $anexosTemplate->setLayout('vertical');
            $anexosEnviado = new THidden('anexos_template_enviado');
            $anexosEnviado->setValue('1');
            $anexosNovos = new TMultiFile('anexos_novos');
            $anexosNovos->setAllowedExtensions(EmailAnexoService::EXTENSOES);
            $anexosNovos->setSize('100%');

            $row = $this->form->addFields([new TLabel('Anexos do template (desmarque para tirar):', null, '14px', null, '100%'), $anexosTemplate, $anexosEnviado],
                                          [new TLabel('Acrescentar anexos (até 10 MB cada, 20 MB no total):', null, '14px', null, '100%'), $anexosNovos]);
            $row->layout = ['col-sm-5', 'col-sm-7'];
        }

        // ---------- lembrete: na hora ou agendado (so com o agendamento confirmado) ----------
        if (self::agendamentoConfirmado($param))
        {
            $agendar = new TCheckButton('agendar');
            $agendar->setIndexValue('S');
            $agendar->setUseSwitch(true, 'green');

            $enviarEm = new TDateTime('enviar_em');
            $enviarEm->setMask('dd/mm/yyyy hh:ii');
            $enviarEm->setSize(170);

            $row = $this->form->addFields([new TLabel('Agendar envio:', null, '14px', null, '100%'), $agendar]);
            $row->layout = ['col-sm-12'];

            $row = $this->form->addFields([new TLabel('Enviar em:', '#ff0000', '14px', null, '100%'), $enviarEm]);
            $row->layout = ['col-sm-12'];
        }

        // Manual mostra o texto editavel no lugar da mensagem pronta; variavel entra no ponto do cursor; contato principal
        // com o quadradinho verde. Sem comentario dentro do script: o TScript junta tudo em uma linha
        TScript::create("
            window.curciolManual = function () {
                var m = $('[name=manual]').is(':checked');
                $('#mensagem_preview').closest('.tformrow').toggle(!m);
                $('[name=content_whatsapp], [name=content_email], [name=titulo], #mensagem_visualizar_botoes').closest('.tformrow').toggle(m);
                $('#caixa_variaveis').toggle(m);
                if (m && window.curciolAutoAltura) { window.curciolAutoAltura(); }
                $('form[name=" . self::$formName . "] [name=assinatura_modo], form[name=" . self::$formName . "] [name=anexos_template_enviado]').closest('.tformrow').toggle(m);
                $('form[name=" . self::$formName . "] [name=assinatura_arquivo]').closest('div[class*=col-sm]').toggle(m && $('form[name=" . self::$formName . "] [name=assinatura_modo]').val() === '" . EnvioEmailService::MODO_ARQUIVO . "');
            };
            $(document).off('change.curciolAssinatura').on('change.curciolAssinatura', 'form[name=" . self::$formName . "] [name=assinatura_modo]', window.curciolManual);
            $(document).off('change.curciolManual').on('change.curciolManual', '[name=manual]', window.curciolManual);
            window.curciolAgendar = function () {
                var a = $('[name=agendar]').is(':checked');
                $('[name=enviar_em]').closest('.tformrow').toggle(a);
                $('[name=btn_enviar] span').contents().last().replaceWith(a ? ' Agendar' : ' Enviar');
            };
            $(document).off('change.curciolAgendar').on('change.curciolAgendar', '[name=agendar]', window.curciolAgendar);
            window.curciolVisualizarAtualizar = function () {
                var e = $('form[name=" . self::$formName . "] [name=content_email]');
                if (e.length && e.next('.note-editor').length) { e.val(e.summernote('code')); }
                __adianti_post_data('" . self::$formName . "', 'class=EnviarMensagemForm&method=onVisualizar&static=1');
            };
            window.curciolVisualizarAlternar = function () {
                var v = $('#mensagem_visualizacao');
                var abrir = !v.is(':visible');
                v.toggle(abrir);
                $('#btn_visualizar_atualizar').toggle(abrir);
                $('#btn_visualizar .curciol-visualizar-rotulo').text(abrir ? 'Fechar visualização' : 'Visualizar');
                if (abrir) { window.curciolVisualizarAtualizar(); } else { v.empty(); }
            };
            $(function () {
                window.curciolManual();
                window.curciolAgendar();
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
            if (MensagemService::categoriaDoEnvio($agendamento) === null)
            {
                throw new Exception(MensagemService::MSG_ESTADO_SEM_ENVIO);
            }

            $tipo = ($param['tipo'] ?? '') === TemplateEscritorio::EMAIL ? TemplateEscritorio::EMAIL : TemplateEscritorio::WHATSAPP;
            $data = new stdClass;

            $contatos = MensagemService::contatosParaEnvio($agendamento, $tipo);
            $this->form->getField('contato')->addItems(array_column($contatos, 'rotulo', 'chave'));
            $data->contato = $contatos ? $contatos[0]['chave'] : null;

            $templates = MensagemService::templatesDoAgendamento($agendamento, $tipo);
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

            if ($tipo === TemplateEscritorio::EMAIL)
            {
                $combo = $this->form->getField('assinatura_modo');
                $combo->clear();
                $combo->addItems(EnvioEmailService::opcoesAssinatura($padrao));
                $data->assinatura_modo = EnvioEmailService::modoInicial($padrao);
                $anexos = EnvioEmailService::anexosDoTemplate($padrao);
                $this->form->getField('anexos_template')->addItems($anexos);
                $data->anexos_template = array_keys($anexos);
            }

            // sugestao: um dia antes do agendamento, se ainda der tempo
            $umDiaAntes = strtotime($agendamento->dt_inicial . ' -1 day');
            $data->enviar_em = $umDiaAntes > time() ? date('d/m/Y H:i', $umDiaAntes) : null;

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
            $template = self::templateDoEnvio($param, $agendamento);
            $html = $agendamento && $template ? self::previewHtml($agendamento, $template) : '';
            $partes = $template ? MensagemService::textoDoTemplate($template) : ['titulo' => '', 'texto' => ''];

            // e-mail: assinatura e anexos do template escolhido
            if (($param['tipo'] ?? '') === TemplateEscritorio::EMAIL)
            {
                $anexos = EnvioEmailService::anexosDoTemplate($template);
                TCombo::reload(self::$formName, 'assinatura_modo', EnvioEmailService::opcoesAssinatura($template), false, false);
                TCheckGroup::reload(self::$formName, 'anexos_template', $anexos, ['layout' => 'vertical']);
                TScript::create("
                    $('form[name=" . self::$formName . "] [name=assinatura_modo]').val(" . json_encode(EnvioEmailService::modoInicial($template)) . ");
                    $('form[name=" . self::$formName . "] [name^=anexos_template]').prop('checked', true);
                    window.curciolManual();");
            }

            TTransaction::close();

            $texto = json_encode($partes['texto']);
            TScript::create("
                $('#mensagem_preview').html(" . json_encode($html) . ");
                $('[name=titulo]').val(" . json_encode($partes['titulo']) . ");
                $('[name=content_whatsapp]').val({$texto});
                if ($('[name=content_email]').length) { $('[name=content_email]').summernote('code', {$texto}); }
                if (window.curciolAutoAltura) { window.curciolAutoAltura(); }
                if ($('#mensagem_visualizacao').is(':visible')) { window.curciolVisualizarAtualizar(); }
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
            // e-mail (09/11/2026): o mesmo corpo do envio, com botoes, assinatura e anexos escolhidos
            $html = $ehEmail
                ? EnvioEmailService::previa($agendamento, self::templateDoEnvio($param, $agendamento), true, (string) ($param['titulo'] ?? ''), $texto, (array) $param)
                : self::mensagemHtml($agendamento, false, (string) ($param['titulo'] ?? ''), $texto);

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
            $template = self::templateDoEnvio($param, $agendamento);
            $manual = !empty($param['manual']);
            MensagemService::exigirEnvioDoAgendamento($agendamento, $template, $manual, (string) ($ehEmail ? ($param['content_email'] ?? '') : ($param['content_whatsapp'] ?? '')));

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

            // e-mail (09/11/2026): botoes do template no texto; assinatura e anexos do template ou os escolhidos no Manual
            $extras = ['assinatura_id' => null, 'anexo_ids' => []];
            if ($ehEmail)
            {
                $texto = EmailMontagemService::comBotoes($texto, $template);
                $extras = EnvioEmailService::escolha($agendamento, $template, $manual, (array) $param);
            }

            if (!empty($param['agendar']))
            {
                $lembrete = MensagemAgendadaService::agendar($agendamento, $tipo, $titulo, $texto, $templateId, $destino, (string) ($param['enviar_em'] ?? ''),
                                                             $extras['assinatura_id'], $extras['anexo_ids']);
                $paraDestino = $lembrete->destino;
                $aviso = 'Lembrete agendado para ' . date('d/m/Y H:i', strtotime($lembrete->enviar_em)) . ': %s';
            }
            else
            {
                $mensagem = $ehEmail
                    ? MensagemService::enviarEmail($agendamento, $titulo, $texto, $templateId, $destino, $extras['assinatura_id'], $extras['anexo_ids'])
                    : MensagemService::enviarWhatsapp($agendamento, $titulo, $texto, $templateId, $destino);
                $paraDestino = $mensagem->destino;
                $aviso = 'Mensagem enviada para %s';
                if ($template->categoria === TemplateEscritorio::LEMBRETE)
                {
                    MensagemAgendadaService::registrarEnviadoNaHora($agendamento, $mensagem);
                }
            }

            TTransaction::close();

            $para = $ehEmail ? $paraDestino : PessoaContatoService::formatarTelefone($paraDestino);
            $agendamentoId = (int) $agendamento->id;

            TScript::create("Template.closeRightPanel();
                if ($('#adianti_right_panel > [page_name=AgendamentoFormView]').length) {
                    Adianti.waitMessage = false;
                    __adianti_load_page('index.php?class=AgendamentoFormView&method=onShow&key={$agendamentoId}&register_state=false');
                }");
            TToast::show('success', sprintf($aviso, $para), 'topRight', 'fas:check');
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    /** Template escolhido, so entre os que a tela oferece neste canal (null = nenhum). */
    private static function templateDoEnvio(array $param, ?Agendamento $agendamento): ?TemplateEscritorio
    {
        $id = (int) ($param['template'] ?? $param['key'] ?? 0);
        $tipo = ($param['tipo'] ?? '') === TemplateEscritorio::EMAIL ? TemplateEscritorio::EMAIL : TemplateEscritorio::WHATSAPP;
        $permitidos = $agendamento ? MensagemService::templatesDoAgendamento($agendamento, $tipo) : MensagemService::templatesDoEnvio($tipo);

        return isset($permitidos[$id]) ? TemplateEscritorio::find($id) : null;
    }

    private static function agendamentoConfirmado($param): bool
    {
        if (empty($param['agendamento_id'])) { return false; }

        TTransaction::open(self::$database);
        $agendamento = Agendamento::find((int) $param['agendamento_id']);
        TTransaction::close();

        return $agendamento && (int) $agendamento->estado_agenda_id === (int) EstadoAgenda::CONFIRMADO;
    }

    /** A mensagem como sai: variaveis trocadas; WhatsApp em texto, e-mail com o assunto em cima. */
    private static function previewHtml(Agendamento $agendamento, TemplateEscritorio $template): string
    {
        $partes = MensagemService::textoDoTemplate($template);

        if ($template->tipo_template == TemplateEscritorio::EMAIL)
        {
            return EnvioEmailService::previa($agendamento, $template, false, $partes['titulo'], $partes['texto'], []);
        }

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
