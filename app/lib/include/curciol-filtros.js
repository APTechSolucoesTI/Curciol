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

/*
 * Faixa "Filtros aplicados" no topo das listagens (22/10/2026): mostra TODOS os filtros ativos, de qualquer origem,
 *  - funil no cabecalho das colunas (lidos da tela; celula com varios campos aparece inteira);
 *  - cortina (botao Filtros) e botoes rapidos (vem do servidor: FiltroLista::faixa -> curciolFaixaFiltros).
 * O "x" de cada filtro tira so ele; os outros continuam valendo (filtros que se somam, FiltroLista::acumular).
 * Substitui a tdatagrid_update_filter_badges do framework, que so via o cabecalho.
 */
(function () {
    window.curciolFiltrosServidor = window.curciolFiltrosServidor || {};

    var esc = function (s) { return $('<div>').text(s == null ? '' : String(s)).html(); };

    var textoCampo = function ($campo) {
        if ($campo.is('select')) {
            return $campo.find('option:selected').map(function () { return $(this).text(); }).get()
                .filter(function (t) { return $.trim(t) !== ''; }).join(', ');
        }
        var v = $campo.val();
        return (v == null) ? '' : $.trim(String(v));
    };

    var badge = function (rotulo, valor, remover) {
        var $b = $('<div class="curciol-filtro-ativo" style="border: 1px solid var(--button-border, #ccc); border-radius: 4px; padding: 4px 8px; display: flex; align-items: center; gap: 4px; font-size: 12px;">'
            + '<span>' + esc(rotulo) + ': <b>' + esc(valor) + '</b></span>'
            + '<span title="Remover filtro" class="btn btn-sm btn-link" style="padding: 0 2px; font-size: 13px; line-height: 1; background: none; border: none; box-shadow: none; min-height: 15px; height: 15px; color: var(--button-text);">&times;</span></div>');
        $b.find('.btn-link').on('click', remover);
        return $b;
    };

    window.curciolFaixaFiltros = function (datagridId, itens, urlRemover) {
        window.curciolFiltrosServidor[datagridId] = { itens: itens || [], url: urlRemover };
        setTimeout(function () { tdatagrid_update_filter_badges(datagridId); }, 20);
        setTimeout(function () { tdatagrid_update_filter_badges(datagridId); }, 400);
    };

    window.tdatagrid_update_filter_badges = function (datagridId) {
        var $grid = $('#' + datagridId);
        if (!$grid.length) { return; }
        $grid.prev('.filter-badges-container').remove();

        var $container = $('<div class="filter-badges-container" style="margin-bottom: 10px;">'
            + '<div style="font-size: 12px; color: #666; display: flex; align-items: center; gap: 5px;"><i class="fas fa-filter"></i><span>Filtros aplicados:</span></div>'
            + '<div class="curciol-filtros-lista" style="display: flex; flex-wrap: wrap; gap: 3px; margin-top: 2px;"></div></div>');
        var $lista = $container.find('.curciol-filtros-lista');

        // funil do cabecalho: cada celula com valor vira um filtro (todos os campos da celula juntos)
        $grid.find('#datagrid-header-filter-row td').each(function (indice) {
            var $celula = $(this);
            var $campos = $celula.find('input, select').filter(function () { return $(this).attr('type') !== 'hidden' || $(this).closest('.bdaterange-container').length; });
            if (!$campos.length) { return; }
            var rotulo = $.trim($grid.find('thead tr th').eq(indice).text());
            var valores;
            if ($campos.first().attr('widget') === 'bdaterange') {
                var de = $campos.eq(0).val(), ate = $campos.eq(1).val();
                valores = (de || ate) ? [(de || '...') + ' até ' + (ate || '...')] : [];
            } else {
                valores = $campos.map(function () { return textoCampo($(this)); }).get().filter(function (t) { return t !== ''; });
            }
            if (!valores.length) { return; }
            $lista.append(badge(rotulo, valores.join(' / '), function () {
                var $primeiro = $campos.first();
                if ($primeiro.attr('widget') === 'bdaterange' && $primeiro.closest('.bdaterange-container').data('picker')) {
                    $primeiro.closest('.bdaterange-container').data('picker').clear();
                }
                $campos.val('').trigger('change.select2');
                var acao = $primeiro.data('changeaction') || $primeiro.data('exitaction') || $primeiro.attr('changeaction') || $primeiro.attr('exitaction');
                if (acao) { eval(acao); }
            }));
        });

        // cortina e botoes rapidos (do servidor)
        var servidor = window.curciolFiltrosServidor[datagridId];
        if (servidor) {
            $.each(servidor.itens, function (i, item) {
                $lista.append(badge(item.rotulo, item.valor, function () {
                    var url = servidor.url;
                    $.each(item.campos, function (j, campo) { url += '&__filtros_extra%5B' + encodeURIComponent(campo) + '%5D='; });
                    __adianti_load_page(url);
                }));
            });
        }

        $grid.before($container);
        $container.toggle($lista.children().length > 0);
    };
})();
