<?php
/**
 * Visual da agenda (29/10/2026), usado por AgendamentosFilterForm e AgendamentoCalendarFormView:
 * - cada horario na cor da agenda: agendamento com fundo claro da cor; compromisso com contorno tracejado e bloqueio
 *   listrado, tambem na cor da agenda; intervalo e "nao atende" hachurados em cinza (sao da grade, nao de uma agenda);
 * - legenda (estados e tipos de horario) que abre/fecha pelo botao ao lado do periodo.
 */
class AgendaVisual
{
    /** Cores derivadas da cor da agenda: [cor, fundo (claro), texto (escuro), lista (listras do bloqueio)]. */
    public static function cores(?string $hex): array
    {
        $hex = ltrim(trim((string) $hex), '#');
        if (strlen($hex) === 3) { $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2]; }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) { $hex = '528bb7'; }
        $rgb = array_map('hexdec', str_split($hex, 2));
        $mistura = function (array $alvo, float $peso) use ($rgb) {
            return sprintf('#%02x%02x%02x', ...array_map(function ($c, $a) use ($peso) { return (int) round($c * (1 - $peso) + $a * $peso); }, $rgb, $alvo));
        };
        return [
            'cor'   => '#' . strtolower($hex),
            'fundo' => $mistura([255, 255, 255], 0.85),
            'texto' => $mistura([0, 0, 0], 0.6),
            'lista' => $mistura([255, 255, 255], 0.65),
        ];
    }

    /** Aplica ao evento do FullCalendar o estilo do tipo (agendamento, compromisso, bloqueio) na cor da agenda. */
    public static function estilizar(array $evento, string $tipo, ?string $corAgenda, $agendaId): array
    {
        $c = self::cores($corAgenda);
        $evento['textColor'] = $c['texto'];
        $evento['borderColor'] = $c['cor'];
        $evento['backgroundColor'] = ($tipo === 'compromisso') ? '#ffffff' : $c['fundo'];
        $evento['classNames'] = ['agd-' . $tipo];
        $evento['agenda_id'] = (string) $agendaId;
        unset($evento['color']);
        return $evento;
    }

    /** Legenda (estados e tipos de horario), recolhivel; o botao que abre fica nos filtros (botaoLegenda). */
    public static function legenda(array $estados, ?string $corExemplo): TElement
    {
        $estadosHtml = '';
        foreach ($estados as $e) {
            $estadosHtml .= '<span class="agd-leg"><span class="agd-bolinha" style="background:' . htmlspecialchars((string) $e->cor) . '"></span>' . htmlspecialchars((string) $e->nome) . '</span>';
        }
        $c = self::cores($corExemplo);
        $exemplo = 'style="--agd-cor:' . $c['cor'] . ';--agd-fundo:' . $c['fundo'] . ';--agd-lista:' . $c['lista'] . '"';
        $painel = new TElement('div');
        $painel->id = 'agd-legenda';
        $painel->class = 'agd-painel';
        $painel->add('<div class="agd-legenda"><b>Estado do agendamento</b>' . $estadosHtml . '</div>'
            . '<div class="agd-legenda"><b>Tipos de horário</b>'
            . '<span class="agd-leg"><span class="agd-amostra agd-am-ag" ' . $exemplo . '></span>Agendamento (cor da agenda)</span>'
            . '<span class="agd-leg"><span class="agd-amostra agd-am-comp" ' . $exemplo . '></span>Compromisso (tracejado, cor da agenda)</span>'
            . '<span class="agd-leg"><span class="agd-amostra agd-am-bloq" ' . $exemplo . '></span>Bloqueio (listrado, cor da agenda)</span>'
            . '<span class="agd-leg"><span class="agd-amostra agd-am-fundo"></span>Intervalo / não atende</span>'
            . '</div>');
        return $painel;
    }

    /** Botao que abre/fecha a legenda (fica ao lado do periodo). */
    public static function botaoLegenda(): string
    {
        return '<button type="button" class="agd-alternar" aria-expanded="false"><i class="fas fa-chevron-right"></i> <span class="agd-txt-abrir">Expandir legenda</span><span class="agd-txt-fechar">Recolher legenda</span></button>';
    }


    public static function estilo(): string
    {
        return '<style>
            .agd-filtros { display: flex; flex-wrap: wrap; align-items: flex-start; gap: 8px 14px; width: 100%; }
            .agd-filtro { display: flex; flex-direction: column; gap: 3px; }
            .agd-filtro label { margin: 0; }
            .agd-filtro-largo { width: 290px; max-width: 100%; }
            .agd-filtro-periodo { width: 230px; max-width: 100%; }
            /* largura da legenda aberta: o botao fica no mesmo lugar aberto ou fechado */
            .agd-filtro-legenda { margin-left: auto; align-items: flex-start; width: 470px; max-width: 100%; }
            .agd-filtro-legenda .agd-alternar { padding: 0; }
            .agd-alternar { border: 0; background: transparent; color: #2b6a9b; font-weight: 600; height: 28px; padding: 0 6px; cursor: pointer; white-space: nowrap; }
            .agd-alternar i { transition: transform .15s; font-size: 10px; }
            .agd-alternar .agd-txt-fechar, .agd-alternar.agd-aberta .agd-txt-abrir { display: none; }
            .agd-alternar.agd-aberta .agd-txt-fechar { display: inline; }
            .agd-alternar.agd-aberta i { transform: rotate(90deg); }
            .agd-painel { display: none; flex-wrap: wrap; gap: 8px 24px; padding: 4px 0 0; font-size: 12px; }
            .agd-painel.agd-aberta { display: flex; }
            .agd-legenda { display: flex; flex-direction: column; gap: 5px; min-width: 170px; }
            .agd-leg { display: flex; align-items: center; gap: 7px; }
            .agd-bolinha { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
            .agd-amostra { width: 22px; height: 13px; border-radius: 3px; display: inline-block; }
            .agd-am-ag { background: var(--agd-fundo); border: 1px solid var(--agd-cor); }
            .agd-am-comp { background: #fff; border: 1.5px dashed var(--agd-cor); }
            .agd-am-bloq { border: 1px solid var(--agd-cor); background: repeating-linear-gradient(135deg, var(--agd-lista) 0 4px, var(--agd-fundo) 4px 8px); }
            .agd-am-fundo { background: repeating-linear-gradient(135deg, #e3e6ea 0 3px, #f8f9fa 3px 7px); }
            #agendamentos .fc-event.agd-agendamento, #agendamentos .fc-event.agd-compromisso, #agendamentos .fc-event.agd-bloqueio { border-radius: 4px; font-size: 12px; }
            /* linhas de horario mais altas e o conteudo do bloco preso dentro dele (antes vazava do agendamento curto) */
            #agendamentos .fc-timegrid-slot { height: 30px; }
            #agendamentos .fc-timegrid-event .agd-ev-l2 { line-height: 12px; }
            #agendamentos .fc-timegrid-event { overflow: hidden; }
            #agendamentos .fc-timegrid-event .fc-event-time { float: left; margin: 0 5px 0 0; font-size: 11px; line-height: 15px; }
            #agendamentos .fc-timegrid-event .fc-event-title-container, #agendamentos .fc-timegrid-event .fc-event-title { overflow: hidden; line-height: 15px; }
            #agendamentos .fc-timegrid-event .agd-ev-l1 { display: block; }
            #agendamentos .fc-timegrid-event .agd-ev-est { display: inline-block; vertical-align: middle; margin-right: 4px; }
            #agendamentos .fc-event.agd-compromisso { border-style: dashed; border-width: 1.5px; }
            #agendamentos .fc-event.agd-bloqueio { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.55) 0 7px, rgba(255,255,255,0) 7px 14px); }
            #agendamentos .fc-event.agd-bloqueio .agd-ev-texto { background: rgba(255,255,255,.85); border-radius: 3px; padding: 0 3px; }
            #agendamentos .fc-bg-event.agd-fundo { background: repeating-linear-gradient(135deg, #e3e6ea 0 3px, #f8f9fa 3px 7px); opacity: 1; }
            #agendamentos .fc-bg-event.agd-fundo .fc-event-title { color: #6f7d92; font-size: 11px; font-weight: 600; font-style: normal; }
            #agendamentos .agd-ev { display: flex; flex-direction: column; gap: 1px; line-height: 1.25; }
            #agendamentos .agd-ev-l1 { display: flex; align-items: center; gap: 5px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            #agendamentos .agd-ev-l2 { font-size: 11px; opacity: .85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            #agendamentos .agd-ev-est { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 0 1.5px #fff; }
            #agendamentos .agd-ev-online { font-size: 10px; font-weight: 600; border: 1px solid currentColor; border-radius: 3px; padding: 0 3px; }
        </style>';
    }

    /** Abre/fecha a legenda (lembrado no navegador). */
    public static function script(): string
    {
        return <<<'JS'
(function () {
    function mostrar(aberta) {
        $('.agd-alternar').toggleClass('agd-aberta', aberta).attr('aria-expanded', aberta ? 'true' : 'false');
        $('#agd-legenda').toggleClass('agd-aberta', aberta);
    }
    $(document).off('click.agdlegenda').on('click.agdlegenda', '.agd-alternar', function () {
        var aberta = !$('#agd-legenda').hasClass('agd-aberta');
        mostrar(aberta);
        try { localStorage.setItem('curciol_agenda_legenda', aberta ? '1' : '0'); } catch (e) {}
    });
    try { mostrar(localStorage.getItem('curciol_agenda_legenda') === '1'); } catch (e) {}
})();
JS;
    }
}
