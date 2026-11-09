/*
 * Curciol (09/11/2026): campo de texto que acompanha o tamanho do texto, no editor de e-mail (summernote) e no texto simples
 * (WhatsApp). Ao abrir ja mostra o texto inteiro; cresce e encolhe enquanto o usuario escreve, nunca abaixo da altura
 * original. Continua redimensionavel a mao: depois de puxar a borda, so cresce quando o texto passar do tamanho escolhido.
 *
 * Uso: marque o campo com data-autoaltura="1" (TText ou THtmlEditor ->setProperty('data-autoaltura', '1')).
 * window.curciolAutoAltura(raiz) reajusta tudo (chamar quando um campo escondido aparecer ou o texto for trocado por script).
 */
(function ($) {
    'use strict';

    function bordas(el) { return el.offsetHeight - el.clientHeight; }

    function ajustar(el) {
        if (!el || !el.offsetParent) { return; } // escondido: ajusta quando aparecer
        var $el = $(el);
        if ($el.data('autoalturaMin') == null) { $el.data('autoalturaMin', el.offsetHeight || 0); }

        if ($el.data('autoalturaManual')) {
            if (el.scrollHeight > el.clientHeight) { el.style.height = (el.scrollHeight + bordas(el)) + 'px'; }
            return;
        }

        var rolagem = $(el).closest('.right-panel, .modal-body, body').scrollTop();
        el.style.height = 'auto';
        el.style.height = Math.max($el.data('autoalturaMin'), el.scrollHeight + bordas(el)) + 'px';
        $(el).closest('.right-panel, .modal-body, body').scrollTop(rolagem);
    }

    function editavel(textarea) { return $(textarea).next('.note-editor').find('.note-editable')[0] || null; }

    function observar(textarea) {
        var ed = editavel(textarea);
        if (!ed || $(ed).data('autoalturaObservado')) { return; }
        $(ed).data('autoalturaObservado', 1);
        var tempo = null;
        var depois = function () { clearTimeout(tempo); tempo = setTimeout(function () { ajustar(ed); }, 30); };
        if (window.MutationObserver) {
            new MutationObserver(depois).observe(ed, { childList: true, subtree: true, characterData: true });
        }
        ed.addEventListener('load', depois, true); // imagem que termina de carregar muda a altura
        ajustar(ed);
        setTimeout(function () { ajustar(ed); }, 350); // depois da cortina abrir
    }

    window.curciolAutoAltura = function (raiz) {
        $(raiz || document).find('textarea[data-autoaltura]').each(function () {
            if ($(this).next('.note-editor').length) { observar(this); ajustar(editavel(this)); }
            else { ajustar(this); }
        });
    };

    $(document)
        .on('input.curciolAutoAltura', 'textarea[data-autoaltura]', function () { ajustar(this); })
        .on('summernote.init.curciolAutoAltura summernote.change.curciolAutoAltura', 'textarea[data-autoaltura]', function () {
            var t = this;
            setTimeout(function () { observar(t); ajustar(editavel(t)); }, 0);
        })
        // puxar a borda: vale a altura escolhida
        .on('mousedown.curciolAutoAltura', '.note-statusbar, .note-resizebar', function () {
            $(this).closest('.note-editor').find('.note-editable').data('autoalturaManual', 1);
        })
        .on('mousedown.curciolAutoAltura', 'textarea[data-autoaltura]', function () { $(this).data('autoalturaAntes', this.offsetHeight); })
        .on('mouseup.curciolAutoAltura', 'textarea[data-autoaltura]', function () {
            if ($(this).data('autoalturaAntes') != null && this.offsetHeight !== $(this).data('autoalturaAntes')) { $(this).data('autoalturaManual', 1); }
        });

    $(function () { setTimeout(function () { window.curciolAutoAltura(); }, 0); });
})(jQuery);
