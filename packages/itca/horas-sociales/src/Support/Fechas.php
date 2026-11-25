<?php

namespace Itca\HorasSociales\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/**
 * Fechas del módulo.
 * "Hoy" se calcula con la zona horaria de El Salvador (config 'zona_horaria'), no con la
 * de la aplicación: si la app está en UTC, desde las 6:00 p. m. ya sería "mañana" (R4).
 */
class Fechas
{
    public static function zona(): string
    {
        return config('horas-sociales.zona_horaria') ?: config('app.timezone', 'UTC');
    }

    /** Hoy en El Salvador, como AAAA-MM-DD. */
    public static function hoy(): string
    {
        return CarbonImmutable::now(self::zona())->format('Y-m-d');
    }

    /** "18 de noviembre de 2026" */
    public static function larga($fecha): string
    {
        return Carbon::parse($fecha)->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }
}
