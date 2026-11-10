<?php
/**
 * Configuracoes > APChat > Usuarios (10/11/2026): cada usuario ativo da Curciol com o atendente do APChat ligado (balao de
 * mensagens no topo) e os avisos (liga/desliga); o balao mostra sempre Abertos e Pendentes, como o APChat. "Ligar automaticamente":
 * pelo e-mail, senao pelo nome (ApchatUsuarioService). Um atendente so pode estar ligado a um usuario.
 */
class ApchatUsuarioList extends TPage
{
    private $form;
    private static $formName = 'form_ApchatUsuarioList';

    public function __construct($param = null)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('Usuários do APChat');

        try
        {
            $atendentes = ApchatBancoService::configurado() && ApchatBancoService::disponivel() ? ApchatBancoService::atendentes() : null;
        }
        catch (Throwable $e)
        {
            $atendentes = null;
        }

        $explica = new TElement('div');
        $explica->style = 'margin-bottom:8px;color:#555;';
        $explica->add('Cada usuário vê no balão do topo as conversas do APChat do atendente ligado a ele. Um atendente só pode estar ligado a um usuário.');
        $this->form->addContent([$explica]);

        if ($atendentes === null)
        {
            $aviso = new TElement('div');
            $aviso->class = 'alert alert-warning';
            $aviso->add('O banco do APChat não respondeu: não dá para escolher os atendentes agora. ' . htmlspecialchars(ApchatBancoService::erro(), ENT_QUOTES, 'UTF-8'));
            $this->form->addContent([$aviso]);
        }
        else
        {
            $opcoes = [];
            foreach ($atendentes as $a)
            {
                $opcoes[(int) $a['id']] = $a['name'] . (!empty($a['inactive']) ? ' (inativo no APChat)' : '');
            }

            TTransaction::open('escritorio');
            $usuarios = TTransaction::get()->query("SELECT id, name FROM system_users WHERE active = 'Y' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
            $ligacoes = [];
            foreach (ApchatUsuario::where('id', '>', 0)->load() ?: [] as $l)
            {
                $ligacoes[(int) $l->system_user_id] = $l;
            }
            TTransaction::close();

            $titulo = $this->form->addFields([new TLabel('<b>Usuário da Curciol</b>')], [new TLabel('<b>Atendente do APChat</b>')], [new TLabel('<b>Avisos no balão</b>')]);
            $titulo->layout = ['col-sm-4', 'col-sm-5', 'col-sm-3'];

            foreach ($usuarios as $u)
            {
                $id = (int) $u['id'];
                $l = $ligacoes[$id] ?? null;
                $combo = new TCombo("atendente_{$id}");
                $combo->addItems($opcoes);
                $combo->setSize('100%');
                $combo->setValue($l ? $l->apchat_usuario_id : null);
                $avisos = new TCheckButton("avisos_{$id}");
                $avisos->setIndexValue('S');
                $avisos->setUseSwitch(true, 'green');
                $avisos->setValue(!$l || $l->avisos === 'S' ? 'S' : null);

                $row = $this->form->addFields([new TLabel(htmlspecialchars((string) $u['name'], ENT_QUOTES, 'UTF-8'))], [$combo], [$avisos]);
                $row->layout = ['col-sm-4', 'col-sm-5', 'col-sm-3'];
            }

            $btn = $this->form->addAction('Salvar', new TAction([__CLASS__, 'onSalvar'], ['static' => 1]), 'fas:save #ffffff');
            $btn->addStyleClass('btn-primary');
            $this->form->addAction('Ligar automaticamente', new TAction([__CLASS__, 'onLigarAutomatico'], ['static' => 1]), 'fas:magic #2196F3');
        }

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(TBreadCrumb::create(['Configurações', 'APChat', 'Usuários']));
        $container->add($this->form);
        parent::add($container);
    }

    public function onReload($param = null)
    {
    }

    public function onShow($param = null)
    {
    }

    /** Grava todos: primeiro desliga quem ficou sem atendente (libera o atendente para outro usuario), depois liga. */
    public static function onSalvar($param)
    {
        try
        {
            TTransaction::open('escritorio');
            $usuarios = array_map('intval', array_column(TTransaction::get()->query("SELECT id FROM system_users WHERE active = 'Y'")->fetchAll(PDO::FETCH_ASSOC), 'id'));
            $escolhidos = [];
            foreach ($usuarios as $id)
            {
                $escolhidos[$id] = !empty($param["atendente_{$id}"]) ? (int) $param["atendente_{$id}"] : null;
            }
            $repetidos = array_filter(array_count_values(array_filter($escolhidos)), function ($n) { return $n > 1; });
            if ($repetidos)
            {
                throw new Exception('O mesmo atendente do APChat foi escolhido para mais de um usuário.');
            }
            foreach ($escolhidos as $id => $atendente)
            {
                $atual = ApchatUsuarioService::doUsuario($id);
                if ($atual && $atual->apchat_usuario_id && (int) $atual->apchat_usuario_id !== (int) $atendente)
                {
                    ApchatUsuarioService::ligar($id, null);
                }
            }
            foreach ($escolhidos as $id => $atendente)
            {
                if ($atendente) { ApchatUsuarioService::ligar($id, $atendente); }
                ApchatUsuarioService::preferencias($id, ($param["avisos_{$id}"] ?? '') === 'S', false);
            }
            TTransaction::close();
            TToast::show('success', 'Usuários do APChat salvos', 'topRight', 'far:check-circle');
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }

    public static function onLigarAutomatico($param = null)
    {
        try
        {
            TTransaction::open('escritorio');
            $n = ApchatUsuarioService::ligarAutomatico();
            TTransaction::close();
            TToast::show('success', $n ? "{$n} usuário(s) ligado(s) pelo e-mail ou pelo nome" : 'Nenhum usuário novo para ligar', 'topRight', 'fas:magic');
            AdiantiCoreApplication::loadPage(__CLASS__, 'onReload');
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
