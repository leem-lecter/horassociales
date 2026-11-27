{{-- Comportamiento de los formularios de decisión y de "Aprobar marcadas" (M6). Se incluye una vez por pantalla. --}}
<script>
    (function () {
        // No aprobar sin comentario: aviso al instante (el servidor también lo revisa).
        document.addEventListener('submit', function (ev) {
            var form = ev.target;
            if (form.matches('[data-resolver]')) {
                var boton = ev.submitter;
                var comentario = form.querySelector('[name=comentario]');
                var error = form.querySelector('.error-resolver');
                if (boton && boton.value === 'rechazar' && ! comentario.value.trim()) {
                    ev.preventDefault();
                    error.hidden = false;
                    comentario.classList.add('con-error');
                    comentario.focus();
                    return;
                }
                var mensaje = form.getAttribute('data-confirmar');
                if (mensaje && ! window.confirm(mensaje)) {
                    ev.preventDefault();
                    return;
                }
            }
            if (form.matches('[data-lote]') && ! window.confirm(form.getAttribute('data-lote-confirmar').replace('N', contar()))) {
                ev.preventDefault();
                return;
            }
            // Un solo envío: evita el doble clic. Se desactiva después de armar el envío,
            // así el botón elegido (decision) sí viaja.
            if (form.matches('[data-resolver], [data-lote]')) {
                setTimeout(function () {
                    form.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
                }, 0);
            }
        });

        document.addEventListener('input', function (ev) {
            var form = ev.target.closest('[data-resolver]');
            if (form && ev.target.name === 'comentario' && ev.target.value.trim()) {
                form.querySelector('.error-resolver').hidden = true;
                ev.target.classList.remove('con-error');
            }
        });

        // Aprobar varias de una vez (decisión 4).
        function items() { return Array.prototype.slice.call(document.querySelectorAll('[data-lote-item]')); }
        function contar() { return items().filter(function (c) { return c.checked; }).length; }
        function pintarLote() {
            var n = contar();
            document.querySelectorAll('[data-aprobar-lote]').forEach(function (b) {
                b.disabled = n === 0;
                b.textContent = n ? 'Aprobar marcadas (' + n + ')' : 'Aprobar marcadas';
            });
            var todas = document.querySelector('[data-marcar-todas]');
            if (todas) {
                todas.checked = n > 0 && n === items().length;
                todas.indeterminate = n > 0 && n < items().length;
            }
        }
        document.addEventListener('change', function (ev) {
            if (ev.target.matches('[data-marcar-todas]')) {
                items().forEach(function (c) { c.checked = ev.target.checked; });
            }
            if (ev.target.matches('[data-marcar-todas], [data-lote-item]')) pintarLote();
        });
        pintarLote();
    })();
</script>
