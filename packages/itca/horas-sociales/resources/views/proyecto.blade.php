@extends(config('horas-sociales.layout') ?: 'horas-sociales::layouts.modulo')

@section('titulo', 'Proyecto de trabajo')

@section('sse-contenido')
    @php
        $estados = [
            'pendiente' => ['aviso', 'En revisión'], 'aprobado' => ['ok', 'Aprobado'], 'rechazado' => ['mal', 'No aprobado'],
            'borrador' => ['', 'Borrador'], 'enviado' => ['aviso', 'Enviado, en revisión'],
        ];
        $estado = $proyecto->estado ?? 'borrador';
        $e = $estudiante;
        $p = $proyecto;
        // Opciones comunes de cada campo: deshabilitado si no es editable (R10) y obligatorio al enviar.
        $o = ['dis' => ! $editable, 'req' => true];
        $lista = fn (array $valores) => array_combine($valores, $valores);
        $tiposCarrera = [
            \Itca\HorasSociales\Models\Estudiante::TECNICO => 'Técnico (2 años) · '.config('horas-sociales.metas.tecnico').' horas',
            \Itca\HorasSociales\Models\Estudiante::INGENIERIA => 'Ingeniería o carrera de 5 años · '.config('horas-sociales.metas.ingenieria').' horas',
        ];

        // Filas de objetivos: lo que se acaba de enviar (si hubo error) o lo guardado. Al editar, al menos 2 filas.
        $filas = old('objetivos', $p ? $p->objetivos->map->only(['actividad', 'objetivo', 'duracion'])->all() : []);
        if ($editable) {
            while (count($filas) < 2) {
                $filas[] = [];
            }
        }
        if (! $filas) {
            $filas = [[]];
        }
    @endphp

    <div>
        <div class="cabecera"><h1>Proyecto de trabajo</h1><span class="codigo">FBSSE-001</span>
            <span class="pastilla {{ $estados[$estado][0] }}">{{ $estados[$estado][1] }}</span></div>
        <p class="bajada">Es lo primero que se llena. El coordinador debe aprobarlo antes de que empieces tu servicio social.</p>
    </div>

    @if (session('sse_aviso'))
        <p class="nota ok" role="status">{{ session('sse_aviso') }}</p>
    @endif
    @if (session('sse_error'))
        <p class="nota mal" role="alert">{{ session('sse_error') }}</p>
    @endif
    @if ($errors->any())
        <p class="nota mal" role="alert"><b>Revisa los campos marcados en rojo.</b> {{ $errors->count() === 1 ? 'Hay 1 dato' : 'Hay '.$errors->count().' datos' }} por corregir.</p>
    @endif

    @if ($estado === 'enviado')
        <p class="nota aviso">Enviaste este proyecto. El coordinador lo está revisando.</p>
    @elseif ($estado === 'aprobado')
        <p class="nota ok">El coordinador aprobó este proyecto. Ya puedes registrar horas.</p>
    @elseif ($estado === 'rechazado')
        <p class="nota mal"><b>No aprobado.</b> {{ $p->comentario_coordinador ?: 'Corrige y vuelve a enviar.' }}</p>
    @endif

    @if ($editable)
        <form class="pila" method="POST" action="{{ route('sse.proyecto.guardar') }}">
        @csrf
    @else
        <div class="pila">
    @endif

    <section class="hoja"><header><h2>1. Datos generales</h2></header>
        <div class="rejilla">
            @include('horas-sociales::partials.campo', ['name' => 'nombre', 'label' => 'Nombre completo del estudiante', 'valor' => $e->nombre, 'cls' => 'ancho'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'carnet', 'label' => 'N.º de carnet', 'valor' => $e->carnet, 'dis' => true])
            @include('horas-sociales::partials.campo', ['name' => 'anio_academico', 'label' => 'Año académico', 'valor' => $e->anio_academico, 'numerico' => 4, 'ayuda' => 'Por ejemplo: 2026'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'escuela', 'label' => 'Escuela', 'valor' => $e->escuela] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'sede', 'label' => 'Sede de ITCA', 'valor' => $e->sede, 'opciones' => $lista(\Itca\HorasSociales\Models\Estudiante::SEDES)] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'carrera', 'label' => 'Carrera', 'valor' => $e->carrera, 'cls' => 'ancho'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'tipo_carrera', 'label' => 'Tipo de carrera', 'valor' => $e->tipo_carrera, 'opciones' => $tiposCarrera, 'ayuda' => 'Define cuántas horas de servicio social te pide tu carrera.'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'anio_estudios', 'label' => 'Año de estudios', 'valor' => $e->anio_estudios, 'opciones' => $lista(\Itca\HorasSociales\Models\Estudiante::ANIOS_ESTUDIO)] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'jornada', 'label' => 'Jornada', 'valor' => $e->jornada, 'opciones' => $lista(\Itca\HorasSociales\Models\Estudiante::JORNADAS)] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'modalidad', 'label' => 'Modalidad', 'valor' => $e->modalidad, 'opciones' => $lista(\Itca\HorasSociales\Models\Estudiante::MODALIDADES)] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'correo', 'label' => 'Correo electrónico', 'valor' => $e->correo, 'tipo' => 'email'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'telefono', 'label' => 'Teléfono de contacto', 'valor' => $e->telefono, 'numerico' => 8, 'ayuda' => '8 dígitos, sin guion'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'emergencia_nombre', 'label' => 'En caso de emergencia contactar a', 'valor' => $e->emergencia_nombre] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'emergencia_telefono', 'label' => 'Teléfono de emergencia', 'valor' => $e->emergencia_telefono, 'numerico' => 8, 'ayuda' => '8 dígitos, sin guion'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'coordinador', 'label' => 'Coordinador de Servicio Social Estudiantil', 'valor' => $e->coordinador, 'cls' => 'ancho'] + $o)
        </div>
    </section>

    <section class="hoja"><header><h2>2. Institución donde harás tu servicio social</h2></header>
        <div class="rejilla">
            @include('horas-sociales::partials.campo', ['name' => 'institucion', 'label' => 'Nombre de la institución', 'valor' => $p?->institucion] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'institucion_telefono', 'label' => 'Teléfono', 'valor' => $p?->institucion_telefono, 'numerico' => 8, 'ayuda' => '8 dígitos, sin guion'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'actividades_principales', 'label' => 'Principales actividades a realizar', 'valor' => $p?->actividades_principales, 'tipo' => 'area', 'cls' => 'ancho'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'supervisor_nombre', 'label' => 'Persona responsable de supervisarte', 'valor' => $p?->supervisor_nombre, 'cls' => 'ancho'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'supervisor_correo', 'label' => 'Correo de la persona responsable', 'valor' => $p?->supervisor_correo, 'tipo' => 'email'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'supervisor_telefono', 'label' => 'Teléfono de contacto', 'valor' => $p?->supervisor_telefono, 'numerico' => 8, 'ayuda' => '8 dígitos, sin guion'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'tiempo_estimado', 'label' => 'Tiempo estimado de las actividades', 'valor' => $p?->tiempo_estimado, 'ayuda' => 'Por ejemplo: 5 meses'] + $o)
            @include('horas-sociales::partials.campo', ['name' => 'fecha_inicio', 'label' => 'Fecha de inicio del servicio social', 'valor' => $p?->fecha_inicio, 'tipo' => 'date', 'ayuda' => 'No puede ser asueto ni vacaciones.'] + $o)
        </div>
    </section>

    <section class="hoja"><header><h2>3. Objetivos de las actividades</h2></header>
        @error('objetivos')<p class="nota mal" style="margin-bottom:12px">{{ $message }}</p>@enderror
        <div class="desliza">
            <table>
                <thead><tr><th>N.º</th><th>Actividad principal</th><th>Objetivo</th><th>Duración (horas)</th></tr></thead>
                <tbody id="filas-objetivos">
                @foreach ($filas as $i => $fila)
                    <tr>
                        <td class="num">{{ $i + 1 }}</td>
                        @foreach (['actividad' => [200, 'Actividad'], 'objetivo' => [200, 'Objetivo'], 'duracion' => [90, 'Duración en horas']] as $col => [$ancho, $nombreCol])
                            <td>
                                @if ($col === 'duracion')
                                    {{-- Solo números: "160 horas" guardado antes se muestra como 160 --}}
                                    <input name="objetivos[{{ $i }}][{{ $col }}]" aria-label="{{ $nombreCol }} {{ $i + 1 }}"
                                           value="{{ preg_replace('/\D/', '', (string) ($fila[$col] ?? '')) }}" style="min-width:{{ $ancho }}px"
                                           type="text" inputmode="numeric" pattern="[0-9]*" maxlength="4" data-solo-numeros placeholder="Ej.: 160"
                                           @disabled(! $editable) @class(['con-error' => $errors->has("objetivos.$i.$col")])>
                                @else
                                    <input name="objetivos[{{ $i }}][{{ $col }}]" aria-label="{{ $nombreCol }} {{ $i + 1 }}"
                                           value="{{ $fila[$col] ?? '' }}" style="min-width:{{ $ancho }}px"
                                           @disabled(! $editable) @class(['con-error' => $errors->has("objetivos.$i.$col")])>
                                @endif
                                @error("objetivos.$i.$col")<small class="error" style="display:block;color:var(--mal)">{{ $message }}</small>@enderror
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if ($editable)
            <div class="fila" style="margin-top:12px">
                <button type="button" class="boton plano chico" id="mas-objetivo">Agregar otra actividad</button>
                <span class="gris">Las filas vacías no se guardan.</span>
            </div>
        @endif
    </section>

    <section class="hoja"><header><h2>4. Horario</h2></header>
        <p class="gris" style="margin-bottom:12px">Escribe las horas en que asistirás, solo con números: al escribir <b>08001200</b> queda <b>08:00 a 12:00</b>. Cada fila usa reloj de 12 horas (en la fila PM, 01:00 a 05:00 es de 1 a 5 de la tarde). No puede coincidir con tus clases ni pasar de 8 horas por día. Deja vacío lo que no uses.</p>
        @if (collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'horario.')))
            <p class="nota mal" style="margin-bottom:12px">Revisa las celdas marcadas en rojo: cada una debe quedar como <b>08:00 a 12:00</b>, con horas de 00 a 12 y minutos de 00 a 59.</p>
        @endif
        <div class="desliza">
            <table class="horario">
                <thead><tr><th>Hora / Día</th>@foreach (\Itca\HorasSociales\Models\ProyectoHorario::DIAS as $nombreDia)<th>{{ $nombreDia }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach (\Itca\HorasSociales\Models\ProyectoHorario::TURNOS as $turno => $nombreTurno)
                    <tr><th>{{ $nombreTurno }}</th>
                        @foreach (\Itca\HorasSociales\Models\ProyectoHorario::DIAS as $dia => $nombreDia)
                            @php
                                // "8:00 a 12:00" guardado antes se muestra como "08:00 a 12:00"
                                $celda = preg_replace('/\b(\d):/', '0$1:', (string) old("horario.$dia.$turno", $horario[$dia][$turno] ?? ''));
                            @endphp
                            <td><input name="horario[{{ $dia }}][{{ $turno }}]" aria-label="{{ $nombreDia }} {{ $nombreTurno }}"
                                       value="{{ $celda }}" placeholder="00:00 a 00:00" data-horario
                                       inputmode="numeric" maxlength="13" pattern="(0[0-9]|1[0-2]):[0-5][0-9] a (0[0-9]|1[0-2]):[0-5][0-9]"
                                       title="Formato: 08:00 a 12:00"
                                       @disabled(! $editable) @class(['con-error' => $errors->has("horario.$dia.$turno")])></td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="rejilla" style="margin-top:16px">
            @include('horas-sociales::partials.campo', ['name' => 'lugar_ejecucion', 'label' => 'Lugar donde se ejecutará el proyecto', 'valor' => $p?->lugar_ejecucion, 'cls' => 'ancho'] + $o)
            <div class="ancho">
                <label class="fila" style="align-items:flex-start;flex-wrap:nowrap">
                    <input type="hidden" name="declaracion_aceptada" value="0" @disabled(! $editable)>
                    <input type="checkbox" name="declaracion_aceptada" value="1" id="c-declaracion_aceptada" style="margin-top:4px"
                           @checked(old('declaracion_aceptada', $p?->declaracion_aceptada)) @disabled(! $editable) required>
                    <span>Doy fe de que he leído las reglas y recomendaciones de la charla informativa de Servicio Social Estudiantil, y me comprometo a realizar las actividades de este proyecto y a reportar cualquier anomalía a mi coordinador.</span>
                </label>
                @error('declaracion_aceptada')<small class="error" style="color:var(--mal);font-weight:500">{{ $message }}</small>@enderror
            </div>
        </div>
    </section>

    <section class="hoja"><header><h2>5. Resolución del coordinador</h2>
            <span class="pastilla {{ $estados[$estado][0] }}">{{ $estados[$estado][1] }}</span></header>
        <p class="gris">La marca el coordinador desde su aplicación. La firma y el sello se siguen poniendo a mano en la hoja impresa.</p>
        @if ($p?->comentario_coordinador && $estado !== 'rechazado')
            <p style="margin-top:10px"><b>Comentario:</b> {{ $p->comentario_coordinador }}</p>
        @endif
        <div class="fila" style="margin-top:14px">
            @if ($editable)
                {{-- formnovalidate: el borrador se puede guardar a medias --}}
                <button type="submit" name="accion" value="borrador" class="boton suave" formnovalidate>Guardar borrador</button>
                <button type="submit" name="accion" value="enviar" class="boton">Enviar al coordinador</button>
            @else
                <form method="POST" action="{{ route('sse.proyecto.reabrir') }}"
                      @if ($estado === 'aprobado') onsubmit="return confirm('Tu proyecto ya está aprobado. Si lo editas, no podrás registrar horas hasta que el coordinador lo apruebe otra vez. ¿Continuar?')" @endif>
                    @csrf
                    <button class="boton plano">Editar y volver a enviar</button>
                </form>
                <span class="gris">Si lo editas, necesita una nueva aprobación.</span>
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
            // Fecha de inicio: avisa al momento si cae en asueto o vacaciones (R5),
            // con el mismo mensaje que da el servidor al enviar.
            (function () {
                var periodos = @json($periodos);
                var primeraHora = @json($primeraHora ? substr($primeraHora, 0, 10) : null);
                var campo = document.getElementById('c-fecha_inicio');
                if (! campo) return;
                var aviso = campo.parentNode.querySelector('small');
                if (! aviso) {
                    aviso = document.createElement('small');
                    campo.parentNode.appendChild(aviso);
                }
                var ayuda = aviso.classList.contains('error') ? 'No puede ser asueto ni vacaciones.' : aviso.textContent;
                aviso.setAttribute('aria-live', 'polite');

                function largo(fecha) {
                    var p = fecha.split('-');
                    return new Date(+p[0], p[1] - 1, +p[2]).toLocaleDateString('es', { day: 'numeric', month: 'long', year: 'numeric' });
                }
                function revisar() {
                    var f = campo.value;   // AAAA-MM-DD
                    var problema = '';
                    if (f) {
                        // Si cae en varios periodos, se nombra el más corto (Viernes Santo antes que Semana Santa)
                        var hallado = null;
                        periodos.forEach(function (p) {
                            var dura = function (x) { return Date.parse(x[2]) - Date.parse(x[1]); };
                            if (p[1] <= f && f <= p[2] && (! hallado || dura(p) < dura(hallado))) {
                                hallado = p;
                            }
                        });
                        if (hallado) {
                            problema = 'El ' + largo(f) + ' es día no hábil (' + hallado[0] + '). Elige otra fecha.';
                        } else if (primeraHora && f > primeraHora) {
                            problema = 'Ya tienes horas registradas desde el ' + largo(primeraHora) + '. La fecha de inicio no puede ser posterior.';
                        }
                    }
                    campo.setCustomValidity(problema);
                    campo.classList.toggle('con-error', !! problema);
                    aviso.classList.toggle('error', !! problema);
                    aviso.style.color = problema ? '' : (f ? 'var(--ok)' : '');
                    aviso.textContent = problema || (f ? 'Fecha permitida: es día hábil.' : ayuda);
                }
                campo.addEventListener('input', revisar);
                campo.addEventListener('change', revisar);
                if (campo.value && ! aviso.classList.contains('error')) revisar();
            })();

            // Campos de solo números (teléfonos, año, duración): se borra al instante cualquier letra o símbolo.
            document.addEventListener('input', function (ev) {
                if (ev.target.matches('[data-solo-numeros]')) {
                    ev.target.value = ev.target.value.replace(/\D/g, '');
                }
            });

            // Horario: solo se teclean números y el campo pone solo ":" y " a ".
            // 08001200 -> 08:00 a 12:00
            document.addEventListener('input', function (ev) {
                if (! ev.target.matches('[data-horario]')) return;
                var d = ev.target.value.replace(/\D/g, '').slice(0, 8);
                var t = d.slice(0, 2);
                if (d.length > 2) t += ':' + d.slice(2, 4);
                if (d.length > 4) t += ' a ' + d.slice(4, 6);
                if (d.length > 6) t += ':' + d.slice(6, 8);
                ev.target.value = t;
            });

            // "Agregar otra actividad": copia una fila vacía con el siguiente número.
            document.getElementById('mas-objetivo').addEventListener('click', function () {
                var cuerpo = document.getElementById('filas-objetivos');
                var i = cuerpo.rows.length;
                if (i >= 10) { this.disabled = true; return; }
                var fila = cuerpo.rows[0].cloneNode(true);
                fila.cells[0].textContent = i + 1;
                fila.querySelectorAll('small').forEach(function (s) { s.remove(); });
                fila.querySelectorAll('input').forEach(function (inp) {
                    inp.value = '';
                    inp.classList.remove('con-error');
                    inp.name = inp.name.replace(/objetivos\[\d+\]/, 'objetivos[' + i + ']');
                    inp.setAttribute('aria-label', inp.getAttribute('aria-label').replace(/\d+$/, i + 1));
                });
                cuerpo.appendChild(fila);
                fila.querySelector('input').focus();
            });
        </script>
    @endif
@endsection

@section(config('horas-sociales.seccion', 'content'))
    @include('horas-sociales::partials.estilos')
    @include('horas-sociales::partials.marco', ['actual' => 'sse.proyecto'])
@endsection
