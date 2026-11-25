{{--
    "Ver días no hábiles": lista desplegable de asuetos y vacaciones, debajo de cada campo de fecha.
    Uso: @include('horas-sociales::partials.dias-no-habiles', ['periodos' => $periodos, 'anios' => [2026, 2027]])
    periodos: [nombre, inicio, fin] en AAAA-MM-DD (PeriodoNoHabil::paraPantalla()).
    La pantalla de Reglas (M5) tendrá el calendario completo.
--}}
@php
    // Se agrega el año solo si es distinto al del título (vacaciones de fin de año).
    $dia = fn (string $f, $anio) => \Illuminate\Support\Carbon::parse($f)->locale('es')
        ->isoFormat((int) substr($f, 0, 4) === (int) $anio ? 'ddd D [de] MMMM' : 'ddd D [de] MMMM [de] YYYY');
    $porAnio = collect($anios)->mapWithKeys(fn ($anio) => [
        $anio => collect($periodos)->filter(fn ($p) => (int) substr($p[1], 0, 4) === (int) $anio || (int) substr($p[2], 0, 4) === (int) $anio)->values(),
    ]);
@endphp
<details class="dias-no-habiles">
    <summary>Ver días no hábiles</summary>
    @foreach ($porAnio as $anio => $lista)
        <p><b>{{ $anio }}</b></p>
        @if ($lista->isEmpty())
            <p class="gris">No hay días no hábiles cargados para {{ $anio }}. Avísale a tu coordinador.</p>
        @else
            <ul>
                @foreach ($lista as [$nombre, $inicio, $fin])
                    <li>{{ $nombre }} <span class="gris">· {{ $inicio === $fin ? $dia($inicio, $anio) : 'del '.$dia($inicio, $anio).' al '.$dia($fin, $anio) }}</span></li>
                @endforeach
            </ul>
        @endif
    @endforeach
</details>
