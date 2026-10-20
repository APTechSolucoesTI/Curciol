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
})();
