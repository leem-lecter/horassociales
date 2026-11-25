<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    /** Fotos de evidencia (R7). */
    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class, 'registro_id')->orderBy('id');
    }

    /** Registros que cuentan para el total: todos menos los no aprobados. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->where('estado', '!=', self::RECHAZADO);
    }

    /** R11: un registro aprobado no se edita ni se borra. */
    public function borrable(): bool
    {
        return $this->estado !== self::APROBADO;
    }

    /** Carpeta de sus fotos dentro del disco de evidencias. */
    public function carpetaEvidencias(): string
    {
        return "sse-evidencias/{$this->estudiante_id}/{$this->id}";
    }

    /**
     * Borra el registro, sus filas de evidencias y los archivos de las fotos.
     * Primero la base de datos (todo o nada) y al final los archivos: si algo falla,
     * no queda un registro apuntando a fotos que ya no existen.
     */
    public function borrarConFotos(): void
    {
        DB::transaction(function () {
            $this->evidencias()->delete();
            $this->delete();
        });

        Storage::disk(config('horas-sociales.disco_evidencias'))->deleteDirectory($this->carpetaEvidencias());
    }
}
