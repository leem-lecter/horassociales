<?php

namespace Itca\HorasSociales\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/** Carga los asuetos y vacaciones del año pasado, el actual y el siguiente. */
class PeriodosNoHabilesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([now()->year - 1, now()->year, now()->year + 1] as $anio) {
            Artisan::call('sse:periodos', ['anio' => $anio]);
        }
    }
}
