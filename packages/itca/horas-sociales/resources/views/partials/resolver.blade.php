{{--
    Decisión del profesor (M6): comentario + Aprobar / No aprobar.
    Parámetros:
      accion    URL del POST (sse.revision.proyecto, .registro o .finalizacion)
      aprobar   texto del botón de aprobar, o false para no mostrarlo (por omisión "Aprobar")
      rechazar  texto del botón de no aprobar, o false (por omisión "No aprobar")
      confirmar mensaje de confirmación antes de enviar (opcional, para cambiar una decisión)
    El comentario es obligatorio al no aprobar: lo revisa la pantalla y también el servidor.
--}}
@php
    $txtAprobar = $aprobar ?? 'Aprobar';
    $txtRechazar = $rechazar ?? 'No aprobar';
@endphp
<form method="POST" action="{{ $accion }}" class="resolver" data-resolver
      @isset($confirmar) data-confirmar="{{ $confirmar }}" @endisset>
    @csrf
    <input name="comentario" maxlength="500" autocomplete="off"
           aria-label="Comentario para el estudiante"
           placeholder="{{ $txtRechazar ? 'Comentario (obligatorio si no apruebas)' : 'Comentario (opcional)' }}">
    <small class="error-resolver" role="alert" hidden>Escribe un comentario para que el estudiante sepa qué corregir.</small>
    <div class="fila" style="gap:6px">
        @if ($txtAprobar)
            <button class="boton chico" name="decision" value="aprobar">{{ $txtAprobar }}</button>
        @endif
        @if ($txtRechazar)
            <button class="boton plano chico" name="decision" value="rechazar">{{ $txtRechazar }}</button>
        @endif
    </div>
</form>
