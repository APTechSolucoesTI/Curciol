<?php
/**
 * Clique num item do balao do topo (13/11/2026): ticket ou conversa do chat interno do APChat, ou caixa do APMail.
 * O balao nao leva o endereco: o navegador pergunta aqui, que confere NA HORA (sem cache) se o item ainda deveria estar no
 * balao do usuario e so entao devolve para onde ir. Item desatualizado (ticket encerrado, transferido ou fora dos acessos,
 * conversa lida ou caixa sem acesso) volta um aviso e é removido do balão.
 * Nao conseguiu conferir (APChat/APMail fora): tambem nao abre.
 *   engine.php?class=TopoAbrir&method=onAbrir&static=1&tipo=ticket|chat|caixa&id=...
 *   -> {ok: true, app: true, link, nome} (app instalado) | {ok: true, url, janela} (aba) | {ok: false, mensagem}
 */
class TopoAbrir
{
    public static function onAbrir($param = null)
    {
        $usuario = (int) TSession::getValue('userid');
        if (session_status() === PHP_SESSION_ACTIVE)
        {
            session_write_close(); // so le a sessao: nao segura os outros pedidos do usuario
        }
        try
        {
            TTransaction::open('escritorio');
            $r = self::decidir((array) $param, $usuario);
            TTransaction::close();
        }
        catch (Throwable $e)
        {
            TTransaction::rollback();
            $r = self::nao('Não foi possível conferir agora. Tente de novo em instantes.');
        }
        if (!headers_sent())
        {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
        }
        echo json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Usa a transacao aberta ('escritorio'). */
    public static function decidir(array $param, int $usuario): array
    {
        if ($usuario <= 0) { return self::nao('Sessão encerrada: entre de novo na Curciol.'); }
        $id = $param['id'] ?? null;
        if (!is_string($id)) { return self::nao('Item inválido.'); }
        $id = trim($id);
        $tipo = $param['tipo'] ?? '';
        if ($tipo === 'caixa') { return self::caixa($id, $usuario); }
        if ($tipo !== 'ticket' && $tipo !== 'chat') { return self::nao('Item desconhecido.'); }

        $ligacao = ApchatUsuarioService::doUsuario($usuario);
        if (!$ligacao || !$ligacao->apchat_usuario_id)
        {
            return self::nao('Seu usuário não está ligado a um usuário do APChat (Administração › Usuários das integrações).', true);
        }
        if ($ligacao->avisos !== 'S') { return self::nao('As notificações do APChat estão desligadas para você.', true); }
        $eu = (int) $ligacao->apchat_usuario_id;
        return $tipo === 'chat' ? self::chat($id, $eu) : self::ticket($id, $eu);
    }

    private static function ticket(string $id, int $eu): array
    {
        $ticket = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if ($ticket === false) { return self::nao('Atendimento inválido.'); }
        try
        {
            $c = ApchatBancoService::conferirTicket($ticket, $eu);
        }
        catch (Throwable $e)
        {
            return self::nao('Não foi possível conferir o atendimento no APChat agora. Tente de novo em instantes.');
        }
        if (!$c)
        {
            ApchatBalaoService::invalidarEstado();
            return self::nao('Este atendimento não existe mais no APChat.', true);
        }
        $t = $c['ticket'];
        // o mesmo que o balao mostra: aberto com ele, ou pendente que ele ve pela regra do APChat (fora o que aguarda o SIM/NAO)
        $meu = ($t['status'] ?? '') === 'open' && (int) ($t['userId'] ?? 0) === $eu;
        $pendente = ($t['status'] ?? '') === 'pending'
                 && !in_array(preg_replace('/\D/', '', (string) ($t['numero'] ?? '')), ApchatRespostaService::numerosAguardando(), true)
                 && ApchatVisibilidadeService::podeVer($t + ['carteira' => $c['carteira']], ['id' => $eu] + $c['acessos'], $c['config']);
        if (!($c['acessos']['existe'] ?? true) || (!$meu && !$pendente))
        {
            ApchatAcessoService::esquecer($eu); // o balao seguinte ja vem conferido
            ApchatBalaoService::invalidarEstado();
            return self::nao('Este atendimento não está mais nas suas notificações do APChat (foi encerrado, transferido ou você não tem mais acesso a ele).', true);
        }
        return LinkApp::ligado('apchat') ? self::app('web+apchat://ticket/' . $ticket, 'APChat') : self::aba(ApchatApiService::urlTicket($ticket), 'apchat');
    }

    /** $id = "usuario:N" (conversa com o usuario N) ou "equipe:N". */
    private static function chat(string $id, int $eu): array
    {
        if (!preg_match('/^(usuario|equipe):([1-9][0-9]{0,11})$/', $id, $m)) { return self::nao('Conversa inválida.'); }
        [$tipo, $conversa] = [$m[1], (int) $m[2]];
        try
        {
            $linhas = ApchatBancoService::chatNaoLidas([$eu]);
        }
        catch (Throwable $e)
        {
            return self::nao('Não foi possível conferir o chat interno do APChat agora. Tente de novo em instantes.');
        }
        foreach ($linhas as $l)
        {
            if ((int) $l['usuario'] === $eu && $l['tipo'] === $tipo && (int) $l['conversa_id'] === $conversa && (int) $l['qtd'] > 0)
            {
                return LinkApp::ligado('apchat') ? self::app('web+apchat://chat/' . $tipo . '/' . $conversa, 'APChat')
                                                 : self::aba(ApchatApiService::urlChat($tipo, $conversa), 'apchat');
            }
        }
        ApchatBalaoService::invalidarEstado();
        return self::nao('Esta conversa não está mais nas suas notificações do APChat.', true);
    }

    /** $id = id da caixa no APMail (mailboxes[].id). */
    private static function caixa(string $id, int $usuario): array
    {
        $id = strtolower($id);
        if ($id === '' || !preg_match('/^[0-9a-z-]{1,64}$/', $id)) { return self::nao('Caixa inválida.'); }
        $r = ApmailEnvelopeService::caixas($usuario, true, true);
        if (($r['situacao'] ?? '') !== 'ok')
        {
            $motivo = ['nao_liberado' => 'Seu e-mail não está liberado no APMail para esta integração.', 'sem_email' => 'Seu usuário não tem e-mail de usuário do APMail.',
                       'desligado' => 'As notificações do APMail estão desligadas para você.', 'sem_integracao' => 'Integração com o APMail não configurada.'];
            $situacao = $r['situacao'] ?? '';
            return self::nao($motivo[$situacao] ?? 'Não foi possível conferir a caixa no APMail agora. Tente de novo em instantes.', isset($motivo[$situacao]));
        }
        foreach ($r['caixas'] as $c)
        {
            if ((string) $c['id'] !== $id) { continue; }
            $caminho = (string) ($c['caminho'] ?? '/');
            if ($caminho === '/') { return self::nao('O APMail não informou o endereço desta caixa.'); }
            return LinkApp::ligado('apmail') ? self::app(LinkApp::apmailCaixaLink($caminho), 'APMail') : self::aba((string) $c['url'], 'apmail');
        }
        return self::nao('Você não tem mais acesso a esta caixa no APMail.', true);
    }

    private static function app(string $link, string $nome): array
    {
        return ['ok' => true, 'app' => true, 'link' => $link, 'nome' => $nome];
    }

    private static function aba(string $url, string $janela): array
    {
        if (!preg_match('#^https?://#i', $url)) { return self::nao('Endereço inválido.'); }
        return ['ok' => true, 'url' => $url, 'janela' => $janela];
    }

    private static function nao(string $mensagem, bool $desatualizado = false): array
    {
        return ['ok' => false, 'mensagem' => $mensagem, 'desatualizado' => $desatualizado];
    }
}
