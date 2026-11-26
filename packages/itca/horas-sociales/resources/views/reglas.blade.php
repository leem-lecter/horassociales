@extends(config('horas-sociales.layout') ?: 'horas-sociales::layouts.modulo')

@section('titulo', 'Reglas del servicio social')

@section('sse-contenido')
    @php
        $metas = config('horas-sociales.metas');
        $maxDia = (int) config('horas-sociales.max_horas_por_dia');
        $porcentaje = config('horas-sociales.porcentaje_ambiental');
        $meses = (int) config('horas-sociales.plazo_entrega_meses');
        $tipos = ['asueto' => 'Asueto', 'vacaciones' => 'Vacaciones', 'otro' => 'Agregado por el coordinador'];
        // "lun 2 de noviembre"; el año solo si es distinto al del título (fin de año).
        $dia = fn ($f, $anio) => $f->locale('es')->isoFormat($f->year === (int) $anio ? 'ddd D [de] MMMM' : 'ddd D [de] MMMM [de] YYYY');
    @endphp

    <div>
        <div class="cabecera"><h1>Reglas del servicio social</h1></div>
        <p class="bajada">Resumen de los 25 puntos de la hoja de indicaciones. Este registro en línea no reemplaza las hojas en papel: te ayuda a llevar la cuenta.</p>
    </div>

    <section class="hoja"><header><h2>Lo que este formulario revisa por ti</h2></header>
        <ul class="lista">
            <li>Tu carrera pide {{ $metas['tecnico'] }} horas si es técnica (2 años) y {{ $metas['ingenieria'] }} si es ingeniería o carrera de 5 años.</li>
            <li>No puedes registrar horas antes de que el coordinador apruebe tu proyecto de trabajo (regla 3). Si lo editas después, necesita una nueva aprobación.</li>
            <li>Máximo {{ $maxDia }} horas por día, sumando todos tus registros (regla 16), y solo horas enteras (regla 17).</li>
            <li>Las fechas no pueden ser futuras ni anteriores al inicio de tu proyecto.</li>
            <li>No se hacen horas sociales en días no hábiles: asuetos nacionales y vacaciones. Tampoco puede empezar ahí tu proyecto. La lista está abajo.</li>
            <li>Al menos el {{ $porcentaje }} % de tus horas deben ser de medio ambiente, con fotos de evidencia (regla 21).</li>
            <li>La fecha de finalización debe ser tu último día registrado: las fechas coinciden en todos los formularios (regla 20).</li>
            <li>Tienes {{ $meses }} meses desde que terminas para entregar la documentación; la hoja de finalización te dice la fecha límite (regla 18).</li>
            <li>Un registro aprobado ya no se borra. Al enviar la hoja de finalización, el registro diario de ese proyecto se cierra.</li>
            <li>Solo el coordinador aprueba o devuelve tu proyecto, tus horas y tu hoja de finalización (regla 4).</li>
        </ul>
    </section>

    <section class="hoja"><header><h2>Días no hábiles</h2></header>
        <p class="gris" style="margin-bottom:12px">En estas fechas no se registran horas sociales. Son los asuetos nacionales y las vacaciones completas de Semana Santa, agosto y fin de año. El coordinador puede agregar otros periodos.</p>
        <div class="pila" style="gap:10px">
            @foreach ($porAnio as $anio => $lista)
                <details class="anio-reglas" @if ($anio === $actual) open @endif>
                    <summary>{{ $anio }}{{ $anio === $actual ? ' (este año)' : '' }} · {{ $lista->count() }} {{ $lista->count() === 1 ? 'periodo' : 'periodos' }}</summary>
                    @if ($lista->isEmpty())
                        <p class="gris" style="margin-top:8px">No hay días no hábiles cargados para {{ $anio }}.
                            @if ($esProfesor) Cárgalos con <code>php artisan sse:periodos {{ $anio }}</code>. @else Avísale a tu coordinador. @endif
                        </p>
                    @else
                        <div class="desliza" style="margin-top:8px">
                            <table>
                                <thead><tr><th>Fechas</th><th>Motivo</th><th>Tipo</th><th class="num">Días</th></tr></thead>
                                <tbody>
                                @foreach ($lista as $p)
                                    <tr>
                                        <td style="white-space:nowrap">{{ $p->fecha_inicio->isSameDay($p->fecha_fin) ? $dia($p->fecha_inicio, $anio) : 'del '.$dia($p->fecha_inicio, $anio).' al '.$dia($p->fecha_fin, $anio) }}</td>
                                        <td>{{ $p->nombre }}</td>
                                        <td>{{ $tipos[$p->tipo] ?? $p->tipo }}</td>
                                        <td class="num">{{ (int) $p->fecha_inicio->diffInDays($p->fecha_fin) + 1 }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </details>
            @endforeach
        </div>
    </section>

    <section class="hoja"><header><h2>Lo que sigue siendo en papel</h2></header>
        <ul class="lista">
            <li>Las hojas oficiales se llenan a mano, con lapicero de tinta azul y por un solo lado.</li>
            <li>Sin tachones, letras repintadas, corrector, manchas, arrugas ni dobleces. Tampoco lápiz repintado.</li>
            <li>Firmas y sellos originales, en azul. No se aceptan escaneados.</li>
            <li>La carta de solicitud (FBSSE-004) se sigue entregando en papel.</li>
            <li>Solo el coordinador recibe la documentación, completa, firmada y sellada.</li>
            <li>El servicio de medio ambiente es obligatorio en el primer ciclo de la carrera.</li>
            <li>Puedes hacerlo en varios lugares, uno a la vez, con una solicitud aprobada por cada lugar.</li>
            <li>No puede coincidir con tus fechas y horas de clases.</li>
        </ul>
    </section>
@endsection

@section(config('horas-sociales.seccion', 'content'))
    @include('horas-sociales::partials.estilos')
    @include('horas-sociales::partials.marco', ['actual' => 'sse.reglas'])
@endsection
