<?php
/**
 * Atendimento do APChat (ticket fechado) no historico do cliente (10/11/2026): informacoes do ticket no topo, botao
 * "Abrir no APChat" e a conversa so com as mensagens de texto. So mostra ticket do proprio cliente (ApchatHistoricoService).
 * 11/11/2026: visual do APChat (ApchatConversaHtml); Profissional = todos os atendentes do ticket.
 */
class ApchatTicketView extends TPage
{
    private $form;

    public function __construct($param = null)
    {
        parent::__construct();
        if (!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }
        $this->form = new BootstrapFormBuilder('form_ApchatTicketView');
        $this->form->setFormTitle('Atendimento pelo WhatsApp');

        parent::setTargetContainer('adianti_right_panel');
        $fechar = new TButton('closeCurtain');
        $fechar->class = 'btn btn-sm btn-default';
        $fechar->style = 'margin-right:10px;';
        $fechar->onClick = 'Template.closeRightPanel();';
        $fechar->setLabel('Fechar');
        $fechar->setImage('fas:times');
        $this->form->addHeaderWidget($fechar);

        parent::add($this->form);
        $style = new TStyle('right-panel > .container-part[page-name=ApchatTicketView]');
        $style->width = '70% !important';
        $style->show(true);
    }

    public function onShow($param = null)
    {
        try
        {
            $ticketId = (int) ($param['key'] ?? 0);
            $pessoaId = (int) ($param['pessoa_id'] ?? 0);
            TTransaction::open('escritorio');
            $t = ApchatHistoricoService::ticket($ticketId, $pessoaId);
            $mensagens = ApchatHistoricoService::conversa($ticketId, $pessoaId);
            $url = ApchatApiService::urlTicket($ticketId);
            TTransaction::close();

            $this->form->setFormTitle('Atendimento pelo APChat');
            $this->form->addHeaderWidget(self::botaoAbrir($ticketId, $url));

            // 11/11/2026: no padrao do APChat - contato, "#ticket · numero · canal · atribuido"; campos vazios ficam vazios
            $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
            $data = function ($v) { return $v ? date('d/m/Y H:i', strtotime($v)) : ''; };
            $info = array_filter(['#' . $ticketId, $h($t['numero']), $t['canal'] !== '' ? LogoSistemaService::apchat(14) . ' ' . $h($t['canal']) : '', $h($t['atribuido'])], 'strlen');
            $this->form->addContent([ApchatConversaHtml::estilo() . '<div class="apc-topo">' . ApchatConversaHtml::avatar($t['contato'])
                . '<div><div class="apc-topo-nome">' . ($h($t['contato']) ?: $h($t['numero'])) . '</div><div class="apc-topo-info">' . implode(' · ', $info) . '</div></div></div>']);
            $campo = function ($rotulo, $valor) { return [new TLabel($rotulo, null, '12px', 'B', '100%'), new TTextDisplay($valor, '', '12px', '')]; };
            $row = $this->form->addFields($campo('Abertura:', $data($t['aberto_em'])), $campo('Encerramento:', $data($t['fechado_em'])),
                                          $campo('Fila:', $h($t['fila'])), $campo('Profissional:', $h(implode(', ', $t['profissionais']))));
            $row->layout = ['col-sm-2', 'col-sm-2', 'col-sm-3', 'col-sm-5'];
            $this->form->addContent([ApchatContatoConversaView::conversaHtml($mensagens, ['contato' => $t['contato'], 'tela' => true])]);
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            $this->form->addContent(['<div class="alert alert-danger">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>']);
        }
    }

    /** Botao que abre o ticket no APChat: pelo app instalado (LinkApp, chave do escritorio) ou na mesma aba ("apchat"). */
    public static function botaoAbrir(int $ticketId, string $url): TElement
    {
        $b = new TElement('a');
        $b->class = 'btn btn-sm btn-success';
        $b->style = 'margin-right:6px;';
        $b->href = '#';
        $b->onclick = LinkApp::apchatTicket($ticketId, $url);
        $b->add('<i class="fab fa-whatsapp"></i> Abrir no APChat');
        return $b;
    }
}
