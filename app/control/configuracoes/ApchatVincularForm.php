<?php
/**
 * Janela da tela "Contatos sem vinculo" do APChat (03/11/2026):
 *   - modo "vincular": poe o numero do APChat na aba Contatos de um cadastro existente (nome, descricao, principal);
 *   - modo "trocar": o numero do APChat passa a ser o de um contato que a pessoa ja tem (o historico do numero antigo
 *     no APChat vai para este contato, com o aviso obrigatorio da troca de numero).
 * Ao gravar, a cortina da conversa e atualizada (ver ApchatVinculoService).
 */
class ApchatVincularForm extends TWindow
{
    protected $form;
    private static $formName = 'form_ApchatVincular';

    public function __construct($param = null)
    {
        parent::__construct();
        parent::setSize(0.6, null);
        parent::setProperty('class', 'window_modal');
        $modo = ($param['modo'] ?? 'vincular') === 'trocar' ? 'trocar' : 'vincular';
        parent::setTitle($modo === 'trocar' ? 'Trocar o número de um contato' : 'Vincular a um cadastro existente');

        $this->form = new BootstrapFormBuilder(self::$formName);

        $key = new THidden('key');
        $modo_campo = new THidden('modo');
        $pessoa_id = new TDBUniqueSearch('pessoa_id', 'escritorio', 'Pessoa', 'id', 'nome', 'nome asc');
        $pessoa_id->setMinLength(2);
        $pessoa_id->setMask('{id} - {nome}');
        $pessoa_id->setSize('100%');

        $key->setValue((int) ($param['key'] ?? 0));
        $modo_campo->setValue($modo);

        $numero = '';
        try
        {
            $c = ApchatBancoService::contato((int) ($param['key'] ?? 0));
            $numero = $c ? (string) $c['number'] : '';
        }
        catch (Exception $e)
        {
        }

        $this->form->addFields([$key, $modo_campo]);
        $this->form->addContent(['<div style="margin-bottom:6px">Número do APChat: <b>' . ApchatContatoSemVinculoList::formatarNumero($numero) . '</b></div>']);
        $row = $this->form->addFields([new TLabel('Cadastro (nome):', '#ff0000', '12px', null, '100%'), $pessoa_id]);
        $row->layout = ['col-sm-12'];

        if ($modo === 'trocar')
        {
            $contato_id = new TCombo('contato_id');
            $contato_id->setSize('100%');
            $pessoa_id->setChangeAction(new TAction([__CLASS__, 'onChangePessoa']));
            $row = $this->form->addFields([new TLabel('Contato que recebe este número:', '#ff0000', '12px', null, '100%'), $contato_id]);
            $row->layout = ['col-sm-12'];
            $this->form->addContent(['<div style="color:#607D8B; font-size:11px"><i class="fas fa-info-circle" style="color:#03A9F4"></i> O número do contato escolhido é trocado por este. '
                . 'No APChat, o contato do número antigo deixa de existir e todo o histórico passa para este número.</div>']);
        }
        else
        {
            $nome = new TEntry('nome');
            $descricao = new TEntry('descricao');
            $principal = new TCheckButton('principal');
            $nome->forceUpperCase();
            $descricao->forceUpperCase();
            $nome->setSize('100%');
            $descricao->setSize('100%');
            $nome->setTip('De quem é o contato (vazio: o da própria pessoa, se for o principal)');
            $descricao->setTip('Ex.: FILHO - JOSÉ, ESPOSA, ESCRITÓRIO (opcional)');
            $principal->setUseSwitch(true, 'blue');
            $principal->setIndexValue('S');
            $principal->setInactiveIndexValue('N');
            $row = $this->form->addFields([new TLabel('Nome do contato:', null, '12px', null, '100%'), $nome],
                                          [new TLabel('Descrição:', null, '12px', null, '100%'), $descricao],
                                          [new TLabel('Principal:', null, '12px', null, '100%'), $principal]);
            $row->layout = ['col-sm-5', 'col-sm-5', 'col-sm-2'];
            $this->form->addContent(['<div style="color:#607D8B; font-size:11px"><i class="fas fa-info-circle" style="color:#03A9F4"></i> O número entra na aba Contatos do cadastro. '
                . 'Principal: passa a ser o Telefone principal dos Dados cadastrais (um telefone só pode ser principal de uma pessoa).</div>']);
        }

        $btn = $this->form->addAction($modo === 'trocar' ? 'Trocar número' : 'Vincular', new TAction([__CLASS__, 'onSave'], ['static' => 1]), 'fas:save #ffffff');
        $btn->addStyleClass('btn-primary');

        parent::add($this->form);
    }

    public function onShow($param = null)
    {
    }

    /** Modo trocar: os contatos (com telefone) da pessoa escolhida. */
    public static function onChangePessoa($param = null)
    {
        try
        {
            $itens = [];
            if (!empty($param['pessoa_id']))
            {
                TTransaction::open('escritorio');
                $st = TTransaction::get()->prepare("SELECT id, nome, descricao, telefone, principal FROM pessoa_contato WHERE pessoa_id = ? AND COALESCE(telefone, '') <> '' ORDER BY (principal = 'S') DESC, id");
                $st->execute([(int) $param['pessoa_id']]);
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c)
                {
                    $itens[$c['id']] = PessoaContatoService::formatarTelefone($c['telefone'])
                        . ($c['principal'] === 'S' ? ' (principal)' : '')
                        . (trim((string) $c['descricao']) !== '' ? ' - ' . $c['descricao'] : '')
                        . (trim((string) $c['nome']) !== '' ? ' - ' . $c['nome'] : '');
                }
                TTransaction::close();
            }
            TCombo::reload(self::$formName, 'contato_id', $itens, true);
        }
        catch (Exception $e)
        {
            TTransaction::rollbackAll();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onSave($param = null)
    {
        try
        {
            $id = (int) ($param['key'] ?? 0);
            $c = ApchatBancoService::contato($id);
            if (!$c) { throw new Exception('Contato não encontrado no APChat.'); }
            if (empty($param['pessoa_id'])) { throw new Exception('Escolha o cadastro.'); }

            if (($param['modo'] ?? '') === 'trocar')
            {
                if (empty($param['contato_id'])) { throw new Exception('Escolha o contato que recebe este número.'); }
                TTransaction::open('escritorio');
                $st = TTransaction::get()->prepare('SELECT telefone FROM pessoa_contato WHERE id = ? AND pessoa_id = ?');
                $st->execute([(int) $param['contato_id'], (int) $param['pessoa_id']]);
                $antigo = APChatContactService::normalizarTelefone((string) $st->fetchColumn());
                TTransaction::close();
                $novo = APChatContactService::normalizarTelefone($c['number']);
                if ($antigo === '' || $novo === '') { throw new Exception('Contato sem telefone válido.'); }
                // aviso obrigatorio da troca de numero
                new TQuestion(PessoaContatoService::mensagemTrocaDeNumero([[$antigo, $novo]]),
                              new TAction([__CLASS__, 'onConfirmarTroca'], ['key' => $id, 'contato_id' => (int) $param['contato_id'], 'static' => 1]),
                              null, 'Troca de número', 'Confirmar a troca', 'Cancelar');
                return;
            }

            $r = ApchatVinculoService::vincular((int) $param['pessoa_id'], (string) $c['number'], $param['nome'] ?? null, $param['descricao'] ?? null, ($param['principal'] ?? 'N') === 'S');
            self::concluir($id, 'Número vinculado ao cadastro.', $r);
        }
        catch (Exception $e)
        {
            TTransaction::rollbackAll();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onConfirmarTroca($param = null)
    {
        try
        {
            $id = (int) ($param['key'] ?? 0);
            $c = ApchatBancoService::contato($id);
            if (!$c) { throw new Exception('Contato não encontrado no APChat.'); }
            $r = ApchatVinculoService::trocarNumero((int) $param['contato_id'], (string) $c['number']);
            // o contato do APChat pode ter passado a ser o do numero antigo (transferencia): reabre pelo mesmo id
            self::concluir($id, 'Número trocado.', $r);
        }
        catch (Exception $e)
        {
            TTransaction::rollbackAll();
            new TMessage('error', $e->getMessage());
        }
    }

    private static function concluir(int $id, string $mensagem, object $r): void
    {
        TWindow::closeWindow();
        $apchat = in_array($r->situacao, [ApchatContatoLog::SITUACAO_ERRO, ApchatContatoLog::SITUACAO_PENDENTE], true) ? ' APChat: ' . $r->mensagem : '';
        TToast::show($apchat ? 'warning' : 'success', $mensagem . $apchat, 'topRight', $apchat ? 'fas:exclamation-triangle' : 'far:check-circle');
        ApchatContatoConversaView::reabrir($id);
    }
}
