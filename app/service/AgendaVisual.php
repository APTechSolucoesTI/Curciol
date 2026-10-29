<?php
/**
 * Visual da agenda (29/10/2026), usado por AgendamentoCalendarFormView:
 * - cada horario na cor da agenda: agendamento com fundo claro da cor; compromisso com contorno tracejado; bloqueio
 *   listrado; intervalo e "nao atende" hachurados em cinza (sao da grade, nao de uma agenda);
 * - faixa acima do calendario: as agendas como etiquetas com caixa de selecao (mostra/esconde os horarios dela, so na
 *   tela, guardado no navegador) e "Calendario e legenda", que abre/fecha o mini calendario do mes e a legenda.
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

    /** Faixa com as agendas (etiquetas com caixa de selecao) e o "Calendario e legenda" recolhivel. */
    public static function faixa(array $agendas, array $estados): TElement
    {
        $faixa = new TElement('div');
        $faixa->id = 'agd-faixa';
        $faixa->class = 'agd-faixa';

        $linha = '<div class="agd-linha"><button type="button" class="agd-alternar"><i class="fas fa-chevron-right"></i> <span class="agd-txt-abrir">Calendário e legenda</span><span class="agd-txt-fechar">Recolher calendário e legenda</span></button>';
        foreach ($agendas as $a) {
            $c = self::cores($a->cor);
            $linha .= '<label class="agd-chip" style="--agd-cor:' . $c['cor'] . ';--agd-fundo:' . $c['fundo'] . ';--agd-texto:' . $c['texto'] . '">'
                . '<input type="checkbox" checked data-agenda="' . (int) $a->id . '"> <span class="agd-bolinha"></span>' . htmlspecialchars((string) $a->nome) . '</label>';
        }
        $linha .= '</div>';

        $estadosHtml = '';
        foreach ($estados as $e) {
            $estadosHtml .= '<span class="agd-leg"><span class="agd-bolinha" style="background:' . htmlspecialchars((string) $e->cor) . '"></span>' . htmlspecialchars((string) $e->nome) . '</span>';
        }
        $c = self::cores($agendas ? reset($agendas)->cor : null);
        $exemplo = 'style="--agd-cor:' . $c['cor'] . ';--agd-fundo:' . $c['fundo'] . ';--agd-lista:' . $c['lista'] . '"';
        $painel = '<div class="agd-painel">'
            . '<div class="agd-mes" id="agd-mes"></div>'
            . '<div class="agd-legenda"><b>Estado do agendamento</b>' . $estadosHtml . '</div>'
            . '<div class="agd-legenda"><b>Tipos de horário</b>'
            . '<span class="agd-leg"><span class="agd-amostra agd-am-ag" ' . $exemplo . '></span>Agendamento (cor da agenda)</span>'
            . '<span class="agd-leg"><span class="agd-amostra agd-am-comp" ' . $exemplo . '></span>Compromisso (tracejado)</span>'
            . '<span class="agd-leg"><span class="agd-amostra agd-am-bloq" ' . $exemplo . '></span>Bloqueio (listrado)</span>'
            . '<span class="agd-leg"><span class="agd-amostra agd-am-fundo"></span>Intervalo / não atende</span>'
            . '</div></div>';

        $faixa->add($linha . $painel);
        return $faixa;
    }

    public static function estilo(): string
    {
        return '<style>
            .agd-faixa { padding: 4px 2px 8px; font-size: 12px; }
            .agd-linha { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 8px; }
            .agd-alternar { border: 0; background: transparent; color: #2b6a9b; font-weight: 600; height: 26px; padding: 0 6px; cursor: pointer; }
            .agd-alternar i { transition: transform .15s; font-size: 10px; }
            .agd-faixa .agd-txt-fechar, .agd-faixa.agd-aberta .agd-txt-abrir { display: none; }
            .agd-faixa.agd-aberta .agd-txt-fechar { display: inline; }
            .agd-faixa.agd-aberta .agd-alternar i { transform: rotate(90deg); }
            .agd-chip { display: inline-flex; align-items: center; gap: 6px; height: 26px; margin: 0; padding: 0 9px; border-radius: 5px; cursor: pointer; font-weight: normal;
                        border: 1px solid var(--agd-cor); background: var(--agd-fundo); color: var(--agd-texto); }
            .agd-chip input { margin: 0; width: 13px; height: 13px; }
            .agd-chip.agd-off { border-color: #d0d4da; background: #fff; color: #8694b0; }
            .agd-bolinha { width: 9px; height: 9px; border-radius: 50%; display: inline-block; background: var(--agd-cor); flex-shrink: 0; }
            .agd-painel { display: none; flex-wrap: wrap; gap: 12px 28px; padding: 8px 6px 2px; }
            .agd-faixa.agd-aberta .agd-painel { display: flex; }
            .agd-legenda { display: flex; flex-direction: column; gap: 5px; min-width: 170px; }
            .agd-leg { display: flex; align-items: center; gap: 7px; }
            .agd-amostra { width: 22px; height: 13px; border-radius: 3px; display: inline-block; }
            .agd-am-ag { background: var(--agd-fundo); border: 1px solid var(--agd-cor); }
            .agd-am-comp { background: #fff; border: 1.5px dashed var(--agd-cor); }
            .agd-am-bloq { border: 1px solid var(--agd-cor); background: repeating-linear-gradient(135deg, var(--agd-lista) 0 4px, var(--agd-fundo) 4px 8px); }
            .agd-am-fundo { background: repeating-linear-gradient(135deg, #e3e6ea 0 3px, #f8f9fa 3px 7px); }
            .agd-mes { width: 210px; }
            .agd-mes .agd-mes-cab { display: flex; align-items: center; justify-content: space-between; font-weight: 600; margin-bottom: 4px; }
            .agd-mes .agd-mes-cab button { border: 0; background: transparent; width: 22px; height: 22px; color: #516283; cursor: pointer; }
            .agd-mes .agd-grade { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 1px; text-align: center; font-size: 11px; }
            .agd-mes .agd-grade span { color: #6f7d92; font-weight: 600; padding: 2px 0; }
            .agd-mes .agd-grade button { border: 0; background: transparent; border-radius: 4px; padding: 3px 0; color: #516283; cursor: pointer; font-size: 11px; }
            .agd-mes .agd-grade button.agd-periodo { background: #edf3f9; color: #122f4e; font-weight: 600; }
            .agd-mes .agd-grade button.agd-hoje { background: #528bb7; color: #fff; font-weight: 700; }
            #agendamentos .fc-event.agd-agendamento, #agendamentos .fc-event.agd-compromisso, #agendamentos .fc-event.agd-bloqueio { border-radius: 4px; font-size: 12px; }
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

    /** Mostrar/esconder agendas (no navegador), abrir/fechar a faixa e o mini calendario ligado ao FullCalendar. */
    public static function script(): string
    {
        return <<<'JS'
(function () {
    var ocultas = {}, mesBase = null;
    try { ocultas = JSON.parse(localStorage.getItem('curciol_agenda_ocultas') || '{}') || {}; } catch (e) { ocultas = {}; }
    function cal() { return $('#agendamentos').data('fullcalendar'); }
    function salvar() { try { localStorage.setItem('curciol_agenda_ocultas', JSON.stringify(ocultas)); } catch (e) {} }
    function aplicar() {
        var c = cal(); if (!c) { return; }
        c.getEvents().forEach(function (ev) {
            var a = String(ev.extendedProps.agenda_id || ''); if (!a) { return; }
            var normal = ev.extendedProps.fundo ? 'background' : 'auto';
            var quer = ocultas[a] ? 'none' : normal;
            if (ev.display !== quer) { ev.setProp('display', quer); }
        });
    }
    function iso(d) { return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-' + ('0' + d.getDate()).slice(-2); }
    function mes() {
        var c = cal(), alvo = document.getElementById('agd-mes'); if (!c || !alvo) { return; }
        var base = mesBase || c.getDate(), ano = base.getFullYear(), m = base.getMonth();
        var nomes = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
        var ini = iso(c.view.activeStart), fim = iso(new Date(c.view.activeEnd.getTime() - 1)), hoje = iso(new Date());
        var h = '<div class="agd-mes-cab"><button type="button" data-mes="-1" aria-label="Mês anterior">&#8249;</button>' + nomes[m] + ' ' + ano + '<button type="button" data-mes="1" aria-label="Próximo mês">&#8250;</button></div><div class="agd-grade">';
        ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'].forEach(function (l) { h += '<span>' + l + '</span>'; });
        var primeiro = new Date(ano, m, 1).getDay(), dias = new Date(ano, m + 1, 0).getDate();
        for (var i = 0; i < primeiro; i++) { h += '<i></i>'; }
        for (var d = 1; d <= dias; d++) {
            var s = iso(new Date(ano, m, d)), cls = s === hoje ? 'agd-hoje' : ((s >= ini && s <= fim) ? 'agd-periodo' : '');
            h += '<button type="button" class="' + cls + '" data-dia="' + s + '">' + d + '</button>';
        }
        alvo.innerHTML = h + '</div>';
    }
    $(document).off('.agdvisual');
    $(document).on('change.agdvisual', '#agd-faixa .agd-chip input', function () {
        var a = String($(this).data('agenda'));
        if (this.checked) { delete ocultas[a]; } else { ocultas[a] = true; }
        $(this).closest('.agd-chip').toggleClass('agd-off', !this.checked);
        salvar(); aplicar();
    });
    $(document).on('click.agdvisual', '#agd-faixa .agd-alternar', function () {
        var aberta = !$('#agd-faixa').hasClass('agd-aberta');
        $('#agd-faixa').toggleClass('agd-aberta', aberta);
        try { localStorage.setItem('curciol_agenda_faixa', aberta ? '1' : '0'); } catch (e) {}
        mes();
    });
    $(document).on('click.agdvisual', '#agd-mes [data-mes]', function () {
        var c = cal(), b = mesBase || (c ? c.getDate() : new Date());
        mesBase = new Date(b.getFullYear(), b.getMonth() + parseInt($(this).data('mes'), 10), 1); mes();
    });
    $(document).on('click.agdvisual', '#agd-mes [data-dia]', function () {
        var c = cal(); if (c) { c.changeView('timeGridDay', $(this).data('dia')); }
    });
    $('#agd-faixa .agd-chip input').each(function () {
        var off = !!ocultas[String($(this).data('agenda'))];
        this.checked = !off; $(this).closest('.agd-chip').toggleClass('agd-off', off);
    });
    try { if (localStorage.getItem('curciol_agenda_faixa') === '1') { $('#agd-faixa').addClass('agd-aberta'); } } catch (e) {}
    var tentativas = 0;
    (function quandoPronto() {
        var c = cal();
        if (!c) { if (tentativas++ < 60) { setTimeout(quandoPronto, 100); } return; }
        c.on('eventsSet', aplicar);
        c.on('datesSet', function () { mesBase = null; mes(); });
        aplicar(); mes();
    })();
})();
JS;
    }
}
