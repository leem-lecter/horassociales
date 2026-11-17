<?php

namespace Itca\HorasSociales\Console;

use Illuminate\Console\Command;
use Itca\HorasSociales\Models\PeriodoNoHabil;
use Itca\HorasSociales\Support\CalendarioSalvadoreno;

/**
 * php artisan sse:periodos           -> año actual y siguiente
 * php artisan sse:periodos 2027      -> solo 2027
 *
 * Se puede correr cuantas veces se quiera: actualiza sin duplicar
 * y no toca los periodos que el profesor agregó a mano.
 */
class GenerarPeriodosNoHabiles extends Command
{
    protected $signature = 'sse:periodos {anio? : Año a generar (por defecto, el actual y el siguiente)}';

    protected $description = 'Genera los asuetos y vacaciones de El Salvador en los que no se hacen horas sociales';

    public function handle(): int
    {
        $anios = $this->argument('anio')
            ? [(int) $this->argument('anio')]
            : [(int) now()->year, (int) now()->year + 1];

        foreach ($anios as $anio) {
            $periodos = CalendarioSalvadoreno::periodos($anio);

            foreach ($periodos as $p) {
                $periodo = PeriodoNoHabil::where('nombre', $p['nombre'])
                    ->whereDate('fecha_inicio', $p['fecha_inicio'])
                    ->first() ?? new PeriodoNoHabil(['nombre' => $p['nombre'], 'fecha_inicio' => $p['fecha_inicio']]);

                $periodo->fill(['tipo' => $p['tipo'], 'fecha_fin' => $p['fecha_fin'], 'origen' => 'automatico'])->save();
            }

            $this->info("{$anio}: ".count($periodos).' periodos listos.');
        }

        return self::SUCCESS;
    }
}
