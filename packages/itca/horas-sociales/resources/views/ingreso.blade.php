{{-- SOLO PARA EL PROYECTO DE PRUEBAS. La aplicación del profesor usa su propio inicio de sesión. --}}
@extends('horas-sociales::layouts.modulo')

@section('titulo', 'Ingresar')

@section(config('horas-sociales.seccion', 'content'))
    @include('horas-sociales::partials.estilos')
    <main class="ingreso">
        <form class="hoja" method="POST" action="{{ route('sse.ingreso.enviar') }}">
            @csrf
            <div>
                <span class="codigo">SSE · ITCA-FEPADE · PRUEBAS</span>
                <h1 style="margin-top:10px">Registro de horas sociales</h1>
                <p class="gris" style="margin-top:6px">Entra con tu carnet para registrar tu servicio social y ver cuántas horas llevas.</p>
            </div>
            <label class="campo"><span>Carnet</span>
                <input name="carnet" id="c-carnet" value="{{ old('carnet') }}" autocomplete="username" inputmode="numeric" required class="{{ $errors->has('carnet') ? 'con-error' : '' }}">
            </label>
            <label class="campo"><span>Contraseña</span>
                <input name="password" id="c-password" type="password" autocomplete="current-password" required>
            </label>
            @if ($errors->any())
                <p class="nota mal">{{ $errors->first() }}</p>
            @endif
            <button class="boton">Entrar</button>
            <div class="demo">
                <span class="gris">Cuentas de prueba (contraseña <b>demo1234</b>):</span>
                <span>Estudiantes: 251234, 250042, 249876 · Profesor: coordinador</span>
            </div>
        </form>
    </main>
@endsection
