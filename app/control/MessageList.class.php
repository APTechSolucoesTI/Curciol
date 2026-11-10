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

        if ($r['situacao'] === 'sem_ligacao')
        {
            $lista->add(TElement::tag('div', 'Seu usuário não está ligado a um atendente do APChat (Configurações › APChat › Usuários do APChat).',
                                      ['style' => 'padding:6px 4px;color:#777;white-space:normal;width:min(340px, 90vw);']));
        }
        else
        {
            // lado a lado (10/11/2026): "A responder" e, se o usuario ligou, "Tickets Abertos" (todos os abertos com ele, respondidos ou nao)
            $duas = !empty($r['mostrar_abertos']);
            $colunas = new TElement('div');
            $colunas->{'class'} = 'apchat-balao-colunas';
            $colunas->{'style'} = 'display:flex;flex-wrap:wrap;gap:18px;align-items:flex-start;width:' . ($duas ? 'min(760px, 90vw)' : 'min(370px, 90vw)') . ';';
            $colunas->add(self::coluna('A responder', $total, $r['itens'], 'Nenhuma conversa esperando resposta.', function ($item) {
                return $item['status'] === 'pending' ? 'far fa-comment-dots' : 'fas fa-headset';
            }));
            if ($duas)
            {
                $colunas->add(self::coluna('Tickets Abertos', count($r['abertos']), $r['abertos'], 'Nenhum ticket aberto com você.', function ($item) {
                    return 'far fa-folder-open';
                }));
            }
            $lista->add($colunas);
        }

        $this->add($lista);
        TScript::create('BuilderTemplate.processFastDrop();');
    }

    private static function coluna(string $titulo, int $quantos, array $itens, string $vazio, callable $icone): TElement
    {
        $col = new TElement('div');
        $col->{'class'} = 'apchat-balao-coluna';
        $col->{'style'} = 'flex:1 1 300px;min-width:0;';
        $cab = new TElement('div');
        $cab->{'style'} = 'display:flex;align-items:center;gap:8px;font-weight:700;font-size:15px;padding:0 0 6px;margin-bottom:8px;border-bottom:2px solid #25D366;';
        $cab->add(TElement::tag('span', $titulo));
        $cab->add(TElement::tag('span', (string) $quantos, ['style' => 'background:#25D366;color:#fff;border-radius:10px;padding:0 8px;font-size:12px;line-height:18px;']));
        $col->add($cab);
        if (!$itens)
        {
            $col->add(TElement::tag('div', $vazio, ['style' => 'padding:6px 4px;color:#777;white-space:normal;']));
        }
        foreach ($itens as $item)
        {
            $col->add(self::linha($item, $icone($item)));
        }
        return $col;
    }

    private static function linha(array $item, string $icone): TElement
    {
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $a = new TElement('a');
        $a->class = 'item-notification';
        $a->href = '#';
        $a->style = 'white-space:normal;';
        $a->onclick = "window.open('" . $h($item['url']) . "', 'apchat'); return false;";

        $div = new TElement('div');
        $div->{'class'} = 'pull-left';
        $div->add(TElement::tag('i', '', ['class' => $icone . ' fa-2x', 'style' => 'color:#25D366']));
        $a->add($div);

        $onde = implode(' · ', array_filter([$item['canal'], $item['fila']]));
        $quando = $item['quando'] ? date('d/m H:i', strtotime($item['quando'])) : '';
        $h6 = new TElement('h6');
        // ultima mensagem nossa (ja respondida): "Voce:" em cinza; do cliente: em destaque
        $ultima = !empty($item['nossa'])
            ? '<span style="color:#999">Você: ' . $h($item['ultima']) . '</span>'
            : '<span style="color:#333">' . $h($item['ultima']) . '</span>';
        $h6->add('<b>' . $h($item['contato']) . '</b><br>' . $ultima);
        $h6->add(TElement::tag('small', [TElement::tag('i', '', ['class' => 'far fa-clock']), ' ' . $h($quando) . ($onde !== '' ? ' · ' . $h($onde) : '')]));
        $corpo = new TElement('div');
        $corpo->add($h6);
        $a->add($corpo);

        return $a;
    }
}
