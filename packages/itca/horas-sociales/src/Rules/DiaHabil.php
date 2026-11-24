<?php

namespace Itca\HorasSociales\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Itca\HorasSociales\Models\PeriodoNoHabil;

/**
 * R5: la fecha no puede caer en un asueto ni en vacaciones.
 * Se usa en la fecha de inicio del proyecto (M3) y en la fecha del registro diario (M4).
 */
class DiaHabil implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $fecha = Carbon::parse($value);
        } catch (\Throwable) {
            return; // La regla 'date' ya avisa que la fecha no es válida.
        }

        $periodo = PeriodoNoHabil::para($fecha);

        if ($periodo) {
            $fail("El {$fecha->locale('es')->isoFormat('D [de] MMMM [de] YYYY')} es día no hábil ({$periodo->nombre}). Elige otra fecha.");
        }
    }
}
