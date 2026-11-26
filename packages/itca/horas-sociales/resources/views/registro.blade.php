@extends(config('horas-sociales.layout') ?: 'horas-sociales::layouts.modulo')

@section('titulo', 'Registro diario')

@section('sse-contenido')
    @php
        $estados = [
            'pendiente' => ['aviso', 'En revisión'], 'aprobado' => ['ok', 'Aprobado'], 'rechazado' => ['mal', 'No aprobado'],
        ];
        $fecha = fn ($f) => $f->locale('es')->isoFormat('D MMM YYYY');
        $esAmbiental = $pestana === 'ambiental';
        $tipoElegido = old('tipo', $pestana);
        $maxDia = (int) config('horas-sociales.max_horas_por_dia');
        $maxFotos = (int) config('horas-sociales.max_fotos');
        $maxKb = (int) config('horas-sociales.max_kb_foto');
        $mbFoto = round($maxKb / 1024, 1);
        $total = $registros->where('estado', '!=', 'rechazado')->sum('horas');
        $errorFotos = $errors->first('fotos') ?: collect($errors->get('fotos.*'))->flatten()->first();
        $ayudaFotos = "Obligatorias en medio ambiente. Hasta {$maxFotos} fotos; se achican solas antes de enviarlas.";
        $o = ['dis' => ! $activo, 'req' => true];
        $tipos = ['general' => 'Servicio social general', 'ambiental' => 'Cuido y conservación del medio ambiente'];
        $columnas = $esAmbiental ? 6 : 5;
    @endphp

    <div>
        <div class="cabecera"><h1>Registro diario</h1><span class="codigo">FBSSE-002</span></div>
        <p class="bajada">Anota cada día que hagas servicio social. El coordinador revisa lo que envías y decide si las horas quedan aprobadas.</p>
    </div>

    @if (session('sse_aviso'))
        <p class="nota ok" role="status">{{ session('sse_aviso') }}</p>
    @endif
    @if (session('sse_error'))
        <p class="nota mal" role="alert">{{ session('sse_error') }}</p>
    @endif
    @if ($envioMuyPesado)
        <p class="nota mal" role="alert"><b>Las fotos pesaban demasiado y el registro no se guardó.</b> Vuelve a intentarlo con menos fotos o con fotos más livianas.</p>
    @endif
    @if ($cerrado)
        <p class="nota aviso"><b>Tu registro de horas quedó cerrado.</b> Ya enviaste tu <a href="{{ route('sse.finalizacion') }}">hoja de finalización</a>: no se agregan ni se borran registros de este proyecto. Si el coordinador te la devuelve, se abre otra vez.</p>
    @elseif (! $activo)
        <p class="nota aviso"><b>Aún no puedes registrar horas.</b> Primero el coordinador debe aprobar tu <a href="{{ route('sse.proyecto') }}">proyecto de trabajo</a>.</p>
    @elseif ($errors->has('proyecto'))
        <p class="nota mal" role="alert">{{ $errors->first('proyecto') }}</p>
    @endif
    @if ($errors->any() && ! $errors->has('proyecto'))
        <p class="nota mal" role="alert"><b>Revisa los campos marcados en rojo.</b>
            @if (old('tipo') === 'ambiental') Vuelve a elegir las fotos: el navegador no las guarda después de un error. @endif
        </p>
    @endif

    <div class="dos">
        <form class="hoja pila" id="form-registro" method="POST" action="{{ route('sse.registro.guardar') }}" enctype="multipart/form-data" style="gap:14px">
            @csrf
            <h2>Nuevo registro</h2>

            <div class="pila" style="gap:6px">
                @include('horas-sociales::partials.campo', ['name' => 'fecha', 'label' => 'Fecha', 'valor' => $hoy, 'tipo' => 'date', 'ayuda' => 'No se aceptan asuetos ni vacaciones.'] + $o)
                @include('horas-sociales::partials.dias-no-habiles', ['periodos' => $periodos, 'anios' => $anios])
            </div>
            @include('horas-sociales::partials.campo', ['name' => 'tipo', 'label' => 'Tipo de actividad', 'valor' => $pestana, 'opciones' => $tipos] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'actividad', 'label' => 'Actividad desarrollada', 'tipo' => 'area'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'horas', 'label' => 'Total de horas del día', 'numerico' => 1, 'ayuda' => "Solo horas enteras. Máximo {$maxDia} por día."] + $o)

            <label class="campo" for="c-fotos" id="caja-fotos" @if ($tipoElegido !== 'ambiental') hidden @endif>
                <span>Fotos de evidencia</span>
                <input type="file" name="fotos[]" id="c-fotos" accept="image/*" multiple
                       @disabled(! $activo || $tipoElegido !== 'ambiental') @class(['con-error' => $errorFotos])>
                @if ($errorFotos)
                    <small class="error">{{ $errorFotos }}</small>
                @else
                    <small>{{ $ayudaFotos }}</small>
                @endif
            </label>

            <button class="boton" id="enviar-registro" @disabled(! $activo)>Enviar a revisión</button>
        </form>

        <section class="hoja">
            <header>
                <nav class="pestanas" aria-label="Hojas del registro">
                    <a href="{{ route('sse.registro') }}" @if (! $esAmbiental) aria-current="page" @endif>Control general</a>
                    <a href="{{ route('sse.registro', ['pestana' => 'ambiental']) }}" @if ($esAmbiental) aria-current="page" @endif>Medio ambiente ({{ config('horas-sociales.porcentaje_ambiental') }} %)</a>
                </nav>
            </header>
            @if ($esAmbiental && $resumen->metaAmbiental)
                <p class="gris" style="margin-bottom:10px">Llevas {{ $resumen->ambientalAprobadas }} horas aprobadas de las {{ $resumen->metaAmbiental }} que pide tu carrera.</p>
            @endif

            <div class="desliza">
                <table>
                    <thead>
                        <tr>
                            <th title="Hoja y renglón donde va este registro en el formulario en papel">Hoja · fila</th>
                            <th>Fecha</th>
                            <th>Actividad desarrollada</th>
                            <th class="num">Horas</th>
                            @if ($esAmbiental)<th>Fotos</th>@endif
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($registros as $r)
                        <tr>
                            <td class="num gris" style="white-space:nowrap">{{ $hojaFila[$r->id] ?? '—' }}</td>
                            <td style="white-space:nowrap">{{ $fecha($r->fecha) }}</td>
                            <td>{{ $r->actividad }}
                                @if ($r->comentario_coordinador)
                                    <br><small style="color:{{ $r->estado === 'rechazado' ? 'var(--mal)' : 'var(--gris)' }}">Coordinador: {{ $r->comentario_coordinador }}</small>
                                @endif
                            </td>
                            <td class="num">{{ $r->horas }}</td>
                            @if ($esAmbiental)
                                <td style="white-space:nowrap">
                                    @forelse ($r->evidencias as $i => $ev)
                                        <a href="{{ route('sse.registro.foto', [$r, $ev]) }}" target="_blank" rel="noopener" title="{{ $ev->nombre_original }}">Foto {{ $i + 1 }}</a>@if (! $loop->last)<br>@endif
                                    @empty
                                        <span class="gris">—</span>
                                    @endforelse
                                </td>
                            @endif
                            <td>
                                <div class="estado-acciones">
                                    <span class="pastilla {{ $estados[$r->estado][0] }}">{{ $estados[$r->estado][1] }}</span>
                                    @if ($r->borrable() && ! $cerrado)
                                        <form method="POST" action="{{ route('sse.registro.borrar', $r) }}"
                                              data-confirmar="¿Eliminar el registro del {{ $fecha($r->fecha) }}?{{ $r->evidencias->isNotEmpty() ? ' También se borran sus fotos.' : '' }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="boton plano chico">Eliminar</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $columnas }}" class="gris">Todavía no hay registros aquí.</td></tr>
                    @endforelse
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3">Total de horas (sin las no aprobadas)</td><td class="num">{{ $total }}</td><td colspan="{{ $columnas - 4 }}"></td></tr>
                    </tfoot>
                </table>
            </div>
            <p class="gris" style="margin-top:10px;font-size:13px">«Hoja · fila» te dice en qué hoja y renglón del formulario en papel va cada registro ({{ config('horas-sociales.filas_por_hoja') }} renglones por hoja, en orden de fecha). Si registras tarde un día anterior, las filas siguientes se corren una.</p>
        </section>
    </div>

    <script>
        // Eliminar: pide confirmación antes de enviar el formulario.
        document.addEventListener('submit', function (ev) {
            var mensaje = ev.target.getAttribute('data-confirmar');
            if (mensaje && ! window.confirm(mensaje)) ev.preventDefault();
        });
    </script>

    @if ($activo)
        @include('horas-sociales::partials.aviso-fecha')
        <script>
            (function () {
                var hoy = @json($hoy);
                var inicio = @json($inicio);
                var horasPorDia = @json((object) $horasPorDia);
                var maxDia = @json($maxDia);
                var maxFotos = @json($maxFotos);
                var maxBytes = @json($maxKb * 1024);
                var mbFoto = @json($mbFoto);
                var largo = sseAvisoFecha.largo;

                var form = document.getElementById('form-registro');
                var fecha = document.getElementById('c-fecha');
                var tipo = document.getElementById('c-tipo');
                var horas = document.getElementById('c-horas');
                var caja = document.getElementById('caja-fotos');
                var fotos = document.getElementById('c-fotos');
                var boton = document.getElementById('enviar-registro');

                // Campos de solo números: se borra al instante cualquier letra o símbolo.
                document.addEventListener('input', function (ev) {
                    if (ev.target.matches('[data-solo-numeros]')) {
                        ev.target.value = ev.target.value.replace(/\D/g, '');
                    }
                });

                // Fecha: R4 (no futura ni antes del inicio) y R5 (día no hábil), con el mismo mensaje del servidor.
                fecha.max = hoy;
                if (inicio) fecha.min = inicio;
                sseAvisoFecha({
                    campo: fecha,
                    periodos: @json($periodos),
                    ayuda: 'No se aceptan asuetos ni vacaciones.',
                    antes: function (f) {
                        if (f > hoy) return 'La fecha no puede ser futura.';
                        if (inicio && f < inicio) return 'Es anterior al inicio de tu proyecto (' + largo(inicio) + ').';
                        return '';
                    },
                });

                // Horas: R3 (enteras, de 1 a 8) y R2 (lo que le queda a ese día).
                var avisoHoras = horas.parentNode.querySelector('small');
                var ayudaHoras = 'Solo horas enteras. Máximo ' + maxDia + ' por día.';
                function revisarHoras() {
                    var f = fecha.value, h = horas.value, ya = horasPorDia[f] || 0, quedan = maxDia - ya;
                    var problema = '', info = '';
                    if (h !== '' && (+h < 1 || +h > maxDia)) {
                        problema = 'Escribe horas enteras, de 1 a ' + maxDia + '.';
                    } else if (f && ya) {
                        var dia = 'El ' + largo(f) + ' ya tiene ' + ya + ' h registradas';
                        if (quedan <= 0) problema = dia + ': es el máximo de ' + maxDia + ' por día.';
                        else if (h !== '' && +h > quedan) problema = dia + '. Puedes agregar hasta ' + quedan + ' h (máximo ' + maxDia + ' por día).';
                        else info = 'Ese día ya tienes ' + ya + ' h; puedes agregar hasta ' + quedan + '.';
                    }
                    horas.setCustomValidity(problema);
                    horas.classList.toggle('con-error', !! problema);
                    avisoHoras.classList.toggle('error', !! problema);
                    avisoHoras.textContent = problema || info || ayudaHoras;
                }
                horas.addEventListener('input', revisarHoras);
                fecha.addEventListener('change', revisarHoras);
                fecha.addEventListener('input', revisarHoras);
                if (! avisoHoras.classList.contains('error')) revisarHoras();

                // Tipo: el campo de fotos solo aparece (y solo se envía) en medio ambiente.
                var avisoFotos = caja.querySelector('small');
                var ayudaFotos = avisoFotos.classList.contains('error') ? @json($ayudaFotos) : avisoFotos.textContent;
                var procesando = false;

                function revisarFotos(mostrar) {
                    var amb = tipo.value === 'ambiental', lista = Array.prototype.slice.call(fotos.files), problema = '';
                    var total = lista.reduce(function (t, a) { return t + a.size; }, 0);
                    if (amb && ! lista.length) problema = 'Sube al menos una foto de evidencia: es obligatoria para las horas de medio ambiente.';
                    else if (lista.length > maxFotos) problema = 'Puedes subir hasta ' + maxFotos + ' fotos por registro.';
                    else if (lista.some(function (a) { return a.size > maxBytes; })) problema = 'Cada foto debe ser JPG, PNG o WEBP de hasta ' + mbFoto + ' MB.';
                    fotos.setCustomValidity(amb ? problema : '');
                    if (! mostrar) return;
                    var mostrarError = !! problema && lista.length > 0;
                    fotos.classList.toggle('con-error', mostrarError);
                    avisoFotos.classList.toggle('error', mostrarError);
                    avisoFotos.textContent = mostrarError ? problema
                        : (lista.length ? lista.length + (lista.length === 1 ? ' foto lista' : ' fotos listas') + ' (' + (total / 1048576).toFixed(1) + ' MB).' : ayudaFotos);
                }

                function cambiarTipo() {
                    var amb = tipo.value === 'ambiental';
                    caja.hidden = ! amb;
                    fotos.disabled = ! amb;
                    revisarFotos(false);
                }
                tipo.addEventListener('change', cambiarTipo);
                cambiarTipo();

                // Fotos: cada una se achica a 1600 px en JPEG antes de enviarla, para no pasar el límite de PHP.
                var MAX_LADO = 1600, CALIDAD = 0.82;
                function achicar(archivo) {
                    return new Promise(function (listo) {
                        if (! /^image\//.test(archivo.type)) return listo(archivo);
                        var url = URL.createObjectURL(archivo), img = new Image();
                        img.onload = function () {
                            var escala = Math.min(1, MAX_LADO / Math.max(img.naturalWidth, img.naturalHeight));
                            if (escala === 1 && archivo.size <= maxBytes && /jpeg|png|webp/.test(archivo.type)) {
                                URL.revokeObjectURL(url);
                                return listo(archivo);
                            }
                            var lienzo = document.createElement('canvas');
                            lienzo.width = Math.round(img.naturalWidth * escala);
                            lienzo.height = Math.round(img.naturalHeight * escala);
                            var ctx = lienzo.getContext('2d');
                            ctx.fillStyle = '#fff';
                            ctx.fillRect(0, 0, lienzo.width, lienzo.height);
                            ctx.drawImage(img, 0, 0, lienzo.width, lienzo.height);
                            URL.revokeObjectURL(url);
                            lienzo.toBlob(function (blob) {
                                if (! blob) return listo(archivo);
                                var nombre = archivo.name.replace(/\.[^.]+$/, '') + '.jpg';
                                listo(new File([blob], nombre, { type: 'image/jpeg', lastModified: Date.now() }));
                            }, 'image/jpeg', CALIDAD);
                        };
                        img.onerror = function () { URL.revokeObjectURL(url); listo(archivo); };
                        img.src = url;
                    });
                }

                fotos.addEventListener('change', function () {
                    var lista = Array.prototype.slice.call(fotos.files);
                    if (! lista.length || lista.length > maxFotos || typeof DataTransfer === 'undefined') {
                        revisarFotos(true);
                        return;
                    }
                    procesando = true;
                    boton.disabled = true;
                    avisoFotos.classList.remove('error');
                    avisoFotos.textContent = 'Preparando las fotos…';
                    Promise.all(lista.map(achicar)).then(function (listas) {
                        var dt = new DataTransfer();
                        listas.forEach(function (a) { dt.items.add(a); });
                        fotos.files = dt.files;
                    }).catch(function () {
                        // Si el navegador no puede achicarlas, se envían como están y el servidor revisa el tamaño.
                    }).then(function () {
                        procesando = false;
                        boton.disabled = false;
                        revisarFotos(true);
                    });
                });

                // Enviar: se desactiva el botón para que un doble clic no mande el registro dos veces.
                form.addEventListener('submit', function (ev) {
                    if (procesando) { ev.preventDefault(); return; }
                    boton.disabled = true;
                    boton.textContent = 'Enviando…';
                });
                // Al volver con el botón "atrás" del navegador, el botón queda activo otra vez.
                window.addEventListener('pageshow', function () {
                    boton.disabled = false;
                    boton.textContent = 'Enviar a revisión';
                });
            })();
        </script>
    @endif
@endsection

@section(config('horas-sociales.seccion', 'content'))
    @include('horas-sociales::partials.estilos')
    @include('horas-sociales::partials.marco', ['actual' => 'sse.registro'])
@endsection
