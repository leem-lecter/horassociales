{{--
    Un campo de formulario con su etiqueta y su error, igual que campo() del prototipo.
    Uso: @include('horas-sociales::partials.campo', ['name' => 'correo', 'label' => 'Correo', 'valor' => $x, 'tipo' => 'email'])
    Opcionales: tipo (text, email, tel, date, area), opciones (valor => texto), cls, ayuda, req, dis.
    numerico => N: solo acepta números, hasta N cifras (teléfonos, años).
--}}
@php
    $tipo = $tipo ?? 'text';
    $valor = old($name, $valor ?? '');
    if ($valor instanceof \DateTimeInterface) {
        $valor = $valor->format('Y-m-d');
    }
    $error = $errors->first($name);
    $id = 'c-'.$name;
@endphp
<label class="campo {{ $cls ?? '' }}" for="{{ $id }}">
    <span>{{ $label }}</span>
    @if ($tipo === 'area')
        <textarea name="{{ $name }}" id="{{ $id }}" @required($req ?? false) @disabled($dis ?? false)>{{ $valor }}</textarea>
    @elseif (isset($opciones))
        <select name="{{ $name }}" id="{{ $id }}" @required($req ?? false) @disabled($dis ?? false)>
            <option value="">Elige una opción</option>
            @foreach ($opciones as $opValor => $opTexto)
                <option value="{{ $opValor }}" @selected((string) $valor === (string) $opValor)>{{ $opTexto }}</option>
            @endforeach
        </select>
    @elseif (isset($numerico))
        {{-- Solo cifras: el teclado del celular muestra números y el script del formulario borra cualquier otra tecla --}}
        <input name="{{ $name }}" id="{{ $id }}" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="{{ $numerico }}"
               data-solo-numeros value="{{ preg_replace('/\D/', '', (string) $valor) }}"
               @required($req ?? false) @disabled($dis ?? false) @class(['con-error' => $error])>
    @else
        <input name="{{ $name }}" id="{{ $id }}" type="{{ $tipo }}" value="{{ $valor }}"
               @required($req ?? false) @disabled($dis ?? false) @class(['con-error' => $error])>
    @endif
    @if ($error)
        <small class="error">{{ $error }}</small>
    @elseif (! empty($ayuda))
        <small>{{ $ayuda }}</small>
    @endif
</label>
