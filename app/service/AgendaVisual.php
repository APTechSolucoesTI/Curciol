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
            'borda' => $mistura([255, 255, 255], 0.45),
        ];
    }

    /** Camada de cada tipo na mesma agenda: o de numero maior fica por cima (o intervalo, 4, vem do fundo da agenda). */
    const CAMADAS = ['agendamento' => 1, 'compromisso' => 2, 'bloqueio' => 3];

    /**
     * Aplica ao evento do FullCalendar o estilo do tipo (agendamento, compromisso, bloqueio) na cor da agenda e poe o
     * horario real como segunda linha do cartao (o recorte das camadas muda o tamanho do bloco, nao o horario mostrado).
     */
    public static function estilizar(array $evento, string $tipo, ?string $corAgenda, $agendaId): array
    {
        $c = self::cores($corAgenda);
        $evento['textColor'] = $c['texto'];
        $evento['borderColor'] = $c['borda'];
        $evento['backgroundColor'] = ($tipo === 'compromisso') ? '#ffffff' : $c['fundo'];
        $evento['classNames'] = ['agd-' . $tipo];
        $evento['agenda_id'] = (string) $agendaId;
        $evento['camada'] = self::CAMADAS[$tipo] ?? 1;
        $evento['cor_fundo'] = $c['fundo'];
        unset($evento['color']);
        if (!empty($evento['title']) && !empty($evento['start']) && !empty($evento['end'])) {
            $hora = "<span class='agd-ev-hora'>" . self::hora((string) $evento['start'], (string) $evento['end']) . "</span>";
            $p = strpos($evento['title'], "<span class='agd-ev-l2'>");
            if ($p !== false) {
                $evento['title'] = substr($evento['title'], 0, $p) . $hora . substr($evento['title'], $p);
            }
        }
        return $evento;
    }

    /** "14:30 – 15:00" (mesmo dia) ou "27/10 08:00 – 29/10 17:00". */
    public static function hora(string $inicio, string $fim): string
    {
        $i = strtotime(str_replace('T', ' ', $inicio));
        $f = strtotime(str_replace('T', ' ', $fim));
        if (!$i || !$f) { return ''; }
        return date('Y-m-d', $i) === date('Y-m-d', $f) ? date('H:i', $i) . ' – ' . date('H:i', $f) : date('d/m H:i', $i) . ' – ' . date('d/m H:i', $f);
    }

    /** Texto do resumo: "30/10/2026 14:30 às 15:00"; em datas diferentes, a data nas duas pontas. */
    public static function horarioTexto($inicio, $fim): string
    {
        $i = strtotime(str_replace('T', ' ', (string) $inicio));
        $f = strtotime(str_replace('T', ' ', (string) $fim));
        if (!$i) { return ''; }
        if (!$f) { return date('d/m/Y H:i', $i); }
        return date('d/m/Y', $i) === date('d/m/Y', $f)
            ? date('d/m/Y H:i', $i) . ' às ' . date('H:i', $f)
            : date('d/m/Y H:i', $i) . ' às ' . date('d/m/Y H:i', $f);
    }

    /**
     * Cartao com o resumo ao passar o mouse (popover do FullCalendar), como o do agendamento: titulo e os itens
     * preenchidos ("Horario", "Agenda", "Observacao"...). Os textos vao escapados (o popover fica em atributo).
     */
    public static function resumo(string $cartao, string $titulo, array $itens): string
    {
        $e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $conteudo = '<div>';
        foreach ($itens as $rotulo => $valor) {
            if (trim((string) $valor) === '') { continue; }
            $conteudo .= '<b>' . $e($rotulo) . ':</b><br/>' . nl2br($e(trim((string) $valor))) . '<br/>';
        }
        $conteudo .= '</div>';
        return self::comResumo($cartao, $e($titulo), $conteudo);
    }

    /**
     * Cartao + resumo ao passar o mouse (30/10/2026): no lugar do popover do Bootstrap (so era ligado em alguns
     * momentos e se posicionava pelo texto cortado dentro do cartao: aparecia longe ou nao aparecia), o resumo vai
     * em base64 no cartao e o scriptCalendario o mostra junto do cursor, ligado em cada evento desenhado.
     * $titulo e $conteudo sao HTML ja escapado.
     */
    public static function comResumo(string $cartao, string $titulo, string $conteudo): string
    {
        return "<div class='agd-resumo-alvo' data-resumo-titulo='" . base64_encode($titulo) . "' data-resumo='" . base64_encode($conteudo) . "'>{$cartao}</div>";
    }

    /** Bloqueio ou compromisso "de dia inteiro": de varios dias ou com 8 horas ou mais (vai para a faixa Dia inteiro). */
    public static function diaInteiro(array $e): bool
    {
        if (!in_array((int) ($e['camada'] ?? 0), [2, 3], true) || empty($e['start']) || empty($e['end'])) { return false; }
        $i = strtotime(str_replace('T', ' ', (string) $e['start']));
        $f = strtotime(str_replace('T', ' ', (string) $e['end']));
        return date('Y-m-d', $i) !== date('Y-m-d', $f - 1) || ($f - $i) >= 8 * 3600;
    }

    /**
     * Sobreposicao na mesma agenda: intervalo > bloqueio > compromisso > agendamento. O trecho de um horario coberto por
     * outro de camada maior (da mesma agenda) sai do bloco; coberto todo, o bloco nao aparece. O bloqueio/compromisso de
     * dia inteiro nao e cortado aqui (organizar() o divide por dia e corta cada dia), mas cobre os de baixo. Agendas
     * diferentes: ver organizar(). Os eventos sem camada (fundo "Disponivel", "Nao atende") passam como estao.
     */
    public static function camadas(array $eventos): array
    {
        $cobertura = self::cobertura($eventos);
        $saida = [];
        foreach ($eventos as $e) {
            $camada = (int) ($e['camada'] ?? 0);
            if ($camada <= 0 || $camada >= 4 || !isset($e['agenda_id']) || empty($e['start']) || empty($e['end']) || self::diaInteiro($e)) { $saida[] = $e; continue; }
            foreach (self::recortar($e, $cobertura) as $x) { $saida[] = $x; }
        }
        return $saida;
    }

    /** Horarios ocupados por agenda e camada: agenda => camada => [[ini, fim], ...] (timestamps). */
    private static function cobertura(array $eventos): array
    {
        $ts = function ($v) { return strtotime(str_replace('T', ' ', (string) $v)); };
        $cobertura = [];
        foreach ($eventos as $e) {
            if (empty($e['camada']) || !isset($e['agenda_id']) || empty($e['start']) || empty($e['end']) || !empty($e['allDay'])) { continue; }
            $cobertura[(string) $e['agenda_id']][(int) $e['camada']][] = [$ts($e['start']), $ts($e['end'])];
        }
        return $cobertura;
    }

    /** O evento sem os trechos cobertos por camadas maiores da mesma agenda (0, 1 ou mais pedacos). */
    private static function recortar(array $e, array $cobertura): array
    {
        $ts = function ($v) { return strtotime(str_replace('T', ' ', (string) $v)); };
        $camada = (int) ($e['camada'] ?? 0);
        $pedacos = [[$ts($e['start']), $ts($e['end'])]];
        foreach ($cobertura[(string) ($e['agenda_id'] ?? '')] ?? [] as $outra => $intervalos) {
            if ($outra <= $camada) { continue; }
            foreach ($intervalos as [$ci, $cf]) {
                $novos = [];
                foreach ($pedacos as [$pi, $pf]) {
                    if ($cf <= $pi || $ci >= $pf) { $novos[] = [$pi, $pf]; continue; }
                    if ($ci > $pi) { $novos[] = [$pi, $ci]; }
                    if ($cf < $pf) { $novos[] = [$cf, $pf]; }
                }
                $pedacos = $novos;
            }
        }
        $saida = [];
        foreach ($pedacos as [$pi, $pf]) {
            $x = $e;
            $x['start'] = date('Y-m-d\TH:i:s', $pi);
            $x['end'] = date('Y-m-d\TH:i:s', $pf);
            $saida[] = $x;
        }
        return $saida;
    }

    /**
     * Agendas diferentes (30/10/2026): agendamento por cima de compromisso, por cima de bloqueio, por cima de intervalo.
     * Bloqueio e compromisso ficam numa camada de fundo da coluna no dia/semana (nao espremem os cartoes; continuam
     * clicaveis, scriptCalendario), com o compromisso acima do bloqueio; agendamentos por cima de tudo, lado a lado.
     * O de dia inteiro (varios dias ou 8h+): no dia/semana, um bloco por dia, do comeco ao fim daquele dia e cortado pelo
     * intervalo da agenda (so_na_grade); no mes e na aba Agenda, uma barra so de ponta a ponta (so_fora_da_grade).
     * O scriptCalendario esconde em cada visao o que nao e dela. $de/$ate: periodo pedido pelo calendario (so os dias dele).
     */
    public static function organizar(array $eventos, ?string $de = null, ?string $ate = null): array
    {
        $cobertura = self::cobertura($eventos);
        $saida = [];
        foreach ($eventos as $e) {
            $camada = (int) ($e['camada'] ?? 0);
            if (!in_array($camada, [2, 3], true)) { $saida[] = $e; continue; }
            if (!self::diaInteiro($e)) {
                $e['display'] = 'background';
                $e['fundo_na_grade'] = true; // no mes e na lista vira item normal (scriptCalendario)
                $e['classNames'][] = 'agd-de-fundo';
                $saida[] = $e;
                continue;
            }
            $i = strtotime(str_replace('T', ' ', (string) $e['start']));
            $f = strtotime(str_replace('T', ' ', (string) $e['end']));

            $barra = $e;
            $barra['start'] = date('Y-m-d', $i);
            $barra['end'] = date('Y-m-d', strtotime(date('Y-m-d', $f - 1) . ' +1 day'));
            $barra['allDay'] = true;
            $barra['so_fora_da_grade'] = true;
            $barra['classNames'][] = 'agd-dia-inteiro';
            if ($camada === 2) { $barra['backgroundColor'] = $e['cor_fundo'] ?? '#ffffff'; }
            $saida[] = $barra;

            $primeiro = strtotime(date('Y-m-d', $i));
            if ($de && strtotime(substr($de, 0, 10)) > $primeiro) { $primeiro = strtotime(substr($de, 0, 10)); }
            $limite = $ate ? min($f, strtotime(substr($ate, 0, 10) . ' +1 day')) : $f;
            for ($dia = $primeiro; $dia < $limite; $dia = strtotime(date('Y-m-d', $dia) . ' +1 day')) {
                $amanha = strtotime(date('Y-m-d', $dia) . ' +1 day');
                $pedaco = $e;
                $pedaco['start'] = date('Y-m-d\TH:i:s', max($i, $dia));
                $pedaco['end'] = date('Y-m-d\TH:i:s', min($f, $amanha));
                foreach (self::recortar($pedaco, $cobertura) as $x) {
                    $x['display'] = 'background';
                    $x['so_na_grade'] = true;
                    $x['classNames'][] = 'agd-de-fundo';
                    $saida[] = $x;
                }
            }
        }
        return $saida;
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
            /* grade (30/10/2026): linhas cinza-claras entre os dias e nas horas (meia hora tracejada); hoje em azul claro */
            #agendamentos { --fc-border-color: #e3e7ec; --fc-today-bg-color: #eef5fc; --fc-now-indicator-color: #dd5a43; --fc-neutral-bg-color: #fafbfc; }
            #agendamentos td, #agendamentos th, #agendamentos .fc-scrollgrid { border-color: #e3e7ec; }
            #agendamentos .fc-timegrid-slot { height: 30px; }
            #agendamentos .fc-timegrid-slot-minor { border-top-style: dashed; border-top-color: #eef0f3; }
            #agendamentos .fc-timegrid-slot-label-cushion, #agendamentos .fc-timegrid-axis-cushion { font-size: 11px; color: #6f7d92; }
            #agendamentos .fc-col-header-cell { text-align: left; background: #fafbfc; }
            #agendamentos .fc-col-header-cell-cushion { display: inline-block; margin: 4px 6px; padding: 2px 4px; font-size: 12px; font-weight: 600; color: #516283; }
            #agendamentos .fc-col-header-cell.fc-day-today .fc-col-header-cell-cushion { background: #528bb7; color: #fff; border-radius: 11px; padding: 2px 9px; }
            #agendamentos .fc-daygrid-day.fc-day-today .fc-daygrid-day-number { background: #528bb7; color: #fff; border-radius: 11px; padding: 1px 7px; margin: 3px; }
            #agendamentos .fc-mesHoje-view .fc-daygrid-day-frame { min-height: 105px; }

            /* cartoes: respiro entre eles e ate a linha do dia, cantos arredondados, sombra ao passar o mouse */
            #agendamentos .fc-timegrid-col-events { margin: 0 4px 0 2px; }
            #agendamentos .fc-timegrid-event-harness > .fc-timegrid-event { top: 1px; bottom: 1px; right: 3px; }
            #agendamentos .fc-timegrid-event, #agendamentos .fc-daygrid-event { border-radius: 6px; font-size: 12px; overflow: hidden; transition: box-shadow .15s, filter .15s; }
            #agendamentos .fc-timegrid-event:hover, #agendamentos .fc-daygrid-event:hover { filter: brightness(.97); box-shadow: 0 2px 6px rgba(18,47,78,.18); }
            #agendamentos .fc-timegrid-event .fc-event-main { padding: 3px 6px; }
            /* nome (cortado), horario de inicio e fim sempre, depois o detalhe: o horario do FullCalendar some no dia/semana */
            #agendamentos .fc-timegrid-event .fc-event-time, #agendamentos .fc-bg-event .fc-event-time { display: none; }
            /* resumo ao passar o mouse (scriptCalendario): junto do cursor, por cima de tudo */
            #agendamentos .agd-resumo-alvo { display: block; }
            #agd-resumo { display: none; position: fixed; z-index: 3000; max-width: 320px; background: #fff; border: 1px solid #d0d4da; border-radius: 6px; box-shadow: 0 6px 18px rgba(18,47,78,.2); font-size: 12px; color: #122f4e; pointer-events: none; }
            #agd-resumo .agd-resumo-titulo { background: #f3f5f6; border-bottom: 1px solid #e3e7ec; border-radius: 6px 6px 0 0; padding: 6px 10px; font-weight: 600; }
            #agd-resumo .agd-resumo-corpo { padding: 8px 10px; line-height: 1.45; }
            #agendamentos .agd-ev { display: flex; flex-direction: column; gap: 1px; line-height: 15px; overflow: hidden; }
            #agendamentos .agd-ev-l1 { display: block; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            #agendamentos .agd-ev-hora { display: block; font-size: 11px; opacity: .8; white-space: nowrap; }
            #agendamentos .agd-ev-l2 { display: block; font-size: 11px; opacity: .85; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            #agendamentos .fc-timeGridDay-view .fc-timegrid-event .agd-ev-l2 { white-space: normal; }
            #agendamentos .agd-ev-est { width: 7px; height: 7px; border-radius: 50%; display: inline-block; vertical-align: middle; margin: -2px 4px 0 0; box-shadow: 0 0 0 1.5px #fff; }
            #agendamentos .agd-ev-online { font-size: 10px; font-weight: 600; border: 1px solid currentColor; border-radius: 3px; padding: 0 3px; margin-left: 4px; }

            /* mes e faixa "Dia inteiro": uma linha (nome cortado e horario); no mes fica o horario de inicio do FullCalendar */
            #agendamentos .fc-daygrid-event .fc-event-main { display: flex; align-items: center; gap: 4px; padding: 1px 6px; min-width: 0; }
            #agendamentos .fc-daygrid-event .fc-event-title-container { flex: 1 1 auto; min-width: 0; order: 1; }
            #agendamentos .fc-daygrid-event .fc-event-time { order: 2; flex-shrink: 0; font-size: 11px; opacity: .75; }
            #agendamentos .fc-daygrid-event .agd-ev { flex-direction: row; align-items: center; gap: 6px; }
            #agendamentos .fc-daygrid-event .agd-ev-l2 { display: none; }
            #agendamentos .fc-mesHoje-view .agd-ev-hora, #agendamentos .fc-list .agd-ev-hora { display: none; }
            /* aba Agenda (lista): a linha inteira na cor da agenda (scriptCalendario), sem a bolinha */
            #agendamentos .fc-list-event-dot, #agendamentos .fc-list-event-graphic { display: none; }
            #agendamentos .fc-list-event td { background: transparent; border-color: rgba(255,255,255,.7); }
            #agendamentos .fc-list-event:hover { filter: brightness(.96); }
            #agendamentos .fc-list-event .agd-ev { flex-direction: row; gap: 8px; align-items: baseline; }
            #agendamentos .fc-list-event .agd-ev-l2 { display: inline; }

            /* bloqueio listrado e compromisso tracejado, na cor da agenda */
            #agendamentos .fc-event.agd-bloqueio, #agendamentos .fc-bg-event.agd-bloqueio { background-image: repeating-linear-gradient(135deg, rgba(255,255,255,.55) 0 7px, rgba(255,255,255,0) 7px 14px); }
            #agendamentos .fc-event.agd-compromisso { border-style: dashed; border-width: 1.5px; }
            #agendamentos .agd-ev-texto { background: rgba(255,255,255,.85); border-radius: 3px; padding: 0 3px; }

            /* agendas diferentes: bloqueio e compromisso curtos ficam no fundo da coluna, por baixo dos agendamentos
               (compromisso acima do bloqueio, acima do intervalo); continuam clicaveis */
            #agendamentos .fc-bg-event.agd-de-fundo { opacity: 1; border-radius: 4px; cursor: pointer; color: #334155; }
            #agendamentos .fc-bg-event.agd-compromisso { outline: 1.5px dashed rgba(18,47,78,.35); outline-offset: -2px; }
            #agendamentos .fc-bg-event.agd-de-fundo .agd-ev { padding: 3px 6px; }
            #agendamentos .fc-timegrid-bg-harness:has(> .agd-fundo) { z-index: 1; }
            #agendamentos .fc-timegrid-bg-harness:has(> .agd-bloqueio) { z-index: 2; }
            #agendamentos .fc-timegrid-bg-harness:has(> .agd-compromisso) { z-index: 3; }
            #agendamentos .fc-bg-event.agd-fundo { background: repeating-linear-gradient(135deg, #e3e6ea 0 3px, #f8f9fa 3px 7px); opacity: 1; }
            #agendamentos .fc-bg-event.agd-fundo .fc-event-title { color: #6f7d92; font-size: 11px; font-weight: 600; font-style: normal; }
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

    /**
     * Bloqueio e compromisso que ficam no fundo da coluna (organizar) nao recebem o clique do FullCalendar: o clique
     * neles abre o cadastro, como nos demais (AgendamentoCalendarFormView::onClick).
     */
    public static function scriptCalendario(): string
    {
        return <<<'JS'
(function () {
    var tentativas = 0;
    (function quandoPronto() {
        var cal = $('#agendamentos').data('fullcalendar');
        if (!cal) { if (tentativas++ < 60) { setTimeout(quandoPronto, 100); } return; }
        function naGrade() { return !!(cal.view && String(cal.view.type).indexOf('timeGrid') === 0); }
        var gradeAntes = naGrade();
        cal.setOption('eventDataTransform', function (e) {
            if (e.so_na_grade) { if (!naGrade()) { e.display = 'none'; } return e; }
            if (e.so_fora_da_grade) { if (naGrade()) { e.display = 'none'; } return e; }
            if (e.fundo_na_grade && !naGrade()) { delete e.display; }
            return e;
        });
        cal.on('datesSet', function () {
            if (naGrade() !== gradeAntes) { gradeAntes = naGrade(); cal.refetchEvents(); }
        });
        var dica = document.getElementById('agd-resumo');
        if (!dica) { dica = document.createElement('div'); dica.id = 'agd-resumo'; document.body.appendChild(dica); }
        function decod(b) { try { return decodeURIComponent(escape(atob(b || ''))); } catch (e) { return ''; } }
        function posicionar(ev) {
            var m = 14, w = dica.offsetWidth, h = dica.offsetHeight, x = ev.clientX + m, y = ev.clientY + m;
            if (x + w > window.innerWidth - 8) { x = ev.clientX - w - m; }
            if (y + h > window.innerHeight - 8) { y = ev.clientY - h - m; }
            dica.style.left = Math.max(8, x) + 'px';
            dica.style.top = Math.max(8, y) + 'px';
        }
        function esconder() { dica.style.display = 'none'; }
        $(window).off('scroll.agdresumo').on('scroll.agdresumo', esconder);
        cal.on('datesSet', esconder);
        cal.setOption('eventWillUnmount', esconder);
        cal.setOption('eventDidMount', function (info) {
            var alvo = info.el.querySelector('.agd-resumo-alvo');
            if (alvo) {
                var titulo = decod(alvo.getAttribute('data-resumo-titulo')), conteudo = decod(alvo.getAttribute('data-resumo'));
                info.el.addEventListener('mouseenter', function (ev) {
                    dica.innerHTML = (titulo ? '<div class="agd-resumo-titulo">' + titulo + '</div>' : '') + '<div class="agd-resumo-corpo">' + conteudo + '</div>';
                    dica.style.display = 'block';
                    posicionar(ev);
                });
                info.el.addEventListener('mousemove', posicionar);
                info.el.addEventListener('mouseleave', esconder);
            }
            if (info.el.classList.contains('fc-list-event')) {
                info.el.style.backgroundColor = info.event.backgroundColor || '';
                info.el.style.color = info.event.textColor || '';
                return;
            }
            if (info.event.display !== 'background' || !/^(bloqueio|compromisso)-\d+$/.test(String(info.event.id))) { return; }
            info.el.addEventListener('click', function (e) {
                e.stopPropagation();
                __adianti_load_page('index.php?class=AgendamentoCalendarFormView&method=onClick&static=1&key=' + encodeURIComponent(info.event.id) + '&id=' + encodeURIComponent(info.event.id));
            });
        });
        if (cal.getEvents().length) { cal.refetchEvents(); }
    })();
})();
JS;
    }
}
