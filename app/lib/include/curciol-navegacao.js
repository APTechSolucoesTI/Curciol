/*
 * Caminho de paginas no topo (breadcrumb): os itens eram links sem destino (nao levavam a lugar nenhum).
 *  - item com o mesmo nome de um item do menu lateral: leva a ele (como clicar no menu);
 *  - o item da propria pagina, quando ela e uma lista: recarrega a lista mantendo os filtros;
 *  - nome de modulo sem tela propria (ex.: "Financeiro"): vira texto, sem cara de link.
 * Carregado depois do admin-all.min.js.
 */
(function () {
    if (typeof Template === 'undefined' || window.__curciolBreadcrumb) {
        return;
    }
    window.__curciolBreadcrumb = true;

    function normalizar(t) {
        return String(t || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
    }
    function classe(href) {
        var m = /[?&]class=([A-Za-z0-9_]+)/.exec(href || '');
        return m ? m[1] : '';
    }
    function itensDoMenu() {
        var mapa = {};
        $('a[href*="class="]').not('.tbreadcrumb a').each(function () {
            var href = $(this).attr('href') || '';
            var rotulo = normalizar($(this).text());
            if (rotulo && /^(index|engine)\.php\?class=/.test(href)) {
                (mapa[rotulo] = mapa[rotulo] || []).push(href);
            }
        });
        return mapa;
    }

    function ajustar() {
        var menu = null;
        $('ol.tbreadcrumb').each(function () {
            var bc = $(this);
            if (bc.data('curciolBc')) {
                return;
            }
            bc.data('curciolBc', true);
            menu = menu || itensDoMenu();
            var pagina = bc.closest('[page_name]').attr('page_name') || '';
            var itens = bc.children('li').not('.home');
            itens.each(function (i) {
                var link = $(this).children('a').first();
                if (!link.length || link.attr('href')) {
                    return;
                }
                var destinos = menu[normalizar(link.text())] || [];
                var destino = destinos.filter(function (h) { return classe(h) === pagina; })[0] || destinos[0] || null;
                var ultimo = (i === itens.length - 1);
                if (ultimo && /List$/.test(pagina) && (!destino || classe(destino) === pagina)) {
                    destino = 'index.php?class=' + pagina;   // a propria lista: recarrega com os filtros
                }
                if (destino) {
                    link.attr({ href: destino, generator: 'adianti' });
                } else {
                    link.replaceWith($('<span class="curciol-bc-texto" style="cursor:default"></span>').append(link.contents()));
                }
            });
        });
    }

    // abas do topo: aba nova (aberta pelo menu, inclusive depois de fechada) comeca sem filtros; clicar no menu de uma
    // aba que continua aberta recarrega a lista mantendo os filtros (onReload em vez de onShow, que os zerava)
    if (typeof window.__adianti_load_page === 'function' && !window.__adianti_load_page.__curciol) {
        var carregarPagina = window.__adianti_load_page;
        window.__adianti_load_page = function (page) {
            var args = Array.prototype.slice.call(arguments);
            if (typeof page === 'string' && page.indexOf('adianti_open_tab=1') >= 0 && page.indexOf('adianti_reload_tab=1') < 0
                && page.indexOf('curciol_limpar_filtros') < 0) {
                var nome = (typeof __adianti_query_to_json === 'function') ? (__adianti_query_to_json(page).adianti_tab_name || '') : '';
                if (window.Adianti && Adianti.tabs && nome && Adianti.tabs[nome]) {
                    if (/List$/.test(classe(page))) {
                        args[0] = page.replace(/([?&]method=)onShow\b/, '$1onReload');
                    }
                } else {
                    args[0] = page + '&curciol_limpar_filtros=1';
                }
            }
            return carregarPagina.apply(this, args);
        };
        window.__adianti_load_page.__curciol = true;
    }

    var depoisDeCarregar = Template.onAfterLoad;
    Template.onAfterLoad = function () {
        var resultado = (typeof depoisDeCarregar === 'function') ? depoisDeCarregar.apply(this, arguments) : undefined;
        setTimeout(ajustar, 0);
        return resultado;
    };
    if (Template.onAfterPost === depoisDeCarregar) {
        Template.onAfterPost = Template.onAfterLoad;
    }
    $(function () { setTimeout(ajustar, 300); });

    // botoes que levam a outra tela do menu (ex.: Agenda <-> Listagem, 30/10/2026): abre pelo item do menu, como se o
    // usuario clicasse nele (aba, endereco e item marcado no menu mudam juntos); sem o item no menu, carrega a pagina
    window.curciolAbrirPeloMenu = function (pagina, reserva) {
        var link = $('a[href*="class=' + pagina + '"]').not('.tbreadcrumb a').filter(function () {
            var href = $(this).attr('href') || '';
            return classe(href) === pagina && href.indexOf('adianti_open_tab') >= 0;
        }).first();
        if (link.length) { link[0].click(); } else { __adianti_load_page(reserva || ('index.php?class=' + pagina)); }
    };
})();
