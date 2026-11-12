<?php
/**
 * Administracao > Usuarios das integracoes (10/11/2026): numa tela so, para cada usuario ativo da Curciol,
 * - APChat: o usuario do APChat ligado (balao do topo) e as notificacoes; um atendente so pode estar ligado a um usuario; "Ligar automaticamente"
 *   pelo e-mail, senao pelo nome (ApchatUsuarioService);
 * - APMail: o e-mail de usuario do APMail (comeca com o do cadastro) e as notificacoes do envelope; "Conferir no APMail" consulta
 *   cada e-mail (so leitura) e mostra na Verificacao APMail se o APMail o reconhece.
 * - 12/11/2026: um botao "Ligar automaticamente" so (APChat como antes + APMail: ApmailUsuarioService::ligarAutomatico testa o e-mail
 *   escolhido, o do cadastro e o login); a Verificacao APMail mostra a situacao gravada (com a data) sem precisar conferir de novo.
 * So o grupo Admin (migracao 2026-11-10-apmail.sql). Endereco e token das integracoes: Escritorio > Configuracoes.
 */
class ApchatUsuarioList extends TPage
{
    private $form;
    private static $formName = 'form_ApchatUsuarioList';
    const LAYOUT = ['col-sm-3', 'col-sm-3', 'col-sm-1', 'col-sm-3', 'col-sm-1', 'col-sm-1'];

    public function __construct($param = null)
    {
        parent::__construct();

        $this->form = new BootstrapFormBuilder(self::$formName);
        $this->form->setFormTitle('Usuários das integrações');

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
        $explica->add('<b>APChat:</b> cada usuário vê no balão do topo os tickets do usuário do APChat ligado a ele (um usuário do APChat só pode estar ligado a um usuário da Curciol). '
                    . '<b>APMail:</b> o envelope do topo mostra as caixas do e-mail de usuário do APMail (começa com o e-mail do cadastro do usuário). '
                    . 'Endereço e token das integrações ficam em Escritório › Configurações.');
        $this->form->addContent([$explica]);

        if ($atendentes === null)
        {
            $aviso = new TElement('div');
            $aviso->class = 'alert alert-warning';
            $aviso->add('O banco do APChat não respondeu: os usuários do APChat não podem ser trocados agora (o APMail pode). ' . htmlspecialchars(ApchatBancoService::erro(), ENT_QUOTES, 'UTF-8'));
            $this->form->addContent([$aviso]);
        }
        $opcoes = [];
        foreach ($atendentes ?? [] as $a)
        {
            $opcoes[(int) $a['id']] = $a['name'] . (!empty($a['inactive']) ? ' (inativo no APChat)' : '');
        }

        TTransaction::open('escritorio');
        $usuarios = TTransaction::get()->query("SELECT id, name, email FROM system_users WHERE active = 'Y' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        $ligacoes = [];
        foreach (ApchatUsuario::where('id', '>', 0)->load() ?: [] as $l) { $ligacoes[(int) $l->system_user_id] = $l; }
        $apmail = [];
        foreach (ApmailUsuario::where('id', '>', 0)->load() ?: [] as $l) { $apmail[(int) $l->system_user_id] = $l; }
        $conferir = !empty($param['conferir']) ? self::conferirApmail($usuarios) : [];
        TTransaction::close();

        $disponivel = new THidden('apchat_disponivel');
        $disponivel->setValue($atendentes === null ? '0' : '1');
        $this->form->addFields([$disponivel]);

        $titulo = $this->form->addFields([new TLabel('<b>Usuário da Curciol</b>')], [new TLabel('<b>Usuário do APChat</b>')], [new TLabel('<b>Notificações APChat</b>')],
                                         [new TLabel('<b>E-mail de usuário do APMail</b>')], [new TLabel('<b>Notificações APMail</b>')], [new TLabel('<b>Verificação APMail</b>')]);
        $titulo->layout = self::LAYOUT;

        foreach ($usuarios as $u)
        {
            $id = (int) $u['id'];
            $l = $ligacoes[$id] ?? null;
            $combo = new TCombo("atendente_{$id}");
            $itens = $opcoes;
            if ($atendentes === null && $l && $l->apchat_usuario_id)
            {
                $itens = [(int) $l->apchat_usuario_id => 'Usuário ' . (int) $l->apchat_usuario_id . ' do APChat'];
            }
            $combo->addItems($itens);
            $combo->setSize('100%');
            $combo->setValue($l ? $l->apchat_usuario_id : null);
            if ($atendentes === null) { $combo->setEditable(false); }
            $avisos = new TCheckButton("avisos_{$id}");
            $avisos->setIndexValue('S');
            $avisos->setUseSwitch(true, 'green');
            $avisos->setValue(!$l || $l->avisos === 'S' ? 'S' : null);

            $m = $apmail[$id] ?? null;
            $email = new TEntry("email_apmail_{$id}");
            $email->setSize('100%');
            $email->setValue($m && trim((string) $m->email) !== '' ? $m->email : mb_strtolower(trim((string) $u['email'])));
            $email->placeholder = 'e-mail de usuário do APMail';
            $avisosMail = new TCheckButton("avisos_apmail_{$id}");
            $avisosMail->setIndexValue('S');
            $avisosMail->setUseSwitch(true, 'blue');
            $avisosMail->setValue(!$m || $m->avisos !== 'N' ? 'S' : null);
            $situacao = new TElement('span');
            $situacao->add($conferir[$id] ?? self::situacaoGravada($m ? $m->situacao : null, $m ? $m->conferido_em : null));

            $row = $this->form->addFields([new TLabel(htmlspecialchars((string) $u['name'], ENT_QUOTES, 'UTF-8'))], [$combo], [$avisos], [$email], [$avisosMail], [$situacao]);
            $row->layout = self::LAYOUT;
        }

        $btn = $this->form->addAction('Salvar', new TAction([__CLASS__, 'onSalvar'], ['static' => 1]), 'fas:save #ffffff');
        $btn->addStyleClass('btn-primary');
        $this->form->addAction('Ligar automaticamente', new TAction([__CLASS__, 'onLigarAutomatico'], ['static' => 1]), 'fas:magic #2196F3');
        $this->form->addAction('Conferir no APMail', new TAction([__CLASS__, 'onConferirApmail'], ['static' => 1]), 'fas:check-double ' . LogoSistemaService::COR_APMAIL);

        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->add(TBreadCrumb::create(['Administração', 'Usuários das integrações']));
        $container->add($this->form);
        parent::add($container);
    }

    public function onReload($param = null)
    {
    }

    public function onShow($param = null)
    {
    }

    /** Consulta (so leitura) o e-mail de cada usuario no APMail: [usuario => icone + resumo]. */
    private static function conferirApmail(array $usuarios): array
    {
        $escritorio = ApmailApiService::escritorio();
        $token = $escritorio ? trim((string) $escritorio->token_apmail) : '';
        $r = [];
        foreach ($usuarios as $u)
        {
            $id = (int) $u['id'];
            if ($token === '') { $r[$id] = '<span title="Sem token do APMail no escritório" style="color:#999">—</span>'; continue; }
            $email = ApmailUsuarioService::email($id);
            if ($email === '') { $r[$id] = '<span title="Sem e-mail" style="color:#999">—</span>'; continue; }
            try
            {
                $n = ApmailApiService::notificacoes(ApmailApiService::url($escritorio), $token, $email);
                $r[$id] = '<i class="fas fa-check-circle" style="color:#4CAF50" title="Reconhecido: ' . count($n['caixas']) . ' caixa(s), ' . $n['pendentes'] . ' conversa(s) de e-mail pendente(s)"></i> '
                        . '<small>' . count($n['caixas']) . ' cx</small>';
            }
            catch (Throwable $e)
            {
                $r[$id] = '<i class="fas fa-times-circle" style="color:#e53935" title="' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '"></i>';
            }
        }
        return $r;
    }

    /** Coluna Verificacao APMail com a ultima conferencia gravada (S reconhecido, N nao reconhecido). */
    public static function situacaoGravada(?string $situacao, ?string $quando): string
    {
        if ($situacao !== 'S' && $situacao !== 'N') { return ''; }
        $data = $quando ? date('d/m/Y H:i', strtotime($quando)) : '';
        return $situacao === 'S'
            ? '<i class="fas fa-check-circle" style="color:#4CAF50" title="Reconhecido no APMail' . ($data ? ' em ' . $data : '') . '"></i> <small style="color:#888">' . $data . '</small>'
            : '<i class="fas fa-times-circle" style="color:#e53935" title="Não reconhecido no APMail' . ($data ? ' em ' . $data : '') . '"></i> <small style="color:#888">' . $data . '</small>';
    }

    public static function onConferirApmail($param = null)
    {
        AdiantiCoreApplication::loadPage(__CLASS__, 'onReload', ['conferir' => 1]);
    }

    /**
     * Grava todos. APChat (so se o banco do APChat respondeu ao abrir a tela): primeiro desliga quem ficou sem atendente (libera o
     * atendente para outro usuario), depois liga. APMail: e-mail (igual ao do cadastro = segue o cadastro) e avisos.
     */
    public static function onSalvar($param)
    {
        try
        {
            TTransaction::open('escritorio');
            $usuarios = array_map('intval', array_column(TTransaction::get()->query("SELECT id FROM system_users WHERE active = 'Y'")->fetchAll(PDO::FETCH_ASSOC), 'id'));
            if (($param['apchat_disponivel'] ?? '0') === '1')
            {
                $escolhidos = [];
                foreach ($usuarios as $id)
                {
                    $escolhidos[$id] = !empty($param["atendente_{$id}"]) ? (int) $param["atendente_{$id}"] : null;
                }
                $repetidos = array_filter(array_count_values(array_filter($escolhidos)), function ($n) { return $n > 1; });
                if ($repetidos)
                {
                    throw new Exception('O mesmo usuário do APChat foi escolhido para mais de um usuário da Curciol.');
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
            }
            foreach ($usuarios as $id)
            {
                if (array_key_exists("email_apmail_{$id}", $param))
                {
                    ApmailUsuarioService::salvar($id, (string) $param["email_apmail_{$id}"], ($param["avisos_apmail_{$id}"] ?? '') === 'S');
                }
            }
            TTransaction::close();
            ApmailEnvelopeService::limparCache();
            TToast::show('success', 'Usuários das integrações salvos', 'topRight', 'far:check-circle');
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
            $partes = [];
            // APChat: so com o banco do APChat respondendo
            if (ApchatBancoService::configurado() && ApchatBancoService::disponivel())
            {
                TTransaction::open('escritorio');
                $n = ApchatUsuarioService::ligarAutomatico();
                TTransaction::close();
                $partes[] = 'APChat: ' . ($n ? "{$n} usuário(s) ligado(s) pelo e-mail ou pelo nome" : 'nenhum novo');
            }
            else
            {
                $partes[] = 'APChat: banco do APChat não respondeu';
            }
            // APMail: testa o e-mail escolhido, o do cadastro e o login de cada usuario
            TTransaction::open('escritorio');
            $m = ApmailUsuarioService::ligarAutomatico();
            TTransaction::close();
            ApmailEnvelopeService::limparCache();
            $partes[] = "APMail: {$m['ligados']} reconhecido(s), {$m['nao_reconhecidos']} não reconhecido(s)";
            TToast::show('success', implode('<br>', $partes), 'topRight', 'fas:magic');
            AdiantiCoreApplication::loadPage(__CLASS__, 'onReload');
        }
        catch (Exception $e)
        {
            TTransaction::rollback();
            new TMessage('error', $e->getMessage());
        }
    }
}
