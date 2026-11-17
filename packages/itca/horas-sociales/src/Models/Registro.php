<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** FBSSE-002: un día de servicio social. */
class Registro extends Model
{
    public const GENERAL = 'general';
    public const AMBIENTAL = 'ambiental';

    public const PENDIENTE = 'pendiente';
    public const APROBADO = 'aprobado';
    public const RECHAZADO = 'rechazado';

    protected $table = 'sse_registros';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'horas' => 'integer',
            'resuelto_at' => 'datetime',
        ];
    }

    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class, 'proyecto_id');
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    /** Registros que cuentan para el total: todos menos los no aprobados. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('estado', '!=', self::RECHAZADO);
    }
}
