(function () {
    'use strict';

    // Filtra el valor: deja sólo dígitos y puntos, y corta a 8 dígitos (los
    // puntos no cuentan al límite). Ej: "abc1234567890123" -> "12345678".
    function sanitize(value) {
        var s = String(value || '').replace(/[^0-9.]/g, '');
        var out = '';
        var digits = 0;
        for (var i = 0; i < s.length; i++) {
            var ch = s.charAt(i);
            if (ch === '.') {
                out += ch;
            } else if (digits < 8) {
                out += ch;
                digits++;
            }
        }
        return out;
    }

    // Debounce: ejecutar `fn` recién cuando paró de tipear `wait` ms.
    function debounce(fn, wait) {
        var t = null;
        return function () {
            var args = arguments, ctx = this;
            if (t) clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    function ensureFeedback(input) {
        var fb = input.parentNode.querySelector('.codigo-cuenta-feedback');
        if (!fb) {
            fb = document.createElement('div');
            fb.className = 'codigo-cuenta-feedback small mt-1';
            input.parentNode.appendChild(fb);
        }
        return fb;
    }

    function setStatus(input, kind, msg) {
        // kind: 'ok' | 'err' | 'warn' | ''
        input.classList.remove('is-valid', 'is-invalid');
        var fb = ensureFeedback(input);
        fb.classList.remove('text-success', 'text-danger', 'text-warning');
        if (kind === 'ok') {
            input.classList.add('is-valid');
            fb.classList.add('text-success');
        } else if (kind === 'err') {
            input.classList.add('is-invalid');
            fb.classList.add('text-danger');
        } else if (kind === 'warn') {
            fb.classList.add('text-warning');
        }
        fb.textContent = msg || '';
    }

    function checkServer(input, code) {
        var endpoint = input.dataset.checkUrl;
        if (!endpoint) return;
        var ignore = input.dataset.ignoreId || '';
        var url = endpoint + '?codigo=' + encodeURIComponent(code) + (ignore ? '&ignore_id=' + encodeURIComponent(ignore) : '');
        fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (input.value !== code) return; // el usuario siguió tipiando
                if (data && data.exists) {
                    setStatus(input, 'err', 'Ese código ya existe (' + (data.nombre || '') + ').');
                } else {
                    setStatus(input, 'ok', 'Disponible.');
                }
            })
            .catch(function () { /* silent */ });
    }

    var debouncedCheck = debounce(function (input) {
        var v = input.value;
        var pattern = /^\d\.\d\.\d{2}\.\d{2}\.\d{2}$/;
        if (v === '') {
            setStatus(input, '', '');
            return;
        }
        if (!pattern.test(v)) {
            setStatus(input, 'warn', 'Formato esperado: X.X.XX.XX.XX (8 dígitos).');
            return;
        }
        checkServer(input, v);
    }, 350);

    function bind(input) {
        if (!input || input.dataset.codigoCuentaBound === '1') return;
        input.dataset.codigoCuentaBound = '1';

        function applySanitize() {
            var clean = sanitize(input.value);
            if (clean !== input.value) {
                var pos = input.selectionStart;
                input.value = clean;
                if (typeof pos === 'number') {
                    try { input.setSelectionRange(pos - 1, pos - 1); } catch (e) {}
                }
            }
        }

        input.addEventListener('input', function () {
            applySanitize();
            debouncedCheck(input);
        });

        input.addEventListener('paste', function (e) {
            e.preventDefault();
            var txt = (e.clipboardData || window.clipboardData).getData('text');
            input.value = sanitize(input.value + txt);
            debouncedCheck(input);
        });

        input.addEventListener('blur', function () {
            input.value = sanitize(input.value);
            debouncedCheck(input);
        });

        // Disparar la validación si ya hay valor (server-rendered).
        if (input.value) debouncedCheck(input);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input.codigo-cuenta').forEach(bind);
    });
})();
