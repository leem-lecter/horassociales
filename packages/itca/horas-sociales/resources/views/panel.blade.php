@extends(config('horas-sociales.layout') ?: 'horas-sociales::layouts.modulo')

@section('titulo', 'Mi avance')

@section('sse-contenido')
    @php
        $estados = [
            'pendiente' => ['aviso', 'En revisión'], 'aprobado' => ['ok', 'Aprobado'], 'rechazado' => ['mal', 'No aprobado'],
            'borrador' => ['', 'Borrador'], 'enviado' => ['aviso', 'Enviado, en revisión'],
        ];
        $fecha = fn ($f) => $f ? $f->locale('es')->isoFormat('D MMM YYYY') : '—';
    @endphp

    <div>
        <div class="cabecera"><h1>Mi avance</h1></div>
        @if ($resumen->meta)
            <p class="bajada">{{ $estudiante->carrera }}. Tu carrera pide <b>{{ $resumen->meta }} horas</b> de servicio social; al menos {{ $resumen->metaAmbiental }} deben ser en proyectos de medio ambiente.</p>
        @else
            <p class="bajada">Todavía no sabemos si tu carrera pide 300 o 500 horas. Complétalo en tu proyecto de trabajo.</p>
        @endif
    </div>

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
            <div class="leyenda">
                <span><i style="background:var(--azul)"></i>Aprobadas</span>
                <span><i style="background:repeating-linear-gradient(135deg,var(--azul) 0 3px,var(--azul-suave) 3px 6px)"></i>En revisión</span>
                <span><i style="background:var(--papel);border:1px solid var(--linea)"></i>Faltan</span>
            </div>
        @endif
    </section>

    @if (! $proyecto || $proyecto->estado !== 'aprobado')
        <p class="nota aviso"><b>Tu proyecto de trabajo aún no está aprobado.</b> No puedes registrar horas hasta que el coordinador lo autorice.</p>
    @endif
    @if ($noAprobados)
        <p class="nota mal"><b>{{ $noAprobados }} {{ $noAprobados > 1 ? 'registros no aprobados' : 'registro no aprobado' }}.</b> Revisa el comentario del coordinador en tu registro diario.</p>
    @endif
    @if ($resumen->faltaAmbiental())
        <p class="nota">Te faltan <b>{{ $resumen->faltaAmbiental() }} horas de medio ambiente</b> para llegar al {{ config('horas-sociales.porcentaje_ambiental') }} %. Recuerda guardar fotos como evidencia.</p>
    @endif

    <section class="hoja">
        <header><h2>Proyecto de trabajo</h2>
            @if ($proyecto)<span class="pastilla {{ $estados[$proyecto->estado][0] }}">{{ $estados[$proyecto->estado][1] }}</span>@endif
            <span class="codigo">FBSSE-001</span>
        </header>
        @if ($proyecto)
            <p><b>{{ $proyecto->institucion }}</b></p>
            <p class="gris">Supervisa: {{ $proyecto->supervisor_nombre ?? '—' }} · Inicio: {{ $fecha($proyecto->fecha_inicio) }}</p>
        @else
            <p class="gris">Todavía no has llenado tu proyecto de trabajo.</p>
        @endif
    </section>

    <section class="hoja">
        <header><h2>Últimos registros</h2></header>
        <div class="desliza">
            <table>
                <thead><tr><th>Fecha</th><th>Actividad</th><th>Tipo</th><th class="num">Horas</th><th>Estado</th></tr></thead>
                <tbody>
                @forelse ($ultimos as $r)
                    <tr>
                        <td style="white-space:nowrap">{{ $fecha($r->fecha) }}</td>
                        <td>{{ $r->actividad }}</td>
                        <td>{{ $r->tipo === 'ambiental' ? 'Medio ambiente' : 'General' }}</td>
                        <td class="num">{{ $r->horas }}</td>
                        <td><span class="pastilla {{ $estados[$r->estado][0] }}">{{ $estados[$r->estado][1] }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="gris">Todavía no hay registros.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection

@section(config('horas-sociales.seccion', 'content'))
    @include('horas-sociales::partials.estilos')
    @include('horas-sociales::partials.marco', ['actual' => 'sse.panel'])
@endsection
