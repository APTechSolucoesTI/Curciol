<?php
/**
 * Envelope do APMail no topo (10/11/2026), atualizado a cada 10 s pelo BuilderTemplate.js (so com a aba a vista; ao voltar para a aba,
 * na hora e direto no APMail: fresco=1). Termos (API do APMail):
 * - Pendentes = conversas em A Responder + Em Atendimento (o contador do icone);
 * - Nao lidos = E-MAILS nao lidos pelo usuario (cada e-mail conta, nao a conversa): maior que o "nao lidos" da tela do APMail
 *   quando uma conversa tem mais de um e-mail nao lido (conferido em 11/11/2026: 20 conversas, 23 e-mails).
 * Do APMail, sempre "e-mail" (nunca "mensagem", que e do WhatsApp/APChat). Ao passar o mouse, as caixas dele com pendentes e e-mails nao lidos; o clique abre a caixa no APMail,
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
            $r = ApmailEnvelopeService::caixas((int) TSession::getValue('userid'), !empty($param['fresco']));
            $inicio = LinkApp::apmailInicio(ApmailApiService::url(ApmailApiService::escritorio()));
            TTransaction::close();
        }
        catch (Throwable $e)
        {
            TTransaction::rollback();
            $r = ['situacao' => 'indisponivel', 'pendentes' => 0, 'nao_lidas' => 0, 'caixas' => []];
            $inicio = LinkApp::apmailInicio(ApmailApiService::URL_PADRAO);
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
        $a->onclick = $inicio;
        $a->{'aria-label'} = 'Abrir APMail, ' . $total . ' pendentes';
        // logo do APMail (10/11/2026)
        $a->add(LogoSistemaService::apmail(22));
        $a->add(TElement::tag('div', $total, ['class' => 'ball-notice', 'data-count' => $total]));
        $rotulo = TElement::tag('div', $a, ['class' => 'fast-drop-label']);
        $rotulo->add(TElement::tag('button', '<i class="fas fa-chevron-down" aria-hidden="true"></i>', [
            'type' => 'button', 'class' => 'topo-notificacoes-alternar', 'aria-label' => 'Notificações do APMail',
            'aria-expanded' => 'false', 'aria-controls' => 'apmail-notificacoes',
        ]));
        $this->add($rotulo);

        $lista = new TElement('div');
        $lista->{'class'} = 'fast-drop-open topo-notificacoes-painel';
        $lista->id = 'apmail-notificacoes';
        $lista->add(TElement::tag('div', '', ['class' => 'topo-notificacoes-status', 'role' => 'status']));
        $cor = LogoSistemaService::COR_APMAIL;
        $caixa = new TElement('div');
        $cab = '<div style="display:flex;align-items:center;gap:8px;font-weight:700;font-size:15px;color:' . $cor . ';padding:0 0 6px;margin-bottom:8px;border-bottom:2px solid ' . $cor . ';">'
             . LogoSistemaService::apmail(18) . '<span>APMail</span>'
             . '<span style="background:' . $cor . ';color:#fff;border-radius:10px;padding:0 8px;font-size:12px;line-height:18px;">' . $total . '</span>'
             . '<span style="margin-left:auto;font-weight:400;font-size:12px;color:var(--button-text-color);">pendentes</span></div>';
        $caixa->add($cab);
        // o que cada numero conta (como a API do APMail informa)
        $caixa->add('<div style="font-size:11px;color:var(--button-text-color);margin:-4px 0 8px;line-height:1.35;white-space:normal;">Pendentes = conversas de e-mail em A Responder + Em Atendimento.'
                  . '<br>Não lidos = e-mails não lidos (cada e-mail conta: uma conversa com 3 e-mails não lidos conta 3).</div>');

        $avisos = [
            'sem_integracao' => 'Integração com o APMail não configurada (Escritório › Configurações › APMail).',
            'sem_email'      => 'Seu usuário não tem e-mail de usuário do APMail (Administração › Usuários das integrações).',
            'nao_liberado'   => 'Seu e-mail não está liberado no APMail para esta integração.',
            'indisponivel'   => 'O APMail não respondeu agora; tentamos de novo em instantes.',
        ];
        if (isset($avisos[$r['situacao']]))
        {
            $caixa->add(TElement::tag('div', $avisos[$r['situacao']], ['style' => 'padding:6px 4px;color:var(--button-text-color);white-space:normal;']));
        }
        elseif (!$r['caixas'])
        {
            $caixa->add(TElement::tag('div', 'Nenhuma caixa de e-mail liberada para você.', ['style' => 'padding:6px 4px;color:var(--button-text-color);white-space:normal;']));
        }
        foreach ($r['caixas'] as $c)
        {
            $caixa->add(self::linha($c));
        }
        $lista->add($caixa);
        $this->add($lista);

        TScript::create('BuilderTemplate.processFastDrop();');
    }

    /** Caixa do APMail: nome e e-mail; chips "N pendentes" (destaque) e "N e-mails nao lidos"; zerada fica apagada. */
    private static function linha(array $c): TElement
    {
        $h = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $a = new TElement('a');
        $a->class = 'apmail-caixa';
        $a->href = '#';
        $a->style = 'display:flex;gap:10px;align-items:center;padding:8px;margin-bottom:6px;border-radius:8px;white-space:normal;color:inherit;border:1px solid '
                  . ($c['pendentes'] > 0 ? 'rgba(19,121,156,.45);background:rgba(19,121,156,.07);' : '#e6e8eb;');
        $a->onclick = LinkApp::notificacao('caixa', (string) ($c['id'] ?? ''));
        $chips = '<span style="background:' . ($c['pendentes'] > 0 ? LogoSistemaService::COR_APMAIL : '#52637b') . ';color:#fff;border-radius:6px;padding:1px 8px;font-size:12px;font-weight:600;">'
               . (int) $c['pendentes'] . ' pendente' . ($c['pendentes'] === 1 ? '' : 's') . '</span>'
               . '<span style="border:1px solid #d0d5dd;color:var(--button-text-color);border-radius:12px;padding:0 8px;font-size:12px;">'
               . (int) $c['nao_lidas'] . ($c['nao_lidas'] === 1 ? ' e-mail não lido' : ' e-mails não lidos') . '</span>';
        $a->add('<span style="flex:0 0 36px;width:36px;height:36px;border-radius:50%;background:rgba(19,121,156,.1);display:flex;align-items:center;justify-content:center;">'
              . LogoSistemaService::apmail(20) . '</span>'
              . '<span style="flex:1;min-width:0;display:block;">'
              . '<b style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:14px;color:var(--text-color);">' . $h($c['nome']) . '</b>'
              . '<span style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--button-text-color);font-size:12px;">' . $h($c['email']) . '</span>'
              . '<span style="display:flex;flex-wrap:wrap;gap:6px;margin-top:5px;">' . $chips . '</span>'
              . '</span>');
        return $a;
    }
}
