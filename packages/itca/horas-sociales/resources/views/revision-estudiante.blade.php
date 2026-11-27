@extends(config('horas-sociales.layout') ?: 'horas-sociales::layouts.modulo')

@section('titulo', $estudiante->nombre.' · Revisión')

@section('sse-contenido')
    @php
        $estadosRegistro = ['pendiente' => ['aviso', 'En revisión'], 'aprobado' => ['ok', 'Aprobado'], 'rechazado' => ['mal', 'No aprobado']];
        $estadosHoja = [
            'borrador' => ['', 'Borrador'], 'enviado' => ['aviso', 'Por revisar'],
            'aprobado' => ['ok', 'Aprobado'], 'rechazado' => ['mal', 'No aprobado'],
        ];
        $fecha = fn ($f) => $f ? \Illuminate\Support\Carbon::parse($f)->locale('es')->isoFormat('D MMM YYYY') : '—';
        $momento = fn ($f) => $f ? $f->copy()->setTimezone(config('horas-sociales.zona_horaria'))->locale('es')->isoFormat('D MMM YYYY, h:mm a') : '—';
        $v = fn ($x) => filled($x) ? $x : '—';
        $esAmbiental = $pestana === 'ambiental';
        $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
        $horario = [];
        foreach ($proyecto?->horarios ?? [] as $h) {
            $horario[$h->dia][$h->turno] = $h->horas;
        }
        ksort($horario);
        $pendientesTabla = $registros->where('estado', 'pendiente');
        $total = $registros->where('estado', '!=', 'rechazado')->sum('horas');
    @endphp

    <div>
        <p style="margin-bottom:8px"><a href="{{ route('sse.revision') }}">← Volver a Revisión de horas</a></p>
        <div class="cabecera"><h1>{{ $estudiante->nombre }}</h1><span class="codigo">Carnet {{ $estudiante->carnet }}</span></div>
        <p class="bajada">
            {{ $estudiante->carrera ?: 'Carrera sin indicar' }}@if ($estudiante->escuela) · {{ $estudiante->escuela }}@endif.
            @if ($resumen->meta)
                Su carrera pide <b>{{ $resumen->meta }} horas</b>; al menos {{ $resumen->metaAmbiental }} de medio ambiente.
            @else
                Todavía no indicó si su carrera pide 300 o 500 horas.
            @endif
        </p>
    </div>

    @if (session('sse_aviso'))
        <p class="nota ok" role="status">{{ session('sse_aviso') }}</p>
    @endif
    @if (session('sse_error'))
        <p class="nota mal" role="alert">{{ session('sse_error') }}</p>
    @endif
    @if ($errors->any())
        <p class="nota mal" role="alert">{{ $errors->first() }}</p>
    @endif

    <section class="hoja">
        <div class="cifras">
            <div class="cifra"><b>{{ $resumen->aprobadas }}</b><span>horas aprobadas</span></div>
            <div class="cifra"><b>{{ $resumen->pendientes }}</b><span>horas en revisión</span></div>
            <div class="cifra"><b>{{ $resumen->meta ? $resumen->faltan : '—' }}</b><span>horas que faltan</span></div>
            <div class="cifra"><b>{{ $resumen->ambientalAprobadas }}<span style="font:inherit;color:var(--gris)"> / {{ $resumen->metaAmbiental ?? '—' }}</span></b><span>horas de medio ambiente</span></div>
        </div>
        @if ($resumen->meta)
            <div class="barra" style="margin:18px 0 8px" role="img" aria-label="{{ $resumen->aprobadas }} de {{ $resumen->meta }} horas aprobadas">
                <i class="a" style="width: {{ $resumen->porcentaje($resumen->aprobadas) }}%"></i>
                <i class="p" style="width: {{ $resumen->porcentaje($resumen->pendientes) }}%"></i>
            </div>
        @endif
        <p class="gris" style="font-size:13px;margin-top:6px">Las cifras suman todos sus proyectos.</p>
    </section>

    @if ($otrosProyectos)
        <p class="nota">Tiene {{ $otrosProyectos === 1 ? 'otro proyecto anterior' : $otrosProyectos.' proyectos anteriores' }}. Aquí se muestra el más reciente.</p>
    @endif

    <section class="hoja">
        <header><h2>Datos del estudiante</h2></header>
        <dl class="datos">
            <div><dt>Correo</dt><dd>{{ $v($estudiante->correo) }}</dd></div>
            <div><dt>Teléfono</dt><dd>{{ $v($estudiante->telefono) }}</dd></div>
            <div><dt>Sede</dt><dd>{{ $v($estudiante->sede) }}</dd></div>
            <div><dt>Jornada y modalidad</dt><dd>{{ $v(collect([$estudiante->jornada, $estudiante->modalidad])->filter()->join(' · ')) }}</dd></div>
            <div><dt>Año académico</dt><dd>{{ $v($estudiante->anio_academico) }}</dd></div>
            <div><dt>Año de estudios</dt><dd>{{ $v($estudiante->anio_estudios) }}</dd></div>
            <div><dt>Contacto de emergencia</dt><dd>{{ $v(collect([$estudiante->emergencia_nombre, $estudiante->emergencia_telefono])->filter()->join(' · ')) }}</dd></div>
            <div><dt>Coordinador</dt><dd>{{ $v($estudiante->coordinador) }}</dd></div>
        </dl>
    </section>

    {{-- Proyecto de trabajo --}}
    <section class="hoja" id="proyecto">
        <header>
            <h2>Proyecto de trabajo</h2>
            @if ($proyecto)<span class="pastilla {{ $estadosHoja[$proyecto->estado][0] ?? '' }}">{{ $estadosHoja[$proyecto->estado][1] ?? $proyecto->estado }}</span>@endif
            <span class="codigo">FBSSE-001</span>
        </header>
        @if (! $proyecto)
            <p class="gris">Todavía no ha llenado su proyecto de trabajo.</p>
        @else
            <dl class="datos">
                <div><dt>Institución</dt><dd>{{ $v($proyecto->institucion) }}</dd></div>
                <div><dt>Teléfono de la institución</dt><dd>{{ $v($proyecto->institucion_telefono) }}</dd></div>
                <div><dt>Lugar de ejecución</dt><dd>{{ $v($proyecto->lugar_ejecucion) }}</dd></div>
                <div><dt>Fecha de inicio</dt><dd>{{ $fecha($proyecto->fecha_inicio) }}</dd></div>
                <div><dt>Supervisor</dt><dd>{{ $v($proyecto->supervisor_nombre) }}</dd></div>
                <div><dt>Correo del supervisor</dt><dd>{{ $v($proyecto->supervisor_correo) }}</dd></div>
                <div><dt>Teléfono del supervisor</dt><dd>{{ $v($proyecto->supervisor_telefono) }}</dd></div>
                <div><dt>Tiempo estimado</dt><dd>{{ $v($proyecto->tiempo_estimado) }}</dd></div>
                <div><dt>Enviado</dt><dd>{{ $momento($proyecto->enviado_at) }}</dd></div>
            </dl>

            <h3>Actividades principales</h3>
            <p style="white-space:pre-line">{{ $v($proyecto->actividades_principales) }}</p>

            <h3>Objetivos</h3>
            <div class="desliza">
                <table>
                    <thead><tr><th class="num">#</th><th>Actividad</th><th>Objetivo</th><th class="num">Duración (horas)</th></tr></thead>
                    <tbody>
                    @forelse ($proyecto->objetivos as $o)
                        <tr><td class="num">{{ $loop->iteration }}</td><td>{{ $o->actividad }}</td><td>{{ $o->objetivo }}</td><td class="num">{{ $o->duracion }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="gris">Sin objetivos.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <h3>Horario</h3>
            @if ($horario)
                <div class="desliza">
                    <table style="max-width:520px">
                        <thead><tr><th>Día</th><th>Mañana (AM)</th><th>Tarde (PM)</th></tr></thead>
                        <tbody>
                        @foreach ($horario as $dia => $turnos)
                            <tr><td>{{ $dias[$dia] ?? $dia }}</td><td class="num">{{ $turnos['am'] ?? '—' }}</td><td class="num">{{ $turnos['pm'] ?? '—' }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="gris">Sin horario.</p>
            @endif

            @if ($proyecto->comentario_coordinador)
                <p style="margin-top:14px"><b>Comentario del coordinador:</b> {{ $proyecto->comentario_coordinador }}</p>
            @endif
            @if ($proyecto->estado === 'enviado')
                <div class="fila" style="margin-top:16px">
                    @include('horas-sociales::partials.resolver', ['accion' => route('sse.revision.proyecto', $proyecto)])
                </div>
            @elseif (in_array($proyecto->estado, ['aprobado', 'rechazado'], true) && $proyecto->resuelto_at)
                <p class="gris" style="margin-top:10px">Revisado el {{ $momento($proyecto->resuelto_at) }}.</p>
            @elseif ($proyecto->estado === 'borrador')
                <p class="gris" style="margin-top:10px">El estudiante todavía no lo envía.</p>
            @endif
        @endif
    </section>

    {{-- Registro diario --}}
    <section class="hoja" id="registros">
        <header>
            <h2>Registro diario</h2>
            <span class="codigo">FBSSE-002</span>
            <nav class="pestanas" aria-label="Hojas del registro">
                <a href="{{ route('sse.revision.estudiante', $estudiante) }}#registros" @if (! $esAmbiental) aria-current="page" @endif>Control general</a>
                <a href="{{ route('sse.revision.estudiante', ['estudiante' => $estudiante, 'pestana' => 'ambiental']) }}#registros" @if ($esAmbiental) aria-current="page" @endif>Medio ambiente ({{ config('horas-sociales.porcentaje_ambiental') }} %)</a>
            </nav>
        </header>
        @if ($cerrado)
            <p class="nota aviso" style="margin-bottom:12px">Ya envió su hoja de finalización: las horas ya revisadas no se pueden cambiar. Las que siguen en revisión sí.</p>
        @endif
        @if ($pendientesTabla->isNotEmpty())
            <form id="form-lote" method="POST" action="{{ route('sse.revision.registros') }}" class="fila" style="margin-bottom:10px"
                  data-lote data-lote-confirmar="¿Aprobar los N registros marcados?">
                @csrf
                <button class="boton chico" data-aprobar-lote disabled>Aprobar marcadas</button>
                <span class="gris" style="font-size:13px">Marca las que quieras aprobar juntas.</span>
            </form>
        @endif
        <div class="desliza">
            <table>
                <thead>
                    <tr>
                        <th>@if ($pendientesTabla->isNotEmpty())<input type="checkbox" aria-label="Marcar todas las que están en revisión" data-marcar-todas>@endif</th>
                        <th title="Hoja y renglón del formulario en papel">Hoja · fila</th>
                        <th>Fecha</th>
                        <th>Actividad desarrollada</th>
                        <th class="num">Horas</th>
                        @if ($esAmbiental)<th>Fotos</th>@endif
                        <th>Estado</th>
                        <th>Decisión</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($registros as $r)
                    <tr>
                        <td>
                            @if ($r->estado === 'pendiente')
                                <input type="checkbox" name="ids[]" value="{{ $r->id }}" form="form-lote" data-lote-item aria-label="Marcar el registro del {{ $fecha($r->fecha) }}">
                            @endif
                        </td>
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
                        <td><span class="pastilla {{ $estadosRegistro[$r->estado][0] }}">{{ $estadosRegistro[$r->estado][1] }}</span></td>
                        <td>
                            @if ($r->estado === 'pendiente')
                                @include('horas-sociales::partials.resolver', ['accion' => route('sse.revision.registro', $r)])
                            @elseif ($cerrado)
                                <span class="gris">—</span>
                            @elseif ($r->estado === 'aprobado')
                                {{-- Decisión 3: cambiar una decisión mientras el registro diario no esté cerrado. --}}
                                @include('horas-sociales::partials.resolver', [
                                    'accion' => route('sse.revision.registro', $r), 'aprobar' => false, 'rechazar' => 'Cambiar a no aprobado',
                                    'confirmar' => '¿Cambiar este registro aprobado a no aprobado? Sus horas dejan de contar.',
                                ])
                            @else
                                @include('horas-sociales::partials.resolver', [
                                    'accion' => route('sse.revision.registro', $r), 'aprobar' => 'Cambiar a aprobado', 'rechazar' => false,
                                    'confirmar' => '¿Cambiar este registro a aprobado? Sus horas vuelven a contar.',
                                ])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $esAmbiental ? 8 : 7 }}" class="gris">{{ $proyecto ? 'Todavía no hay registros aquí.' : 'Sin proyecto, no hay registros.' }}</td></tr>
                @endforelse
                </tbody>
                @if ($registros->isNotEmpty())
                    <tfoot>
                        <tr><td colspan="4">Total de horas (sin las no aprobadas)</td><td class="num">{{ $total }}</td><td colspan="{{ $esAmbiental ? 3 : 2 }}"></td></tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </section>

    {{-- Hoja de finalización --}}
    <section class="hoja" id="finalizacion">
        <header>
            <h2>Hoja de finalización</h2>
            @if ($finalizacion)<span class="pastilla {{ $estadosHoja[$finalizacion->estado][0] ?? '' }}">{{ $estadosHoja[$finalizacion->estado][1] ?? $finalizacion->estado }}</span>@endif
            <span class="codigo">FBSSE-003</span>
        </header>
        @if (! $finalizacion || $finalizacion->estado === 'borrador')
            <p class="gris">{{ $finalizacion ? 'La está llenando; todavía no la envía.' : 'Todavía no ha enviado su hoja de finalización.' }}</p>
        @else
            <dl class="datos">
                <div><dt>Fecha de finalización</dt><dd>{{ $fecha($finalizacion->fecha_finalizacion) }}</dd></div>
                <div><dt>Entrega la documentación hasta</dt><dd>{{ $fecha($finalizacion->fechaLimiteEntrega()) }}</dd></div>
                <div><dt>Dirección de la institución</dt><dd>{{ $v($finalizacion->direccion_institucion) }}</dd></div>
                <div><dt>Teléfonos de la institución</dt><dd>{{ $v($finalizacion->telefonos_institucion) }}</dd></div>
                <div><dt>Correo institucional</dt><dd>{{ $v($finalizacion->correo_institucional) }}</dd></div>
                <div><dt>Enviada</dt><dd>{{ $momento($finalizacion->enviado_at) }}</dd></div>
            </dl>
            @if (filled($finalizacion->observaciones))
                <h3>Observaciones</h3>
                <p style="white-space:pre-line">{{ $finalizacion->observaciones }}</p>
            @endif
            @if ($finalizacion->comentario_coordinador)
                <p style="margin-top:14px"><b>Comentario del coordinador:</b> {{ $finalizacion->comentario_coordinador }}</p>
            @endif

            @if ($finalizacion->estado === 'enviado')
                @if ($pendientesProyecto)
                    <p class="nota aviso" style="margin-top:14px"><b>{{ $pendientesProyecto }} h de este proyecto siguen en revisión.</b> Revísalas arriba antes de aprobar la hoja.</p>
                @elseif ($ultimo !== $finalizacion->fecha_finalizacion?->format('Y-m-d'))
                    <p class="nota mal" style="margin-top:14px">La fecha de finalización ya no coincide con el último día con horas ({{ $fecha($ultimo) }}). Devuelve la hoja para que el estudiante la corrija.</p>
                @elseif ($resumen->meta && $resumen->faltan)
                    <p class="nota" style="margin-top:14px">Le faltan {{ $resumen->faltan }} h para la meta de su carrera.</p>
                @endif
                <div class="fila" style="margin-top:16px">
                    @include('horas-sociales::partials.resolver', ['accion' => route('sse.revision.finalizacion', $finalizacion), 'rechazar' => 'Devolver'])
                </div>
            @elseif ($finalizacion->resuelto_at)
                <p class="gris" style="margin-top:10px">Revisada el {{ $momento($finalizacion->resuelto_at) }}.</p>
            @endif
        @endif
    </section>

    @include('horas-sociales::partials.resolver-script')
@endsection

@section(config('horas-sociales.seccion', 'content'))
    @include('horas-sociales::partials.estilos')
    @include('horas-sociales::partials.marco', ['actual' => 'sse.revision'])
@endsection
