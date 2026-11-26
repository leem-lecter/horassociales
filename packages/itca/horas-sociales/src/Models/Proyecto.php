<?php

namespace Itca\HorasSociales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/** FBSSE-001: proyecto de trabajo del estudiante. */
class Proyecto extends Model
{
    public const BORRADOR = 'borrador';
    public const ENVIADO = 'enviado';
    public const APROBADO = 'aprobado';
    public const RECHAZADO = 'rechazado';

    protected $table = 'sse_proyectos';

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'declaracion_aceptada' => 'boolean',
            'enviado_at' => 'datetime',
            'resuelto_at' => 'datetime',
        ];
    }

    public function estudiante(): BelongsTo
    {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }

    public function objetivos(): HasMany
    {
        return $this->hasMany(ProyectoObjetivo::class, 'proyecto_id')->orderBy('orden');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(ProyectoHorario::class, 'proyecto_id');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(Registro::class, 'proyecto_id');
    }

    public function finalizacion(): HasOne
    {
        return $this->hasOne(Finalizacion::class, 'proyecto_id');
    }

    /** Regla 3: solo con el proyecto aprobado se pueden registrar horas. */
    public function permiteRegistrarHoras(): bool
    {
        return $this->estado === self::APROBADO;
    }

    /**
     * R13: con la hoja de finalización enviada o aprobada ya no se agregan ni se borran
     * registros, para que la fecha de finalización (R8) no cambie. Consulta la base de
     * datos cada vez, así sirve también dentro de una transacción.
     */
    public function registroCerrado(): bool
    {
        return $this->finalizacion()
            ->whereIn('estado', [Finalizacion::ENVIADO, Finalizacion::APROBADO])
            ->exists();
    }

    /** R8: fecha (AAAA-MM-DD) del último registro que cuenta, o null si no hay ninguno. */
    public function ultimoDiaRegistrado(): ?string
    {
        $fecha = $this->registros()->vigentes()->max('fecha');

        return $fecha ? substr((string) $fecha, 0, 10) : null;
    }

    /** Mientras está enviado o aprobado, el estudiante no lo puede editar. */
    public function editable(): bool
    {
        return in_array($this->estado, [self::BORRADOR, self::RECHAZADO], true);
    }
}
