<?php
/**
 * Cortina da tela "Contatos sem vinculo" do APChat (03/11/2026): a conversa do contato no APChat (so leitura, de todos
 * os tickets) para identificar de quem e o numero, os cadastros da Curciol que ja tem o numero e as acoes - da para
 * fazer mais de uma com o mesmo contato (ex.: criar um cliente e vincular o numero a outro cliente):
 *   - Criar novo cadastro (Cliente, Parte contraria, Fornecedor, Parceiro, Profissional, Representante legal);
 *   - Vincular a um cadastro existente; Trocar o numero de um contato existente (ApchatVincularForm);
 *   - Ignorar / Voltar para a lista.
 */
class ApchatContatoConversaView extends TPage
{
    protected $form;
    private static $formName = 'formView_ApchatContatoConversa';

    const CADASTROS = [
        'ClienteForm'            => 'Cliente',
        'ContraparteForm'        => 'Parte contrária',
        'FornecedorForm'         => 'Fornecedor',
        'ParceiroForm'           => 'Parceiro',
        'ProfissionalForm'       => 'Profissional',
        'RepresentanteLegalForm' => 'Representante legal',
    ];

    public function __construct($param = null)
    {
        parent::__construct();
        if (!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setTagName('div');
        $this->form->setFormTitle('Contato do APChat');

        parent::setTargetContainer('adianti_right_panel');

        $btnClose = new TButton('closeCurtain');
        $btnClose->class = 'btn btn-sm btn-default';
        $btnClose->style = 'margin-right:10px;';
        $btnClose->onClick = "Template.closeRightPanel();";
        $btnClose->setLabel("Fechar");
        $btnClose->setImage('fas:times');
        $this->form->addHeaderWidget($btnClose);

        parent::add($this->form);

        $style = new TStyle('right-panel > .container-part[page-name=ApchatContatoConversaView]');
        $style->width = '65% !important';
        $style->show(true);
    }

    public function onShow($param = null)
    {
        try
        {
            $id = (int) ($param['key'] ?? $param['id'] ?? 0);
            $c = ApchatBancoService::contato($id);
            if (!$c) { throw new Exception('Contato não encontrado no APChat (pode ter sido excluído ou transferido).'); }

            $numero = (string) $c['number'];
            $vinculos = ApchatVinculoService::vinculos($id, $numero);
            $ignorado = ApchatVinculoService::ignorado($id);
            $mensagens = ApchatBancoService::conversa($id);

            $this->form->setFormTitle(htmlspecialchars((string) $c['name']) . ' · ' . ApchatContatoSemVinculoList::formatarNumero($numero));

            // Abrir no APChat (10/11/2026): o ticket mais recente do contato, na aba "apchat"
            $ultima = $mensagens ? end($mensagens) : null;
            if ($ultima && !empty($ultima['ticketId']))
            {
                TTransaction::open('escritorio');
                $url = ApchatApiService::urlTicket((int) $ultima['ticketId']);
                TTransaction::close();
                $this->form->addHeaderWidget(ApchatTicketView::botaoAbrir($url));
            }

            $row = $this->form->addFields(
                [new TLabel("Nome no APChat:", null, '12px', 'B', '100%'), new TTextDisplay(htmlspecialchars((string) $c['name']), '', '12px', '')],
                [new TLabel("Número:", null, '12px', 'B', '100%'), new TTextDisplay(ApchatContatoSemVinculoList::formatarNumero($numero), '', '12px', '')],
                [new TLabel("Criado no APChat em:", null, '12px', 'B', '100%'), new TTextDisplay($c['createdAt'] ? date('d/m/Y H:i', strtotime($c['createdAt'])) : '', '', '12px', '')]
            );
            $row->layout = ['col-sm-5', 'col-sm-3', 'col-sm-4'];

            // vinculos ja feitos
            $this->form->addContent([self::titulo('Na Curciol')]);
            if ($vinculos)
            {
                $html = '<table class="table table-sm table-bordered" style="margin-bottom:8px"><tr style="background:#f3f5f8"><th>Cadastro</th><th>Contato</th><th>Descrição</th><th>Principal</th></tr>';
                foreach ($vinculos as $v)
                {
                    $html .= '<tr><td>' . (int) $v['pessoa_id'] . ' - ' . htmlspecialchars((string) $v['pessoa']) . '</td><td>' . htmlspecialchars((string) $v['nome'])
                           . '</td><td>' . htmlspecialchars((string) $v['descricao']) . '</td><td>' . ($v['principal'] === 'S' ? 'Sim' : 'Não') . '</td></tr>';
                }
                $this->form->addContent([$html . '</table>']);
            }
            else
            {
                $this->form->addContent(['<div style="color:#777;margin-bottom:8px">Nenhum cadastro da Curciol tem este número ainda.' . ($ignorado ? ' <b>Contato ignorado.</b>' : '') . '</div>']);
            }

            // acoes (da para fazer mais de uma)
            if ($numero !== '')
            {
                $novo = new TDropDown('Criar novo cadastro', 'fas:user-plus #69aa46');
                $novo->setButtonClass('btn btn-default btn-sm');
                foreach (self::CADASTROS as $classe => $rotulo)
                {
                    $novo->addAction($rotulo, new TAction([__CLASS__, 'onNovoCadastro'], ['key' => $id, 'cadastro' => $classe, 'static' => 1]));
                }
                $vincular = new TButton('btn_vincular');
                $vincular->setAction(new TAction(['ApchatVincularForm', 'onShow'], ['key' => $id, 'modo' => 'vincular']), 'Vincular a um cadastro existente');
                $vincular->setImage('fas:link #478fca');
                $vincular->class = 'btn btn-default btn-sm';
                $trocar = new TButton('btn_trocar');
                $trocar->setAction(new TAction(['ApchatVincularForm', 'onShow'], ['key' => $id, 'modo' => 'trocar']), 'Trocar o número de um contato');
                $trocar->setImage('fas:exchange-alt #e67e22');
                $trocar->class = 'btn btn-default btn-sm';
                $ignorar = new TButton('btn_ignorar');
                $ignorar->setAction(new TAction([__CLASS__, $ignorado ? 'onDesfazerIgnorar' : 'onIgnorar'], ['key' => $id, 'static' => 1]), $ignorado ? 'Voltar para a lista' : 'Ignorar');
                $ignorar->setImage($ignorado ? 'fas:undo #555' : 'fas:eye-slash #999');
                $ignorar->class = 'btn btn-default btn-sm';
                foreach ([$vincular, $trocar, $ignorar] as $b) { $this->form->addField($b); }
                $acoes = new TElement('div');
                $acoes->style = 'display:flex; flex-wrap:wrap; gap:6px; margin:4px 0 10px';
                $acoes->add($novo);
                $acoes->add($vincular);
                $acoes->add($trocar);
                $acoes->add($ignorar);
                $this->form->addContent([$acoes]);
            }
            else
            {
                $this->form->addContent(['<div class="alert alert-warning">Contato sem número (o WhatsApp não informou o telefone): dá para ler a conversa, mas não dá para vincular a um cadastro.</div>']);
            }

            $this->form->addContent([self::titulo('Conversa no APChat (só leitura)')]);
            $this->form->addContent([self::conversaHtml($mensagens, ['contato' => (string) $c['name']])]);
        }
        catch (Exception $e)
        {
            $this->form->addContent(['<div class="alert alert-danger">' . htmlspecialchars($e->getMessage()) . '</div>']);
        }
    }

    private static function titulo(string $texto): string
    {
        return '<div style="font-weight:600; color:#0d4069; border-bottom:1px solid #d6dde6; margin:10px 0 6px; padding-bottom:3px">' . $texto . '</div>';
    }

    /** Conversa no padrao do APChat (ApchatConversaHtml, 11/11/2026): baloes, formatacao do WhatsApp, separadores e vistos. */
    public static function conversaHtml(array $mensagens, array $opcoes = []): string
    {
        return ApchatConversaHtml::html($mensagens, $opcoes);
    }

    /** Abre o cadastro escolhido (cortina por cima) ja com o numero e o nome do APChat. */
    public static function onNovoCadastro($param = null)
    {
        try
        {
            $classe = (string) ($param['cadastro'] ?? '');
            if (!isset(self::CADASTROS[$classe])) { throw new Exception('Cadastro inválido.'); }
            $c = ApchatBancoService::contato((int) ($param['key'] ?? 0));
            if (!$c) { throw new Exception('Contato não encontrado no APChat.'); }
            Navegacao::atualizarListaAoFechar();
            AdiantiCoreApplication::loadPage($classe, 'onShow', ['apchat_numero' => $c['number'], 'apchat_nome' => $c['name'], 'target_container' => 'adianti_right_panel']);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onIgnorar($param = null)
    {
        new TQuestion('Ignorar este contato? Ele sai da lista de contatos sem vínculo (dá para voltar pelo filtro Situação: Ignorados).',
                      new TAction([__CLASS__, 'onConfirmarIgnorar'], ['key' => (int) ($param['key'] ?? 0), 'static' => 1]));
    }

    public static function onConfirmarIgnorar($param = null)
    {
        try
        {
            $c = ApchatBancoService::contato((int) ($param['key'] ?? 0));
            if (!$c) { throw new Exception('Contato não encontrado no APChat.'); }
            ApchatVinculoService::ignorar((int) $c['id'], $c['number'], $c['name']);
            TToast::show('success', 'Contato ignorado.', 'topRight', 'far:check-circle');
            self::reabrir((int) $c['id']);
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onDesfazerIgnorar($param = null)
    {
        try
        {
            ApchatVinculoService::desfazerIgnorar((int) ($param['key'] ?? 0));
            TToast::show('success', 'O contato voltou para a lista.', 'topRight', 'far:check-circle');
            self::reabrir((int) ($param['key'] ?? 0));
        }
        catch (Exception $e)
        {
            new TMessage('error', $e->getMessage());
        }
    }

    /** Atualiza a cortina no lugar (depois de uma acao) e marca a lista de baixo para atualizar ao fechar. */
    public static function reabrir(int $id): void
    {
        Navegacao::atualizarListaAoFechar();
        TScript::create('$("#adianti_right_panel > [page_name=\'ApchatContatoConversaView\']").remove();');
        AdiantiCoreApplication::loadPage(__CLASS__, 'onShow', ['key' => $id, 'target_container' => 'adianti_right_panel']);
    }
}
