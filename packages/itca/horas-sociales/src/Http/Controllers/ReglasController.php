<?php

namespace Itca\HorasSociales\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Itca\HorasSociales\Models\PeriodoNoHabil;
use Itca\HorasSociales\Support\Fechas;

/** Pantalla de Reglas: la ven el estudiante y el profesor. */
class ReglasController extends Controller
{
    public function index()
    {
        $actual = (int) substr(Fechas::hoy(), 0, 4);
        $periodos = PeriodoNoHabil::orderBy('fecha_inicio')->orderBy('fecha_fin')->get();

        // Años con periodos cargados, más el actual y el siguiente (para avisar si faltan).
        $anios = $periodos->flatMap(fn (PeriodoNoHabil $p) => [$p->fecha_inicio->year, $p->fecha_fin->year])
            ->merge([$actual, $actual + 1])
            ->unique()
            ->filter(fn ($anio) => $anio >= $actual - 1)   // los años viejos ya no sirven
            ->sort()
            ->values();

        return view('horas-sociales::reglas', [
            'esProfesor' => Gate::allows('sse-revisar'),
            'actual' => $actual,
            // Un periodo de fin de año (24 dic al 1 ene) aparece en los dos años.
            'porAnio' => $anios->mapWithKeys(fn ($anio) => [
                $anio => $periodos->filter(fn (PeriodoNoHabil $p) => $p->fecha_inicio->year <= $anio && $p->fecha_fin->year >= $anio)->values(),
            ]),
        ]);
    }
}
