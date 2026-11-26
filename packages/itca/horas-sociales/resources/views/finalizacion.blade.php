@extends(config('horas-sociales.layout') ?: 'horas-sociales::layouts.modulo')

@section('titulo', 'Hoja de finalización')

@section('sse-contenido')
    @php
        $estados = [
            'borrador' => ['', 'Borrador'], 'enviado' => ['aviso', 'Enviada, en revisión'],
            'aprobado' => ['ok', 'Aprobada'], 'rechazado' => ['mal', 'Devuelta'],
        ];
        $estado = $finalizacion->estado ?? 'borrador';
        $e = $estudiante;
        $p = $proyecto;
        $f = $finalizacion;
        $o = ['dis' => ! $editable, 'req' => true];
        $lista = fn (array $valores) => array_combine($valores, $valores);
        $Est = \Itca\HorasSociales\Models\Estudiante::class;
        $largo = fn ($fecha) => \Itca\HorasSociales\Support\Fechas::larga($fecha);
        // Fecha que se muestra: lo que se acaba de enviar (si hubo error) o lo guardado.
        $fechaVista = old('fecha_finalizacion', $fechaFin);
        $limite = $fechaVista && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaVista)
            ? \Itca\HorasSociales\Models\Finalizacion::limitePara($fechaVista)->format('Y-m-d')
            : null;
        $ayudaFecha = $ultimo ? 'Tu último registro es del '.$largo($ultimo).'.' : 'Todavía no tienes horas registradas.';
    @endphp

    <div>
        <div class="cabecera"><h1>Hoja de finalización</h1><span class="codigo">FBSSE-003</span>
            <span class="pastilla {{ $estados[$estado][0] }}">{{ $estados[$estado][1] }}</span></div>
        <p class="bajada">Se llena cuando terminas tu servicio social en la institución. Las fechas deben ser las mismas que aparecen en tu proyecto y en tu registro diario.</p>
    </div>

    @if (session('sse_aviso'))
        <p class="nota ok" role="status">{{ session('sse_aviso') }}</p>
    @endif
    @if (session('sse_error'))
        <p class="nota mal" role="alert">{{ session('sse_error') }}</p>
    @endif

    @if (! $puede)
        <p class="nota aviso"><b>Todavía no puedes llenar esta hoja.</b> Primero el coordinador debe aprobar tu <a href="{{ route('sse.proyecto') }}">proyecto de trabajo</a>.</p>
    @elseif ($errors->has('proyecto'))
        <p class="nota mal" role="alert">{{ $errors->first('proyecto') }}</p>
    @endif
    @if ($errors->any() && ! $errors->has('proyecto'))
        <p class="nota mal" role="alert"><b>Revisa los campos marcados en rojo.</b> {{ $errors->count() === 1 ? 'Hay 1 dato' : 'Hay '.$errors->count().' datos' }} por corregir.</p>
    @endif

    @if ($estado === 'enviado')
        <p class="nota aviso">Enviaste esta hoja. El coordinador la está revisando. Tu registro diario de este proyecto quedó cerrado: ya no se agregan ni se borran horas.</p>
    @elseif ($estado === 'aprobado')
        <p class="nota ok">El coordinador aprobó tu hoja de finalización.</p>
    @elseif ($estado === 'rechazado')
        <p class="nota mal"><b>El coordinador te devolvió la hoja.</b> {{ $f->comentario_coordinador ?: 'Corrige y vuelve a enviarla.' }} Tu registro diario se abrió otra vez por si necesitas corregir horas.</p>
    @endif

    @if ($puede && $editable && $pendientes)
        <p class="nota aviso">Tienes <b>{{ $pendientes }} {{ $pendientes === 1 ? 'hora' : 'horas' }} en revisión</b> en este proyecto. Conviene esperar a que el coordinador las revise antes de finalizar: al enviar la hoja ya no podrás borrar registros.</p>
    @endif

    @if ($puede)
        <p class="nota" id="nota-limite" @if (! $limite) hidden @endif>
            {{ $estado === 'borrador' || $estado === 'rechazado' ? 'Si terminas el' : 'Terminaste el' }}
            <span data-fin>{{ $fechaVista && $limite ? $largo($fechaVista) : '' }}</span>, tienes hasta el
            <b data-limite>{{ $limite ? $largo($limite) : '' }}</b> para entregar la documentación al coordinador.
            Después de {{ $meses }} meses el servicio social se anula.
        </p>
    @endif

    @if ($editable)
        <form class="pila" method="POST" action="{{ route('sse.finalizacion.guardar') }}">
        @csrf
    @else
        <div class="pila">
    @endif

    <section class="hoja"><header><h2>Datos del estudiante</h2></header>
        <div class="rejilla">
            @include('horas-sociales::partials.campo', ['name' => 'x_nombre', 'label' => 'Nombre completo', 'valor' => $e->nombre, 'dis' => true, 'cls' => 'ancho'])
            @include('horas-sociales::partials.campo', ['name' => 'x_escuela', 'label' => 'Escuela de ITCA-FEPADE', 'valor' => $e->escuela, 'dis' => true])
            @include('horas-sociales::partials.campo', ['name' => 'sede', 'label' => 'Sede donde estudia', 'valor' => $e->sede, 'opciones' => $lista($Est::SEDES)] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'x_carrera', 'label' => 'Carrera que estudia', 'valor' => $e->carrera, 'dis' => true, 'cls' => 'ancho'])
            @include('horas-sociales::partials.campo', ['name' => 'x_carnet', 'label' => 'Carné N.º', 'valor' => $e->carnet, 'dis' => true])
            @include('horas-sociales::partials.campo', ['name' => 'jornada', 'label' => 'Jornada', 'valor' => $e->jornada, 'opciones' => $lista($Est::JORNADAS)] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'modalidad', 'label' => 'Modalidad de estudio', 'valor' => $e->modalidad, 'opciones' => $lista($Est::MODALIDADES)] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'anio_estudios', 'label' => 'Año de estudios', 'valor' => $e->anio_estudios, 'opciones' => $lista($Est::ANIOS_ESTUDIO)] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'telefono', 'label' => 'Teléfono', 'valor' => $e->telefono, 'numerico' => 8, 'ayuda' => '8 dígitos, sin guion'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'correo', 'label' => 'Correo electrónico', 'valor' => $e->correo, 'tipo' => 'email'] + $o)
        </div>
        <p class="gris" style="margin-top:12px">Nombre, escuela, carrera y carné vienen de tu proyecto de trabajo y no se cambian aquí.</p>
    </section>

    <section class="hoja"><header><h2>Datos de la institución</h2></header>
        <div class="rejilla">
            @include('horas-sociales::partials.campo', ['name' => 'x_institucion', 'label' => 'Nombre completo de la institución', 'valor' => $p?->institucion, 'dis' => true, 'cls' => 'ancho'])
            @include('horas-sociales::partials.campo', ['name' => 'direccion_institucion', 'label' => 'Dirección', 'valor' => $f?->direccion_institucion, 'cls' => 'ancho'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'telefonos_institucion', 'label' => 'Teléfono', 'valor' => $f?->telefonos_institucion ?? $p?->institucion_telefono, 'numerico' => 8, 'ayuda' => '8 dígitos, sin guion'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'correo_institucional', 'label' => 'Correo electrónico institucional', 'valor' => $f?->correo_institucional ?? $p?->supervisor_correo, 'tipo' => 'email'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'x_inicio', 'label' => 'Fecha de inicio', 'valor' => $p?->fecha_inicio, 'tipo' => 'date', 'dis' => true, 'ayuda' => 'Viene de tu proyecto de trabajo.'])
            @include('horas-sociales::partials.campo', ['name' => 'fecha_finalizacion', 'label' => 'Fecha de finalización', 'valor' => $fechaFin, 'tipo' => 'date', 'ayuda' => $ayudaFecha] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'observaciones', 'label' => 'Observaciones (opcional)', 'valor' => $f?->observaciones, 'tipo' => 'area', 'cls' => 'ancho', 'dis' => ! $editable])
        </div>

        <div class="fila" style="margin-top:16px">
            @if ($editable)
                {{-- formnovalidate: el borrador se puede guardar a medias --}}
                <button type="submit" name="accion" value="borrador" class="boton suave" formnovalidate>Guardar borrador</button>
                <button type="submit" name="accion" value="enviar" class="boton">Enviar hoja de finalización</button>
                <span class="gris">Al enviarla se cierra tu registro diario de este proyecto.</span>
                @if ($pendientes)
                    <span class="pastilla aviso">{{ $pendientes }} h en revisión</span>
                @endif
            @elseif ($puede)
                <span class="gris">Enviada al coordinador. La firma y el sello van en la hoja impresa.</span>
            @endif
        </div>
    </section>

    @if ($editable)
        </form>
    @else
        </div>
    @endif

    @if ($editable)
        <script>
            (function () {
                var ultimo = @json($ultimo);
                var meses = @json($meses);
                var campo = document.getElementById('c-fecha_finalizacion');
                var nota = document.getElementById('nota-limite');
                var aviso = campo.parentNode.querySelector('small');
                var ayuda = @json($ayudaFecha);

                function largo(f) {
                    var p = f.split('-');
                    return new Date(+p[0], p[1] - 1, +p[2]).toLocaleDateString('es', { day: 'numeric', month: 'long', year: 'numeric' });
                }

                // R9: fecha + 6 meses, sin desborde (31 de agosto -> 28 de febrero), igual que el servidor.
                function limite(f) {
                    var p = f.split('-'), anio = +p[0], mes = p[1] - 1 + meses, dia = +p[2];
                    anio += Math.floor(mes / 12);
                    mes = mes % 12;
                    var ultimoDelMes = new Date(anio, mes + 1, 0).getDate();
                    var d = new Date(anio, mes, Math.min(dia, ultimoDelMes));
                    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
                }

                // R8 en pantalla: el mismo mensaje que da el servidor al enviar.
                function revisar() {
                    var f = campo.value;
                    var problema = '';
                    if (f && ! ultimo) problema = @json(\Itca\HorasSociales\Http\Controllers\FinalizacionController::MENSAJE_SIN_REGISTROS);
                    else if (f && f !== ultimo) problema = 'La fecha de finalización debe coincidir con tu último día registrado (' + largo(ultimo) + ').';

                    campo.setCustomValidity(problema);
                    campo.classList.toggle('con-error', !! problema);
                    if (aviso) {
                        aviso.classList.toggle('error', !! problema);
                        aviso.style.color = problema ? '' : (f ? 'var(--ok)' : '');
                        aviso.textContent = problema || (f ? 'Coincide con tu último día registrado.' : ayuda);
                    }

                    if (nota) {
                        nota.hidden = ! f;
                        if (f) {
                            nota.querySelector('[data-fin]').textContent = largo(f);
                            nota.querySelector('[data-limite]').textContent = largo(limite(f));
                        }
                    }
                }

                campo.addEventListener('input', revisar);
                campo.addEventListener('change', revisar);
                // Si el servidor ya marcó un error, se deja a la vista hasta que cambie la fecha.
                if (campo.value && ! (aviso && aviso.classList.contains('error'))) revisar();
            })();

            // Teléfonos: se borra al instante cualquier letra o símbolo.
            document.addEventListener('input', function (ev) {
                if (ev.target.matches('[data-solo-numeros]')) {
                    ev.target.value = ev.target.value.replace(/\D/g, '');
                }
            });
        </script>
    @endif
@endsection

@section(config('horas-sociales.seccion', 'content'))
    @include('horas-sociales::partials.estilos')
    @include('horas-sociales::partials.marco', ['actual' => 'sse.finalizacion'])
@endsection
