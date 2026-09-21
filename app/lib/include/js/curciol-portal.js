/* ==========================================================================
   Curciol — Área do cliente
   --------------------------------------------------------------------------
   Dois trabalhos, ambos só de apresentação:

   1. Marcar o <body> quando uma tela do portal está montada, para a folha de
      estilo poder esconder a barra administrativa sem afetar o restante do
      layout público.
   2. Copiar o texto do cabeçalho de cada coluna para um data-rotulo na célula
      correspondente. No celular a tabela vira cards e o <thead> some; o rótulo
      volta pelo CSS via content: attr(data-rotulo). Ler do cabeçalho real
      evita que os rótulos saiam de sincronia quando as colunas mudarem.

   Nada aqui altera dados, ações, rotas ou requisições.
   ========================================================================== */
(function () {
    'use strict';

    var CLASSE_ATIVA = 'curciol-portal-ativo';

    function marcarBody() {
        if (!document.body) {
            return;
        }

        var temPortal = document.querySelector('.curciol-portal') !== null;

        document.body.classList.toggle(CLASSE_ATIVA, temPortal);
    }

    function rotularCelulas(raiz) {
        var tabelas = (raiz || document).querySelectorAll('.curciol-lista table');

        Array.prototype.forEach.call(tabelas, function (tabela) {
            var cabecalhos = tabela.querySelectorAll('thead th');

            if (!cabecalhos.length) {
                return;
            }

            var rotulos = Array.prototype.map.call(cabecalhos, function (th) {
                return (th.textContent || '').replace(/\s+/g, ' ').trim();
            });

            var linhas = tabela.querySelectorAll('tbody tr');

            Array.prototype.forEach.call(linhas, function (linha) {
                // a linha de filtros não é um registro
                if (linha.id === 'datagrid-header-filter-row') {
                    return;
                }

                Array.prototype.forEach.call(linha.cells, function (celula, indice) {
                    var rotulo = rotulos[indice];

                    // células de ação não levam rótulo
                    if (!rotulo || celula.classList.contains('action')) {
                        return;
                    }

                    if (celula.getAttribute('data-rotulo') !== rotulo) {
                        celula.setAttribute('data-rotulo', rotulo);
                    }
                });
            });
        });
    }

    function atualizar() {
        marcarBody();
        rotularCelulas(document);
    }

    function observar() {
        var alvo = document.getElementById('adianti_div_content') || document.body;

        if (!alvo || typeof MutationObserver === 'undefined') {
            return;
        }

        var agendado = false;

        new MutationObserver(function () {
            // o Adianti troca o conteúdo em lote; um passe por quadro basta
            if (agendado) {
                return;
            }

            agendado = true;

            window.requestAnimationFrame(function () {
                agendado = false;
                atualizar();
            });
        }).observe(alvo, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            atualizar();
            observar();
        });
    } else {
        atualizar();
        observar();
    }
})();
