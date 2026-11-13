<?php
/**
 * Balao do APChat no topo (10/11/2026), atualizado a cada verify_messages_menu pelo BuilderTemplate.js: tickets Abertos e Pendentes
 * do atendente ligado ao usuario, como no APChat; o contador conta os tickets esperando resposta (ApchatBalaoService) e, desde
 * 13/11/2026, as mensagens não lidas do Chat Privado. O ícone abre o APChat, e os itens só abrem após conferência no servidor.
 * (Antes: mensagens internas do framework, sem uso desde 05/2024.)
 */
class MessageList extends TElement
{
    public function __construct($param)
    {
        parent::__construct('div');

        try
        {
            TTransaction::open('escritorio');
            $r = ApchatBalaoService::conversas((int) TSession::getValue('userid'));
            $inicio = LinkApp::apchatInicio(ApchatApiService::urlInicio());
            TTransaction::close();
        }
        catch (Throwable $e)
        {
            TTransaction::rollback();
            $r = ['situacao' => 'erro', 'itens' => [], 'abertos' => [], 'total' => 0];
        }

        if (in_array($r['situacao'], ['desligado', 'erro'], true))
        {
            return; // sem balao (avisos desligados pelo usuario, ou APChat fora)
        }

        $this->class = 'fast-drop';
        $total = (int) $r['total'];

        $a = new TElement('a');
        $a->{'class'} = 'button-circle';
        $a->{'href'} = '#';
        $a->onclick = $inicio;
        $a->{'aria-label'} = 'Abrir APChat, ' . $total . ' notificações';
        // logo do APChat (10/11/2026) no lugar do icone de conversa
        $a->add(LogoSistemaService::apchat(22));
        $a->add(TElement::tag('div', $total, ['class' => 'ball-notice', 'data-count' => $total]));
        $rotulo = TElement::tag('div', $a, ['class' => 'fast-drop-label']);
        $rotulo->add(TElement::tag('button', '<i class="fas fa-chevron-down" aria-hidden="true"></i>', [
            'type' => 'button', 'class' => 'topo-notificacoes-alternar', 'aria-label' => 'Notificações do APChat',
            'aria-expanded' => 'false', 'aria-controls' => 'apchat-notificacoes',
        ]));
        $this->add($rotulo);

        $lista = new TElement('div');
        $lista->{'class'} = 'fast-drop-open topo-notificacoes-painel';
        $lista->id = 'apchat-notificacoes';
        $lista->add(TElement::tag('div', '', ['class' => 'topo-notificacoes-status', 'role' => 'status']));

        if ($r['situacao'] === 'sem_ligacao')
        {
            $lista->add(TElement::tag('div', 'Seu usuário não está ligado a um usuário do APChat (Administração › Usuários das integrações).',
                                      ['style' => 'padding:6px 4px;color:var(--button-text-color);white-space:normal;width:min(340px, 90vw);']));
        }
        else
        {
            $abas = TElement::tag('div', self::aba('atendimentos', 'Atendimentos', 'far fa-comment-alt', $r['total_atendimentos'], true),
                                  ['class' => 'apchat-abas', 'role' => 'tablist', 'aria-label' => 'Notificações do APChat']);
            $abas->add(self::aba('chat', 'Chat Privado', 'far fa-comments', $r['total_chat'], false));
            $lista->add($abas);

            // como as abas do APChat (10/11/2026): Abertos a esquerda, Pendentes a direita; numero em cima do ticket esperando resposta
            $colunas = new TElement('div');
            $colunas->{'class'} = 'apchat-balao-colunas';
            $colunas->{'style'} = 'display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start;';
            // icones e cores das abas do APChat: Abertos (azul, conversa) e Pendentes (laranja, relogio)
            $colunas->add(self::coluna('Abertos', $r['abertos'], 'Nenhum ticket aberto com você.', 'far fa-comment-alt', '#2563EB'));
            $colunas->add(self::coluna('Pendentes', $r['pendentes'], 'Nenhum ticket pendente.', 'far fa-clock', '#92400e'));
            $lista->add(TElement::tag('div', $colunas, [
                'id' => 'apchat-painel-atendimentos', 'data-painel' => 'atendimentos', 'role' => 'tabpanel',
                'aria-labelledby' => 'apchat-aba-atendimentos', 'tabindex' => '0',
            ]));
            $painelChat = TElement::tag('div', self::chat($r['chat'], $r['chat_situacao']), [
                'id' => 'apchat-painel-chat', 'data-painel' => 'chat', 'role' => 'tabpanel',
                'aria-labelledby' => 'apchat-aba-chat', 'tabindex' => '0',
            ]);
            $painelChat->setProperty('hidden', 'hidden');
            $lista->add($painelChat);
        }

        $this->add($lista);
        TScript::create('BuilderTemplate.processFastDrop();');
    }

    private static function aba(string $id, string $rotulo, string $icone, int $total, bool $ativa): TElement
    {
        $aba = TElement::tag('button', '<i class="' . $icone . '" aria-hidden="true"></i><span>' . $rotulo . '</span>', [
            'type' => 'button', 'id' => 'apchat-aba-' . $id, 'data-aba' => $id, 'role' => 'tab',
            'aria-controls' => 'apchat-painel-' . $id, 'aria-selected' => $ativa ? 'true' : 'false',
            'tabindex' => $ativa ? '0' : '-1', 'onclick' => "CurciolApchatAba(this)",
        ]);
        if ($total > 0)
        {
            $aba->add(TElement::tag('span', $total, ['class' => 'apchat-aba-num', 'aria-label' => $total . ' notificações']));
        }
        return $aba;
    }

    private static function chat(array $itens, string $situacao): TElement
    {
        $cor = '#7C3AED';
        $sec = new TElement('div');
        $sec->{'class'} = 'apchat-balao-chat';
        if ($situacao !== 'ok')
        {
            $sec->add(TElement::tag('div', 'Chat interno indisponível no momento.', ['class' => 'apchat-balao-chat-indisponivel', 'style' => 'padding:6px 4px;color:var(--button-text-color);white-space:normal;']));
            return $sec;
        }
        if (!$itens)
        {
            $sec->add(TElement::tag('div', 'Nenhuma mensagem nova no chat interno.', ['style' => 'padding:6px 4px;color:var(--button-text-color);white-space:normal;']));
        }
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $grade = new TElement('div');
        $grade->{'style'} = 'display:flex;flex-wrap:wrap;gap:6px 18px;';
        foreach ($itens as $item)
        {
            $a = new TElement('a');
            $a->class = 'apchat-balao-conversa';
            $a->href = '#';
            $a->title = 'Abrir a conversa no APChat';
            $a->style = 'flex:1 1 300px;min-width:0;display:flex;gap:10px;align-items:flex-start;padding:8px;border-radius:8px;white-space:normal;color:inherit;border:1px solid rgba(124,58,237,.35);background:rgba(124,58,237,.05);';
            $a->onclick = LinkApp::notificacao('chat', $item['tipo'] . ':' . $item['conversa_id']);
            $icone = $item['tipo'] === 'equipe' ? 'fas fa-users' : 'fas fa-user';
            $a->add('<span style="flex:0 0 36px;width:36px;height:36px;border-radius:50%;background:' . $cor . ';display:flex;align-items:center;justify-content:center;">'
                  . '<i class="' . $icone . '" style="color:#fff;font-size:16px;width:auto;"></i></span>'
                  . '<span style="flex:1;min-width:0;display:block;">'
                  . '<span style="display:flex;align-items:baseline;gap:8px;">'
                  .   '<b style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:14px;color:var(--text-color);">' . $h($item['nome']) . '</b>'
                  .   '<span class="apchat-balao-tempo" style="color:var(--button-text-color);font-size:12px;white-space:nowrap;">' . $h(self::tempo($item['quando'] ?? null)) . '</span>'
                  . '</span>'
                  . '<span style="display:flex;align-items:center;gap:8px;margin-top:2px;">'
                  .   '<span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--button-text-color);font-size:13px;">' . $h($item['ultima']) . '</span>'
                  .   '<span class="apchat-balao-chat-nao-lidas" title="Mensagens não lidas" style="flex:0 0 auto;min-width:20px;height:20px;padding:0 6px;border-radius:10px;background:' . $cor . ';color:#fff;font-size:12px;font-weight:700;line-height:20px;text-align:center;">' . (int) $item['qtd'] . '</span>'
                  . '</span>'
                  . '</span>');
            $grade->add($a);
        }
        $sec->add($grade);
        return $sec;
    }

    private static function coluna(string $titulo, array $itens, string $vazio, string $icone, string $cor): TElement
    {
        $col = new TElement('div');
        $col->{'class'} = 'apchat-balao-coluna';
        $col->{'style'} = 'flex:1 1 300px;min-width:0;';
        $cab = new TElement('div');
        $cab->{'class'} = 'apchat-balao-aba';
        $cab->{'style'} = 'display:flex;align-items:center;gap:8px;font-weight:700;font-size:15px;color:var(--text-color);padding:0 0 6px;margin-bottom:8px;border-bottom:2px solid ' . $cor . ';';
        $cab->add(TElement::tag('i', '', ['class' => $icone, 'style' => 'width:auto;']));
        $cab->add(TElement::tag('span', $titulo));
        $cab->add(TElement::tag('span', (string) count($itens), ['style' => 'background:' . $cor . ';color:#fff;border-radius:10px;padding:0 8px;font-size:12px;line-height:18px;']));
        $col->add($cab);
        if (!$itens)
        {
            $col->add(TElement::tag('div', $vazio, ['style' => 'padding:6px 4px;color:var(--button-text-color);white-space:normal;']));
        }
        foreach ($itens as $item)
        {
            $col->add(self::linha($item));
        }
        return $col;
    }

    /**
     * Card do ticket no padrao da lista de atendimentos do APChat (10/11/2026), sem foto: logo do WhatsApp; nome, numero do ticket e
     * tempo; ultima mensagem (nossa = "Voce:") e numero verde de mensagens sem resposta; fila (azul), atendente e canal.
     */
    private static function linha(array $item): TElement
    {
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $a = new TElement('a');
        $a->class = 'apchat-balao-ticket';
        $a->href = '#';
        $a->title = 'Abrir no APChat';
        $a->style = 'display:flex;gap:10px;align-items:flex-start;padding:8px;margin-bottom:6px;border-radius:8px;white-space:normal;color:inherit;border:1px solid '
                  . ($item['aguardando'] ? 'rgba(37,211,102,.45);background:rgba(37,211,102,.07);' : '#e6e8eb;');
        $a->onclick = LinkApp::notificacao('ticket', (string) $item['ticket_id']);

        $corta = 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
        $miudo = 'color:var(--button-text-color);font-size:12px;white-space:nowrap;';
        $ultima = !empty($item['nossa']) ? 'Você: ' . $item['ultima'] : $item['ultima'];
        $numero = $item['aguardando']
            ? '<span class="apchat-balao-nao-lidas" title="Esperando resposta" style="flex:0 0 auto;min-width:20px;height:20px;padding:0 6px;border-radius:10px;background:#166534;color:#fff;font-size:12px;font-weight:700;line-height:20px;text-align:center;">' . (int) $item['nao_lidas'] . '</span>'
            : '';
        $chips = '';
        if (trim((string) $item['fila']) !== '')
        {
            $chips .= '<span class="apchat-balao-fila" style="background:#3B82F6;color:#fff;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:600;">' . $h($item['fila']) . '</span>';
        }
        if (trim((string) ($item['atendente'] ?? '')) !== '')
        {
            $chips .= '<span class="apchat-balao-atendente" style="font-size:12px;font-weight:600;color:var(--text-color);">' . $h($item['atendente']) . '</span>';
        }
        if (trim((string) $item['canal']) !== '')
        {
            $chips .= '<span class="apchat-balao-canal" style="border:1px solid #d0d5dd;color:var(--button-text-color);border-radius:12px;padding:0 8px;font-size:12px;">' . $h($item['canal']) . '</span>';
        }

        $a->add('<span style="flex:0 0 36px;width:36px;height:36px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;">'
              . '<i class="fab fa-whatsapp" style="color:#fff;font-size:21px;width:auto;"></i></span>'
              . '<span style="flex:1;min-width:0;display:block;">'
              . '<span style="display:flex;align-items:baseline;gap:8px;">'
              .   '<b style="' . $corta . 'font-size:14px;color:var(--text-color);">' . $h($item['contato']) . '</b>'
              .   '<span style="' . $miudo . '">#' . (int) $item['ticket_id'] . '</span>'
              .   '<span class="apchat-balao-tempo" style="' . $miudo . '">' . $h(self::tempo($item['quando'] ?? null)) . '</span>'
              . '</span>'
              . '<span style="display:flex;align-items:center;gap:8px;margin-top:2px;">'
              .   '<span style="' . $corta . 'color:var(--button-text-color);font-size:13px;">' . $h($ultima) . '</span>' . $numero
              . '</span>'
              . ($chips !== '' ? '<span style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px;align-items:center;">' . $chips . '</span>' : '')
              . '</span>');

        return $a;
    }

    /** Tempo desde a ultima mensagem, como no APChat: 5m, 3h; mais de um dia, a data. */
    private static function tempo(?string $quando): string
    {
        $t = $quando ? strtotime($quando) : false;
        if (!$t) { return ''; }
        $seg = max(0, time() - $t);
        if ($seg < 60) { return 'agora'; }
        if ($seg < 3600) { return intdiv($seg, 60) . 'm'; }
        if ($seg < 86400) { return intdiv($seg, 3600) . 'h'; }
        return date('d/m', $t);
    }
}
