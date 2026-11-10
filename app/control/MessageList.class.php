<?php
/**
 * Balao do APChat no topo (10/11/2026), atualizado a cada verify_messages_menu pelo BuilderTemplate.js: as conversas a responder
 * do atendente ligado ao usuario (ApchatBalaoService). O clique abre o ticket no APChat, sempre na mesma aba ("apchat").
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
        $a->{'title'} = 'Conversas do APChat a responder';
        $a->add(TElement::tag('i', TElement::tag('div', $total, ['class' => 'ball-notice', 'data-count' => $total]), ['class' => 'far fa-comments fa-fw']));
        $this->add(TElement::tag('div', $a, ['class' => 'fast-drop-label']));

        $lista = new TElement('div');
        $lista->{'class'} = 'fast-drop-open';
        $lista->add(TElement::tag('div', 'Conversas do APChat a responder', ['class' => 'header']));

        if ($r['situacao'] === 'sem_ligacao')
        {
            $lista->add(TElement::tag('div', 'Seu usuário não está ligado a um atendente do APChat (Configurações › APChat › Usuários do APChat).',
                                      ['style' => 'padding:10px 14px;color:#777;white-space:normal;']));
        }
        elseif (!$r['itens'])
        {
            $lista->add(TElement::tag('div', 'Nenhuma conversa esperando resposta.', ['style' => 'padding:10px 14px;color:#777;']));
        }

        foreach ($r['itens'] as $item)
        {
            $lista->add(self::linha($item, $item['status'] === 'pending' ? 'far fa-comment-dots' : 'fas fa-headset'));
        }

        if ($r['abertos'])
        {
            $lista->add(TElement::tag('div', 'Tickets abertos com você', ['class' => 'header']));
            foreach ($r['abertos'] as $item)
            {
                $lista->add(self::linha($item, 'far fa-folder-open'));
            }
        }

        $this->add($lista);
        TScript::create('BuilderTemplate.processFastDrop();');
    }

    private static function linha(array $item, string $icone): TElement
    {
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $a = new TElement('a');
        $a->class = 'item-notification';
        $a->href = '#';
        $a->onclick = "window.open('" . $h($item['url']) . "', 'apchat'); return false;";

        $div = new TElement('div');
        $div->{'class'} = 'pull-left';
        $div->add(TElement::tag('i', '', ['class' => $icone . ' fa-2x', 'style' => 'color:#25D366']));
        $a->add($div);

        $onde = implode(' · ', array_filter([$item['canal'], $item['fila']]));
        $quando = $item['quando'] ? date('d/m H:i', strtotime($item['quando'])) : '';
        $h6 = new TElement('h6');
        $h6->add('<b>' . $h($item['contato']) . '</b><br><span style="color:#555">' . $h($item['ultima']) . '</span>');
        $h6->add(TElement::tag('small', [TElement::tag('i', '', ['class' => 'far fa-clock']), ' ' . $h($quando) . ($onde !== '' ? ' · ' . $h($onde) : '')]));
        $corpo = new TElement('div');
        $corpo->add($h6);
        $a->add($corpo);

        return $a;
    }
}
