<?php
/**
 * Envelope do APMail no topo (10/11/2026), atualizado a cada 30 s pelo BuilderTemplate.js (so com a aba a vista). Termos (API do APMail):
 * - Pendentes = conversas em A Responder + Em Atendimento (o contador do icone);
 * - Nao lidas = MENSAGENS nao lidas pelo usuario (nao conversas): pode ser maior que o "nao lidos" da tela do APMail.
 * Ao passar o mouse, as caixas dele com pendentes e mensagens nao lidas; o clique abre a caixa no APMail,
 * sempre na mesma aba ("apmail"). Dados: ApmailEnvelopeService (cache de 30 s por e-mail).
 */
class MailList extends TElement
{
    public function __construct($param)
    {
        parent::__construct('div');

        try
        {
            TTransaction::open('escritorio');
            $r = ApmailEnvelopeService::caixas((int) TSession::getValue('userid'));
            TTransaction::close();
        }
        catch (Throwable $e)
        {
            TTransaction::rollback();
            $r = ['situacao' => 'indisponivel', 'pendentes' => 0, 'nao_lidas' => 0, 'caixas' => []];
        }

        if ($r['situacao'] === 'desligado')
        {
            return; // notificacoes do APMail desligadas para o usuario
        }

        $this->class = 'fast-drop';
        $total = (int) $r['pendentes'];

        $a = new TElement('a');
        $a->{'class'} = 'button-circle';
        $a->{'href'} = '#';
        // logo do APMail (10/11/2026)
        $a->add(LogoSistemaService::apmail(22));
        $a->add(TElement::tag('div', $total, ['class' => 'ball-notice', 'data-count' => $total]));
        $this->add(TElement::tag('div', $a, ['class' => 'fast-drop-label']));

        $lista = new TElement('div');
        $lista->{'class'} = 'fast-drop-open';
        $cor = LogoSistemaService::COR_APMAIL;
        $caixa = new TElement('div');
        $caixa->style = 'width:min(380px, 90vw);';
        $cab = '<div style="display:flex;align-items:center;gap:8px;font-weight:700;font-size:15px;color:' . $cor . ';padding:0 0 6px;margin-bottom:8px;border-bottom:2px solid ' . $cor . ';">'
             . LogoSistemaService::apmail(18) . '<span>APMail</span>'
             . '<span style="background:' . $cor . ';color:#fff;border-radius:10px;padding:0 8px;font-size:12px;line-height:18px;">' . $total . '</span>'
             . '<span style="margin-left:auto;font-weight:400;font-size:12px;color:#888;">pendentes</span></div>';
        $caixa->add($cab);
        // o que cada numero conta (como a API do APMail informa)
        $caixa->add('<div style="font-size:11px;color:#888;margin:-4px 0 8px;line-height:1.35;white-space:normal;">Pendentes = conversas em A Responder + Em Atendimento.'
                  . '<br>Não lidas = mensagens não lidas (cada mensagem conta, não a conversa).</div>');

        $avisos = [
            'sem_integracao' => 'Integração com o APMail não configurada (Escritório › Configurações › APMail).',
            'sem_email'      => 'Seu usuário não tem e-mail de usuário do APMail (Administração › Usuários das integrações).',
            'nao_liberado'   => 'Seu e-mail não está liberado no APMail para esta integração.',
            'indisponivel'   => 'O APMail não respondeu agora; tentamos de novo em instantes.',
        ];
        if (isset($avisos[$r['situacao']]))
        {
            $caixa->add(TElement::tag('div', $avisos[$r['situacao']], ['style' => 'padding:6px 4px;color:#777;white-space:normal;']));
        }
        elseif (!$r['caixas'])
        {
            $caixa->add(TElement::tag('div', 'Nenhuma caixa de e-mail liberada para você.', ['style' => 'padding:6px 4px;color:#777;white-space:normal;']));
        }
        foreach ($r['caixas'] as $c)
        {
            $caixa->add(self::linha($c));
        }
        $lista->add($caixa);
        $this->add($lista);
        // fica aberto enquanto a classe apmail-aberto estiver no envelope (BuilderTemplate.js tira 400 ms depois que o mouse sai)
        $this->add('<style>#envelope_mail .fast-drop.apmail-aberto > .fast-drop-open{display:block;position:absolute;border:1px solid var(--template-border-color);'
                 . 'padding:14px;border-radius:0 0 6px 6px;background:var(--modal-background);overflow:auto;max-height:70vh;right:0;}</style>');

        TScript::create('BuilderTemplate.processFastDrop();');
    }

    /** Caixa do APMail: nome e e-mail; chips "N pendentes" (destaque) e "N mensagens nao lidas"; zerada fica apagada. */
    private static function linha(array $c): TElement
    {
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $zerada = $c['pendentes'] === 0 && $c['nao_lidas'] === 0;
        $a = new TElement('a');
        $a->class = 'apmail-caixa';
        $a->href = '#';
        $a->style = 'display:flex;gap:10px;align-items:center;padding:8px;margin-bottom:6px;border-radius:8px;white-space:normal;color:inherit;border:1px solid '
                  . ($c['pendentes'] > 0 ? 'rgba(19,121,156,.45);background:rgba(19,121,156,.07);' : '#e6e8eb;') . ($zerada ? 'opacity:.6;' : '');
        $a->onclick = "window.open('" . $h($c['url']) . "', 'apmail'); return false;";
        $chips = '<span style="background:' . ($c['pendentes'] > 0 ? LogoSistemaService::COR_APMAIL : '#c5ccd3') . ';color:#fff;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:600;">'
               . (int) $c['pendentes'] . ' pendente' . ($c['pendentes'] === 1 ? '' : 's') . '</span>'
               . '<span style="border:1px solid #d0d5dd;color:#555;border-radius:12px;padding:0 8px;font-size:12px;">'
               . (int) $c['nao_lidas'] . ($c['nao_lidas'] === 1 ? ' mensagem não lida' : ' mensagens não lidas') . '</span>';
        $a->add('<span style="flex:0 0 36px;width:36px;height:36px;border-radius:50%;background:rgba(19,121,156,.1);display:flex;align-items:center;justify-content:center;">'
              . LogoSistemaService::apmail(20) . '</span>'
              . '<span style="flex:1;min-width:0;display:block;">'
              . '<b style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:14px;color:#222;">' . $h($c['nome']) . '</b>'
              . '<span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#888;font-size:12px;">' . $h($c['email']) . '</span>'
              . '<span style="display:flex;flex-wrap:wrap;gap:6px;margin-top:5px;">' . $chips . '</span>'
              . '</span>');
        return $a;
    }
}
