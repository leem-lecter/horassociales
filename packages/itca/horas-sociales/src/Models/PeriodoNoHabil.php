<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Asueto o vacaciones: días en que no se hacen horas sociales. */
class PeriodoNoHabil extends Model
{
    public const ASUETO = 'asueto';
    public const VACACIONES = 'vacaciones';
    public const OTRO = 'otro';

    protected $table = 'sse_periodos_no_habiles';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function scopeQueCubren(Builder $query, $fecha): Builder
    {
        $dia = $fecha instanceof \DateTimeInterface ? $fecha->format('Y-m-d') : (string) $fecha;

        return $query->whereDate('fecha_inicio', '<=', $dia)->whereDate('fecha_fin', '>=', $dia);
    }

    /**
     * El periodo que vuelve no hábil una fecha, o null si se puede trabajar.
     * Si hay varios, devuelve el más corto (Viernes Santo antes que "Vacaciones de Semana Santa").
     */
    public static function para($fecha): ?self
    {
        return static::queCubren($fecha)
            ->get()
            ->sortBy(fn (self $p) => $p->fecha_inicio->diffInDays($p->fecha_fin))
            ->first();
    }
}
