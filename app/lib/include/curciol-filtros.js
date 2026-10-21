/*
 * Enter aplica o filtro nas listagens (antes era preciso clicar em "Aplicar"/"Buscar" com o mouse):
 *  - campo do filtro da coluna (popover com o botao Aplicar): Enter = Aplicar;
 *  - formulario com botao de busca (ex.: filtros da cortina): Enter = Buscar;
 *  - linha de filtros do cabecalho sem popover: Enter tira o foco, o que dispara a busca do campo.
 * Carregado depois do admin-all.min.js.
 */
(function () {
    if (window.__curciolFiltrosEnter) {
        return;
    }
    window.__curciolFiltrosEnter = true;

    // filtro da coluna com lista de sugestoes (autocomplete/combo): escolher ja aplica, sem precisar clicar em Aplicar
    $(document).on('select2:select select2:unselect', '.popover select', function () {
        var aplicar = $(this).closest('.popover').find('.apply-filter').first();
        if (aplicar.length) {
            setTimeout(function () { aplicar.trigger('click'); }, 50);
        }
    });

    $(document).on('keydown', 'input', function (e) {
        if (e.key !== 'Enter' || e.isComposing) {
            return;
        }
        var campo = $(this);
        var tipo = (campo.attr('type') || 'text').toLowerCase();
        if (['button', 'submit', 'checkbox', 'radio', 'file'].indexOf(tipo) !== -1) {
            return;
        }

        // filtro da coluna (popover)
        var aplicar = campo.closest('.popover').find('.apply-filter').first();
        if (aplicar.length) {
            e.preventDefault();
            campo.trigger('change');
            aplicar.trigger('click');
            return;
        }

        // linha de filtros do cabecalho (sem popover)
        if (campo.closest('#datagrid-header-filter-row').length) {
            e.preventDefault();
            campo.trigger('blur');
            return;
        }

        // formulario com botao de busca
        var buscar = campo.closest('form').find('button, a').filter(function () {
            var acao = ($(this).attr('onclick') || '') + ' ' + ($(this).attr('href') || '');
            return /method=onSearch\b/.test(acao);
        }).first();
        if (buscar.length) {
            e.preventDefault();
            campo.trigger('change');
            buscar.trigger('click');
        }
    });
})();
