(function () {
    var seletor = 'input[data-curciol-telefone="1"]';
    function formatar() {
        var campo = this;
        var texto = campo.value || '';
        var posicao = campo.selectionStart;
        var anteriores = texto.slice(0, posicao === null ? texto.length : posicao).replace(/\D/g, '').length;
        var numero = texto.replace(/\D/g, '');
        if (numero.length > 11) {
            if (!/^55\d{10,11}$/.test(numero)) { return; }
            numero = numero.slice(2);
            anteriores = Math.max(0, anteriores - 2);
        }
        var grupo = numero.length > 10 ? 5 : 4;
        var valor = numero ? '(' + numero.slice(0, 2) : '';
        if (numero.length > 2) { valor += ') ' + numero.slice(2, 2 + grupo); }
        if (numero.length > 2 + grupo) { valor += '-' + numero.slice(2 + grupo); }
        if (valor === texto) { return; }
        campo.value = valor;
        if (document.activeElement === campo && posicao !== null) {
            var cursor = 0;
            var vistos = 0;
            while (cursor < valor.length && vistos < anteriores) {
                if (/\d/.test(valor.charAt(cursor))) { vistos++; }
                cursor++;
            }
            campo.setSelectionRange(cursor, cursor);
        }
    }
    $(document).off('input.curciolTelefone change.curciolTelefone', seletor)
        .on('input.curciolTelefone change.curciolTelefone', seletor, formatar);
    setTimeout(function () { $(seletor).each(formatar); }, 0);
})();
