<?php

namespace Itca\HorasSociales\Support;

use Itca\HorasSociales\Models\Estudiante;
use Itca\HorasSociales\Models\Registro;

/** Cifras del panel: horas aprobadas, en revisión, faltantes y de medio ambiente. */
class Resumen
{
    public function __construct(
        public readonly ?int $meta,
        public readonly int $aprobadas,
        public readonly int $pendientes,
        public readonly int $faltan,
        public readonly ?int $metaAmbiental,
        public readonly int $ambientalAprobadas,
        public readonly int $ambientalPendientes,
    ) {}

    public static function de(Estudiante $estudiante): self
    {
        $sumas = $estudiante->registros()
            ->selectRaw('estado, tipo, SUM(horas) AS total')
            ->groupBy('estado', 'tipo')
            ->get();

        $suma = fn (string $estado, ?string $tipo = null) => (int) $sumas
            ->where('estado', $estado)
            ->when($tipo, fn ($c) => $c->where('tipo', $tipo))
            ->sum('total');

        $meta = $estudiante->meta();
        $aprobadas = $suma(Registro::APROBADO);

        return new self(
            meta: $meta,
            aprobadas: $aprobadas,
            pendientes: $suma(Registro::PENDIENTE),
            faltan: $meta ? max(0, $meta - $aprobadas) : 0,
            metaAmbiental: $meta ? (int) ceil($meta * config('horas-sociales.porcentaje_ambiental') / 100) : null,
            ambientalAprobadas: $suma(Registro::APROBADO, Registro::AMBIENTAL),
            ambientalPendientes: $suma(Registro::PENDIENTE, Registro::AMBIENTAL),
        );
    }

    /** Porcentaje del ancho de la barra de avance (0 a 100). */
    public function porcentaje(int $horas): float
    {
        return $this->meta ? min(100, round(100 * $horas / $this->meta, 2)) : 0;
    }

    public function faltaAmbiental(): int
    {
        return $this->metaAmbiental ? max(0, $this->metaAmbiental - $this->ambientalAprobadas - $this->ambientalPendientes) : 0;
    }
}
