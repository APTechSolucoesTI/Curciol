<?php
/**
 * Campo de filtro com varios itens (29/10/2026), usado na agenda para clientes e agendas:
 * - campo fino de busca; a lista abre ao clicar e so fecha ao clicar fora (marca um por um, sem fechar);
 * - "Todas" no topo marca (ou desmarca) todos os itens que estao na lista;
 * - os marcados ficam empilhados abaixo do campo (um por linha, com x para tirar) e vao no formulario como nome[];
 * - itens com cor (agendas) mostram a bolinha da cor na lista e nos marcados;
 * - lista fixa (opcoes) ou busca no servidor ($urlBusca, a partir de $minimo letras, devolve [{id, nome}]).
 * Ao fechar a lista (se mudou algo) ou ao tirar um marcado, roda $acao (JavaScript) para aplicar o filtro.
 */
class FiltroMultiplo
{
    /**
     * @param array $opcoes      [id => ['nome' => , 'cor' => ]] (lista fixa) ou [] com $urlBusca
     * @param array $marcados    [id => ['nome' => , 'cor' => ]]
     */
    public static function campo(string $nome, string $placeholder, string $textoTodos, array $opcoes, array $marcados, string $acao, ?string $urlBusca = null, int $minimo = 3): TElement
    {
        $e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        $bolinha = function ($cor) use ($e) { return $cor ? '<span class="fm-cor" style="background:' . $e($cor) . '"></span>' : ''; };

        $itens = '';
        foreach ($opcoes as $id => $o) {
            $itens .= '<label class="fm-item" data-id="' . (int) $id . '" data-nome="' . $e($o['nome']) . '" data-cor="' . $e($o['cor'] ?? '') . '">'
                . '<input type="checkbox">' . $bolinha($o['cor'] ?? '') . '<span class="fm-txt">' . $e($o['nome']) . '</span></label>';
        }
        $lista = '';
        foreach ($marcados as $id => $o) {
            $lista .= self::marcado($nome, (int) $id, (string) $o['nome'], (string) ($o['cor'] ?? ''));
        }

        $div = new TElement('div');
        $div->class = 'fm';
        $div->{'data-nome'} = $nome;
        $div->{'data-busca'} = (string) $urlBusca;
        $div->{'data-minimo'} = $urlBusca ? $minimo : 0;
        $div->{'data-acao'} = $acao;
        $div->add('<div class="fm-campo"><input type="text" class="fm-busca tfield" autocomplete="off" placeholder="' . $e($placeholder) . '"><i class="fas fa-search fm-lupa"></i></div>'
            . '<div class="fm-lista"><label class="fm-item fm-todos"><input type="checkbox"><b>' . $e($textoTodos) . '</b></label>'
            . '<div class="fm-itens">' . $itens . '</div><div class="fm-vazio"></div></div>'
            . '<div class="fm-marcados">' . $lista . '</div>');
        return $div;
    }

    private static function marcado(string $nome, int $id, string $texto, string $cor): string
    {
        $e = function ($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); };
        return '<div class="fm-marcado" data-id="' . $id . '">' . ($cor ? '<span class="fm-cor" style="background:' . $e($cor) . '"></span>' : '')
            . '<span class="fm-txt">' . $e($texto) . '</span><button type="button" class="fm-tirar" title="Tirar" aria-label="Tirar">&times;</button>'
            . '<input type="hidden" name="' . $e($nome) . '[]" value="' . $id . '"></div>';
    }

    public static function estilo(): string
    {
        return '<style>
            .fm { position: relative; width: 100%; }
            /* nos formularios o Adianti encolhe o campo ao tamanho do texto: aqui ele ocupa a coluna */
            .fb-inline-field-container:has(.fm) { width: 100%; float: none; }
            .fm .fm-campo { position: relative; }
            .fm .fm-busca { width: 100%; height: 28px; box-sizing: border-box; padding: 0 26px 0 8px; border: 1px solid #dfe4ed; border-radius: 5px; background: #fff; }
            .fm .fm-busca:focus { outline: none; border-color: #556372; }
            .fm .fm-lupa { position: absolute; right: 9px; top: 8px; font-size: 11px; color: #8694b0; pointer-events: none; }
            .fm .fm-lista { display: none; position: absolute; left: 0; right: 0; top: 30px; z-index: 1060; background: #fff; border: 1px solid #556372; border-radius: 5px;
                            box-shadow: 0 6px 16px rgba(18,47,78,.18); max-height: 280px; overflow-y: auto; padding: 4px 0; }
            .fm.fm-aberto .fm-lista { display: block; }
            .fm .fm-item { display: flex; align-items: center; gap: 8px; margin: 0; padding: 0 10px; min-height: 28px; font-weight: normal; cursor: pointer; }
            .fm .fm-item:hover { background: #f3f5f6; }
            .fm .fm-item input { margin: 0; width: 13px; height: 13px; flex-shrink: 0; }
            .fm .fm-todos { border-bottom: 1px solid #eceef1; }
            .fm .fm-vazio { color: #8694b0; padding: 4px 10px; font-size: 12px; }
            .fm .fm-vazio:empty { display: none; }
            .fm .fm-cor { width: 9px; height: 9px; border-radius: 50%; display: inline-block; flex-shrink: 0; }
            .fm .fm-marcados { display: flex; flex-direction: column; gap: 2px; margin-top: 3px; }
            .fm .fm-marcado { display: flex; align-items: center; gap: 7px; min-height: 24px; padding: 0 4px 0 8px; border-radius: 4px; background: #f3f5f6; font-size: 12px; }
            .fm .fm-marcado .fm-txt { flex-grow: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .fm .fm-tirar { border: 0; background: transparent; color: #8694b0; width: 22px; height: 22px; line-height: 1; font-size: 15px; cursor: pointer; }
            .fm .fm-tirar:hover { color: #dd5a43; }
        </style>';
    }

    public static function script(): string
    {
        return <<<'JS'
(function () {
    if (window.curciolFiltroMultiplo) { return; }
    window.curciolFiltroMultiplo = true;
    var temporizador = null;
    function norm(s) { return (s || '').toString().normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
    function ids(fm) { return fm.find('.fm-marcado').map(function () { return String($(this).data('id')); }).get(); }
    function sincronizar(fm) {
        var marcados = ids(fm), visiveis = fm.find('.fm-itens .fm-item').filter(function () { return this.style.display !== 'none'; });
        fm.find('.fm-itens .fm-item').each(function () { $(this).find('input').prop('checked', marcados.indexOf(String($(this).data('id'))) >= 0); });
        var todos = visiveis.length > 0 && visiveis.filter(function () { return $(this).find('input').prop('checked'); }).length === visiveis.length;
        fm.find('.fm-todos input').prop('checked', todos);
        fm.find('.fm-todos').toggle(visiveis.length > 0);
    }
    function adicionar(fm, item) {
        var id = String(item.data('id'));
        if (ids(fm).indexOf(id) >= 0) { return; }
        var linha = $('<div class="fm-marcado"></div>').attr('data-id', id);
        var cor = item.data('cor');
        if (cor) { linha.append($('<span class="fm-cor"></span>').css('background', cor)); }
        linha.append($('<span class="fm-txt"></span>').text(item.data('nome')));
        linha.append('<button type="button" class="fm-tirar" title="Tirar" aria-label="Tirar">&times;</button>');
        linha.append($('<input type="hidden">').attr('name', fm.data('nome') + '[]').val(id));
        fm.find('.fm-marcados').append(linha);
    }
    function remover(fm, id) { fm.find('.fm-marcado').filter(function () { return String($(this).data('id')) === String(id); }).remove(); }
    function aplicar(fm) { fm.data('mudou', false); var acao = fm.attr('data-acao'); if (acao) { (new Function(acao))(); } }
    function fechar(fm) { fm.removeClass('fm-aberto'); if (fm.data('mudou')) { aplicar(fm); } }
    function buscar(fm, texto) {
        var url = fm.data('busca'), minimo = parseInt(fm.data('minimo'), 10) || 0, itens = fm.find('.fm-itens'), vazio = fm.find('.fm-vazio');
        if (!url) {
            var t = norm(texto), achou = 0;
            itens.find('.fm-item').each(function () { var ok = norm($(this).data('nome')).indexOf(t) >= 0; this.style.display = ok ? '' : 'none'; if (ok) { achou++; } });
            vazio.text(achou ? '' : 'Nada encontrado.');
            sincronizar(fm);
            return;
        }
        if (texto.trim().length < minimo) { itens.empty(); vazio.text('Digite ao menos ' + minimo + ' letras para buscar.'); sincronizar(fm); return; }
        clearTimeout(temporizador);
        temporizador = setTimeout(function () {
            vazio.text('Buscando...');
            $.getJSON(url + '&q=' + encodeURIComponent(texto.trim()), function (lista) {
                itens.empty();
                (lista || []).forEach(function (o) {
                    var item = $('<label class="fm-item"><input type="checkbox"></label>').attr('data-id', o.id).attr('data-nome', o.nome).attr('data-cor', o.cor || '');
                    if (o.cor) { item.append($('<span class="fm-cor"></span>').css('background', o.cor)); }
                    item.append($('<span class="fm-txt"></span>').text(o.nome));
                    itens.append(item);
                });
                vazio.text((lista || []).length ? '' : 'Nada encontrado.');
                sincronizar(fm);
            });
        }, 300);
    }
    $(document).on('focus.fm click.fm', '.fm .fm-busca', function () {
        var fm = $(this).closest('.fm');
        $('.fm.fm-aberto').not(fm).each(function () { fechar($(this)); });
        if (!fm.hasClass('fm-aberto')) { fm.addClass('fm-aberto'); buscar(fm, this.value); }
    });
    $(document).on('input.fm', '.fm .fm-busca', function () { buscar($(this).closest('.fm'), this.value); });
    $(document).on('keydown.fm', '.fm .fm-busca', function (e) { if (e.key === 'Escape' || e.key === 'Tab') { fechar($(this).closest('.fm')); } if (e.key === 'Enter') { e.preventDefault(); } });
    $(document).on('mousedown.fm', function (e) { $('.fm.fm-aberto').each(function () { if (!$.contains(this, e.target)) { fechar($(this)); } }); });
    $(document).on('change.fm', '.fm .fm-itens .fm-item input', function () {
        var item = $(this).closest('.fm-item'), fm = item.closest('.fm');
        if (this.checked) { adicionar(fm, item); } else { remover(fm, item.data('id')); }
        fm.data('mudou', true); sincronizar(fm);
    });
    $(document).on('change.fm', '.fm .fm-todos input', function () {
        var fm = $(this).closest('.fm'), marcar = this.checked;
        fm.find('.fm-itens .fm-item').filter(function () { return this.style.display !== 'none'; }).each(function () {
            if (marcar) { adicionar(fm, $(this)); } else { remover(fm, $(this).data('id')); }
        });
        fm.data('mudou', true); sincronizar(fm);
    });
    $(document).on('click.fm', '.fm .fm-tirar', function () {
        var fm = $(this).closest('.fm');
        $(this).closest('.fm-marcado').remove();
        sincronizar(fm); aplicar(fm);
    });
})();
JS;
    }
}
