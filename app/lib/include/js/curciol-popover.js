/* ==========================================================================
   Curciol - popover de linha (o balao que aparece ao passar o mouse)
   --------------------------------------------------------------------------
   Por que este arquivo existe.

   O framework liga um popover Bootstrap em cada <tr> das listagens, com
   trigger "hover", o balao montado no <body> e atraso de 10ms. Nessas
   listagens a linha ocupa a largura toda e o balao e grande: medido na
   listagem de andamentos, 776x369px cobrindo SEIS linhas do proprio grid.

   Dai saiam os dois defeitos relatados, que tem a mesma raiz:

   1. Piscar. O balao aceita o ponteiro. Ao mover o mouse em direcao ao texto,
      o ponteiro entra no balao, a linha de origem recebe mouseleave, o balao
      some, o ponteiro cai na linha que estava escondida embaixo, essa linha
      abre o seu balao, que de novo cai sob o ponteiro. Medido na tela de
      andamentos, a sequencia de eventos era:

          -3 +4 -4 +5 -5 +6 -6 +7 -7 +8 -8 +9 -9 +9 -9 +9 -9 +9 -9 +9 -9 +9

      a linha 9 abrindo e fechando seis vezes com o mouse praticamente parado.

   2. Nao aparecer nada. Com 10ms de atraso, atravessar a lista dispara um
      show/hide por linha - 7 baloes ao passar por 8 linhas. Os pares correm
      juntos e se cruzam (o hidden de um chega depois do shown do seguinte),
      o estado interno do Bootstrap fica invertido e a linha para de responder
      ao hover.

   A correcao ataca a causa, nao os sintomas:

   - pointer-events:none nos baloes de hover, pelo CSS. O balao deixa de
     roubar o ponteiro, entao a linha sob o cursor e sempre a linha real e o
     vai-e-volta nao tem como comecar. Quem tem trigger de clique (BHelper)
     continua clicavel, porque so o template de hover leva a classe.
   - atraso de abertura de 200ms, para o mouse de passagem nao disparar
     balao em cada linha do caminho.

   Sobre o valor do atraso: ele foi 350ms na primeira versao e isso quebrou o
   uso normal - descendo a lista em ritmo de leitura, abria um balao so, e
   parecia que o hover so funcionava na primeira linha. Medido nesta tela,
   descendo dez linhas:

       atraso   em 4s   em 2s   em 0,8s   ping-pong
        150ms      10      10         5   nao
        200ms      10       9         2   nao
        250ms      10       7         1   nao
        350ms       9       1         1   nao

   200ms responde em ritmo de leitura e ainda corta a passagem rapida de dez
   baloes para dois. E repare na ultima coluna: o ping-pong nao volta em
   nenhum atraso, porque quem o resolve e o pointer-events, nao o atraso. Por
   isso da para deixar o atraso curto sem trazer o defeito de volta.

   Duas coisas foram testadas e deixadas como estavam, para o arquivo nao
   crescer com conserto de problema que nao existe:

   - Rolagem. O Popper reposiciona o balao junto da linha (9px de folga).
     Chegamos a fecha-lo ao rolar e so piorou: sumia ao menor toque na roda.
   - Balao orfao quando a lista e trocada sob o cursor. So acontece com
     innerHTML nativo, e nem o framework nem o app usam isso em lugar nenhum
     (zero ocorrencias); pelo caminho real, via jQuery, nao sobra balao.

   Isto substitui __adianti_process_popover do framework em vez de edita-lo:
   o arquivo do framework e minificado e volta a cada atualizacao do Adianti.
   Este arquivo carrega depois (LIBRARIES_USER vem depois de LIBRARIES), entao
   a funcao daqui e a que vale. Vale para TODAS as listagens, nao so a de
   andamentos - o defeito era do framework, nao da tela.
   ========================================================================== */
(function () {
    'use strict';

    if (typeof window.jQuery === 'undefined' || typeof jQuery.fn.popover !== 'function') {
        return;
    }

    var $ = window.jQuery;

    var ATRASO_ABRIR  = 200;
    var ATRASO_FECHAR = 80;

    var MODELO_HOVER =
        '<div class="popover curciol-popover-hover" role="tooltip" style="max-width:800px">'
        + '<div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';

    var MODELO_CLIQUE =
        '<div class="popover" role="tooltip" style="max-width:800px">'
        + '<div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>';

    var LADOS = ['auto', 'top', 'right', 'bottom', 'left'];

    function lado(tip, elemento) {
        var valor = $(elemento).attr('popside');
        return (typeof valor === 'undefined' || LADOS.indexOf(valor) === -1) ? 'auto' : valor;
    }

    /* Mesma leitura do framework, inclusive o popaction, para nao mudar o que
       ja funciona em outras telas. */
    function conteudo() {
        var elemento = $(this);

        if (typeof elemento.attr('popaction') === 'undefined') {
            if (typeof elemento.attr('popcontent64') !== 'undefined') {
                return base64_decode(elemento.attr('popcontent64'));
            }
            return elemento.attr('popcontent') || '';
        }

        __adianti_get_page(elemento.attr('popaction'), function (resultado) {
            var popover = elemento.attr('data-content', resultado).data('bs.popover');
            if (popover) {
                popover.setContent();
                popover.show();
            }
        }, { 'static': '0' });

        return '<i class="fa fa-spinner fa-spin fa-5x fa-fw"></i>';
    }

    function titulo() {
        return $(this).attr('poptitle') || '';
    }

    window.__adianti_process_popover = function () {
        $('[popover="true"]').removeAttr('popover').attr('data-popover', 'true');

        $('[data-popover="true"]:not([poptrigger]):not([data-popover-processed="true"])').popover({
            placement: lado,
            trigger: 'hover',
            container: 'body',
            template: MODELO_HOVER,
            delay: { show: ATRASO_ABRIR, hide: ATRASO_FECHAR },
            content: conteudo,
            html: true,
            title: titulo,
            sanitizeFn: function (d) { return d; }
        }).attr('data-popover-processed', true);

        $('[data-popover="true"][poptrigger="click"]:not([data-popover-processed="true"])').popover({
            placement: lado,
            trigger: 'click',
            container: 'body',
            template: MODELO_CLIQUE,
            delay: { show: 10, hide: 10 },
            content: conteudo,
            sanitizeFn: function (d) { return d; },
            html: true,
            title: titulo
        }).on('shown.bs.popover', function () {
            var elemento = $(this);
            if (typeof elemento.attr('popaction') !== 'undefined') {
                __adianti_get_page(elemento.attr('popaction'), function (resultado) {
                    var popover = elemento.attr('data-content', resultado).data('bs.popover');
                    if (popover) {
                        popover.setContent();
                    }
                }, { 'static': '0' });
            }
        }).attr('data-popover-processed', true);
    };
})();
