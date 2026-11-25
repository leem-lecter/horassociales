<?php

namespace Itca\HorasSociales\Support;

use Itca\HorasSociales\Models\Registro;

/**
 * Columna "Hoja · fila": en qué hoja y renglón del FBSSE-002 en papel va cada registro.
 * Se calcula, no se guarda. Los registros llegan ordenados por fecha; los rechazados
 * no ocupan renglón. Con 20 renglones por hoja, el registro 21 queda en "H2 · 1".
 */
class HojaFila
{
    /**
     * @param  iterable<Registro>  $registros  de una sola pestaña, ordenados por fecha
     * @return array<int, string|null>  id del registro => "H1 · 3" (null si no fue aprobado)
     */
    public static function para(iterable $registros, ?int $filasPorHoja = null): array
    {
        $filasPorHoja ??= (int) config('horas-sociales.filas_por_hoja', 20);
        $n = 0;
        $resultado = [];

        foreach ($registros as $registro) {
            if ($registro->estado === Registro::RECHAZADO) {
                $resultado[$registro->id] = null;

                continue;
            }
            $n++;
            $hoja = intdiv($n - 1, $filasPorHoja) + 1;
            $fila = (($n - 1) % $filasPorHoja) + 1;
            $resultado[$registro->id] = "H{$hoja} · {$fila}";
        }

        return $resultado;
    }
}
