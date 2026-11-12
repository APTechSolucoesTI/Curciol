<?php
/**
 * Conversa de e-mail do APMail no historico do cliente (12/11/2026), aberta pela aba Atendimentos (lista e linha do tempo).
 * Layout do APMail: cada e-mail com remetente, Para, Cc, data e corpo, do mais antigo para o mais recente (a lista comeca no topo).
 * So a conversa do proprio cliente (ApmailHistoricoService). Sem anexos: so o aviso para abrir no APMail.
 * Corpo: limpo no servidor (EmailHtmlLimpo) e isolado num iframe com sandbox (sem scripts) e CSP propria (sem nada de fora).
 */
class ApmailConversaView extends TPage
{
    private $form;

    public function __construct($param = null)
    {
        parent::__construct();
        if (!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }
        $this->form = new BootstrapFormBuilder('form_ApmailConversaView');
        $this->form->setFormTitle('Conversa de e-mail');

        parent::setTargetContainer('adianti_right_panel');
        $fechar = new TButton('closeCurtain');
        $fechar->class = 'btn btn-sm btn-default';
        $fechar->style = 'margin-right:10px;';
        $fechar->onClick = 'Template.closeRightPanel();';
        $fechar->setLabel('Fechar');
        $fechar->setImage('fas:times');
        $this->form->addHeaderWidget($fechar);

        parent::add($this->form);
        $style = new TStyle('right-panel > .container-part[page-name=ApmailConversaView]');
        $style->width = '70% !important';
        $style->show(true);
    }

    public function onShow($param = null)
    {
        try
        {
            $conversaId = (int) ($param['key'] ?? 0);
            $pessoaId = (int) ($param['pessoa_id'] ?? 0);
            TTransaction::open('escritorio');
            $d = ApmailHistoricoService::conversa($conversaId, $pessoaId);
            $urlApmail = ApmailApiService::url(ApmailApiService::escritorio());
            TTransaction::close();

            $c = $d['conversa'];
            $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
            $onclick = LinkApp::apmailMensagem((string) ($c['ultima_mensagem_apmail_id'] ?? ''), $urlApmail);
            if ($onclick !== '')
            {
                $b = new TElement('a');
                $b->class = 'btn btn-sm';
                $b->style = 'margin-right:6px;background:' . LogoSistemaService::COR_APMAIL . ';color:#fff;';
                $b->href = '#';
                $b->onclick = $onclick;
                $b->add('<i class="far fa-envelope"></i> Abrir no APMail');
                $this->form->addHeaderWidget($b);
            }

            $n = (int) $c['qtd_mensagens'];
            $this->form->addContent([ApchatConversaHtml::estilo() . self::estilo() . '<div class="apm-topo"><div class="apm-assunto">' . ($h($c['assunto']) ?: '(sem assunto)') . '</div>'
                . '<div class="apm-info">' . implode(' · ', array_filter([$h($c['caixa_nome']), $n . ($n === 1 ? ' e-mail' : ' e-mails')], 'strlen')) . '</div></div>']);
            $campo = function ($rotulo, $valor) { return [new TLabel($rotulo, null, '12px', 'B', '100%'), new TTextDisplay($valor, '', '12px', '')]; };
            $row = $this->form->addFields($campo('Início:', AtendimentoTimeLine::dataHora($c['inicio_em'])), $campo('Fim:', AtendimentoTimeLine::dataHora($c['fim_em'])),
                                          $campo('Fila:', $h($c['caixa_nome'])), $campo('Profissional:', $h(implode(', ', $d['profissionais']))));
            $row->layout = ['col-sm-2', 'col-sm-2', 'col-sm-3', 'col-sm-5'];

            $html = '<div class="apm-lista"><div class="apm-trilha">';
            foreach ($d['mensagens'] as $m)
            {
                $html .= self::email($m, (string) $c['assunto']);
            }
            $this->form->addContent([$html . '</div></div>' . ApchatConversaHtml::scriptAlturaTela('.apm-lista')]);
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            $this->form->addContent(['<div class="alert alert-danger">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>']);
        }
    }

    private static function email(array $m, string $assuntoConversa): string
    {
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $enviado = $m['direcao'] === 'outbound';
        $nome = trim((string) $m['de_nome']);
        $cab = '<div class="apm-de">' . ($h($nome) ?: $h($m['de_email'])) . ($nome !== '' && $m['de_email'] ? ' <span class="apm-end">&lt;' . $h($m['de_email']) . '&gt;</span>' : '') . '</div>';
        $para = self::enderecos($m['para']);
        $cc = self::enderecos($m['cc']);
        if ($para !== '') { $cab .= '<div class="apm-linha"><b>Para</b> ' . $para . '</div>'; }
        if ($cc !== '') { $cab .= '<div class="apm-linha"><b>Cc</b> ' . $cc . '</div>'; }
        if (trim((string) $m['assunto']) !== '' && trim((string) $m['assunto']) !== trim($assuntoConversa))
        {
            $cab .= '<div class="apm-linha"><b>Assunto</b> ' . $h($m['assunto']) . '</div>';
        }
        if ($enviado && trim((string) $m['enviado_por_nome']) !== '')
        {
            $cab .= '<div class="apm-enviado-por"><i class="fas fa-reply"></i> Enviado por ' . $h($m['enviado_por_nome']) . '</div>';
        }

        $corpoHtml = EmailHtmlLimpo::limpar((string) $m['corpo_html']);
        if (trim(strip_tags($corpoHtml, '<img>')) !== '')
        {
            $doc = '<!DOCTYPE html><html><head><meta charset="utf-8">'
                 . '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; img-src data:; style-src \'unsafe-inline\'">'
                 . '<base target="_blank"><style>body{font:13px/1.45 Arial,Helvetica,sans-serif;margin:0;color:#222;overflow-wrap:anywhere}'
                 . 'img{max-width:100%;height:auto}table{max-width:100%}.apm-img{color:#888;font-style:italic}'
                 . 'blockquote{border-left:3px solid #d6dbe1;margin:6px 0;padding-left:8px;color:#555}</style></head><body>' . $corpoHtml . '</body></html>';
            $corpo = '<iframe class="apm-quadro" sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" '
                   . 'onload="try{var d=this.contentWindow.document;this.style.height=\'10px\';this.style.height=(Math.max(d.body.scrollHeight,d.documentElement.scrollHeight)+4)+\'px\'}catch(e){}" srcdoc="' . $h($doc) . '"></iframe>';
        }
        else
        {
            $corpo = '<div class="apm-texto">' . nl2br($h(trim((string) $m['corpo_texto']))) . '</div>';
        }
        // 12/11/2026: anexo (arquivo de verdade), convite de agenda e imagem do corpo (assinatura/logo) separados; nada e exibido
        $anexos = (int) $m['qtd_anexos'];
        $imagens = (int) ($m['qtd_imagens_corpo'] ?? 0);
        $convite = in_array($m['tem_convite'] ?? false, [true, 't', 1, '1', 'true'], true);
        $aviso = ($anexos > 0 ? '<div class="apm-anexos"><i class="fas fa-paperclip"></i> ' . $anexos . ($anexos === 1 ? ' anexo' : ' anexos') . ': abra no APMail para ver.</div>' : '')
               . ($convite ? '<div class="apm-anexos apm-convite"><i class="far fa-calendar-alt"></i> Convite de agenda: abra no APMail para ver.</div>' : '')
               . ($imagens > 0 ? '<div class="apm-nota"><i class="far fa-image"></i> ' . ($imagens === 1 ? 'Imagem da assinatura ou do corpo do e-mail' : $imagens . ' imagens da assinatura ou do corpo do e-mail')
                                  . ' (não exibida' . ($imagens === 1 ? '' : 's') . ').</div>' : '');

        return '<div class="apm-email' . ($enviado ? ' apm-enviado' : '') . '"><div class="apm-cab">' . ApchatConversaHtml::avatar($nome !== '' ? $nome : (string) $m['de_email'])
             . '<div class="apm-cab-texto">' . $cab . '</div><div class="apm-data">' . AtendimentoTimeLine::dataHora($m['mensagem_em']) . '</div></div>'
             . '<div class="apm-corpo">' . $corpo . '</div>' . $aviso . '</div>';
    }

    /** "Nome <email>, email" a partir do JSON gravado pela sincronizacao. */
    private static function enderecos($json): string
    {
        $lista = is_array($json) ? $json : json_decode((string) $json, true);
        $r = [];
        foreach ((array) $lista as $p)
        {
            $e = htmlspecialchars((string) ($p['email'] ?? ''), ENT_QUOTES, 'UTF-8');
            $n = htmlspecialchars(trim((string) ($p['nome'] ?? '')), ENT_QUOTES, 'UTF-8');
            if ($e === '') { continue; }
            $r[] = $n !== '' ? $n . ' <span class="apm-end">&lt;' . $e . '&gt;</span>' : $e;
        }
        return implode(', ', $r);
    }

    private static function estilo(): string
    {
        return '<style>
.apm-topo{margin:2px 0 8px}
.apm-assunto{font-weight:600;font-size:16px;color:#222}
.apm-info{font-size:12px;color:#667}
.apm-lista{background:#f3f5f8;border-radius:6px;padding:12px;display:flex;flex-direction:column;overflow:auto;height:calc(100vh - 190px);min-height:220px}
.apm-trilha{display:flex;flex-direction:column;gap:10px}
.apm-email{background:#fff;border:1px solid #e1e5ea;border-radius:8px;overflow:hidden;flex:0 0 auto}
.apm-email.apm-enviado{border-left:4px solid ' . LogoSistemaService::COR_APMAIL . '}
.apm-cab{display:flex;gap:10px;align-items:flex-start;padding:10px 12px;border-bottom:1px solid #eef1f4;background:#fafbfc}
.apm-cab-texto{flex:1;min-width:0}
.apm-de{font-weight:600;font-size:13px;color:#222}
.apm-end{color:#667;font-weight:400;font-size:12px}
.apm-linha{font-size:12px;color:#444;overflow-wrap:anywhere}
.apm-linha b{color:#333;display:inline-block;min-width:52px}
.apm-enviado-por{font-size:11.5px;color:' . LogoSistemaService::COR_APMAIL . ';margin-top:2px}
.apm-data{margin-left:auto;color:#667;font-size:12px;white-space:nowrap}
.apm-corpo{padding:10px 12px}
.apm-quadro{width:100%;border:0;min-height:40px;display:block}
.apm-texto{font-size:13px;line-height:1.45;overflow-wrap:anywhere}
.apm-anexos{padding:6px 12px;font-size:12px;color:#5d4a00;background:#fffbea;border-top:1px solid #f0e6c0}
.apm-convite{color:#1b4a6e;background:#eef6fb;border-top-color:#d3e6f2}
.apm-nota{padding:4px 12px 6px;font-size:11.5px;color:#888}
</style>';
    }
}
