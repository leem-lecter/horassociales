{{--
    Marco del módulo: barra lateral + contenido.
    Uso: @include('horas-sociales::partials.marco', ['actual' => 'sse.panel'])
         y el contenido de la pantalla en @section('sse-contenido').
--}}
@php
    $esProfesor = \Illuminate\Support\Facades\Gate::allows('sse-revisar');
    // [ruta, título, código]. Las que aún no existen se muestran como pendientes.
    $enlaces = $esProfesor
        ? [['sse.revision', 'Revisión de horas', 'Vista del profesor'], ['sse.reglas', 'Reglas', 'Reglas del SSE']]
        : [
            ['sse.panel', 'Mi avance', 'Resumen'],
            ['sse.proyecto', 'Proyecto de trabajo', 'FBSSE-001'],
            ['sse.registro', 'Registro diario', 'FBSSE-002'],
            ['sse.finalizacion', 'Finalización', 'FBSSE-003'],
            ['sse.reglas', 'Reglas', 'Reglas del SSE'],
        ];
    $usuario = auth()->user();
@endphp
<div class="marco">
    <aside class="lateral">
        <div class="marca"><b>Horas sociales</b><span>Servicio Social Estudiantil · ITCA-FEPADE</span></div>
        <nav class="nav" aria-label="Secciones">
            @foreach ($enlaces as [$ruta, $titulo, $codigo])
                @if (\Illuminate\Support\Facades\Route::has($ruta))
                    <a href="{{ route($ruta) }}" @if ($ruta === $actual) aria-current="page" @endif>{{ $titulo }}<small>{{ $codigo }}</small></a>
                @else
                    <span class="pendiente" title="Se construye en un paso siguiente del plan">{{ $titulo }}<small>{{ $codigo }} · pendiente</small></span>
                @endif
            @endforeach
        </nav>
        <div class="quien">
            <div>
                <b>{{ $usuario->name ?? 'Usuario' }}</b><br>
                <span class="gris">{{ $esProfesor ? 'Profesor' : 'Carnet '.($usuario->{config('horas-sociales.campo_carnet')} ?? '—') }}</span>
            </div>
            @if (\Illuminate\Support\Facades\Route::has('sse.salir'))
                <form method="POST" action="{{ route('sse.salir') }}">
                    @csrf
                    <button class="boton plano chico">Salir</button>
                </form>
            @endif
        </div>
    </aside>
    <main class="principal">
        <div class="pila">
            @yield('sse-contenido')
        </div>
    </main>
</div>
