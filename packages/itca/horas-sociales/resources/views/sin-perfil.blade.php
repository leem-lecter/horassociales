@extends(config('horas-sociales.layout') ?: 'horas-sociales::layouts.modulo')

@section('titulo', 'Horas sociales')

@section('sse-contenido')
    <div><div class="cabecera"><h1>Horas sociales</h1></div></div>
    @if ($esProfesor)
        <p class="nota">Entraste como profesor. Revisa y aprueba lo que envían los estudiantes en <a href="{{ route('sse.revision') }}">Revisión de horas</a>.</p>
    @else
        <p class="nota mal"><b>Tu cuenta no tiene carnet.</b> Para registrar horas sociales, tu usuario necesita un carnet de estudiante. Pide al coordinador que lo agregue.</p>
    @endif
@endsection

@section(config('horas-sociales.seccion', 'content'))
    @include('horas-sociales::partials.estilos')
    @include('horas-sociales::partials.marco', ['actual' => 'sse.panel'])
@endsection
