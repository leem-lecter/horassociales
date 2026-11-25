{{--
    Aviso inmediato de fechas (R5), común a Proyecto de trabajo y Registro diario.
    Define window.sseAvisoFecha(opciones), que revisa el campo cada vez que cambia y
    muestra debajo el mismo mensaje que daría el servidor.

    Uso (en un <script> después de incluir este archivo):
        sseAvisoFecha({
            campo: document.getElementById('c-fecha'),
            periodos: @json($periodos),          // [nombre, inicio, fin] en AAAA-MM-DD
            ayuda: 'Texto de ayuda normal',
            antes: function (f) { return ''; },   // revisiones que van antes de R5 (opcional)
            despues: function (f) { return ''; }, // revisiones que van después de R5 (opcional)
        });
    sseAvisoFecha.largo('2026-11-18') -> "18 de noviembre de 2026"
--}}
@once
<script>
    window.sseAvisoFecha = (function () {
        function largo(fecha) {
            var p = fecha.split('-');
            return new Date(+p[0], p[1] - 1, +p[2]).toLocaleDateString('es', { day: 'numeric', month: 'long', year: 'numeric' });
        }

        // Si la fecha cae en varios periodos, se nombra el más corto (Viernes Santo antes que Semana Santa).
        function periodoDe(f, periodos) {
            var dura = function (x) { return Date.parse(x[2]) - Date.parse(x[1]); };
            var hallado = null;
            periodos.forEach(function (p) {
                if (p[1] <= f && f <= p[2] && (! hallado || dura(p) < dura(hallado))) {
                    hallado = p;
                }
            });
            return hallado;
        }

        function iniciar(o) {
            var campo = o.campo;
            if (! campo) return function () {};
            var aviso = campo.parentNode.querySelector('small');
            if (! aviso) {
                aviso = document.createElement('small');
                campo.parentNode.appendChild(aviso);
            }
            var ayuda = aviso.classList.contains('error') ? (o.ayuda || '') : aviso.textContent;
            aviso.setAttribute('aria-live', 'polite');

            function revisar() {
                var f = campo.value;   // AAAA-MM-DD
                var problema = '';
                if (f) {
                    problema = (o.antes && o.antes(f)) || '';
                    if (! problema) {
                        var p = periodoDe(f, o.periodos || []);
                        if (p) problema = 'El ' + largo(f) + ' es día no hábil (' + p[0] + '). Elige otra fecha.';
                    }
                    if (! problema && o.despues) problema = o.despues(f) || '';
                }
                campo.setCustomValidity(problema);
                campo.classList.toggle('con-error', !! problema);
                aviso.classList.toggle('error', !! problema);
                aviso.style.color = problema ? '' : (f ? 'var(--ok)' : '');
                aviso.textContent = problema || (f ? 'Fecha permitida: es día hábil.' : ayuda);
            }

            campo.addEventListener('input', revisar);
            campo.addEventListener('change', revisar);
            // Si el servidor ya marcó un error, se deja a la vista hasta que el estudiante cambie la fecha.
            if (campo.value && ! aviso.classList.contains('error')) revisar();
            return revisar;
        }

        iniciar.largo = largo;
        return iniciar;
    })();
</script>
@endonce
