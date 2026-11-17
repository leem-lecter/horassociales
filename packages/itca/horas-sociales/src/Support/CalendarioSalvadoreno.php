<?php

namespace Itca\HorasSociales\Support;

use DateTimeImmutable;

/**
 * Días en que no se pueden hacer horas sociales, según el calendario salvadoreño.
 *
 * - Asuetos nacionales del art. 190 del Código de Trabajo.
 * - Vacaciones completas de Semana Santa, agosto y fin de año (decisión del coordinador).
 *
 * No depende de Laravel: se puede probar con PHP solo.
 * Lo que devuelve se guarda en la tabla sse_periodos_no_habiles, donde el
 * profesor puede corregirlo o agregar otros periodos (fiestas patronales, etc.).
 */
class CalendarioSalvadoreno
{
    public const ASUETO = 'asueto';
    public const VACACIONES = 'vacaciones';

    /** Asuetos de fecha fija: [mes-día, nombre] */
    private const ASUETOS_FIJOS = [
        ['01-01', 'Año Nuevo'],
        ['05-01', 'Día del Trabajo'],
        ['05-10', 'Día de la Madre'],
        ['06-17', 'Día del Padre'],
        ['08-06', 'Fiestas del Divino Salvador del Mundo'],
        ['09-15', 'Día de la Independencia'],
        ['11-02', 'Día de los Difuntos'],
        ['12-25', 'Navidad'],
    ];

    /** Domingo de Pascua (algoritmo de Meeus/Jones/Butcher, calendario gregoriano). */
    public static function pascua(int $anio): DateTimeImmutable
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $dia));
    }

    /**
     * Periodos no hábiles de un año.
     *
     * @return list<array{nombre: string, tipo: string, fecha_inicio: string, fecha_fin: string}>
     */
    public static function periodos(int $anio): array
    {
        $periodos = [];

        foreach (self::ASUETOS_FIJOS as [$mesDia, $nombre]) {
            $fecha = "{$anio}-{$mesDia}";
            $periodos[] = self::periodo($nombre, self::ASUETO, $fecha, $fecha);
        }

        $pascua = self::pascua($anio);
        foreach ([[-3, 'Jueves Santo'], [-2, 'Viernes Santo'], [-1, 'Sábado Santo']] as [$dias, $nombre]) {
            $fecha = $pascua->modify("{$dias} days")->format('Y-m-d');
            $periodos[] = self::periodo($nombre, self::ASUETO, $fecha, $fecha);
        }

        // Vacaciones completas
        $periodos[] = self::periodo('Vacaciones de Semana Santa', self::VACACIONES,
            $pascua->modify('-6 days')->format('Y-m-d'),   // Lunes Santo
            $pascua->format('Y-m-d'));                     // Domingo de Resurrección
        $periodos[] = self::periodo('Vacaciones de agosto', self::VACACIONES, "{$anio}-08-01", "{$anio}-08-06");
        $periodos[] = self::periodo('Vacaciones de fin de año', self::VACACIONES, "{$anio}-12-24", ($anio + 1).'-01-01');

        usort($periodos, fn ($x, $y) => [$x['fecha_inicio'], $x['fecha_fin']] <=> [$y['fecha_inicio'], $y['fecha_fin']]);

        return $periodos;
    }

    /**
     * Periodo que cubre la fecha (Y-m-d), o null si es día hábil.
     * Si hay varios (Viernes Santo cae dentro de las vacaciones), devuelve el más corto,
     * que es el nombre más preciso para mostrar al estudiante.
     */
    public static function periodoQueCubre(string $fecha): ?array
    {
        $anio = (int) substr($fecha, 0, 4);
        // Incluye el año anterior por las vacaciones de fin de año que cruzan al 1 de enero.
        $cubren = array_filter(
            [...self::periodos($anio - 1), ...self::periodos($anio)],
            fn ($p) => $p['fecha_inicio'] <= $fecha && $fecha <= $p['fecha_fin'],
        );
        usort($cubren, fn ($x, $y) => (strtotime($x['fecha_fin']) - strtotime($x['fecha_inicio']))
            <=> (strtotime($y['fecha_fin']) - strtotime($y['fecha_inicio'])));

        return $cubren[0] ?? null;
    }

    private static function periodo(string $nombre, string $tipo, string $inicio, string $fin): array
    {
        return ['nombre' => $nombre, 'tipo' => $tipo, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin];
    }
}
