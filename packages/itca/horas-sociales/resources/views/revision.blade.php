@extends(config('horas-sociales.layout') ?: 'horas-sociales::layouts.modulo')

@section('titulo', 'Revisión de horas')

@section('sse-contenido')
    @php
        $estadosProyecto = [
            'borrador' => ['', 'Borrador'], 'enviado' => ['aviso', 'Por aprobar'],
            'aprobado' => ['ok', 'Aprobado'], 'rechazado' => ['mal', 'No aprobado'],
        ];
        $fecha = fn ($f) => $f ? \Illuminate\Support\Carbon::parse($f)->locale('es')->isoFormat('D MMM YYYY') : '—';
        $ficha = fn ($e) => route('sse.revision.estudiante', $e);
        $horasPendientes = $registros->sum('horas');
    @endphp

    <div>
        <div class="cabecera"><h1>Revisión de horas</h1><span class="codigo">Vista del profesor</span></div>
        <p class="bajada">Aprueba o no aprueba lo que envían los estudiantes. Si no apruebas algo, escribe un comentario: el estudiante lo ve en su pantalla. La firma y el sello siguen en las hojas de papel.</p>
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

    {{-- Proyectos de trabajo enviados --}}
    <section class="hoja" id="proyectos">
        <header>
            <h2>Proyectos de trabajo por aprobar</h2>
            <span class="pastilla {{ $proyectos->isNotEmpty() ? 'aviso' : '' }}">{{ $proyectos->count() }}</span>
            <span class="codigo">FBSSE-001</span>
        </header>
        @forelse ($proyectos as $p)
            <div class="fila revision-item">
                <div style="flex:1 1 280px">
                    <b><a href="{{ $ficha($p->estudiante) }}">{{ $p->estudiante->nombre }}</a></b>
                    <span class="gris">· carnet {{ $p->estudiante->carnet }}</span><br>
                    {{ $p->institucion ?: 'Institución sin nombre' }}
                    <span class="gris">· supervisa {{ $p->supervisor_nombre ?: '—' }} · inicia {{ $fecha($p->fecha_inicio) }}</span><br>
                    <a href="{{ $ficha($p->estudiante) }}#proyecto">Ver proyecto completo</a>
                </div>
                @include('horas-sociales::partials.resolver', ['accion' => route('sse.revision.proyecto', $p)])
            </div>
        @empty
            <p class="gris">No hay proyectos esperando aprobación.</p>
        @endforelse
    </section>

    {{-- Registros pendientes --}}
    <section class="hoja" id="horas">
        <header>
            <h2>Horas por revisar</h2>
            <span class="pastilla {{ $registros->isNotEmpty() ? 'aviso' : '' }}">{{ $registros->count() }}</span>
            @if ($horasPendientes)<span class="gris">{{ $horasPendientes }} h en total</span>@endif
            <span class="codigo">FBSSE-002</span>
        </header>
        @if ($registros->isNotEmpty())
            <form id="form-lote" method="POST" action="{{ route('sse.revision.registros') }}" class="fila" style="margin-bottom:10px"
                  data-lote data-lote-confirmar="¿Aprobar los N registros marcados?">
                @csrf
                <button class="boton chico" data-aprobar-lote disabled>Aprobar marcadas</button>
                <span class="gris" style="font-size:13px">Marca las que quieras aprobar juntas. Para no aprobar, hazlo una por una con su comentario.</span>
            </form>
        @endif
        <div class="desliza">
            <table>
                <thead>
                    <tr>
                        <th>@if ($registros->isNotEmpty())<input type="checkbox" aria-label="Marcar todas" data-marcar-todas>@endif</th>
                        <th>Estudiante</th>
                        <th>Fecha</th>
                        <th>Actividad desarrollada</th>
                        <th>Tipo</th>
                        <th class="num">Horas</th>
                        <th>Fotos</th>
                        <th>Decisión</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($registros as $r)
                    <tr>
                        <td><input type="checkbox" name="ids[]" value="{{ $r->id }}" form="form-lote" data-lote-item
                                   aria-label="Marcar el registro de {{ $r->estudiante->nombre }} del {{ $fecha($r->fecha) }}"></td>
                        <td><a href="{{ $ficha($r->estudiante) }}">{{ $r->estudiante->nombre }}</a><br><span class="gris num">{{ $r->estudiante->carnet }}</span></td>
                        <td style="white-space:nowrap">{{ $fecha($r->fecha) }}</td>
                        <td>{{ $r->actividad }}</td>
                        <td>{{ $r->tipo === 'ambiental' ? 'Medio ambiente' : 'General' }}</td>
                        <td class="num">{{ $r->horas }}</td>
                        <td style="white-space:nowrap">
                            @forelse ($r->evidencias as $i => $ev)
                                <a href="{{ route('sse.registro.foto', [$r, $ev]) }}" target="_blank" rel="noopener" title="{{ $ev->nombre_original }}">Foto {{ $i + 1 }}</a>@if (! $loop->last)<br>@endif
                            @empty
                                <span class="gris">—</span>
                            @endforelse
                        </td>
                        <td>@include('horas-sociales::partials.resolver', ['accion' => route('sse.revision.registro', $r)])</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="gris">No hay horas esperando revisión.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Hojas de finalización enviadas --}}
    <section class="hoja" id="finalizaciones">
        <header>
            <h2>Hojas de finalización por revisar</h2>
            <span class="pastilla {{ $finalizaciones->isNotEmpty() ? 'aviso' : '' }}">{{ $finalizaciones->count() }}</span>
            <span class="codigo">FBSSE-003</span>
        </header>
        @forelse ($finalizaciones as $f)
            @php
                $e = $f->proyecto->estudiante;
                $x = \Itca\HorasSociales\Support\Resumen::de($e);
                $pend = $pendientesPorProyecto[$f->proyecto_id] ?? 0;
            @endphp
            <div class="fila revision-item">
                <div style="flex:1 1 280px">
                    <b><a href="{{ $ficha($e) }}#finalizacion">{{ $e->nombre }}</a></b>
                    <span class="gris">· carnet {{ $e->carnet }}</span><br>
                    {{ $f->proyecto->institucion ?: 'Institución sin nombre' }}
                    <span class="gris">· terminó el {{ $fecha($f->fecha_finalizacion) }} · entrega la documentación hasta el {{ $fecha($f->fechaLimiteEntrega()) }}</span><br>
                    <span class="gris">Horas aprobadas: {{ $x->aprobadas }} de {{ $x->meta ?? '—' }}.</span>
                    @if ($x->meta && $x->faltan)<span class="gris"> Le faltan {{ $x->faltan }} para la meta.</span>@endif
                    @if ($pend)
                        <br><span style="color:var(--aviso)"><b>{{ $pend }} h de este proyecto siguen en revisión.</b> Revísalas antes de aprobar la hoja.</span>
                    @endif
                </div>
                @include('horas-sociales::partials.resolver', ['accion' => route('sse.revision.finalizacion', $f), 'rechazar' => 'Devolver'])
            </div>
        @empty
            <p class="gris">No hay hojas de finalización esperando revisión.</p>
        @endforelse
    </section>

    {{-- Avance por estudiante (R12) --}}
    <section class="hoja" id="estudiantes">
        <header>
            <h2>Estudiantes</h2>
            <span class="gris">{{ $estudiantes->total() }}</span>
        </header>
        <form method="GET" action="{{ route('sse.revision') }}#estudiantes" class="fila buscar" role="search" style="margin-bottom:12px">
            <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Nombre o carnet" aria-label="Buscar estudiante por nombre o carnet" style="flex:1 1 200px;width:auto">
            <button class="boton plano chico">Buscar</button>
            @if ($buscar !== '')<a href="{{ route('sse.revision') }}#estudiantes">Ver todos</a>@endif
        </form>
        <div class="desliza">
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Carnet</th>
                        <th>Proyecto</th>
                        <th class="num">Meta</th>
                        <th class="num">Aprobadas</th>
                        <th class="num">En revisión</th>
                        <th class="num">Faltan</th>
                        <th class="num">Medio ambiente</th>
                        <th style="min-width:120px">Avance</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($estudiantes as $e)
                    @php
                        $x = $resumenes[$e->id];
                        $p = $e->proyectos->first();
                    @endphp
                    <tr>
                        <td><a href="{{ $ficha($e) }}">{{ $e->nombre }}</a></td>
                        <td class="num">{{ $e->carnet }}</td>
                        <td>
                            @if ($p)
                                <span class="pastilla {{ $estadosProyecto[$p->estado][0] ?? '' }}">{{ $estadosProyecto[$p->estado][1] ?? $p->estado }}</span>
                            @else
                                <span class="gris">Sin proyecto</span>
                            @endif
                        </td>
                        <td class="num">{{ $x->meta ?? '—' }}</td>
                        <td class="num">{{ $x->aprobadas }}</td>
                        <td class="num">{{ $x->pendientes }}</td>
                        <td class="num">{{ $x->meta ? $x->faltan : '—' }}</td>
                        <td class="num">{{ $x->ambientalAprobadas }} / {{ $x->metaAmbiental ?? '—' }}</td>
                        <td>
                            @if ($x->meta)
                                <div class="barra" style="height:10px;margin-top:5px" role="img" aria-label="{{ $x->aprobadas }} de {{ $x->meta }} horas aprobadas">
                                    <i class="{{ $x->faltan ? 'a' : 'v' }}" style="width: {{ $x->porcentaje($x->aprobadas) }}%"></i>
                                </div>
                            @else
                                <span class="gris">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="gris">
                        {{ $buscar !== '' ? 'Ningún estudiante coincide con «'.$buscar.'».' : 'Todavía no hay estudiantes con horas sociales.' }}
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($estudiantes->lastPage() > 1)
            <div class="fila paginas">
                @if ($estudiantes->onFirstPage())<span class="gris">← Anterior</span>@else<a href="{{ $estudiantes->previousPageUrl() }}#estudiantes">← Anterior</a>@endif
                <span class="gris">Página {{ $estudiantes->currentPage() }} de {{ $estudiantes->lastPage() }}</span>
                @if ($estudiantes->hasMorePages())<a href="{{ $estudiantes->nextPageUrl() }}#estudiantes">Siguiente →</a>@else<span class="gris">Siguiente →</span>@endif
            </div>
        @endif
    </section>

    @include('horas-sociales::partials.resolver-script')
@endsection

@section(config('horas-sociales.seccion', 'content'))
    @include('horas-sociales::partials.estilos')
    @include('horas-sociales::partials.marco', ['actual' => 'sse.revision'])
@endsection
