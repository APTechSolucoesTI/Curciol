/*
 * Cortina lateral (#adianti_right_panel) aberta enquanto ha uma janela (TWindow) na tela.
 *
 * A cortina tem z-index menor que as janelas e abria por tras delas. Padrao do sistema:
 * ao abrir uma cortina, as janelas visiveis (e o fundo escuro delas) saem de cena - nao
 * sao fechadas; quando a ultima cortina fecha, elas voltam exatamente como estavam.
 * Janelas abertas a partir da propria cortina aparecem normalmente.
 *
 * Carregado depois do admin-all.min.js (que define o objeto Template).
 */
(function () {
    if (typeof Template === 'undefined' || Template.__cortinaSobreJanela) {
        return;
    }
    Template.__cortinaSobreJanela = true;

    var MARCA = 'curciol-oculta-pela-cortina';

    function vaiParaCortina(url, data) {
        url = String(url || '');
        data = String(data || '');
        return url.indexOf('target_container=adianti_right_panel') !== -1
            || data.indexOf('adianti_target_container="adianti_right_panel"') !== -1;
    }

    function cortinaAberta() {
        return $('#adianti_right_panel').is(':visible') && $('#adianti_right_panel > [page_name]').length > 0;
    }

    // janelas que ja existiam antes de cada navegacao: a URL de uma janela aberta de dentro da cortina tambem
    // carrega target_container=adianti_right_panel (o Adianti repete o parametro), e ela nao pode ser escondida
    var anteriores = null;
    function anotarJanelas() {
        anteriores = $('.ui-dialog, .ui-widget-overlay').toArray();
    }
    ['onBeforeLoad', 'onBeforePost'].forEach(function (gancho) {
        var original = Template[gancho];
        Template[gancho] = function () {
            anotarJanelas();
            return (typeof original === 'function') ? original.apply(this, arguments) : undefined;
        };
    });

    function esconderJanelas() {
        var lista = anteriores ? $(anteriores) : $('.ui-dialog, .ui-widget-overlay');
        lista.filter(':visible').not('#adianti_right_panel *').addClass(MARCA).hide();
    }

    function abriuJanelaNova() {
        return anteriores !== null && $('.ui-dialog').not(anteriores).length > 0;
    }

    function devolverJanelas() {
        $('.' + MARCA).removeClass(MARCA).show();
    }

    var depoisDeCarregar = Template.onAfterLoad;
    Template.onAfterLoad = function (url, data) {
        var resultado = depoisDeCarregar.apply(this, arguments);
        if (vaiParaCortina(url, data) && !abriuJanelaNova()) {
            esconderJanelas();
        } else if (!cortinaAberta()) {
            // a cortina foi fechada por outra navegacao: as janelas voltam
            devolverJanelas();
        }
        return resultado;
    };
    if (Template.onAfterPost === depoisDeCarregar) {
        Template.onAfterPost = Template.onAfterLoad;
    }

    var fecharCortina = Template.closeRightPanel;
    Template.closeRightPanel = function () {
        var ultima = $('#adianti_right_panel > [page_name]').length <= 1;
        var resultado = fecharCortina.apply(this, arguments);
        if (ultima) {
            devolverJanelas();
        }
        return resultado;
    };
})();
