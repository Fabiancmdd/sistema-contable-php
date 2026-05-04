(function () {
    'use strict';

    // Bloquea cualquier carácter que no sea dígito o punto en los inputs
    // marcados con la clase .codigo-cuenta. Aplica al tipear, al pegar y
    // (por las dudas) al perder el foco.
    function sanitize(value) {
        return String(value || '').replace(/[^0-9.]/g, '').slice(0, 12);
    }

    function bind(input) {
        if (!input || input.dataset.codigoCuentaBound === '1') return;
        input.dataset.codigoCuentaBound = '1';

        // Bloqueo en vivo: cualquier tecla que produzca otro carácter se filtra.
        input.addEventListener('input', function () {
            var clean = sanitize(input.value);
            if (clean !== input.value) {
                var pos = input.selectionStart;
                input.value = clean;
                if (typeof pos === 'number') {
                    try { input.setSelectionRange(pos - 1, pos - 1); } catch (e) {}
                }
            }
        });

        // Pegado desde portapapeles (Ctrl+V / clic derecho).
        input.addEventListener('paste', function (e) {
            e.preventDefault();
            var txt = (e.clipboardData || window.clipboardData).getData('text');
            var clean = sanitize(input.value + txt);
            input.value = clean;
        });

        // Garantía final al salir del campo.
        input.addEventListener('blur', function () {
            input.value = sanitize(input.value);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input.codigo-cuenta').forEach(bind);
    });
})();
